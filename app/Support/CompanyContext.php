<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * CÔNG TY ĐANG LÀM VIỆC
 *
 * Phần mềm chạy cho nhiều công ty, mỗi công ty có bộ phòng ban riêng. Công ty của
 * người dùng suy ra từ PHÒNG BAN ĐANG CHỌN (session('user')['company_id'], ghi lúc
 * đăng nhập và mỗi lần đổi bộ phận). Không có màn chọn công ty riêng.
 *
 * Dùng để giới hạn phạm vi đối chiếu "Ngưỡng khối lượng tồn trữ lớn nhất" - Phụ lục IV
 * NĐ 24/2026/NĐ-CP: chỉ cộng tồn của các phòng ban thuộc cùng một công ty.
 *
 * Query Builder thuần, không Eloquent.
 */
class CompanyContext
{
    /**
     * Id công ty của người dùng đang đăng nhập.
     *
     * Ưu tiên giá trị đã ghi ở session; phiên cũ chưa có thì suy lại từ phòng ban đang
     * chọn, cuối cùng mới lấy công ty mặc định.
     */
    public static function currentId(): ?int
    {
        $fromSession = session('user')['company_id'] ?? null;

        if ($fromSession) {
            return (int) $fromSession;
        }

        $deptId = session('user')['selected_department_id'] ?? null;

        return $deptId ? self::resolveForDepartment((int) $deptId) : self::defaultId();
    }

    /** Tên công ty đang làm việc, để hiển thị trên tiêu đề / lời dẫn màn hình. */
    public static function currentName(): ?string
    {
        return self::name(self::currentId());
    }

    /** Công ty mặc định = công ty còn hoạt động có id nhỏ nhất. */
    public static function defaultId(): ?int
    {
        $id = DB::table('companies')->where('status_id', 1)->min('id');

        return $id ? (int) $id : null;
    }

    /** Công ty của một phòng ban; phòng chưa gắn thì trả công ty mặc định. */
    public static function resolveForDepartment(?int $departmentId): ?int
    {
        if (! $departmentId) {
            return self::defaultId();
        }

        $companyId = DB::table('deparments')->where('id', $departmentId)->value('company_id');

        return $companyId ? (int) $companyId : self::defaultId();
    }

    /** Tên công ty theo id. */
    public static function name(?int $companyId): ?string
    {
        if (! $companyId) {
            return null;
        }

        return DB::table('companies')->where('id', $companyId)->value('name');
    }

    /**
     * Id các phòng ban thuộc một công ty.
     *
     * Trả null khi $companyId rỗng để bên gọi giữ nguyên hành vi "không giới hạn phạm vi"
     * (dùng cho CLI / seed / phiên chưa có công ty). Công ty có thật nhưng chưa có phòng
     * ban nào thì trả mảng rỗng - nghĩa là không có tồn để cộng.
     *
     * @return array<int>|null
     */
    public static function departmentIds(?int $companyId): ?array
    {
        if (! $companyId) {
            return null;
        }

        return DB::table('deparments')
            ->where('company_id', $companyId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /** Công ty còn hoạt động cho ô chọn ở màn Phòng Ban. */
    public static function options()
    {
        return DB::table('companies')
            ->where('status_id', 1)
            ->orderBy('name', 'asc')
            ->get();
    }

    /** Bảng tra id => tên, cho lịch sử thay đổi hiện tên thay vì con số. */
    public static function nameMap(): array
    {
        return DB::table('companies')->pluck('name', 'id')->all();
    }
}
