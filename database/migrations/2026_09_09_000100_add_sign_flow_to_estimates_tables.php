<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * DỰ TRÙ - Chuyển trình ký từ 2 BƯỚC CỐ ĐỊNH sang QUY TRÌNH KÝ ĐỘNG.
 *
 * Thêm cho mỗi bảng chemical_estimates / standard_estimates / material_estimates:
 *   - sign_step_count : số bước ký người lập đã khai
 *   - current_step    : bước đang chờ ký (null khi đã duyệt xong / bị từ chối / nháp)
 *
 * Cột manager_signed_* / director_signed_* GIỮ NGUYÊN (dữ liệu phiếu đã duyệt), không dùng nữa.
 *
 * Data-migrate: sinh các dòng <loại>_estimate_signs role-based cho phiếu đang dở dang để
 * giữ nguyên tiến độ ký (không bắt phòng ban trình ký lại từ đầu).
 *   pending_manager  -> pending_sign, current_step 1, 2 bước chờ ký
 *   pending_director -> pending_sign, current_step 2, bước 1 đã ký, bước 2 chờ ký
 *   approved         -> 2 bước đã ký (lấy từ manager_/director_signed_*)
 *   rejected         -> 2 bước role-based, bước ứng với reject_step cũ = rejected
 *   draft / cancelled-> không sinh bước (người lập khai lại trong modal)
 */
return new class extends Migration
{
    private const TYPES = ['chemical', 'standard', 'material'];

    public function up(): void
    {
        $managerRoles = implode(',', config('estimate.manager_roles'));
        $bodRoles = implode(',', config('estimate.bod_roles'));
        $now = now();

        foreach (self::TYPES as $type) {
            $table = $type.'_estimates';
            $signTable = $type.'_estimate_signs';
            $fk = $type.'_estimate_id';

            Schema::table($table, function (Blueprint $t) {
                if (! Schema::hasColumn($t->getTable(), 'sign_step_count')) {
                    $t->unsignedTinyInteger('sign_step_count')->default(0)->after('submitted_at');
                }
                if (! Schema::hasColumn($t->getTable(), 'current_step')) {
                    $t->unsignedTinyInteger('current_step')->nullable()->after('sign_step_count');
                }
            });

            DB::table($table)->orderBy('id')->each(function ($row) use ($table, $signTable, $fk, $managerRoles, $bodRoles, $now) {
                $status = $row->app_status;

                if (in_array($status, ['draft', 'cancelled'], true)) {
                    return;
                }

                // Đã có bước ký (chạy migration 2 lần) thì bỏ qua
                if (DB::table($signTable)->where($fk, $row->id)->exists()) {
                    return;
                }

                $actor = $row->submitted_by ?: ($row->created_by ?: 'Hệ thống');
                $base = [
                    $fk => $row->id,
                    'active' => 1,
                    'created_by' => $actor,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $step1 = $base + ['step_no' => 1, 'role_names' => $managerRoles, 'status' => 'pending'];
                $step2 = $base + ['step_no' => 2, 'role_names' => $bodRoles, 'status' => 'pending'];

                $listUpdate = ['sign_step_count' => 2, 'current_step' => null, 'updated_at' => $now];

                if ($status === 'pending_manager') {
                    $listUpdate += ['app_status' => 'pending_sign', 'current_step' => 1];
                } elseif ($status === 'pending_director') {
                    $listUpdate += ['app_status' => 'pending_sign', 'current_step' => 2];
                    $step1 = array_merge($step1, [
                        'status' => 'signed',
                        'signed_by' => $row->manager_signed_by,
                        'signed_at' => $row->manager_signed_at,
                    ]);
                } elseif ($status === 'approved') {
                    $step1 = array_merge($step1, [
                        'status' => 'signed',
                        'signed_by' => $row->manager_signed_by,
                        'signed_at' => $row->manager_signed_at,
                    ]);
                    $step2 = array_merge($step2, [
                        'status' => 'signed',
                        'signed_by' => $row->director_signed_by,
                        'signed_at' => $row->director_signed_at,
                    ]);
                } elseif ($status === 'rejected') {
                    if ($row->reject_step === 'director') {
                        $step2 = array_merge($step2, ['status' => 'rejected', 'reject_reason' => $row->reject_reason]);
                        $listUpdate += ['reject_step' => '2'];
                    } else {
                        $step1 = array_merge($step1, ['status' => 'rejected', 'reject_reason' => $row->reject_reason]);
                        $listUpdate += ['reject_step' => '1'];
                    }
                }

                DB::table($signTable)->insert([$step1, $step2]);
                DB::table($table)->where('id', $row->id)->update($listUpdate);
            });
        }
    }

    public function down(): void
    {
        foreach (self::TYPES as $type) {
            Schema::table($type.'_estimates', function (Blueprint $t) {
                foreach (['current_step', 'sign_step_count'] as $col) {
                    if (Schema::hasColumn($t->getTable(), $col)) {
                        $t->dropColumn($col);
                    }
                }
            });
        }
    }
};
