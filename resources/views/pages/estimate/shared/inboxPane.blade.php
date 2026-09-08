{{--
| DỰ TRÙ - KÝ DUYỆT (MỌI PHÒNG BAN) - hộp ký duyệt liên phòng ban.
| Gom các phiếu dự trù đang chờ CHÍNH người đang đăng nhập ký, mọi phòng trong cùng công ty.
| Ký / Từ chối đi qua signStep / reject với scope=inbox.
| Biến vào: $estRoute, $inboxRequests, $inboxItems, $inboxSigns, $signPermission
--}}
<div class="table-responsive">
    <table id="estInboxTable" class="table table-bordered table-hover w-100">
        <thead>
            <tr>
                <th class="text-center" style="width:45px">STT</th>
                <th style="width:150px">Phòng Ban</th>
                <th style="width:150px">Mã Phiếu</th>
                <th class="text-center" style="width:90px">Kỳ Dự Trù</th>
                <th class="text-center" style="width:70px">Số Mục</th>
                <th style="width:320px">Trình Ký</th>
                <th style="width:120px">Người Lập</th>
                <th class="text-center" style="width:160px">Thao Tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($inboxRequests as $req)
                @php
                    $items = $inboxItems->get($req->id, collect());
                    $signs = $inboxSigns->get($req->id, collect());
                    $isLastStep = (int) $req->my_step_no >= (int) $req->sign_step_count;
                @endphp
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>
                        <span class="est-code">{{ $req->department_short ?: '—' }}</span>
                        <div class="md-sub small text-muted">{{ $req->department_name }}</div>
                    </td>
                    <td>
                        <span class="est-code">{{ $req->code }}</span>
                        @if ($req->note)
                            <div class="md-sub"><span class="md-note" title="{{ $req->note }}">{{ $req->note }}</span></div>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="est-period">{{ str_pad($req->month, 2, '0', STR_PAD_LEFT) }}/{{ $req->year }}</span>
                    </td>
                    <td class="text-center"><span class="md-tag">{{ $items->count() }}</span></td>
                    <td>
                        @include('pages.estimate.shared.signFlow', ['row' => $req, 'signs' => $signs])
                    </td>
                    <td class="md-sub">
                        {{ $req->submitted_by ?: $req->created_by ?: '—' }}
                        <br><small>{{ $req->submitted_at ? \Carbon\Carbon::parse($req->submitted_at)->format('d/m/Y') : '' }}</small>
                    </td>
                    <td class="text-center">
                        <div class="md-actions">
                            <button type="button" class="btn btn-sm btn-primary" data-toggle="modal"
                                data-target="#estInboxDetail_{{ $req->id }}" title="Xem chi tiết">
                                <i class="fas fa-list-ul"></i>
                            </button>
                            @if (user_can($signPermission))
                                <form class="form-md-confirm d-inline" data-require-password="1"
                                    action="{{ route($estRoute . 'signStep') }}" method="POST"
                                    data-title="Ký duyệt bước {{ $req->my_step_no }}/{{ $req->sign_step_count }}?"
                                    data-text="Phiếu {{ $req->code }} ({{ $req->department_short }}) {{ $isLastStep ? 'sẽ được phê duyệt và ghi nhận tiếp nhận.' : 'sẽ chuyển tới người ký bước ' . ($req->my_step_no + 1) . '.' }}">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $req->id }}">
                                    <input type="hidden" name="scope" value="inbox">
                                    <button type="submit" class="btn btn-sm btn-success" title="Ký duyệt">
                                        <i class="fas fa-{{ $isLastStep ? 'stamp' : 'signature' }}"></i>
                                    </button>
                                </form>
                                <button type="button" class="btn btn-sm btn-outline-danger btn-est-reject"
                                    data-id="{{ $req->id }}" data-code="{{ $req->code }}" data-scope="inbox" title="Từ chối">
                                    <i class="fas fa-times"></i>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted">Không có phiếu dự trù nào đang chờ bạn ký duyệt.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
