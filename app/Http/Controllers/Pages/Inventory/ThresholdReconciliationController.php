<?php

namespace App\Http\Controllers\Pages\Inventory;

use App\Http\Controllers\Controller;
use App\Support\ActiveIngredientThreshold;
use App\Support\CompanyContext;
use App\Support\MixtureHazardThreshold;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * TỒN - ĐỐI CHIẾU NGƯỠNG PHỤ LỤC IV NĐ 24/2026/NĐ-CP
 *
 * Màn hình CHỈ ĐỌC. Phạm vi cộng tồn gói trong CÔNG TY của phòng ban đang chọn
 * (App\Support\CompanyContext::currentId()) - chỉ các phòng ban thuộc công ty đó:
 *   - NHÓM 9 (PL IV Bảng A): từng hoạt chất được danh mục hoá chất tham chiếu - tổng tồn
 *     quy ra kg (× % hàm lượng) so với active_ingredients.threshold_kg.
 *   - NHÓM 10 (PL IV Bảng B): từng hỗn hợp có hoạt chất nhóm 9 + đã tick nhóm nguy hại -
 *     tổng tồn thô quy ra kg (KHÔNG × %) so với ngưỡng THẤP NHẤT trong các nhóm đã tick.
 *   - TỔNG TỈ LỆ (Điều 33 k2 điểm b): Σ (qxᵢ / QUXᵢ) gộp cả nhóm 9 và nhóm 10; >= 1 thì
 *     cơ sở phải xây dựng Kế hoạch phòng ngừa, ứng phó sự cố hoá chất.
 *
 * Phần tính nằm ở App\Support\ActiveIngredientThreshold / App\Support\MixtureHazardThreshold
 * để khớp với cảnh báo trên Danh Mục Hoá Chất, Tồn Kho Hoá Chất và Nhập Hoá Chất.
 */
class ThresholdReconciliationController extends Controller
{
    public function index()
    {
        session()->put(['title' => 'TỒN - ĐỐI CHIẾU NGƯỠNG']);

        // Phạm vi đối chiếu gói trong công ty của phòng ban đang chọn: chỉ cộng tồn của
        // các phòng ban thuộc công ty này.
        $companyId = CompanyContext::currentId();

        $rows = collect(ActiveIngredientThreshold::onHandByIngredient(null, $companyId))
            ->map(function ($row) {
                $row->has_unconvertible = ! empty($row->unconvertible);

                if ($row->threshold_kg === null || $row->threshold_kg <= 0) {
                    $row->ratio = null;
                    $row->peak_ratio = null;
                    $row->current_level = 'no_threshold';
                    $row->level = 'no_threshold';

                    return $row;
                }

                // level = theo ĐỈNH (peak_kg / ngưỡng); ratio = theo tồn hiện tại
                ActiveIngredientThreshold::applyRatios($row, (float) $row->threshold_kg);

                return $row;
            })
            // Vượt ngưỡng lên trước, rồi theo tỉ lệ đỉnh giảm dần, cuối cùng là chưa có ngưỡng
            ->sort(function ($a, $b) {
                return (($b->peak_ratio ?? -1) <=> ($a->peak_ratio ?? -1))
                    ?: strcmp($a->ai_name, $b->ai_name);
            })
            ->values();

        // BẢNG B - hỗn hợp (chem_names) có hoạt chất Bảng A + đã tick nhóm nguy hại
        $tableBRows = collect(MixtureHazardThreshold::onHandByChemName(null, $companyId))
            ->map(function ($row) {
                $row->has_unconvertible = ! empty($row->unconvertible);

                if ($row->min_threshold_kg === null || $row->min_threshold_kg <= 0) {
                    $row->ratio = null;
                    $row->peak_ratio = null;
                    $row->current_level = 'no_threshold';
                    $row->level = 'no_threshold';

                    return $row;
                }

                MixtureHazardThreshold::applyRatios($row, (float) $row->min_threshold_kg);

                return $row;
            })
            ->sort(function ($a, $b) {
                return (($b->peak_ratio ?? -1) <=> ($a->peak_ratio ?? -1))
                    ?: strcmp($a->chem_name, $b->chem_name);
            })
            ->values();

        // -------------------------------------------------------------------------
        // ĐIỀU 33 khoản 2 điểm b - "Tổng tỉ lệ khối lượng hoá chất nguy hiểm tồn trữ
        // trên ngưỡng" gộp CẢ nhóm 9 (PL IV Bảng A) và nhóm 10 (PL IV Bảng B):
        //     Σ ( qxᵢ / QUXᵢ )
        //   qxᵢ  = khối lượng tồn trữ lớn nhất tại một thời điểm của hoá chất nguy hiểm i
        //          (lấy theo Tồn Thực Tế Cao Nhất - peak_kg).
        //   QUXᵢ = ngưỡng khối lượng tồn trữ lớn nhất quy định tại Bảng A / Bảng B.
        // Tổng >= 1  => cơ sở phải xây dựng Kế hoạch phòng ngừa, ứng phó sự cố hoá chất,
        // kể cả khi chưa có chất đơn lẻ nào chạm ngưỡng (điểm a). Chỉ cộng các hoạt chất /
        // hỗn hợp ĐÃ khai ngưỡng (peak_ratio khác null).
        // -------------------------------------------------------------------------
        $combinedRows = [];
        $sumPeakRatio = 0.0;
        $sumCurrentRatio = 0.0;

        foreach ($rows as $row) {
            if ($row->peak_ratio === null) {
                continue;
            }

            $sumPeakRatio += (float) $row->peak_ratio;
            $sumCurrentRatio += (float) $row->ratio;
            $combinedRows[] = (object) [
                'group' => 9,
                'name' => $row->ai_name,
                'sub' => $row->ai_code ?: ($row->cas_no ? 'CAS ' . $row->cas_no : null),
                'threshold_kg' => (float) $row->threshold_kg,
                'peak_kg' => $row->peak_kg,
                'total_kg' => $row->total_kg,
                'peak_ratio' => (float) $row->peak_ratio,
                'ratio' => (float) $row->ratio,
                'level' => $row->level,
            ];
        }

        foreach ($tableBRows as $row) {
            if ($row->peak_ratio === null) {
                continue;
            }

            $sumPeakRatio += (float) $row->peak_ratio;
            $sumCurrentRatio += (float) $row->ratio;
            $combinedRows[] = (object) [
                'group' => 10,
                'name' => $row->chem_name,
                'sub' => $row->strictest_group ? ('nhóm nguy hại ' . $row->strictest_group) : null,
                'threshold_kg' => (float) $row->min_threshold_kg,
                'peak_kg' => $row->peak_kg,
                'total_kg' => $row->total_kg,
                'peak_ratio' => (float) $row->peak_ratio,
                'ratio' => (float) $row->ratio,
                'level' => $row->level,
            ];
        }

        // Đóng góp lớn nhất lên đầu
        usort($combinedRows, fn($a, $b) => $b->peak_ratio <=> $a->peak_ratio);

        $warnRatio = ActiveIngredientThreshold::warnRatio();
        $combinedLevel = $sumPeakRatio >= 1.0
            ? ActiveIngredientThreshold::LEVEL_EXCEEDED
            : ($sumPeakRatio >= $warnRatio
                ? ActiveIngredientThreshold::LEVEL_WARN
                : ActiveIngredientThreshold::LEVEL_OK);

        return view('pages.inventory.ThresholdReconciliation.list', [
            'rows' => $rows,
            'companyName' => CompanyContext::name($companyId),
            'summary' => [
                'exceeded' => $rows->where('level', ActiveIngredientThreshold::LEVEL_EXCEEDED)->count(),
                'warn' => $rows->where('level', ActiveIngredientThreshold::LEVEL_WARN)->count(),
                'ok' => $rows->where('level', ActiveIngredientThreshold::LEVEL_OK)->count(),
                'no_threshold' => $rows->where('level', 'no_threshold')->count(),
            ],
            'tableBRows' => $tableBRows,
            'tableBSummary' => [
                'exceeded' => $tableBRows->where('level', MixtureHazardThreshold::LEVEL_EXCEEDED)->count(),
                'warn' => $tableBRows->where('level', MixtureHazardThreshold::LEVEL_WARN)->count(),
                'ok' => $tableBRows->where('level', MixtureHazardThreshold::LEVEL_OK)->count(),
            ],
            'warnPercent' => (int) round(ActiveIngredientThreshold::warnRatio() * 100),
            // Điều 33 k2 điểm b - tổng tỉ lệ tồn trữ trên ngưỡng gộp nhóm 9 + nhóm 10
            'combined' => [
                'rows' => $combinedRows,
                'sum_peak_ratio' => $sumPeakRatio,
                'sum_current_ratio' => $sumCurrentRatio,
                'level' => $combinedLevel,
            ],
            // Số hoạt chất nhóm 9 (PL IV bảng A) đã khai ngưỡng nhưng chưa gắn vào tên hoá chất nào
            'unlinkedIngredients' => DB::table('active_ingredients')
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('active_ingredient_classifications as aic')
                        ->whereColumn('aic.active_ingredients_id', 'active_ingredients.id')
                        ->where('aic.appendix', 'IV')
                        ->where('aic.table_ref', 'A');
                })
                ->where('status_id', 1)
                ->where('app_status', 'approved')
                ->whereNotNull('threshold_kg')
                ->whereNotIn('id', function ($query) {
                    $query->select('active_ingredients_id')
                        ->from('chem_name_active_ingredient');
                })
                ->orderBy('name')
                ->pluck('name'),
        ]);
    }

    /**
     * JSON cho modal "Chi tiết đối chiếu Ngưỡng Tồn Trữ PL IV" trên màn hình này.
     *
     * Giống modal của Danh Mục Hoá Chất nhưng khoá theo đối tượng của từng bảng:
     *   - table=A, id = active_ingredients.id  (một hoạt chất Bảng A)
     *   - table=B, id = chem_names.id          (một hỗn hợp Bảng B)
     *
     * Trả về toàn bộ dữ liệu đã tạo nên hai con số hiển thị trên bảng:
     *   - "Tồn thực tế" : các mã xuất nhập còn tồn (quy ra kg) + phần chưa quy đổi được.
     *   - "Tồn cao nhất": diễn biến từng chứng từ theo ngày, luỹ kế kg, đánh dấu dòng đạt đỉnh.
     * Phạm vi cộng tồn gói trong công ty của phòng ban đang chọn.
     */
    public function thresholdDetail(Request $request)
    {
        $table = strtoupper((string) $request->query('table', 'A')) === 'B' ? 'B' : 'A';
        $id = (int) $request->query('id');
        $companyId = CompanyContext::currentId();

        if ($table === 'A') {
            $rows = ActiveIngredientThreshold::onHandByIngredient(null, $companyId, true);
            $row = $rows[$id] ?? null;

            if (! $row) {
                return response()->json(['ok' => false, 'reason' => 'Không tìm thấy hoạt chất.']);
            }

            $this->applyLevel($row, 'A');

            return response()->json([
                'ok' => true,
                'category_code' => $row->ai_code ?: '—',
                'chem_name' => $row->ai_name,
                'warn_percent' => (int) round(ActiveIngredientThreshold::warnRatio() * 100),
                'tableA' => [$this->thresholdDetailPayload($row, 'A')],
                'tableB' => null,
            ]);
        }

        $rows = MixtureHazardThreshold::onHandByChemName(null, $companyId, true);
        $row = $rows[$id] ?? null;

        if (! $row) {
            return response()->json(['ok' => false, 'reason' => 'Không tìm thấy hỗn hợp.']);
        }

        $this->applyLevel($row, 'B');

        return response()->json([
            'ok' => true,
            'category_code' => '—',
            'chem_name' => $row->chem_name,
            'warn_percent' => (int) round(MixtureHazardThreshold::warnRatio() * 100),
            'tableA' => [],
            'tableB' => $this->thresholdDetailPayload($row, 'B'),
        ]);
    }

    /** Gắn ratio (tồn hiện tại) + peak_ratio (đỉnh) + level (theo đỉnh) cho một dòng tồn. */
    private function applyLevel($row, string $table): void
    {
        $threshold = $table === 'A' ? $row->threshold_kg : $row->min_threshold_kg;

        if ($threshold === null || $threshold <= 0) {
            $row->ratio = null;
            $row->peak_ratio = null;
            $row->current_level = 'no_threshold';
            $row->level = 'no_threshold';

            return;
        }

        if ($table === 'A') {
            ActiveIngredientThreshold::applyRatios($row, (float) $threshold);
        } else {
            MixtureHazardThreshold::applyRatios($row, (float) $threshold);
        }
    }

    /** Gom một dòng đánh giá ngưỡng (Bảng A hoặc B) thành mảng đã format sẵn cho modal. */
    private function thresholdDetailPayload($row, string $table): array
    {
        $num = fn($v) => rtrim(rtrim(number_format((float) $v, 3, '.', ','), '0'), '.') ?: '0';
        $signed = fn($v) => ((float) $v > 0 ? '+' : '') . $num($v);
        $qty = fn($v, $u) => (abs((float) $v) < 1e-9 ? '—' : $num($v) . ($u ? ' ' . $u : ''));
        $typeLabels = ['import' => 'Nhập', 'export' => 'Xuất', 'cancel' => 'Huỷ bỏ', 'balancing' => 'Cân đối'];
        // Nhãn cho mức theo ĐỈNH (đã từng đạt) và mức theo tồn HIỆN TẠI
        $peakLabels = [
            'ok' => 'Trong ngưỡng',
            'warn' => 'Đã sắp chạm ngưỡng',
            'exceeded' => 'Đã vượt ngưỡng',
            'no_threshold' => 'Chưa có ngưỡng',
        ];
        $curLabels = [
            'ok' => 'Trong ngưỡng',
            'warn' => 'Sắp chạm ngưỡng',
            'exceeded' => 'Vượt ngưỡng',
            'no_threshold' => 'Chưa có ngưỡng',
        ];

        // Số mã xuất nhập gộp lại thành hai con số hiển thị
        $onhandCount = count($row->onhand_rows);
        $importRefs = [];
        foreach ($row->timeline as $t) {
            if ($t->type === 'import') {
                $importRefs[$t->ref] = true;
            }
        }

        return [
            'table' => $table,
            'title' => $table === 'A' ? $row->ai_name : $row->chem_name,
            'subtitle' => $table === 'A'
                ? ($row->ai_code ?: '')
                : ('Nhóm nguy hại: ' . implode(', ', $row->hazard_labels) . ' · ngưỡng thấp nhất nhóm ' . ($row->strictest_group ?? '—')),
            'threshold_kg' => $num($table === 'A' ? $row->threshold_kg : $row->min_threshold_kg),
            'total_kg' => $num($row->total_kg),
            'peak_kg' => $num($row->peak_kg),
            'peak_date' => $row->peak_date ? \Carbon\Carbon::parse($row->peak_date)->format('d/m/Y') : '—',
            'ratio_percent' => isset($row->ratio) && $row->ratio !== null ? (int) round($row->ratio * 100) . '%' : '—',
            'peak_ratio_percent' => isset($row->peak_ratio) && $row->peak_ratio !== null ? (int) round($row->peak_ratio * 100) . '%' : '—',
            'level' => $row->level ?? 'ok',
            'level_label' => $peakLabels[$row->level ?? 'ok'] ?? ($row->level ?? ''),
            'current_level' => $row->current_level ?? ($row->level ?? 'ok'),
            'current_level_label' => $curLabels[$row->current_level ?? 'ok'] ?? ($row->current_level ?? ''),
            'has_unconvertible' => ! empty($row->unconvertible),
            // "Tồn thực tế = tổng tồn còn lại của N mã xuất nhập"
            'onhand_count' => $onhandCount,
            // "Tồn cao nhất dựng lại từ M chứng từ, trong đó K lần nhập"
            'timeline_count' => count($row->timeline),
            'import_count' => count($importRefs),
            'by_department' => array_map(fn($d) => [
                'department_name' => $d->department_name,
                'kg' => $num($d->kg),
            ], $row->by_department),
            'onhand_rows' => array_map(fn($o) => [
                'ref' => $o->ref ?? '',
                'date' => isset($o->date) && $o->date ? \Carbon\Carbon::parse($o->date)->format('d/m/Y') : '—',
                'category_code' => $o->category_code,
                'chem_name' => $o->chem_name ?? '',
                'department_name' => $o->department_name,
                'imported' => $qty($o->imported ?? 0, $o->unit_short),
                'balanced' => isset($o->balanced) ? ($signed($o->balanced) === '0' ? '—' : $signed($o->balanced) . ($o->unit_short ? ' ' . $o->unit_short : '')) : '—',
                'exported' => $qty($o->exported ?? 0, $o->unit_short),
                'on_hand' => $num($o->on_hand_unit) . ($o->unit_short ? ' ' . $o->unit_short : ''),
                'on_hand_kg' => $num($o->on_hand_kg),
            ], $row->onhand_rows),
            'unconvertible' => array_map(fn($u) => [
                'category_code' => $u->category_code,
                'chem_name' => $u->chem_name ?? '',
                'reason' => $u->reason,
            ], $row->unconvertible),
            'timeline' => array_map(fn($t) => [
                'date' => \Carbon\Carbon::parse($t->date)->format('d/m/Y'),
                'type_label' => $typeLabels[$t->type] ?? $t->type,
                'ref' => $t->ref,
                'category_code' => $t->category_code,
                'department_name' => $t->department_name,
                'delta' => $signed($t->delta_unit) . ($t->unit_short ? ' ' . $t->unit_short : ''),
                'delta_kg' => $signed($t->delta_kg),
                'running_kg' => $num($t->running_kg),
                'is_peak' => (bool) $t->is_peak,
            ], $row->timeline),
        ];
    }
}
