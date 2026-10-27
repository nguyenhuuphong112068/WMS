<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Gửi form ngầm (middleware SoftSubmit + layout/softSubmit): Sửa / Khoá ở Dữ Liệu Gốc
 * và Danh Mục trả JSON thay vì redirect, trang không tải lại.
 *
 * Chạy trên CSDL thật, mọi thao tác ghi nằm trong transaction và được rollback.
 */
class SoftSubmitTest extends TestCase
{
    private const PAGE = '/materData/packagingSpecification';

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
                'department_id' => 1,
                'selected_department' => 'QA',
                'selected_department_id' => 1,
            ],
        ];
    }

    private function softHeaders(): array
    {
        return [
            'X-WMS-Soft' => '1',
            'X-WMS-Page' => url(self::PAGE.'?tab=x'),
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json',
        ];
    }

    private function packaging(string $name): int
    {
        return DB::table('packaging_specifications')->insertGetId([
            'name' => $name, 'app_status' => 'approved', 'status_id' => 1,
            'created_by' => 'Nguoi Kiem Thu', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_soft_update_returns_json_and_clears_flash(): void
    {
        DB::beginTransaction();

        try {
            $id = $this->packaging('ZTEST SOFT 1');

            $res = $this->withSession($this->sess())->withHeaders($this->softHeaders())
                ->post(self::PAGE.'/update', ['id' => $id, 'name' => 'ZTEST SOFT 1b', 'change_reason' => 'Kiểm thử lưu ngầm']);

            $res->assertOk()
                ->assertJson(['soft' => true, 'error' => null, 'errors' => []])
                ->assertSessionMissing('success');

            $this->assertNotEmpty($res->json('success'));
            $this->assertSame('ZTEST SOFT 1b', DB::table('packaging_specifications')->where('id', $id)->value('name'));
        } finally {
            DB::rollBack();
        }
    }

    public function test_soft_update_validation_errors_come_back_as_json(): void
    {
        DB::beginTransaction();

        try {
            $id = $this->packaging('ZTEST SOFT 2');

            $res = $this->withSession($this->sess())->withHeaders($this->softHeaders())
                ->post(self::PAGE.'/update', ['id' => $id, 'name' => 'ZTEST SOFT 2b']);

            $res->assertOk()->assertJson(['soft' => true])
                ->assertSessionMissing('errors')
                ->assertSessionMissing('_old_input');

            $this->assertNotEmpty($res->json('errors.updateErrors.change_reason.0'));
            $this->assertSame('ZTEST SOFT 2', DB::table('packaging_specifications')->where('id', $id)->value('name'));
        } finally {
            DB::rollBack();
        }
    }

    public function test_soft_business_error_is_reported(): void
    {
        DB::beginTransaction();

        try {
            $id = $this->packaging('ZTEST SOFT 3');

            $res = $this->withSession($this->sess())->withHeaders($this->softHeaders())
                ->post(self::PAGE.'/update', ['id' => $id, 'name' => 'ZTEST SOFT 3', 'change_reason' => 'Không đổi gì']);

            $res->assertOk()->assertJson(['soft' => true, 'success' => null])->assertSessionMissing('error');
            $this->assertNotEmpty($res->json('error'));
        } finally {
            DB::rollBack();
        }
    }

    public function test_soft_deactivate(): void
    {
        DB::beginTransaction();

        try {
            $id = $this->packaging('ZTEST SOFT 4');

            $this->withSession($this->sess())->withHeaders($this->softHeaders())
                ->post(self::PAGE.'/deActive', ['id' => $id, 'change_reason' => 'Ngưng dùng'])
                ->assertOk()->assertJson(['soft' => true, 'error' => null]);

            $this->assertSame(0, DB::table('packaging_specifications')->where('id', $id)->value('status_id'));
        } finally {
            DB::rollBack();
        }
    }

    /** Redirect sang trang khác (hết phiên -> đăng nhập): trả URL, giữ flash cho trang đích. */
    public function test_redirect_to_other_page_is_followed_by_client(): void
    {
        $this->withHeaders($this->softHeaders())
            ->post(self::PAGE.'/update', ['id' => 1])
            ->assertOk()
            ->assertJson(['soft' => true, 'redirect' => route('login')])
            ->assertSessionHas('error');
    }

    public function test_without_header_still_redirects(): void
    {
        DB::beginTransaction();

        try {
            $id = $this->packaging('ZTEST SOFT 5');

            $this->withSession($this->sess())->from(self::PAGE)
                ->post(self::PAGE.'/update', ['id' => $id, 'name' => 'ZTEST SOFT 5b', 'change_reason' => 'Lưu thường'])
                ->assertRedirect(self::PAGE)
                ->assertSessionHas('success');
        } finally {
            DB::rollBack();
        }
    }

    public function test_script_only_on_master_data_and_category_pages(): void
    {
        foreach ([self::PAGE, '/materData/company', '/materData/zone', '/category/materialCategory'] as $url) {
            $this->withSession($this->sess())->get($url)->assertOk()->assertSee('window.wmsSoft', false);
        }

        $this->withSession($this->sess())->get('/home')->assertOk()->assertDontSee('window.wmsSoft', false);
    }
}
