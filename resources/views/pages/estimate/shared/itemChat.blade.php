{{--
| Khung "Trao đổi" của một mục dự trù (tab Theo dõi dự trù). Tin cũ trên, mới dưới; cao theo
| nội dung (trống ~2 dòng, tối đa 220px rồi cuộn). Tin của chính mình nằm bên phải.
| Biến vào: $item (->chats đã sắp mới nhất trước), $estRoute, $canChat
--}}
@php
    $chatMe = \App\Support\Signer::actor();
@endphp
<div class="est-chat">
    <div class="est-chat-messages">
        @forelse ($item->chats->reverse() as $chat)
            @if ($chat->type === 'system')
                <div class="est-chat-msg is-system">
                    <span>{{ $chat->content }}</span>
                    <small>{{ $chat->user_name }} · {{ $chat->created_at_formatted }}</small>
                </div>
            @else
                <div class="est-chat-msg {{ $chat->user_name === $chatMe ? 'is-me' : '' }}">
                    <div class="est-chat-meta"><b>{{ $chat->user_name }}</b> · {{ $chat->created_at_formatted }}</div>
                    <div class="est-chat-bubble">{{ $chat->content }}</div>
                </div>
            @endif
        @empty
            <div class="est-chat-empty">Chưa có trao đổi</div>
        @endforelse
    </div>
    @if ($canChat)
        <div class="est-chat-input">
            @csrf
            <input type="text" class="chat-input-field" maxlength="1000" placeholder="Nhập tin nhắn và nhấn Enter..."
                data-item-id="{{ $item->id }}" data-route="{{ route($estRoute . 'storeItemChat') }}">
            <button type="button" class="est-chat-send" title="Gửi"><i class="fas fa-paper-plane"></i></button>
        </div>
    @endif
</div>

@once
    <style>
        .est-chat {
            display: flex;
            flex-direction: column;
            min-width: 240px;
            border: 1px solid var(--primary-lighter);
            border-radius: var(--border-radius-md, 8px);
            background: #fff;
            overflow: hidden;
        }

        .est-chat-messages {
            min-height: 44px;
            max-height: 220px;
            overflow-y: auto;
            padding: 8px;
            background: var(--bg-neutral);
            font-size: 0.82rem;
        }

        .est-chat-empty {
            color: #94A3B8;
            font-style: italic;
            text-align: center;
            line-height: 28px;
        }

        .est-chat-msg {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            margin-bottom: 6px;
        }

        .est-chat-msg:last-child {
            margin-bottom: 0;
        }

        .est-chat-meta {
            font-size: 0.68rem;
            color: #64748B;
            margin-bottom: 2px;
        }

        .est-chat-meta b {
            color: var(--primary);
            font-weight: 600;
        }

        .est-chat-bubble {
            max-width: 92%;
            padding: 5px 10px;
            border-radius: 10px 10px 10px 2px;
            background: #fff;
            border: 1px solid #E2E8F0;
            color: var(--text-main);
            white-space: pre-wrap;
            word-break: break-word;
        }

        .est-chat-msg.is-me {
            align-items: flex-end;
        }

        .est-chat-msg.is-me .est-chat-bubble {
            border-radius: 10px 10px 2px 10px;
            background: var(--primary-soft);
            border-color: var(--primary-lighter);
        }

        .est-chat-msg.is-system {
            align-items: center;
            text-align: center;
            color: #64748B;
            font-style: italic;
            font-size: 0.75rem;
        }

        .est-chat-msg.is-system small {
            font-size: 0.65rem;
            color: #94A3B8;
        }

        .est-chat-input {
            display: flex;
            align-items: center;
            border-top: 1px solid #E2E8F0;
            background: #fff;
        }

        .est-chat-input .chat-input-field {
            flex: 1;
            min-width: 0;
            border: 0;
            padding: 7px 10px;
            font-size: 0.82rem;
            outline: none;
            background: transparent;
        }

        .est-chat-send {
            border: 0;
            background: transparent;
            color: var(--primary);
            padding: 6px 10px;
            transition: color 0.2s, transform 0.2s;
        }

        .est-chat-send:hover {
            color: var(--primary-dark);
            transform: translateY(-1px);
        }

        .est-chat-input:focus-within {
            box-shadow: inset 0 0 0 1px var(--primary-light);
        }
    </style>
@endonce
