<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tách 6 quyền "Dữ Liệu Gốc" chung (materData_view / _create / _update / _deActive
 * / _approve / _reject) thành 4 phạm vi theo loại dữ liệu gốc:
 *   - materData_material_*  : Tên Vật Tư, Phân Loại Vật Tư
 *   - materData_chemical_*  : Tên Hoá Chất, Hoạt Chất, Nhà SX / NCC, Nhóm Nguy Hại B
 *   - materData_standard_*  : Tên Chất Chuẩn
 *   - materData_common_*    : Công Ty, Phòng Ban, Tổ, Mục Đích, Trạng Thái, Định Khu,
 *                             Đơn Vị Tính, Quy Cách Đóng Gói, Điều Kiện Bảo Quản, Tên Sản Phẩm
 *
 * Role / user đang có quyền cũ sẽ được gán tương ứng cả 4 phạm vi để không mất quyền.
 */
return new class extends Migration
{
    private const GROUP_ID = 1; // permission_groups: Dữ Liệu Gốc

    private const ACTIONS = [
        'view'     => 'Xem',
        'create'   => 'Thêm',
        'update'   => 'Sửa',
        'deActive' => 'Khoá',
        'approve'  => 'Duyệt',
        'reject'   => 'Từ Chối',
    ];

    /** scope => nhãn hiển thị (giữ đúng thứ tự để 4 nhóm hiện đúng thứ tự trên màn Nhóm Quyền) */
    private const SCOPES = [
        'material' => 'Vật Tư',
        'chemical' => 'Hoá Chất',
        'standard' => 'Chất Chuẩn',
        'common'   => 'Dùng Chung',
    ];

    /** 24 tên quyền phạm vi mới */
    private function newNames(): array
    {
        $names = [];
        foreach (array_keys(self::SCOPES) as $scope) {
            foreach (array_keys(self::ACTIONS) as $action) {
                $names[] = "materData_{$scope}_{$action}";
            }
        }

        return $names;
    }

    public function up(): void
    {
        $now = now();

        // 1. Seed 24 quyền mới
        foreach (self::SCOPES as $scope => $scopeLabel) {
            foreach (self::ACTIONS as $action => $actionLabel) {
                DB::table('permissions')->updateOrInsert(
                    ['name' => "materData_{$scope}_{$action}"],
                    [
                        'permission_group' => self::GROUP_ID,
                        'display_name' => "{$actionLabel} Dữ Liệu Gốc {$scopeLabel}",
                        'description' => "{$actionLabel} bản ghi dữ liệu gốc thuộc nhóm {$scopeLabel}",
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }
        }

        // 2. Chuyển gán quyền cũ -> 4 phạm vi mới (role_permission + user_permission)
        foreach (array_keys(self::ACTIONS) as $action) {
            $oldId = DB::table('permissions')->where('name', "materData_{$action}")->value('id');

            $newIds = DB::table('permissions')
                ->whereIn('name', array_map(fn ($s) => "materData_{$s}_{$action}", array_keys(self::SCOPES)))
                ->pluck('id');

            if ($oldId) {
                $roleIds = DB::table('role_permission')->where('permission_id', $oldId)->pluck('role_id');
                foreach ($roleIds as $roleId) {
                    foreach ($newIds as $newId) {
                        DB::table('role_permission')->updateOrInsert(
                            ['role_id' => $roleId, 'permission_id' => $newId],
                            []
                        );
                    }
                }

                $userRows = DB::table('user_permission')->where('permission_id', $oldId)->get();
                foreach ($userRows as $row) {
                    foreach ($newIds as $newId) {
                        DB::table('user_permission')->updateOrInsert(
                            ['user_id' => $row->user_id, 'permission_id' => $newId],
                            ['is_denied' => $row->is_denied]
                        );
                    }
                }
            }
        }

        // 3. Cấp sẵn toàn bộ cho nhóm Admin
        $adminRoleIds = DB::table('roles')->where('name', 'Admin')->pluck('id');
        $allNewIds = DB::table('permissions')->whereIn('name', $this->newNames())->pluck('id');
        foreach ($adminRoleIds as $roleId) {
            foreach ($allNewIds as $newId) {
                DB::table('role_permission')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $newId],
                    []
                );
            }
        }

        // 4. Bỏ 6 quyền cũ
        $oldIds = DB::table('permissions')
            ->whereIn('name', array_map(fn ($a) => "materData_{$a}", array_keys(self::ACTIONS)))
            ->pluck('id');
        DB::table('role_permission')->whereIn('permission_id', $oldIds)->delete();
        DB::table('user_permission')->whereIn('permission_id', $oldIds)->delete();
        DB::table('permissions')->whereIn('id', $oldIds)->delete();
    }

    public function down(): void
    {
        $now = now();

        // Seed lại 6 quyền cũ
        $labelOld = [
            'view' => ['Xem Dữ Liệu Gốc', 'Mở các màn hình thuộc menu Dữ Liệu Gốc'],
            'create' => ['Thêm Dữ Liệu Gốc', 'Thêm mới bản ghi dữ liệu gốc'],
            'update' => ['Sửa Dữ Liệu Gốc', 'Cập nhật bản ghi dữ liệu gốc'],
            'deActive' => ['Vô Hiệu Dữ Liệu Gốc', 'Vô hiệu hoá bản ghi dữ liệu gốc'],
            'approve' => ['Duyệt Dữ Liệu Gốc', 'Duyệt bản ghi dữ liệu gốc để dùng ở màn hình nghiệp vụ'],
            'reject' => ['Từ Chối Dữ Liệu Gốc', 'Đánh dấu từ chối bản ghi dữ liệu gốc'],
        ];

        foreach ($labelOld as $action => [$display, $desc]) {
            DB::table('permissions')->updateOrInsert(
                ['name' => "materData_{$action}"],
                [
                    'permission_group' => self::GROUP_ID,
                    'display_name' => $display,
                    'description' => $desc,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );

            $oldId = DB::table('permissions')->where('name', "materData_{$action}")->value('id');
            $newIds = DB::table('permissions')
                ->whereIn('name', array_map(fn ($s) => "materData_{$s}_{$action}", array_keys(self::SCOPES)))
                ->pluck('id');

            // role có bất kỳ phạm vi mới nào -> nhận lại quyền cũ
            $roleIds = DB::table('role_permission')->whereIn('permission_id', $newIds)->distinct()->pluck('role_id');
            foreach ($roleIds as $roleId) {
                DB::table('role_permission')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $oldId],
                    []
                );
            }

            $userRows = DB::table('user_permission')->whereIn('permission_id', $newIds)->get()->unique('user_id');
            foreach ($userRows as $row) {
                DB::table('user_permission')->updateOrInsert(
                    ['user_id' => $row->user_id, 'permission_id' => $oldId],
                    ['is_denied' => $row->is_denied]
                );
            }
        }

        // Xoá 24 quyền phạm vi
        $newIds = DB::table('permissions')->whereIn('name', $this->newNames())->pluck('id');
        DB::table('role_permission')->whereIn('permission_id', $newIds)->delete();
        DB::table('user_permission')->whereIn('permission_id', $newIds)->delete();
        DB::table('permissions')->whereIn('id', $newIds)->delete();
    }
};
