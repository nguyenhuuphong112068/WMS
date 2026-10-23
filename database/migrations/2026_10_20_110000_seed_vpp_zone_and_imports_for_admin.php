<?php

use App\Support\CheckStatus;
use App\Support\MaterialCode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * KHO VPP + NHẬP KHO 10 VĂN PHÒNG PHẨM CHO PHÒNG HÀNH CHÍNH
 *
 * Phòng làm bộ phận mua hàng Hành Chánh (deparments.purchasing_key = admin) chưa có định khu,
 * nên dựng một kho tối giản theo cấu trúc Kho/Kệ/Cột/Tầng/Vị trí (mã nối "/"):
 *   HC01 Kho VPP / 01 Kệ-Tủ 01 / 01 Cột 01
 *     - Tầng 01: 10 vị trí Sử Dụng (in_use) cho vật tư - mỗi VPP một vị trí, gán làm định khu
 *       mặc định của dòng "Vật tư của phòng"
 *     - Tầng 02: 2 vị trí Biệt Trữ (quarantine) để các lần nhập sau đặt lô chờ kiểm tra
 *
 * Sau đó nhập 10 lô (mỗi VPP #01..#10 một lô, số lượng 100) ở trạng thái ĐÃ KIỂM TRA - ĐẠT
 * để vào tồn kho và cấp phát được ngay. Lịch sử ghi đủ hai bước "Thêm mới" + "Kiểm tra Đạt"
 * như khi thao tác trên màn Nhập Vật Tư. Chạy lại an toàn: kho/lô đã có thì bỏ qua.
 */
return new class extends Migration
{
    private const ACTOR = 'Hệ thống';

    private const WAREHOUSE_CODE = 'HC01';

    private const IMPORT_NOTE = 'Nhập tồn đầu kỳ văn phòng phẩm';

    private const AMOUNT = 100;

    private const ITEMS = ['VPP #01', 'VPP #02', 'VPP #03', 'VPP #04', 'VPP #05',
        'VPP #06', 'VPP #07', 'VPP #08', 'VPP #09', 'VPP #10'];

    public function up(): void
    {
        $departmentId = DB::table('deparments')->where('purchasing_key', 'admin')->orderBy('id')->value('id');

        if (! $departmentId) {
            return;
        }

        $categories = DB::table('material_categories')
            ->whereIn('technical_specification', self::ITEMS)
            ->pluck('id', 'technical_specification');

        DB::transaction(function () use ($departmentId, $categories) {
            $locationIds = $this->buildZone($departmentId);

            foreach (self::ITEMS as $i => $tag) {
                $categoryId = $categories[$tag] ?? null;
                $locationId = $locationIds[$i] ?? null;

                if (! $categoryId) {
                    continue;
                }

                $deptRow = DB::table('material_department_categories')
                    ->where('department_id', $departmentId)->where('category_id', $categoryId)->first();

                if (! $deptRow) {
                    continue;
                }

                if (! $deptRow->default_location_id && $locationId) {
                    DB::table('material_department_categories')->where('id', $deptRow->id)->update([
                        'default_location_id' => $locationId,
                        'updated_by' => self::ACTOR,
                        'updated_at' => now(),
                    ]);
                }

                $exists = DB::table('material_imports')
                    ->where('department_id', $departmentId)->where('category_id', $categoryId)
                    ->where('note', self::IMPORT_NOTE)->exists();

                if (! $exists) {
                    $this->import($departmentId, $categoryId, $locationId);
                }
            }
        });
    }

    /** @return array<int, int> id 10 vị trí Sử Dụng theo thứ tự 01..10 */
    private function buildZone(int $departmentId): array
    {
        $base = [
            'department_id' => $departmentId,
            'status_id' => 1,
            'created_by' => self::ACTOR,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $warehouseId = DB::table('warehouses')->where('code', self::WAREHOUSE_CODE)->where('department_id', $departmentId)->value('id');

        if ($warehouseId) {
            return DB::table('locations')
                ->where('warehouse_id', $warehouseId)->where('zone_type', 'in_use')
                ->orderBy('tier_id')->orderBy('position')->pluck('id')->all();
        }

        $code = self::WAREHOUSE_CODE;
        $warehouseId = DB::table('warehouses')->insertGetId($base + ['code' => $code, 'name' => 'Kho VPP']);

        $code .= '/01';
        $shelfId = DB::table('shelves')->insertGetId($base + [
            'code' => $code, 'name' => 'Kệ/Tủ 01', 'warehouse_id' => $warehouseId,
        ]);

        $code .= '/01';
        $columnId = DB::table('columns')->insertGetId($base + [
            'code' => $code, 'name' => 'Cột 01', 'warehouse_id' => $warehouseId, 'shelf_id' => $shelfId, 'position' => 1,
        ]);

        $locationIds = [];

        foreach ([1 => ['in_use', 10], 2 => ['quarantine', 2]] as $tier => [$zoneType, $count]) {
            $tierCode = $code.'/'.str_pad((string) $tier, 2, '0', STR_PAD_LEFT);
            $tierId = DB::table('tiers')->insertGetId($base + [
                'code' => $tierCode,
                'name' => 'Tầng '.str_pad((string) $tier, 2, '0', STR_PAD_LEFT),
                'warehouse_id' => $warehouseId,
                'shelf_id' => $shelfId,
                'column_id' => $columnId,
                'position' => $tier,
            ]);

            for ($position = 1; $position <= $count; $position++) {
                $id = DB::table('locations')->insertGetId($base + [
                    'code' => $tierCode.'/'.str_pad((string) $position, 2, '0', STR_PAD_LEFT),
                    'zone_type' => $zoneType,
                    'color' => $zoneType === 'quarantine' ? '#F59E0B' : '#16A34A',
                    'warehouse_id' => $warehouseId,
                    'shelf_id' => $shelfId,
                    'column_id' => $columnId,
                    'tier_id' => $tierId,
                    'position' => $position,
                    'item_type' => 'material',
                ]);

                if ($zoneType === 'in_use') {
                    $locationIds[] = $id;
                }
            }
        }

        return $locationIds;
    }

    private function import(int $departmentId, int $categoryId, ?int $locationId): void
    {
        $code = MaterialCode::next($departmentId);

        $id = DB::table('material_imports')->insertGetId([
            'code' => $code,
            'department_id' => $departmentId,
            'category_id' => $categoryId,
            'amount' => self::AMOUNT,
            'imported_date' => now()->format('Y-m-d'),
            'imported_by' => self::ACTOR,
            'location_id' => $locationId,
            'note' => self::IMPORT_NOTE,
            'status_id' => 1,
            'is_checked' => 1,
            'check_result' => CheckStatus::PASSED,
            'checked_by' => self::ACTOR,
            'checked_at' => now(),
            'created_by' => self::ACTOR,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $row = DB::table('material_imports')->where('id', $id)->first();

        foreach ([
            ['Thêm mới', 'Tạo mới phiếu nhập, mã xuất nhập '.$code.'.'],
            ['Kiểm tra Đạt', 'Kiểm tra ĐẠT, lô được nhập kho và cộng vào tồn.'],
        ] as [$action, $note]) {
            DB::table('material_import_histories')->insert([
                'material_import_id' => $row->id,
                'action' => $action,
                'code' => $row->code,
                'category_id' => $row->category_id,
                'amount' => $row->amount,
                'imported_date' => $row->imported_date,
                'imported_by' => $row->imported_by,
                'location_id' => $row->location_id,
                'note' => $row->note,
                'status_id' => $row->status_id,
                'is_checked' => $row->is_checked,
                'check_result' => $row->check_result,
                'checked_by' => $row->checked_by,
                'checked_at' => $row->checked_at,
                'change_note' => $note,
                'created_by' => self::ACTOR,
                'created_at' => now(),
            ]);
        }
    }

    /** Xoá các lô nhập tồn đầu kỳ (chưa xuất) và kho VPP nếu không còn lô nào nằm trong. */
    public function down(): void
    {
        $departmentId = DB::table('deparments')->where('purchasing_key', 'admin')->orderBy('id')->value('id');

        $importIds = DB::table('material_imports')
            ->where('department_id', $departmentId)->where('note', self::IMPORT_NOTE)
            ->whereNotIn('id', DB::table('material_exports')->whereNotNull('import_id')->select('import_id'))
            ->pluck('id');

        DB::table('material_import_histories')->whereIn('material_import_id', $importIds)->delete();
        DB::table('material_imports')->whereIn('id', $importIds)->delete();

        $warehouseId = DB::table('warehouses')->where('code', self::WAREHOUSE_CODE)->where('department_id', $departmentId)->value('id');

        if (! $warehouseId) {
            return;
        }

        $locationIds = DB::table('locations')->where('warehouse_id', $warehouseId)->pluck('id');

        if (DB::table('material_imports')->whereIn('location_id', $locationIds)->exists()) {
            return;
        }

        DB::table('material_department_categories')->whereIn('default_location_id', $locationIds)
            ->update(['default_location_id' => null]);

        foreach (['locations', 'tiers', 'columns', 'shelves'] as $table) {
            DB::table($table)->where('warehouse_id', $warehouseId)->delete();
        }

        DB::table('warehouses')->where('id', $warehouseId)->delete();
    }
};
