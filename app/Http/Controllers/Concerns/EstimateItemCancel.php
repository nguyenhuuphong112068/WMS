<?php

namespace App\Http\Controllers\Concerns;

use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\MaterialPurchasing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * HUỶ MỤC DỰ TRÙ CẦN XÁC NHẬN CỦA HAI BÊN - dùng chung cho dự trù vật tư / hoá chất / chất chuẩn.
 *
 * Phòng đề nghị bấm Huỷ trên trang chi tiết phiếu (updateItemStatus) -> mục "Chờ xác nhận huỷ"
 * (status_id vẫn 1). Bộ phận mua hàng mở phiếu từ tab "Đơn dự trù đã được phê duyệt", nút
 * Đồng ý huỷ / Không đồng ý hiện ngay trên mục đó (purchaseCancel) - CHỈ khi phòng đề nghị đã
 * bấm huỷ, bộ phận mua hàng không tự đề nghị huỷ. Đủ cả hai bên mới đặt status_id = 0.
 * Mỗi bên ghi một cặp cột cancel_requester_* / cancel_purchasing_*.
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
 *     'purchasing_permission' => quyền tiếp nhận xử lý (estimate_<loại>_purchasing): ngoài việc
 *                        đang chọn đúng phòng mua hàng, user còn phải có quyền này mới thấy tab
 *                        "Đơn dự trù đã được phê duyệt" và xác nhận huỷ phía mua hàng,
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
            'action' => 'required|in:cancel,cancel_reject',
        ]);

        $cfg = $this->cancelConfig();

        if (! user_can($cfg['purchasing_permission'])) {
            return redirect()->back()->with('error', 'Bạn không có quyền tiếp nhận xử lý '.self::ITEM_LABEL.' của bộ phận mua hàng!');
        }

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

        // Bộ phận mua hàng chỉ trả lời đề nghị huỷ của phòng đề nghị, không tự đề nghị huỷ
        if (! $item->cancel_requester_at || $item->cancel_purchasing_at || (int) $item->status_id !== 1) {
            return redirect()->back()->with('error', 'Phòng đề nghị chưa đề nghị huỷ '.self::ITEM_LABEL.' này, hoặc đề nghị đã được xử lý!');
        }

        if (! $this->isAdminUser()
            && ! MaterialPurchasing::isPurchaser($this->departmentId(), $item->purchasing_department, $item->list_company_id)) {
            return redirect()->back()->with('error', 'Phòng ban đang chọn không phải bộ phận mua hàng của mặt hàng này!');
        }

        return $this->applyCancel($item, 'purchasing', $request->action, $request->cancel_reason);
    }

    /**
     * TIẾP NHẬN phiếu dự trù vừa được Ban Giám Đốc duyệt (reception_status waiting -> received).
     * Bộ phận mua hàng bấm ở Trang chủ / trang chi tiết phiếu (mở từ tab "Đơn dự trù đã được phê
     * duyệt"); lưu người + thời điểm tiếp nhận (received_by / received_at), ghi nhật ký phiếu.
     */
    public function receive(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'reception_note' => 'nullable|string|max:500',
        ]);

        $list = DB::table(self::TABLE)->where('id', $request->id)->first();

        if (! $list || ! $this->canViewAsPurchaser($list)) {
            return redirect()->back()->with('error', 'Không tìm thấy phiếu đã duyệt, hoặc bạn không phải bộ phận mua hàng tiếp nhận phiếu này!');
        }

        if ($list->reception_status !== 'waiting') {
            return redirect()->back()->with('error', 'Phiếu '.$list->code.' đã được tiếp nhận bởi '.($list->received_by ?: '—').'!');
        }

        $note = trim((string) $request->reception_note) ?: null;

        DB::table(self::TABLE)->where('id', $list->id)->update([
            'reception_status' => 'received',
            'received_by' => $this->actor(),
            'received_at' => now(),
            'reception_note' => $note,
        ]);

        self::writeHistory($list->id, 'Tiếp nhận', null, 'waiting', 'received', $note);
        AuditTrialController::log('Tiếp nhận', self::TABLE, $list->id, $list->code, 'reception_status: waiting -> received');

        return redirect()->back()->with('success', 'Đã tiếp nhận phiếu dự trù '.$list->code.'!');
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
     * User có quyền tiếp nhận xử lý VÀ phòng đang chọn là bộ phận mua hàng của loại dự trù này
     * (loại hàng không khai bộ phận mua hàng thì chỉ Cung Ứng - mặc định - phụ trách).
     */
    protected function isPurchasingViewer(int $departmentId): bool
    {
        $cfg = $this->cancelConfig();

        if (! user_can($cfg['purchasing_permission'])) {
            return false;
        }

        // Admin xem / xử lý được mọi phần của bộ phận mua hàng ở bất kỳ phòng nào
        if ($this->isAdminUser()) {
            return true;
        }

        $keys = MaterialPurchasing::keysOfDepartment($departmentId);

        return $keys && ($cfg['purchasing_col'] || in_array(MaterialPurchasing::DEFAULT_KEY, $keys, true));
    }

    private function isAdminUser(): bool
    {
        return user_has_any_role(session('user')['userId'] ?? 0, ['Admin']);
    }

    /**
     * Tab "Đơn dự trù đã được phê duyệt": mọi phiếu đã duyệt xong của TẤT CẢ phòng ban (mọi công
     * ty), kỳ gần nhất trước. Chỉ bộ phận mua hàng xem được (isPurchasingViewer), còn lại -> null.
     */
    protected function approvedLists(int $departmentId)
    {
        if (! $this->isPurchasingViewer($departmentId)) {
            return null;
        }

        $fk = $this->cancelConfig()['item_fk'];

        $itemStats = $this->itemStatsQuery();

        return DB::table(self::TABLE)
            ->leftJoin('deparments', self::TABLE.'.department_id', '=', 'deparments.id')
            ->leftJoin('companies', 'deparments.company_id', '=', 'companies.id')
            ->leftJoinSub($itemStats, 'item_stats', 'item_stats.'.$fk, '=', self::TABLE.'.id')
            ->select(
                self::TABLE.'.*',
                'deparments.shortName as department_short_name',
                'companies.short_name as company_short_name',
                DB::raw('COALESCE(item_stats.item_pending_cancel, 0) as pending_cancel_count'),
                DB::raw('COALESCE(item_stats.item_total, 0) as item_total'),
                DB::raw('COALESCE(item_stats.item_cancelled, 0) as item_cancelled'),
                DB::raw('COALESCE(item_stats.item_fulfilled, 0) as item_fulfilled')
            )
            ->where(self::TABLE.'.app_status', 'approved')
            // Phiếu chờ tiếp nhận, rồi phiếu có mục chờ xác nhận huỷ lên đầu
            ->orderByRaw("CASE WHEN ".self::TABLE.".reception_status = 'waiting' THEN 0 ELSE 1 END")
            ->orderByRaw('CASE WHEN item_stats.item_pending_cancel > 0 THEN 0 ELSE 1 END')
            ->orderBy(self::TABLE.'.year', 'desc')
            ->orderBy(self::TABLE.'.month', 'desc')
            ->orderBy(self::TABLE.'.id', 'desc')
            ->get()
            ->each(fn ($row) => $row->approved_status = self::approvedStatus($row));
    }

    /**
     * Đếm mục theo phiếu: tổng, đã huỷ, đã giao, chờ xác nhận huỷ (phòng đề nghị đã bấm huỷ, chờ bộ
     * phận mua hàng) - để suy tình trạng phiếu (approvedStatus). Nhóm theo cột item_fk.
     */
    private function itemStatsQuery()
    {
        $fk = $this->cancelConfig()['item_fk'];

        return DB::table(self::ITEM_TABLE)
            ->select(
                $fk,
                DB::raw('COUNT(*) as item_total'),
                DB::raw('SUM(CASE WHEN status_id = 0 THEN 1 ELSE 0 END) as item_cancelled'),
                DB::raw('SUM(CASE WHEN status_id = 1 AND fulfilled_date IS NOT NULL THEN 1 ELSE 0 END) as item_fulfilled'),
                DB::raw('SUM(CASE WHEN status_id = 1 AND cancel_requester_at IS NOT NULL AND cancel_purchasing_at IS NULL THEN 1 ELSE 0 END) as item_pending_cancel')
            )
            ->where('active', 1)
            ->groupBy($fk);
    }

    /**
     * Gắn approved_status (tình trạng xử lý) cho các phiếu ĐÃ DUYỆT trong $rows - cột "Tình Trạng"
     * ở tab "Danh sách phiếu"; phiếu chưa duyệt -> null.
     */
    protected function attachApprovedStatus($rows): void
    {
        $fk = $this->cancelConfig()['item_fk'];
        $approvedIds = $rows->where('app_status', 'approved')->pluck('id')->all();
        $stats = $approvedIds
            ? $this->itemStatsQuery()->whereIn($fk, $approvedIds)->get()->keyBy($fk)
            : collect();

        $rows->each(function ($row) use ($stats) {
            if ($row->app_status !== 'approved') {
                $row->approved_status = null;

                return;
            }

            $stat = $stats->get($row->id);
            $row->item_total = (int) ($stat->item_total ?? 0);
            $row->item_cancelled = (int) ($stat->item_cancelled ?? 0);
            $row->item_fulfilled = (int) ($stat->item_fulfilled ?? 0);
            $row->approved_status = self::approvedStatus($row);
        });
    }

    /**
     * Tình trạng một phiếu đã duyệt, theo thứ tự ưu tiên:
     *  - cancelled : mọi mục đều bị huỷ
     *  - waiting   : chưa ai tiếp nhận
     *  - completed : mọi mục còn hiệu lực đã giao
     *  - progress  : đã hoàn thành n/m mục (m = mục không bị huỷ)
     * Trả ['key', 'label', 'done', 'total'].
     */
    public static function approvedStatus($row): array
    {
        $total = (int) $row->item_total - (int) $row->item_cancelled;
        $done = (int) $row->item_fulfilled;

        [$key, $label] = match (true) {
            (int) $row->item_total > 0 && $total === 0 => ['cancelled', 'Huỷ'],
            $row->reception_status === 'waiting' => ['waiting', 'Chưa tiếp nhận'],
            $total > 0 && $done >= $total => ['completed', 'Hoàn thành'],
            default => ['progress', "Đã hoàn thành {$done}/{$total} mục"],
        };

        return ['key' => $key, 'label' => $label, 'done' => $done, 'total' => $total];
    }

    /**
     * Phiếu bộ phận mua hàng được MỞ XEM dù không thuộc phòng đang chọn: phiếu đã duyệt, phòng
     * đang chọn là bộ phận mua hàng. Trang chi tiết khi đó chỉ đọc - mọi thao tác vẫn đi qua
     * findOwn() nên chỉ phòng lập phiếu làm được.
     */
    protected function canViewAsPurchaser($list): bool
    {
        return $list->app_status === 'approved' && $this->isPurchasingViewer($this->departmentId());
    }

    /**
     * Mục + phiếu cho bộ phận mua hàng hẹn ngày đáp ứng (updatePromisedDate): phiếu đã duyệt của
     * MỌI phòng ban, chỉ khi canViewAsPurchaser(). Phòng đề nghị chỉ xem ngày hẹn, không sửa.
     */
    protected function findPurchasingItem($id): array
    {
        $item = DB::table(self::ITEM_TABLE)->where('id', $id)->where('active', 1)->first();
        $list = $item ? DB::table(self::TABLE)->where('id', $item->{$this->cancelConfig()['item_fk']})->first() : null;

        return $list && $this->canViewAsPurchaser($list) ? [$item, $list] : [null, null];
    }

    /**
     * Mục đã duyệt (mọi công ty) do phòng đang chọn phụ trách mua, chưa giao / chưa huỷ - gộp vào
     * tab "Theo dõi dự trù" của bộ phận mua hàng. Không phải bộ phận mua hàng hoặc không có quyền
     * tiếp nhận xử lý -> null.
     */
    protected function purchasingItems(int $departmentId)
    {
        if (! $this->isPurchasingViewer($departmentId)) {
            return null;
        }

        $keys = MaterialPurchasing::keysOfDepartment($departmentId);
        $cfg = $this->cancelConfig();

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
            // Bộ phận mua hàng tiếp nhận phiếu của MỌI công ty (NM1 không có bộ phận mua hàng).
            // Admin thấy mục của mọi bộ phận mua hàng
            ->when($cfg['purchasing_col'] && ! $this->isAdminUser(), function ($q) use ($keys, $cat, $cfg) {
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
