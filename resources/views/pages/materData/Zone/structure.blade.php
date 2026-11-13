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
            <button type="button" id="zs-layout-btn" class="btn btn-sm btn-outline-primary" hidden
                    title="Đặt các kệ/tủ vào đúng vị trí trong kho/phòng, xem cả phòng ở 3D">
                <i class="fas fa-vector-square mr-1"></i> Mặt bằng kho/phòng
            </button>
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

        {{-- Mặt bằng kho/phòng: vị trí đặt các kệ/tủ trong phòng --}}
        @include('pages.materData.Zone.layout')

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
                    <div id="zs-preview" class="zs-preview" hidden></div>
                </div>

                {{-- Thông số kệ: 4 thẻ cùng kiểu Cột/kệ × Tầng/cột × Ngang/tầng × Sâu/tầng, đổi số là lưới bên dưới đổi theo --}}
                <div id="zs-spec" class="zs-spec"></div>

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

    /* ---------- Thông số kệ: 4 thẻ cùng kiểu ---------- */
    .zs-spec {
        margin-top: 16px;
        padding: 14px 16px 16px;
        border: 1px solid var(--primary-soft);
        border-radius: var(--border-radius-lg);
        background: var(--bg-neutral);
    }

    .zs-spec:empty {
        display: none;
    }

    .zs-spec-head {
        display: flex;
        flex-wrap: wrap;
        gap: 8px 14px;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
    }

    .zs-spec-title {
        font-weight: 700;
        color: var(--primary);
        font-size: 0.9rem;
    }

    .zs-spec-title i {
        margin-right: 6px;
    }

    .zs-spec-formula {
        font-size: 0.82rem;
        color: #475569;
        background: #fff;
        border: 1px solid var(--primary-soft);
        border-radius: 999px;
        padding: 4px 14px;
    }

    .zs-spec-formula b {
        color: var(--primary);
    }

    .zs-spec-formula .is-mixed {
        color: #B45309;
    }

    .zs-spec-formula.is-over,
    .zs-spec-formula.is-over b {
        color: #B91C1C;
        border-color: #FECACA;
    }

    /* Trái: 4 thẻ thông số (2 × 2) · Phải: mô hình 3D */
    .zs-spec-body {
        display: grid;
        grid-template-columns: minmax(380px, 460px) minmax(0, 1fr);
        gap: 18px;
        align-items: stretch;
    }

    .zs-spec-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px 22px;
        align-content: start;
    }

    .zs-spec-card {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        padding: 12px 12px 10px;
        border: 1px solid #e2e8f0;
        border-radius: var(--border-radius-lg);
        background: #fff;
        box-shadow: var(--shadow-sm);
        transition: border-color 0.2s, box-shadow 0.2s, transform 0.2s;
    }

    .zs-spec-card:hover,
    .zs-spec-card:focus-within {
        border-color: var(--primary-light);
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.12);
        transform: translateY(-1px);
    }

    /* dấu × nối các thẻ: kệ = cột × tầng × ngang × sâu */
    .zs-spec-card:nth-child(even)::before {
        content: '×';
        position: absolute;
        left: -17px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 1rem;
        font-weight: 700;
        color: var(--primary-lighter);
    }

    .zs-spec-card.is-mixed {
        border-style: dashed;
        border-color: #F59E0B;
    }

    .zs-spec-label {
        display: flex;
        align-items: center;
        gap: 8px;
        align-self: stretch;
        font-size: 0.84rem;
        font-weight: 700;
        color: var(--text-main);
    }

    .zs-spec-no {
        flex: 0 0 auto;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: var(--primary-soft);
        color: var(--primary);
        font-size: 0.72rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .zs-spec-label small {
        display: block;
        font-weight: 500;
        color: #64748b;
        font-size: 0.72rem;
    }

    .zs-fig {
        width: 100%;
        max-width: 170px;
        height: 84px;
    }

    .zs-fig .f-post {
        stroke: var(--primary-lighter);
        stroke-width: 2;
        stroke-linecap: round;
        fill: none;
    }

    .zs-fig .f-line {
        stroke: var(--primary-lighter);
        stroke-width: 1;
    }

    .zs-fig .f-box {
        fill: var(--primary-soft);
        stroke: var(--primary);
        stroke-width: 1.2;
    }

    .zs-fig .f-box.is-first {
        fill: var(--primary-lighter);
    }

    .zs-fig .f-ghost {
        fill: #fff;
        stroke: var(--primary-lighter);
        stroke-width: 1;
    }

    .zs-fig .f-front {
        stroke: var(--primary-dark);
        stroke-width: 2.5;
        stroke-linecap: round;
    }

    .zs-fig text {
        font-size: 8px;
        font-weight: 700;
        fill: #64748b;
        font-family: inherit;
    }

    .zs-fig text.is-first {
        fill: var(--primary-dark);
    }

    .zs-stepper {
        display: flex;
        align-items: stretch;
        border: 1px solid #cbd5e1;
        border-radius: var(--border-radius-md);
        overflow: hidden;
        background: #fff;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .zs-stepper:focus-within {
        border-color: var(--primary);
        box-shadow: 0 0 0 2px rgba(var(--primary-rgb), 0.15);
    }

    .zs-stepper button {
        width: 34px;
        border: none;
        background: var(--primary-soft);
        color: var(--primary);
        font-size: 0.8rem;
        cursor: pointer;
        transition: background 0.2s, color 0.2s;
    }

    .zs-stepper button:hover:not(:disabled) {
        background: var(--primary);
        color: #fff;
    }

    .zs-stepper button:disabled {
        color: #cbd5e1;
        cursor: not-allowed;
    }

    .zs-stepper input {
        width: 64px;
        border: none;
        text-align: center;
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--primary);
        padding: 2px 0;
        outline: none;
        -moz-appearance: textfield;
    }

    .zs-stepper input::-webkit-outer-spin-button,
    .zs-stepper input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    .zs-stepper input::placeholder {
        color: #F59E0B;
    }

    .zs-stepper input:disabled {
        background: #fff;
    }

    .zs-spec-cap {
        min-height: 18px;
        font-size: 0.74rem;
        color: #64748b;
        text-align: center;
    }

    .zs-spec-cap b {
        color: var(--primary-dark);
    }

    .zs-spec-card.is-mixed .zs-spec-cap {
        color: #B45309;
    }

    .zs-spec-order {
        border: 1px solid var(--primary-lighter);
        background: #fff;
        color: var(--primary-dark);
        border-radius: 999px;
        padding: 1px 10px;
        font-size: 0.74rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }

    .zs-spec-order:hover:not(:disabled) {
        background: var(--primary-soft);
        border-color: var(--primary);
    }

    .zs-spec-order:disabled {
        cursor: default;
        opacity: 0.8;
    }

    /* ---------- Mô hình 3D (bộ vẽ chung pages/shared/shelf3d): màu ô theo trạng thái ---------- */
    .zs-spec .w3d-cell.is-plan {
        background: var(--primary-soft);
        border-color: var(--primary);
    }

    .zs-spec .w3d-cell.is-free {
        background: #EEF2F6;
        border-color: #A9B4C2;
    }

    .zs-spec .w3d-cell.is-busy {
        background: var(--primary);
        border-color: var(--primary-dark);
    }

    .zs-spec .w3d-cell.is-off {
        background: #CFD4D9;
        border-color: #9AA1A8;
    }

    .zs-spec .w3d-cell.is-new {
        background: #DCFCE7;
        border-color: #16A34A;
    }

    @media (max-width: 1100px) {
        .zs-spec-body {
            grid-template-columns: minmax(0, 1fr);
        }
    }

    @media (max-width: 480px) {
        .zs-spec-grid {
            grid-template-columns: minmax(0, 1fr);
        }

        .zs-spec-card::before {
            display: none;
        }
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

@include('pages.shared.shelf3d')

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
    "layout": @json(route('pages.materData.zone.structure.layout')),
    "saveLayout": @json(route('pages.materData.zone.structure.saveLayout')),
    "locationTypes": @json($locationTypes),
    "zoneTypes": @json(\App\Support\ZoneType::TYPES),
    "zoneColors": @json(\App\Support\ZoneType::DEFAULT_COLORS),
    "maxColumns": {{ (int) $structureLimits['maxColumns'] }},
    "maxTiers": {{ (int) $structureLimits['maxTiers'] }},
    "maxLocations": {{ (int) $structureLimits['maxLocations'] }},
    "maxDepth": {{ (int) $structureLimits['maxDepth'] }},
    "maxBatch": {{ (int) $structureLimits['maxBatch'] }},
    "maxCells": {{ (int) $structureLimits['maxCells'] }},
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
        layoutBtn: byId('zs-layout-btn'),
        layout: byId('zs-layout'),
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
        preview: byId('zs-preview'),
        drafts: byId('zs-drafts'),
        addShelf: byId('zs-add-shelf'),
        spec: byId('zs-spec'),
        columns: byId('zs-columns'),
        foot: byId('zs-foot'),
        summary: byId('zs-summary')
    };

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

    // Nhãn ô ngang thứ n: đánh số nguyên từ 1 (1, 2, 3...).
    function letters(n) {
        return String(n);
    }

    // Đoạn cuối mã vị trí: <số ô ngang 2 chữ số>-<số dãy sâu>, ví dụ 05.2 (khớp máy chủ).
    function cellSeg(position, depth) {
        return seg(position) + '.' + depth;
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
        el.layout.hidden = view !== 'layout';
        el.layoutBtn.hidden = view !== 'shelves';
        el.editor.hidden = !isEditor;
        el.save.hidden = !isEditor;
        el.reset.hidden = !isEditor;
        el.total.hidden = !isEditor;
        el.filter.hidden = isEditor || view === 'layout';
        el.filter.value = '';
        el.note.hidden = true;

        el.add.hidden = isEditor || view === 'layout' || !CFG.canCreate;
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

        if (state.view === 'layout') {
            parts.push('<span class="zs-crumb-sep">/</span>');
            parts.push('<button type="button" class="zs-crumb is-current"><i class="fas fa-vector-square mr-1"></i>Mặt bằng</button>');
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

    // Mặt bằng kho/phòng (Zone/layout): đặt các kệ/tủ vào phòng, xem cả phòng ở 3D
    el.layoutBtn.addEventListener('click', function () {
        if (!state.warehouse || !window.ZoneLayout || !confirmLeave()) return;
        show('layout');
        window.ZoneLayout.open(state.warehouse, {
            onDirty: setDirty,
            onOpenShelf: function (id) { loadShelf(id); }
        });
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

        // Kệ mới dựng sẵn theo thông số lần trước (hoặc mặc định), chỉ cần chỉnh 4 con số ở khung Thông số.
        var fresh = !(template && template.length);
        state.columns = (fresh ? specTemplate(lastSpec()) : template).map(function (tiers, index) { return newColumn(index + 1, tiers); });
        renderColumns();
        setDirty(!fresh);

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
        el.spec.innerHTML = '';
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
                // Kệ chưa có cột nào: dựng sẵn theo thông số mặc định để chỉnh tiếp ở khung Thông số.
                var empty = !state.columns.length && CFG.canUpdate;
                if (empty) {
                    state.columns = specTemplate(lastSpec()).map(function (tiers, index) { return newColumn(index + 1, tiers); });
                }
                renderColumns();
                renderCrumbs();
                setDirty(empty);
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
    var MAIN = { columns: el.columns, code: el.code, name: el.name, preview: el.preview, spec: el.spec };

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
            preview: card.querySelector('.zs-preview'),
            spec: card.querySelector('.zs-spec')
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
        el.spec = ctx.spec;
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
                + '<div class="zs-spec"></div>'
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

    /* ---------- Thông số kệ: Cột/kệ × Tầng/cột × Ngang/tầng × Sâu/tầng ---------- */

    // Bốn thông số khai cùng một kiểu thẻ; đổi một số chỉ áp đúng chiều đó cho cả kệ đang thao tác,
    // các chiều khác (và chỗ đã chỉnh riêng ở từng cột/tầng) giữ nguyên.
    var SPEC_DIMS = [
        { key: 'columns', label: 'Số cột', sub: 'của một kệ/tủ', min: 1, limit: 'maxColumns' },
        { key: 'tiers', label: 'Số tầng', sub: 'của mỗi cột', min: 1, limit: 'maxTiers' },
        { key: 'max', label: 'Vị trí ngang', sub: 'mỗi tầng · 1, 2, 3...', min: 1, limit: 'maxLocations' },
        { key: 'depth', label: 'Vị trí sâu', sub: 'mỗi tầng · dãy 1 sát cửa', min: 1, limit: 'maxDepth' }
    ];
    var SPEC_DEFAULT = { columns: 1, tiers: 4, max: 5, depth: 1 };
    var SPEC_KEY = 'wms.zone.spec';

    // Thông số lần tạo kệ trước của người dùng này (chỉ để gợi ý, đọc hỏng thì dùng mặc định).
    function lastSpec() {
        var saved = null;
        try { saved = JSON.parse(localStorage.getItem(SPEC_KEY) || 'null'); } catch (err) { saved = null; }
        var spec = {};
        SPEC_DIMS.forEach(function (dim) {
            var value = saved ? Number(saved[dim.key]) : 0;
            spec[dim.key] = value >= dim.min && value <= CFG[dim.limit] ? value : SPEC_DEFAULT[dim.key];
        });
        return spec;
    }

    function rememberSpec() {
        if (state.shelf) return;
        var values = specValues();
        var spec = {};
        SPEC_DIMS.forEach(function (dim) { spec[dim.key] = values[dim.key] || values.sample[dim.key]; });
        try { localStorage.setItem(SPEC_KEY, JSON.stringify(spec)); } catch (err) { /* bỏ qua */ }
    }

    // Mẫu tầng cho `spec.columns` cột: [[{position, max, depth}...], ...]
    function specTemplate(spec) {
        var tiers = [];
        for (var p = 1; p <= spec.tiers; p++) tiers.push({ position: p, max: spec.max, depth: spec.depth });
        var list = [];
        for (var c = 0; c < spec.columns; c++) list.push(tiers);
        return list;
    }

    function liveColumns() {
        return state.columns.filter(function (column) { return !column.removed; })
            .sort(function (a, b) { return a.position - b.position; });
    }

    function liveTiers(column) {
        return activeTiers(column).sort(function (a, b) { return a.position - b.position; });
    }

    // Giá trị hiện tại của từng chiều: số nếu cả kệ đồng đều, null nếu các cột/tầng khác nhau.
    // `sample` = giá trị của cột/tầng đầu tiên, dùng để vẽ hình minh hoạ khi chiều đó không đều.
    function specValues() {
        var cols = liveColumns();
        var sets = { tiers: {}, max: {}, depth: {} };
        var sample = { columns: cols.length, tiers: 0, max: 0, depth: 0 };

        cols.forEach(function (column) {
            var tiers = activeTiers(column);
            sets.tiers[tiers.length] = true;
            if (!sample.tiers) sample.tiers = tiers.length;
            tiers.forEach(function (tier) {
                sets.max[tier.max] = true;
                sets.depth[tier.depth] = true;
                if (!sample.max) { sample.max = tier.max; sample.depth = tier.depth; }
            });
        });

        function one(set) {
            var keys = Object.keys(set);
            return keys.length > 1 ? null : Number(keys[0] || 0);
        }

        return {
            columns: cols.length,
            tiers: one(sets.tiers),
            max: one(sets.max),
            depth: one(sets.depth),
            sample: {
                columns: sample.columns || 1,
                tiers: sample.tiers || 1,
                max: sample.max || 1,
                depth: sample.depth || 1
            }
        };
    }

    /* hình minh hoạ: cùng khung, cùng màu, mỗi thẻ tô đậm đúng chiều nó đếm */

    var FIG = { w: 160, h: 84 };

    function svg(body) {
        return '<svg class="zs-fig" viewBox="0 0 ' + FIG.w + ' ' + FIG.h + '" aria-hidden="true">' + body + '</svg>';
    }

    function rect(x, y, w, h, cls) {
        return '<rect x="' + x.toFixed(1) + '" y="' + y.toFixed(1) + '" width="' + Math.max(w, 1).toFixed(1)
            + '" height="' + Math.max(h, 1).toFixed(1) + '" rx="1.5" class="' + cls + '"/>';
    }

    function line(x1, y1, x2, y2, cls) {
        return '<line class="' + cls + '" x1="' + x1.toFixed(1) + '" y1="' + y1.toFixed(1)
            + '" x2="' + x2.toFixed(1) + '" y2="' + y2.toFixed(1) + '"/>';
    }

    function text(x, y, value, cls, anchor) {
        return '<text x="' + x.toFixed(1) + '" y="' + y.toFixed(1) + '" text-anchor="' + (anchor || 'middle') + '"'
            + (cls ? ' class="' + cls + '"' : '') + '>' + esc(value) + '</text>';
    }

    // Nhãn thứ i (0-based) khi chỉ vẽ `shown` trên tổng `total` mục: mục áp chót thành "…", mục cuối là số thật.
    function capLabel(i, shown, total, format) {
        if (total > shown && i === shown - 1) return format(total);
        if (total > shown && i === shown - 2) return '…';
        return format(i + 1);
    }

    // Mặt trước kệ: các cột (khoang) đứng cạnh nhau, vạch mờ là các tầng.
    function figColumns(v) {
        var n = Math.min(v.columns, 6);
        var t = Math.min(v.tiers, 5);
        var x0 = 10, y0 = 14, w = 140, h = 58, bw = w / n, out = '';

        for (var i = 0; i < n; i++) {
            var x = x0 + i * bw;
            out += rect(x + 2, y0, bw - 4, h, 'f-box' + (i === 0 ? ' is-first' : ''));
            for (var j = 1; j < t; j++) {
                out += line(x + 2, y0 + h * j / t, x + bw - 2, y0 + h * j / t, 'f-line');
            }
            out += text(x + bw / 2, y0 - 4, capLabel(i, n, v.columns, seg), i === 0 ? 'is-first' : '');
        }
        out += line(6, y0 + h + 4, 154, y0 + h + 4, 'f-post');
        return svg(out);
    }

    // Một cột: các tầng xếp chồng, Tầng 01 tô đậm ở dưới hoặc trên theo chiều đánh số.
    function figTiers(v) {
        var n = Math.min(v.tiers, 6);
        var x0 = 58, y0 = 6, w = 48, h = 70, th = h / n, out = '';
        var topDown = state.tierOrder === 'top_down';

        out += line(x0 - 3, 2, x0 - 3, y0 + h + 4, 'f-post');
        out += line(x0 + w + 3, 2, x0 + w + 3, y0 + h + 4, 'f-post');

        for (var i = 0; i < n; i++) {
            // i = 0 là hàng trên cùng của hình
            var index = topDown ? i : n - 1 - i;
            var y = y0 + i * th;
            out += rect(x0, y + 1.5, w, th - 3, 'f-box' + (index === 0 ? ' is-first' : ''));
            out += text(x0 - 9, y + th / 2 + 3, capLabel(index, n, v.tiers, seg), index === 0 ? 'is-first' : '', 'end');
        }
        return svg(out);
    }

    // Mặt trước một tầng: các vị trí ngang A, B, C... dọc mép trước.
    function figMax(v) {
        var n = Math.min(v.max, 8);
        var x0 = 8, y0 = 20, w = 144, h = 34, bw = w / n, out = '';

        out += line(4, y0 + h + 3, 156, y0 + h + 3, 'f-post');
        for (var i = 0; i < n; i++) {
            out += rect(x0 + i * bw + 1.5, y0, bw - 3, h, 'f-box' + (i === 0 ? ' is-first' : ''));
            out += text(x0 + i * bw + bw / 2, y0 + h + 15, capLabel(i, n, v.max, letters), i === 0 ? 'is-first' : '');
        }
        return svg(out);
    }

    // Nhìn từ trên xuống một tầng (phối cảnh): dãy 1 sát cửa ở trước, các dãy sâu dần vào trong.
    function figDepth(v) {
        var rows = Math.min(v.depth, 5);
        var cols = Math.min(v.max, 6);
        var dy = Math.min(54 / rows, 13), dx = dy * 0.75;
        var front = 12 + (54 + rows * dy) / 2;
        var x0 = 22, cw = (124 - rows * dx) / cols, out = '';

        function pt(c, r) {
            return (x0 + c * cw + r * dx).toFixed(1) + ',' + (front - r * dy).toFixed(1);
        }

        for (var r = rows - 1; r >= 0; r--) {
            for (var c = 0; c < cols; c++) {
                out += '<polygon class="f-box' + (r === 0 ? ' is-first' : '') + '" points="'
                    + pt(c, r) + ' ' + pt(c + 1, r) + ' ' + pt(c + 1, r + 1) + ' ' + pt(c, r + 1) + '"/>';
            }
            out += text(x0 + (r + 0.5) * dx - 5, front - (r + 0.5) * dy + 3, capLabel(r, rows, v.depth, String),
                r === 0 ? 'is-first' : '', 'end');
        }
        out += line(x0, front + 1, x0 + cols * cw, front + 1, 'f-front');
        out += text(x0 + cols * cw / 2, front + 14, 'Cửa / mép trước', 'is-first');
        return svg(out);
    }

    var FIGS = { columns: figColumns, tiers: figTiers, max: figMax, depth: figDepth };

    function specCaption(key, value) {
        if (value === null) return 'Đang khác nhau giữa các ' + (key === 'tiers' ? 'cột' : 'tầng');
        if (key === 'columns') return 'Cột <b>01</b> → <b>' + seg(value) + '</b>';
        if (key === 'max') return 'Ô <b>1</b> → <b>' + letters(value) + '</b> dọc mép trước';
        if (key === 'depth') return 'Dãy <b>1</b> (sát cửa) → <b>' + value + '</b>';
        return '';
    }

    function specEditable() {
        return state.shelf ? CFG.canUpdate : CFG.canCreate;
    }

    // Khung tĩnh của 4 thẻ; số, hình, chú thích được vẽ lại ở paintSpec.
    function specSkeleton(node) {
        var off = specEditable() ? '' : ' disabled';
        node.innerHTML = '<div class="zs-spec-head">'
            +   '<span class="zs-spec-title"><i class="fas fa-ruler-combined"></i>Thông số kệ/tủ</span>'
            +   '<span class="zs-spec-formula"></span>'
            + '</div>'
            + '<div class="zs-spec-body">'
            + '<div class="zs-spec-grid">'
            + SPEC_DIMS.map(function (dim, i) {
                return '<div class="zs-spec-card" data-dim="' + dim.key + '">'
                    + '<div class="zs-spec-label"><span class="zs-spec-no">' + (i + 1) + '</span>'
                    +   '<span>' + dim.label + '<small>' + dim.sub + '</small></span></div>'
                    + '<div class="zs-spec-fig"></div>'
                    + '<div class="zs-stepper">'
                    +   '<button type="button" class="zs-spec-step" data-step="-1" title="Bớt 1"' + off + '><i class="fas fa-minus"></i></button>'
                    +   '<input type="number" class="zs-spec-input" min="' + dim.min + '" max="' + CFG[dim.limit] + '"'
                    +     ' aria-label="' + esc(dim.label + ' ' + dim.sub) + '"' + off + '>'
                    +   '<button type="button" class="zs-spec-step" data-step="1" title="Thêm 1"' + off + '><i class="fas fa-plus"></i></button>'
                    + '</div>'
                    + '<div class="zs-spec-cap">' + (dim.key === 'tiers'
                        ? '<button type="button" class="zs-spec-order" title="Bấm để đổi chiều đánh số tầng"' + off + '></button>'
                        : '') + '</div>'
                    + '</div>';
            }).join('')
            + '</div>'
            + stage3dMarkup()
            + '</div>';
        node.dataset.ready = specEditable() ? 'edit' : 'view';
    }

    function paintSpec() {
        var node = el.spec;
        if (!node || state.view !== 'editor') return;
        if (node.dataset.ready !== (specEditable() ? 'edit' : 'view') || !node.querySelector('.zs-spec-card')) specSkeleton(node);

        var v = specValues();
        var focused = document.activeElement;
        var figValues = {
            columns: v.columns || 1,
            tiers: v.tiers || v.sample.tiers,
            max: v.max || v.sample.max,
            depth: v.depth || v.sample.depth
        };

        SPEC_DIMS.forEach(function (dim) {
            var card = node.querySelector('.zs-spec-card[data-dim="' + dim.key + '"]');
            var value = v[dim.key];
            var input = card.querySelector('.zs-spec-input');

            card.classList.toggle('is-mixed', value === null);
            if (input !== focused) input.value = value === null ? '' : value;
            input.placeholder = value === null ? '≠' : '';
            input.title = value === null ? 'Đang khác nhau - nhập số để đặt chung cho cả kệ' : '';

            if (specEditable()) {
                var shown = value === null ? v.sample[dim.key] : value;
                card.querySelector('[data-step="-1"]').disabled = value !== null && shown <= dim.min;
                card.querySelector('[data-step="1"]').disabled = value !== null && shown >= CFG[dim.limit];
            }

            card.querySelector('.zs-spec-fig').innerHTML = FIGS[dim.key](figValues);

            var cap = card.querySelector('.zs-spec-cap');
            if (dim.key === 'tiers') {
                cap.querySelector('.zs-spec-order').innerHTML = '<i class="fas fa-arrow-'
                    + (state.tierOrder === 'top_down' ? 'down' : 'up') + ' mr-1"></i>'
                    + (state.tierOrder === 'top_down' ? 'Tầng 01 trên cùng' : 'Tầng 01 dưới cùng');
            } else {
                cap.innerHTML = specCaption(dim.key, value);
            }
        });

        var cells = state.columns.reduce(function (sum, column) { return sum + columnCells(column); }, 0);
        var parts = [[v.columns, 'cột'], [v.tiers, 'tầng'], [v.max, 'ngang'], [v.depth, 'sâu']].map(function (part) {
            return part[0] === null ? '<span class="is-mixed">≠ ' + part[1] + '</span>' : '<b>' + part[0] + '</b> ' + part[1];
        });
        var formula = node.querySelector('.zs-spec-formula');
        formula.classList.toggle('is-over', cells > CFG.maxCells);
        formula.innerHTML = parts.join(' × ') + ' = <b>' + num(cells) + '</b> vị trí'
            + (cells > CFG.maxCells ? ' (vượt mức ' + num(CFG.maxCells) + ' mỗi lượt lưu)' : '');

        schedule3d(node);
    }

    // Đặt số cột đang dùng: thêm thì mở lại cột cũ đang khoá trước rồi mới tạo cột mới (giống cột cuối),
    // bớt thì gỡ từ cột bên phải; cột đang có lô hàng thì dừng lại.
    function setColumnCount(count, warnings) {
        var live = liveColumns();

        while (live.length < count) {
            var dormant = state.columns.filter(function (column) { return column.removed; })
                .sort(function (a, b) { return a.position - b.position; })[0];
            if (dormant) {
                dormant.removed = false;
                if (dormant.wasRemoved) {
                    dormant.tiers.forEach(function (tier) { tier.removed = false; });
                    if (!dormant.tiers.length) dormant.tiers.push(newTier(1, 10, 1));
                }
            } else {
                if (maxPosition(state.columns) >= 99) break;
                var last = live[live.length - 1];
                var template = last ? templateOf(last) : specTemplate(lastSpec())[0];
                state.columns.push(newColumn(maxPosition(state.columns) + 1, template));
            }
            live = liveColumns();
        }

        while (live.length > count) {
            var column = live[live.length - 1];
            if (columnBusy(column)) {
                warnings.push(column.label + ' đang có lô hàng, không gỡ được');
                break;
            }
            if (column.existing) column.removed = true;
            else dropNew(state.columns, column, function (x) { if (!x.custom) x.label = 'Cột ' + seg(x.position); });
            live = liveColumns();
        }
    }

    // Đặt số tầng đang dùng cho mọi cột: thêm ở trên cùng (giống tầng cao nhất), bớt từ tầng cao nhất.
    function setTierCount(count, warnings) {
        liveColumns().forEach(function (column) {
            var live = liveTiers(column);

            while (live.length < count) {
                var dormant = column.tiers.filter(function (tier) { return tier.removed; })
                    .sort(function (a, b) { return a.position - b.position; })[0];
                if (dormant) {
                    dormant.removed = false;
                } else {
                    var highest = maxPosition(column.tiers);
                    if (highest >= 99) break;
                    var top = live[live.length - 1];
                    column.tiers.push(newTier(highest + 1, top ? top.max : SPEC_DEFAULT.max, top ? top.depth : 1));
                }
                live = liveTiers(column);
            }

            while (live.length > count) {
                var tier = live[live.length - 1];
                if (tier.lastBusy) {
                    warnings.push(column.label + ' - ' + tier.label + ' đang có lô hàng, không gỡ được');
                    break;
                }
                if (tier.existing) tier.removed = true;
                else dropNew(column.tiers, tier, function (x) { if (!x.custom) x.label = 'Tầng ' + seg(x.position); });
                live = liveTiers(column);
            }
        });
    }

    // Đặt số vị trí ngang / sâu cho mọi tầng; không thu nhỏ qua ô đang có lô hàng.
    function setCellCount(key, count, warnings) {
        liveColumns().forEach(function (column) {
            activeTiers(column).forEach(function (tier) {
                var next = key === 'max' ? clampMax(tier, count) : clampDepth(tier, count);
                if (next > count && tier.lastBusy) {
                    warnings.push(column.label + ' - ' + tier.label + ' giữ ' + next + (key === 'max' ? ' ô ngang' : ' dãy sâu'));
                }
                tier[key] = next;
            });
        });
    }

    function applySpec(key, value) {
        var dim = SPEC_DIMS.find(function (item) { return item.key === key; });
        if (!dim || !specEditable()) return;

        value = Math.round(Number(value));
        if (!value || value < dim.min) value = dim.min;
        if (value > CFG[dim.limit]) {
            toast('warning', 'Tối đa ' + CFG[dim.limit] + ' - ' + dim.label.toLowerCase() + ' ' + dim.sub.split(' · ')[0] + '.');
            value = CFG[dim.limit];
        }

        var warnings = [];
        if (key === 'columns') setColumnCount(value, warnings);
        else if (key === 'tiers') setTierCount(value, warnings);
        else setCellCount(key, value, warnings);

        setDirty(true);
        renderColumns();
        rememberSpec();

        if (warnings.length) {
            toast('warning', 'Không thu nhỏ được chỗ đang có lô hàng: ' + warnings.join('; ') + '. Hãy chuyển hàng đi trước.');
        }
    }

    function specCurrent(key) {
        var v = specValues();
        return v[key] === null ? v.sample[key] : v[key];
    }

    el.editor.addEventListener('click', function (event) {
        var order = event.target.closest('.zs-spec-order');
        if (order) {
            setTierOrder(state.tierOrder === 'top_down' ? 'bottom_up' : 'top_down');
            renderAllColumns();
            setDirty(true);
            return;
        }

        var step = event.target.closest('.zs-spec-step');
        if (!step) return;
        var key = step.closest('.zs-spec-card').dataset.dim;
        applySpec(key, specCurrent(key) + Number(step.dataset.step));
    });

    el.editor.addEventListener('change', function (event) {
        var input = event.target.closest('.zs-spec-input');
        if (!input) return;
        if (input.value === '') {
            paintSpec();
            return;
        }
        applySpec(input.closest('.zs-spec-card').dataset.dim, input.value);
    });

    el.editor.addEventListener('keydown', function (event) {
        var input = event.target.closest('.zs-spec-input');
        if (!input) return;
        // Mũi tên lên/xuống tăng giảm như nút +/-, Enter để áp số vừa gõ.
        if (event.key === 'ArrowUp' || event.key === 'ArrowDown') {
            event.preventDefault();
            var key = input.closest('.zs-spec-card').dataset.dim;
            applySpec(key, specCurrent(key) + (event.key === 'ArrowUp' ? 1 : -1));
            input.value = specCurrent(key);
        } else if (event.key === 'Enter') {
            event.preventDefault();
            input.blur();
        }
    });

    /* ---------- Mô hình 3D của kệ (bộ vẽ chung pages/shared/shelf3d) ---------- */

    // Kệ dựng theo đúng lưới sau khi lưu: vách giữa các cột, mặt từng tầng, từng ô Ngang × Sâu
    // (dãy 1 sát mép trước). Kệ quá nhiều ô thì vẽ mặt tầng dạng lưới kẻ thay cho từng ô.
    var MAX_3D_CELLS = 4000;

    function stage3dMarkup() {
        return '<div class="w3d" title="Kéo chuột để xoay · nhấp đúp để về góc nghiêng">' + WmsShelf3d.markup() + '</div>';
    }

    // Đang tô theo phân loại thì ô 3D cũng mang màu phân loại (ô có hàng đậm, ô trống nhạt) như lưới 2D.
    function cell3dStyle(tier, key, cls) {
        if (!state.shelf || state.cellView !== 'zone' || (cls !== 'is-free' && cls !== 'is-busy')) return '';
        var attr = tier.attrs[key];
        var color = attr && attr.zone_type && CFG.zoneColors[attr.zone_type];
        if (!color) return '';
        return ';background:' + (cls === 'is-busy' ? color : soften(color)) + ';border-color:' + color;
    }

    // Trạng thái hiển thị của một ô trên mô hình (chỉ các ô còn trong lưới sau khi lưu).
    function cell3dClass(tier, key) {
        if (!state.shelf) return 'is-plan';
        var k = splitKey(key);
        if (!inOld(tier, k.p, k.d)) return 'is-new';
        if (tier.busy.has(key)) return 'is-busy';
        if (tier.off.has(key)) return 'is-off';
        return 'is-free';
    }

    function render3d(node) {
        var stage = node && node.querySelector('.w3d');
        if (!stage || !window.WmsShelf3d) return;

        var cols = liveColumns().filter(function (column) { return activeTiers(column).length; });
        var total = cols.reduce(function (sum, column) { return sum + columnCells(column); }, 0);
        var drawCells = total <= MAX_3D_CELLS;
        var topDown = state.tierOrder === 'top_down';
        var levels = cols.reduce(function (max, column) { return Math.max(max, activeTiers(column).length); }, 1);

        WmsShelf3d.render(stage, {
            unit: 16, gap: 3, pad: 7, tierHeight: 46,
            levels: levels,
            columns: cols.map(function (column) {
                return {
                    label: column.label,
                    code: columnCode(column),
                    tiers: liveTiers(column).map(function (tier, ti) {
                        var cells = [];
                        if (drawCells) {
                            for (var d = 1; d <= tier.depth; d++) {
                                for (var p = 1; p <= tier.max; p++) {
                                    var key = cellKey(p, d);
                                    var cls = cell3dClass(tier, key);
                                    var code = cellCode(column, tier, tier.codes[key]) || tierCode(column, tier) + '/' + cellSeg(p, d);
                                    cells.push({
                                        p: p, d: d, cls: cls, style: cell3dStyle(tier, key, cls),
                                        title: code, label: code.split('/').pop()
                                    });
                                }
                            }
                        }
                        return {
                            label: tierSegment(tier),
                            first: tier.position === 1,
                            // Hàng vật lý tính từ dưới lên; đánh số từ trên xuống thì Tầng 01 nằm trên cùng.
                            level: topDown ? levels - 1 - ti : ti,
                            max: tier.max,
                            depth: tier.depth,
                            texture: !drawCells,
                            title: column.label + ' · ' + tier.label + ' · ' + tier.max + ' ngang × ' + tier.depth + ' sâu',
                            cells: cells
                        };
                    })
                };
            })
        });
    }

    function schedule3d(node) {
        if (!node || node.dataset.raf) return;
        node.dataset.raf = '1';
        requestAnimationFrame(function () {
            delete node.dataset.raf;
            render3d(node);
        });
    }

    // Rê vào thẻ thông số nào thì chiều đó sáng lên trên mô hình 3D.
    ['mouseover', 'focusin'].forEach(function (type) {
        el.editor.addEventListener(type, function (event) {
            var spec = event.target.closest('.zs-spec');
            if (!spec) return;
            var card = event.target.closest('.zs-spec-card');
            var stage = spec.querySelector('.w3d');
            if (stage) stage.dataset.hl = card ? card.dataset.dim : '';
        });
    });

    el.editor.addEventListener('mouseout', function (event) {
        var spec = event.target.closest('.zs-spec');
        if (!spec || spec.contains(event.relatedTarget)) return;
        var stage = spec.querySelector('.w3d');
        if (stage && !spec.contains(document.activeElement)) stage.dataset.hl = '';
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
    var TYPE_LETTERS = { material: 'V', vpp: 'P', chemical: 'H', standard: 'C' };

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
                var code = cellCode(column, tier, tier.codes[key]) || tierCode(column, tier) + '/' + cellSeg(p, d);
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
        schedule3d(el.spec);
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
        el.editor.classList.toggle('zs-top-down', state.tierOrder === 'top_down');
    }

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
        paintSpec();
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
