{{--
| Phiếu chi tiết của một đề nghị chuyển vật tư liên phòng ban. Một modal dùng cho cả hai
| vai, tự đổi cột và nút theo phòng đang đăng nhập:
|   - Mình là B (phòng được đề nghị): chọn mã xuất nhập của kho mình + số lượng -> Cấp phát.
|   - Mình là A (phòng đề nghị)     : Nhận (= Xác nhận kiểm tra lô, modal transferReceiveCheckModal:
|                                     khai thông tin lô + định khu của phòng mình) / Từ chối nhận.
--}}
@php
    $mtStatus = $mtStatus ?? [
        'draft' => ['label' => 'Lưu tạm', 'class' => 'neutral'],
        'pending_sign' => ['label' => 'Chờ ký', 'class' => 'warning'],
        'pending' => ['label' => 'Chờ cấp phát', 'class' => 'pending'],
        'partial' => ['label' => 'Cấp một phần', 'class' => 'warning'],
        'completed' => ['label' => 'Đã cấp đủ', 'class' => 'accepted'],
        'rejected' => ['label' => 'Từ chối', 'class' => 'rejected'],
        'canceled' => ['label' => 'Đã huỷ', 'class' => 'rejected'],
        'issued' => ['label' => 'Chờ nhận', 'class' => 'issued'],
        'received' => ['label' => 'Đã nhận', 'class' => 'accepted'],
        'returned' => ['label' => 'Đã từ chối nhận', 'class' => 'rejected'],
    ];
    $mtBadge = $mtBadge ?? fn($status) => $mtStatus[$status] ?? ['label' => $status, 'class' => 'pending'];
    // Cả đề nghị liên phòng ban lẫn đề nghị VPP (tab Đề nghị VPP) dùng chung modal chi tiết
    $mtAll = $transferSent->getCollection()
        ->merge($transferSentDone->getCollection())
        ->merge($transferReceived->getCollection())
        ->merge($transferDone->getCollection())
        ->merge($vppSent->getCollection())
        ->merge($vppSentDone->getCollection())
        ->merge($vppReceived->getCollection())
        ->merge($vppDone->getCollection())
        // Phiếu chờ mình ký có thể không nằm trong 4 danh sách trên (VD người ký thuộc phòng thứ ba)
        ->merge($transferToSign)
        ->merge($vppToSign)
        ->unique('id');
    // Phiếu người đăng nhập đang ký được ở bước hiện tại -> chân modal có nút Đồng ý / Từ chối
    $mtSignable = $transferToSign->merge($vppToSign)->keyBy('id');
@endphp

<style>
    .mat-pick-display {
        border: 1px solid #ced4da;
        border-radius: var(--border-radius-md, 8px);
        padding: 7px 10px;
        min-height: 38px;
        background: #fff;
        font-size: 0.86rem;
    }
    .mat-pick-display.is-empty {
        border-style: dashed;
        background: var(--primary-soft, #eaf3fc);
    }
</style>

@foreach ($mtAll as $req)
    @php
        $items = $transferItems[$req->id] ?? collect();
        $iAmSource = (int) $req->to_department_id === (int) $currentDepartmentId;
        // Tồn của phòng cấp phát theo từng vật tư (MaterialExportController::transferSourceStock)
        $sourceStock = $transferSourceStock[(int) $req->to_department_id] ?? [];
        $mtSignRow = $mtSignable->get($req->id);
        $mtCanSign = (bool) $mtSignRow;
        // Đề nghị VPP (gửi Hành Chính): phòng đề nghị không bao giờ thấy tồn kho VPP; phía Hành Chính
        // cũng ẩn ở bước đề nghị / ký duyệt, tới bước cấp phát mới hiện
        $mtIsVpp = \App\Support\MaterialVpp::isVppDepartment((int) $req->to_department_id)
            && (! $iAmSource || in_array($req->status, ['draft', \App\Support\MaterialTransferSignFlow::STATUS_PENDING_SIGN], true));
    @endphp
    <div class="modal fade md-modal" id="matTransferDetailModal_{{ $req->id }}" data-req-id="{{ $req->id }}" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl" style="max-width: 96vw;" role="document">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-light py-2">
                    <h5 class="modal-title font-weight-bold text-primary" style="font-size: 1.05rem;">
                        <i class="fas fa-people-arrows mr-2"></i> {{ $req->title ?: 'Đề Nghị Liên Phòng Ban' }}
                        <span class="text-muted font-weight-normal" style="font-size: 0.85rem;">({{ $req->code }})</span>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body p-3">
                    <div class="card mb-3 border-0" style="background-color: #f1f5f9 !important;">
                        <div class="card-body py-2 px-3">
                            <div class="row align-items-center" style="font-size: 0.9rem;">
                                <div class="col-md-3">
                                    <span class="text-muted">{{ $iAmSource ? 'Phòng đề nghị:' : 'Gửi đến phòng:' }}</span>
                                    <b class="text-primary ml-1">{{ $req->partner_name ?: '—' }}</b>
                                </div>
                                <div class="col-md-2">
                                    <span class="text-muted">Trạng thái:</span>
                                    <span class="exp-req-badge mt-req-status {{ $mtBadge($req->status)['class'] }} ml-1">{{ $mtBadge($req->status)['label'] }}</span>
                                </div>
                                <div class="col-md-2">
                                    <span class="text-muted">Người lập:</span>
                                    <b class="ml-1">{{ $req->updated_by ?: $req->created_by ?: '—' }}</b>
                                </div>
                                <div class="col-md-2">
                                    <span class="text-muted">Ngày lập:</span>
                                    <b class="ml-1">{{ $expDate($req->created_at) }}</b>
                                </div>
                                <div class="col-md-3">
                                    <span class="text-muted">Ngày mong muốn:</span>
                                    <b class="ml-1">{{ $req->needed_date ? $expDate($req->needed_date) : '—' }}</b>
                                </div>
                            </div>
                            @if ($req->note)
                                <div class="mt-2 text-muted" style="font-size: 0.85rem;">
                                    <i class="fas fa-comment-dots mr-1 text-secondary"></i><b>Ghi chú:</b> {{ $req->note }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover mb-0 w-100" style="font-size: 0.86rem;">
                            <thead class="bg-light text-center">
                                <tr>
                                    <th style="width: 40px">STT</th>
                                    <th style="min-width: 220px">Vật Tư</th>
                                    <th style="min-width: 90px" class="text-right">SL Đề Nghị</th>
                                    @unless ($mtIsVpp)
                                        <th style="min-width: 100px" class="text-right" title="Tồn hiện hành của phòng cấp phát">Tồn Hiện Hành</th>
                                        <th style="min-width: 100px" class="text-right" title="Tồn trừ phần các đề nghị nội bộ của phòng cấp phát đang chờ ký / chờ cấp đã giữ chỗ">Tồn Khả Dụng</th>
                                    @endunless
                                    @if ($iAmSource)
                                        <th style="min-width: 240px">Mã Xuất Nhập (Kho Mình) <span class="text-danger">*</span></th>
                                        <th style="min-width: 110px" class="text-right">SL Cấp Phát <span class="text-danger">*</span></th>
                                    @else
                                        <th style="min-width: 220px">Mã Cấp / Định Khu Nhận</th>
                                        <th style="min-width: 110px" class="text-right">SL Cấp Phát</th>
                                    @endif
                                    <th style="min-width: 90px">ĐVT</th>
                                    <th style="min-width: 130px">Ghi Chú</th>
                                    <th style="min-width: 170px" class="text-center">Thao Tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($items as $item)
                                    <tr class="mat-issue-row">
                                        <td class="text-center align-middle text-muted">{{ $loop->iteration }}</td>
                                        <td class="align-middle">
                                            <span class="font-weight-bold text-dark">{{ $item->material_name ?: '—' }}</span>
                                            <span class="badge badge-secondary ml-1">{{ $item->category_code }}</span>
                                            @if ($item->technical_specification)
                                                <div class="md-sub small text-muted">{{ $item->technical_specification }}</div>
                                            @endif
                                        </td>
                                        <td class="align-middle text-right font-weight-bold text-primary">
                                            {{ $expNum($item->requested_amount) }} {{ $item->requested_unit }}
                                        </td>
                                        @unless ($mtIsVpp)
                                        @php
                                            $stock = $sourceStock[(int) $item->category_id] ?? ['remaining' => 0, 'available' => 0, 'unit' => null];
                                            // Chỉ so với SL đề nghị khi cùng đơn vị, khác đơn vị thì không tô màu thiếu
                                            $sameUnit = ! $item->requested_unit || ! $stock['unit'] || $item->requested_unit === $stock['unit'];
                                            $short = $sameUnit && $stock['available'] + 0.0001 < (float) $item->requested_amount;
                                        @endphp
                                        <td class="align-middle text-right">
                                            {{ $expNum($stock['remaining']) }} <span class="text-muted">{{ $stock['unit'] }}</span>
                                        </td>
                                        <td class="align-middle text-right font-weight-bold {{ $short ? 'text-danger' : 'text-success' }}"
                                            @if ($short) title="Tồn khả dụng của phòng cấp phát không đủ số lượng đề nghị" @endif>
                                            @if ($short)<i class="fas fa-exclamation-triangle mr-1"></i>@endif{{ $expNum($stock['available']) }}
                                            <span class="text-muted font-weight-normal">{{ $stock['unit'] }}</span>
                                        </td>
                                        @endunless

                                        @if ($iAmSource && $item->status === 'pending')
                                            {{-- B đang cấp phát: chỉ chọn mã xuất nhập của mình + số lượng. Định khu
                                                 là việc của phòng A, để A tự chọn ở bước Nhận. --}}
                                            @php
                                                /*
                                                | Điền sẵn mã xuất nhập theo FEFO rồi FIFO như cấp phát nội bộ
                                                | (MaterialPicking::planFrom, một dòng một lô: ưu tiên lô MỘT MÌNH đủ,
                                                | không có thì lô tốt nhất còn hàng). Vẫn đổi được bằng nút chọn.
                                                */
                                                $mtPlan = \App\Support\MaterialPicking::planFrom(
                                                    $availableImports->where('category_id', $item->category_id),
                                                    (float) $item->requested_amount,
                                                    false
                                                );
                                                $mtLine = $mtPlan['lines'][0] ?? null;
                                                $mtSameUnit = $mtLine && (! $item->requested_unit || ! $mtLine['unit'] || $item->requested_unit === $mtLine['unit']);
                                                $mtIssueAmount = $mtLine && $mtSameUnit ? $mtLine['suggested_amount'] : (float) $item->requested_amount;
                                            @endphp
                                            <td class="align-middle mt-code-cell">
                                                <div class="d-flex align-items-center" style="gap: 5px;">
                                                    @if ($mtLine)
                                                        <div class="mat-pick-display flex-grow-1" data-import-id="{{ $mtLine['import_id'] }}"
                                                            title="Gợi ý theo FEFO / FIFO - bấm nút bên cạnh để chọn mã khác">
                                                            <span class="font-weight-bold">{{ $mtLine['import_code'] }}</span>
                                                            <span class="text-muted ml-1">còn {{ $expNum($mtLine['available']) }} {{ $mtLine['unit'] }}</span>
                                                            @if ($mtLine['location_code'])
                                                                <span class="ml-1">{{ \App\Support\ZoneType::tag($mtLine['location_code'], $mtLine['location_zone_type'] ?? null) }}</span>
                                                            @endif
                                                            @if ($mtSameUnit && $mtPlan['shortage'] > 0.0001)
                                                                <small class="d-block text-danger">
                                                                    <i class="fas fa-exclamation-triangle mr-1"></i>Không lô nào đủ, thiếu {{ $expNum($mtPlan['shortage']) }} {{ $mtLine['unit'] }}
                                                                </small>
                                                            @endif
                                                        </div>
                                                    @else
                                                        <div class="mat-pick-display flex-grow-1 is-empty">
                                                            <span class="text-muted"><i class="fas fa-boxes mr-1"></i> Không còn lô khả dụng</span>
                                                        </div>
                                                    @endif
                                                    {{-- Cùng phân quyền cấp phát nội bộ: không có quyền điều chỉnh mã xuất nhập thì khoá đúng mã đề xuất --}}
                                                    @if (user_can(\App\Http\Controllers\Pages\Export\MaterialExportController::LOT_ADJUST_PERMISSION))
                                                        <button type="button" class="btn btn-sm btn-outline-primary btn-open-mat-picker"
                                                            data-category-id="{{ $item->category_id }}" title="Chọn từ tồn kho của phòng">
                                                            <i class="fas fa-list-ul"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="align-middle text-right mt-amount-cell">
                                                <input type="text" inputmode="decimal" min="0.0001" name="issued_amount"
                                                    class="form-control text-right font-weight-bold input-mat-issue-amount js-decimal"
                                                    value="{{ (float) $mtIssueAmount }}"
                                                    @if ($mtLine) max="{{ (float) $mtLine['available'] }}" @endif
                                                    @unless (user_can(\App\Http\Controllers\Pages\Export\MaterialExportController::AMOUNT_ADJUST_PERMISSION))
                                                        readonly title="Không có quyền điều chỉnh số lượng cấp phát"
                                                    @endunless
                                                    style="height: 38px !important; min-height: 38px !important; font-size: 0.88rem;" required>
                                            </td>
                                            <td class="align-middle text-center mt-unit-cell">
                                                <select name="issued_unit" class="form-control select-mat-issue-unit text-center"
                                                    style="height: 38px !important; min-height: 38px !important; font-size: 0.88rem;">
                                                    <option value="">-- ĐVT --</option>
                                                    @foreach ($units as $u)
                                                        @php $uVal = $u->short_name ?: $u->name; @endphp
                                                        <option value="{{ $uVal }}" {{ $item->requested_unit == $uVal ? 'selected' : '' }}>{{ $uVal }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                        @else
                                            <td class="align-middle mt-code-cell">
                                                @if ($item->import_code)
                                                    <span class="exp-code font-weight-bold">{{ $item->import_code }}</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif

                                                @if (! $iAmSource && $item->status === 'issued')
                                                    {{-- Phòng mình (A) đang nhận: định khu chọn trong modal Xác nhận kiểm tra khi bấm Nhận --}}
                                                    @if (! in_array((int) $item->category_id, $declaredCategoryIds, true))
                                                        <small class="text-danger d-block mt-1 mt-undeclared">
                                                            <i class="fas fa-exclamation-triangle mr-1"></i>Chưa khai vật tư này ở tab "Vật Tư Của Phòng"
                                                        </small>
                                                    @endif
                                                @elseif ($item->dest_location_code)
                                                    <div class="mt-1">{{ \App\Support\ZoneType::tag($item->dest_location_code, $item->dest_location_zone_type ?? null) }}</div>
                                                @endif
                                            </td>
                                            <td class="align-middle text-right mt-amount-cell">
                                                @if ($item->issued_amount !== null)
                                                    <span class="font-weight-bold text-success">{{ $expNum($item->issued_amount) }}</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td class="align-middle text-center mt-unit-cell">{{ $item->issued_unit ?: '—' }}</td>
                                        @endif

                                        <td class="align-middle text-muted mt-note-cell" title="{{ $item->note }}">
                                            {{ $item->note ?: '—' }}
                                            @if ($item->status === 'rejected' && $item->reject_note)
                                                <div class="text-danger mt-1"><i class="fas fa-ban mr-1"></i>{{ $item->reject_note }}</div>
                                            @elseif ($item->status === 'returned' && $item->return_note)
                                                <div class="text-danger mt-1"><i class="fas fa-undo mr-1"></i>{{ $item->return_note }}</div>
                                            @endif
                                        </td>

                                        <td class="align-middle text-center mt-action-cell" style="white-space: nowrap;">
                                            @if ($mtCanSign && $item->status === 'draft')
                                                {{-- Duyệt một phần: bỏ tick = từ chối mục này khi bấm Đồng ý (bắt buộc lý do) --}}
                                                <div class="custom-control custom-checkbox d-inline-block">
                                                    <input type="checkbox" class="custom-control-input mt-approve-item" id="mtApprove_{{ $item->id }}"
                                                        value="{{ $item->id }}" checked>
                                                    <label class="custom-control-label font-weight-bold text-success" for="mtApprove_{{ $item->id }}">Duyệt</label>
                                                </div>
                                                <input type="text" maxlength="450" class="form-control form-control-sm mt-1 mt-approve-reason d-none"
                                                    placeholder="Lý do từ chối mục này..." style="min-width: 180px;">
                                            @elseif ($iAmSource && $item->status === 'pending' && user_can('export_material_transfer'))
                                                <form action="{{ route('pages.export.materialExport.transferIssueStore') }}" method="POST" class="d-inline form-mat-transfer-issue">
                                                    @csrf
                                                    <input type="hidden" name="item_id" value="{{ $item->id }}">
                                                    <input type="hidden" name="import_id" class="hidden-mat-import-id" value="">
                                                    <input type="hidden" name="issued_amount" class="hidden-mat-issued-amount" value="">
                                                    <input type="hidden" name="issued_unit" class="hidden-mat-issued-unit" value="">
                                                    <button type="button" class="btn btn-xs btn-success btn-trigger-mat-transfer-issue px-2 py-1 shadow-sm" title="Xác nhận cấp phát ngay">
                                                        <i class="fas fa-check-circle mr-1"></i> Cấp phát
                                                    </button>
                                                </form>

                                                <form action="{{ route('pages.export.materialExport.transferRequestReject') }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="item_id" value="{{ $item->id }}">
                                                    <button type="button" class="btn btn-xs btn-danger ml-1 px-2 py-1 shadow-sm btn-mat-transfer-reject" title="Từ chối cấp phát">
                                                        <i class="fas fa-ban mr-1"></i> Từ chối
                                                    </button>
                                                </form>
                                            @elseif (! $iAmSource && $item->status === 'issued' && (user_can('export_material_transfer_receive') || user_can('export_material_transfer_return')))
                                                @if (user_can('export_material_transfer_receive'))
                                                    <form action="{{ route('pages.export.materialExport.transferReceiveStore') }}" method="POST" class="d-inline form-mat-transfer-receive"
                                                        data-reject-url="{{ route('pages.export.materialExport.transferReceiveReject') }}">
                                                        @csrf
                                                        <input type="hidden" name="item_id" value="{{ $item->id }}">
                                                        <button type="button" class="btn btn-xs btn-success btn-trigger-mat-transfer-receive px-2 py-1 shadow-sm"
                                                            title="Xác nhận kiểm tra lô và nhận hàng"
                                                            data-row="{{ json_encode([
                                                                'code' => $item->import_code,
                                                                'material' => trim(($item->material_name ?: '—') . ' (' . $item->category_code . ')'),
                                                                'amount' => $item->issued_amount !== null ? $expNum($item->issued_amount) . ' ' . $item->issued_unit : '—',
                                                                'from' => $req->partner_name,
                                                                'batch_no' => $item->source_batch_no ?: '—',
                                                                'invoice_number' => $item->source_invoice_number ?: '—',
                                                                'invoice_date' => $expDate($item->source_invoice_date),
                                                                'expired_date' => $expDate($item->source_expired_date),
                                                                'purpose' => $item->source_purpose ?: '—',
                                                            ]) }}">
                                                            <i class="fas fa-inbox mr-1"></i> Nhận
                                                        </button>
                                                    </form>
                                                @endif
                                                @if (user_can('export_material_transfer_return'))
                                                    <form action="{{ route('pages.export.materialExport.transferReceiveReject') }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <input type="hidden" name="item_id" value="{{ $item->id }}">
                                                        <button type="button" class="btn btn-xs btn-danger ml-1 px-2 py-1 shadow-sm btn-mat-transfer-return" title="Từ chối nhận, hoàn tồn cho phòng gửi">
                                                            <i class="fas fa-undo mr-1"></i> Từ chối nhận
                                                        </button>
                                                    </form>
                                                @endif
                                            @else
                                                <span class="exp-req-badge {{ $mtBadge($item->status)['class'] }}">{{ $mtBadge($item->status)['label'] }}</span>
                                                @if ($item->issued_by)
                                                    <small class="d-block text-muted">Cấp: {{ $item->issued_by }}</small>
                                                @endif
                                                @if ($item->issued_at)
                                                    <small class="d-block text-muted" style="font-size: 0.78rem;">{{ $expDateTime($item->issued_at) }}</small>
                                                @endif
                                                @if ($item->received_by)
                                                    <small class="d-block text-muted">Nhận: {{ $item->received_by }}</small>
                                                @endif
                                                @if ($item->received_at)
                                                    <small class="d-block text-muted" style="font-size: 0.78rem;">{{ $expDateTime($item->received_at) }}</small>
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $mtIsVpp ? 8 : 10 }}" class="text-center text-muted py-3">Phiếu này không có vật tư nào.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    @if ($mtCanSign)
                        <button type="button" class="btn btn-outline-danger btn-mt-sign"
                            data-mode="reject" data-id="{{ $req->id }}" data-code="{{ $req->code }}" data-step="{{ $mtSignRow->sign_step_no }}">
                            <i class="fas fa-times mr-1"></i> Từ chối
                        </button>
                        {{-- Mục bỏ tick "Duyệt" ở cột Thao Tác được gửi kèm là mục bị từ chối (transferLists: .btn-mt-sign) --}}
                        <button type="button" class="btn btn-success btn-mt-sign"
                            data-mode="sign" data-id="{{ $req->id }}" data-code="{{ $req->code }}" data-step="{{ $mtSignRow->sign_step_no }}">
                            <i class="fas fa-signature mr-1"></i> Đồng ý
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endforeach

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var MatTransferToast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });

        var MatTransferStatus = @json($mtStatus);

        function mtEsc(text) {
            return $('<div>').text(text == null ? '' : String(text)).html();
        }

        function mtNum(value) {
            var n = parseFloat(value);
            return isNaN(n) ? '' : n.toLocaleString('en-US', { maximumFractionDigits: 4 });
        }

        function mtBadgeOf(status) {
            return MatTransferStatus[status] || { label: status, class: 'pending' };
        }

        /** Đổi badge trạng thái phiếu ở đầu modal và ở dòng danh sách phía sau, không tải lại trang. */
        function mtRefreshRequestStatus($row, status) {
            if (!status) {
                return;
            }
            var b = mtBadgeOf(status);
            var $modal = $row.closest('.modal');

            $modal.find('.mt-req-status').attr('class', 'exp-req-badge mt-req-status ml-1 ' + b.class).text(b.label);
            $('[data-target="#' + $modal.attr('id') + '"]').closest('tr').find('.exp-req-badge').first()
                .attr('class', 'exp-req-badge ' + b.class).text(b.label);
        }

        /** Dòng đã xử lý xong: cột Thao Tác thành badge trạng thái + người / thời điểm. */
        function mtFinishRow($row, status, lines) {
            var b = mtBadgeOf(status);
            var html = '<span class="exp-req-badge ' + b.class + '">' + mtEsc(b.label) + '</span>';
            (lines || []).forEach(function(line) {
                if (line) {
                    html += '<small class="d-block text-muted">' + mtEsc(line) + '</small>';
                }
            });
            $row.find('.mt-action-cell').html(html);
        }

        /** Hỏi lý do rồi gửi AJAX (từ chối cấp phát / từ chối nhận). */
        function askReasonThenSubmit($btn, options) {
            var $form = $btn.closest('form');
            Swal.fire({
                title: options.title,
                text: options.text || '',
                input: 'text',
                inputPlaceholder: options.placeholder,
                showCancelButton: true,
                confirmButtonText: options.confirmText,
                cancelButtonText: 'Huỷ',
                confirmButtonColor: '#dc3545',
                inputValidator: function(value) {
                    if (!value) {
                        return options.requiredMessage;
                    }
                }
            }).then(function(result) {
                if (result.isConfirmed) {
                    if ($form.find('input[name="' + options.field + '"]').length === 0) {
                        $form.append('<input type="hidden" name="' + options.field + '">');
                    }
                    $form.find('input[name="' + options.field + '"]').val(result.value);
                    submitTransferForm($btn, $form, $btn.html(), options.done);
                }
            });
        }

        // Bỏ tick "Duyệt" = từ chối mục đó: hiện ô lý do, tô đỏ dòng
        $(document).on('change', '.mt-approve-item', function() {
            var $tr = $(this).closest('tr');
            var rejected = !this.checked;

            $tr.toggleClass('table-danger', rejected);
            $(this).next('label').text(rejected ? 'Từ chối' : 'Duyệt').toggleClass('text-success', !rejected).toggleClass('text-danger', rejected);
            $tr.find('.mt-approve-reason').toggleClass('d-none', !rejected);

            if (rejected) {
                $tr.find('.mt-approve-reason').trigger('focus');
            }
        });

        $(document).on('click', '.btn-mat-transfer-reject', function(e) {
            e.preventDefault();
            askReasonThenSubmit($(this), {
                title: 'Từ chối cấp phát mục này?',
                placeholder: 'Nhập lý do từ chối...',
                confirmText: 'Từ chối',
                field: 'reject_note',
                requiredMessage: 'Vui lòng nhập lý do từ chối!',
                done: function($row, data) {
                    $row.find('.mt-code-cell, .mt-amount-cell').html('<span class="text-muted">—</span>');
                    $row.find('.mt-unit-cell').text('—');
                    $row.find('.mt-note-cell').append('<div class="text-danger mt-1"><i class="fas fa-ban mr-1"></i>' + mtEsc(data.reject_note) + '</div>');
                    mtFinishRow($row, 'rejected');
                }
            });
        });

        $(document).on('click', '.btn-mat-transfer-return', function(e) {
            e.preventDefault();
            askReasonThenSubmit($(this), {
                title: 'Từ chối nhận mục này?',
                text: 'Tồn kho sẽ được hoàn lại cho phòng gửi.',
                placeholder: 'Nhập lý do từ chối nhận...',
                confirmText: 'Từ chối nhận',
                field: 'return_note',
                requiredMessage: 'Vui lòng nhập lý do từ chối nhận!',
                done: function($row, data) {
                    $row.find('.input-mat-dest-location').next('.select2-container').addBack().remove();
                    $row.find('.mt-undeclared').remove();
                    $row.find('.mt-note-cell').append('<div class="text-danger mt-1"><i class="fas fa-undo mr-1"></i>' + mtEsc(data.return_note) + '</div>');
                    mtFinishRow($row, 'returned');
                }
            });
        });

        /** Gửi form bằng AJAX, xong thì cập nhật đúng dòng đó trong modal (không tải lại trang). */
        function submitTransferForm($btn, $form, idleHtml, done) {
            var $row = $btn.closest('.mat-issue-row');
            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                beforeSend: function() {
                    $row.find('.mt-action-cell .btn').prop('disabled', true);
                    $btn.html('<i class="fas fa-spinner fa-spin mr-1"></i> Đang xử lý...');
                },
                success: function(res) {
                    if (res.success) {
                        MatTransferToast.fire({ icon: 'success', title: res.message });
                        var data = res.data || {};
                        if (done) {
                            done($row, data);
                        }
                        mtRefreshRequestStatus($row, data.request_status);
                    } else {
                        $row.find('.mt-action-cell .btn').prop('disabled', false);
                        $btn.html(idleHtml);
                        MatTransferToast.fire({ icon: 'error', title: res.message });
                    }
                },
                error: function() {
                    $row.find('.mt-action-cell .btn').prop('disabled', false);
                    $btn.html(idleHtml);
                    MatTransferToast.fire({ icon: 'error', title: 'Có lỗi xảy ra, vui lòng thử lại!' });
                }
            });
        }

        $(document).on('click', '.btn-trigger-mat-transfer-issue', function(e) {
            e.preventDefault();

            var $btn = $(this);
            var $row = $btn.closest('.mat-issue-row');
            var $form = $btn.closest('.form-mat-transfer-issue');

            // Đọc thẳng thuộc tính, KHÔNG dùng .data(): .data() cache giá trị lần đọc đầu nên chọn
            // lại mã khác trong picker (chỉ đổi attr) vẫn gửi mã cũ lên server
            var importId = $row.find('.mat-pick-display').attr('data-import-id');
            var issuedAmount = $row.find('.input-mat-issue-amount').val();

            if (!importId) {
                MatTransferToast.fire({ icon: 'error', title: 'Vui lòng chọn Mã Xuất Nhập!' });
                return;
            }

            if (!issuedAmount || parseFloat(issuedAmount) <= 0) {
                MatTransferToast.fire({ icon: 'error', title: 'Vui lòng nhập SL Cấp Phát hợp lệ!' });
                return;
            }

            $form.find('.hidden-mat-import-id').val(importId);
            $form.find('.hidden-mat-issued-amount').val(issuedAmount);
            $form.find('.hidden-mat-issued-unit').val($row.find('.select-mat-issue-unit').val());

            // Bấm là cấp luôn, không hỏi xác nhận
            submitTransferForm($btn, $form, '<i class="fas fa-check-circle mr-1"></i> Cấp phát', function($row, data) {
                $row.find('.mt-code-cell').html('<span class="exp-code font-weight-bold">' + mtEsc(data.import_code) + '</span>');
                $row.find('.mt-amount-cell').html('<span class="font-weight-bold text-success">' + mtNum(data.issued_amount) + '</span>');
                $row.find('.mt-unit-cell').text(data.issued_unit || '—');
                mtFinishRow($row, 'issued', [data.issued_by ? 'Cấp: ' + data.issued_by : '', data.issued_at]);
            });
        });

        /* ---------- NHẬN = XÁC NHẬN KIỂM TRA LÔ (modal transferReceiveCheckModal) ---------- */
        var $rc = $('#matReceiveCheckModal');
        var rcTarget = null; // { $btn, $form } của mục đang nhận

        function rcSync() {
            var failed = $rc.find('[name="rc_check_result"]:checked').val() === 'failed';

            $rc.find('.imp-check-opt').removeClass('is-active');
            $rc.find('[name="rc_check_result"]:checked').closest('.imp-check-opt').addClass('is-active');
            $rc.find('.js-rc-passed-only').toggleClass('d-none', failed);
            $rc.find('.js-rc-hint-failed').toggleClass('d-none', !failed);
            $rc.find('.js-rc-note-star').toggleClass('d-none', !failed);
            $rc.find('.js-rc-submit')
                .toggleClass('btn-success', !failed)
                .toggleClass('btn-danger', failed)
                .html(failed
                    ? '<i class="fas fa-ban mr-1"></i> Ghi nhận Không đạt (từ chối nhận)'
                    : '<i class="fas fa-clipboard-check mr-1"></i> Xác nhận Kiểm tra Đạt &amp; Nhận');
        }

        $rc.on('change', '[name="rc_check_result"]', rcSync);

        // Bootstrap 4 gỡ modal-open khi đóng modal con - trả lại để phiếu chi tiết còn cuộn được
        $rc.on('hidden.bs.modal', function() {
            if ($('.modal.show').length) {
                $('body').addClass('modal-open');
            }
        });

        $(document).on('click', '.btn-trigger-mat-transfer-receive', function(e) {
            e.preventDefault();
            if (!$rc.length) return;

            var $btn = $(this);
            var row = $btn.data('row') || {};
            rcTarget = { $btn: $btn, $form: $btn.closest('.form-mat-transfer-receive') };

            $rc.find('.rc-code').text(row.code || '—');
            $rc.find('.rc-material').text(row.material || '—');
            $rc.find('.rc-amount').text(row.amount || '—');
            $rc.find('.rc-from').text(row.from || '—');

            $rc.find('[name="rc_check_result"][value="passed"]').prop('checked', true);
            $rc.find('.rc-batch-no').text(row.batch_no || '—');
            $rc.find('.rc-invoice-number').text(row.invoice_number || '—');
            $rc.find('.rc-invoice-date').text(row.invoice_date || '—');
            $rc.find('.rc-expired-date').text(row.expired_date || '—');
            $rc.find('.rc-purpose').text(row.purpose || '—');
            $rc.find('[name="check_note"]').val('');
            $rc.find('[name="dest_location_id"]').val('').trigger('change');
            rcSync();

            // Modal con nằm trên phiếu chi tiết: nền mờ của nó phải che phiếu chi tiết
            $rc.one('shown.bs.modal', function() {
                $('.modal-backdrop').last().css('z-index', 1055);
            });
            $rc.modal('show');
        });

        $rc.on('click', '.js-rc-submit', function() {
            if (!rcTarget) return;

            var failed = $rc.find('[name="rc_check_result"]:checked').val() === 'failed';
            var $btn = rcTarget.$btn;
            var $form = rcTarget.$form;
            var note = $.trim($rc.find('[name="check_note"]').val());

            // Gửi qua form ẩn của dòng để dùng chung submitTransferForm (khoá nút, toast, cập nhật trạng thái phiếu)
            var $send = $('<form method="POST">').attr('action', failed ? $form.data('reject-url') : $form.attr('action'));
            $send.append($form.find('[name="_token"], [name="item_id"]').clone());

            if (failed) {
                if (!note) {
                    MatTransferToast.fire({ icon: 'error', title: 'Vui lòng nhập kết luận kiểm tra (lý do không đạt)!' });
                    $rc.find('[name="check_note"]').trigger('focus');
                    return;
                }
                $send.append($('<input type="hidden" name="return_note">').val('Kiểm tra không đạt: ' + note));
            } else {
                if (!$rc.find('[name="dest_location_id"]').val()) {
                    MatTransferToast.fire({ icon: 'error', title: 'Vui lòng chọn định khu vị trí lưu trữ thật!' });
                    return;
                }
                $rc.find('[name="dest_location_id"], [name="check_note"]').each(function() {
                    if (this.name) $send.append($('<input type="hidden">').attr('name', this.name).val($(this).val()));
                });
            }

            var $submit = $(this).prop('disabled', true);
            var locSelected = $rc.find('[name="dest_location_id"] option:selected');

            submitTransferForm($btn, $send, $btn.html(), function($row, data) {
                $rc.modal('hide');
                $row.find('.mt-undeclared').remove();

                if (failed) {
                    $row.find('.mt-note-cell').append('<div class="text-danger mt-1"><i class="fas fa-undo mr-1"></i>' + mtEsc(data.return_note) + '</div>');
                    mtFinishRow($row, 'returned');
                    return;
                }

                var locText = $.trim(locSelected.text()).split(/\s+/)[0];
                $row.find('.mt-code-cell').append('<div class="mt-1">' + window.wmsZoneTag(locText, locSelected.attr('data-zone') || '') + '</div>');
                mtFinishRow($row, 'received', [data.received_by ? 'Nhận: ' + data.received_by : '', data.received_at]);
            });

            // submitTransferForm chạy AJAX - mở lại nút khi xong (thành công thì modal đã đóng)
            $(document).one('ajaxComplete', function() { $submit.prop('disabled', false); });
        });
    });
</script>
