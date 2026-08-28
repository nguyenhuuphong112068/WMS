<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Kiểm thử nhóm màn hình DỰ TRÙ:
 * - Dự Trù Hoá Chất : lập phiếu, khai mặt hàng (trong và ngoài danh mục), trình ký 2 bước.
 *   Duyệt xong phiếu TỰ đánh dấu đã tiếp nhận - không còn màn "Tiếp Nhận Dự Trù".
 *
 * Chạy trên CSDL thật nhưng mọi thao tác ghi đều nằm trong transaction và được rollback.
 */
class ChemicalEstimateSmokeTest extends TestCase
{
    private const LIST_URL = '/estimate/chemicalEstimate';

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

    private function fakeSession(): array
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

    /** Kỳ dự trù chắc chắn chưa có phiếu để không đụng dữ liệu thật. */
    private function period(): array
    {
        return ['month' => 12, 'year' => 2099];
    }

    private function unitId(): int
    {
        return (int) DB::table('units')->where('status_id', 1)->where('app_status', 'approved')->value('id');
    }

    public function test_screens_render(): void
    {
        $session = $this->fakeSession();

        $this->withSession($session)->get(self::LIST_URL)
            ->assertStatus(200)
            ->assertSee('Dự Trù Hoá Chất', false)
            ->assertSee('mdTable', false)
            ->assertSee('createModal', false)
            ->assertSee('updateModal', false)
            ->assertSee('rejectModal', false)
            ->assertSee('estimate/chemicalEstimate/store', false);
    }

    public function test_full_flow_from_draft_to_completed(): void
    {
        DB::beginTransaction();

        try {
            $session = $this->fakeSession();
            $period = $this->period();
            $unitId = $this->unitId();

            // ---------- Lập phiếu ----------
            $this->withSession($session)
                ->post(self::LIST_URL.'/store', $period + ['note' => 'ZTEST du tru'])
                ->assertSessionHas('success');

            $list = DB::table('chemical_estimates')
                ->where('department_id', 6)
                ->where('month', $period['month'])
                ->where('year', $period['year'])
                ->first();

            $this->assertNotNull($list, 'Không tạo được phiếu dự trù.');
            $this->assertEquals('draft', $list->app_status, 'Phiếu mới phải ở trạng thái Nháp.');
            $this->assertNotEmpty($list->code, 'Phiếu phải có mã.');
            $this->assertNull($list->reception_status, 'Phiếu chưa duyệt thì chưa có tình trạng tiếp nhận.');

            // Trùng kỳ dự trù -> lỗi validate
            $this->withSession($session)
                ->post(self::LIST_URL.'/store', $period)
                ->assertSessionHasErrors(['month'], null, 'createErrors');

            // ---------- Chưa có mặt hàng thì không trình ký được ----------
            $this->withSession($session)
                ->post(self::LIST_URL.'/submit', ['id' => $list->id])
                ->assertSessionHas('error');

            $this->assertEquals('draft', DB::table('chemical_estimates')->where('id', $list->id)->value('app_status'));

            // ---------- Mặt hàng lấy từ danh mục ----------
            $categoryId = (int) DB::table('chemical_categories')
                ->where('status_id', 1)->where('app_status', 'approved')->value('id');

            $this->withSession($session)
                ->post(self::LIST_URL.'/storeItem', [
                    'estimate_list_id' => $list->id,
                    'source' => 'category',
                    'category_id' => $categoryId,
                    'technical_information' => 'ZTEST do tinh khiet 99.9%',
                    'purpose' => 'ZTEST pha dong HPLC',
                    'amounts' => [
                        ['amount' => '5', 'unit_id' => $unitId, 'for_month_year' => '2099-12'],
                        ['amount' => '2.5', 'unit_id' => $unitId, 'for_month_year' => '2100-01'],
                    ],
                ])
                ->assertSessionHas('success');

            $item = DB::table('chemical_estimate_items')->where('estimate_list_id', $list->id)->first();

            $this->assertEquals($categoryId, (int) $item->category_id, 'Mặt hàng phải gắn với danh mục.');
            $this->assertNull($item->chem_name, 'Lấy từ danh mục thì không lưu tên tự nhập.');
            $this->assertEquals(2, DB::table('chemical_estimate_item_amounts')->where('estimate_item_id', $item->id)->count());

            // ---------- Mặt hàng ngoài danh mục ----------
            $this->withSession($session)
                ->post(self::LIST_URL.'/storeItem', [
                    'estimate_list_id' => $list->id,
                    'source' => 'manual',
                    'chem_name' => 'ZTEST Hoa chat ngoai danh muc',
                    'purpose' => 'ZTEST thu nghiem',
                    'amounts' => [
                        ['amount' => '1', 'unit_id' => $unitId, 'for_month_year' => '2099-12'],
                    ],
                ])
                ->assertSessionHas('success');

            $manual = DB::table('chemical_estimate_items')
                ->where('estimate_list_id', $list->id)
                ->whereNull('category_id')
                ->first();

            $this->assertNotNull($manual, 'Phải khai được hoá chất chưa có trong danh mục.');
            $this->assertEquals('ZTEST Hoa chat ngoai danh muc', $manual->chem_name);

            // Thiếu dòng số lượng -> lỗi validate
            $this->withSession($session)
                ->post(self::LIST_URL.'/storeItem', [
                    'estimate_list_id' => $list->id,
                    'source' => 'manual',
                    'chem_name' => 'ZTEST thieu so luong',
                ])
                ->assertSessionHasErrors(['amounts'], null, 'itemCreateErrors');

            // Modal mở sẵn 3 tháng: tháng nào để trống số lượng thì bị bỏ qua, không báo lỗi
            $this->withSession($session)
                ->post(self::LIST_URL.'/storeItem', [
                    'estimate_list_id' => $list->id,
                    'source' => 'manual',
                    'chem_name' => 'ZTEST bo qua thang trong',
                    'amounts' => [
                        ['amount' => '4', 'unit_id' => $unitId, 'for_month_year' => '2099-12'],
                        ['amount' => '', 'unit_id' => '', 'for_month_year' => '2100-01'],
                        ['amount' => '', 'unit_id' => '', 'for_month_year' => '2100-02'],
                    ],
                ])
                ->assertSessionHas('success');

            $pruned = DB::table('chemical_estimate_items')
                ->where('estimate_list_id', $list->id)
                ->where('chem_name', 'ZTEST bo qua thang trong')
                ->first();

            $this->assertEquals(
                1,
                DB::table('chemical_estimate_item_amounts')->where('estimate_item_id', $pruned->id)->count(),
                'Dòng tháng để trống số lượng phải bị bỏ qua.'
            );

            // Trang chi tiết mở sẵn đúng 3 tháng liên tiếp từ tháng dự trù của phiếu
            $this->withSession($session)->get(self::LIST_URL.'/detail?id='.$list->id)
                ->assertStatus(200)
                ->assertSee('data-default-periods="[&quot;2099-12&quot;,&quot;2100-01&quot;,&quot;2100-02&quot;]"', false);

            // ---------- Sửa mặt hàng: số lượng được ghi lại toàn bộ ----------
            $this->withSession($session)
                ->post(self::LIST_URL.'/updateItem', [
                    'id' => $item->id,
                    'source' => 'category',
                    'category_id' => $categoryId,
                    'technical_information' => 'ZTEST sua thong tin ky thuat',
                    'purpose' => 'ZTEST pha dong HPLC',
                    'amounts' => [
                        ['amount' => '7', 'unit_id' => $unitId, 'for_month_year' => '2099-12'],
                    ],
                ])
                ->assertSessionHas('success');

            $amounts = DB::table('chemical_estimate_item_amounts')->where('estimate_item_id', $item->id)->get();

            $this->assertCount(1, $amounts, 'Sửa mặt hàng phải ghi lại toàn bộ dòng số lượng.');
            $this->assertEquals(7.0, (float) $amounts->first()->amount);
            $this->assertEquals('2099-12-01', \Carbon\Carbon::parse($amounts->first()->for_month_year)->format('Y-m-d'));

            // ---------- Trang chi tiết khi còn sửa được ----------
            $this->withSession($session)->get(self::LIST_URL.'/detail?id='.$list->id)
                ->assertStatus(200)
                ->assertSee($list->code, false)
                ->assertSee('ZTEST Hoa chat ngoai danh muc', false)
                ->assertSee('Ngoài danh mục', false)
                ->assertSee('itemCreateModal', false)
                ->assertSee('itemUpdateModal', false)
                ->assertSee('estimate/chemicalEstimate/deleteItem', false);

            // ---------- Trình ký ----------
            $this->withSession($session)
                ->post(self::LIST_URL.'/submit', ['id' => $list->id])
                ->assertSessionHas('success');

            // Trình ký xong thì trang chi tiết chuyển sang chỉ xem
            $this->withSession($session)->get(self::LIST_URL.'/detail?id='.$list->id)
                ->assertStatus(200)
                ->assertDontSee('estimate/chemicalEstimate/storeItem', false)
                ->assertSee('Phiếu đã trình ký nên chi tiết chỉ xem', false);

            $this->assertEquals('pending_manager', DB::table('chemical_estimates')->where('id', $list->id)->value('app_status'));

            // Đã trình ký thì khoá chi tiết
            $this->withSession($session)
                ->post(self::LIST_URL.'/storeItem', [
                    'estimate_list_id' => $list->id,
                    'source' => 'manual',
                    'chem_name' => 'ZTEST them sau khi trinh ky',
                    'amounts' => [['amount' => '1', 'unit_id' => $unitId, 'for_month_year' => '2099-12']],
                ])
                ->assertSessionHas('error');

            // Chưa qua bước 1 thì Ban Giám Đốc chưa ký được
            $this->withSession($session)
                ->post(self::LIST_URL.'/signDirector', ['id' => $list->id])
                ->assertSessionHas('error');

            // ---------- Bước 1: Phó/Trưởng Phòng ----------
            $this->withSession($session)
                ->post(self::LIST_URL.'/signManager', ['id' => $list->id])
                ->assertSessionHas('success');

            $row = DB::table('chemical_estimates')->where('id', $list->id)->first();

            $this->assertEquals('pending_director', $row->app_status);
            $this->assertEquals('Nguoi Kiem Thu', $row->manager_signed_by);
            $this->assertNotNull($row->manager_signed_at);

            // ---------- Bước 2: Ban Giám Đốc ----------
            $this->withSession($session)
                ->post(self::LIST_URL.'/signDirector', ['id' => $list->id])
                ->assertSessionHas('success');

            $row = DB::table('chemical_estimates')->where('id', $list->id)->first();

            $this->assertEquals('approved', $row->app_status);
            $this->assertEquals('Nguoi Kiem Thu', $row->director_signed_by);
            // Duyệt xong phiếu tự đánh dấu đã tiếp nhận - không đi qua màn tiếp nhận nào
            $this->assertEquals('received', $row->reception_status, 'Duyệt xong phải tự đánh dấu đã tiếp nhận.');
            $this->assertEquals('Hệ thống', $row->received_by);

            // ---------- Nhật ký trình ký ghi đủ các bước ----------
            $actions = DB::table('chemical_estimate_histories')
                ->where('estimate_list_id', $list->id)
                ->pluck('action')
                ->all();

            foreach (['Trình ký', 'Ký duyệt'] as $action) {
                $this->assertContains($action, $actions, 'Thiếu bước "'.$action.'" trong nhật ký trình ký.');
            }

            $this->withSession($session)
                ->get(self::LIST_URL.'/history?id='.$list->id)
                ->assertStatus(200)
                ->assertJsonStructure(['rows' => [['action', 'step', 'from_status', 'to_status', 'created_by', 'created_at']]]);
        } finally {
            DB::rollBack();
        }
    }

    public function test_reject_sends_estimate_back_for_editing(): void
    {
        DB::beginTransaction();

        try {
            $session = $this->fakeSession();
            $unitId = $this->unitId();

            $this->withSession($session)
                ->post(self::LIST_URL.'/store', ['month' => 11, 'year' => 2099])
                ->assertSessionHas('success');

            $list = DB::table('chemical_estimates')
                ->where('department_id', 6)->where('month', 11)->where('year', 2099)->first();

            $this->withSession($session)
                ->post(self::LIST_URL.'/storeItem', [
                    'estimate_list_id' => $list->id,
                    'source' => 'manual',
                    'chem_name' => 'ZTEST tu choi',
                    'amounts' => [['amount' => '3', 'unit_id' => $unitId, 'for_month_year' => '2099-11']],
                ])
                ->assertSessionHas('success');

            $this->withSession($session)->post(self::LIST_URL.'/submit', ['id' => $list->id]);

            // Thiếu lý do -> lỗi validate
            $this->withSession($session)
                ->post(self::LIST_URL.'/reject', ['id' => $list->id])
                ->assertSessionHasErrors(['reject_reason'], null, 'rejectErrors');

            $this->withSession($session)
                ->post(self::LIST_URL.'/reject', ['id' => $list->id, 'reject_reason' => 'ZTEST so luong qua cao'])
                ->assertSessionHas('success');

            $row = DB::table('chemical_estimates')->where('id', $list->id)->first();

            $this->assertEquals('rejected', $row->app_status);
            $this->assertEquals('manager', $row->reject_step);
            $this->assertEquals('ZTEST so luong qua cao', $row->reject_reason);

            // Bị từ chối thì sửa lại được và trình ký lại từ bước 1
            $this->withSession($session)
                ->post(self::LIST_URL.'/storeItem', [
                    'estimate_list_id' => $list->id,
                    'source' => 'manual',
                    'chem_name' => 'ZTEST bo sung sau khi bi tu choi',
                    'amounts' => [['amount' => '1', 'unit_id' => $unitId, 'for_month_year' => '2099-11']],
                ])
                ->assertSessionHas('success');

            $this->withSession($session)
                ->post(self::LIST_URL.'/submit', ['id' => $list->id])
                ->assertSessionHas('success');

            $row = DB::table('chemical_estimates')->where('id', $list->id)->first();

            $this->assertEquals('pending_manager', $row->app_status);
            $this->assertNull($row->rejected_by, 'Trình ký lại phải xoá dấu vết từ chối cũ.');
            $this->assertNull($row->reject_reason);
        } finally {
            DB::rollBack();
        }
    }
}
