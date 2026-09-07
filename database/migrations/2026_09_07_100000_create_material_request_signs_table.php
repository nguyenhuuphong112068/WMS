<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * QUY TRÌNH KÝ DUYỆT TỰ CHỌN CHO ĐỀ NGHỊ CẤP PHÁT VẬT TƯ
 *
 * Trước đây luồng ký bị đóng cứng: Trưởng/Phó Phòng ký (bắt buộc) rồi Ban Giám Đốc ký
 * (khi needs_director = 1). Nay người lập phiếu tự khai SỐ BƯỚC KÝ và NGƯỜI KÝ của
 * từng bước, mỗi bước một dòng trong material_request_signs.
 *
 *   0 bước  -> trình ký là duyệt luôn, phiếu đi thẳng đến người cấp phát.
 *   n bước  -> ký lần lượt theo step_no; ký hết bước cuối mới approved.
 *
 * material_request_lists giữ lại các cột cũ (needs_director, manager_signed_*,
 * director_signed_*) để không mất dữ liệu đã ký, nhưng code mới không đọc nữa - trạng
 * thái bước ký đọc hết từ bảng này.
 *
 * Phiếu cũ được backfill sang bảng mới: bước không biết đích danh người ký thì để
 * user_id = NULL và ghi role_names, ai thuộc role đó vẫn ký tiếp được như trước.
 */
return new class extends Migration
{
    /** Role được ký hai bước của luồng cũ - chỉ dùng để backfill. */
    private const LEGACY_MANAGER_ROLES = 'Trưởng Phòng,Phó Phòng,Phó Trưởng Phòng';

    private const LEGACY_DIRECTOR_ROLES = 'Ban Giám Đốc';

    public function up(): void
    {
        if (! Schema::hasTable('material_request_signs')) {
            Schema::create('material_request_signs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('request_list_id');       // -> material_request_lists.id
                $table->unsignedInteger('step_no');                  // Thứ tự ký: 1, 2, 3...
                $table->unsignedBigInteger('user_id')->nullable();   // -> user_management.id (người ký được chỉ định)
                $table->string('user_name', 255)->nullable();        // Ảnh chụp "Họ Tên (userName)" lúc chỉ định
                $table->string('role_names', 255)->nullable();       // Chỉ phiếu cũ: role được ký thay khi không có user_id
                $table->string('status', 20)->default('pending');    // pending | signed | rejected
                $table->string('signed_by', 255)->nullable();
                $table->timestamp('signed_at')->nullable();
                $table->string('reject_reason', 500)->nullable();
                $table->tinyInteger('active')->default(1);           // Sửa phiếu: bỏ hiệu lực bước cũ, không xoá cứng
                $table->string('created_by')->nullable();
                $table->string('updated_by')->nullable();
                $table->timestamps();

                $table->index(['request_list_id', 'step_no'], 'material_request_signs_parent_index');
                $table->index('user_id', 'material_request_signs_user_id_index');
            });
        }

        Schema::table('material_request_lists', function (Blueprint $table) {
            if (! Schema::hasColumn('material_request_lists', 'sign_step_count')) {
                // Số bước ký của phiếu; 0 = duyệt thẳng, không cần ai ký
                $table->unsignedInteger('sign_step_count')->default(0)->after('app_status');
            }
            if (! Schema::hasColumn('material_request_lists', 'current_step')) {
                // Bước đang chờ ký; NULL khi nháp / đã duyệt / bị từ chối / đã huỷ
                $table->unsignedInteger('current_step')->nullable()->after('sign_step_count');
            }
            if (! Schema::hasColumn('material_request_lists', 'created_user_id')) {
                // Người lập phiếu - cần id thật để gửi thông báo khi phiếu được duyệt / trả về
                $table->unsignedBigInteger('created_user_id')->nullable()->after('created_by');
            }
        });

        $this->backfill();
    }

    /**
     * Chuyển phiếu cũ sang mô hình bước ký.
     *
     * Bước 1 luôn là Trưởng/Phó Phòng; bước 2 (Ban Giám Đốc) chỉ dựng khi phiếu có
     * needs_director hoặc đã có chữ ký Ban Giám Đốc.
     */
    private function backfill(): void
    {
        DB::table('material_request_lists')->orderBy('id')->each(function ($list) {
            if (DB::table('material_request_signs')->where('request_list_id', $list->id)->exists()) {
                return;
            }

            $needsDirector = (bool) ($list->needs_director ?? false) || ! empty($list->director_signed_at);

            $steps = [[
                'no' => 1,
                'roles' => self::LEGACY_MANAGER_ROLES,
                'key' => 'manager',
                'signed_by' => $list->manager_signed_by ?? null,
                'signed_at' => $list->manager_signed_at ?? null,
            ]];

            if ($needsDirector) {
                $steps[] = [
                    'no' => 2,
                    'roles' => self::LEGACY_DIRECTOR_ROLES,
                    'key' => 'director',
                    'signed_by' => $list->director_signed_by ?? null,
                    'signed_at' => $list->director_signed_at ?? null,
                ];
            }

            $rows = [];

            foreach ($steps as $step) {
                $rejected = $list->app_status === 'rejected' && ($list->reject_step ?? null) === $step['key'];

                $rows[] = [
                    'request_list_id' => $list->id,
                    'step_no' => $step['no'],
                    'user_id' => null,
                    'user_name' => $step['signed_by'],
                    'role_names' => $step['roles'],
                    'status' => $step['signed_at'] ? 'signed' : ($rejected ? 'rejected' : 'pending'),
                    'signed_by' => $step['signed_by'],
                    'signed_at' => $step['signed_at'],
                    'reject_reason' => $rejected ? ($list->reject_reason ?? null) : null,
                    'active' => 1,
                    'created_by' => $list->created_by ?? null,
                    'created_at' => $list->created_at ?? now(),
                    'updated_at' => $list->updated_at ?? now(),
                ];
            }

            DB::table('material_request_signs')->insert($rows);

            $currentStep = match ($list->app_status) {
                'pending_manager' => 1,
                'pending_director' => 2,
                default => null,
            };

            DB::table('material_request_lists')->where('id', $list->id)->update([
                'app_status' => in_array($list->app_status, ['pending_manager', 'pending_director'], true)
                    ? 'pending_sign'
                    : $list->app_status,
                'sign_step_count' => count($rows),
                'current_step' => $currentStep,
                'reject_step' => $list->app_status === 'rejected'
                    ? (string) (($list->reject_step ?? null) === 'director' ? 2 : 1)
                    : $list->reject_step,
            ]);
        });
    }

    public function down(): void
    {
        // Trả app_status về hai trạng thái cũ theo bước đang chờ ký
        DB::table('material_request_lists')->where('app_status', 'pending_sign')->orderBy('id')->each(function ($list) {
            DB::table('material_request_lists')->where('id', $list->id)->update([
                'app_status' => (int) ($list->current_step ?? 1) >= 2 ? 'pending_director' : 'pending_manager',
            ]);
        });

        Schema::table('material_request_lists', function (Blueprint $table) {
            foreach (['sign_step_count', 'current_step', 'created_user_id'] as $column) {
                if (Schema::hasColumn('material_request_lists', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('material_request_signs');
    }
};
