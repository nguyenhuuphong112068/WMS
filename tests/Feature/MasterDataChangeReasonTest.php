<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Bắt buộc nhập "Lý do điều chỉnh" khi Sửa / Khoá dữ liệu gốc và danh mục.
 *
 * Chạy trên CSDL thật, mọi thao tác ghi nằm trong transaction và được rollback.
 */
class MasterDataChangeReasonTest extends TestCase
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

    /** Modal cập nhật của mọi màn dữ liệu gốc phải có ô "change_reason". */
    public function test_update_modals_have_change_reason_field(): void
    {
        $screens = [
            '/materData/company',
            '/materData/department',
            '/materData/status',
            '/materData/unit',
            '/materData/chemName',
            '/materData/packagingSpecification',
            '/materData/zone',
            '/category/materialCategory',
            '/category/chemicalCategory',
            '/category/standardCategory',
        ];

        foreach ($screens as $url) {
            $this->withSession($this->sess())->get($url)
                ->assertStatus(200)
                ->assertSee('name="change_reason"', false);
        }
    }

    /** Sửa mà không gửi lý do -> bị chặn, dữ liệu không đổi. */
    public function test_update_without_reason_is_rejected(): void
    {
        DB::beginTransaction();

        try {
            $id = DB::table('packaging_specifications')->insertGetId([
                'name' => 'ZTEST QCĐG', 'app_status' => 'approved', 'status_id' => 1,
                'created_by' => 'Nguoi Kiem Thu', 'created_at' => now(), 'updated_at' => now(),
            ]);

            // Thiếu change_reason -> validate lỗi
            $this->withSession($this->sess())
                ->post('/materData/packagingSpecification/update', ['id' => $id, 'name' => 'ZTEST Doi Ten'])
                ->assertSessionHasErrors('change_reason', null, 'updateErrors');

            $this->assertSame('ZTEST QCĐG', DB::table('packaging_specifications')->where('id', $id)->value('name'));

            // Có change_reason -> lưu được và ghi vào lịch sử
            $this->withSession($this->sess())
                ->post('/materData/packagingSpecification/update', [
                    'id' => $id, 'name' => 'ZTEST Doi Ten', 'change_reason' => 'Đổi tên theo yêu cầu QA',
                ])
                ->assertSessionHas('success');

            $this->assertSame('ZTEST Doi Ten', DB::table('packaging_specifications')->where('id', $id)->value('name'));
            $this->assertSame('Đổi tên theo yêu cầu QA', DB::table('datamaster_histories')
                ->where('table_name', 'packaging_specifications')->where('record_id', $id)
                ->where('action', 'Cập nhật')->value('change_reason'));
        } finally {
            DB::rollBack();
        }
    }

    /** Sửa nhưng không đổi trường nào -> chặn, không phát sinh lịch sử. */
    public function test_update_with_no_field_change_is_blocked(): void
    {
        DB::beginTransaction();

        try {
            $id = DB::table('packaging_specifications')->insertGetId([
                'name' => 'ZTEST QCĐG2', 'app_status' => 'approved', 'status_id' => 1,
                'created_by' => 'Nguoi Kiem Thu', 'created_at' => now(), 'updated_at' => now(),
            ]);

            $this->withSession($this->sess())
                ->post('/materData/packagingSpecification/update', [
                    'id' => $id, 'name' => 'ZTEST QCĐG2', 'change_reason' => 'Không đổi gì',
                ])
                ->assertSessionHas('error');

            $this->assertSame(0, DB::table('datamaster_histories')
                ->where('table_name', 'packaging_specifications')->where('record_id', $id)
                ->where('action', 'Cập nhật')->count());
        } finally {
            DB::rollBack();
        }
    }

    /** Khoá mà không gửi lý do -> bị chặn, trạng thái giữ nguyên. */
    public function test_deactivate_without_reason_is_rejected(): void
    {
        DB::beginTransaction();

        try {
            $id = DB::table('packaging_specifications')->insertGetId([
                'name' => 'ZTEST QCĐG3', 'app_status' => 'approved', 'status_id' => 1,
                'created_by' => 'Nguoi Kiem Thu', 'created_at' => now(), 'updated_at' => now(),
            ]);

            $this->withSession($this->sess())
                ->post('/materData/packagingSpecification/deActive', ['id' => $id])
                ->assertSessionHas('error');
            $this->assertSame(1, DB::table('packaging_specifications')->where('id', $id)->value('status_id'));

            $this->withSession($this->sess())
                ->post('/materData/packagingSpecification/deActive', ['id' => $id, 'change_reason' => 'Ngưng dùng quy cách này'])
                ->assertSessionHas('success');
            $this->assertSame(0, DB::table('packaging_specifications')->where('id', $id)->value('status_id'));
            $this->assertSame('Ngưng dùng quy cách này', DB::table('datamaster_histories')
                ->where('table_name', 'packaging_specifications')->where('record_id', $id)
                ->where('action', 'Khoá')->value('change_reason'));
        } finally {
            DB::rollBack();
        }
    }
}
