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
 * DỮ LIỆU GỐC - CÔNG TY
 *
 * Phần mềm triển khai cho nhiều công ty, mỗi công ty có nhiều phòng ban riêng. Mỗi phòng
 * ban gắn vào đúng một công ty (deparments.company_id). Việc đối chiếu "Ngưỡng khối lượng
 * tồn trữ lớn nhất tại một thời điểm" (Phụ lục IV NĐ 24/2026/NĐ-CP) chỉ cộng tồn trong
 * phạm vi các phòng ban thuộc cùng một công ty - xem App\Support\CompanyContext.
 *
 * Cấu trúc mã / hành vi giống các màn dữ liệu gốc khác: mã tự sinh CT00001, khoá bằng
 * status_id (không xoá cứng), có lịch sử thay đổi.
 */
class CompanyController extends Controller
{
    private const TABLE = 'companies';
    private const LABEL = 'công ty';

    /** Tiền tố mã sinh tự động: CT00001, CT00002... */
    private const CODE_PREFIX = 'CT';
    private const CODE_LENGTH = 5;

    /** Các cột người dùng nhập - dùng chung cho ảnh chụp và mô tả thay đổi của lịch sử. */
    private const FIELDS = [
        'name' => 'Tên công ty',
        'short_name' => 'Tên viết tắt',
    ];

    public function index()
    {
        $datas = DB::table(self::TABLE)->orderBy('name', 'asc')->get();

        session()->put(['title' => 'DỮ LIỆU GỐC - CÔNG TY']);

        return view('pages.materData.Company.list', [
            'datas' => $datas,
            // Số phòng ban đang gắn từng công ty, hiện ngay trên bảng
            'departmentCounts' => DB::table('deparments')
                ->select('company_id', DB::raw('COUNT(*) as total'))
                ->groupBy('company_id')
                ->pluck('total', 'company_id'),
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

        $id = DB::table(self::TABLE)->insertGetId($this->payload($request) + [
            'code' => $this->nextCode(),
            'status_id' => 1,
            'created_by' => $this->actor(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DataMasterHistory::record(self::TABLE, $id, 'Thêm mới', 'Khai báo mới ' . self::LABEL . ': ' . $request->name . '.', self::FIELDS);

        AuditTrialController::log('Thêm mới', self::TABLE, $id, 'NA', 'Thêm ' . self::LABEL . ': ' . $request->name);

        return redirect()->back()->with('success', 'Đã thêm ' . self::LABEL . ' thành công!');
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
        $note = DataMasterHistory::note(self::FIELDS, $current, $payload);

        DB::table(self::TABLE)->where('id', $current->id)->update($payload + [
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        DataMasterHistory::record(self::TABLE, $current->id, 'Cập nhật', $note ?: 'Lưu lại nhưng nội dung không đổi.', self::FIELDS);

        AuditTrialController::log('Cập nhật', self::TABLE, $current->id, $current->name, $request->name);

        return redirect()->back()->with('success', 'Cập nhật ' . self::LABEL . ' thành công!');
    }

    public function deActive(Request $request)
    {
        $current = DB::table(self::TABLE)->where('id', $request->id)->first();

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy ' . self::LABEL . ' cần thay đổi trạng thái!');
        }

        // Không cho khoá công ty còn phòng ban đang gắn - tránh mất phạm vi đối chiếu ngưỡng
        if ($current->status_id == 1) {
            $used = DB::table('deparments')->where('company_id', $current->id)->count();

            if ($used > 0) {
                return redirect()->back()->with('error', 'Không thể khoá: còn ' . $used . ' phòng ban thuộc ' . self::LABEL . ' này. Chuyển các phòng ban sang công ty khác trước.');
            }
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

    /**
     * Mã kế tiếp theo dạng CT00001: lấy số lớn nhất đang có rồi cộng 1.
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

    private function rules($ignoreId = null): array
    {
        return [
            'name' => ['required', 'max:255', Rule::unique(self::TABLE, 'name')->ignore($ignoreId)],
            'short_name' => ['required', 'max:50', Rule::unique(self::TABLE, 'short_name')->ignore($ignoreId)],
        ];
    }

    private function payload(Request $request): array
    {
        return [
            'name' => trim((string) $request->name),
            'short_name' => trim((string) $request->short_name),
        ];
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên công ty.',
            'name.max' => 'Tên công ty tối đa 255 ký tự.',
            'name.unique' => 'Tên công ty này đã tồn tại.',
            'short_name.required' => 'Vui lòng nhập tên viết tắt.',
            'short_name.max' => 'Tên viết tắt tối đa 50 ký tự.',
            'short_name.unique' => 'Tên viết tắt này đã tồn tại.',
        ];
    }
}
