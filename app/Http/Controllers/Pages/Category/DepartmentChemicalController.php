<?php

namespace App\Http\Controllers\Pages\Category;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
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
 * của chất. Màn hình này khai phần CÁCH DÙNG của riêng phòng ban đang chọn:
 * hạn dùng nội bộ, ngưỡng tồn tối thiểu, vị trí lưu trữ quy hoạch, điều kiện bảo quản.
 *
 * Để trống một ô nghĩa là "theo mặc định của danh mục" - xem App\Support\DepartmentChemical.
 *
 * Mỗi dòng ở đây cũng chính là lời khai "phòng tôi có dùng chất này", dùng cho cột
 * "Phòng Ban Đang Dùng" ở tab Danh Mục Hoá Chất Công Ty.
 *
 * Không xoá cứng: khoá (status_id = 0) để giữ lại vết đã từng khai.
 */
class DepartmentChemicalController extends Controller
{
    private const TABLE = 'department_chemicals';

    private const LABEL = 'hoá chất của phòng';

    public function store(Request $request)
    {
        $departmentId = $this->departmentId();

        $validator = Validator::make($request->all(), $this->rules($departmentId), $this->messages());

        if ($validator->fails()) {
            return $this->backToTab()->withErrors($validator, 'dcCreateErrors')->withInput();
        }

        $id = DB::table(self::TABLE)->insertGetId($this->payload($request) + [
            'department_id' => $departmentId,
            'category_id' => (int) $request->category_id,
            'status_id' => 1,
            'created_by' => $this->actor(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        AuditTrialController::log(
            'Thêm mới',
            self::TABLE,
            $id,
            'NA',
            'Khai hoá chất cho phòng ban, category_id: '.$request->category_id
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

        // Không cho đổi hoá chất của một dòng đã khai: đó là khoá của dòng.
        // Khai nhầm thì khoá dòng cũ rồi khai dòng mới, để giữ vết.
        $validator = Validator::make($request->all(), $this->rules($departmentId, true), $this->messages());

        if ($validator->fails()) {
            return $this->backToTab()->withErrors($validator, 'dcUpdateErrors')->withInput();
        }

        DB::table(self::TABLE)->where('id', $current->id)->update($this->payload($request) + [
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        AuditTrialController::log(
            'Cập nhật',
            self::TABLE,
            $current->id,
            'hạn: '.($current->shelf_life_months ?? 'mặc định').' | ngưỡng: '.($current->min_stock ?? 'mặc định'),
            'hạn: '.($request->shelf_life_months ?: 'mặc định').' | ngưỡng: '.($request->min_stock ?: 'mặc định')
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
            'exists:chemical_categories,id',
            // Mỗi phòng chỉ khai một dòng cho một hoá chất, khớp ràng buộc unique ở DB
            Rule::unique(self::TABLE, 'category_id')->where('department_id', $departmentId),
        ];

        return $rules;
    }

    private function payload(Request $request): array
    {
        return [
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
            'category_id.required' => 'Vui lòng chọn hoá chất cần khai.',
            'category_id.exists' => 'Hoá chất được chọn không tồn tại.',
            'category_id.unique' => 'Phòng ban đã khai hoá chất này rồi, hãy sửa dòng đang có.',
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
        return session('user')['fullName'] ?? 'NA';
    }
}
