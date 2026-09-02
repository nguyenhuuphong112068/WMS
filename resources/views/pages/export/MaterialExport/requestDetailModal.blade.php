{{-- Một modal cho mỗi đề nghị. Biến vào: $req, $items --}}
@php
    $b = $expReqBadge($req->app_status);
    $canIssue = $req->app_status === 'approved';
@endphp
<div class="modal fade md-modal" id="reqDetailModal_{{ $req->id }}" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 90vw;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-list-ul mr-2"></i>Đề Nghị {{ $req->code }}@if ($req->name) <span class="text-muted font-weight-normal">— {{ $req->name }}</span>@endif
                    <span class="md-badge {{ $b['class'] }} ml-2">{{ $b['label'] }}</span>
                </h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">

                <div class="row mb-3">
                    <div class="col-md-3"><small class="text-muted">Tổ</small><div class="font-weight-bold">{{ $req->group_name }}</div></div>
                    <div class="col-md-3"><small class="text-muted">Người lập</small><div class="font-weight-bold">{{ $req->created_by }}</div></div>
                    <div class="col-md-3"><small class="text-muted">Cần BGĐ duyệt</small><div class="font-weight-bold">{{ $req->needs_director ? 'Có' : 'Không' }}</div></div>
                    <div class="col-md-3"><small class="text-muted">Ghi chú</small><div>{{ $req->note ?: '—' }}</div></div>
                </div>

                @if (! $canIssue)
                    <div class="md-hint mb-3">
                        <i class="fas fa-info-circle mr-1"></i>
                        Đề nghị chưa được phê duyệt xong nên kho chưa cấp phát được.
                    </div>
                @else
                    <div class="md-hint mb-3">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        <b>Cấp phát là trừ tồn ngay</b> - hàng coi như đã rời kho. Một mục thiếu hàng ở lô đề xuất thì
                        <b>cấp từ nhiều mã xuất nhập</b> (bấm <i class="fas fa-layer-group"></i> cạnh ô cấp phát để xem
                        danh mục tồn kèm khuyến nghị FEFO/FIFO). Kho không đủ hàng thì cấp được bao nhiêu hay bấy nhiêu:
                        mục đó chuyển sang <b>"Cấp phát một phần"</b> và <b>cấp thêm được</b> khi có hàng về.
                        Sau đó Tổ bấm <b>"Sử Dụng Vật Tư"</b> để ghi nhật ký số thực dùng, hoặc trả phần chưa dùng về kho.
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th style="width:170px">Vật tư</th>
                                <th style="width:120px">Quy cách</th>
                                <th style="width:90px">SL đề nghị</th>
                                <th style="width:130px">Thiết bị liên quan</th>
                                <th style="width:140px">Mục đích</th>
                                <th style="width:115px">Trạng thái</th>
                                <th>Cấp phát</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $it)
                                @php
                                    $issuedNum = $expNum($it->issued_amount);
                                    $lots = ($issuedLots ?? collect())->get($it->id, collect());

                                    /*
                                    | Kho thiếu hàng thì cấp trước phần có: dòng nằm ở "Cấp phát một phần",
                                    | vẫn cấp thêm được cho tới khi đủ số đề nghị.
                                    */
                                    $requestedAmount = (float) $it->requested_amount;
                                    $issuedAmount = (float) $it->issued_amount;
                                    $shortAmount = max($requestedAmount - $issuedAmount, 0);
                                    $unitLabel = $it->issued_unit ?: $it->requested_unit;

                                    // Dòng cũ ghi 'issued' nhưng số cấp chưa đủ cũng coi là cấp một phần
                                    $isOwing = $shortAmount > 0.00005;
                                    $isPartial = $it->status === 'partial' || ($it->status === 'issued' && $isOwing);
                                    $isDone = in_array($it->status, ['issued', 'used', 'returned']);
                                    $hasLots = $isDone || $it->status === 'partial';
                                    $canTopUp = $canIssue && $isOwing && in_array($it->status, ['pending', 'partial', 'issued']);
                                @endphp
                                <tr>
                                    <td>
                                        <div class="font-weight-bold">{{ $it->display_name ?: '—' }}</div>
                                        @unless ($it->category_id) <span class="badge badge-secondary">Ngoài danh mục</span> @endunless
                                    </td>
                                    <td class="md-sub">{{ $it->technical_specification ?: '—' }}</td>
                                    <td class="text-right">{{ rtrim(rtrim(number_format((float) $it->requested_amount, 4, '.', ''), '0'), '.') }} {{ $it->requested_unit }}</td>
                                    <td class="md-sub">{{ $it->product_name ?: '—' }}</td>
                                    <td class="md-sub">{{ $it->purpose ?: '—' }}</td>
                                    <td>
                                        @if ($hasLots)
                                            @php
                                                $itemBadge = match (true) {
                                                    $isPartial => 'badge-warning',
                                                    $it->status === 'used' => 'badge-info',
                                                    $it->status === 'returned' => 'badge-secondary',
                                                    default => 'badge-success',
                                                };
                                                $itemStatusLabel = $isPartial
                                                    ? $reqItemStatuses['partial']
                                                    : ($reqItemStatuses[$it->status] ?? $it->status);
                                            @endphp
                                            <span class="badge {{ $itemBadge }}">{{ $itemStatusLabel }}</span>
                                            @if ($isPartial)
                                                <div class="md-sub small">
                                                    Đã cấp <b>{{ $issuedNum }}</b>/{{ $expNum($requestedAmount) }} {{ $unitLabel }}
                                                    · còn thiếu <b class="text-danger">{{ $expNum($shortAmount) }}</b>
                                                </div>
                                            @endif
                                            <div class="md-sub small">{{ $it->issued_by }} · {{ $it->issued_at ? \Carbon\Carbon::parse($it->issued_at)->format('d/m/Y H:i') : '' }}</div>
                                        @elseif ($it->status === 'rejected')
                                            <span class="badge badge-danger">Từ chối</span>
                                            @if ($it->note) <div class="md-sub small text-danger">{{ $it->note }}</div> @endif
                                        @else
                                            <span class="badge badge-light border">Chờ cấp phát</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($hasLots)
                                            {{-- Một mục có thể đã cấp từ nhiều mã xuất nhập: liệt kê đủ từng lô --}}
                                            @forelse ($lots as $lot)
                                                <div class="mb-1">
                                                    Mã xuất nhập: <b>{{ $lot->code }}</b> — {{ $expNum($lot->amount) }} {{ $it->issued_unit }}
                                                    @if ($lot->status_id)
                                                        <span class="badge badge-warning ml-1" title="Cấp phát trừ tồn ngay">đã trừ kho</span>
                                                    @else
                                                        <span class="badge badge-secondary ml-1">đã trả về kho</span>
                                                    @endif
                                                    @if ($lot->expired_date)
                                                        <span class="md-sub small">· hạn {{ \Carbon\Carbon::parse($lot->expired_date)->format('d/m/Y') }}</span>
                                                    @elseif ($lot->imported_date)
                                                        <span class="md-sub small">· nhập {{ \Carbon\Carbon::parse($lot->imported_date)->format('d/m/Y') }}</span>
                                                    @endif
                                                </div>
                                            @empty
                                                <div>Mã xuất nhập: <b>{{ $it->issued_import_code ?: ($it->import_code ?: '—') }}</b> — {{ $issuedNum }} {{ $it->issued_unit }}
                                                    <span class="badge badge-warning ml-1" title="Cấp phát trừ tồn ngay">đã trừ kho</span>
                                                </div>
                                            @endforelse

                                            @if ($lots->count() > 1)
                                                <div class="md-sub small" style="color: var(--primary-dark);">
                                                    <i class="fas fa-layer-group mr-1"></i>
                                                    Tổng đã cấp <b>{{ $issuedNum }} {{ $unitLabel }}</b> từ {{ $lots->count() }} mã xuất nhập.
                                                </div>
                                            @endif

                                            @if ($isPartial)
                                                <div class="md-sub small text-danger">
                                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                                    Mới cấp <b>{{ $issuedNum }}/{{ $expNum($requestedAmount) }} {{ $unitLabel }}</b>,
                                                    còn thiếu <b>{{ $expNum($shortAmount) }}</b> — có hàng về thì cấp thêm ở dưới.
                                                </div>
                                            @endif

                                            @if (in_array($it->status, ['issued', 'partial']))
                                                @perm('export_material_request')
                                                    <button type="button" class="btn btn-xs btn-primary mt-1 btn-me-use"
                                                        title="{{ $isPartial ? 'Chốt luôn phần đã cấp - chốt xong sẽ không cấp thêm được nữa' : 'Ghi nhật ký số thực dùng, hoặc trả phần chưa dùng về kho' }}"
                                                        data-item-id="{{ $it->id }}"
                                                        data-material="{{ $it->display_name }}"
                                                        data-code="{{ $lots->count() ? $lots->pluck('code')->implode(', ') : ($it->issued_import_code ?: $it->import_code) }}"
                                                        data-amount="{{ $issuedNum }}"
                                                        data-unit="{{ $unitLabel }}"
                                                        data-product="{{ $it->product_name }}"
                                                        data-request="{{ $req->code }}">
                                                        <i class="fas fa-hand-holding-medical mr-1"></i>Sử Dụng Vật Tư
                                                    </button>
                                                @endperm
                                            @elseif ($it->status === 'used')
                                                <span class="badge badge-info mt-1"><i class="fas fa-check mr-1"></i>Đã ghi nhật ký sử dụng</span>
                                            @elseif ($it->status === 'returned')
                                                <span class="badge badge-secondary mt-1"><i class="fas fa-rotate-left mr-1"></i>Đã trả về kho</span>
                                            @endif
                                        @endif

                                        @if ($canTopUp && user_can('export_material_issue'))
                                            @if ($hasLots)
                                                <hr class="my-2">
                                                <div class="font-weight-bold small mb-1" style="color: var(--primary-dark);">
                                                    <i class="fas fa-plus-circle mr-1"></i>Cấp phát thêm
                                                    <span class="md-sub">(còn thiếu {{ $expNum($shortAmount) }} {{ $unitLabel }})</span>
                                                </div>
                                            @endif
                                            @php
                                                /*
                                                | Kế hoạch chia lô do App\Support\MaterialPicking dựng sẵn ở controller:
                                                | rót đủ số đề nghị theo đúng thứ tự nên xuất (hạn gần nhất trước, vật tư
                                                | không hạn dùng theo ngày nhập). Lô đầu không đủ thì kế hoạch tự có thêm
                                                | dòng của lô kế tiếp - JS dựng các dòng cấp phát từ đúng mảng này.
                                                */
                                                $plan = ($issuePlans ?? collect())->get($it->id, ['lines' => [], 'shortage' => $shortAmount]);
                                                $planLines = $plan['lines'] ?? [];
                                                $shortage = (float) ($plan['shortage'] ?? 0);
                                            @endphp
                                            <form action="{{ route($expRoute . 'issueStore') }}" method="POST" class="me-issue-form"
                                                data-item-id="{{ $it->id }}"
                                                data-category-id="{{ $it->category_id }}"
                                                data-material="{{ $it->display_name }}"
                                                data-needed="{{ $shortAmount }}"
                                                data-unit="{{ $unitLabel }}"
                                                data-need-label="{{ $isPartial ? 'Cần cấp thêm' : 'Đề nghị' }}"
                                                data-plan="{{ json_encode($planLines) }}">
                                                @csrf
                                                <input type="hidden" name="item_id" value="{{ $it->id }}">

                                                <div class="me-issue-lines"></div>

                                                <div class="me-issue-foot">
                                                    <button type="button" class="btn btn-xs btn-outline-primary me-issue-add">
                                                        <i class="fas fa-plus mr-1"></i>Thêm mã xuất nhập
                                                    </button>
                                                    <input type="text" name="issued_unit" class="form-control form-control-sm me-issue-unit"
                                                        value="{{ $unitLabel }}" title="Đơn vị tính">
                                                    <button type="submit" class="btn btn-sm btn-success">
                                                        <i class="fas fa-check mr-1"></i>Cấp phát
                                                    </button>
                                                    <span class="me-issue-sum"></span>
                                                </div>
                                            </form>

                                            @if (! $it->category_id)
                                                <div class="md-sub small text-danger mt-1">
                                                    <i class="fas fa-exclamation-circle mr-1"></i>
                                                    Vật tư ngoài danh mục nên kho không có mã xuất nhập nào để chọn.
                                                </div>
                                            @elseif (! count($planLines))
                                                <div class="md-sub small text-danger mt-1">
                                                    <i class="fas fa-exclamation-circle mr-1"></i>
                                                    Không còn lô nào của vật tư này còn hạn và còn hứa được.
                                                </div>
                                            @elseif ($shortage > 0.00005)
                                                <div class="md-sub small text-danger mt-1">
                                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                                    Gom cả {{ count($planLines) }} mã xuất nhập còn hứa được vẫn thiếu
                                                    <b>{{ $expNum($shortage) }} {{ $unitLabel }}</b> so với phần cần cấp — cấp một phần rồi chờ hàng về,
                                                    mục này giữ trạng thái <b>Cấp phát một phần</b> để cấp tiếp.
                                                </div>
                                            @elseif (count($planLines) > 1)
                                                <div class="md-sub small mt-1" style="color: var(--primary-dark);">
                                                    <i class="fas fa-star mr-1"></i>
                                                    Lô đề xuất không đủ nên hệ thống chia sang {{ count($planLines) }} mã xuất nhập theo thứ tự nên xuất.
                                                </div>
                                            @endif
                                        @elseif (! $hasLots)
                                            <span class="text-muted">—</span>
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
