<?php

namespace App\Http\Controllers\Pages\MaterData;

use App\Http\Controllers\Controller;
use App\Support\InitialData\InitialDataImporter;
use App\Support\InitialData\InitialDataSchema;
use App\Support\InitialData\InitialDataTemplate;
use App\Support\Signer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * KHỞI TẠO DỮ LIỆU BAN ĐẦU
 *
 * Tải file Excel mẫu (nhiều sheet liên kết) cho khách hàng điền dữ liệu gốc, danh mục,
 * định khu và tồn đầu kỳ; sau đó nạp lại file đó vào DB. Toàn bộ luật nằm ở
 * App\Support\InitialData\* - controller chỉ nhận file và trả kết quả.
 *
 * Chỉ tài khoản Admin được dùng: thao tác ghi hàng loạt vào gần như mọi bảng nghiệp vụ.
 */
class InitialDataController extends Controller
{
    private const FILE_NAME = 'Mau_Khoi_Tao_Du_Lieu_WMS.xlsx';

    public function index()
    {
        $this->authorizeAdmin();

        session()->put(['title' => 'DỮ LIỆU GỐC - KHỞI TẠO DỮ LIỆU BAN ĐẦU']);

        return view('pages.materData.InitialData.list', [
            'report' => session('initialDataReport'),
            'departments' => DB::table('deparments')->where('isActive', 1)->orderBy('shortName')->get(['id', 'shortName', 'name']),
        ]);
    }

    /**
     * Sinh file mẫu mới nhất theo dữ liệu đang có. Chọn phòng ban = file riêng của phòng đó
     * (gửi song song cho nhiều phòng điền, nạp về lần lượt); chọn module = file chỉ thu thập
     * Vật tư / Hoá chất / Chất chuẩn; chọn giai đoạn = file chỉ thu thập dữ liệu gốc / danh mục /
     * tồn đầu kỳ.
     */
    public function template(Request $request)
    {
        $this->authorizeAdmin();

        $department = $request->filled('department_id')
            ? DB::table('deparments')->where('id', $request->integer('department_id'))->where('isActive', 1)->first(['id', 'shortName'])
            : null;
        abort_if($request->filled('department_id') && ! $department, 404, 'Không tìm thấy phòng ban.');

        $module = $request->filled('module') ? (string) $request->input('module') : null;
        abort_if($module !== null && ! isset(InitialDataSchema::MODULES[$module]), 404, 'Không có module này.');

        $stage = $request->filled('stage') ? $request->integer('stage') : null;
        abort_if($stage !== null && ! isset(InitialDataSchema::STAGES[$stage]), 404, 'Không có giai đoạn này.');

        // Giai đoạn Tổ chức / Người dùng: một file chung cho mọi phòng ban, không chia phòng / module
        if (in_array($stage, InitialDataSchema::SHARED_STAGES, true)) {
            $department = null;
            $module = null;
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

    /** Kiểm tra (mode = check, không ghi DB) hoặc nạp thật (mode = import). */
    public function process(Request $request)
    {
        $this->authorizeAdmin();

        $validator = Validator::make($request->all(), [
            'excel_file' => ['required', 'file', 'mimes:xlsx', 'max:51200'],
            'mode' => ['required', 'in:check,import'],
            'password' => ['nullable', 'string', 'max:255'],
        ], [
            'excel_file.required' => 'Vui lòng chọn file Excel khởi tạo dữ liệu.',
            'excel_file.mimes' => 'File phải là Excel dạng .xlsx (file mẫu tải từ hệ thống).',
            'excel_file.max' => 'File tối đa 50MB.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'initialDataErrors');
        }

        $commit = $request->mode === 'import';
        $fileName = $request->file('excel_file')->getClientOriginalName();

        $report = (new InitialDataImporter(Signer::actor(), Signer::userName(), $request->input('password')))
            ->run($request->file('excel_file')->getRealPath(), $commit, $fileName);

        $report['mode'] = $request->mode;
        $report['file_name'] = $fileName;
        $report['ran_at'] = now()->format('d/m/Y H:i');

        if ($report['committed']) {
            $message = 'Đã nạp file "'.$fileName.'" vào hệ thống.';
        } elseif ($report['error_total']) {
            $message = 'File còn '.$report['error_total'].' lỗi, chưa có dữ liệu nào được ghi. Sửa lỗi theo danh sách bên dưới rồi nạp lại.';
        } else {
            $message = 'Kiểm tra đạt, file sẵn sàng nạp vào hệ thống.';
        }

        return redirect()->route('pages.materData.initialData.list')
            ->with('initialDataReport', $report)
            ->with($report['error_total'] ? 'error' : 'success', $message);
    }

    private function authorizeAdmin(): void
    {
        abort_unless(user_has_any_role(session('user')['userId'] ?? 0, ['Admin']), 403, 'Chỉ tài khoản Admin được khởi tạo dữ liệu.');
    }
}
