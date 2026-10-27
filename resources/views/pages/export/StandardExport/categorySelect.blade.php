{{--
|--------------------------------------------------------------------------
| Ô CHẤT CHUẨN CỦA ĐỀ NGHỊ CẤP PHÁT (cho Tổ + liên phòng ban)
|--------------------------------------------------------------------------
| Không render sẵn ~3.800 option cho mỗi modal / mỗi dòng nữa: select gắn class
| js-std-category + data-lookup-url (pages.category.lookup.standardCategories), gõ tìm
| qua AJAX - xem App\Support\CategoryLookup::searchStandardCategories. Dòng của phiếu
| nháp chỉ render option đang chọn.
|
| Thông tin của dòng đang chọn đọc bằng wmsSelectedItems($select)[0]: kết quả AJAX mang
| sẵn code / unit (đơn vị phòng mình) / manufacturer, option render sẵn mang data-*.
| Picker điền giá trị bằng JS phải gọi wmsEnsureOption trước .val().
--}}
<script>
    window.stdInitCategorySelect = function ($scope) {
        $scope.find('select.js-std-category').addBack('select.js-std-category').each(function () {
            var $select = $(this);

            if ($select.hasClass('select2-hidden-accessible')) return;

            var $modal = $select.closest('.modal');

            $select.select2({
                theme: 'bootstrap4',
                width: '100%',
                dropdownParent: $modal.length ? $modal : $(document.body),
                placeholder: '-- Chọn chất chuẩn --',
                ajax: wmsLookupAjax($select),
                // Nhiều chất chuẩn trùng tên khác nhà sản xuất: hiện NSX ngay dưới tên
                templateResult: function (item) {
                    if (item.loading || !item.manufacturer) return item.text;

                    return $('<div>').text(item.text)
                        .append($('<small class="d-block text-muted">').text(item.manufacturer));
                },
                language: {
                    inputTooShort: function () { return 'Gõ mã / tên chất chuẩn để tìm'; },
                    noResults: function () { return 'Không tìm thấy chất chuẩn phù hợp'; },
                    searching: function () { return 'Đang tìm...'; },
                    loadingMore: function () { return 'Đang tải thêm...'; },
                    errorLoading: function () { return 'Không tải được dữ liệu, vui lòng thử lại'; }
                }
            });
        });
    };

    /** Dòng vừa nhân bản mang theo khung select2 + option của dòng gốc: gỡ ra để khởi tạo lại. */
    window.stdCleanCategoryClone = function ($row) {
        $row.find('select.js-std-category').each(function () {
            $(this).next('.select2-container').remove();
            $(this).removeClass('select2-hidden-accessible')
                .removeAttr('data-select2-id aria-hidden tabindex')
                .find('option').not('[value=""]').remove();
            $(this).val('');
        });
    };

    /** Picker điền chất chuẩn vào một ô: tạo option nếu chưa có rồi chọn. */
    window.stdSetCategory = function ($select, id, text, attrs) {
        wmsEnsureOption($select, id, text, attrs);
        $select.val(String(id));
    };

    document.addEventListener('DOMContentLoaded', function () {
        stdInitCategorySelect($(document));
    });
</script>
