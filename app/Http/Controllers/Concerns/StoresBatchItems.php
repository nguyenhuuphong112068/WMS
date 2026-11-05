<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

/**
 * THÊM NHIỀU DÒNG MỘT LẦN - modal Thêm mới dạng bảng của Danh Mục (Vật Tư / Hoá Chất / Chất Chuẩn,
 * cả danh mục công ty lẫn danh mục của phòng).
 *
 * Form gửi items[i][<trường>], mỗi dòng một bản ghi. Controller kiểm tra từng dòng bằng chính
 * rules() / messages() của một bản ghi, chỉ thêm tiền tố items.*:
 *
 *   $items = $this->batchItems($request, fn ($item) => blank($item['chem_names_id'] ?? null));
 *   $validator = Validator::make(['items' => $items],
 *       $this->batchRules($this->rules()), $this->batchMessages($this->messages()));
 *
 * Lỗi của dòng i nằm ở khoá items.<i>.<trường>, view gom thành "Dòng N: ..." (shared/batchTable).
 * Một dòng lỗi thì không lưu dòng nào. Khi báo lỗi phải gửi lại đúng mảng $items đã lọc
 * (withInput(['items' => $items])) để số dòng khớp với bảng dựng lại.
 */
trait StoresBatchItems
{
    /** Số dòng tối đa của một lần lưu. */
    protected int $batchMax = 200;

    /** Các dòng hợp lệ về dạng (mảng), bỏ dòng $isBlank coi là để trống hoàn toàn. */
    protected function batchItems(Request $request, callable $isBlank): array
    {
        return collect((array) $request->input('items', []))
            ->filter(fn ($item) => is_array($item) && ! $isBlank($item))
            ->values()
            ->all();
    }

    /** Rules của một bản ghi áp cho từng dòng items.* */
    protected function batchRules(array $rules): array
    {
        $prefixed = ['items' => ['required', 'array', 'max:'.$this->batchMax]];

        foreach ($rules as $field => $rule) {
            $prefixed['items.*.'.$field] = $rule;
        }

        return $prefixed;
    }

    protected function batchMessages(array $messages, string $emptyMessage = 'Vui lòng thêm ít nhất một dòng.'): array
    {
        $prefixed = [
            'items.required' => $emptyMessage,
            'items.max' => 'Mỗi lần lưu tối đa :max dòng.',
        ];

        foreach ($messages as $key => $message) {
            $prefixed['items.*.'.$key] = $message;
        }

        return $prefixed;
    }
}
