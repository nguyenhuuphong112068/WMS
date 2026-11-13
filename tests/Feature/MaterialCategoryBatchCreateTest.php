<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Modal Thêm mới dạng bảng nhiều dòng ở trang Danh Mục - Vật Tư:
 * - "Thêm Danh Mục Vật Tư Công Ty Mới" (MaterialCategoryController::store)
 * - "Khai Vật Tư Cho Phòng" (DepartmentMaterialController::store)
 *
 * Chạy trên CSDL thật, mọi thao tác ghi nằm trong transaction và được rollback.
 */
class MaterialCategoryBatchCreateTest extends TestCase
{
    private const PAGE = '/category/materialCategory';

    private const DEPARTMENT_ID = 6;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'wms',
            'database.connections.mysql.host' => '127.0.0.1',
            'database.connections.mysql.username' => 'root',
            'database.connections.mysql.password' => '',
        ]);
    }

    private function sess(): array
    {
        return [
            'user' => [
                'userId' => 1,
                'userName' => 'Admin',
                'fullName' => 'Nguoi Kiem Thu',
                'userGroup' => 'Admin',
                'department' => 'QA',
                'department_id' => self::DEPARTMENT_ID,
                'selected_department' => 'QA',
                'selected_department_id' => self::DEPARTMENT_ID,
            ],
        ];
    }

    private function companyRow(int $nameId, int $manufacturerId): array
    {
        return [
            'material_names_id' => $nameId,
            'manufacturers_id' => $manufacturerId,
            'part_number' => 'ZTEST-PN',
            'technical_specification' => '',
            'classification' => ['importance' => 'high', 'dangerous' => 'no', 'qa_calibration' => 'after', 'contact' => 'yes'],
            'purchasing_department' => 'supply',
        ];
    }

    /** Vật tư đã duyệt mà phòng đang thử chưa khai. */
    private function freeCategories(int $count)
    {
        return DB::table('material_categories')
            ->where('status_id', 1)
            ->where('app_status', 'approved')
            ->whereNotIn('id', DB::table('material_department_categories')
                ->where('department_id', self::DEPARTMENT_ID)->select('category_id'))
            ->limit($count)
            ->pluck('id');
    }

    /** Hệ số cho mọi đơn vị phòng khác đang dùng cho vật tư này - đủ để không bị bắt khai quy đổi. */
    private function conversionsFor(int $categoryId, int $unitId): array
    {
        return DB::table('material_department_categories')
            ->where('category_id', $categoryId)
            ->where('department_id', '<>', self::DEPARTMENT_ID)
            ->whereNotNull('unit_id')
            ->where('unit_id', '<>', $unitId)
            ->distinct()
            ->pluck('unit_id')
            ->mapWithKeys(fn ($unit) => [$unit => '2'])
            ->all();
    }

    public function test_page_renders_both_batch_modals(): void
    {
        $this->withSession($this->sess())->get(self::PAGE)
            ->assertOk()
            ->assertSee('id="mcbRowTpl"', false)
            ->assertSee('id="dmbRowTpl"', false)
            ->assertSee('Điền nhanh cho mọi dòng', false);
    }

    public function test_company_store_saves_many_rows_with_consecutive_codes(): void
    {
        DB::beginTransaction();

        try {
            $names = DB::table('material_names')
                ->whereNotIn('id', DB::table('material_categories')->select('material_names_id'))
                ->limit(2)->pluck('id');
            $manufacturerId = (int) DB::table('manufacturers')->value('id');
            $lastId = (int) DB::table('material_categories')->max('id');

            $this->withSession($this->sess())->from(self::PAGE)
                ->post(self::PAGE.'/store', ['items' => [
                    $this->companyRow($names[0], $manufacturerId),
                    // Dòng để trống hoàn toàn (chỉ có phân loại do Điền nhanh) bị bỏ qua
                    ['material_names_id' => '', 'manufacturers_id' => '', 'classification' => ['importance' => 'low']],
                    $this->companyRow($names[1], $manufacturerId),
                ]])
                ->assertRedirect(self::PAGE)
                ->assertSessionHas('success');

            $rows = DB::table('material_categories')->where('id', '>', $lastId)->orderBy('id')->get();

            $this->assertCount(2, $rows);
            $this->assertSame((int) substr($rows[0]->code, 1) + 1, (int) substr($rows[1]->code, 1));
            $this->assertSame('pending', $rows[0]->app_status);
            $this->assertSame(1, (int) $rows[0]->lead_time_pending);
            $this->assertSame(2, DB::table('material_category_histories')
                ->whereIn('material_category_id', $rows->pluck('id'))->where('action', 'Thêm mới')->count());
        } finally {
            DB::rollBack();
        }
    }

    public function test_company_store_rejects_duplicates_and_redisplays_rows(): void
    {
        DB::beginTransaction();

        try {
            $nameId = (int) DB::table('material_names')
                ->whereNotIn('id', DB::table('material_categories')->select('material_names_id'))
                ->value('id');
            $manufacturerId = (int) DB::table('manufacturers')->value('id');
            $before = DB::table('material_categories')->count();

            $this->withSession($this->sess())->from(self::PAGE)
                ->post(self::PAGE.'/store', ['items' => [
                    $this->companyRow($nameId, $manufacturerId),
                    $this->companyRow($nameId, $manufacturerId),
                ]])
                ->assertRedirect(self::PAGE)
                ->assertSessionHasErrors(['items.1.material_names_id'], null, 'createErrors');

            $this->assertSame($before, DB::table('material_categories')->count());

            // Trang mở lại modal với đủ 2 dòng vừa nhập + lỗi theo dòng
            $this->get(self::PAGE)
                ->assertOk()
                ->assertSee('Dòng 2: Trùng Vật tư - Nhà sản xuất với dòng 1.', false)
                ->assertSee('name="items[1][material_names_id]"', false);
        } finally {
            DB::rollBack();
        }
    }

    public function test_department_store_saves_many_rows(): void
    {
        DB::beginTransaction();

        try {
            $categories = $this->freeCategories(2);
            $unitId = (int) DB::table('units')->where('status_id', 1)->value('id');

            if ($categories->count() < 2) {
                $this->markTestSkipped('Không đủ vật tư chưa khai để kiểm thử.');
            }

            $this->withSession($this->sess())->from(self::PAGE)
                ->post('/category/departmentMaterial/store', ['items' => [
                    ['category_id' => $categories[0], 'unit_id' => $unitId, 'price_level' => 'high', 'min_stock' => '2', 'max_stock' => '10',
                        'conversions' => $this->conversionsFor($categories[0], $unitId)],
                    ['category_id' => $categories[1], 'unit_id' => $unitId, 'price_level' => 'low', 'note' => 'ZTEST',
                        'conversions' => $this->conversionsFor($categories[1], $unitId)],
                ]])
                ->assertRedirect(self::PAGE)
                ->assertSessionHas('success', 'Đã khai 2 vật tư của phòng thành công!');

            $this->assertSame(['high', 'low'], DB::table('material_department_categories')
                ->where('department_id', self::DEPARTMENT_ID)->whereIn('category_id', $categories)
                ->orderByRaw('category_id = ? desc', [$categories[0]])->pluck('price_level')->all());
        } finally {
            DB::rollBack();
        }
    }

    public function test_department_store_reports_errors_per_row(): void
    {
        DB::beginTransaction();

        try {
            $categories = $this->freeCategories(1);
            $units = DB::table('units')->where('status_id', 1)->limit(2)->pluck('id');

            if ($categories->isEmpty() || $units->count() < 2) {
                $this->markTestSkipped('Không đủ dữ liệu để kiểm thử.');
            }

            // Phòng khác đã khai vật tư này bằng đơn vị khác -> dòng phải khai hệ số quy đổi
            $otherDepartment = (int) DB::table('deparments')->where('id', '<>', self::DEPARTMENT_ID)->value('id');
            DB::table('material_department_categories')->insert([
                'department_id' => $otherDepartment,
                'category_id' => $categories[0],
                'unit_id' => $units[1],
                'status_id' => 1,
                'created_by' => 'Nguoi Kiem Thu',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Thiếu "Theo Giá Trị" (bắt buộc ở cấp phòng) cũng báo theo dòng
            $row = ['category_id' => $categories[0], 'unit_id' => $units[0], 'min_stock' => '10', 'max_stock' => '2'];

            $this->withSession($this->sess())->from(self::PAGE)
                ->post('/category/departmentMaterial/store', ['items' => [$row, $row]])
                ->assertRedirect(self::PAGE)
                ->assertSessionHasErrors([
                    'items.0.max_stock',
                    'items.0.price_level',
                    'items.0.conversions.'.$units[1],
                    'items.1.category_id',
                ], null, 'dmCreateErrors');

            $this->get(self::PAGE)
                ->assertOk()
                ->assertSee('Dòng 1: Ngưỡng tồn tối đa phải lớn hơn hoặc bằng ngưỡng tồn tối thiểu.', false);

            // Khai đủ hệ số + ngưỡng hợp lệ thì lưu được, hệ số ghi theo đúng dòng
            $row = ['category_id' => $categories[0], 'unit_id' => $units[0], 'price_level' => 'low',
                'conversions' => $this->conversionsFor($categories[0], $units[0])];

            $this->withSession($this->sess())->from(self::PAGE)
                ->post('/category/departmentMaterial/store', ['items' => [$row]])
                ->assertSessionHas('success', 'Đã khai vật tư của phòng thành công!');

            $this->assertTrue(DB::table('material_department_categories')
                ->where('department_id', self::DEPARTMENT_ID)->where('category_id', $categories[0])->exists());
        } finally {
            DB::rollBack();
        }
    }
}
