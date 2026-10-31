<?php

namespace App\Http\Controllers\Pages\MaterData;

use App\Http\Controllers\Concerns\RequiresChangeReason;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\DataMasterHistory;
use App\Support\MaterialTransferSignFlow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * DỮ LIỆU GỐC - TRÌNH KÝ ĐỀ NGHỊ LIÊN PHÒNG BAN (gồm Đề nghị VPP).
 *
 * Mỗi dòng là MỘT bước ký của một loại đề nghị (VPP / liên phòng ban khác) cho một công ty
 * (công ty của phòng đề nghị): thứ tự, vai trò và phạm vi phòng ban - xem
 * App\Support\MaterialTransferSignFlow. Không có bước nào thì đề nghị gửi thẳng, không ký.
 */
class TransferSignFlowController extends Controller
{
    use RequiresChangeReason;

    private const TABLE = MaterialTransferSignFlow::TABLE;

    private const LABEL = 'bước ký đề nghị liên phòng ban';

    private const FIELDS = [
        'kind' => 'Loại đề nghị',
        'company_id' => 'Công ty',
        'step_no' => 'Thứ tự bước',
        'role_id' => 'Vai trò ký',
        'scope' => 'Phạm vi',
        'department_id' => 'Phòng cố định',
    ];

    /** Bộ quyền riêng của màn này: materData_transferSign_view / create / update / deActive. */
    private function authorize(string $action): void
    {
        abort_unless(user_can('materData_transferSign_'.$action), 403, 'Bạn không có quyền này ở màn Trình Ký Đề Nghị Liên Phòng Ban.');
    }

    public function index()
    {
        $this->authorize('view');

        $datas = DB::table(self::TABLE)
            ->leftJoin('companies', 'companies.id', '=', self::TABLE.'.company_id')
            ->leftJoin('roles', 'roles.id', '=', self::TABLE.'.role_id')
            ->leftJoin('deparments', 'deparments.id', '=', self::TABLE.'.department_id')
            ->select(
                self::TABLE.'.*',
                'companies.name as company_name',
                'companies.short_name as company_short',
                'roles.name as role_name',
                'deparments.name as department_name',
                'deparments.shortName as department_short'
            )
            ->orderBy(self::TABLE.'.kind')
            ->orderBy(self::TABLE.'.company_id')
            ->orderBy(self::TABLE.'.step_no')
            ->get();

        session()->put(['title' => 'DỮ LIỆU GỐC - TRÌNH KÝ ĐỀ NGHỊ LIÊN PHÒNG BAN']);

        return view('pages.materData.TransferSignFlow.list', [
            'datas' => $datas,
            'companies' => \App\Support\CompanyContext::options(),
            'roles' => DB::table('roles')->orderBy('name')->get(['id', 'name']),
            'departments' => DB::table('deparments')
                ->leftJoin('companies', 'companies.id', '=', 'deparments.company_id')
                ->where('deparments.isActive', 1)
                ->orderBy('deparments.company_id')
                ->orderBy('deparments.name')
                ->get(['deparments.id', 'deparments.name', 'deparments.shortName', 'companies.short_name as company_short']),
            'historyCounts' => DataMasterHistory::counts(self::TABLE),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create');

        $validator = $this->validator($request);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        $id = DB::table(self::TABLE)->insertGetId($this->payload($request) + [
            'status_id' => 1,
            'created_by' => $this->actor(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DataMasterHistory::record(self::TABLE, $id, 'Thêm mới', 'Khai báo mới '.self::LABEL.'.', self::FIELDS, $this->maps());
        AuditTrialController::log('Thêm mới', self::TABLE, $id, 'NA', 'Thêm '.self::LABEL);

        return redirect()->back()->with('success', 'Đã thêm '.self::LABEL.'!');
    }

    public function update(Request $request)
    {
        $this->authorize('update');

        $current = DB::table(self::TABLE)->where('id', $request->id)->first();

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::LABEL.' cần cập nhật!');
        }

        $validator = $this->validator($request, (int) $current->id, true);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        $payload = $this->payload($request);
        $note = DataMasterHistory::note(self::FIELDS, $current, $payload, $this->maps());

        if ($note === '') {
            return redirect()->back()->with('error', 'Chưa có thông tin nào thay đổi nên không lưu.')->withInput();
        }

        DB::table(self::TABLE)->where('id', $current->id)->update($payload + [
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        DataMasterHistory::record(self::TABLE, $current->id, 'Cập nhật', $note, self::FIELDS, $this->maps(), $this->changeReason($request));
        AuditTrialController::log('Cập nhật', self::TABLE, $current->id, 'NA', $note);

        return redirect()->back()->with('success', 'Cập nhật '.self::LABEL.' thành công!');
    }

    public function deActive(Request $request)
    {
        $this->authorize('deActive');

        $current = DB::table(self::TABLE)->where('id', $request->id)->first();

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::LABEL.' cần thay đổi trạng thái!');
        }

        if ($stop = $this->guardChangeReason($request)) {
            return $stop;
        }

        $newStatus = $current->status_id == 1 ? 0 : 1;

        // Mở khoá lại mà thứ tự bước đã bị dòng khác dùng thì quy trình sẽ mơ hồ
        if ($newStatus === 1 && $this->stepTaken($current->kind, (int) $current->company_id, (int) $current->step_no, (int) $current->id)) {
            return redirect()->back()->with('error', 'Bước '.$current->step_no.' của loại đề nghị / công ty này đã có dòng khác. Sửa thứ tự trước khi mở khoá.');
        }

        DB::table(self::TABLE)->where('id', $current->id)->update([
            'status_id' => $newStatus,
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        DataMasterHistory::record(
            self::TABLE,
            $current->id,
            $newStatus == 1 ? 'Mở khoá' : 'Khoá',
            DataMasterHistory::statusNote($current->status_id, $newStatus),
            self::FIELDS,
            $this->maps(),
            $this->changeReason($request)
        );
        AuditTrialController::log($newStatus == 1 ? 'Mở khoá' : 'Khoá', self::TABLE, $current->id, 'status_id: '.$current->status_id, 'status_id: '.$newStatus);

        return redirect()->back()->with('success', ($newStatus == 1 ? 'Đã mở khoá ' : 'Đã khoá ').self::LABEL.'!');
    }

    public function history(Request $request)
    {
        $this->authorize('view');

        return response()->json(['rows' => DataMasterHistory::rows(self::TABLE, (int) $request->id)]);
    }

    private function validator(Request $request, ?int $ignoreId = null, bool $withReason = false)
    {
        $validator = Validator::make($request->all(), [
            'kind' => ['required', Rule::in(array_keys(MaterialTransferSignFlow::KINDS))],
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'step_no' => ['required', 'integer', 'min:1', 'max:10'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'scope' => ['required', Rule::in(array_keys(MaterialTransferSignFlow::SCOPES))],
            'department_id' => ['nullable', 'required_if:scope,'.MaterialTransferSignFlow::SCOPE_DEPARTMENT, 'integer', 'exists:deparments,id'],
        ] + ($withReason ? $this->changeReasonRules() : []), [
            'kind.required' => 'Vui lòng chọn loại đề nghị.',
            'company_id.required' => 'Vui lòng chọn công ty.',
            'step_no.required' => 'Vui lòng nhập thứ tự bước.',
            'step_no.min' => 'Thứ tự bước từ 1 trở lên.',
            'step_no.max' => 'Thứ tự bước tối đa 10.',
            'role_id.required' => 'Vui lòng chọn vai trò ký.',
            'scope.required' => 'Vui lòng chọn phạm vi.',
            'department_id.required_if' => 'Phạm vi "Phòng cố định" phải chọn phòng ban.',
        ] + ($withReason ? $this->changeReasonMessages() : []));

        $validator->after(function ($validator) use ($request, $ignoreId) {
            if ($this->stepTaken((string) $request->kind, (int) $request->company_id, (int) $request->step_no, $ignoreId)) {
                $validator->errors()->add('step_no', 'Loại đề nghị / công ty này đã có bước '.$request->step_no.'.');
            }
        });

        return $validator;
    }

    /** Thứ tự bước đã có dòng đang hiệu lực khác dùng chưa. */
    private function stepTaken(string $kind, int $companyId, int $stepNo, ?int $ignoreId = null): bool
    {
        return DB::table(self::TABLE)
            ->where('kind', $kind)
            ->where('company_id', $companyId)
            ->where('step_no', $stepNo)
            ->where('status_id', 1)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();
    }

    private function payload(Request $request): array
    {
        $isFixed = $request->scope === MaterialTransferSignFlow::SCOPE_DEPARTMENT;

        return [
            'kind' => (string) $request->kind,
            'company_id' => (int) $request->company_id,
            'step_no' => (int) $request->step_no,
            'role_id' => (int) $request->role_id,
            'scope' => (string) $request->scope,
            'department_id' => $isFixed ? (int) $request->department_id : null,
        ];
    }

    /** Bảng tra nhãn để lịch sử hiện chữ thay vì mã. */
    private function maps(): array
    {
        return [
            'kind' => MaterialTransferSignFlow::KINDS,
            'company_id' => DB::table('companies')->pluck('name', 'id')->all(),
            'role_id' => DB::table('roles')->pluck('name', 'id')->all(),
            'scope' => MaterialTransferSignFlow::SCOPES,
            'department_id' => DB::table('deparments')->pluck('name', 'id')->all(),
        ];
    }

    private function actor(): string
    {
        return \App\Support\Signer::actor();
    }
}
