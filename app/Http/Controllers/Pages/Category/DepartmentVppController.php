<?php

namespace App\Http\Controllers\Pages\Category;

use App\Http\Controllers\Concerns\RequiresChangeReason;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\VppWatchlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * DANH MỤC - VPP, TAB "VPP CỦA PHÒNG"
 *
 * Tab này nằm chung trang với tab "Danh Mục VPP Công Ty" (VppCategoryController::index).
 * Controller này chỉ nhận các thao tác thêm / sửa / khoá rồi quay lại đúng tab đó.
 *
 * Danh mục VPP (vpp_categories) dùng chung toàn công ty vì nó mô tả BẢN CHẤT của
 * VPP: tên, nhà sản xuất, thông tin kỹ thuật, phân loại theo bộ tiêu chí công ty, bộ
 * phận mua hàng. Màn hình này khai phần CÁCH DÙNG của riêng phòng ban đang chọn: phân loại
 * theo bộ nhóm của phòng, ngưỡng tồn tối thiểu / tối đa, định khu (đơn vị tính ở danh mục công ty).
 *
 * PHÂN LOẠI ở đây (classification_id) là bộ nhóm do chính phòng khai ở Dữ Liệu Gốc → Phân
 * Loại, không liên quan tới phân loại bản chất VPP của danh mục công ty.
 *
 * ĐỊNH KHU (default_location_id) là chỗ DỰ KIẾN để VPP, chỉ dùng để điền sẵn ô vị trí
 * lúc nhập. Vị trí THỰC TẾ của từng lô nằm ở vpp_imports.location_id, hai cái này
 * khác nhau và không thay thế cho nhau được.
 *
 * Dòng phòng do HỆ THỐNG tạo: thêm một mã ở Danh Mục VPP Công Ty là mọi phòng ban có ngay một
 * dòng tương ứng (DepartmentVpp::syncRows) - số mã chung và số mã của phòng luôn bằng nhau.
 * Phòng không thêm / khoá dòng, chỉ SỬA phần riêng của mình.
 */
class DepartmentVppController extends Controller
{
    use RequiresChangeReason;

    private const TABLE = 'vpp_department_categories';

    private const LABEL = 'VPP của phòng';

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

        // Không cho đổi VPP của một dòng: dòng phòng do hệ thống tạo theo danh mục chung.
        $validator = Validator::make(
            $request->all(),
            $this->rules($departmentId) + $this->changeReasonRules(),
            $this->messages() + $this->changeReasonMessages()
        );

        $this->checkStockRange($validator, $request->all());

        if ($validator->fails()) {
            return $this->backToTab()->withErrors($validator, 'dmUpdateErrors')->withInput();
        }

        DB::table(self::TABLE)->where('id', $current->id)->update($this->payload($request->all()) + [
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        $locations = DB::table('locations')->pluck('code', 'id');
        $classifications = DB::table('department_classification')->pluck('name', 'id');

        AuditTrialController::log(
            'Cập nhật',
            self::TABLE,
            $current->id,
            'phân loại: '.($classifications[$current->classification_id] ?? 'chưa khai')
                .' | ngưỡng: '.($current->min_stock ?? 'chưa khai')
                .' | ngưỡng tối đa: '.($current->max_stock ?? 'chưa khai')
                .' | định khu: '.($locations[$current->default_location_id] ?? 'chưa khai'),
            'phân loại: '.($classifications[(int) $request->classification_id] ?? 'chưa khai')
                .' | ngưỡng: '.($request->min_stock ?: 'chưa khai')
                .' | ngưỡng tối đa: '.($request->max_stock ?: 'chưa khai')
                .' | định khu: '.($locations[(int) $request->default_location_id] ?? 'chưa khai')
                .' | Lý do: '.$this->changeReason($request)
        );

        return $this->backToTab()->with('success', 'Cập nhật '.self::LABEL.' thành công!');
    }

    /**
     * "Đề nghị dự trù VPP" bấm ngay trên một dòng của tab "VPP Của Phòng" - bắt buộc
     * nêu số lượng cần đề nghị + lý do dự trù. Hiện lại ở tab "Danh sách VPP cần dự trù" bên Dự Trù VPP -
     * xem App\Support\VppWatchlist.
     */
    public function watchlistRemember(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => ['required', 'integer', 'exists:vpp_categories,id'],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:999999999'],
            'note' => ['required', 'max:500'],
        ], [
            'category_id.required' => 'Không xác định được VPP cần đề nghị dự trù!',
            'category_id.exists' => 'VPP được chọn không tồn tại.',
            'quantity.required' => 'Vui lòng nhập số lượng cần đề nghị.',
            'quantity.numeric' => 'Số lượng cần đề nghị phải là số.',
            'quantity.gt' => 'Số lượng cần đề nghị phải lớn hơn 0.',
            'quantity.max' => 'Số lượng cần đề nghị quá lớn.',
            'note.required' => 'Vui lòng nhập lý do dự trù.',
            'note.max' => 'Lý do dự trù tối đa 500 ký tự.',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()]);
        }

        $deptVpp = DB::table(self::TABLE)
            ->where('department_id', $this->departmentId())
            ->where('category_id', (int) $request->category_id)
            ->first();

        if ($deptVpp && $deptVpp->status_id != 1) {
            return response()->json([
                'success' => false,
                'message' => 'VPP của phòng đã bị khoá, không thể đề nghị dự trù!',
            ]);
        }

        $result = VppWatchlist::remember(
            $this->departmentId(),
            (int) $request->category_id,
            null,
            null,
            (float) $request->quantity,
            $request->note,
            'category',
            $this->actor()
        );

        return response()->json($result);
    }

    private function rules(int $departmentId): array
    {
        $rules = [
            // Phân loại riêng của phòng, phải thuộc ĐÚNG phòng ban đang chọn
            'classification_id' => [
                'nullable',
                Rule::exists('department_classification', 'id')
                    ->where('department_id', $departmentId)
                    ->where('status_id', 1),
            ],
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

        $rules['id'] = ['required', 'exists:'.self::TABLE.',id'];

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

    /** Giá trị ghi DB - $input là $request->all() (Cập nhật) hoặc một dòng items[i] (Thêm mới). */
    private function payload(array $input): array
    {
        return [
            'classification_id' => ! empty($input['classification_id']) ? (int) $input['classification_id'] : null,
            'min_stock' => $this->nullIfBlank($input['min_stock'] ?? null),
            'max_stock' => $this->nullIfBlank($input['max_stock'] ?? null),
            'default_location_id' => ! empty($input['default_location_id']) ? (int) $input['default_location_id'] : null,
            'note' => $this->nullIfBlank($input['note'] ?? null),
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
            'category_id.required' => 'Vui lòng chọn VPP cần khai.',
            'category_id.exists' => 'VPP được chọn không tồn tại.',
            'category_id.unique' => 'Phòng ban đã khai VPP này rồi, hãy sửa dòng đang có.',
            'min_stock.numeric' => 'Ngưỡng tồn tối thiểu phải là số.',
            'min_stock.min' => 'Ngưỡng tồn tối thiểu không được âm.',
            'max_stock.numeric' => 'Ngưỡng tồn tối đa phải là số.',
            'max_stock.min' => 'Ngưỡng tồn tối đa không được âm.',
            'default_location_id.exists' => 'Định khu không thuộc phòng ban đang chọn.',
            'note.max' => 'Ghi chú tối đa 500 ký tự.',
        ];
    }

    /**
     * Quay lại trang Danh Mục VPP và mở sẵn tab "VPP Của Phòng".
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
