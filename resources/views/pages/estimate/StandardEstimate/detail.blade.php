@extends ('layout.master')

{{--
|--------------------------------------------------------------------------
| DỰ TRÙ - CHI TIẾT MỘT PHIẾU DỰ TRÙ CHẤT CHUẨN
|--------------------------------------------------------------------------
| Cùng bố cục với chi tiết dự trù hoá chất, khác ở chỗ mỗi dòng còn khai NHÓM CHUẨN
| mong muốn để bộ phận Cung Ứng biết cần mua chuẩn chính hay chuẩn tạp.
|
| Biến vào: $list, $items, $histories, $categories, $units, $groups, $canEditItems,
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

    {{-- Trang này không có bảng lọc nên không kéo theo pages.shared.standardGroupFilter,
         phải khai riêng hai thẻ nhóm chuẩn / version đang dùng ở cột Chất Chuẩn. --}}
    <style>
        .sgr-chip {
            display: inline-block;
            background: var(--primary-soft);
            color: var(--primary-dark);
            border: 1px solid var(--primary-lighter);
            border-radius: 999px;
            padding: 1px 9px;
            font-size: 0.74rem;
            font-weight: 700;
            letter-spacing: 0.3px;
            white-space: nowrap;
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

                <div class="box">
                    <label>Tiếp Nhận (Cung Ứng)</label>
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
                    <b>Bị từ chối ở bước {{ $signSteps[$list->reject_step]['label'] ?? '—' }}</b>
                    bởi {{ $list->rejected_by ?: 'NA' }}
                    {{ $list->rejected_at ? '(' . \Carbon\Carbon::parse($list->rejected_at)->format('d/m/Y H:i') . ')' : '' }}:
                    {{ $list->reject_reason }}
                </div>
            @endif

            @if ($list->reception_note)
                <div class="md-hint mb-3">
                    <i class="fas fa-truck-ramp-box mr-1"></i>
                    <b>Ghi chú của bộ phận Cung Ứng:</b> {{ $list->reception_note }}
                </div>
            @endif

            <div class="card md-card">
                <div class="card-body">

                    <div class="md-toolbar">
                        <div>
                            <a href="{{ $backRoute }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-1"></i> Quay lại danh sách
                            </a>

                            @if ($canEditItems)
                                <button type="button" class="btn btn-primary btn-est-item-create ml-1">
                                    <i class="fas fa-plus mr-1"></i> Thêm chất chuẩn
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
                            @if ($canEditItems)
                                Mỗi chất chuẩn khai được nhiều dòng số lượng cho nhiều tháng khác nhau.
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
                                    <th style="width: 240px">Chất Chuẩn</th>
                                    <th style="width: 150px">Nhóm Chuẩn</th>
                                    <th>Thông Tin Kỹ Thuật</th>
                                    <th>Mục Đích Sử Dụng</th>
                                    <th style="width: 260px">Số Lượng Dự Trù Theo Tháng</th>
                                    <th style="width: 130px">Người Khai</th>
                                    @if ($canEditItems)
                                        <th class="text-center" style="width: 110px">Thao Tác</th>
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
                                                    <span class="md-tag">{{ $item->category_code }}</span>
                                                    <span class="sgr-version ml-1">v{{ $item->category_version }}</span>
                                                    @if ($item->category_cas_no)
                                                        <br><small>CAS: {{ $item->category_cas_no }}</small>
                                                    @endif
                                                @else
                                                    <span class="est-outside">Ngoài danh mục</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="md-sub">
                                            @if ($item->group_label)
                                                <span class="sgr-chip">{{ $item->group_label }}</span>
                                            @else
                                                <span class="md-empty">—</span>
                                            @endif
                                        </td>
                                        <td class="md-sub">{{ $item->technical_information ?: '—' }}</td>
                                        <td class="md-sub">{{ $item->purpose ?: '—' }}</td>
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
                                        <td class="md-sub">
                                            {{ $item->created_by ?: '—' }}
                                            <br><small>{{ $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('d/m/Y') : '' }}</small>
                                        </td>
                                        @if ($canEditItems)
                                            <td>
                                                <div class="md-actions">
                                                    <button type="button" class="btn btn-sm btn-warning btn-est-item-edit"
                                                        title="Sửa"
                                                        data-row="{{ json_encode([
                                                            'id' => $item->id,
                                                            'category_id' => $item->category_id,
                                                            'standard_name' => $item->standard_name,
                                                            'group_key' => $item->group_key,
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
                                                        data-title="Xoá chất chuẩn khỏi phiếu?"
                                                        data-text="Chất chuẩn &quot;{{ $item->display_name }}&quot; và toàn bộ số lượng theo tháng của nó sẽ bị xoá khỏi phiếu {{ $list->code }}."
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
        @include('pages.estimate.StandardEstimate.itemCreate')
        @include('pages.estimate.StandardEstimate.itemUpdate')
    @endif

    @include('pages.estimate.shared.historyModal')
@endsection
