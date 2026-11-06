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
                    <label for="zs-wh-code"><span id="zs-wh-code-label">Mã kho/phòng</span> <span class="text-danger">*</span></label>
                    <input type="text" id="zs-wh-code" class="form-control form-control-sm" maxlength="40" placeholder="VD: 03">
                </div>
                <div class="form-group mb-0 zs-grow">
                    <label for="zs-wh-name"><span id="zs-wh-name-label">Tên kho/phòng</span> <span class="text-danger">*</span></label>
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
                <div class="zs-view" role="group" aria-label="Tô màu ô theo">
                    <span class="zs-view-label">Tô màu theo</span>
                    <button type="button" class="zs-view-btn" data-view="zone"><i class="fas fa-tags mr-1"></i>Phân loại</button>
                    <button type="button" class="zs-view-btn" data-view="status"><i class="fas fa-box mr-1"></i>Trạng thái</button>
                </div>
                <div id="zs-legend-zone" class="zs-legend-items"></div>
                <div id="zs-legend-status" class="zs-legend-items">
                    <span><i class="zs-swatch is-busy"></i> Có lô hàng</span>
                    <span><i class="zs-swatch is-free"></i> Trống</span>
                    <span><i class="zs-swatch is-off"></i> Đang khoá</span>
                    <span><i class="zs-swatch is-new"></i> Sẽ tạo</span>
                    <span><i class="zs-swatch is-cut"></i> Sẽ khoá</span>
                    <span><i class="zs-swatch is-picked"></i> Đang chọn</span>
                    <span><i class="zs-swatch has-color"></i> Màu phân loại</span>
                </div>
            </div>

            {{-- Sửa phân loại / loại lưu trữ cho các vị trí đang chọn trên lưới (chỉ khi sửa kệ đã có) --}}
            <div id="zs-loc-bar" class="zs-loc-bar" hidden>
                <span class="zs-loc-count"><i class="fas fa-hand-pointer mr-1"></i><b id="zs-loc-count">0</b> vị trí đang chọn</span>
                <div class="zs-loc-field">
                    <label for="zs-loc-zone" class="mb-0">Phân loại</label>
                    <select id="zs-loc-zone" class="form-control form-control-sm">
                        <option value="__keep">- Giữ nguyên -</option>
                        <option value="">Chưa phân loại</option>
                        @foreach (\App\Support\ZoneType::TYPES as $value => $label)
                            <option value="{{ $value }}" data-color="{{ \App\Support\ZoneType::DEFAULT_COLORS[$value] }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="zs-loc-field">
                    <label for="zs-loc-type" class="mb-0">Loại lưu trữ</label>
                    <select id="zs-loc-type" class="form-control form-control-sm">
                        <option value="__keep">- Giữ nguyên -</option>
                        <option value="">Dùng chung</option>
                        @foreach ($locationTypes as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="zs-loc-actions">
                    <button type="button" id="zs-loc-all" class="btn btn-sm btn-outline-secondary">Chọn cả kệ</button>
                    <button type="button" id="zs-loc-clear" class="btn btn-sm btn-outline-secondary">Bỏ chọn</button>
                    <button type="button" id="zs-loc-apply" class="btn btn-sm btn-primary"><i class="fas fa-check mr-1"></i>Áp dụng</button>
                </div>
            </div>

            <section class="zs-shelf" data-draft="0">
                <div class="zs-shelf-title"><i class="fas fa-layer-group"></i> <span id="zs-shelf-title">Cấu trúc kệ/tủ</span></div>
                <div class="zs-form">
                    <div class="form-group mb-0">
                        <label for="zs-code">Mã kệ/tủ <span class="text-danger">*</span></label>
                        <input type="text" id="zs-code" class="form-control form-control-sm zs-code-input" maxlength="40" placeholder="VD: 01/05">
                    </div>
                    <div class="form-group mb-0 zs-grow">
                        <label for="zs-name">Tên kệ/tủ <span class="text-danger">*</span></label>
                        <input type="text" id="zs-name" class="form-control form-control-sm zs-name-input" maxlength="255" placeholder="VD: Kệ/Tủ 05">
                    </div>
                    <div class="form-group mb-0" id="zs-tier-order-group">
                        <label for="zs-tier-order">Đánh số tầng</label>
                        <select id="zs-tier-order" class="form-control form-control-sm">
                            <option value="bottom_up">Từ dưới lên (Tầng 01 dưới cùng)</option>
                            <option value="top_down">Từ trên xuống (Tầng 01 trên cùng)</option>
                        </select>
                    </div>
                    <div id="zs-preview" class="zs-preview" hidden></div>
                </div>

                {{-- Lưới chọn nhanh kiểu chèn bảng của Word, chỉ hiện khi kệ chưa có cột nào --}}
                <div id="zs-picker" class="zs-picker" hidden>
                    <div class="zs-picker-title">
                        Chọn chiều đánh số tầng, rê chuột chọn số tầng (dọc) × số ô ngang mỗi tầng cho mỗi cột, nhập số dãy sâu, rồi bấm để xác nhận.
                    </div>
                    <div class="zs-picker-row">
                        <div id="zs-picker-grid" class="zs-picker-grid"></div>
                        <div class="zs-picker-side">
                            <label class="mb-1">Đánh số tầng</label>
                            <div class="zs-order-pick" role="group" aria-label="Chiều đánh số tầng">
                                <button type="button" class="zs-order-btn" data-order="bottom_up">
                                    <i class="fas fa-arrow-up"></i><span>Từ dưới lên<small>Tầng 01 dưới cùng</small></span>
                                </button>
                                <button type="button" class="zs-order-btn" data-order="top_down">
                                    <i class="fas fa-arrow-down"></i><span>Từ trên xuống<small>Tầng 01 trên cùng</small></span>
                                </button>
                            </div>
                            <label for="zs-picker-cols" class="mb-1 mt-2">Số cột</label>
                            <input type="number" id="zs-picker-cols" class="form-control form-control-sm" min="1" value="1">
                            <label for="zs-picker-depth" class="mb-1 mt-2" title="Số dãy tính từ cửa vào trong của mỗi tầng">Số dãy sâu</label>
                            <input type="number" id="zs-picker-depth" class="form-control form-control-sm" min="1" value="1">
                            <span id="zs-picker-readout" class="zs-picker-readout">Chưa chọn</span>
                        </div>
                    </div>
                </div>

                <div id="zs-columns" class="zs-columns"></div>
            </section>

            {{-- Các kệ tạo thêm cùng lượt, mỗi kệ một card có lưới riêng --}}
            <div id="zs-drafts"></div>

            <div id="zs-foot" class="zs-foot" hidden>
                <button type="button" id="zs-add-shelf" class="btn btn-sm btn-outline-primary" hidden
                        title="Thêm một kệ/tủ mới bên dưới, sao chép cấu trúc cột/tầng của kệ cuối">
                    <i class="fas fa-plus mr-1"></i> Thêm kệ
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
        font-size: 1.15rem;
        line-height: 1.3;
        color: var(--text-main);
        white-space: normal;
        overflow-wrap: anywhere;
        min-width: 0;
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
        overflow-wrap: anywhere;
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

    .zs-legend-items {
        display: flex;
        gap: 6px 16px;
        flex-wrap: wrap;
        align-items: center;
    }

    .zs-legend-items[hidden] {
        display: none;
    }

    .zs-view {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding-right: 14px;
        border-right: 1px solid #e2e8f0;
    }

    .zs-view-label {
        font-weight: 600;
        margin-right: 4px;
    }

    .zs-view-btn {
        border: 1px solid var(--primary-lighter);
        background: #fff;
        color: var(--primary);
        border-radius: var(--border-radius-md);
        padding: 2px 10px;
        font-size: 0.76rem;
        transition: all 0.2s;
    }

    .zs-view-btn:hover {
        background: var(--primary-soft);
    }

    .zs-view-btn.active {
        background: var(--primary);
        border-color: var(--primary);
        color: #fff;
    }

    .zs-zone-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border: 1px solid #e2e8f0;
        background: #fff;
        border-radius: 999px;
        padding: 2px 10px 2px 6px;
        font-size: 0.76rem;
        color: #334155;
        transition: all 0.2s;
    }

    .zs-zone-chip .zs-swatch {
        margin-right: 0;
        background: var(--zt-soft);
        border: 1.5px solid var(--zt);
    }

    .zs-zone-chip b {
        color: var(--zt);
    }

    .zs-zone-chip.is-empty {
        opacity: 0.55;
    }

    button.zs-zone-chip:hover {
        border-color: var(--zt);
        box-shadow: var(--shadow-sm);
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
        /* cột đầu = số tầng của các hàng đang chọn, theo chiều đánh số */
        grid-template-columns: 20px repeat(var(--cols), 14px);
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

    .zs-plabel {
        font-size: 9px;
        font-weight: 700;
        line-height: 14px;
        text-align: right;
        padding-right: 3px;
        color: var(--primary-dark);
        user-select: none;
    }

    .zs-plabel.is-first {
        color: #fff;
        background: var(--primary);
        border-radius: 3px;
        text-align: center;
        padding-right: 0;
    }

    /* Hai nút chọn chiều đánh số tầng ngay trong khung tạo kệ */
    .zs-order-pick {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .zs-order-btn {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 6px 10px;
        border: 1px solid #cbd5e1;
        border-radius: var(--border-radius-md);
        background: #fff;
        color: var(--text-main);
        text-align: left;
        cursor: pointer;
        transition: all 0.2s;
    }

    .zs-order-btn i {
        width: 14px;
        color: #94a3b8;
    }

    .zs-order-btn span {
        display: flex;
        flex-direction: column;
        font-size: 0.8rem;
        font-weight: 600;
        line-height: 1.2;
    }

    .zs-order-btn small {
        font-size: 0.72rem;
        font-weight: 500;
        color: #64748b;
    }

    .zs-order-btn:hover {
        border-color: var(--primary-light);
        background: var(--primary-soft);
    }

    .zs-order-btn.active {
        border-color: var(--primary);
        background: var(--primary-soft);
        box-shadow: 0 0 0 1px var(--primary);
    }

    .zs-order-btn.active i {
        color: var(--primary);
    }

    .zs-picker-side {
        display: flex;
        flex-direction: column;
        width: 190px;
        font-size: 0.82rem;
        font-weight: 600;
    }

    .zs-picker-readout {
        margin-top: 10px;
        color: var(--primary);
        font-weight: 700;
    }

    /* ---------- Kệ tạo thêm cùng lượt ---------- */
    .zs-draft {
        border-left: 4px solid var(--primary-light);
    }

    .zs-draft-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 10px;
    }

    .zs-draft-head .btn,
    .zs-foot .btn {
        border-radius: var(--border-radius-md);
    }

    /* ---------- Dãy cột xếp ngang như kệ thật ---------- */
    .zs-columns {
        margin-top: 14px;
        display: flex;
        align-items: stretch;
        gap: 14px;
        overflow-x: auto;
        padding-bottom: 8px;
        margin-bottom: 8px;
    }

    .zs-columns:empty {
        display: none;
    }

    .zs-columns .zs-col {
        flex: 0 0 auto;
        display: flex;
        flex-direction: column;
        min-width: 250px;
        margin-bottom: 0;
    }

    /* Tầng 01 nằm dưới cùng: đẩy lưới xuống đáy để các cột thẳng hàng theo tầng */
    .zs-columns .zs-col > .zs-grid,
    .zs-columns .zs-col > .zs-note,
    .zs-columns .zs-col > .zs-col-off {
        margin-top: auto;
    }

    /* Đánh số từ trên xuống: Tầng 01 trên cùng, các cột thẳng hàng theo mép trên */
    #zs-editor.zs-top-down .zs-columns .zs-col > .zs-grid,
    #zs-editor.zs-top-down .zs-columns .zs-col > .zs-note,
    #zs-editor.zs-top-down .zs-columns .zs-col > .zs-col-off {
        margin-top: 0;
    }

    #zs-editor.zs-top-down .zs-columns .zs-col > .zs-col-foot {
        margin-top: auto;
    }

    /* Ô "Thêm cột" nhỏ gọn ở đầu hàng cột, không kéo dài theo chiều cao các cột */
    .zs-col-add {
        flex: 0 0 auto;
        align-self: flex-start;
        width: 88px;
        padding: 14px 6px;
        border: 2px dashed var(--primary-lighter);
        border-radius: var(--border-radius-lg);
        background: var(--bg-neutral);
        color: var(--primary);
        font-weight: 600;
        font-size: 0.8rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 4px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .zs-col-add i {
        font-size: 1rem;
    }

    /* Hộp "Đổi tên / mã tầng" */
    .zs-ask {
        text-align: left;
        font-size: 0.85rem;
    }

    .zs-ask label {
        display: block;
        margin: 12px 0 4px;
        font-weight: 600;
        color: var(--text-main);
    }

    .zs-ask .swal2-input {
        width: 100%;
        margin: 0;
        height: 38px;
        font-size: 0.9rem;
    }

    .zs-ask-code {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .zs-ask-code span {
        flex: none;
        font-family: Consolas, monospace;
        font-size: 0.8rem;
        color: #64748b;
    }

    .zs-ask-preview {
        margin-top: 8px;
        font-size: 0.8rem;
        color: #64748b;
    }

    .zs-ask-preview b {
        font-family: Consolas, monospace;
        color: var(--primary-dark);
    }

    .zs-col-add:hover {
        background: var(--primary-soft);
        border-color: var(--primary);
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
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
        margin-bottom: 10px;
    }

    .zs-col-bar {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 8px;
    }

    .zs-col-actions {
        display: flex;
        gap: 4px;
        flex: none;
    }

    .zs-col-actions .btn {
        border-radius: var(--border-radius-md);
    }

    /* Nút chỉ có biểu tượng (Nhân bản, Gỡ cột): tên đầy đủ hiện khi rê chuột */
    .zs-icon-btn {
        width: 28px;
        height: 28px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #cbd5e1;
        border-radius: var(--border-radius-md);
        background: #fff;
        color: #475569;
        font-size: 0.8rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .zs-icon-btn:hover {
        border-color: var(--primary);
        color: var(--primary);
        background: var(--primary-soft);
    }

    .zs-icon-btn.is-danger:hover {
        border-color: #DC2626;
        color: #DC2626;
        background: #FEF2F2;
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

    /* Số tầng · số vị trí nằm dòng dưới tên cột để tiêu đề không kéo rộng card */
    .zs-col-meta {
        display: block;
        margin-top: 2px;
        padding-left: 14px;
        font-size: 0.78rem;
        font-weight: 500;
        color: #64748b;
    }

    .zs-col.is-removed .zs-col-meta {
        color: #DC2626;
        font-weight: 600;
    }

    .zs-bulk {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.8rem;
        font-weight: 600;
        color: #475569;
    }

    /* Ngang × Sâu + nút áp dụng gộp thành một ô liền */
    .zs-bulk-group {
        display: inline-flex;
        align-items: stretch;
        height: 28px;
        border: 1px solid #cbd5e1;
        border-radius: var(--border-radius-md);
        background: #fff;
        overflow: hidden;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .zs-bulk-group:focus-within {
        border-color: var(--primary-light);
        box-shadow: 0 0 0 2px rgba(var(--primary-rgb), 0.15);
    }

    .zs-bulk-group input {
        width: 54px;
        border: none;
        outline: none;
        padding: 0 6px;
        font-size: 0.8rem;
        font-weight: 500;
        color: var(--text-main);
        background: transparent;
        -moz-appearance: textfield;
    }

    .zs-bulk-group input::-webkit-outer-spin-button,
    .zs-bulk-group input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    .zs-bulk-group .zs-x {
        display: flex;
        align-items: center;
        padding: 0 2px;
    }

    .zs-bulk-group button {
        border: none;
        border-left: 1px solid #cbd5e1;
        background: var(--primary-soft);
        color: var(--primary);
        padding: 0 10px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .zs-bulk-group button:hover {
        background: var(--primary);
        color: #fff;
    }

    .zs-grid {
        overflow-x: auto;
        padding-bottom: 6px;
    }

    .zs-row {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 6px;
    }

    .zs-row-label {
        width: 52px;
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

    /* Một tầng = lưới nhìn từ trên xuống: dãy trong cùng ở trên, dãy 1 (sát cửa) ở dưới,
       thước chữ A, B, C... chạy dọc mép trước giống nhãn dán trên kệ thật */
    /* Lưới 3 cột: [số dãy | ô | ô Sâu + góc kéo], 3 hàng: [ô | thước chữ | ô Ngang] */
    .zs-block {
        display: grid;
        grid-template-columns: 12px auto auto;
        gap: 2px 3px;
        flex: none;
    }

    .zs-block > .zs-depth-rule { grid-column: 1; grid-row: 1; }
    .zs-block > .zs-cells { grid-column: 2; grid-row: 1; }
    .zs-block > .zs-side { grid-column: 3; grid-row: 1; align-self: start; }
    .zs-block > .zs-front-rule { grid-column: 2; grid-row: 2; }
    .zs-block > .zs-corner { grid-column: 3; grid-row: 2; align-self: start; }
    .zs-block > .zs-under { grid-column: 2; grid-row: 3; justify-self: center; }

    .zs-depth-rule {
        display: grid;
        grid-auto-rows: var(--zs-cell);
        gap: 2px;
        font-size: 9px;
        line-height: var(--zs-cell);
        text-align: right;
        color: #64748b;
        user-select: none;
    }

    .zs-front-rule {
        display: grid;
        grid-template-columns: repeat(var(--cols), var(--zs-cell));
        gap: 2px;
        font-size: 8px;
        font-weight: 700;
        line-height: 10px;
        text-align: center;
        color: #64748b;
        user-select: none;
    }

    .zs-front-rule span {
        overflow: hidden;
        border-top: 2px solid #CBD5E1;
    }

    .zs-cell.is-void {
        visibility: hidden;
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

    /* Màu phân loại của vị trí: vạch màu ở đáy ô, không che màu trạng thái */
    .zs-cell.has-color {
        box-shadow: inset 0 -4px 0 var(--loc);
    }

    .zs-swatch.has-color {
        background: #EEF2F6;
        box-shadow: inset 0 -4px 0 #7C3AED;
    }

    .zs-cell {
        font-size: 9px;
        font-weight: 700;
        line-height: var(--zs-cell);
        text-align: center;
        color: #475569;
        overflow: hidden;
        user-select: none;
    }

    .zs-cell.is-busy {
        color: #fff;
    }

    /* Chế độ tô theo phân loại: nền ô = màu phân loại (ô có hàng tô đậm, ô trống tô nhạt) */
    .zs-by-zone .zs-cell.has-zone.is-free {
        background: var(--zt-soft);
        border-color: var(--zt);
        color: var(--zt);
    }

    .zs-by-zone .zs-cell.has-zone.is-busy {
        background: var(--zt);
        border-color: var(--zt);
        color: #fff;
    }

    .zs-by-zone .zs-cell.has-color {
        box-shadow: none;
    }

    .zs-pickable .zs-cell[data-id] {
        cursor: pointer;
    }

    .zs-pickable.is-marquee,
    .zs-pickable.is-marquee .zs-cell[data-id] {
        cursor: crosshair;
    }

    .zs-marquee {
        position: fixed;
        z-index: 1050;
        border: 1px dashed #2E7BC4;
        background: rgba(46, 123, 196, .12);
        pointer-events: none;
    }

    .zs-cell.is-picked,
    .zs-swatch.is-picked {
        outline: 2px solid #F59E0B;
        outline-offset: 1px;
    }

    .zs-swatch.is-picked {
        background: #fff;
    }

    /* ---------- Thanh sửa vị trí đang chọn ---------- */
    .zs-loc-bar {
        position: sticky;
        top: 0;
        z-index: 5;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 14px;
        padding: 10px 14px;
        margin-bottom: 14px;
        border: 1px solid var(--primary-lighter);
        border-radius: var(--border-radius-lg);
        background: var(--primary-soft);
        box-shadow: var(--shadow-sm);
    }

    .zs-loc-count {
        font-size: 0.85rem;
        color: var(--primary-dark);
    }

    .zs-loc-field {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.82rem;
        font-weight: 600;
        color: var(--text-main);
    }

    .zs-loc-field select {
        width: 150px;
        border-radius: var(--border-radius-md);
    }

    .zs-loc-field .select2-container {
        width: 180px !important;
        font-size: 0.82rem;
    }

    .zs-loc-actions {
        display: flex;
        gap: 6px;
        margin-left: auto;
    }

    .zs-loc-actions .btn {
        border-radius: var(--border-radius-md);
    }

    /* Tên cột/tầng bấm được để đổi tên */
    .zs-rename {
        border: none;
        background: transparent;
        padding: 0;
        color: inherit;
        font: inherit;
        cursor: pointer;
        transition: color 0.2s;
    }

    .zs-rename:hover {
        color: var(--primary);
    }

    .zs-rename .fa-pen {
        font-size: 0.6rem;
        opacity: 0.45;
        margin-left: 3px;
    }

    /* Góc kéo ở góc dưới phải lưới: kéo ngang đổi số ô ngang, kéo lên/xuống đổi số dãy sâu */
    .zs-handle {
        display: block;
        width: 12px;
        height: 12px;
        border: 1px solid #94a3b8;
        border-radius: 3px;
        background: linear-gradient(135deg, transparent 45%, #94a3b8 45%, #94a3b8 55%, transparent 55%), #f1f5f9;
        cursor: nesw-resize;
        padding: 0;
        touch-action: none;
        transition: background 0.2s, border-color 0.2s;
    }

    .zs-handle:hover,
    .zs-handle.is-dragging {
        background: var(--primary);
        border-color: var(--primary-dark);
    }

    /* Ô Ngang (dưới thước chữ) và ô Sâu (cạnh dãy trong cùng) nằm sát chiều mà nó điều khiển */
    .zs-num,
    .zs-depth {
        display: block;
        width: 36px;
        height: 22px;
        font-size: 0.74rem;
        text-align: center;
        padding: 0 2px;
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        background: #fff;
        -moz-appearance: textfield;
        transition: border-color 0.2s;
    }

    .zs-num:focus,
    .zs-depth:focus {
        outline: none;
        border-color: var(--primary-light);
    }

    .zs-num::-webkit-outer-spin-button,
    .zs-num::-webkit-inner-spin-button,
    .zs-depth::-webkit-outer-spin-button,
    .zs-depth::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
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

    .zs-x {
        flex: none;
        font-size: 0.72rem;
        color: #94a3b8;
    }

    .zs-row.is-removed .zs-row-label,
    .zs-row.is-removed .zs-num,
    .zs-row.is-removed .zs-depth {
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
    "saveShelf": @json(route('pages.materData.zone.structure.saveShelf')),
    "saveLocations": @json(route('pages.materData.zone.structure.saveLocations')),
    "locationTypes": @json($locationTypes),
    "zoneTypes": @json(\App\Support\ZoneType::TYPES),
    "zoneColors": @json(\App\Support\ZoneType::DEFAULT_COLORS),
    "maxColumns": {{ (int) $structureLimits['maxColumns'] }},
    "maxTiers": {{ (int) $structureLimits['maxTiers'] }},
    "maxLocations": {{ (int) $structureLimits['maxLocations'] }},
    "maxDepth": {{ (int) $structureLimits['maxDepth'] }},
    "maxBatch": {{ (int) $structureLimits['maxBatch'] }},
    "canCreate": @json(user_can(md_perm('zone', 'create'))),
    "canUpdate": @json(user_can(md_perm('zone', 'update'))),
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
        whCodeLabel: byId('zs-wh-code-label'),
        whNameLabel: byId('zs-wh-name-label'),
        locZone: byId('zs-loc-zone'),
        locBar: byId('zs-loc-bar'),
        locCount: byId('zs-loc-count'),
        locType: byId('zs-loc-type'),
        locAll: byId('zs-loc-all'),
        locClear: byId('zs-loc-clear'),
        locApply: byId('zs-loc-apply'),
        warehouses: byId('zs-warehouses'),
        shelves: byId('zs-shelves'),
        editor: byId('zs-editor'),
        shelfTitle: byId('zs-shelf-title'),
        code: byId('zs-code'),
        name: byId('zs-name'),
        tierOrder: byId('zs-tier-order'),
        tierOrderGroup: byId('zs-tier-order-group'),
        preview: byId('zs-preview'),
        drafts: byId('zs-drafts'),
        addShelf: byId('zs-add-shelf'),
        picker: byId('zs-picker'),
        pickerGrid: byId('zs-picker-grid'),
        pickerCols: byId('zs-picker-cols'),
        pickerDepth: byId('zs-picker-depth'),
        readout: byId('zs-picker-readout'),
        columns: byId('zs-columns'),
        foot: byId('zs-foot'),
        summary: byId('zs-summary')
    };

    el.pickerCols.max = CFG.maxColumns;
    el.pickerDepth.max = CFG.maxDepth;

    /*
     | view: 'warehouses' | 'shelves' | 'editor'
     | shelf: kệ đang sửa (null = đang tạo kệ mới)
     | columns: [{ position, existing, removed, wasRemoved, code, label, tiers: [...] }]
     | tier:    { position, max (ô ngang), depth (dãy sâu), origMax, origDepth, lastBusy, lastBusyDepth,
     |            busy:Set, off:Set, codes:{}, attrs:{} (khoá ô "<ngang>:<sâu>"), existing, removed, wasRemoved, label }
    */
    var state = {
        view: 'warehouses',
        warehouses: [], shelves: [], suggest: '',
        warehouse: null, shelf: null,
        columns: [], dirty: false, editingWarehouse: null, formKind: 'warehouse',
        // Chiều đánh số tầng của kệ đang sửa / các kệ đang tạo: 'bottom_up' | 'top_down'
        tierOrder: 'bottom_up',
        // Vị trí đang chọn trên lưới (id); cellView = tô ô theo 'zone' (phân loại) hay 'status'
        picked: new Set(), painting: null, cellView: 'zone',
        codeTimer: null, nameTouched: false,
        // Tạo kệ mới: kệ đầu + các kệ thêm bên dưới [{ code, name, nameTouched, columns }], current = kệ đang thao tác
        drafts: [], current: 0,
        // Mã kệ đã kiểm với máy chủ: { mã: true nếu đã tồn tại }
        taken: {}
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

    // Chữ của ô ngang thứ n kiểu cột Excel: 1 -> A, 26 -> Z, 27 -> AA (khớp máy chủ).
    function letters(n) {
        var out = '';
        while (n > 0) {
            n--;
            out = String.fromCharCode(65 + n % 26) + out;
            n = Math.floor(n / 26);
        }
        return out;
    }

    function cellKey(position, depth) {
        return position + ':' + depth;
    }

    function splitKey(key) {
        var parts = String(key).split(':');
        return { p: Number(parts[0]) || 0, d: Number(parts[1]) || 1 };
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
            + '<div class="zs-tile-name">' + esc(item.name) + (off ? ' (đã khoá)' : '')
            + '</div>'
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

        closeDrafts();
        clearPicked();
        el.locBar.hidden = true;
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

    // Khung nhập mã + tên dùng chung cho kho/phòng (thêm, sửa) và sửa nhanh kệ/tủ từ thẻ kệ.
    function openForm(kind, item) {
        var what = kind === 'shelf' ? 'kệ/tủ' : 'kho/phòng';
        state.formKind = kind;
        state.editingWarehouse = item || null;
        el.whTitle.textContent = item ? 'Sửa ' + what + ' ' + item.code : 'Kho/Phòng mới';
        el.whCodeLabel.textContent = 'Mã ' + what;
        el.whNameLabel.textContent = 'Tên ' + what;
        el.whSave.innerHTML = '<i class="fas fa-save mr-1"></i> Lưu ' + what;
        el.whCode.value = item ? item.code : '';
        el.whName.value = item ? item.name : '';
        el.whForm.hidden = false;
        el.whCode.focus();
    }

    function openWarehouseForm(warehouse) {
        openForm('warehouse', warehouse);
    }

    function hideWarehouseForm() {
        el.whForm.hidden = true;
        state.editingWarehouse = null;
    }

    el.whCancel.addEventListener('click', hideWarehouseForm);

    el.whSave.addEventListener('click', function () {
        var code = el.whCode.value.trim();
        var name = el.whName.value.trim();

        if (state.formKind === 'shelf') {
            saveShelfForm(code, name);
            return;
        }

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

    function saveShelfForm(code, name) {
        var shelf = state.editingWarehouse;
        if (!code || !name) {
            toast('warning', 'Nhập mã và tên kệ/tủ trước khi lưu.');
            return;
        }

        var renaming = code !== shelf.code;
        var ask = renaming
            ? Swal.fire({
                icon: 'warning',
                title: 'Đổi mã kệ/tủ ' + shelf.code + ' thành ' + code + '?',
                text: 'Mã của mọi cột, tầng, vị trí thuộc kệ này sẽ đổi theo (VD ' + shelf.code + '/01/01/01 -> ' + code + '/01/01/01). Nhãn đã in theo mã cũ cần in lại.',
                showCancelButton: true,
                confirmButtonText: 'Đổi mã',
                cancelButtonText: 'Huỷ',
                confirmButtonColor: '#2E7BC4',
                cancelButtonColor: '#94A3B8'
            })
            : Promise.resolve({ isConfirmed: true });

        ask.then(function (choice) {
            if (!choice.isConfirmed) return;

            el.whSave.disabled = true;
            post(CFG.saveShelf, { id: shelf.id, code: code, name: name }).then(function (result) {
                el.whSave.disabled = false;
                if (!result.ok) {
                    toast('error', result.data.message || 'Không lưu được kệ/tủ.');
                    return;
                }

                window.zoneStructureStale = true;
                toast('success', result.data.message);
                hideWarehouseForm();
                loadShelves();
            });
        });
    }

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
                editable: CFG.canUpdate
            });
        }).join('');
    }

    el.shelves.addEventListener('click', function (event) {
        var edit = event.target.closest('.zs-tile-edit');
        if (edit) {
            openForm('shelf', state.shelves.find(function (x) { return String(x.id) === edit.dataset.edit; }));
            return;
        }

        var card = event.target.closest('.zs-tile');
        if (card) loadShelf(card.dataset.id);
    });

    /* ---------- cấp 3: một kệ/tủ ---------- */

    // segment = mã tầng tự đặt (đoạn cuối của mã tầng); null = tầng mới lấy số thứ tự, tầng có sẵn giữ mã cũ.
    function newTier(position, max, depth, segment) {
        return {
            position: position, max: max, depth: depth || 1, origMax: 0, origDepth: 0, lastBusy: 0, lastBusyDepth: 0,
            busy: new Set(), off: new Set(), codes: {}, attrs: {},
            existing: false, removed: false, wasRemoved: false,
            segment: segment || null,
            label: 'Tầng ' + seg(position)
        };
    }

    function newColumn(position, template) {
        var tiers = (template || []).map(function (tier) { return newTier(tier.position, tier.max, tier.depth, tier.segment); });
        return {
            position: position, existing: false, removed: false, wasRemoved: false,
            code: null, label: 'Cột ' + seg(position), tiers: tiers
        };
    }

    function shelfCode() {
        return el.code.value.trim() || (state.shelf ? state.shelf.code : '');
    }

    // Đang đổi mã kệ có sẵn: mã con đang hiển thị đổi tiền tố theo mã mới để xem trước.
    function renamed() {
        return !!state.shelf && shelfCode() !== state.shelf.code;
    }

    function remap(code) {
        if (!code || !renamed() || code.indexOf(state.shelf.code + '/') !== 0) return code;
        return shelfCode() + code.slice(state.shelf.code.length);
    }

    function columnCode(column) {
        return remap(column.code) || (shelfCode() || '?') + '/' + seg(column.position);
    }

    // Đoạn mã của tầng: mã tự đặt, hoặc đoạn cuối của mã đang có, hoặc số thứ tự với tầng mới.
    function tierSegment(tier) {
        if (tier.segment) return tier.segment;
        if (tier.code) return tier.code.split('/').pop();
        return seg(tier.position);
    }

    function tierCode(column, tier) {
        if (tier.segment) return columnCode(column) + '/' + tier.segment;
        return remap(tier.code) || columnCode(column) + '/' + seg(tier.position);
    }

    // Mã một vị trí có sẵn sau khi kệ/tầng đổi mã: thay tiền tố mã tầng cũ bằng mã tầng mới.
    function cellCode(column, tier, code) {
        var old = remap(tier.code);
        var now = tierCode(column, tier);
        code = remap(code);
        return old && old !== now && code && code.indexOf(old + '/') === 0 ? now + code.slice(old.length) : code;
    }

    function activeTiers(column) {
        return column.tiers.filter(function (tier) { return !tier.removed; });
    }

    function columnCells(column) {
        if (column.removed) return 0;
        return activeTiers(column).reduce(function (sum, tier) { return sum + tier.max * tier.depth; }, 0);
    }

    function maxPosition(list) {
        return list.reduce(function (max, item) { return Math.max(max, item.position); }, 0);
    }

    // Mẫu tầng của cột còn dùng cuối cùng, để cột mới thêm vào giống cột trước.
    function templateOf(column) {
        // Mã tầng tự đặt (khác số thứ tự) đi theo sang cột mới; cột khác mã nên không trùng.
        return column ? activeTiers(column).map(function (tier) {
            var own = tierSegment(tier);
            return { position: tier.position, max: tier.max, depth: tier.depth, segment: own !== seg(tier.position) ? own : null };
        }) : [];
    }

    function startCreate(template) {
        if (!confirmLeave()) return;

        state.shelf = null;
        show('editor');
        el.shelfTitle.textContent = 'Kệ/Tủ mới trong ' + state.warehouse.code;
        el.code.readOnly = false;
        el.code.value = state.suggest || '';
        state.nameTouched = false;
        syncName();
        setTierOrder(state.tierOrder);

        if (template && template.length) {
            state.columns = template.map(function (tiers, index) { return newColumn(index + 1, tiers); });
            showPicker(false);
            renderColumns();
            setDirty(true);
        } else {
            state.columns = [];
            openPicker();
            renderColumns();
            setDirty(false);
        }

        initDrafts();
        refreshCodeCheck();
        renderCrumbs();
        el.code.focus();
    }

    function loadShelf(shelfId) {
        if (!confirmLeave()) return;

        state.shelf = null;
        show('editor');
        el.columns.innerHTML = '';
        showPicker(false);
        el.foot.hidden = true;
        el.note.textContent = 'Đang tải cấu trúc kệ/tủ...';
        el.note.hidden = false;

        getJson(CFG.detail + '?shelf_id=' + encodeURIComponent(shelfId))
            .then(function (data) {
                state.shelf = data.shelf;
                el.shelfTitle.textContent = 'Cấu trúc kệ/tủ ' + data.shelf.code;
                el.code.value = data.shelf.code;
                el.code.readOnly = !CFG.canUpdate;
                el.name.value = data.shelf.name;
                el.preview.hidden = true;
                setTierOrder(data.shelf.tier_order);
                el.tierOrder.disabled = !CFG.canUpdate;

                state.columns = data.columns.map(function (column) {
                    var removed = Number(column.status_id) !== 1;
                    return {
                        position: Number(column.position),
                        existing: true, removed: removed, wasRemoved: removed,
                        code: column.code, label: column.name || ('Cột ' + seg(column.position)),
                        tiers: column.tiers.map(function (tier) {
                            var off = Number(tier.status_id) !== 1;
                            var codes = tier.codes || {};
                            // Tầng đang khoá hết ô thì sức chứa hiện tại là 0; lấy khung các ô đã có
                            // làm mức gợi ý để khi "Dùng lại" là mở lại đủ các ô cũ.
                            var had = Object.keys(codes).map(splitKey);
                            var hadMax = had.reduce(function (m, k) { return Math.max(m, k.p); }, 0);
                            var hadDepth = had.reduce(function (m, k) { return Math.max(m, k.d); }, 0);
                            return {
                                position: Number(tier.position),
                                max: Number(tier.max) || hadMax || 1,
                                depth: Number(tier.depth) || hadDepth || 1,
                                origMax: Number(tier.max) || 0,
                                origDepth: Number(tier.depth) || 0,
                                lastBusy: Number(tier.last_busy) || 0,
                                lastBusyDepth: Number(tier.last_busy_depth) || 0,
                                busy: new Set(tier.busy || []),
                                off: new Set(tier.off || []),
                                codes: codes,
                                attrs: tier.attrs || {},
                                code: tier.code,
                                existing: true, removed: off, wasRemoved: off,
                                label: tier.name || ('Tầng ' + seg(tier.position))
                            };
                        })
                    };
                });

                el.note.hidden = true;
                el.locBar.hidden = !CFG.canUpdate;
                updatePicked();
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

    // Ô mã/tên có ở kệ đầu và ở từng kệ tạo thêm; el.code/el.name luôn trỏ vào kệ đang thao tác.
    el.editor.addEventListener('input', function (event) {
        if (event.target.classList.contains('zs-name-input')) {
            state.nameTouched = true;
            setDirty(true);
        } else if (event.target.classList.contains('zs-code-input')) {
            if (state.shelf) {
                setDirty(true);
                refreshCodeCheck();
                renderColumns();
                return;
            }
            syncName();
            setDirty(true);
            refreshCodeCheck();
            renderColumns();
            if (creatingMany()) paintAllPreviews();
        }
    });


    // Kết quả kiểm mã lưu theo từng mã, nên kết quả về trễ của lần gõ trước không lẫn sang mã đang nhập.
    function codeTaken() {
        return !!state.taken[el.code.value.trim()];
    }

    function paintPreview() {
        if (state.shelf) {
            paintRenamePreview();
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

        if (duplicateCode(code)) {
            el.preview.className = 'zs-preview is-bad';
            el.preview.innerHTML = 'Mã <b>' + esc(code) + '</b> trùng với một kệ khác đang tạo.';
            return;
        }

        el.preview.innerHTML = 'Sẽ tạo kệ/tủ <b>' + esc(code) + '</b> · cột <b>' + esc(code) + '/01</b>'
            + ' · tầng <b>' + esc(code) + '/01/01</b> · vị trí <b>' + esc(code) + '/01/01/A1</b>...';
    }

    /* ---------- tạo nhiều kệ: mỗi kệ thêm là một card bên dưới, có lưới riêng ---------- */

    // Mã kế tiếp: tăng phần số cuối, giữ số chữ số (01/05 -> 01/06, K9 -> K10).
    function nextCode(code) {
        var match = /^(.*?)(\d+)$/.exec(code || '');
        if (!match) return '';
        var next = String(Number(match[2]) + 1);
        while (next.length < match[2].length) next = '0' + next;
        return match[1] + next;
    }

    function autoName(code) {
        return code ? 'Kệ/Tủ ' + code.split('/').pop() : '';
    }

    // Kệ đầu tiên dùng khung cố định trong HTML; kệ thêm sau được dựng trong #zs-drafts.
    var MAIN = { columns: el.columns, code: el.code, name: el.name, preview: el.preview };

    function creatingMany() {
        return !state.shelf && state.drafts.length > 0;
    }

    function ctxOf(index) {
        if (index === 0) return MAIN;
        var card = el.drafts.querySelector('[data-draft="' + index + '"]');
        return {
            columns: card.querySelector('.zs-columns'),
            code: card.querySelector('.zs-code-input'),
            name: card.querySelector('.zs-name-input'),
            preview: card.querySelector('.zs-preview')
        };
    }

    // Ghi trạng thái kệ đang làm việc vào bản nháp của nó.
    function saveDraft() {
        if (!creatingMany()) return;
        var draft = state.drafts[state.current];
        draft.code = el.code.value;
        draft.name = el.name.value;
        draft.nameTouched = state.nameTouched;
        draft.columns = state.columns;
    }

    // Các hàm vẽ/sửa lưới đều làm trên "kệ đang chọn" (el.columns, el.code, state.columns...),
    // nên đổi kệ chỉ cần trỏ lại các tham chiếu này sang card của kệ đó.
    function activate(index) {
        if (!creatingMany() || index === state.current) return;
        saveDraft();

        var ctx = ctxOf(index);
        var draft = state.drafts[index];
        state.current = index;
        el.columns = ctx.columns;
        el.code = ctx.code;
        el.name = ctx.name;
        el.preview = ctx.preview;
        state.columns = draft.columns;
        state.nameTouched = draft.nameTouched;
    }

    function eachDraft(fn) {
        var back = state.current;
        state.drafts.forEach(function (draft, i) {
            activate(i);
            fn(draft, i);
        });
        activate(back);
    }

    function renderAllColumns() {
        if (creatingMany()) eachDraft(renderColumns);
        else renderColumns();
    }

    function paintAllPreviews() {
        if (creatingMany()) eachDraft(paintPreview);
        else paintPreview();
    }

    function duplicateCode(code) {
        return creatingMany() && state.drafts.some(function (draft, i) {
            return i !== state.current && draft.code.trim() === code;
        });
    }

    function initDrafts() {
        state.drafts = [{ code: el.code.value, name: el.name.value, nameTouched: state.nameTouched, columns: state.columns }];
        state.current = 0;
        el.drafts.innerHTML = '';
        el.addShelf.hidden = !CFG.canCreate;
        el.foot.hidden = false;
    }

    function closeDrafts() {
        activate(0);
        state.drafts = [];
        state.current = 0;
        el.drafts.innerHTML = '';
        el.addShelf.hidden = true;
    }

    function renderDrafts() {
        activate(0);

        el.drafts.innerHTML = state.drafts.slice(1).map(function (draft, i) {
            var index = i + 1;
            return '<section class="zs-shelf zs-draft" data-draft="' + index + '">'
                + '<div class="zs-draft-head">'
                +   '<div class="zs-shelf-title mb-0"><i class="fas fa-layer-group"></i> Kệ/Tủ mới thứ ' + (index + 1) + ' trong ' + esc(state.warehouse.code) + '</div>'
                +   '<button type="button" class="btn btn-sm btn-outline-danger zs-draft-drop"><i class="fas fa-times mr-1"></i>Bỏ kệ này</button>'
                + '</div>'
                + '<div class="zs-form">'
                +   '<div class="form-group mb-0"><label>Mã kệ/tủ <span class="text-danger">*</span></label>'
                +     '<input type="text" class="form-control form-control-sm zs-code-input" maxlength="40" value="' + esc(draft.code) + '"></div>'
                +   '<div class="form-group mb-0 zs-grow"><label>Tên kệ/tủ <span class="text-danger">*</span></label>'
                +     '<input type="text" class="form-control form-control-sm zs-name-input" maxlength="255" value="' + esc(draft.name) + '"></div>'
                +   '<div class="zs-preview" hidden></div>'
                + '</div>'
                + '<div class="zs-columns"></div>'
                + '</section>';
        }).join('');

        eachDraft(function () {
            renderColumns();
            paintPreview();
        });
    }

    function draftCells(draft) {
        return draft.columns.reduce(function (sum, column) { return sum + columnCells(column); }, 0);
    }

    // Lỗi đầu tiên trong các kệ sắp tạo: { index, message } hoặc null.
    function draftProblem() {
        var codes = state.drafts.map(function (draft) { return draft.code.trim(); });

        for (var i = 0; i < state.drafts.length; i++) {
            var draft = state.drafts[i];
            var label = 'Kệ ' + (codes[i] || 'thứ ' + (i + 1));
            var columns = draft.columns.filter(function (column) { return !column.removed; });

            if (!codes[i] || !draft.name.trim()) return { index: i, message: label + ': chưa nhập đủ mã và tên kệ/tủ.' };
            if (codes.indexOf(codes[i]) !== i) return { index: i, message: 'Mã kệ/tủ ' + codes[i] + ' bị lặp giữa các kệ đang tạo.' };
            if (state.taken[codes[i]]) return { index: i, message: 'Mã kệ/tủ ' + codes[i] + ' đã tồn tại. Hãy đổi mã.' };
            if (!columns.length) return { index: i, message: label + ': phải có ít nhất một cột.' };

            var empty = columns.filter(function (column) { return !activeTiers(column).length; });
            if (empty.length) {
                return { index: i, message: label + ': ' + empty.map(function (c) { return c.label; }).join(', ') + ' không còn tầng nào.' };
            }
        }

        return null;
    }

    function draftCard(index) {
        return index === 0 ? MAIN.code.closest('.zs-shelf') : el.drafts.querySelector('[data-draft="' + index + '"]');
    }

    el.addShelf.addEventListener('click', function () {
        saveDraft();

        if (state.drafts.length >= CFG.maxBatch) {
            toast('warning', 'Tối đa ' + CFG.maxBatch + ' kệ/tủ mỗi lượt tạo.');
            return;
        }

        // Kệ mới sao chép cấu trúc của kệ cuối cùng để chỉ cần sửa chỗ khác biệt.
        var last = state.drafts[state.drafts.length - 1];
        var source = last.columns.filter(function (column) { return !column.removed; });
        if (!source.length) {
            toast('warning', 'Hãy khai cấu trúc cột/tầng cho kệ ' + (last.code.trim() || 'trước') + ' trước khi thêm kệ mới.');
            return;
        }

        var codes = state.drafts.map(function (draft) { return draft.code.trim(); });
        var code = nextCode(last.code.trim());
        while (code && codes.indexOf(code) >= 0) code = nextCode(code);

        state.drafts.push({
            code: code,
            name: autoName(code),
            nameTouched: false,
            columns: source.map(function (column, index) { return newColumn(index + 1, templateOf(column)); })
        });

        var index = state.drafts.length - 1;
        renderDrafts();
        activate(index);
        refreshCodeCheck();
        updateSummary();
        setDirty(true);

        draftCard(index).scrollIntoView({ behavior: 'smooth', block: 'start' });
        el.code.focus({ preventScroll: true });
        el.code.select();
    });

    el.drafts.addEventListener('click', function (event) {
        var drop = event.target.closest('.zs-draft-drop');
        if (!drop) return;

        var index = Number(drop.closest('[data-draft]').dataset.draft);
        activate(0);
        state.drafts.splice(index, 1);
        renderDrafts();
        paintAllPreviews();
        updateSummary();
        setDirty(true);
    });

    // Thao tác trong card nào thì chuyển "kệ đang chọn" sang kệ đó trước khi các xử lý khác chạy.
    ['pointerdown', 'click', 'input', 'change', 'focusin'].forEach(function (type) {
        el.editor.addEventListener(type, function (event) {
            var card = event.target.closest ? event.target.closest('[data-draft]') : null;
            if (card) activate(Number(card.dataset.draft));
        }, true);
    });

    // Sửa kệ có sẵn: chỉ hiện dòng xem trước khi đang đổi mã kệ.
    function paintRenamePreview() {
        var code = shelfCode();
        el.preview.hidden = !renamed();
        if (el.preview.hidden) return;

        if (codeTaken()) {
            el.preview.className = 'zs-preview is-bad';
            el.preview.innerHTML = 'Mã <b>' + esc(code) + '</b> đã tồn tại. Hãy đổi mã kệ/tủ.';
            return;
        }

        el.preview.className = 'zs-preview';
        el.preview.innerHTML = 'Đổi mã kệ <b>' + esc(state.shelf.code) + '</b> thành <b>' + esc(code) + '</b>: mã cột, tầng, vị trí con đổi theo'
            + ' (<b>' + esc(state.shelf.code) + '/01/01/A1</b> -> <b>' + esc(code) + '/01/01/A1</b>). Bấm "Lưu cấu trúc" để áp dụng.';
    }

    function refreshCodeCheck() {
        paintPreview();
        clearTimeout(state.codeTimer);

        var code = el.code.value.trim();
        if (!code || (state.shelf && code === state.shelf.code)) return;

        state.codeTimer = setTimeout(function () {
            getJson(CFG.checkCode + '?code=' + encodeURIComponent(code)).then(function (data) {
                state.taken[data.code] = !!data.taken;
                paintAllPreviews();
            }).catch(function () { /* bỏ qua, máy chủ sẽ kiểm lại khi lưu */ });
        }, 250);
    }

    /* lưới chọn nhanh kiểu Word */

    var PICKER_MAX_ROWS = Math.min(CFG.maxTiers, 20);
    var PICKER_MAX_COLS = Math.min(CFG.maxLocations, 60);
    var pickerRows = 6;
    var pickerCols = 20;

    var pickerHover = { r: 0, c: 0 };

    // Khung chọn nhanh có ô chọn chiều tầng riêng, nên lúc đang mở thì ẩn ô "Đánh số tầng" ở đầu form.
    function showPicker(on) {
        el.picker.hidden = !on;
        el.tierOrderGroup.hidden = on;
    }

    function openPicker() {
        pickerRows = 6;
        pickerCols = 20;
        pickerHover = { r: 0, c: 0 };
        el.pickerCols.value = 1;
        el.pickerDepth.value = 1;
        el.readout.textContent = 'Chưa chọn';
        showPicker(true);
        drawPicker(0, 0);
    }

    // Số tầng của hàng thứ r (tính từ trên) khi đang chọn `rows` hàng, theo chiều đánh số.
    function pickerTier(r, rows) {
        return state.tierOrder === 'top_down' ? r : rows - r + 1;
    }

    function drawPicker(hoverRow, hoverCol) {
        el.pickerGrid.style.setProperty('--cols', pickerCols);
        var html = '';
        for (var r = 1; r <= pickerRows; r++) {
            html += '<span class="zs-plabel" data-r="' + r + '"></span>';
            for (var c = 1; c <= pickerCols; c++) {
                html += '<span class="zs-pcell" data-r="' + r + '" data-c="' + c + '"></span>';
            }
        }
        el.pickerGrid.innerHTML = html;
        paintPicker(hoverRow, hoverCol);
    }

    // Tô vùng đang chọn và ghi số tầng cạnh từng hàng (Tầng 01 tô đậm để thấy ngay nằm trên hay dưới).
    function paintPicker(r, c) {
        pickerHover = { r: r, c: c };
        el.pickerGrid.querySelectorAll('.zs-pcell').forEach(function (node) {
            node.classList.toggle('is-on', Number(node.dataset.r) <= r && Number(node.dataset.c) <= c);
        });
        el.pickerGrid.querySelectorAll('.zs-plabel').forEach(function (node) {
            var row = Number(node.dataset.r);
            var tier = row <= r ? pickerTier(row, r) : 0;
            node.textContent = tier ? seg(tier) : '';
            node.classList.toggle('is-first', tier === 1);
        });
        paintReadout();
    }

    function paintReadout() {
        var r = pickerHover.r;
        var c = pickerHover.c;
        if (!r || !c) {
            el.readout.textContent = state.tierOrder === 'top_down' ? 'Tầng 01 nằm trên cùng' : 'Tầng 01 nằm dưới cùng';
            return;
        }
        var cols = pickerColumns();
        var depth = pickerDepth();
        el.readout.textContent = cols + ' cột × ' + r + ' tầng × ' + c + ' ngang × ' + depth + ' sâu = ' + num(cols * r * c * depth) + ' ô';
    }

    document.querySelectorAll('.zs-order-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            setTierOrder(btn.dataset.order);
            paintPicker(pickerHover.r, pickerHover.c);
            renderAllColumns();
        });
    });

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
            paintPicker(r, c);
        }
    });

    function pickerColumns() {
        return Math.min(Math.max(Number(el.pickerCols.value) || 1, 1), CFG.maxColumns);
    }

    function pickerDepth() {
        return Math.min(Math.max(Number(el.pickerDepth.value) || 1, 1), CFG.maxDepth);
    }

    el.pickerGrid.addEventListener('click', function (event) {
        var cell = event.target.closest('.zs-pcell');
        if (!cell) return;

        var rows = Number(cell.dataset.r);
        var cols = Number(cell.dataset.c);
        var template = [];
        var depth = pickerDepth();
        for (var p = 1; p <= rows; p++) template.push({ position: p, max: cols, depth: depth });

        var start = maxPosition(state.columns);
        for (var i = 1; i <= pickerColumns(); i++) {
            state.columns.push(newColumn(start + i, template));
        }

        showPicker(false);
        renderColumns();
        setDirty(true);
    });

    /* các khối cột */

    var lockedSize = null;

    // Phần cố định của một khối cột ngoài các ô: nhãn tầng, tay kéo, ô số, nút gỡ, lề, khoảng cách.
    var COLUMN_CHROME = 190;

    // Mọi cột dùng chung một cỡ ô; các cột xếp ngang nên chia bề ngang cho đủ số cột,
    // không vừa thì giữ cỡ tối thiểu và cho cuộn ngang.
    function cellSize() {
        if (lockedSize) return lockedSize;

        var widest = 1;
        state.columns.forEach(function (column) {
            column.tiers.forEach(function (tier) { widest = Math.max(widest, tier.max, tier.origMax); });
        });

        var count = Math.max(state.columns.length, 1);
        var perColumn = Math.floor((el.columns.parentElement.clientWidth - 140) / count) - COLUMN_CHROME;
        return Math.max(12, Math.min(Math.floor(perColumn / widest) - 2, 26));
    }

    // Chữ cái hiện trong ô theo loại lưu trữ (để trống = dùng chung).
    var TYPE_LETTERS = { material: 'V', chemical: 'H', standard: 'C' };

    // Ô (p ngang, d sâu) có trên lưới mới / có trong sức chứa cũ không.
    function inNew(tier, p, d) { return p <= tier.max && d <= tier.depth; }
    function inOld(tier, p, d) { return p <= tier.origMax && d <= tier.origDepth; }

    function cellsMarkup(column, tier) {
        var width = Math.max(tier.max, tier.origMax);
        var height = Math.max(tier.depth, tier.origDepth);
        var markup = '';

        // Dãy trong cùng vẽ trên, dãy 1 (sát cửa) vẽ dưới cùng, sát thước chữ mép trước.
        for (var d = height; d >= 1; d--) {
            for (var p = 1; p <= width; p++) {
                var key = cellKey(p, d);
                var isNew = inNew(tier, p, d);
                var isOld = inOld(tier, p, d);
                var cls, status;

                if (!isNew && !isOld) {
                    // Nằm ngoài cả lưới cũ lẫn mới (ví dụ thu hẹp ngang nhưng tăng sâu): chừa trống
                    markup += '<span class="zs-cell is-void"></span>';
                    continue;
                }
                if (tier.removed || column.removed || !isNew) {
                    // Ô đang mở nằm ngoài lưới mới (hoặc cả tầng/cột bị gỡ)
                    cls = 'is-cut'; status = tier.wasRemoved || column.wasRemoved ? 'đang khoá' : 'sẽ khoá';
                } else if (!isOld) {
                    // Ngoài sức chứa cũ: ô chưa có thì sinh mới, ô từng bị khoá do thu nhỏ thì mở lại
                    cls = 'is-new'; status = tier.codes[key] ? 'sẽ mở lại' : 'sẽ tạo';
                } else if (tier.busy.has(key)) {
                    cls = 'is-busy'; status = 'có lô hàng';
                } else if (tier.off.has(key)) {
                    cls = 'is-off'; status = 'đang khoá';
                } else {
                    cls = 'is-free'; status = 'trống';
                }
                var code = cellCode(column, tier, tier.codes[key]) || tierCode(column, tier) + '/' + letters(p) + d;
                var attr = cls === 'is-new' || cls === 'is-cut' ? null : tier.attrs[key];
                var extra = '';
                var letter = '';

                if (attr) {
                    var styles = [];
                    var zt = attr.zone_type && CFG.zoneColors[attr.zone_type];
                    if (attr.shown) styles.push('--loc:' + esc(attr.shown));
                    if (zt) styles.push('--zt:' + zt, '--zt-soft:' + soften(zt));
                    extra = ' data-id="' + attr.id + '" data-zone="' + esc(zt ? attr.zone_type : '') + '"'
                        + (styles.length ? ' style="' + styles.join(';') + '"' : '');
                    cls += (attr.shown ? ' has-color' : '') + (zt ? ' has-zone' : '')
                        + (state.picked.has(attr.id) ? ' is-picked' : '');
                    status += ' · ' + (CFG.zoneTypes[attr.zone_type] || 'Chưa phân loại')
                        + ' · ' + (CFG.locationTypes[attr.item_type] || 'Dùng chung');
                    letter = TYPE_LETTERS[attr.item_type] || '';
                }

                markup += '<span class="zs-cell ' + cls + '"' + extra + ' title="' + esc(code) + ' - ' + esc(status) + '">' + letter + '</span>';
            }
        }

        return markup;
    }

    // Khối một tầng: thước số dãy bên trái, lưới ô, thước chữ ô ngang dọc mép trước.
    function blockMarkup(column, tier) {
        var width = Math.max(tier.max, tier.origMax);
        var height = Math.max(tier.depth, tier.origDepth);
        var depthRule = '';
        var frontRule = '';
        for (var d = height; d >= 1; d--) depthRule += '<span>' + d + '</span>';
        for (var p = 1; p <= width; p++) frontRule += '<span>' + letters(p) + '</span>';

        var locked = column.removed;
        var off = tier.removed ? ' disabled' : '';
        var note = tier.lastBusy ? ' (ô ' + letters(tier.lastBusy) + ', dãy ' + tier.lastBusyDepth + ' đang có lô hàng)' : '';

        return '<div class="zs-block" style="--cols:' + width + '">'
            + '<div class="zs-depth-rule" title="Dãy tính từ cửa vào trong (1 = sát cửa)">' + depthRule + '</div>'
            + '<div class="zs-cells">' + cellsMarkup(column, tier) + '</div>'
            + '<div class="zs-side">' + (locked ? '' :
                '<input type="number" class="zs-depth" data-pos="' + tier.position + '" min="1" max="' + CFG.maxDepth
                + '" value="' + tier.depth + '" title="Số dãy sâu (1 = sát cửa)"' + off + '>') + '</div>'
            + '<div class="zs-front-rule" title="Ô ngang dọc mép trước tầng">' + frontRule + '</div>'
            + '<div class="zs-corner">' + (locked || tier.removed ? '' :
                '<button type="button" class="zs-handle" data-pos="' + tier.position + '" title="Kéo ngang để đổi số ô ngang, kéo lên/xuống để đổi số dãy sâu' + esc(note) + '"></button>') + '</div>'
            + '<div class="zs-under">' + (locked ? '' :
                '<input type="number" class="zs-num" data-pos="' + tier.position + '" min="1" max="' + CFG.maxLocations
                + '" value="' + tier.max + '" title="Số ô ngang (A, B, C...)"' + off + '>') + '</div>'
            + '</div>';
    }

    function rowMarkup(column, tier) {
        return '<div class="zs-row' + (tier.removed ? ' is-removed' : '') + '" data-pos="' + tier.position + '">'
            + '<button type="button" class="zs-row-label zs-rename zs-tier-rename" data-pos="' + tier.position + '" title="'
            + esc(tierCode(column, tier)) + ' - bấm để đổi tên / mã tầng">' + esc(tier.label) + '</button>'
            + blockMarkup(column, tier)
            + (column.removed ? '' :
                '<button type="button" class="zs-drop" data-pos="' + tier.position + '" title="'
                + (tier.removed ? 'Dùng lại tầng này' : 'Gỡ tầng này') + '"><i class="fas fa-' + (tier.removed ? 'undo' : 'times') + '"></i></button>')
            + '</div>';
    }

    function columnMarkup(column, index) {
        var tiers = activeTiers(column);
        var visible = column.tiers
            .filter(function (tier) { return !(tier.removed && tier.origMax === 0 && !tier.existing); })
            .sort(function (a, b) { return state.tierOrder === 'top_down' ? a.position - b.position : b.position - a.position; });

        var meta = column.removed
            ? (column.wasRemoved ? 'Đang khoá' : 'Sẽ khoá toàn bộ cột')
            : tiers.length + ' tầng · ' + num(columnCells(column)) + ' vị trí';

        var head = '<div class="zs-col-head">'
            + '<div class="zs-col-bar">'
            +   '<div class="zs-col-name">'
            +     '<div class="zs-col-title"><i class="fas fa-grip-lines-vertical"></i> '
            +       '<button type="button" class="zs-rename zs-col-rename" title="Bấm để đổi tên cột">' + esc(column.label) + '<i class="fas fa-pen"></i></button>'
            +       ' <span class="zs-code">' + esc(columnCode(column)) + '</span></div>'
            +     '<small class="zs-col-meta">' + meta + '</small>'
            +   '</div>'
            +   '<div class="zs-col-actions">'
            +   (column.removed
                    ? '<button type="button" class="btn btn-sm btn-outline-success zs-col-drop"><i class="fas fa-undo mr-1"></i>Dùng lại cột</button>'
                    : '<button type="button" class="zs-icon-btn zs-col-copy" title="Nhân bản: thêm một cột mới có cùng cấu trúc tầng"><i class="fas fa-clone"></i></button>'
                      + '<button type="button" class="zs-icon-btn is-danger zs-col-drop" title="Gỡ cột"><i class="fas fa-times"></i></button>')
            +   '</div>'
            + '</div>'
            + (column.removed ? '' :
                '<div class="zs-bulk"><label class="mb-0" title="Đặt số ô ngang × số dãy sâu cho mọi tầng của cột (để trống ô nào thì giữ nguyên chiều đó)">Mọi tầng</label>'
                + '<div class="zs-bulk-group">'
                +   '<input type="number" class="zs-bulk-value" min="1" max="' + CFG.maxLocations + '" placeholder="Ngang" title="Số ô ngang">'
                +   '<span class="zs-x">×</span>'
                +   '<input type="number" class="zs-bulk-depth" min="1" max="' + CFG.maxDepth + '" placeholder="Sâu" title="Số dãy sâu">'
                +   '<button type="button" class="zs-bulk-apply" title="Áp dụng cho mọi tầng của cột"><i class="fas fa-check"></i></button>'
                + '</div></div>')
            + '</div>';

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

    // Màu nhạt của một mã hex để tô ô trống (rgba thay cho color-mix, chạy được trên trình duyệt cũ).
    function soften(hex) {
        var n = parseInt(hex.slice(1), 16);
        return 'rgba(' + ((n >> 16) & 255) + ',' + ((n >> 8) & 255) + ',' + (n & 255) + ',0.22)';
    }

    var VIEW_KEY = 'wms.zone.cellView';
    try { state.cellView = localStorage.getItem(VIEW_KEY) === 'status' ? 'status' : 'zone'; } catch (err) { state.cellView = 'zone'; }

    function applyView() {
        el.columns.classList.toggle('zs-by-zone', state.cellView === 'zone');
        document.querySelectorAll('.zs-view-btn').forEach(function (btn) {
            btn.classList.toggle('active', btn.dataset.view === state.cellView);
        });
        byId('zs-legend-zone').hidden = state.cellView !== 'zone';
        byId('zs-legend-status').hidden = state.cellView !== 'status';
        renderZoneLegend();
    }

    // Chú thích phân loại kèm số vị trí đang mở; bấm vào để chọn nhanh các ô thuộc phân loại đó.
    function renderZoneLegend() {
        var box = byId('zs-legend-zone');
        if (state.cellView !== 'zone') return;

        var counts = { '': 0 };
        state.columns.forEach(function (column) {
            column.tiers.forEach(function (tier) {
                Object.keys(tier.attrs || {}).forEach(function (key) {
                    var k = splitKey(key);
                    if (column.removed || tier.removed || !inNew(tier, k.p, k.d) || !inOld(tier, k.p, k.d) || tier.off.has(key)) return;
                    var type = tier.attrs[key].zone_type;
                    type = type && CFG.zoneTypes[type] ? type : '';
                    counts[type] = (counts[type] || 0) + 1;
                });
            });
        });

        var tag = state.shelf && CFG.canUpdate ? 'button' : 'span';
        var items = Object.keys(CFG.zoneTypes).map(function (type) {
            return { type: type, label: CFG.zoneTypes[type], color: CFG.zoneColors[type] };
        }).concat([{ type: '', label: 'Chưa phân loại', color: '#94A3B8' }]);

        box.innerHTML = items.map(function (item) {
            var count = counts[item.type] || 0;
            return '<' + tag + (tag === 'button' ? ' type="button" title="Chọn các vị trí ' + esc(item.label) + '"' : '')
                + ' class="zs-zone-chip' + (count ? '' : ' is-empty') + '" data-zone="' + item.type + '"'
                + ' style="--zt:' + item.color + ';--zt-soft:' + soften(item.color) + '">'
                + '<i class="zs-swatch"></i>' + esc(item.label) + ' <b>' + num(count) + '</b></' + tag + '>';
        }).join('');
    }

    document.querySelectorAll('.zs-view-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            state.cellView = btn.dataset.view;
            try { localStorage.setItem(VIEW_KEY, state.cellView); } catch (err) {}
            applyView();
        });
    });

    byId('zs-legend-zone').addEventListener('click', function (event) {
        var chip = event.target.closest('button.zs-zone-chip');
        if (!chip) return;
        clearPicked();
        el.columns.querySelectorAll('.zs-cell[data-id]').forEach(function (node) {
            if ((node.dataset.zone || '') === chip.dataset.zone && !node.classList.contains('is-off')) pick(node, true);
        });
        updatePicked();
    });

    function setTierOrder(order) {
        state.tierOrder = order === 'top_down' ? 'top_down' : 'bottom_up';
        el.tierOrder.value = state.tierOrder;
        el.tierOrder.disabled = false;
        el.editor.classList.toggle('zs-top-down', state.tierOrder === 'top_down');
        document.querySelectorAll('.zs-order-btn').forEach(function (btn) {
            btn.classList.toggle('active', btn.dataset.order === state.tierOrder);
        });
    }

    // Đổi chiều đánh số: chỉ xếp lại lưới, mã tầng giữ theo số thứ tự; áp cho mọi kệ đang tạo.
    el.tierOrder.addEventListener('change', function () {
        setTierOrder(el.tierOrder.value);
        renderAllColumns();
        setDirty(true);
    });

    function renderColumns() {
        state.columns.sort(function (a, b) { return a.position - b.position; });
        el.columns.style.setProperty('--zs-cell', cellSize() + 'px');
        el.columns.classList.toggle('zs-pickable', !!state.shelf && CFG.canUpdate);
        el.columns.innerHTML = state.columns.map(columnMarkup).join('')
            + (state.columns.length || state.current > 0
                ? '<button type="button" class="zs-col-add" title="Thêm một cột bên phải, cùng cấu trúc tầng với cột cuối">'
                    + '<i class="fas fa-plus"></i> Thêm cột</button>'
                : '');
        el.foot.hidden = !state.columns.length && !creatingMany();
        updateSummary();
        applyView();
    }

    // Kéo một hàng chỉ đổi hàng đó, nên vẽ lại mình nó thay vì dựng lại cả lưới.
    function renderRow(column, tier, section) {
        var row = section.querySelector('.zs-row[data-pos="' + tier.position + '"]');
        if (!row) {
            renderColumns();
            return;
        }

        // Chỉ vẽ lại thước và ô; góc kéo đang giữ con trỏ thì giữ nguyên phần tử.
        var fresh = document.createElement('div');
        fresh.innerHTML = blockMarkup(column, tier);
        fresh = fresh.firstChild;
        var block = row.querySelector('.zs-block');
        block.style.setProperty('--cols', Math.max(tier.max, tier.origMax));
        ['.zs-depth-rule', '.zs-cells', '.zs-front-rule'].forEach(function (selector) {
            block.querySelector(selector).innerHTML = fresh.querySelector(selector).innerHTML;
        });
        row.querySelector('.zs-num').value = tier.max;
        row.querySelector('.zs-depth').value = tier.depth;
        updateSummary();
    }

    function updateSummary() {
        var lists = creatingMany()
            ? state.drafts.map(function (draft, i) { return i === state.current ? state.columns : draft.columns; })
            : [state.columns];
        var cols = [].concat.apply([], lists).filter(function (column) { return !column.removed; });
        var tiers = cols.reduce(function (sum, column) { return sum + activeTiers(column).length; }, 0);
        var cells = cols.reduce(function (sum, column) { return sum + columnCells(column); }, 0);
        var text = (lists.length > 1 ? lists.length + ' kệ · ' : '')
            + cols.length + ' cột · ' + tiers + ' tầng · ' + num(cells) + ' vị trí';

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

    // Không cho thu nhỏ qua ô / dãy xa nhất đang có lô hàng: thao tác sai bị chặn ngay ở tay.
    function clampMax(tier, value) {
        return Math.min(Math.max(value, Math.max(1, tier.lastBusy)), CFG.maxLocations);
    }

    function clampDepth(tier, value) {
        return Math.min(Math.max(value, Math.max(1, tier.lastBusyDepth)), CFG.maxDepth);
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

    el.editor.addEventListener('pointerdown', function (event) {
        var handle = event.target.closest('.zs-handle');
        if (!handle) return;

        var found = columnAt(handle);
        var tier = found && findTier(found.column, handle.dataset.pos);
        if (!tier || tier.removed) return;

        var size = parseFloat(getComputedStyle(el.columns).getPropertyValue('--zs-cell')) || 14;
        var step = size + 2;
        // Tính theo độ dời so với lúc bấm: ngang đổi số ô ngang, lên/xuống đổi số dãy sâu.
        var startX = event.clientX;
        var startY = event.clientY;
        var startMax = tier.max;
        var startDepth = tier.depth;

        lockedSize = size;
        handle.classList.add('is-dragging');
        handle.setPointerCapture(event.pointerId);
        event.preventDefault();

        function onMove(moveEvent) {
            var nextMax = clampMax(tier, startMax + Math.round((moveEvent.clientX - startX) / step));
            var nextDepth = clampDepth(tier, startDepth + Math.round((startY - moveEvent.clientY) / step));
            if (nextMax === tier.max && nextDepth === tier.depth) return;
            tier.max = nextMax;
            tier.depth = nextDepth;
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

    el.editor.addEventListener('change', function (event) {
        var input = event.target.closest('.zs-num, .zs-depth');
        if (!input) return;

        var found = columnAt(input);
        var tier = found && findTier(found.column, input.dataset.pos);
        if (!tier) return;

        var isDepth = input.classList.contains('zs-depth');
        var next = isDepth ? clampDepth(tier, Number(input.value) || 1) : clampMax(tier, Number(input.value) || 1);
        if (next > Number(input.value) && tier.lastBusy) {
            toast('warning', tier.label + ' không thể nhỏ hơn ' + next + (isDepth ? ' dãy' : ' ô ngang') + ' vì ô '
                + (isDepth ? 'ở dãy ' + tier.lastBusyDepth : letters(tier.lastBusy)) + ' đang có lô hàng.');
        }
        if (isDepth) tier.depth = next;
        else tier.max = next;
        setDirty(true);
        renderColumns();
    });

    el.editor.addEventListener('click', function (event) {
        if (event.target.closest('.zs-col-add')) {
            var last = state.columns.filter(function (column) { return !column.removed; }).pop();
            addColumn(templateOf(last));
            return;
        }

        var found = columnAt(event.target);
        if (!found) return;
        var column = found.column;

        var renameTier = event.target.closest('.zs-tier-rename');
        if (renameTier) {
            askTier(column, findTier(column, renameTier.dataset.pos));
            return;
        }

        if (event.target.closest('.zs-col-rename')) {
            askName('Đổi tên cột', column.label, function (value) {
                column.label = value;
                column.custom = true;
                setDirty(true);
                renderColumns();
            });
            return;
        }

        var drop = event.target.closest('.zs-drop');
        if (drop) {
            var tier = findTier(column, drop.dataset.pos);
            if (!tier) return;

            if (!tier.removed && tier.lastBusy) {
                toast('warning', tier.label + ' đang có lô hàng, không gỡ được. Hãy chuyển hàng đi trước.');
                return;
            }

            if (tier.existing) {
                tier.removed = !tier.removed;
            } else {
                dropNew(column.tiers, tier, function (x) { if (!x.custom) x.label = 'Tầng ' + seg(x.position); });
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
            column.tiers.push(newTier(highest + 1, last ? last.max : 10, last ? last.depth : 1));
            setDirty(true);
            renderColumns();
            return;
        }

        if (event.target.closest('.zs-bulk-apply')) {
            var value = Number(found.section.querySelector('.zs-bulk-value').value);
            var depthValue = Number(found.section.querySelector('.zs-bulk-depth').value);
            if (!value && !depthValue) return;

            var blocked = [];
            activeTiers(column).forEach(function (item) {
                var parts = [];
                if (value) {
                    var next = clampMax(item, value);
                    if (next !== value && item.lastBusy) parts.push('ngang tối thiểu ' + next);
                    item.max = next;
                }
                if (depthValue) {
                    var nextDepth = clampDepth(item, depthValue);
                    if (nextDepth !== depthValue && item.lastBusy) parts.push('sâu tối thiểu ' + nextDepth);
                    item.depth = nextDepth;
                }
                if (parts.length) blocked.push(item.label + ' (' + parts.join(', ') + ')');
            });
            setDirty(true);
            renderColumns();

            if (blocked.length) {
                toast('warning', 'Một số tầng không thu nhỏ được vì đang có lô hàng: ' + blocked.join('; '));
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
                    if (!column.tiers.length) column.tiers.push(newTier(1, 10, 1));
                }
            } else {
                dropNew(state.columns, column, function (x) { if (!x.custom) x.label = 'Cột ' + seg(x.position); });
            }
            setDirty(true);
            renderColumns();
        }
    });

    var SEGMENT_RE = /^[A-Za-z0-9._-]{1,20}$/;

    // Đổi tên hiển thị và mã tầng (đoạn cuối trong mã tầng / mã vị trí) trong cùng một hộp.
    function askTier(column, tier) {
        var prefix = columnCode(column) + '/';
        Swal.fire({
            title: 'Đổi tên / mã tầng',
            html: '<div class="zs-ask">'
                + '<label for="zs-ask-name">Tên tầng</label>'
                + '<input id="zs-ask-name" class="swal2-input" maxlength="100" value="' + esc(tier.label) + '">'
                + '<label for="zs-ask-code">Mã tầng</label>'
                + '<div class="zs-ask-code"><span>' + esc(prefix) + '</span>'
                + '<input id="zs-ask-code" class="swal2-input" maxlength="20" value="' + esc(tierSegment(tier)) + '"></div>'
                + '<div id="zs-ask-preview" class="zs-ask-preview"></div>'
                + '</div>',
            showCancelButton: true,
            confirmButtonText: 'Đổi',
            cancelButtonText: 'Huỷ',
            confirmButtonColor: '#2E7BC4',
            cancelButtonColor: '#94A3B8',
            focusConfirm: false,
            didOpen: function () {
                var code = byId('zs-ask-code');
                var paint = function () {
                    byId('zs-ask-preview').innerHTML = 'Mã vị trí: <b>' + esc(prefix + (code.value.trim() || '?') + '/A1') + '</b>, '
                        + esc(prefix + (code.value.trim() || '?') + '/B1') + '...';
                };
                code.addEventListener('input', paint);
                paint();
            },
            preConfirm: function () {
                var name = byId('zs-ask-name').value.trim();
                var code = byId('zs-ask-code').value.trim();
                if (!name) return Swal.showValidationMessage('Tên tầng không được để trống.');
                if (!SEGMENT_RE.test(code)) {
                    return Swal.showValidationMessage('Mã tầng 1-20 ký tự, chỉ gồm chữ không dấu, số và dấu . _ - (không có khoảng trắng, không có dấu /).');
                }
                var clash = column.tiers.find(function (other) { return other !== tier && tierSegment(other) === code; });
                if (clash) return Swal.showValidationMessage('Mã ' + code + ' đang là mã của ' + clash.label + ' trong cột này.');
                return { name: name, code: code };
            }
        }).then(function (result) {
            if (!result.isConfirmed) return;
            tier.label = result.value.name;
            tier.custom = true;
            if (result.value.code !== tierSegment(tier)) tier.segment = result.value.code;
            setDirty(true);
            renderColumns();
        });
    }

    function askName(title, current, done) {
        Swal.fire({
            title: title,
            input: 'text',
            inputValue: current,
            inputAttributes: { maxlength: 100 },
            showCancelButton: true,
            confirmButtonText: 'Đổi tên',
            cancelButtonText: 'Huỷ',
            confirmButtonColor: '#2E7BC4',
            cancelButtonColor: '#94A3B8',
            inputValidator: function (value) { return value.trim() ? null : 'Tên không được để trống.'; }
        }).then(function (result) {
            if (result.isConfirmed) done(result.value.trim());
        });
    }

    /* ---------- chọn vị trí để đổi loại lưu trữ / màu ---------- */

    function clearPicked() {
        state.picked.clear();
        document.querySelectorAll('.zs-cell.is-picked').forEach(function (node) { node.classList.remove('is-picked'); });
        updatePicked();
    }

    function updatePicked() {
        el.locCount.textContent = num(state.picked.size);
        el.locApply.disabled = !state.picked.size;
        el.locClear.disabled = !state.picked.size;
    }

    function pick(node, on) {
        var id = Number(node.dataset.id);
        if (on) state.picked.add(id);
        else state.picked.delete(id);
        node.classList.toggle('is-picked', on);
    }

    // Bấm hoặc rê chuột qua các ô để chọn/bỏ chọn; ô đầu tiên quyết định đang chọn hay đang bỏ.
    el.editor.addEventListener('pointerdown', function (event) {
        if (event.shiftKey && event.button === 0 && startMarquee(event)) return;
        var node = event.target.closest('.zs-pickable .zs-cell[data-id]');
        if (!node) return;
        event.preventDefault();
        state.painting = !state.picked.has(Number(node.dataset.id));
        pick(node, state.painting);
        updatePicked();
    });

    el.editor.addEventListener('pointerover', function (event) {
        if (state.painting === null) return;
        var node = event.target.closest('.zs-pickable .zs-cell[data-id]');
        if (!node) return;
        pick(node, state.painting);
        updatePicked();
    });

    document.addEventListener('pointerup', function () {
        state.painting = null;
    });

    // Shift + kéo chuột: quét khung chữ nhật, mọi ô chạm khung được thêm vào lựa chọn.
    // Điểm đầu neo theo nội dung vùng cột nên cuộn trang giữa chừng vẫn đúng.
    var marquee = null;

    function startMarquee(event) {
        if (!el.columns.classList.contains('zs-pickable')) return false;
        if (!el.columns.contains(event.target)) return false;
        if (event.target.closest('input, select, textarea, button, a')) return false;

        event.preventDefault();
        var box = el.columns.getBoundingClientRect();
        marquee = {
            x: event.clientX - box.left + el.columns.scrollLeft,
            y: event.clientY - box.top + el.columns.scrollTop,
            cx: event.clientX,
            cy: event.clientY,
            base: new Set(state.picked),
            node: document.createElement('div')
        };
        marquee.node.className = 'zs-marquee';
        document.body.appendChild(marquee.node);
        el.columns.classList.add('is-marquee');
        drawMarquee();
        return true;
    }

    function drawMarquee() {
        if (!marquee) return;
        var box = el.columns.getBoundingClientRect();
        var sx = box.left - el.columns.scrollLeft + marquee.x;
        var sy = box.top - el.columns.scrollTop + marquee.y;
        var left = Math.min(sx, marquee.cx), right = Math.max(sx, marquee.cx);
        var top = Math.min(sy, marquee.cy), bottom = Math.max(sy, marquee.cy);

        marquee.node.style.left = left + 'px';
        marquee.node.style.top = top + 'px';
        marquee.node.style.width = (right - left) + 'px';
        marquee.node.style.height = (bottom - top) + 'px';

        el.columns.querySelectorAll('.zs-cell[data-id]').forEach(function (node) {
            var r = node.getBoundingClientRect();
            var inside = r.right >= left && r.left <= right && r.bottom >= top && r.top <= bottom;
            pick(node, inside || marquee.base.has(Number(node.dataset.id)));
        });
        updatePicked();
    }

    function endMarquee() {
        if (!marquee) return;
        marquee.node.remove();
        marquee = null;
        el.columns.classList.remove('is-marquee');
    }

    document.addEventListener('pointermove', function (event) {
        if (!marquee) return;
        marquee.cx = event.clientX;
        marquee.cy = event.clientY;
        drawMarquee();
    });
    document.addEventListener('pointerup', endMarquee);
    document.addEventListener('pointercancel', endMarquee);
    window.addEventListener('scroll', drawMarquee, true);

    el.locAll.addEventListener('click', function () {
        el.columns.querySelectorAll('.zs-cell[data-id]').forEach(function (node) { pick(node, true); });
        updatePicked();
    });

    el.locClear.addEventListener('click', clearPicked);

    el.locApply.addEventListener('click', function () {
        var setZone = el.locZone.value !== '__keep';
        var setType = el.locType.value !== '__keep';

        if (!state.picked.size) return;
        if (!setZone && !setType) {
            toast('warning', 'Chọn phân loại hoặc loại lưu trữ cần áp cho các vị trí đang chọn.');
            return;
        }

        el.locApply.disabled = true;
        post(CFG.saveLocations, {
            shelf_id: state.shelf.id,
            ids: Array.from(state.picked),
            set_zone: setZone,
            zone_type: setZone ? el.locZone.value : null,
            set_type: setType,
            item_type: setType ? el.locType.value : null
        }).then(function (result) {
            el.locApply.disabled = false;
            if (!result.ok) {
                toast('error', result.data.message || 'Không cập nhật được vị trí.');
                return;
            }

            var byId = {};
            result.data.locations.forEach(function (attr) { byId[attr.id] = attr; });
            state.columns.forEach(function (column) {
                column.tiers.forEach(function (tier) {
                    Object.keys(tier.attrs).forEach(function (position) {
                        var attr = byId[tier.attrs[position].id];
                        if (attr) tier.attrs[position] = attr;
                    });
                });
            });

            window.zoneStructureStale = true;
            state.picked.clear();
            updatePicked();
            renderColumns();
            toast('success', result.data.message);
        });
    });

    updatePicked();

    // Ô chọn Phân loại hiện kèm chấm màu cố định của từng phân loại
    function zoneOption(option) {
        if (!option.id && option.id !== '') return option.text;
        var color = option.element && option.element.getAttribute('data-color');
        var kind = color ? '' : (option.id === '__keep' ? ' is-keep' : ' is-none');
        var dot = document.createElement('span');
        dot.innerHTML = '<i class="zone-dot' + kind + '"' + (color ? ' style="--dot:' + color + '"' : '') + '></i>' + esc(option.text);
        return dot;
    }

    if (window.jQuery && jQuery.fn.select2) {
        jQuery(el.locZone).select2({
            theme: 'bootstrap4',
            minimumResultsForSearch: Infinity,
            templateResult: zoneOption,
            templateSelection: zoneOption
        });
    }

    function addColumn(template) {
        var highest = maxPosition(state.columns);
        var active = state.columns.filter(function (column) { return !column.removed; }).length;

        if (active >= CFG.maxColumns || highest >= 99) {
            toast('warning', 'Tối đa ' + CFG.maxColumns + ' cột mỗi kệ/tủ.');
            return;
        }

        var tiers = template && template.length ? template : [{ position: 1, max: 10, depth: 1 }];
        state.columns.push(newColumn(highest + 1, tiers));
        setDirty(true);
        renderColumns();

        var sections = el.columns.querySelectorAll('.zs-col');
        if (sections.length) sections[sections.length - 1].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' });
    }

    window.addEventListener('resize', function () {
        if (state.view === 'editor') renderAllColumns();
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

    function payloadColumns(list) {
        return list
            .filter(function (column) { return !column.removed; })
            .map(function (column) {
                return {
                    position: column.position,
                    name: column.label,
                    tiers: activeTiers(column).map(function (tier) {
                        return { position: tier.position, max: tier.max, depth: tier.depth, name: tier.label, code: tier.segment || null };
                    })
                };
            });
    }

    // Tạo nhiều kệ một lượt: kiểm từng tab, lỗi ở kệ nào thì mở đúng tab đó.
    function saveDrafts() {
        saveDraft();

        var problem = draftProblem();
        if (problem) {
            draftCard(problem.index).scrollIntoView({ behavior: 'smooth', block: 'start' });
            toast('warning', problem.message);
            return;
        }

        var shelves = state.drafts.map(function (draft) {
            return { code: draft.code.trim(), name: draft.name.trim(), columns: payloadColumns(draft.columns) };
        });
        var cells = state.drafts.reduce(function (sum, draft) { return sum + draftCells(draft); }, 0);

        Swal.fire({
            icon: 'question',
            title: 'Tạo ' + shelves.length + ' kệ/tủ?',
            text: shelves.map(function (item, i) {
                return item.code + ' (' + item.columns.length + ' cột · ' + num(draftCells(state.drafts[i])) + ' vị trí)';
            }).join(', ') + ' - tổng ' + num(cells) + ' vị trí.',
            showCancelButton: true,
            confirmButtonText: 'Tạo',
            cancelButtonText: 'Huỷ',
            confirmButtonColor: '#2E7BC4',
            cancelButtonColor: '#94A3B8'
        }).then(function (choice) {
            if (!choice.isConfirmed) return;

            var template = state.columns.filter(function (c) { return !c.removed; }).map(templateOf);
            el.save.disabled = true;

            post(CFG.apply, {
                warehouse_id: state.warehouse.id,
                tier_order: state.tierOrder,
                shelves: shelves
            }).then(function (result) {
                el.save.disabled = false;
                if (!result.ok) {
                    toast('error', result.data.message || 'Không lưu được.');
                    return;
                }

                window.zoneStructureStale = true;
                setDirty(false);
                afterCreate(result.data.message, template);
            });
        });
    }

    el.save.addEventListener('click', function () {
        if (creatingMany() && state.drafts.length > 1) {
            saveDrafts();
            return;
        }

        var code = shelfCode();
        var name = el.name.value.trim();

        if (!code || !name) {
            toast('warning', 'Nhập mã và tên kệ/tủ trước khi lưu.');
            return;
        }
        if ((!state.shelf || renamed()) && codeTaken()) {
            toast('warning', 'Mã kệ/tủ ' + code + ' đã tồn tại. Hãy đổi mã.');
            return;
        }

        var columns = payloadColumns(state.columns);
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
                else if (!tier.removed && (tier.max < tier.origMax || tier.depth < tier.origDepth)) shrunk++;
            });
        });

        var warning = [];
        if (droppedCols) warning.push('gỡ ' + droppedCols + ' cột');
        if (droppedTiers) warning.push('gỡ ' + droppedTiers + ' tầng');
        if (shrunk) warning.push('thu nhỏ ' + shrunk + ' tầng');

        var text = columns.length + ' cột · ' + num(cells) + ' vị trí.';
        if (renamed()) {
            text += ' Đổi mã kệ ' + state.shelf.code + ' thành ' + code + ': mã mọi cột, tầng, vị trí con đổi theo, nhãn đã in theo mã cũ cần in lại.';
        }
        if (warning.length) {
            text += ' Lượt này sẽ ' + warning.join(', ') + ': các vị trí bị loại sẽ bị KHOÁ (không xoá) và không chọn được khi nhập kho nữa.';
        }

        Swal.fire({
            icon: warning.length || renamed() ? 'warning' : 'question',
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
                tier_order: state.tierOrder,
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
