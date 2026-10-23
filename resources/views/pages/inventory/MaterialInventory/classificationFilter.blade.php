{{--
| Badge lọc theo Phân loại vật tư (danh mục công ty) - dùng chung cho các bảng tồn có dòng
| mang data-classification: #mdTable (Theo mã xuất nhập), #miSummaryTable (Theo mã vật tư),
| #mfTable (Khả dụng tháng tới). Cùng tiêu chí: HOẶC; khác tiêu chí: VÀ.
--}}
<style>
    .mi-class-bar { display: flex; flex-wrap: wrap; align-items: center; gap: 4px 14px; margin-bottom: 10px; }
    .mi-class-group { display: inline-flex; flex-wrap: wrap; align-items: center; gap: 4px; }
    .mi-class-group-label { font-size: .74rem; font-weight: 700; color: var(--text-muted, #64748B); margin-right: 2px; }
    .mi-class-bar .mi-chip { margin: 0; }
    .mi-class-bar .mi-chip.critical:not(.is-active) { border-color: #FCA5A5; color: #991B1B; }
    .mi-class-bar .mi-chip.banned:not(.is-active) { border-color: #FCD34D; color: #854D0E; }
</style>

<div class="mi-class-bar" id="miClassBar">
    <span class="mi-chip is-active" data-class-key="">Tất cả</span>
    @foreach (\App\Support\MaterialClassification::filterGroups() as $group)
        <span class="mi-class-group" title="{{ $group['label'] }}">
            <i class="{{ $group['icon'] }} mi-class-group-label"></i>
            @foreach ($group['chips'] as $chip)
                <span class="mi-chip {{ $chip['class'] }}" data-class-key="{{ $chip['key'] }}">{{ $chip['short'] }}</span>
            @endforeach
        </span>
    @endforeach
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var miClassTables = ['mdTable', 'miSummaryTable', 'mfTable'];
        var miClassWant = [];

        $.fn.dataTable.ext.search.push(function (settings, data, dataIndex, rowData) {
            if (miClassTables.indexOf(settings.nTable.id) === -1 || !miClassWant.length) return true;
            // Dòng nạp sau (data-deferred) chưa có <tr> nên đọc thuộc tính từ dữ liệu dòng
            var tr = settings.aoData[dataIndex].nTr;
            var keys = ' ' + (mdDeferred.rowAttr(rowData, tr, 'classification') || '') + ' ';

            var groups = {};
            miClassWant.forEach(function (value) {
                var criterion = value.split(':')[0];
                (groups[criterion] = groups[criterion] || []).push(value);
            });

            return Object.keys(groups).every(function (criterion) {
                return groups[criterion].some(function (value) {
                    return keys.indexOf(' ' + value + ' ') !== -1;
                });
            });
        });

        $('#miClassBar').on('click', '.mi-chip', function () {
            var key = $(this).data('class-key') || '';
            if (key === '') {
                $('#miClassBar .mi-chip').removeClass('is-active');
            } else {
                $(this).toggleClass('is-active');
            }
            miClassWant = $('#miClassBar .mi-chip.is-active').map(function () {
                return $(this).data('class-key') || null;
            }).get();
            $('#miClassBar .mi-chip[data-class-key=""]').toggleClass('is-active', miClassWant.length === 0);

            miClassTables.forEach(function (id) {
                if ($.fn.DataTable.isDataTable('#' + id)) $('#' + id).DataTable().draw();
            });
        });
    });
</script>
