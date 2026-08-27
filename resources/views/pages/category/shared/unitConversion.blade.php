{{--
| KHỐI "QUY ĐỔI ĐƠN VỊ" - dùng chung cho 4 modal khai báo của phòng
| (Hoá Chất Của Phòng và Chất Chuẩn Của Phòng, cả Thêm mới lẫn Cập nhật).
|
| Đơn vị tính là của riêng từng phòng, nên cùng một mã có thể đang được phòng khác dùng
| bằng đơn vị khác. Khi đó phải khai hệ số quy đổi, nếu không lúc CHUYỂN KHO hệ thống
| không đổi được số lượng từ đơn vị phòng gửi sang đơn vị phòng nhận.
|
| Hệ số đọc theo chiều: 1 <đơn vị phòng đang khai> = <hệ số> <đơn vị phòng kia>.
|
| Biến vào:
| - $prefix     : 'ds' (chất chuẩn) hoặc 'dc' (hoá chất), để không đụng modal của tab kia
| - $bag        : error bag của chính form đang dựng
| - $unitsInUse : [category_id => [đơn vị phòng khác đang dùng]]
| - $conversions: [category_id => ['<from>-<to>' => hệ số đã khai]]
| - $label      : 'chất chuẩn' hoặc 'hoá chất', chỉ dùng trong câu chữ
--}}

<div class="cuc-box" data-prefix="{{ $prefix }}" data-units-in-use="{{ json_encode($unitsInUse) }}"
    data-conversions="{{ json_encode($conversions) }}"
    data-old="{{ json_encode($bag->any() ? (array) old('conversions', []) : []) }}" style="display: none">

    <input type="hidden" class="cuc-category">

    <div class="cuc-head">
        <i class="fas fa-right-left mr-1"></i>
        Quy Đổi Đơn Vị <span class="text-danger">*</span>
    </div>

    <p class="cuc-note">
        Phòng khác đang dùng đơn vị khác cho {{ $label }} này. Khai hệ số để lúc
        <b>chuyển kho</b> hệ thống đổi đúng số lượng giữa hai phòng.
    </p>

    <div class="cuc-list"></div>

    @foreach ($bag->keys() as $key)
        @if (str_starts_with($key, 'conversions'))
            <span class="md-error d-block">{{ $bag->first($key) }}</span>
        @endif
    @endforeach
</div>

@once
    <style>
    .cuc-box {
        margin-bottom: 1rem;
        padding: 14px 16px;
        border: 1px solid var(--primary-lighter);
        border-radius: var(--border-radius-md);
        background: var(--primary-soft);
    }

    .cuc-head {
        font-weight: 700;
        color: var(--primary-dark);
        margin-bottom: 4px;
    }

    .cuc-note {
        font-size: 0.8rem;
        color: #64748b;
        margin: 0 0 10px;
    }

    .cuc-row {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        padding: 7px 0;
        border-top: 1px dashed var(--primary-lighter);
    }

    .cuc-row:first-child {
        border-top: none;
    }

    .cuc-eq {
        font-weight: 700;
        color: var(--text-main);
        white-space: nowrap;
    }

    .cuc-row input {
        width: 130px;
    }

    .cuc-dept {
        font-size: 0.78rem;
        color: #64748b;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        /* Dựng lại danh sách ô nhập hệ số theo mã + đơn vị đang chọn của chính form đó */
        function cucRefresh($box) {
            var prefix = $box.data('prefix');
            var $form = $box.closest('form');
            var categoryId = String($box.find('.cuc-category').val() || '');
            var unitId = String($form.find('.' + prefix + '-unit').val() || '');

            var unitsInUse = ($box.data('units-in-use') || {})[categoryId] || [];
            var declared = ($box.data('conversions') || {})[categoryId] || {};
            var olds = $box.data('old') || {};

            var $list = $box.find('.cuc-list').empty();
            var $unitOption = $form.find('.' + prefix + '-unit option:selected');
            var ownShort = $unitOption.data('short') || 'đơn vị';
            var shown = 0;

            unitsInUse.forEach(function(row) {
                // Trùng đơn vị thì không có gì để quy đổi
                if (!unitId || String(row.unit_id) === unitId) {
                    return;
                }

                var otherShort = row.unit_short_name || row.unit_name || '';
                var value = olds[row.unit_id];

                if (value === undefined || value === null || value === '') {
                    value = declared[unitId + '-' + row.unit_id];
                }

                if (value === undefined || value === null) {
                    value = '';
                }

                var $row = $('<div>').addClass('cuc-row');

                $('<span>').addClass('cuc-eq').text('1 ' + ownShort + ' =').appendTo($row);

                $('<input>').attr({
                    type: 'number',
                    step: '0.00000001',
                    min: '0.00000001',
                    name: 'conversions[' + row.unit_id + ']',
                    placeholder: 'Nhập hệ số'
                }).addClass('form-control').val(value).appendTo($row);

                $('<span>').addClass('cuc-eq').text(otherShort).appendTo($row);

                $('<span>').addClass('cuc-dept')
                    .text('(phòng ' + (row.department_short || row.department_name || '') + ' đang dùng)')
                    .appendTo($row);

                $list.append($row);
                shown++;
            });

            $box.toggle(shown > 0);
        }

        /* Đổi mã (modal Thêm mới) hoặc đổi đơn vị -> tính lại các ô cần khai */
        $(document).on('change', '.ds-category, .dc-category', function() {
            var $form = $(this).closest('form');
            var $box = $form.find('.cuc-box');

            $box.find('.cuc-category').val($(this).val());
            cucRefresh($box);
        });

        $(document).on('change', '.ds-unit, .dc-unit', function() {
            cucRefresh($(this).closest('form').find('.cuc-box'));
        });

        /* Modal Cập nhật: mã nằm ở dòng đang sửa chứ không có ô chọn */
        $(document).on('click', '.btn-md-edit[data-modal="#dsUpdateModal"], .btn-md-edit[data-modal="#dcUpdateModal"]',
            function() {
                var row = $(this).data('row') || {};
                var $box = $($(this).data('modal')).find('.cuc-box');

                $box.find('.cuc-category').val(row.category_id || '');

                // Ô đơn vị được đổ giá trị ở handler khác, đợi nó chạy xong rồi mới dựng
                setTimeout(function() {
                    cucRefresh($box);
                }, 0);
            });

        /* Form vừa báo lỗi thì modal mở lại sẵn, phải dựng lại ngay để giữ số đã nhập */
        $('.cuc-box').each(function() {
            var $box = $(this);
            var $form = $box.closest('form');
            var $category = $form.find('.ds-category, .dc-category');

            if ($category.length) {
                $box.find('.cuc-category').val($category.val());
            }

            cucRefresh($box);
        });
    });
    </script>
@endonce
