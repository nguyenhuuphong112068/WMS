<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ZoneSmokeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Trang đọc dữ liệu thật, ép về kết nối mysql của dự án (chỉ SELECT).
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'wms',
            'database.connections.mysql.host' => '127.0.0.1',
            'database.connections.mysql.username' => 'root',
            'database.connections.mysql.password' => '',
        ]);
    }

    private function fakeSession(string $moduleCode): array
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
            'module' => [
                'code' => $moduleCode,
                'name' => $moduleCode === 'hoa_chat' ? 'Hoá Chất' : 'Vật Tư',
                'icon' => 'fas fa-boxes',
            ],
        ];
    }

    public function test_material_zone_page_renders(): void
    {
        $response = $this->withSession($this->fakeSession('vat_tu'))->get('/material/materData/zone');

        $response->assertStatus(200);
        $response->assertSee('Định Khu', false);
        $response->assertSee('zoneTable-warehouse', false);
        $response->assertSee('zoneTable-room', false);
        $response->assertSee('zoneTable-shelf', false);
        $response->assertSee('zoneTable-location', false);
        $response->assertSee('createModal-location', false);
        $response->assertSee('updateModal-location', false);
        $response->assertSee('material/materData/zone/warehouse/store', false);
        $response->assertSee('material/materData/zone/location/destroy', false);
    }

    public function test_chemical_zone_page_renders(): void
    {
        $response = $this->withSession($this->fakeSession('hoa_chat'))->get('/chemical/materData/zone');

        $response->assertStatus(200);
        $response->assertSee('Định Khu', false);
        $response->assertSee('chemical/materData/zone/shelf/update', false);
    }

    public function test_full_crud_round_trip(): void
    {
        DB::beginTransaction();

        try {
            $session = $this->fakeSession('vat_tu');

            // CREATE kho
            $this->withSession($session)
                ->post('/material/materData/zone/warehouse/store', ['code' => 'ZTEST-W', 'name' => 'Kho Kiem Thu'])
                ->assertSessionHas('success');

            $warehouse = DB::table('warehouses')->where('code', 'ZTEST-W')->first();
            $this->assertNotNull($warehouse);
            $this->assertEquals(1, $warehouse->status_id);

            // Trùng mã -> báo lỗi validate, không tạo thêm dòng
            $this->withSession($session)
                ->post('/material/materData/zone/warehouse/store', ['code' => 'ZTEST-W', 'name' => 'Trung ma'])
                ->assertSessionHasErrors(['code'], null, 'create_warehouse');
            $this->assertEquals(1, DB::table('warehouses')->where('code', 'ZTEST-W')->count());

            // CREATE phòng thuộc kho vừa tạo
            $this->withSession($session)->post('/material/materData/zone/room/store', [
                'code' => 'ZTEST-R',
                'name' => 'Phong Kiem Thu',
                'warehouse_id' => $warehouse->id,
            ])->assertSessionHas('success');

            $room = DB::table('rooms')->where('code', 'ZTEST-R')->first();
            $this->assertEquals($warehouse->id, $room->warehouse_id);

            // UPDATE phòng
            $this->withSession($session)->post('/material/materData/zone/room/update', [
                'id' => $room->id,
                'code' => 'ZTEST-R2',
                'name' => 'Phong Kiem Thu Sua',
                'warehouse_id' => $warehouse->id,
            ])->assertSessionHas('success');

            $room = DB::table('rooms')->where('id', $room->id)->first();
            $this->assertEquals('ZTEST-R2', $room->code);
            $this->assertEquals('Phong Kiem Thu Sua', $room->name);

            // Thiếu kho cha -> lỗi validate
            $this->withSession($session)
                ->post('/material/materData/zone/room/store', ['code' => 'ZTEST-R3', 'name' => 'Thieu kho'])
                ->assertSessionHasErrors(['warehouse_id'], null, 'create_room');

            // KHOÁ / MỞ KHOÁ
            $this->withSession($session)
                ->post('/material/materData/zone/room/deActive', ['id' => $room->id])
                ->assertSessionHas('success');
            $this->assertEquals(0, DB::table('rooms')->where('id', $room->id)->value('status_id'));

            $this->withSession($session)
                ->post('/material/materData/zone/room/deActive', ['id' => $room->id])
                ->assertSessionHas('success');
            $this->assertEquals(1, DB::table('rooms')->where('id', $room->id)->value('status_id'));

            // XOÁ kho khi còn phòng trực thuộc -> bị chặn
            $this->withSession($session)
                ->post('/material/materData/zone/warehouse/destroy', ['id' => $warehouse->id])
                ->assertSessionHas('error');
            $this->assertEquals(1, DB::table('warehouses')->where('id', $warehouse->id)->count());

            // Xoá từ dưới lên thì được
            $this->withSession($session)
                ->post('/material/materData/zone/room/destroy', ['id' => $room->id])
                ->assertSessionHas('success');
            $this->assertEquals(0, DB::table('rooms')->where('id', $room->id)->count());

            $this->withSession($session)
                ->post('/material/materData/zone/warehouse/destroy', ['id' => $warehouse->id])
                ->assertSessionHas('success');
            $this->assertEquals(0, DB::table('warehouses')->where('id', $warehouse->id)->count());
        } finally {
            DB::rollBack();
        }
    }

    public function test_unknown_zone_type_is_rejected(): void
    {
        $response = $this->withSession($this->fakeSession('vat_tu'))
            ->post('/material/materData/zone/hacker/store', ['code' => 'X', 'name' => 'Y']);

        $response->assertStatus(404);
    }
}
