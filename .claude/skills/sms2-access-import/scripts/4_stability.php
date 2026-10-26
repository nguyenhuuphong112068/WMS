<?php
/**
 * BƯỚC 4 - ĐÁNH GIÁ HẠN DÙNG CHUẨN THỨ CẤP (DOD) từ dbo_T_DS_DOD_<BP> / dbo_T_DOD_item_<BP> (SMS 2.0).
 *
 * Ghi vào tab "Đánh giá hạn dùng CTC" của Tồn Kho Chất Chuẩn (standard_stability_assessment_list / _item / _histories).
 *   - 1 dòng DS_DOD = 1 phiếu, gắn vào ống standard_imports.code = Maxn của đúng phòng (bước 3 phải chạy trước).
 *     Ống không có trên WMS (đã dùng hết nên bước 3 không nạp) -> bỏ qua, ghi báo cáo.
 *   - start_date giữ nguyên; chu kỳ (assessment_period) = khoảng cách SoThang hay gặp nhất giữa 2 mốc liền nhau
 *     (Access không lưu chu kỳ), phiếu 1 mốc -> 3 tháng.
 *   - Mốc: name "N Tháng" (0 -> "Ban đầu"), timepoint = SoThang, due_date = due_date Access (không tính lại).
 *     SMS 2.0 chỉ lưu mốc từ lúc dùng phần mềm nên phiếu cũ có thể bắt đầu ở mốc 9, 12... tháng - giữ nguyên.
 *   - testings = tests tách dấu phẩy (mảnh "6S", "8R Isomer" nối lại vào chỉ tiêu trước: "Giới hạn 4R, 6S, 8R Isomer";
 *     sửa gõ nhầm SMS2_DOD_TYPOS) -> object {name, issued = DaCapPhat, note = "Cấp phát ngày … (SMS 2.0)"}. Tên chỉ
 *     tiêu phải có trong Dữ liệu gốc "Chỉ tiêu kiểm" (purposes, so theo collation) - thiếu thì tạo, để sửa mốc trên
 *     WMS không bị chặn "Chỉ tiêu kiểm không có trong Dữ Liệu Gốc". issured = DaCapPhat.
 *   - HoanThanh = 1 -> status "Đạt" (Access không có Không Đạt; result "NA" vẫn coi là Đạt + báo cáo), result = result
 *     Access, done_at = ngày LỚN NHẤT đọc được trong Done_date (chuỗi tự do "Định lượng: 07.08.26, TCLQ: 14.08.26";
 *     bỏ ngày trước start_date), không đọc được -> update_at; chuỗi Done_date gốc + GhiChu / GhiChu2 (bỏ "NA", "1. NA")
 *     -> note (≤ 255).
 *   - Trạng thái phiếu tính như StandardStabilityController: chưa mốc nào xong = Ban Đầu, xong một phần = Đang Đánh
 *     Giá, xong hết = Hoàn Thành. Access "Kết Thúc Đánh Giá" mà còn mốc chưa làm -> Dừng Đánh Giá (lý do ghi rõ).
 *   - created_by = SMS2['actor'] (nhận ra khi chạy lại); người tạo / ngày tạo / trạng thái gốc ghi vào nhật ký "Lập phiếu".
 *
 * CHẠY LẠI = xoá phiếu do script tạo (sms2_delete_stability) + chỉ tiêu kiểm bước 4 tạo, rồi dựng lại. Bước 3 chạy lại
 * cũng xoá phiếu (ống bị dựng lại) -> chạy lại bước 4 sau bước 3.
 * php 4_stability.php <thư mục JSON> [--dry]          Ra: <thư mục>/4_stability_report.csv
 */
require __DIR__ . '/_boot.php';

use App\Support\DataMasterHistory;
use Illuminate\Support\Facades\DB;

$dir = rtrim($argv[1] ?? '', '/\\');
$dry = in_array('--dry', $argv, true);
$ACTOR = SMS2['actor'];
$now = now();

const SMS2_DOD_PURPOSE_NOTE = 'Nạp từ SMS 2.0 (Chỉ tiêu đánh giá hạn dùng DOD).';
const SMS2_DOD_TYPOS = ['DĐịnh lượng' => 'Định lượng'];
const SMS2_DOD_DEFAULT_PERIOD = 3;

/** "Định lượng, Giới hạn 4R, 6S, 8R Isomer" -> ['Định lượng', 'Giới hạn 4R, 6S, 8R Isomer'] */
function sms2_dod_tests($s): array
{
    $out = [];
    foreach (preg_split('/\s*,\s*/u', sms2_clean($s)) as $part) {
        if ($part === '') {
            continue;
        }
        if ($out && preg_match('/^\d+[A-Z](\s|$)/u', $part)) {
            $out[count($out) - 1] .= ', ' . $part;
            continue;
        }
        $out[] = SMS2_DOD_TYPOS[$part] ?? $part;
    }
    return array_values(array_unique($out));
}

/** Mọi ngày trong chuỗi tự do: dd.mm.yy, dd/mm/yyyy, dd-mm-yyyy... -> ['Y-m-d', ...] */
function sms2_dod_dates($s): array
{
    preg_match_all('#\b(\d{1,2})[./-](\d{1,2})[./-](\d{4}|\d{2})\b#', (string) $s, $m, PREG_SET_ORDER);
    $out = [];
    foreach ($m as [, $d, $mo, $y]) {
        $y = strlen($y) === 2 ? 2000 + (int) $y : (int) $y;
        if (checkdate((int) $mo, (int) $d, $y)) {
            $out[] = sprintf('%04d-%02d-%02d', $y, $mo, $d);
        }
    }
    return $out;
}

/** Bỏ "NA", "1. NA" ... khỏi ghi chú nhiều dòng của Access. */
function sms2_dod_note($s): ?string
{
    $lines = [];
    foreach (preg_split('/\r\n|\r|\n/', (string) $s) as $line) {
        $line = sms2_clean($line);
        if ($line === '' || sms2_nullish(preg_replace('/^\d+\.\s*/u', '', $line)) === null) {
            continue;
        }
        $lines[] = $line;
    }
    return $lines ? implode('; ', $lines) : null;
}

function sms2_dod_cut(?string $v, int $len): ?string
{
    return $v === null ? null : (mb_strlen($v) > $len ? mb_substr($v, 0, $len - 1) . '…' : $v);
}

$lists = sms2_json("$dir/dod_list.json");
$items = [];
foreach (sms2_json("$dir/dod_item.json") as $it) {
    if (! $it['Huy']) {
        $items[$it['Dept'] . '|' . $it['Maxn']][] = $it;
    }
}

$stats = [];
$inc = function ($k, $n = 1) use (&$stats) { $stats[$k] = ($stats[$k] ?? 0) + $n; };
$report = [];

DB::beginTransaction();
try {
    /* ---------- 0. Dọn dữ liệu script tạo trước đó ---------- */
    sms2_delete_stability($argv, $inc);
    $dodPurposes = DB::table('datamaster_histories')->where('table_name', 'purposes')
        ->where('change_note', SMS2_DOD_PURPOSE_NOTE)->pluck('record_id');
    foreach ($dodPurposes as $pid) {
        DB::table('datamaster_histories')->where('table_name', 'purposes')->where('record_id', $pid)->delete();
        $inc('cleanup_purposes', DB::table('purposes')->where('id', $pid)->delete());
    }

    /* ---------- 1. Chỉ tiêu kiểm ---------- */
    $purposeName = [];
    $resolvePurpose = function (string $name) use (&$purposeName, $ACTOR, $now, $inc): string {
        $k = mb_strtolower($name);
        if (! isset($purposeName[$k])) {
            $found = DB::table('purposes')->where('name', $name)->value('name'); // collation: hoa/thường, dấu
            if ($found === null) {
                $id = DB::table('purposes')->insertGetId(['name' => mb_substr($name, 0, 255), 'status_id' => 1,
                    'created_by' => $ACTOR, 'created_at' => $now, 'updated_at' => $now]);
                DataMasterHistory::record('purposes', $id, 'Thêm mới', SMS2_DOD_PURPOSE_NOTE, ['name' => 'Tên chỉ tiêu kiểm']);
                $inc('purposes_new');
                $found = $name;
            }
            $purposeName[$k] = $found;
        }
        return $purposeName[$k];
    };

    /* ---------- 2. Phiếu + mốc ---------- */
    foreach ($lists as $l) {
        $bp = $l['Dept'];
        $deptId = SMS2['departments'][$bp]['id'] ?? null;
        $rows = $items[$bp . '|' . $l['Maxn']] ?? [];
        usort($rows, fn ($a, $b) => $a['SoThang'] <=> $b['SoThang'] ?: $a['ID'] <=> $b['ID']);
        $review = [];

        $import = $deptId ? DB::table('standard_imports')->where('department_id', $deptId)->where('code', $l['Maxn'])->first() : null;
        if (! $import) {
            $inc('skip_no_import');
            $report[] = [$bp, $l['ID'], $l['Maxn'], $l['MaChuan'], $l['start_date'], $l['status'], count($rows), '', '',
                'BỎ QUA: ống không có trên WMS (đã dùng hết - bước 3 không nạp)'];
            continue;
        }
        if ($import->group_code !== 'CTC') {
            $review[] = 'ống không thuộc nhóm CTC (' . $import->group_code . ')';
        }
        if (! $import->stability_batch) {
            $review[] = 'ống chưa tick Lô đánh giá hạn dùng';
        }

        // Chu kỳ = bước SoThang hay gặp nhất
        $gaps = [];
        for ($i = 1; $i < count($rows); $i++) {
            $gap = (int) $rows[$i]['SoThang'] - (int) $rows[$i - 1]['SoThang'];
            if ($gap > 0) {
                $gaps[] = $gap;
            }
        }
        $period = max(1, min(60, (int) (sms2_mode($gaps) ?? SMS2_DOD_DEFAULT_PERIOD)));

        $startDate = substr((string) $l['start_date'], 0, 10);
        $listId = DB::table('standard_stability_assessment_list')->insertGetId([
            'import_id' => $import->id,
            'start_date' => $startDate,
            'status' => 'Ban Đầu',
            'assessment_period' => $period,
            'note' => sms2_dod_cut(sms2_dod_note($l['Ghichu']), 255),
            'created_by' => $ACTOR,
            'created_at' => $l['NgayTao'] ?: $now,
        ]);
        $inc('lists');

        $done = 0;
        foreach ($rows as $it) {
            $tests = [];
            foreach (sms2_dod_tests($it['tests']) as $name) {
                $tests[] = [
                    'name' => $resolvePurpose($name),
                    'issued' => (bool) $it['DaCapPhat'],
                    'note' => $it['DaCapPhat'] && $it['NgayCapPhat']
                        ? 'Cấp phát ngày ' . date('d/m/Y', strtotime($it['NgayCapPhat'])) . ' (SMS 2.0)' : null,
                ];
            }

            $finished = (bool) $it['HoanThanh'];
            $doneAt = null;
            if ($finished) {
                $dates = array_filter(sms2_dod_dates($it['Done_date']), fn ($d) => $d >= $startDate);
                $doneAt = $dates ? max($dates) : ($it['update_at'] ? substr($it['update_at'], 0, 10) : null);
                if (! $dates) {
                    $review[] = 'T' . $it['SoThang'] . ': không đọc được ngày thực hiện "' . $it['Done_date'] . '"';
                }
                if (sms2_nullish($it['result']) === null) {
                    $review[] = 'T' . $it['SoThang'] . ': kết quả "' . $it['result'] . '" - ghi Đạt';
                }
                $done++;
            }

            $note = array_filter([
                $finished && sms2_clean($it['Done_date']) !== '' ? 'Ngày thực hiện SMS 2.0: ' . sms2_clean($it['Done_date']) : null,
                sms2_dod_note($it['GhiChu']),
                sms2_dod_note($it['GhiChu2']),
            ]);

            DB::table('standard_stability_assessment_item')->insert([
                'standard_stability_assessment_list_id' => $listId,
                'name' => (int) $it['SoThang'] === 0 ? 'Ban đầu' : (int) $it['SoThang'] . ' Tháng',
                'timepoint' => (int) $it['SoThang'],
                'due_date' => $it['due_date'] ? substr($it['due_date'], 0, 10) : null,
                'testings' => $tests ? json_encode($tests, JSON_UNESCAPED_UNICODE) : null,
                'issured' => (int) (bool) $it['DaCapPhat'],
                'done_at' => $doneAt,
                'result' => $finished ? (sms2_nullish($it['result']) ?? 'Đạt') : null,
                'status' => $finished ? 'Đạt' : 'Ban Đầu',
                'note' => sms2_dod_cut($note ? implode(' · ', $note) : null, 255),
                'created_by' => $ACTOR,
                'created_at' => $l['NgayTao'] ?: $now,
                'updated_by' => $finished ? ($it['update_by'] ?: $ACTOR) : $ACTOR,
                'updated_at' => $it['update_at'] ?: $now,
            ]);
            $inc('items');
            $inc($finished ? 'items_done' : 'items_waiting');
        }

        $total = count($rows);
        $ended = mb_stripos((string) $l['status'], 'Kết Thúc') !== false;
        $stopReason = null;
        if ($total === 0 || $done === 0) {
            $status = 'Ban Đầu';
        } elseif ($done >= $total) {
            $status = 'Hoàn Thành';
        } elseif ($ended) {
            $status = 'Dừng Đánh Giá';
            $stopReason = 'Kết thúc đánh giá trên SMS 2.0 (còn ' . ($total - $done) . ' mốc chưa làm).';
        } else {
            $status = 'Đang Đánh Giá';
        }
        if ($ended && $status === 'Ban Đầu') {
            $review[] = 'SMS 2.0 "Kết Thúc Đánh Giá" nhưng chưa mốc nào hoàn thành';
        }
        if ($total === 0) {
            $review[] = 'phiếu không có mốc';
        }

        DB::table('standard_stability_assessment_list')->where('id', $listId)->update([
            'status' => $status,
            'stop_reason' => $stopReason,
            'stopped_at' => $stopReason ? ($l['NgayTao'] ?: $now) : null,
            'stopped_by' => $stopReason ? $ACTOR : null,
        ]);
        $inc('status_' . $status);

        DB::table('standard_stability_assessment_histories')->insert([
            'standard_stability_assessment_list_id' => $listId,
            'action' => 'Lập phiếu',
            'new_values' => 'Bắt đầu ' . date('d/m/Y', strtotime($startDate)) . ', chu kỳ ' . $period . ' tháng',
            'note' => sms2_dod_cut('Chuyển từ SMS 2.0 (DS_DOD_' . $bp . ' ID ' . $l['ID'] . '): ống ' . $l['Maxn'] . ', mã chuẩn '
                . $l['MaChuan'] . ', ' . $total . ' mốc (' . $done . ' đã xong). Người tạo gốc: ' . ($l['NguoiTao'] ?: '—')
                . ' ' . date('d/m/Y H:i', strtotime($l['NgayTao'] ?: 'now')) . '. Trạng thái gốc: ' . $l['status'] . '.', 500),
            'created_by' => $ACTOR,
            'created_at' => $now,
        ]);

        $report[] = [$bp, $l['ID'], $l['Maxn'], $l['MaChuan'], $startDate, $l['status'], $total, $done, $status . ' · chu kỳ ' . $period . ' th',
            implode('; ', $review)];
    }

    $orphans = 0;
    $listKeys = array_flip(array_map(fn ($l) => $l['Dept'] . '|' . $l['Maxn'], $lists));
    foreach ($items as $k => $rows) {
        if (! isset($listKeys[$k])) {
            $orphans += count($rows);
        }
    }
    if ($orphans) {
        $inc('items_orphan_skipped', $orphans);
    }

    sms2_write_csv("$dir/4_stability_report.csv",
        ['Bộ phận', 'ID DS_DOD', 'Mã ống chuẩn', 'Mã chuẩn Access', 'Ngày bắt đầu', 'Trạng thái SMS 2.0', 'Số mốc', 'Mốc đã xong',
         'WMS', 'Cần rà'], $report);

    ksort($stats);
    foreach ($stats as $k => $v) {
        printf("  %-28s %12s\n", $k, $v);
    }

    if ($dry) {
        DB::rollBack();
        echo "DRY RUN - đã rollback. Báo cáo: $dir/4_stability_report.csv\n";
    } else {
        DB::commit();
        echo "ĐÃ GHI. Báo cáo: $dir/4_stability_report.csv\n";
    }
} catch (Throwable $e) {
    DB::rollBack();
    fwrite(STDERR, 'LỖI: ' . $e->getMessage() . ' @' . $e->getFile() . ':' . $e->getLine() . "\n");
    exit(1);
}
