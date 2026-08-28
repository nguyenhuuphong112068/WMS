<?php

namespace App\Http\Controllers\Pages\Export;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\DepartmentChemical;
use App\Support\UnitConverter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * SỬ DỤNG - SỬ DỤNG HOÁ CHẤT
 *
 * Ghi nhận từng lần lấy hoá chất ra khỏi kho từ một phiếu nhập cụ thể:
 * sử dụng cho công việc (type = export) hoặc huỷ bỏ (type = cancel).
 *
 * Mã xuất nhập không sinh mới - lấy đúng mã của phiếu nhập được xuất ra.
 * Phiếu chỉ khoá (deActive) chứ không xoá cứng; phiếu đã khoá không trừ tồn.
 *
 * Hai quy tắc của nghiệp vụ xuất:
 * - Chỉ được CHỌN phiếu nhập còn hạn sử dụng và còn tồn > 0.
 * - Được XUẤT VƯỢT tồn tối đa 5% để bù sai số cân đong. Phần vượt làm tồn bị âm,
 *   xử lý bằng chức năng Cân Đối ở màn hình Tồn Kho Hoá Chất.
 */
class ChemicalExportController extends Controller
{
    private const TABLE = 'chemical_exports';

    private const HISTORY_TABLE = 'chemical_export_histories';

    private const REQUEST_TABLE = 'chemical_transfer_requests';

    private const LABEL = 'phiếu sử dụng hoá chất';

    /** Các trường được theo dõi thay đổi, dùng làm nhãn trong lịch sử điều chỉnh. */
    private const FIELDS = [
        'code' => 'Mã xuất nhập',
        'amount' => 'Số lượng',
        'type' => 'Loại phiếu',
        'exported_date' => 'Ngày sử dụng',
        'to_department_id' => 'Phòng ban nhận',
        'purpose' => 'Mục đích sử dụng',
        'test_report_no' => 'Số phiếu KN, OOS, BCSL',
        'checked_by' => 'Người kiểm tra',
    ];

    /** Sai số cho phép khi so số lượng xuất với tồn (cột decimal 15,4). */
    private const EPSILON = 0.00005;

    /** Được xuất vượt tồn còn lại tối đa ngần này (5%). */
    private const OVER_ISSUE_RATIO = 0.05;

    public const TYPES = [
        'export' => 'Sử dụng',
        'cancel' => 'Huỷ bỏ',
        'transfer' => 'Chuyển kho',
    ];

    /**
     * Loại phiếu CHUYỂN KHO - chuyển hoá chất sang kho phòng ban khác.
     *
     * Khác 'export' / 'cancel' ở hai điểm:
     * - Bắt buộc chọn phòng ban nhận (to_department_id).
     * - KHÔNG được xuất vượt tồn: hàng chuyển đi sẽ thành tồn của phòng nhận, cho
     *   vượt là tự sinh thêm hàng trong hệ thống. Phần 5% chỉ dành cho hao hụt
     *   cân đong khi thật sự sử dụng / huỷ bỏ.
     */
    private const TYPE_TRANSFER = 'transfer';

    /**
     * Loại phiếu HUỶ BỎ - bước 1 của nghiệp vụ huỷ hoá chất.
     *
     * Lập phiếu là "loại bỏ": hoá chất bị đánh dấu bỏ và trừ tồn ngay, nhưng CHƯA huỷ.
     * Phiếu rơi vào tab "Hoá chất chờ huỷ" để gom lại xin quyết định huỷ một lần
     * (bước 2, xem ChemicalDisposalController).
     */
    private const TYPE_CANCEL = 'cancel';

    public function index(Request $request)
    {
        $departmentId = $this->departmentId();

        $datas = DB::table(self::TABLE)
            ->leftJoin('chemical_imports', self::TABLE.'.import_id', '=', 'chemical_imports.id')
            ->leftJoin('chemical_categories', 'chemical_imports.category_id', '=', 'chemical_categories.id')
            ->leftJoin('chem_names', 'chemical_categories.chem_names_id', '=', 'chem_names.id')
            // Đơn vị tính khai ở danh mục hoá chất CỦA PHÒNG, không còn ở danh mục chung
            ->tap(fn ($query) => DepartmentChemical::joinUnit($query, $departmentId, 'chemical_imports.category_id'))
            // Phòng ban nhận và mã lô đã sinh ra bên đó, chỉ có ở phiếu chuyển kho
            ->leftJoin('deparments', self::TABLE.'.to_department_id', '=', 'deparments.id')
            ->leftJoin('chemical_imports as received', self::TABLE.'.received_import_id', '=', 'received.id')
            ->select(
                self::TABLE.'.*',
                'chemical_categories.code as category_code',
                'chemical_categories.classification',
                'chem_names.name as chem_name',
                'units.short_name as unit_short_name',
                'units.name as unit_name',
                'chemical_imports.amount as import_amount',
                'chemical_imports.batch_no',
                'chemical_imports.expired_date',
                'deparments.name as to_department_name',
                'deparments.shortName as to_department_short',
                'received.code as received_code',
                // Phiếu loại bỏ đã gom vào đợt huỷ nào, để khoá nút Sửa / Khoá trên bảng
                'chemical_disposals.code as disposal_code',
                'chemical_disposals.app_status as disposal_status'
            )
            ->leftJoin('chemical_disposals', self::TABLE.'.disposal_id', '=', 'chemical_disposals.id')
            ->where(self::TABLE.'.department_id', $departmentId)
            ->orderBy(self::TABLE.'.exported_date', 'desc')
            ->orderBy(self::TABLE.'.id', 'desc')
            ->get();

        session()->put(['title' => 'SỬ DỤNG - SỬ DỤNG HOÁ CHẤT']);

        [$from, $to] = $this->reportRange($request);

        $requests = $this->transferRequests($departmentId);

        return view('pages.export.ChemicalExport.list', [
            'datas' => $datas,
            'categories' => $this->categoryOptions($departmentId),
            'requestsSent' => $requests['sent'],
            'requestsReceived' => $requests['received'],
            'chemical_imports' => $this->importOptions($departmentId),
            'checkers' => $this->checkerOptions($departmentId),
            'departments' => $this->departmentOptions($departmentId),
            'types' => self::TYPES,
            'overIssuePercent' => (int) round(self::OVER_ISSUE_RATIO * 100),
            'adjustCounts' => $this->adjustCounts($departmentId),
            'report' => $this->usageReport($departmentId, $from, $to),
            'reportFrom' => $from,
            'reportTo' => $to,
            // Bước 2 của nghiệp vụ huỷ: hàng chờ huỷ và các đợt xin quyết định huỷ
            'waitingDisposal' => ChemicalDisposalController::waiting($departmentId),
            'chemical_disposals' => ChemicalDisposalController::batches($departmentId),
            'disposalStatuses' => ChemicalDisposalController::STATUSES,
            'disposalMethods' => ChemicalDisposalController::METHODS,
            'disposalExecutors' => ChemicalDisposalController::EXECUTORS,
            // Lọc xong thì trang tải lại, quay về đúng tab thay vì tab sổ
            'activeTab' => in_array($request->input('tab'), ['report', 'request', 'disposal'], true)
                ? $request->input('tab')
                : 'book',
        ]);
    }

    /**
     * TRA LÔ THEO MÃ XUẤT NHẬP - quét mã vạch trên nhãn hoặc gõ tay mã.
     *
     * Trả về đúng lô của phòng ban đang đứng kèm cờ có xuất được hay không. Điều kiện
     * xuất được lấy nguyên từ importOptions() - cùng một nguồn với ô chọn phiếu nhập
     * trên form, để quét mã và chọn tay không bao giờ cho hai kết quả khác nhau.
     */
    public function lookup(Request $request)
    {
        $code = trim((string) $request->code);

        if ($code === '') {
            return response()->json(['ok' => false, 'reason' => 'Vui lòng quét mã vạch trên nhãn hoặc nhập mã xuất nhập.']);
        }

        $import = $this->importOptions($this->departmentId())->firstWhere('code', $code);

        if (! $import) {
            return response()->json([
                'ok' => false,
                'reason' => 'Không tìm thấy mã xuất nhập "'.$code.'" trong kho của phòng ban này, '
                    .'hoặc phiếu nhập đã bị khoá.',
            ]);
        }

        return response()->json([
            'ok' => (bool) $import->selectable,
            'id' => $import->id,
            'code' => $import->code,
            'chem_name' => $import->chem_name ?: '—',
            'category_code' => $import->category_code ?: '—',
            'batch_no' => $import->batch_no ?: '—',
            'remaining' => $this->number($import->remaining).' '.($import->unit_short_name ?: ''),
            'expired_date' => $import->expired_date
                ? \Carbon\Carbon::parse($import->expired_date)->format('d/m/Y')
                : '—',
            'reason' => $import->selectable ? null : $this->notSelectableReason($import),
        ]);
    }

    /** Vì sao một lô không xuất được, viết đúng cách xử lý tiếp theo cho người dùng. */
    private function notSelectableReason($import): string
    {
        if ($import->expired) {
            return 'Lô '.$import->code.' đã hết hạn sử dụng ngày '
                .\Carbon\Carbon::parse($import->expired_date)->format('d/m/Y')
                .' nên không xuất ra sử dụng được.';
        }

        if ($import->waiting_internal) {
            return 'Lô '.$import->code.' chưa xác định hạn dùng nội bộ. Vào màn hình Tồn Kho Hoá Chất, '
                .'tab "Chưa Xác Định Hạn Nội Bộ" để xác định trước.';
        }

        return 'Lô '.$import->code.' đã hết tồn, vui lòng quét lô khác.';
    }

    public function store(Request $request)
    {
        $departmentId = $this->departmentId();

        $import = $this->findImport($request->import_id, $departmentId);

        $validator = Validator::make($request->all(), $this->rules($departmentId), $this->messages());
        $this->checkImport($validator, $request, $import);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        $id = DB::table(self::TABLE)->insertGetId($this->payload($request, $import) + [
            'department_id' => $departmentId,
            // Người sử dụng luôn là người đang đăng nhập, không nhận giá trị từ form
            'exported_by' => $this->actor(),
            'status_id' => 1,
            'created_by' => $this->actor(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->logHistory($id, 'Thêm mới');

        // Lập phiếu chuyển từ một đề nghị thì gắn ngược phiếu vào đề nghị đó, để đối
        // chiếu được đề nghị với hàng đã đi thật. Chỉ nhận đề nghị gửi ĐẾN phòng mình.
        if ($request->filled('request_id')) {
            DB::table(self::REQUEST_TABLE)
                ->where('id', $request->request_id)
                ->where('to_department_id', $departmentId)
                ->whereNull('export_id')
                ->update(['export_id' => $id, 'updated_by' => $this->actor(), 'updated_at' => now()]);
        }

        AuditTrialController::log(
            'Thêm mới',
            self::TABLE,
            $id,
            'NA',
            self::TYPES[$request->type].' hoá chất, mã xuất nhập: '.$import->code.', số lượng: '.$request->amount
        );

        return redirect()->back()->with('success', 'Đã ghi nhận '.self::LABEL.' cho phiếu nhập '.$import->code.'!');
    }

    public function update(Request $request)
    {
        $departmentId = $this->departmentId();

        $current = DB::table(self::TABLE)
            ->where('id', $request->id)
            ->where('department_id', $departmentId)
            ->first();

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::LABEL.' cần cập nhật!');
        }

        if ($blocked = $this->receivedGuard($current, 'cập nhật')) {
            return $blocked;
        }

        if ($blocked = $this->disposalGuard($current, 'cập nhật')) {
            return $blocked;
        }

        $import = $this->findImport($request->import_id, $departmentId);

        $validator = Validator::make($request->all(), $this->rules($departmentId), $this->messages());
        // Bỏ qua chính bản ghi đang sửa khi tính tồn, nếu không số lượng cũ bị trừ hai lần.
        // Giữ nguyên phiếu nhập cũ thì không xét lại điều kiện hạn dùng / còn tồn,
        // phiếu đã ghi rồi, chỉ khi ĐỔI sang phiếu khác mới coi là một lần chọn mới.
        $this->checkImport($validator, $request, $import, (int) $current->id, (int) $current->import_id);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        $payload = $this->payload($request, $import);

        // Dựng mô tả thay đổi TRƯỚC khi ghi đè, lúc này còn cả giá trị cũ lẫn mới
        $note = $this->changeNote($current, $payload, $request->adjust_reason);

        if ($note === '') {
            return redirect()->back()->with('error', 'Không có thông tin nào thay đổi nên chưa cập nhật '.self::LABEL.'.');
        }

        DB::table(self::TABLE)->where('id', $current->id)->update($payload + [
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        $this->logHistory($current->id, 'Cập nhật', $note);

        AuditTrialController::log('Cập nhật', self::TABLE, $current->id, $current->code, $note);

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

        if ($blocked = $this->receivedGuard($current, 'khoá / mở khoá')) {
            return $blocked;
        }

        if ($blocked = $this->disposalGuard($current, 'khoá / mở khoá')) {
            return $blocked;
        }

        // Phiếu bị phòng nhận từ chối thì không mở lại được: mở lại là ép phòng kia
        // nhận thứ họ đã nói không nhận. Muốn chuyển tiếp thì lập phiếu chuyển mới.
        if ($current->rejected_at && $current->status_id == 0) {
            return redirect()->back()->with(
                'error',
                'Phiếu chuyển kho '.$current->code.' đã bị phòng nhận từ chối ngày '
                .\Carbon\Carbon::parse($current->rejected_at)->format('d/m/Y H:i')
                .' nên không mở khoá lại được. Lý do: '.($current->reject_reason ?: 'không ghi')
                .'. Vui lòng lập phiếu chuyển mới.'
            );
        }

        $newStatus = $current->status_id == 1 ? 0 : 1;

        // Mở khoá lại thì số lượng cũ phải còn nằm trong hạn mức xuất của phiếu nhập
        if ($newStatus == 1) {
            $import = DB::table('chemical_imports')->where('id', $current->import_id)->first();
            $remaining = $import ? $this->remaining($import, (int) $current->id) : 0;

            if (! $import || (float) $current->amount > $this->maxIssuable($remaining, $import) + self::EPSILON) {
                return redirect()->back()->with(
                    'error',
                    'Không mở khoá được: phiếu nhập chỉ còn '.$this->number($remaining).' trong khi phiếu này cần '.$this->number((float) $current->amount).'.'
                );
            }
        }

        DB::table(self::TABLE)->where('id', $current->id)->update([
            'status_id' => $newStatus,
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        $action = $newStatus == 1 ? 'Mở khoá' : 'Khoá';

        $this->logHistory(
            $current->id,
            $action,
            $action.' phiếu'.($request->filled('adjust_reason') ? '. Lý do: '.trim($request->adjust_reason) : '')
        );

        AuditTrialController::log(
            $action,
            self::TABLE,
            $current->id,
            'status_id: '.$current->status_id,
            'status_id: '.$newStatus
        );

        return redirect()->back()->with(
            'success',
            ($newStatus == 1 ? 'Đã mở khoá ' : 'Đã khoá ').self::LABEL.' của phiếu nhập '.$current->code.'!'
        );
    }

    /**
     * Số lần ĐIỀU CHỈNH của từng phiếu: [export_id => số lần].
     *
     * Bỏ dòng "Thêm mới" vì đó là lúc lập phiếu chứ không phải một lần chỉnh sửa.
     * Badge trên nút Sửa chỉ hiện khi phiếu thật sự đã bị đổi ít nhất một lần.
     */
    private function adjustCounts(int $departmentId)
    {
        return DB::table(self::HISTORY_TABLE)
            ->select('export_id', DB::raw('COUNT(*) as times'))
            ->whereIn('export_id', function ($query) use ($departmentId) {
                $query->select('id')
                    ->from(self::TABLE)
                    ->where('department_id', $departmentId);
            })
            ->where('action', '<>', 'Thêm mới')
            ->groupBy('export_id')
            ->pluck('times', 'export_id');
    }

    /** Trả về lịch sử điều chỉnh của một phiếu sử dụng cho modal xem lịch sử. */
    public function history(Request $request)
    {
        $rows = DB::table(self::HISTORY_TABLE)
            ->leftJoin('chemical_imports', self::HISTORY_TABLE.'.import_id', '=', 'chemical_imports.id')
            ->leftJoin('chemical_categories', 'chemical_imports.category_id', '=', 'chemical_categories.id')
            ->leftJoin('chem_names', 'chemical_categories.chem_names_id', '=', 'chem_names.id')
            ->tap(fn ($query) => DepartmentChemical::joinUnit($query, $this->departmentId(), 'chemical_imports.category_id'))
            ->leftJoin('deparments', self::HISTORY_TABLE.'.to_department_id', '=', 'deparments.id')
            ->select(
                self::HISTORY_TABLE.'.*',
                'chem_names.name as chem_name',
                'units.short_name as unit_short_name',
                'units.name as unit_name',
                'deparments.name as to_department_name'
            )
            // Chỉ cho xem lịch sử của phiếu thuộc phòng ban đang chọn
            ->whereIn(self::HISTORY_TABLE.'.export_id', function ($query) {
                $query->select('id')
                    ->from(self::TABLE)
                    ->where('department_id', $this->departmentId());
            })
            ->where(self::HISTORY_TABLE.'.export_id', $request->id)
            ->orderBy(self::HISTORY_TABLE.'.id', 'desc')
            ->get();

        return response()->json([
            'rows' => $rows->map(function ($row) {
                $unit = $row->unit_short_name ?: $row->unit_name;

                return [
                    'action' => $row->action,
                    'change_note' => $row->change_note,
                    'created_by' => $row->created_by ?: 'NA',
                    'created_at' => $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('d/m/Y H:i') : '',
                    'snapshot' => [
                        'Mã xuất nhập' => $row->code ?: '—',
                        'Hoá chất' => $row->chem_name ?: '—',
                        'Số lượng' => $row->amount !== null ? $this->number((float) $row->amount).' '.$unit : '—',
                        'Loại phiếu' => self::TYPES[$row->type] ?? ($row->type ?: '—'),
                        'Phòng ban nhận' => $row->to_department_name ?: '—',
                        'Ngày sử dụng' => $row->exported_date ? \Carbon\Carbon::parse($row->exported_date)->format('d/m/Y') : '—',
                        'Người sử dụng' => $row->exported_by ?: '—',
                        'Người kiểm tra' => $row->checked_by ?: '—',
                        'Mục đích sử dụng' => $row->purpose ?: '—',
                        'Số phiếu KN, OOS, BCSL' => $row->test_report_no ?: '—',
                        'Trạng thái' => $row->status_id == 1 ? 'Hiệu lực' : 'Đã khoá',
                    ],
                ];
            })->values(),
        ]);
    }

    /**
     * Chụp lại giá trị của phiếu NGAY SAU khi thay đổi vào bảng lịch sử.
     *
     * Đọc lại từ DB thay vì dùng payload để ảnh chụp luôn khớp đúng những gì đã lưu.
     */
    private function logHistory($id, string $action, ?string $note = null): void
    {
        $row = DB::table(self::TABLE)->where('id', $id)->first();

        if (! $row) {
            return;
        }

        DB::table(self::HISTORY_TABLE)->insert([
            'export_id' => $row->id,
            'action' => $action,
            'code' => $row->code,
            'import_id' => $row->import_id,
            'amount' => $row->amount,
            'type' => $row->type,
            'to_department_id' => $row->to_department_id,
            'exported_date' => $row->exported_date,
            'exported_by' => $row->exported_by,
            'purpose' => $row->purpose,
            'test_report_no' => $row->test_report_no,
            'checked_by' => $row->checked_by,
            'status_id' => $row->status_id,
            'change_note' => $note,
            'created_by' => $this->actor(),
            'created_at' => now(),
        ]);
    }

    /**
     * Mô tả nội dung đã đổi theo dạng "Trường: cũ -> mới".
     *
     * Trả về chuỗi rỗng khi không có gì đổi, để màn hình không ghi một dòng lịch sử trống.
     * Lý do người dùng nhập được đặt lên đầu chuỗi.
     */
    private function changeNote($current, array $payload, ?string $reason = null): string
    {
        $parts = [];

        foreach (self::FIELDS as $field => $title) {
            $old = $current->$field;
            $new = $payload[$field] ?? null;

            // amount lấy từ DB là chuỗi "10.5000" còn form gửi "10.5", phải so theo giá trị số
            if ($field === 'amount') {
                if (abs((float) $old - (float) $new) < self::EPSILON) {
                    continue;
                }

                $parts[] = $title.': '.$this->number((float) $old).' -> '.$this->number((float) $new);

                continue;
            }

            if ((string) $old === (string) $new) {
                continue;
            }

            if ($field === 'type') {
                $parts[] = $title.': '.(self::TYPES[$old] ?? '—').' -> '.(self::TYPES[$new] ?? '—');

                continue;
            }

            if ($field === 'exported_date') {
                $parts[] = $title.': '.$this->historyDate($old).' -> '.$this->historyDate($new);

                continue;
            }

            if ($field === 'to_department_id') {
                $parts[] = $title.': '.$this->departmentName($old).' -> '.$this->departmentName($new);

                continue;
            }

            $parts[] = $title.': '.($old === null || $old === '' ? '—' : $old).' -> '.($new === null || $new === '' ? '—' : $new);
        }

        if (! $parts) {
            return '';
        }

        $reason = trim((string) $reason);

        return ($reason !== '' ? 'Lý do: '.$reason.' | ' : '').implode(' | ', $parts);
    }

    private function historyDate($value): string
    {
        return $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';
    }

    private function departmentName($id): string
    {
        return $id ? (DB::table('deparments')->where('id', $id)->value('name') ?: 'NA') : '—';
    }

    /**
     * Phiếu nhập của phòng ban đang chọn, còn hiệu lực.
     *
     * Kèm sẵn tồn còn lại / hạn mức xuất để form hiển thị mà không phải hỏi DB theo
     * từng phiếu. Cờ selectable = còn hạn dùng VÀ còn tồn > 0: modal Thêm mới chỉ
     * hiện phiếu selectable, modal Cập Nhật giữ cả phiếu không selectable để phiếu
     * xuất cũ còn chọn lại được đúng phiếu nhập của nó.
     */
    private function importOptions(int $departmentId)
    {
        $used = $this->sumByImport(self::TABLE, 'amount', $departmentId);
        $balanced = $this->sumByImport('chemical_balancings', 'balancing_amount', $departmentId);
        $today = now()->startOfDay();

        $query = DB::table('chemical_imports')
            ->leftJoin('chemical_categories', 'chemical_imports.category_id', '=', 'chemical_categories.id')
            ->leftJoin('chem_names', 'chemical_categories.chem_names_id', '=', 'chem_names.id');

        // Hạn dùng nội bộ và đơn vị tính lấy theo cấu hình của phòng ban đang chọn
        return DepartmentChemical::join($query, $departmentId, 'chemical_imports.category_id')
            ->leftJoin('units', DepartmentChemical::TABLE.'.unit_id', '=', 'units.id')
            ->select(
                'chemical_imports.id',
                'chemical_imports.code',
                'chemical_imports.amount',
                'chemical_imports.batch_no',
                'chemical_imports.expired_date',
                'chemical_imports.internal_expired_date',
                'chemical_imports.is_partial_lot',
                'chemical_categories.code as category_code',
                DepartmentChemical::shelfLifeColumn(),
                'chem_names.name as chem_name',
                'units.short_name as unit_short_name'
            )
            ->where('chemical_imports.department_id', $departmentId)
            ->where('chemical_imports.status_id', 1)
            ->orderBy('chemical_imports.imported_date', 'desc')
            ->orderBy('chemical_imports.id', 'desc')
            ->get()
            ->map(function ($import) use ($used, $balanced, $today) {
                $import->used = (float) ($used[$import->id] ?? 0);
                $import->balanced = (float) ($balanced[$import->id] ?? 0);
                $import->remaining = max((float) $import->amount + $import->balanced - $import->used, 0);
                $import->max_amount = $this->maxIssuable($import->remaining, $import);

                $import->expired = $import->expired_date
                    && \Carbon\Carbon::parse($import->expired_date)->startOfDay()->lt($today);

                // Hoá chất có hạn dùng mặc định mà chưa xác định hạn nội bộ thì chưa được dùng
                $import->waiting_internal = (int) ($import->shelf_life_months ?? 0) > 0
                    && ! $import->internal_expired_date;

                $import->selectable = ! $import->expired
                    && ! $import->waiting_internal
                    && $import->remaining > self::EPSILON;

                return $import;
            });
    }

    /**
     * Khoảng thời gian của báo cáo sử dụng.
     *
     * Mặc định từ đầu tháng hiện tại đến hôm nay. Người dùng nhập ngược ngày thì
     * đảo lại cho đúng thay vì trả về báo cáo rỗng.
     *
     * @return array{0: string, 1: string}
     */
    private function reportRange(Request $request): array
    {
        $parse = function ($value, $fallback) {
            try {
                return $value ? \Carbon\Carbon::parse($value)->format('Y-m-d') : $fallback;
            } catch (\Exception $e) {
                return $fallback;
            }
        };

        $from = $parse($request->input('from'), now()->startOfMonth()->format('Y-m-d'));
        $to = $parse($request->input('to'), now()->format('Y-m-d'));

        return $from <= $to ? [$from, $to] : [$to, $from];
    }

    /**
     * BÁO CÁO SỬ DỤNG HOÁ CHẤT THEO KHOẢNG THỜI GIAN.
     *
     * Cộng dồn các phiếu sử dụng còn hiệu lực trong khoảng ngày, gom theo
     * chemical_categories.code (mã danh mục hoá chất). Mỗi dòng có:
     * - Số lượng theo đơn vị phòng đã khai cho hoá chất đó (department_chemicals.unit_id)
     * - Số lượng QUY ĐỔI SANG KG qua App\Support\UnitConverter
     *
     * Đơn vị nhóm đếm (chai, thùng...) không quy đổi tự động được, và đổi thể tích
     * sang khối lượng thì cần tỉ trọng d (g/ml) của hoá chất - thiếu thì để trống
     * kèm lý do thay vì hiện số sai.
     */
    private function usageReport(int $departmentId, string $from, string $to)
    {
        $kgUnit = DB::table('units')->where('short_name', 'kg')->first();

        // Chuỗi trong DB::raw là hằng, không ghép từ dữ liệu người dùng
        $rows = DB::table(self::TABLE)
            ->join('chemical_imports', self::TABLE.'.import_id', '=', 'chemical_imports.id')
            ->join('chemical_categories', 'chemical_imports.category_id', '=', 'chemical_categories.id')
            ->leftJoin('chem_names', 'chemical_categories.chem_names_id', '=', 'chem_names.id')
            ->tap(fn ($query) => DepartmentChemical::joinUnit($query, $departmentId, 'chemical_imports.category_id'))
            ->select(
                'chemical_categories.id as category_id',
                'chemical_categories.code as category_code',
                'chemical_categories.density',
                'chemical_categories.classification',
                'chem_names.name as chem_name',
                'units.short_name as unit_short_name',
                'units.name as unit_name',
                'units.unit_group',
                'units.factor_to_base',
                DB::raw('SUM(CASE WHEN '.self::TABLE.".type = 'export' THEN ".self::TABLE.'.amount ELSE 0 END) as used'),
                DB::raw('SUM(CASE WHEN '.self::TABLE.".type = 'cancel' THEN ".self::TABLE.'.amount ELSE 0 END) as cancelled'),
                DB::raw('SUM('.self::TABLE.'.amount) as total'),
                DB::raw('COUNT(*) as times'),
                DB::raw('COUNT(DISTINCT '.self::TABLE.'.import_id) as code_count'),
                DB::raw('MAX('.self::TABLE.'.exported_date) as last_exported_date')
            )
            ->where(self::TABLE.'.department_id', $departmentId)
            ->where(self::TABLE.'.status_id', 1)
            ->whereBetween(self::TABLE.'.exported_date', [$from, $to])
            // Gom đủ mọi cột không phải hàm tổng, tránh lỗi ONLY_FULL_GROUP_BY của MySQL
            ->groupBy(
                'chemical_categories.id',
                'chemical_categories.code',
                'chemical_categories.density',
                'chemical_categories.classification',
                'chem_names.name',
                'units.short_name',
                'units.name',
                'units.unit_group',
                'units.factor_to_base'
            )
            ->orderBy('chemical_categories.code', 'asc')
            ->get();

        return $rows->map(function ($row) use ($kgUnit) {
            $unit = (object) [
                'unit_group' => $row->unit_group,
                'factor_to_base' => $row->factor_to_base,
            ];
            $density = $row->density !== null ? (float) $row->density : null;

            $row->used = (float) $row->used;
            $row->cancelled = (float) $row->cancelled;
            $row->total = (float) $row->total;
            $row->unit = $row->unit_short_name ?: $row->unit_name;

            $check = $kgUnit
                ? UnitConverter::check($unit, $kgUnit, $density)
                : ['ok' => false, 'reason' => 'Chưa có đơn vị "kg" trong Dữ Liệu Gốc nên không quy đổi được.'];

            $row->convertible = $check['ok'];
            $row->convert_note = $check['reason'];

            $convert = fn ($value) => $check['ok'] && $kgUnit
                ? UnitConverter::convert($value, $unit, $kgUnit, $density)
                : null;

            $row->used_kg = $convert($row->used);
            $row->cancelled_kg = $convert($row->cancelled);
            $row->total_kg = $convert($row->total);

            return $row;
        });
    }

    /**
     * Phiếu chuyển kho đã được phòng nhận lấy hàng thì khoá lại, không cho sửa nữa.
     *
     * Phòng nhận đã ghi tồn theo số lượng này, phòng gửi sửa tiếp là hai bên lệch số.
     * Muốn đổi thì phòng nhận phải huỷ lô đã nhận trước.
     *
     * @return \Illuminate\Http\RedirectResponse|null null nghĩa là được phép đi tiếp
     */
    private function receivedGuard($current, string $action)
    {
        if (! $current->received_import_id) {
            return null;
        }

        return redirect()->back()->with(
            'error',
            'Phiếu chuyển kho '.$current->code.' đã được phòng nhận lấy hàng nên không '.$action.' được nữa. '
            .'Phòng nhận phải khoá lô đã nhận trước khi phòng gửi chỉnh lại phiếu.'
        );
    }

    /**
     * Phiếu loại bỏ đã được gom vào một đợt huỷ thì khoá lại, không cho sửa nữa.
     *
     * Số lượng, số lô và căn cứ loại bỏ trên phiếu chính là nội dung đã đưa vào hồ sơ
     * xin quyết định huỷ. Sửa phiếu sau khi gom là hồ sơ một đằng, dữ liệu một nẻo.
     * Đợt còn đang gom thì gỡ phiếu ra khỏi đợt là sửa lại được.
     *
     * @return \Illuminate\Http\RedirectResponse|null null nghĩa là được phép đi tiếp
     */
    private function disposalGuard($current, string $action)
    {
        if (! $current->disposal_id) {
            return null;
        }

        $batch = DB::table('chemical_disposals')->where('id', $current->disposal_id)->first();

        return redirect()->back()->with(
            'error',
            'Phiếu loại bỏ '.$current->code.' đã được gom vào đợt huỷ '.($batch->code ?? '')
            .' ('.(ChemicalDisposalController::STATUSES[$batch->app_status ?? ''] ?? 'không rõ').') nên không '
            .$action.' được nữa. '
            .(($batch->app_status ?? '') === 'draft'
                ? 'Vào tab "Hoá chất chờ huỷ", gỡ phiếu khỏi đợt rồi sửa.'
                : 'Đợt đã trình duyệt, danh sách phiếu phải giữ nguyên như hồ sơ đã gửi.')
        );
    }

    /**
     * ĐỀ NGHỊ CHUYỂN HOÁ CHẤT - phòng đang thiếu gửi đề nghị sang phòng đang có.
     *
     * Đề nghị chỉ là NGUỒN THÔNG TIN trước khi chuyển, không động vào tồn kho. Tồn chỉ
     * đổi khi phòng giữ hàng lập phiếu chuyển và phòng nhận bấm Nhận.
     */
    public function requestStore(Request $request)
    {
        $departmentId = $this->departmentId();

        $validator = Validator::make($request->all(), [
            'to_department_id' => [
                'required',
                Rule::exists('deparments', 'id')->where('active', 1),
                Rule::notIn([$departmentId]),
            ],
            'category_id' => ['required', 'exists:chemical_categories,id'],
            'amount' => ['required', 'numeric', 'min:0.0001'],
            'needed_date' => ['nullable', 'date'],
            'reason' => ['nullable', 'max:500'],
        ], [
            'to_department_id.required' => 'Vui lòng chọn phòng ban được đề nghị.',
            'to_department_id.exists' => 'Phòng ban được đề nghị không tồn tại hoặc đã ngừng hoạt động.',
            'to_department_id.not_in' => 'Không gửi đề nghị cho chính phòng ban của mình.',
            'category_id.required' => 'Vui lòng chọn hoá chất cần.',
            'category_id.exists' => 'Hoá chất được chọn không tồn tại trong danh mục.',
            'amount.required' => 'Vui lòng nhập số lượng cần.',
            'amount.numeric' => 'Số lượng phải là số.',
            'amount.min' => 'Số lượng phải lớn hơn 0.',
            'needed_date.date' => 'Ngày cần không hợp lệ.',
            'reason.max' => 'Lý do tối đa 500 ký tự.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'requestErrors')->withInput();
        }

        $id = DB::table(self::REQUEST_TABLE)->insertGetId([
            'department_id' => $departmentId,
            'to_department_id' => (int) $request->to_department_id,
            'category_id' => (int) $request->category_id,
            'amount' => (float) $request->amount,
            'needed_date' => $this->nullIfBlank($request->needed_date),
            'reason' => $this->nullIfBlank($request->reason),
            'app_status' => 'pending',
            'requested_by' => $this->actor(),
            'status_id' => 1,
            'created_by' => $this->actor(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        AuditTrialController::log(
            'Thêm mới',
            self::REQUEST_TABLE,
            $id,
            'NA',
            'Đề nghị chuyển hoá chất cho '.$this->departmentName($request->to_department_id).', số lượng: '.$request->amount
        );

        return redirect()->back()->with('success', 'Đã gửi đề nghị chuyển hoá chất, chờ phòng ban kia trả lời!');
    }

    /**
     * Trả lời một đề nghị gửi ĐẾN phòng ban đang đứng: đồng ý hoặc từ chối.
     *
     * Đồng ý mới chỉ là trả lời, hàng chưa đi. Phòng giữ hàng vẫn phải lập phiếu chuyển
     * (chọn đúng mã lô nào để chuyển) - lúc đó phiếu mới được gắn ngược lại đề nghị này.
     */
    public function requestRespond(Request $request)
    {
        $departmentId = $this->departmentId();

        $current = DB::table(self::REQUEST_TABLE)
            ->where('id', $request->id)
            // Chỉ phòng ĐƯỢC ĐỀ NGHỊ mới được trả lời
            ->where('to_department_id', $departmentId)
            ->where('app_status', 'pending')
            ->first();

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy đề nghị cần trả lời, hoặc đề nghị đã được trả lời rồi!');
        }

        $validator = Validator::make($request->all(), [
            'app_status' => ['required', 'in:accepted,rejected'],
            // Từ chối thì bắt buộc nói lý do, đồng ý thì ghi chú tuỳ ý
            'response_note' => [$request->app_status === 'rejected' ? 'required' : 'nullable', 'max:500'],
        ], [
            'app_status.required' => 'Vui lòng chọn đồng ý hoặc từ chối.',
            'app_status.in' => 'Lựa chọn trả lời không hợp lệ.',
            'response_note.required' => 'Từ chối đề nghị thì phải ghi lý do.',
            'response_note.max' => 'Nội dung trả lời tối đa 500 ký tự.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'respondErrors')->withInput();
        }

        DB::table(self::REQUEST_TABLE)->where('id', $current->id)->update([
            'app_status' => $request->app_status,
            'response_note' => $this->nullIfBlank($request->response_note),
            'responded_by' => $this->actor(),
            'responded_at' => now(),
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        AuditTrialController::log(
            $request->app_status === 'accepted' ? 'Đồng ý đề nghị' : 'Từ chối đề nghị',
            self::REQUEST_TABLE,
            $current->id,
            'pending',
            $request->app_status.($request->response_note ? ' - '.$request->response_note : '')
        );

        return redirect()->back()->with(
            'success',
            $request->app_status === 'accepted'
                ? 'Đã đồng ý đề nghị. Lập phiếu Chuyển kho để hàng đi thật sự.'
                : 'Đã từ chối đề nghị.'
        );
    }

    /** Đề nghị phòng mình GỬI ĐI và đề nghị GỬI ĐẾN phòng mình. */
    private function transferRequests(int $departmentId): array
    {
        $base = fn () => DB::table(self::REQUEST_TABLE)
            ->leftJoin('chemical_categories', self::REQUEST_TABLE.'.category_id', '=', 'chemical_categories.id')
            ->leftJoin('chem_names', 'chemical_categories.chem_names_id', '=', 'chem_names.id')
            // Đơn vị theo PHÒNG ĐÃ TẠO đề nghị (chính chủ request), dù đang xem chiều gửi
            // hay chiều nhận: số lượng ghi trong đề nghị luôn theo đơn vị của phòng đó.
            ->tap(fn ($query) => DepartmentChemical::joinUnitOn(
                $query,
                self::REQUEST_TABLE.'.department_id',
                self::REQUEST_TABLE.'.category_id'
            ))
            ->leftJoin('chemical_exports', self::REQUEST_TABLE.'.export_id', '=', 'chemical_exports.id')
            ->select(
                self::REQUEST_TABLE.'.*',
                'chemical_categories.code as category_code',
                'chem_names.name as chem_name',
                'units.short_name as unit_short_name',
                'units.name as unit_name',
                'chemical_exports.code as export_code'
            )
            ->where(self::REQUEST_TABLE.'.status_id', 1)
            ->orderBy(self::REQUEST_TABLE.'.id', 'desc');

        return [
            // Phòng mình cần hàng, gửi đề nghị đi
            'sent' => $base()
                ->leftJoin('deparments', self::REQUEST_TABLE.'.to_department_id', '=', 'deparments.id')
                ->addSelect('deparments.name as partner_name', 'deparments.shortName as partner_short')
                ->where(self::REQUEST_TABLE.'.department_id', $departmentId)
                ->get(),
            // Phòng khác cần hàng của phòng mình
            'received' => $base()
                ->leftJoin('deparments', self::REQUEST_TABLE.'.department_id', '=', 'deparments.id')
                ->addSelect('deparments.name as partner_name', 'deparments.shortName as partner_short')
                ->where(self::REQUEST_TABLE.'.to_department_id', $departmentId)
                ->get(),
        ];
    }

    /** Danh mục hoá chất để chọn khi gửi đề nghị. */
    private function categoryOptions(int $departmentId)
    {
        return DB::table('chemical_categories')
            ->leftJoin('chem_names', 'chemical_categories.chem_names_id', '=', 'chem_names.id')
            // Đơn vị hiện trên ô chọn là đơn vị PHÒNG ĐANG CHỌN đã khai cho hoá chất đó
            ->tap(fn ($query) => DepartmentChemical::joinUnit($query, $departmentId, 'chemical_categories.id'))
            ->select(
                'chemical_categories.id',
                'chemical_categories.code',
                'chem_names.name as chem_name',
                'units.short_name as unit_short_name'
            )
            ->where('chemical_categories.status_id', 1)
            ->where('chemical_categories.app_status', 'approved')
            ->orderBy('chemical_categories.code', 'asc')
            ->get();
    }

    /** Phòng ban nhận hàng chuyển kho: mọi phòng đang hoạt động, trừ phòng đang đứng. */
    private function departmentOptions(int $departmentId)
    {
        return DB::table('deparments')
            ->select('id', 'name', 'shortName')
            ->where('active', 1)
            ->where('id', '<>', $departmentId)
            ->orderBy('name', 'asc')
            ->get();
    }

    /** Người kiểm tra: user đang hoạt động của phòng ban đang chọn. */
    private function checkerOptions(int $departmentId)
    {
        // user_management.deparment lưu theo deparments.shortName chứ không phải id
        return DB::table('user_management')
            ->join('deparments', 'user_management.deparment', '=', 'deparments.shortName')
            ->select('user_management.userName', 'user_management.fullName')
            ->where('deparments.id', $departmentId)
            ->where('user_management.isActive', 1)
            ->where('user_management.isLocked', 0)
            ->orderBy('user_management.fullName', 'asc')
            ->get();
    }

    /** Tổng một cột số theo từng phiếu nhập trong phòng ban: [import_id => tổng]. */
    private function sumByImport(string $table, string $column, int $departmentId)
    {
        return DB::table($table)
            ->select('import_id', DB::raw('SUM(`'.$column.'`) as total'))
            ->where('department_id', $departmentId)
            ->where('status_id', 1)
            ->groupBy('import_id')
            ->pluck('total', 'import_id');
    }

    /**
     * Tồn còn lại của một phiếu nhập, có thể bỏ qua một phiếu xuất đang được sửa.
     *
     * Tồn = số lượng nhập + số đã cân đối - số đã xuất (kể cả phần huỷ bỏ).
     */
    private function remaining($import, ?int $ignoreExportId = null): float
    {
        $query = DB::table(self::TABLE)
            ->where('import_id', $import->id)
            ->where('status_id', 1);

        if ($ignoreExportId) {
            $query->where('id', '<>', $ignoreExportId);
        }

        $balanced = (float) DB::table('chemical_balancings')
            ->where('import_id', $import->id)
            ->where('status_id', 1)
            ->sum('balancing_amount');

        return max((float) $import->amount + $balanced - (float) $query->sum('amount'), 0);
    }

    /**
     * Hạn mức được xuất: tồn còn lại cộng thêm phần vượt cho phép.
     *
     * LÔ LẺ (nhận một phần từ phòng ban khác) không được vượt: phòng gửi đã cân chia
     * sẵn nên số lượng là con số đã chốt, không còn hao hụt cân đong để trừ hao.
     */
    private function maxIssuable(float $remaining, $import = null): float
    {
        if ($import && ! empty($import->is_partial_lot)) {
            return $remaining;
        }

        return $remaining * (1 + self::OVER_ISSUE_RATIO);
    }

    /**
     * CHUYỂN NGUYÊN hay không: lô còn y nguyên như lúc nhập và chuyển đi trọn vẹn.
     *
     * Phải đủ cả ba điều kiện:
     * 1. Số lượng chuyển đúng bằng LƯỢNG NHẬP GỐC (imports.amount, KHÔNG cộng cân đối).
     * 2. Lô chưa cân đối lần nào - đã cân đối nghĩa là đã đụng vào, không còn nguyên.
     * 3. Lô chưa xuất ra lần nào - đã dùng / huỷ / chuyển bớt thì cũng không còn nguyên.
     *
     * Thiếu bất kỳ điều kiện nào thì là CHUYỂN LẺ.
     *
     * @param  int|null  $ignoreExportId  phiếu xuất đang sửa, không tính vào phần đã xuất
     */
    private function isFullTransfer($import, $amount, ?int $ignoreExportId = null): bool
    {
        if (abs((float) $amount - (float) $import->amount) > self::EPSILON) {
            return false;
        }

        $balanced = DB::table('chemical_balancings')
            ->where('import_id', $import->id)
            ->where('status_id', 1)
            ->exists();

        if ($balanced) {
            return false;
        }

        $used = DB::table(self::TABLE)
            ->where('import_id', $import->id)
            ->where('status_id', 1);

        if ($ignoreExportId) {
            $used->where('id', '<>', $ignoreExportId);
        }

        return ! $used->exists();
    }

    private function findImport($importId, int $departmentId)
    {
        // Kèm shelf_life_months (theo cấu hình phòng ban) để kiểm tra hạn dùng nội bộ khi xuất
        $query = DB::table('chemical_imports')
            ->leftJoin('chemical_categories', 'chemical_imports.category_id', '=', 'chemical_categories.id');

        return DepartmentChemical::join($query, $departmentId, 'chemical_imports.category_id')
            ->select('chemical_imports.*', DepartmentChemical::shelfLifeColumn())
            ->where('chemical_imports.id', $importId)
            ->where('chemical_imports.department_id', $departmentId)
            ->where('chemical_imports.status_id', 1)
            ->first();
    }

    /**
     * Kiểm tra phiếu nhập được chọn và số lượng xuất.
     *
     * @param  int|null  $ignoreExportId  phiếu xuất đang sửa, không tính vào tồn
     * @param  int|null  $currentImportId  phiếu nhập bản ghi đang giữ; giữ nguyên phiếu này
     *                                     thì không xét lại điều kiện hạn dùng / còn tồn
     */
    private function checkImport($validator, Request $request, $import, ?int $ignoreExportId = null, ?int $currentImportId = null): void
    {
        $validator->after(function ($validator) use ($request, $import, $ignoreExportId, $currentImportId) {
            if (! $import) {
                $validator->errors()->add('import_id', 'Phiếu nhập được chọn không tồn tại hoặc đã bị khoá.');

                return;
            }

            $remaining = $this->remaining($import, $ignoreExportId);

            // Chỉ chặn khi người dùng CHỌN một phiếu nhập khác với phiếu bản ghi đang giữ
            if ((int) $import->id !== (int) $currentImportId) {
                if ($import->expired_date && \Carbon\Carbon::parse($import->expired_date)->startOfDay()->lt(now()->startOfDay())) {
                    $validator->errors()->add(
                        'import_id',
                        'Phiếu nhập '.$import->code.' đã hết hạn sử dụng ngày '.\Carbon\Carbon::parse($import->expired_date)->format('d/m/Y').', không được xuất ra sử dụng.'
                    );

                    return;
                }

                if ($remaining <= self::EPSILON) {
                    $validator->errors()->add('import_id', 'Phiếu nhập '.$import->code.' đã hết tồn, vui lòng chọn phiếu khác.');

                    return;
                }

                // Hoá chất có khai báo hạn dùng mặc định thì phải xác định hạn dùng nội bộ trước khi dùng.
                //
                // Ngoại lệ: CHUYỂN NGUYÊN cả lô. Hạn dùng nội bộ là hạn sau khi mở lô, mà chuyển
                // nguyên thì lô chưa bị mở - phòng nhận mới là bên mở nên bên đó xác định. Chuyển
                // lẻ thì ngược lại: phải mở lô ra mới cân chia được nên hạn nội bộ phải có sẵn.
                $fullTransfer = $request->type === self::TYPE_TRANSFER
                    && is_numeric($request->amount)
                    && $this->isFullTransfer($import, $request->amount, $ignoreExportId);

                if ((int) ($import->shelf_life_months ?? 0) > 0 && ! $import->internal_expired_date && ! $fullTransfer) {
                    $validator->errors()->add(
                        'import_id',
                        'Phiếu nhập '.$import->code.' chưa xác định hạn dùng nội bộ nên chưa được sử dụng. '
                        .'Vào màn hình Tồn Kho Hoá Chất, tab "Chưa Xác Định Hạn Nội Bộ" để xác định trước. '
                        .'(Chuyển nguyên cả lô thì không cần, phòng nhận sẽ tự xác định.)'
                    );

                    return;
                }
            }

            if (! is_numeric($request->amount)) {
                return;
            }

            // Chuyển kho không được vượt tồn: hàng chuyển đi thành tồn của phòng nhận,
            // cho vượt là tự sinh thêm hàng. Phần 5% chỉ dành cho hao hụt cân đong.
            if ($request->type === self::TYPE_TRANSFER) {
                if ((float) $request->amount > $remaining + self::EPSILON) {
                    $validator->errors()->add(
                        'amount',
                        'Phiếu nhập '.$import->code.' chỉ còn '.$this->number($remaining)
                        .'. Chuyển kho không được vượt tồn, khác với Sử dụng / Huỷ bỏ.'
                    );
                }

                return;
            }

            $limit = $this->maxIssuable($remaining, $import);

            if ((float) $request->amount > $limit + self::EPSILON) {
                // Lô lẻ không có phần vượt, thông báo phải nói đúng lý do
                $validator->errors()->add(
                    'amount',
                    empty($import->is_partial_lot)
                        ? 'Phiếu nhập '.$import->code.' còn '.$this->number($remaining).'. Được xuất vượt tối đa '
                            .(int) round(self::OVER_ISSUE_RATIO * 100).'%, tức không quá '.$this->number($limit).'.'
                        : 'Phiếu nhập '.$import->code.' chỉ còn '.$this->number($remaining)
                            .'. Đây là lô nhận lẻ từ phòng ban khác nên không được xuất vượt lượng đã nhận.'
                );
            }
        });
    }

    private function departmentId(): int
    {
        return (int) (session('user')['selected_department_id'] ?? 0);
    }

    private function actor(): string
    {
        return session('user')['fullName'] ?? 'NA';
    }

    private function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 4, '.', ','), '0'), '.');
    }

    private function rules(int $departmentId): array
    {
        return [
            'import_id' => ['required', 'exists:imports,id'],
            'amount' => ['required', 'numeric', 'min:0.0001'],
            'type' => ['required', 'in:'.implode(',', array_keys(self::TYPES))],
            'exported_date' => ['required', 'date'],
            // Chỉ phiếu chuyển kho mới cần phòng nhận, và phải khác phòng đang đứng
            'to_department_id' => [
                'exclude_unless:type,'.self::TYPE_TRANSFER,
                'required',
                Rule::exists('deparments', 'id')->where('active', 1),
                Rule::notIn([$departmentId]),
            ],
            'purpose' => ['nullable', 'max:500'],
            // Căn cứ loại bỏ (Số Phiếu KN, OOS, BCSL), in vào hồ sơ xin quyết định huỷ
            'test_report_no' => ['nullable', 'max:100'],
            // Chỉ ghi vào lịch sử điều chỉnh, không lưu thành cột của exports
            'adjust_reason' => ['nullable', 'max:500'],
            // Người kiểm tra phải là user đang hoạt động của chính phòng ban này
            'checked_by' => ['nullable', Rule::in($this->checkerOptions($departmentId)->pluck('fullName'))],
        ];
    }

    private function payload(Request $request, $import): array
    {
        return [
            'code' => $import->code,
            'import_id' => (int) $import->id,
            'amount' => (float) $request->amount,
            'type' => $request->type,
            'exported_date' => $request->exported_date,
            // Chỉ phiếu chuyển kho mới giữ phòng nhận, đổi sang loại khác thì xoá đi
            'to_department_id' => $request->type === self::TYPE_TRANSFER && $request->to_department_id
                ? (int) $request->to_department_id
                : null,
            'purpose' => $this->nullIfBlank($request->purpose),
            // Số phiếu KN / OOS / BCSL là căn cứ loại bỏ, chỉ phiếu Huỷ bỏ mới dùng đến
            'test_report_no' => $request->type === self::TYPE_CANCEL
                ? $this->nullIfBlank($request->test_report_no)
                : null,
            'checked_by' => $this->nullIfBlank($request->checked_by),
        ];
    }

    private function nullIfBlank($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function messages(): array
    {
        return [
            'import_id.required' => 'Vui lòng chọn phiếu nhập cần xuất hoá chất.',
            'import_id.exists' => 'Phiếu nhập được chọn không tồn tại.',
            'amount.required' => 'Vui lòng nhập số lượng.',
            'amount.numeric' => 'Số lượng phải là số.',
            'amount.min' => 'Số lượng phải lớn hơn 0.',
            'type.required' => 'Vui lòng chọn loại phiếu.',
            'type.in' => 'Loại phiếu không hợp lệ.',
            'to_department_id.required' => 'Vui lòng chọn phòng ban nhận hoá chất.',
            'to_department_id.exists' => 'Phòng ban nhận không tồn tại hoặc đã ngừng hoạt động.',
            'to_department_id.not_in' => 'Không chuyển hoá chất cho chính phòng ban của mình.',
            'exported_date.required' => 'Vui lòng chọn ngày sử dụng.',
            'exported_date.date' => 'Ngày sử dụng không hợp lệ.',
            'purpose.max' => 'Mục đích sử dụng tối đa 500 ký tự.',
            'test_report_no.max' => 'Số phiếu KN, OOS, BCSL tối đa 100 ký tự.',
            'adjust_reason.max' => 'Lý do điều chỉnh tối đa 500 ký tự.',
            'checked_by.in' => 'Người kiểm tra phải là nhân viên đang hoạt động của phòng ban này.',
        ];
    }
}
