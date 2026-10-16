<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * ĐỀ NGHỊ VẬT TƯ TỪ KHO VPP (văn phòng phẩm) - SỬ DỤNG VẬT TƯ, tab "Đề nghị VPP".
 *
 * Đề nghị VPP KHÔNG phải một bộ bảng riêng: đó là các đề nghị chuyển vật tư liên phòng ban
 * (material_transfer_requests) GỬI TỚI PHÒNG HÀNH CHÍNH - phòng giữ kho VPP. Phân loại suy
 * thẳng từ phòng nhận nên đề nghị lập tay, lập từ danh sách vật tư cần đề nghị, hay tự sinh
 * từ danh sách đề nghị liên phòng ban theo chu kỳ đều rơi đúng tab mà không cần cột riêng.
 * Các bước sau (Hành Chính cấp phát -> phòng đề nghị nhận hàng) dùng nguyên luồng liên
 * phòng ban của MaterialExportController.
 *
 * Phòng Hành Chính nhận ra theo cùng quy tắc với bộ phận mua hàng "Hành Chánh"
 * (MaterialPurchasing, khoá admin).
 *
 * Đề nghị gửi tới Hành Chính CHỈ gồm vật tư có Bộ Phận Mua Hàng = Hành Chánh - vật tư Hành
 * Chánh mua cũng là vật tư Hành Chánh cấp phát (MaterialClassification::isAdminPurchased).
 * Chặn ở mọi đường lập phiếu: Đề nghị VPP (lập tay / từ danh sách cần đề nghị / sửa nháp) và
 * danh sách liên phòng ban theo chu kỳ gửi Hành Chính - xem allowedCategoryIds().
 */
class MaterialVpp
{
    /** Tên tab ở màn Sử Dụng Vật Tư (?tab=vpp). */
    public const TAB = 'vpp';

    /** Khoá bộ phận mua hàng của vật tư phải đề nghị từ kho VPP. */
    public const PURCHASING_KEY = MaterialClassification::PURCHASING_ADMIN;

    private static ?array $departmentIds = null;

    /** Mọi phòng Hành Chính đang hoạt động (có thể nhiều công ty, mỗi công ty một phòng). */
    public static function departmentIds(): array
    {
        return self::$departmentIds ??= MaterialPurchasing::departmentIdsOf(self::PURCHASING_KEY);
    }

    public static function isVppDepartment(?int $departmentId): bool
    {
        return $departmentId && in_array((int) $departmentId, self::departmentIds(), true);
    }

    /** Tab của màn Sử Dụng Vật Tư chứa một đề nghị liên phòng ban, theo phòng nhận đề nghị. */
    public static function tabFor(?int $toDepartmentId): string
    {
        return self::isVppDepartment($toDepartmentId) ? self::TAB : 'transfer';
    }

    /**
     * Phòng Hành Chính mà phòng $fromDepartmentId gửi đề nghị VPP tới: ưu tiên cùng công ty,
     * công ty chưa có thì lấy phòng Hành Chính đầu tiên. Phòng đang đứng chính là Hành Chính
     * (không tự xin kho của mình được) hoặc hệ thống chưa có phòng Hành Chính thì trả null.
     */
    public static function targetDepartmentId(int $fromDepartmentId): ?int
    {
        $ids = self::departmentIds();

        if (! $ids || in_array($fromDepartmentId, $ids, true)) {
            return null;
        }

        $companyId = DB::table('deparments')->where('id', $fromDepartmentId)->value('company_id');

        $sameCompany = $companyId
            ? DB::table('deparments')->whereIn('id', $ids)->where('company_id', $companyId)->orderBy('id')->value('id')
            : null;

        return (int) ($sameCompany ?: $ids[0]);
    }

    /** Trong các category_id gửi lên, những id được phép đề nghị từ kho VPP (Hành Chánh mua). */
    public static function allowedCategoryIds(array $categoryIds): array
    {
        $categoryIds = array_values(array_unique(array_filter(array_map('intval', $categoryIds))));

        if (! $categoryIds) {
            return [];
        }

        return DB::table('material_categories')
            ->whereIn('id', $categoryIds)
            ->where('purchasing_department', self::PURCHASING_KEY)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Lỗi validate cho các dòng không phải vật tư Hành Chánh mua: [items.i.category_id => thông báo].
     * Dòng đánh số theo thứ tự trên form vì chỉ số items[] do JS sinh có thể nhảy cóc.
     */
    public static function itemErrors(array $items): array
    {
        $allowed = self::allowedCategoryIds(array_column($items, 'category_id'));
        $errors = [];
        $row = 0;

        foreach ($items as $index => $item) {
            $row++;
            $categoryId = (int) ($item['category_id'] ?? 0);

            if ($categoryId && ! in_array($categoryId, $allowed, true)) {
                $errors['items.'.$index.'.category_id'] = 'Dòng '.$row.': vật tư không do Hành Chánh mua, không đề nghị từ kho VPP được.';
            }
        }

        return $errors;
    }

    /** Phòng Hành Chính nhận đề nghị VPP của phòng đang đứng (id, name, shortName), null nếu không có. */
    public static function targetDepartment(int $fromDepartmentId): ?object
    {
        $id = self::targetDepartmentId($fromDepartmentId);

        return $id ? DB::table('deparments')->select('id', 'name', 'shortName')->where('id', $id)->first() : null;
    }
}
