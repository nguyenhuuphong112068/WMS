<?php

use Illuminate\Support\Facades\DB;

if (! function_exists('user_current_department_id')) {
    /**
     * Phòng ban đang chọn của phiên hiện tại. Dùng để lọc quyền theo phòng ban.
     */
    function user_current_department_id(): ?int
    {
        $id = session('user')['selected_department_id'] ?? null;

        return $id ? (int) $id : null;
    }
}

if (! function_exists('user_permission_names')) {
    /**
     * Danh sách tên quyền user thực sự có, TÍNH THEO PHÒNG BAN ĐANG CHỌN:
     *   - quyền từ role gán qua user_role có department_id = NULL (áp mọi phòng)
     *     hoặc = phòng ban đang chọn;
     *   - sau đó áp quyền cấp riêng cho user (user_permission) đè lên (không phụ thuộc phòng).
     * Tài khoản Admin luôn có toàn bộ quyền, mọi phòng.
     * Kết quả cache theo (user, phòng ban) trong 1 request.
     */
    function user_permission_names($userId, ?int $departmentId = null): array
    {
        static $cache = [];

        $departmentId ??= user_current_department_id();
        $key = $userId . ':' . ($departmentId ?? 0);

        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        if (user_has_any_role($userId, ['Admin'], $departmentId)) {
            return $cache[$key] = array_fill_keys(
                DB::table('permissions')->pluck('name')->all(),
                true
            );
        }

        $names = [];

        $fromRole = DB::table('permissions')
            ->join('role_permission', 'permissions.id', '=', 'role_permission.permission_id')
            ->join('user_role', 'role_permission.role_id', '=', 'user_role.role_id')
            ->where('user_role.user_id', $userId)
            ->where(function ($q) use ($departmentId) {
                $q->whereNull('user_role.department_id');
                if ($departmentId) {
                    $q->orWhere('user_role.department_id', $departmentId);
                }
            })
            ->pluck('permissions.name');

        foreach ($fromRole as $name) {
            $names[$name] = true;
        }

        // Quyền cấp riêng cho user ghi đè kết quả từ nhóm quyền (áp mọi phòng)
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

        return $cache[$key] = $names;
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
     * Kiểm tra user có thuộc một trong các role được liệt kê không (theo phòng ban đang chọn).
     *  - Role chính (user_management.role_id) là danh tính gốc: áp cho MỌI phòng ban.
     *  - Role gán qua user_role chỉ tính khi department_id = NULL hoặc = phòng ban đang chọn.
     * Role 'Admin' được coi là toàn quyền (bỏ qua $roleNames).
     */
    function user_has_any_role($userId, array $roleNames, ?int $departmentId = null): bool
    {
        $departmentId ??= user_current_department_id();

        $primaryRole = DB::table('user_management')
            ->leftJoin('roles', 'roles.id', '=', 'user_management.role_id')
            ->where('user_management.id', $userId)
            ->value('roles.name');

        $assignedRoles = DB::table('user_role')
            ->join('roles', 'roles.id', '=', 'user_role.role_id')
            ->where('user_role.user_id', $userId)
            ->where(function ($q) use ($departmentId) {
                $q->whereNull('user_role.department_id');
                if ($departmentId) {
                    $q->orWhere('user_role.department_id', $departmentId);
                }
            })
            ->pluck('roles.name')
            ->all();

        $userRoleNames = array_filter(array_merge([$primaryRole], $assignedRoles));

        if (in_array('Admin', $userRoleNames, true)) {
            return true;
        }

        return count(array_intersect($roleNames, $userRoleNames)) > 0;
    }
}

if (! function_exists('user_allowed_department_ids')) {
    /**
     * Các phòng ban user được phép vào làm việc (chọn ở "Chuyển Bộ Phận").
     *  - Trả ['*'] nếu user là Admin (role chính) hoặc có role gán với department_id = NULL
     *    (role áp mọi phòng) -> vào được mọi phòng.
     *  - Ngược lại: phòng ban chính + các phòng ban đã gán role cụ thể.
     * Cache theo user trong 1 request.
     */
    function user_allowed_department_ids($userId): array
    {
        static $cache = [];

        if (array_key_exists($userId, $cache)) {
            return $cache[$userId];
        }

        $primaryRole = DB::table('user_management')
            ->leftJoin('roles', 'roles.id', '=', 'user_management.role_id')
            ->where('user_management.id', $userId)
            ->value('roles.name');

        if ($primaryRole === 'Admin') {
            return $cache[$userId] = ['*'];
        }

        $hasGlobalRole = DB::table('user_role')
            ->where('user_id', $userId)
            ->whereNull('department_id')
            ->exists();

        if ($hasGlobalRole) {
            return $cache[$userId] = ['*'];
        }

        $ids = DB::table('user_role')
            ->where('user_id', $userId)
            ->whereNotNull('department_id')
            ->pluck('department_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $primaryDeptId = DB::table('user_management')->where('id', $userId)->value('deparment_id');
        if ($primaryDeptId) {
            $ids[] = (int) $primaryDeptId;
        }

        return $cache[$userId] = array_values(array_unique($ids));
    }
}

if (! function_exists('user_can_access_department')) {
    /**
     * User có được phép chọn / làm việc ở phòng ban $departmentId không.
     */
    function user_can_access_department($userId, $departmentId): bool
    {
        $allowed = user_allowed_department_ids($userId);

        return $allowed === ['*'] || in_array((int) $departmentId, $allowed, true);
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

if (! function_exists('users_with_permission')) {
    /**
     * Tra NGƯỢC: id các user đang hoạt động có quyền $permissionName TẠI phòng ban
     * $departmentId (và được phép vào làm việc ở phòng đó).
     *
     * Dùng để gửi thông báo cho đúng nhóm người phụ trách một bước nghiệp vụ, ví dụ
     * "phiếu đã duyệt, kho cấp phát đi" -> mọi user có export_material_issue của phòng.
     * Số user trong hệ thống nhỏ nên duyệt từng người, tận dụng cache của
     * user_permission_names() thay vì dựng một truy vấn gộp khó đọc.
     *
     * @return array<int>
     */
    function users_with_permission(string $permissionName, ?int $departmentId = null): array
    {
        $departmentId ??= user_current_department_id();

        $userIds = DB::table('user_management')->where('isActive', 1)->pluck('id')->all();

        $matched = [];

        foreach ($userIds as $userId) {
            if (! isset(user_permission_names($userId, $departmentId)[$permissionName])) {
                continue;
            }

            if ($departmentId && ! user_can_access_department($userId, $departmentId)) {
                continue;
            }

            $matched[] = (int) $userId;
        }

        return $matched;
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
