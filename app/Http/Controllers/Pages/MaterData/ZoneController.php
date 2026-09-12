<?php

namespace App\Http\Controllers\Pages\MaterData;

use App\Http\Controllers\Concerns\RequiresChangeReason;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\DataMasterHistory;
use App\Support\ZoneType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * ĐỊNH KHU - Dữ Liệu Gốc
 *
 * Gộp 5 cấp lưu trữ Kho -> Kệ/Tủ -> Cột -> Tầng -> Vị Trí vào chung một màn hình,
 * mỗi cấp là một tab và có đầy đủ Thêm / Sửa / Khoá-Mở / Xoá.
 */
class ZoneController extends Controller
{
    use RequiresChangeReason;

    /**
     * Cấu hình 5 cấp định khu. Khoá của mảng chính là tham số {type} trên route,
     * nên mọi tên bảng dùng cho Query Builder đều lấy từ đây chứ không lấy từ input.
     */
    private const ZONES = [
        'warehouse' => [
            'table' => 'warehouses',
            'label' => 'kho/phòng',
            'parents' => [],
            'children' => [
                ['shelves', 'warehouse_id', 'kệ/tủ'],
                ['columns', 'warehouse_id', 'cột'],
                ['tiers', 'warehouse_id', 'tầng'],
                ['locations', 'warehouse_id', 'vị trí'],
            ],
        ],
        'shelf' => [
            'table' => 'shelves',
            'label' => 'kệ/tủ',
            'parents' => ['warehouse_id' => 'warehouses'],
            'children' => [
                ['columns', 'shelf_id', 'cột'],
                ['tiers', 'shelf_id', 'tầng'],
                ['locations', 'shelf_id', 'vị trí'],
            ],
        ],
        'column' => [
            'table' => 'columns',
            'label' => 'cột',
            'parents' => ['warehouse_id' => 'warehouses', 'shelf_id' => 'shelves'],
            'children' => [
                ['tiers', 'column_id', 'tầng'],
                ['locations', 'column_id', 'vị trí'],
            ],
        ],
        'tier' => [
            'table' => 'tiers',
            'label' => 'tầng',
            'parents' => ['warehouse_id' => 'warehouses', 'shelf_id' => 'shelves', 'column_id' => 'columns'],
            'children' => [
                ['locations', 'tier_id', 'vị trí'],
            ],
        ],
        'location' => [
            'table' => 'locations',
            'label' => 'vị trí',
            'parents' => [
                'warehouse_id' => 'warehouses',
                'shelf_id' => 'shelves',
                'column_id' => 'columns',
                'tier_id' => 'tiers',
            ],
            'children' => [],
            'itemType' => true,
            // Vị trí chỉ định danh bằng mã (A01, B02...) nên không có cột tên
            'hasName' => false,
        ],
    ];

    /**
     * LOẠI LƯU TRỮ của một vị trí - chỉ cấp vị trí mới có.
     *
     * Màn hình Tồn Kho của từng loại chỉ vẽ các ô đúng loại của mình; để trống là
     * "Dùng chung", ô đó hiện ở cả ba màn hình.
     */
    public const LOCATION_TYPES = [
        'material' => 'Vật tư',
        'chemical' => 'Hoá chất',
        'standard' => 'Chất chuẩn',
    ];

    /** Nhãn của các cột cấp cha, dùng khi ghi lịch sử thay đổi. */
    private const PARENT_LABELS = [
        'warehouse_id' => 'Kho/Phòng',
        'shelf_id' => 'Kệ/Tủ',
        'column_id' => 'Cột',
        'tier_id' => 'Tầng',
    ];

    public function index()
    {
        $departmentId = session('user')['selected_department_id'];

        $warehouses = DB::table('warehouses')
            ->leftJoin('deparments', 'warehouses.department_id', '=', 'deparments.id')
            ->select('warehouses.*', 'deparments.name as department_name')
            ->where('warehouses.department_id', $departmentId)
            ->orderBy('warehouses.code', 'asc')
            ->get();

        $shelves = DB::table('shelves')
            ->leftJoin('warehouses', 'shelves.warehouse_id', '=', 'warehouses.id')
            ->select('shelves.*', 'warehouses.name as warehouse_name')
            ->where('shelves.department_id', $departmentId)
            ->orderBy('shelves.code', 'asc')
            ->get();

        $columns = DB::table('columns')
            ->leftJoin('warehouses', 'columns.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('shelves', 'columns.shelf_id', '=', 'shelves.id')
            ->select('columns.*', 'warehouses.name as warehouse_name', 'shelves.name as shelf_name')
            ->where('columns.department_id', $departmentId)
            ->orderBy('columns.code', 'asc')
            ->get();

        $tiers = DB::table('tiers')
            ->leftJoin('warehouses', 'tiers.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('shelves', 'tiers.shelf_id', '=', 'shelves.id')
            ->leftJoin('columns', 'tiers.column_id', '=', 'columns.id')
            ->select(
                'tiers.*',
                'warehouses.name as warehouse_name',
                'shelves.name as shelf_name',
                'columns.name as column_name'
            )
            ->where('tiers.department_id', $departmentId)
            ->orderBy('tiers.code', 'asc')
            ->get();

        $locations = DB::table('locations')
            ->leftJoin('warehouses', 'locations.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('shelves', 'locations.shelf_id', '=', 'shelves.id')
            ->leftJoin('columns', 'locations.column_id', '=', 'columns.id')
            ->leftJoin('tiers', 'locations.tier_id', '=', 'tiers.id')
            ->select(
                'locations.*',
                'warehouses.name as warehouse_name',
                'shelves.name as shelf_name',
                'columns.name as column_name',
                'tiers.name as tier_name'
            )
            ->where('locations.department_id', $departmentId)
            ->orderBy('locations.code', 'asc')
            ->get();

        session()->put(['title' => 'DỮ LIỆU GỐC - ĐỊNH KHU']);

        return view('pages.materData.Zone.list', [
            'warehouses' => $warehouses,
            'shelves' => $shelves,
            'columns' => $columns,
            'tiers' => $tiers,
            'locations' => $locations,
            'locationTypes' => self::LOCATION_TYPES,
            // Phân loại định khu + bảng màu cho modal khai báo và chip trên bảng
            'zoneClassifications' => ZoneType::TYPES,
            'zoneTypeColors' => ZoneType::DEFAULT_COLORS,
            'zoneTypeIcons' => ZoneType::ICONS,
            'zonePalette' => ZoneType::PALETTE,
            /*
            | Số lần thay đổi của từng mục, khoá là '<bảng>-<id>' vì năm cấp nằm chung
            | một trang. Badge trên nút Sửa đọc từ đây, nội dung lịch sử tải sau qua
            | route history khi người dùng bấm vào badge.
            */
            'historyCounts' => DataMasterHistory::countsOf(['warehouses', 'shelves', 'columns', 'tiers', 'locations']),
        ]);
    }

    /** Trả về lịch sử thay đổi của một mục định khu cho modal xem lịch sử. */
    public function history(Request $request, string $type)
    {
        $zone = $this->zone($type);

        return response()->json([
            'rows' => DataMasterHistory::rows($zone['table'], (int) $request->id),
        ]);
    }

    public function store(Request $request, string $type)
    {
        $zone = $this->zone($type);

        $validator = Validator::make($request->all(), $this->rules($zone), $this->messages());

        if ($validator->fails()) {
            return $this->backToTab($type, 'create')->withErrors($validator, 'create_' . $type);
        }

        $id = DB::table($zone['table'])->insertGetId($this->payload($request, $zone) + [
            'department_id' => session('user')['selected_department_id'],
            'status_id' => 1,
            'created_by' => $this->actor(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DataMasterHistory::record(
            $zone['table'],
            $id,
            'Thêm mới',
            'Khai báo mới ' . $zone['label'] . ': ' . $this->caption($zone, $request->code, $request->name) . '.',
            $this->fields($zone),
            $this->maps($zone)
        );

        AuditTrialController::log(
            'Thêm mới',
            $zone['table'],
            $id,
            'NA',
            'Thêm ' . $zone['label'] . ': ' . $this->caption($zone, $request->code, $request->name)
                . $this->itemTypeNote($zone, $request->input('item_type'))
                . $this->zoneTypeNote($request->input('zone_type'))
        );

        return $this->backToTab($type)->with('success', 'Đã thêm ' . $zone['label'] . ' thành công!');
    }

    public function update(Request $request, string $type)
    {
        $zone = $this->zone($type);

        $current = DB::table($zone['table'])->where('id', $request->id)->first();

        if (! $current) {
            return $this->backToTab($type)->with('error', 'Không tìm thấy ' . $zone['label'] . ' cần cập nhật!');
        }

        $validator = Validator::make(
            $request->all(),
            $this->rules($zone, $current->id) + $this->changeReasonRules(),
            $this->messages() + $this->changeReasonMessages()
        );

        if ($validator->fails()) {
            return $this->backToTab($type, 'update')->withErrors($validator, 'update_' . $type);
        }

        $payload = $this->payload($request, $zone);
        $note = DataMasterHistory::note($this->fields($zone), $current, $payload, $this->maps($zone));

        if ($note === '') {
            return $this->backToTab($type)->with('error', 'Chưa có thông tin nào thay đổi nên không lưu.');
        }

        DB::table($zone['table'])->where('id', $current->id)->update($payload + [
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        DataMasterHistory::record(
            $zone['table'],
            $current->id,
            'Cập nhật',
            $note,
            $this->fields($zone),
            $this->maps($zone),
            $this->changeReason($request)
        );

        AuditTrialController::log(
            'Cập nhật',
            $zone['table'],
            $current->id,
            $this->caption($zone, $current->code, $current->name ?? null)
                . $this->itemTypeNote($zone, $current->item_type ?? null)
                . $this->zoneTypeNote($current->zone_type ?? null),
            $this->caption($zone, $request->code, $request->name)
                . $this->itemTypeNote($zone, $request->input('item_type'))
                . $this->zoneTypeNote($request->input('zone_type'))
        );

        return $this->backToTab($type)->with('success', 'Cập nhật ' . $zone['label'] . ' thành công!');
    }

    public function deActive(Request $request, string $type)
    {
        $zone = $this->zone($type);

        $current = DB::table($zone['table'])->where('id', $request->id)->first();

        if (! $current) {
            return $this->backToTab($type)->with('error', 'Không tìm thấy ' . $zone['label'] . ' cần thay đổi trạng thái!');
        }

        if ($stop = $this->guardChangeReason($request)) {
            return $stop;
        }

        $newStatus = $current->status_id == 1 ? 0 : 1;

        DB::table($zone['table'])->where('id', $current->id)->update([
            'status_id' => $newStatus,
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        DataMasterHistory::record(
            $zone['table'],
            $current->id,
            $newStatus == 1 ? 'Mở khoá' : 'Khoá',
            DataMasterHistory::statusNote($current->status_id, $newStatus),
            $this->fields($zone),
            $this->maps($zone),
            $this->changeReason($request)
        );

        AuditTrialController::log(
            $newStatus == 1 ? 'Mở khoá' : 'Khoá',
            $zone['table'],
            $current->id,
            'status_id: ' . $current->status_id,
            'status_id: ' . $newStatus
        );

        return $this->backToTab($type)->with(
            'success',
            ($newStatus == 1 ? 'Đã mở khoá ' : 'Đã khoá ') . $zone['label'] . ' ' . $current->code . '!'
        );
    }

    /**
     * Xoá hẳn một mục. Chỉ cho phép khi không còn cấp con nào đang trỏ tới,
     * để không làm mồ côi dữ liệu bên dưới.
     */
    public function destroy(Request $request, string $type)
    {
        $zone = $this->zone($type);

        $current = DB::table($zone['table'])->where('id', $request->id)->first();

        if (! $current) {
            return $this->backToTab($type)->with('error', 'Không tìm thấy ' . $zone['label'] . ' cần xoá!');
        }

        if ($stop = $this->guardChangeReason($request)) {
            return $stop;
        }

        foreach ($zone['children'] as [$childTable, $childColumn, $childLabel]) {
            $used = DB::table($childTable)->where($childColumn, $current->id)->count();

            if ($used > 0) {
                return $this->backToTab($type)->with(
                    'error',
                    'Không thể xoá ' . $zone['label'] . ' "' . ($current->name ?? $current->code) . '" vì đang có ' . $used . ' ' . $childLabel
                        . ' trực thuộc. Vui lòng xoá hoặc chuyển các ' . $childLabel . ' này trước.'
                );
            }
        }

        DB::table($zone['table'])->where('id', $current->id)->delete();

        // Bản ghi không còn để đọc lại nên chụp từ giá trị vừa đọc trước khi xoá
        DataMasterHistory::write(
            $zone['table'],
            $current->id,
            'Xoá',
            'Xoá hẳn ' . $zone['label'] . ': ' . $this->caption($zone, $current->code, $current->name ?? null) . '.',
            DataMasterHistory::snapshot($this->fields($zone), $current, $this->maps($zone)),
            $this->changeReason($request)
        );

        AuditTrialController::log(
            'Xoá',
            $zone['table'],
            $current->id,
            $this->caption($zone, $current->code, $current->name ?? null),
            'NA'
        );

        return $this->backToTab($type)->with('success', 'Đã xoá ' . $zone['label'] . ' ' . $current->code . ' thành công!');
    }

    /** Lấy cấu hình của một cấp, chặn mọi giá trị {type} lạ. */
    private function zone(string $type): array
    {
        abort_unless(array_key_exists($type, self::ZONES), 404);

        return self::ZONES[$type];
    }

    private function actor(): string
    {
        return \App\Support\Signer::actor();
    }

    /**
     * Cấp này có khai tên riêng hay không.
     *
     * Vị trí chỉ dùng mã nên trả về false - form, bảng, lịch sử và log đều bỏ cột tên.
     */
    private function hasName(array $zone): bool
    {
        return $zone['hasName'] ?? true;
    }

    /** Chuỗi nhận biết một mục dùng trong thông báo và log: "MÃ - Tên", hoặc chỉ "MÃ". */
    private function caption(array $zone, $code, $name = null): string
    {
        $code = (string) $code;

        return $this->hasName($zone) ? $code . ' - ' . (string) $name : $code;
    }

    /** Nhãn các cột của một cấp, dùng cho ảnh chụp và mô tả thay đổi của lịch sử. */
    private function fields(array $zone): array
    {
        $fields = ['code' => 'Mã ' . $zone['label']];

        if ($this->hasName($zone)) {
            $fields['name'] = 'Tên ' . $zone['label'];
        }

        foreach (array_keys($zone['parents']) as $column) {
            $fields[$column] = self::PARENT_LABELS[$column];
        }

        if (! empty($zone['itemType'])) {
            $fields['item_type'] = 'Loại lưu trữ';
        }

        // Hai thuộc tính này có ở cả 5 cấp nên khai sau cùng, không phụ thuộc cấu hình cấp
        $fields['zone_type'] = 'Phân loại định khu';
        $fields['color'] = 'Màu hiển thị';

        return $fields;
    }

    /** Bảng tra nhãn của cấp cha và loại lưu trữ, để lịch sử hiện tên thay vì id. */
    private function maps(array $zone): array
    {
        $maps = [];

        foreach ($zone['parents'] as $column => $parentTable) {
            $maps[$column] = DB::table($parentTable)
                ->orderBy('code', 'asc')
                ->get(['id', 'code', 'name'])
                ->mapWithKeys(fn ($row) => [$row->id => $row->code . ' - ' . $row->name])
                ->all();
        }

        // Vị trí không chọn loại nghĩa là dùng chung cho cả ba màn hình Tồn Kho
        if (! empty($zone['itemType'])) {
            $maps['item_type'] = ['' => 'Dùng chung'] + self::LOCATION_TYPES;
        }

        // Lịch sử hiện "Dự Phòng" thay vì "reserve"
        $maps['zone_type'] = ZoneType::historyMap();

        return $maps;
    }

    /** Phần " · Loại: ..." ghép vào log của cấp vị trí để thấy được lần đổi loại lưu trữ. */
    private function itemTypeNote(array $zone, $value): string
    {
        if (empty($zone['itemType'])) {
            return '';
        }

        return ' · Loại: ' . (self::LOCATION_TYPES[$value] ?? 'Dùng chung');
    }

    /** Phần " · Phân loại: ..." ghép vào log của mọi cấp, cấp nào cũng phân loại được. */
    private function zoneTypeNote($value): string
    {
        return ' · Phân loại: ' . ZoneType::label($value ?: null);
    }

    private function rules(array $zone, $ignoreId = null): array
    {
        $rules = [
            'code' => ['required', 'max:50', Rule::unique($zone['table'], 'code')->ignore($ignoreId)],
        ];

        if ($this->hasName($zone)) {
            $rules['name'] = ['required', 'max:255'];
        }

        foreach ($zone['parents'] as $column => $parentTable) {
            $rules[$column] = ['nullable', 'integer', Rule::exists($parentTable, 'id')];
        }

        if (! empty($zone['itemType'])) {
            $rules['item_type'] = ['nullable', Rule::in(array_keys(self::LOCATION_TYPES))];
        }

        $rules['zone_type'] = ['nullable', Rule::in(array_keys(ZoneType::TYPES))];
        $rules['color'] = ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'];

        return $rules;
    }

    private function payload(Request $request, array $zone): array
    {
        $data = ['code' => trim((string) $request->code)];

        if ($this->hasName($zone)) {
            $data['name'] = trim((string) $request->name);
        }

        foreach (array_keys($zone['parents']) as $column) {
            $data[$column] = $request->input($column) ?: null;
        }

        if (! empty($zone['itemType'])) {
            $data['item_type'] = $request->input('item_type') ?: null;
        }

        $data['zone_type'] = $request->input('zone_type') ?: null;
        // Màu rỗng/sai định dạng lưu null để mục bám theo màu mặc định của phân loại
        $data['color'] = ZoneType::normalize($request->input('color'));

        return $data;
    }

    private function messages(): array
    {
        return [
            'code.required' => 'Vui lòng nhập mã.',
            'code.max' => 'Mã tối đa 50 ký tự.',
            'code.unique' => 'Mã này đã tồn tại, vui lòng nhập mã khác.',
            'name.required' => 'Vui lòng nhập tên.',
            'name.max' => 'Tên tối đa 255 ký tự.',
            'warehouse_id.required' => 'Vui lòng chọn kho/phòng.',
            'warehouse_id.exists' => 'Kho/Phòng được chọn không hợp lệ.',
            'shelf_id.required' => 'Vui lòng chọn kệ/tủ.',
            'shelf_id.exists' => 'Kệ/Tủ được chọn không hợp lệ.',
            'column_id.required' => 'Vui lòng chọn cột.',
            'column_id.exists' => 'Cột được chọn không hợp lệ.',
            'tier_id.required' => 'Vui lòng chọn tầng.',
            'tier_id.exists' => 'Tầng được chọn không hợp lệ.',
            'item_type.in' => 'Loại lưu trữ được chọn không hợp lệ.',
            'zone_type.in' => 'Phân loại định khu được chọn không hợp lệ.',
            'color.regex' => 'Màu không hợp lệ, vui lòng chọn lại màu trên bảng màu.',
        ];
    }

    /**
     * Quay lại đúng tab vừa thao tác. Truyền $form ('create'/'update') khi
     * validate lỗi để màn hình tự mở lại đúng modal kèm dữ liệu đã nhập.
     */
    private function backToTab(string $type, ?string $form = null)
    {
        $redirect = redirect()->back()->with('activeTab', $type);

        if ($form !== null) {
            $redirect = $redirect->withInput()->with('formTab', $type . '-' . $form);
        }

        return $redirect;
    }
}
