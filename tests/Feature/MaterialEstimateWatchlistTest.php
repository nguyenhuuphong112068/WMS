<?php

namespace Tests\Feature;

use App\Support\MaterialVpp;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * DỰ TRÙ VẬT TƯ - tab "Danh sách vật tư cần dự trù".
 *
 * Kiểm hai lối đi của các vật tư người dùng tick chọn:
 * - DỰ TRÙ VẬT TƯ, "Danh sách vật tư cần dự trù" (vật tư Cung Ứng mua): lập phiếu dự trù.
 * - SỬ DỤNG VẬT TƯ, Đề nghị VPP > "Danh sách vật tư cần đề nghị Hành Chính" (vật tư Hành
 *   Chính mua): gửi đề nghị tới kho VPP của phòng Hành Chính.
 *
 * Chạy trên CSDL thật nhưng mọi thao tác ghi nằm trong transaction và được rollback.
 */
class MaterialEstimateWatchlistTest extends TestCase
{
    private const LIST_URL = '/estimate/materialEstimate';

    private const EXPORT_URL = '/export/materialExport';

    private const DEPARTMENT_ID = 4;

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
                'department' => 'KTBT',
                'department_id' => self::DEPARTMENT_ID,
                'selected_department' => 'KTBT',
                'selected_department_id' => self::DEPARTMENT_ID,
            ],
        ];
    }

    private function unitId(): int
    {
        return (int) DB::table('units')->where('status_id', 1)->where('app_status', 'approved')->value('id');
    }

    private function categoryId(): int
    {
        return (int) DB::table('material_categories')
            ->where('status_id', 1)->where('app_status', 'approved')->value('id');
    }

    public function test_watchlist_tab_only_lets_create_estimate(): void
    {
        $this->withSession($this->fakeSession())->get(self::LIST_URL.'?tab=watchlist')
            ->assertStatus(200)
            ->assertSee('wlSourceFilter', false)
            ->assertSee('Thiếu tồn khả dụng cho tháng', false)
            ->assertSee('btn-wl-select-all', false)
            ->assertSee('watchlistEstimateModal', false)
            ->assertSee('estimate/materialEstimate/watchlistEstimateStore', false)
            // Vật tư Hành Chính mua đã chuyển sang tab Đề nghị VPP ở Sử Dụng Vật Tư
            ->assertDontSee('wlPurchasingFilter', false)
            ->assertDontSee('watchlistTransferStore', false);
    }

    public function test_vpp_tab_shows_watchlist_of_admin_items(): void
    {
        $this->withSession($this->fakeSession())->get(self::EXPORT_URL.'?tab=vpp&sub=watchlist')
            ->assertStatus(200)
            ->assertSee('mePaneVpp', false)
            ->assertSee('Danh sách vật tư cần đề nghị Hành Chính', false)
            ->assertSee('vppWatchlistModal', false)
            ->assertSee('export/materialExport/vppWatchlistStore', false);
    }

    public function test_create_estimate_from_selected_watchlist_items(): void
    {
        DB::beginTransaction();

        try {
            $categoryId = $this->categoryId();
            $unitId = $this->unitId();

            $this->withSession($this->fakeSession())
                ->post(self::LIST_URL.'/watchlistEstimateStore', [
                    'month' => 11,
                    'year' => 2099,
                    'note' => 'ZTEST lap tu watchlist',
                    'items' => [
                        [
                            'category_id' => $categoryId,
                            'amount' => '7',
                            'unit_id' => $unitId,
                            'for_month_year' => '2099-11',
                            'purpose' => 'ZTEST ton duoi nguong',
                        ],
                        [
                            'material_name' => 'ZTEST vat tu ngoai danh muc',
                            'amount' => '2',
                            'unit_id' => $unitId,
                            'for_month_year' => '2099-12',
                        ],
                    ],
                ])
                ->assertSessionHas('success');

            $list = DB::table('material_estimates')
                ->where('department_id', self::DEPARTMENT_ID)
                ->where('month', 11)->where('year', 2099)
                ->first();

            $this->assertNotNull($list, 'Không tạo được phiếu dự trù từ tab vật tư cần dự trù.');
            $this->assertEquals('draft', $list->app_status, 'Phiếu mới phải ở trạng thái Nháp.');

            $items = DB::table('material_estimate_items')
                ->where('material_estimate_id', $list->id)
                ->orderBy('id')
                ->get();

            $this->assertCount(2, $items);
            $this->assertEquals($categoryId, (int) $items[0]->category_id);
            $this->assertNull($items[0]->material_name, 'Lấy từ danh mục thì không lưu tên tự nhập.');
            $this->assertNull($items[1]->category_id);
            $this->assertEquals('ZTEST vat tu ngoai danh muc', $items[1]->material_name);

            $amount = DB::table('material_estimate_item_amounts')
                ->where('material_estimate_item_id', $items[0]->id)
                ->first();

            $this->assertNotNull($amount, 'Mỗi vật tư phải có một dòng số lượng.');
            $this->assertEquals('2099-11-01', $amount->for_month_year);
        } finally {
            DB::rollBack();
        }
    }

    public function test_estimate_requires_amount_and_unit(): void
    {
        $this->withSession($this->fakeSession())
            ->post(self::LIST_URL.'/watchlistEstimateStore', [
                'month' => 11,
                'year' => 2099,
                'items' => [
                    ['category_id' => $this->categoryId(), 'for_month_year' => '2099-11'],
                ],
            ])
            ->assertSessionHasErrors(['items.0.amount', 'items.0.unit_id'], null, 'watchlistEstimateErrors');
    }

    public function test_vpp_request_from_selected_watchlist_items(): void
    {
        DB::beginTransaction();

        try {
            $categoryId = (int) DB::table('material_categories')
                ->where('status_id', 1)->where('app_status', 'approved')
                ->where('purchasing_department', MaterialVpp::PURCHASING_KEY)
                ->value('id');
            $toDepartmentId = MaterialVpp::targetDepartmentId(self::DEPARTMENT_ID);

            $this->assertNotNull($toDepartmentId, 'Phải có phòng Hành Chính để gửi đề nghị VPP.');

            $this->withSession($this->fakeSession())
                ->post(self::EXPORT_URL.'/vppWatchlistStore', [
                    'title' => 'ZTEST de nghi VPP',
                    // Phòng nhận do server tự xác định, gửi phòng khác lên cũng không đổi
                    'to_department_id' => self::DEPARTMENT_ID,
                    'needed_date' => '2099-11-20',
                    'note' => 'ZTEST ghi chu',
                    'items' => [
                        ['category_id' => $categoryId, 'requested_amount' => '3', 'requested_unit' => 'cái', 'note' => 'ZTEST'],
                    ],
                ])
                ->assertSessionHas('success');

            $req = DB::table('material_transfer_requests')
                ->where('title', 'ZTEST de nghi VPP')
                ->first();

            $this->assertNotNull($req, 'Không tạo được đề nghị VPP.');
            $this->assertEquals('pending', $req->status, 'Đề nghị gửi đi phải ở trạng thái Chờ cấp phát.');
            $this->assertEquals(self::DEPARTMENT_ID, (int) $req->department_id);
            $this->assertEquals($toDepartmentId, (int) $req->to_department_id);
            $this->assertStringStartsWith('LPB-', $req->code);

            $item = DB::table('material_transfer_items')->where('transfer_request_id', $req->id)->first();

            $this->assertNotNull($item);
            $this->assertEquals($categoryId, (int) $item->category_id);
            $this->assertEquals(3.0, (float) $item->requested_amount);
        } finally {
            DB::rollBack();
        }
    }

    public function test_vpp_request_rejects_missing_category(): void
    {
        $this->withSession($this->fakeSession())
            ->post(self::EXPORT_URL.'/vppWatchlistStore', [
                'title' => 'ZTEST',
                'items' => [
                    ['requested_amount' => '1'],
                ],
            ])
            ->assertSessionHasErrors(['items.0.category_id'], null, 'vppWatchlistErrors');
    }
}
