<?php

namespace App\Http\Controllers\Pages\Statistic;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\MaterData\ConsumptionObjectController;
use App\Support\MaterialPeriodicRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * THỐNG KÊ - THỐNG KÊ CẤP PHÁT VẬT TƯ
 *
 * Trong một khoảng thời gian, mỗi ĐỐI TƯỢNG (consumption_objects: thiết bị, máy...) đã tiêu
 * thụ những vật tư nào, bao nhiêu, cho mục đích gì.
 *
 * Nguồn số liệu là số THỰC CẤP chứ không phải số đề nghị: mỗi lần kho cấp phát ghi một dòng
 * material_exports (type = export, gắn request_item_id), ngày cấp = created_at của dòng đó.
 * Đối tượng + mục đích đọc từ dòng đề nghị (material_request_items.consumption_object_id,
 * purpose) mà người lập khai ở modal Tạo Đề Nghị Cấp Phát Vật Tư.
 *
 * Một thiết bị có thể có nhiều dòng consumption_objects (mỗi tần suất bảo trì một dòng), nên
 * thống kê gộp theo (loại, mã) đối tượng. Dòng đề nghị chưa gắn đối tượng gom vào nhóm
 * "Chưa gắn đối tượng" để tổng số không bị hụt.
 *
 * Chỉ đọc, luôn trong phạm vi phòng ban đang chọn ở topNAV.
 */
class MaterialIssueStatisticController extends Controller
{
    private const PERMISSION = 'export_material_view';

    /** Giá trị bộ lọc đối tượng cho các dòng chưa gắn đối tượng. */
    public const NO_OBJECT = 'none';

    public function index(Request $request)
    {
        abort_unless(user_can(self::PERMISSION), 403);

        $period = $this->period($request);
        $objectFilter = (string) $request->query('object', '');
        $typeFilter = (string) $request->query('object_type', '');
        $selectedObject = ctype_digit($objectFilter)
            ? DB::table('consumption_objects')->where('id', (int) $objectFilter)->first(['id', 'type', 'code', 'name'])
            : null;

        $rows = $this->issueRows($period, $objectFilter, $selectedObject, $typeFilter);

        session()->put(['title' => 'THỐNG KÊ - THỐNG KÊ CẤP PHÁT VẬT TƯ']);

        return view('pages.statistic.MaterialIssue.list', [
            'period' => $period,
            'presets' => $this->presets(),
            'objectFilter' => $objectFilter,
            'selectedObject' => $selectedObject,
            'typeFilter' => $typeFilter,
            'objectTypes' => ConsumptionObjectController::typeLabels(),
            'groups' => $this->summarize($rows),
            'rows' => $rows,
        ]);
    }

    /** Ô chọn Đối Tượng ở bộ lọc (select2 AJAX, 30 dòng mỗi trang). */
    public function objects(Request $request)
    {
        abort_unless(user_can(self::PERMISSION), 403);

        $found = MaterialPeriodicRequest::objectSearch(
            (string) $request->q,
            $request->type ?: null,
            null,
            [],
            30,
            max(0, ((int) $request->page - 1) * 30)
        );

        // Thống kê gộp mọi tần suất của cùng một thiết bị nên mỗi mã chỉ hiện một lần
        return response()->json([
            'results' => collect($found['items'])->unique(fn ($object) => $object->type.'|'.$object->code)->map(fn ($object) => [
                'id' => (int) $object->id,
                'text' => $object->code.' - '.$object->name,
                'hint' => trim(($object->type_label ?? '').($object->location ? ' · '.$object->location : ''), ' ·'),
            ])->values(),
            'pagination' => ['more' => (bool) $found['more']],
        ]);
    }

    /* ==========================================================
     |  SỐ LIỆU
     ========================================================== */

    /** Từng lần cấp phát (một dòng material_exports) trong kỳ, kèm đối tượng + vật tư + mục đích. */
    private function issueRows(array $period, string $objectFilter, $selectedObject, string $typeFilter)
    {
        return DB::table('material_exports')
            ->join('material_request_items', 'material_request_items.id', '=', 'material_exports.request_item_id')
            ->join('material_request_lists', 'material_request_lists.id', '=', 'material_request_items.request_list_id')
            ->leftJoin('consumption_objects', 'consumption_objects.id', '=', 'material_request_items.consumption_object_id')
            ->leftJoin('material_categories', 'material_categories.id', '=', 'material_request_items.category_id')
            ->leftJoin('material_names', 'material_names.id', '=', 'material_categories.material_names_id')
            ->select(
                'material_exports.id',
                'material_exports.amount',
                'material_exports.code as import_code',
                'material_exports.used_by',
                'material_exports.created_at as issued_at',
                'material_request_items.category_id',
                'material_request_items.material_name as free_name',
                'material_request_items.technical_specification',
                'material_request_items.purpose',
                'material_request_items.issued_unit',
                'material_request_items.requested_unit',
                'material_request_lists.code as request_code',
                'material_request_lists.name as request_name',
                'material_categories.code as category_code',
                'material_names.name as material_name',
                'consumption_objects.type as object_type',
                'consumption_objects.code as object_code',
                'consumption_objects.name as object_name',
                'consumption_objects.location as object_location'
            )
            ->where('material_exports.department_id', $this->departmentId())
            ->where('material_exports.type', 'export')
            ->where('material_exports.status_id', 1)
            ->whereBetween('material_exports.created_at', [
                Carbon::parse($period['from'])->startOfDay(),
                Carbon::parse($period['to'])->endOfDay(),
            ])
            ->when($objectFilter === self::NO_OBJECT, fn ($query) => $query->whereNull('material_request_items.consumption_object_id'))
            // Gộp mọi tần suất của cùng một thiết bị: lọc theo (loại, mã) của đối tượng đã chọn
            ->when($selectedObject, fn ($query) => $query
                ->where('consumption_objects.type', $selectedObject->type)
                ->where('consumption_objects.code', $selectedObject->code))
            ->when($typeFilter !== '' && $objectFilter !== self::NO_OBJECT, fn ($query) => $query->where('consumption_objects.type', $typeFilter))
            ->orderBy('material_exports.created_at', 'desc')
            ->orderBy('material_exports.id', 'desc')
            ->get()
            ->map(function ($row) {
                $row->unit = $row->issued_unit ?: $row->requested_unit;
                $row->display_name = $row->category_id ? $row->material_name : $row->free_name;
                $row->object_key = $row->object_code ? $row->object_type.'|'.$row->object_code : self::NO_OBJECT;

                return $row;
            });
    }

    /**
     * Gom theo đối tượng -> vật tư (+ đơn vị, khác đơn vị thì không cộng được với nhau):
     * tổng số lượng cấp, số lần cấp, số phiếu đề nghị, các mục đích, lần cấp gần nhất.
     * Đối tượng xếp theo mã, nhóm "Chưa gắn đối tượng" đứng cuối.
     */
    private function summarize($rows): array
    {
        $typeLabels = ConsumptionObjectController::typeLabels();
        $groups = [];

        foreach ($rows as $row) {
            $group = &$groups[$row->object_key];
            $group ??= [
                'code' => $row->object_code,
                'name' => $row->object_name,
                'type' => $row->object_code ? ($typeLabels[$row->object_type] ?? $row->object_type) : null,
                'location' => $row->object_location,
                'issues' => 0,
                'items' => [],
            ];
            $group['issues']++;

            $itemKey = ($row->category_id ?: 'free:'.$row->display_name).'|'.$row->unit;
            $item = &$group['items'][$itemKey];
            $item ??= [
                'name' => $row->display_name ?: 'NA',
                'code' => $row->category_code,
                'spec' => $row->technical_specification,
                'unit' => $row->unit,
                'amount' => 0.0,
                'issues' => 0,
                'requests' => [],
                'purposes' => [],
                'last_at' => null,
            ];
            $item['amount'] += (float) $row->amount;
            $item['issues']++;
            $item['requests'][$row->request_code] = true;
            if (trim((string) $row->purpose) !== '') {
                $item['purposes'][trim($row->purpose)] = true;
            }
            $item['last_at'] = max($item['last_at'] ?? '', (string) $row->issued_at);

            unset($group, $item);
        }

        foreach ($groups as &$group) {
            uasort($group['items'], fn ($a, $b) => strcmp($a['name'], $b['name']));
            foreach ($group['items'] as &$item) {
                $item['requests'] = count($item['requests']);
                $item['purposes'] = array_keys($item['purposes']);
            }
            unset($item);
        }
        unset($group);

        uksort($groups, function ($a, $b) {
            if ($a === self::NO_OBJECT || $b === self::NO_OBJECT) {
                return $a === self::NO_OBJECT ? 1 : -1;
            }

            return strcmp($a, $b);
        });

        return $groups;
    }

    /* ==========================================================
     |  KỲ THỐNG KÊ
     ========================================================== */

    /** Từ ngày - đến ngày trên URL; mặc định tháng hiện tại. Đảo lại nếu nhập ngược. */
    private function period(Request $request): array
    {
        $from = $this->parseDate($request->query('from_date')) ?? Carbon::today()->startOfMonth();
        $to = $this->parseDate($request->query('to_date')) ?? Carbon::today()->endOfMonth();

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        return ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')];
    }

    private function parseDate($value): ?Carbon
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    /** Mốc chọn nhanh, mỗi mốc là trọn kỳ. */
    private function presets(): array
    {
        $today = Carbon::today();

        return [
            ['label' => 'Tháng này', 'from' => $today->copy()->startOfMonth(), 'to' => $today->copy()->endOfMonth()],
            ['label' => 'Tháng trước', 'from' => $today->copy()->subMonthNoOverflow()->startOfMonth(), 'to' => $today->copy()->subMonthNoOverflow()->endOfMonth()],
            ['label' => 'Quý này', 'from' => $today->copy()->startOfQuarter(), 'to' => $today->copy()->endOfQuarter()],
            ['label' => '6 tháng', 'from' => $today->copy()->subMonthsNoOverflow(5)->startOfMonth(), 'to' => $today->copy()->endOfMonth()],
            ['label' => 'Năm nay', 'from' => $today->copy()->startOfYear(), 'to' => $today->copy()->endOfYear()],
        ];
    }

    private function departmentId(): int
    {
        return (int) (session('user')['selected_department_id'] ?? 0);
    }
}
