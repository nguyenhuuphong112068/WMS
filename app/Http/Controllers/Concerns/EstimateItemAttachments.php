<?php

namespace App\Http\Controllers\Concerns;

use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\AttachmentBackup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * FILE ĐÍNH KÈM THEO TỪNG MỤC DỰ TRÙ (Dự Trù Hoá Chất / Chất Chuẩn) - cùng cách làm với Dự Trù
 * Vật Tư (MaterialEstimateController::uploadAttachment ...). Chỉ đính kèm / xoá khi phiếu còn
 * Nháp / Bị từ chối; xoá là xoá mềm (active = 0) để truy vết.
 *
 * Controller dùng trait phải khai:
 *   - hằng TABLE, ITEM_TABLE, SIGN_TABLE, ESTIMATE_FK, LABEL
 *   - hằng ATTACHMENT_TABLE, ATTACHMENT_FOLDER, ATTACHMENT_ITEM_FK (cột id mục dự trù ở bảng file)
 *   - cancelConfig()['item_fk'] (cột id phiếu ở bảng mục - bảng file dùng cùng tên cột)
 *   - findOwn(), editable(), departmentId(), actor(), nullIfBlank(), canViewAsPurchaser(),
 *     writeHistory(), cancelConfig()['category_table' / 'name_table' / 'name_fk']
 *
 * Form gửi lên: list_id, item_id, attachments[].
 */
trait EstimateItemAttachments
{
    /** Đuôi file được đính kèm - giống Dự Trù Vật Tư. */
    protected static function attachmentMimes(): string
    {
        return 'pdf,doc,docx,xls,xlsx,csv,ppt,pptx,txt,jpg,jpeg,png,gif,bmp,webp,zip,rar,7z,msg,eml';
    }

    public function uploadAttachment(Request $request)
    {
        $list = $this->findOwn($request->list_id);

        if (! $list) {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::LABEL.' cần đính kèm file!');
        }

        $validator = Validator::make($request->all(), [
            'attachments' => ['required', 'array', 'max:20'],
            'attachments.*' => ['file', 'max:20480', 'mimes:'.self::attachmentMimes()],
            'note' => ['nullable', 'max:255'],
            'item_id' => ['required', 'integer'],
        ], [
            'attachments.required' => 'Vui lòng chọn file cần đính kèm.',
            'item_id.required' => 'File đính kèm phải gắn với một mục dự trù.',
            'attachments.max' => 'Mỗi lần tải tối đa 20 file.',
            'attachments.*.file' => 'File tải lên không hợp lệ.',
            'attachments.*.max' => 'Mỗi file tối đa 20MB.',
            'attachments.*.mimes' => 'Định dạng file không được hỗ trợ.',
            'note.max' => 'Ghi chú tối đa 255 ký tự.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->errors()->first());
        }

        if (! $this->editable($list)) {
            return redirect()->back()->with('error', 'Phiếu '.$list->code.' đã trình ký nên không đính kèm file được nữa!');
        }

        // Mỗi file phải gắn với một mục dự trù thuộc đúng phiếu này
        $itemId = DB::table(self::ITEM_TABLE)
            ->where('id', $request->item_id)
            ->where($this->cancelConfig()['item_fk'], $list->id)
            ->where('active', 1)
            ->value('id');

        if (! $itemId) {
            return redirect()->back()->with('error', 'Không tìm thấy mục dự trù cần đính kèm file!');
        }

        $names = DB::transaction(fn () => $this->storeItemFiles(
            (int) $list->id, (int) $itemId, $request->file('attachments', []), $this->nullIfBlank($request->note)
        ));

        if (! $names) {
            return redirect()->back()->with('error', 'Không có file hợp lệ nào được tải lên!');
        }

        AuditTrialController::log('Thêm mới', self::ATTACHMENT_TABLE, $list->id, $list->code, 'Đính kèm file: '.implode(', ', $names));

        if ($list->app_status !== 'draft') {
            self::writeHistory($list->id, 'Đính kèm file', null, $list->app_status, $list->app_status, 'Đính kèm '.count($names).' file: '.implode(', ', $names));
        }

        return redirect()->back()->with('success', 'Đã đính kèm '.count($names).' file vào phiếu '.$list->code.'!');
    }

    /**
     * Xem / tải file: người trong phòng lập phiếu, người được chỉ định ký phiếu, hoặc bộ phận mua
     * hàng (phiếu đã duyệt, xem từ tab "Đơn dự trù đã được phê duyệt").
     */
    public function downloadAttachment($id)
    {
        $listFk = $this->cancelConfig()['item_fk'];

        $attachment = DB::table(self::ATTACHMENT_TABLE)
            ->join(self::TABLE, self::ATTACHMENT_TABLE.'.'.$listFk, '=', self::TABLE.'.id')
            ->select(self::ATTACHMENT_TABLE.'.*', self::TABLE.'.department_id')
            ->where(self::ATTACHMENT_TABLE.'.id', $id)
            ->where(self::ATTACHMENT_TABLE.'.active', 1)
            ->first();

        if (! $attachment) {
            abort(404, 'Không tìm thấy file đính kèm.');
        }

        $isSigner = DB::table(self::SIGN_TABLE)
            ->where(self::ESTIMATE_FK, $attachment->{$listFk})
            ->where('user_id', session('user')['userId'] ?? 0)
            ->where('active', 1)
            ->exists();

        $isPurchaser = $this->canViewAsPurchaser(DB::table(self::TABLE)->where('id', $attachment->{$listFk})->first());

        if ((int) $attachment->department_id !== $this->departmentId() && ! $isSigner && ! $isPurchaser) {
            abort(403, 'Bạn không có quyền xem file này.');
        }

        if (! Storage::exists($attachment->file_path)) {
            abort(404, 'File không tồn tại trên hệ thống lưu trữ.');
        }

        return Storage::response($attachment->file_path, $attachment->file_name, [
            'Content-Disposition' => "inline; filename*=UTF-8''".rawurlencode($attachment->file_name),
        ]);
    }

    public function deleteAttachment(Request $request)
    {
        $attachment = DB::table(self::ATTACHMENT_TABLE)
            ->where('id', $request->id)
            ->where('active', 1)
            ->first();

        $list = $attachment ? $this->findOwn($attachment->{$this->cancelConfig()['item_fk']}) : null;

        if (! $attachment || ! $list) {
            return redirect()->back()->with('error', 'Không tìm thấy file đính kèm cần xoá!');
        }

        if (! $this->editable($list)) {
            return redirect()->back()->with('error', 'Phiếu '.$list->code.' đã trình ký nên không xoá file đính kèm được nữa!');
        }

        DB::table(self::ATTACHMENT_TABLE)->where('id', $attachment->id)->update([
            'active' => 0,
            'deleted_by' => $this->actor(),
            'deleted_at' => now(),
            'updated_at' => now(),
        ]);

        AuditTrialController::log('Xoá', self::ATTACHMENT_TABLE, $list->id, $list->code, 'Xoá file đính kèm: '.$attachment->file_name);

        if ($list->app_status !== 'draft') {
            self::writeHistory($list->id, 'Xoá file đính kèm', null, $list->app_status, $list->app_status, 'Xoá file: '.$attachment->file_name);
        }

        return redirect()->back()->with('success', 'Đã xoá file '.$attachment->file_name.'!');
    }

    /**
     * Lưu file đính kèm của một mục dự trù.
     *
     * @return string[] tên các file đã lưu
     */
    protected function storeItemFiles(int $listId, int $itemId, array $files, ?string $note): array
    {
        $names = [];

        foreach ($files as $file) {
            if (! $file || ! $file->isValid()) {
                continue;
            }

            $path = $file->store('public/'.self::ATTACHMENT_FOLDER);
            AttachmentBackup::copy($path, self::ATTACHMENT_FOLDER);

            DB::table(self::ATTACHMENT_TABLE)->insert([
                $this->cancelConfig()['item_fk'] => $listId,
                self::ATTACHMENT_ITEM_FK => $itemId,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'file_type' => $file->getClientMimeType() ?: $file->getClientOriginalExtension(),
                'note' => $note,
                'active' => 1,
                'created_by' => $this->actor(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $names[] = $file->getClientOriginalName();
        }

        return $names;
    }

    /** File còn hiệu lực của các mục: [item_id => Collection]. */
    protected static function filesByItem(array $itemIds)
    {
        if (! $itemIds) {
            return collect();
        }

        return DB::table(self::ATTACHMENT_TABLE)
            ->whereIn(self::ATTACHMENT_ITEM_FK, $itemIds)
            ->where('active', 1)
            ->orderBy('id', 'asc')
            ->get()
            ->groupBy(self::ATTACHMENT_ITEM_FK);
    }

    /**
     * Modal thêm nhiều mặt hàng gửi file theo items[i][files][]. Trả file theo chỉ số dòng MỚI
     * sau khi pruneEmptyItemRows() bỏ dòng trống - $keptIndexes là chỉ số GỐC của các dòng còn giữ.
     *
     * @return array<int, \Illuminate\Http\UploadedFile[]>
     */
    protected function itemFilesByIndex(Request $request, array $keptIndexes): array
    {
        $files = [];

        foreach ($keptIndexes as $newIndex => $origIndex) {
            $lineFiles = array_values(array_filter((array) $request->file('items.'.$origIndex.'.files', [])));

            if ($lineFiles) {
                $files[$newIndex] = $lineFiles;
            }
        }

        return $files;
    }

    /** Luật kiểm tra file của modal thêm nhiều mặt hàng. */
    protected static function itemFileRules(): array
    {
        return [
            'items.*.files' => ['nullable', 'array', 'max:10'],
            'items.*.files.*' => ['file', 'max:20480', 'mimes:'.self::attachmentMimes()],
        ];
    }

    protected static function itemFileMessages(string $itemLabel): array
    {
        return [
            'items.*.files.max' => 'Mỗi '.$itemLabel.' đính kèm tối đa 10 file.',
            'items.*.files.*.file' => 'File đính kèm không hợp lệ.',
            'items.*.files.*.max' => 'Mỗi file đính kèm tối đa 20MB.',
            'items.*.files.*.mimes' => 'Định dạng file đính kèm không được hỗ trợ.',
        ];
    }

    /**
     * Mỗi mã danh mục (category_id) chỉ được MỘT dòng còn hiệu lực trong một phiếu dự trù - cần
     * nhiều tháng thì khai thêm cột số lượng ở dòng đã có. Kiểm cả dòng đã lưu trong phiếu và các
     * dòng trùng nhau trong cùng lần gửi (giống Dự Trù Vật Tư).
     *
     * @param  array<string, int|null>  $categoryIds  [khoá lỗi => category_id]
     * @return array<string, string> [khoá lỗi => thông báo]
     */
    protected function duplicateCategoryErrors(?int $listId, array $categoryIds, string $itemLabel, ?int $exceptItemId = null): array
    {
        $categoryIds = array_filter(array_map(fn ($id) => $id ? (int) $id : null, $categoryIds));

        if (! $categoryIds) {
            return [];
        }

        $cfg = $this->cancelConfig();

        $seen = $listId
            ? DB::table(self::ITEM_TABLE)
                ->where($cfg['item_fk'], $listId)
                ->where('active', 1)
                ->whereNotNull('category_id')
                ->when($exceptItemId, fn ($query) => $query->where('id', '!=', $exceptItemId))
                ->pluck('category_id')
                ->mapWithKeys(fn ($id) => [(int) $id => 'saved'])
                ->all()
            : [];

        $labels = DB::table($cfg['category_table'])
            ->leftJoin($cfg['name_table'], $cfg['category_table'].'.'.$cfg['name_fk'], '=', $cfg['name_table'].'.id')
            ->whereIn($cfg['category_table'].'.id', array_values($categoryIds))
            ->select($cfg['category_table'].'.id', $cfg['category_table'].'.code', $cfg['name_table'].'.name')
            ->get()
            ->mapWithKeys(fn ($row) => [(int) $row->id => trim($row->code.' — '.$row->name, ' —')])
            ->all();

        $errors = [];

        foreach ($categoryIds as $key => $categoryId) {
            $label = $labels[$categoryId] ?? ucfirst($itemLabel);

            if (isset($seen[$categoryId])) {
                $errors[$key] = $seen[$categoryId] === 'saved'
                    ? $label.' đã có trong phiếu, mỗi mã '.$itemLabel.' chỉ được một dòng - hãy sửa số lượng ở dòng đã có.'
                    : $label.' bị khai trùng nhiều dòng, mỗi mã '.$itemLabel.' chỉ được một dòng.';

                continue;
            }

            $seen[$categoryId] = 'new';
        }

        return $errors;
    }
}
