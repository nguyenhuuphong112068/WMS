<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Kiểm thử khói 4 màn hình VẬT TƯ mới: Nhập / Sử Dụng / Tồn / Dự Trù.
 * Chỉ kiểm tra trang dựng được (HTTP 200) và luồng dự trù trình ký 2 bước chạy đúng.
 * Mọi thao tác ghi nằm trong transaction và được rollback.
 */
class MaterialModulesSmokeTest extends TestCase
{
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

    private function session(): array
    {
        return [
            'user' => [
                'userId' => 1,
                'userName' => 'Admin',
                'fullName' => 'Nguoi Kiem Thu',
                'userGroup' => 'Admin',
                'department' => 'QA',
                'department_id' => 6,
                'selected_department' => 'QA',
                'selected_department_id' => 6,
            ],
        ];
    }

    public function test_all_four_screens_render(): void
    {
        $s = $this->session();

        $this->withSession($s)->get('/import/materialImport')->assertStatus(200)->assertSee('Nhập vật tư', false);
        $this->withSession($s)->get('/inventory/materialInventory')->assertStatus(200)->assertSee('TỒN KHO VẬT TƯ', false);
        $this->withSession($s)->get('/export/materialExport')->assertStatus(200)->assertSee('Đề nghị cấp phát vật tư', false);
        $this->withSession($s)->get('/export/materialExport?tab=request')->assertStatus(200);
        $this->withSession($s)->get('/estimate/materialEstimate')->assertStatus(200)->assertSee('Dự Trù Vật Tư', false);
    }

    public function test_reception_screen_is_gone(): void
    {
        $this->withSession($this->session())->get('/estimate/estimateReception')->assertStatus(404);
    }

    public function test_material_estimate_two_step_signoff(): void
    {
        DB::beginTransaction();

        try {
            $s = $this->session();
            $unitId = (int) DB::table('units')->where('status_id', 1)->where('app_status', 'approved')->value('id');

            $this->withSession($s)->post('/estimate/materialEstimate/store', ['month' => 12, 'year' => 2099, 'note' => 'ZTEST'])
                ->assertSessionHas('success');

            $list = DB::table('material_estimates')->where('department_id', 6)->where('month', 12)->where('year', 2099)->first();
            $this->assertNotNull($list);
            $this->assertEquals('draft', $list->app_status);
            $this->assertNotEmpty($list->code);

            // Chưa có mặt hàng -> không trình ký được
            $this->withSession($s)->post('/estimate/materialEstimate/submit', ['id' => $list->id])->assertSessionHas('error');

            // Thêm mặt hàng ngoài danh mục
            $this->withSession($s)->post('/estimate/materialEstimate/storeItem', [
                'material_estimate_id' => $list->id,
                'source' => 'manual',
                'material_name' => 'ZTEST vat tu ngoai danh muc',
                'purpose' => 'ZTEST',
                'amounts' => [['amount' => '10', 'unit_id' => $unitId, 'for_month_year' => '2099-12']],
            ])->assertSessionHas('success');

            $item = DB::table('material_estimate_items')->where('material_estimate_id', $list->id)->first();
            $this->assertNotNull($item);
            $this->assertEquals('ZTEST vat tu ngoai danh muc', $item->material_name);

            // Trình ký -> pending_manager
            $this->withSession($s)->post('/estimate/materialEstimate/submit', ['id' => $list->id])->assertSessionHas('success');
            $this->assertEquals('pending_manager', DB::table('material_estimates')->where('id', $list->id)->value('app_status'));

            // Ký bước 1 -> pending_director
            $this->withSession($s)->post('/estimate/materialEstimate/signManager', ['id' => $list->id])->assertSessionHas('success');
            $this->assertEquals('pending_director', DB::table('material_estimates')->where('id', $list->id)->value('app_status'));

            // Ký bước 2 -> approved + tự tiếp nhận
            $this->withSession($s)->post('/estimate/materialEstimate/signDirector', ['id' => $list->id])->assertSessionHas('success');
            $row = DB::table('material_estimates')->where('id', $list->id)->first();
            $this->assertEquals('approved', $row->app_status);
            $this->assertEquals('received', $row->reception_status);
            $this->assertEquals('Hệ thống', $row->received_by);

            // Chi tiết mở được
            $this->withSession($s)->get('/estimate/materialEstimate/detail?id='.$list->id)->assertStatus(200)->assertSee($row->code, false);
        } finally {
            DB::rollBack();
        }
    }

    public function test_material_export_request_flow(): void
    {
        DB::beginTransaction();

        try {
            $s = $this->session();
            $groupId = (int) DB::table('groups')->where('department_id', 6)->where('status_id', 1)->value('id');

            if (! $groupId) {
                $this->markTestSkipped('Phòng QA chưa có Tổ nào để kiểm thử đề nghị.');
            }

            // Tạo đề nghị (trình ký luôn)
            $this->withSession($s)->post('/export/materialExport/requestStore', [
                'action_type' => 'send',
                'group_id' => $groupId,
                'needs_director' => 0,
                'items' => [
                    ['material_name' => 'ZTEST vat tu', 'requested_amount' => '5', 'requested_unit' => 'cái', 'purpose' => 'ZTEST'],
                ],
            ])->assertSessionHas('success');

            $req = DB::table('material_request_lists')->where('department_id', 6)->orderBy('id', 'desc')->first();
            $this->assertNotNull($req);
            $this->assertEquals('pending_manager', $req->app_status);

            // Trưởng/Phó Phòng ký -> approved (không cần BGĐ)
            $this->withSession($s)->post('/export/materialExport/requestSignManager', ['request_list_id' => $req->id])->assertSessionHas('success');
            $row = DB::table('material_request_lists')->where('id', $req->id)->first();
            $this->assertEquals('approved', $row->app_status);
            $this->assertEquals('waiting', $row->issue_status);
        } finally {
            DB::rollBack();
        }
    }
}
