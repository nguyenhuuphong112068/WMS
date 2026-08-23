<?php

namespace App\Http\Controllers\Pages\Estimate;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * DỰ TRÙ - TIẾP NHẬN DỰ TRÙ
 *
 * Màn hình của bộ phận Cung Ứng. Chỉ hiện những phiếu dự trù đã đi hết 2 bước trình ký
 * (estimate_lists.app_status = 'approved') của TẤT CẢ phòng ban, không lọc theo phòng
 * ban đang chọn như màn Dự Trù Hoá Chất.
 *
 * Luồng xử lý của Cung Ứng - cột reception_status:
 *   waiting (Chờ tiếp nhận) -> [Tiếp nhận] -> received (Đang giải quyết)
 *                           -> [Hoàn tất]  -> completed (Đã giải quyết)
 *
 * Không sửa nội dung phiếu ở đây, chỉ cập nhật tình trạng giải quyết và ghi chú.
 * Mọi thao tác ghi vào estimate_list_histories để phòng ban theo dõi được ngay trên
 * danh sách dự trù của mình.
 */
class EstimateReceptionController extends Controller
{
    private const TABLE = 'estimate_lists';

    private const LABEL = 'phiếu dự trù';

    public function index()
    {
        $datas = DB::table(self::TABLE)
            ->leftJoin('deparments', self::TABLE.'.department_id', '=', 'deparments.id')
            ->select(
                self::TABLE.'.*',
                'deparments.name as department_name',
                'deparments.shortName as department_short_name'
            )
            ->where(self::TABLE.'.app_status', 'approved')
            ->orderByRaw("FIELD(".self::TABLE.".reception_status, 'waiting', 'received', 'completed')")
            ->orderBy(self::TABLE.'.director_signed_at', 'desc')
            ->orderBy(self::TABLE.'.id', 'desc')
            ->get();

        session()->put(['title' => 'DỰ TRÙ - TIẾP NHẬN DỰ TRÙ']);

        return view('pages.estimate.EstimateReception.list', [
            'datas' => $datas,
            'itemCounts' => $this->itemCounts($datas->pluck('id')->all()),
            'appStatuses' => config('estimate.app_statuses'),
            'signSteps' => config('estimate.sign_steps'),
            'receptionStatuses' => config('estimate.reception_statuses'),
            'canReceive' => $this->canReceive(),
        ]);
    }

    /** Trang chi tiết phiếu - dùng chung view với màn Dự Trù Hoá Chất, ở chế độ chỉ xem. */
    public function detail(Request $request)
    {
        $list = DB::table(self::TABLE)
            ->leftJoin('deparments', self::TABLE.'.department_id', '=', 'deparments.id')
            ->select(self::TABLE.'.*', 'deparments.name as department_name', 'deparments.shortName as department_short_name')
            ->where(self::TABLE.'.id', $request->id)
            ->where(self::TABLE.'.app_status', 'approved')
            ->first();

        if (! $list) {
            return redirect()->route('pages.estimate.estimateReception.list')
                ->with('error', 'Không tìm thấy '.self::LABEL.' đã phê duyệt tương ứng!');
        }

        session()->put(['title' => 'DỰ TRÙ - CHI TIẾT PHIẾU '.$list->code]);

        return view('pages.estimate.shared.detail', [
            'list' => $list,
            'items' => ChemicalEstimateController::itemsOf($list->id),
            'histories' => ChemicalEstimateController::historiesOf($list->id),
            'categories' => collect(),
            'units' => collect(),
            'appStatuses' => config('estimate.app_statuses'),
            'signSteps' => config('estimate.sign_steps'),
            'receptionStatuses' => config('estimate.reception_statuses'),
            'canEditItems' => false,
            'backRoute' => route('pages.estimate.estimateReception.list'),
            'estRoute' => 'pages.estimate.estimateReception.',
        ]);
    }

    public function history(Request $request)
    {
        return response()->json(['rows' => ChemicalEstimateController::historiesOf((int) $request->id)]);
    }

    /** Chờ tiếp nhận -> Đang giải quyết. */
    public function receive(Request $request)
    {
        return $this->setReception($request, 'waiting', 'received', 'Tiếp nhận');
    }

    /** Đang giải quyết -> Đã giải quyết. */
    public function complete(Request $request)
    {
        return $this->setReception($request, 'received', 'completed', 'Giải quyết xong');
    }

    /** Chuyển tình trạng tiếp nhận sau khi kiểm tra đúng bước và đúng quyền. */
    private function setReception(Request $request, string $from, string $to, string $action)
    {
        $current = DB::table(self::TABLE)
            ->where('id', $request->id)
            ->where('app_status', 'approved')
            ->first();

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::LABEL.' đã phê duyệt tương ứng!');
        }

        if (! $this->canReceive()) {
            return redirect()->back()->with('error', 'Chỉ bộ phận Cung Ứng mới được tiếp nhận và giải quyết phiếu dự trù!');
        }

        if (($current->reception_status ?? 'waiting') !== $from) {
            return redirect()->back()->with(
                'error',
                'Phiếu '.$current->code.' đang ở tình trạng "'.$this->receptionLabel($current->reception_status).'", không thực hiện được thao tác này!'
            );
        }

        $validator = Validator::make($request->all(), [
            'reception_note' => ['nullable', 'max:500'],
        ], [
            'reception_note.max' => 'Ghi chú tiếp nhận tối đa 500 ký tự.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'receptionErrors')->withInput();
        }

        $note = trim((string) $request->reception_note);

        $payload = [
            'reception_status' => $to,
            'reception_note' => $note === '' ? $current->reception_note : $note,
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ];

        if ($to === 'received') {
            $payload['received_by'] = $this->actor();
            $payload['received_at'] = now();
        } else {
            $payload['completed_by'] = $this->actor();
            $payload['completed_at'] = now();
        }

        DB::table(self::TABLE)->where('id', $current->id)->update($payload);

        ChemicalEstimateController::writeHistory(
            $current->id,
            $action,
            'reception',
            $current->reception_status ?? 'waiting',
            $to,
            $note === '' ? null : $note
        );

        AuditTrialController::log(
            $action,
            self::TABLE,
            $current->id,
            'reception_status: '.($current->reception_status ?? 'waiting'),
            'reception_status: '.$to
        );

        return redirect()->back()->with(
            'success',
            $to === 'received'
                ? 'Đã tiếp nhận phiếu '.$current->code.'! Phiếu chuyển sang trạng thái đang giải quyết.'
                : 'Đã hoàn tất giải quyết phiếu '.$current->code.'!'
        );
    }

    /** Số mặt hàng của từng phiếu: [estimate_list_id => số dòng]. */
    private function itemCounts(array $listIds): array
    {
        if (! $listIds) {
            return [];
        }

        return DB::table('estimate_items')
            ->select('estimate_list_id', DB::raw('COUNT(*) as total'))
            ->whereIn('estimate_list_id', $listIds)
            ->groupBy('estimate_list_id')
            ->pluck('total', 'estimate_list_id')
            ->all();
    }

    /** Người đang đăng nhập có thuộc bộ phận Cung Ứng không (Admin luôn được). */
    private function canReceive(): bool
    {
        return user_has_any_role(session('user')['userId'] ?? 0, config('estimate.supply_roles'));
    }

    private function receptionLabel(?string $status): string
    {
        return config('estimate.reception_statuses')[$status ?? 'waiting'] ?? ($status ?: '—');
    }

    private function actor(): string
    {
        return session('user')['fullName'] ?? 'NA';
    }
}
