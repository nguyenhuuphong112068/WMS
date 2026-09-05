<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * LỊCH SỬ THAY ĐỔI DỮ LIỆU GỐC
 *
 * Mọi màn hình trong nhóm "Dữ Liệu Gốc" cùng ghi vào một bảng datamaster_histories
 * (xem migration create_datamaster_histories_table).
 *
 * Cách dùng trong Controller:
 *
 *   private const TABLE  = 'units';
 *   private const FIELDS = ['short_name' => 'Ký hiệu', 'name' => 'Tên đơn vị tính'];
 *
 *   // Sau khi thêm / sửa / khoá / duyệt:
 *   DataMasterHistory::record(self::TABLE, $id, 'Thêm mới', 'Khai báo mới.', self::FIELDS);
 *
 *   // Mô tả nội dung vừa đổi để đưa vào change_note:
 *   $note = DataMasterHistory::note(self::FIELDS, $current, $payload);
 *
 *   // Số lần đã đổi của từng dòng, dùng cho badge trên bảng:
 *   'historyCounts' => DataMasterHistory::counts(self::TABLE)
 *
 * Cột khoá ngoại (department_id, unit_group...) truyền thêm $maps để ảnh chụp và mô tả
 * hiện ra tên đọc được thay vì con số: ['department_id' => [1 => 'Phòng QA', ...]].
 */
class DataMasterHistory
{
    public const TABLE = 'datamaster_histories';

    /** Lần khai báo đầu tiên - không tính là một lần thay đổi nên không lên badge. */
    public const ACTION_CREATE = 'Thêm mới';

    private const APP_STATUS = [
        'pending' => 'Chờ duyệt',
        'approved' => 'Đã duyệt',
        'rejected' => 'Từ chối',
    ];

    /**
     * Ghi một lần thay đổi: tự đọc lại bản ghi trong bảng gốc để chụp giá trị mới nhất.
     * Gọi SAU khi đã insert / update xong.
     */
    public static function record(string $table, int $recordId, string $action, ?string $note, array $fields, array $maps = []): void
    {
        $row = DB::table($table)->where('id', $recordId)->first();

        if (! $row) {
            return;
        }

        self::write($table, $recordId, $action, $note, self::snapshot($fields, $row, $maps));
    }

    /**
     * Ghi một lần thay đổi với ảnh chụp tự dựng sẵn.
     * Dùng cho thao tác Xoá - lúc đó bản ghi không còn để đọc lại nữa.
     */
    public static function write(string $table, int $recordId, string $action, ?string $note, array $snapshot = []): void
    {
        DB::table(self::TABLE)->insert([
            'table_name' => $table,
            'record_id' => $recordId,
            'action' => $action,
            'snapshot' => json_encode($snapshot, JSON_UNESCAPED_UNICODE),
            'change_note' => $note,
            'created_by' => \App\Support\Signer::actor(),
            'created_at' => now(),
        ]);
    }

    /**
     * Số lần thay đổi của từng dòng trong một bảng: [record_id => số lần].
     *
     * Bỏ dòng "Thêm mới" vì đó là lúc khai báo chứ không phải một lần sửa, nên badge
     * chỉ hiện khi bản ghi thật sự đã bị đổi ít nhất một lần.
     */
    public static function counts(string $table)
    {
        return DB::table(self::TABLE)
            ->select('record_id', DB::raw('COUNT(*) as times'))
            ->where('table_name', $table)
            ->where('action', '<>', self::ACTION_CREATE)
            ->groupBy('record_id')
            ->pluck('times', 'record_id');
    }

    /**
     * Số lần thay đổi của nhiều bảng cùng lúc: ['<bảng>-<id>' => số lần].
     * Dùng cho màn hình gộp nhiều bảng vào một trang như Định Khu.
     */
    public static function countsOf(array $tables): array
    {
        return DB::table(self::TABLE)
            ->select('table_name', 'record_id', DB::raw('COUNT(*) as times'))
            ->whereIn('table_name', $tables)
            ->where('action', '<>', self::ACTION_CREATE)
            ->groupBy('table_name', 'record_id')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->table_name . '-' . $row->record_id => (int) $row->times])
            ->all();
    }

    /** Lịch sử của một bản ghi, mới nhất nằm trên cùng - đúng dạng modal xem lịch sử cần. */
    public static function rows(string $table, int $recordId): array
    {
        return DB::table(self::TABLE)
            ->where('table_name', $table)
            ->where('record_id', $recordId)
            ->orderBy('id', 'desc')
            ->get()
            ->map(fn ($row) => [
                'action' => $row->action,
                'change_note' => $row->change_note,
                'created_by' => $row->created_by ?: 'NA',
                'created_at' => $row->created_at ? Carbon::parse($row->created_at)->format('d/m/Y H:i') : '',
                'snapshot' => json_decode($row->snapshot ?? '', true) ?: [],
            ])
            ->values()
            ->all();
    }

    /** Ảnh chụp giá trị bản ghi dạng [Nhãn => Giá trị đọc được]. */
    public static function snapshot(array $fields, $row, array $maps = []): array
    {
        $snapshot = [];

        if (property_exists($row, 'created_by') || property_exists($row, 'created_at')) {
            $snapshot['Người tạo'] = self::label($row->created_by ?? null);
            $snapshot['Ngày tạo'] = ($row->created_at ?? null)
                ? Carbon::parse($row->created_at)->format('d/m/Y H:i')
                : '—';
        }

        foreach ($fields as $column => $title) {
            $snapshot[$title] = self::label($row->$column ?? null, $maps[$column] ?? null);
        }

        if (property_exists($row, 'app_status')) {
            $snapshot['Trạng thái duyệt'] = self::APP_STATUS[$row->app_status] ?? self::label($row->app_status);
        }

        $snapshot['Trạng thái sử dụng'] = self::activeLabel($row);

        return $snapshot;
    }

    /** Mô tả nội dung đã đổi theo dạng "Trường: cũ -> mới", ghép bằng dấu gạch đứng. */
    public static function note(array $fields, $current, array $payload, array $maps = []): string
    {
        $parts = [];

        foreach ($fields as $column => $title) {
            if (! array_key_exists($column, $payload)) {
                continue;
            }

            $old = $current->$column ?? null;
            $new = $payload[$column];

            if ((string) $old === (string) $new) {
                continue;
            }

            $parts[] = $title . ': '
                . self::label($old, $maps[$column] ?? null) . ' -> '
                . self::label($new, $maps[$column] ?? null);
        }

        return implode(' | ', $parts);
    }

    /** Mô tả một lần đổi trạng thái sử dụng (Khoá / Mở khoá). */
    public static function statusNote($oldActive, $newActive): string
    {
        return 'Trạng thái sử dụng: '
            . ($oldActive ? 'Hoạt động' : 'Đã khoá') . ' -> '
            . ($newActive ? 'Hoạt động' : 'Đã khoá');
    }

    /** Mô tả một lần đổi trạng thái duyệt (Phê duyệt / Từ chối duyệt). */
    public static function approvalNote(?string $oldStatus, string $newStatus): string
    {
        return 'Trạng thái duyệt: '
            . (self::APP_STATUS[$oldStatus] ?? 'Chờ duyệt') . ' -> '
            . (self::APP_STATUS[$newStatus] ?? $newStatus);
    }

    /** Giá trị hiển thị của một cột: tra bảng nhãn nếu có, rỗng thì hiện dấu gạch. */
    private static function label($value, ?array $map = null): string
    {
        if ($map !== null) {
            $value = $map[$value] ?? null;
        }

        $value = is_bool($value) ? ($value ? '1' : '0') : (string) ($value ?? '');

        return trim($value) === '' ? '—' : $value;
    }

    /**
     * Trạng thái sử dụng của bản ghi. Các bảng dữ liệu gốc đang dùng 3 tên cột khác nhau
     * cho cùng một ý nghĩa (status_id ở bảng mới, active / isActive ở hai bảng cũ).
     */
    private static function activeLabel($row): string
    {
        foreach (['status_id', 'active', 'isActive'] as $column) {
            if (property_exists($row, $column)) {
                return $row->$column ? 'Hoạt động' : 'Đã khoá';
            }
        }

        return '—';
    }
}
