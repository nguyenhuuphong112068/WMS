<style>
    .me-flow { display: flex; align-items: center; flex-wrap: wrap; gap: 4px; }
    .me-flow .step {
        display: inline-flex; align-items: center; gap: 5px; border: 1px solid var(--primary-soft);
        border-radius: 999px; padding: 2px 10px; font-size: .74rem; font-weight: 600; background: #fff;
    }
    .me-flow .step.done { background: #DCFCE7; color: #166534; border-color: #86EFAC; }
    .me-flow .step.current { background: var(--primary-soft); color: var(--primary-dark); border-color: var(--primary-lighter); }
    .me-flow .step.rejected { background: #FEE2E2; color: #991B1B; border-color: #FCA5A5; }
    .me-flow .step.skip { opacity: .45; }
</style>

<div class="md-toolbar">
    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#reqCreateModal">
        <i class="fas fa-plus mr-1"></i> Tạo đề nghị cấp phát vật tư
    </button>
    <p class="hint">
        <i class="fas fa-info-circle mr-1"></i>
        Tổ lập đề nghị → <b>Trưởng/Phó Phòng</b> duyệt (bắt buộc) → <b>Ban Giám Đốc</b> duyệt (nếu phiếu đánh dấu cần)
        → kho <b>cấp phát</b> từng dòng → Tổ lập phiếu sử dụng ở tab "Sổ".
    </p>
</div>

<div class="table-responsive">
    <table id="meReqTable" class="table table-bordered table-hover w-100 md-table">
        <thead>
            <tr>
                <th class="text-center" style="width:45px">STT</th>
                <th style="width:150px">Mã Đề Nghị</th>
                <th style="width:120px">Tổ</th>
                <th class="text-center" style="width:70px">Số Mục</th>
                <th style="width:130px">Trạng Thái</th>
                <th style="width:320px">Trình Ký</th>
                <th class="text-center" style="width:110px">Cấp Phát</th>
                <th style="width:120px">Người Lập</th>
                <th class="text-center" style="width:180px">Thao Tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($requestLists as $req)
                @php
                    $b = $expReqBadge($req->app_status);
                    $items = $requestItems->get($req->id, collect());
                    $editable = in_array($req->app_status, ['draft', 'rejected']);
                @endphp
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td><span class="exp-code font-weight-bold">{{ $req->code }}</span>
                        @if ($req->note) <div class="md-sub small text-muted">{{ $req->note }}</div> @endif
                    </td>
                    <td class="md-sub">{{ $req->group_name ?: '—' }}</td>
                    <td class="text-center"><span class="md-tag">{{ $items->count() }}</span></td>
                    <td>
                        <span class="md-badge {{ $b['class'] }}">{{ $b['label'] }}</span>
                        @if ($req->app_status === 'rejected' && $req->reject_reason)
                            <div class="md-sub small text-danger">{{ $req->reject_reason }}</div>
                        @endif
                    </td>
                    <td>
                        <div class="me-flow">
                            @foreach ($reqSignSteps as $k => $step)
                                @php
                                    $signedAt = $req->{$step['signed_at']} ?? null;
                                    $cls = 'step';
                                    if ($signedAt) $cls .= ' done';
                                    elseif ($req->app_status === 'rejected' && $req->reject_step === $k) $cls .= ' rejected';
                                    elseif ($req->app_status === $step['from']) $cls .= ' current';
                                    if ($k === 'director' && ! $req->needs_director && ! $signedAt) $cls .= ' skip';
                                @endphp
                                <span class="{{ $cls }}" title="{{ $step['label'] }}">
                                    @if ($signedAt) <i class="fas fa-check"></i>
                                    @elseif (str_contains($cls, 'rejected')) <i class="fas fa-times"></i>
                                    @else {{ $step['no'] }} @endif
                                    {{ $step['label'] }}
                                    @if ($k === 'director' && ! $req->needs_director) <em>(không cần)</em> @endif
                                </span>
                            @endforeach
                        </div>
                    </td>
                    <td class="text-center md-sub">
                        @if ($req->issue_status)
                            <span class="badge badge-{{ $req->issue_status === 'completed' ? 'success' : 'info' }}">
                                {{ $reqIssueStatuses[$req->issue_status] ?? $req->issue_status }}
                            </span>
                        @else — @endif
                    </td>
                    <td class="md-sub">{{ $req->created_by ?: '—' }}
                        <br><small>{{ $req->created_at ? \Carbon\Carbon::parse($req->created_at)->format('d/m/Y') : '' }}</small>
                    </td>
                    <td class="text-center">
                        <div class="md-actions">
                            <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#reqDetailModal_{{ $req->id }}" title="Xem / Cấp phát">
                                <i class="fas fa-list-ul"></i>
                            </button>

                            @if ($editable)
                                <button type="button" class="btn btn-sm btn-warning" data-toggle="modal" data-target="#reqEditModal_{{ $req->id }}" title="Sửa">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form class="form-md-confirm d-inline" action="{{ route($expRoute . 'requestSubmit') }}" method="POST"
                                    data-title="Trình ký đề nghị {{ $req->code }}?" data-text="Đề nghị sẽ chuyển sang chờ Trưởng/Phó Phòng duyệt.">
                                    @csrf <input type="hidden" name="request_list_id" value="{{ $req->id }}">
                                    <button type="submit" class="btn btn-sm btn-success" title="Trình ký"><i class="fas fa-paper-plane"></i></button>
                                </form>
                                <form class="form-md-confirm d-inline" action="{{ route($expRoute . 'requestDestroy') }}" method="POST"
                                    data-title="Huỷ đề nghị {{ $req->code }}?" data-text="Đề nghị sẽ bị huỷ." data-danger="1">
                                    @csrf <input type="hidden" name="request_list_id" value="{{ $req->id }}">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Huỷ"><i class="fas fa-trash"></i></button>
                                </form>
                            @endif

                            @if ($req->app_status === 'pending_manager' && $canSignManager)
                                <form class="form-md-confirm d-inline" action="{{ route($expRoute . 'requestSignManager') }}" method="POST"
                                    data-title="Duyệt bước Trưởng/Phó Phòng?" data-text="Đề nghị {{ $req->code }} {{ $req->needs_director ? 'sẽ chuyển lên Ban Giám Đốc.' : 'sẽ được duyệt và kho có thể cấp phát.' }}">
                                    @csrf <input type="hidden" name="request_list_id" value="{{ $req->id }}">
                                    <button type="submit" class="btn btn-sm btn-success" title="Duyệt"><i class="fas fa-signature"></i></button>
                                </form>
                            @endif
                            @if ($req->app_status === 'pending_director' && $canSignDirector)
                                <form class="form-md-confirm d-inline" action="{{ route($expRoute . 'requestSignDirector') }}" method="POST"
                                    data-title="Ban Giám Đốc phê duyệt {{ $req->code }}?" data-text="Duyệt xong kho có thể cấp phát.">
                                    @csrf <input type="hidden" name="request_list_id" value="{{ $req->id }}">
                                    <button type="submit" class="btn btn-sm btn-success" title="Phê duyệt"><i class="fas fa-stamp"></i></button>
                                </form>
                            @endif
                            @if (($req->app_status === 'pending_manager' && $canSignManager) || ($req->app_status === 'pending_director' && $canSignDirector))
                                <button type="button" class="btn btn-sm btn-outline-danger btn-req-reject" data-id="{{ $req->id }}" data-code="{{ $req->code }}" title="Từ chối">
                                    <i class="fas fa-times"></i>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center text-muted">Chưa có đề nghị cấp phát vật tư nào.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
