{{--
| File đính kèm của MỘT mục dự trù trên trang chi tiết phiếu Hoá Chất / Chất Chuẩn - cùng giao
| diện với Dự Trù Vật Tư (MaterialEstimate/detail). Có file thì nổi nhãn cam kèm số file; phiếu
| còn sửa được thì có nút Đính kèm / xoá file (xem Concerns\EstimateItemAttachments).
|
| Biến vào: $item (có ->files), $list, $estRoute, $canAttach, $itemLabel (chữ thường).
--}}
@once
    <style>
        .est-item-files {
            margin-top: 6px;
        }

        .est-item-file {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.78rem;
            max-width: 100%;
        }

        .est-item-file a {
            color: var(--primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .est-file-count {
            display: inline-block;
            margin-bottom: 4px;
            padding: 2px 10px;
            border-radius: 999px;
            font-size: 0.74rem;
            font-weight: 700;
            background: #FEF3C7;
            color: #B45309;
            border: 1px solid #FCD34D;
        }

        .est-item-files:has(.est-file-count) .est-item-file {
            background: #FFFBEB;
            border-radius: 6px;
            padding: 1px 6px;
            margin-bottom: 2px;
        }

        .est-item-files:has(.est-file-count) .est-item-file a {
            color: #B45309;
            font-weight: 600;
        }

        .est-item-attach {
            font-size: 0.72rem;
            padding: 1px 8px;
            cursor: pointer;
            transition: all .2s;
        }
    </style>
@endonce

<div class="est-item-files">
    @if ($item->files->count())
        <span class="est-file-count" title="{{ ucfirst($itemLabel) }} này có {{ $item->files->count() }} file đính kèm">
            <i class="fas fa-paperclip mr-1"></i>{{ $item->files->count() }} file đính kèm
        </span>
    @endif
    @foreach ($item->files as $itemFile)
        <div class="est-item-file">
            <a href="{{ route($estRoute . 'downloadAttachment', ['id' => $itemFile->id]) }}" target="_blank"
                title="{{ $itemFile->file_name }} · {{ $itemFile->created_by }}">
                <i class="fas fa-paperclip mr-1"></i>{{ $itemFile->file_name }}
            </a>
            @if ($canAttach)
                <form action="{{ route($estRoute . 'deleteAttachment') }}" method="POST"
                    class="d-inline form-md-confirm" data-title="Xoá file đính kèm?"
                    data-text="{{ $itemFile->file_name }}">
                    @csrf
                    <input type="hidden" name="id" value="{{ $itemFile->id }}">
                    <button type="submit" class="btn btn-link btn-sm p-0 text-danger" title="Xoá file">
                        <i class="fas fa-times"></i>
                    </button>
                </form>
            @endif
        </div>
    @endforeach

    @if ($canAttach)
        <form action="{{ route($estRoute . 'uploadAttachment') }}" method="POST" enctype="multipart/form-data" class="d-inline">
            @csrf
            <input type="hidden" name="list_id" value="{{ $list->id }}">
            <input type="hidden" name="item_id" value="{{ $item->id }}">
            <label class="btn btn-xs btn-outline-secondary mb-0 mt-1 est-item-attach" title="Đính kèm file cho {{ $itemLabel }} này">
                <i class="fas fa-paperclip mr-1"></i> Đính kèm
                <input type="file" name="attachments[]" multiple class="d-none"
                    onchange="if (this.files.length) this.form.submit();">
            </label>
        </form>
    @endif
</div>
