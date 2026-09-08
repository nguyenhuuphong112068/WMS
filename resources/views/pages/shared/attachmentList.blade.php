{{--
|--------------------------------------------------------------------------
| NÚT MỞ CỬA SỔ XEM FILE ĐÍNH KÈM (dùng chung cho màn hình Nhập và Tồn Kho)
|--------------------------------------------------------------------------
| Tham số:
|   $attachments  - collection bản ghi file đính kèm (id, file_name, is_active,
|                   created_by, created_at)
|   $routePrefix  - tiền tố tên route, ví dụ 'pages.import.standardImport.'
|                   (cần route <prefix>downloadAttachment và <prefix>toggleAttachmentStatus)
|   $statusPerm   - tên quyền được đổi trạng thái file; bỏ trống thì chỉ xem
|   $code         - mã ống chuẩn / mã xuất nhập của phiếu nhập
|   $name         - tên vật tư / hoá chất / chất chuẩn liên quan
|   $typeLabel    - nhãn loại hàng ('Vật tư' / 'Hoá chất' / 'Chất chuẩn')
|
| Bấm nút mở modal #attachmentListModal (markup ở pages/shared/attachmentListModal,
| CSS + JS ở pages/shared/attachmentListAssets - đã nạp sẵn trong shared/assets).
--}}
@php
    $attStatusPerm = $statusPerm ?? null;
    $attCanToggle = $attStatusPerm && user_can($attStatusPerm);

    $attUploadPerm = $uploadPerm ?? null;
    $attCanUpload = $attUploadPerm && user_can($attUploadPerm);

    $attFiles = $attachments
        ->map(function ($a) use ($routePrefix) {
            $on = ! isset($a->is_active) || $a->is_active;

            return [
                'id' => $a->id,
                'file_name' => $a->file_name,
                'created_by' => $a->created_by ?: '—',
                'created_at' => $a->created_at
                    ? \Carbon\Carbon::parse($a->created_at)->format('d/m/Y H:i')
                    : '—',
                'is_active' => $on ? 1 : 0,
                'url' => route($routePrefix . 'downloadAttachment', ['id' => $a->id]),
            ];
        })
        ->values();
@endphp

@if ($attachments->isNotEmpty() || $attCanUpload)
    <button type="button" class="btn btn-xs btn-outline-primary att-open-modal" title="Xem/thêm file đính kèm"
        data-code="{{ $code ?? '' }}" data-name="{{ $name ?? '' }}" data-type-label="{{ $typeLabel ?? '' }}"
        data-toggle-url="{{ $attCanToggle ? route($routePrefix . 'toggleAttachmentStatus') : '' }}"
        data-upload-url="{{ $attCanUpload && isset($parentId) ? route($routePrefix . 'uploadAttachment', ['id' => $parentId]) : '' }}"
        data-files="{{ json_encode($attFiles) }}">
        <i class="fas fa-paperclip"></i> ({{ $attachments->count() }})
    </button>
@else
    <span class="text-muted">—</span>
@endif
