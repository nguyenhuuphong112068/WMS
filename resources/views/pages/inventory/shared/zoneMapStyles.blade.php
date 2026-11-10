{{--
|--------------------------------------------------------------------------
| CSS SƠ ĐỒ THẺ TỒN THEO VỊ TRÍ (card - grid) - dùng chung
|--------------------------------------------------------------------------
| Vật Tư (MaterialInventory/zoneMap, dựng ở server) và Hoá Chất / Chất Chuẩn
| (shared/zoneGrid, dựng ở trình duyệt) cùng một bộ class .mz-*.
--}}
@once
<style>
    /* ---------- Dòng đầu: chip lọc (trái) + công cụ Thẻ | 3D | Mặt bằng, tìm kiếm (phải) ----------
       Bỏ hàng thẻ thống kê và gộp lọc + công cụ vào một dòng để nhường chiều cao cho khung 3D / Mặt bằng. */
    .mz-head { display: flex; flex-wrap: wrap; gap: 8px 12px; align-items: center; justify-content: space-between; margin-bottom: 10px; }

    .mz-tools { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-left: auto; }
    .mz-search { position: relative; display: flex; align-items: center; gap: 4px; }
    .mz-search input { padding-left: 30px; width: 210px; min-width: 160px; border-radius: var(--border-radius-md); }
    .mz-search > i { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: var(--primary-lighter); }
    /* nút quét mã trên nhãn bằng camera (pages.shared.cameraScan) */
    .mz-search .btn-camera-scan { height: 31px; padding: 0 10px; border-radius: var(--border-radius-md); }

    .mz-filters { display: flex; flex-wrap: wrap; align-items: center; gap: 4px; min-width: 0; }
    .mz-filter {
        border: 1px solid #DCE7F3; background: #fff; border-radius: 999px; padding: 4px 9px; line-height: 1.35;
        font-size: .75rem; font-weight: 600; color: var(--text-main); cursor: pointer; transition: all .2s;
    }
    .mz-filter:hover { border-color: var(--primary-light); }
    .mz-filter.is-active { background: var(--primary); border-color: var(--primary); color: #fff; }
    .mz-filter .dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 5px; }
    .mz-filter .dot.ok { background: #16A34A; }
    .mz-filter .dot.warn { background: #F59E0B; }
    .mz-filter .dot.danger { background: #DC2626; }
    .mz-filter .dot.empty { background: #CBD5E1; }
    .mz-filter .dot.zt { background: var(--zt); }
    .mz-filter-label { align-self: center; font-size: .68rem; font-weight: 700; letter-spacing: .4px; text-transform: uppercase; color: #64748B; margin-right: 2px; }
    .mz-filter-sep { width: 1px; height: 20px; align-self: center; background: #DCE7F3; margin: 0 4px; }

    /* ---------- Tương kỵ hoá chất cùng tầng (chỉ Tồn Kho Hoá Chất) ---------- */
    .mz-filter-compat { color: #B91C1C; border-color: #FECACA; }
    .mz-filter-compat i { margin-right: 4px; }
    .mz-filter-compat b { margin-left: 4px; padding: 0 6px; border-radius: 999px; background: #FEE2E2; color: #991B1B; }
    .mz-filter-compat.is-zero { color: #94A3B8; border-color: #DCE7F3; }
    .mz-filter-compat.is-zero b { background: #EEF2F6; color: #94A3B8; }
    .mz-filter-compat.is-active { background: #DC2626; border-color: #DC2626; color: #fff; }
    .mz-filter-compat.is-active b { background: rgba(255, 255, 255, .25); color: #fff; }
    .mz-cell.is-compat { box-shadow: 0 0 0 2px #DC2626; }
    .mz-cell-compat {
        flex: none; width: 18px; height: 18px; border-radius: 50%; background: #DC2626; color: #fff;
        font-size: .62rem; display: inline-flex; align-items: center; justify-content: center;
    }
    .mz-d-compat {
        border: 1px solid #FECACA; background: #FEF2F2; border-radius: var(--border-radius-md);
        padding: 10px 12px; margin-bottom: 12px; font-size: .82rem; color: #7F1D1D;
    }
    .mz-d-compat-head { font-weight: 700; color: #B91C1C; margin-bottom: 4px; }
    .mz-d-compat ol { margin: 0; padding-left: 18px; }
    .mz-d-compat li + li { margin-top: 8px; padding-top: 8px; border-top: 1px dashed #FECACA; }
    .mz-d-compat .concl { line-height: 1.7; }
    .mz-d-compat .attr {
        display: inline-block; padding: 0 7px; border-radius: 999px; background: #fff; border: 1px solid #FCA5A5;
        color: #991B1B; font-weight: 600; font-size: .76rem; line-height: 1.5;
    }
    .mz-d-compat .vs { color: #B91C1C; }
    .mz-d-compat .pairs { color: #B91C1C; font-size: .76rem; margin-top: 2px; }
    .mz-d-compat .pairs::before { content: 'Chi tiết: '; color: #64748B; }
    .mz-d-compat .md-sub { color: #64748B; }

    /* ---------- Kho ---------- */
    .mz-wh {
        background: #fff; border: 1px solid #DCE7F3; border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-sm); overflow: hidden; margin-bottom: 16px;
    }
    .mz-wh-head {
        display: flex; align-items: center; gap: 12px; padding: 13px 18px; cursor: pointer;
        background: linear-gradient(135deg, var(--primary), var(--primary-light)); color: #fff;
    }
    .mz-wh-head .ic {
        width: 34px; height: 34px; flex: none; border-radius: 10px; background: rgba(255, 255, 255, .18);
        display: flex; align-items: center; justify-content: center;
    }
    .mz-wh-title { font-weight: 700; letter-spacing: .4px; }
    .mz-wh-sub { font-size: .72rem; opacity: .88; }
    .mz-wh-meta { margin-left: auto; display: flex; align-items: center; gap: 6px; }
    .mz-pill { background: rgba(255, 255, 255, .2); border-radius: 999px; padding: 3px 11px; font-size: .72rem; font-weight: 700; white-space: nowrap; }
    .mz-pill.is-alert { background: #FEF3C7; color: #92400E; }
    .mz-caret { transition: transform .25s; }
    .mz-wh.is-closed .mz-caret { transform: rotate(-90deg); }
    .mz-wh.is-closed .mz-wh-body { display: none; }
    .mz-wh-body { padding: 16px 18px 4px; }

    /* ---------- Kệ / Tủ ---------- */
    .mz-shelf { border-left: 3px solid var(--primary-lighter); padding-left: 14px; margin-bottom: 16px; }
    .mz-shelf-head { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; color: var(--primary-dark); font-weight: 700; }
    .mz-shelf-head .tag { background: var(--primary-soft); color: var(--primary); border-radius: 999px; padding: 2px 10px; font-size: .7rem; font-weight: 700; }

    /* ---------- Cột ---------- */
    .mz-column { background: var(--bg-neutral); border: 1px solid #E6EEF7; border-radius: var(--border-radius-md); padding: 12px 14px; margin-bottom: 12px; }
    .mz-column-head { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; font-size: .88rem; font-weight: 700; color: var(--text-main); }
    .mz-column-head .sub { margin-left: auto; font-size: .72rem; font-weight: 600; color: #64748B; }

    /* ---------- Tầng ---------- */
    .mz-tier { margin-bottom: 12px; }
    .mz-tier:last-child { margin-bottom: 0; }
    .mz-tier-head {
        display: flex; align-items: center; gap: 8px; margin-bottom: 8px;
        font-size: .76rem; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: .4px;
    }
    .mz-tier-head .tag {
        background: #fff; border: 1px solid #DCE7F3; color: var(--primary); border-radius: 999px;
        padding: 1px 9px; font-size: .68rem; font-weight: 700; text-transform: none; letter-spacing: 0;
    }
    .mz-tier-head .sub { margin-left: auto; font-size: .7rem; font-weight: 600; color: #94A3B8; text-transform: none; letter-spacing: 0; }

    /* ---------- Ô vị trí ---------- */
    .mz-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(196px, 1fr)); gap: 10px; align-items: stretch; }
    /* Thẻ là <button>: ép flex cột + height 100% để các thẻ cùng hàng cao bằng nhau. */
    .mz-cell {
        display: flex; flex-direction: column; justify-content: flex-start; align-items: stretch;
        width: 100%; height: 100%; text-align: left; background: #fff; background-image: none; padding: 10px 12px;
        border: 1px solid #DCE7F3; border-top: 3px solid #CBD5E1; border-radius: 10px; transition: all .2s;
        appearance: none; -webkit-appearance: none;
    }
    .mz-cell:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); border-color: var(--primary-light); }
    .mz-cell.is-ok { border-top-color: #16A34A; }
    .mz-cell.is-warn { border-top-color: #F59E0B; background: #FFFCF5; }
    .mz-cell.is-danger { border-top-color: #DC2626; background: #FFF9F9; }
    .mz-cell.is-empty { border-style: dashed; border-top-style: solid; background: #FBFDFF; cursor: default; }
    .mz-cell.is-empty:hover { transform: none; box-shadow: none; border-color: #DCE7F3; }

    .mz-cell-top { display: flex; align-items: center; gap: 6px; margin-bottom: 4px; }
    .mz-cell-code { font-weight: 700; color: var(--primary-dark); font-size: .84rem; }
    .mz-cell-lots {
        margin-left: auto; min-width: 22px; height: 22px; padding: 0 7px; border-radius: 999px;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: .72rem; font-weight: 700; background: var(--primary-soft); color: var(--primary);
    }
    .mz-cell.is-warn .mz-cell-lots { background: #FEF3C7; color: #92400E; }
    .mz-cell.is-danger .mz-cell-lots { background: #FEE2E2; color: #991B1B; }
    .mz-cell.is-empty .mz-cell-lots { background: #EEF2F6; color: #94A3B8; }
    .mz-cell-name { font-size: .78rem; color: var(--text-main); font-weight: 600; margin-bottom: 6px; }
    .mz-cell-items { font-size: .73rem; color: #64748B; line-height: 1.45; min-height: 32px; margin-bottom: 6px; }
    .mz-cell-items > div { display: flex; align-items: baseline; gap: 8px; }
    .mz-cell-items .nm { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .mz-cell-items .qty { margin-left: auto; flex: none; font-weight: 700; color: var(--primary-dark); }
    .mz-cell-items .qty .u { font-weight: 600; color: #94A3B8; }
    .mz-cell.is-warn .mz-cell-items .qty { color: #B45309; }
    .mz-cell.is-danger .mz-cell-items .qty { color: #B91C1C; }
    .mz-cell-foot { margin-top: auto; padding-top: 6px; border-top: 1px dashed #E2E8F0; font-size: .7rem; color: #94A3B8; font-weight: 600; }
    .mz-cell-foot .alert { color: #C2410C; }
    .mz-cell-empty-note { font-size: .73rem; color: #94A3B8; font-style: italic; }

    /* Màu định khu của vị trí (App\Support\ZoneType::badge()) chỉ tô dải header (mã vị trí),
       thân thẻ giữ nền theo tình trạng hàng (is-ok/is-warn/is-danger/is-empty) ở trên. */
    .mz-cell.is-zoned .mz-cell-top {
        margin: -10px -12px 8px; padding: 7px 12px; background: var(--chip); border-radius: 7px 7px 0 0;
    }
    .mz-cell.is-zoned .mz-cell-code { color: var(--chip-text); }
    .mz-cell.is-zoned .mz-cell-lots { background: rgba(255, 255, 255, .3); color: var(--chip-text); }

    /* ---------- Khối chưa xếp vị trí + trạng thái rỗng ---------- */
    .mz-unzoned { border: 1px dashed #F59E0B; background: #FFFBEB; border-radius: var(--border-radius-lg); padding: 14px 18px; margin-bottom: 16px; }
    .mz-unzoned-head { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; font-weight: 700; color: #B45309; margin-bottom: 8px; }

    /* Khối Chưa xếp vị trí dựng ở trình duyệt (shared/zoneGrid): thu gọn được + Định khu nhanh */
    .mz-unzoned-head.is-toggle { cursor: pointer; user-select: none; }
    .mz-unzoned.is-closed .mz-caret { transform: rotate(-90deg); }
    .mz-unzoned.is-closed .mz-unzoned-head { margin-bottom: 0; }
    .mz-unzoned.is-closed .mz-unzoned-grid, .mz-unzoned.is-closed .mz-unzoned-tools { display: none; }
    .mz-unzoned-tools { margin-left: auto; display: flex; flex-wrap: wrap; gap: 6px; }
    .mz-unzoned-tools .btn { border-radius: var(--border-radius-md); font-weight: 600; transition: all .2s; }
    .mz-unzoned-tools .btn:not(:disabled):hover { transform: translateY(-1px); }

    .mz-ucard { cursor: default; }
    .mz-ucard.is-pickable { cursor: pointer; }
    .mz-ucard .mz-cell-code { overflow-wrap: anywhere; }
    .mz-ucard-pick { width: 15px; height: 15px; flex: none; margin: 0; accent-color: var(--primary); cursor: pointer; }
    .mz-ucard-row { display: flex; align-items: flex-start; gap: 8px; margin-bottom: 6px; }
    .mz-ucard-row .mz-cell-name {
        flex: 1; min-width: 0; margin-bottom: 0;
        display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;
    }
    .mz-ucard-row .mz-cell-lots { flex: none; }
    .mz-ucard-foot { display: flex; align-items: center; gap: 6px; }
    .mz-ucard-assign {
        margin-left: auto; flex: none; border: 1px solid var(--primary-lighter); background: #fff; color: var(--primary);
        border-radius: 999px; padding: 2px 10px; font-size: .7rem; font-weight: 700; white-space: nowrap; cursor: pointer; transition: all .2s;
    }
    .mz-ucard-assign:hover { background: var(--primary); border-color: var(--primary); color: #fff; }
    .mz-ucard.is-picked { border-color: var(--primary); background: var(--primary-soft); box-shadow: 0 0 0 2px rgba(var(--primary-rgb), .25); }
    .mz-as-list { max-height: 260px; overflow-y: auto; border-radius: var(--border-radius-md); }
    .mz-as-list td:nth-child(2), .mz-as-list td:nth-child(4) { white-space: nowrap; }
    .mz-opt-warn small { display: block; color: #B91C1C; }
    .mz-empty { text-align: center; padding: 40px 20px; color: #94A3B8; }
    .mz-empty i { font-size: 2.2rem; color: var(--primary-lighter); margin-bottom: 10px; display: block; }
    .mz-noresult { display: none; }
</style>
@endonce
