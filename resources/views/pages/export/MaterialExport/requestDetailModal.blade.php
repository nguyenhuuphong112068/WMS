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
                    <i class="fas fa-list-ul mr-2"></i>Đề Nghị {{ $req->code }}
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
                @endif

                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th style="width:180px">Vật tư</th>
                                <th style="width:130px">Quy cách</th>
                                <th style="width:90px">SL đề nghị</th>
                                <th style="width:150px">Sản phẩm / Mục đích</th>
                                <th style="width:120px">Trạng thái</th>
                                <th>Cấp phát</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $it)
                                @php
                                    $used = in_array($it->id, $usedRequestItemIds);
                                    $matchImports = $availableImports->where('category_id', $it->category_id);
                                @endphp
                                <tr>
                                    <td>
                                        <div class="font-weight-bold">{{ $it->display_name ?: '—' }}</div>
                                        @unless ($it->category_id) <span class="badge badge-secondary">Ngoài danh mục</span> @endunless
                                    </td>
                                    <td class="md-sub">{{ $it->technical_specification ?: '—' }}</td>
                                    <td class="text-right">{{ rtrim(rtrim(number_format((float) $it->requested_amount, 4, '.', ''), '0'), '.') }} {{ $it->requested_unit }}</td>
                                    <td class="md-sub">
                                        {{ $it->product_name ?: '—' }}
                                        @if ($it->purpose) <div><small>{{ $it->purpose }}</small></div> @endif
                                    </td>
                                    <td>
                                        @if ($it->status === 'issued')
                                            <span class="badge badge-success">Đã cấp phát</span>
                                            <div class="md-sub small">{{ $it->issued_by }} · {{ $it->issued_at ? \Carbon\Carbon::parse($it->issued_at)->format('d/m/Y H:i') : '' }}</div>
                                        @elseif ($it->status === 'rejected')
                                            <span class="badge badge-danger">Từ chối</span>
                                            @if ($it->note) <div class="md-sub small text-danger">{{ $it->note }}</div> @endif
                                        @else
                                            <span class="badge badge-light border">Chờ cấp phát</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($it->status === 'issued')
                                            <div>Mã lô: <b>{{ $it->issued_import_code }}</b> — {{ rtrim(rtrim(number_format((float) $it->issued_amount, 4, '.', ''), '0'), '.') }} {{ $it->issued_unit }}</div>
                                            @if (! $used)
                                                <button type="button" class="btn btn-xs btn-primary mt-1 btn-me-use"
                                                    data-item-id="{{ $it->id }}" data-code="{{ $it->issued_import_code }}"
                                                    data-amount="{{ rtrim(rtrim(number_format((float) $it->issued_amount, 4, '.', ''), '0'), '.') }}"
                                                    data-unit="{{ $it->issued_unit }}" data-remaining="—">
                                                    <i class="fas fa-hand-holding-medical mr-1"></i>Lập phiếu sử dụng
                                                </button>
                                            @else
                                                <span class="badge badge-info mt-1">Đã lập phiếu sử dụng</span>
                                            @endif
                                        @elseif ($canIssue && $it->status === 'pending')
                                            <form action="{{ route($expRoute . 'issueStore') }}" method="POST" class="form-inline" style="gap:6px;">
                                                @csrf
                                                <input type="hidden" name="item_id" value="{{ $it->id }}">
                                                <select name="import_id" class="form-control form-control-sm" required>
                                                    <option value="">-- Chọn mã lô --</option>
                                                    @foreach ($matchImports as $mi)
                                                        <option value="{{ $mi->id }}" {{ $mi->selectable ? '' : 'disabled' }}>
                                                            {{ $mi->code }} (còn {{ rtrim(rtrim(number_format((float) $mi->remaining, 4, '.', ''), '0'), '.') }} {{ $mi->unit_short_name }}){{ $mi->expired ? ' · HẾT HẠN' : '' }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <input type="number" step="0.0001" min="0.0001" name="issued_amount" class="form-control form-control-sm" style="width:90px"
                                                    value="{{ rtrim(rtrim(number_format((float) $it->requested_amount, 4, '.', ''), '0'), '.') }}" required>
                                                <input type="text" name="issued_unit" class="form-control form-control-sm" style="width:70px" value="{{ $it->requested_unit }}">
                                                <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-check"></i></button>
                                            </form>
                                            <form action="{{ route($expRoute . 'issueReject') }}" method="POST" class="form-md-confirm-cancel mt-1"
                                                data-title="Từ chối cấp phát mục này?" data-text="Nhập lý do từ chối." data-danger="1">
                                                @csrf
                                                <input type="hidden" name="item_id" value="{{ $it->id }}">
                                                <button type="submit" class="btn btn-xs btn-outline-danger"><i class="fas fa-times mr-1"></i>Từ chối</button>
                                            </form>
                                        @else
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
