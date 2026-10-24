{{--
| Một bảng của tab Báo cáo nhập hoá chất - dùng chung cho card Hoá chất mua và 2 tab con
| Nhận chuyển cùng công ty / khác công ty (ChemicalImportController::importReports()).
| Biến vào: $rpId (id bảng), $rpRows, $rpTransfer (true = bảng nhận chuyển), $rpEmpty
--}}
@php
    $rpTotalKg = $rpRows->whereNotNull('total_kg')->sum('total_kg');
    $rpNotConvertible = $rpRows->whereNull('total_kg')->count();
@endphp

@include('pages.shared.classificationFilter', ['clsTarget' => $rpId])

<div class="table-responsive">
    <table id="{{ $rpId }}" class="table table-bordered table-hover w-100 imp-report-table" data-empty="{{ $rpEmpty }}">
        <thead>
            <tr>
                <th class="text-center" style="width: 60px">STT</th>
                <th style="width: 120px">Mã Danh Mục</th>
                <th>Hoá Chất</th>
                <th class="text-center" style="width: 90px">{{ $rpTransfer ? 'Lượt Nhận' : 'Lượt Nhập' }}</th>
                <th class="text-center" style="width: 150px">{{ $rpTransfer ? 'Lần Nhận Gần Nhất' : 'Lần Nhập Gần Nhất' }}</th>
                <th class="text-right" style="width: 140px">{{ $rpTransfer ? 'Tổng Nhận' : 'Tổng Nhập' }}</th>
                <th class="text-right" style="width: 140px">Quy Đổi (Kg)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rpRows as $row)
                <tr data-classification="{{ $impCls($row->category_id) }}">
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td><span class="imp-code">{{ $row->category_code }}</span></td>
                    <td>
                        <div class="font-weight-bold">
                            {{ $row->chem_name ?: '—' }}
                            @if ($impIsSpecial($row->category_id))
                                <span class="badge-special-control ml-1" title="Hoá chất kiểm soát đặc biệt (Phụ lục III NĐ 24/2026)">
                                    <i class="fas fa-shield-alt"></i>Kiểm soát đặc biệt
                                </span>
                            @endif
                        </div>
                        <div class="md-sub">
                            @if ($rpTransfer)
                                Nhận từ: {{ $row->from_departments ?: '—' }}
                            @else
                                {{ $row->supplier_count }} nhà cung cấp
                            @endif
                            · đơn vị {{ $row->unit }}
                            @if ($row->density !== null)
                                · d = {{ $impNum($row->density) }} g/ml
                            @endif
                        </div>
                    </td>
                    <td class="text-center md-sub" data-order="{{ $row->times }}">
                        {{ $row->times }}
                    </td>
                    <td class="text-center md-sub" data-order="{{ $row->last_imported_date ?: '0000-00-00' }}">
                        {{ $impDate($row->last_imported_date) }}
                    </td>
                    <td class="text-right" data-order="{{ $row->total }}">
                        <span class="imp-amount">{{ $impNum($row->total) }}</span>
                        <span class="md-sub">{{ $row->unit }}</span>
                    </td>
                    <td class="text-right" data-order="{{ $row->total_kg ?? -1 }}">
                        @if ($row->total_kg !== null)
                            <span class="imp-kg">{{ $impNum($row->total_kg) }}</span>
                            <span class="md-sub">kg</span>
                        @else
                            <span class="imp-kg-na" title="{{ $row->convert_note }}">Không quy đổi</span>
                            @if ($row->convert_cause ?? '')
                                <div class="kg-na-cause" title="{{ $row->convert_note }}">
                                    <i class="fas fa-info-circle"></i>{{ $row->convert_cause }}
                                </div>
                            @endif
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
        @if ($rpRows->count())
            <tfoot>
                <tr>
                    <th colspan="5" class="text-right">Tổng cộng quy đổi</th>
                    <th class="text-right md-sub">{{ $rpNotConvertible }} mã không quy đổi được</th>
                    <th class="text-right">
                        <span class="imp-kg">{{ $impNum($rpTotalKg) }}</span>
                        <span class="md-sub">kg</span>
                    </th>
                </tr>
            </tfoot>
        @endif
    </table>
</div>
