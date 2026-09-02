<?php

use Illuminate\Support\Facades\DB;

if (! function_exists('user_permission_names')) {
    /**
     * Danh sách tên quyền user thực sự có = quyền từ nhóm quyền (role_permission),
     * sau đó áp quyền cấp riêng cho user (user_permission) đè lên.
     * Tài khoản Admin luôn có toàn bộ quyền, không bị chặn bởi user_permission.
     * Kết quả cache theo user trong 1 request để tránh query lặp.
     */
    function user_permission_names($userId): array
    {
        static $cache = [];

        if (array_key_exists($userId, $cache)) {
            return $cache[$userId];
        }

        if (user_has_any_role($userId, ['Admin'])) {
            return $cache[$userId] = array_fill_keys(
                DB::table('permissions')->pluck('name')->all(),
                true
            );
        }

        $names = [];

        $fromRole = DB::table('permissions')
            ->join('role_permission', 'permissions.id', '=', 'role_permission.permission_id')
            ->join('user_role', 'role_permission.role_id', '=', 'user_role.role_id')
            ->where('user_role.user_id', $userId)
            ->pluck('permissions.name');

        foreach ($fromRole as $name) {
            $names[$name] = true;
        }

        // Quyền cấp riêng cho user ghi đè kết quả từ nhóm quyền
        $overrides = DB::table('permissions')
            ->join('user_permission', 'permissions.id', '=', 'user_permission.permission_id')
            ->where('user_permission.user_id', $userId)
            ->pluck('user_permission.is_denied', 'permissions.name');

        foreach ($overrides as $name => $isDenied) {
            if ($isDenied) {
                unset($names[$name]);
            } else {
                $names[$name] = true;
            }
        }

        return $cache[$userId] = $names;
    }
}

if (! function_exists('user_has_permission')) {
    /**
     * $typeReturn = 'boolean'  -> true / false
     * $typeReturn = 'disabled' -> '' nếu có quyền, 'disabled' nếu không (dùng cho thuộc tính input)
     */
    function user_has_permission($userId, $permissionName, $typeReturn = 'boolean')
    {
        $result = isset(user_permission_names($userId)[$permissionName]);

        if ($typeReturn == "disabled") {
            return $result ? "" : "disabled";
        }

        return $result;
    }
}

if (! function_exists('user_has_any_role')) {
    /**
     * Kiểm tra user có thuộc một trong các role được liệt kê không.
     * Role 'Admin' luôn được coi là có toàn quyền (bỏ qua $roleNames).
     * Gộp cả role chính (user_management.userGroup) lẫn các role gán qua user_role/roles
     * để tương thích với dữ liệu cũ (chỉ có userGroup, chưa gán user_role).
     */
    function user_has_any_role($userId, array $roleNames): bool
    {
        $primaryGroup = DB::table('user_management')->where('id', $userId)->value('userGroup');

        $assignedRoles = DB::table('user_role')
            ->join('roles', 'roles.id', '=', 'user_role.role_id')
            ->where('user_role.user_id', $userId)
            ->pluck('roles.name')
            ->all();

        $userRoleNames = array_filter(array_merge([$primaryGroup], $assignedRoles));

        if (in_array('Admin', $userRoleNames, true)) {
            return true;
        }

        return count(array_intersect($roleNames, $userRoleNames)) > 0;
    }
}

if (! function_exists('user_can')) {
    /**
     * Bản rút gọn của user_has_permission cho user đang đăng nhập.
     * Dùng trong view/controller để khỏi phải viết lại session('user')['userId'] mỗi lần.
     *
     * $typeReturn = 'boolean'  -> true / false
     * $typeReturn = 'disabled' -> '' nếu có quyền, 'disabled' nếu không
     */
    function user_can($permissionName, $typeReturn = 'boolean')
    {
        return user_has_permission(session('user')['userId'] ?? 0, $permissionName, $typeReturn);
    }
}

if (! function_exists('user_can_any')) {
    /**
     * Có ít nhất một trong các quyền được liệt kê.
     * Dùng cho nút mở màn hình gộp nhiều thao tác (ví dụ tab, nút mở modal chung).
     */
    function user_can_any(array $permissionNames): bool
    {
        $owned = user_permission_names(session('user')['userId'] ?? 0);

        foreach ($permissionNames as $name) {
            if (isset($owned[$name])) {
                return true;
            }
        }

        return false;
    }
}
