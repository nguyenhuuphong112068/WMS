<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Kiểm thử 5 màn hình Dữ Liệu Gốc có phê duyệt:
 * Tên Hoá Chất / Nhà Sản Xuất / Nhà Cung Cấp / Quy Cách Đóng Gói / Đơn Vị Tính.
 *
 * Chạy trên CSDL thật nhưng mọi thao tác ghi đều nằm trong transaction và được rollback.
 */
class MasterDataApprovalSmokeTest extends TestCase
{
    /**
     * Cấu hình từng chức năng: url, bảng, tiêu đề trên trang và dữ liệu mẫu.
     */
    private const SCREENS = [
        'chemName' => [
            'url' => '/materData/chemName',
            'table' => 'wms_chem_names',
            'heading' => 'Tên Hoá Chất',
            'payload' => [
                'name' => 'ZTEST Hoa Chat',
                'active_ingredient_name' => 'ZTEST Hoat Chat',
                'cas_no' => '75-05-8',
                'doc_no' => 'ZTEST-DOC',
                'chemical_formula' => 'CH3CN',
            ],
        ],
        'materialName' => [
            'url' => '/materData/materialName',
            'table' => 'wms_material_names',
            'heading' => 'Tên Vật Tư',
            'payload' => [
                'name' => 'ZTEST Vat Tu',
                'technical_information' => 'Thong so ky thuat kiem thu',
            ],
        ],
        'chemManufacturer' => [
            'url' => '/materData/chemManufacturer',
            'table' => 'wms_chem_manufacturers',
            'heading' => 'Nhà Sản Xuất',
            'payload' => [
                'short_name' => 'ZTESTM',
                'name' => 'ZTEST Nha San Xuat',
            ],
        ],
        'chemSupplier' => [
            'url' => '/materData/chemSupplier',
            'table' => 'wms_chem_suppliers',
            'heading' => 'Nhà Cung Cấp',
            'payload' => [
                'name' => 'ZTEST Nha Cung Cap',
                'address' => 'So 1 Duong Kiem Thu',
                'tax_no' => '0123456789',
                'note' => 'Ghi chu kiem thu',
            ],
        ],
        'packagingSpecification' => [
            'url' => '/materData/packagingSpecification',
            'table' => 'wms_packaging_specifications',
            'heading' => 'Quy Cách Đóng Gói',
            'payload' => [
                'name' => 'ZTEST Chai 1L',
            ],
        ],
        'unit' => [
            'url' => '/materData/unit',
            'table' => 'wms_units',
            'heading' => 'Đơn Vị Tính',
            'payload' => [
                'short_name' => 'ZTU',
                'name' => 'ZTEST Don Vi Tinh',
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // Trang đọc dữ liệu thật, ép về kết nối mysql của dự án.
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'wms',
            'database.connections.mysql.host' => '127.0.0.1',
            'database.connections.mysql.username' => 'root',
            'database.connections.mysql.password' => '',
        ]);
    }

    private function fakeSession(): array
    {
        return [
            'user' => [
                'userId' => 1,
                'userName' => 'tester',
                'fullName' => 'Nguoi Kiem Thu',
                'userGroup' => 'Admin',
                'department' => 'QA',
                'department_id' => 1,
                'selected_department' => 'QA',
                'selected_department_id' => 1,
            ],
        ];
    }

    public function test_all_screens_render(): void
    {
        // Trang trống: vẫn phải lên đủ khung màn hình và 2 modal.
        foreach (self::SCREENS as $screen) {
            $response = $this->withSession($this->fakeSession())->get($screen['url']);

            $response->assertStatus(200);
            $response->assertSee($screen['heading'], false);
            $response->assertSee('mdTable', false);
            $response->assertSee('createModal', false);
            $response->assertSee('updateModal', false);
            $response->assertSee(ltrim($screen['url'], '/') . '/store', false);
        }
    }

    public function test_screens_show_row_actions_when_data_exists(): void
    {
        DB::beginTransaction();

        try {
            foreach (self::SCREENS as $screen) {
                $url = ltrim($screen['url'], '/');

                DB::table($screen['table'])->insert($screen['payload'] + [
                    'app_status' => 'pending',
                    'status_id' => 1,
                    'created_by' => 'Nguoi Kiem Thu',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $response = $this->withSession($this->fakeSession())->get($screen['url']);

                $response->assertStatus(200);
                $response->assertSee($screen['payload']['name'], false);
                $response->assertSee('Chờ duyệt', false);
                $response->assertSee($url . '/approve', false);
                $response->assertSee($url . '/reject', false);
                $response->assertSee($url . '/deActive', false);
                // Dữ liệu đổ vào modal cập nhật đi kèm nút sửa
                $response->assertSee('btn-md-edit', false);
                $response->assertSee('data-row=', false);
            }
        } finally {
            DB::rollBack();
        }
    }

    public function test_full_crud_and_approval_round_trip(): void
    {
        DB::beginTransaction();

        try {
            $session = $this->fakeSession();

            foreach (self::SCREENS as $key => $screen) {
                $table = $screen['table'];
                $payload = $screen['payload'];

                // CREATE -> bản ghi mới phải ở trạng thái chờ duyệt
                $this->withSession($session)
                    ->post($screen['url'] . '/store', $payload)
                    ->assertSessionHas('success');

                $row = DB::table($table)->where('name', $payload['name'])->first();
                $this->assertNotNull($row, "[$key] Không tạo được bản ghi.");
                $this->assertEquals('pending', $row->app_status, "[$key] Bản ghi mới phải chờ duyệt.");
                $this->assertEquals(1, $row->status_id, "[$key] Bản ghi mới phải đang hoạt động.");
                $this->assertEquals('Nguoi Kiem Thu', $row->created_by, "[$key] Sai người tạo.");
                $this->assertNull($row->approved_by, "[$key] Bản ghi mới chưa được duyệt.");

                // Trùng tên -> lỗi validate, không tạo thêm dòng
                $this->withSession($session)
                    ->post($screen['url'] . '/store', $payload)
                    ->assertSessionHasErrors(['name'], null, 'createErrors');
                $this->assertEquals(1, DB::table($table)->where('name', $payload['name'])->count());

                // Thiếu tên -> lỗi validate
                $this->withSession($session)
                    ->post($screen['url'] . '/store', array_merge($payload, ['name' => '']))
                    ->assertSessionHasErrors(['name'], null, 'createErrors');

                // APPROVE
                $this->withSession($session)
                    ->post($screen['url'] . '/approve', ['id' => $row->id])
                    ->assertSessionHas('success');

                $row = DB::table($table)->where('id', $row->id)->first();
                $this->assertEquals('approved', $row->app_status, "[$key] Duyệt không thành công.");
                $this->assertEquals('Nguoi Kiem Thu', $row->approved_by, "[$key] Sai người duyệt.");
                $this->assertNotNull($row->approved_at, "[$key] Thiếu thời điểm duyệt.");

                // UPDATE -> phải quay lại chờ duyệt và xoá dấu vết duyệt cũ
                $updated = array_merge($payload, [
                    'id' => $row->id,
                    'name' => $payload['name'] . ' Sua',
                ]);

                $this->withSession($session)
                    ->post($screen['url'] . '/update', $updated)
                    ->assertSessionHas('success');

                $row = DB::table($table)->where('id', $row->id)->first();
                $this->assertEquals($payload['name'] . ' Sua', $row->name, "[$key] Không cập nhật được tên.");
                $this->assertEquals('pending', $row->app_status, "[$key] Sửa xong phải chờ duyệt lại.");
                $this->assertNull($row->approved_by, "[$key] Phải xoá người duyệt cũ khi sửa.");
                $this->assertNull($row->approved_at, "[$key] Phải xoá thời điểm duyệt cũ khi sửa.");
                $this->assertEquals('Nguoi Kiem Thu', $row->updated_by, "[$key] Sai người cập nhật.");

                // REJECT
                $this->withSession($session)
                    ->post($screen['url'] . '/reject', ['id' => $row->id])
                    ->assertSessionHas('success');
                $this->assertEquals('rejected', DB::table($table)->where('id', $row->id)->value('app_status'));

                // KHOÁ / MỞ KHOÁ
                $this->withSession($session)
                    ->post($screen['url'] . '/deActive', ['id' => $row->id])
                    ->assertSessionHas('success');
                $this->assertEquals(0, DB::table($table)->where('id', $row->id)->value('status_id'));

                $this->withSession($session)
                    ->post($screen['url'] . '/deActive', ['id' => $row->id])
                    ->assertSessionHas('success');
                $this->assertEquals(1, DB::table($table)->where('id', $row->id)->value('status_id'));

                // Thao tác trên id không tồn tại -> báo lỗi, không vỡ trang
                $this->withSession($session)
                    ->post($screen['url'] . '/approve', ['id' => 999999999])
                    ->assertSessionHas('error');
            }
        } finally {
            DB::rollBack();
        }
    }

    public function test_guest_is_redirected_to_login(): void
    {
        foreach (self::SCREENS as $screen) {
            $this->get($screen['url'])->assertRedirect(route('login'));
        }
    }
}
