<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Quyền con theo từng màn hình Dữ Liệu Gốc.
 *
 * Mỗi phạm vi (material / chemical / standard / common) có 3 quyền nhóm: view (Xem), manage (Thêm / Sửa / Khoá) và
 * review (Duyệt / Từ chối). Quyền nhóm materData_<phạm vi>_<thao tác> phủ nhiều màn hình;
 * mỗi màn hình có quyền con materData_<phạm vi>_<thao tác>_<màn hình>, parent_id = quyền nhóm. Quyền con chỉ có
 * hiệu lực khi có cả quyền nhóm (user_permission_names), nên mọi chỗ kiểm tra dùng quyền con: menu trái, nút
 * Thêm / Sửa / Duyệt / Từ chối / Khoá, bảng chờ duyệt ở Trang chủ, Cấu trúc kho.
 *
 * Màn có bộ quyền riêng (Trình Ký Liên Phòng Ban, Khởi Tạo Dữ Liệu) không nằm ở đây.
 */
class MaterDataPermissions
{
    /** Nhãn thao tác, theo thứ tự hiển thị */
    public const ACTIONS = [
        'view' => 'Xem',
        'manage' => 'Thêm/Sửa/Khoá',
        'review' => 'Duyệt/Từ Chối',
    ];

    /** Diễn giải mặc định cho quyền con của màn hình MỚI; màn đã có giữ diễn giải đang lưu (xem 2026_11_03_150000) */
    private const DEFAULT_DESCRIPTIONS = [
        'view' => 'Thấy mục %s trên menu Dữ Liệu Gốc và mở màn hình để xem. Chỉ xem, không thêm hay sửa được.',
        'manage' => 'Thêm mới, sửa, khoá / mở khoá bản ghi ở màn Dữ Liệu Gốc › %s.',
        'review' => 'Duyệt hoặc từ chối bản ghi đang Chờ duyệt ở màn Dữ Liệu Gốc › %s. Bản ghi chỉ chọn được ở các màn nghiệp vụ sau khi Đã duyệt.',
    ];

    /** Thao tác cụ thể trên màn hình -> quyền gộp; view gọi md_perm('unit', 'create') vẫn đúng */
    private const ALIASES = [
        'create' => 'manage',
        'update' => 'manage',
        'deActive' => 'manage',
        'approve' => 'review',
        'reject' => 'review',
    ];

    private const BASIC = ['view', 'manage'];

    private const WITH_REVIEW = ['view', 'manage', 'review'];

    /** [khoá màn hình (đoạn route pages.materData.<khoá>.) => [phạm vi, tên màn hình, thao tác có trên màn]] */
    public const SCREENS = [
        'materialName' => ['material', 'Tên Vật Tư', self::WITH_REVIEW],
        'departmentClassification' => ['material', 'Phân Loại', self::BASIC],
        'materialSignFlow' => ['material', 'Trình Ký Đề Nghị CP Vật Tư', self::BASIC],
        'activeIngredient' => ['chemical', 'Đơn Chất', self::WITH_REVIEW],
        'mixtureHazardCategory' => ['chemical', 'Nhóm Nguy Hại Bảng B', self::WITH_REVIEW],
        'chemName' => ['chemical', 'Hoá Chất', self::WITH_REVIEW],
        'standardName' => ['standard', 'Tên Chuẩn', self::WITH_REVIEW],
        'purpose' => ['standard', 'Chỉ Tiêu Kiểm', self::BASIC],
        'company' => ['common', 'Công Ty', self::BASIC],
        'department' => ['common', 'Phòng Ban', self::BASIC],
        'group' => ['common', 'Tổ', self::BASIC],
        'unit' => ['common', 'Đơn Vị Tính', self::WITH_REVIEW],
        'packagingSpecification' => ['common', 'Quy Cách Đóng Gói', self::WITH_REVIEW],
        'storageCondition' => ['common', 'Điều Kiện Bảo Quản', self::WITH_REVIEW],
        'chemManufacturer' => ['common', 'Nhà Sản Xuất', self::WITH_REVIEW],
        'chemSupplier' => ['common', 'Nhà Cung Cấp', self::WITH_REVIEW],
        'status' => ['common', 'Trạng Thái', self::BASIC],
        'productName' => ['common', 'Tên Sản Phẩm', self::BASIC],
        'zone' => ['common', 'Định Khu', self::BASIC],
        'consumptionObject' => ['common', 'Đối Tượng', self::BASIC],
    ];

    /** Tên quyền con của một thao tác trên một màn hình, vd. materData_material_create_materialName */
    public static function name(string $screen, string $action): string
    {
        $scope = self::SCREENS[$screen][0] ?? throw new \InvalidArgumentException("Màn Dữ Liệu Gốc không có: $screen");
        $action = self::ALIASES[$action] ?? $action;

        return "materData_{$scope}_{$action}_{$screen}";
    }

    /** [màn hình, thao tác] nếu là quyền con của một màn hình Dữ Liệu Gốc, ngược lại null */
    public static function parse(string $name): ?array
    {
        if (! preg_match('/^materData_(material|chemical|standard|common)_([a-zA-Z]+)_([a-zA-Z]+)$/', $name, $m)
            || (self::SCREENS[$m[3]][0] ?? null) !== $m[1]) {
            return null;
        }

        return [$m[3], $m[2]];
    }

    /** Quyền nhóm materData_<phạm vi>_<thao tác> (cha của quyền con các màn hình) */
    public static function isGroupPermission(string $name): bool
    {
        return (bool) preg_match('/^materData_(material|chemical|standard|common)_(view|manage|review)$/', $name);
    }

    public static function can(string $screen, string $action): bool
    {
        return user_can(self::name($screen, $action));
    }

    /**
     * Tạo / cập nhật quyền con cho mọi màn hình. Quyền con MỚI được cấp cho mọi nhóm quyền đang có quyền nhóm cha,
     * để người đang dùng không mất quyền nào. Chạy lại được.
     */
    public static function seed(): void
    {
        $now = now();

        foreach (self::SCREENS as $screen => [$scope, $label, $actions]) {
            foreach ($actions as $action) {
                $parent = DB::table('permissions')->where('name', "materData_{$scope}_{$action}")->first();
                if (! $parent) {
                    continue;
                }

                $name = self::name($screen, $action);
                $row = [
                    'permission_group' => $parent->permission_group,
                    'parent_id' => $parent->id,
                    'display_name' => self::ACTIONS[$action].' '.$label,
                    'updated_at' => $now,
                ];

                $existing = DB::table('permissions')->where('name', $name)->first();
                if ($existing) {
                    DB::table('permissions')->where('id', $existing->id)->update($row);

                    continue;
                }

                $permissionId = DB::table('permissions')->insertGetId($row + [
                    'name' => $name,
                    'description' => sprintf(self::DEFAULT_DESCRIPTIONS[$action], $label),
                    'created_at' => $now,
                ]);

                foreach (DB::table('role_permission')->where('permission_id', $parent->id)->pluck('role_id') as $roleId) {
                    DB::table('role_permission')->insert(['role_id' => $roleId, 'permission_id' => $permissionId]);
                }

                // Quyền cấp riêng cho user (Cho phép / Từ chối) ở quyền nhóm cũng áp sang quyền con
                foreach (DB::table('user_permission')->where('permission_id', $parent->id)->get() as $override) {
                    DB::table('user_permission')->insert([
                        'user_id' => $override->user_id,
                        'permission_id' => $permissionId,
                        'is_denied' => $override->is_denied,
                    ]);
                }
            }
        }
    }
}
