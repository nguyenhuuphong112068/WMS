{{--
|--------------------------------------------------------------------------
| TỒN - CSS + JS dùng chung
|--------------------------------------------------------------------------
| Dùng lại toàn bộ giao diện md-* của nhóm Dữ Liệu Gốc (bảng, card, modal,
| DataTables, SweetAlert) rồi bổ sung phần riêng của nhóm Tồn:
| - Thẻ trạng thái tồn (Còn hàng / Sắp hết / Sắp hết hạn / Hết hạn / Hết hàng / Âm kho)
| - Thanh tiến độ đã dùng của từng mã xuất nhập
| - Bộ lọc nhanh theo trạng thái tồn
| - Hai tab: tồn theo mã xuất nhập và tồn cộng dồn theo hoá chất
| - Modal Cân Đối số lượng nhập của một mã xuất nhập
|
| Quy ước để phần JS bên dưới hoạt động:
| - Bảng tồn theo mã xuất nhập : id="mdTable" (do assets của Dữ Liệu Gốc khởi tạo)
| - Bảng tồn theo hoá chất : id="invSummaryTable"
| - Mỗi dòng của bảng mã xuất nhập có data-state="in|low|near|expired|out|over"
| - Nút lọc                : class="inv-chip" kèm data-state="all" hoặc mã trạng thái
| - Nút cân đối            : class="btn-inv-balancing" kèm data-row='{...}'
| - Modal cân đối          : id="balancingModal"
| - Badge số lần cân đối   : class="btn-inv-history" kèm data-row='{...}', nằm ở góc
|                            trên bên phải nút cân đối, bấm vào mở modal lịch sử
| - Modal lịch sử cân đối  : id="balancingHistoryModal", dữ liệu lấy từ
|                            [data-balancings] '{"<import_id>":[{...}]}'
| - Nút hạn dùng nội bộ    : class="btn-inv-internal" kèm data-row='{...}'
| - Modal hạn dùng nội bộ  : id="internalExpiryModal"
--}}

@include('pages.materData.shared.assets')

<style>
    /* ---------- Thẻ trạng thái tồn ---------- */
    .inv-badge {
        display: inline-block;
        border-radius: 999px;
        padding: 1px 10px;
        font-size: 0.72rem;
        font-weight: 700;
        white-space: nowrap;
        border: 1px solid transparent;
    }

    .inv-badge-in {
        background: #DCFCE7;
        color: #15803D;
        border-color: #86EFAC;
    }

    .inv-badge-low {
        background: #FEF3C7;
        color: #B45309;
        border-color: #FCD34D;
    }

    .inv-badge-near {
        background: #FFEDD5;
        color: #C2410C;
        border-color: #FDBA74;
    }

    .inv-badge-expired {
        background: #FEE2E2;
        color: #B91C1C;
        border-color: #FCA5A5;
    }

    .inv-badge-out {
        background: #E2E8F0;
        color: #475569;
        border-color: #CBD5E1;
    }

    /* Đã xuất vượt lượng nhập - phải cân đối lại */
    .inv-badge-over {
        background: #FCE7F3;
        color: #9D174D;
        border-color: #F9A8D4;
    }

    /* ---------- Mã xuất nhập / số lượng ---------- */
    .inv-code {
        font-weight: 700;
        color: var(--primary-dark);
        letter-spacing: 0.6px;
        white-space: nowrap;
    }

    .inv-amount {
        font-weight: 700;
        color: var(--text-main);
        white-space: nowrap;
    }

    .inv-remaining {
        font-size: 1rem;
        font-weight: 700;
        color: var(--primary-dark);
        white-space: nowrap;
    }

    .inv-remaining.is-zero {
        color: #94A3B8;
    }

    /* Tồn âm do xuất vượt */
    .inv-remaining.is-over {
        color: #9D174D;
    }

    .inv-muted {
        color: #94A3B8;
    }

    /* Tồn cộng dồn theo lô / theo hoá chất - nhạt hơn cột Tồn Còn Lại của chính dòng đó */
    .inv-group-total {
        font-weight: 700;
        white-space: nowrap;
        color: var(--primary);
    }

    .inv-group-total.is-zero {
        color: #94A3B8;
    }

    /* Số ngày còn lại tới hạn áp dụng, ở tab Hạn dùng dưới 6 tháng */
    .inv-countdown {
        display: inline-block;
        margin-top: 2px;
        font-size: 0.76rem;
        font-weight: 700;
        color: #64748b;
        white-space: nowrap;
    }

    .inv-countdown.is-near {
        color: #C2410C;
    }

    .inv-countdown.is-over {
        color: #B91C1C;
    }

    /* Số đã cân đối: dương cộng thêm, âm trừ bớt */
    .inv-balanced {
        font-weight: 700;
        white-space: nowrap;
    }

    .inv-balanced.is-plus {
        color: #15803D;
    }

    .inv-balanced.is-minus {
        color: #B91C1C;
    }

    /* ---------- Modal cân đối ---------- */
    .md-modal .inv-readonly {
        background: var(--primary-soft);
        border-color: var(--primary-lighter);
        color: var(--primary-dark);
        font-weight: 700;
        cursor: default;
    }

    .md-modal .inv-readonly:focus {
        box-shadow: none;
        border-color: var(--primary-lighter);
    }

    .inv-preview {
        display: block;
        margin-top: 4px;
        font-size: 0.78rem;
        font-weight: 700;
        color: var(--primary-dark);
    }

    .inv-preview.is-bad {
        color: #B91C1C;
    }

    /* ---------- Badge số lần cân đối, gắn ở góc trên bên phải nút Cân Đối ---------- */
    .inv-btn-wrap {
        position: relative;
        display: inline-block;
    }

    .inv-count-badge {
        position: absolute;
        top: -8px;
        right: -8px;
        min-width: 20px;
        height: 20px;
        padding: 0 5px;
        border: 2px solid #fff;
        border-radius: 999px;
        background: var(--accent);
        color: #fff;
        font-size: 0.7rem;
        font-weight: 700;
        line-height: 16px;
        text-align: center;
        cursor: pointer;
        box-shadow: var(--shadow-sm);
        transition: transform var(--transition-fast), background-color var(--transition-fast);
    }

    .inv-count-badge:hover {
        background: var(--primary-dark);
        transform: scale(1.12);
    }

    .inv-count-badge:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.25);
    }

    /* ---------- Modal Lịch Sử Cân Đối ---------- */
    .inv-hist-head {
        display: flex;
        flex-wrap: wrap;
        gap: 10px 26px;
        margin-bottom: 18px;
        padding: 14px 18px;
        border: 1px dashed var(--primary-lighter);
        border-radius: var(--border-radius-md);
        background: var(--primary-soft);
    }

    .inv-hist-head label {
        display: block;
        margin: 0 0 2px;
        color: #64748b;
        font-size: 0.74rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .inv-hist-head > div > div {
        font-size: 0.92rem;
        font-weight: 700;
        color: var(--text-main);
    }

    .inv-hist-table {
        margin-bottom: 18px;
    }

    .inv-hist-table thead th {
        background: var(--primary-soft);
        color: var(--primary);
        font-weight: 700;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        border-bottom: none;
        vertical-align: middle;
    }

    .inv-hist-table tbody tr:hover {
        background: rgba(var(--primary-rgb), 0.04);
    }

    .inv-hist-table .is-empty {
        padding: 18px;
        text-align: center;
        color: #94A3B8;
        font-size: 0.85rem;
    }

    /* ---------- Ô "Hiển thị" và ô "Tìm kiếm" gom về cùng một hàng ---------- */
    .inv-dt-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 8px 18px;
        margin-bottom: 14px;
    }

    .inv-dt-bar .dataTables_length,
    .inv-dt-bar .dataTables_filter {
        margin: 0;
        padding: 0;
        float: none;
        white-space: nowrap;
    }

    .inv-dt-bar .dataTables_filter {
        margin-left: auto;
        text-align: right;
    }

    .inv-dt-bar label {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0;
        color: #64748b;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .inv-dt-bar select,
    .inv-dt-bar input {
        border: 1px solid #dbe6f2;
        border-radius: var(--border-radius-md);
        padding: 6px 10px;
        color: var(--text-main);
        transition: border-color var(--transition-fast), box-shadow var(--transition-fast);
    }

    .inv-dt-bar input {
        min-width: 230px;
    }

    .inv-dt-bar select:focus,
    .inv-dt-bar input:focus {
        outline: none;
        border-color: var(--primary-light);
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.12);
    }

    /* ---------- Bộ chọn định khu Kho / Phòng / Kệ / Vị trí ---------- */
    .inv-zone-picker {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        gap: 12px;
        margin-bottom: 14px;
        padding: 16px 18px;
        border: 1px solid var(--primary-lighter);
        border-radius: var(--border-radius-lg);
        background: var(--primary-soft);
    }

    .inv-zone-field {
        flex: 1 1 180px;
        min-width: 150px;
    }

    .inv-zone-field label {
        display: block;
        margin-bottom: 5px;
        color: var(--primary-dark);
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .inv-zone-field label i {
        margin-right: 5px;
        opacity: 0.75;
    }

    .inv-zone-picker select {
        border-radius: var(--border-radius-md);
        border: 1px solid var(--primary-lighter);
        background: #fff;
    }

    .inv-zone-picker select:focus {
        border-color: var(--primary-light);
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.12);
    }

    /* Cấp không còn lựa chọn nào (ví dụ kho chưa chia phòng) thì làm mờ cho biết */
    .inv-zone-picker select:disabled {
        background: #F1F5F9;
        color: #94A3B8;
        cursor: not-allowed;
    }

    .btn-inv-zone-reset {
        border-radius: var(--border-radius-md);
        white-space: nowrap;
    }

    /* ---------- Dòng tóm tắt vị trí đang xem ---------- */
    .inv-zone-summary {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px 20px;
        margin-bottom: 14px;
        font-size: 0.86rem;
    }

    .inv-zone-summary .path {
        font-weight: 700;
        color: var(--primary-dark);
    }

    .inv-zone-summary .stat {
        color: #64748b;
    }

    .inv-zone-summary .stat b {
        color: var(--text-main);
    }

    .inv-zone-none {
        color: #B45309;
        font-weight: 600;
        font-style: italic;
    }

    /* ---------- Thanh tiến độ đã dùng ---------- */
    .inv-bar {
        height: 6px;
        margin-top: 5px;
        border-radius: 999px;
        background: var(--primary-soft);
        overflow: hidden;
    }

    .inv-bar > span {
        display: block;
        height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, var(--primary-light), var(--primary));
        transition: width var(--transition-normal);
    }

    .inv-bar.is-low > span {
        background: linear-gradient(90deg, #FCD34D, #F59E0B);
    }

    .inv-bar.is-out > span {
        background: #CBD5E1;
    }

    /* ---------- Bộ lọc nhanh ---------- */
    .inv-filters {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 16px;
    }

    .inv-chip {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 7px 14px;
        border: 1px solid #dbe6f2;
        border-radius: 999px;
        background: #fff;
        color: #475569;
        font-size: 0.83rem;
        font-weight: 600;
        cursor: pointer;
        transition: all var(--transition-fast);
    }

    .inv-chip:hover {
        background: var(--primary-soft);
        border-color: var(--primary-lighter);
        color: var(--primary-dark);
        transform: translateY(-1px);
    }

    .inv-chip.is-active {
        background: linear-gradient(135deg, var(--primary-light), var(--primary));
        border-color: var(--primary);
        color: #fff;
        box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.24);
    }

    .inv-chip .count {
        display: inline-block;
        min-width: 20px;
        padding: 0 6px;
        border-radius: 999px;
        background: var(--primary-soft);
        color: var(--primary-dark);
        font-size: 0.72rem;
        font-weight: 700;
        text-align: center;
    }

    .inv-chip.is-active .count {
        background: rgba(255, 255, 255, 0.28);
        color: #fff;
    }

    /* ---------- Tab chuyển cách xem ---------- */
    .inv-tabs {
        display: flex;
        gap: 6px;
        margin-bottom: 18px;
        border-bottom: 1px solid var(--primary-soft);
    }

    .inv-tab {
        padding: 9px 18px;
        border: none;
        background: transparent;
        color: #64748b;
        font-weight: 600;
        font-size: 0.9rem;
        border-bottom: 2px solid transparent;
        margin-bottom: -1px;
        cursor: pointer;
        transition: all var(--transition-fast);
    }

    .inv-tab:hover {
        color: var(--primary-dark);
    }

    .inv-tab.is-active {
        color: var(--primary);
        border-bottom-color: var(--primary);
    }

    .inv-tab .inv-tab-count {
        display: inline-block;
        min-width: 20px;
        margin-left: 5px;
        padding: 0 6px;
        border-radius: 999px;
        background: var(--primary-soft);
        color: var(--primary-dark);
        font-size: 0.72rem;
        font-weight: 700;
        text-align: center;
    }

    .inv-pane {
        display: none;
    }

    .inv-pane.is-active {
        display: block;
    }

    #invSummaryTable thead th {
        background: var(--primary-soft);
        color: var(--primary);
        font-weight: 700;
        font-size: 0.82rem;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        border-bottom: none;
        vertical-align: middle;
    }

    #invSummaryTable tbody tr:hover {
        background: rgba(var(--primary-rgb), 0.04);
    }

    /* ---------- Hạn dùng nội bộ ---------- */
    .inv-internal {
        font-weight: 700;
        color: var(--primary-dark);
        white-space: nowrap;
    }

    .inv-internal-none {
        display: inline-block;
        background: #FEF3C7;
        color: #B45309;
        border: 1px solid #FCD34D;
        border-radius: 999px;
        padding: 1px 9px;
        font-size: 0.72rem;
        font-weight: 700;
        white-space: nowrap;
    }

    /* Nhắc việc ở tab "Chưa có hạn nội bộ" - những phiếu này đang bị chặn sử dụng */
    .md-toolbar .hint.inv-blocking {
        background: #FEF3C7;
        border: 1px solid #FCD34D;
        border-radius: var(--border-radius-md);
        color: #B45309;
        padding: 9px 13px;
    }

    .md-toolbar .hint.inv-blocking b {
        color: #92400E;
    }

    /* Kết quả xem trước trong modal xác định hạn dùng nội bộ */
    .md-modal .inv-int-result {
        font-weight: 700;
        letter-spacing: 0.6px;
    }

    .md-modal .inv-int-note.is-capped {
        color: #B45309;
        font-weight: 600;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        var table = $('#mdTable').DataTable();

        // Bảng dùng chung nhắc bấm "Thêm mới", màn hình tồn không có nút đó
        table.settings()[0].oLanguage.sEmptyTable =
            'Chưa có phiếu nhập nào để tính tồn kho. Hãy tạo phiếu ở màn hình Nhập Hoá Chất.';

        /* ---------- Lọc nhanh theo trạng thái tồn ---------- */
        var invState = 'all';

        $.fn.dataTable.ext.search.push(function(settings, data, index) {
            if (settings.nTable.id !== 'mdTable' || invState === 'all') return true;

            return $(settings.aoData[index].nTr).data('state') === invState;
        });

        $(document).on('click', '.inv-chip', function() {
            invState = $(this).data('state');

            $('.inv-chip').removeClass('is-active');
            $(this).addClass('is-active');

            table.draw();
        });

        /* ---------- Bảng phụ: tồn theo hoá chất và lịch sử cân đối ---------- */
        // Trả về API của bảng để nơi gọi dùng lại (ví dụ bộ lọc định khu cần draw)
        function invTable(selector, order, emptyText) {
            return $(selector).DataTable({
                autoWidth: false,
                responsive: true,
                pageLength: 25,
                order: [order],
                lengthMenu: [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, 'Tất cả']
                ],
                language: {
                    search: 'Tìm kiếm:',
                    lengthMenu: 'Hiển thị _MENU_ dòng',
                    info: 'Hiển thị _START_ đến _END_ của _TOTAL_ dòng',
                    infoEmpty: 'Không có dữ liệu',
                    zeroRecords: 'Không tìm thấy dòng nào phù hợp',
                    emptyTable: emptyText,
                    paginate: {
                        previous: 'Trước',
                        next: 'Sau'
                    }
                }
            });
        }

        invTable('#invSummaryTable', [1, 'asc'], 'Chưa có hoá chất nào trong kho.');

        // Chưa có hạn nội bộ: phiếu nhập lâu nhất lên trước vì cần xác định gấp
        invTable('#invInternalTable', [4, 'asc'],
            'Tất cả hoá chất có khai báo hạn dùng mặc định đều đã xác định hạn dùng nội bộ.');

        // Sắp hết hạn: cột 7 = Hạn Áp Dụng, gần hết hạn nhất lên trước
        invTable('#invExpiringTable', [7, 'asc'],
            'Không có mã xuất nhập nào còn tồn mà sắp hết hạn.');

        // Theo định khu: cột 1 = Vị Trí, gom các mã cùng chỗ đứng cạnh nhau
        var zoneTable = invTable('#invZoneTable', [1, 'asc'],
            'Chưa có mã xuất nhập nào ở định khu đang chọn.');

        /* ---------- Lọc tồn theo định khu Kho -> Phòng -> Kệ -> Vị trí ----------
        | Bốn ô chọn dây chuyền: chọn cấp trên thì cấp dưới chỉ còn các mục thuộc cấp đó.
        | Chọn tới cấp nào thì lọc tới cấp đó, nên chỉ chọn Kho là thấy cả kho.
        | Toàn bộ dữ liệu 4 cấp đã nằm sẵn trong [data-zones] nên không phải tải lại trang.
        */
        var $picker = $('.inv-zone-picker');

        if ($picker.length) {
            var zones = $picker.data('zones') || {};

            // Cấp dưới lọc theo cột nào của cấp trên
            var levels = [
                { name: 'warehouse', source: 'warehouses', parents: [] },
                { name: 'room', source: 'rooms', parents: ['warehouse'] },
                { name: 'shelf', source: 'shelves', parents: ['warehouse', 'room'] },
                { name: 'location', source: 'locations', parents: ['warehouse', 'room', 'shelf'] },
            ];

            var picked = { warehouse: '', room: '', shelf: '', location: '' };

            function $select(level) {
                return $picker.find('.inv-zone-select[data-level="' + level + '"]');
            }

            /** Đổ lại lựa chọn của một cấp theo các cấp trên đang chọn */
            function fillLevel(level) {
                var items = (zones[level.source] || []).filter(function(item) {
                    return level.parents.every(function(parent) {
                        return !picked[parent] || String(item[parent + '_id']) === String(picked[parent]);
                    });
                });

                var $el = $select(level.name);
                var keep = items.some(function(i) { return String(i.id) === String(picked[level.name]); });

                // Lựa chọn cũ không còn hợp lệ sau khi đổi cấp trên thì bỏ
                if (!keep) picked[level.name] = '';

                $el.find('option:gt(0)').remove();
                items.forEach(function(item) {
                    $el.append($('<option></option>')
                        .attr('value', item.id)
                        .text(item.name + (item.code ? ' (' + item.code + ')' : '')));
                });

                $el.val(picked[level.name] || '').prop('disabled', items.length === 0);
            }

            function fillAll() {
                levels.forEach(fillLevel);
            }

            /** Câu mô tả vị trí đang xem, lấy đúng nhãn của ô chọn sâu nhất đã chọn */
            function pathText() {
                var parts = [];

                levels.forEach(function(level) {
                    if (picked[level.name]) {
                        parts.push($select(level.name).find('option:selected').text());
                    }
                });

                return parts.length ? parts.join(' / ') : 'Toàn bộ định khu của phòng';
            }

            // Lọc dòng: cấp nào đang chọn thì dòng phải khớp đúng cấp đó
            $.fn.dataTable.ext.search.push(function(settings, data, index) {
                if (settings.nTable.id !== 'invZoneTable') return true;

                var $row = $(settings.aoData[index].nTr);

                return levels.every(function(level) {
                    return !picked[level.name] ||
                        String($row.data(level.name) || '') === String(picked[level.name]);
                });
            });

            function applyZone() {
                zoneTable.draw();

                // Đếm trên đúng phần đang hiển thị sau khi lọc
                var rows = zoneTable.rows({ search: 'applied' }).nodes();
                var chems = {};

                $(rows).each(function() { chems[$(this).data('category')] = true; });

                $('.inv-zone-path').text(pathText());
                $('.inv-zone-codes').text(rows.length);
                $('.inv-zone-chems').text(Object.keys(chems).length);
            }

            $(document).on('change', '.inv-zone-select', function() {
                picked[$(this).data('level')] = $(this).val();

                fillAll();
                applyZone();
            });

            $(document).on('click', '.btn-inv-zone-reset', function() {
                picked = { warehouse: '', room: '', shelf: '', location: '' };

                fillAll();
                applyZone();
            });

            fillAll();
            applyZone();
        }

        /* ---------- Ô "Hiển thị" và ô "Tìm kiếm" về cùng một hàng ----------
        | Bản dựng DataTables Bootstrap 4 đặt hai ô này vào hai cột của một hàng
        | riêng nên chúng nằm cách nhau theo chiều dọc. Gom cả hai vào một thanh
        | flex rồi bỏ hàng cũ đi, làm sau khi bảng đã khởi tạo xong.
        */
        $('.inv-pane .dataTables_wrapper').each(function() {
            var $wrapper = $(this);
            var $length = $wrapper.find('.dataTables_length');
            var $filter = $wrapper.find('.dataTables_filter');

            if (!$length.length && !$filter.length) return;

            // Lấy hàng cũ trước khi di chuyển, sau đó mới xoá để không mất tham chiếu
            var $oldRows = $length.closest('.row').add($filter.closest('.row'));

            $wrapper.prepend($('<div class="inv-dt-bar"></div>').append($length).append($filter));
            $oldRows.remove();
        });

        /* ---------- Modal Cân Đối ---------- */
        var $balancing = $('#balancingModal');

        /** Bỏ số 0 thừa ở phần thập phân: 12.5000 -> 12.5 */
        function trimNum(value) {
            return String(Number(value).toFixed(4)).replace(/\.?0+$/, '');
        }

        /** Tồn sau cân đối, hiện ngay dưới ô nhập để thấy trước kết quả */
        function previewBalancing() {
            var $form = $balancing.find('form');
            var gap = Number($form.find('[name="current_gap"]').val() || 0);
            var input = $form.find('[name="balancing_amount"]').val();
            var $preview = $form.find('.inv-preview');

            if (input === '' || isNaN(Number(input))) {
                $preview.text('').removeClass('is-bad');
                return;
            }

            var amount = Number(input);
            var after = gap + amount;
            var unit = $form.data('unit') || '';

            // Vượt hạn mức 5% thì báo ngay, Controller vẫn kiểm tra lại khi lưu
            var min = Number($form.data('min-input'));
            var max = Number($form.data('max-input'));

            if (!isNaN(min) && !isNaN(max) && (amount < min || amount > max)) {
                $preview
                    .text('Vượt hạn mức cân đối - chỉ được nhập từ ' + trimNum(min) + ' đến ' + trimNum(max))
                    .addClass('is-bad');
                return;
            }

            $preview
                .text('Tồn sau cân đối: ' + trimNum(after) + ' ' + unit)
                // Controller không cho cân đối xong tồn vẫn âm
                .toggleClass('is-bad', after < 0);
        }

        $(document).on('input', '#balancingModal [name="balancing_amount"]', previewBalancing);

        $(document).on('click', '.btn-inv-balancing', function() {
            var row = $(this).data('row') || {};
            var $form = $balancing.find('form');

            $form.data('unit', row.unit || '');
            $form.data('min-input', row.min_input);
            $form.data('max-input', row.max_input);
            $form.find('[name="import_id"]').val(row.import_id);
            $form.find('[name="current_gap"]').val(row.gap);
            $form.find('.inv-code-view').val(row.code || '');
            $form.find('.inv-chem-view').val(row.chem_name || '');
            $form.find('.inv-gap-view').val(trimNum(row.gap) + ' ' + (row.unit || ''));
            $form.find('.inv-imported-view').val(
                trimNum(row.imported) + (Number(row.balanced) ? ' (đã cân đối ' +
                    (Number(row.balanced) > 0 ? '+' : '') + trimNum(row.balanced) + ')' : ''));

            // Hạn mức cân đối còn lại của mã xuất nhập
            $form.find('.inv-limit-view').val('±' + trimNum(row.limit) + ' ' + (row.unit || ''));
            $form.find('.inv-limit-hint').text(
                'Lần này được nhập từ ' + trimNum(row.min_input) + ' đến ' + trimNum(row.max_input) + '.');

            $form.find('[name="balancing_amount"]')
                .attr('min', trimNum(row.min_input))
                .attr('max', trimNum(row.max_input))
                // Tồn đang âm thì gợi ý sẵn phần bù vừa đủ về 0
                .val(Number(row.gap) < 0 ? trimNum(-row.gap) : '');

            previewBalancing();
            $balancing.modal('show');
        });

        /* ---------- Modal Lịch Sử Cân Đối (mở từ badge trên nút Cân Đối) ---------- */
        var $history = $('#balancingHistoryModal');

        /** Chặn HTML lọt vào từ tên người cân đối */
        function esc(value) {
            return $('<div>').text(value === null || value === undefined ? '' : value).html();
        }

        $(document).on('click', '.btn-inv-history', function() {
            var row = $(this).data('row') || {};
            var histories = ($history.data('balancings') || {})[row.import_id] || [];
            var imported = Number(row.imported) || 0;
            var balanced = Number(row.balanced) || 0;

            $history.find('.inv-hist-code').text(row.code || '—');
            $history.find('.inv-hist-chem').text(
                (row.chem_name || '—') + (row.category_code ? ' (' + row.category_code + ')' : ''));
            $history.find('.inv-hist-imported').text(trimNum(imported) + ' ' + (row.unit || ''));
            $history.find('.inv-hist-gap').text(trimNum(row.gap) + ' ' + (row.unit || ''));
            $history.find('.inv-hist-balanced')
                .attr('class', 'inv-balanced inv-hist-balanced ' + (balanced >= 0 ? 'is-plus' : 'is-minus'))
                .text((balanced > 0 ? '+' : '') + trimNum(balanced) + ' ' + (row.unit || ''));

            var $body = $history.find('.inv-hist-table tbody');

            if (!histories.length) {
                // Badge chỉ hiện khi đã cân đối nên nhánh này gần như không xảy ra, vẫn giữ để bảng không trống trơn
                $body.html('<tr><td colspan="5" class="is-empty">Mã xuất nhập này chưa được cân đối lần nào.</td></tr>');
            } else {
                $body.html(histories.map(function(item, index) {
                    var amount = Number(item.balancing_amount);
                    var ratio = imported > 0 ? (Math.abs(amount) / imported * 100).toFixed(2) + '%' : '—';

                    return '<tr>' +
                        '<td class="text-center">' + (index + 1) + '</td>' +
                        '<td class="text-right"><span class="inv-balanced ' +
                        (amount > 0 ? 'is-plus' : 'is-minus') + '">' +
                        (amount > 0 ? '+' : '') + trimNum(amount) + '</span> ' +
                        '<span class="md-sub">' + esc(row.unit || '') + '</span></td>' +
                        '<td class="text-center md-sub">' + ratio + '</td>' +
                        '<td class="md-sub">' + (esc(item.balancing_by) || '—') + '</td>' +
                        '<td class="text-center md-sub">' + esc(item.balancing_at) + '</td>' +
                        '</tr>';
                }).join(''));
            }

            $history.modal('show');
        });

        /* ---------- Modal Xác Định Hạn Dùng Nội Bộ ---------- */
        var $internal = $('#internalExpiryModal');

        /** Ngày yyyy-mm-dd -> Date lúc 0h, tránh lệch múi giờ khi parse chuỗi thuần */
        function toDate(value) {
            if (!value) return null;

            var parts = String(value).substr(0, 10).split('-');

            if (parts.length !== 3) return null;

            return new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
        }

        function fmtDate(date) {
            if (!date) return '';

            var d = String(date.getDate()).padStart(2, '0');
            var m = String(date.getMonth() + 1).padStart(2, '0');

            return d + '/' + m + '/' + date.getFullYear();
        }

        /**
         * Cộng thêm số tháng, không tràn sang tháng sau khi ngày gốc không tồn tại
         * ở tháng đích (31/01 + 1 tháng = 28/02), khớp với addMonthsNoOverflow của Carbon.
         */
        function addMonths(date, months) {
            var target = new Date(date.getFullYear(), date.getMonth() + months, 1);
            var lastDay = new Date(target.getFullYear(), target.getMonth() + 1, 0).getDate();

            target.setDate(Math.min(date.getDate(), lastDay));

            return target;
        }

        /** Xem trước hạn nội bộ ngay khi đổi ngày xác định; Controller vẫn tính lại từ DB */
        function previewInternal() {
            var $form = $internal.find('form');
            var months = Number($form.find('[name="shelf_life_months"]').val() || 0);
            var determined = toDate($form.find('[name="determined_date"]').val());
            var manufacturer = toDate($form.find('[name="manufacturer_expiry"]').val());
            var $result = $form.find('.inv-int-result');
            var $note = $form.find('.inv-int-note');

            if (!determined || months <= 0) {
                $result.val('');
                $note.text('').removeClass('is-capped');

                return;
            }

            var internal = addMonths(determined, months);
            var capped = manufacturer && internal > manufacturer;

            if (capped) internal = manufacturer;

            $result.val(fmtDate(internal));
            $note.text(capped ?
                    'Cộng đủ ' + months + ' tháng sẽ vượt hạn nhà sản xuất nên lấy theo hạn nhà sản xuất.' :
                    'Ngày xác định + ' + months + ' tháng.')
                .toggleClass('is-capped', !!capped);
        }

        $(document).on('change input', '#internalExpiryModal [name="determined_date"]', previewInternal);

        $(document).on('click', '.btn-inv-internal', function() {
            var row = $(this).data('row') || {};
            var $form = $internal.find('form');

            $form.find('[name="import_id"]').val(row.import_id);
            $form.find('[name="shelf_life_months"]').val(row.shelf_life_months);
            $form.find('[name="manufacturer_expiry"]').val(row.expired_date || '');
            $form.find('.inv-int-code').val(row.code || '');
            $form.find('.inv-int-chem').val(row.chem_name || '');
            $form.find('.inv-int-months').val((row.shelf_life_months || 0) + ' tháng');
            $form.find('.inv-int-manu').val(fmtDate(toDate(row.expired_date)) || '—');
            $form.find('.inv-int-current').val(fmtDate(toDate(row.internal_expired_date)) || '');

            previewInternal();
            $internal.modal('show');
        });

        /* ---------- Chuyển tab ---------- */
        $(document).on('click', '.inv-tab', function() {
            var target = $(this).data('pane');

            $('.inv-tab').removeClass('is-active');
            $(this).addClass('is-active');

            $('.inv-pane').removeClass('is-active');
            $('#' + target).addClass('is-active');

            // DataTables đo sai bề rộng cột khi bảng bị ẩn lúc khởi tạo
            $.fn.dataTable.tables({
                visible: true,
                api: true
            }).columns.adjust();
        });
    });
</script>
