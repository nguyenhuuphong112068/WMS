<?php

namespace App\Http\Controllers\Pages\AuditTrail;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class AuditTrialController extends Controller
{
    public function index()
    {
        $startDate = request()->get('startDate', now()->subDays(3)->format('Y-m-d'));
        $endDate = request()->get('endDate', now()->format('Y-m-d'));

        $datas = DB::table('audittriallog')
            ->select('audittriallog.*', 'user_management.fullName')
            ->join('user_management', 'audittriallog.userName', '=', 'user_management.userName')
            ->whereDate('audittriallog.created_at', '>=', $startDate)
            ->whereDate('audittriallog.created_at', '<=', $endDate)
            ->orderBy('created_at', 'desc')->get();

        session()->put(['title' => 'Audit Trial Log']);

        return view('pages.auditTrail.list', [
            'datas'     => $datas,
            'startDate' => $startDate,
            'endDate'   => $endDate
        ]);
    }

    public static function log($action, $table, $recordId, $old = null, $new = null)
    {

        DB::table('audittriallog')->insert([
            'userName'     =>  session('user')['userName'] ?? 'NA',
            'action'      => $action,
            'table_Audit'       => $table,
            'record_Id_AuditTrial'    => $recordId,
            'old_values'  => $old,
            'new_values'  => $new,
            'ip_address'  => request()->ip(),
            'created_at'  => now(),
        ]);
    }
}
