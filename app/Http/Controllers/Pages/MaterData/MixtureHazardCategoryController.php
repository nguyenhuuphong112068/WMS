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
 * DỮ LIỆU GỐC - NHÓM NGUY HẠI BẢNG B (Phụ lục IV Nghị định 24/2026/NĐ-CP)
 *
 * Bảng B phân loại hỗn hợp hoá chất theo nhóm nguy hại GHS, mỗi nhóm kèm
 * "Ngưỡng khối lượng hoá chất tồn trữ lớn nhất tại một thời điểm (kg)".
 * Màn "Tên Hoá Chất" tick các nhóm này cho từng hỗn hợp; hệ thống đối chiếu
 * tổng tồn trữ toàn công ty với ngưỡng thấp nhất trong các nhóm đã tick.
 *
 * Dữ liệu mới tạo ở trạng thái "Chờ duyệt", chỉ dùng để cảnh báo sau khi phê duyệt.
 * Sửa lại một bản ghi đã duyệt sẽ đưa về "Chờ duyệt" để duyệt lại.
 */
class MixtureHazardCategoryController extends Controller
{
    private const TABLE = 'mixture_hazard_categories';
    private const LABEL = 'nhóm nguy hại Bảng B';

    /** Tiền tố mã sinh tự động: B00001, B00002... */
    private const CODE_PREFIX = 'B';
    private const CODE_LENGTH = 5;

    /** 4 phần của Bảng B. */
    public const GROUPS = [
        'I' => 'Nguy hại sức khỏe',
        'II' => 'Nguy hại vật chất',
        'III' => 'Nguy hại cho môi trường',
        'IV' => 'Nguy hại khác',
    ];

    /** Các cột người dùng nhập - dùng chung cho ảnh chụp và mô tả thay đổi của lịch sử. */
    private const FIELDS = [
        'hazard_group' => 'Phần Bảng B',
        'name' => 'Nhóm phân loại',
        'threshold_kg' => 'Ngưỡng tồn trữ (kg)',
        'threshold_basis' => 'Cách tính ngưỡng',
    ];

    public function index()
    {
        $datas = DB::table(self::TABLE)
            ->orderByRaw("FIELD(hazard_group, 'I', 'II', 'III', 'IV')")
            ->orderBy('ordinal', 'asc')
            ->get();

        session()->put(['title' => 'DỮ LIỆU GỐC - NHÓM NGUY HẠI BẢNG B']);

        return view('pages.materData.MixtureHazardCategory.list', [
            'datas' => $datas,
            'groups' => self::GROUPS,
            'historyCounts' => DataMasterHistory::counts(self::TABLE),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules(), $this->messages());

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        $id = DB::transaction(function () use ($request) {
            return DB::table(self::TABLE)->insertGetId($this->payload($request) + [
                'code' => $this->nextCode(),
                'ordinal' => $this->nextOrdinal($request->hazard_group),
                'is_statutory' => 0,
                'app_status' => 'pending',
                'status_id' => 1,
                'created_by' => $this->actor(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        DataMasterHistory::record(self::TABLE, $id, 'Thêm mới', 'Khai báo mới ' . self::LABEL . ': ' . $this->shortName($request->name) . '.', self::FIELDS, $this->maps());

        AuditTrialController::log('Thêm mới', self::TABLE, $id, 'NA', 'Thêm ' . self::LABEL . ': ' . $this->shortName($request->name));

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

        // Đổi phần Bảng B thì xếp lại xuống cuối phần mới
        if ($payload['hazard_group'] !== $current->hazard_group) {
            $payload['ordinal'] = $this->nextOrdinal($payload['hazard_group']);
        }

        $note = DataMasterHistory::note(self::FIELDS, $current, $payload, $this->maps());

        if ($current->is_statutory) {
            $note = trim('Sửa dữ liệu luật định. ' . $note);
        }

        DB::table(self::TABLE)->where('id', $current->id)->update($payload + [
            'app_status' => 'pending',
            'approved_by' => null,
            'approved_at' => null,
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        DataMasterHistory::record(self::TABLE, $current->id, 'Cập nhật', $note ?: 'Lưu lại nhưng nội dung không đổi.', self::FIELDS, $this->maps());

        AuditTrialController::log('Cập nhật', self::TABLE, $current->id, $this->shortName($current->name), $this->shortName($request->name));

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
            ($newStatus == 1 ? 'Đã mở khoá ' : 'Đã khoá ') . self::LABEL . '!'
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
            ($appStatus === 'approved' ? 'Đã duyệt ' : 'Đã từ chối ') . self::LABEL . '!'
        );
    }

    /**
     * Mã kế tiếp theo dạng B00001: lấy số lớn nhất đang có rồi cộng 1.
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

    /** STT kế tiếp trong một phần Bảng B. */
    private function nextOrdinal(string $group): int
    {
        return (int) DB::table(self::TABLE)->where('hazard_group', $group)->max('ordinal') + 1;
    }

    private function actor(): string
    {
        return \App\Support\Signer::actor();
    }

    /** Rút gọn mô tả nhóm (nhiều dòng) để đưa vào note / audit. */
    private function shortName(?string $name): string
    {
        $name = trim(preg_replace('/\s+/', ' ', (string) $name));

        return mb_strlen($name) > 60 ? mb_substr($name, 0, 60) . '…' : $name;
    }

    /** Bảng tra giá trị đọc được cho lịch sử thay đổi. */
    private function maps(): array
    {
        $groupLabels = [];
        foreach (self::GROUPS as $code => $label) {
            $groupLabels[$code] = $code . ' - ' . $label;
        }

        return [
            'hazard_group' => $groupLabels,
            'threshold_basis' => ['net' => 'Theo khối lượng tịnh (net)'],
        ];
    }

    private function rules($ignoreId = null): array
    {
        return [
            'hazard_group' => ['required', Rule::in(array_keys(self::GROUPS))],
            'name' => ['required', 'max:1000', Rule::unique(self::TABLE, 'name')->ignore($ignoreId)],
            'threshold_kg' => ['required', 'numeric', 'gt:0', 'max:999999999999'],
            'threshold_basis' => ['nullable', Rule::in(['net'])],
        ];
    }

    private function payload(Request $request): array
    {
        $basis = trim((string) $request->threshold_basis);

        return [
            'hazard_group' => $request->hazard_group,
            'name' => trim((string) $request->name),
            'threshold_kg' => $request->threshold_kg,
            'threshold_basis' => $basis === '' ? null : $basis,
        ];
    }

    private function messages(): array
    {
        return [
            'hazard_group.required' => 'Vui lòng chọn phần Bảng B (I - IV).',
            'hazard_group.in' => 'Phần Bảng B không hợp lệ.',
            'name.required' => 'Vui lòng nhập mô tả nhóm phân loại.',
            'name.max' => 'Mô tả nhóm phân loại tối đa 1000 ký tự.',
            'name.unique' => 'Nhóm phân loại này đã tồn tại.',
            'threshold_kg.required' => 'Vui lòng nhập ngưỡng tồn trữ.',
            'threshold_kg.numeric' => 'Ngưỡng tồn trữ phải là số.',
            'threshold_kg.gt' => 'Ngưỡng tồn trữ phải lớn hơn 0.',
            'threshold_kg.max' => 'Ngưỡng tồn trữ quá lớn.',
            'threshold_basis.in' => 'Cách tính ngưỡng không hợp lệ.',
        ];
    }
}
