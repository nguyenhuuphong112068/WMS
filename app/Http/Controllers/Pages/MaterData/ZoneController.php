<?php

namespace App\Http\Controllers\Pages\MaterData;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
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
        ],
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
            'active' => 1,
            'created_by' => $this->actor(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        AuditTrialController::log(
            'Thêm mới',
            $zone['table'],
            $id,
            'NA',
            'Thêm ' . $zone['label'] . ': ' . $request->code . ' - ' . $request->name
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

        DB::table($zone['table'])->where('id', $current->id)->update($this->payload($request, $zone) + [
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        AuditTrialController::log(
            'Cập nhật',
            $zone['table'],
            $current->id,
            $current->code . ' - ' . $current->name,
            $request->code . ' - ' . $request->name
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
            'active' => $newStatus,
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

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
                    'Không thể xoá ' . $zone['label'] . ' "' . $current->name . '" vì đang có ' . $used . ' ' . $childLabel
                        . ' trực thuộc. Vui lòng xoá hoặc chuyển các ' . $childLabel . ' này trước.'
                );
            }
        }

        DB::table($zone['table'])->where('id', $current->id)->delete();

        AuditTrialController::log(
            'Xoá',
            $zone['table'],
            $current->id,
            $current->code . ' - ' . $current->name,
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
        return session('user')['fullName'] ?? 'NA';
    }

    private function rules(array $zone, $ignoreId = null): array
    {
        $rules = [
            'code' => ['required', 'max:50', Rule::unique($zone['table'], 'code')->ignore($ignoreId)],
            'name' => ['required', 'max:255'],
        ];

        foreach ($zone['parents'] as $column => $parentTable) {
            $rules[$column] = ['required', Rule::exists($parentTable, 'id')];
        }

        return $rules;
    }

    private function payload(Request $request, array $zone): array
    {
        $data = [
            'code' => trim((string) $request->code),
            'name' => trim((string) $request->name),
        ];

        foreach (array_keys($zone['parents']) as $column) {
            $data[$column] = $request->input($column);
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
