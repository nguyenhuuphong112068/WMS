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
 * DỮ LIỆU GỐC - TÊN HOẠT CHẤT (Phụ lục IV Nghị định 24/2026/NĐ-CP)
 *
 * Danh mục hoạt chất phải xây dựng Kế hoạch phòng ngừa, ứng phó sự cố hoá chất, kèm
 * "Ngưỡng khối lượng hoá chất tồn trữ lớn nhất tại một thời điểm (kg)" (cột threshold_kg).
 * Màn hình "Tên Hoá Chất" gắn nhiều hoạt chất qua bảng pivot chem_name_active_ingredient
 * để hệ thống đối chiếu tồn trữ toàn công ty với ngưỡng và cảnh báo.
 *
 * Dữ liệu mới tạo ở trạng thái "Chờ duyệt", chỉ dùng để cảnh báo sau khi phê duyệt.
 * Sửa lại một bản ghi đã duyệt sẽ đưa về "Chờ duyệt" để duyệt lại.
 */
class ActiveIngredientController extends Controller
{
    private const TABLE = 'active_ingredients';
    private const LABEL = 'tên hoạt chất';

    /** Tiền tố mã sinh tự động: A00001, A00002... */
    private const CODE_PREFIX = 'A';
    private const CODE_LENGTH = 5;

    /** Các cột người dùng nhập - dùng chung cho ảnh chụp và mô tả thay đổi của lịch sử. */
    private const FIELDS = [
        'name' => 'Tên hoạt chất',
        'name_en' => 'Tên tiếng Anh',
        'cas_no' => 'Số CAS',
        'chemical_formula' => 'Công thức hoá học',
        'is_table_a' => 'Thuộc Bảng A PL IV NĐ 24/2026',
        'threshold_kg' => 'Ngưỡng tồn trữ (kg)',
    ];

    public function index()
    {
        $datas = DB::table(self::TABLE)->orderBy('name', 'asc')->get();

        session()->put(['title' => 'DỮ LIỆU GỐC - TÊN HOẠT CHẤT']);

        return view('pages.materData.ActiveIngredient.list', [
            'datas' => $datas,
            // Số lần thay đổi của từng dòng, hiện thành badge ở góc nút Sửa
            'historyCounts' => DataMasterHistory::counts(self::TABLE),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules(null, $request), $this->messages());

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        // Sinh mã trong transaction để hai người thêm cùng lúc không trùng mã
        $id = DB::transaction(function () use ($request) {
            return DB::table(self::TABLE)->insertGetId($this->payload($request) + [
                'code' => $this->nextCode(),
                'is_statutory' => 0,
                'app_status' => 'pending',
                'status_id' => 1,
                'created_by' => $this->actor(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        DataMasterHistory::record(self::TABLE, $id, 'Thêm mới', 'Khai báo mới ' . self::LABEL . ': ' . $request->name . '.', self::FIELDS, $this->maps());

        AuditTrialController::log('Thêm mới', self::TABLE, $id, 'NA', 'Thêm ' . self::LABEL . ': ' . $request->name);

        return redirect()->back()->with('success', 'Đã thêm ' . self::LABEL . ' thành công! Bản ghi đang chờ duyệt.');
    }

    public function update(Request $request)
    {
        $current = DB::table(self::TABLE)->where('id', $request->id)->first();

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy ' . self::LABEL . ' cần cập nhật!');
        }

        $validator = Validator::make($request->all(), $this->rules($current->id, $request), $this->messages());

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        $payload = $this->payload($request);
        $note = DataMasterHistory::note(self::FIELDS, $current, $payload, $this->maps());

        // Nhắc rõ khi sửa dữ liệu lấy từ nghị định
        if ($current->is_statutory) {
            $note = trim('Sửa dữ liệu luật định. ' . $note);
        }

        DB::table(self::TABLE)->where('id', $current->id)->update($payload + [
            // Sửa nội dung thì phải duyệt lại từ đầu
            'app_status' => 'pending',
            'approved_by' => null,
            'approved_at' => null,
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        DataMasterHistory::record(self::TABLE, $current->id, 'Cập nhật', $note ?: 'Lưu lại nhưng nội dung không đổi.', self::FIELDS, $this->maps());

        AuditTrialController::log('Cập nhật', self::TABLE, $current->id, $current->name, $request->name);

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

    /**
     * Mã kế tiếp theo dạng A00001: lấy số lớn nhất đang có rồi cộng 1.
     * Màn hình chỉ khoá bản ghi chứ không xoá nên mã không bị dùng lại.
     */
    private function nextCode(): string
    {
        $numbers = DB::table(self::TABLE)
            ->where('code', 'like', self::CODE_PREFIX . '%')
            ->pluck('code')
            ->map(fn ($code) => (int) substr($code, strlen(self::CODE_PREFIX)));

        $next = ($numbers->max() ?? 0) + 1;

        return self::CODE_PREFIX . str_pad((string) $next, self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }

    private function actor(): string
    {
        return \App\Support\Signer::actor();
    }

    /** Bảng tra giá trị đọc được cho lịch sử thay đổi. */
    private function maps(): array
    {
        return [
            'is_table_a' => [0 => 'Không', 1 => 'Có'],
        ];
    }

    private function rules($ignoreId = null, ?Request $request = null): array
    {
        $request = $request ?? request();
        $isTableA = $request->boolean('is_table_a');

        return [
            'name' => ['required', 'max:255', Rule::unique(self::TABLE, 'name')->ignore($ignoreId)],
            'name_en' => ['nullable', 'max:255'],
            'cas_no' => ['nullable', 'max:100'],
            'chemical_formula' => ['nullable', 'max:255'],
            'is_table_a' => ['nullable', 'boolean'],
            'threshold_kg' => $isTableA
                ? ['required', 'numeric', 'gt:0', 'max:999999999']
                : ['nullable', 'numeric', 'gt:0', 'max:999999999'],
        ];
    }

    private function payload(Request $request): array
    {
        $isTableA = $request->boolean('is_table_a');

        return [
            'name' => trim((string) $request->name),
            'name_en' => $this->nullable($request->name_en),
            'cas_no' => $this->nullable($request->cas_no),
            'chemical_formula' => $this->nullable($request->chemical_formula),
            'is_table_a' => $isTableA ? 1 : 0,
            // Ngưỡng chỉ có nghĩa với chất thuộc Bảng A; chất thường thì bỏ ngưỡng
            'threshold_kg' => $isTableA && trim((string) $request->threshold_kg) !== '' ? $request->threshold_kg : null,
        ];
    }

    private function nullable($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên hoạt chất.',
            'name.max' => 'Tên hoạt chất tối đa 255 ký tự.',
            'name.unique' => 'Tên hoạt chất này đã tồn tại.',
            'name_en.max' => 'Tên chất tối đa 255 ký tự.',
            'cas_no.max' => 'Số CAS tối đa 100 ký tự.',
            'chemical_formula.max' => 'Công thức hoá học tối đa 255 ký tự.',
            'is_table_a.boolean' => 'Giá trị "Thuộc Bảng A" không hợp lệ.',
            'threshold_kg.required' => 'Hoạt chất thuộc Bảng A bắt buộc phải có ngưỡng tồn trữ, không được để trống.',
            'threshold_kg.numeric' => 'Ngưỡng tồn trữ phải là số.',
            'threshold_kg.gt' => 'Ngưỡng tồn trữ phải lớn hơn 0.',
            'threshold_kg.max' => 'Ngưỡng tồn trữ quá lớn.',
        ];
    }
}
