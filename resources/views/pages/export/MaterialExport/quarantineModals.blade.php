{{--
| SỬ DỤNG VẬT TƯ - MODAL CỦA TAB "VẬT TƯ HỎNG"
|   #qrCreateModal    : bước 1 - cách ly NHIỀU mã một lần (mỗi mã một dòng, số lượng mặc định = phần
|                       còn cách ly được, chung một lý do); server tạo mỗi mã một phiếu cách ly
|   #qrLotPickerModal : danh mục tồn kho có bộ lọc, tick chọn nhiều mã đổ vào #qrCreateModal
|   #qrEditModal      : sửa số lượng / lý do khi còn đang cách ly
|   #qrDecideModal  : bước 2 - quyết định loại bỏ / trả về kho (ký bằng mật khẩu)
|   #qrDisposeModal : bước 3 - ghi nhận huỷ một hoặc nhiều phiếu (ký bằng mật khẩu)
--}}

@php
    $qrRoute = 'pages.export.materialQuarantine.';
    $qrBag = $errors->getBag('quarantineErrors');
    $qrDecideBag = $errors->getBag('quarantineDecideErrors');
    $qrDisposeBag = $errors->getBag('quarantineDisposeErrors');
    $qrSignError = (string) session('signatureError');

    // Gợi ý phương pháp huỷ - vẫn gõ tự do được
    $qrMethods = ['Đốt', 'Chôn lấp', 'Chuyển đơn vị xử lý chất thải', 'Tái chế / thanh lý phế liệu'];

    // Bộ lọc của Danh mục tồn kho: mức hạn dùng (ngưỡng ở config/material.php) + kho (đoạn đầu mã định khu)
    $qrCritical = (int) config('material.near_expiry_days.critical', 30);
    $qrWarning = (int) config('material.near_expiry_days.warning', 60);
    $qrExpiry = fn($lot) => !$lot->expired_date ? 'none' : ($lot->expiry_level ?: 'ok');
    $qrWarehouse = fn($lot) => $lot->location_code ? explode('/', $lot->location_code)[0] : '';
    $qrWarehouses = collect($quarantineLots)->map($qrWarehouse)->filter()->unique()->sort()->values();
    $qrHasNoLocation = collect($quarantineLots)->contains(fn($lot) => !$lot->location_code);

    // Dữ liệu lô cho JS dựng dòng ở bảng cách ly - (object) để json luôn ra object theo id
    $qrLotData = collect($quarantineLots)->mapWithKeys(fn($lot) => [
        $lot->id => [
            'id' => (int) $lot->id,
            'code' => $lot->code,
            'name' => $lot->material_name,
            'category_code' => $lot->category_code,
            'limit' => round((float) $lot->quarantinable, 4),
            'limit_text' => $expNum($lot->quarantinable),
            'unit' => $lot->unit_short_name,
            'expired_date' => $lot->expired_date ? \Carbon\Carbon::parse($lot->expired_date)->format('d/m/Y') : '',
            'expiry' => $qrExpiry($lot),
            'days' => $lot->days_to_expiry,
        ],
    ]);

    // Lỗi của các dòng cách ly (items, items.N.amount...) gom lại hiện dưới bảng
    $qrItemErrors = collect($qrBag->getMessages())
        ->filter(fn($messages, $key) => str_starts_with($key, 'items'))
        ->flatten()
        ->unique()
        ->values();
@endphp

<style>
    .qr-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 10px;
        padding: 12px 14px;
        margin-bottom: 16px;
        border-radius: var(--border-radius-md, 8px);
        background: var(--primary-soft);
    }

    .qr-summary small {
        display: block;
        color: var(--primary-dark);
        opacity: 0.8;
    }

    .qr-choices {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }

    .qr-choice {
        display: flex;
        gap: 10px;
        align-items: flex-start;
        padding: 12px 14px;
        margin: 0;
        border: 2px solid var(--primary-lighter);
        border-radius: var(--border-radius-md, 8px);
        cursor: pointer;
        font-weight: 400;
        transition: all 0.2s;
    }

    .qr-choice:hover {
        transform: translateY(-1px);
    }

    .qr-choice input {
        margin-top: 4px;
    }

    .qr-choice b {
        display: block;
    }

    .qr-choice.is-remove.is-checked {
        border-color: #DC2626;
        background: #FEF2F2;
    }

    .qr-choice.is-return.is-checked {
        border-color: #16A34A;
        background: #F0FDF4;
    }

    .qr-lot-pick {
        display: flex;
        gap: 8px;
    }

    .qr-lot-pick .qr-lot-select {
        flex: 1 1 auto;
        min-width: 0;
    }

    .qr-lot-pick .btn {
        flex: 0 0 auto;
        white-space: nowrap;
        transition: transform 0.2s ease;
    }

    .qr-lot-pick .btn:hover {
        transform: translateY(-1px);
    }

    #qrLotPickerTable thead th {
        position: sticky;
        top: 0;
        z-index: 1;
        background: var(--primary-soft);
        color: var(--primary);
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        vertical-align: middle;
    }

    #qrLotPickerTable tbody tr.qr-lot-row {
        cursor: pointer;
    }

    #qrLotPickerTable tr.is-checked>td {
        background: var(--primary-soft);
    }

    #qrCreateItems thead th {
        background: var(--primary-soft);
        color: var(--primary);
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        vertical-align: middle;
    }

    #qrCreateItems td {
        vertical-align: middle;
    }

    #qrCreateItems tr.is-flash>td {
        background: #FEF3C7;
        transition: background 0.3s;
    }

    .qr-dispose-list {
        max-height: 200px;
        overflow-y: auto;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .qr-dispose-list li {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        padding: 6px 10px;
        border-bottom: 1px dashed var(--primary-lighter);
    }

    @media (max-width: 575px) {
        .qr-choices {
            grid-template-columns: 1fr;
        }
    }
</style>

{{-- ============ BƯỚC 1 - CÁCH LY ============ --}}
<div class="modal fade md-modal" id="qrCreateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-xl" style="max-width: 1060px;" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-biohazard mr-2"></i>Cách ly vật tư hỏng chờ quyết định</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form action="{{ route($qrRoute . 'store') }}" method="POST" id="qrCreateForm">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Mã xuất nhập cần cách ly <span class="text-danger">*</span></label>
                        <div class="qr-lot-pick">
                            <div class="qr-lot-select">
                                <select id="qrQuickAdd" class="form-control exp-select">
                                    <option value="">-- Chọn nhanh mã xuất nhập để thêm --</option>
                                    @foreach ($quarantineLots as $lot)
                                        <option value="{{ $lot->id }}">
                                            {{ $lot->code }} — {{ $lot->material_name }} (còn {{ $expNum($lot->quarantinable) }} {{ $lot->unit_short_name }}){{ $lot->expired ? ' · HẾT HẠN' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-qr-lot-picker"
                                title="Mở danh mục tồn kho, lọc theo hạn dùng / kho rồi tick chọn nhiều mã">
                                <i class="fas fa-layer-group mr-1"></i> Danh mục tồn kho
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive mb-3">
                        <table class="table table-bordered table-sm mb-0" id="qrCreateItems" data-no-datatable>
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 45px">STT</th>
                                    <th style="width: 170px">Mã Xuất Nhập</th>
                                    <th>Vật Tư</th>
                                    <th class="text-center" style="width: 140px">Hạn Dùng</th>
                                    <th class="text-right" style="width: 130px">Còn Cách Ly Được</th>
                                    <th style="width: 180px">Số Lượng Cách Ly <span class="text-danger">*</span></th>
                                    <th class="text-center" style="width: 50px"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="qr-items-empty">
                                    <td colspan="7" class="text-center text-muted py-3">Chưa chọn mã xuất nhập nào.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    @foreach ($qrItemErrors as $qrItemError)
                        <div class="md-error text-danger small">{{ $qrItemError }}</div>
                    @endforeach

                    <div class="form-group mb-0 mt-3">
                        <label>Lý do cách ly <span class="text-danger">*</span></label>
                        <textarea name="reason" rows="2" maxlength="500" required
                            class="form-control {{ $qrBag->has('reason') ? 'is-invalid' : '' }}"
                            placeholder="Ví dụ: Bao bì rách, vật tư ẩm mốc, hết hạn sử dụng...">{{ old('reason') }}</textarea>
                        @if ($qrBag->has('reason')) <div class="md-error text-danger small">{{ $qrBag->first('reason') }}</div> @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-danger" id="qrCreateSubmit" disabled>
                        <i class="fas fa-biohazard mr-1"></i> Cách ly <span class="qr-create-count"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ============ DANH MỤC TỒN KHO - CHỌN MÃ CẦN CÁCH LY ============ --}}
<div class="modal fade md-modal" id="qrLotPickerModal" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 1065;">
    <div class="modal-dialog modal-dialog-centered modal-xl" style="max-width: 92vw;" role="document">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-light py-2">
                <h5 class="modal-title font-weight-bold text-primary" style="font-size: 1.05rem;">
                    <i class="fas fa-layer-group mr-2"></i> Danh Mục Tồn Kho - Chọn Mã Cần Cách Ly
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>

            <div class="modal-body p-3">
                <div class="form-row align-items-end mb-3">
                    <div class="col-md-5 mb-2 mb-md-0">
                        <label class="mb-1">Tìm kiếm</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                            </div>
                            <input type="text" id="qrPickerSearch" class="form-control" autocomplete="off"
                                placeholder="Mã xuất nhập, mã / tên vật tư, quy cách, định khu...">
                        </div>
                    </div>
                    <div class="col-md-3 mb-2 mb-md-0">
                        <label class="mb-1">Hạn dùng</label>
                        <select id="qrPickerExpiry" class="form-control">
                            <option value="">Tất cả</option>
                            <option value="expired">Đã hết hạn</option>
                            <option value="critical">Sát hạn (≤ {{ $qrCritical }} ngày)</option>
                            <option value="warning">Cận hạn ({{ $qrCritical + 1 }}–{{ $qrWarning }} ngày)</option>
                            <option value="ok">Còn hạn</option>
                            <option value="none">Không có hạn dùng</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2 mb-md-0">
                        <label class="mb-1">Kho</label>
                        <select id="qrPickerWarehouse" class="form-control">
                            <option value="">Tất cả kho</option>
                            @foreach ($qrWarehouses as $warehouse)
                                <option value="{{ $warehouse }}">{{ $warehouse }}</option>
                            @endforeach
                            @if ($qrHasNoLocation)
                                <option value="__none">Chưa xếp vị trí</option>
                            @endif
                        </select>
                    </div>
                    <div class="col-md-2 text-md-right">
                        <span class="badge badge-info px-2 py-2" id="qrPickerCount" style="font-size: 0.85rem;">{{ count($quarantineLots) }} mã</span>
                    </div>
                </div>

                <div class="table-responsive border rounded" style="max-height: 58vh; overflow-y: auto;">
                    <table class="table table-sm table-bordered table-hover mb-0" id="qrLotPickerTable" style="font-size: 0.88rem;" data-no-datatable>
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 45px">
                                    <input type="checkbox" id="qrPickerCheckAll" class="pick-check" title="Chọn tất cả mã đang hiện">
                                </th>
                                <th class="text-center" style="width: 45px">STT</th>
                                <th style="width: 170px">Mã Xuất Nhập</th>
                                <th style="min-width: 220px">Vật Tư</th>
                                <th style="width: 150px">Định Khu</th>
                                <th class="text-center" style="width: 100px">Ngày Nhập</th>
                                <th class="text-center" style="width: 150px">Hạn Dùng</th>
                                <th class="text-right" style="width: 140px">Còn Cách Ly Được</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($quarantineLots as $lot)
                                @php
                                    $lotExpiry = $qrExpiry($lot);
                                    $lotWarehouse = $qrWarehouse($lot);
                                @endphp
                                <tr class="qr-lot-row" data-id="{{ $lot->id }}" data-expiry="{{ $lotExpiry }}"
                                    data-warehouse="{{ $lotWarehouse !== '' ? $lotWarehouse : '__none' }}"
                                    data-search="{{ implode(' ', array_filter([$lot->code, $lot->category_code, $lot->material_name, $lot->technical_specification, $lot->location_code])) }}">
                                    <td class="text-center">
                                        <input type="checkbox" class="qr-lot-check pick-check" value="{{ $lot->id }}">
                                    </td>
                                    <td class="text-center text-muted qr-lot-no">{{ $loop->iteration }}</td>
                                    <td><span class="exp-code font-weight-bold">{{ $lot->code }}</span></td>
                                    <td>
                                        <div class="font-weight-bold">{{ $lot->material_name ?: '—' }}</div>
                                        @if ($lot->category_code)
                                            <span class="md-tag">{{ $lot->category_code }}</span>
                                        @endif
                                        @if ($lot->technical_specification)
                                            <div class="small text-muted">{{ $lot->technical_specification }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $lot->location_code ?: '—' }}</td>
                                    <td class="text-center">{{ $expDate($lot->imported_date) }}</td>
                                    <td class="text-center">
                                        @if ($lot->expired_date)
                                            <div>{{ $expDate($lot->expired_date) }}</div>
                                            @if ($lotExpiry === 'expired')
                                                <span class="badge badge-danger">Hết hạn</span>
                                            @elseif ($lotExpiry === 'critical')
                                                <span class="badge badge-danger">Sát hạn · còn {{ $lot->days_to_expiry }} ngày</span>
                                            @elseif ($lotExpiry === 'warning')
                                                <span class="badge badge-warning">Cận hạn · còn {{ $lot->days_to_expiry }} ngày</span>
                                            @endif
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="text-right font-weight-bold">{{ $expNum($lot->quarantinable) }} <span class="md-sub">{{ $lot->unit_short_name }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">Kho không còn mã xuất nhập nào còn tồn để cách ly.</td>
                                </tr>
                            @endforelse
                            <tr class="qr-lot-none" style="display: none;">
                                <td colspan="8" class="text-center text-muted py-4">Không có mã nào khớp bộ lọc.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer py-2 d-flex justify-content-between">
                <span class="font-weight-bold text-primary" id="qrPickerSelected">Đã chọn: 0 mã</span>
                <div>
                    <button type="button" class="btn btn-secondary mr-2" data-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-primary" id="qrPickerConfirm">
                        <i class="fas fa-check mr-1"></i> Dùng các mã đã chọn
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ============ SỬA PHIẾU ĐANG CÁCH LY ============ --}}
<div class="modal fade md-modal" id="qrEditModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Sửa phiếu cách ly <span class="qr-code"></span></h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form action="{{ route($qrRoute . 'update') }}" method="POST">
                @csrf
                <input type="hidden" name="id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Số lượng cách ly <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" inputmode="decimal" name="amount" class="form-control js-decimal" required>
                            <div class="input-group-append"><span class="input-group-text qr-unit">—</span></div>
                        </div>
                    </div>
                    <div class="form-group mb-0">
                        <label>Lý do cách ly <span class="text-danger">*</span></label>
                        <textarea name="reason" rows="2" maxlength="500" class="form-control" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i> Lưu</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ============ BƯỚC 2 - QUYẾT ĐỊNH ============ --}}
<div class="modal fade md-modal" id="qrDecideModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-gavel mr-2"></i>Quyết định vật tư cách ly</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form action="{{ route($qrRoute . 'decide') }}" method="POST">
                @csrf
                <input type="hidden" name="id" value="{{ old('id') }}">
                <div class="modal-body">
                    <div class="qr-summary">
                        <div><small>Phiếu cách ly</small><b class="exp-code qr-code">—</b></div>
                        <div><small>Vật tư</small><b class="qr-material">—</b></div>
                        <div><small>Mã xuất nhập</small><b class="exp-code qr-import">—</b></div>
                        <div><small>Số lượng</small><b class="qr-amount">—</b></div>
                        <div style="grid-column: 1 / -1"><small>Lý do cách ly</small><span class="qr-reason text-danger">—</span></div>
                    </div>

                    <div class="form-group">
                        <label>Quyết định <span class="text-danger">*</span></label>
                        <div class="qr-choices">
                            <label class="qr-choice is-remove">
                                <input type="radio" name="decision" value="remove" {{ old('decision') === 'remove' ? 'checked' : '' }}>
                                <span>
                                    <b class="text-danger"><i class="fas fa-trash-alt mr-1"></i> Loại bỏ</b>
                                    <small class="text-muted">Trừ tồn kho, không thể quay lại kho. Chuyển sang chờ huỷ.</small>
                                </span>
                            </label>
                            <label class="qr-choice is-return">
                                <input type="radio" name="decision" value="return" {{ old('decision') === 'return' ? 'checked' : '' }}>
                                <span>
                                    <b class="text-success"><i class="fas fa-undo mr-1"></i> Trả về kho</b>
                                    <small class="text-muted">Vật tư đạt, được cấp phát / sử dụng lại bình thường.</small>
                                </span>
                            </label>
                        </div>
                        @if ($qrDecideBag->has('decision')) <div class="md-error text-danger small">{{ $qrDecideBag->first('decision') }}</div> @endif
                    </div>

                    <div class="form-group">
                        <label>Nội dung / căn cứ quyết định <span class="text-danger">*</span></label>
                        <textarea name="decision_note" rows="2" maxlength="500" required
                            class="form-control {{ $qrDecideBag->has('decision_note') ? 'is-invalid' : '' }}"
                            placeholder="Ví dụ: Theo biên bản kiểm tra số ..., vật tư không đạt">{{ old('decision_note') }}</textarea>
                        @if ($qrDecideBag->has('decision_note')) <div class="md-error text-danger small">{{ $qrDecideBag->first('decision_note') }}</div> @endif
                    </div>

                    {{-- Thành phần thứ 2 của chữ ký điện tử - 21 CFR Part 11 §11.200 --}}
                    <div class="form-group mb-0">
                        <label>Mật khẩu xác nhận <span class="text-danger">*</span></label>
                        <input type="password" name="sign_password" autocomplete="current-password" class="form-control"
                            placeholder="Nhập lại mật khẩu đăng nhập để ký xác nhận" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-signature mr-1"></i> Ký xác nhận</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ============ BƯỚC 3 - HUỶ ============ --}}
<div class="modal fade md-modal" id="qrDisposeModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-fire mr-2"></i>Ghi nhận huỷ vật tư đã loại bỏ</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form action="{{ route($qrRoute . 'dispose') }}" method="POST">
                @csrf
                <div class="qr-dispose-ids">
                    @foreach ((array) old('ids', []) as $oldId)
                        <input type="hidden" name="ids[]" value="{{ $oldId }}">
                    @endforeach
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Phiếu được huỷ (<span class="qr-dispose-count">0</span>)</label>
                        <ul class="qr-dispose-list"></ul>
                        @if ($qrDisposeBag->has('ids')) <div class="md-error text-danger small">{{ $qrDisposeBag->first('ids') }}</div> @endif
                    </div>

                    <div class="form-group">
                        <label>Phương pháp huỷ <span class="text-danger">*</span></label>
                        <input type="text" name="destroy_method" maxlength="255" list="qrMethodList" required
                            class="form-control {{ $qrDisposeBag->has('destroy_method') ? 'is-invalid' : '' }}"
                            value="{{ old('destroy_method') }}" placeholder="Chọn gợi ý hoặc tự nhập">
                        <datalist id="qrMethodList">
                            @foreach ($qrMethods as $method)
                                <option value="{{ $method }}">
                            @endforeach
                        </datalist>
                        @if ($qrDisposeBag->has('destroy_method')) <div class="md-error text-danger small">{{ $qrDisposeBag->first('destroy_method') }}</div> @endif
                    </div>

                    <div class="form-group">
                        <label>Ghi chú</label>
                        <textarea name="destroy_note" rows="2" maxlength="500" class="form-control"
                            placeholder="Số biên bản huỷ, đơn vị thực hiện...">{{ old('destroy_note') }}</textarea>
                    </div>

                    <div class="form-group mb-0">
                        <label>Mật khẩu xác nhận <span class="text-danger">*</span></label>
                        <input type="password" name="sign_password" autocomplete="current-password" class="form-control"
                            placeholder="Nhập lại mật khẩu đăng nhập để ký xác nhận" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-signature mr-1"></i> Ký xác nhận huỷ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        /* ---------- Bước 1: bảng các mã cần cách ly ---------- */
        var qrLots = @json((object) $qrLotData->all());
        var $qrItems = $('#qrCreateItems tbody');
        var qrSeq = 0;
        var QR_EPS = 0.00005;

        function qrEsc(value) {
            return $('<div>').text(value == null ? '' : value).html();
        }

        function qrExpiryHtml(lot) {
            if (!lot.expired_date) return '—';

            var badge = '';

            if (lot.expiry === 'expired') badge = '<span class="badge badge-danger">Hết hạn</span>';
            else if (lot.expiry === 'critical') badge = '<span class="badge badge-danger">Sát hạn · còn ' + lot.days + ' ngày</span>';
            else if (lot.expiry === 'warning') badge = '<span class="badge badge-warning">Cận hạn · còn ' + lot.days + ' ngày</span>';

            return '<div>' + qrEsc(lot.expired_date) + '</div>' + badge;
        }

        function qrItemRow(id) {
            return $qrItems.find('tr.qr-item[data-id="' + id + '"]');
        }

        // Thêm một mã vào bảng - số lượng mặc định = toàn bộ phần còn cách ly được
        function qrAddItem(id, amount) {
            var lot = qrLots[String(id)];

            if (!lot) return;

            if (qrItemRow(id).length) {
                var $row = qrItemRow(id).addClass('is-flash');
                setTimeout(function () { $row.removeClass('is-flash'); }, 800);

                return;
            }

            var i = qrSeq++;
            var value = (amount !== undefined && amount !== null && amount !== '') ? amount : lot.limit;

            $qrItems.append(
                '<tr class="qr-item" data-id="' + lot.id + '">' +
                    '<td class="text-center qr-item-no"></td>' +
                    '<td><span class="exp-code font-weight-bold">' + qrEsc(lot.code) + '</span>' +
                        '<input type="hidden" name="items[' + i + '][import_id]" value="' + lot.id + '"></td>' +
                    '<td><div class="font-weight-bold">' + qrEsc(lot.name || '—') + '</div>' +
                        (lot.category_code ? '<span class="md-tag">' + qrEsc(lot.category_code) + '</span>' : '') + '</td>' +
                    '<td class="text-center">' + qrExpiryHtml(lot) + '</td>' +
                    '<td class="text-right">' + qrEsc(lot.limit_text) + ' <span class="md-sub">' + qrEsc(lot.unit) + '</span></td>' +
                    '<td><div class="input-group input-group-sm">' +
                        '<input type="text" inputmode="decimal" class="form-control js-decimal qr-item-amount" required' +
                        ' name="items[' + i + '][amount]" value="' + qrEsc(value) + '">' +
                        '<div class="input-group-append"><span class="input-group-text">' + qrEsc(lot.unit || '—') + '</span></div>' +
                    '</div></td>' +
                    '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-secondary qr-item-drop" title="Bỏ mã này">' +
                        '<i class="fas fa-times"></i></button></td>' +
                '</tr>'
            );
        }

        // Số lượng phải > 0 và không vượt phần còn cách ly được của mã đó
        function qrCheckAmount(el) {
            var $input = $(el);
            var lot = qrLots[String($input.closest('tr').attr('data-id'))];
            var amount = parseFloat($input.val());

            $input.toggleClass('is-invalid', !(amount > QR_EPS) || (!!lot && amount > lot.limit + QR_EPS));
        }

        function qrSyncItems() {
            var $rows = $qrItems.find('tr.qr-item');

            $rows.each(function (n) {
                $(this).find('.qr-item-no').text(n + 1);
                qrCheckAmount($(this).find('.qr-item-amount'));
            });

            $qrItems.find('.qr-items-empty').toggle(!$rows.length);
            $('#qrCreateSubmit').prop('disabled', !$rows.length)
                .find('.qr-create-count').text($rows.length ? '(' + $rows.length + ' mã)' : '');
        }

        $(document).on('input', '.qr-item-amount', function () {
            qrCheckAmount(this);
        });

        $(document).on('click', '.qr-item-drop', function () {
            $(this).closest('tr').remove();
            qrSyncItems();
        });

        $('#qrQuickAdd').on('change', function () {
            var id = $(this).val();

            if (!id) return;

            qrAddItem(id);
            qrSyncItems();
            $(this).val('').trigger('change');
        });

        $('#qrCreateForm').on('submit', function (e) {
            var $rows = $qrItems.find('tr.qr-item');

            $rows.find('.qr-item-amount').each(function () { qrCheckAmount(this); });

            if (!$rows.length || $rows.find('.qr-item-amount.is-invalid').length) {
                e.preventDefault();

                Swal.fire({
                    icon: 'warning',
                    title: 'Chưa cách ly được',
                    text: !$rows.length
                        ? 'Vui lòng chọn ít nhất một mã xuất nhập cần cách ly.'
                        : 'Số lượng cách ly phải lớn hơn 0 và không vượt phần còn cách ly được của mã đó.',
                    confirmButtonText: 'Đã hiểu'
                });
            }
        });

        qrSyncItems();

        /*
        | Mở modal cách ly với ĐÚNG các mã truyền vào (bỏ các dòng đang có) - dùng ở nhóm "Vật tư
        | sắp hết hạn dùng". Lý do chỉ điền sẵn khi ô lý do đang trống.
        */
        window.qrOpenCreate = function (ids, reason) {
            $qrItems.find('tr.qr-item').remove();

            (ids || []).forEach(function (id) {
                qrAddItem(id);
            });

            qrSyncItems();

            var $reason = $('#qrCreateModal [name="reason"]');

            if (reason && !$.trim($reason.val())) {
                $reason.val(reason);
            }

            $('#qrCreateModal').modal('show');
        };

        /* ---------- Danh mục tồn kho: lọc + tick chọn nhiều mã ---------- */
        var $qrPicker = $('#qrLotPickerModal');
        var $qrLotRows = $qrPicker.find('.qr-lot-row');

        // Tìm không cần gõ dấu: "bac dan" vẫn ra "Bạc đạn"
        function qrPlain(text) {
            return String(text || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/đ/g, 'd');
        }

        $qrLotRows.each(function () {
            $(this).data('plain', qrPlain($(this).attr('data-search')));
        });

        function qrPaintPicked() {
            var picked = 0;

            $qrLotRows.each(function () {
                var on = $(this).find('.qr-lot-check').prop('checked');

                $(this).toggleClass('is-checked', on);
                if (on) picked++;
            });

            var $visible = $qrLotRows.filter(':visible');

            $('#qrPickerSelected').text('Đã chọn: ' + picked + ' mã');
            $('#qrPickerCheckAll').prop('checked', $visible.length > 0 && $visible.find('.qr-lot-check:not(:checked)').length === 0);
        }

        function qrFilterLots() {
            var words = qrPlain($('#qrPickerSearch').val()).split(/\s+/).filter(Boolean);
            var expiry = $('#qrPickerExpiry').val();
            var warehouse = $('#qrPickerWarehouse').val();
            var shown = 0;

            $qrLotRows.each(function () {
                var $row = $(this);
                var plain = $row.data('plain');
                var match = (!expiry || $row.attr('data-expiry') === expiry)
                    && (!warehouse || $row.attr('data-warehouse') === warehouse)
                    && words.every(function (word) { return plain.indexOf(word) !== -1; });

                $row.toggle(match);

                if (match) $row.find('.qr-lot-no').text(++shown);
            });

            $qrPicker.find('.qr-lot-none').toggle($qrLotRows.length > 0 && shown === 0);
            $('#qrPickerCount').text(shown + ' mã');
            qrPaintPicked();
        }

        $('#qrPickerSearch').on('input', qrFilterLots);
        $('#qrPickerExpiry, #qrPickerWarehouse').on('change', qrFilterLots);

        // Bấm cả dòng cũng tick / bỏ tick
        $qrPicker.on('click', '.qr-lot-row', function (e) {
            if ($(e.target).is('.qr-lot-check')) return;

            var $check = $(this).find('.qr-lot-check');
            $check.prop('checked', !$check.prop('checked'));
            qrPaintPicked();
        });

        $qrPicker.on('change', '.qr-lot-check', qrPaintPicked);

        // Chọn tất cả: chỉ áp cho các mã đang hiện theo bộ lọc
        $('#qrPickerCheckAll').on('change', function () {
            $qrLotRows.filter(':visible').find('.qr-lot-check').prop('checked', $(this).prop('checked'));
            qrPaintPicked();
        });

        // Mở bảng: tick sẵn các mã đang có trong bảng cách ly
        $(document).on('click', '.btn-qr-lot-picker', function () {
            $qrLotRows.each(function () {
                $(this).find('.qr-lot-check').prop('checked', qrItemRow($(this).attr('data-id')).length > 0);
            });

            qrFilterLots();
            $qrPicker.modal('show');
        });

        $qrPicker.on('shown.bs.modal', function () {
            $('#qrPickerSearch').trigger('focus');
        });

        // Bảng cách ly = đúng các mã đang tick: thêm mã mới (số lượng mặc định = tồn), bỏ mã đã bỏ tick,
        // mã giữ nguyên thì giữ số lượng người dùng đã sửa
        $('#qrPickerConfirm').on('click', function () {
            var picked = {};

            $qrLotRows.find('.qr-lot-check:checked').each(function () {
                picked[String($(this).val())] = true;
            });

            $qrItems.find('tr.qr-item').each(function () {
                if (!picked[String($(this).attr('data-id'))]) $(this).remove();
            });

            $qrLotRows.find('.qr-lot-check:checked').each(function () {
                qrAddItem($(this).val());
            });

            qrSyncItems();
            $qrPicker.modal('hide');
        });

        // Bảng chồng lên modal cách ly: đóng bảng thì trả lại trạng thái cuộn cho modal bên dưới
        $qrPicker.on('hidden.bs.modal', function () {
            if ($('.modal.show').length) {
                $('body').addClass('modal-open');
            }
        });

        /* ---------- Sửa phiếu đang cách ly ---------- */
        $(document).on('click', '.btn-qr-edit', function () {
            var r = $(this).data('row') || {};
            var $m = $('#qrEditModal');

            $m.find('[name="id"]').val(r.id);
            $m.find('[name="amount"]').val(r.amount);
            $m.find('[name="reason"]').val(r.reason || '');
            $m.find('.qr-code').text(r.code || '');
            $m.find('.qr-unit').text(r.unit || '—');
            $m.modal('show');
        });

        /* ---------- Bước 2: quyết định ---------- */
        function qrPaintChoices() {
            $('#qrDecideModal .qr-choice').each(function () {
                $(this).toggleClass('is-checked', $(this).find('input').prop('checked'));
            });
        }

        $(document).on('change', '#qrDecideModal [name="decision"]', qrPaintChoices);

        function qrFillDecide(r, keepInput) {
            var $m = $('#qrDecideModal');

            $m.find('[name="id"]').val(r.id);
            $m.find('.qr-code').text(r.code || '—');
            $m.find('.qr-material').text(r.material_name || '—');
            $m.find('.qr-import').text(r.import_code || '—');
            $m.find('.qr-amount').text(r.amount_text || '—');
            $m.find('.qr-reason').text(r.reason || '—');

            if (!keepInput) {
                $m.find('[name="decision"]').prop('checked', false);
                $m.find('[name="decision_note"]').val('');
            }

            $m.find('[name="sign_password"]').val('');
            qrPaintChoices();
            $m.modal('show');
        }

        $(document).on('click', '.btn-qr-decide', function () {
            qrFillDecide($(this).data('row') || {}, false);
        });

        /* ---------- Bước 3: huỷ một hoặc nhiều phiếu ---------- */
        function qrOpenDispose(rows) {
            var $m = $('#qrDisposeModal');
            var $ids = $m.find('.qr-dispose-ids').empty();
            var $list = $m.find('.qr-dispose-list').empty();

            rows.forEach(function (r) {
                $ids.append($('<input type="hidden" name="ids[]">').val(r.id));
                $list.append(
                    $('<li>')
                        .append($('<span>').append($('<b class="exp-code">').text(r.code)).append(' — ' + (r.material_name || '')))
                        .append($('<span class="text-nowrap">').text(r.amount_text || ''))
                );
            });

            $m.find('.qr-dispose-count').text(rows.length);
            $m.find('[name="sign_password"]').val('');
            $m.modal('show');
        }

        $(document).on('click', '.btn-qr-dispose', function () {
            qrOpenDispose([$(this).data('row') || {}]);
        });

        function qrSelected() {
            return $('.qr-check:checked').map(function () { return $(this).data('row'); }).get();
        }

        function qrPaintSelected() {
            var n = $('.qr-check:checked').length;
            $('#qrDisposeSelected').prop('disabled', n === 0).find('.qr-selected-count').text(n ? '(' + n + ')' : '');
            $('#qrCheckAll').prop('checked', n > 0 && n === $('.qr-check').length);
        }

        $(document).on('change', '.qr-check', qrPaintSelected);
        $(document).on('change', '#qrCheckAll', function () {
            $('.qr-check').prop('checked', $(this).prop('checked'));
            qrPaintSelected();
        });
        $(document).on('click', '#qrDisposeSelected', function () {
            var rows = qrSelected();
            if (rows.length) qrOpenDispose(rows);
        });

        /* ---------- Lỗi validate / sai mật khẩu: mở lại đúng modal ---------- */
        @if ($qrBag->any())
            @json(array_values((array) old('items', []))).forEach(function (item) {
                if (item && item.import_id) qrAddItem(item.import_id, item.amount);
            });
            qrSyncItems();
            $('#qrCreateModal').modal('show');
        @endif

        @if ($qrDecideBag->any() || str_starts_with($qrSignError, 'Quyết định '))
            (function () {
                var oldId = @json(old('id'));
                var $btn = $('.btn-qr-decide').filter(function () {
                    return String(($(this).data('row') || {}).id) === String(oldId);
                }).first();

                if ($btn.length) qrFillDecide($btn.data('row') || {}, true);
            })();
        @endif

        @if ($qrDisposeBag->any() || $qrSignError === 'Ghi nhận huỷ vật tư')
            (function () {
                var oldIds = @json(array_map('strval', (array) old('ids', [])));
                var rows = $('.qr-check').filter(function () {
                    return oldIds.indexOf(String($(this).val())) !== -1;
                }).map(function () { return $(this).data('row'); }).get();

                if (rows.length) qrOpenDispose(rows);
                else $('#qrDisposeModal').modal('show');
            })();
        @endif
    });
</script>
