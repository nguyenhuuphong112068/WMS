@include('pages.import.shared.assets')

@php
    $impToday = \Carbon\Carbon::today();
@endphp

<style>
    /* Mã nhóm chuẩn của chính ống này */
    .sd-group-tag {
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
    .sd-badge-tag {
        display: inline-block;
        border-radius: 4px;
        padding: 1px 6px;
        font-size: 0.7rem;
        font-weight: 600;
        margin-right: 2px;
        margin-bottom: 2px;
    }
</style>

<div class="content-wrapper">
    <div class="md-page">

        <div class="card md-card">
            <div class="card-body">

                <div class="imp-tabs">
                    <button type="button" class="imp-tab {{ $activeTab === 'book' ? 'is-active' : '' }}"
                        data-pane="impPaneBook">
                        <i class="fas fa-book mr-1"></i> Sổ nhập chất chuẩn
                    </button>
                </div>

                {{-- ============ SỔ NHẬP CHẤT CHUẨN ============ --}}
                <div class="imp-pane {{ $activeTab === 'book' ? 'is-active' : '' }}" id="impPaneBook">

                    <div class="md-toolbar">
                        @perm('import_standard_create')
                            <button type="button" class="btn btn-primary btn-md-create">
                                <i class="fas fa-plus mr-1"></i> Nhập chất chuẩn
                            </button>
                        @endperm
                        <p class="hint">
                            <i class="fas fa-info-circle mr-1"></i>
                            Đang hiệu lực {{ $datas->where('status_id', 1)->count() }}/{{ $datas->count() }} ống chuẩn.
                            Mã ống chuẩn = <b>{{ session('user')['selected_department'] }}</b> + mã nhóm chuẩn +
                            năm + tháng + số thứ tự trong năm.
                        </p>
                    </div>

                    @include('pages.shared.barcodeSearch', [
                        'scanTitle' => 'Quét mã vạch',
                        'scanTables' => [
                            ['id' => 'mdTable', 'column' => 1, 'pane' => 'impPaneBook', 'label' => 'Sổ nhập chất chuẩn'],
                        ],
                    ])

                    @include('pages.shared.standardGroupFilter', ['sgrTarget' => 'mdTable'])

                    <div class="table-responsive">
                        <table id="mdTable" class="table table-bordered table-hover w-100">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 45px">STT</th>
                                    <th style="width: 165px">Mã Ống Chuẩn</th>
                                    <th>Chất Chuẩn</th>
                                    <th style="width: 175px">Đặc Tính</th>
                                    <th class="text-right" style="width: 95px">Số Lượng</th>
                                    <th style="width: 100px">Số Lô</th>
                                    <th style="width: 110px">Số PKN / CoA</th>
                                    <th style="width: 165px" title="Vị trí lưu trữ thực tế của ống chuẩn">
                                        Vị Trí Lưu Trữ</th>
                                    <th class="text-center" style="width: 95px">Ngày Nhập</th>
                                    <th class="text-center" style="width: 120px">Hạn Dùng / Retest</th>
                                    <th class="text-center" style="width: 65px" title="File hồ sơ đính kèm"><i class="fas fa-paperclip"></i></th>
                                    <th class="text-center" style="width: 135px">Thao Tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($datas as $row)
                                    @php
                                        $impExpired = $row->expired_date ? \Carbon\Carbon::parse($row->expired_date) : null;
                                        $impExpiredClass = '';
                                        if ($impExpired) {
                                            $impExpiredClass = $impExpired->lt($impToday)
                                                ? 'imp-expired'
                                                : ($impExpired->lte($impToday->copy()->addDays(30))
                                                    ? 'imp-expiring'
                                                    : '');
                                        }

                                        $rowAttachments = $attachments->get($row->id) ?? collect();
                                        $hasProperties = $row->standard_form || $row->potency || $row->moisture || $row->weight_controlled || $row->requires_aliquot;
                                    @endphp
                                    {{-- data-groups để bộ lọc Phân nhóm chuẩn nhận ra dòng này --}}
                                    <tr data-groups="{{ $impGroups($row->groups) }}">
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="imp-code font-weight-bold">{{ $row->code }}</div>
                                            <div class="mt-1">
                                                <span class="md-tag">{{ $row->category_code ?: '—' }}</span>
                                                <span class="sgr-version ml-1">Ver {{ $row->category_version }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="font-weight-bold">{{ $row->standard_name ?: '—' }}</div>
                                            <div class="mt-1 d-flex flex-wrap align-items-center" style="gap: 4px;">
                                                <span class="sd-group-tag" title="Mã nhóm: {{ $row->group_code }}">
                                                    {{ $impGroupName($row->group_code) }}
                                                </span>
                                                @if ($row->cas_no)
                                                    <span class="md-sub small text-muted">CAS: {{ $row->cas_no }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            @if ($hasProperties)
                                                @if ($row->standard_form)
                                                    <div class="mb-1">
                                                        <span class="badge badge-secondary">{{ $row->standard_form }}</span>
                                                    </div>
                                                @endif
                                                <div>
                                                    @if ($row->potency)
                                                        <span class="sd-badge-tag bg-light text-dark border" title="Hàm lượng">
                                                            HL: <b>{{ $row->potency }}</b>
                                                        </span>
                                                    @endif
                                                    @if ($row->moisture)
                                                        <span class="sd-badge-tag bg-light text-dark border" title="Độ ẩm">
                                                            Ẩm: <b>{{ $row->moisture }}</b>
                                                        </span>
                                                    @endif
                                                    @if ($row->weight_controlled)
                                                        <span class="sd-badge-tag badge-warning text-dark" title="Cần kiểm soát khối lượng">
                                                            <i class="fas fa-balance-scale"></i> Kiểm soát KL
                                                        </span>
                                                    @endif
                                                    @if ($row->requires_aliquot)
                                                        <span class="sd-badge-tag badge-info" title="Cần triết ống trước khi dùng">
                                                            <i class="fas fa-vial"></i> Triết ống
                                                        </span>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-right" data-order="{{ $row->amount }}">
                                            <span class="imp-amount">{{ $impNum($row->amount) }}</span>
                                            <span class="md-sub">{{ $row->unit_short_name ?: $row->unit_name }}</span>
                                        </td>
                                        <td class="md-sub">{{ $row->batch_no ?: '—' }}</td>
                                        <td class="md-sub">{{ $row->coa_no ?: '—' }}</td>
                                        <td class="md-sub">
                                            @if ($row->location_code)
                                                <div class="font-weight-bold">
                                                    <span class="md-tag">{{ $row->location_code }}</span>
                                                </div>
                                                <div>{{ $row->warehouse_name ?: '—' }} / {{ $row->room_name ?: '—' }} /
                                                    {{ $row->shelf_name ?: '—' }}</div>
                                            @else
                                                <span class="imp-no-location">Chưa xếp vị trí</span>
                                            @endif
                                        </td>
                                        <td class="text-center md-sub" data-order="{{ $row->imported_date }}">
                                            {{ $impDate($row->imported_date) }}
                                        </td>
                                        <td class="text-center md-sub {{ $impExpiredClass }}"
                                            data-order="{{ $row->expired_date ?: '9999-12-31' }}">
                                            @if ($row->expiry_type === 'check online' || $row->expiry_type === 'undetermined' || $row->expiry_type === 'unlimited')
                                                <span class="badge badge-warning" title="Hạn dùng chưa xác định từ NSX. Tra cứu trực tuyến khi sử dụng.">
                                                    <i class="fas fa-globe"></i> Check online
                                                </span>
                                            @elseif ($row->expiry_type === 'retest')
                                                <div class="font-weight-bold">{{ $impDate($row->expired_date) }}</div>
                                                <span class="badge badge-info" title="Hạn retest do NSX công bố">
                                                    Retest
                                                </span>
                                            @elseif ($row->expiry_type === 'Requires_re-evaluation')
                                                <div class="font-weight-bold">{{ $impDate($row->expired_date) }}</div>
                                                <span class="badge badge-secondary" title="Cần xác định lại hạn dùng nội bộ sau khi mở ống">
                                                    Hạn nội bộ
                                                </span>
                                            @else
                                                {{ $impDate($row->expired_date) }}
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if ($rowAttachments->isNotEmpty())
                                                <div class="dropdown">
                                                    <button class="btn btn-xs btn-outline-primary dropdown-toggle" type="button" data-toggle="dropdown" title="Xem danh sách file đính kèm">
                                                        <i class="fas fa-paperclip"></i> ({{ $rowAttachments->count() }})
                                                    </button>
                                                    <div class="dropdown-menu dropdown-menu-right shadow-sm">
                                                        @foreach ($rowAttachments as $att)
                                                            <a class="dropdown-item small py-2" href="{{ route($impRoute . 'downloadAttachment', ['id' => $att->id]) }}" target="_blank" title="Mở xem file trực tiếp">
                                                                <i class="fas fa-external-link-alt mr-2 text-primary"></i> {{ $att->file_name }}
                                                            </a>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="md-actions">
                                                @php $impAdjust = (int) ($historyCounts[$row->id] ?? 0); @endphp
                                                <span class="imp-btn-wrap">
                                                    @perm('import_standard_update')
                                                        <button type="button" class="btn btn-sm btn-warning btn-md-edit"
                                                            title="Điều chỉnh thông tin nhập"
                                                            data-row="{{ json_encode([
                                                                'id' => $row->id,
                                                                'code' => $row->code,
                                                                'category_id' => $row->category_id,
                                                                'group_key' => $impGroupKey($row->group_code),
                                                                'group_label' => $impGroupName($row->group_code),
                                                                'amount' => $row->amount,
                                                                'imported_date' => $row->imported_date,
                                                                'expiry_type' => $row->expiry_type ?: 'Specify',
                                                                'expired_date' => $row->expired_date,
                                                                'retest_interval_months' => $row->retest_interval_months,
                                                                'batch_no' => $row->batch_no,
                                                                'coa_no' => $row->coa_no,
                                                                'potency' => $row->potency,
                                                                'moisture' => $row->moisture,
                                                                'standard_form' => $row->standard_form,
                                                                'weight_controlled' => $row->weight_controlled,
                                                                'requires_aliquot' => $row->requires_aliquot,
                                                                'location_id' => $row->location_id,
                                                                'purpose_id' => $row->purpose_id,
                                                                'note' => $row->note,
                                                                'attachments' => $rowAttachments->map(fn($a) => ['id' => $a->id, 'file_name' => $a->file_name])->toArray(),
                                                            ]) }}">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                    @endperm

                                                    @if ($impAdjust > 0)
                                                        <button type="button" class="imp-count-badge btn-imp-history"
                                                            title="Xem {{ $impAdjust }} lần điều chỉnh của phiếu này"
                                                            data-url="{{ route($impRoute . 'history', ['id' => $row->id]) }}"
                                                            data-title="{{ $row->code }} - {{ $row->standard_name }}">{{ $impAdjust }}</button>
                                                    @endif
                                                </span>

                                                <a class="btn btn-sm btn-outline-secondary" target="_blank"
                                                    title="In nhãn dán ống chuẩn (mã vạch Code 128)"
                                                    @perm('import_standard_label')
                                                        href="{{ route($impRoute . 'label', ['id' => $row->id]) }}">
                                                        <i class="fas fa-tag"></i>
                                                    </a>
                                                    @endperm

                                                @perm('import_standard_delete')
                                                    <form class="form-md-confirm d-inline"
                                                        action="{{ route($impRoute . 'deActive') }}" method="POST"
                                                        data-title="{{ $row->status_id == 1 ? 'Khoá' : 'Mở khoá' }} {{ $impLabel }}?"
                                                        data-text="{{ $row->status_id == 1 ? 'Sau khi khoá' : 'Sau khi mở khoá' }}, ống chuẩn &quot;{{ $row->code }}&quot; {{ $row->status_id == 1 ? 'sẽ không còn được tính vào tồn kho.' : 'sẽ được tính vào tồn kho trở lại.' }}"
                                                        data-danger="{{ $row->status_id == 1 ? '1' : '' }}">
                                                        @csrf
                                                        <input type="hidden" name="id" value="{{ $row->id }}">
                                                        <button type="submit"
                                                            class="btn btn-sm btn-{{ $row->status_id == 1 ? 'secondary' : 'primary' }}"
                                                            title="{{ $row->status_id == 1 ? 'Khoá' : 'Mở khoá' }}">
                                                            <i class="fas fa-{{ $row->status_id == 1 ? 'lock' : 'unlock' }}"></i>
                                                        </button>
                                                    </form>
                                                @endperm
                                            </div>
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
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        $('#mdTable').DataTable().order([8, 'desc']).draw();
    });
</script>
