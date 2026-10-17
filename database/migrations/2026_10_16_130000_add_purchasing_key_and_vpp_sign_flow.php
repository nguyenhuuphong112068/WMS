<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * BỘ PHẬN MUA HÀNG + QUY TRÌNH KÝ ĐỀ NGHỊ VPP
 *
 * 1. deparments.purchasing_key (supply = Cung Ứng / admin = Hành Chính, NULL = không): phòng
 *    đảm nhận bộ phận mua hàng nào - khai ở Dữ Liệu Gốc > Phòng Ban, thay cho việc dò theo tên
 *    (App\Support\MaterialPurchasing). Bộ phận mua hàng tiếp nhận dự trù / đề nghị VPP của MỌI
 *    công ty (thực tế chỉ NM2 có Cung Ứng + Hành Chính, NM1 gửi về NM2). Gán sẵn theo cách dò
 *    tên cũ để giữ nguyên hành vi.
 * 2. Phòng "Hành Chính - NM1" cho công ty NM1 (không phải bộ phận mua hàng) - người ký bước 2
 *    đề nghị VPP của NM1.
 * 3. material_transfer_sign_steps: quy trình ký đề nghị chuyển vật tư liên phòng ban theo LOẠI
 *    (kind: vpp = gửi kho VPP Hành Chính / transfer = gửi phòng khác) và CÔNG TY của phòng đề
 *    nghị; mỗi bước = vai trò + phạm vi (requester = phòng đề nghị / receiver = phòng được đề
 *    nghị / department = một phòng cố định). Khai sẵn:
 *      VPP NM2      : ① Quản Lý Bộ Phận của phòng đề nghị
 *      VPP NM1      : ① Quản Lý Bộ Phận của phòng đề nghị → ② Quản Lý Bộ Phận của Hành Chính - NM1
 *      Liên phòng ban (mọi công ty): ① Quản Lý Bộ Phận của phòng ĐƯỢC đề nghị
 * 4. material_transfer_signs: các bước ký của từng phiếu (chụp lại quy trình lúc gửi) + cột
 *    theo dõi ký trên material_transfer_requests. Trạng thái phiếu thêm 'pending_sign'.
 */
return new class extends Migration
{
    /** Cách dò tên cũ - chỉ dùng một lần để gán sẵn purchasing_key. */
    private const MATCHERS = [
        'supply' => [['Cung Ứng', 'Cung ứng'], ['PROC', 'CU']],
        'admin' => [['Hành Ch'], ['HC']],
    ];

    public function up(): void
    {
        if (! Schema::hasColumn('deparments', 'purchasing_key')) {
            Schema::table('deparments', function (Blueprint $table) {
                $table->string('purchasing_key', 20)->nullable()->after('is_general');
            });

            foreach (DB::table('deparments')->get() as $department) {
                foreach (self::MATCHERS as $key => [$names, $prefixes]) {
                    $match = false;
                    foreach ($names as $name) {
                        $match = $match || mb_stripos((string) $department->name, $name) !== false;
                    }
                    foreach ($prefixes as $prefix) {
                        $match = $match || stripos((string) $department->shortName, $prefix) === 0;
                    }
                    if ($match) {
                        DB::table('deparments')->where('id', $department->id)->update(['purchasing_key' => $key]);
                        break;
                    }
                }
            }
        }

        // Phòng Hành Chính - NM1: công ty có id nhỏ nhất mà chưa có bộ phận mua hàng nào
        $nm1 = DB::table('companies')
            ->whereNotIn('id', DB::table('deparments')->whereNotNull('purchasing_key')->whereNotNull('company_id')->pluck('company_id'))
            ->orderBy('id')
            ->value('id');
        $hcNm1 = null;

        if ($nm1) {
            $hcNm1 = DB::table('deparments')->where('company_id', $nm1)->where('name', 'Hành Chính - NM1')->value('id')
                ?: DB::table('deparments')->insertGetId([
                    'company_id' => $nm1,
                    'name' => 'Hành Chính - NM1',
                    'shortName' => 'HC',
                    'isActive' => 1,
                    'is_general' => 1,
                    'purchasing_key' => null,
                    'prepareBy' => 'Hệ thống',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        if (! Schema::hasTable('material_transfer_sign_steps')) {
            Schema::create('material_transfer_sign_steps', function (Blueprint $table) {
                $table->id();
                // vpp = đề nghị gửi kho VPP (phòng Hành Chính); transfer = đề nghị liên phòng ban khác
                $table->string('kind', 20)->default('vpp');
                $table->unsignedBigInteger('company_id');
                $table->unsignedTinyInteger('step_no');
                $table->unsignedBigInteger('role_id');
                // requester = phòng đề nghị; receiver = phòng được đề nghị; department = phòng cố định
                $table->string('scope', 20)->default('requester');
                $table->unsignedBigInteger('department_id')->nullable();
                $table->unsignedBigInteger('status_id')->default(1);
                $table->string('created_by')->nullable();
                $table->string('updated_by')->nullable();
                $table->timestamps();
                $table->index(['kind', 'company_id', 'step_no']);
            });

            $managerRole = DB::table('roles')->whereIn('name', (array) config('estimate.department_manager_roles'))->value('id');

            if ($managerRole) {
                $seed = fn ($kind, $companyId, $stepNo, $scope, $departmentId = null) => DB::table('material_transfer_sign_steps')->insert([
                    'kind' => $kind, 'company_id' => $companyId, 'step_no' => $stepNo, 'role_id' => $managerRole, 'scope' => $scope,
                    'department_id' => $departmentId, 'status_id' => 1, 'created_by' => 'Hệ thống',
                    'created_at' => now(), 'updated_at' => now(),
                ]);

                foreach (DB::table('companies')->pluck('id') as $companyId) {
                    $seed('vpp', $companyId, 1, 'requester');

                    if ((int) $companyId === (int) $nm1 && $hcNm1) {
                        $seed('vpp', $companyId, 2, 'department', $hcNm1);
                    }

                    $seed('transfer', $companyId, 1, 'receiver');
                }
            }
        }

        if (! Schema::hasTable('material_transfer_signs')) {
            Schema::create('material_transfer_signs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('transfer_request_id');
                $table->unsignedTinyInteger('step_no');
                $table->string('role_names')->nullable();
                // Phòng mà vai trò phải có phạm vi trên (đã giải từ scope lúc gửi)
                $table->unsignedBigInteger('department_id')->nullable();
                $table->string('status', 20)->default('pending');
                $table->string('signed_by')->nullable();
                $table->timestamp('signed_at')->nullable();
                $table->string('note', 500)->nullable();
                $table->tinyInteger('active')->default(1);
                $table->string('created_by')->nullable();
                $table->string('updated_by')->nullable();
                $table->timestamps();
                $table->index(['transfer_request_id', 'active']);
            });
        }

        Schema::table('material_transfer_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('material_transfer_requests', 'current_step')) {
                $table->unsignedTinyInteger('current_step')->nullable()->after('status');
            }
            if (! Schema::hasColumn('material_transfer_requests', 'sign_reject_reason')) {
                $table->string('sign_rejected_by')->nullable()->after('current_step');
                $table->timestamp('sign_rejected_at')->nullable()->after('sign_rejected_by');
                $table->string('sign_reject_reason', 500)->nullable()->after('sign_rejected_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('material_transfer_requests', function (Blueprint $table) {
            foreach (['current_step', 'sign_rejected_by', 'sign_rejected_at', 'sign_reject_reason'] as $column) {
                if (Schema::hasColumn('material_transfer_requests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
        Schema::dropIfExists('material_transfer_signs');
        Schema::dropIfExists('material_transfer_sign_steps');

        if (Schema::hasColumn('deparments', 'purchasing_key')) {
            Schema::table('deparments', fn (Blueprint $table) => $table->dropColumn('purchasing_key'));
        }
    }
};
