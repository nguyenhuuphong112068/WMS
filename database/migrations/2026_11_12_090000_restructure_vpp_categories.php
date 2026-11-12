<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * DANH MỤC VPP CÔNG TY - GỌN LẠI THEO NGHIỆP VỤ VPP (12/11/2026)
 *
 * - Phân loại đổi sang 5 NHÓM DỰ TRÙ (App\Support\VppClassification), lưu {"group":"1"} ở cột
 *   classification. Phân loại cũ 4 tiêu chí kiểu Vật Tư không suy ra được nhóm nên để trống
 *   (Chưa phân loại) - người dùng chọn lại nhóm khi sửa mã; giá trị cũ được ghi vào lịch sử.
 * - ĐƠN VỊ TÍNH chuyển từ vpp_department_categories.unit_id lên vpp_categories.unit_id: cả công
 *   ty dùng chung một đơn vị cho mỗi mã VPP (lấy đơn vị các phòng đang dùng nhiều nhất). Bỏ luôn
 *   hệ số quy đổi đơn vị giữa các phòng của VPP (category_unit_conversions category_type 'vpp').
 * - BỎ Thời gian đặt hàng (lead_time_days / lead_time_pending + quyền category_vpp_lead_time),
 *   Thông tin kỹ thuật (technical_specification), Part Number (part_number). Bảng lịch sử
 *   vpp_category_histories giữ nguyên các cột này để ảnh chụp cũ vẫn đọc được.
 * - Điều kiện trình ký đề nghị VPP (vpp_request_sign_rules.conditions) theo tiêu chí cũ đổi
 *   sang tiêu chí "group": tiêu chí cũ "Tất cả" -> nhóm "Tất cả".
 */
return new class extends Migration
{
    private const OLD_CRITERIA = ['price', 'importance', 'dangerous', 'qa_calibration'];

    public function up(): void
    {
        Schema::table('vpp_categories', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_id')->nullable()->after('manufacturers_id');
        });

        // Đơn vị phòng đang dùng nhiều nhất cho từng mã (hoà thì lấy dòng khai gần nhất)
        $units = DB::table('vpp_department_categories')
            ->select('category_id', 'unit_id', DB::raw('COUNT(*) as uses'), DB::raw('MAX(id) as last_id'))
            ->whereNotNull('unit_id')
            ->groupBy('category_id', 'unit_id')
            ->orderByDesc('uses')
            ->orderByDesc('last_id')
            ->get();
        $unitOf = [];
        foreach ($units as $row) {
            $unitOf[$row->category_id] ??= (int) $row->unit_id;
        }

        $now = now();
        $unitNames = DB::table('units')->pluck('short_name', 'id');

        foreach (DB::table('vpp_categories')->get() as $category) {
            $unitId = $unitOf[$category->id] ?? null;

            DB::table('vpp_categories')->where('id', $category->id)->update([
                'unit_id' => $unitId,
                'classification' => null,
            ]);

            $old = array_filter([
                'Phân loại cũ' => $category->classification ? \App\Support\VppClassification::describe($category->classification) : null,
                'Thông tin kỹ thuật' => $category->technical_specification,
                'Part Number' => $category->part_number,
                'Thời gian đặt hàng' => $category->lead_time_days === null ? null : $category->lead_time_days.' ngày',
            ], fn ($value) => $value !== null && $value !== '');

            $note = 'Chuyển cấu trúc danh mục VPP: phân loại theo 5 nhóm dự trù (chưa chọn nhóm)'
                .'; đơn vị tính dùng chung toàn công ty: '.($unitId ? ($unitNames[$unitId] ?? $unitId) : 'chưa có')
                .($old ? '; bỏ '.collect($old)->map(fn ($value, $label) => $label.': '.$value)->implode('; ') : '');

            DB::table('vpp_category_histories')->insert([
                'vpp_category_id' => $category->id,
                'action' => 'Cập nhật',
                'code' => $category->code,
                'material_names_id' => $category->material_names_id,
                'manufacturers_id' => $category->manufacturers_id,
                'unit_id' => $unitId,
                'classification' => null,
                'purchasing_department' => $category->purchasing_department,
                'app_status' => $category->app_status,
                'status_id' => $category->status_id,
                'change_note' => $note,
                'change_reason' => 'Đổi cấu trúc danh mục VPP theo yêu cầu nghiệp vụ',
                'created_by' => 'Hệ thống',
                'created_at' => $now,
            ]);
        }

        Schema::table('vpp_categories', function (Blueprint $table) {
            $table->dropColumn(['part_number', 'technical_specification', 'lead_time_days', 'lead_time_pending']);
        });

        Schema::table('vpp_department_categories', function (Blueprint $table) {
            $table->dropColumn('unit_id');
        });

        DB::table('category_unit_conversions')->where('category_type', 'vpp')->delete();

        $permissionId = DB::table('permissions')->where('name', 'category_vpp_lead_time')->value('id');
        if ($permissionId) {
            DB::table('role_permission')->where('permission_id', $permissionId)->delete();
            DB::table('user_permission')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }

        foreach (DB::table('vpp_request_sign_rules')->whereNotNull('conditions')->get(['id', 'conditions']) as $rule) {
            $groups = json_decode((string) $rule->conditions, true);
            if (! is_array($groups)) {
                continue;
            }

            $converted = [];
            foreach ($groups as $group) {
                if (! is_array($group)) {
                    continue;
                }
                $hadOld = array_intersect(array_keys($group), self::OLD_CRITERIA);
                $group = array_diff_key($group, array_flip(self::OLD_CRITERIA));
                if ($hadOld) {
                    $group = ['group' => '*'] + $group;
                }
                if ($group && ! in_array($group, $converted, true)) {
                    $converted[] = $group;
                }
            }

            DB::table('vpp_request_sign_rules')->where('id', $rule->id)->update([
                'conditions' => $converted ? json_encode($converted, JSON_UNESCAPED_UNICODE) : null,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('vpp_department_categories', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_id')->nullable()->after('classification_id');
        });

        foreach (DB::table('vpp_categories')->whereNotNull('unit_id')->get(['id', 'unit_id']) as $category) {
            DB::table('vpp_department_categories')->where('category_id', $category->id)->update(['unit_id' => $category->unit_id]);
        }

        Schema::table('vpp_categories', function (Blueprint $table) {
            $table->dropColumn('unit_id');
            $table->string('part_number', 100)->nullable()->after('manufacturers_id');
            $table->string('technical_specification', 100)->nullable();
            $table->unsignedSmallInteger('lead_time_days')->nullable();
            $table->boolean('lead_time_pending')->default(false);
        });
    }
};
