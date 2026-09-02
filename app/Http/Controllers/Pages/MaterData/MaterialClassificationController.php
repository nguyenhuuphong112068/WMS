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
 * DỮ LIỆU GỐC - PHÂN LOẠI VẬT TƯ
 *
 * Mỗi phòng ban tự khai bộ nhóm phân loại vật tư của phòng mình (thay cho nhóm A / B / C
 * cứng dùng chung trước đây). Màn hình luôn làm việc trên phòng ban đang chọn ở topNAV -
 * department_id lấy từ session, không cho chọn tay để tránh sửa nhầm sang phòng khác.
 *
 * Không có bước duyệt. Khoá (status_id = 0) thay cho xoá cứng.
 */
class MaterialClassificationController extends Controller
{
    private const TABLE = 'material_classifications';

    private const LABEL = 'phân loại vật tư';

    /** Các cột người dùng nhập - dùng chung cho ảnh chụp và mô tả thay đổi của lịch sử. */
    private const FIELDS = [
        'name' => 'Tên phân loại',
    ];

    public function index()
    {
        $departmentId = $this->departmentId();

        $datas = DB::table(self::TABLE)
            ->where('department_id', $departmentId)
            ->orderBy('name', 'asc')
            ->get();

        session()->put(['title' => 'DỮ LIỆU GỐC - PHÂN LOẠI VẬT TƯ']);

        return view('pages.materData.MaterialClassification.list', [
            'datas' => $datas,
            'departmentName' => session('user')['selected_department'] ?? '',
            // Số lần thay đổi của từng dòng, hiện thành badge ở góc nút Sửa
            'historyCounts' => DataMasterHistory::counts(self::TABLE),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules(), $this->messages());

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        $id = DB::table(self::TABLE)->insertGetId([
            'name' => trim((string) $request->name),
            'department_id' => $this->departmentId(),
            'status_id' => 1,
            'created_by' => $this->actor(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DataMasterHistory::record(self::TABLE, $id, 'Thêm mới', 'Khai báo mới '.self::LABEL.': '.$request->name.'.', self::FIELDS);

        AuditTrialController::log('Thêm mới', self::TABLE, $id, 'NA', 'Thêm '.self::LABEL.': '.$request->name);

        return redirect()->back()->with('success', 'Đã thêm '.self::LABEL.' thành công!');
    }

    public function update(Request $request)
    {
        $current = DB::table(self::TABLE)
            ->where('id', $request->id)
            ->where('department_id', $this->departmentId())
            ->first();

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::LABEL.' cần cập nhật!');
        }

        $validator = Validator::make($request->all(), $this->rules($current->id), $this->messages());

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        $payload = ['name' => trim((string) $request->name)];
        $note = DataMasterHistory::note(self::FIELDS, $current, $payload);

        DB::table(self::TABLE)->where('id', $current->id)->update($payload + [
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        DataMasterHistory::record(self::TABLE, $current->id, 'Cập nhật', $note ?: 'Lưu lại nhưng nội dung không đổi.', self::FIELDS);

        AuditTrialController::log('Cập nhật', self::TABLE, $current->id, $current->name, $request->name);

        return redirect()->back()->with('success', 'Cập nhật '.self::LABEL.' thành công!');
    }

    public function deActive(Request $request)
    {
        $current = DB::table(self::TABLE)
            ->where('id', $request->id)
            ->where('department_id', $this->departmentId())
            ->first();

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::LABEL.' cần thay đổi trạng thái!');
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
            self::FIELDS
        );

        AuditTrialController::log(
            $newStatus == 1 ? 'Mở khoá' : 'Khoá',
            self::TABLE,
            $current->id,
            'status_id: '.$current->status_id,
            'status_id: '.$newStatus
        );

        return redirect()->back()->with(
            'success',
            ($newStatus == 1 ? 'Đã mở khoá ' : 'Đã khoá ').self::LABEL.' '.$current->name.'!'
        );
    }

    /**
     * Trả về lịch sử thay đổi của một dòng cho modal xem lịch sử.
     * Chỉ đọc được phân loại của phòng ban đang chọn, giống các thao tác còn lại.
     */
    public function history(Request $request)
    {
        $id = (int) DB::table(self::TABLE)
            ->where('id', $request->id)
            ->where('department_id', $this->departmentId())
            ->value('id');

        return response()->json([
            'rows' => $id ? DataMasterHistory::rows(self::TABLE, $id) : [],
        ]);
    }

    private function departmentId(): int
    {
        return (int) (session('user')['selected_department_id'] ?? 0);
    }

    private function actor(): string
    {
        return session('user')['fullName'] ?? 'NA';
    }

    private function rules($ignoreId = null): array
    {
        return [
            'name' => [
                'required',
                'max:100',
                Rule::unique(self::TABLE, 'name')
                    ->where(fn ($query) => $query->where('department_id', $this->departmentId()))
                    ->ignore($ignoreId),
            ],
        ];
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên phân loại.',
            'name.max' => 'Tên phân loại tối đa 100 ký tự.',
            'name.unique' => 'Tên phân loại này đã tồn tại trong phòng ban.',
        ];
    }
}
