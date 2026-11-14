<?php

namespace App\Http\Controllers\Concerns;

use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\TransferSignFlow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * KÝ DUYỆT ĐỀ NGHỊ LIÊN PHÒNG BAN cho màn Sử Dụng Hoá Chất / Chất Chuẩn (App\Support\TransferSignFlow).
 *
 * Controller dùng trait khai:
 *   - hằng TRANSFER_REQUEST_TABLE, TRANSFER_ITEM_TABLE
 *   - transferSignFlowClass(): lớp con của TransferSignFlow
 *   - transferSignTab(): tab mở lại sau thao tác
 *   - actor(), departmentId() (sẵn có ở controller)
 */
trait TransferSigning
{
    use VerifiesSignature;

    /** @return class-string<TransferSignFlow> */
    abstract protected function transferSignFlowClass(): string;

    abstract protected function transferSignTab(): string;

    /** Gửi phiếu đang Lưu tạm vào quy trình ký 2 bước. @return array{status, error, steps} */
    protected function startTransferSign(int $requestId): array
    {
        return ($this->transferSignFlowClass())::start($requestId, $this->actor());
    }

    /** Câu báo sau khi gửi + loại flash (error khi chưa gửi được). @return array{0: string, 1: string} */
    protected function transferSendFlash(array $result, string $code, int $toDepartmentId, string $kindLabel): array
    {
        $toDeptName = DB::table('deparments')->where('id', $toDepartmentId)->value('name') ?: 'phòng ban khác';

        return [
            $result['error'] ? 'error' : 'success',
            ($this->transferSignFlowClass())::sendMessage($result, $code, $kindLabel, $toDeptName),
        ];
    }

    /**
     * Phiếu chờ ký ở BƯỚC CỦA PHÒNG $departmentId (dùng với ->where(...) trên truy vấn có bảng phiếu):
     * phòng cấp phát thấy phiếu từ lúc tới lượt quản lý bộ phận của mình ký (bước 2).
     */
    protected function transferAwaitingSignAt(int $departmentId): callable
    {
        $flow = $this->transferSignFlowClass();
        $table = self::TRANSFER_REQUEST_TABLE;

        return fn ($query) => $query->where($table.'.status', $flow::STATUS_PENDING_SIGN)
            ->whereExists(fn ($sub) => $sub->selectRaw('1')
                ->from($flow::SIGN_TABLE)
                ->whereColumn($flow::SIGN_TABLE.'.transfer_request_id', $table.'.id')
                ->whereColumn($flow::SIGN_TABLE.'.step_no', $table.'.current_step')
                ->where($flow::SIGN_TABLE.'.active', 1)
                ->where($flow::SIGN_TABLE.'.department_id', $departmentId));
    }

    /**
     * Bước ký của các phiếu đang hiện + những phiếu người đăng nhập ký được.
     *
     * @return array{signs: \Illuminate\Support\Collection, signable: array<int, int>}
     */
    protected function transferSignViewData($requests): array
    {
        $flow = $this->transferSignFlowClass();
        $requests = collect($requests);

        return [
            'signs' => $flow::signsOf($requests->pluck('id')->unique()->values()->all()),
            'signable' => $flow::signableOf($requests, (int) (session('user')['userId'] ?? 0)),
        ];
    }

    /**
     * KÝ DUYỆT bước đang chờ của phiếu 'pending_sign'. Người ký xét theo vai trò + phạm vi phòng
     * ban của bước (TransferSignFlow::canSign), nhập lại mật khẩu (21 CFR Part 11).
     */
    public function transferSign(Request $request)
    {
        [$req, $sign, $stop] = $this->transferSignTarget($request, 'Ký duyệt đề nghị liên phòng ban');

        if ($stop) {
            return $stop;
        }

        $flow = $this->transferSignFlowClass();
        $note = trim((string) $request->note);
        $done = $flow::sign($req, $sign, $this->actor(), $note !== '' ? mb_substr($note, 0, 500) : null);

        AuditTrialController::log(
            'Ký duyệt',
            self::TRANSFER_REQUEST_TABLE,
            $req->id,
            'Bước '.$sign->step_no,
            $done ? 'status: pending' : 'Chuyển bước '.($sign->step_no + 1)
        );

        $toDeptName = DB::table('deparments')->where('id', $req->to_department_id)->value('name') ?: 'phòng cấp phát';

        return redirect()->back()->with('success', $done
            ? 'Đã ký duyệt '.$req->code.' - phiếu đã tới '.$toDeptName.' để cấp phát.'
            : 'Đã ký bước '.$sign->step_no.' của '.$req->code.'! Đã chuyển tới quản lý bộ phận '.$toDeptName.' ký bước '.($sign->step_no + 1).'.')
            ->with('activeTab', $this->transferSignTab());
    }

    /** Từ chối ở bước đang chờ: phiếu về Lưu tạm kèm lý do (bắt buộc), phòng đề nghị sửa rồi gửi lại. */
    public function transferSignReject(Request $request)
    {
        if (trim((string) $request->reason) === '') {
            return redirect()->back()->with('error', 'Vui lòng nhập lý do từ chối.')->with('activeTab', $this->transferSignTab());
        }

        [$req, $sign, $stop] = $this->transferSignTarget($request, 'Từ chối ký đề nghị liên phòng ban');

        if ($stop) {
            return $stop;
        }

        ($this->transferSignFlowClass())::reject($req, $sign, $this->actor(), mb_substr(trim((string) $request->reason), 0, 500));

        AuditTrialController::log('Từ chối ký', self::TRANSFER_REQUEST_TABLE, $req->id, 'Bước '.$sign->step_no, 'Lý do: '.$request->reason);

        return redirect()->back()
            ->with('success', 'Đã từ chối '.$req->code.' - phiếu trả về Lưu tạm cho phòng đề nghị.')
            ->with('activeTab', $this->transferSignTab());
    }

    /** Phiếu + bước đang chờ mà người đăng nhập ký được; sai thì trả kèm redirect báo lỗi. */
    private function transferSignTarget(Request $request, string $action): array
    {
        $flow = $this->transferSignFlowClass();
        $req = DB::table(self::TRANSFER_REQUEST_TABLE)->where('id', $request->transfer_request_id)->first();
        $sign = $flow::currentSign($req);
        $back = fn (string $message) => redirect()->back()->with('error', $message)->with('activeTab', $this->transferSignTab());

        if (! $req || ! $sign) {
            return [null, null, $back('Phiếu không ở bước chờ ký nên không thao tác được!')];
        }

        if (! $flow::canSign((int) (session('user')['userId'] ?? 0), $sign)) {
            return [null, null, $back('Bạn không có quyền ký bước '.$sign->step_no.' của phiếu '.$req->code.'!')];
        }

        if ($stop = $this->guardSignature($request, self::TRANSFER_REQUEST_TABLE, $req->id, $action)) {
            return [null, null, $stop->with('activeTab', $this->transferSignTab())];
        }

        return [$req, $sign, null];
    }
}
