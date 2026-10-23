<?php

namespace App\Http\Controllers\Pages\MaterData;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\DataMasterHistory;
use App\Support\ZoneType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * ĐỊNH KHU - CẤU TRÚC KHO
 *
 * Gộp thao tác khai báo Kệ/Tủ -> Cột -> Tầng -> Vị Trí vào một lưới: mỗi cột là một khối,
 * hàng là tầng, ô là vị trí. Năm bảng warehouses / shelves / columns / tiers / locations
 * vẫn giữ nguyên, chỉ gộp chỗ nhập liệu. Mã các cấp con tự sinh theo mã cấp cha:
 *   Kệ 01/01 -> Cột 01/01/01 -> Tầng 01/01/01/01 -> Vị trí 01/01/01/01/01
 *
 * Vị trí là bản ghi thật (lô nhập, lịch sử, nhãn in đều trỏ vào id/mã), nên khi thu nhỏ
 * ta KHOÁ ô thừa chứ không xoá, và khi nới lại thì mở đúng dải đã khoá. Ô đang có lô hàng
 * thì không cho thu nhỏ/gỡ qua.
 */
class ZoneStructureController extends Controller
{
    private const MAX_COLUMNS = 30;
    private const MAX_TIERS = 30;
    private const MAX_LOCATIONS = 200;
    // Một lượt lưu có thể sinh vài nghìn vị trí, chặn ngưỡng để transaction không phình quá lớn.
    private const MAX_CELLS = 20000;
    // Số kệ tối đa tạo cùng lúc theo một cấu trúc.
    private const MAX_BATCH = 20;

    /** Các bảng lô hàng có location_id - vị trí đang có lô còn hiệu lực coi là "có hàng". */
    private const LOT_TABLES = ['chemical_imports', 'standard_imports', 'material_imports'];

    public static function limits(): array
    {
        return [
            'maxColumns' => self::MAX_COLUMNS,
            'maxTiers' => self::MAX_TIERS,
            'maxLocations' => self::MAX_LOCATIONS,
            'maxBatch' => self::MAX_BATCH,
        ];
    }

    /** Danh sách kho/phòng kèm số kệ, số vị trí và số vị trí đang có hàng để chọn trên thẻ. */
    public function warehouses()
    {
        $departmentId = $this->departmentId();

        $warehouses = DB::table('warehouses')
            ->where('department_id', $departmentId)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'status_id']);

        $shelfCounts = DB::table('shelves')
            ->whereIn('warehouse_id', $warehouses->pluck('id'))
            ->where('status_id', 1)
            ->select('warehouse_id', DB::raw('COUNT(*) as total'))
            ->groupBy('warehouse_id')
            ->pluck('total', 'warehouse_id');

        $stats = $this->locationStats('warehouse_id', $warehouses->pluck('id')->all());

        foreach ($warehouses as $warehouse) {
            $warehouse->shelves = (int) ($shelfCounts[$warehouse->id] ?? 0);
            $warehouse->total = $stats[$warehouse->id]['total'] ?? 0;
            $warehouse->used = $stats[$warehouse->id]['used'] ?? 0;
        }

        return response()->json(['warehouses' => $warehouses]);
    }

    public function saveWarehouse(Request $request)
    {
        $id = $request->input('id');

        if (! user_can($id ? 'materData_common_update' : 'materData_common_create')) {
            return $this->fail('Bạn không có quyền thực hiện thao tác này.', 403);
        }

        $validator = Validator::make($request->all(), [
            'code' => ['required', 'string', 'max:40', Rule::unique('warehouses', 'code')->ignore($id)],
            'name' => ['required', 'string', 'max:255'],
        ], [
            'code.required' => 'Chưa nhập mã kho/phòng.',
            'code.max' => 'Mã kho/phòng tối đa 40 ký tự.',
            'code.unique' => 'Mã kho/phòng này đã được dùng.',
            'name.required' => 'Chưa nhập tên kho/phòng.',
        ]);

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first());
        }

        $code = trim((string) $request->input('code'));
        $name = trim((string) $request->input('name'));
        // Phân loại định khu khai ở từng vị trí, kho/phòng chỉ có mã + tên
        $fields = ['code' => 'Mã kho/phòng', 'name' => 'Tên kho/phòng'];
        $maps = [];

        if ($id) {
            $warehouse = DB::table('warehouses')
                ->where('id', $id)
                ->where('department_id', $this->departmentId())
                ->first();

            if (! $warehouse) {
                return $this->fail('Không tìm thấy kho/phòng.', 404);
            }

            $payload = ['code' => $code, 'name' => $name];
            $note = DataMasterHistory::note($fields, $warehouse, $payload, $maps);

            if ($note === '') {
                return response()->json(['ok' => true, 'warehouse_id' => (int) $id, 'message' => 'Không có thay đổi.']);
            }

            DB::table('warehouses')->where('id', $id)->update($payload + [
                'updated_by' => $this->actor(),
                'updated_at' => now(),
            ]);

            DataMasterHistory::record('warehouses', (int) $id, 'Cập nhật', $note, $fields, $maps);
            AuditTrialController::log('Cập nhật', 'warehouses', $id, $warehouse->code . ' - ' . $warehouse->name, $code . ' - ' . $name);
        } else {
            $id = DB::table('warehouses')->insertGetId([
                'code' => $code,
                'name' => $name,
                'department_id' => $this->departmentId(),
                'status_id' => 1,
                'created_by' => $this->actor(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DataMasterHistory::record('warehouses', $id, 'Thêm mới', 'Khai báo mới kho/phòng: ' . $code . ' - ' . $name . '.', $fields, $maps);
            AuditTrialController::log('Thêm mới', 'warehouses', $id, 'NA', 'Thêm kho/phòng: ' . $code . ' - ' . $name);
        }

        return response()->json([
            'ok' => true,
            'warehouse_id' => (int) $id,
            'message' => 'Đã lưu kho/phòng ' . $code . '.',
        ]);
    }

    /** Kệ/tủ của một kho kèm số cột, số tầng, số vị trí; gợi ý sẵn mã cho kệ tiếp theo. */
    public function shelves(Request $request)
    {
        $warehouse = $this->warehouse($request->input('warehouse_id'));

        if (! $warehouse) {
            return $this->fail('Không tìm thấy kho/phòng.', 404);
        }

        $shelves = DB::table('shelves')
            ->where('department_id', $this->departmentId())
            ->where('warehouse_id', $warehouse->id)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'status_id']);

        $ids = $shelves->pluck('id')->all();

        $columnCounts = DB::table('columns')
            ->whereIn('shelf_id', $ids)
            ->where('status_id', 1)
            ->select('shelf_id', DB::raw('COUNT(*) as total'))
            ->groupBy('shelf_id')
            ->pluck('total', 'shelf_id');

        $tierCounts = DB::table('tiers')
            ->whereIn('shelf_id', $ids)
            ->where('status_id', 1)
            ->select('shelf_id', DB::raw('COUNT(*) as total'))
            ->groupBy('shelf_id')
            ->pluck('total', 'shelf_id');

        $stats = $this->locationStats('shelf_id', $ids);

        foreach ($shelves as $shelf) {
            $shelf->columns = (int) ($columnCounts[$shelf->id] ?? 0);
            $shelf->tiers = (int) ($tierCounts[$shelf->id] ?? 0);
            $shelf->total = $stats[$shelf->id]['total'] ?? 0;
            $shelf->used = $stats[$shelf->id]['used'] ?? 0;
        }

        return response()->json([
            'shelves' => $shelves,
            'suggest' => $this->suggestShelfCode($warehouse, $shelves->pluck('code')->all()),
        ]);
    }

    /** Sửa nhanh mã + tên kệ từ thẻ kệ; đổi mã thì mã cột/tầng/vị trí con đổi theo. */
    public function saveShelf(Request $request)
    {
        if (! user_can('materData_common_update')) {
            return $this->fail('Bạn không có quyền thực hiện thao tác này.', 403);
        }

        $validator = Validator::make($request->all(), [
            'id' => ['required', 'integer'],
            'code' => ['required', 'string', 'max:40'],
            'name' => ['required', 'string', 'max:255'],
        ], [
            'code.required' => 'Chưa nhập mã kệ/tủ.',
            'code.max' => 'Mã kệ/tủ tối đa 40 ký tự.',
            'name.required' => 'Chưa nhập tên kệ/tủ.',
        ]);

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first());
        }

        $shelf = $this->shelf($request->input('id'));

        if (! $shelf) {
            return $this->fail('Không tìm thấy kệ/tủ.', 404);
        }

        $code = trim((string) $request->input('code'));
        $name = trim((string) $request->input('name'));
        $fields = ['code' => 'Mã kệ/tủ', 'name' => 'Tên kệ/tủ'];
        $maps = [];
        $payload = ['code' => $code, 'name' => $name];
        $note = DataMasterHistory::note($fields, $shelf, $payload, $maps);

        if ($note === '') {
            return response()->json(['ok' => true, 'shelf_id' => (int) $shelf->id, 'message' => 'Không có thay đổi.']);
        }

        $plan = $this->renamePlan($shelf, $code);

        if (is_string($plan)) {
            return $this->fail($plan);
        }

        $renamed = DB::transaction(function () use ($shelf, $plan, $payload, $note, $fields, $maps) {
            $renamed = $this->applyRename($plan);

            DB::table('shelves')->where('id', $shelf->id)->update($payload + [
                'updated_by' => $this->actor(),
                'updated_at' => now(),
            ]);

            DataMasterHistory::record('shelves', (int) $shelf->id, 'Cập nhật',
                $note . ($renamed ? ' (đổi mã ' . $renamed . ' cột/tầng/vị trí con theo mã kệ)' : ''), $fields, $maps);
            AuditTrialController::log('Cập nhật', 'shelves', $shelf->id, $shelf->code . ' - ' . $shelf->name,
                $payload['code'] . ' - ' . $payload['name']);

            return $renamed;
        });

        return response()->json([
            'ok' => true,
            'shelf_id' => (int) $shelf->id,
            'message' => 'Đã lưu kệ/tủ ' . $code . ($renamed ? ', đổi mã ' . $renamed . ' cột/tầng/vị trí theo mã mới.' : '.'),
        ]);
    }

    /** Đổi phân loại định khu và/hoặc loại lưu trữ cho các vị trí đang chọn trên lưới của một kệ. */
    public function saveLocations(Request $request)
    {
        if (! user_can('materData_common_update')) {
            return $this->fail('Bạn không có quyền thực hiện thao tác này.', 403);
        }

        $validator = Validator::make($request->all(), [
            'shelf_id' => ['required', 'integer'],
            'ids' => ['required', 'array', 'min:1', 'max:' . self::MAX_CELLS],
            'ids.*' => ['integer'],
            'set_type' => ['boolean'],
            'item_type' => ['nullable', Rule::in(array_keys(ZoneController::LOCATION_TYPES))],
            'set_zone' => ['boolean'],
            'zone_type' => ['nullable', Rule::in(array_keys(ZoneType::TYPES))],
        ], [
            'ids.required' => 'Chưa chọn vị trí nào.',
            'item_type.in' => 'Loại lưu trữ được chọn không hợp lệ.',
            'zone_type.in' => 'Phân loại định khu không hợp lệ.',
        ]);

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first());
        }

        $shelf = $this->shelf($request->input('shelf_id'));

        if (! $shelf) {
            return $this->fail('Không tìm thấy kệ/tủ.', 404);
        }

        $payload = [];
        if ($request->boolean('set_type')) {
            $payload['item_type'] = $request->input('item_type') ?: null;
        }
        if ($request->boolean('set_zone')) {
            $payload['zone_type'] = $request->input('zone_type') ?: null;
        }

        if (! $payload) {
            return $this->fail('Chưa chọn thay đổi nào: hãy chọn phân loại hoặc loại lưu trữ.');
        }

        $rows = DB::table('locations')
            ->where('shelf_id', $shelf->id)
            ->whereIn('id', $request->input('ids'))
            ->get(['id', 'code', 'zone_type', 'item_type']);

        if ($rows->isEmpty()) {
            return $this->fail('Không tìm thấy vị trí nào của kệ/tủ này.', 404);
        }

        $fields = array_intersect_key([
            'zone_type' => 'Phân loại định khu', 'item_type' => 'Loại lưu trữ',
        ], $payload);
        $maps = [
            'item_type' => ['' => 'Dùng chung'] + ZoneController::LOCATION_TYPES,
            'zone_type' => ZoneType::historyMap(),
        ];
        $actor = $this->actor();
        $now = now();

        $changed = DB::transaction(function () use ($rows, $payload, $fields, $maps, $actor, $now, $shelf) {
            $changed = 0;

            foreach ($rows as $row) {
                $note = DataMasterHistory::note($fields, $row, $payload, $maps);
                if ($note === '') {
                    continue;
                }

                DB::table('locations')->where('id', $row->id)->update($payload + [
                    'updated_by' => $actor,
                    'updated_at' => $now,
                ]);
                DataMasterHistory::record('locations', (int) $row->id, 'Cập nhật', $note, $fields, $maps);
                $changed++;
            }

            if ($changed) {
                $parts = [];
                if (array_key_exists('zone_type', $payload)) {
                    $parts[] = 'phân loại: ' . ZoneType::label($payload['zone_type']);
                }
                if (array_key_exists('item_type', $payload)) {
                    $parts[] = 'loại lưu trữ: ' . (ZoneController::LOCATION_TYPES[$payload['item_type']] ?? 'Dùng chung');
                }
                AuditTrialController::log('Cập nhật vị trí', 'locations', $shelf->id, $shelf->code,
                    $changed . ' vị trí của kệ ' . $shelf->code . ' - ' . implode(', ', $parts));
            }

            return $changed;
        });

        $updated = DB::table('locations')
            ->whereIn('id', $rows->pluck('id'))
            ->get(['id', 'zone_type', 'item_type'])
            ->map(fn ($row) => $this->cellAttrs($row));

        return response()->json([
            'ok' => true,
            'message' => $changed ? 'Đã cập nhật ' . $changed . ' vị trí.' : 'Các vị trí đã chọn không có gì thay đổi.',
            'locations' => $updated->values(),
        ]);
    }

    /** Thuộc tính hiển thị của một ô: phân loại, loại lưu trữ và màu của phân loại (mỗi phân loại một màu). */
    private function cellAttrs($row): array
    {
        return [
            'id' => (int) $row->id,
            'zone_type' => $row->zone_type,
            'item_type' => $row->item_type,
            'shown' => isset(ZoneType::DEFAULT_COLORS[$row->zone_type]) ? ZoneType::color($row->zone_type) : null,
        ];
    }

    /**
     * Kế hoạch đổi mã kệ: mọi cột/tầng/vị trí của kệ có mã bắt đầu bằng "<mã cũ>/" đổi tiền tố
     * sang mã mới. Trả về chuỗi lỗi nếu mã mới (hoặc mã con sinh ra) đã bị chiếm.
     */
    private function renamePlan($shelf, string $newCode)
    {
        $plan = ['columns' => [], 'tiers' => [], 'locations' => []];
        $old = (string) $shelf->code;

        if ($newCode === '' || $newCode === $old) {
            return $plan;
        }

        if (DB::table('shelves')->where('code', $newCode)->where('id', '!=', $shelf->id)->exists()) {
            return 'Mã kệ/tủ ' . $newCode . ' đã tồn tại. Hãy chọn mã khác.';
        }

        $labels = ['columns' => 'cột', 'tiers' => 'tầng', 'locations' => 'vị trí'];

        foreach (array_keys($plan) as $table) {
            $rows = DB::table($table)->where('shelf_id', $shelf->id)->get(['id', 'code']);
            $oldCodes = [];

            foreach ($rows as $row) {
                if (str_starts_with((string) $row->code, $old . '/')) {
                    $plan[$table][$row->id] = $newCode . substr($row->code, strlen($old));
                    $oldCodes[] = $row->code;
                }
            }

            if (array_intersect($plan[$table], $oldCodes)) {
                return 'Mã mới ' . $newCode . ' chồng lên mã cũ của chính kệ này. Hãy chọn mã khác.';
            }

            foreach (array_chunk(array_values($plan[$table]), 1000) as $chunk) {
                $clash = DB::table($table)->whereIn('code', $chunk)->limit(5)->pluck('code');

                if ($clash->isNotEmpty()) {
                    return 'Không đổi được mã kệ: mã ' . $labels[$table] . ' ' . $clash->implode(', ')
                        . ' đã tồn tại. Hãy chọn mã khác hoặc sửa bản ghi trùng ở tab Danh sách định khu.';
                }
            }
        }

        return $plan;
    }

    /** Ghi kế hoạch đổi mã; trả về số bản ghi con đã đổi. Gọi bên trong transaction. */
    private function applyRename(array $plan): int
    {
        $count = 0;
        $actor = $this->actor();
        $now = now();

        foreach ($plan as $table => $codes) {
            foreach ($codes as $id => $code) {
                DB::table($table)->where('id', $id)->update(['code' => $code, 'updated_by' => $actor, 'updated_at' => $now]);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Cấu trúc đầy đủ của một kệ: cột -> tầng -> ô nào có hàng, ô nào đang khoá.
     * Lưới dựa vào đó để chặn kéo thu nhỏ qua ô đang có hàng.
     */
    public function detail(Request $request)
    {
        $shelf = $this->shelf($request->input('shelf_id'));

        if (! $shelf) {
            return $this->fail('Không tìm thấy kệ/tủ.', 404);
        }

        $columns = DB::table('columns')
            ->where('shelf_id', $shelf->id)
            ->whereNotNull('position')
            ->orderBy('position')
            ->get(['id', 'code', 'name', 'position', 'status_id']);

        $tiers = DB::table('tiers')
            ->where('shelf_id', $shelf->id)
            ->whereNotNull('position')
            ->orderBy('position')
            ->get(['id', 'code', 'name', 'position', 'status_id', 'column_id'])
            ->groupBy('column_id');

        $locations = DB::table('locations')
            ->where('shelf_id', $shelf->id)
            ->whereNotNull('position')
            ->get(['id', 'code', 'tier_id', 'position', 'status_id', 'zone_type', 'item_type'])
            ->groupBy('tier_id');

        $busy = $this->busyIds($locations->flatten()->pluck('id')->all());

        foreach ($columns as $column) {
            $column->tiers = ($tiers[$column->id] ?? collect())->map(function ($tier) use ($locations, $busy) {
                $cells = $locations[$tier->id] ?? collect();
                $tier->max = (int) $cells->where('status_id', 1)->max('position');
                $tier->busy = [];
                $tier->off = [];
                $tier->codes = [];
                $tier->attrs = [];

                foreach ($cells as $cell) {
                    $position = (int) $cell->position;
                    $tier->codes[$position] = $cell->code;
                    $tier->attrs[$position] = $this->cellAttrs($cell);

                    // Ô đã khoá thì coi là khoá dù còn lô cũ trỏ vào - khoá lại lần nữa không đổi gì
                    if ((int) $cell->status_id !== 1) {
                        $tier->off[] = $position;
                    } elseif (isset($busy[$cell->id])) {
                        $tier->busy[] = $position;
                    }
                }

                $tier->last_busy = $tier->busy ? max($tier->busy) : 0;

                return $tier;
            })->values();
        }

        return response()->json(['shelf' => $shelf, 'columns' => $columns]);
    }

    /** Báo sớm mã kệ đã có ngay lúc gõ, thay vì để khai xong cả lưới mới bị từ chối. */
    public function checkCode(Request $request)
    {
        $code = trim((string) $request->input('code'));

        return response()->json([
            'code' => $code,
            'taken' => $code !== '' && DB::table('shelves')->where('code', $code)->exists(),
        ]);
    }

    /**
     * Tạo mới hoặc sửa cấu trúc kệ/tủ theo lưới người dùng vừa khai. Khi tạo mới có thể gửi
     * kèm danh sách `shelves` (mã + tên) để tạo cùng lúc nhiều kệ có chung một cấu trúc.
     */
    public function apply(Request $request)
    {
        $shelfId = $request->input('shelf_id');

        if (! user_can($shelfId ? 'materData_common_update' : 'materData_common_create')) {
            return $this->fail('Bạn không có quyền thực hiện thao tác này.', 403);
        }

        $batch = ! $shelfId && is_array($request->input('shelves'));
        // Tạo nhiều kệ: mỗi kệ trong `shelves` mang lưới `columns` riêng; còn lại lưới nằm ở gốc request.
        $grid = $batch ? 'shelves.*.columns' : 'columns';

        $validator = Validator::make($request->all(), [
            'warehouse_id' => ['required', 'integer'],
            'code' => [$shelfId || $batch ? 'nullable' : 'required', 'string', 'max:40'],
            'name' => [$batch ? 'nullable' : 'required', 'string', 'max:255'],
            'shelves' => ['nullable', 'array', 'min:1', 'max:' . self::MAX_BATCH],
            'shelves.*.code' => ['required', 'string', 'max:40'],
            'shelves.*.name' => ['required', 'string', 'max:255'],
            $grid => ['required', 'array', 'min:1', 'max:' . self::MAX_COLUMNS],
            $grid . '.*.position' => ['required', 'integer', 'min:1', 'max:99'],
            $grid . '.*.name' => ['nullable', 'string', 'max:100'],
            $grid . '.*.tiers.*.name' => ['nullable', 'string', 'max:100'],
            $grid . '.*.tiers' => ['required', 'array', 'min:1', 'max:' . self::MAX_TIERS],
            $grid . '.*.tiers.*.position' => ['required', 'integer', 'min:1', 'max:99'],
            $grid . '.*.tiers.*.max' => ['required', 'integer', 'min:1', 'max:' . self::MAX_LOCATIONS],
        ], [
            'code.required' => 'Chưa nhập mã kệ/tủ.',
            'code.max' => 'Mã kệ/tủ tối đa 40 ký tự.',
            'name.required' => 'Chưa nhập tên kệ/tủ.',
            'shelves.max' => 'Tối đa ' . self::MAX_BATCH . ' kệ/tủ mỗi lượt tạo.',
            'shelves.*.code.required' => 'Chưa nhập mã cho một kệ/tủ trong danh sách.',
            'shelves.*.code.max' => 'Mã kệ/tủ tối đa 40 ký tự.',
            'shelves.*.name.required' => 'Chưa nhập tên cho một kệ/tủ trong danh sách.',
            $grid . '.required' => 'Kệ/tủ phải có ít nhất một cột.',
            $grid . '.max' => 'Tối đa ' . self::MAX_COLUMNS . ' cột mỗi kệ/tủ.',
            $grid . '.*.tiers.required' => 'Mỗi cột phải có ít nhất một tầng.',
            $grid . '.*.tiers.max' => 'Tối đa ' . self::MAX_TIERS . ' tầng mỗi cột.',
            $grid . '.*.tiers.*.max.min' => 'Mỗi tầng phải có ít nhất 1 vị trí.',
            $grid . '.*.tiers.*.max.max' => 'Tối đa ' . self::MAX_LOCATIONS . ' vị trí mỗi tầng.',
        ]);

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first());
        }

        $warehouse = $this->warehouse($request->input('warehouse_id'));

        if (! $warehouse) {
            return $this->fail('Không tìm thấy kho/phòng.', 404);
        }

        $shelf = null;
        $existingColumns = collect();
        $existingTiers = collect();
        $existingCells = collect();

        if ($shelfId) {
            $shelf = $this->shelf($shelfId);

            if (! $shelf) {
                return $this->fail('Không tìm thấy kệ/tủ.', 404);
            }

            $targets = [[
                'code' => trim((string) $request->input('code')) ?: $shelf->code,
                'name' => trim((string) $request->input('name')),
                'wanted' => $this->wantedGrid($request->input('columns')),
                'names' => $this->namesGrid($request->input('columns')),
            ]];

            // Đổi mã kệ: mã cột/tầng/vị trí con đổi tiền tố theo; kiểm trùng trước khi ghi.
            $rename = $this->renamePlan($shelf, $targets[0]['code']);
            if (is_string($rename)) {
                return $this->fail($rename);
            }

            $existingColumns = DB::table('columns')
                ->where('shelf_id', $shelf->id)->whereNotNull('position')
                ->get()->keyBy('position');
            $existingTiers = DB::table('tiers')
                ->where('shelf_id', $shelf->id)->whereNotNull('position')
                ->get()->groupBy('column_id')->map(fn ($rows) => $rows->keyBy('position'));
            $existingCells = DB::table('locations')
                ->where('shelf_id', $shelf->id)->whereNotNull('position')
                ->get(['id', 'code', 'tier_id', 'position', 'status_id'])
                ->groupBy('tier_id');

            // Các bước kiểm/ghi phía sau đọc mã con từ đây, nên áp mã mới vào bản đọc luôn.
            foreach ([[$existingColumns, 'columns'], [$existingTiers->flatten(), 'tiers'], [$existingCells->flatten(), 'locations']] as [$list, $table]) {
                foreach ($list as $row) {
                    if (isset($rename[$table][$row->id])) {
                        $row->code = $rename[$table][$row->id];
                    }
                }
            }
        } else {
            $rows = $batch
                ? $request->input('shelves')
                : [$request->only('code', 'name') + ['columns' => $request->input('columns')]];
            $targets = array_map(fn ($row) => [
                'code' => trim((string) ($row['code'] ?? '')),
                'name' => trim((string) ($row['name'] ?? '')),
                'wanted' => $this->wantedGrid($row['columns']),
                'names' => $this->namesGrid($row['columns']),
            ], array_values($rows));

            $codes = array_column($targets, 'code');
            $repeated = array_unique(array_diff_assoc($codes, array_unique($codes)));
            if ($repeated) {
                return $this->fail('Mã kệ/tủ bị lặp trong danh sách: ' . implode(', ', $repeated) . '.');
            }

            $taken = DB::table('shelves')->whereIn('code', $codes)->pluck('code');
            if ($taken->isNotEmpty()) {
                return $this->fail('Mã kệ/tủ ' . $taken->implode(', ') . ' đã tồn tại. Hãy đổi mã kệ.');
            }
        }

        $cells = array_sum(array_map(fn ($target) => array_sum(array_map('array_sum', $target['wanted'])), $targets));
        if ($cells > self::MAX_CELLS) {
            return $this->fail((count($targets) > 1 ? 'Các kệ này' : 'Kệ này') . ' sẽ có '
                . number_format($cells, 0, ',', '.') . ' vị trí, vượt mức '
                . number_format(self::MAX_CELLS, 0, ',', '.') . ' cho một lượt lưu.');
        }

        // Kiểm hết trước khi ghi bất cứ thứ gì, để không lưu được một nửa.
        if ($blocked = $this->blockedCells($targets[0]['wanted'], $existingColumns, $existingTiers, $existingCells)) {
            return $this->fail('Không thể thu nhỏ/gỡ: các ô bị loại bỏ đang có lô hàng. '
                . implode(' | ', $blocked) . '. Vui lòng chuyển hàng đi trước.');
        }

        // Dự tính mọi mã sẽ sinh ra, mã unique toàn bảng nên phải chắc chưa bị bản ghi khác chiếm.
        $newCodes = ['columns' => [], 'tiers' => [], 'locations' => []];

        foreach ($targets as $target) {
            foreach ($this->plannedCodes($target['code'], $target['wanted'], $existingColumns, $existingTiers, $existingCells) as $table => $codes) {
                array_push($newCodes[$table], ...$codes);
            }
        }

        foreach ($newCodes as $table => $codes) {
            foreach (array_chunk($codes, 1000) as $chunk) {
                $clash = DB::table($table)->whereIn('code', $chunk)->limit(5)->pluck('code');

                if ($clash->isNotEmpty()) {
                    $label = ['columns' => 'cột', 'tiers' => 'tầng', 'locations' => 'vị trí'][$table];

                    return $this->fail('Mã ' . $label . ' đã tồn tại: ' . $clash->implode(', ')
                        . '. Hãy đổi mã kệ hoặc sửa bản ghi trùng ở tab Danh sách định khu.');
                }
            }
        }

        $context = [
            'department_id' => $this->departmentId(),
            // Sửa kệ có sẵn thì cấp con mới đi theo kho của chính kệ đó
            'warehouse_id' => $shelf && $shelf->warehouse_id ? (int) $shelf->warehouse_id : (int) $warehouse->id,
            'actor' => $this->actor(),
            'now' => now(),
        ];

        $rename = $rename ?? [];

        $results = DB::transaction(function () use (
            $shelf, $targets, $rename, $existingColumns, $existingTiers, $existingCells, $context
        ) {
            $renamed = $rename ? $this->applyRename($rename) : 0;

            return array_map(fn ($target) => $this->writeShelf(
                $shelf, $target['code'], $target['name'], $target['wanted'], $target['names'],
                $existingColumns, $existingTiers, $existingCells, $context + ['renamed_codes' => $renamed]
            ), $targets);
        });

        if (count($results) === 1) {
            $message = 'Đã lưu cấu trúc kệ/tủ ' . $targets[0]['code']
                . ($results[0]['summary'] ? ': ' . $results[0]['summary'] . '.' : '.');
        } else {
            $message = 'Đã tạo ' . count($results) . ' kệ/tủ: ' . implode(', ', array_column($targets, 'code'))
                . ' (' . number_format($cells, 0, ',', '.') . ' vị trí).';
        }

        return response()->json([
            'ok' => true,
            'shelf_id' => $results[0]['shelf_id'],
            'shelf_ids' => array_column($results, 'shelf_id'),
            'message' => $message,
        ]);
    }

    /** Tên cột/tầng người dùng đặt trên lưới: ['columns' => [cột => tên], 'tiers' => [cột => [tầng => tên]]]. */
    private function namesGrid(array $columns): array
    {
        $names = ['columns' => [], 'tiers' => []];

        foreach ($columns as $column) {
            $colPos = (int) $column['position'];
            $names['columns'][$colPos] = trim((string) ($column['name'] ?? ''));

            foreach ($column['tiers'] as $tier) {
                $names['tiers'][$colPos][(int) $tier['position']] = trim((string) ($tier['name'] ?? ''));
            }
        }

        return $names;
    }

    /** Lưới người dùng gửi lên -> wanted[cột][tầng] = số vị trí, đã sắp theo vị trí. */
    private function wantedGrid(array $columns): array
    {
        $wanted = [];
        foreach ($columns as $column) {
            $tiers = [];
            foreach ($column['tiers'] as $tier) {
                $tiers[(int) $tier['position']] = (int) $tier['max'];
            }
            ksort($tiers);
            $wanted[(int) $column['position']] = $tiers;
        }
        ksort($wanted);

        return $wanted;
    }

    /** Các mã cột/tầng/vị trí chưa có mà lượt lưu này sẽ sinh ra cho một kệ. */
    private function plannedCodes(string $shelfCode, array $wanted, $existingColumns, $existingTiers, $existingCells): array
    {
        $newCodes = ['columns' => [], 'tiers' => [], 'locations' => []];

        foreach ($wanted as $colPos => $tiers) {
            $column = $existingColumns->get($colPos);
            $colCode = $column ? $column->code : $shelfCode . '/' . $this->seg($colPos);

            if (! $column) {
                $newCodes['columns'][] = $colCode;
            }

            foreach ($tiers as $tierPos => $max) {
                $tier = $column ? optional($existingTiers->get($column->id))->get($tierPos) : null;
                $tierCode = $tier ? $tier->code : $colCode . '/' . $this->seg($tierPos);

                if (! $tier) {
                    $newCodes['tiers'][] = $tierCode;
                }

                $have = $tier ? ($existingCells[$tier->id] ?? collect())->pluck('position')->map(fn ($p) => (int) $p)->flip() : collect();

                for ($p = 1; $p <= $max; $p++) {
                    if (! $have->has($p)) {
                        $newCodes['locations'][] = $tierCode . '/' . $this->seg($p);
                    }
                }
            }
        }

        return $newCodes;
    }

    /** Ghi một kệ (tạo mới khi $shelf = null) theo lưới $wanted. Gọi bên trong transaction. */
    private function writeShelf($shelf, string $shelfCode, string $name, array $wanted, array $names, $existingColumns, $existingTiers, $existingCells, array $context): array
    {
        $totals = [
            'code_changed' => 0, 'renamed_codes' => (int) ($context['renamed_codes'] ?? 0),
            'columns_added' => 0, 'columns_removed' => 0,
            'tiers_added' => 0, 'tiers_removed' => 0,
            'created' => 0, 'locked' => 0, 'unlocked' => 0, 'renamed' => 0,
        ];

        if ($shelf) {
            $shelfId = $shelf->id;
            $changes = [];

            if ($shelf->code !== $shelfCode) {
                $changes['code'] = $shelfCode;
                $totals['code_changed'] = 1;
            }
            if ($shelf->name !== $name) {
                $changes['name'] = $name;
            }
            if ((int) $shelf->status_id !== 1) {
                $changes['status_id'] = 1;
            }
            if ($changes) {
                DB::table('shelves')->where('id', $shelfId)->update($changes + [
                    'updated_by' => $context['actor'],
                    'updated_at' => $context['now'],
                ]);
            }
        } else {
            $shelfId = DB::table('shelves')->insertGetId([
                'code' => $shelfCode,
                'name' => $name,
                'department_id' => $context['department_id'],
                'warehouse_id' => $context['warehouse_id'],
                'status_id' => 1,
                'created_by' => $context['actor'],
                'created_at' => $context['now'],
                'updated_at' => $context['now'],
            ]);
        }

        foreach ($wanted as $colPos => $tiers) {
            $column = $existingColumns->get($colPos);

            $columnName = ($names['columns'][$colPos] ?? '') ?: 'Cột ' . $this->seg($colPos);

            if ($column) {
                $columnId = $column->id;
                $columnCode = $column->code;
                $this->reopen('columns', $column, $context);
                $totals['renamed'] += $this->rename('columns', $column, $names['columns'][$colPos] ?? '', $context);
            } else {
                $columnCode = $shelfCode . '/' . $this->seg($colPos);
                $columnId = DB::table('columns')->insertGetId([
                    'code' => $columnCode,
                    'name' => $columnName,
                    'department_id' => $context['department_id'],
                    'warehouse_id' => $context['warehouse_id'],
                    'shelf_id' => $shelfId,
                    'position' => $colPos,
                    'status_id' => 1,
                    'created_by' => $context['actor'],
                    'created_at' => $context['now'],
                    'updated_at' => $context['now'],
                ]);
                $totals['columns_added']++;
            }

            $oldTiers = $column ? ($existingTiers->get($column->id) ?? collect()) : collect();

            foreach ($tiers as $tierPos => $max) {
                $tier = $oldTiers->get($tierPos);

                $tierName = $names['tiers'][$colPos][$tierPos] ?? '';

                if ($tier) {
                    $tierId = $tier->id;
                    $tierCode = $tier->code;
                    $this->reopen('tiers', $tier, $context);
                    $totals['renamed'] += $this->rename('tiers', $tier, $tierName, $context);
                } else {
                    $tierCode = $columnCode . '/' . $this->seg($tierPos);
                    $tierId = DB::table('tiers')->insertGetId([
                        'code' => $tierCode,
                        'name' => $tierName ?: 'Tầng ' . $this->seg($tierPos),
                        'department_id' => $context['department_id'],
                        'warehouse_id' => $context['warehouse_id'],
                        'shelf_id' => $shelfId,
                        'column_id' => $columnId,
                        'position' => $tierPos,
                        'status_id' => 1,
                        'created_by' => $context['actor'],
                        'created_at' => $context['now'],
                        'updated_at' => $context['now'],
                    ]);
                    $totals['tiers_added']++;
                }

                $cells = $tier ? ($existingCells[$tier->id] ?? collect()) : collect();
                $changes = $this->syncTier($shelfId, $columnId, $tierId, $tierCode, $cells, $max, $context);

                foreach ($changes as $key => $value) {
                    $totals[$key] += $value;
                }
            }

            // Tầng bị gỡ khỏi lưới: khoá lại, không xoá, vì mã và lịch sử vẫn phải tra được.
            foreach ($oldTiers as $tierPos => $tier) {
                if (isset($tiers[$tierPos])) {
                    continue;
                }
                $totals['locked'] += $this->lockTier($tier, $context);
                if ((int) $tier->status_id === 1) {
                    $totals['tiers_removed']++;
                }
            }
        }

        // Cột bị gỡ khỏi lưới: khoá cột cùng toàn bộ tầng và vị trí bên dưới.
        foreach ($existingColumns as $colPos => $column) {
            if (isset($wanted[$colPos])) {
                continue;
            }

            foreach ($existingTiers->get($column->id) ?? collect() as $tier) {
                $totals['locked'] += $this->lockTier($tier, $context);
            }

            if ((int) $column->status_id === 1) {
                DB::table('columns')->where('id', $column->id)->update([
                    'status_id' => 0,
                    'updated_by' => $context['actor'],
                    'updated_at' => $context['now'],
                ]);
                $totals['columns_removed']++;
            }
        }

        $summary = $this->describe($totals);
        $fields = ['code' => 'Mã kệ/tủ', 'name' => 'Tên kệ/tủ', 'warehouse_id' => 'Kho/Phòng'];
        $maps = ['warehouse_id' => DB::table('warehouses')
            ->where('id', $context['warehouse_id'])
            ->get(['id', 'code', 'name'])
            ->mapWithKeys(fn ($row) => [$row->id => $row->code . ' - ' . $row->name])
            ->all()];

        DataMasterHistory::record(
            'shelves',
            $shelfId,
            $shelf ? 'Cập nhật' : 'Thêm mới',
            ($shelf ? 'Sửa cấu trúc kệ/tủ ' : 'Tạo cấu trúc kệ/tủ ') . $shelfCode . ': '
                . count($wanted) . ' cột, ' . array_sum(array_map('count', $wanted)) . ' tầng'
                . ($summary ? ' (' . $summary . ')' : '') . '.',
            $fields,
            $maps
        );

        AuditTrialController::log(
            $shelf ? 'Sửa cấu trúc kệ/tủ' : 'Tạo cấu trúc kệ/tủ',
            'shelves',
            $shelfId,
            $shelf ? $shelf->code . ' - ' . $shelf->name : 'NA',
            $shelfCode . ' - ' . $name . ': ' . count($wanted) . ' cột' . ($summary ? ', ' . $summary : '')
        );

        return ['shelf_id' => $shelfId, 'summary' => $summary];
    }

    /**
     * Đưa số vị trí của một tầng về $max: tạo ô còn thiếu, mở lại dải vừa nằm ngoài sức chứa
     * cũ, khoá ô vượt sức chứa mới. Ô người dùng chủ động khoá nằm trong sức chứa cũ giữ nguyên.
     */
    private function syncTier(int $shelfId, int $columnId, int $tierId, string $tierCode, $cells, int $max, array $context): array
    {
        $changes = ['created' => 0, 'locked' => 0, 'unlocked' => 0];
        $oldMax = (int) $cells->where('status_id', 1)->max('position');
        $have = $cells->pluck('position')->map(fn ($p) => (int) $p)->flip();

        $rows = [];
        for ($p = 1; $p <= $max; $p++) {
            if ($have->has($p)) {
                continue;
            }
            $rows[] = [
                'code' => $tierCode . '/' . $this->seg($p),
                'department_id' => $context['department_id'],
                'warehouse_id' => $context['warehouse_id'],
                'shelf_id' => $shelfId,
                'column_id' => $columnId,
                'tier_id' => $tierId,
                'position' => $p,
                'status_id' => 1,
                'created_by' => $context['actor'],
                'created_at' => $context['now'],
                'updated_at' => $context['now'],
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('locations')->insert($chunk);
        }
        $changes['created'] = count($rows);

        $stamp = ['updated_by' => $context['actor'], 'updated_at' => $context['now']];

        if ($max > $oldMax) {
            $changes['unlocked'] = DB::table('locations')
                ->where('tier_id', $tierId)
                ->whereBetween('position', [$oldMax + 1, $max])
                ->where('status_id', '<>', 1)
                ->update(['status_id' => 1] + $stamp);
        }

        $changes['locked'] = DB::table('locations')
            ->where('tier_id', $tierId)
            ->where('position', '>', $max)
            ->where('status_id', 1)
            ->update(['status_id' => 0] + $stamp);

        return $changes;
    }

    /** Khoá một tầng cùng các vị trí đang mở của nó, trả về số vị trí vừa khoá. */
    private function lockTier($tier, array $context): int
    {
        $stamp = ['status_id' => 0, 'updated_by' => $context['actor'], 'updated_at' => $context['now']];

        $locked = DB::table('locations')
            ->where('tier_id', $tier->id)
            ->where('status_id', 1)
            ->update($stamp);

        if ((int) $tier->status_id === 1) {
            DB::table('tiers')->where('id', $tier->id)->update($stamp);
        }

        return $locked;
    }

    /** Cột/tầng từng bị gỡ nay được thêm lại thì mở dùng trở lại. */
    private function reopen(string $table, $row, array $context): void
    {
        if ((int) $row->status_id !== 1) {
            DB::table($table)->where('id', $row->id)->update([
                'status_id' => 1,
                'updated_by' => $context['actor'],
                'updated_at' => $context['now'],
            ]);
        }
    }

    /** Các ô đang có lô hàng mà lượt lưu này sẽ khoá (thu nhỏ tầng, gỡ tầng, gỡ cột). */
    private function blockedCells(array $wanted, $columns, $tiers, $cells): array
    {
        $doomed = [];

        foreach ($columns as $colPos => $column) {
            foreach ($tiers->get($column->id) ?? collect() as $tierPos => $tier) {
                $max = $wanted[$colPos][$tierPos] ?? 0;

                foreach ($cells[$tier->id] ?? collect() as $cell) {
                    if ((int) $cell->status_id === 1 && (int) $cell->position > $max) {
                        $doomed[$cell->id] = $cell->code;
                    }
                }
            }
        }

        if (! $doomed) {
            return [];
        }

        $busy = $this->busyIds(array_keys($doomed));
        $codes = array_values(array_intersect_key($doomed, $busy));
        sort($codes);

        return $codes
            ? [count($codes) . ' vị trí (' . implode(', ', array_slice($codes, 0, 5)) . (count($codes) > 5 ? '...' : '') . ')']
            : [];
    }

    /** Tập id vị trí (trong $ids, hoặc toàn bộ nếu null) đang có lô hàng còn hiệu lực. */
    private function busyIds(?array $ids = null): array
    {
        if ($ids !== null && ! $ids) {
            return [];
        }

        $busy = [];

        foreach (self::LOT_TABLES as $table) {
            foreach (array_chunk($ids ?? [null], 1000) as $chunk) {
                $query = DB::table($table)->whereNotNull('location_id')->where('status_id', 1);
                if ($ids !== null) {
                    $query->whereIn('location_id', $chunk);
                }
                foreach ($query->distinct()->pluck('location_id') as $id) {
                    $busy[(int) $id] = true;
                }
            }
        }

        return $busy;
    }

    /** Số vị trí đang mở và số vị trí có hàng, gom theo $groupColumn (warehouse_id / shelf_id). */
    private function locationStats(string $groupColumn, array $groupIds): array
    {
        if (! $groupIds) {
            return [];
        }

        $locations = DB::table('locations')
            ->whereIn($groupColumn, $groupIds)
            ->where('status_id', 1)
            ->get(['id', $groupColumn]);

        $busy = $this->busyIds($locations->pluck('id')->all());
        $stats = [];

        foreach ($locations as $location) {
            $key = $location->$groupColumn;
            $stats[$key]['total'] = ($stats[$key]['total'] ?? 0) + 1;
            $stats[$key]['used'] = ($stats[$key]['used'] ?? 0) + (isset($busy[$location->id]) ? 1 : 0);
        }

        return $stats;
    }

    /** Mã kệ tiếp theo trong kho: <mã kho>/<số kệ lớn nhất + 1>, ví dụ 01/05 -> 01/06. */
    private function suggestShelfCode($warehouse, array $codes): string
    {
        $prefix = $warehouse->code . '/';
        $highest = 0;

        foreach ($codes as $code) {
            if (str_starts_with($code, $prefix) && preg_match('/^(\d+)$/', substr($code, strlen($prefix)), $m)) {
                $highest = max($highest, (int) $m[1]);
            }
        }

        $next = $prefix . $this->seg($highest + 1);

        return DB::table('shelves')->where('code', $next)->exists() ? '' : $next;
    }

    /** Đổi tên hiển thị của một cột/tầng có sẵn (mã giữ nguyên); trả về 1 nếu có đổi. */
    private function rename(string $table, $row, string $name, array $context): int
    {
        if ($name === '' || $name === (string) $row->name) {
            return 0;
        }

        DB::table($table)->where('id', $row->id)->update([
            'name' => $name,
            'updated_by' => $context['actor'],
            'updated_at' => $context['now'],
        ]);

        return 1;
    }

    private function describe(array $totals): string
    {
        $labels = [
            'code_changed' => 'đổi mã kệ',
            'renamed_codes' => 'đổi mã %d cột/tầng/vị trí con',
            'renamed' => 'đổi tên %d cột/tầng',
            'columns_added' => 'thêm %d cột',
            'columns_removed' => 'gỡ %d cột',
            'tiers_added' => 'thêm %d tầng',
            'tiers_removed' => 'gỡ %d tầng',
            'created' => 'tạo %d vị trí',
            'locked' => 'khoá %d vị trí',
            'unlocked' => 'mở lại %d vị trí',
        ];

        $parts = [];
        foreach ($labels as $key => $format) {
            if (! empty($totals[$key])) {
                $parts[] = sprintf($format, $totals[$key]);
            }
        }

        return implode(', ', $parts);
    }

    /** Số thứ tự trong mã: 2 chữ số (01..99), vượt 99 thì giữ nguyên (100, 101...). */
    private function seg(int $position): string
    {
        return str_pad((string) $position, 2, '0', STR_PAD_LEFT);
    }

    private function warehouse($id)
    {
        return DB::table('warehouses')
            ->where('id', $id)
            ->where('department_id', $this->departmentId())
            ->first();
    }

    private function shelf($id)
    {
        return DB::table('shelves')
            ->where('id', $id)
            ->where('department_id', $this->departmentId())
            ->first();
    }

    private function departmentId()
    {
        return session('user')['selected_department_id'];
    }

    private function actor(): string
    {
        return \App\Support\Signer::actor();
    }

    private function fail(string $message, int $status = 422)
    {
        return response()->json(['message' => $message], $status);
    }
}
