<?php

namespace App\Http\Controllers\Pages\AuditTrail;

use App\Http\Controllers\Controller;
use App\Support\Signer;
use Illuminate\Support\Facades\DB;

class AuditTrialController extends Controller
{
    public function index()
    {
        $startDate = request()->get('startDate', now()->subDays(3)->format('Y-m-d'));
        $endDate = request()->get('endDate', now()->format('Y-m-d'));

        // LEFT JOIN: dòng audit của tài khoản đã bị vô hiệu / đăng nhập sai tên
        // vẫn phải hiển thị, không được biến mất khỏi nhật ký.
        $datas = DB::table('audittriallog')
            ->select('audittriallog.*', 'user_management.fullName')
            ->leftJoin('user_management', 'audittriallog.userName', '=', 'user_management.userName')
            ->whereDate('audittriallog.created_at', '>=', $startDate)
            ->whereDate('audittriallog.created_at', '<=', $endDate)
            ->orderBy('audittriallog.created_at', 'desc')->get();

        session()->put(['title' => 'Audit Trial Log']);

        return view('pages.auditTrail.list', [
            'datas'     => $datas,
            'startDate' => $startDate,
            'endDate'   => $endDate,
        ]);
    }

    /**
     * Ghi một dòng Audit Trail. Bảng chỉ ghi thêm - không sửa, không xoá.
     *
     * @param  string|null  $actor  Ghi đè người thực hiện (dùng cho các sự kiện
     *                              trước khi có session: đăng nhập sai, khoá tài khoản).
     */
    public static function log($action, $table, $recordId, $old = null, $new = null, ?string $actor = null)
    {
        DB::table('audittriallog')->insert([
            'userName'              => $actor ?? Signer::userName(),
            'action'               => $action,
            'table_Audit'          => $table,
            'record_Id_AuditTrial' => $recordId,
            'old_values'           => $old,
            'new_values'           => $new,
            'ip_address'           => request()->ip(),
            'created_at'           => now(),
        ]);
    }
}
