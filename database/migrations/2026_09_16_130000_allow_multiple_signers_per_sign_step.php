<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MỘT BƯỚC KÝ CÓ THỂ GIAO CHO NHIỀU NGƯỜI - AI KÝ TRƯỚC CŨNG ĐƯỢC
 *
 * Trước đây mỗi bước chỉ chỉ định đúng một người ký, người đó nghỉ phép là phiếu đứng
 * luôn. Nay một bước khai được NHIỀU người cùng lúc và chỉ cần MỘT trong số họ ký là bước
 * đó xong, phiếu đi tiếp bước sau.
 *
 * Danh sách người ký của một bước tách ra bảng con để mỗi người một dòng, không nhét
 * nhiều id vào một ô:
 *
 *   material_request_sign_flow_step_users : người ký của một bước trong DỮ LIỆU GỐC
 *   material_request_sign_candidates      : ảnh chụp danh sách đó trên TỪNG PHIẾU
 *
 * Phải chụp lại trên phiếu chứ không đọc thẳng dữ liệu gốc: quy trình gốc đổi người ký
 * sau này thì phiếu đang ký dở vẫn phải giữ đúng danh sách lúc trình ký (21 CFR Part 11).
 *
 * material_request_signs:
 *   - user_id   : GIỮ LẠI cho phiếu cũ (một người ký đích danh). Phiếu mới để NULL, đọc
 *                 danh sách ở bảng candidates.
 *   - signed_user_id : ai trong danh sách đã thật sự ký bước này.
 */
return new class extends Migration
{
    public function up(): void
    {
        /* ---------- Dữ liệu gốc: người ký của một bước ---------- */
        Schema::create('material_request_sign_flow_step_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('step_id');        // -> material_request_sign_flow_steps.id
            $table->unsignedBigInteger('user_id');        // -> user_management.id
            $table->string('user_name', 255)->nullable(); // Ảnh chụp "Họ Tên (userName)" lúc khai
            $table->tinyInteger('active')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->index('step_id', 'material_request_sign_flow_step_users_step_id_index');
            $table->index('user_id', 'material_request_sign_flow_step_users_user_id_index');
        });

        // Chuyển người ký duy nhất của các bước đã khai sang bảng mới
        DB::table('material_request_sign_flow_steps')
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->each(function ($step) {
                DB::table('material_request_sign_flow_step_users')->insert([
                    'step_id' => $step->id,
                    'user_id' => $step->user_id,
                    'user_name' => $step->user_name,
                    'active' => $step->active,
                    'created_by' => $step->created_by,
                    'created_at' => $step->created_at ?? now(),
                    'updated_at' => $step->updated_at ?? now(),
                ]);
            });

        Schema::table('material_request_sign_flow_steps', function (Blueprint $table) {
            $table->dropIndex('material_request_sign_flow_steps_user_id_index');
            $table->dropColumn(['user_id', 'user_name']);
        });

        /* ---------- Trên phiếu: ảnh chụp danh sách người được ký từng bước ---------- */
        Schema::create('material_request_sign_candidates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sign_id');        // -> material_request_signs.id
            $table->unsignedBigInteger('user_id');        // -> user_management.id
            $table->string('user_name', 255)->nullable(); // Ảnh chụp "Họ Tên (userName)" lúc trình ký
            $table->tinyInteger('active')->default(1);    // Sửa phiếu: bỏ hiệu lực dòng cũ, không xoá cứng
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->index('sign_id', 'material_request_sign_candidates_sign_id_index');
            $table->index('user_id', 'material_request_sign_candidates_user_id_index');
        });

        // Phiếu đang dở dang: chuyển người ký đích danh của từng bước sang danh sách mới
        DB::table('material_request_signs')
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->each(function ($sign) {
                DB::table('material_request_sign_candidates')->insert([
                    'sign_id' => $sign->id,
                    'user_id' => $sign->user_id,
                    'user_name' => $sign->user_name,
                    'active' => $sign->active,
                    'created_by' => $sign->created_by,
                    'created_at' => $sign->created_at ?? now(),
                    'updated_at' => $sign->updated_at ?? now(),
                ]);
            });

        Schema::table('material_request_signs', function (Blueprint $table) {
            if (! Schema::hasColumn('material_request_signs', 'signed_user_id')) {
                // Ai trong danh sách đã ký bước này - signed_by chỉ lưu chuỗi tên
                $table->unsignedBigInteger('signed_user_id')->nullable()->after('signed_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('material_request_signs', function (Blueprint $table) {
            if (Schema::hasColumn('material_request_signs', 'signed_user_id')) {
                $table->dropColumn('signed_user_id');
            }
        });

        Schema::dropIfExists('material_request_sign_candidates');

        Schema::table('material_request_sign_flow_steps', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('role_id');
            $table->string('user_name', 255)->nullable()->after('user_id');
            $table->index('user_id', 'material_request_sign_flow_steps_user_id_index');
        });

        // Trả lại người ký đầu tiên của mỗi bước - luồng cũ chỉ chứa được một người
        DB::table('material_request_sign_flow_step_users')
            ->where('active', 1)
            ->orderBy('id')
            ->get()
            ->groupBy('step_id')
            ->each(function ($rows, $stepId) {
                $first = $rows->first();

                DB::table('material_request_sign_flow_steps')->where('id', $stepId)->update([
                    'user_id' => $first->user_id,
                    'user_name' => $first->user_name,
                ]);
            });

        Schema::dropIfExists('material_request_sign_flow_step_users');
    }
};
