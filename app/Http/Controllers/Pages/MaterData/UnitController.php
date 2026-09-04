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
 * DỮ LIỆU GỐC - ĐƠN VỊ TÍNH
 *
 * Dữ liệu mới tạo ở trạng thái "Chờ duyệt", chỉ dùng được sau khi phê duyệt.
 * Sửa lại một bản ghi đã duyệt sẽ đưa về "Chờ duyệt" để duyệt lại.
 */
class UnitController extends Controller
{
    private const TABLE = 'units';
    private const LABEL = 'đơn vị tính';

    /** Các cột người dùng nhập - dùng chung cho ảnh chụp và mô tả thay đổi của lịch sử. */
    private const FIELDS = [
        'short_name' => 'Ký hiệu',
        'name' => 'Tên đơn vị tính',
        'unit_group' => 'Nhóm đơn vị',
        'factor_to_base' => 'Hệ số quy đổi',
    ];

    public function index()
    {
        $datas = DB::table(self::TABLE)
            ->orderBy('unit_group', 'asc')
            ->orderBy('factor_to_base', 'asc')
            ->get();

        session()->put(['title' => 'DỮ LIỆU GỐC - ĐƠN VỊ TÍNH']);

        return view('pages.materData.Unit.list', [
            'datas' => $datas,
            // Số lần thay đổi của từng dòng, hiện thành badge ở góc nút Sửa
            'historyCounts' => DataMasterHistory::counts(self::TABLE),
            'groups' => config('unit.groups'),
            'suggestions' => config('unit.suggestions'),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules(), $this->messages());

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        $id = DB::table(self::TABLE)->insertGetId($this->payload($request) + [
            'app_status' => 'pending',
            'status_id' => 1,
            'created_by' => $this->actor(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DataMasterHistory::record(self::TABLE, $id, 'Thêm mới', 'Khai báo mới ' . self::LABEL . ': ' . $request->short_name . ' - ' . $request->name . '.', self::FIELDS, $this->maps());

        AuditTrialController::log(
            'Thêm mới',
            self::TABLE,
            $id,
            'NA',
            'Thêm ' . self::LABEL . ': ' . $request->short_name . ' - ' . $request->name
        );

        return redirect()->back()->with('success', 'Đã thêm ' . self::LABEL . ' thành công! Bản ghi đang chờ duyệt.');
    }

    public function update(Request $request)
    {
        $current = DB::table(self::TABLE)->where('id', $request->id)->first();

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy ' . self::LABEL . ' cần cập nhật!');
        }

        $validator = Validator::make($request->all(), $this->rules($current->id), $this->messages());

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        $payload = $this->payload($request);
        $note = DataMasterHistory::note(self::FIELDS, $current, $payload, $this->maps());

        DB::table(self::TABLE)->where('id', $current->id)->update($payload + [
            // Sửa nội dung thì phải duyệt lại từ đầu
            'app_status' => 'pending',
            'approved_by' => null,
            'approved_at' => null,
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        DataMasterHistory::record(self::TABLE, $current->id, 'Cập nhật', $note ?: 'Lưu lại nhưng nội dung không đổi.', self::FIELDS, $this->maps());

        AuditTrialController::log(
            'Cập nhật',
            self::TABLE,
            $current->id,
            $current->short_name . ' - ' . $current->name,
            $request->short_name . ' - ' . $request->name
        );

        return redirect()->back()->with('success', 'Cập nhật ' . self::LABEL . ' thành công! Bản ghi chuyển về chờ duyệt.');
    }

    public function deActive(Request $request)
    {
        $current = DB::table(self::TABLE)->where('id', $request->id)->first();

        if (! $current) {
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

    public function approve(Request $request)
    {
        return $this->setApproval($request, 'approved');
    }

    public function reject(Request $request)
    {
        return $this->setApproval($request, 'rejected');
    }

    /** Trả về lịch sử thay đổi của một dòng cho modal xem lịch sử. */
    public function history(Request $request)
    {
        return response()->json([
            'rows' => DataMasterHistory::rows(self::TABLE, (int) $request->id),
        ]);
    }

    /** Ghi nhận kết quả duyệt: ai duyệt, duyệt lúc nào. */
    private function setApproval(Request $request, string $appStatus)
    {
        $current = DB::table(self::TABLE)->where('id', $request->id)->first();

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy ' . self::LABEL . ' cần duyệt!');
        }

        DB::table(self::TABLE)->where('id', $current->id)->update([
            'app_status' => $appStatus,
            'approved_by' => $this->actor(),
            'approved_at' => now(),
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        DataMasterHistory::record(
            self::TABLE,
            $current->id,
            $appStatus === 'approved' ? 'Phê duyệt' : 'Từ chối duyệt',
            DataMasterHistory::approvalNote($current->app_status, $appStatus),
            self::FIELDS,
            $this->maps()
        );

        AuditTrialController::log(
            $appStatus === 'approved' ? 'Phê duyệt' : 'Từ chối duyệt',
            self::TABLE,
            $current->id,
            'app_status: ' . $current->app_status,
            'app_status: ' . $appStatus
        );

        return redirect()->back()->with(
            'success',
            ($appStatus === 'approved' ? 'Đã duyệt ' : 'Đã từ chối ') . self::LABEL . ' ' . $current->name . '!'
        );
    }

    private function actor(): string
    {
        return \App\Support\Signer::actor();
    }

    /** Bảng tra nhãn để lịch sử hiện tên nhóm đơn vị thay vì mã nhóm. */
    private function maps(): array
    {
        return ['unit_group' => config('unit.groups')];
    }

    private function rules($ignoreId = null): array
    {
        return [
            'short_name' => ['required', 'max:100', Rule::unique(self::TABLE, 'short_name')->ignore($ignoreId)],
            'name' => ['required', 'max:255', Rule::unique(self::TABLE, 'name')->ignore($ignoreId)],
            'unit_group' => ['required', Rule::in(array_keys(config('unit.groups')))],
            'factor_to_base' => ['required', 'numeric', 'gt:0'],
        ];
    }

    private function payload(Request $request): array
    {
        return [
            'short_name' => trim((string) $request->short_name),
            'name' => trim((string) $request->name),
            'unit_group' => (string) $request->unit_group,
            'factor_to_base' => (float) $request->factor_to_base,
        ];
    }

    private function messages(): array
    {
        return [
            'short_name.required' => 'Vui lòng nhập ký hiệu đơn vị tính.',
            'short_name.max' => 'Ký hiệu tối đa 100 ký tự.',
            'short_name.unique' => 'Ký hiệu này đã tồn tại.',
            'name.required' => 'Vui lòng nhập tên đơn vị tính.',
            'name.max' => 'Tên đơn vị tính tối đa 255 ký tự.',
            'name.unique' => 'Tên đơn vị tính này đã tồn tại.',
            'unit_group.required' => 'Vui lòng chọn nhóm đơn vị.',
            'unit_group.in' => 'Nhóm đơn vị không hợp lệ.',
            'factor_to_base.required' => 'Vui lòng nhập hệ số quy đổi.',
            'factor_to_base.numeric' => 'Hệ số quy đổi phải là số.',
            'factor_to_base.gt' => 'Hệ số quy đổi phải lớn hơn 0.',
        ];
    }
}
