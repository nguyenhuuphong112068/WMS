<?php

namespace App\Http\Controllers\Pages\MaterData;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\InitialData\InitialDataPreview;
use App\Support\InitialData\InitialDataSchema;
use App\Support\InitialData\InitialDataTemplate;
use App\Support\Signer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * KHỞI TẠO DỮ LIỆU BAN ĐẦU
 *
 * - Admin tải file Excel mẫu (nhiều sheet liên kết) theo giai đoạn / module / phòng ban để gửi các
 *   phòng điền.
 * - Phòng ban (quyền materData_initialData_upload) đính kèm file đã điền. Đính kèm CHỈ lưu file để
 *   Admin xem / tải về - không ghi gì vào bảng nghiệp vụ. Admin tổng hợp, làm sạch dữ liệu rồi
 *   kiểm tra / nạp bằng lệnh `php artisan wms:initial-data check|import` (App\Support\InitialData\*).
 */
class InitialDataController extends Controller
{
    private const FILE_NAME = 'Mau_Khoi_Tao_Du_Lieu_WMS.xlsx';

    private const UPLOAD_PERMISSION = 'materData_initialData_upload';

    /** Thư mục lưu file đính kèm (đĩa local, không public) - tải về qua download() có kiểm quyền. */
    private const UPLOAD_DIR = 'initial-data/uploads';

    /** Trạng thái Admin xử lý file đính kèm. */
    public const REVIEW_STATUSES = [
        'pending' => ['label' => 'Chờ xử lý', 'class' => 'warning'],
        'processed' => ['label' => 'Đã xử lý', 'class' => 'success'],
        'returned' => ['label' => 'Trả lại', 'class' => 'danger'],
    ];

    public function index()
    {
        $this->authorizeView();

        session()->put(['title' => 'DỮ LIỆU GỐC - KHỞI TẠO DỮ LIỆU BAN ĐẦU']);

        $isAdmin = $this->isAdmin();

        $files = DB::table('initial_data_files')
            ->leftJoin('deparments', 'initial_data_files.department_id', '=', 'deparments.id')
            ->select('initial_data_files.*', 'deparments.shortName as department_short', 'deparments.name as department_name')
            // Lấy cả file đã hết hiệu lực (bị thay thế / gỡ) để hiện lịch sử nộp trên từng việc
            // Phòng ban chỉ thấy file của phòng mình + file chính mình đính kèm
            ->when(! $isAdmin, fn ($q) => $q->where(fn ($w) => $w
                ->whereIn('initial_data_files.department_id', $this->uploadDepartments()->pluck('id'))
                ->orWhere('initial_data_files.uploaded_by_user_id', session('user')['userId'])))
            ->orderByDesc('initial_data_files.created_at')
            ->get();

        return view('pages.materData.InitialData.list', [
            'isAdmin' => $isAdmin,
            'departments' => DB::table('deparments')->where('isActive', 1)->orderBy('shortName')->get(['id', 'shortName', 'name']),
            'uploadDepartments' => $this->uploadDepartments(),
            'tracker' => $this->tracker($files, $isAdmin),
        ]);
    }

    /**
     * Bảng việc cần làm theo giai đoạn: mỗi việc = giai đoạn + phòng ban (+ module ở GĐ3 / GĐ4).
     *   GĐ1 : 1 việc của Admin cho toàn hệ thống
     *   GĐ2 : mỗi phòng ban 1 việc (người dùng & tổ của phòng)
     *   GĐ3 / GĐ4 : mỗi phòng có kho × 4 module
     * Trạng thái lấy theo lần nộp mới nhất khớp việc (file đủ module / đủ giai đoạn khớp mọi việc
     * con): Đã xử lý = hoàn thành; Admin đánh dấu "Không áp dụng" thì việc không tính vào tiến độ.
     */
    private function tracker($files, bool $isAdmin): array
    {
        $visibleIds = $this->uploadDepartments()->pluck('id')->all();
        $departments = DB::table('deparments')
            ->leftJoin('companies', 'deparments.company_id', '=', 'companies.id')
            ->where('deparments.isActive', 1)
            ->orderBy('companies.code')->orderBy('deparments.shortName')
            ->select('deparments.id', 'deparments.shortName', 'deparments.name', 'deparments.is_general',
                'deparments.company_id', 'companies.short_name as company_short', 'companies.name as company_name')
            ->get()
            ->filter(fn ($d) => $isAdmin || in_array($d->id, $visibleIds));
        $deadlines = DB::table('initial_data_deadlines')->pluck('deadline', 'stage');
        $skips = DB::table('initial_data_task_skips')->get()
            ->keyBy(fn ($r) => $r->stage.'|'.$r->module.'|'.$r->department_id);

        $task = function (int $stage, ?string $module, ?object $department) use ($files, $skips) {
            $matches = $files->filter(fn ($f) => ($f->stage === null || (int) $f->stage === $stage)
                && ($module === null || $f->module === null || $f->module === $module)
                && ($stage === 1 || (int) $f->department_id === (int) $department?->id))->values();
            // File còn hiệu lực mới nhất quyết định trạng thái; các lần nộp khác là lịch sử
            $latest = $matches->firstWhere('status_id', 1);
            $skip = $skips->get($stage.'|'.$module.'|'.$department?->id);

            $state = match (true) {
                $latest?->review_status === 'processed' => 'done',
                (bool) $skip => 'skipped',
                $latest !== null => $latest->review_status,
                default => 'todo',
            };

            return [
                'stage' => $stage,
                'module' => $module,
                'department' => $department,
                'state' => $state,
                'latest' => $latest,
                'count' => $matches->count(),
                'history' => $matches,
                'skip' => $skip,
            ];
        };

        // Đếm tiến độ: việc "Không áp dụng" không tính
        $progress = function ($tasks) {
            $counted = collect($tasks)->where('state', '!=', 'skipped');

            return [
                'total' => $counted->count(),
                'done' => $counted->where('state', 'done')->count(),
                'waiting' => $counted->where('state', 'pending')->count(),
                'returned' => $counted->where('state', 'returned')->count(),
            ];
        };

        $stages = [];
        foreach (InitialDataSchema::stagesFor($isAdmin) as $stage => $label) {
            // Việc gom theo công ty -> phòng ban, để lọc / so tiến độ từng nhà máy
            $companies = [];
            if ($stage === 1) {
                if ($isAdmin) {
                    $companies['all'] = ['id' => null, 'label' => 'Toàn hệ thống', 'groups' => ['Toàn hệ thống' => [$task(1, null, null)]]];
                }
            } else {
                foreach ($departments as $department) {
                    // GĐ3 / GĐ4 chỉ áp cho phòng có kho (Phòng ban chung không có kho)
                    if ($stage >= 3 && (int) $department->is_general !== 1) {
                        continue;
                    }
                    $modules = in_array($stage, InitialDataSchema::SHARED_STAGES, true) ? [null] : array_keys(InitialDataSchema::MODULES);
                    $key = (string) ($department->company_id ?? 'none');
                    $companies[$key] ??= [
                        'id' => $department->company_id,
                        'label' => $department->company_short ?: 'Chưa gắn công ty',
                        'title' => $department->company_name,
                        'groups' => [],
                    ];
                    $companies[$key]['groups'][$department->shortName.' - '.$department->name] =
                        array_map(fn ($m) => $task($stage, $m, $department), $modules);
                }
            }

            foreach ($companies as &$company) {
                $company += $progress(collect($company['groups'])->flatten(1));
            }
            unset($company);

            $stages[$stage] = [
                'label' => $label,
                'companies' => $companies,
                'deadline' => $deadlines[$stage] ?? null,
                'closed' => $this->deadlinePassed($stage),
            ]
                + $progress(collect($companies)->pluck('groups')->flatMap(fn ($g) => collect($g)->flatten(1)));
        }

        return $stages;
    }

    /** Admin đánh dấu / bỏ đánh dấu "Không áp dụng" cho một việc trên bảng theo dõi. */
    public function skip(Request $request)
    {
        $this->authorizeView();

        $stage = $request->integer('stage');
        abort_unless(isset(InitialDataSchema::stagesFor($this->isAdmin())[$stage]), 404, 'Không có giai đoạn này.');
        $module = $request->input('module') ?: null;
        $departmentId = $request->filled('department_id') ? $request->integer('department_id') : null;
        // Phòng ban tự đánh dấu việc của phòng mình (module / giai đoạn phòng không dùng)
        abort_unless($this->isAdmin() || ($departmentId && $this->uploadDepartments()->pluck('id')->contains($departmentId)),
            403, 'Chỉ đánh dấu được việc của phòng mình.');

        $query = DB::table('initial_data_task_skips')->where('stage', $stage)
            ->where(fn ($q) => $module === null ? $q->whereNull('module') : $q->where('module', $module))
            ->where(fn ($q) => $departmentId === null ? $q->whereNull('department_id') : $q->where('department_id', $departmentId));

        $label = 'Giai đoạn '.$stage.($module ? ' · '.InitialDataSchema::MODULES[$module] : '')
            .($departmentId ? ' · '.DB::table('deparments')->where('id', $departmentId)->value('shortName') : '');

        if ($query->exists()) {
            $query->delete();
            AuditTrialController::log('Áp dụng lại việc khởi tạo dữ liệu', 'initial_data_task_skips', 'NA', $label, null);

            return redirect()->back()->with('success', 'Đã áp dụng lại việc "'.$label.'".');
        }

        DB::table('initial_data_task_skips')->insert([
            'stage' => $stage,
            'module' => $module,
            'department_id' => $departmentId,
            'created_by' => Signer::actor(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        AuditTrialController::log('Bỏ qua việc khởi tạo dữ liệu', 'initial_data_task_skips', 'NA', null, $label);

        return redirect()->back()->with('success', 'Đã đánh dấu "'.$label.'" không áp dụng.');
    }

    /**
     * Sinh file mẫu mới nhất theo dữ liệu đang có. Chọn phòng ban = file riêng của phòng đó
     * (gửi song song cho nhiều phòng điền, nạp về lần lượt); chọn module = file chỉ thu thập
     * Vật tư / Hoá chất / Chất chuẩn; chọn giai đoạn = file chỉ thu thập dữ liệu gốc / danh mục /
     * tồn đầu kỳ.
     */
    public function template(Request $request)
    {
        $this->authorizeView();
        $isAdmin = $this->isAdmin();

        $department = $request->filled('department_id')
            ? DB::table('deparments')->where('id', $request->integer('department_id'))->where('isActive', 1)->first(['id', 'shortName'])
            : null;
        abort_if($request->filled('department_id') && ! $department, 404, 'Không tìm thấy phòng ban.');

        $module = $request->filled('module') ? (string) $request->input('module') : null;
        abort_if($module !== null && ! isset(InitialDataSchema::MODULES[$module]), 404, 'Không có module này.');

        $stage = $request->filled('stage') ? $request->integer('stage') : null;
        abort_if($stage !== null && ! isset(InitialDataSchema::stagesFor($isAdmin)[$stage]), 404, 'Không có giai đoạn này.');

        // Giai đoạn Tổ chức / Người dùng: một file chung cho mọi phòng ban, không chia phòng / module
        $shared = in_array($stage, InitialDataSchema::SHARED_STAGES, true);
        if ($shared) {
            $module = null;
        }
        // GĐ1 Tổ chức là file toàn hệ thống; GĐ2 file phòng ban được điền sẵn tổ + người dùng của phòng
        if ($stage === 1) {
            $department = null;
        }

        // Phòng ban chỉ tải file của một giai đoạn 2 - 4; GĐ3 / GĐ4 phải chọn một module và phòng mình
        if (! $isAdmin) {
            abort_if($stage === null || $stage === 1, 403, 'Chỉ Admin được tải file mẫu giai đoạn 1 hoặc cả 4 giai đoạn.');
            abort_if($department === null || (! $shared && $module === null), 403, 'Chọn một module và phòng ban để tải file mẫu.');
            abort_if($department && ! $this->uploadDepartments()->pluck('id')->contains($department->id), 403, 'Bạn không thuộc phòng ban này.');
        }

        // VD: Mau_Khoi_Tao_Du_Lieu_WMS_GD3_HoaChat_QC1.xlsx
        $suffix = collect([
            $stage ? 'GD'.$stage : null,
            $module ? Str::studly(Str::ascii(InitialDataSchema::MODULES[$module])) : null,
            $department ? preg_replace('/[^A-Za-z0-9_-]+/', '-', $department->shortName) : null,
        ])->filter()->map(fn ($part) => '_'.$part)->implode('');
        $fileName = str_replace('.xlsx', $suffix.'.xlsx', self::FILE_NAME);

        $path = storage_path('app/initial-data/'.now()->format('YmdHis').'_'.$fileName);
        InitialDataTemplate::save($path, $department?->id, $module, $stage);

        return response()->download($path, $fileName)->deleteFileAfterSend(true);
    }

    /**
     * Đính kèm file đã điền. File mẫu WMS mang sẵn giai đoạn / module / phòng ban trong sheet ẩn:
     * lựa chọn trên form phải khớp, tránh nộp nhầm file của giai đoạn / phòng khác.
     */
    public function upload(Request $request)
    {
        $this->authorizeView();

        $validator = Validator::make($request->all(), [
            'stage' => ['nullable', 'integer', 'in:'.implode(',', array_keys(InitialDataSchema::stagesFor($this->isAdmin())))],
            'module' => ['nullable', 'in:'.implode(',', array_keys(InitialDataSchema::MODULES))],
            'department_id' => ['required', 'integer', 'in:'.$this->uploadDepartments()->pluck('id')->implode(',')],
            'excel_file' => ['required', 'file', 'mimes:xlsx', 'max:51200'],
            'note' => ['nullable', 'string', 'max:1000'],
        ], [
            'department_id.required' => 'Vui lòng chọn phòng ban nộp file.',
            'department_id.in' => 'Bạn không thuộc phòng ban đã chọn.',
            'excel_file.required' => 'Vui lòng chọn file Excel đã điền.',
            'excel_file.mimes' => 'File phải là Excel dạng .xlsx (file mẫu tải từ hệ thống).',
            'excel_file.max' => 'File tối đa 50MB.',
            'note.max' => 'Ghi chú tối đa 1000 ký tự.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'uploadErrors')->withInput();
        }

        $file = $request->file('excel_file');
        $stage = $request->filled('stage') ? $request->integer('stage') : null;
        $module = $request->input('module') ?: null;
        $departmentId = $request->integer('department_id');
        $department = DB::table('deparments')->where('id', $departmentId)->first(['id', 'shortName']);

        if (in_array($stage, InitialDataSchema::SHARED_STAGES, true)) {
            $module = null;
        }

        if ($stage && $this->deadlinePassed($stage)) {
            return redirect()->back()->withErrors(['excel_file' => 'Giai đoạn '.$stage.' đã hết hạn nộp ('
                .date('d/m/Y', strtotime($this->stageDeadline($stage))).'), không đính kèm được nữa. Liên hệ Admin nếu cần gia hạn.'], 'uploadErrors')->withInput();
        }

        $meta = InitialDataSchema::fileMeta($file->getRealPath());
        $mismatch = $meta ? $this->metaMismatch($meta, $stage, $module, $department->shortName) : null;
        if ($mismatch) {
            return redirect()->back()->withErrors(['excel_file' => $mismatch], 'uploadErrors')->withInput();
        }

        $hash = hash_file('sha256', $file->getRealPath());
        $same = DB::table('initial_data_files')->where('status_id', 1)->where('file_hash', $hash)->first(['submission_no', 'created_by', 'created_at']);
        if ($same) {
            return redirect()->back()->withErrors(['excel_file' => 'File này đã được đính kèm (lần nộp '.$same->submission_no.', '
                .$same->created_by.' lúc '.date('d/m/Y H:i', strtotime($same->created_at)).').'], 'uploadErrors')->withInput();
        }

        $submissionNo = DB::table('initial_data_files')
            ->where('stage', $stage)->where('module', $module)->where('department_id', $departmentId)
            ->count() + 1;

        $storedPath = $file->storeAs(self::UPLOAD_DIR.'/'.now()->format('Y/m'), Str::uuid().'.xlsx', 'local');
        $actor = Signer::actor();

        $id = DB::table('initial_data_files')->insertGetId([
            'stage' => $stage,
            'module' => $module,
            'department_id' => $departmentId,
            'submission_no' => $submissionNo,
            'original_name' => $file->getClientOriginalName(),
            'stored_path' => $storedPath,
            'file_size' => $file->getSize(),
            'file_hash' => $hash,
            'template_version' => $meta['version'] ?? null,
            'note' => $request->input('note'),
            'review_status' => 'pending',
            'uploaded_by_user_id' => session('user')['userId'],
            'status_id' => 1,
            'created_by' => $actor,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // File trước của cùng việc tự hết hiệu lực, trỏ sang file thay thế (vẫn xem được ở lịch sử nộp)
        $replaced = DB::table('initial_data_files')
            ->where('id', '!=', $id)->where('status_id', 1)
            ->where(fn ($q) => $stage === null ? $q->whereNull('stage') : $q->where('stage', $stage))
            ->where(fn ($q) => $module === null ? $q->whereNull('module') : $q->where('module', $module))
            ->where('department_id', $departmentId)
            ->update(['status_id' => 0, 'replaced_by_id' => $id, 'updated_by' => $actor, 'updated_at' => now()]);

        AuditTrialController::log('Đính kèm file khởi tạo dữ liệu', 'initial_data_files', $id, null,
            $this->describe($stage, $module, $department->shortName).' - lần nộp '.$submissionNo.' - "'.$file->getClientOriginalName().'"');

        return redirect()->back()->with('success', 'Đã đính kèm file "'.$file->getClientOriginalName().'" (lần nộp '.$submissionNo.').'
            .($replaced ? ' File nộp trước của việc này đã được thay thế.' : '').' Admin sẽ tổng hợp và nạp lên hệ thống.');
    }

    public function download(int $id)
    {
        $this->authorizeView();

        $row = $this->visibleFile($id, true);
        abort_unless($row && Storage::disk('local')->exists($row->stored_path), 404, 'Không tìm thấy file.');

        return response()->download(Storage::disk('local')->path($row->stored_path), $row->original_name);
    }

    /** Xem nội dung file đã đính kèm ngay trên web (JSON theo từng sheet), không cần tải về. */
    public function preview(int $id)
    {
        $this->authorizeView();

        $row = $this->visibleFile($id, true);
        abort_unless($row && Storage::disk('local')->exists($row->stored_path), 404, 'Không tìm thấy file.');

        $data = InitialDataPreview::read(Storage::disk('local')->path($row->stored_path));
        abort_unless($data, 422, 'Không đọc được file Excel này, hãy tải về để mở bằng Excel.');

        return response()->json($data);
    }

    /** Admin cập nhật trạng thái xử lý file: Chờ xử lý / Đã xử lý / Trả lại (kèm ghi chú). */
    public function review(Request $request)
    {
        $this->authorizeAdmin();

        $validator = Validator::make($request->all(), [
            'id' => ['required', 'integer'],
            'review_status' => ['required', 'in:'.implode(',', array_keys(self::REVIEW_STATUSES))],
            'review_note' => ['nullable', 'string', 'max:1000', 'required_if:review_status,returned'],
        ], [
            'review_note.required_if' => 'Trả lại file thì phải ghi lý do để phòng ban sửa.',
            'review_note.max' => 'Ghi chú tối đa 1000 ký tự.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'reviewErrors')->withInput();
        }

        $row = DB::table('initial_data_files')->where('id', $request->integer('id'))->where('status_id', 1)->first();
        abort_unless($row, 404, 'Không tìm thấy file.');

        DB::table('initial_data_files')->where('id', $row->id)->update([
            'review_status' => $request->input('review_status'),
            'review_note' => $request->input('review_note'),
            'reviewed_by' => Signer::actor(),
            'reviewed_at' => now(),
            'updated_by' => Signer::actor(),
            'updated_at' => now(),
        ]);

        AuditTrialController::log('Xử lý file khởi tạo dữ liệu', 'initial_data_files', $row->id,
            self::REVIEW_STATUSES[$row->review_status]['label'] ?? $row->review_status,
            self::REVIEW_STATUSES[$request->input('review_status')]['label'].($request->input('review_note') ? ' - '.$request->input('review_note') : ''));

        return redirect()->back()->with('success', 'Đã cập nhật trạng thái file "'.$row->original_name.'".');
    }

    /**
     * Admin xoá hẳn một lần nộp (bản ghi + file trên đĩa). Quyền cứng: chỉ role Admin, không qua bảng
     * permissions. Xoá file đang hiệu lực thì lần nộp trước nó (bị nó thay thế) được dùng lại.
     */
    public function destroy(Request $request)
    {
        $this->authorizeAdmin();

        $row = DB::table('initial_data_files')->where('id', $request->integer('id'))->first();
        abort_unless($row, 404, 'Không tìm thấy file.');

        $department = $row->department_id ? DB::table('deparments')->where('id', $row->department_id)->value('shortName') : null;
        $actor = Signer::actor();

        DB::transaction(function () use ($row, $actor) {
            $previous = DB::table('initial_data_files')->where('replaced_by_id', $row->id)
                ->orderByDesc('submission_no')->orderByDesc('id')->first();

            if ($previous) {
                // Lần nộp trước nối sang file thay thế của file bị xoá (nếu có), hoặc hiệu lực lại
                DB::table('initial_data_files')->where('id', $previous->id)->update([
                    'status_id' => (int) $row->status_id === 1 ? 1 : $previous->status_id,
                    'replaced_by_id' => (int) $row->status_id === 1 ? null : $row->replaced_by_id,
                    'updated_by' => $actor,
                    'updated_at' => now(),
                ]);
                DB::table('initial_data_files')->where('replaced_by_id', $row->id)->where('id', '!=', $previous->id)
                    ->update(['replaced_by_id' => $previous->id]);
            }

            DB::table('initial_data_files')->where('id', $row->id)->delete();
        });

        if ($row->stored_path && Storage::disk('local')->exists($row->stored_path)) {
            Storage::disk('local')->delete($row->stored_path);
        }

        AuditTrialController::log('Xoá file khởi tạo dữ liệu', 'initial_data_files', $row->id,
            $this->describe($row->stage, $row->module, $department ?? 'Toàn hệ thống').' - lần nộp '.$row->submission_no.' - "'.$row->original_name.'"', 'Đã xoá');

        return redirect()->back()->with('success', 'Đã xoá file "'.$row->original_name.'" (lần nộp '.$row->submission_no.').');
    }

    /** Admin đặt / đổi / xoá hạn nộp của giai đoạn 2 - 4 (để trống = không giới hạn). */
    public function deadline(Request $request)
    {
        $this->authorizeAdmin();

        $validator = Validator::make($request->all(), [
            'stage' => ['required', 'integer', 'in:2,3,4'],
            'deadline' => ['nullable', 'date_format:Y-m-d'],
        ], [
            'deadline.date_format' => 'Hạn nộp không hợp lệ.',
        ]);
        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->errors()->first());
        }

        $stage = $request->integer('stage');
        $old = $this->stageDeadline($stage);
        $new = $request->input('deadline') ?: null;

        if ($new === null) {
            DB::table('initial_data_deadlines')->where('stage', $stage)->delete();
        } else {
            DB::table('initial_data_deadlines')->updateOrInsert(['stage' => $stage], [
                'deadline' => $new,
                'updated_by' => Signer::actor(),
                'updated_at' => now(),
            ] + ($old ? [] : ['created_by' => Signer::actor(), 'created_at' => now()]));
        }

        $format = fn ($d) => $d ? date('d/m/Y', strtotime($d)) : 'không giới hạn';
        AuditTrialController::log('Đặt hạn nộp khởi tạo dữ liệu', 'initial_data_deadlines', $stage, $format($old), $format($new));

        return redirect()->back()->with('success', 'Hạn nộp giai đoạn '.$stage.': '.$format($new).'.');
    }

    private function stageDeadline(int $stage): ?string
    {
        return DB::table('initial_data_deadlines')->where('stage', $stage)->value('deadline');
    }

    /** Đã qua hết ngày hạn nộp của giai đoạn (giai đoạn 1 không có hạn). */
    private function deadlinePassed(int $stage): bool
    {
        $deadline = $stage > 1 ? $this->stageDeadline($stage) : null;

        return $deadline !== null && now()->toDateString() > $deadline;
    }

    /** Nhãn "Giai đoạn 3 · Hoá chất · QC1" dùng cho Audit Trail. */
    private function describe(?int $stage, ?string $module, string $department): string
    {
        return collect([
            $stage ? 'Giai đoạn '.$stage : 'Đủ 4 giai đoạn',
            $module ? InitialDataSchema::MODULES[$module] : 'Cả 4 module',
            $department,
        ])->implode(' · ');
    }

    /** Câu báo lỗi khi thông tin trong file mẫu khác lựa chọn trên form; null = khớp. */
    private function metaMismatch(array $meta, ?int $stage, ?string $module, string $department): ?string
    {
        if (! InitialDataSchema::acceptsVersion($meta['version'], $meta['stage'])) {
            return 'File là mẫu phiên bản cũ ('.$meta['version'].'). Vui lòng tải file mẫu mới nhất và điền lại.';
        }
        if ($meta['stage'] !== $stage) {
            return 'File là mẫu '.($meta['stage'] ? 'giai đoạn '.$meta['stage'].' - '.InitialDataSchema::STAGES[$meta['stage']] : 'đủ 4 giai đoạn')
                .', khác giai đoạn đã chọn.';
        }
        if ($meta['module'] !== $module) {
            return 'File là mẫu '.($meta['module'] ? 'module '.InitialDataSchema::MODULES[$meta['module']] : 'cả 4 module').', khác module đã chọn.';
        }
        if ($meta['department'] !== null && mb_strtolower($meta['department']) !== mb_strtolower($department)) {
            return 'File là mẫu riêng của phòng '.$meta['department'].', khác phòng ban đã chọn ('.$department.').';
        }

        return null;
    }

    /** File người đang đăng nhập được xem; $withInactive = cả file đã bị thay thế / gỡ (tải từ lịch sử nộp). */
    private function visibleFile(int $id, bool $withInactive = false): ?object
    {
        $row = DB::table('initial_data_files')->where('id', $id)
            ->when(! $withInactive, fn ($q) => $q->where('status_id', 1))
            ->first();

        if (! $row || $this->isAdmin()) {
            return $row;
        }

        $allowed = $this->uploadDepartments()->pluck('id')->contains($row->department_id)
            || (int) $row->uploaded_by_user_id === (int) session('user')['userId'];

        return $allowed ? $row : null;
    }

    /** Phòng ban người dùng được nộp file thay mặt: Admin = mọi phòng, còn lại = phòng mình thuộc về. */
    private function uploadDepartments()
    {
        $userId = session('user')['userId'] ?? 0;

        return DB::table('deparments')->where('isActive', 1)->orderBy('shortName')->get(['id', 'shortName', 'name'])
            ->filter(fn ($d) => $this->isAdmin() || user_can_access_department($userId, $d->id))
            ->values();
    }

    private function isAdmin(): bool
    {
        return user_has_any_role(session('user')['userId'] ?? 0, ['Admin']);
    }

    private function authorizeAdmin(): void
    {
        abort_unless($this->isAdmin(), 403, 'Chỉ tài khoản Admin được khởi tạo dữ liệu.');
    }

    private function authorizeView(): void
    {
        abort_unless($this->isAdmin() || user_can(self::UPLOAD_PERMISSION), 403, 'Bạn không có quyền đính kèm file khởi tạo dữ liệu.');
    }
}
