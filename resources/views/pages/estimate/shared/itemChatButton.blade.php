{{--
| Nút "Trao đổi" của một mục trên trang chi tiết phiếu: mở modal xem (và gửi, nếu có quyền) trao
| đổi của mục - cùng khung chat với tab Theo dõi dự trù. Hiện khi mục đã có trao đổi, hoặc phiếu
| đã duyệt và user có quyền trao đổi.
| Biến vào: $item (->chats), $list, $estRoute, $chatPermission (vd. 'estimate_material_chat')
--}}
@php
    $chatCount = $item->chats->where('type', '!=', 'system')->count();
    $canChatHere = $list->app_status === 'approved' && user_can($chatPermission);
@endphp
@if ($item->chats->isNotEmpty() || $canChatHere)
    <button type="button" class="btn btn-sm btn-outline-info btn-est-chat mt-1" data-chat-src="est-chat-src-{{ $item->id }}"
        data-title="{{ $item->display_name }}" title="Xem trao đổi">
        <i class="fas fa-comments mr-1"></i>Trao đổi
        @if ($chatCount)
            <span class="badge badge-info ml-1 est-chat-count">{{ $chatCount }}</span>
        @endif
    </button>
    <div class="d-none" id="est-chat-src-{{ $item->id }}">
        @include('pages.estimate.shared.itemChat', ['canChat' => $canChatHere])
    </div>
@endif
