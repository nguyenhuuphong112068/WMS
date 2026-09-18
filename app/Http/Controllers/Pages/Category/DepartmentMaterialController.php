<?php

namespace App\Http\Controllers\Pages\Category;

use App\Http\Controllers\Concerns\RequiresChangeReason;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\CategoryUnitConversion;
use App\Support\MaterialWatchlist;
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
 * vật tư: tên, nhà sản xuất, thông tin kỹ thuật, phân loại theo bộ tiêu chí công ty, bộ
 * phận mua hàng. Màn hình này khai phần CÁCH DÙNG của riêng phòng ban đang chọn: phân loại
 * theo bộ nhóm của phòng, đơn vị tính, ngưỡng tồn tối thiểu, định khu.
 *
 * PHÂN LOẠI ở đây (classification_id) là bộ nhóm do chính phòng khai ở Dữ Liệu Gốc → Phân
 * Loại, không liên quan tới phân loại bản chất vật tư của danh mục công ty.
 *
 * ĐỊNH KHU (default_location_id) là chỗ DỰ KIẾN để vật tư, chỉ dùng để điền sẵn ô vị trí
 * lúc nhập. Vị trí THỰC TẾ của từng lô nằm ở material_imports.location_id, hai cái này
 * khác nhau và không thay thế cho nhau được.
 *
 * Mỗi dòng ở đây cũng chính là lời khai "phòng tôi có dùng vật tư này", dùng cho cột
 * "Phòng Ban Đang Dùng" ở tab Danh Mục Vật Tư Công Ty.
 *
 * Không xoá cứng: khoá (status_id = 0) để giữ lại vết đã từng khai.
 */
class DepartmentMaterialController extends Controller
{
    use RequiresChangeReason;

    private const TABLE = 'material_department_categories';

    private const LABEL = 'vật tư của phòng';

    public function store(Request $request)
    {
        $departmentId = $this->departmentId();

        $validator = Validator::make($request->all(), $this->rules($departmentId), $this->messages());
        $this->checkConversions($validator, $request, (int) $request->category_id, $departmentId);
        $this->checkStockRange($validator, $request);

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

        $this->saveConversions($request, (int) $request->category_id, $departmentId);

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

        if ($current->status_id != 1) {
            return $this->backToTab()->with('error', 'Vật tư của phòng đã bị khoá, không thể chỉnh sửa!');
        }

        // Không cho đổi vật tư của một dòng đã khai: đó là khoá của dòng. Khai nhầm thì
        // khoá dòng cũ rồi khai dòng mới, để giữ vết.
        $validator = Validator::make(
            $request->all(),
            $this->rules($departmentId, true) + $this->changeReasonRules(),
            $this->messages() + $this->changeReasonMessages()
        );

        $this->checkConversions($validator, $request, (int) $current->category_id, $departmentId);

        $this->checkStockRange($validator, $request);

        if ($validator->fails()) {
            return $this->backToTab()->withErrors($validator, 'dmUpdateErrors')->withInput();
        }

        DB::table(self::TABLE)->where('id', $current->id)->update($this->payload($request) + [
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        $this->saveConversions($request, (int) $current->category_id, $departmentId);

        $units = DB::table('units')->pluck('name', 'id');
        $locations = DB::table('locations')->pluck('code', 'id');
        $classifications = DB::table('department_classification')->pluck('name', 'id');

        AuditTrialController::log(
            'Cập nhật',
            self::TABLE,
            $current->id,
            'phân loại: '.($classifications[$current->classification_id] ?? 'chưa khai')
                .' | đơn vị: '.($units[$current->unit_id] ?? 'chưa khai')
                .' | ngưỡng: '.($current->min_stock ?? 'chưa khai')
                .' | ngưỡng tối đa: '.($current->max_stock ?? 'chưa khai')
                .' | định khu: '.($locations[$current->default_location_id] ?? 'chưa khai'),
            'phân loại: '.($classifications[(int) $request->classification_id] ?? 'chưa khai')
                .' | đơn vị: '.($units[(int) $request->unit_id] ?? 'chưa khai')
                .' | ngưỡng: '.($request->min_stock ?: 'chưa khai')
                .' | ngưỡng tối đa: '.($request->max_stock ?: 'chưa khai')
                .' | định khu: '.($locations[(int) $request->default_location_id] ?? 'chưa khai')
                .' | Lý do: '.$this->changeReason($request)
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

    /**
     * "Đề nghị dự trù vật tư" bấm ngay trên một dòng của tab "Vật Tư Của Phòng" - bắt buộc
     * nêu lý do dự trù. Hiện lại ở tab "Danh sách vật tư cần dự trù" bên Dự Trù Vật Tư -
     * xem App\Support\MaterialWatchlist.
     */
    public function watchlistRemember(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => ['required', 'integer', 'exists:material_categories,id'],
            'note' => ['required', 'max:500'],
        ], [
            'category_id.required' => 'Không xác định được vật tư cần đề nghị dự trù!',
            'category_id.exists' => 'Vật tư được chọn không tồn tại.',
            'note.required' => 'Vui lòng nhập lý do dự trù.',
            'note.max' => 'Lý do dự trù tối đa 500 ký tự.',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()]);
        }

        $deptMaterial = DB::table(self::TABLE)
            ->where('department_id', $this->departmentId())
            ->where('category_id', (int) $request->category_id)
            ->first();

        if ($deptMaterial && $deptMaterial->status_id != 1) {
            return response()->json([
                'success' => false,
                'message' => 'Vật tư của phòng đã bị khoá, không thể đề nghị dự trù!',
            ]);
        }

        $result = MaterialWatchlist::remember(
            $this->departmentId(),
            (int) $request->category_id,
            null,
            null,
            $request->note,
            'category',
            $this->actor()
        );

        return response()->json($result);
    }

    private function rules(int $departmentId, bool $isUpdate = false): array
    {
        $rules = [
            // Phân loại riêng của phòng, phải thuộc ĐÚNG phòng ban đang chọn
            'classification_id' => [
                'nullable',
                Rule::exists('department_classification', 'id')
                    ->where('department_id', $departmentId)
                    ->where('status_id', 1),
            ],
            'unit_id' => ['required', 'integer', 'exists:units,id'],
            'min_stock' => ['nullable', 'numeric', 'min:0'],
            'max_stock' => ['nullable', 'numeric', 'min:0'],
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
            'exists:material_categories,id',
            // Mỗi phòng chỉ khai một dòng cho một vật tư, khớp ràng buộc unique ở DB
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
    private function checkStockRange($validator, Request $request): void
    {
        $validator->after(function ($validator) use ($request) {
            $min = trim((string) $request->min_stock);
            $max = trim((string) $request->max_stock);

            if ($min === '' || $max === '' || ! is_numeric($min) || ! is_numeric($max)) {
                return;
            }

            if ((float) $max < (float) $min) {
                $validator->errors()->add('max_stock', 'Ngưỡng tồn tối đa phải lớn hơn hoặc bằng ngưỡng tồn tối thiểu.');
            }
        });
    }

    /**
     * Bắt khai hệ số quy đổi khi phòng chọn đơn vị lệch với đơn vị phòng khác đang dùng.
     *
     * Cùng một mã vật tư mà mỗi phòng một đơn vị thì lúc CHUYỂN VẬT TƯ LIÊN PHÒNG BAN hệ
     * thống phải biết đổi qua lại; thiếu hệ số là phòng nhận không nhận hàng được.
     */
    private function checkConversions($validator, Request $request, int $categoryId, int $departmentId): void
    {
        $validator->after(function ($validator) use ($request, $categoryId, $departmentId) {
            $missing = CategoryUnitConversion::missingFor(
                CategoryUnitConversion::TYPE_MATERIAL,
                $categoryId,
                $departmentId,
                (int) $request->unit_id,
                (array) $request->input('conversions', [])
            );

            foreach ($missing as $unit) {
                $validator->errors()->add(
                    'conversions.'.$unit->unit_id,
                    'Vui lòng khai hệ số quy đổi sang '.($unit->unit_short_name ?: $unit->unit_name)
                    .' - đơn vị phòng '.($unit->department_short ?: $unit->department_name).' đang dùng cho vật tư này.'
                );
            }
        });
    }

    private function saveConversions(Request $request, int $categoryId, int $departmentId): void
    {
        CategoryUnitConversion::saveDeclarations(
            CategoryUnitConversion::TYPE_MATERIAL,
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
            'classification_id' => $request->classification_id ? (int) $request->classification_id : null,
            'unit_id' => (int) $request->unit_id,
            'min_stock' => $this->nullIfBlank($request->min_stock),
            'max_stock' => $this->nullIfBlank($request->max_stock),
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
            'category_id.required' => 'Vui lòng chọn vật tư cần khai.',
            'category_id.exists' => 'Vật tư được chọn không tồn tại.',
            'category_id.unique' => 'Phòng ban đã khai vật tư này rồi, hãy sửa dòng đang có.',
            'unit_id.required' => 'Vui lòng chọn đơn vị tính của phòng cho vật tư này.',
            'unit_id.exists' => 'Đơn vị tính không hợp lệ.',
            'min_stock.numeric' => 'Ngưỡng tồn tối thiểu phải là số.',
            'min_stock.min' => 'Ngưỡng tồn tối thiểu không được âm.',
            'max_stock.numeric' => 'Ngưỡng tồn tối đa phải là số.',
            'max_stock.min' => 'Ngưỡng tồn tối đa không được âm.',
            'default_location_id.exists' => 'Định khu không thuộc phòng ban đang chọn.',
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
