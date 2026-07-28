<?php

namespace App\Http\Controllers\Pages\DocumentStorage;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

/**
 * SỔ XIN CẤP LẠI HỒ SƠ (BMR/BPR)
 *
 * Quy trình:
 *  1. Người xin tự trình bày, ghi thông tin vào sổ sau khi QĐ/P.QĐ PXSX đồng ý  -> pending_pm
 *  2. QĐ/P.QĐ PXSX đồng ý ký tên vào sổ                                          -> pending_qa
 *  3. Chuyển sổ + các trang hồ sơ xin lại đến TP/PP. ĐBCL xem, ký tên và ghi
 *     "đồng ý hay không đồng ý"                                                  -> pending_issue | rejected
 *  4. Sau khi TP/PP. ĐBCL ký duyệt, nhân sự mang sổ + các trang sai đến bộ phận
 *     ban hành để cấp lại trang đã duyệt và gạch bỏ trang sai; người cấp lại
 *     ký tên vào sổ                                                              -> completed
 */
class DocumentReissueController extends Controller
{
    public function index()
    {
        if (!session()->has('user') || !isset(session('user')['selected_department_id'])) {
            return redirect()->route('login')->with('error', 'Phiên làm việc hết hạn, vui lòng đăng nhập lại.');
        }

        try {
            $user           = session('user');
            $selectedDeptId = $user['selected_department_id'];

            // Sổ đi từ phân xưởng sản xuất sang ĐBCL rồi sang bộ phận ban hành, nên hai
            // vai trò cuối phải xem được đơn của MỌI bộ phận - nếu lọc theo bộ phận đang
            // chọn thì TP/PP. ĐBCL (thuộc phòng QA) sẽ không thấy đơn của PXSX để duyệt.
            $seesAllDepartments = user_has_any_role(
                $user['userId'],
                ['QA_Manager', 'Người cho lại hồ sơ']
            );

            $datas = DB::table('document_reissue_requests')
                ->when(!$seesAllDepartments, function ($query) use ($selectedDeptId) {
                    $query->where('document_reissue_requests.department_id', $selectedDeptId);
                })
                ->leftJoin('deparments', 'document_reissue_requests.department_id', '=', 'deparments.id')
                ->select(
                    'document_reissue_requests.*',
                    'deparments.name as department_name',
                    'deparments.shortName as department_short_name'
                )
                ->orderBy('document_reissue_requests.request_date', 'desc')
                ->orderBy('document_reissue_requests.id', 'desc')
                ->get();

            $pendingPmCount    = 0;
            $pendingQaCount    = 0;
            $pendingIssueCount = 0;
            $completedCount    = 0;

            foreach ($datas as $data) {
                switch ($data->status) {
                    case 'pending_pm':
                        $pendingPmCount++;
                        break;
                    case 'pending_qa':
                        $pendingQaCount++;
                        break;
                    case 'pending_issue':
                        $pendingIssueCount++;
                        break;
                    case 'completed':
                        $completedCount++;
                        break;
                }
            }

            // Danh mục sản phẩm chỉ dùng để gợi ý tên BMR/BPR; người dùng vẫn gõ tay được
            // nên không để việc thiếu bảng product_name chặn cả trang.
            $products = Schema::hasTable('product_name')
                ? DB::table('product_name')->where('active', true)->orderBy('name', 'asc')->get()
                : collect();

            session()->put(['title' => 'XIN CẤP LẠI HỒ SƠ']);

            return view('pages.DocumentStorage.Reissue.list', [
                'datas'             => $datas,
                'products'          => $products,
                'pendingPmCount'    => $pendingPmCount,
                'pendingQaCount'    => $pendingQaCount,
                'pendingIssueCount' => $pendingIssueCount,
                'completedCount'    => $completedCount,
            ]);
        } catch (\Exception $e) {
            Log::error("DocumentReissue index error: " . $e->getMessage());
            return "Lỗi Server (500): " . $e->getMessage() . " tại dòng " . $e->getLine();
        }
    }

    /**
     * BƯỚC 1: Người xin ghi thông tin vào sổ.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'request_date' => 'required|date',
            'product_name' => 'required|max:255',
            'pages'        => 'required|max:255',
            'reason'       => 'required',
        ], [
            'request_date.required' => 'Vui lòng chọn ngày.',
            'product_name.required' => 'Tên sản phẩm BMR/BPR là bắt buộc.',
            'pages.required'        => 'Vui lòng ghi số trang cần xin lại.',
            'reason.required'       => 'Vui lòng ghi lý do xin lại.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        $user  = session('user');
        $token = trim((string) $request->input('submit_token'));

        // Chống double-submit: mỗi lần mở form chỉ ghi được đúng một dòng, dù người
        // dùng bấm "Ghi sổ" nhiều lần khi trang đang tải chậm.
        if ($token !== '') {
            $existing = DB::table('document_reissue_requests')->where('submit_token', $token)->first();
            if ($existing) {
                return redirect()->back()->with('success', 'Đề nghị này đã được ghi sổ (' . $existing->code . ').');
            }
        }

        $id       = null;
        $attempts = 0;

        // generateCode() đọc rồi mới ghi nên hai request song song có thể sinh trùng mã;
        // unique index sẽ chặn và ta sinh lại mã mới.
        while (true) {
            $attempts++;

            try {
                $id = DB::table('document_reissue_requests')->insertGetId([
                    'code'              => $this->generateCode(),
                    'submit_token'      => $token !== '' ? $token : null,
                    'department_id'     => $user['selected_department_id'],
                    'request_date'      => $request->request_date,
                    'product_name'      => $request->product_name,
                    'process_no'        => $request->process_no,
                    'edition'           => $request->edition,
                    'pages'             => $request->pages,
                    'reason'            => $request->reason,
                    'capa'              => $request->capa,
                    'status'            => 'pending_pm',
                    'requester_user_id' => $user['userId'],
                    'requester_name'    => $user['fullName'],
                    'requested_date'    => $request->request_date,
                    'note'              => $request->note,
                    'created_by'        => $user['fullName'],
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);

                break;
            } catch (UniqueConstraintViolationException $e) {
                // Request song song đã ghi trước bằng đúng token này -> không tạo dòng thứ hai.
                if ($token !== '') {
                    $existing = DB::table('document_reissue_requests')->where('submit_token', $token)->first();
                    if ($existing) {
                        return redirect()->back()->with('success', 'Đề nghị này đã được ghi sổ (' . $existing->code . ').');
                    }
                }

                if ($attempts >= 3) {
                    Log::error("DocumentReissue store error: " . $e->getMessage());
                    return redirect()->back()->with('error', 'Lỗi khi ghi sổ: ' . $e->getMessage());
                }
            } catch (\Exception $e) {
                Log::error("DocumentReissue store error: " . $e->getMessage());
                return redirect()->back()->with('error', 'Lỗi khi ghi sổ: ' . $e->getMessage());
            }
        }

        AuditTrialController::log(
            'Create',
            'document_reissue_requests',
            $id,
            'NA',
            'Đề nghị cấp lại hồ sơ: ' . $request->product_name . ' - trang ' . $request->pages
        );

        return redirect()->back()->with('success', 'Đã ghi sổ đề nghị cấp lại hồ sơ, chờ QĐ/P.QĐ PXSX ký duyệt!');
    }

    /**
     * Sửa nội dung đề nghị khi chưa có ai ký duyệt.
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'           => 'required',
            'request_date' => 'required|date',
            'product_name' => 'required|max:255',
            'pages'        => 'required|max:255',
            'reason'       => 'required',
        ], [
            'request_date.required' => 'Vui lòng chọn ngày.',
            'product_name.required' => 'Tên sản phẩm BMR/BPR là bắt buộc.',
            'pages.required'        => 'Vui lòng ghi số trang cần xin lại.',
            'reason.required'       => 'Vui lòng ghi lý do xin lại.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        $user = session('user');
        $row  = DB::table('document_reissue_requests')->where('id', $request->id)->first();

        if (!$row) {
            return redirect()->back()->with('error', 'Không tìm thấy đề nghị cấp lại hồ sơ.');
        }

        if ($row->status !== 'pending_pm') {
            return redirect()->back()->with('error', 'Đề nghị đã được ký duyệt, không thể sửa nội dung.');
        }

        DB::table('document_reissue_requests')->where('id', $row->id)->update([
            'request_date'   => $request->request_date,
            'product_name'   => $request->product_name,
            'process_no'     => $request->process_no,
            'edition'        => $request->edition,
            'pages'          => $request->pages,
            'reason'         => $request->reason,
            'capa'           => $request->capa,
            'requested_date' => $request->request_date,
            'note'           => $request->note,
            'updated_by'     => $user['fullName'],
            'updated_at'     => now(),
        ]);

        AuditTrialController::log(
            'Update',
            'document_reissue_requests',
            $row->id,
            $row->product_name . ' - trang ' . $row->pages,
            $request->product_name . ' - trang ' . $request->pages
        );

        return redirect()->back()->with('success', 'Đã cập nhật nội dung đề nghị!');
    }

    /**
     * BƯỚC 2: QĐ/P.QĐ PXSX đồng ý và ký tên vào sổ.
     */
    public function pmSign(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'pmErrors')->withInput();
        }

        $user = session('user');
        $row  = DB::table('document_reissue_requests')->where('id', $request->id)->first();

        if (!$row) {
            return redirect()->back()->with('error', 'Không tìm thấy đề nghị cấp lại hồ sơ.');
        }

        if ($row->status !== 'pending_pm') {
            return redirect()->back()->with('error', 'Đề nghị này không còn ở bước chờ QĐ/P.QĐ PXSX ký duyệt.');
        }

        DB::table('document_reissue_requests')->where('id', $row->id)->update([
            'status'         => 'pending_qa',
            'pm_user_id'     => $user['userId'],
            'pm_name'        => $user['fullName'],
            // Ngày ký lấy từ server tại thời điểm ký, không nhận từ form để không sửa được.
            'pm_signed_date' => now()->toDateString(),
            'pm_note'        => $request->pm_note,
            'updated_by'     => $user['fullName'],
            'updated_at'     => now(),
        ]);

        AuditTrialController::log(
            'Update',
            'document_reissue_requests',
            $row->id,
            'pending_pm',
            'pending_qa - QĐ/P.QĐ PXSX ký duyệt: ' . $user['fullName']
        );

        return redirect()->back()->with('success', 'Đã ký duyệt! Chuyển sổ và các trang hồ sơ xin lại đến TP/PP. ĐBCL.');
    }

    /**
     * BƯỚC 3: TP/PP. ĐBCL xem, ký tên và ghi "đồng ý / không đồng ý".
     */
    public function qaReview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'          => 'required',
            'qa_decision' => 'required|in:agree,disagree',
        ], [
            'qa_decision.required' => 'Vui lòng chọn đồng ý hoặc không đồng ý.',
            'qa_decision.in'       => 'Ý kiến không hợp lệ.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'qaErrors')->withInput();
        }

        $user = session('user');
        $row  = DB::table('document_reissue_requests')->where('id', $request->id)->first();

        if (!$row) {
            return redirect()->back()->with('error', 'Không tìm thấy đề nghị cấp lại hồ sơ.');
        }

        if ($row->status !== 'pending_qa') {
            return redirect()->back()->with('error', 'Đề nghị này chưa được QĐ/P.QĐ PXSX ký duyệt hoặc đã được xử lý.');
        }

        $agreed = $request->qa_decision === 'agree';

        DB::table('document_reissue_requests')->where('id', $row->id)->update([
            'status'         => $agreed ? 'pending_issue' : 'rejected',
            'qa_user_id'     => $user['userId'],
            'qa_name'        => $user['fullName'],
            // Ngày ký lấy từ server tại thời điểm ký, không nhận từ form để không sửa được.
            'qa_signed_date' => now()->toDateString(),
            'qa_decision'    => $request->qa_decision,
            'qa_opinion'     => $request->qa_opinion,
            'updated_by'     => $user['fullName'],
            'updated_at'     => now(),
        ]);

        AuditTrialController::log(
            'Update',
            'document_reissue_requests',
            $row->id,
            'pending_qa',
            ($agreed ? 'pending_issue - ĐBCL đồng ý' : 'rejected - ĐBCL không đồng ý') . ': ' . $user['fullName']
        );

        return redirect()->back()->with(
            'success',
            $agreed
                ? 'TP/PP. ĐBCL đã đồng ý! Mang sổ + các trang hồ sơ sai đến bộ phận ban hành để cấp lại.'
                : 'Đã ghi nhận ý kiến KHÔNG ĐỒNG Ý của TP/PP. ĐBCL.'
        );
    }

    /**
     * BƯỚC 4: Bộ phận ban hành cấp lại các trang đã duyệt, gạch bỏ trang sai và ký tên vào sổ.
     */
    public function issue(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'           => 'required',
            'issued_pages' => 'required|max:255',
        ], [
            'issued_pages.required' => 'Vui lòng ghi các trang đã cấp lại.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'issueErrors')->withInput();
        }

        $user = session('user');
        $row  = DB::table('document_reissue_requests')->where('id', $request->id)->first();

        if (!$row) {
            return redirect()->back()->with('error', 'Không tìm thấy đề nghị cấp lại hồ sơ.');
        }

        if ($row->status !== 'pending_issue') {
            return redirect()->back()->with('error', 'Chỉ cấp lại hồ sơ sau khi TP/PP. ĐBCL đã ký duyệt đồng ý.');
        }

        // Gạch bỏ trang sai là thao tác bắt buộc trên hồ sơ giấy để tránh nhầm lẫn.
        if (!$request->boolean('wrong_pages_voided')) {
            return redirect()->back()->with('error', 'Phải xác nhận đã gạch bỏ các trang hồ sơ sai trước khi cấp lại.');
        }

        DB::table('document_reissue_requests')->where('id', $row->id)->update([
            'status'             => 'completed',
            'issuer_user_id'     => $user['userId'],
            'issuer_name'        => $user['fullName'],
            // Ngày cấp lại lấy từ server tại thời điểm ký, không nhận từ form để không sửa được.
            'issued_date'        => now()->toDateString(),
            'issued_pages'       => $request->issued_pages,
            'wrong_pages_voided' => true,
            'issue_note'         => $request->issue_note,
            'updated_by'         => $user['fullName'],
            'updated_at'         => now(),
        ]);

        AuditTrialController::log(
            'Update',
            'document_reissue_requests',
            $row->id,
            'pending_issue',
            'completed - Cấp lại trang ' . $request->issued_pages . ' bởi ' . $user['fullName']
        );

        return redirect()->back()->with('success', 'Đã cấp lại hồ sơ và ký tên vào sổ!');
    }

    /**
     * Huỷ đề nghị (chỉ khi chưa cấp lại xong).
     */
    public function cancel(Request $request)
    {
        $user = session('user');
        $row  = DB::table('document_reissue_requests')->where('id', $request->id)->first();

        if (!$row) {
            return redirect()->back()->with('error', 'Không tìm thấy đề nghị cấp lại hồ sơ.');
        }

        if ($row->status === 'completed') {
            return redirect()->back()->with('error', 'Hồ sơ đã cấp lại xong, không thể huỷ.');
        }

        DB::table('document_reissue_requests')->where('id', $row->id)->update([
            'status'     => 'cancelled',
            'updated_by' => $user['fullName'],
            'updated_at' => now(),
        ]);

        AuditTrialController::log('Update', 'document_reissue_requests', $row->id, $row->status, 'cancelled');

        return redirect()->back()->with('success', 'Đã huỷ đề nghị cấp lại hồ sơ!');
    }

    /**
     * Sinh mã đề nghị dạng CLHS-YYYYMM-0001.
     */
    private function generateCode(): string
    {
        $prefix = 'CLHS-' . date('Ym') . '-';

        $last = DB::table('document_reissue_requests')
            ->where('code', 'like', $prefix . '%')
            ->orderBy('code', 'desc')
            ->value('code');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
