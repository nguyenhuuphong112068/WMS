<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * MÃ ỐNG CHUẨN - một chỗ duy nhất định nghĩa cách sinh mã.
 *
 *      deparments.shortName + mã nhóm chuẩn + yy + mm + số thứ tự (4 chữ số)
 *      QC1              +   VKN         + 26 + 01 + 0036   ->  QC1VKN26010036
 *
 * - Số thứ tự đếm trong NĂM, riêng cho từng cặp (phòng ban, nhóm chuẩn). Sang năm
 *   mới bắt đầu lại từ 0001, còn trong năm thì tháng nào cũng đếm tiếp.
 * - Số thứ tự lấy từ hai cột standard_imports.seq_year / seq_no chứ không cắt chuỗi
 *   từ code: mã nhóm dài ngắn khác nhau (CN 2 ký tự, IMP 3 ký tự) nên cắt chuỗi
 *   theo vị trí cố định là sai ngay.
 *
 * Màn hình Nhập gọi next() bên trong transaction lúc lưu, và previews() để hiện mã
 * xem trước trên form - hai đường đi nhưng cùng một công thức.
 */
class StandardCode
{
    public const TABLE = 'standard_imports';

    /** Số thứ tự trong mã ống chuẩn: 4 chữ số, bắt đầu từ 0001. */
    public const SEQ_LENGTH = 4;

    /**
     * Ghép mã ống chuẩn từ các phần đã biết.
     *
     * Tách riêng khỏi next() để phần xem trước và phần lưu thật không thể lệch nhau.
     */
    public static function build(string $shortName, string $groupCode, int $year, int $month, int $seq): string
    {
        return $shortName
            .$groupCode
            .substr(str_pad((string) $year, 4, '0', STR_PAD_LEFT), -2)
            .str_pad((string) $month, 2, '0', STR_PAD_LEFT)
            .str_pad((string) $seq, self::SEQ_LENGTH, '0', STR_PAD_LEFT);
    }

    /**
     * Số thứ tự kế tiếp của một cặp (phòng ban, nhóm chuẩn) trong một năm.
     *
     * Phiếu nhập chỉ khoá chứ không xoá nên số thứ tự không bị cấp lại.
     */
    public static function nextSeq(int $departmentId, string $groupCode, int $year): int
    {
        $max = DB::table(self::TABLE)
            ->where('department_id', $departmentId)
            ->where('group_code', $groupCode)
            ->where('seq_year', $year)
            ->max('seq_no');

        return (int) $max + 1;
    }

    /**
     * Mã ống chuẩn kế tiếp kèm phần số thứ tự để lưu xuống DB.
     *
     * Gọi trong transaction của lúc lưu: hai người nhập cùng lúc thì không được ra
     * hai mã giống nhau.
     *
     * @return array{code: string, seq_year: int, seq_no: int}
     */
    public static function next(int $departmentId, string $shortName, string $groupCode, string $importedDate): array
    {
        $date = \Carbon\Carbon::parse($importedDate);
        $year = (int) $date->year;
        $seq = self::nextSeq($departmentId, $groupCode, $year);

        return [
            'code' => self::build($shortName, $groupCode, $year, (int) $date->month, $seq),
            'seq_year' => $year,
            'seq_no' => $seq,
        ];
    }

    /**
     * Mã dự kiến của từng nhóm chuẩn để hiện trước trên form: [mã nhóm => mã ống].
     *
     * Chỉ để xem, mã thật vẫn sinh lúc lưu. Gom một truy vấn cho cả phòng ban rồi
     * tính trong PHP để không phải hỏi DB theo từng nhóm.
     *
     * @param  string  $importedDate  ngày nhập đang chọn trên form (quyết định yy/mm)
     */
    public static function previews(int $departmentId, string $shortName, string $importedDate): array
    {
        $date = \Carbon\Carbon::parse($importedDate);
        $year = (int) $date->year;

        $maxByGroup = DB::table(self::TABLE)
            ->select('group_code', DB::raw('MAX(seq_no) as max_seq'))
            ->where('department_id', $departmentId)
            ->where('seq_year', $year)
            ->groupBy('group_code')
            ->pluck('max_seq', 'group_code');

        $previews = [];

        foreach (config('standard.groups') as $key => $group) {
            $seq = (int) ($maxByGroup[$group['code']] ?? 0) + 1;

            $previews[$key] = self::build($shortName, $group['code'], $year, (int) $date->month, $seq);
        }

        return $previews;
    }

    /** Chuỗi JSON mảng mã nhóm chuẩn -> mảng mã, bỏ mã không còn khai báo trong config. */
    public static function decodeGroups($value): array
    {
        if (! $value) {
            return [];
        }

        $decoded = json_decode($value, true);

        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_intersect(array_keys(config('standard.groups')), $decoded));
    }

    /** Tên hiển thị của một mã nhóm chuẩn, ví dụ PRS -> "Chuẩn Chính (PRS)". */
    public static function groupLabel(?string $key): string
    {
        $group = config('standard.groups')[$key] ?? null;

        return $group ? $group['name'].' ('.$group['short'].')' : ($key ?: '—');
    }

    /** Mã nhóm dùng trong mã ống chuẩn của một khoá nhóm, ví dụ IMPRS -> "IMP". */
    public static function groupCode(?string $key): ?string
    {
        return config('standard.groups')[$key]['code'] ?? null;
    }
}
