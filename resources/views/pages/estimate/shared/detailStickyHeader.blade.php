{{--
| TIÊU ĐỀ BẢNG CỐ ĐỊNH KHI CUỘN của trang chi tiết phiếu dự trù (Hoá Chất / Chất Chuẩn) - giống
| Dự Trù Vật Tư. position: sticky ngay trên <th> gốc nên tiêu đề và thân bảng luôn chung một
| bảng, cột không bao giờ lệch. Bọc bảng bằng .est-detail-table (mở overflow của
| .table-responsive); --est-sticky-top = chiều cao topNAV cố định, JS đo lại cho đúng.
--}}
<style>
    .est-detail-table {
        overflow: visible;
    }

    .est-detail-table #mdTable thead th {
        position: sticky;
        top: var(--est-sticky-top, 57px);
        z-index: 3;
        background: var(--primary-soft);
        box-shadow: inset 0 -2px 0 var(--primary-lighter);
    }

    .est-lead-warn {
        white-space: normal;
        text-align: left;
        line-height: 1.35;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        function estStickyTop() {
            var height = $('.main-header').outerHeight() || 57;

            document.documentElement.style.setProperty('--est-sticky-top', height + 'px');
        }

        estStickyTop();
        $(window).on('resize', estStickyTop);
    });
</script>
