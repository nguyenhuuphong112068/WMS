<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Gán phân loại (đối tượng + nhóm nghiệp vụ như thẻ trang chủ) cho thông báo gửi trước khi
 * thanh Thông báo thành lịch sử của trang chủ, để mọi dòng cùng một kiểu hiển thị.
 *
 * Thông báo "đang chờ bạn ký / chờ cấp / chờ QA" cũ nay đã có dòng tương ứng do trang chủ ghi
 * (App\Support\ReviewHistory) nên đánh dấu đã đọc - giữ lại làm lịch sử, không xoá.
 */
return new class extends Migration
{
    private const PENDING_PUSHES = ['Chờ ký duyệt', 'Chờ cấp phát', 'Trình ký', 'Đề nghị hiệu chuẩn vật tư'];

    public function up(): void
    {
        $rows = DB::table('notifications')
            ->whereNull('review_type')
            ->whereNull('review_object')
            ->get(['id', 'activity_type', 'url']);

        foreach ($rows as $row) {
            [$object, $group] = $this->classify((string) $row->activity_type, (string) $row->url);

            if ($object || $group) {
                DB::table('notifications')->where('id', $row->id)->update(['review_object' => $object, 'review_group' => $group]);
            }
        }

        $pendingIds = DB::table('notifications')->whereNull('review_type')->whereIn('activity_type', self::PENDING_PUSHES)->pluck('id');

        if ($pendingIds->isNotEmpty()) {
            DB::table('notification_recipients')->whereIn('notification_id', $pendingIds)->where('is_read', 0)
                ->update(['is_read' => 1, 'read_at' => now()]);
        }
    }

    public function down(): void
    {
        DB::table('notifications')->whereNull('review_type')->update(['review_object' => null, 'review_group' => null]);
    }

    /** [đối tượng, nhóm nghiệp vụ] suy từ loại thông báo + đường dẫn. */
    private function classify(string $type, string $url): array
    {
        $object = match (true) {
            str_contains($url, 'chemicalEstimate') => 'hc',
            str_contains($url, 'standardEstimate') => 'cc',
            str_contains($url, 'material') => 'vt',
            default => null,
        };

        $group = match (true) {
            str_contains($url, 'Estimate') || $type === 'Dự trù theo chu kỳ' => 'estimate',
            str_contains($type, 'hiệu chuẩn') => 'quality',
            str_contains($url, 'tab=vpp') => 'vpp',
            str_contains($url, 'tab=transfer') => 'transfer',
            str_contains($url, 'materialExport') => 'internal',
            default => null,
        };

        return [$object, $group];
    }
};
