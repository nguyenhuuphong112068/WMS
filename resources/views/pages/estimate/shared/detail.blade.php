@extends ('layout.master')

{{--
|--------------------------------------------------------------------------
| DỰ TRÙ - CHI TIẾT MỘT PHIẾU DỰ TRÙ
|--------------------------------------------------------------------------
| Dùng chung cho hai màn hình:
| - Dự Trù Hoá Chất   : $canEditItems = true khi phiếu còn Nháp / Bị từ chối
| - Tiếp Nhận Dự Trù  : luôn ở chế độ chỉ xem
|
| Biến vào: $list, $items, $histories, $categories, $units, $canEditItems,
|           $backRoute, $estRoute, $appStatuses, $signSteps, $receptionStatuses
--}}

@php
    $estIcon = 'fas fa-clipboard-check';
    $estNum = fn($value) => rtrim(rtrim(number_format((float) $value, 4, '.', ','), '0'), '.');
    $estStatusClass = match ($list->app_status) {
        'approved' => 'approved',
        'rejected' => 'rejected',
        default => 'pending',
    };
@endphp

@section('mainContent')
    @include('pages.estimate.shared.assets')

    <div class="content-wrapper">
        <div class="md-page">

            <div class="est-info">
                <div class="box">
                    <label>Trạng Thái Trình Ký</label>
                    <div class="val">
                        <span class="md-badge {{ $estStatusClass }}">
                            {{ $appStatuses[$list->app_status] ?? $list->app_status }}
                        </span>
                    </div>
                </div>

                <div class="box">
                    <label>Người Lập Phiếu</label>
                    <div class="val">{{ $list->created_by ?: '—' }}</div>
                    <div class="md-sub">
                        {{ $list->created_at ? \Carbon\Carbon::parse($list->created_at)->format('d/m/Y H:i') : '' }}
                    </div>
                </div>

                @foreach ($signSteps as $stepKey => $step)
                    <div class="box">
                        <label>Bước {{ $step['no'] }} - {{ $step['label'] }}</label>
                        <div class="val">{{ $list->{$step['signed_by']} ?: '—' }}</div>
                        <div class="md-sub">
                            {{ $list->{$step['signed_at']} ? \Carbon\Carbon::parse($list->{$step['signed_at']})->format('d/m/Y H:i') : 'Chưa ký' }}
                        </div>
                    </div>
                @endforeach

            </div>

            @if ($list->app_status === 'rejected' && $list->reject_reason)
                <div class="est-reject-note">
                    <i class="fas fa-triangle-exclamation mr-1"></i>
                    <b>Bị từ chối ở bước {{ $signSteps[$list->reject_step]['label'] ?? '—' }}</b>
                    bởi {{ $list->rejected_by ?: 'NA' }}
                    {{ $list->rejected_at ? '(' . \Carbon\Carbon::parse($list->rejected_at)->format('d/m/Y H:i') . ')' : '' }}:
                    {{ $list->reject_reason }}
                </div>
            @endif



            <div class="card md-card">
                <div class="card-body">

                    <div class="md-toolbar">
                        <div>
                            <a href="{{ $backRoute }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-1"></i> Quay lại danh sách
                            </a>

                            @if ($canEditItems && user_can('estimate_chemical_update'))
                                <button type="button" class="btn btn-primary btn-est-item-create ml-1">
                                    <i class="fas fa-plus mr-1"></i> Thêm mặt hàng
                                </button>
                            @endif

                            <button type="button" class="btn btn-info ml-1 btn-est-history"
                                data-url="{{ route($estRoute . 'history', ['id' => $list->id]) }}"
                                data-title="Phiếu {{ $list->code }}">
                                <i class="fas fa-route mr-1"></i> Theo dõi trình ký
                            </button>
                        </div>

                        <p class="hint">
                            <i class="fas fa-info-circle mr-1"></i>
                            @if ($canEditItems && user_can('estimate_chemical_update'))
                                Mỗi mặt hàng khai được nhiều dòng số lượng cho nhiều tháng khác nhau.
                            @else
                                Phiếu đã trình ký nên chi tiết chỉ xem, không sửa được.
                            @endif
                        </p>
                    </div>

                    <div class="table-responsive">
                        <table id="mdTable" class="table table-bordered table-hover w-100">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 55px">STT</th>
                                    <th style="width: 200px">Hoá Chất</th>
                                    <th>Thông Tin Kỹ Thuật</th>
                                    <th>Mục Đích Sử Dụng</th>
                                    <th style="width: 200px">Số Lượng Dự Trù</th>
                                    <th style="width: 150px">Ngày Hẹn Đáp Ứng</th>
                                    <th style="width: 280px">Trao Đổi</th>
                                    <th style="width: 110px">Người Tạo</th>
                                    @if ($canEditItems && user_can('estimate_chemical_update'))
                                        <th class="text-center" style="width: 90px">Thao Tác</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($items as $item)
                                    <tr @if($item->status_id == 0) style="opacity: 0.6; background-color: #f8f9fa;" @endif>
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="font-weight-bold">{{ $item->display_name ?: '—' }}</div>
                                            <div class="md-sub">
                                                @if ($item->category_id)
                                                    <span class="md-tag">{{ $item->category_code }}</span>
                                                    @if ($item->category_type)
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
                                            <div>{{ $item->technical_information ?: '—' }}</div>
                                        </td>
                                        <td class="md-sub">
                                            <div class="mb-1">{{ $item->purpose ?: '—' }}</div>
                                            @if ($item->expected_delivery_date)
                                                <div class="text-primary"><i class="fas fa-calendar-alt mr-1"></i> Mong muốn giao: <b>{{ \Carbon\Carbon::parse($item->expected_delivery_date)->format('d/m/Y') }}</b></div>
                                            @endif
                                            
                                            @if ($list->app_status === 'approved' && user_can('estimate_chemical_tracking'))
                                                <div class="mt-2 pt-2 border-top">
                                                    @if ($item->status_id == 0)
                                                        <span class="badge badge-danger mb-1">Đã huỷ không dự trù</span>
                                                        @if ($item->cancel_reason)
                                                            <div class="text-danger small mb-1">Lý do: {{ $item->cancel_reason }}</div>
                                                        @endif
                                                        <form action="{{ route($estRoute . 'updateItemStatus') }}" method="POST" class="d-inline ml-2 form-md-confirm" data-title="Khôi phục lại mặt hàng?" data-text="Bạn muốn tiếp tục dự trù mặt hàng này?">
                                                            @csrf
                                                            <input type="hidden" name="id" value="{{ $item->id }}">
                                                            <input type="hidden" name="action" value="undo">
                                                            <button type="submit" class="btn btn-xs btn-outline-secondary" title="Hoàn tác"><i class="fas fa-undo"></i> Hoàn tác</button>
                                                        </form>
                                                    @elseif ($item->fulfilled_date)
                                                        <div class="text-success mb-1"><i class="fas fa-check-circle mr-1"></i> Đã giao: <b>{{ \Carbon\Carbon::parse($item->fulfilled_date)->format('d/m/Y') }}</b></div>
                                                        @if ($item->fulfilled_by)
                                                            <div class="small text-muted mb-1"><i class="fas fa-user-check mr-1"></i> {{ $item->fulfilled_by }}</div>
                                                        @endif
                                                        <form action="{{ route($estRoute . 'updateItemStatus') }}" method="POST" class="d-inline mt-1 form-md-confirm" data-title="Hoàn tác trạng thái?" data-text="Mặt hàng này chưa được giao?">
                                                            @csrf
                                                            <input type="hidden" name="id" value="{{ $item->id }}">
                                                            <input type="hidden" name="action" value="undo">
                                                            <button type="submit" class="btn btn-xs btn-outline-secondary" title="Hoàn tác"><i class="fas fa-undo"></i> Hoàn tác</button>
                                                        </form>
                                                    @else
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
                                                    @endif
                                                </div>
                                            @elseif ($item->status_id == 0)
                                                <div class="mt-2 pt-2 border-top">
                                                    <span class="badge badge-danger">Đã huỷ không dự trù</span>
                                                    @if ($item->cancel_reason)
                                                        <div class="text-danger small mt-1">Lý do: {{ $item->cancel_reason }}</div>
                                                    @endif
                                                </div>
                                            @elseif ($item->fulfilled_date)
                                                <div class="text-success mt-1 border-top pt-2"><i class="fas fa-check-circle mr-1"></i> Đã giao: <b>{{ \Carbon\Carbon::parse($item->fulfilled_date)->format('d/m/Y') }}</b></div>
                                                @if ($item->fulfilled_by)
                                                    <div class="small text-muted"><i class="fas fa-user-check mr-1"></i> {{ $item->fulfilled_by }}</div>
                                                @endif
                                            @endif
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
                                                <span class="md-empty">—</span>
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
                                            @if ($list->app_status === 'approved' && user_can('estimate_chemical_tracking'))
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
                                            @else
                                                <div class="text-center md-sub">
                                                    {{ $item->promised_date ? \Carbon\Carbon::parse($item->promised_date)->format('d/m/Y') : 'Chưa có' }}
                                                </div>
                                                <div class="text-center">{!! $daysLeftText !!}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="chat-container d-flex flex-column" style="height: 200px; max-height: 200px;">
                                                <div class="chat-messages flex-grow-1 overflow-auto bg-light border p-2 mb-1" style="font-size: 0.85rem;" id="chat-messages-{{ $item->id }}">
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
                                            {{ $item->created_by ?: '—' }}
                                            <br><small>{{ $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('d/m/Y') : '' }}</small>
                                        </td>
                                        @if ($canEditItems && user_can('estimate_chemical_update'))
                                            <td>
                                                <div class="md-actions">
                                                    <button type="button" class="btn btn-sm btn-warning btn-est-item-edit"
                                                        title="Sửa"
                                                        data-row="{{ json_encode([
                                                            'id' => $item->id,
                                                            'category_id' => $item->category_id,
                                                            'chem_name' => $item->chem_name,
                                                            'technical_information' => $item->technical_information,
                                                            'purpose' => $item->purpose,
                                                            'amounts' => $item->amounts->map(fn($amount) => [
                                                                'amount' => rtrim(rtrim(number_format((float) $amount->amount, 4, '.', ''), '0'), '.'),
                                                                'unit_id' => $amount->unit_id,
                                                                'for_month_year' => \Carbon\Carbon::parse($amount->for_month_year)->format('Y-m'),
                                                            ]),
                                                        ]) }}">
                                                        <i class="fas fa-edit"></i>
                                                    </button>

                                                    <form class="form-md-confirm d-inline"
                                                        action="{{ route($estRoute . 'deleteItem') }}" method="POST"
                                                        data-title="Xoá mặt hàng khỏi phiếu?"
                                                        data-text="Mặt hàng &quot;{{ $item->display_name }}&quot; và toàn bộ số lượng theo tháng của nó sẽ bị xoá khỏi phiếu {{ $list->code }}."
                                                        data-danger="1">
                                                        @csrf
                                                        <input type="hidden" name="id" value="{{ $item->id }}">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Xoá">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('model')
    @if ($canEditItems)
        @include('pages.estimate.ChemicalEstimate.itemCreate')
        @include('pages.estimate.ChemicalEstimate.itemUpdate')
    @endif

    @include('pages.estimate.shared.historyModal')
@endsection
