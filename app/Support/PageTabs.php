<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * QUYỀN XEM TỪNG TAB của các trang nhiều tab - quyền con (permissions.parent_id) của quyền xem trang.
 *
 * Tên quyền tab = tên quyền xem trang bỏ đuôi "_view" + "_tab_" + khoá tab,
 * vd. export_material_view -> export_material_tab_book. Quyền con chỉ có hiệu lực khi user có
 * quyền cha (user_permission_names()). Riêng tab có sẵn quyền riêng từ trước thì khai 'permission'.
 *
 * View dùng:  @tab('export_material', 'book') ... @endtab   (nút tab + vùng nội dung của tab)
 *             $activeTab = PageTabs::active('export_material', $activeTab)  - tab đang mở không
 *             được xem thì chuyển sang tab đầu tiên được xem.
 *
 * Thêm tab mới: khai ở đây + tạo migration gọi PageTabs::seed() (xem 2026_10_21_150000).
 */
class PageTabs
{
    /** trang => ['parent' => quyền xem trang, 'tabs' => [khoá => nhãn | ['label', 'permission']]] */
    public const PAGES = [
        // ----- Danh Mục -----
        'category_material' => ['parent' => 'category_material_view', 'tabs' => [
            'company' => 'Danh mục vật tư công ty',
            'department' => 'Vật tư của phòng',
            'internal' => 'DS đề nghị nội bộ theo chu kỳ',
            'external' => 'DS đề nghị liên phòng ban theo chu kỳ',
            'estimate' => 'DS dự trù theo chu kỳ',
        ]],
        'category_chemical' => ['parent' => 'category_chemical_view', 'tabs' => [
            'company' => 'Danh mục hoá chất công ty',
            'department' => 'Hoá chất của phòng',
        ]],
        'category_standard' => ['parent' => 'category_standard_view', 'tabs' => [
            'company' => 'Danh mục chất chuẩn công ty',
            'department' => 'Chất chuẩn của phòng',
        ]],

        // ----- Nhập -----
        'import_material' => ['parent' => 'import_material_view', 'tabs' => [
            'book' => 'Sổ nhập vật tư',
            'check' => 'Chờ kiểm tra',
        ]],
        'import_chemical' => ['parent' => 'import_chemical_view', 'tabs' => [
            'check' => 'Nhập hoá chất',
            'book' => 'Sổ nhập hoá chất',
            'report' => 'Báo cáo nhập hoá chất',
        ]],
        'import_standard' => ['parent' => 'import_standard_view', 'tabs' => [
            'book' => 'Sổ nhập chất chuẩn',
            'check' => 'Chờ kiểm tra',
        ]],

        // ----- Sử Dụng -----
        'export_material' => ['parent' => 'export_material_view', 'tabs' => [
            'periodic' => 'Đề nghị nội bộ - Theo định kỳ',
            'risk_assessment' => 'Đề nghị nội bộ - Theo ĐG rủi ro',
            'regular' => 'Đề nghị nội bộ - Thường quy',
            'vpp' => 'Đề nghị liên phòng ban - VPP đến HC',
            'transfer' => 'Đề nghị liên phòng ban - Phòng ban khác',
            'prepare' => 'Chờ cấp phát',
            'calibration' => 'Xác nhận hiệu chuẩn',
            'book' => 'Sổ cấp phát',
            'quarantine' => 'Cách ly - Loại bỏ - Huỷ',
            'late' => 'Ghi nhận cấp phát trễ',
        ]],
        'export_chemical' => ['parent' => 'export_chemical_view', 'tabs' => [
            // Tab đầu: nút lập phiếu Sử dụng + các dòng đã Lưu Tạm (khoá 'draft' giữ nguyên vì quyền đã cấp)
            'draft' => 'Sử Dụng Hoá Chất',
            'book' => 'Sổ sử dụng hoá chất',
            'banned' => 'Hoá chất cấm',
            'request' => 'Đề nghị chuyển liên phòng ban',
            'quarantine' => 'Cách ly - Loại bỏ - Huỷ',
            'report' => 'Báo cáo theo khoảng thời gian',
        ]],
        'export_standard' => ['parent' => 'export_standard_view', 'tabs' => [
            'book' => 'Sổ sử dụng chất chuẩn',
            'request' => 'Đề nghị cấp phát chuẩn nội bộ',
            'transfer' => 'Đề nghị cấp phát chuẩn liên phòng ban',
        ]],

        // ----- Tồn Kho -----
        'inventory_material' => ['parent' => 'inventory_material_view', 'tabs' => [
            'code' => 'Theo mã xuất nhập',
            'material' => 'Tồn theo mã vật tư',
            'zone' => 'Tồn kho theo vị trí',
            'next' => 'Khả dụng tháng tới',
            'check' => 'Kiểm kê định kỳ',
        ]],
        'inventory_chemical' => ['parent' => 'inventory_chemical_view', 'tabs' => [
            'code' => 'Theo mã xuất nhập',
            'chemical' => 'Theo hoá chất',
            'zone' => 'Theo định khu',
            'expiring' => 'Cận hạn dùng',
            'internal' => 'Chưa có hạn nội bộ',
        ]],
        'inventory_standard' => ['parent' => 'inventory_standard_view', 'tabs' => [
            'code' => 'Theo mã ống chuẩn',
            'standard' => 'Theo chất chuẩn',
            'zone' => 'Theo định khu',
            'expiring' => 'Hạn dùng sắp hết',
            'internal' => 'Chưa có hạn nội bộ',
            'weight' => 'Kiểm soát khối lượng',
        ]],

        // ----- Dự Trù -----
        'estimate_material' => ['parent' => 'estimate_material_view', 'tabs' => [
            'list' => 'Danh sách phiếu',
            'tracking' => 'Theo dõi dự trù',
            'watchlist' => 'Danh sách vật tư cần dự trù',
            'approved' => 'Đơn dự trù đã được phê duyệt',
        ]],
        'estimate_chemical' => ['parent' => 'estimate_chemical_view', 'tabs' => [
            'list' => 'Danh sách phiếu',
            'tracking' => 'Theo dõi dự trù',
            'watchlist' => 'Danh sách hoá chất cần dự trù',
            'approved' => 'Đơn dự trù đã được phê duyệt',
        ]],
        'estimate_standard' => ['parent' => 'estimate_standard_view', 'tabs' => [
            'list' => 'Danh sách phiếu',
            'tracking' => 'Theo dõi dự trù',
            'watchlist' => 'Danh sách chất chuẩn cần dự trù',
            'approved' => 'Đơn dự trù đã được phê duyệt',
        ]],
    ];

    /** Tên quyền xem một tab. */
    public static function permission(string $page, string $tab): string
    {
        $meta = self::PAGES[$page]['tabs'][$tab] ?? null;

        if (is_array($meta) && isset($meta['permission'])) {
            return $meta['permission'];
        }

        return preg_replace('/_view$/', '', self::PAGES[$page]['parent']).'_tab_'.$tab;
    }

    public static function label(string $page, string $tab): string
    {
        $meta = self::PAGES[$page]['tabs'][$tab];

        return is_array($meta) ? $meta['label'] : $meta;
    }

    /** User đang đăng nhập được xem tab không (trang / tab chưa khai báo -> cho xem). */
    public static function can(string $page, string $tab): bool
    {
        if (! isset(self::PAGES[$page]['tabs'][$tab])) {
            return true;
        }

        return user_can(self::permission($page, $tab));
    }

    /** Khoá các tab được xem, theo thứ tự khai báo. */
    public static function allowed(string $page): array
    {
        return array_values(array_filter(
            array_keys(self::PAGES[$page]['tabs'] ?? []),
            fn ($tab) => self::can($page, $tab)
        ));
    }

    /** [khoá tab => bool] cho cả trang. */
    public static function map(string $page): array
    {
        $map = [];
        foreach (array_keys(self::PAGES[$page]['tabs'] ?? []) as $tab) {
            $map[$tab] = self::can($page, $tab);
        }

        return $map;
    }

    /** Tab đang mở: giữ $tab nếu được xem, không thì tab đầu tiên được xem (null nếu không còn tab nào). */
    public static function active(string $page, ?string $tab): ?string
    {
        if ($tab !== null && self::can($page, $tab)) {
            return $tab;
        }

        return self::allowed($page)[0] ?? null;
    }

    /**
     * Tạo / cập nhật quyền tab (quyền con của quyền xem trang). Quyền tab MỚI TẠO được cấp cho
     * Admin + mọi nhóm quyền đang có quyền xem trang, để không ai mất tab đang dùng; $skip =
     * [tên vai trò => [khoá trang => [tab không cấp]]] cho các vai trò cần hẹp hơn.
     * Quyền đã có từ trước chỉ cập nhật tên / quyền cha, không đụng phân quyền.
     */
    public static function seed(array $skip = []): void
    {
        $now = now();
        $roleNames = DB::table('roles')->pluck('name', 'id');

        foreach (self::PAGES as $page => $config) {
            $parent = DB::table('permissions')->where('name', $config['parent'])->first();

            if (! $parent) {
                continue;
            }

            $parentRoles = DB::table('role_permission')->where('permission_id', $parent->id)->pluck('role_id')
                ->merge(DB::table('roles')->where('name', 'Admin')->pluck('id'))
                ->unique();

            foreach (array_keys($config['tabs']) as $tab) {
                $name = self::permission($page, $tab);
                $existing = DB::table('permissions')->where('name', $name)->first();

                if ($existing) {
                    DB::table('permissions')->where('id', $existing->id)->update([
                        'parent_id' => $parent->id,
                        'updated_at' => $now,
                    ]);

                    continue;
                }

                $permissionId = DB::table('permissions')->insertGetId([
                    'permission_group' => $parent->permission_group,
                    'parent_id' => $parent->id,
                    'name' => $name,
                    'display_name' => 'Tab '.self::label($page, $tab),
                    'description' => ($parent->display_name ?: $parent->name).' - tab "'.self::label($page, $tab).'"',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                foreach ($parentRoles as $roleId) {
                    if (in_array($tab, $skip[$roleNames[$roleId] ?? ''][$page] ?? [], true)) {
                        continue;
                    }

                    DB::table('role_permission')->updateOrInsert([
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                    ], []);
                }
            }
        }
    }
}
