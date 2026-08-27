@include('pages.export.shared.assets')

<style>
    /* Mã nhóm chuẩn của ống - phần nằm giữa mã ống chuẩn */
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
</style>

<div class="content-wrapper">
    <div class="md-page">

        <div class="card md-card">
            <div class="card-body">

                <div class="exp-tabs">
                    <button type="button" class="exp-tab {{ $activeTab === 'book' ? 'is-active' : '' }}"
                        data-pane="expPaneBook">
                        <i class="fas fa-book mr-1"></i> Sổ sử dụng chất chuẩn
                    </button>
                    <button type="button" class="exp-tab {{ $activeTab === 'request' ? 'is-active' : '' }}"
                        data-pane="expPaneRequest">
                        <i class="fas fa-hand-holding-medical mr-1"></i> Đề nghị cấp phát chuẩn
                    </button>
                </div>

                {{-- ============ SỔ SỬ DỤNG CHẤT CHUẨN ============ --}}
                <div class="exp-pane {{ $activeTab === 'book' ? 'is-active' : '' }}" id="expPaneBook">

                    <div class="md-toolbar">
                        <button type="button" class="btn btn-primary btn-md-create">
                            <i class="fas fa-plus mr-1"></i> Sử dụng chất chuẩn
                        </button>
                        <p class="hint">
                            <i class="fas fa-info-circle mr-1"></i>
                            Tổng cộng <b>{{ $datas->count() }}</b> phiếu.
                            Được xuất vượt tồn tối đa <b>{{ $overIssuePercent }}%</b> để bù sai số cân đong.
                        </p>
                    </div>

                    @include('pages.shared.barcodeSearch', [
                        'scanTitle' => 'Quét mã vạch',
                        'scanTables' => [
                            ['id' => 'mdTable', 'column' => 1, 'pane' => 'expPaneBook', 'label' => 'Sổ sử dụng chất chuẩn'],
                        ],
                    ])

                    @include('pages.shared.standardGroupFilter', ['sgrTarget' => 'mdTable'])

                    <div class="table-responsive">
                        <table id="mdTable" class="table table-bordered table-hover w-100">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 50px">STT</th>
                                    <th style="width: 155px">Mã Ống Chuẩn</th>
                                    <th>Chất Chuẩn</th>
                                    <th style="width: 110px">Tổ</th>
                                    <th class="text-right" style="width: 100px">Số Lượng</th>
                                    <th class="text-center" style="width: 95px">Loại Phiếu</th>
                                    <th class="text-center" style="width: 120px">Thời Gian</th>
                                    <th style="width: 180px">Sản Phẩm / Lô / Chỉ Tiêu</th>
                                    <th style="width: 130px">Người Thực Hiện</th>
                                    <th class="text-center" style="width: 90px">Thao Tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($datas as $row)
                                    <tr data-groups="{{ $expGroups($row->groups) }}">
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td>
                                            <span class="exp-code">{{ $row->code }}</span>
                                            <div class="mt-1">
                                                <span class="sd-group-tag">{{ $expGroupName($row->group_code) }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="font-weight-bold">{{ $row->standard_name ?: '—' }}</div>
                                            <div class="md-sub">
                                                <span class="md-tag">{{ $row->category_code ?: '—' }}</span>
                                                <span class="sgr-version ml-1">v{{ $row->category_version }}</span>
                                                @if ($row->standard_batch_no ?? $row->batch_no)
                                                    <span class="ml-1">Lô {{ $row->standard_batch_no ?? $row->batch_no }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            @if ($row->group_name)
                                                <span class="font-weight-bold text-primary">{{ $row->group_name }}</span>
                                            @else
                                                <span class="md-empty">—</span>
                                            @endif
                                        </td>
                                        <td class="text-right" data-order="{{ $row->amount }}">
                                            <span class="exp-amount">{{ $expNum($row->amount) }}</span>
                                            <span class="md-sub">{{ $row->unit_short_name ?: $row->unit_name }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="exp-badge exp-badge-{{ $row->type }}">
                                                {{ $types[$row->type] ?? $row->type }}
                                            </span>
                                        </td>
                                        <td class="text-center md-sub" data-order="{{ $row->created_at }}">
                                            {{ $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('d/m/Y H:i') : '—' }}
                                        </td>
                                        <td class="md-sub">
                                            @if ($row->type === 'cancel')
                                                <div class="text-danger small font-weight-bold"><i class="fas fa-ban mr-1"></i> Lý do: {{ $row->reason ?: '—' }}</div>
                                            @else
                                                @if ($row->product_name)
                                                    <div class="font-weight-bold text-dark">{{ $row->product_name }}</div>
                                                @endif
                                                @if ($row->batch_no)
                                                    <div><small class="text-muted">Lô SP: {{ $row->batch_no }}</small></div>
                                                @endif
                                                @if ($row->testing)
                                                    <div><small class="text-info">CT: {{ $row->testing }}</small></div>
                                                @endif
                                                @if (!$row->product_name && !$row->batch_no && !$row->testing)
                                                    <span class="md-empty">—</span>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="md-sub">{{ $row->created_by ?: '—' }}</td>
                                        <td>
                                            <div class="md-actions text-center">
                                                @php $expAdjust = (int) ($adjustCounts[$row->id] ?? 0); @endphp
                                                <span class="exp-btn-wrap">
                                                    <button type="button" class="btn btn-sm btn-warning btn-md-edit"
                                                        title="Cập nhật phiếu"
                                                        data-row="{{ json_encode([
                                                             'id' => $row->id,
                                                             'import_id' => $row->import_id,
                                                             'group_id' => $row->group_id,
                                                             'amount' => $row->amount,
                                                             'type' => $row->type,
                                                             'product_name' => $row->product_name,
                                                             'batch_no' => $row->batch_no,
                                                             'testing' => $row->testing,
                                                             'reason' => $row->reason,
                                                         ]) }}">
                                                        <i class="fas fa-edit"></i>
                                                    </button>

                                                    @if ($expAdjust > 0)
                                                        <button type="button" class="exp-count-badge btn-exp-history"
                                                            title="Xem {{ $expAdjust }} lần điều chỉnh của phiếu này"
                                                            data-url="{{ route($expRoute . 'history', ['id' => $row->id]) }}"
                                                            data-title="{{ $row->code }} - {{ $row->standard_name }}">{{ $expAdjust }}</button>
                                                    @endif
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- ============ ĐỀ NGHỊ CẤP PHÁT CHUẨN ============ --}}
                @include('pages.export.StandardExport.requestPane')



            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Bảng dùng chung sắp theo cột 1, riêng sổ sử dụng cần xem lần dùng gần nhất trước
        // (cột 6 = Ngày Sử Dụng)
        $('#mdTable').DataTable().order([6, 'desc']).draw();
    });
</script>
