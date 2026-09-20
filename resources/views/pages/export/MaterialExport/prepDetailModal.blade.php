{{--
| CHI TIẾT SOẠN HÀNG CỦA MỘT VẬT TƯ - mở từ tab "Soạn vật tư cấp phát".
| Biến vào: $g (một nhóm của $prepGroups), $prepIndex (thứ tự nhóm, dùng làm id modal).
--}}
@php
    // Tiền tố tham số lọc của từng tab đề nghị - để bấm mã phiếu là nhảy đúng tab, đã lọc sẵn "Chưa cấp phát đủ"
    $prepTabPrefix = ['periodic' => 'reqp_', 'risk_assessment' => 'reqk_', 'regular' => 'reqt_'];

    $prepShortage = (float) $g['shortage'];
    $prepIsShort = $prepShortage > 0.00005;
    $prepToday = now()->startOfDay();

    $prepTypeLabel = ['periodic' => 'Định kỳ', 'risk_assessment' => 'ĐG rủi ro', 'regular' => 'Thường quy'];
@endphp

<div class="modal fade md-modal" id="prepDetailModal_{{ $prepIndex }}" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 1000px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-dolly mr-2"></i>Soạn hàng: {{ $g['name'] ?: '—' }}
                    @if ($g['category_code'])
                        <span class="md-tag ml-2">{{ $g['category_code'] }}</span>
                    @endif
                </h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">

                <div class="exp-summary mb-3">
                    <div>
                        <small class="text-muted">Tổng cần soạn</small>
                        <div class="font-weight-bold">{{ $expNum($g['needed']) }} {{ $g['unit'] }}</div>
                    </div>
                    <div>
                        <small class="text-muted">Tồn khả dụng</small>
                        <div class="font-weight-bold">{{ $expNum($g['available']) }} {{ $g['unit'] }}</div>
                    </div>
                    <div>
                        <small class="text-muted">Tồn sổ sách</small>
                        <div class="font-weight-bold">{{ $expNum($g['remaining']) }} {{ $g['unit'] }}</div>
                    </div>
                    <div>
                        <small class="text-muted">Tình trạng</small>
                        <div>
                            @if ($prepIsShort)
                                <span class="badge badge-danger">Thiếu {{ $expNum($prepShortage) }} {{ $g['unit'] }}</span>
                            @else
                                <span class="badge badge-success">Đủ hàng</span>
                            @endif
                        </div>
                    </div>
                    <div>
                        <small class="text-muted">Đề nghị đang chờ</small>
                        <div class="font-weight-bold">{{ collect($g['demands'])->pluck('request_code')->unique()->count() }} phiếu</div>
                    </div>
                    <div>
                        <small class="text-muted">Quy cách</small>
                        <div class="font-weight-bold">{{ $g['specification'] ?: '—' }}</div>
                    </div>
                </div>

                <div class="font-weight-bold mb-2" style="color: var(--primary-dark);">
                    <i class="fas fa-layer-group mr-1"></i>Lô nên lấy
                </div>
                <div class="table-responsive mb-3">
                    <table class="table table-bordered table-sm" data-no-datatable>
                        <thead>
                            <tr>
                                <th class="text-center" style="width:45px">TT</th>
                                <th style="width:150px">Mã Xuất Nhập</th>
                                <th style="width:150px">Vị Trí</th>
                                <th class="text-center" style="width:110px">Hạn Dùng</th>
                                <th class="text-right" style="width:110px">Số Lượng Lấy</th>
                                <th class="text-right" style="width:110px">Tồn Của Lô</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($g['plan'] as $line)
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td><span class="exp-code font-weight-bold">{{ $line['import_code'] }}</span></td>
                                    <td>
                                        @if ($line['location_code'])
                                            <span class="prep-loc"><i class="fas fa-map-marker-alt"></i>{{ $line['location_code'] }}</span>
                                        @else
                                            <span class="text-muted">Chưa xếp vị trí</span>
                                        @endif
                                    </td>
                                    <td class="text-center md-sub">
                                        @if ($line['expired_date'])
                                            {{ \Carbon\Carbon::parse($line['expired_date'])->format('d/m/Y') }}
                                            @if ($line['expiry_level'] === 'critical')
                                                <div><span class="badge badge-danger">Cận hạn</span></div>
                                            @elseif ($line['expiry_level'] === 'warning')
                                                <div><span class="badge badge-warning">Sắp hết hạn</span></div>
                                            @endif
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="text-right font-weight-bold">
                                        {{ $expNum($line['suggested_amount']) }} <span class="md-sub">{{ $line['unit'] ?: $g['unit'] }}</span>
                                    </td>
                                    <td class="text-right md-sub">{{ $expNum($line['available']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-danger">Không còn lô nào của vật tư này còn hạn và còn hứa được.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="font-weight-bold mb-2" style="color: var(--primary-dark);">
                    <i class="fas fa-file-signature mr-1"></i>Đề nghị đang chờ vật tư này
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm" data-no-datatable>
                        <thead>
                            <tr>
                                <th style="width:170px">Mã Đề Nghị</th>
                                <th class="text-center" style="width:95px">Loại</th>
                                <th class="text-center" style="width:150px">Ngày Lập / Duyệt</th>
                                <th class="text-center" style="width:100px">Ngày Mong Muốn</th>
                                <th style="width:120px">Người Lập</th>
                                <th>Mục Đích / Ghi Chú</th>
                                <th class="text-right" style="width:110px">Còn Phải Cấp</th>
                                <th class="text-center" style="width:150px">Trạng Thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($g['demands'] as $d)
                                @php
                                    $dDue = $d['needed_date'] ? \Carbon\Carbon::parse($d['needed_date'])->startOfDay() : null;
                                    $dPartial = (float) $d['issued'] > 0.00005;
                                @endphp
                                <tr>
                                    <td>
                                        <a href="{{ route($expRoute . 'list', ['tab' => $d['request_type'], ($prepTabPrefix[$d['request_type']] ?? 'reqt_') . 'unissued' => 1]) }}"
                                            class="exp-code font-weight-bold" title="Mở tab đề nghị để cấp phát">{{ $d['request_code'] }}</a>
                                        @if ($d['request_name'])
                                            <div class="md-sub small font-weight-bold" style="color: var(--primary-dark);">{{ $d['request_name'] }}</div>
                                        @endif
                                        @if ($d['item_total'] > 1)
                                            <div class="md-sub small">Phiếu gồm {{ $d['item_total'] }} mục</div>
                                        @endif
                                    </td>
                                    <td class="text-center md-sub">
                                        {{ $prepTypeLabel[$d['request_type']] ?? $d['request_type'] }}
                                    </td>
                                    <td class="text-center md-sub">
                                        <div>Lập: {{ $d['created_at'] ? \Carbon\Carbon::parse($d['created_at'])->format('d/m/Y') : '—' }}</div>
                                        <div>Duyệt:
                                            @if ($d['approved_at'])
                                                {{ \Carbon\Carbon::parse($d['approved_at'])->format('d/m/Y H:i') }}
                                            @else
                                                <span title="Phiếu không khai bước ký nào">không cần ký</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-center md-sub">
                                        @if ($dDue)
                                            <span class="{{ $dDue->lt($prepToday) ? 'prep-due-late' : '' }}">{{ $dDue->format('d/m/Y') }}</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="md-sub">{{ $d['created_by'] ?: '—' }}</td>
                                    <td class="md-sub">
                                        {{ $d['purpose'] ?: '—' }}
                                        @if ($d['item_note'])
                                            <div class="small text-muted">{{ $d['item_note'] }}</div>
                                        @endif
                                        @if ($d['request_note'])
                                            <div class="small text-muted"><i class="fas fa-file-alt mr-1"></i>{{ $d['request_note'] }}</div>
                                        @endif
                                    </td>
                                    <td class="text-right font-weight-bold">
                                        {{ $expNum($d['left']) }} <span class="md-sub">{{ $d['unit'] ?: $g['unit'] }}</span>
                                        @if ($dPartial)
                                            <div class="md-sub small">/ {{ $expNum($d['requested']) }} đề nghị</div>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($dPartial)
                                            <span class="badge badge-warning">Đã cấp {{ $expNum($d['issued']) }}/{{ $expNum($d['requested']) }}</span>
                                            <div class="md-sub small">{{ $d['issued_by'] }}
                                                {{ $d['issued_at'] ? '· ' . \Carbon\Carbon::parse($d['issued_at'])->format('d/m/Y H:i') : '' }}
                                            </div>
                                        @else
                                            <span class="badge badge-light border">Chờ cấp phát</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
