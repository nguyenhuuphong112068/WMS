{{--
|--------------------------------------------------------------------------
| TỒN - MODAL "CHI TIẾT CON SỐ" (dùng chung 3 màn Tồn: Vật Tư / Hoá Chất / Chất Chuẩn)
|--------------------------------------------------------------------------
| Mỗi con số theo kỳ trên bảng Tồn (Tồn Đầu Kỳ / Nhập / Cân Đối / Sử Dụng /
| Huỷ - Loại Bỏ / Tồn Cuối Kỳ) và cột Tổng Tồn được bọc trong <button class="inv-mv">
| mang data-scope (import|category), data-id, data-metric (và data-batch cho
| "Tổng tồn theo lô"). Bấm vào -> JS ở pages.inventory.shared.assets gọi
| endpoint *InventoryController::movements() theo ĐÚNG kỳ báo cáo đang xem, rồi
| đổ danh sách bản ghi cộng lại thành con số đó.
|
| Nhận diện bằng id #invMovementModal; mỗi list.blade.php truyền route riêng qua
| $movementUrl.
--}}

<div class="modal fade md-modal" id="invMovementModal" tabindex="-1" role="dialog"
    data-url="{{ $movementUrl }}" data-from="{{ $period['from'] }}" data-to="{{ $period['to'] }}">
    <div class="modal-dialog modal-dialog-centered modal-xl" style="max-width: 1120px;" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-list-ul"></i> Chi Tiết Phát Sinh</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <div class="modal-body">
                <div class="inv-hist-head">
                    <div>
                        <label>Đối Tượng</label>
                        <div class="imv-name">—</div>
                    </div>
                    <div>
                        <label>Mã</label>
                        <div class="inv-code imv-code">—</div>
                    </div>
                    <div>
                        <label>Kỳ Báo Cáo</label>
                        <div class="imv-period">—</div>
                    </div>
                    <div>
                        <label>Đơn Vị Tính</label>
                        <div class="imv-unit">—</div>
                    </div>
                </div>

                {{-- Mỗi metric một pill, bấm để đổi bảng ngay (không gọi lại mạng) --}}
                <div class="imv-pills"></div>

                {{-- Tiêu đề / chân bảng do JS dựng động theo cột riêng của từng metric --}}
                <div class="table-responsive">
                    <table class="table table-bordered table-hover w-100 imv-table">
                        <thead></thead>
                        <tbody></tbody>
                        <tfoot></tfoot>
                    </table>
                </div>

                <div class="imv-state" hidden></div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

@once
    <style>
        /* ---------- Badge số bấm được trên bảng Tồn ---------- */
        .inv-mv {
            display: inline-block;
            padding: 1px 9px;
            border: 1px solid var(--primary-lighter);
            border-radius: 999px;
            background: var(--primary-soft);
            color: var(--primary-dark);
            font: inherit;
            font-weight: 700;
            line-height: 1.4;
            white-space: nowrap;
            cursor: pointer;
            transition: transform var(--transition-fast), background-color var(--transition-fast),
                border-color var(--transition-fast), box-shadow var(--transition-fast);
        }

        .inv-mv:hover,
        .inv-mv:focus {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
            transform: translateY(-1px);
            box-shadow: var(--shadow-sm);
            outline: none;
        }

        .inv-mv.is-in {
            color: #15803D;
        }

        .inv-mv.is-out {
            color: #B91C1C;
        }

        .inv-mv.is-muted {
            color: #94A3B8;
            background: #fff;
        }

        .inv-mv.is-in:hover,
        .inv-mv.is-in:focus,
        .inv-mv.is-out:hover,
        .inv-mv.is-out:focus,
        .inv-mv.is-muted:hover,
        .inv-mv.is-muted:focus {
            color: #fff;
        }

        /* ---------- Hàng pill trong modal ---------- */
        .imv-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 4px 0 16px;
        }

        .imv-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border: 1px solid var(--primary-lighter);
            border-radius: 999px;
            background: #fff;
            color: var(--primary-dark);
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-fast);
        }

        .imv-pill:hover {
            border-color: var(--primary);
            transform: translateY(-1px);
        }

        .imv-pill.is-active {
            background: linear-gradient(135deg, var(--primary-light), var(--primary));
            border-color: var(--primary);
            color: #fff;
            box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.24);
        }

        .imv-pill .imv-pill-total {
            display: inline-block;
            min-width: 20px;
            padding: 0 7px;
            border-radius: 999px;
            background: var(--primary-soft);
            color: var(--primary-dark);
            font-size: 0.74rem;
            font-weight: 700;
            text-align: center;
        }

        .imv-pill.is-active .imv-pill-total {
            background: rgba(255, 255, 255, 0.28);
            color: #fff;
        }

        /* ---------- Bảng chi tiết ---------- */
        .imv-table thead th {
            background: var(--primary-soft);
            color: var(--primary);
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border-bottom: none;
            vertical-align: middle;
        }

        .imv-table tbody tr:hover {
            background: rgba(var(--primary-rgb), 0.04);
        }

        .imv-table td {
            vertical-align: middle;
            font-size: 0.86rem;
        }

        .imv-table tfoot th {
            background: var(--primary-soft);
            color: var(--primary-dark);
            font-size: 0.86rem;
        }

        .imv-amount-in {
            color: #15803D;
            font-weight: 700;
        }

        .imv-amount-out {
            color: #B91C1C;
            font-weight: 700;
        }

        .imv-row-opening td {
            background: var(--primary-soft);
            font-weight: 700;
        }

        .imv-state {
            padding: 26px 16px;
            text-align: center;
            color: #64748B;
            font-size: 0.9rem;
        }

        .imv-state.is-bad {
            color: #B91C1C;
        }

        .imv-state i {
            margin-right: 6px;
        }
    </style>
@endonce
