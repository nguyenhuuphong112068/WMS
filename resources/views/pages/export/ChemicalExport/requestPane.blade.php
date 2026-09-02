{{--
| SỬ DỤNG - TAB ĐỀ NGHỊ CHUYỂN HOÁ CHẤT
|
| Hai bảng:
| - Phòng mình gửi đi : mình đang thiếu, đề nghị phòng khác chuyển sang.
| - Gửi đến phòng mình: phòng khác cần hàng của mình -> trả lời rồi lập phiếu chuyển.
|
| Đề nghị KHÔNG động vào tồn kho, chỉ là thông tin trước khi chuyển.
--}}

@php
    $expReqStatus = [
        'pending' => ['label' => 'Chờ trả lời', 'class' => 'pending'],
        'accepted' => ['label' => 'Đã đồng ý', 'class' => 'accepted'],
        'rejected' => ['label' => 'Đã từ chối', 'class' => 'rejected'],
    ];
    $expReqBadge = fn($status) => $expReqStatus[$status] ?? ['label' => $status, 'class' => 'pending'];
@endphp

<div class="exp-pane {{ $activeTab === 'request' ? 'is-active' : '' }}" id="expPaneRequest">

    <div class="md-toolbar">
        @perm('export_chemical_request')
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#requestModal">
                <i class="fas fa-paper-plane mr-1"></i> Đề nghị chuyển hoá chất
            </button>
        @endperm
        <p class="hint">
            <i class="fas fa-info-circle mr-1"></i>
            Đề nghị chỉ là <b>nguồn thông tin trước khi chuyển</b>, không trừ cộng tồn kho. Hàng chỉ đi khi phòng
            giữ hàng lập phiếu <b>Chuyển kho</b> và phòng nhận bấm <b>Nhận</b>.
        </p>
    </div>

    {{-- ---------- Đề nghị gửi đến phòng mình ---------- --}}
    <h6 class="exp-req-title">
        <i class="fas fa-inbox mr-1"></i> Phòng ban khác đề nghị phòng mình chuyển
        <span class="md-sub">({{ $requestsReceived->where('app_status', 'pending')->count() }} chờ trả lời)</span>
    </h6>

    <div class="table-responsive mb-4">
        <table class="table table-bordered table-hover w-100 md-table exp-req-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 60px">STT</th>
                    <th style="width: 160px">Phòng Đề Nghị</th>
                    <th>Hoá Chất</th>
                    <th class="text-right" style="width: 110px">Số Lượng</th>
                    <th class="text-center" style="width: 100px">Ngày Cần</th>
                    <th>Lý Do</th>
                    <th class="text-center" style="width: 120px">Trạng Thái</th>
                    <th class="text-center" style="width: 150px">Thao Tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($requestsReceived as $req)
                    @php $expReqUnit = $req->unit_short_name ?: $req->unit_name; @endphp
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td>
                            <div class="font-weight-bold">{{ $req->partner_name ?: '—' }}</div>
                            <div class="md-sub">{{ $req->requested_by ?: '—' }}</div>
                        </td>
                        <td>
                            <div class="font-weight-bold">{{ $req->chem_name ?: '—' }}</div>
                            <div class="md-sub"><span class="md-tag">{{ $req->category_code ?: '—' }}</span></div>
                        </td>
                        <td class="text-right">
                            <span class="exp-amount">{{ $expNum($req->amount) }}</span>
                            <span class="md-sub">{{ $expReqUnit }}</span>
                        </td>
                        <td class="text-center md-sub">{{ $expDate($req->needed_date) }}</td>
                        <td class="md-sub">
                            @if ($req->reason)
                                <span class="md-note" title="{{ $req->reason }}">{{ $req->reason }}</span>
                            @else
                                <span class="md-empty">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="exp-req-badge {{ $expReqBadge($req->app_status)['class'] }}">
                                {{ $expReqBadge($req->app_status)['label'] }}
                            </span>
                            @if ($req->response_note)
                                <div class="md-sub mt-1" title="{{ $req->response_note }}">
                                    <span class="md-note">{{ $req->response_note }}</span>
                                </div>
                            @endif
                            @if ($req->export_code)
                                <div class="md-sub mt-1">Đã chuyển: {{ $req->export_code }}</div>
                            @endif
                        </td>
                        <td class="text-center">
                            @if ($req->app_status === 'pending')
                                @perm('export_chemical_approve')
                                    <button type="button" class="btn btn-sm btn-primary btn-exp-respond"
                                        data-id="{{ $req->id }}" data-answer="accepted"
                                        data-title="{{ $req->chem_name }} - {{ $expNum($req->amount) }} {{ $expReqUnit }} cho {{ $req->partner_name }}">
                                        <i class="fas fa-check"></i>
                                    </button>
                                @endperm
                                @perm('export_chemical_approve')
                                    <button type="button" class="btn btn-sm btn-secondary btn-exp-respond"
                                        data-id="{{ $req->id }}" data-answer="rejected"
                                        data-title="{{ $req->chem_name }} - {{ $expNum($req->amount) }} {{ $expReqUnit }} cho {{ $req->partner_name }}">
                                        <i class="fas fa-xmark"></i>
                                    </button>
                                @endperm
                            @elseif ($req->app_status === 'accepted' && ! $req->export_id)
                                @perm('export_chemical_transfer')
                                    <button type="button" class="btn btn-sm btn-success btn-exp-make-transfer"
                                        title="Lập phiếu Chuyển kho cho đề nghị này"
                                        data-request="{{ json_encode([
                                            'request_id' => $req->id,
                                            'to_department_id' => $req->department_id,
                                            'amount' => (float) $req->amount,
                                            'purpose' => 'Chuyển theo đề nghị của ' . $req->partner_name,
                                        ]) }}">
                                        <i class="fas fa-truck-arrow-right mr-1"></i> Lập phiếu
                                    </button>
                                @endperm
                            @else
                                <span class="md-empty">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center md-sub py-4">Chưa có phòng ban nào đề nghị phòng mình
                            chuyển hoá chất.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ---------- Đề nghị phòng mình gửi đi ---------- --}}
    <h6 class="exp-req-title">
        <i class="fas fa-paper-plane mr-1"></i> Phòng mình đề nghị phòng ban khác chuyển
        <span class="md-sub">({{ $requestsSent->where('app_status', 'pending')->count() }} chờ trả lời)</span>
    </h6>

    <div class="table-responsive">
        <table class="table table-bordered table-hover w-100 md-table exp-req-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 60px">STT</th>
                    <th style="width: 160px">Phòng Được Đề Nghị</th>
                    <th>Hoá Chất</th>
                    <th class="text-right" style="width: 110px">Số Lượng</th>
                    <th class="text-center" style="width: 100px">Ngày Cần</th>
                    <th>Lý Do</th>
                    <th class="text-center" style="width: 120px">Trạng Thái</th>
                    <th style="width: 170px">Trả Lời</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($requestsSent as $req)
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td class="font-weight-bold">{{ $req->partner_name ?: '—' }}</td>
                        <td>
                            <div class="font-weight-bold">{{ $req->chem_name ?: '—' }}</div>
                            <div class="md-sub"><span class="md-tag">{{ $req->category_code ?: '—' }}</span></div>
                        </td>
                        <td class="text-right">
                            <span class="exp-amount">{{ $expNum($req->amount) }}</span>
                            <span class="md-sub">{{ $req->unit_short_name ?: $req->unit_name }}</span>
                        </td>
                        <td class="text-center md-sub">{{ $expDate($req->needed_date) }}</td>
                        <td class="md-sub">
                            @if ($req->reason)
                                <span class="md-note" title="{{ $req->reason }}">{{ $req->reason }}</span>
                            @else
                                <span class="md-empty">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="exp-req-badge {{ $expReqBadge($req->app_status)['class'] }}">
                                {{ $expReqBadge($req->app_status)['label'] }}
                            </span>
                            @if ($req->export_code)
                                <div class="md-sub mt-1">Đã chuyển: {{ $req->export_code }}</div>
                            @endif
                        </td>
                        <td class="md-sub">
                            @if ($req->response_note)
                                <span class="md-note" title="{{ $req->response_note }}">{{ $req->response_note }}</span>
                                <div>{{ $req->responded_by }} ·
                                    {{ $req->responded_at ? \Carbon\Carbon::parse($req->responded_at)->format('d/m/Y H:i') : '' }}
                                </div>
                            @else
                                <span class="md-empty">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center md-sub py-4">Phòng mình chưa gửi đề nghị nào.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
