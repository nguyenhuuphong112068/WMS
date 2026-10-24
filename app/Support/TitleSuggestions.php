<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\DB;

/**
 * GỢI Ý TIÊU ĐỀ ĐÃ DÙNG
 *
 * Ô "Tiêu Đề" của các loại đề nghị / danh sách dự trù cho chọn lại tiêu đề phòng mình đã
 * đặt ở những lần trước (datalist - vẫn gõ mới được). Lấy theo phòng đang chọn, mỗi tiêu đề
 * một lần, dùng gần nhất đứng trước. Giao diện: pages/shared/titleDatalist.blade.php.
 *
 * Query Builder thuần, không Eloquent.
 */
class TitleSuggestions
{
    public const LIMIT = 50;

    /** Phiếu tự sinh theo chu kỳ đặt tên "<tiêu đề danh sách> - kỳ dd/mm/yyyy" (MaterialPeriodicRequest) - không gợi ý lại */
    private const GENERATED_LIKE = '% - kỳ __/__/____';

    /**
     * @param  string  $table  Bảng phiếu (có cột department_id)
     * @param  string  $column  Cột tiêu đề (title / name)
     * @param  Closure|null  $scope  Lọc thêm theo loại phiếu, nhận Query Builder
     * @return array<string>
     */
    public static function recent(string $table, int $departmentId, string $column = 'title', ?Closure $scope = null): array
    {
        return DB::table($table)
            ->select($column)
            ->selectRaw('MAX(id) as last_id')
            ->where('department_id', $departmentId)
            ->whereNotNull($column)
            ->where($column, '<>', '')
            ->where($column, 'not like', self::GENERATED_LIKE)
            ->when($scope, $scope)
            ->groupBy($column)
            ->orderByDesc('last_id')
            ->limit(self::LIMIT)
            ->pluck($column)
            ->all();
    }
}
