<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * ĐỀ NGHỊ CHUYỂN VẬT TƯ LIÊN PHÒNG BAN - phần dùng chung giữa hai màn hình lập đề nghị.
 *
 * Phiếu được lập ở hai nơi, cùng ghi vào một bộ bảng:
 *   1. SỬ DỤNG VẬT TƯ, tab "Đề nghị chuyển liên phòng ban" - lập thủ công từ đầu
 *      (App\Http\Controllers\Pages\Export\MaterialExportController).
 *   2. DỰ TRÙ VẬT TƯ, tab "Danh sách vật tư cần dự trù" - gom các vật tư do Hành Chánh
 *      mua rồi gửi thẳng cho phòng Hành Chánh, thay vì lập phiếu dự trù
 *      (App\Http\Controllers\Pages\Estimate\MaterialEstimateController).
 *
 * Gom ở đây để mã phiếu và cách ghi dòng đề nghị của hai màn hình không lệch nhau. Ba bước
 * tiếp theo (phòng nhận cấp phát -> phòng đề nghị nhận hàng) vẫn do màn Sử Dụng Vật Tư xử lý.
 */
class MaterialTransferRequest
{
    public const TABLE = 'material_transfer_requests';

    public const ITEM_TABLE = 'material_transfer_items';

    /**
     * Mã phiếu kế tiếp: LPB-<phòng đề nghị>-<phòng được đề nghị>-<ddmmyy>-<NN>.
     */
    public static function nextCode(int $fromDepartmentId, int $toDepartmentId): string
    {
        $prefix = 'LPB-'.$fromDepartmentId.'-'.$toDepartmentId.'-'.date('dmy').'-';

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
     * Ghi một phiếu đề nghị kèm các dòng vật tư, trả về [id, code].
     *
     * $items là mảng các dòng ['category_id', 'requested_amount', 'requested_unit', 'note'].
     * $status là 'draft' (lưu tạm) hoặc 'pending' (gửi đi ngay) - dòng con mang cùng trạng thái.
     */
    public static function create(int $departmentId, int $toDepartmentId, array $head, array $items, string $status, string $actor): array
    {
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

        return ['id' => $id, 'code' => $code];
    }
}
