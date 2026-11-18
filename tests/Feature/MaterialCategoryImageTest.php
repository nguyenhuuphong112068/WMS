<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Hình ảnh vật tư (không bắt buộc) ở Danh Mục Vật Tư Công Ty: thêm mới kèm ảnh theo từng dòng,
 * sửa thay / gỡ ảnh, xem ảnh qua route image. CSDL thật trong transaction (rollback), storage giả.
 */
class MaterialCategoryImageTest extends TestCase
{
    private const PAGE = '/category/materialCategory';

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

        Storage::fake();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    private function sess(): array
    {
        return ['user' => [
            'userId' => 1, 'userName' => 'Admin', 'fullName' => 'Nguoi Kiem Thu', 'userGroup' => 'Admin',
            'department' => 'QA', 'department_id' => 6, 'selected_department' => 'QA', 'selected_department_id' => 6,
        ]];
    }

    private function row(int $nameId, int $manufacturerId, $image = null): array
    {
        return array_filter([
            'material_names_id' => $nameId,
            'manufacturers_id' => $manufacturerId,
            'part_number' => '',
            'technical_specification' => '',
            'classification' => ['importance' => 'high', 'dangerous' => 'no', 'qa_calibration' => 'after', 'contact' => 'yes'],
            'image' => $image,
        ], fn ($value) => $value !== null);
    }

    private function freeNames(int $count)
    {
        return DB::table('material_names')
            ->whereNotIn('id', DB::table('material_categories')->select('material_names_id'))
            ->limit($count)->pluck('id');
    }

    public function test_store_saves_image_per_row_and_update_replaces_then_removes(): void
    {
        $names = $this->freeNames(2);
        $manufacturerId = (int) DB::table('manufacturers')->value('id');
        $lastId = (int) DB::table('material_categories')->max('id');

        $this->withSession($this->sess())->from(self::PAGE)
            ->post(self::PAGE . '/store', ['items' => [
                // Dòng trống đứng trước: ảnh của dòng sau vẫn phải gắn đúng dòng sau khi đánh số lại
                ['material_names_id' => '', 'manufacturers_id' => ''],
                $this->row($names[0], $manufacturerId),
                $this->row($names[1], $manufacturerId, UploadedFile::fake()->image('vt.jpg', 40, 40)),
            ]])
            ->assertRedirect(self::PAGE)
            ->assertSessionHas('success');

        $rows = DB::table('material_categories')->where('id', '>', $lastId)->orderBy('id')->get();
        $this->assertCount(2, $rows);
        $this->assertNull($rows[0]->image_path);
        $this->assertNotNull($rows[1]->image_path);
        Storage::assertExists($rows[1]->image_path);

        $id = $rows[1]->id;
        $this->withSession($this->sess())->get(self::PAGE . '/image?id=' . $id)->assertOk();
        $this->withSession($this->sess())->get(self::PAGE . '/image?id=' . $rows[0]->id)->assertNotFound();

        // Thay ảnh mới: file cũ vẫn giữ, lịch sử ghi "Thay ảnh mới"
        $oldPath = $rows[1]->image_path;
        $update = $this->row($names[1], $manufacturerId) + ['id' => $id, 'change_reason' => 'Thử thay ảnh'];

        $this->withSession($this->sess())->from(self::PAGE)
            ->post(self::PAGE . '/update', $update + ['image' => UploadedFile::fake()->image('moi.png', 30, 30)])
            ->assertSessionHas('success');

        $newPath = DB::table('material_categories')->where('id', $id)->value('image_path');
        $this->assertNotSame($oldPath, $newPath);
        Storage::assertExists($oldPath);
        $this->assertStringContainsString('Hình ảnh: Thay ảnh mới', (string) DB::table('material_category_histories')
            ->where('material_category_id', $id)->orderByDesc('id')->value('change_note'));

        // Gỡ ảnh
        $this->withSession($this->sess())->from(self::PAGE)
            ->post(self::PAGE . '/update', $update + ['remove_image' => '1'])
            ->assertSessionHas('success');

        $this->assertNull(DB::table('material_categories')->where('id', $id)->value('image_path'));
        $this->assertStringContainsString('Hình ảnh: Gỡ ảnh', (string) DB::table('material_category_histories')
            ->where('material_category_id', $id)->orderByDesc('id')->value('change_note'));

        // Không đổi gì (không chọn ảnh, không gỡ) thì báo chưa có thay đổi
        $this->withSession($this->sess())->from(self::PAGE)
            ->post(self::PAGE . '/update', $update)
            ->assertSessionHas('error');
    }

    public function test_store_rejects_non_image_and_asks_to_pick_again(): void
    {
        $names = $this->freeNames(1);
        $manufacturerId = (int) DB::table('manufacturers')->value('id');
        $lastId = (int) DB::table('material_categories')->max('id');

        $this->withSession($this->sess())->from(self::PAGE)
            ->post(self::PAGE . '/store', ['items' => [
                $this->row($names[0], $manufacturerId, UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')),
            ]])
            ->assertRedirect(self::PAGE)
            ->assertSessionHasErrors(['items.0.image', 'items'], null, 'createErrors');

        $this->assertSame(0, DB::table('material_categories')->where('id', '>', $lastId)->count());
    }

    public function test_page_renders_image_column(): void
    {
        $this->withSession($this->sess())->get(self::PAGE)
            ->assertOk()
            ->assertSee('id="mcuImagePicker"', false)
            ->assertSee('id="mciViewer"', false)
            ->assertSee('name="items[][image]"', false);
    }
}
