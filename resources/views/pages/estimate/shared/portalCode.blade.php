{{--
| Ô "Code Nhà Sản Xuất" của mặt hàng Dự Trù Hoá Chất (modal Thêm dạng bảng + modal Sửa mặt hàng).
|
| <select class="wms-portal-code"> nằm trong khung .wms-portal-box (kèm <div class="wms-portal-warn">):
| select2 tags - chọn một Code NSX đã khai báo Cổng thông tin quốc gia của mã danh mục, hoặc gõ
| code mới. Cảnh báo (không chặn lưu), cùng quy tắc App\Support\ChemicalPortalDeclaration::warning():
|   - code không nằm trong các Code đã khai báo        -> "Code ... chưa khai báo Cổng TTQG"
|   - hoá chất danh mục chưa khai báo lần nào, chưa có code -> "Hoá chất chưa khai báo Cổng TTQG"
|
| Biến vào: $portalSummary [category_id => ['declared' => bool, 'codes' => [...]]].
| JS: window.wmsPortalCode.setup($select, categoryId, value?) dựng lại danh sách code theo mã danh
| mục (value bỏ trống = giữ giá trị đang chọn).
--}}
<style>
    .wms-portal-box .select2-container .select2-selection--single {
        min-height: 31px;
        font-size: 13px;
    }

    .wms-portal-warn:empty {
        display: none;
    }

    .wms-portal-warn {
        margin-top: 4px;
        padding: 3px 7px;
        border-radius: 6px;
        background: rgba(245, 158, 11, 0.12);
        color: #B45309;
        font-size: 11.5px;
        font-weight: 600;
        line-height: 1.35;
    }

    .wms-portal-opt-new {
        color: #B45309;
        font-size: 11px;
        margin-left: 6px;
    }

    .wms-portal-opt-ok {
        color: var(--success, #16A34A);
        font-size: 11px;
        margin-left: 6px;
    }
</style>

<script>
    window.wmsPortalCode = (function() {
        var map = {!! json_encode((object) ($portalSummary ?? []), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!};

        function lower(value) {
            return String(value == null ? '' : value).trim().toLowerCase();
        }

        function infoOf(categoryId) {
            return map[String(categoryId || '')] || { declared: false, codes: [] };
        }

        function declaredMatch(info, code) {
            var key = lower(code);
            var found = null;
            (info.codes || []).forEach(function(declared) {
                if (found === null && lower(declared) === key) found = declared;
            });
            return found;
        }

        function warning(info, code, fromCategory) {
            code = String(code || '').trim();
            if (code) return declaredMatch(info, code) === null ? 'Code ' + code + ' chưa khai báo Cổng TTQG' : null;
            if (fromCategory && !info.declared) return 'Hoá chất chưa khai báo Cổng TTQG';
            return null;
        }

        function render($select) {
            var categoryId = $select.data('category') || '';
            var message = warning(infoOf(categoryId), $select.val(), !!categoryId);
            var $warn = $select.closest('.wms-portal-box').find('.wms-portal-warn');

            $warn.empty();
            if (message) {
                $('<i class="fas fa-exclamation-triangle mr-1"></i>').appendTo($warn);
                $warn.append(document.createTextNode(message));
                $warn.attr('title', 'Đề nghị khai báo ở Danh Mục Hoá Chất Công Ty (cột Khai Báo Cổng TT Quốc Gia)');
            }
        }

        function setup($select, categoryId, value) {
            if (!$select || !$select.length) return;

            categoryId = categoryId ? String(categoryId) : '';
            if (value === undefined || value === null) value = $select.val() || '';
            value = String(value).trim();

            var info = infoOf(categoryId);
            var codes = info.codes || [];
            var matched = value ? declaredMatch(info, value) : null;

            if ($select.hasClass('select2-hidden-accessible')) $select.select2('destroy');

            $select.data('category', categoryId).empty().append(new Option('', '', false, false));
            codes.forEach(function(code) {
                $select.append(new Option(code, code, false, false));
            });
            if (value && matched === null) $select.append(new Option(value, value, false, false));
            $select.val(matched !== null ? matched : value);

            var $modal = $select.closest('.modal');

            $select.select2({
                theme: 'bootstrap4',
                width: '100%',
                tags: true,
                allowClear: true,
                maximumInputLength: 100,
                placeholder: codes.length ? 'Chọn / nhập code' : 'Nhập code NSX',
                dropdownParent: $modal.length ? $modal : $(document.body),
                language: {
                    noResults: function() {
                        return 'Gõ code nhà sản xuất rồi Enter';
                    }
                },
                createTag: function(params) {
                    var term = $.trim(params.term);
                    if (!term || declaredMatch(infoOf($select.data('category')), term) !== null) return null;
                    return { id: term, text: term, newTag: true };
                },
                templateResult: function(item) {
                    if (!item.id) return item.text;

                    var isDeclared = declaredMatch(infoOf($select.data('category')), item.id) !== null;
                    var $line = $('<span>').text(item.text);

                    $('<span>')
                        .addClass(isDeclared ? 'wms-portal-opt-ok' : 'wms-portal-opt-new')
                        .text(isDeclared ? '✓ đã khai báo' : (item.newTag ? 'code mới - chưa khai báo' : 'chưa khai báo'))
                        .appendTo($line);

                    return $line;
                }
            });

            render($select);
        }

        document.addEventListener('DOMContentLoaded', function() {
            $(document).on('change', 'select.wms-portal-code', function() {
                render($(this));
            });

            // Modal Sửa mặt hàng: đổi nguồn / mã danh mục thì dựng lại danh sách Code NSX
            $(document).on('change', '#itemUpdateModal [name="category_id"], #itemUpdateModal [name="source"]', function() {
                var $form = $(this).closest('form');
                var fromCategory = $form.find('[name="source"]:checked').val() === 'category';

                setup($form.find('select.wms-portal-code'), fromCategory ? $form.find('[name="category_id"]').val() : '');
            });

            // Modal Sửa mở lại sau lỗi validate: ô đã in sẵn giá trị old()
            $('#itemUpdateModal select.wms-portal-code').each(function() {
                setup($(this), $(this).attr('data-category') || '');
            });
        });

        return {
            map: map,
            setup: setup,
            render: render,
            warning: warning
        };
    })();
</script>
