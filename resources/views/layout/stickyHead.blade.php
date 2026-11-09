{{--
|--------------------------------------------------------------------------
| Tiêu đề bảng dính khi cuộn - MỌI bảng .table / .dataTable (mdStickyHeads)
|--------------------------------------------------------------------------
| position: sticky bị chặn bởi khung bao có overflow (.table-responsive) nên
| chọn cách dính theo từng bảng, đo lại khi bảng đổi kích thước / vẽ lại /
| mở tab / mở modal:
| - Gặp vùng cuộn dọc riêng (modal, khung cao cố định) trước -> sticky, top 0.
| - Khung bao không tràn ngang -> mở overflow (.md-sticky-open / .md-sticky-clip),
|   sticky ngay dưới topNAV cố định.
| - Khung bao đang cuộn ngang (bảng rộng) -> .md-sticky-float: dịch <thead> theo
|   vị trí cuộn bằng transform.
| Bỏ qua: bảng con nằm trong ô bảng khác, bảng .md-no-sticky / .perm-table, bảng
| màn hình đã tự làm tiêu đề dính (<th> sẵn position: sticky).
| Kiểu dáng ở layout/css.blade.php (khối .md-sticky-*). Gọi lại bằng
| window.mdStickyHeads() nếu màn hình tự đổi bố cục mà không phát sự kiện nào.
--}}
<script>
    (function () {
        if (!window.ResizeObserver || !window.WeakSet) return;

        var SELECTOR = 'table.table, table.dataTable';
        var seen = new WeakSet();
        var floats = new Set();
        var pending = new Set();
        var frame = 0;

        function navHeight() {
            var nav = document.querySelector('.main-header');
            if (!nav) return 0;
            var pos = getComputedStyle(nav).position;
            return pos === 'fixed' || pos === 'sticky' ? Math.max(0, nav.getBoundingClientRect().bottom) : 0;
        }

        function scrollsY(el, cs) {
            return (cs.overflowY === 'auto' || cs.overflowY === 'scroll') && el.scrollHeight > el.clientHeight + 1;
        }

        function each(list, fn) {
            Array.prototype.forEach.call(list, fn);
        }

        function reset(table) {
            table.classList.remove('md-sticky-head', 'md-sticky-float', 'md-sticky-on', 'md-sticky-collapse');
            if (table.tHead) {
                table.tHead.style.transform = '';
                each(table.tHead.querySelectorAll('th'), function (th) { th.style.top = ''; });
            }
            floats.delete(table);
        }

        function setup(table) {
            var head = table.tHead;
            if (!head || !head.rows.length || !table.getClientRects().length) return;

            var managed = table.classList.contains('md-sticky-head') || table.classList.contains('md-sticky-float');
            if (!managed) {
                var firstTh = head.querySelector('th');
                if (firstTh && getComputedStyle(firstTh).position === 'sticky') return;   // màn hình tự làm
            }

            var mode = 'page';
            var scroller = null;
            var opened = [];

            for (var el = table.parentElement; el && el !== document.body; el = el.parentElement) {
                var cs = getComputedStyle(el);
                var wasOpened = el.classList.contains('md-sticky-open') || el.classList.contains('md-sticky-clip');

                if (!wasOpened && (el.classList.contains('modal') || scrollsY(el, cs))) {
                    scroller = el;
                    if (mode === 'page') mode = 'inner';
                    break;
                }
                if (!wasOpened && cs.overflowX === 'visible' && cs.overflowY === 'visible') continue;

                if (el.scrollWidth > el.clientWidth + 1) {
                    // Đang tràn ngang thật: trả lại khung cuộn gốc, dịch thead thay vì sticky
                    el.classList.remove('md-sticky-open', 'md-sticky-clip');
                    mode = 'float';
                } else if (!wasOpened) {
                    opened.push(el);
                }
            }

            reset(table);

            if (mode === 'float') {
                table.classList.add('md-sticky-float');
                table._mdStickyScroller = scroller;
                floats.add(table);
                float(table);
                return;
            }

            opened.forEach(function (box) {
                var cs = getComputedStyle(box);
                var scrolls = /auto|scroll/.test(cs.overflowX + ' ' + cs.overflowY);
                box.classList.add(scrolls ? 'md-sticky-open' : 'md-sticky-clip');
            });

            var base = mode === 'inner' ? 0 : navHeight();
            var offset = 0;
            each(head.rows, function (row) {
                each(row.cells, function (cell) {
                    if (cell.tagName === 'TH') cell.style.top = (base + offset) + 'px';
                });
                offset += row.getBoundingClientRect().height;
            });

            table.classList.add('md-sticky-head');
            if (getComputedStyle(table).borderCollapse === 'collapse') table.classList.add('md-sticky-collapse');
        }

        function float(table) {
            var head = table.tHead;
            if (!head || !table.isConnected) { floats.delete(table); return; }

            var limit = table._mdStickyScroller
                ? table._mdStickyScroller.getBoundingClientRect().top
                : navHeight();
            var rect = table.getBoundingClientRect();
            var off = Math.max(0, Math.min(limit - rect.top, rect.height - head.offsetHeight));

            head.style.transform = off > 0 ? 'translateY(' + off + 'px)' : '';
            table.classList.toggle('md-sticky-on', off > 0);
        }

        function flush() {
            frame = 0;
            var list = Array.from(pending);
            pending.clear();
            list.forEach(function (table) {
                if (table.isConnected) setup(table);
            });
        }

        function schedule(table) {
            pending.add(table);
            if (!frame) frame = requestAnimationFrame(flush);
        }

        var ro = new ResizeObserver(function (entries) {
            entries.forEach(function (entry) { schedule(entry.target); });
        });

        function eligible(table) {
            return !table.classList.contains('md-no-sticky')
                && !table.classList.contains('perm-table')
                && !(table.parentElement && table.parentElement.closest('td, th'));
        }

        function scan(root) {
            each((root || document).querySelectorAll(SELECTOR), function (table) {
                if (seen.has(table) || !eligible(table)) return;
                seen.add(table);
                ro.observe(table);
                schedule(table);
            });
        }

        function refreshAll() {
            scan(document);
            each(document.querySelectorAll('table.md-sticky-head, table.md-sticky-float'), schedule);
        }

        var floatFrame = 0;
        document.addEventListener('scroll', function () {
            if (!floats.size || floatFrame) return;
            floatFrame = requestAnimationFrame(function () {
                floatFrame = 0;
                floats.forEach(float);
            });
        }, true);

        window.addEventListener('resize', refreshAll);

        function start() {
            scan(document);

            var scanFrame = 0;
            new MutationObserver(function () {
                if (scanFrame) return;
                scanFrame = requestAnimationFrame(function () { scanFrame = 0; scan(document); });
            }).observe(document.body, { childList: true, subtree: true });

            if (window.jQuery) {
                jQuery(document).on('draw.dt column-visibility.dt responsive-resize.dt shown.bs.tab shown.bs.modal shown.bs.collapse wms:soft-refreshed', refreshAll);
            }
        }

        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
        else start();

        window.mdStickyHeads = refreshAll;
    })();
</script>
