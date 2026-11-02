<?php

namespace App\Http\Controllers\Pages\Category;

use App\Http\Controllers\Concerns\RequiresChangeReason;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\ProductList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * DANH MỤC - TAB "DANH SÁCH ... THEO SẢN PHẨM" (dùng chung Hoá chất + Chất chuẩn)
 *
 * Tab nằm chung trang Danh Mục của loại hàng (ChemicalCategoryController / StandardCategoryController
 * dựng dữ liệu), controller này chỉ nhận thêm / sửa / khoá rồi quay lại đúng tab. Lớp con khai TYPE
 * (App\Support\ProductList::TYPES): bảng, danh mục, quyền thao tác.
 *
 * Một gói = Tên sản phẩm + Chỉ tiêu kiểm + các mã ĐÃ KHAI CHO PHÒNG (kèm lượng dùng gợi ý, không bắt
 * buộc). Mỗi phòng tự khai gói của phòng mình, không có bước duyệt. Cặp Sản phẩm + Chỉ tiêu đang dùng
 * không trùng trong một phòng. Không xoá cứng: khoá (status_id = 0).
 */
abstract class ProductListController extends Controller
{
    use RequiresChangeReason;

    /** ProductList::CHEMICAL | ProductList::STANDARD */
    protected const TYPE = '';

    public function store(Request $request)
    {
        if (! user_can($this->config('permission'))) {
            return $this->backToTab()->with('error', 'Bạn không có quyền quản lý '.$this->label().'!');
        }

        $departmentId = $this->departmentId();
        $validator = Validator::make($request->all(), $this->rules(), $this->messages());

        $validator->after(function ($validator) use ($request, $departmentId) {
            $this->checkList($validator, $request, $departmentId);
        });

        if ($validator->fails()) {
            return $this->backToTab()->withErrors($validator, $this->bag('Create'))->withInput();
        }

        $id = DB::transaction(function () use ($request, $departmentId) {
            $id = DB::table($this->table())->insertGetId($this->payload($request) + [
                'department_id' => $departmentId,
                'status_id' => 1,
                'created_by' => $this->actor(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->saveItems($id, $request);

            return $id;
        });

        AuditTrialController::log('Thêm mới', $this->table(), $id, 'NA', $this->describe($id));

        return $this->backToTab()->with('success', 'Đã thêm '.$this->label().'!');
    }

    public function update(Request $request)
    {
        if (! user_can($this->config('permission'))) {
            return $this->backToTab()->with('error', 'Bạn không có quyền quản lý '.$this->label().'!');
        }

        $departmentId = $this->departmentId();
        $current = $this->find($request->id);

        if (! $current) {
            return $this->backToTab()->with('error', 'Không tìm thấy '.$this->label().' cần sửa!');
        }

        $validator = Validator::make(
            $request->all(),
            $this->rules() + $this->changeReasonRules(),
            $this->messages() + $this->changeReasonMessages()
        );

        $validator->after(function ($validator) use ($request, $departmentId, $current) {
            $this->checkList($validator, $request, $departmentId, $current->id);
        });

        if ($validator->fails()) {
            return $this->backToTab()->withErrors($validator, $this->bag('Update'))->withInput();
        }

        $before = $this->describe($current->id);

        DB::transaction(function () use ($request, $current) {
            DB::table($this->table())->where('id', $current->id)->update($this->payload($request) + [
                'updated_by' => $this->actor(),
                'updated_at' => now(),
            ]);

            // Danh sách mã ghi lại nguyên bộ theo thứ tự trên form; nội dung cũ lưu ở Audit Trail
            DB::table($this->itemTable())->where('list_id', $current->id)->delete();
            $this->saveItems($current->id, $request);
        });

        AuditTrialController::log(
            'Cập nhật',
            $this->table(),
            $current->id,
            $before,
            $this->describe($current->id).' | Lý do: '.$this->changeReason($request)
        );

        return $this->backToTab()->with('success', 'Đã cập nhật '.$this->label().'!');
    }

    public function deActive(Request $request)
    {
        if (! user_can($this->config('permission'))) {
            return $this->backToTab()->with('error', 'Bạn không có quyền quản lý '.$this->label().'!');
        }

        $current = $this->find($request->id);

        if (! $current) {
            return $this->backToTab()->with('error', 'Không tìm thấy '.$this->label().' cần thay đổi trạng thái!');
        }

        if ($guard = $this->guardChangeReason($request)) {
            return $guard->with('activeTab', 'product');
        }

        $newStatus = $current->status_id == 1 ? 0 : 1;

        // Mở khoá lại mà phòng đã có gói khác cùng Sản phẩm + Chỉ tiêu đang dùng thì không cho
        if ($newStatus == 1 && $this->duplicateExists($current->department_id, $current->product_name, $current->purpose_id, $current->id)) {
            return $this->backToTab()->with('error', 'Phòng đã có gói "'.$current->product_name.'" cùng chỉ tiêu đang dùng, không mở khoá được!');
        }

        DB::table($this->table())->where('id', $current->id)->update([
            'status_id' => $newStatus,
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        AuditTrialController::log(
            $newStatus == 1 ? 'Mở khoá' : 'Khoá',
            $this->table(),
            $current->id,
            'status_id: '.$current->status_id,
            'status_id: '.$newStatus.' | Lý do: '.$this->changeReason($request)
        );

        return $this->backToTab()->with('success', ($newStatus == 1 ? 'Đã mở khoá ' : 'Đã khoá ').$this->label().'!');
    }

    private function rules(): array
    {
        return [
            'product_name' => ['required', 'string', 'max:255'],
            'purpose_id' => ['required', 'integer'],
            'note' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1', 'max:'.ProductList::MAX_ITEMS],
            'items.*.category_id' => ['required', 'integer'],
            'items.*.amount' => ['nullable', 'numeric', 'gt:0', 'max:99999999'],
        ];
    }

    private function messages(): array
    {
        $noun = $this->config('noun');

        return [
            'product_name.required' => 'Vui lòng chọn tên sản phẩm.',
            'product_name.max' => 'Tên sản phẩm tối đa 255 ký tự.',
            'purpose_id.required' => 'Vui lòng chọn chỉ tiêu.',
            'note.max' => 'Ghi chú tối đa 500 ký tự.',
            'items.required' => 'Vui lòng thêm ít nhất một '.$noun.'.',
            'items.min' => 'Vui lòng thêm ít nhất một '.$noun.'.',
            'items.max' => 'Một gói tối đa '.ProductList::MAX_ITEMS.' '.$noun.'.',
            'items.*.category_id.required' => 'Dòng '.$noun.' chưa chọn mã.',
            'items.*.amount.numeric' => 'Lượng dùng gợi ý phải là số.',
            'items.*.amount.gt' => 'Lượng dùng gợi ý phải lớn hơn 0.',
            'items.*.amount.max' => 'Lượng dùng gợi ý quá lớn.',
        ];
    }

    /** Sản phẩm / chỉ tiêu còn hoạt động, mã phải đã khai cho phòng, không trùng mã, không trùng gói. */
    private function checkList($validator, Request $request, int $departmentId, ?int $ignoreId = null): void
    {
        $noun = $this->config('noun');
        $productName = trim((string) $request->product_name);

        if ($productName !== '' && ! DB::table('product_names')->where('name', $productName)->where('status_id', 1)->exists()) {
            $validator->errors()->add('product_name', 'Tên sản phẩm không có trong Dữ Liệu Gốc hoặc đã bị khoá.');
        }

        if ($request->purpose_id && ! DB::table('purposes')->where('id', $request->purpose_id)->where('status_id', 1)->exists()) {
            $validator->errors()->add('purpose_id', 'Chỉ tiêu không có trong Dữ Liệu Gốc hoặc đã bị khoá.');
        }

        if ($productName !== '' && $request->purpose_id
            && $this->duplicateExists($departmentId, $productName, (int) $request->purpose_id, $ignoreId)) {
            $validator->errors()->add('product_name', 'Phòng đã có gói cho sản phẩm "'.$productName.'" với chỉ tiêu này.');
        }

        $categoryIds = collect($request->input('items', []))->pluck('category_id')->filter()->map(fn ($id) => (int) $id);

        if ($categoryIds->count() !== $categoryIds->unique()->count()) {
            $validator->errors()->add('items', 'Một '.$noun.' chỉ được thêm một lần trong gói.');
        }

        $declared = DB::table($this->config('department')::TABLE)
            ->where('department_id', $departmentId)
            ->where('status_id', 1)
            ->whereIn('category_id', $categoryIds->unique())
            ->pluck('category_id')
            ->map(fn ($id) => (int) $id);

        if ($categoryIds->unique()->diff($declared)->isNotEmpty()) {
            $validator->errors()->add('items', 'Có '.$noun.' chưa khai (hoặc đã khoá) ở tab '.ucfirst($noun).' của phòng.');
        }
    }

    private function duplicateExists(int $departmentId, string $productName, int $purposeId, ?int $ignoreId = null): bool
    {
        return DB::table($this->table())
            ->where('department_id', $departmentId)
            ->where('product_name', $productName)
            ->where('purpose_id', $purposeId)
            ->where('status_id', 1)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();
    }

    private function payload(Request $request): array
    {
        $note = trim((string) $request->note);

        return [
            'product_name' => trim((string) $request->product_name),
            'purpose_id' => (int) $request->purpose_id,
            'note' => $note === '' ? null : $note,
        ];
    }

    private function saveItems(int $listId, Request $request): void
    {
        $rows = [];

        foreach (array_values($request->input('items', [])) as $index => $item) {
            $amount = trim((string) ($item['amount'] ?? ''));

            $rows[] = [
                'list_id' => $listId,
                'category_id' => (int) $item['category_id'],
                'amount' => $amount === '' ? null : (float) $amount,
                'sort_order' => $index + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table($this->itemTable())->insert($rows);
    }

    /** Nội dung gói dạng chữ cho Audit Trail: "Sản phẩm | Chỉ tiêu | C00012 x 5, C00020". */
    private function describe(int $id): string
    {
        $table = $this->table();
        $itemTable = $this->itemTable();
        $categoryTable = $this->config('category_table');

        $list = DB::table($table)
            ->leftJoin('purposes', $table.'.purpose_id', '=', 'purposes.id')
            ->select($table.'.*', 'purposes.name as purpose_name')
            ->where($table.'.id', $id)
            ->first();

        $items = DB::table($itemTable)
            ->leftJoin($categoryTable, $itemTable.'.category_id', '=', $categoryTable.'.id')
            ->where($itemTable.'.list_id', $id)
            ->orderBy($itemTable.'.sort_order')
            ->get([$categoryTable.'.code', $itemTable.'.amount'])
            ->map(fn ($item) => $item->code.($item->amount !== null ? ' x '.ProductList::amount($item->amount) : ''))
            ->implode(', ');

        return 'Sản phẩm: '.$list->product_name.' | Chỉ tiêu: '.($list->purpose_name ?: '—')
            .($list->note ? ' | Ghi chú: '.$list->note : '').' | '.ucfirst($this->config('noun')).': '.$items;
    }

    private function find($id)
    {
        return DB::table($this->table())
            ->where('id', $id)
            ->where('department_id', $this->departmentId())
            ->first();
    }

    private function config(string $key)
    {
        return ProductList::config(static::TYPE)[$key];
    }

    private function table(): string
    {
        return $this->config('table');
    }

    private function itemTable(): string
    {
        return $this->config('item_table');
    }

    private function label(): string
    {
        return 'danh sách '.$this->config('noun').' theo sản phẩm';
    }

    /** Túi lỗi của modal: plCreateErrors / plUpdateErrors (mỗi trang chỉ có một loại). */
    private function bag(string $mode): string
    {
        return 'pl'.$mode.'Errors';
    }

    /** Quay lại trang Danh Mục, mở sẵn tab "Danh sách ... theo sản phẩm". */
    private function backToTab()
    {
        return redirect()->back()->with('activeTab', 'product');
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
