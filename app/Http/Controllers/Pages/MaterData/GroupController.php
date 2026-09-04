<?php

namespace App\Http\Controllers\Pages\MaterData;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\DataMasterHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * DỮ LIỆU GỐC - TỔ
 */
class GroupController extends Controller
{
    private const TABLE = 'groups';
    private const LABEL = 'tổ';

    /** Các cột người dùng nhập - dùng chung cho ảnh chụp và mô tả thay đổi của lịch sử. */
    private const FIELDS = [
        'name' => 'Tên tổ',
        'department_id' => 'Phòng ban',
    ];

    public function index()
    {
        // Chỉ phòng ban thuộc công ty đang làm việc (suy từ phòng ban đang chọn)
        $companyId = \App\Support\CompanyContext::currentId();

        $datas = DB::table(self::TABLE)
            ->leftJoin('deparments', self::TABLE . '.department_id', '=', 'deparments.id')
            ->select(
                self::TABLE . '.*',
                'deparments.name as department_name',
                'deparments.shortName as department_short'
            )
            ->when($companyId, fn ($q) => $q->where('deparments.company_id', $companyId))
            ->orderBy(self::TABLE . '.name', 'asc')
            ->get();

        $departments = DB::table('deparments')
            ->where('isActive', 1)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->orderBy('name', 'asc')
            ->get();

        session()->put(['title' => 'DỮ LIỆU GỐC - TỔ']);

        return view('pages.materData.Group.list', [
            'datas' => $datas,
            // Số lần thay đổi của từng dòng, hiện thành badge ở góc nút Sửa
            'historyCounts' => DataMasterHistory::counts(self::TABLE),
            'departments' => $departments,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules(), $this->messages());

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        $id = DB::table(self::TABLE)->insertGetId($this->payload($request) + [
            'status_id' => 1,
            'created_by' => $this->actor(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DataMasterHistory::record(self::TABLE, $id, 'Thêm mới', 'Khai báo mới ' . self::LABEL . ': ' . $request->name . '.', self::FIELDS, $this->maps());

        AuditTrialController::log('Thêm mới', self::TABLE, $id, 'NA', 'Thêm ' . self::LABEL . ': ' . $request->name);

        return redirect()->back()->with('success', 'Đã thêm ' . self::LABEL . ' thành công!');
    }

    public function update(Request $request)
    {
        $current = DB::table(self::TABLE)->where('id', $request->id)->first();

        if (!$current) {
            return redirect()->back()->with('error', 'Không tìm thấy ' . self::LABEL . ' cần cập nhật!');
        }

        $validator = Validator::make($request->all(), $this->rules($current->id), $this->messages());

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        $payload = $this->payload($request);
        $note = DataMasterHistory::note(self::FIELDS, $current, $payload, $this->maps());

        DB::table(self::TABLE)->where('id', $current->id)->update($payload + [
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        DataMasterHistory::record(self::TABLE, $current->id, 'Cập nhật', $note ?: 'Lưu lại nhưng nội dung không đổi.', self::FIELDS, $this->maps());

        AuditTrialController::log('Cập nhật', self::TABLE, $current->id, $current->name, $request->name);

        return redirect()->back()->with('success', 'Cập nhật ' . self::LABEL . ' thành công!');
    }

    public function deActive(Request $request)
    {
        $current = DB::table(self::TABLE)->where('id', $request->id)->first();

        if (!$current) {
            return redirect()->back()->with('error', 'Không tìm thấy ' . self::LABEL . ' cần thay đổi trạng thái!');
        }

        $newStatus = $current->status_id == 1 ? 0 : 1;

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
            $this->maps()
        );

        AuditTrialController::log(
            $newStatus == 1 ? 'Mở khoá' : 'Khoá',
            self::TABLE,
            $current->id,
            'status_id: ' . $current->status_id,
            'status_id: ' . $newStatus
        );

        return redirect()->back()->with(
            'success',
            ($newStatus == 1 ? 'Đã mở khoá ' : 'Đã khoá ') . self::LABEL . ' ' . $current->name . '!'
        );
    }

    /** Trả về lịch sử thay đổi của một dòng cho modal xem lịch sử. */
    public function history(Request $request)
    {
        return response()->json([
            'rows' => DataMasterHistory::rows(self::TABLE, (int) $request->id),
        ]);
    }

    private function actor(): string
    {
        return \App\Support\Signer::actor();
    }

    /** Bảng tra nhãn để lịch sử hiện tên phòng ban thay vì department_id. */
    private function maps(): array
    {
        return ['department_id' => DB::table('deparments')->pluck('name', 'id')->all()];
    }

    private function rules($ignoreId = null): array
    {
        return [
            'name' => [
                'required',
                'max:255',
                Rule::unique(self::TABLE, 'name')
                    ->where(fn($query) => $query->where('department_id', request('department_id')))
                    ->ignore($ignoreId),
            ],
            'department_id' => ['required', 'integer'],
        ];
    }

    private function payload(Request $request): array
    {
        return [
            'name' => trim((string) $request->name),
            'department_id' => (int) $request->department_id,
        ];
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên tổ.',
            'name.max' => 'Tên tổ tối đa 255 ký tự.',
            'name.unique' => 'Tên tổ này đã tồn tại trong phòng ban.',
            'department_id.required' => 'Vui lòng chọn phòng ban.',
        ];
    }
}
