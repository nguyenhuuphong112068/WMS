<?php

use App\Support\MaterDataPermissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * DỮ LIỆU GỐC "TÊN VPP" - tách tên văn phòng phẩm khỏi Tên Vật Tư (16/11/2026).
 *
 * - Bảng vpp_names cùng cấu trúc material_names; Danh Mục VPP công ty trỏ về đây:
 *   vpp_categories.material_names_id / vpp_category_histories.material_names_id -> vpp_names_id.
 * - Tên chuyển sang vpp_names (giữ nguyên id): mọi tên Danh Mục VPP đang / từng dùng + toàn bộ tên VPP nạp
 *   từ DanhMucVPPALL (created_by 'Import DanhMucVPPALL (system)') mà Danh Mục Vật Tư chưa dùng.
 *   Tên Danh Mục Vật Tư cũng đang dùng thì chép sang (giữ ở cả hai); tên còn lại xoá khỏi material_names.
 * - Lịch sử thay đổi (datamaster_histories) đi theo tên: chuyển (hoặc chép) sang table_name vpp_names,
 *   nhãn "Tên vật tư" trong ảnh chụp / mô tả đổi thành "Tên VPP".
 * - Quyền: nhóm materData_vpp_review (chép role từ materData_material_review) + quyền con
 *   materData_vpp_{view,manage,review}_vppName (MaterDataPermissions::seed).
 *
 * Chạy trên cả wms và wms_nm1.
 */
return new class extends Migration
{
    private const IMPORT_MARK = 'Import DanhMucVPPALL (system)';

    public function up(): void
    {
        if (! Schema::hasTable('vpp_names')) {
            Schema::create('vpp_names', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('app_status', 20)->default('pending');
                $table->tinyInteger('status_id')->default(1);
                $table->string('created_by')->nullable();
                $table->string('updated_by')->nullable();
                $table->string('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
            });
        }

        $vppUsed = $this->usedIds('vpp_categories', 'vpp_category_histories', 'material_names_id');
        $materialUsed = $this->usedIds('material_categories', 'material_category_histories', 'material_names_id');
        $imported = DB::table('material_names')->where('created_by', self::IMPORT_MARK)->pluck('id')->all();

        $moveIds = array_values(array_unique(array_merge($vppUsed, array_diff($imported, $materialUsed))));
        $sharedIds = array_values(array_intersect($moveIds, $materialUsed));
        $exclusiveIds = array_values(array_diff($moveIds, $sharedIds));

        DB::transaction(function () use ($moveIds, $sharedIds, $exclusiveIds) {
            foreach (array_chunk($moveIds, 500) as $chunk) {
                $rows = DB::table('material_names')->whereIn('id', $chunk)->get()
                    ->map(fn ($r) => (array) $r)->all();
                DB::table('vpp_names')->insertOrIgnore($rows);
            }

            // Lịch sử: tên chỉ VPP dùng -> chuyển hẳn; tên dùng chung -> chép một bản cho vpp_names
            foreach (array_chunk($exclusiveIds, 500) as $chunk) {
                DB::table('datamaster_histories')->where('table_name', 'material_names')->whereIn('record_id', $chunk)
                    ->update(['table_name' => 'vpp_names']);
            }
            foreach (array_chunk($sharedIds, 500) as $chunk) {
                $copies = DB::table('datamaster_histories')->where('table_name', 'material_names')->whereIn('record_id', $chunk)
                    ->orderBy('id')->get()
                    ->map(fn ($r) => ['table_name' => 'vpp_names'] + array_diff_key((array) $r, ['id' => 1, 'table_name' => 1]))
                    ->all();
                if ($copies) {
                    DB::table('datamaster_histories')->insert($copies);
                }
            }
            DB::table('datamaster_histories')->where('table_name', 'vpp_names')->update([
                'snapshot' => DB::raw("REPLACE(snapshot, '\"Tên vật tư\"', '\"Tên VPP\"')"),
                'change_note' => DB::raw("REPLACE(change_note, 'Tên vật tư', 'Tên VPP')"),
            ]);

            foreach (array_chunk($exclusiveIds, 500) as $chunk) {
                DB::table('material_names')->whereIn('id', $chunk)->delete();
            }
        });

        foreach (['vpp_categories', 'vpp_category_histories'] as $table) {
            if (Schema::hasColumn($table, 'material_names_id')) {
                Schema::table($table, fn (Blueprint $t) => $t->renameColumn('material_names_id', 'vpp_names_id'));
            }
        }

        $this->seedPermissions();
    }

    public function down(): void
    {
        foreach (['vpp_categories', 'vpp_category_histories'] as $table) {
            if (Schema::hasColumn($table, 'vpp_names_id')) {
                Schema::table($table, fn (Blueprint $t) => $t->renameColumn('vpp_names_id', 'material_names_id'));
            }
        }

        if (Schema::hasTable('vpp_names')) {
            DB::transaction(function () {
                // Tên VPP trả về material_names giữ id; trùng tên với tên vật tư đã có thì bỏ qua
                foreach (DB::table('vpp_names')->orderBy('id')->get() as $row) {
                    $existing = DB::table('material_names')->where('id', $row->id)->exists();
                    if (! $existing && ! DB::table('material_names')->where('name', $row->name)->exists()) {
                        DB::table('material_names')->insert((array) $row);
                        DB::table('datamaster_histories')->where('table_name', 'vpp_names')->where('record_id', $row->id)
                            ->update([
                                'table_name' => 'material_names',
                                'snapshot' => DB::raw("REPLACE(snapshot, '\"Tên VPP\"', '\"Tên vật tư\"')"),
                                'change_note' => DB::raw("REPLACE(change_note, 'Tên VPP', 'Tên vật tư')"),
                            ]);
                    }
                }
                DB::table('datamaster_histories')->where('table_name', 'vpp_names')->delete();
            });
            Schema::drop('vpp_names');
        }

        $ids = DB::table('permissions')->where('name', 'like', 'materData\_vpp\_%\_vppName')
            ->orWhere('name', 'materData_vpp_review')->pluck('id');
        DB::table('role_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('user_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }

    /** id tên mà bảng danh mục + bảng lịch sử danh mục đang / từng trỏ tới */
    private function usedIds(string $table, string $historyTable, string $column): array
    {
        $ids = [];
        foreach ([$table, $historyTable] as $t) {
            if (Schema::hasColumn($t, $column)) {
                $ids = array_merge($ids, DB::table($t)->whereNotNull($column)->distinct()->pluck($column)->all());
            }
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    private function seedPermissions(): void
    {
        $now = now();
        $source = DB::table('permissions')->where('name', 'materData_material_review')->first();

        if ($source && ! DB::table('permissions')->where('name', 'materData_vpp_review')->exists()) {
            $id = DB::table('permissions')->insertGetId([
                'permission_group' => $source->permission_group,
                'parent_id' => null,
                'name' => 'materData_vpp_review',
                'display_name' => 'Duyệt/Từ Chối Dữ Liệu Gốc VPP',
                'description' => "Quyền nền cho nút Duyệt / Từ chối bản ghi đang Chờ duyệt ở các màn Dữ Liệu Gốc nhóm VPP có bước duyệt (Tên VPP).\n"
                    .'Lưu ý: bật thêm quyền con "Duyệt/Từ Chối <màn hình>". Bản ghi chỉ chọn được ở các màn nghiệp vụ sau khi Đã duyệt.',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach (DB::table('role_permission')->where('permission_id', $source->id)->pluck('role_id') as $roleId) {
                DB::table('role_permission')->insertOrIgnore(['role_id' => $roleId, 'permission_id' => $id]);
            }
            foreach (DB::table('user_permission')->where('permission_id', $source->id)->get() as $grant) {
                DB::table('user_permission')->insertOrIgnore(['permission_id' => $id] + array_diff_key((array) $grant, ['id' => 1, 'permission_id' => 1]));
            }
        }

        // Màn Phân Loại / Trình Ký Đề Nghị CP VPP không còn - diễn giải quyền nhóm chỉ nhắc Tên VPP
        DB::table('permissions')->where('name', 'materData_vpp_view')->update([
            'description' => "Quyền nền để xem các màn Dữ Liệu Gốc nhóm VPP: Tên VPP. Chỉ xem, không thêm hay sửa được.\n"
                .'Lưu ý: chọn màn nào hiện trên menu bằng các quyền con "Xem <màn hình>" bên dưới; chỉ có quyền này mà không có quyền con thì không thấy màn nào.',
            'updated_at' => $now,
        ]);
        DB::table('permissions')->where('name', 'materData_vpp_manage')->update([
            'description' => "Quyền nền cho các nút Thêm mới, Sửa, Khoá / Mở khoá ở các màn Dữ Liệu Gốc nhóm VPP (Tên VPP).\n"
                .'Lưu ý: bật thêm quyền con "Thêm/Sửa/Khoá <màn hình>" cho từng màn được phép làm. Sửa bản ghi đã duyệt sẽ đưa bản ghi về Chờ duyệt.',
            'updated_at' => $now,
        ]);

        MaterDataPermissions::seed();

        $descriptions = [
            'view' => 'Thấy mục Tên VPP trên menu Dữ Liệu Gốc và xem danh sách tên văn phòng phẩm dùng chung toàn công ty (Danh Mục VPP chọn tên từ đây).',
            'manage' => "Thêm tên VPP mới, sửa tên, khoá / mở khoá ở màn Tên VPP.\nLưu ý: tên mới hoặc tên vừa sửa ở trạng thái Chờ duyệt, phải được duyệt mới chọn được ở Danh Mục VPP.",
            'review' => 'Duyệt hoặc từ chối tên VPP đang Chờ duyệt ở màn Tên VPP. Tên chỉ chọn được ở Danh Mục VPP sau khi Đã duyệt.',
        ];
        foreach ($descriptions as $action => $text) {
            DB::table('permissions')->where('name', MaterDataPermissions::name('vppName', $action))
                ->update(['description' => $text, 'updated_at' => $now]);
        }
    }
};
