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

        /* Vật tư có file đính kèm: nhãn cam nổi bật kèm số file */
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

        .est-lead-warn {
            white-space: normal;
            text-align: left;
            line-height: 1.35;
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

                        @if ($canEditItems && user_can('estimate_material_update'))
                            <button type="button" class="btn btn-primary btn-est-item-create">
                                <i class="fas fa-plus mr-1"></i> Thêm vật tư
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
                        @if ($list->app_status === 'pending_sign' && $canSignCurrent && $pendingSign && user_can('estimate_material_sign'))
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
                                    <th style="width: 95px">Mã Vật Tư</th>
                                    <th style="width: 220px">Vật Tư</th>
                                    <th style="width: 90px">NSX</th>
                                    <th style="width: 130px">Quy Cách</th>
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
                                    <tr>
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td class="font-weight-bold text-nowrap">{{ $item->material_code ?: '—' }}</td>
                                        <td>
                                            <div class="font-weight-bold">{{ $item->display_name ?: '—' }}</div>
                                            @unless ($item->category_id)
                                                <span class="est-outside">Ngoài danh mục</span>
                                            @endunless

                                            {{-- File đính kèm của riêng vật tư này - có file thì nổi màu kèm số lượng --}}
                                            <div class="est-item-files">
                                                @if ($item->files->count())
                                                    <span class="est-file-count" title="Vật tư này có {{ $item->files->count() }} file đính kèm">
                                                        <i class="fas fa-paperclip mr-1"></i>{{ $item->files->count() }} file đính kèm
                                                    </span>
                                                @endif
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
                                        <td class="md-sub">
                                            {{ $item->category_id ? ($item->category_manufacturer_short_name ?: ($item->category_manufacturer_name ?: '—')) : '—' }}
                                        </td>
                                        <td class="md-sub">
                                            {{ $item->category_id ? ($item->category_technical_specification ?: '—') : '—' }}
                                            @if ($item->part_number)
                                                <div class="mt-1">P/N: <b>{{ $item->part_number }}</b></div>
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
                                            @if ($canEditItems && user_can('estimate_material_update'))
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
                                            @endif
                                            {{-- Hoàn thành / Huỷ mục đã duyệt (phòng đề nghị), xác nhận huỷ (bộ phận mua hàng) --}}
                                            @include('pages.estimate.shared.itemStatus', ['trackPermission' => 'estimate_material_tracking'])
                                            @include('pages.estimate.shared.itemChatButton', ['chatPermission' => 'estimate_material_chat'])
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

    <style>
        /*
        | TIÊU ĐỀ BẢNG CỐ ĐỊNH KHI CUỘN - position: sticky ngay trên <th> gốc nên tiêu đề và
        | thân bảng luôn chung một bảng, cột không bao giờ lệch (FixedHeader chép tiêu đề ra
        | bảng nổi riêng nên lệch cột với bảng có Responsive).
        |
        | Sticky chỉ bám được cửa sổ khi không thẻ cha nào cắt overflow: mở .table-responsive
        | của trang này ra - bảng đã bật Responsive (cột thừa tự thu gọn) nên không cần cuộn ngang.
        | --est-sticky-top = chiều cao topNAV cố định, JS bên dưới đo lại cho đúng.
        */
        .est-detail-table {
            overflow: visible;
        }

        .est-detail-table #mdTable thead th {
            position: sticky;
            top: var(--est-sticky-top, 57px);
            z-index: 3;
            background: var(--primary-soft);
            box-shadow: inset 0 -2px 0 var(--primary-lighter);
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function estStickyTop() {
                var height = $('.main-header').outerHeight() || 57;

                document.documentElement.style.setProperty('--est-sticky-top', height + 'px');
            }

            estStickyTop();
            $(window).on('resize', estStickyTop);
        });
    </script>
@endsection

@section('model')
    @if ($canEditItems)
        @include('pages.estimate.MaterialEstimate.itemCreate')
        @include('pages.estimate.MaterialEstimate.itemUpdate')
    @endif

    @include('pages.estimate.shared.historyModal')
    @include('pages.estimate.MaterialEstimate.reject')
@endsection
