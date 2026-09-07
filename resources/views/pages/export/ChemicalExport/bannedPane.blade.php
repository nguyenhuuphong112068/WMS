{{--
| SỬ DỤNG - TAB HOÁ CHẤT CẤM (SỔ HOÁ CHẤT CẤM)
|
| Sổ theo dõi từng LÔ hoá chất thuộc Nhóm HC Cấm (Luật Đầu tư 2025, số 143/2025/QH15):
| mỗi dòng là MỘT lần nhập hoặc xuất/sử dụng, xếp theo thời gian tăng dần, cộng dồn
| "Tồn của lô" (theo import_id) và "Tổng tồn các lô" (theo category_id, gộp mọi lô của
| cùng hoá chất) - đúng cách một cuốn sổ giấy được ghi tay qua từng lần nhập/xuất.
| Dữ liệu do ChemicalExportController::bannedLedger() dựng sẵn, chỉ để xem/tra cứu -
| thao tác Sửa/Khoá phiếu vẫn thực hiện ở tab Sổ sử dụng hoá chất / màn Nhập Hoá Chất.
--}}

<div class="exp-pane {{ $activeTab === 'banned' ? 'is-active' : '' }}" id="expPaneBanned">

    @include('pages.shared.rangeFilter', [
        'rfRoute' => $expRoute . 'list',
        'rfTab' => 'banned',
        'rfPrefix' => 'ban_',
        'rfRange' => $bannedRange,
        'rfPerPage' => $bannedPerPage,
        'rfSearch' => false,
        'rfDateLabel' => 'Ngày ghi sổ',
    ])

    <div class="table-responsive">
        <table id="expBannedTable" class="table table-bordered table-hover w-100 bld-table">
            <thead>
                <tr>
                    <th rowspan="2" class="align-middle" style="width: 130px">Mã</th>
                    <th rowspan="2" class="text-center align-middle" style="width: 100px">Ngày Tháng</th>
                    <th rowspan="2" class="align-middle" style="width: 170px">Số Lô Hoá Chất</th>
                    <th rowspan="2" class="align-middle" style="width: 150px">Mua Từ Nhà Cung Cấp</th>
                    <th rowspan="2" class="align-middle" style="width: 130px">Số Chứng Từ Mua</th>
                    <th colspan="4" class="text-center">Khối Lượng</th>
                    <th rowspan="2" class="align-middle" style="width: 110px">Vị Trí Lưu Kho</th>
                    <th rowspan="2" class="align-middle" style="width: 170px">Mục Đích Sử Dụng</th>
                    <th rowspan="2" class="align-middle" style="width: 130px">Người Thực Hiện</th>
                    <th rowspan="2" class="align-middle" style="width: 130px">Người Kiểm Tra</th>
                </tr>
                <tr>
                    <th class="text-center" style="width: 100px">Nhập</th>
                    <th class="text-center" style="width: 100px">Xuất</th>
                    <th class="text-center" style="width: 110px">Tồn Của Lô</th>
                    <th class="text-center" style="width: 120px">Tổng Tồn Các Lô</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($bannedLedger as $row)
                    <tr class="{{ $row->event_type === 'import' ? 'bld-row-in' : 'bld-row-out' }}">
                        <td>
                            <span class="exp-code">{{ $row->code ?: '—' }}</span>
                            <div class="md-sub mt-1">
                                <span class="md-tag">{{ $row->category_code ?: '—' }}</span>
                            </div>
                        </td>
                        <td class="text-center md-sub" data-order="{{ $row->event_date }}">{{ $expDate($row->event_date) }}</td>
                        <td>
                            <div class="font-weight-bold">{{ $row->batch_no ?: '—' }}</div>
                            <div class="md-sub">{{ $row->chem_name ?: '—' }}</div>
                        </td>
                        <td class="md-sub">{{ $row->supplier_name ?: '—' }}</td>
                        <td class="md-sub">{{ $row->invoice_number ?: '—' }}</td>
                        <td class="text-right" data-order="{{ $row->imported_amount ?? -1 }}">
                            @if ($row->imported_amount !== null)
                                <span class="exp-amount bld-in">{{ $expNum($row->imported_amount) }}</span>
                                <span class="md-sub">{{ $row->unit }}</span>
                            @else
                                <span class="md-empty">—</span>
                            @endif
                        </td>
                        <td class="text-right" data-order="{{ $row->exported_amount ?? -1 }}">
                            @if ($row->exported_amount !== null)
                                <span class="exp-amount bld-out">{{ $expNum($row->exported_amount) }}</span>
                                <span class="md-sub">{{ $row->unit }}</span>
                            @else
                                <span class="md-empty">—</span>
                            @endif
                        </td>
                        <td class="text-right" data-order="{{ $row->lot_balance }}">
                            <span class="exp-amount">{{ $expNum($row->lot_balance) }}</span>
                            <span class="md-sub">{{ $row->unit }}</span>
                        </td>
                        <td class="text-right" data-order="{{ $row->category_balance }}">
                            <span class="exp-amount">{{ $expNum($row->category_balance) }}</span>
                            <span class="md-sub">{{ $row->unit }}</span>
                        </td>
                        <td class="md-sub">{{ $row->location_code ?: '—' }}</td>
                        <td class="md-sub">
                            @if ($row->purpose)
                                <span class="md-note" title="{{ $row->purpose }}">{{ $row->purpose }}</span>
                            @else
                                <span class="md-empty">—</span>
                            @endif
                        </td>
                        <td class="md-sub">{{ $row->actor ?: '—' }}</td>
                        <td class="md-sub">{{ $row->checked_by ?: '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @include('pages.shared.paginator', [
        'pgItems' => $bannedLedger,
        'pgTab' => 'banned',
        'pgUnit' => 'dòng sổ',
    ])
</div>

@once
    <style>
        .bld-table thead th {
            background: var(--primary-soft);
            color: var(--primary-dark);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .bld-row-in {
            background: #F0FDF4;
        }

        .bld-row-out {
            background: #FFF7ED;
        }

        .bld-in {
            color: #15803D;
        }

        .bld-out {
            color: #C2410C;
        }
    </style>
@endonce
