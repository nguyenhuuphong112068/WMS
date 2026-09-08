{{--
| KÝ DUYỆT (MỌI PHÒNG BAN) - hộp ký duyệt liên phòng ban.
| Gom các đề nghị cấp phát vật tư của mọi phòng trong công ty đang chờ CHÍNH người
| đang đăng nhập ký. Ký / Từ chối đi qua requestSign / requestReject với scope=inbox.
--}}

<div class="table-responsive">
    <table id="meInboxTable" class="table table-bordered table-hover w-100 md-table" data-server-paged>
        <thead>
            <tr>
                <th class="text-center" style="width:45px">STT</th>
                <th style="width:150px">Phòng Ban</th>
                <th style="width:150px">Mã Đề Nghị</th>
                <th class="text-center" style="width:70px">Số Mục</th>
                <th style="width:300px">Trình Ký</th>
                <th style="width:120px">Người Lập</th>
                <th class="text-center" style="width:170px">Thao Tác</th>
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
                        <span class="font-weight-bold" style="color: var(--primary-dark);">{{ $req->department_short ?: '—' }}</span>
                        <div class="md-sub small text-muted">{{ $req->department_name }}</div>
                    </td>
                    <td>
                        <span class="exp-code font-weight-bold">{{ $req->code }}</span>
                        @if ($req->name) <div class="md-sub small font-weight-bold" style="color: var(--primary-dark);">{{ $req->name }}</div> @endif
                        @if ($req->note) <div class="md-sub small text-muted">{{ $req->note }}</div> @endif
                    </td>
                    <td class="text-center"><span class="md-tag">{{ $items->count() }}</span></td>
                    <td>
                        <div class="me-flow">
                            @foreach ($signs as $s)
                                @php
                                    $cls = 'step';
                                    if ($s->status === 'signed') $cls .= ' done';
                                    elseif ($s->status === 'rejected') $cls .= ' rejected';
                                    elseif ((int) $req->current_step === (int) $s->step_no) $cls .= ' current';
                                    else $cls .= ' skip';
                                    $who = $s->signer_full_name ?: ($s->user_name ?: ($s->role_names ?: '—'));
                                @endphp
                                <span class="{{ $cls }}" title="Bước {{ $s->step_no }}: {{ $who }}{{ $s->signed_at ? ' — đã ký ' . \Carbon\Carbon::parse($s->signed_at)->format('d/m/Y H:i') : '' }}">
                                    @if ($s->status === 'signed') <i class="fas fa-check"></i>
                                    @elseif ($s->status === 'rejected') <i class="fas fa-times"></i>
                                    @else {{ $s->step_no }} @endif
                                    {{ $who }}
                                </span>
                            @endforeach
                        </div>
                    </td>
                    <td class="md-sub">{{ $req->submitted_by ?: $req->created_by ?: '—' }}
                        <br><small>{{ $req->submitted_at ? \Carbon\Carbon::parse($req->submitted_at)->format('d/m/Y') : '' }}</small>
                    </td>
                    <td class="text-center">
                        <div class="md-actions">
                            <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#inboxDetailModal_{{ $req->id }}" title="Xem chi tiết">
                                <i class="fas fa-list-ul"></i>
                            </button>
                            <form class="form-md-confirm d-inline" action="{{ route($expRoute . 'requestSign') }}" method="POST"
                                data-require-password="1"
                                data-title="Ký duyệt bước {{ $req->my_step_no }}/{{ $req->sign_step_count }}?"
                                data-text="Đề nghị {{ $req->code }} ({{ $req->department_short }}) {{ $isLastStep ? 'sẽ được duyệt và kho có thể cấp phát.' : 'sẽ chuyển tới người ký bước ' . ($req->my_step_no + 1) . '.' }}">
                                @csrf
                                <input type="hidden" name="request_list_id" value="{{ $req->id }}">
                                <input type="hidden" name="scope" value="inbox">
                                <button type="submit" class="btn btn-sm btn-success" title="Ký duyệt">
                                    <i class="fas fa-{{ $isLastStep ? 'stamp' : 'signature' }}"></i>
                                </button>
                            </form>
                            <button type="button" class="btn btn-sm btn-outline-danger btn-req-reject"
                                data-id="{{ $req->id }}" data-code="{{ $req->code }}" data-scope="inbox" title="Từ chối">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted">Không có đề nghị nào đang chờ bạn ký duyệt.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
