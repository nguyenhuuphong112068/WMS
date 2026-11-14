{{--
| SỬ DỤNG HOÁ CHẤT - TAB "TRẢ HOÁ CHẤT ACETONITRIL HOẶC METHANOL VỀ KHO"
|
| Hai nhóm xem ($returnView):
|   usage   : phiếu sử dụng Acetonitril / Methanol còn hiệu lực, lượng đã trả, lượng còn trả được + nút [Trả về kho]
|   history : các lần trả về kho (dòng chemical_exports type = 'return', số lượng âm) + nút [Huỷ phiếu trả]
| Xem App\Support\ChemicalReturn + App\Http\Controllers\Concerns\ChemicalReturnActions.
|
| Dữ liệu vào: $returnRows (paginator), $returnRange, $returnKeyword, $returnPerPage, $returnView.
--}}

@php
    $rtKeep = collect(request()->query())
        ->reject(fn($value, $key) => is_array($value) || in_array($key, ['tab', 'rview', 'ret_page'], true))
        ->all();
    $rtViewUrl = fn($view) => route($expRoute . 'list', array_merge($rtKeep, ['tab' => 'return', 'rview' => $view]));
    $rtHistory = $returnView === 'history';
    $rtCanReturn = user_can(\App\Support\ChemicalReturn::PERMISSION);
@endphp

<style>
    .rt-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 12px;
    }

    .rt-filter {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 12px;
        border: 1px solid var(--primary-lighter);
        border-radius: var(--border-radius-md, 8px);
        background: #fff;
        color: var(--primary-dark);
        font-weight: 600;
        transition: all 0.2s;
    }

    .rt-filter:hover {
        text-decoration: none;
        color: var(--primary-dark);
        background: var(--primary-soft);
        transform: translateY(-1px);
    }

    .rt-filter.is-on {
        background: var(--primary);
        border-color: var(--primary);
        color: #fff;
    }

    .rt-amount-returned {
        color: #15803D;
        font-weight: 700;
    }

    .rt-row-off td {
        opacity: 0.55;
    }
</style>

<div class="rt-toolbar">
    <a href="{{ $rtViewUrl('usage') }}" class="rt-filter {{ $rtHistory ? '' : 'is-on' }}">
        <i class="fas fa-flask"></i> Phiếu sử dụng
    </a>
    <a href="{{ $rtViewUrl('history') }}" class="rt-filter {{ $rtHistory ? 'is-on' : '' }}">
        <i class="fas fa-history"></i> Lịch sử trả về kho
    </a>
</div>

@include('pages.shared.rangeFilter', [
    'rfRoute' => $expRoute . 'list',
    'rfTab' => 'return',
    'rfPrefix' => 'ret_',
    'rfInBar' => true,
    'rfRange' => $returnRange,
    'rfPerPage' => $returnPerPage,
    'rfKeyword' => $returnKeyword,
    'rfHidden' => ['rview' => $returnView],
    'rfDateLabel' => $rtHistory ? 'Ngày trả' : 'Ngày sử dụng',
    'rfPlaceholder' => $rtHistory ? 'Mã xuất nhập, số lô, tên hoá chất, lý do trả về, người trả...' : 'Mã xuất nhập, số lô, tên hoá chất, người sử dụng...',
])

<div class="table-responsive">
    @if ($rtHistory)
        <table id="expReturnTable" class="table table-bordered table-hover w-100 md-table" data-server-paged>
            <thead>
                <tr>
                    <th class="text-center" style="width:50px">STT</th>
                    <th style="width:140px">Mã Xuất Nhập</th>
                    <th>Hoá Chất</th>
                    <th class="text-right" style="width:110px">Lượng Trả</th>
                    <th class="text-center" style="width:105px">Ngày Trả</th>
                    <th style="width:140px">Người Trả</th>
                    <th style="width:200px">Lý Do Trả Về</th>
                    <th style="width:190px">Phiếu Sử Dụng Gốc</th>
                    <th class="text-center" style="width:110px">Trạng Thái</th>
                    <th class="text-center" style="width:80px">Thao Tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($returnRows as $row)
                    @php $rtUnit = $row->unit_short_name ?: $row->unit_name; @endphp
                    <tr class="{{ $row->status_id == 1 ? '' : 'rt-row-off' }}">
                        <td class="text-center">{{ $returnRows->firstItem() + $loop->index }}</td>
                        <td>
                            <span class="exp-code">{{ $row->code }}</span>
                            <div class="md-sub mt-1"><span class="md-tag">{{ $row->category_code ?: '—' }}</span></div>
                        </td>
                        <td>
                            <div class="font-weight-bold">{{ $row->chem_name ?: '—' }}</div>
                            @if ($row->batch_no)
                                <div class="md-sub">Lô {{ $row->batch_no }}</div>
                            @endif
                        </td>
                        <td class="text-right">
                            <span class="rt-amount-returned">{{ $expNum(-(float) $row->amount) }}</span>
                            <span class="md-sub">{{ $rtUnit }}</span>
                        </td>
                        <td class="text-center md-sub">{{ $expDate($row->exported_date) }}</td>
                        <td class="md-sub">{{ $row->exported_by ?: '—' }}</td>
                        <td class="md-sub">{{ $row->purpose ?: '—' }}</td>
                        <td class="md-sub">
                            <div>{{ $expDate($row->source_date) }} · {{ $expNum($row->source_amount) }} {{ $rtUnit }}</div>
                            <div class="text-muted">{{ $row->source_by ?: '—' }}{{ $row->source_purpose ? ' · ' . $row->source_purpose : '' }}</div>
                        </td>
                        <td class="text-center">
                            @if ($row->status_id == 1)
                                <span class="exp-badge exp-badge-return">Đã trả về kho</span>
                            @else
                                <span class="exp-badge exp-badge-cancel">Đã huỷ</span>
                                <div class="md-sub mt-1">{{ $row->updated_by }} · {{ $expDateTime($row->updated_at) }}</div>
                            @endif
                        </td>
                        <td class="text-center">
                            @if ($row->status_id == 1 && $rtCanReturn)
                                <form class="form-md-confirm d-inline" action="{{ route($expRoute . 'returnCancel') }}" method="POST"
                                    data-title="Huỷ phiếu trả về kho?"
                                    data-text="Lượng {{ $expNum(-(float) $row->amount) }} {{ $rtUnit }} của mã &quot;{{ $row->code }}&quot; sẽ bị trừ khỏi tồn kho trở lại."
                                    data-require-reason="1"
                                    data-danger="1">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $row->id }}">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Huỷ phiếu trả về kho">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                </form>
                            @else
                                <span class="md-empty">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center text-muted">Chưa có lần trả hoá chất về kho nào trong khoảng đã lọc.</td></tr>
                @endforelse
            </tbody>
        </table>
    @else
        <table id="expReturnTable" class="table table-bordered table-hover w-100 md-table" data-server-paged>
            <thead>
                <tr>
                    <th class="text-center" style="width:50px">STT</th>
                    <th style="width:140px">Mã Xuất Nhập</th>
                    <th>Hoá Chất</th>
                    <th class="text-center" style="width:105px">Ngày Sử Dụng</th>
                    <th style="width:140px">Người Sử Dụng</th>
                    <th style="width:200px">Lý Do Trả Về</th>
                    <th class="text-right" style="width:110px">Đã Sử Dụng</th>
                    <th class="text-right" style="width:100px">Đã Trả</th>
                    <th class="text-right" style="width:120px">Còn Trả Được</th>
                    <th class="text-center" style="width:80px">Thao Tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($returnRows as $row)
                    @php
                        $rtUnit = $row->unit_short_name ?: $row->unit_name;
                        $rtLocked = $row->import_status_id != 1
                            ? 'Lô đã bị khoá'
                            : ($row->returnable <= \App\Support\ChemicalReturn::EPSILON ? 'Đã trả hết lượng sử dụng' : '');
                        $rtData = [
                            'id' => $row->id,
                            'code' => $row->code,
                            'chem_name' => $row->chem_name,
                            'batch_no' => $row->batch_no,
                            'date' => $expDate($row->exported_date),
                            'by' => $row->exported_by,
                            'used' => $expNum($row->amount),
                            'returned' => $expNum($row->returned),
                            'returnable' => (float) $row->returnable,
                            'returnable_text' => $expNum($row->returnable),
                            'unit' => $rtUnit,
                        ];
                    @endphp
                    <tr>
                        <td class="text-center">{{ $returnRows->firstItem() + $loop->index }}</td>
                        <td>
                            <span class="exp-code">{{ $row->code }}</span>
                            <div class="md-sub mt-1"><span class="md-tag">{{ $row->category_code ?: '—' }}</span></div>
                        </td>
                        <td>
                            <div class="font-weight-bold">{{ $row->chem_name ?: '—' }}</div>
                            @if ($row->batch_no)
                                <div class="md-sub">Lô {{ $row->batch_no }}</div>
                            @endif
                        </td>
                        <td class="text-center md-sub">{{ $expDate($row->exported_date) }}</td>
                        <td class="md-sub">{{ $row->exported_by ?: '—' }}</td>
                        <td class="md-sub">
                            @forelse ($row->return_reasons as $reason)
                                <div>{{ $reason }}</div>
                            @empty
                                <span class="md-empty">—</span>
                            @endforelse
                        </td>
                        <td class="text-right">
                            <span class="exp-amount">{{ $expNum($row->amount) }}</span>
                            <span class="md-sub">{{ $rtUnit }}</span>
                        </td>
                        <td class="text-right">
                            @if ($row->returned > 0)
                                <span class="rt-amount-returned">{{ $expNum($row->returned) }}</span>
                            @else
                                <span class="md-empty">—</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <span class="exp-amount {{ $row->returnable > 0 ? '' : 'md-empty' }}">{{ $expNum($row->returnable) }}</span>
                            <span class="md-sub">{{ $rtUnit }}</span>
                        </td>
                        <td class="text-center">
                            @if ($rtCanReturn)
                                <button type="button" class="btn btn-sm btn-success btn-ret-open"
                                    {{ $rtLocked ? 'disabled' : '' }}
                                    title="{{ $rtLocked ?: 'Trả về kho' }}"
                                    data-row="{{ json_encode($rtData) }}">
                                    <i class="fas fa-undo-alt"></i>
                                </button>
                            @else
                                <span class="md-empty">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center text-muted">Không có phiếu sử dụng Acetonitril / Methanol nào trong khoảng đã lọc.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif
</div>

@include('pages.shared.paginator', [
    'pgItems' => $returnRows,
    'pgTab' => 'return',
    'pgUnit' => $rtHistory ? 'phiếu trả' : 'phiếu sử dụng',
])
