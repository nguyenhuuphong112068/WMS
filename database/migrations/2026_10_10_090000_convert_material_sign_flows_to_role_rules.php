<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * TRÌNH KÝ ĐỀ NGHỊ CẤP PHÁT VẬT TƯ: TỪ "QUY TRÌNH THEO PHÂN LOẠI" SANG "VAI TRÒ + ĐIỀU KIỆN"
 *
 * Cách cũ: mỗi TỔ HỢP phân loại vật tư một quy trình (dãy bước ký). Muốn phủ hết các tổ hợp
 * thì phải khai rất nhiều quy trình gần giống nhau, cùng một vai trò lặp lại ở nhiều nơi.
 *
 * Cách mới lấy VAI TRÒ làm gốc: mỗi vai trò khai một lần, kèm
 *   - level      : CẤP KÝ - phiếu ký lần lượt từ cấp nhỏ tới cấp lớn
 *   - conditions : ĐIỀU KIỆN để vai trò này nhận trình ký, NULL = nhận mọi đề nghị
 *   - người ký   : bảng material_request_sign_rule_users, chỉ cần MỘT người ký là xong bước
 *
 * conditions là JSON dạng danh sách NHÓM: [{"price":"high","importance":"high"},{"dangerous":"yes"}]
 *   - trong một nhóm các tiêu chí nối bằng VÀ
 *   - các nhóm nối với nhau bằng HOẶC
 *   - khoá là tiêu chí của App\Support\MaterialClassification::CRITERIA, riêng
 *     "classification_id" là phân loại danh mục vật tư của phòng (department_classification.id)
 * Đề nghị kích hoạt vai trò khi CÓ ÍT NHẤT MỘT dòng vật tư thoả một nhóm. Không vai trò nào
 * được kích hoạt thì đề nghị được duyệt thẳng. Xem App\Support\MaterialSignFlow.
 *
 * CHUYỂN DỮ LIỆU CŨ: mỗi bước của mỗi quy trình cũ thành một nhóm điều kiện của vai trò
 * tương ứng (điều kiện của quy trình đó). Cùng vai trò ở nhiều quy trình thì gộp nhóm
 * (HOẶC) và gộp người ký; cấp ký lấy số bước nhỏ nhất của vai trò đó, trùng thì dồn lên.
 * Quy trình không khai điều kiện nào thì vai trò của nó nhận mọi đề nghị.
 *
 * Phiếu đã trình ký KHÔNG bị ảnh hưởng: các bước ký đã được chụp sang material_request_signs
 * + material_request_sign_candidates lúc trình ký.
 *
 * Ba bảng cũ bị bỏ hẳn. down() chỉ xoá hai bảng mới, không dựng lại dữ liệu cũ.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_request_sign_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('department_id');          // -> deparments.id
            $table->unsignedBigInteger('role_id');                // -> roles.id, vai trò nhận trình ký
            $table->unsignedInteger('level');                     // Cấp ký: 1, 2, 3... ký từ nhỏ tới lớn
            $table->json('conditions')->nullable();               // Nhóm điều kiện, NULL = nhận mọi đề nghị
            $table->tinyInteger('status_id')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            // Mỗi vai trò chỉ khai một lần trong một phòng
            $table->unique(['department_id', 'role_id'], 'material_request_sign_rules_dept_role_unique');
            $table->index('role_id', 'material_request_sign_rules_role_id_index');
        });

        Schema::create('material_request_sign_rule_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rule_id');                // -> material_request_sign_rules.id
            $table->unsignedBigInteger('user_id');                // -> user_management.id
            $table->string('user_name', 255)->nullable();         // Ảnh chụp "Họ Tên (userName)" lúc khai
            $table->tinyInteger('active')->default(1);            // Sửa: bỏ hiệu lực dòng cũ, không xoá cứng
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->index('rule_id', 'material_request_sign_rule_users_rule_id_index');
            $table->index('user_id', 'material_request_sign_rule_users_user_id_index');
        });

        if (Schema::hasTable('material_request_sign_flows')) {
            $this->convertOldFlows();
        }

        Schema::dropIfExists('material_request_sign_flow_step_users');
        Schema::dropIfExists('material_request_sign_flow_steps');
        Schema::dropIfExists('material_request_sign_flows');
    }

    public function down(): void
    {
        Schema::dropIfExists('material_request_sign_rule_users');
        Schema::dropIfExists('material_request_sign_rules');
    }

    private function convertOldFlows(): void
    {
        $flows = DB::table('material_request_sign_flows')->where('status_id', 1)->orderBy('id')->get();

        // department_id => role_id => ['level', 'groups', 'always', 'users', 'created_by']
        $rules = [];

        foreach ($flows as $flow) {
            $group = json_decode((string) $flow->criteria, true) ?: [];
            if ($flow->classification_id) {
                $group['classification_id'] = (string) $flow->classification_id;
            }

            $steps = DB::table('material_request_sign_flow_steps')
                ->where('flow_id', $flow->id)
                ->where('active', 1)
                ->orderBy('step_no')
                ->get();

            foreach ($steps as $step) {
                $rule = &$rules[$flow->department_id][$step->role_id];
                $rule ??= ['level' => PHP_INT_MAX, 'groups' => [], 'always' => false, 'users' => [], 'created_by' => $flow->created_by];

                $rule['level'] = min($rule['level'], (int) $step->step_no);

                if ($group) {
                    $rule['groups'][json_encode($group)] = $group;
                } else {
                    $rule['always'] = true;
                }

                $users = DB::table('material_request_sign_flow_step_users')
                    ->where('step_id', $step->id)
                    ->where('active', 1)
                    ->get();

                foreach ($users as $user) {
                    $rule['users'][$user->user_id] = $user->user_name;
                }

                unset($rule);
            }
        }

        foreach ($rules as $departmentId => $byRole) {
            // Cấp ký phải khác nhau trong một phòng: trùng thì dồn vai trò sau lên cấp kế tiếp
            uasort($byRole, fn ($a, $b) => $a['level'] <=> $b['level']);
            $used = [];

            foreach ($byRole as $roleId => $rule) {
                $level = $rule['level'];
                while (isset($used[$level])) {
                    $level++;
                }
                $used[$level] = true;

                $ruleId = DB::table('material_request_sign_rules')->insertGetId([
                    'department_id' => $departmentId,
                    'role_id' => $roleId,
                    'level' => $level,
                    'conditions' => $rule['always'] || ! $rule['groups']
                        ? null
                        : json_encode(array_values($rule['groups']), JSON_UNESCAPED_UNICODE),
                    'status_id' => 1,
                    'created_by' => $rule['created_by'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                foreach ($rule['users'] as $userId => $userName) {
                    DB::table('material_request_sign_rule_users')->insert([
                        'rule_id' => $ruleId,
                        'user_id' => $userId,
                        'user_name' => $userName,
                        'active' => 1,
                        'created_by' => $rule['created_by'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
};
