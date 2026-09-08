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
    @perm('export_material_request')
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#reqCreateModal">
            <i class="fas fa-plus mr-1"></i> Tạo đề nghị cấp phát vật tư
        </button>
    @endperm

    @if (!empty($reqUnissued))
        <a href="{{ route($expRoute . 'list', array_merge(request()->except(['req_unissued', 'req_page']), ['tab' => 'request'])) }}"
            class="btn btn-warning" title="Bấm để xem lại tất cả đề nghị">
            <i class="fas fa-filter mr-1"></i> Đang lọc: Chưa cấp phát đủ ({{ $reqUnissuedCount }}) <i class="fas fa-times ml-1"></i>
        </a>
    @else
        <a href="{{ route($expRoute . 'list', array_merge(request()->except(['req_page']), ['tab' => 'request', 'req_unissued' => 1])) }}"
            class="btn btn-outline-warning text-dark" style="border-color: #d97706; background-color: #fffbeb;"
            title="Lọc các đề nghị đã duyệt nhưng kho chưa cấp phát đủ">
            <i class="fas fa-hourglass-half mr-1 text-warning"></i> Chưa cấp phát đủ
            @if ($reqUnissuedCount > 0)
                <span class="badge badge-warning ml-1" style="font-size: 0.8rem;">{{ $reqUnissuedCount }}</span>
            @endif
        </a>
    @endif
</div>

@include('pages.shared.rangeFilter', [
    'rfRoute' => $expRoute . 'list',
    'rfTab' => 'request',
    'rfPrefix' => 'req_',
    'rfRange' => $reqRange,
    'rfPerPage' => $reqPerPage,
    'rfSearch' => false,
    'rfDateLabel' => 'Ngày lập',
])

<div class="table-responsive">
    <table id="meReqTable" class="table table-bordered table-hover w-100 md-table" data-server-paged>
        <thead>
            <tr>
                <th class="text-center" style="width:45px">STT</th>
                <th style="width:150px">Mã Đề Nghị</th>
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
                    $editable = in_array($req->app_status, ['draft', 'rejected']) && user_can('export_material_request');
                @endphp
                <tr>
                    <td class="text-center">{{ $requestLists->firstItem() + $loop->index }}</td>
                    <td><span class="exp-code font-weight-bold">{{ $req->code }}</span>
                        @if ($req->name) <div class="md-sub small font-weight-bold" style="color: var(--primary-dark);">{{ $req->name }}</div> @endif
                        @if ($req->note) <div class="md-sub small text-muted">{{ $req->note }}</div> @endif
                    </td>
                    <td class="text-center"><span class="md-tag">{{ $items->count() }}</span></td>
                    <td>
                        <span class="md-badge {{ $b['class'] }}">{{ $b['label'] }}</span>
                        @if ($req->app_status === 'rejected' && $req->reject_reason)
                            <div class="md-sub small text-danger">{{ $req->reject_reason }}</div>
                        @endif
                    </td>
                    <td>
                        @php $signs = $requestSigns->get($req->id, collect()); @endphp
                        @if ($signs->isEmpty())
                            <span class="md-sub">Không cần ký duyệt</span>
                        @else
                            <div class="me-flow">
                                @foreach ($signs as $s)
                                    @php
                                        // Bước đã ký / bị từ chối / đang chờ ký; các bước phía sau còn mờ
                                        $cls = 'step';
                                        if ($s->status === 'signed') $cls .= ' done';
                                        elseif ($s->status === 'rejected') $cls .= ' rejected';
                                        elseif ($req->app_status === 'pending_sign' && (int) $req->current_step === (int) $s->step_no) $cls .= ' current';
                                        else $cls .= ' skip';

                                        // Phiếu cũ không chỉ định đích danh thì hiện chức danh được ký thay
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
                        @endif
                    </td>
                    <td class="text-center md-sub">
                        @if ($req->issue_status)
                            <span class="badge badge-{{ $req->issue_status === 'completed' ? 'success' : 'info' }}">
                                {{ $reqIssueStatuses[$req->issue_status] ?? $req->issue_status }}
                            </span>
                        @else — @endif
                    </td>
                    <td class="md-sub">{{ $req->updated_by ?: $req->created_by ?: '—' }}
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
                                    data-title="Trình ký đề nghị {{ $req->code }}?"
                                    data-text="{{ $signs->isEmpty() ? 'Đề nghị không khai bước ký nào nên sẽ được duyệt ngay, kho có thể cấp phát.' : 'Đề nghị sẽ chuyển tới người ký bước 1 trong ' . $signs->count() . ' bước.' }}">
                                    @csrf <input type="hidden" name="request_list_id" value="{{ $req->id }}">
                                    <button type="submit" class="btn btn-sm btn-success" title="Trình ký"><i class="fas fa-paper-plane"></i></button>
                                </form>
                                <form class="form-md-confirm d-inline" action="{{ route($expRoute . 'requestDestroy') }}" method="POST"
                                    data-title="Huỷ đề nghị {{ $req->code }}?" data-text="Đề nghị sẽ bị huỷ." data-danger="1">
                                    @csrf <input type="hidden" name="request_list_id" value="{{ $req->id }}">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Huỷ"><i class="fas fa-trash"></i></button>
                                </form>
                            @endif

                            {{-- Chỉ đúng người được chỉ định ở bước đang chờ mới thấy nút Ký / Từ chối --}}
                            @if ($req->can_sign && user_can('export_material_approve'))
                                @php $isLastStep = (int) $req->pending_sign->step_no >= (int) $req->sign_step_count; @endphp
                                <form class="form-md-confirm d-inline" action="{{ route($expRoute . 'requestSign') }}" method="POST"
                                    data-require-password="1"
                                    data-title="Ký duyệt bước {{ $req->pending_sign->step_no }}/{{ $req->sign_step_count }}?"
                                    data-text="Đề nghị {{ $req->code }} {{ $isLastStep ? 'sẽ được duyệt và kho có thể cấp phát.' : 'sẽ chuyển tới người ký bước ' . ($req->pending_sign->step_no + 1) . '.' }}">
                                    @csrf <input type="hidden" name="request_list_id" value="{{ $req->id }}">
                                    <button type="submit" class="btn btn-sm btn-success" title="Ký duyệt">
                                        <i class="fas fa-{{ $isLastStep ? 'stamp' : 'signature' }}"></i>
                                    </button>
                                </form>
                                <button type="button" class="btn btn-sm btn-outline-danger btn-req-reject" data-id="{{ $req->id }}" data-code="{{ $req->code }}" title="Từ chối">
                                    <i class="fas fa-times"></i>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted">
                    {{ !empty($reqUnissued) ? 'Không có đề nghị nào chưa cấp phát đủ.' : 'Chưa có đề nghị cấp phát vật tư nào.' }}
                </td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('pages.shared.paginator', [
    'pgItems' => $requestLists,
    'pgTab' => 'request',
    'pgUnit' => !empty($reqUnissued) ? 'đề nghị chưa cấp phát đủ' : 'đề nghị',
])
