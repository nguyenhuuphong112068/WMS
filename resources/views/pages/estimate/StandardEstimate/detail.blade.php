@extends ('layout.master')

{{--
|--------------------------------------------------------------------------
| DỰ TRÙ - CHI TIẾT MỘT PHIẾU DỰ TRÙ CHẤT CHUẨN
|--------------------------------------------------------------------------
| Cùng bố cục với chi tiết dự trù vật tư (MaterialEstimate/detail): mã / NSX tách cột, file
| đính kèm theo từng chất chuẩn, cảnh báo không kịp thời gian đặt hàng, tiêu đề bảng cố định.
| Riêng chất chuẩn: mỗi dòng còn khai NHÓM CHUẨN mong muốn để bộ phận mua hàng biết cần mua
| chuẩn chính hay chuẩn tạp; mã danh mục kèm version.
|
| Biến vào: $list, $items, $histories, $categories, $units, $groups, $canEditItems,
|           $backRoute, $estRoute, $appStatuses, $receptionStatuses, $approvedAt
--}}

@php
    $estIcon = 'fas fa-clipboard-check';
    $estNum = fn($value) => rtrim(rtrim(number_format((float) $value, 4, '.', ','), '0'), '.');
    $estCanEdit = $canEditItems && user_can('estimate_standard_update');
@endphp

@section('mainContent')
    @include('pages.estimate.shared.assets')

    {{-- Trang này không có bảng lọc nên không kéo theo pages.shared.standardGroupFilter,
         phải khai riêng hai thẻ nhóm chuẩn / version đang dùng ở bảng. --}}
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

                    {{-- Nút là con trực tiếp của .md-toolbar để layout gom chung một hàng với
                         ô Hiển thị / Tìm kiếm của DataTables (.md-tablebar - layout/master.blade.php) --}}
                    <div class="md-toolbar">
                        <a href="{{ $backRoute }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-1"></i> Quay lại danh sách
                        </a>

                        @if ($estCanEdit)
                            <button type="button" class="btn btn-primary btn-est-item-create">
                                <i class="fas fa-plus mr-1"></i> Thêm chất chuẩn
                            </button>
                        @endif

                        <button type="button" class="btn btn-info btn-est-history"
                            data-url="{{ route($estRoute . 'history', ['id' => $list->id]) }}"
                            data-title="Phiếu {{ $list->code }}">
                            <i class="fas fa-route mr-1"></i> Theo dõi trình ký
                        </button>
                        {{-- Bộ phận mua hàng (mở từ tab "Đơn dự trù đã được phê duyệt"): tiếp nhận phiếu vừa duyệt --}}
                        @if ($purchaserView ?? false)
                            @include('pages.estimate.shared.receptionAction')
                        @endif

                        {{-- Người ký được bước đang chờ: Đồng ý / Từ chối ngay trên trang chi tiết (đã bỏ khỏi dòng danh sách) --}}
                        @if ($list->app_status === 'pending_sign' && $canSignCurrent && $pendingSign && user_can('estimate_standard_sign'))
                            @php $estIsLast = (int) $pendingSign->step_no >= (int) $list->sign_step_count; @endphp
                            <form class="form-md-confirm d-inline" data-require-password="1" action="{{ route($estRoute . 'signStep') }}"
                                method="POST" data-title="Ký duyệt bước {{ $pendingSign->step_no }}/{{ $list->sign_step_count }} phiếu {{ $list->code }}?"
                                data-text="{{ $estIsLast ? 'Phiếu sẽ được phê duyệt và chuyển bộ phận mua hàng tiếp nhận.' : 'Phiếu sẽ chuyển tới người ký bước ' . ($pendingSign->step_no + 1) . '.' }}">
                                @csrf
                                <input type="hidden" name="id" value="{{ $list->id }}">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-{{ $estIsLast ? 'stamp' : 'signature' }} mr-1"></i> Đồng ý
                                </button>
                            </form>
                            <button type="button" class="btn btn-outline-danger btn-est-reject" data-id="{{ $list->id }}" data-code="{{ $list->code }}">
                                <i class="fas fa-times mr-1"></i> Từ chối
                            </button>
                        @endif
                    </div>

                    <div class="table-responsive est-detail-table">
                        <table id="mdTable" class="table table-bordered table-hover w-100">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 55px">STT</th>
                                    <th style="width: 110px">Mã Chất Chuẩn</th>
                                    <th style="width: 220px">Chất Chuẩn</th>
                                    <th style="width: 90px">NSX</th>
                                    <th style="width: 120px">Nhóm Chuẩn</th>
                                    <th>Thông Tin Kỹ Thuật</th>
                                    <th>Mục Đích Sử Dụng</th>
                                    <th style="width: 200px">Số Lượng Dự Trù</th>
                                    <th style="width: 150px">Ngày Mong Muốn</th>
                                    @if ($purchaserView ?? false)
                                        <th style="width: 150px">Ngày Hẹn Đáp Ứng</th>
                                    @endif
                                    <th style="width: 110px">Người Tạo</th>
                                    <th class="text-center" style="width: 150px">Thao Tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($items as $item)
                                    <tr @if ($item->status_id == 0) style="opacity: 0.6; background-color: #f8f9fa;" @endif>
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td class="text-nowrap">
                                            @if ($item->category_id)
                                                <div class="font-weight-bold">{{ $item->category_code ?: '—' }}</div>
                                                <span class="sgr-version mt-1">v{{ $item->category_version }}</span>
                                            @else
                                                <span class="md-empty">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="font-weight-bold">{{ $item->display_name ?: '—' }}</div>
                                            @if ($item->category_id && $item->category_cas_no)
                                                <div class="md-sub">CAS: {{ $item->category_cas_no }}</div>
                                            @endif
                                            @unless ($item->category_id)
                                                <span class="est-outside">Ngoài danh mục</span>
                                            @endunless

                                            {{-- File đính kèm của riêng chất chuẩn này --}}
                                            @include('pages.estimate.shared.itemFiles', [
                                                'canAttach' => $estCanEdit,
                                                'itemLabel' => 'chất chuẩn',
                                            ])
                                        </td>
                                        <td class="md-sub">
                                            {{ $item->category_id ? ($item->category_manufacturer_short_name ?: ($item->category_manufacturer_name ?: '—')) : '—' }}
                                        </td>
                                        <td class="md-sub">
                                            @if ($item->group_label)
                                                <span class="sgr-chip">{{ $item->group_label }}</span>
                                            @else
                                                <span class="md-empty">—</span>
                                            @endif
                                        </td>
                                        <td class="md-sub">{{ $item->technical_information ?: '—' }}</td>
                                        <td class="md-sub">
                                            <div class="mb-1">{{ $item->purpose ?: '—' }}</div>
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
                                        <td data-order="{{ $item->expected_delivery_date }}">
                                            @if ($item->expected_delivery_date)
                                                <div class="text-primary font-weight-bold">{{ \Carbon\Carbon::parse($item->expected_delivery_date)->format('d/m/Y') }}</div>
                                                @php
                                                    $leadWarn = \App\Support\MaterialLeadTime::check(
                                                        $list->created_at, $approvedAt ?? null,
                                                        $item->expected_delivery_date, $item->category_lead_time_days
                                                    );
                                                @endphp
                                                @if ($leadWarn)
                                                    <span class="badge badge-danger mt-1 est-lead-warn" title="{{ \App\Support\MaterialLeadTime::message($leadWarn) }}">
                                                        <i class="fas fa-exclamation-triangle mr-1"></i>Không kịp đặt hàng
                                                        <br>cần {{ $leadWarn['lead_days'] }} ngày, còn {{ $leadWarn['available_days'] }} ngày
                                                    </span>
                                                @endif
                                            @else
                                                <span class="md-empty">—</span>
                                            @endif
                                        </td>
                                        {{-- Ngày hẹn đáp ứng: chỉ bộ phận mua hàng sửa, khi mở phiếu từ tab "Đơn dự trù đã được phê duyệt" --}}
                                        @if ($purchaserView ?? false)
                                            <td>
                                                @include('pages.estimate.shared.promisedDates', ['editable' => $list->app_status === 'approved'])
                                            </td>
                                        @endif
                                        <td class="md-sub">
                                            {{ $item->updated_by ?: $item->created_by ?: '—' }}
                                            <br><small>{{ $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('d/m/Y') : '' }}</small>
                                        </td>
                                        <td>
                                            @if ($estCanEdit)
                                                <div class="md-actions">
                                                    <button type="button" class="btn btn-sm btn-warning btn-est-item-edit" title="Sửa"
                                                        data-row="{{ json_encode([
                                                            'id' => $item->id,
                                                            'category_id' => $item->category_id,
                                                            'standard_name' => $item->standard_name,
                                                            'group_key' => $item->group_key,
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
                                                        data-title="Xoá chất chuẩn khỏi phiếu?"
                                                        data-text="Chất chuẩn &quot;{{ $item->display_name }}&quot; và toàn bộ số lượng theo tháng sẽ bị xoá khỏi phiếu {{ $list->code }}."
                                                        data-danger="1">
                                                        @csrf
                                                        <input type="hidden" name="id" value="{{ $item->id }}">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Xoá">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            @endif
                                            {{-- Hoàn thành / Huỷ mục đã duyệt (phòng đề nghị), xác nhận huỷ (bộ phận mua hàng) --}}
                                            @include('pages.estimate.shared.itemStatus', ['trackPermission' => 'estimate_standard_tracking'])
                                            @include('pages.estimate.shared.itemChatButton', ['chatPermission' => 'estimate_standard_chat'])
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    @include('pages.estimate.shared.detailStickyHeader')
@endsection

@section('model')
    @if ($canEditItems)
        @include('pages.estimate.StandardEstimate.itemCreate')
        @include('pages.estimate.StandardEstimate.itemUpdate')
    @endif

    @include('pages.estimate.shared.historyModal')
    @include('pages.estimate.StandardEstimate.reject')
@endsection
