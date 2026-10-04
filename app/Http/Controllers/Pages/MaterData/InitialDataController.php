<?php

namespace App\Http\Controllers\Pages\MaterData;

use App\Http\Controllers\Controller;
use App\Support\InitialData\InitialDataImporter;
use App\Support\InitialData\InitialDataTemplate;
use App\Support\Signer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
        ]);
    }

    /** Sinh file mẫu mới nhất (danh sách vai trò, đơn vị, hoạt chất lấy theo DB hiện tại). */
    public function template()
    {
        $this->authorizeAdmin();

        $path = storage_path('app/initial-data/'.now()->format('YmdHis').'_'.self::FILE_NAME);
        InitialDataTemplate::save($path);

        return response()->download($path, self::FILE_NAME)->deleteFileAfterSend(true);
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
