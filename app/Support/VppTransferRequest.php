<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * ĐỀ NGHỊ CHUYỂN VPP LIÊN PHÒNG BAN - phần dùng chung giữa hai màn hình lập đề nghị.
 *
 * Phiếu được lập ở hai nơi, cùng ghi vào một bộ bảng:
 *   1. SỬ DỤNG VPP, tab "Đề nghị chuyển liên phòng ban" - lập thủ công từ đầu
 *      (App\Http\Controllers\Pages\Export\VppExportController).
 *   2. SỬ DỤNG VPP, tab "Đề nghị VPP" > "Danh sách VPP cần đề nghị Hành Chính" - gom
 *      các VPP do Hành Chính mua rồi gửi thẳng cho kho VPP của phòng Hành Chính, thay vì
 *      lập phiếu dự trù (VppExportController::vppWatchlistStore, App\Support\VppStore).
 *
 * Gom ở đây để mã phiếu và cách ghi dòng đề nghị của hai màn hình không lệch nhau. Ba bước
 * tiếp theo (phòng nhận cấp phát -> phòng đề nghị nhận hàng) vẫn do màn Sử Dụng VPP xử lý.
 */
class VppTransferRequest
{
    public const TABLE = 'vpp_transfer_requests';

    public const ITEM_TABLE = 'vpp_transfer_items';

    /**
     * Mã phiếu kế tiếp: VPP-<phòng đề nghị>-<phòng được đề nghị>-<ddmmyy>-<NN>.
     */
    public static function nextCode(int $fromDepartmentId, int $toDepartmentId): string
    {
        $prefix = 'VPP-'.$fromDepartmentId.'-'.$toDepartmentId.'-'.date('dmy').'-';

        $latestCode = DB::table(self::TABLE)
            ->where('code', 'LIKE', $prefix.'%')
            ->orderBy('id', 'desc')
            ->value('code');

        $seq = 1;

        if ($latestCode) {
            $parts = explode('-', $latestCode);
            $seq = (int) end($parts) + 1;
        }

        return $prefix.str_pad((string) $seq, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Ghi một phiếu đề nghị kèm các dòng VPP, trả về [id, code].
     *
     * $items là mảng các dòng ['category_id', 'requested_amount', 'requested_unit', 'note'].
     * $status là 'draft' (lưu tạm) hoặc 'pending' (gửi đi ngay). Gửi ngay thì ghi Lưu tạm rồi đi
     * qua VppTransferSignFlow::start(): có quy trình ký thì phiếu thành 'pending_sign', chưa
     * tới phòng được đề nghị. Gửi ngay thì kết quả có thêm khoá 'send' (kết quả của start()).
     */
    public static function create(int $departmentId, int $toDepartmentId, array $head, array $items, string $status, string $actor): array
    {
        $send = $status === 'pending';
        $status = 'draft';
        $code = self::nextCode($departmentId, $toDepartmentId);

        $id = DB::transaction(function () use ($departmentId, $toDepartmentId, $head, $items, $status, $actor, $code) {
            $id = DB::table(self::TABLE)->insertGetId([
                'code' => $code,
                'title' => $head['title'] ?? null,
                'department_id' => $departmentId,
                'to_department_id' => $toDepartmentId,
                'status' => $status,
                'note' => $head['note'] ?? null,
                'needed_date' => $head['needed_date'] ?? null,
                'created_by' => $actor,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($items as $item) {
                DB::table(self::ITEM_TABLE)->insert([
                    'transfer_request_id' => $id,
                    'category_id' => (int) $item['category_id'],
                    'requested_amount' => (float) ($item['requested_amount'] ?? 0),
                    'requested_unit' => $item['requested_unit'] ?? null,
                    'note' => $item['note'] ?? null,
                    'status' => $status,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return $id;
        });

        return ['id' => $id, 'code' => $code]
            + ($send ? ['send' => VppTransferSignFlow::start((int) $id, $actor)] : []);
    }

    /**
     * Phiếu đề nghị đang Lưu tạm (Nháp) của phòng gửi tới một phòng - cho tuỳ chọn "Thêm vào
     * đề nghị nháp" ở tab Danh sách VPP cần đề nghị Hành Chính. Kèm số mục còn hiệu lực.
     */
    public static function drafts(int $departmentId, ?int $toDepartmentId)
    {
        if (! $toDepartmentId) {
            return collect();
        }

        return DB::table(self::TABLE.' as r')
            ->leftJoin(self::ITEM_TABLE.' as i', function ($join) {
                $join->on('i.transfer_request_id', '=', 'r.id')->where('i.active', 1);
            })
            ->select('r.id', 'r.code', 'r.title', 'r.created_at', 'r.updated_at', DB::raw('COUNT(i.id) as item_count'))
            ->where('r.department_id', $departmentId)
            ->where('r.to_department_id', $toDepartmentId)
            ->where('r.status', 'draft')
            ->groupBy('r.id', 'r.code', 'r.title', 'r.created_at', 'r.updated_at')
            ->orderByDesc('r.updated_at')
            ->orderByDesc('r.id')
            ->get();
    }

    /**
     * Thêm dòng VPP vào một phiếu đang Lưu tạm, phiếu vẫn ở trạng thái Nháp (người dùng tự
     * gửi đi sau). $items cùng dạng với create(). Người gọi đã kiểm tra phiếu là Nháp của phòng.
     */
    public static function appendItems(int $requestId, array $items, string $actor): void
    {
        DB::transaction(function () use ($requestId, $items, $actor) {
            foreach ($items as $item) {
                DB::table(self::ITEM_TABLE)->insert([
                    'transfer_request_id' => $requestId,
                    'category_id' => (int) $item['category_id'],
                    'requested_amount' => (float) ($item['requested_amount'] ?? 0),
                    'requested_unit' => $item['requested_unit'] ?? null,
                    'note' => $item['note'] ?? null,
                    'status' => 'draft',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table(self::TABLE)->where('id', $requestId)->update([
                'updated_by' => $actor,
                'updated_at' => now(),
            ]);
        });
    }
}
