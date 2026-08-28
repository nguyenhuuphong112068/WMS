@php
    // Định dạng số lượng: bỏ số 0 thừa ở phần thập phân (giống shared/detail.blade.php).
    // Bảng này được @include từ dataTable nên phải tự khai, không kế thừa từ trang chi tiết.
    $estNum = fn($value) => rtrim(rtrim(number_format((float) $value, 4, '.', ','), '0'), '.');
@endphp

<div class="table-responsive">
    <table id="mdTrackingTable" class="table table-bordered table-hover w-100">
        <thead>
            <tr>
                <th class="text-center" style="width: 55px">STT</th>
                <th style="width: 120px">Mã Phiếu</th>
                <th style="width: 200px">Mặt Hàng</th>
                <th>Thông Tin Kỹ Thuật</th>
                <th>Mục Đích Sử Dụng</th>
                <th style="width: 200px">Số Lượng</th>
                <th style="width: 150px">Ngày Hẹn Đáp Ứng</th>
                <th style="width: 280px">Trao Đổi</th>
                <th style="width: 110px">Người Tạo</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>
                        <a href="{{ route($estRoute . 'detail', ['id' => $item->list_id]) }}" class="font-weight-bold" title="Xem chi tiết phiếu">{{ $item->list_code }}</a>
                    </td>
                    <td>
                        <div class="font-weight-bold">{{ $item->display_name ?: '-' }}</div>
                        <div class="md-sub">
                            @if ($item->category_id)
                                @if (!empty($item->category_code))
                                    <span class="md-tag">{{ $item->category_code }}</span>
                                @endif
                                @if (!empty($item->category_type))
                                    <span class="ml-1">{{ $item->category_type }}</span>
                                @endif
                            @else
                                <span class="est-outside">Ngoài danh mục</span>
                            @endif
                        </div>
                    </td>
                    <td class="md-sub">
                        @if ($item->category_manufacturer_name)
                            <div class="mb-1">NSX: <b>{{ $item->category_manufacturer_name }}</b></div>
                        @endif
                        <div>{{ $item->technical_information ?: '-' }}</div>
                    </td>
                    <td class="md-sub">
                        <div class="mb-1">{{ $item->purpose ?: '-' }}</div>
                        @if ($item->expected_delivery_date)
                            <div class="text-primary"><i class="fas fa-calendar-alt mr-1"></i> Mong muốn giao: <b>{{ \Carbon\Carbon::parse($item->expected_delivery_date)->format('d/m/Y') }}</b></div>
                        @endif
                        
                        <div class="mt-2 pt-2 border-top">
                            <form action="{{ route($estRoute . 'updateItemStatus') }}" method="POST" class="d-inline mr-1 form-md-confirm" data-title="Xác nhận hoàn thành?" data-text="Mặt hàng này đã được giao đến khoa/phòng?">
                                @csrf
                                <input type="hidden" name="id" value="{{ $item->id }}">
                                <input type="hidden" name="action" value="complete">
                                <button type="submit" class="btn btn-sm btn-success" title="Xác nhận đã được giao"><i class="fas fa-check"></i> Hoàn thành</button>
                            </form>
                            <form action="{{ route($estRoute . 'updateItemStatus') }}" method="POST" class="d-inline form-md-confirm-cancel" data-title="Huỷ dự trù mặt hàng này?" data-text="Xác nhận khoa/phòng không cần dự trù mặt hàng này nữa?" data-danger="1">
                                @csrf
                                <input type="hidden" name="id" value="{{ $item->id }}">
                                <input type="hidden" name="action" value="cancel">
                                <button type="submit" class="btn btn-sm btn-danger" title="Không cần dự trù nữa"><i class="fas fa-times"></i> Huỷ</button>
                            </form>
                        </div>
                    </td>
                    <td>
                        @forelse ($item->amounts as $amount)
                            <span class="est-chip">
                                <b>{{ $estNum($amount->amount) }}
                                    {{ $amount->unit_short_name ?: $amount->unit_name }}</b>
                                <span>&middot;
                                    {{ \Carbon\Carbon::parse($amount->for_month_year)->format('m/Y') }}</span>
                            </span>
                        @empty
                            <span class="md-empty">-</span>
                        @endforelse
                    </td>
                    <td>
                        @php
                            $daysLeftText = '';
                            if ($item->promised_date) {
                                $promised = \Carbon\Carbon::parse($item->promised_date)->startOfDay();
                                $today = \Carbon\Carbon::now()->startOfDay();
                                $diff = $today->diffInDays($promised, false);
                                if ($diff > 0) {
                                    $daysLeftText = "<span class='text-success small'>Còn {$diff} ngày</span>";
                                } elseif ($diff == 0) {
                                    $daysLeftText = "<span class='text-warning small'>Hôm nay</span>";
                                } else {
                                    $daysLeftText = "<span class='text-danger small'>Quá hạn " . abs($diff) . " ngày</span>";
                                }
                            }
                        @endphp
                        <form action="{{ route($estRoute . 'updatePromisedDate') }}" method="POST" class="d-flex flex-column promised-date-form">
                            @csrf
                            <input type="hidden" name="id" value="{{ $item->id }}">
                            <div class="d-flex align-items-center mb-1">
                                <input type="date" name="promised_date" class="form-control form-control-sm input-promised-date flex-grow-1" value="{{ $item->promised_date ? \Carbon\Carbon::parse($item->promised_date)->format('Y-m-d') : '' }}" data-route="{{ route($estRoute . 'updatePromisedDate') }}">
                                <button type="button" class="btn btn-sm btn-link text-info p-1 ml-1 btn-promised-date-history position-relative" data-item-id="{{ $item->id }}" data-route="{{ route($estRoute . 'getPromisedDateHistory', $item->id) }}" title="Lịch sử ngày hẹn">
                                    <i class="fas fa-history"></i>
                                    @if ($item->history_count > 0)
                                        <span class="badge badge-danger badge-pill position-absolute promised-date-history-badge" style="top: -5px; right: -5px; font-size: 0.6rem; padding: 2px 4px;">{{ $item->history_count }}</span>
                                    @endif
                                </button>
                            </div>
                            <div class="promised-date-days-left text-center">{!! $daysLeftText !!}</div>
                        </form>
                    </td>
                    <td>
                        <div class="chat-container d-flex flex-column" style="height: 200px; max-height: 200px;">
                            <div class="chat-messages flex-grow-1 overflow-auto bg-light border p-2 mb-1" style="font-size: 0.85rem;" id="chat-messages-tracking-{{ $item->id }}">
                                @foreach ($item->chats as $chat)
                                    <div class="chat-message mb-2 {{ $chat->type === 'system' ? 'text-muted font-italic' : '' }}">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <strong class="text-primary">{{ $chat->user_name }}</strong>
                                            <small style="font-size: 0.7rem;">{{ $chat->created_at_formatted }}</small>
                                        </div>
                                        <div>{{ $chat->content }}</div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="chat-input mt-auto">
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control chat-input-field" placeholder="Nhập tin nhắn và nhấn Enter..." data-item-id="{{ $item->id }}" data-route="{{ route($estRoute . 'storeItemChat') }}">
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="md-sub">
                        {{ $item->created_by ?: '-' }}
                        <br><small>{{ $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('d/m/Y') : '' }}</small>
                    </td>
                </tr>
            @endforeach
            
            @if(count($items) == 0)
            <tr>
                <td colspan="9" class="text-center text-muted py-4">Không có mặt hàng nào đang được theo dõi</td>
            </tr>
            @endif
        </tbody>
    </table>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        if ($.fn.DataTable.isDataTable('#mdTrackingTable')) {
            $('#mdTrackingTable').DataTable().destroy();
        }
        $('#mdTrackingTable').DataTable({
            "order": [], // Let the server handle ordering by promised_date asc
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.21/i18n/Vietnamese.json"
            }
        });

        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
        });
    });
</script>
