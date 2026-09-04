<?php

namespace App\Http\Controllers\Pages\Category;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * DANH MỤC - VẬT TƯ, TAB "VẬT TƯ CỦA PHÒNG"
 *
 * Tab này nằm chung trang với tab "Danh Mục Vật Tư Công Ty" (MaterialCategoryController::index).
 * Controller này chỉ nhận các thao tác thêm / sửa / khoá rồi quay lại đúng tab đó.
 *
 * Danh mục vật tư (material_categories) dùng chung toàn công ty vì nó mô tả BẢN CHẤT của
 * vật tư: tên, nhà sản xuất, thông tin kỹ thuật. Màn hình này khai phần CÁCH DÙNG của
 * riêng phòng ban đang chọn: phân loại theo bộ nhóm của phòng, đơn vị tính, ngưỡng tồn
 * tối thiểu.
 *
 * Mỗi dòng ở đây cũng chính là lời khai "phòng tôi có dùng vật tư này", dùng cho cột
 * "Phòng Ban Đang Dùng" ở tab Danh Mục Vật Tư Công Ty.
 *
 * Không xoá cứng: khoá (status_id = 0) để giữ lại vết đã từng khai.
 */
class DepartmentMaterialController extends Controller
{
    private const TABLE = 'material_department_categories';

    private const LABEL = 'vật tư của phòng';

    public function store(Request $request)
    {
        $departmentId = $this->departmentId();

        $validator = Validator::make($request->all(), $this->rules($departmentId), $this->messages());

        if ($validator->fails()) {
            return $this->backToTab()->withErrors($validator, 'dmCreateErrors')->withInput();
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
            'Khai vật tư cho phòng ban, category_id: '.$request->category_id
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

        // Không cho đổi vật tư của một dòng đã khai: đó là khoá của dòng. Khai nhầm thì
        // khoá dòng cũ rồi khai dòng mới, để giữ vết.
        $validator = Validator::make($request->all(), $this->rules($departmentId, true), $this->messages());

        if ($validator->fails()) {
            return $this->backToTab()->withErrors($validator, 'dmUpdateErrors')->withInput();
        }

        DB::table(self::TABLE)->where('id', $current->id)->update($this->payload($request) + [
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        $units = DB::table('units')->pluck('name', 'id');
        $classifications = DB::table('material_classifications')->pluck('name', 'id');

        AuditTrialController::log(
            'Cập nhật',
            self::TABLE,
            $current->id,
            'phân loại: '.($classifications[$current->classification_id] ?? 'chưa khai')
                .' | đơn vị: '.($units[$current->unit_id] ?? 'chưa khai')
                .' | ngưỡng: '.($current->min_stock ?? 'chưa khai'),
            'phân loại: '.($classifications[(int) $request->classification_id] ?? 'chưa khai')
                .' | đơn vị: '.($units[(int) $request->unit_id] ?? 'chưa khai')
                .' | ngưỡng: '.($request->min_stock ?: 'chưa khai')
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
            'classification_id' => [
                'nullable',
                Rule::exists('material_classifications', 'id')
                    ->where('department_id', $departmentId)
                    ->where('status_id', 1),
            ],
            'unit_id' => ['required', 'integer', 'exists:units,id'],
            'min_stock' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'max:500'],
        ];

        if ($isUpdate) {
            $rules['id'] = ['required', 'exists:'.self::TABLE.',id'];

            return $rules;
        }

        $rules['category_id'] = [
            'required',
            'exists:material_categories,id',
            // Mỗi phòng chỉ khai một dòng cho một vật tư, khớp ràng buộc unique ở DB
            Rule::unique(self::TABLE, 'category_id')->where('department_id', $departmentId),
        ];

        return $rules;
    }

    private function payload(Request $request): array
    {
        return [
            'classification_id' => $request->classification_id ? (int) $request->classification_id : null,
            'unit_id' => (int) $request->unit_id,
            'min_stock' => $this->nullIfBlank($request->min_stock),
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
            'category_id.required' => 'Vui lòng chọn vật tư cần khai.',
            'category_id.exists' => 'Vật tư được chọn không tồn tại.',
            'category_id.unique' => 'Phòng ban đã khai vật tư này rồi, hãy sửa dòng đang có.',
            'classification_id.exists' => 'Phân loại không thuộc phòng ban đang chọn hoặc đã bị khoá.',
            'unit_id.required' => 'Vui lòng chọn đơn vị tính của phòng cho vật tư này.',
            'unit_id.exists' => 'Đơn vị tính không hợp lệ.',
            'min_stock.numeric' => 'Ngưỡng tồn tối thiểu phải là số.',
            'min_stock.min' => 'Ngưỡng tồn tối thiểu không được âm.',
            'note.max' => 'Ghi chú tối đa 500 ký tự.',
        ];
    }

    /**
     * Quay lại trang Danh Mục Vật Tư và mở sẵn tab "Vật Tư Của Phòng".
     *
     * Trang có 2 tab nên phải nói rõ tab nào, nếu không người dùng bấm lưu ở tab 2 lại
     * thấy màn hình nhảy về tab 1.
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
