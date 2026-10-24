{{--
| Bảng phiếu hiệu chuẩn của tab "Xác Nhận Hiệu Chuẩn" - dùng chung cho 2 tab con.
| Biến vào: $calRows (mỗi phần tử một phiếu, đã sắp mới nhất trước), $calTableId, $calEmpty,
| cùng $calStatusMeta / $calTypeLabel khai ở calibrationPane.
--}}
<div class="table-responsive">
    <table id="{{ $calTableId }}" class="table table-bordered table-hover w-100 md-table">
        <thead>
            <tr>
                <th class="text-center" style="width:45px">STT</th>
                <th style="width:200px">Phiếu Hiệu Chuẩn</th>
                <th style="width:170px">Bộ Phận Đề Nghị</th>
                <th>Vật Tư</th>
                <th style="width:220px">Mã Xuất Nhập · Số Lượng</th>
                <th style="width:170px">Đề Nghị Cấp Phát</th>
                <th style="width:240px">Kết Quả QA</th>
                <th class="text-center" style="width:110px" data-orderable="false">Thao Tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($calRows as $row)
                @php
                    $meta = $calStatusMeta[$row->status] ?? ['label' => $row->status, 'class' => '', 'icon' => 'fas fa-circle'];
                    $calData = [
                        'code' => $row->code,
                        'material' => trim(($row->category_code ? $row->category_code . ' - ' : '') . ($row->material_name ?? '')),
                        'department' => $row->department_name,
                        'amount_text' => $expNum($row->amount) . ' ' . $row->unit,
                        // Chỉ dòng còn chờ QA - mỗi mã xuất nhập một ô Số lượng đạt
                        'lots' => $row->lines->where('app_status', 'pending')->map(fn ($line) => [
                            'id' => $line->id,
                            'code' => $line->import_code,
                            'amount' => (float) $line->amount,
                            // Đơn vị + Seri No người đề nghị đã nhập - QA chỉ chọn kết quả
                            'units' => $line->units->map(fn ($u) => [
                                'id' => $u->id,
                                'unit_no' => (int) $u->unit_no,
                                'serial' => $u->serial_no,
                                'amount' => (float) $u->amount,
                            ])->values(),
                        ])->values(),
                        'unit' => $row->unit,
                        'request_code' => $row->request_code,
                        'requested_text' => $expNum($row->item_requested_amount) . ' ' . $row->unit,
                        'total' => (float) $row->lines->where('app_status', 'pending')->sum('amount'),
                        'left' => (float) $row->item_left,
                    ];
                @endphp
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td data-order="{{ $row->requested_at }}">
                        <span class="exp-code font-weight-bold">{{ $row->code }}</span>
                        <div class="mt-1"><span class="cal-status {{ $meta['class'] }}"><i class="{{ $meta['icon'] }}"></i> {{ $meta['label'] }}</span></div>
                        <div class="md-sub text-muted mt-1">{{ $row->requested_by }} · {{ $expDateTime($row->requested_at) }}</div>
                        @if ($row->request_note)
                            <div class="md-sub small">{{ $row->request_note }}</div>
                        @endif
                    </td>
                    <td>
                        <span class="cal-dept">{{ $row->department_name ?: '—' }}</span>
                    </td>
                    <td>
                        <div class="font-weight-bold">{{ $row->material_name ?: '—' }}</div>
                        @if ($row->category_code)
                            <span class="md-tag">{{ $row->category_code }}</span>
                        @endif
                        @if ($row->technical_specification)
                            <div class="md-sub small text-muted">{{ $row->technical_specification }}</div>
                        @endif
                    </td>
                    <td>
                        @foreach ($row->lines as $line)
                            <div class="cal-lot">
                                <span class="exp-code font-weight-bold">{{ $line->import_code }}</span>
                                — {{ $expNum($line->amount) }} {{ $row->unit }}
                                @if (in_array($line->app_status, ['passed', 'issued'], true))
                                    <span class="badge badge-success">Đạt</span>
                                @elseif ($line->app_status === 'failed')
                                    <span class="badge badge-danger">Không đạt</span>
                                @endif
                                @php $serials = $line->units->pluck('serial_no')->filter(); @endphp
                                @if ($serials->isNotEmpty())
                                    <div class="md-sub small {{ $line->app_status === 'failed' ? 'text-danger' : ($line->app_status === 'pending' ? 'text-muted' : 'text-success') }}">
                                        S/N: {{ $serials->implode(', ') }}
                                    </div>
                                @endif
                                @if ($line->expired_date)
                                    <span class="md-sub">· hạn {{ $expDate($line->expired_date) }}</span>
                                @endif
                            </div>
                        @endforeach
                        @if (count($row->lines) > 1)
                            <div class="md-sub small font-weight-bold">Tổng {{ $expNum($row->amount) }} {{ $row->unit }}</div>
                        @endif
                        @if ($row->issued_amount > 0.00005)
                            <div class="md-sub small text-primary">Đã cấp {{ $expNum($row->issued_amount) }} {{ $row->unit }}</div>
                        @endif
                    </td>
                    <td>
                        @if ($row->own)
                            <a href="{{ route($expRoute . 'list', ['tab' => $row->request_type ?: 'regular']) }}" class="exp-code font-weight-bold">{{ $row->request_code }}</a>
                        @else
                            <span class="exp-code font-weight-bold">{{ $row->request_code }}</span>
                        @endif
                        <div class="md-sub small">{{ $calTypeLabel[$row->request_type] ?? '' }}</div>
                        <div class="md-sub small">Đề nghị cấp: <b>{{ $expNum($row->item_requested_amount) }} {{ $row->unit }}</b></div>
                        @if ($row->object)
                            <div class="md-sub small font-weight-bold text-dark"><i class="fas fa-crosshairs mr-1 text-primary"></i>{{ $row->object }}</div>
                        @endif
                        @if ($row->purpose)
                            <div class="md-sub small">{{ $row->purpose }}</div>
                        @endif
                    </td>
                    <td class="md-sub">
                        @if ($row->result_at && in_array($row->status, ['passed', 'issued', 'failed'], true))
                            @if ($row->passed_amount > 0.00005)
                                <div class="font-weight-bold text-success">Đạt {{ $expNum($row->passed_amount) }} {{ $row->unit }}</div>
                            @endif
                            @if ($row->failed_amount > 0.00005)
                                <div class="font-weight-bold text-danger">Không đạt {{ $expNum($row->failed_amount) }} {{ $row->unit }}</div>
                            @endif
                            @if ($row->result_note)
                                <div>{{ $row->result_note }}</div>
                            @endif
                            <div class="text-muted">{{ $row->result_by }} · {{ $expDateTime($row->result_at) }}</div>
                            @if ($row->quarantine_codes)
                                <div class="small text-danger"><i class="fas fa-biohazard mr-1"></i>Cách ly: {{ implode(', ', $row->quarantine_codes) }}</div>
                            @endif
                        @elseif ($row->status === 'canceled')
                            <span class="text-muted">{{ $row->result_note ?: 'Đã huỷ' }}</span>
                        @else
                            <span class="text-muted">Chờ QA xác nhận</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <div class="md-actions">
                            @if ($row->status === 'pending' && $row->incoming && $calibrationCanConfirm)
                                <button type="button" class="btn btn-sm btn-primary btn-cal-confirm" title="Ký xác nhận kết quả hiệu chuẩn"
                                    data-row="{{ json_encode($calData) }}">
                                    <i class="fas fa-ruler-combined"></i>
                                </button>
                            @endif
                            @if ($row->status === 'pending' && $row->own)
                                @perm('export_material_issue')
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-cal-cancel" title="Huỷ đề nghị hiệu chuẩn, nhả hàng về kho"
                                        data-code="{{ $row->code }}">
                                        <i class="fas fa-times"></i>
                                    </button>
                                @endperm
                            @endif
                            @unless ($row->status === 'pending')
                                <span class="md-sub text-muted">—</span>
                            @endunless
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted">{{ $calEmpty }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
