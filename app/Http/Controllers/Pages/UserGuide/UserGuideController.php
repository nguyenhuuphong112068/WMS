<?php

namespace App\Http\Controllers\Pages\UserGuide;

use App\Http\Controllers\Controller;
use App\Support\PermissionMatrix;
use App\Support\UserGuideLinks;

/**
 * HƯỚNG DẪN SỬ DỤNG
 *
 * Mỗi tài liệu hướng dẫn ứng với một nhóm menu trên leftNAV. Nội dung viết sẵn trong
 * resources/views/pages/userGuide/<khoá>/ (mỗi màn hình một file trong sections/),
 * không lưu CSDL nên đi theo git cùng mã nguồn.
 *
 * Thêm tài liệu mới: khai một dòng trong GUIDES + tạo thư mục view cùng tên khoá.
 *
 * Riêng các bảng phân quyền (ma trận chung, "vai trò được làm gì" của từng module) đọc thẳng
 * cấu hình Nhóm Quyền hiện tại qua App\Support\PermissionMatrix, nên luôn khớp khi phân quyền đổi.
 */
class UserGuideController extends Controller
{
    /** Khoá => thông tin hiển thị. Khoá chính là tham số {guide} trên route và tên thư mục view. */
    public const GUIDES = [
        // Đứng đầu: nút "?" ở Trang chủ dẫn tới mục Trang chủ của tài liệu này trước mục menu của Phân Quyền
        'general' => [
            'title' => 'Bắt Đầu & Bảo Mật',
            'icon' => 'fas fa-shield-alt',
            'summary' => 'Đăng nhập, đổi mật khẩu, khoá tài khoản và hết phiên; các chức năng của Trang chủ; Lịch Sử Đăng Nhập, Audit Trail và cách hệ thống đáp ứng 21 CFR Part 11.',
            'updated_at' => '2026-10-28',
        ],
        'permission' => [
            'title' => 'Phân Quyền',
            'icon' => 'fas fa-user-shield',
            'summary' => 'Cách hệ thống phân quyền theo nhóm quyền và phòng ban, cách thiết lập cho người dùng, các vai trò hiện có và bảng phân quyền chung của toàn hệ thống.',
            'updated_at' => '2026-10-28',
        ],
        'materData' => [
            'title' => 'Dữ Liệu Gốc',
            'icon' => 'fas fa-database',
            'summary' => 'Khai báo tổ chức, danh mục dùng chung, hoá chất - chất chuẩn - vật tư, định khu kho và khởi tạo dữ liệu ban đầu.',
            'updated_at' => '2026-10-28',
        ],
        'material' => [
            'title' => 'Vật Tư',
            'icon' => 'fas fa-cubes',
            'summary' => 'Trọn vòng đời vật tư: danh mục, nhập và kiểm tra, đề nghị - ký duyệt - cấp phát, liên phòng ban và VPP, tồn kho - kiểm kê, dự trù và thống kê, kèm sơ đồ đường đi phiếu.',
            'updated_at' => '2026-10-28',
        ],
        'chemical' => [
            'title' => 'Hoá Chất',
            'icon' => 'fas fa-flask',
            'summary' => 'Danh mục và phân loại NĐ 24/2026, tương kỵ lưu trữ, nhập - kiểm tra, sử dụng và hoá chất cấm, liên phòng ban, cách ly, tồn kho - hạn dùng nội bộ, ngưỡng tồn trữ PL IV và dự trù.',
            'updated_at' => '2026-10-28',
        ],
        'standard' => [
            'title' => 'Chất Chuẩn',
            'icon' => 'fas fa-vial',
            'summary' => 'Mã ống chuẩn theo nhóm, nhập - kiểm tra, cấp phát cho tổ và ghi sử dụng, liên phòng ban, cách ly - loại bỏ - huỷ, hạn dùng retest / check online / nội bộ, kiểm soát khối lượng, đánh giá hạn dùng chuẩn thứ cấp và dự trù.',
            'updated_at' => '2026-10-28',
        ],
    ];

    public function index()
    {
        session()->put(['title' => 'HƯỚNG DẪN SỬ DỤNG']);

        return view('pages.userGuide.list', [
            'guides' => self::GUIDES,
        ]);
    }

    public function show(string $guide)
    {
        abort_unless(isset(self::GUIDES[$guide]), 404);

        session()->put(['title' => 'HƯỚNG DẪN SỬ DỤNG - ' . mb_strtoupper(self::GUIDES[$guide]['title'])]);

        $data = [
            'guideKey' => $guide,
            'guide' => self::GUIDES[$guide],
            // Mục lục ở <khoá>/toc.php, dùng chung với nút "?" trên topNAV
            'ugGroups' => UserGuideLinks::groups($guide),
        ];

        if ($guide === 'permission') {
            $data += $this->permissionMatrix();
        } elseif (in_array($guide, ['materData', 'material', 'chemical', 'standard'], true)) {
            // Bảng "vai trò được làm gì" ở mục Quyền Truy Cập của từng tài liệu
            $data['ugRoleSummary'] = PermissionMatrix::roleSummary($guide);
        }

        return view('pages.userGuide.' . $guide . '.index', $data);
    }

    /**
     * Ma trận chung cho tài liệu Phân Quyền: roles, tree, assigned như màn hình Nhóm Quyền, kèm
     *  - parentOf   : [permissionId => parentId] để đánh dấu ô đã tick nhưng thiếu quyền cha;
     *  - roleCounts : [roleId => [model => số quyền có hiệu lực]] cho bảng các vai trò.
     */
    private function permissionMatrix(): array
    {
        $matrix = PermissionMatrix::build();
        $parentOf = [];

        foreach ($matrix['tree'] as $model) {
            foreach ($model['groups'] as $permissions) {
                foreach ($permissions as $permission) {
                    $parentOf[$permission->id] = $permission->parent_id;
                }
            }
        }

        $roleCounts = [];
        foreach ($matrix['roles'] as $role) {
            foreach ($matrix['tree'] as $modelKey => $model) {
                $count = 0;
                foreach ($model['groups'] as $permissions) {
                    foreach ($permissions as $permission) {
                        if ($role->name === 'Admin'
                            || PermissionMatrix::isEffective($role->id, $permission->id, $parentOf, $matrix['assigned'])) {
                            $count++;
                        }
                    }
                }
                $roleCounts[$role->id][$modelKey] = $count;
            }
        }

        return $matrix + ['parentOf' => $parentOf, 'roleCounts' => $roleCounts];
    }
}
