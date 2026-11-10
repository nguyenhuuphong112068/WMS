<?php

namespace App\Http\Controllers\Pages\Category;

use App\Http\Controllers\Concerns\RequiresChangeReason;
use App\Http\Controllers\Concerns\StoresBatchItems;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\CategoryUnitConversion;
use App\Support\ChemicalCompatibility;
use App\Support\DepartmentChemical;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * DANH MỤC - HOÁ CHẤT, TAB "HOÁ CHẤT CỦA PHÒNG"
 *
 * Tab này nằm chung trang với tab "Danh Mục Hoá Chất Công Ty" (ChemicalCategoryController::index).
 * Controller này chỉ nhận các thao tác thêm / sửa / khoá rồi quay lại đúng tab đó.
 *
 * Danh mục hoá chất (chemical_categories) dùng chung toàn công ty vì nó mô tả BẢN CHẤT
 * của chất. Màn hình này khai phần CÁCH DÙNG của riêng phòng ban đang chọn: đơn vị tính,
 * hạn dùng nội bộ, ngưỡng tồn tối thiểu, định khu, điều kiện bảo quản.
 *
 * Để trống một ô nghĩa là "theo mặc định của danh mục" - xem App\Support\DepartmentChemical.
 *
 * Riêng ĐƠN VỊ TÍNH thì bắt buộc: danh mục chung không còn cột đơn vị, mọi màn hình
 * Nhập / Xuất / Tồn của phòng đều đọc đơn vị từ dòng khai ở đây.
 *
 * Mỗi dòng ở đây cũng chính là lời khai "phòng tôi có dùng chất này", dùng cho cột
 * "Phòng Ban Đang Dùng" ở tab Danh Mục Hoá Chất Công Ty.
 *
 * Modal Thêm mới là BẢNG NHIỀU DÒNG (items[i][...], mỗi dòng một hoá chất): một lần khai được
 * nhiều hoá chất. Modal Cập nhật vẫn sửa từng dòng.
 *
 * Không xoá cứng: khoá (status_id = 0) để giữ lại vết đã từng khai.
 */
class DepartmentChemicalController extends Controller
{
    use RequiresChangeReason;
    use StoresBatchItems;

    private const TABLE = 'chemical_department_categories';

    private const LABEL = 'hoá chất của phòng';

    /**
     * Khai nhiều hoá chất một lần từ bảng items[i][...] của modal Thêm mới. Một dòng lỗi thì không
     * lưu dòng nào, lỗi báo theo "Dòng N".
     */
    public function store(Request $request)
    {
        $departmentId = $this->departmentId();

        $items = $this->batchItems($request, fn ($item) => blank($item['category_id'] ?? null));

        $rules = $this->batchRules($this->rules($departmentId));
        // Một hoá chất chỉ một dòng trong cùng lần khai
        $rules['items.*.category_id'][] = 'distinct';

        $validator = Validator::make(['items' => $items], $rules, $this->batchMessages($this->messages(), 'Vui lòng chọn ít nhất một hoá chất cần khai.') + [
            'items.*.category_id.distinct' => 'Hoá chất bị chọn trùng ở nhiều dòng.',
        ]);

        foreach ($items as $index => $item) {
            $prefix = 'items.'.$index.'.';
            $this->checkConversions($validator, $item, (int) $item['category_id'], $departmentId, $prefix);
            $this->checkStockRange($validator, $item, $prefix);
        }

        if ($validator->fails()) {
            // Gửi lại đúng các dòng đã kiểm tra để số "Dòng N" khớp với bảng
            return $this->backToTab()->withErrors($validator, 'dcCreateErrors')->withInput(['items' => $items]);
        }

        DB::transaction(function () use ($items, $departmentId) {
            foreach ($items as $item) {
                $id = DB::table(self::TABLE)->insertGetId($this->payload($item) + [
                    'department_id' => $departmentId,
                    'category_id' => (int) $item['category_id'],
                    'status_id' => 1,
                    'created_by' => $this->actor(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->saveConversions($item, (int) $item['category_id'], $departmentId);

                AuditTrialController::log(
                    'Thêm mới',
                    self::TABLE,
                    $id,
                    'NA',
                    'Khai hoá chất cho phòng ban, category_id: '.$item['category_id']
                );
            }
        });

        // Tương kỵ không chặn: lưu xong mới đối chiếu (đã thấy cả các dòng vừa khai) và cảnh báo
        $warning = $this->compatibilityWarning(array_map(
            fn ($item) => [(int) $item['category_id'], (int) ($item['default_location_id'] ?? 0)],
            $items
        ));

        return $this->backToTab()->with(
            'success',
            count($items) === 1 ? 'Đã khai '.self::LABEL.' thành công!' : 'Đã khai '.count($items).' '.self::LABEL.' thành công!'
        )->with('warning', $warning);
    }

    public function update(Request $request)
    {
        $departmentId = $this->departmentId();

        $current = DB::table(self::TABLE)
            ->where('id', $request->id)
            ->where('department_id', $departmentId)
            ->first();

        if (! $current) {
            return $this->backToTab()->with('error', 'Không tìm thấy '.self::LABEL.' cần cập nhật!');
        }

        if ($current->status_id != 1) {
            return $this->backToTab()->with('error', 'Hoá chất của phòng đã bị khoá, không thể chỉnh sửa!');
        }

        // Không cho đổi hoá chất của một dòng đã khai: đó là khoá của dòng.
        // Khai nhầm thì khoá dòng cũ rồi khai dòng mới, để giữ vết.
        $validator = Validator::make(
            $request->all(),
            $this->rules($departmentId, true) + $this->changeReasonRules(),
            $this->messages() + $this->changeReasonMessages()
        );

        $this->checkConversions($validator, $request->all(), (int) $current->category_id, $departmentId);

        $this->checkStockRange($validator, $request->all());

        if ($validator->fails()) {
            return $this->backToTab()->withErrors($validator, 'dcUpdateErrors')->withInput();
        }

        DB::table(self::TABLE)->where('id', $current->id)->update($this->payload($request->all()) + [
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        $this->saveConversions($request->all(), (int) $current->category_id, $departmentId);

        $units = DB::table('units')->pluck('name', 'id');

        AuditTrialController::log(
            'Cập nhật',
            self::TABLE,
            $current->id,
            'đơn vị: '.($units[$current->unit_id] ?? 'chưa khai')
                .' | kg/đơn vị: '.($current->pack_weight_kg ?? 'không khai')
                .' | hạn: '.($current->shelf_life_months ?? 'mặc định')
                .' | ngưỡng: '.($current->min_stock ?? 'mặc định')
                .' | ngưỡng tối đa: '.($current->max_stock ?? 'chưa khai'),
            'đơn vị: '.($units[(int) $request->unit_id] ?? 'chưa khai')
                .' | kg/đơn vị: '.($this->packWeightKg($request->all()) ?? 'không khai')
                .' | hạn: '.($request->shelf_life_months ?: 'mặc định')
                .' | ngưỡng: '.($request->min_stock ?: 'mặc định')
                .' | ngưỡng tối đa: '.($request->max_stock ?: 'chưa khai')
                .' | Lý do: '.$this->changeReason($request)
        );

        // Đổi sang định khu mới mà tầng đó đã có hoá chất tương kỵ: lưu bình thường, kèm cảnh báo
        $newLocation = (int) ($request->default_location_id ?? 0);
        $warning = $newLocation && $newLocation !== (int) $current->default_location_id
            ? $this->compatibilityWarning([[(int) $current->category_id, $newLocation]])
            : null;

        return $this->backToTab()->with('success', 'Cập nhật '.self::LABEL.' thành công!')->with('warning', $warning);
    }

    public function deActive(Request $request)
    {
        $current = DB::table(self::TABLE)
            ->where('id', $request->id)
            ->where('department_id', $this->departmentId())
            ->first();

        if (! $current) {
            return $this->backToTab()->with('error', 'Không tìm thấy '.self::LABEL.' cần thay đổi trạng thái!');
        }

        if ($this->changeReason($request) === '') {
            return $this->backToTab()->with('error', 'Vui lòng nhập lý do điều chỉnh.');
        }

        $newStatus = $current->status_id == 1 ? 0 : 1;

        DB::table(self::TABLE)->where('id', $current->id)->update([
            'status_id' => $newStatus,
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        AuditTrialController::log(
            $newStatus == 1 ? 'Mở khoá' : 'Khoá',
            self::TABLE,
            $current->id,
            'status_id: '.$current->status_id,
            'status_id: '.$newStatus.' | Lý do: '.$this->changeReason($request)
        );

        return $this->backToTab()->with(
            'success',
            ($newStatus == 1 ? 'Đã mở khoá ' : 'Đã khoá ').self::LABEL.'!'
        );
    }

    private function rules(int $departmentId, bool $isUpdate = false): array
    {
        $rules = [
            'unit_id' => ['required', 'integer', 'exists:units,id'],
            // Khối lượng hoá chất (kg) trong 1 đơn vị đếm/bao bì - chỉ giữ khi đơn vị của
            // phòng thuộc nhóm count (xem payload()); đơn vị khối lượng/thể tích tự quy đổi.
            'pack_weight_kg' => ['nullable', 'numeric', 'gt:0'],
            'shelf_life_months' => ['nullable', 'integer', 'min:1', 'max:1200'],
            'min_stock' => ['nullable', 'numeric', 'min:0'],
            'max_stock' => ['nullable', 'numeric', 'min:0'],
            'storage_condition_id' => ['nullable', 'exists:storage_conditions,id'],
            // Định khu phải thuộc ĐÚNG phòng ban đang chọn, không mượn được của phòng khác
            'default_location_id' => [
                'nullable',
                Rule::exists('locations', 'id')
                    ->where('department_id', $departmentId)
                    ->where('status_id', 1),
            ],
            'note' => ['nullable', 'max:500'],
        ];

        if ($isUpdate) {
            $rules['id'] = ['required', 'exists:'.self::TABLE.',id'];

            return $rules;
        }

        $rules['category_id'] = [
            'required',
            'exists:chemical_categories,id',
            // Mỗi phòng chỉ khai một dòng cho một hoá chất, khớp ràng buộc unique ở DB
            Rule::unique(self::TABLE, 'category_id')->where('department_id', $departmentId),
        ];

        return $rules;
    }

    /**
     * Ngưỡng tối đa phải lớn hơn ngưỡng tối thiểu.
     *
     * Không dùng rule gte:min_stock vì ô tối thiểu thường để trống, lúc đó gte so sánh
     * chuỗi rỗng và cho kết quả vô nghĩa. Chỉ so khi cả hai ô đều có số.
     */
    private function checkStockRange($validator, array $input, string $prefix = ''): void
    {
        $validator->after(function ($validator) use ($input, $prefix) {
            $min = trim((string) ($input['min_stock'] ?? ''));
            $max = trim((string) ($input['max_stock'] ?? ''));

            if ($min === '' || $max === '' || ! is_numeric($min) || ! is_numeric($max)) {
                return;
            }

            if ((float) $max < (float) $min) {
                $validator->errors()->add($prefix.'max_stock', 'Ngưỡng tồn tối đa phải lớn hơn hoặc bằng ngưỡng tồn tối thiểu.');
            }
        });
    }

    /**
     * Cảnh báo (không chặn) khi đặt hoá chất vào tầng đã có hoá chất tương kỵ theo Sơ đồ lưu trữ
     * hoá chất theo hình đồ cảnh báo (GHS) - xem App\Support\ChemicalCompatibility. Gọi SAU khi lưu.
     *
     * @param  array  $placements  [[category_id, location_id], ...]
     */
    private function compatibilityWarning(array $placements): ?string
    {
        $placements = array_values(array_filter($placements, fn ($row) => $row[0] && $row[1]));

        if (! $placements) {
            return null;
        }

        $codes = DB::table('chemical_categories')
            ->whereIn('id', array_column($placements, 0))
            ->pluck('code', 'id');

        $messages = ChemicalCompatibility::placementWarnings(array_map(
            fn ($row) => [$row[0], $row[1], (string) ($codes[$row[0]] ?? '')],
            $placements
        ));

        return $messages ? 'Cảnh báo tương kỵ: '.implode(' | ', array_unique($messages)) : null;
    }

    /**
     * Bắt khai hệ số quy đổi khi phòng chọn đơn vị lệch với đơn vị phòng khác đang dùng.
     *
     * Cùng một mã mà mỗi phòng một đơn vị thì lúc chuyển kho hệ thống phải biết đổi qua
     * lại; thiếu hệ số là số lượng nhận về sẽ sai đơn vị.
     */
    private function checkConversions($validator, array $input, int $categoryId, int $departmentId, string $prefix = ''): void
    {
        $validator->after(function ($validator) use ($input, $categoryId, $departmentId, $prefix) {
            $missing = CategoryUnitConversion::missingFor(
                CategoryUnitConversion::TYPE_CHEMICAL,
                $categoryId,
                $departmentId,
                (int) ($input['unit_id'] ?? 0),
                (array) ($input['conversions'] ?? [])
            );

            foreach ($missing as $unit) {
                $validator->errors()->add(
                    $prefix.'conversions.'.$unit->unit_id,
                    'Vui lòng khai hệ số quy đổi sang '.($unit->unit_short_name ?: $unit->unit_name)
                    .' - đơn vị phòng '.($unit->department_short ?: $unit->department_name).' đang dùng cho hoá chất này.'
                );
            }
        });
    }

    private function saveConversions(array $input, int $categoryId, int $departmentId): void
    {
        CategoryUnitConversion::saveDeclarations(
            CategoryUnitConversion::TYPE_CHEMICAL,
            $categoryId,
            $departmentId,
            (int) ($input['unit_id'] ?? 0),
            (array) ($input['conversions'] ?? []),
            $this->actor(),
            (array) ($input['conversion_dirs'] ?? [])
        );
    }

    /** Giá trị ghi DB - $input là $request->all() (Cập nhật) hoặc một dòng items[i] (Thêm mới). */
    private function payload(array $input): array
    {
        return [
            'unit_id' => (int) ($input['unit_id'] ?? 0),
            'pack_weight_kg' => $this->packWeightKg($input),
            'shelf_life_months' => $this->nullIfBlank($input['shelf_life_months'] ?? null),
            'min_stock' => $this->nullIfBlank($input['min_stock'] ?? null),
            'max_stock' => $this->nullIfBlank($input['max_stock'] ?? null),
            'storage_condition_id' => ! empty($input['storage_condition_id']) ? (int) $input['storage_condition_id'] : null,
            'default_location_id' => ! empty($input['default_location_id']) ? (int) $input['default_location_id'] : null,
            'note' => $this->nullIfBlank($input['note'] ?? null),
        ];
    }

    private function nullIfBlank($value)
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * "Khối lượng quy đổi" chỉ có nghĩa khi đơn vị của phòng thuộc nhóm đếm / bao bì:
     * đơn vị khối lượng / thể tích đã tự quy đổi ra kg được. Chọn đơn vị nhóm khác thì
     * bỏ con số này đi để không còn dữ liệu thừa treo lại.
     */
    private function packWeightKg(array $input): ?float
    {
        $value = $this->nullIfBlank($input['pack_weight_kg'] ?? null);

        if ($value === null) {
            return null;
        }

        $group = DB::table('units')->where('id', (int) ($input['unit_id'] ?? 0))->value('unit_group');

        return $group === 'count' ? (float) $value : null;
    }

    private function messages(): array
    {
        return [
            'category_id.required' => 'Vui lòng chọn hoá chất cần khai.',
            'category_id.exists' => 'Hoá chất được chọn không tồn tại.',
            'category_id.unique' => 'Phòng ban đã khai hoá chất này rồi, hãy sửa dòng đang có.',
            'unit_id.required' => 'Vui lòng chọn đơn vị tính của phòng cho hoá chất này.',
            'unit_id.exists' => 'Đơn vị tính không hợp lệ.',
            'pack_weight_kg.numeric' => 'Khối lượng quy đổi phải là số.',
            'pack_weight_kg.gt' => 'Khối lượng quy đổi phải lớn hơn 0.',
            'shelf_life_months.integer' => 'Hạn dùng nội bộ phải là số tháng nguyên.',
            'shelf_life_months.min' => 'Hạn dùng nội bộ tối thiểu 1 tháng.',
            'shelf_life_months.max' => 'Hạn dùng nội bộ tối đa 1200 tháng (100 năm).',
            'min_stock.numeric' => 'Ngưỡng tồn tối thiểu phải là số.',
            'min_stock.min' => 'Ngưỡng tồn tối thiểu không được âm.',
            'max_stock.numeric' => 'Ngưỡng tồn tối đa phải là số.',
            'max_stock.min' => 'Ngưỡng tồn tối đa không được âm.',
            'default_location_id.exists' => 'Định khu không thuộc phòng ban đang chọn.',
            'storage_condition_id.exists' => 'Điều kiện bảo quản được chọn không tồn tại.',
            'note.max' => 'Ghi chú tối đa 500 ký tự.',
        ];
    }

    /**
     * Quay lại trang Danh Mục Hoá Chất và mở sẵn tab "Hoá Chất Của Phòng".
     *
     * Trang có 2 tab nên phải nói rõ tab nào, nếu không người dùng bấm lưu ở tab 2
     * lại thấy màn hình nhảy về tab 1.
     */
    private function backToTab()
    {
        return redirect()->back()->with('activeTab', 'department');
    }

    private function departmentId(): int
    {
        return (int) (session('user')['selected_department_id'] ?? 0);
    }

    private function actor(): string
    {
        return \App\Support\Signer::actor();
    }
}
