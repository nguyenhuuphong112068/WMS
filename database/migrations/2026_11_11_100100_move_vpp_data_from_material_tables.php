<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CHUYỂN DỮ LIỆU VPP từ bộ bảng Vật Tư (material_*) sang bộ bảng vpp_*.
 *
 * VPP = danh mục vật tư công ty có Bộ Phận Mua Hàng = Hành Chánh (material_categories.purchasing_department
 * = 'admin'). Cùng với danh mục, mọi dữ liệu nghiệp vụ của các mã đó được CHUYỂN (giữ nguyên id) sang
 * bảng vpp_* tương ứng: danh mục phòng, lô nhập + lịch sử + file, cân đối, sử dụng, cách ly, hiệu chuẩn,
 * kiểm kê, soạn hàng, đề nghị cấp phát, đề nghị liên phòng ban (gồm đề nghị VPP gửi Hành Chính), dự trù,
 * danh sách cần dự trù, danh sách đề nghị theo chu kỳ.
 *
 * Phiếu đầu (đề nghị, chuyển, dự trù, kiểm kê, đợt soạn, DS chu kỳ) đi theo dòng: phiếu có dòng VPP được
 * chép sang bảng vpp_* (cùng id, kèm chữ ký / lịch sử); phiếu không còn dòng Vật Tư nào thì xoá khỏi bảng
 * material_*. Phiếu lẫn cả hai loại (dữ liệu cũ) tách thành hai phiếu cùng số ở hai module.
 *
 * Cấu hình trình ký: Trình Ký Đề Nghị CP Vật Tư chép sang VPP làm cấu hình ban đầu (sửa riêng sau);
 * bước ký liên phòng ban chép sang VPP, bước loại "vpp" (đề nghị gửi Hành Chính) bỏ khỏi Vật Tư.
 *
 * Không hoàn tác tự động (down() để trống) - khôi phục từ bản sao lưu CSDL nếu cần.
 */
return new class extends Migration
{
    private const CHUNK = 500;

    public function up(): void
    {
        if (! Schema::hasTable('vpp_categories')) {
            return;
        }

        DB::transaction(function () {
            $this->copyConfig();

            $categoryIds = DB::table('material_categories')->where('purchasing_department', 'admin')->pluck('id')->all();
            if (! $categoryIds) {
                return;
            }

            // Danh mục công ty + lịch sử + danh mục phòng
            $this->move('categories', fn ($q) => $q->whereIn('id', $categoryIds));
            $this->move('category_histories', fn ($q) => $q->whereIn('material_category_id', $categoryIds));
            $this->move('department_categories', fn ($q) => $q->whereIn('category_id', $categoryIds));

            if (Schema::hasTable('category_unit_conversions')) {
                DB::table('category_unit_conversions')->where('category_type', 'material')
                    ->whereIn('category_id', $categoryIds)->update(['category_type' => 'vpp']);
            }

            // Lô nhập và mọi thứ treo theo lô
            $importIds = $this->move('imports', fn ($q) => $q->whereIn('category_id', $categoryIds));
            $this->move('import_histories', fn ($q) => $q->whereIn('material_import_id', $importIds));
            $this->move('import_attachments', fn ($q) => $q->whereIn('material_import_id', $importIds));
            $this->move('balancings', fn ($q) => $q->whereIn('import_id', $importIds));
            $exportIds = $this->move('exports', fn ($q) => $q->whereIn('import_id', $importIds));
            $this->move('export_histories', fn ($q) => $q->whereIn('material_export_id', $exportIds));
            $this->move('quarantines', fn ($q) => $q->whereIn('import_id', $importIds));

            $calibrationIds = $this->move('calibrations', fn ($q) => $q->whereIn('category_id', $categoryIds));
            $this->move('calibration_units', fn ($q) => $q->whereIn('calibration_id', $calibrationIds));

            $this->move('watchlist_items', fn ($q) => $q->whereIn('category_id', $categoryIds));

            // Kiểm kê
            $this->moveWithHeader('stocktake_items', 'stocktake_id', 'stocktakes', $categoryIds);

            // Đợt soạn hàng
            $this->moveWithHeader('pick_lines', 'wave_id', 'pick_waves', $categoryIds);

            // Đề nghị cấp phát nội bộ (+ chữ ký + danh sách người được ký)
            [$copied, $removed] = $this->moveWithHeader('request_items', 'request_list_id', 'request_lists', $categoryIds);
            $signIds = DB::table('material_request_signs')->whereIn('request_list_id', $copied)->pluck('id')->all();
            $this->copy('request_signs', fn ($q) => $q->whereIn('request_list_id', $copied));
            $this->copy('request_sign_candidates', fn ($q) => $q->whereIn('sign_id', $signIds));
            $removedSignIds = DB::table('material_request_signs')->whereIn('request_list_id', $removed)->pluck('id')->all();
            $this->deleteIn('material_request_sign_candidates', 'sign_id', $removedSignIds);
            $this->deleteIn('material_request_signs', 'request_list_id', $removed);

            // Đề nghị chuyển liên phòng ban / đề nghị VPP gửi Hành Chính (+ chữ ký)
            [$copied, $removed] = $this->moveWithHeader('transfer_items', 'transfer_request_id', 'transfer_requests', $categoryIds);
            $this->copy('transfer_signs', fn ($q) => $q->whereIn('transfer_request_id', $copied));
            $this->deleteIn('material_transfer_signs', 'transfer_request_id', $removed);

            // Dự trù (+ số lượng từng tháng, file từng mục, trao đổi, chữ ký, lịch sử)
            $itemIds = DB::table('material_estimate_items')->whereIn('category_id', $categoryIds)->pluck('id')->all();
            [$copied, $removed] = $this->moveWithHeader('estimate_items', 'material_estimate_id', 'estimates', $categoryIds);
            $this->move('estimate_item_amounts', fn ($q) => $q->whereIn('material_estimate_item_id', $itemIds));
            $this->move('estimate_attachments', fn ($q) => $q->whereIn('material_estimate_item_id', $itemIds));
            $this->move('estimate_attachments', fn ($q) => $q->whereNull('material_estimate_item_id')->whereIn('material_estimate_id', $removed));
            $this->copy('estimate_signs', fn ($q) => $q->whereIn('material_estimate_id', $copied));
            $this->copy('estimate_histories', fn ($q) => $q->whereIn('material_estimate_id', $copied));
            $this->deleteIn('material_estimate_signs', 'material_estimate_id', $removed);
            $this->deleteIn('material_estimate_histories', 'material_estimate_id', $removed);

            if ($itemIds && Schema::hasTable('estimate_item_chats')) {
                foreach (array_chunk($itemIds, self::CHUNK) as $chunk) {
                    DB::table('estimate_item_chats')->where('item_type', 'material')->whereIn('item_id', $chunk)->update(['item_type' => 'vpp']);
                }
            }

            // Danh sách đề nghị theo chu kỳ (+ lịch sử)
            [$copied, $removed] = $this->moveWithHeader('periodic_request_item', 'periodic_request_list_id', 'periodic_request_list', $categoryIds);
            $this->copy('periodic_request_list_histories', fn ($q) => $q->whereIn('periodic_request_list_id', $copied));
            $this->deleteIn('material_periodic_request_list_histories', 'periodic_request_list_id', $removed);
        });
    }

    public function down(): void
    {
        // Không hoàn tác tự động - xem ghi chú đầu file.
    }

    /** Cấu hình trình ký: chép nguyên sang VPP; bước ký loại "vpp" bỏ khỏi Vật Tư. */
    private function copyConfig(): void
    {
        foreach (['request_sign_rules', 'request_sign_rule_users', 'transfer_sign_steps'] as $suffix) {
            if (! DB::table('vpp_'.$suffix)->exists()) {
                $this->copy($suffix, fn ($q) => $q);
            }
        }

        DB::table('material_transfer_sign_steps')->where('kind', 'vpp')->delete();
    }

    /**
     * Chép các dòng chi tiết có category_id thuộc VPP sang vpp_*, chép phiếu đầu chứa chúng (cùng id) và
     * xoá phiếu đầu khỏi material_* khi không còn dòng nào ở lại.
     *
     * @return array{0: int[], 1: int[]} [id phiếu đầu đã chép, id phiếu đầu đã xoá khỏi material_*]
     */
    private function moveWithHeader(string $itemSuffix, string $headerKey, string $headerSuffix, array $categoryIds): array
    {
        $headerIds = DB::table('material_'.$itemSuffix)->whereIn('category_id', $categoryIds)
            ->whereNotNull($headerKey)->distinct()->pluck($headerKey)->map(fn ($id) => (int) $id)->all();

        $this->move($itemSuffix, fn ($q) => $q->whereIn('category_id', $categoryIds));

        if (! $headerIds) {
            return [[], []];
        }

        $this->copy($headerSuffix, fn ($q) => $q->whereIn('id', $headerIds));

        $remaining = DB::table('material_'.$itemSuffix)->whereIn($headerKey, $headerIds)
            ->distinct()->pluck($headerKey)->map(fn ($id) => (int) $id)->all();
        $removed = array_values(array_diff($headerIds, $remaining));
        $this->deleteIn('material_'.$headerSuffix, 'id', $removed);

        return [$headerIds, $removed];
    }

    /** Chép rồi xoá khỏi material_*; trả về id các dòng đã chuyển. */
    private function move(string $suffix, Closure $scope): array
    {
        $ids = $this->copy($suffix, $scope);
        $this->deleteIn('material_'.$suffix, 'id', $ids);

        return $ids;
    }

    /** Chép (giữ nguyên id) các dòng material_<suffix> thoả $scope sang vpp_<suffix>; đổi tên cột material_* -> vpp_*. */
    private function copy(string $suffix, Closure $scope): array
    {
        $from = 'material_'.$suffix;
        $to = 'vpp_'.$suffix;
        $ids = $scope(DB::table($from))->orderBy('id')->pluck('id')->map(fn ($id) => (int) $id)->all();

        foreach (array_chunk($ids, self::CHUNK) as $chunk) {
            $rows = DB::table($from)->whereIn('id', $chunk)->orderBy('id')->get()->map(function ($row) {
                $out = [];
                foreach ((array) $row as $column => $value) {
                    $isOwn = str_starts_with($column, 'material_') && ! in_array($column, ['material_names_id', 'material_name'], true);
                    $out[$isOwn ? 'vpp_'.substr($column, 9) : $column] = $value;
                }

                return $out;
            })->all();

            $existing = DB::table($to)->whereIn('id', $chunk)->pluck('id')->map(fn ($id) => (int) $id)->all();
            $rows = array_values(array_filter($rows, fn ($row) => ! in_array((int) $row['id'], $existing, true)));

            if ($rows) {
                DB::table($to)->insert($rows);
            }
        }

        return $ids;
    }

    private function deleteIn(string $table, string $column, array $ids): void
    {
        foreach (array_chunk($ids, self::CHUNK) as $chunk) {
            DB::table($table)->whereIn($column, $chunk)->delete();
        }
    }
};
