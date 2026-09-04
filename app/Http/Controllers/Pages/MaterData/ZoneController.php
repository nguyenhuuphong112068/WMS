<?php

namespace App\Http\Controllers\Pages\MaterData;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\DataMasterHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * ĐỊNH KHU - Dữ Liệu Gốc
 *
 * Gộp 4 cấp lưu trữ Kho -> Phòng -> Kệ -> Vị Trí vào chung một màn hình,
 * mỗi cấp là một tab và có đầy đủ Thêm / Sửa / Khoá-Mở / Xoá.
 */
class ZoneController extends Controller
{
    /**
     * Cấu hình 4 cấp định khu. Khoá của mảng chính là tham số {type} trên route,
     * nên mọi tên bảng dùng cho Query Builder đều lấy từ đây chứ không lấy từ input.
     */
    private const ZONES = [
        'warehouse' => [
            'table' => 'warehouses',
            'label' => 'kho',
            'parents' => [],
            'children' => [
                ['rooms', 'warehouse_id', 'phòng'],
                ['shelves', 'warehouse_id', 'kệ/tủ'],
                ['locations', 'warehouse_id', 'vị trí'],
            ],
        ],
        'room' => [
            'table' => 'rooms',
            'label' => 'phòng',
            'parents' => ['warehouse_id' => 'warehouses'],
            'children' => [
                ['shelves', 'room_id', 'kệ/tủ'],
                ['locations', 'room_id', 'vị trí'],
            ],
        ],
        'shelf' => [
            'table' => 'shelves',
            'label' => 'kệ/tủ',
            'parents' => ['warehouse_id' => 'warehouses', 'room_id' => 'rooms'],
            'children' => [
                ['locations', 'shelf_id', 'vị trí'],
            ],
        ],
        'location' => [
            'table' => 'locations',
            'label' => 'vị trí',
            'parents' => ['warehouse_id' => 'warehouses', 'room_id' => 'rooms', 'shelf_id' => 'shelves'],
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
        'warehouse_id' => 'Kho',
        'room_id' => 'Phòng',
        'shelf_id' => 'Kệ/Tủ',
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

        $rooms = DB::table('rooms')
            ->leftJoin('warehouses', 'rooms.warehouse_id', '=', 'warehouses.id')
            ->select('rooms.*', 'warehouses.name as warehouse_name')
            ->where('rooms.department_id', $departmentId)
            ->orderBy('rooms.code', 'asc')
            ->get();

        $shelves = DB::table('shelves')
            ->leftJoin('warehouses', 'shelves.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('rooms', 'shelves.room_id', '=', 'rooms.id')
            ->select('shelves.*', 'warehouses.name as warehouse_name', 'rooms.name as room_name')
            ->where('shelves.department_id', $departmentId)
            ->orderBy('shelves.code', 'asc')
            ->get();

        $locations = DB::table('locations')
            ->leftJoin('warehouses', 'locations.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('rooms', 'locations.room_id', '=', 'rooms.id')
            ->leftJoin('shelves', 'locations.shelf_id', '=', 'shelves.id')
            ->select('locations.*', 'warehouses.name as warehouse_name', 'rooms.name as room_name', 'shelves.name as shelf_name')
            ->where('locations.department_id', $departmentId)
            ->orderBy('locations.code', 'asc')
            ->get();

        session()->put(['title' => 'DỮ LIỆU GỐC - ĐỊNH KHU']);

        return view('pages.materData.Zone.list', [
            'warehouses' => $warehouses,
            'rooms' => $rooms,
            'shelves' => $shelves,
            'locations' => $locations,
            'locationTypes' => self::LOCATION_TYPES,
            /*
            | Số lần thay đổi của từng mục, khoá là '<bảng>-<id>' vì bốn cấp nằm chung
            | một trang. Badge trên nút Sửa đọc từ đây, nội dung lịch sử tải sau qua
            | route history khi người dùng bấm vào badge.
            */
            'historyCounts' => DataMasterHistory::countsOf(['warehouses', 'rooms', 'shelves', 'locations']),
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

        $validator = Validator::make($request->all(), $this->rules($zone, $current->id), $this->messages());

        if ($validator->fails()) {
            return $this->backToTab($type, 'update')->withErrors($validator, 'update_' . $type);
        }

        $payload = $this->payload($request, $zone);
        $note = DataMasterHistory::note($this->fields($zone), $current, $payload, $this->maps($zone));

        DB::table($zone['table'])->where('id', $current->id)->update($payload + [
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        DataMasterHistory::record(
            $zone['table'],
            $current->id,
            'Cập nhật',
            $note ?: 'Lưu lại nhưng nội dung không đổi.',
            $this->fields($zone),
            $this->maps($zone)
        );

        AuditTrialController::log(
            'Cập nhật',
            $zone['table'],
            $current->id,
            $this->caption($zone, $current->code, $current->name ?? null) . $this->itemTypeNote($zone, $current->item_type ?? null),
            $this->caption($zone, $request->code, $request->name) . $this->itemTypeNote($zone, $request->input('item_type'))
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
            $this->maps($zone)
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
            DataMasterHistory::snapshot($this->fields($zone), $current, $this->maps($zone))
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
            'warehouse_id.required' => 'Vui lòng chọn kho.',
            'warehouse_id.exists' => 'Kho được chọn không hợp lệ.',
            'room_id.required' => 'Vui lòng chọn phòng.',
            'room_id.exists' => 'Phòng được chọn không hợp lệ.',
            'shelf_id.required' => 'Vui lòng chọn kệ/tủ.',
            'shelf_id.exists' => 'Kệ/Tủ được chọn không hợp lệ.',
            'item_type.in' => 'Loại lưu trữ được chọn không hợp lệ.',
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
