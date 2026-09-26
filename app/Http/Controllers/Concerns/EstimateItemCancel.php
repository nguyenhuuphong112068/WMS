<?php

namespace App\Http\Controllers\Concerns;

use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\MaterialPurchasing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * HUỶ MỤC DỰ TRÙ CẦN XÁC NHẬN CỦA HAI BÊN - dùng chung cho dự trù vật tư / hoá chất / chất chuẩn.
 *
 * Phòng đề nghị (trang chi tiết phiếu, qua updateItemStatus) và bộ phận mua hàng (tab
 * "Bộ phận mua hàng", qua purchaseCancel) mỗi bên ghi một cặp cột cancel_requester_* /
 * cancel_purchasing_*. Bên nào bấm trước thì mục chuyển "Chờ xác nhận huỷ" (status_id vẫn 1);
 * đủ cả hai bên mới đặt status_id = 0. Bên kia từ chối / bên đề nghị rút lại thì xoá hết.
 *
 * Bộ phận mua hàng: vật tư theo material_categories.purchasing_department; hoá chất, chất
 * chuẩn và vật tư ngoài danh mục mặc định Cung Ứng - xem App\Support\MaterialPurchasing.
 *
 * Lớp dùng trait khai các hằng TABLE / ITEM_TABLE / EST_ROUTE / ITEM_LABEL, hàm actor(),
 * departmentId(), nullIfBlank() và cancelConfig():
 *   [
 *     'item_fk'         => cột trỏ về phiếu trên bảng mục (vd. 'material_estimate_id'),
 *     'chat_type'       => item_type ghi vào estimate_item_chats,
 *     'category_table'  => bảng danh mục chung,
 *     'name_table'      => bảng tên, 'name_fk' => cột trỏ tên trên bảng danh mục,
 *     'manual_name'     => cột tên tự gõ trên bảng mục (mục ngoài danh mục),
 *     'purchasing_col'  => cột bộ phận mua hàng trên bảng danh mục, null = luôn Cung Ứng,
 *   ]
 */
trait EstimateItemCancel
{
    abstract protected function cancelConfig(): array;

    /** Chạy trong transaction sau khi trạng thái một mục đổi (vd. tính lại "đã hoàn tất phiếu"). */
    protected function afterItemStatusChange($list): void {}

    public function purchaseCancel(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'action' => 'required|in:cancel,cancel_reject,cancel_withdraw',
        ]);

        $cfg = $this->cancelConfig();

        $item = DB::table(self::ITEM_TABLE)
            ->join(self::TABLE, self::ITEM_TABLE.'.'.$cfg['item_fk'], '=', self::TABLE.'.id')
            ->leftJoin('deparments', self::TABLE.'.department_id', '=', 'deparments.id')
            ->leftJoin($cfg['category_table'], self::ITEM_TABLE.'.category_id', '=', $cfg['category_table'].'.id')
            ->select(
                self::ITEM_TABLE.'.*',
                self::TABLE.'.app_status as list_app_status',
                'deparments.company_id as list_company_id',
                $cfg['purchasing_col']
                    ? DB::raw($cfg['category_table'].'.'.$cfg['purchasing_col'].' as purchasing_department')
                    : DB::raw('NULL as purchasing_department')
            )
            ->where(self::ITEM_TABLE.'.id', $request->id)
            ->where(self::ITEM_TABLE.'.active', 1)
            ->first();

        if (! $item || $item->list_app_status !== 'approved') {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::ITEM_LABEL.' đã duyệt cần xử lý!');
        }

        if (! MaterialPurchasing::isPurchaser($this->departmentId(), $item->purchasing_department, $item->list_company_id)) {
            return redirect()->back()->with('error', 'Phòng ban đang chọn không phải bộ phận mua hàng của mặt hàng này!');
        }

        return $this->applyCancel($item, 'purchasing', $request->action, $request->cancel_reason);
    }

    /**
     * $side = 'requester' | 'purchasing'.
     * - cancel          : bên này đồng ý huỷ; bên kia đã đồng ý -> huỷ thật, chưa -> chờ (bắt buộc lý do).
     * - cancel_reject   : không đồng ý đề nghị huỷ của bên kia.
     * - cancel_withdraw : bên đã đề nghị rút lại khi bên kia chưa xác nhận.
     */
    protected function applyCancel($item, string $side, string $action, ?string $reason)
    {
        $own = $side === 'requester' ? 'cancel_requester' : 'cancel_purchasing';
        $other = $side === 'requester' ? 'cancel_purchasing' : 'cancel_requester';
        $sideLabel = $side === 'requester' ? 'Phòng đề nghị' : 'Bộ phận mua hàng';
        $otherLabel = $side === 'requester' ? 'bộ phận mua hàng' : 'phòng đề nghị';

        if ((int) $item->status_id === 0 || $item->fulfilled_date) {
            return redirect()->back()->with('error', 'Mục đã huỷ hoặc đã giao, không xử lý huỷ được nữa!');
        }

        $ownDone = (bool) $item->{$own.'_at'};
        $otherDone = (bool) $item->{$other.'_at'};
        $reason = $this->nullIfBlank($reason);

        if ($action === 'cancel') {
            if ($ownDone) {
                return redirect()->back()->with('error', $sideLabel.' đã xác nhận huỷ rồi, đang chờ '.$otherLabel.'!');
            }

            if (! $otherDone && ! $reason) {
                return redirect()->back()->with('error', 'Vui lòng nhập lý do huỷ!');
            }

            $update = [$own.'_by' => $this->actor(), $own.'_at' => now()];

            if (! $otherDone) {
                $update['cancel_reason'] = $reason;
                $message = $sideLabel.' đề nghị huỷ mặt hàng. Lý do: '.$reason.' - chờ '.$otherLabel.' xác nhận.';
                $flash = 'Đã gửi đề nghị huỷ, chờ '.$otherLabel.' xác nhận!';
            } else {
                $update['status_id'] = 0;
                $message = $sideLabel.' xác nhận huỷ. Mặt hàng đã huỷ (đủ xác nhận của hai bên).'.($reason ? ' Ghi chú: '.$reason : '');
                $flash = 'Đã huỷ '.self::ITEM_LABEL.' (đủ xác nhận của hai bên)!';
            }
        } elseif ($action === 'cancel_reject') {
            if (! $otherDone || $ownDone) {
                return redirect()->back()->with('error', 'Không có đề nghị huỷ nào của '.$otherLabel.' để từ chối!');
            }

            $update = $this->clearCancel();
            $message = $sideLabel.' không đồng ý huỷ mặt hàng.'.($reason ? ' Lý do: '.$reason : '');
            $flash = 'Đã từ chối đề nghị huỷ!';
        } else {
            if (! $ownDone || $otherDone) {
                return redirect()->back()->with('error', 'Không có đề nghị huỷ nào để rút lại!');
            }

            $update = $this->clearCancel();
            $message = $sideLabel.' rút lại đề nghị huỷ mặt hàng.';
            $flash = 'Đã rút lại đề nghị huỷ!';
        }

        $fk = $this->cancelConfig()['item_fk'];

        DB::transaction(function () use ($item, $update, $message, $fk) {
            DB::table(self::ITEM_TABLE)->where('id', $item->id)->update($update + ['updated_at' => now()]);
            $this->itemSystemChat($item->id, $message);

            if (isset($update['status_id'])) {
                $this->afterItemStatusChange(DB::table(self::TABLE)->where('id', $item->{$fk})->first());
            }
        });

        AuditTrialController::log('Cập nhật', self::ITEM_TABLE, $item->id, 'NA', $message);

        return redirect()->back()->with('success', $flash);
    }

    protected function cancelPending($item): bool
    {
        return (bool) ($item->cancel_requester_at || $item->cancel_purchasing_at);
    }

    protected function clearCancel(): array
    {
        return [
            'cancel_reason' => null,
            'cancel_requester_by' => null,
            'cancel_requester_at' => null,
            'cancel_purchasing_by' => null,
            'cancel_purchasing_at' => null,
        ];
    }

    protected function itemSystemChat(int $itemId, string $content): void
    {
        DB::table('estimate_item_chats')->insert([
            'item_id' => $itemId,
            'item_type' => $this->cancelConfig()['chat_type'],
            'user_name' => $this->actor(),
            'content' => $content,
            'type' => 'system',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Tab "Bộ phận mua hàng": mục đã duyệt (cùng công ty) do phòng đang chọn phụ trách mua,
     * chưa giao / chưa huỷ; đề nghị huỷ chờ xác nhận lên đầu. Không phải bộ phận mua hàng -> null.
     */
    protected function purchasingItems(int $departmentId)
    {
        $keys = MaterialPurchasing::keysOfDepartment($departmentId);
        $cfg = $this->cancelConfig();

        // Loại hàng không khai bộ phận mua hàng thì chỉ Cung Ứng (mặc định) phụ trách
        if (! $keys || (! $cfg['purchasing_col'] && ! in_array(MaterialPurchasing::DEFAULT_KEY, $keys, true))) {
            return null;
        }

        $companyId = DB::table('deparments')->where('id', $departmentId)->value('company_id');
        $cat = $cfg['category_table'];

        return DB::table(self::ITEM_TABLE)
            ->join(self::TABLE, self::ITEM_TABLE.'.'.$cfg['item_fk'], '=', self::TABLE.'.id')
            ->leftJoin('deparments', self::TABLE.'.department_id', '=', 'deparments.id')
            ->leftJoin($cat, self::ITEM_TABLE.'.category_id', '=', $cat.'.id')
            ->leftJoin($cfg['name_table'], $cat.'.'.$cfg['name_fk'], '=', $cfg['name_table'].'.id')
            ->select(
                self::ITEM_TABLE.'.*',
                self::TABLE.'.code as list_code',
                'deparments.shortName as department_short_name',
                $cfg['name_table'].'.name as category_name',
                $cat.'.code as category_code',
                DB::raw(self::ITEM_TABLE.'.'.$cfg['manual_name'].' as manual_name')
            )
            ->where(self::TABLE.'.app_status', 'approved')
            ->where(self::ITEM_TABLE.'.active', 1)
            ->where(self::ITEM_TABLE.'.status_id', 1)
            ->whereNull(self::ITEM_TABLE.'.fulfilled_date')
            ->when($companyId, fn ($q) => $q->where('deparments.company_id', $companyId))
            ->when($cfg['purchasing_col'], function ($q) use ($keys, $cat, $cfg) {
                $q->where(function ($q) use ($keys, $cat, $cfg) {
                    $q->whereIn($cat.'.'.$cfg['purchasing_col'], $keys);
                    if (in_array(MaterialPurchasing::DEFAULT_KEY, $keys, true)) {
                        $q->orWhereNull($cat.'.'.$cfg['purchasing_col']);
                    }
                });
            })
            ->orderByRaw('CASE WHEN '.self::ITEM_TABLE.'.cancel_requester_at IS NOT NULL AND '
                .self::ITEM_TABLE.'.cancel_purchasing_at IS NULL THEN 0 ELSE 1 END')
            ->orderBy(self::TABLE.'.code', 'desc')
            ->get()
            ->map(function ($item) {
                $item->display_name = $item->category_id ? $item->category_name : $item->manual_name;

                return $item;
            });
    }
}
