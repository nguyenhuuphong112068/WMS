{{--
|--------------------------------------------------------------------------
| CSS SƠ ĐỒ THẺ TỒN THEO VỊ TRÍ (card - grid) - dùng chung
|--------------------------------------------------------------------------
| Vật Tư (MaterialInventory/zoneMap, dựng ở server) và Hoá Chất / Chất Chuẩn
| (shared/zoneGrid, dựng ở trình duyệt) cùng một bộ class .mz-*.
--}}
@once
<style>
    /* ---------- Thanh tổng quan + bộ lọc ---------- */
    .mz-head { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between; margin-bottom: 14px; }
    .mz-stats { display: flex; flex-wrap: wrap; gap: 10px; }
    .mz-stat {
        background: var(--primary-soft); border: 1px solid #DCE7F3; border-radius: var(--border-radius-md);
        padding: 8px 16px; min-width: 92px;
    }
    .mz-stat b { display: block; font-size: 1.3rem; font-weight: 700; color: var(--primary); line-height: 1.15; }
    .mz-stat span { font-size: .68rem; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; color: #64748B; }
    .mz-stat.is-alert { background: #FFF7ED; border-color: #FED7AA; }
    .mz-stat.is-alert b { color: #C2410C; }

    .mz-tools { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
    .mz-search { position: relative; }
    .mz-search input { padding-left: 32px; min-width: 240px; border-radius: var(--border-radius-md); }
    .mz-search i { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: var(--primary-lighter); }

    .mz-filters { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 12px; }
    .mz-filter {
        border: 1px solid #DCE7F3; background: #fff; border-radius: 999px; padding: 5px 14px;
        font-size: .78rem; font-weight: 600; color: var(--text-main); cursor: pointer; transition: all .2s;
    }
    .mz-filter:hover { border-color: var(--primary-light); }
    .mz-filter.is-active { background: var(--primary); border-color: var(--primary); color: #fff; }
    .mz-filter .dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 6px; }
    .mz-filter .dot.ok { background: #16A34A; }
    .mz-filter .dot.warn { background: #F59E0B; }
    .mz-filter .dot.danger { background: #DC2626; }
    .mz-filter .dot.empty { background: #CBD5E1; }
    .mz-filter .dot.zt { background: var(--zt); }
    .mz-filter-label { align-self: center; font-size: .72rem; font-weight: 700; letter-spacing: .4px; text-transform: uppercase; color: #64748B; margin-right: 4px; }
    .mz-filter-sep { width: 1px; align-self: stretch; background: #DCE7F3; margin: 0 6px; }

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
    .mz-unzoned-head { display: flex; align-items: center; gap: 8px; font-weight: 700; color: #B45309; margin-bottom: 8px; }
    .mz-empty { text-align: center; padding: 40px 20px; color: #94A3B8; }
    .mz-empty i { font-size: 2.2rem; color: var(--primary-lighter); margin-bottom: 10px; display: block; }
    .mz-noresult { display: none; }
</style>
@endonce
