@extends ('layout.master')

{{--
|--------------------------------------------------------------------------
| DỰ TRÙ - CHI TIẾT MỘT PHIẾU DỰ TRÙ VẬT TƯ
|--------------------------------------------------------------------------
| Cùng bố cục với chi tiết dự trù hoá chất / chất chuẩn. Vật tư không có "nhóm chuẩn"
| nên bỏ cột đó. Duyệt xong phiếu tự đánh dấu đã tiếp nhận.
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

        .est-item-attach {
            font-size: 0.72rem;
            padding: 1px 8px;
            cursor: pointer;
            transition: all .2s;
        }

        .sgr-version {
            display: inline-block;
            background: #EDE9FE;
            color: #5B21B6;
            border: 1px solid #C4B5FD;
            border-radius: 999px;
            padding: 1px 9px;
            font-size: 0.72rem;
            font-weight: 700;
            white-space: nowrap;
        }
    </style>

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
                    <div class="val">{{ $list->updated_by ?: $list->created_by ?: '—' }}</div>
                    <div class="md-sub">
                        {{ $list->created_at ? \Carbon\Carbon::parse($list->created_at)->format('d/m/Y H:i') : '' }}
                    </div>
                </div>

                @foreach ($signs as $step)
                    @php
                        $stepWho = $step->signer_full_name ?: ($step->user_name ?: ($step->role_names ?: '—'));
                        $stepIsBod = (int) $step->step_no === (int) $signs->count();
                    @endphp
                    <div class="box">
                        <label>Bước {{ $step->step_no }}{{ $stepIsBod ? ' - Ban Giám Đốc' : '' }}</label>
                        <div class="val">{{ $stepWho }}</div>
                        <div class="md-sub">
                            @if ($step->status === 'signed')
                                Đã ký &middot; {{ $step->signed_at ? \Carbon\Carbon::parse($step->signed_at)->format('d/m/Y H:i') : '' }}
                            @elseif ($step->status === 'rejected')
                                <span class="text-danger">Đã từ chối</span>
                            @elseif ($list->app_status === 'pending_sign' && (int) $list->current_step === (int) $step->step_no)
                                <span class="text-primary">Đang chờ ký</span>
                            @else
                                Chưa ký
                            @endif
                        </div>
                    </div>
                @endforeach

                <div class="box">
                    <label>Tiếp Nhận</label>
                    <div class="val">
                        @if ($list->reception_status)
                            <span class="est-badge {{ $list->reception_status }}">
                                {{ $receptionStatuses[$list->reception_status] ?? $list->reception_status }}
                            </span>
                        @else
                            <span class="est-badge none">Chưa duyệt xong</span>
                        @endif
                    </div>
                    @if ($list->received_by)
                        <div class="md-sub">{{ $list->received_by }}</div>
                    @endif
                </div>
            </div>

            @if ($list->app_status === 'rejected' && $list->reject_reason)
                <div class="est-reject-note">
                    <i class="fas fa-triangle-exclamation mr-1"></i>
                    <b>Bị từ chối ở bước {{ $list->reject_step }}</b>
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

                            @if ($canEditItems && user_can('estimate_material_update'))
                                <button type="button" class="btn btn-primary btn-est-item-create ml-1">
                                    <i class="fas fa-plus mr-1"></i> Thêm vật tư
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
                            @if ($canEditItems && user_can('estimate_material_update'))
                                Mỗi vật tư khai được nhiều dòng số lượng cho nhiều tháng khác nhau.
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
                                    <th style="width: 240px">Vật Tư</th>
                                    <th>Thông Tin Kỹ Thuật</th>
                                    <th>Mục Đích Sử Dụng</th>
                                    <th style="width: 200px">Số Lượng Dự Trù</th>
                                    <th style="width: 150px">Ngày Hẹn Đáp Ứng</th>
                                    <th style="width: 110px">Người Tạo</th>
                                    @if ($canEditItems && user_can('estimate_material_update'))
                                        <th class="text-center" style="width: 90px">Thao Tác</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($items as $item)
                                    <tr>
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="font-weight-bold">{{ $item->display_name ?: '—' }}</div>
                                            <div class="md-sub">
                                                @if ($item->category_id)
                                                    <small>NSX: {{ $item->category_manufacturer_short_name ?: ($item->category_manufacturer_name ?: '—') }}</small>
                                                    @if ($item->category_technical_specification)
                                                        <br><small>Quy cách: {{ $item->category_technical_specification }}</small>
                                                    @endif
                                                @else
                                                    <span class="est-outside">Ngoài danh mục</span>
                                                @endif
                                                @if ($item->part_number)
                                                    <br><small>P/N: <b>{{ $item->part_number }}</b></small>
                                                @endif
                                            </div>

                                            {{-- File đính kèm của riêng vật tư này --}}
                                            <div class="est-item-files">
                                                @foreach ($item->files as $itemFile)
                                                    <div class="est-item-file">
                                                        <a href="{{ route($estRoute . 'downloadAttachment', ['id' => $itemFile->id]) }}" target="_blank"
                                                            title="{{ $itemFile->file_name }} · {{ $itemFile->created_by }}">
                                                            <i class="fas fa-paperclip mr-1"></i>{{ $itemFile->file_name }}
                                                        </a>
                                                        @if ($canEditItems && user_can('estimate_material_update'))
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

                                                @if ($canEditItems && user_can('estimate_material_update'))
                                                    <form action="{{ route($estRoute . 'uploadAttachment') }}" method="POST"
                                                        enctype="multipart/form-data" class="d-inline">
                                                        @csrf
                                                        <input type="hidden" name="material_estimate_id" value="{{ $list->id }}">
                                                        <input type="hidden" name="material_estimate_item_id" value="{{ $item->id }}">
                                                        <label class="btn btn-xs btn-outline-secondary mb-0 mt-1 est-item-attach" title="Đính kèm file cho vật tư này">
                                                            <i class="fas fa-paperclip mr-1"></i> Đính kèm
                                                            <input type="file" name="attachments[]" multiple class="d-none"
                                                                onchange="if (this.files.length) this.form.submit();">
                                                        </label>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="md-sub">{{ $item->technical_information ?: '—' }}</td>
                                        <td class="md-sub">
                                            <div class="mb-1">{{ $item->purpose ?: '—' }}</div>
                                            @if ($item->expected_delivery_date)
                                                <div class="text-primary"><i class="fas fa-calendar-alt mr-1"></i> Mong muốn giao: <b>{{ \Carbon\Carbon::parse($item->expected_delivery_date)->format('d/m/Y') }}</b></div>
                                                @php
                                                    $leadWarn = \App\Support\MaterialLeadTime::check(
                                                        $list->created_at, $approvedAt ?? null,
                                                        $item->expected_delivery_date, $item->category_lead_time_days
                                                    );
                                                @endphp
                                                @if ($leadWarn)
                                                    <span class="badge badge-danger mt-1" title="{{ \App\Support\MaterialLeadTime::message($leadWarn) }}">
                                                        <i class="fas fa-exclamation-triangle mr-1"></i>Không kịp thời gian đặt hàng
                                                        (cần {{ $leadWarn['lead_days'] }} ngày, còn {{ $leadWarn['available_days'] }} ngày)
                                                    </span>
                                                @endif
                                            @endif

                                            @include('pages.estimate.shared.itemStatus', ['trackPermission' => 'estimate_material_tracking'])
                                        </td>
                                        <td>
                                            @forelse ($item->amounts as $amount)
                                                <span class="est-chip">
                                                    <b>{{ $estNum($amount->amount) }} {{ $amount->unit_short_name ?: $amount->unit_name }}</b>
                                                    <span>&middot; {{ \Carbon\Carbon::parse($amount->for_month_year)->format('m/Y') }}</span>
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
                                            @if ($list->app_status === 'approved' && user_can('estimate_material_tracking'))
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
                                        <td class="md-sub">
                                            {{ $item->updated_by ?: $item->created_by ?: '—' }}
                                            <br><small>{{ $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('d/m/Y') : '' }}</small>
                                        </td>
                                        @if ($canEditItems && user_can('estimate_material_update'))
                                            <td>
                                                <div class="md-actions">
                                                    <button type="button" class="btn btn-sm btn-warning btn-est-item-edit" title="Sửa"
                                                        data-row="{{ json_encode([
                                                            'id' => $item->id,
                                                            'category_id' => $item->category_id,
                                                            'material_name' => $item->material_name,
                                                            'part_number' => $item->part_number,
                                                            'technical_information' => $item->technical_information,
                                                            'purpose' => $item->purpose,
                                                            'expected_delivery_date' => $item->expected_delivery_date,
                                                            'amounts' => $item->amounts->map(fn($amount) => [
                                                                'amount' => rtrim(rtrim(number_format((float) $amount->amount, 4, '.', ''), '0'), '.'),
                                                                'unit_id' => $amount->unit_id,
                                                                'for_month_year' => \Carbon\Carbon::parse($amount->for_month_year)->format('Y-m'),
                                                            ]),
                                                        ]) }}">
                                                        <i class="fas fa-edit"></i>
                                                    </button>

                                                    <form class="form-md-confirm d-inline" action="{{ route($estRoute . 'deleteItem') }}" method="POST"
                                                        data-title="Xoá vật tư khỏi phiếu?"
                                                        data-text="Vật tư &quot;{{ $item->display_name }}&quot; và toàn bộ số lượng theo tháng sẽ bị xoá khỏi phiếu {{ $list->code }}."
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
        @include('pages.estimate.MaterialEstimate.itemCreate')
        @include('pages.estimate.MaterialEstimate.itemUpdate')
    @endif

    @include('pages.estimate.shared.historyModal')
@endsection
