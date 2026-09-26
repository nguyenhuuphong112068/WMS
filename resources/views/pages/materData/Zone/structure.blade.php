{{--
| ĐỊNH KHU - CẤU TRÚC KHO
| Khai Kho/Phòng -> Kệ/Tủ -> Cột -> Tầng -> Vị Trí trên một lưới: chọn kho, chọn kệ, rồi mỗi
| cột là một khối lưới (hàng = tầng, ô = vị trí). Dữ liệu tải/lưu qua ZoneStructureController.
--}}
<div class="card zs" id="zoneStructure">
    <div class="zs-head">
        <nav id="zs-crumbs" class="zs-crumbs"></nav>
        <div class="zs-actions">
            <input type="search" id="zs-filter" class="form-control form-control-sm" placeholder="Lọc theo mã, tên...">
            <span id="zs-total" class="zs-total" hidden></span>
            <span id="zs-dirty" class="zs-dirty" hidden><i class="fas fa-circle"></i> Có thay đổi chưa lưu</span>
            <button type="button" id="zs-add" class="btn btn-sm btn-outline-primary"></button>
            <button type="button" id="zs-reset" class="btn btn-sm btn-outline-secondary" hidden>
                <i class="fas fa-undo mr-1"></i> Hoàn tác
            </button>
            <button type="button" id="zs-save" class="btn btn-sm btn-primary" hidden>
                <i class="fas fa-save mr-1"></i> Lưu cấu trúc
            </button>
        </div>
    </div>

    <div class="zs-body">
        <p id="zs-note" class="zs-note" hidden></p>

        {{-- Khai báo / sửa kho --}}
        <div id="zs-wh-form" class="zs-panel" hidden>
            <div class="zs-panel-title" id="zs-wh-title">Kho/Phòng mới</div>
            <div class="zs-panel-body">
                <div class="form-group mb-0">
                    <label for="zs-wh-code">Mã kho/phòng <span class="text-danger">*</span></label>
                    <input type="text" id="zs-wh-code" class="form-control form-control-sm" maxlength="40" placeholder="VD: 03">
                </div>
                <div class="form-group mb-0 zs-grow">
                    <label for="zs-wh-name">Tên kho/phòng <span class="text-danger">*</span></label>
                    <input type="text" id="zs-wh-name" class="form-control form-control-sm" maxlength="255" placeholder="VD: Kho 03">
                </div>
                <div class="zs-panel-actions">
                    <button type="button" id="zs-wh-cancel" class="btn btn-sm btn-outline-secondary">Huỷ</button>
                    <button type="button" id="zs-wh-save" class="btn btn-sm btn-primary">
                        <i class="fas fa-save mr-1"></i> Lưu kho/phòng
                    </button>
                </div>
            </div>
        </div>

        <div id="zs-warehouses" class="zs-cards" hidden></div>
        <div id="zs-shelves" class="zs-cards" hidden></div>

        {{-- Trình sửa một kệ/tủ --}}
        <div id="zs-editor" hidden>
            <div class="zs-legend">
                <span><i class="zs-swatch is-busy"></i> Có lô hàng</span>
                <span><i class="zs-swatch is-free"></i> Trống</span>
                <span><i class="zs-swatch is-off"></i> Đang khoá</span>
                <span><i class="zs-swatch is-new"></i> Sẽ tạo</span>
                <span><i class="zs-swatch is-cut"></i> Sẽ khoá</span>
            </div>

            <section class="zs-shelf">
                <div class="zs-shelf-title"><i class="fas fa-layer-group"></i> <span id="zs-shelf-title">Cấu trúc kệ/tủ</span></div>
                <div class="zs-form">
                    <div class="form-group mb-0">
                        <label for="zs-code">Mã kệ/tủ <span class="text-danger">*</span></label>
                        <input type="text" id="zs-code" class="form-control form-control-sm" maxlength="40" placeholder="VD: 01/05">
                    </div>
                    <div class="form-group mb-0 zs-grow">
                        <label for="zs-name">Tên kệ/tủ <span class="text-danger">*</span></label>
                        <input type="text" id="zs-name" class="form-control form-control-sm" maxlength="255" placeholder="VD: Kệ/Tủ 05">
                    </div>
                    <div class="form-group mb-0">
                        <label for="zs-item-type">Loại lưu trữ cho vị trí mới</label>
                        <select id="zs-item-type" class="form-control form-control-sm">
                            <option value="">Dùng chung</option>
                            @foreach ($locationTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="zs-preview" class="zs-preview" hidden></div>
                </div>

                {{-- Lưới chọn nhanh kiểu chèn bảng của Word, chỉ hiện khi kệ chưa có cột nào --}}
                <div id="zs-picker" class="zs-picker" hidden>
                    <div class="zs-picker-title">
                        Rê chuột chọn số tầng (dọc) × số vị trí mỗi tầng (ngang) cho mỗi cột, rồi bấm để xác nhận.
                    </div>
                    <div class="zs-picker-row">
                        <div id="zs-picker-grid" class="zs-picker-grid"></div>
                        <div class="zs-picker-side">
                            <label for="zs-picker-cols" class="mb-1">Số cột</label>
                            <input type="number" id="zs-picker-cols" class="form-control form-control-sm" min="1" value="1">
                            <span id="zs-picker-readout" class="zs-picker-readout">Chưa chọn</span>
                        </div>
                    </div>
                </div>
            </section>

            <div id="zs-columns"></div>

            <div id="zs-foot" class="zs-foot" hidden>
                <button type="button" id="zs-add-column" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-plus mr-1"></i> Thêm cột
                </button>
                <span id="zs-summary" class="zs-summary"></span>
            </div>
        </div>
    </div>
</div>

<style>
    .zs {
        overflow: hidden;
    }

    .zs-head {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: center;
        justify-content: space-between;
        padding: 14px 20px;
        border-bottom: 1px solid var(--primary-soft);
        background: #fff;
    }

    .zs-body {
        padding: 20px;
    }

    .zs-crumbs {
        display: flex;
        gap: 6px;
        align-items: center;
        flex-wrap: wrap;
        font-size: 0.92rem;
    }

    .zs-crumb {
        border: none;
        background: none;
        padding: 0;
        color: var(--primary);
        cursor: pointer;
        font-weight: 600;
        transition: color 0.2s;
    }

    .zs-crumb:hover {
        color: var(--primary-dark);
        text-decoration: underline;
    }

    .zs-crumb.is-current {
        color: var(--text-main);
        font-weight: 700;
        cursor: default;
        text-decoration: none;
    }

    .zs-crumb-sep {
        color: var(--primary-lighter);
    }

    .zs-actions {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-wrap: wrap;
    }

    .zs-actions input[type="search"] {
        width: 190px;
        border-radius: var(--border-radius-md);
    }

    .zs-actions .btn {
        border-radius: var(--border-radius-md);
        transition: all 0.2s;
    }

    .zs-actions .btn-primary:hover {
        transform: translateY(-1px);
    }

    .zs-dirty {
        color: #B45309;
        font-size: 0.78rem;
        font-weight: 600;
    }

    .zs-dirty i {
        font-size: 0.5rem;
        vertical-align: 2px;
    }

    .zs-total {
        font-size: 0.78rem;
        font-weight: 700;
        color: var(--primary);
        background: var(--primary-soft);
        border-radius: 999px;
        padding: 4px 12px;
    }

    .zs-note {
        color: #64748b;
        margin: 4px 0 12px;
    }

    .zs-grow {
        flex: 1 1 260px;
    }

    /* ---------- Khung khai báo kho ---------- */
    .zs-panel {
        border: 1px solid var(--primary-lighter);
        background: var(--bg-neutral);
        border-radius: var(--border-radius-lg);
        padding: 16px 18px;
        margin-bottom: 20px;
    }

    .zs-panel-title,
    .zs-shelf-title {
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 10px;
    }

    .zs-panel-body {
        display: flex;
        gap: 14px;
        flex-wrap: wrap;
        align-items: flex-end;
    }

    .zs-panel-body .form-group {
        min-width: 200px;
    }

    .zs-panel-actions {
        display: flex;
        gap: 8px;
    }

    /* ---------- Thẻ Kho / Kệ ---------- */
    .zs-cards {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 18px;
    }

    .zs-tile {
        position: relative;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: var(--border-radius-lg);
        padding: 20px 20px 16px;
        display: flex;
        flex-direction: column;
        cursor: pointer;
        box-shadow: var(--shadow-sm);
        transition: all 0.25s ease;
        overflow: hidden;
        user-select: none;
    }

    .zs-tile::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--tile, var(--primary-lighter));
    }

    .zs-tile:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow-md);
        border-color: var(--primary-light);
    }

    .zs-tile:hover .zs-tile-arrow {
        color: var(--primary);
    }

    .zs-tile:hover .zs-tile-arrow i {
        transform: translateX(3px);
    }

    .zs-tile.is-off {
        opacity: 0.6;
    }

    .zs-tile-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 8px;
    }

    .zs-tile-code-wrap {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
    }

    .zs-tile-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: var(--primary-soft);
        color: var(--primary);
        font-size: 1.05rem;
        flex-shrink: 0;
    }

    .zs-tile-code {
        font-weight: 800;
        font-size: 1.25rem;
        color: var(--text-main);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .zs-tile-tools {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        flex-shrink: 0;
    }

    .zs-tile-badge {
        font-size: 0.74rem;
        font-weight: 700;
        padding: 4px 10px;
        border-radius: 999px;
        background: var(--primary-soft);
        color: var(--primary);
        white-space: nowrap;
    }

    .zs-tile-edit {
        border: 1px solid #e2e8f0;
        background: var(--bg-neutral);
        color: #64748b;
        border-radius: 8px;
        width: 28px;
        height: 28px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .zs-tile-edit:hover {
        background: var(--primary-soft);
        border-color: var(--primary-lighter);
        color: var(--primary);
    }

    .zs-tile-name {
        font-weight: 600;
        color: #334155;
        margin-bottom: 12px;
        min-height: 1.5rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .zs-tile-bar {
        width: 100%;
        height: 7px;
        background: #f1f5f9;
        border-radius: 999px;
        overflow: hidden;
        margin-bottom: 12px;
    }

    .zs-tile-bar span {
        display: block;
        height: 100%;
        border-radius: 999px;
        background: var(--tile, var(--primary));
        transition: width 0.4s ease;
    }

    .zs-tile-foot {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        padding-top: 10px;
        border-top: 1px solid #f1f5f9;
        font-size: 0.8rem;
        color: #64748b;
    }

    .zs-tile-foot strong {
        color: var(--text-main);
    }

    .zs-tile-arrow {
        font-weight: 600;
        white-space: nowrap;
        transition: color 0.2s;
    }

    .zs-tile-arrow i {
        font-size: 0.7rem;
        transition: transform 0.2s;
    }

    .zs-empty {
        grid-column: 1 / -1;
        text-align: center;
        color: #94a3b8;
        padding: 36px 12px;
        border: 1px dashed var(--primary-lighter);
        border-radius: var(--border-radius-lg);
        background: var(--bg-neutral);
    }

    /* ---------- Trình sửa kệ ---------- */
    .zs-legend {
        display: flex;
        gap: 16px;
        flex-wrap: wrap;
        font-size: 0.78rem;
        color: #475569;
        margin-bottom: 14px;
    }

    .zs-swatch {
        display: inline-block;
        width: 12px;
        height: 12px;
        border-radius: 3px;
        vertical-align: -1px;
        margin-right: 4px;
    }

    .zs-shelf,
    .zs-col {
        border: 1px solid #e2e8f0;
        border-radius: var(--border-radius-lg);
        background: #fff;
        padding: 16px 18px 14px;
        margin-bottom: 16px;
        box-shadow: var(--shadow-sm);
    }

    .zs-form {
        display: flex;
        gap: 14px;
        flex-wrap: wrap;
    }

    .zs-form .form-group {
        min-width: 180px;
    }

    .zs-form label,
    .zs-panel label {
        font-size: 0.82rem;
        font-weight: 600;
        color: var(--text-main);
    }

    .zs-form input[readonly] {
        background: var(--bg-neutral);
        color: #64748b;
    }

    .zs-preview {
        flex: 1 1 100%;
        font-size: 0.8rem;
        color: #475569;
        background: var(--primary-soft);
        border-radius: var(--border-radius-md);
        padding: 8px 12px;
    }

    .zs-preview.is-bad {
        background: #FEF2F2;
        color: #B91C1C;
    }

    .zs-preview b {
        color: var(--primary-dark);
    }

    .zs-preview.is-bad b {
        color: #B91C1C;
    }

    .zs-picker {
        margin-top: 16px;
    }

    .zs-picker-title {
        font-size: 0.82rem;
        color: #475569;
        margin-bottom: 8px;
    }

    .zs-picker-row {
        display: flex;
        gap: 18px;
        flex-wrap: wrap;
        align-items: flex-start;
    }

    .zs-picker-grid {
        display: grid;
        gap: 2px;
        grid-template-columns: repeat(var(--cols), 14px);
        width: max-content;
        max-width: 100%;
        overflow-x: auto;
        padding: 6px;
        border: 1px solid #dee2e6;
        border-radius: var(--border-radius-md);
        background: #fff;
    }

    .zs-pcell {
        width: 14px;
        height: 14px;
        border: 1px solid #cbd5e1;
        border-radius: 2px;
        background: #f8fafc;
        cursor: pointer;
    }

    .zs-pcell.is-on {
        background: var(--primary-lighter);
        border-color: var(--primary);
    }

    .zs-picker-side {
        display: flex;
        flex-direction: column;
        width: 150px;
        font-size: 0.82rem;
        font-weight: 600;
    }

    .zs-picker-readout {
        margin-top: 10px;
        color: var(--primary);
        font-weight: 700;
    }

    .zs-col.is-new {
        border-left: 4px solid #16A34A;
    }

    .zs-col.is-removed {
        border-left: 4px solid #DC2626;
        background: #FFFBFB;
    }

    .zs-col-head {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 10px;
    }

    .zs-col-title {
        font-weight: 700;
        color: var(--text-main);
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .zs-col-title > i {
        color: var(--primary);
    }

    .zs-code {
        font-family: Consolas, monospace;
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--primary-dark);
        background: var(--primary-soft);
        border-radius: 6px;
        padding: 2px 8px;
    }

    .zs-col-title small {
        font-weight: 500;
        color: #64748b;
    }

    .zs-col.is-removed .zs-col-title small {
        color: #DC2626;
        font-weight: 600;
    }

    .zs-col-tools {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .zs-bulk {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.8rem;
        font-weight: 600;
        color: #475569;
    }

    .zs-bulk input {
        width: 76px;
    }

    .zs-col-tools .btn {
        border-radius: var(--border-radius-md);
    }

    .zs-grid {
        overflow-x: auto;
        padding-bottom: 6px;
    }

    .zs-row {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 3px;
    }

    .zs-row-label {
        width: 70px;
        flex: none;
        font-size: 0.76rem;
        text-align: right;
        color: #334155;
        white-space: nowrap;
    }

    .zs-cells {
        display: grid;
        grid-template-columns: repeat(var(--cols), var(--zs-cell));
        gap: 2px;
        flex: none;
    }

    .zs-cell {
        height: var(--zs-cell);
        border-radius: 3px;
        border: 1px solid transparent;
    }

    .zs-cell.is-free,
    .zs-swatch.is-free {
        background: #EEF2F6;
        border-color: #D6DCE4;
    }

    .zs-cell.is-busy,
    .zs-swatch.is-busy {
        background: var(--primary);
        border-color: var(--primary-dark);
    }

    .zs-cell.is-off,
    .zs-swatch.is-off {
        background: repeating-linear-gradient(45deg, #CFD4D9, #CFD4D9 3px, #B9BFC5 3px, #B9BFC5 6px);
        border-color: #AEB4BA;
    }

    .zs-cell.is-new,
    .zs-swatch.is-new {
        background: #DCFCE7;
        border: 1px dashed #16A34A;
    }

    .zs-cell.is-cut,
    .zs-swatch.is-cut {
        background: #FEE2E2;
        border-color: #F87171;
    }

    .zs-handle {
        flex: none;
        width: 10px;
        height: 22px;
        border: 1px solid #94a3b8;
        border-radius: 3px;
        background: #f1f5f9;
        cursor: ew-resize;
        padding: 0;
        touch-action: none;
        transition: background 0.2s;
    }

    .zs-handle:hover,
    .zs-handle.is-dragging {
        background: var(--primary);
        border-color: var(--primary-dark);
    }

    .zs-num {
        flex: none;
        width: 62px;
        font-size: 0.76rem;
        padding: 1px 4px;
        border: 1px solid #cbd5e1;
        border-radius: 4px;
    }

    .zs-drop {
        flex: none;
        border: none;
        background: transparent;
        color: #94a3b8;
        cursor: pointer;
        padding: 0 4px;
        transition: color 0.2s;
    }

    .zs-drop:hover {
        color: #DC2626;
    }

    .zs-row.is-removed .zs-row-label,
    .zs-row.is-removed .zs-num {
        text-decoration: line-through;
        opacity: 0.6;
    }

    .zs-col-foot {
        margin-top: 10px;
    }

    .zs-col-off {
        font-size: 0.82rem;
        color: #B91C1C;
    }

    .zs-foot {
        display: flex;
        gap: 14px;
        align-items: center;
        flex-wrap: wrap;
    }

    .zs-summary {
        font-size: 0.85rem;
        font-weight: 600;
        color: #475569;
    }
</style>

<script type="application/json" id="zs-config">
{
    "warehouses": @json(route('pages.materData.zone.structure.warehouses')),
    "saveWarehouse": @json(route('pages.materData.zone.structure.saveWarehouse')),
    "shelves": @json(route('pages.materData.zone.structure.shelves')),
    "detail": @json(route('pages.materData.zone.structure.detail')),
    "checkCode": @json(route('pages.materData.zone.structure.checkCode')),
    "apply": @json(route('pages.materData.zone.structure.apply')),
    "maxColumns": {{ (int) $structureLimits['maxColumns'] }},
    "maxTiers": {{ (int) $structureLimits['maxTiers'] }},
    "maxLocations": {{ (int) $structureLimits['maxLocations'] }},
    "canCreate": @json(user_can('materData_common_create')),
    "canUpdate": @json(user_can('materData_common_update')),
    "csrf": @json(csrf_token())
}
</script>

@verbatim
<script>
(function () {
    'use strict';

    var CFG = JSON.parse(document.getElementById('zs-config').textContent);

    function byId(id) {
        return document.getElementById(id);
    }

    var el = {
        crumbs: byId('zs-crumbs'),
        filter: byId('zs-filter'),
        total: byId('zs-total'),
        dirty: byId('zs-dirty'),
        add: byId('zs-add'),
        reset: byId('zs-reset'),
        save: byId('zs-save'),
        note: byId('zs-note'),
        whForm: byId('zs-wh-form'),
        whTitle: byId('zs-wh-title'),
        whCode: byId('zs-wh-code'),
        whName: byId('zs-wh-name'),
        whSave: byId('zs-wh-save'),
        whCancel: byId('zs-wh-cancel'),
        warehouses: byId('zs-warehouses'),
        shelves: byId('zs-shelves'),
        editor: byId('zs-editor'),
        shelfTitle: byId('zs-shelf-title'),
        code: byId('zs-code'),
        name: byId('zs-name'),
        itemType: byId('zs-item-type'),
        preview: byId('zs-preview'),
        picker: byId('zs-picker'),
        pickerGrid: byId('zs-picker-grid'),
        pickerCols: byId('zs-picker-cols'),
        readout: byId('zs-picker-readout'),
        columns: byId('zs-columns'),
        foot: byId('zs-foot'),
        addColumn: byId('zs-add-column'),
        summary: byId('zs-summary')
    };

    el.pickerCols.max = CFG.maxColumns;

    /*
     | view: 'warehouses' | 'shelves' | 'editor'
     | shelf: kệ đang sửa (null = đang tạo kệ mới)
     | columns: [{ position, existing, removed, wasRemoved, code, label, tiers: [...] }]
     | tier:    { position, max, origMax, lastBusy, busy:Set, off:Set, codes:{}, existing, removed, wasRemoved, label }
    */
    var state = {
        view: 'warehouses',
        warehouses: [], shelves: [], suggest: '',
        warehouse: null, shelf: null,
        columns: [], dirty: false, editingWarehouse: null,
        codeCheck: null, codeTimer: null, nameTouched: false
    };

    // Trang còn tab "Danh sách định khu" do máy chủ dựng sẵn; lưu xong thì đánh dấu để tab đó tải lại.
    window.zoneStructureStale = false;

    var TITLES = { success: 'Đã lưu', warning: 'Chưa làm được', error: 'Không thực hiện được', info: 'Thông tin' };

    function esc(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (ch) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
        });
    }

    function num(value) {
        return Number(value || 0).toLocaleString('vi-VN');
    }

    function seg(position) {
        return String(position).padStart(2, '0');
    }

    function toast(icon, text) {
        Swal.fire({
            icon: icon,
            title: TITLES[icon] || '',
            text: text,
            timer: icon === 'success' ? 2600 : undefined,
            showConfirmButton: icon !== 'success',
            confirmButtonColor: '#2E7BC4'
        });
    }

    function setDirty(value) {
        state.dirty = value;
        el.dirty.hidden = !value;
    }

    function confirmLeave() {
        return !state.dirty || window.confirm('Bỏ các thay đổi chưa lưu?');
    }

    function getJson(url) {
        return fetch(url, { headers: { 'Accept': 'application/json' } }).then(function (r) {
            return r.json().then(function (data) {
                if (!r.ok) throw new Error(data.message || 'Không tải được dữ liệu.');
                return data;
            });
        });
    }

    function post(url, payload) {
        return fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CFG.csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
            .then(function (response) {
                return response.json().then(function (data) { return { ok: response.ok, data: data }; });
            })
            .catch(function () {
                return { ok: false, data: { message: 'Không kết nối được máy chủ.' } };
            });
    }

    /* ---------- thẻ Kho / Kệ ---------- */

    // Mức lấp đầy tô viền trên của thẻ: trống -> xanh nhạt, đầy -> đỏ.
    function fillColor(pct, total) {
        if (!total || pct <= 0) return '#9CC7EE';
        if (pct <= 50) return '#2E7BC4';
        if (pct <= 75) return '#17B8D4';
        if (pct < 100) return '#F59E0B';
        return '#DC2626';
    }

    function tileMarkup(item, options) {
        var used = Number(item.used) || 0;
        var total = Number(item.total) || 0;
        var pct = total ? Math.round(used / total * 100) : 0;
        var off = Number(item.status_id) !== 1;
        var color = fillColor(pct, total);

        return '<div class="zs-tile' + (off ? ' is-off' : '') + '" data-id="' + item.id + '" style="--tile:' + color + '"'
            + ' title="' + esc(item.name || item.code) + ' - ' + num(used) + '/' + num(total) + ' vị trí có lô hàng">'
            + '<div class="zs-tile-top">'
            +   '<div class="zs-tile-code-wrap">'
            +     '<span class="zs-tile-icon"><i class="fas ' + options.icon + '"></i></span>'
            +     '<span class="zs-tile-code">' + esc(item.code) + '</span>'
            +   '</div>'
            +   '<div class="zs-tile-tools">'
            +     '<span class="zs-tile-badge">' + (total ? pct + '% có hàng' : 'Chưa có ô') + '</span>'
            +     (options.editable ? '<button type="button" class="zs-tile-edit" data-edit="' + item.id + '" title="Sửa mã và tên"><i class="fas fa-pen"></i></button>' : '')
            +   '</div>'
            + '</div>'
            + '<div class="zs-tile-name">' + esc(item.name) + (off ? ' (đã khoá)' : '') + '</div>'
            + '<div class="zs-tile-bar"><span style="width:' + Math.min(100, Math.max(pct, pct > 0 ? 3 : 0)) + '%"></span></div>'
            + '<div class="zs-tile-foot">'
            +   '<span><strong>' + options.meta + '</strong></span>'
            +   '<span class="zs-tile-arrow">' + options.action + ' <i class="fas fa-chevron-right ml-1"></i></span>'
            + '</div>'
            + '</div>';
    }

    function matches(item) {
        var keyword = el.filter.value.trim().toLowerCase();
        return !keyword
            || String(item.code).toLowerCase().indexOf(keyword) >= 0
            || String(item.name || '').toLowerCase().indexOf(keyword) >= 0;
    }

    /* ---------- điều hướng ---------- */

    function show(view) {
        state.view = view;
        var isEditor = view === 'editor';

        el.warehouses.hidden = view !== 'warehouses';
        el.shelves.hidden = view !== 'shelves';
        el.editor.hidden = !isEditor;
        el.save.hidden = !isEditor;
        el.reset.hidden = !isEditor;
        el.total.hidden = !isEditor;
        el.filter.hidden = isEditor;
        el.filter.value = '';
        el.note.hidden = true;

        el.add.hidden = isEditor || !CFG.canCreate;
        el.add.innerHTML = '<i class="fas fa-plus mr-1"></i> ' + (view === 'warehouses' ? 'Kho/Phòng mới' : 'Kệ/Tủ mới');

        if (!isEditor) {
            state.columns = [];
            el.columns.innerHTML = '';
            setDirty(false);
        }

        hideWarehouseForm();
        renderCrumbs();
    }

    function renderCrumbs() {
        var parts = ['<button type="button" class="zs-crumb' + (state.view === 'warehouses' ? ' is-current' : '')
            + '" data-go="warehouses"><i class="fas fa-warehouse mr-1"></i>Kho/Phòng</button>'];

        if (state.warehouse) {
            parts.push('<span class="zs-crumb-sep">/</span>');
            parts.push('<button type="button" class="zs-crumb' + (state.view === 'shelves' ? ' is-current' : '')
                + '" data-go="shelves">' + esc(state.warehouse.code) + ' - ' + esc(state.warehouse.name) + '</button>');
        }

        if (state.view === 'editor') {
            parts.push('<span class="zs-crumb-sep">/</span>');
            parts.push('<button type="button" class="zs-crumb is-current">'
                + (state.shelf ? esc(state.shelf.code) : 'Kệ/Tủ mới') + '</button>');
        }

        el.crumbs.innerHTML = parts.join(' ');
    }

    el.crumbs.addEventListener('click', function (event) {
        var crumb = event.target.closest('.zs-crumb[data-go]');
        if (!crumb || crumb.classList.contains('is-current') || !confirmLeave()) return;

        if (crumb.dataset.go === 'warehouses') {
            state.warehouse = null;
            show('warehouses');
            loadWarehouses();
        } else {
            show('shelves');
            loadShelves();
        }
    });

    el.filter.addEventListener('input', function () {
        if (state.view === 'warehouses') renderWarehouses();
        else if (state.view === 'shelves') renderShelves();
    });

    el.add.addEventListener('click', function () {
        if (state.view === 'warehouses') openWarehouseForm(null);
        else if (state.view === 'shelves') startCreate(null);
    });

    /* ---------- cấp 1: kho/phòng ---------- */

    function loadWarehouses() {
        el.warehouses.innerHTML = '<p class="zs-note">Đang tải danh sách kho/phòng...</p>';

        return getJson(CFG.warehouses)
            .then(function (data) {
                state.warehouses = data.warehouses;
                renderWarehouses();
            })
            .catch(function (error) {
                el.warehouses.innerHTML = '<p class="zs-note">' + esc(error.message) + '</p>';
            });
    }

    function renderWarehouses() {
        if (!state.warehouses.length) {
            el.warehouses.innerHTML = '<div class="zs-empty">Phòng ban này chưa có kho/phòng nào.</div>';
            return;
        }

        var list = state.warehouses.filter(matches);
        if (!list.length) {
            el.warehouses.innerHTML = '<div class="zs-empty">Không có kho/phòng nào khớp "' + esc(el.filter.value) + '".</div>';
            return;
        }

        el.warehouses.innerHTML = list.map(function (warehouse) {
            return tileMarkup(warehouse, {
                icon: 'fa-warehouse',
                meta: num(warehouse.shelves) + ' kệ/tủ · ' + num(warehouse.total) + ' vị trí',
                action: 'Xem kệ/tủ',
                editable: CFG.canUpdate
            });
        }).join('');
    }

    el.warehouses.addEventListener('click', function (event) {
        var edit = event.target.closest('.zs-tile-edit');
        if (edit) {
            openWarehouseForm(state.warehouses.find(function (w) { return String(w.id) === edit.dataset.edit; }));
            return;
        }

        var card = event.target.closest('.zs-tile');
        if (!card) return;

        state.warehouse = state.warehouses.find(function (w) { return String(w.id) === card.dataset.id; });
        show('shelves');
        loadShelves();
    });

    function openWarehouseForm(warehouse) {
        state.editingWarehouse = warehouse || null;
        el.whTitle.textContent = warehouse ? 'Sửa kho/phòng ' + warehouse.code : 'Kho/Phòng mới';
        el.whCode.value = warehouse ? warehouse.code : '';
        el.whName.value = warehouse ? warehouse.name : '';
        el.whForm.hidden = false;
        el.whCode.focus();
    }

    function hideWarehouseForm() {
        el.whForm.hidden = true;
        state.editingWarehouse = null;
    }

    el.whCancel.addEventListener('click', hideWarehouseForm);

    el.whSave.addEventListener('click', function () {
        var code = el.whCode.value.trim();
        var name = el.whName.value.trim();

        if (!code || !name) {
            toast('warning', 'Nhập mã và tên kho/phòng trước khi lưu.');
            return;
        }

        el.whSave.disabled = true;
        post(CFG.saveWarehouse, {
            id: state.editingWarehouse ? state.editingWarehouse.id : null,
            code: code,
            name: name
        }).then(function (result) {
            el.whSave.disabled = false;
            if (!result.ok) {
                toast('error', result.data.message || 'Không lưu được kho/phòng.');
                return;
            }

            window.zoneStructureStale = true;
            toast('success', result.data.message);
            hideWarehouseForm();
            loadWarehouses();
        });
    });

    /* ---------- cấp 2: kệ/tủ ---------- */

    function loadShelves() {
        el.shelves.innerHTML = '<p class="zs-note">Đang tải danh sách kệ/tủ...</p>';

        return getJson(CFG.shelves + '?warehouse_id=' + encodeURIComponent(state.warehouse.id))
            .then(function (data) {
                state.shelves = data.shelves;
                state.suggest = data.suggest || '';
                renderShelves();
            })
            .catch(function (error) {
                el.shelves.innerHTML = '<p class="zs-note">' + esc(error.message) + '</p>';
            });
    }

    function renderShelves() {
        if (!state.shelves.length) {
            el.shelves.innerHTML = '<div class="zs-empty">Kho/phòng này chưa có kệ/tủ nào.</div>';
            return;
        }

        var list = state.shelves.filter(matches);
        if (!list.length) {
            el.shelves.innerHTML = '<div class="zs-empty">Không có kệ/tủ nào khớp "' + esc(el.filter.value) + '".</div>';
            return;
        }

        el.shelves.innerHTML = list.map(function (shelf) {
            return tileMarkup(shelf, {
                icon: 'fa-layer-group',
                meta: num(shelf.columns) + ' cột · ' + num(shelf.tiers) + ' tầng · ' + num(shelf.total) + ' vị trí',
                action: 'Sửa cấu trúc',
                editable: false
            });
        }).join('');
    }

    el.shelves.addEventListener('click', function (event) {
        var card = event.target.closest('.zs-tile');
        if (card) loadShelf(card.dataset.id);
    });

    /* ---------- cấp 3: một kệ/tủ ---------- */

    function newTier(position, max) {
        return {
            position: position, max: max, origMax: 0, lastBusy: 0,
            busy: new Set(), off: new Set(), codes: {},
            existing: false, removed: false, wasRemoved: false,
            label: 'Tầng ' + seg(position)
        };
    }

    function newColumn(position, template) {
        var tiers = (template || []).map(function (tier) { return newTier(tier.position, tier.max); });
        return {
            position: position, existing: false, removed: false, wasRemoved: false,
            code: null, label: 'Cột ' + seg(position), tiers: tiers
        };
    }

    function shelfCode() {
        return state.shelf ? state.shelf.code : el.code.value.trim();
    }

    function columnCode(column) {
        return column.code || (shelfCode() || '?') + '/' + seg(column.position);
    }

    function tierCode(column, tier) {
        return tier.code || columnCode(column) + '/' + seg(tier.position);
    }

    function activeTiers(column) {
        return column.tiers.filter(function (tier) { return !tier.removed; });
    }

    function columnCells(column) {
        if (column.removed) return 0;
        return activeTiers(column).reduce(function (sum, tier) { return sum + tier.max; }, 0);
    }

    function maxPosition(list) {
        return list.reduce(function (max, item) { return Math.max(max, item.position); }, 0);
    }

    // Mẫu tầng của cột còn dùng cuối cùng, để cột mới thêm vào giống cột trước.
    function templateOf(column) {
        return column ? activeTiers(column).map(function (tier) { return { position: tier.position, max: tier.max }; }) : [];
    }

    function startCreate(template) {
        if (!confirmLeave()) return;

        state.shelf = null;
        show('editor');
        el.shelfTitle.textContent = 'Kệ/Tủ mới trong ' + state.warehouse.code;
        el.code.readOnly = false;
        el.code.value = state.suggest || '';
        el.itemType.value = template ? el.itemType.value : '';
        state.nameTouched = false;
        syncName();

        if (template && template.length) {
            state.columns = template.map(function (tiers, index) { return newColumn(index + 1, tiers); });
            el.picker.hidden = true;
            renderColumns();
            setDirty(true);
        } else {
            state.columns = [];
            openPicker();
            renderColumns();
            setDirty(false);
        }

        refreshCodeCheck();
        renderCrumbs();
        el.code.focus();
    }

    function loadShelf(shelfId) {
        if (!confirmLeave()) return;

        state.shelf = null;
        show('editor');
        el.columns.innerHTML = '';
        el.picker.hidden = true;
        el.foot.hidden = true;
        el.note.textContent = 'Đang tải cấu trúc kệ/tủ...';
        el.note.hidden = false;

        getJson(CFG.detail + '?shelf_id=' + encodeURIComponent(shelfId))
            .then(function (data) {
                state.shelf = data.shelf;
                el.shelfTitle.textContent = 'Cấu trúc kệ/tủ ' + data.shelf.code;
                el.code.value = data.shelf.code;
                el.code.readOnly = true;
                el.name.value = data.shelf.name;
                el.itemType.value = '';
                el.preview.hidden = true;

                state.columns = data.columns.map(function (column) {
                    var removed = Number(column.status_id) !== 1;
                    return {
                        position: Number(column.position),
                        existing: true, removed: removed, wasRemoved: removed,
                        code: column.code, label: column.name || ('Cột ' + seg(column.position)),
                        tiers: column.tiers.map(function (tier) {
                            var off = Number(tier.status_id) !== 1;
                            var codes = tier.codes || {};
                            return {
                                position: Number(tier.position),
                                // Tầng đang khoá hết ô thì sức chứa hiện tại là 0; lấy số ô đã có
                                // làm mức gợi ý để khi "Dùng lại" là mở lại đủ các ô cũ.
                                max: Number(tier.max) || Object.keys(codes).length || 1,
                                origMax: Number(tier.max) || 0,
                                lastBusy: Number(tier.last_busy) || 0,
                                busy: new Set(tier.busy || []),
                                off: new Set(tier.off || []),
                                codes: codes,
                                code: tier.code,
                                existing: true, removed: off, wasRemoved: off,
                                label: tier.name || ('Tầng ' + seg(tier.position))
                            };
                        })
                    };
                });

                el.note.hidden = true;
                if (!state.columns.length) openPicker();
                renderColumns();
                renderCrumbs();
                setDirty(false);
            })
            .catch(function (error) {
                el.note.textContent = error.message || 'Không tải được cấu trúc kệ/tủ.';
            });
    }

    /* tên kệ tự đi theo mã cho tới khi người dùng tự sửa tên */

    function syncName() {
        if (state.shelf || state.nameTouched) return;
        var code = el.code.value.trim();
        var tail = code.split('/').pop();
        el.name.value = code ? 'Kệ/Tủ ' + tail : '';
    }

    el.name.addEventListener('input', function () {
        state.nameTouched = true;
        setDirty(true);
    });

    el.code.addEventListener('input', function () {
        if (state.shelf) return;
        syncName();
        setDirty(true);
        refreshCodeCheck();
        renderColumns();
    });

    el.itemType.addEventListener('change', function () { setDirty(true); });

    // Kết quả máy chủ có thể là của lần gõ trước, nên chỉ tin khi khớp đúng mã đang nhập.
    function codeTaken() {
        return !!state.codeCheck && state.codeCheck.code === el.code.value.trim() && state.codeCheck.taken;
    }

    function paintPreview() {
        if (state.shelf) {
            el.preview.hidden = true;
            return;
        }

        var code = el.code.value.trim();
        el.preview.hidden = false;
        el.preview.className = 'zs-preview';

        if (!code) {
            el.preview.textContent = 'Nhập mã kệ/tủ - mã cột, tầng, vị trí sẽ tự sinh theo mã này.';
            return;
        }

        if (codeTaken()) {
            el.preview.className = 'zs-preview is-bad';
            el.preview.innerHTML = 'Mã <b>' + esc(code) + '</b> đã tồn tại. Hãy đổi mã kệ/tủ.';
            return;
        }

        el.preview.innerHTML = 'Sẽ tạo kệ/tủ <b>' + esc(code) + '</b> · cột <b>' + esc(code) + '/01</b>'
            + ' · tầng <b>' + esc(code) + '/01/01</b> · vị trí <b>' + esc(code) + '/01/01/01</b>...';
    }

    function refreshCodeCheck() {
        paintPreview();
        clearTimeout(state.codeTimer);

        var code = el.code.value.trim();
        if (state.shelf || !code) return;

        state.codeTimer = setTimeout(function () {
            getJson(CFG.checkCode + '?code=' + encodeURIComponent(code)).then(function (data) {
                state.codeCheck = data;
                paintPreview();
            }).catch(function () { /* bỏ qua, máy chủ sẽ kiểm lại khi lưu */ });
        }, 250);
    }

    /* lưới chọn nhanh kiểu Word */

    var PICKER_MAX_ROWS = Math.min(CFG.maxTiers, 20);
    var PICKER_MAX_COLS = Math.min(CFG.maxLocations, 60);
    var pickerRows = 6;
    var pickerCols = 20;

    function openPicker() {
        pickerRows = 6;
        pickerCols = 20;
        el.pickerCols.value = 1;
        el.readout.textContent = 'Chưa chọn';
        el.picker.hidden = false;
        drawPicker(0, 0);
    }

    function drawPicker(hoverRow, hoverCol) {
        el.pickerGrid.style.setProperty('--cols', pickerCols);
        var html = '';
        for (var r = 1; r <= pickerRows; r++) {
            for (var c = 1; c <= pickerCols; c++) {
                html += '<span class="zs-pcell' + (r <= hoverRow && c <= hoverCol ? ' is-on' : '')
                    + '" data-r="' + r + '" data-c="' + c + '"></span>';
            }
        }
        el.pickerGrid.innerHTML = html;
    }

    el.pickerGrid.addEventListener('mousemove', function (event) {
        var cell = event.target.closest('.zs-pcell');
        if (!cell) return;

        var r = Number(cell.dataset.r);
        var c = Number(cell.dataset.c);

        // Nở thêm khi con trỏ chạm mép, giống lưới chèn bảng của Word.
        var grew = false;
        if (r === pickerRows && pickerRows < PICKER_MAX_ROWS) { pickerRows = Math.min(pickerRows + 2, PICKER_MAX_ROWS); grew = true; }
        if (c === pickerCols && pickerCols < PICKER_MAX_COLS) { pickerCols = Math.min(pickerCols + 5, PICKER_MAX_COLS); grew = true; }

        if (grew) {
            drawPicker(r, c);
        } else {
            el.pickerGrid.querySelectorAll('.zs-pcell').forEach(function (node) {
                node.classList.toggle('is-on', Number(node.dataset.r) <= r && Number(node.dataset.c) <= c);
            });
        }

        var cols = pickerColumns();
        el.readout.textContent = cols + ' cột × ' + r + ' tầng × ' + c + ' vị trí = ' + num(cols * r * c) + ' ô';
    });

    function pickerColumns() {
        return Math.min(Math.max(Number(el.pickerCols.value) || 1, 1), CFG.maxColumns);
    }

    el.pickerGrid.addEventListener('click', function (event) {
        var cell = event.target.closest('.zs-pcell');
        if (!cell) return;

        var rows = Number(cell.dataset.r);
        var cols = Number(cell.dataset.c);
        var template = [];
        for (var p = 1; p <= rows; p++) template.push({ position: p, max: cols });

        var start = maxPosition(state.columns);
        for (var i = 1; i <= pickerColumns(); i++) {
            state.columns.push(newColumn(start + i, template));
        }

        el.picker.hidden = true;
        renderColumns();
        setDirty(true);
    });

    /* các khối cột */

    var lockedSize = null;

    // Mọi cột dùng chung một cỡ ô để lưới các cột thẳng hàng với nhau.
    function cellSize() {
        if (lockedSize) return lockedSize;

        var widest = 1;
        state.columns.forEach(function (column) {
            column.tiers.forEach(function (tier) { widest = Math.max(widest, tier.max, tier.origMax); });
        });

        var available = Math.max(el.columns.clientWidth - 230, 260);
        return Math.max(9, Math.min(Math.floor(available / widest) - 2, 26));
    }

    function cellsMarkup(column, tier) {
        var width = Math.max(tier.max, tier.origMax);
        var markup = '';

        for (var p = 1; p <= width; p++) {
            var cls, status;
            if (tier.removed || column.removed || p > tier.max) {
                // Ô đang mở nằm ngoài sức chứa mới (hoặc cả tầng/cột bị gỡ)
                cls = 'is-cut'; status = tier.wasRemoved || column.wasRemoved ? 'đang khoá' : 'sẽ khoá';
            } else if (p > tier.origMax) {
                // Vượt sức chứa cũ: ô chưa có thì sinh mới, ô từng bị khoá do thu nhỏ thì mở lại
                cls = 'is-new'; status = tier.codes[p] ? 'sẽ mở lại' : 'sẽ tạo';
            } else if (tier.busy.has(p)) {
                cls = 'is-busy'; status = 'có lô hàng';
            } else if (tier.off.has(p)) {
                cls = 'is-off'; status = 'đang khoá';
            } else {
                cls = 'is-free'; status = 'trống';
            }
            var code = tier.codes[p] || tierCode(column, tier) + '/' + seg(p);
            markup += '<span class="zs-cell ' + cls + '" title="' + esc(code) + ' - ' + status + '"></span>';
        }

        return markup;
    }

    function rowMarkup(column, tier) {
        var width = Math.max(tier.max, tier.origMax);
        var note = tier.lastBusy ? ' (ô ' + tier.lastBusy + ' đang có lô hàng)' : '';
        var locked = column.removed;

        return '<div class="zs-row' + (tier.removed ? ' is-removed' : '') + '" data-pos="' + tier.position + '">'
            + '<span class="zs-row-label" title="' + esc(tierCode(column, tier)) + '">' + esc(tier.label) + '</span>'
            + '<div class="zs-cells" style="--cols:' + width + '">' + cellsMarkup(column, tier) + '</div>'
            + (locked ? '' :
                '<button type="button" class="zs-handle" data-pos="' + tier.position + '" title="Kéo để đổi số vị trí' + esc(note) + '"></button>'
                + '<input type="number" class="zs-num" data-pos="' + tier.position + '" min="1" max="' + CFG.maxLocations
                + '" value="' + tier.max + '"' + (tier.removed ? ' disabled' : '') + '>'
                + '<button type="button" class="zs-drop" data-pos="' + tier.position + '" title="'
                + (tier.removed ? 'Dùng lại tầng này' : 'Gỡ tầng này') + '"><i class="fas fa-' + (tier.removed ? 'undo' : 'times') + '"></i></button>')
            + '</div>';
    }

    function columnMarkup(column, index) {
        var tiers = activeTiers(column);
        var visible = column.tiers
            .filter(function (tier) { return !(tier.removed && tier.origMax === 0 && !tier.existing); })
            .sort(function (a, b) { return b.position - a.position; });

        var meta = column.removed
            ? (column.wasRemoved ? 'Đang khoá' : 'Sẽ khoá toàn bộ cột')
            : tiers.length + ' tầng · ' + num(columnCells(column)) + ' vị trí';

        var head = '<div class="zs-col-head">'
            + '<div class="zs-col-title"><i class="fas fa-grip-lines-vertical"></i> ' + esc(column.label)
            +   ' <span class="zs-code">' + esc(columnCode(column)) + '</span>'
            +   ' <small>' + meta + '</small></div>'
            + '<div class="zs-col-tools">'
            + (column.removed ? '' :
                '<div class="zs-bulk"><label class="mb-0">Đặt mọi tầng thành</label>'
                + '<input type="number" class="form-control form-control-sm zs-bulk-value" min="1" max="' + CFG.maxLocations + '">'
                + '<button type="button" class="btn btn-sm btn-outline-secondary zs-bulk-apply">Áp dụng</button></div>'
                + '<button type="button" class="btn btn-sm btn-outline-primary zs-col-copy" title="Thêm một cột mới có cùng cấu trúc tầng">'
                + '<i class="fas fa-clone mr-1"></i>Nhân bản</button>')
            + '<button type="button" class="btn btn-sm ' + (column.removed ? 'btn-outline-success' : 'btn-outline-danger') + ' zs-col-drop">'
            + (column.removed ? '<i class="fas fa-undo mr-1"></i>Dùng lại cột' : '<i class="fas fa-times mr-1"></i>Gỡ cột')
            + '</button>'
            + '</div></div>';

        var body;
        if (column.removed && column.wasRemoved) {
            body = '<div class="zs-col-off">Cột này đang khoá cùng các tầng, vị trí bên dưới. Bấm "Dùng lại cột" để mở lại.</div>';
        } else if (!visible.length) {
            body = '<p class="zs-note mb-0">Chưa có tầng nào. Bấm "Thêm tầng" để bắt đầu.</p>';
        } else {
            body = '<div class="zs-grid">' + visible.map(function (tier) { return rowMarkup(column, tier); }).join('') + '</div>';
        }

        return '<section class="zs-col' + (column.existing ? '' : ' is-new') + (column.removed ? ' is-removed' : '')
            + '" data-index="' + index + '">'
            + head + body
            + (column.removed ? '' : '<div class="zs-col-foot"><button type="button" class="btn btn-sm btn-outline-secondary zs-add-tier">'
                + '<i class="fas fa-plus mr-1"></i> Thêm tầng</button></div>')
            + '</section>';
    }

    function renderColumns() {
        state.columns.sort(function (a, b) { return a.position - b.position; });
        el.columns.style.setProperty('--zs-cell', cellSize() + 'px');
        el.columns.innerHTML = state.columns.map(columnMarkup).join('');
        el.foot.hidden = !state.columns.length;
        el.addColumn.hidden = !state.columns.length;
        updateSummary();
    }

    // Kéo một hàng chỉ đổi hàng đó, nên vẽ lại mình nó thay vì dựng lại cả lưới.
    function renderRow(column, tier, section) {
        var row = section.querySelector('.zs-row[data-pos="' + tier.position + '"]');
        if (!row) {
            renderColumns();
            return;
        }

        var cells = row.querySelector('.zs-cells');
        cells.style.setProperty('--cols', Math.max(tier.max, tier.origMax));
        cells.innerHTML = cellsMarkup(column, tier);
        row.querySelector('.zs-num').value = tier.max;
        updateSummary();
    }

    function updateSummary() {
        var cols = state.columns.filter(function (column) { return !column.removed; });
        var tiers = cols.reduce(function (sum, column) { return sum + activeTiers(column).length; }, 0);
        var cells = cols.reduce(function (sum, column) { return sum + columnCells(column); }, 0);
        var text = cols.length + ' cột · ' + tiers + ' tầng · ' + num(cells) + ' vị trí';

        el.summary.textContent = text;
        el.total.textContent = text;
    }

    function columnAt(target) {
        var section = target.closest('.zs-col');
        return section ? { column: state.columns[Number(section.dataset.index)], section: section } : null;
    }

    function findTier(column, position) {
        return column.tiers.find(function (tier) { return tier.position === Number(position); });
    }

    // Không cho thu nhỏ qua ô cuối cùng đang có lô hàng: thao tác sai bị chặn ngay ở tay.
    function clampMax(tier, value) {
        return Math.min(Math.max(value, Math.max(1, tier.lastBusy)), CFG.maxLocations);
    }

    function columnBusy(column) {
        return column.tiers.some(function (tier) { return tier.lastBusy > 0; });
    }

    // Gỡ hẳn một mục mới (chưa lưu) rồi đánh số lại các mục mới phía sau mục đã có, để không hở số.
    function dropNew(list, item, relabel) {
        var index = list.indexOf(item);
        if (index >= 0) list.splice(index, 1);

        var base = list.filter(function (x) { return x.existing; }).reduce(function (max, x) { return Math.max(max, x.position); }, 0);
        list.filter(function (x) { return !x.existing; })
            .sort(function (a, b) { return a.position - b.position; })
            .forEach(function (x, i) { x.position = base + i + 1; relabel(x); });
    }

    el.columns.addEventListener('pointerdown', function (event) {
        var handle = event.target.closest('.zs-handle');
        if (!handle) return;

        var found = columnAt(handle);
        var tier = found && findTier(found.column, handle.dataset.pos);
        if (!tier || tier.removed) return;

        var cells = handle.parentElement.querySelector('.zs-cells');
        var left = cells.getBoundingClientRect().left;
        var size = parseFloat(getComputedStyle(el.columns).getPropertyValue('--zs-cell')) || 14;

        lockedSize = size;
        handle.classList.add('is-dragging');
        handle.setPointerCapture(event.pointerId);
        event.preventDefault();

        function onMove(moveEvent) {
            var next = clampMax(tier, Math.round((moveEvent.clientX - left) / (size + 2)));
            if (next === tier.max) return;
            tier.max = next;
            setDirty(true);
            renderRow(found.column, tier, found.section);
        }

        function onUp() {
            handle.classList.remove('is-dragging');
            document.removeEventListener('pointermove', onMove);
            document.removeEventListener('pointerup', onUp);
            lockedSize = null;
            renderColumns();
        }

        document.addEventListener('pointermove', onMove);
        document.addEventListener('pointerup', onUp);
    });

    el.columns.addEventListener('change', function (event) {
        var input = event.target.closest('.zs-num');
        if (!input) return;

        var found = columnAt(input);
        var tier = found && findTier(found.column, input.dataset.pos);
        if (!tier) return;

        var next = clampMax(tier, Number(input.value) || 1);
        if (next > Number(input.value) && tier.lastBusy) {
            toast('warning', tier.label + ' không thể nhỏ hơn ' + next + ' vì ô ' + tier.lastBusy + ' đang có lô hàng.');
        }
        tier.max = next;
        setDirty(true);
        renderColumns();
    });

    el.columns.addEventListener('click', function (event) {
        var found = columnAt(event.target);
        if (!found) return;
        var column = found.column;

        var drop = event.target.closest('.zs-drop');
        if (drop) {
            var tier = findTier(column, drop.dataset.pos);
            if (!tier) return;

            if (!tier.removed && tier.lastBusy) {
                toast('warning', tier.label + ' đang có lô hàng (tới ô ' + tier.lastBusy + '), không gỡ được. Hãy chuyển hàng đi trước.');
                return;
            }

            if (tier.existing) {
                tier.removed = !tier.removed;
            } else {
                dropNew(column.tiers, tier, function (x) { x.label = 'Tầng ' + seg(x.position); });
            }
            setDirty(true);
            renderColumns();
            return;
        }

        if (event.target.closest('.zs-add-tier')) {
            var highest = maxPosition(column.tiers);
            if (highest >= CFG.maxTiers) {
                toast('warning', 'Tối đa ' + CFG.maxTiers + ' tầng mỗi cột.');
                return;
            }
            var last = activeTiers(column).sort(function (a, b) { return a.position - b.position; }).pop();
            column.tiers.push(newTier(highest + 1, last ? last.max : 10));
            setDirty(true);
            renderColumns();
            return;
        }

        if (event.target.closest('.zs-bulk-apply')) {
            var value = Number(found.section.querySelector('.zs-bulk-value').value);
            if (!value) return;

            var blocked = [];
            activeTiers(column).forEach(function (item) {
                var next = clampMax(item, value);
                if (next !== value && item.lastBusy) blocked.push(item.label + ' (tối thiểu ' + next + ')');
                item.max = next;
            });
            setDirty(true);
            renderColumns();

            if (blocked.length) {
                toast('warning', 'Một số tầng không xuống được ' + value + ' vì đang có lô hàng: ' + blocked.join(', '));
            }
            return;
        }

        if (event.target.closest('.zs-col-copy')) {
            addColumn(templateOf(column));
            return;
        }

        if (event.target.closest('.zs-col-drop')) {
            if (!column.removed && columnBusy(column)) {
                toast('warning', column.label + ' đang có lô hàng, không gỡ được. Hãy chuyển hàng đi trước.');
                return;
            }

            if (column.existing) {
                column.removed = !column.removed;
                // Dùng lại cột đang khoá thì mở lại luôn các tầng của nó
                if (!column.removed && column.wasRemoved) {
                    column.tiers.forEach(function (item) { item.removed = false; });
                    if (!column.tiers.length) column.tiers.push(newTier(1, 10));
                }
            } else {
                dropNew(state.columns, column, function (x) { x.label = 'Cột ' + seg(x.position); });
            }
            setDirty(true);
            renderColumns();
        }
    });

    function addColumn(template) {
        var highest = maxPosition(state.columns);
        var active = state.columns.filter(function (column) { return !column.removed; }).length;

        if (active >= CFG.maxColumns || highest >= 99) {
            toast('warning', 'Tối đa ' + CFG.maxColumns + ' cột mỗi kệ/tủ.');
            return;
        }

        var tiers = template && template.length ? template : [{ position: 1, max: 10 }];
        state.columns.push(newColumn(highest + 1, tiers));
        setDirty(true);
        renderColumns();

        var sections = el.columns.querySelectorAll('.zs-col');
        if (sections.length) sections[sections.length - 1].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    el.addColumn.addEventListener('click', function () {
        var last = state.columns.filter(function (column) { return !column.removed; }).pop();
        addColumn(templateOf(last));
    });

    window.addEventListener('resize', function () {
        if (state.view === 'editor' && state.columns.length) renderColumns();
    });

    el.reset.addEventListener('click', function () {
        if (state.shelf) {
            setDirty(false);
            loadShelf(state.shelf.id);
            return;
        }
        if (!confirmLeave()) return;
        setDirty(false);
        show('shelves');
        loadShelves();
    });

    /* ---------- lưu ---------- */

    function payloadColumns() {
        return state.columns
            .filter(function (column) { return !column.removed; })
            .map(function (column) {
                return {
                    position: column.position,
                    tiers: activeTiers(column).map(function (tier) { return { position: tier.position, max: tier.max }; })
                };
            });
    }

    el.save.addEventListener('click', function () {
        var code = shelfCode();
        var name = el.name.value.trim();

        if (!code || !name) {
            toast('warning', 'Nhập mã và tên kệ/tủ trước khi lưu.');
            return;
        }
        if (!state.shelf && codeTaken()) {
            toast('warning', 'Mã kệ/tủ ' + code + ' đã tồn tại. Hãy đổi mã.');
            return;
        }

        var columns = payloadColumns();
        if (!columns.length) {
            toast('warning', 'Kệ/tủ phải có ít nhất một cột đang dùng.');
            return;
        }

        var empty = state.columns.filter(function (column) { return !column.removed && !activeTiers(column).length; });
        if (empty.length) {
            toast('warning', empty.map(function (c) { return c.label; }).join(', ') + ' không còn tầng nào. Hãy thêm tầng hoặc gỡ cột.');
            return;
        }

        var cells = state.columns.reduce(function (sum, column) { return sum + columnCells(column); }, 0);
        var droppedCols = state.columns.filter(function (c) { return c.removed && !c.wasRemoved; }).length;
        var droppedTiers = 0;
        var shrunk = 0;
        state.columns.forEach(function (column) {
            if (column.removed) return;
            column.tiers.forEach(function (tier) {
                if (tier.removed && !tier.wasRemoved) droppedTiers++;
                else if (!tier.removed && tier.max < tier.origMax) shrunk++;
            });
        });

        var warning = [];
        if (droppedCols) warning.push('gỡ ' + droppedCols + ' cột');
        if (droppedTiers) warning.push('gỡ ' + droppedTiers + ' tầng');
        if (shrunk) warning.push('thu nhỏ ' + shrunk + ' tầng');

        var text = columns.length + ' cột · ' + num(cells) + ' vị trí.';
        if (warning.length) {
            text += ' Lượt này sẽ ' + warning.join(', ') + ': các vị trí bị loại sẽ bị KHOÁ (không xoá) và không chọn được khi nhập kho nữa.';
        }

        Swal.fire({
            icon: warning.length ? 'warning' : 'question',
            title: state.shelf ? 'Lưu cấu trúc kệ/tủ ' + code + '?' : 'Tạo kệ/tủ ' + code + '?',
            text: text,
            showCancelButton: true,
            confirmButtonText: state.shelf ? 'Lưu' : 'Tạo',
            cancelButtonText: 'Huỷ',
            confirmButtonColor: '#2E7BC4',
            cancelButtonColor: '#94A3B8'
        }).then(function (choice) {
            if (!choice.isConfirmed) return;

            var template = state.columns.filter(function (c) { return !c.removed; }).map(templateOf);
            el.save.disabled = true;

            post(CFG.apply, {
                shelf_id: state.shelf ? state.shelf.id : null,
                warehouse_id: state.warehouse.id,
                code: code,
                name: name,
                item_type: el.itemType.value,
                columns: columns
            }).then(function (result) {
                el.save.disabled = false;
                if (!result.ok) {
                    toast('error', result.data.message || 'Không lưu được.');
                    return;
                }

                window.zoneStructureStale = true;
                setDirty(false);

                if (state.shelf) {
                    toast('success', result.data.message);
                    loadShelf(result.data.shelf_id);
                    return;
                }

                afterCreate(result.data.message, template);
            });
        });
    });

    // Kệ sau thường giống kệ trước: hỏi có tạo tiếp kệ kế với cùng cấu trúc không.
    function afterCreate(message, template) {
        getJson(CFG.shelves + '?warehouse_id=' + encodeURIComponent(state.warehouse.id)).then(function (data) {
            state.shelves = data.shelves;
            state.suggest = data.suggest || '';

            Swal.fire({
                icon: 'success',
                title: 'Đã lưu',
                text: message + (state.suggest ? ' Tạo tiếp kệ/tủ ' + state.suggest + ' với cùng cấu trúc?' : ''),
                showCancelButton: true,
                confirmButtonText: 'Tạo tiếp',
                cancelButtonText: 'Về danh sách kệ/tủ',
                confirmButtonColor: '#2E7BC4',
                cancelButtonColor: '#94A3B8'
            }).then(function (choice) {
                if (choice.isConfirmed) {
                    startCreate(template);
                } else {
                    show('shelves');
                    renderShelves();
                }
            });
        });
    }

    window.addEventListener('beforeunload', function (event) {
        if (state.dirty) {
            event.preventDefault();
            event.returnValue = '';
        }
    });

    window.zoneStructureDirty = function () {
        return state.dirty;
    };

    show('warehouses');
    loadWarehouses();
})();
</script>
@endverbatim
