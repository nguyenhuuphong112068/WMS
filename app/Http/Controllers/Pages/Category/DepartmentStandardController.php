<?php

namespace App\Http\Controllers\Pages\Category;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\CategoryUnitConversion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * DANH MỤC - CHẤT CHUẨN, TAB "CHẤT CHUẨN CỦA PHÒNG"
 *
 * Tab này nằm chung trang với tab "Danh Mục Chất Chuẩn Công Ty"
 * (StandardCategoryController::index). Controller này chỉ nhận các thao tác
 * thêm / sửa / khoá rồi quay lại đúng tab đó.
 *
 * Danh mục chất chuẩn (standard_categories) dùng chung toàn công ty vì nó mô tả BẢN
 * CHẤT của chất chuẩn. Màn hình này khai phần CÁCH DÙNG của riêng phòng ban đang chọn:
 * đơn vị tính, hạn dùng nội bộ sau khi mở ống, ngưỡng tồn tối thiểu, vị trí lưu trữ quy
 * hoạch, điều kiện bảo quản.
 *
 * Để trống một ô nghĩa là "theo mặc định của danh mục" - xem App\Support\DepartmentStandard.
 *
 * Riêng ĐƠN VỊ TÍNH thì bắt buộc: danh mục chung không còn cột đơn vị, mọi màn hình
 * Nhập / Xuất / Tồn của phòng đều đọc đơn vị từ dòng khai ở đây.
 *
 * Mỗi dòng ở đây cũng chính là lời khai "phòng tôi có dùng chất chuẩn này", dùng cho
 * cột "Phòng Ban Đang Dùng" ở tab Danh Mục Chất Chuẩn Công Ty.
 *
 * Không xoá cứng: khoá (status_id = 0) để giữ lại vết đã từng khai.
 */
class DepartmentStandardController extends Controller
{
    private const TABLE = 'standard_department_categories';

    private const LABEL = 'chất chuẩn của phòng';

    public function store(Request $request)
    {
        $departmentId = $this->departmentId();

        $validator = Validator::make($request->all(), $this->rules($departmentId), $this->messages());

        $this->checkConversions($validator, $request, (int) $request->category_id, $departmentId);

        if ($validator->fails()) {
            return $this->backToTab()->withErrors($validator, 'dsCreateErrors')->withInput();
        }

        $id = DB::table(self::TABLE)->insertGetId($this->payload($request) + [
            'department_id' => $departmentId,
            'category_id' => (int) $request->category_id,
            'status_id' => 1,
            'created_by' => $this->actor(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->saveConversions($request, (int) $request->category_id, $departmentId);

        AuditTrialController::log(
            'Thêm mới',
            self::TABLE,
            $id,
            'NA',
            'Khai chất chuẩn cho phòng ban, category_id: '.$request->category_id
        );

        return $this->backToTab()->with('success', 'Đã khai '.self::LABEL.' thành công!');
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

        // Không cho đổi chất chuẩn của một dòng đã khai: đó là khoá của dòng.
        // Khai nhầm thì khoá dòng cũ rồi khai dòng mới, để giữ vết.
        $validator = Validator::make($request->all(), $this->rules($departmentId, true), $this->messages());

        $this->checkConversions($validator, $request, (int) $current->category_id, $departmentId);

        if ($validator->fails()) {
            return $this->backToTab()->withErrors($validator, 'dsUpdateErrors')->withInput();
        }

        DB::table(self::TABLE)->where('id', $current->id)->update($this->payload($request) + [
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        $this->saveConversions($request, (int) $current->category_id, $departmentId);

        $units = DB::table('units')->pluck('name', 'id');

        AuditTrialController::log(
            'Cập nhật',
            self::TABLE,
            $current->id,
            'đơn vị: '.($units[$current->unit_id] ?? 'chưa khai')
                .' | hạn: '.($current->shelf_life_months ?? 'mặc định')
                .' | ngưỡng: '.($current->min_stock ?? 'mặc định'),
            'đơn vị: '.($units[(int) $request->unit_id] ?? 'chưa khai')
                .' | hạn: '.($request->shelf_life_months ?: 'mặc định')
                .' | ngưỡng: '.($request->min_stock ?: 'mặc định')
        );

        return $this->backToTab()->with('success', 'Cập nhật '.self::LABEL.' thành công!');
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
            'status_id: '.$newStatus
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
            'shelf_life_months' => ['nullable', 'integer', 'min:1', 'max:1200'],
            'min_stock' => ['nullable', 'numeric', 'min:0'],
            'storage_condition_id' => ['nullable', 'exists:storage_conditions,id'],
            // Vị trí phải thuộc ĐÚNG phòng ban đang chọn, không mượn được của phòng khác
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
            'exists:standard_categories,id',
            // Mỗi phòng chỉ khai một dòng cho một chất chuẩn, khớp ràng buộc unique ở DB
            Rule::unique(self::TABLE, 'category_id')->where('department_id', $departmentId),
        ];

        return $rules;
    }

    /**
     * Bắt khai hệ số quy đổi khi phòng chọn đơn vị lệch với đơn vị phòng khác đang dùng.
     *
     * Cùng một mã mà mỗi phòng một đơn vị thì lúc chuyển kho hệ thống phải biết đổi qua
     * lại; thiếu hệ số là số lượng nhận về sẽ sai đơn vị.
     */
    private function checkConversions($validator, Request $request, int $categoryId, int $departmentId): void
    {
        $validator->after(function ($validator) use ($request, $categoryId, $departmentId) {
            $missing = CategoryUnitConversion::missingFor(
                CategoryUnitConversion::TYPE_STANDARD,
                $categoryId,
                $departmentId,
                (int) $request->unit_id,
                (array) $request->input('conversions', [])
            );

            foreach ($missing as $unit) {
                $validator->errors()->add(
                    'conversions.'.$unit->unit_id,
                    'Vui lòng khai hệ số quy đổi sang '.($unit->unit_short_name ?: $unit->unit_name)
                    .' - đơn vị phòng '.($unit->department_short ?: $unit->department_name).' đang dùng cho chất chuẩn này.'
                );
            }
        });
    }

    private function saveConversions(Request $request, int $categoryId, int $departmentId): void
    {
        CategoryUnitConversion::saveDeclarations(
            CategoryUnitConversion::TYPE_STANDARD,
            $categoryId,
            $departmentId,
            (int) $request->unit_id,
            (array) $request->input('conversions', []),
            $this->actor()
        );
    }

    private function payload(Request $request): array
    {
        return [
            'unit_id' => (int) $request->unit_id,
            'shelf_life_months' => $this->nullIfBlank($request->shelf_life_months),
            'min_stock' => $this->nullIfBlank($request->min_stock),
            'storage_condition_id' => $request->storage_condition_id ? (int) $request->storage_condition_id : null,
            'default_location_id' => $request->default_location_id ? (int) $request->default_location_id : null,
            'note' => $this->nullIfBlank($request->note),
        ];
    }

    private function nullIfBlank($value)
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function messages(): array
    {
        return [
            'category_id.required' => 'Vui lòng chọn chất chuẩn cần khai.',
            'category_id.exists' => 'Chất chuẩn được chọn không tồn tại.',
            'category_id.unique' => 'Phòng ban đã khai chất chuẩn này rồi, hãy sửa dòng đang có.',
            'unit_id.required' => 'Vui lòng chọn đơn vị tính của phòng cho chất chuẩn này.',
            'unit_id.exists' => 'Đơn vị tính không hợp lệ.',
            'shelf_life_months.integer' => 'Hạn dùng nội bộ phải là số tháng nguyên.',
            'shelf_life_months.min' => 'Hạn dùng nội bộ tối thiểu 1 tháng.',
            'shelf_life_months.max' => 'Hạn dùng nội bộ tối đa 1200 tháng (100 năm).',
            'min_stock.numeric' => 'Ngưỡng tồn tối thiểu phải là số.',
            'min_stock.min' => 'Ngưỡng tồn tối thiểu không được âm.',
            'default_location_id.exists' => 'Vị trí lưu trữ không thuộc phòng ban đang chọn.',
            'storage_condition_id.exists' => 'Điều kiện bảo quản được chọn không tồn tại.',
            'note.max' => 'Ghi chú tối đa 500 ký tự.',
        ];
    }

    /**
     * Quay lại trang Danh Mục Chất Chuẩn và mở sẵn tab "Chất Chuẩn Của Phòng".
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
