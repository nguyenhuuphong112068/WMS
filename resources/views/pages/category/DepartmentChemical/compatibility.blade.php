{{--
| KHUNG CẢNH BÁO TƯƠNG KỴ Ở Ô "ĐỊNH KHU" - modal Khai / Sửa Hoá Chất Cho Phòng
|
| Đối chiếu theo Sơ đồ lưu trữ hoá chất theo hình đồ cảnh báo (GHS) - App\Support\ChemicalCompatibility.
|   - Danh sách định khu (Select2 AJAX) gửi kèm category_id: chỗ cùng kệ/tủ với hoá chất tương kỵ
|     bị khoá, có dòng lý do màu đỏ.
|   - Chọn xong một định khu: gọi lookup.chemicalCompatibility, đổ kết quả vào .dc-compat.
| Controller vẫn chặn lại lúc lưu (DepartmentChemicalController::checkCompatibility), khung này chỉ
| để người dùng thấy trước.
--}}
@php
    $dcPicto = collect(\App\Support\ChemicalCompatibility::labels())
        ->map(fn ($label, $code) => [
            'label' => \App\Support\ChemicalCompatibility::shortLabel($code),
            'picto' => trim(view('pages.shared.safetyPictogram', ['code' => $code, 'size' => 16])->render()),
        ])
        ->all();
@endphp

<style>
    .dc-compat {
        margin-top: 8px;
        border-radius: var(--border-radius-md);
        border: 1px solid #dbe6f2;
        background: var(--bg-neutral);
        padding: 10px 12px;
        font-size: 0.84rem;
        color: var(--text-main);
        transition: background 0.2s, border-color 0.2s;
    }

    .dc-compat[hidden] {
        display: none !important;
    }

    .dc-compat.is-ok {
        border-color: #86EFAC;
        background: #F0FDF4;
    }

    .dc-compat.is-warn {
        border-color: #FCD34D;
        background: #FFFBEB;
    }

    .dc-compat.is-danger {
        border-color: #FCA5A5;
        background: #FEF2F2;
    }

    .dc-compat-head {
        font-weight: 700;
        display: flex;
        align-items: flex-start;
        gap: 7px;
    }

    .dc-compat.is-ok .dc-compat-head {
        color: #15803D;
    }

    .dc-compat.is-warn .dc-compat-head {
        color: #B45309;
    }

    .dc-compat.is-danger .dc-compat-head {
        color: #B91C1C;
    }

    .dc-compat-head i {
        margin-top: 2px;
    }

    .dc-compat-mine {
        margin-top: 6px;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 4px;
    }

    .dc-compat-list {
        list-style: none;
        margin: 8px 0 0;
        padding: 0;
        max-height: 220px;
        overflow-y: auto;
    }

    .dc-compat-list li {
        background: #fff;
        border: 1px solid #FECACA;
        border-radius: 8px;
        padding: 7px 9px;
    }

    .dc-compat-list li + li {
        margin-top: 6px;
    }

    .dc-compat-pairs {
        color: #B91C1C;
        font-weight: 600;
        margin-top: 3px;
    }

    .dc-compat-places {
        color: #64748b;
        margin-top: 3px;
    }

    .dc-compat .safety-chip {
        font-size: 0.74rem;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var DC_SAFETY = @json($dcPicto);
        var DC_COMPAT_URL = @json(route('pages.category.lookup.chemicalCompatibility'));
        var $forms = $('#dcCreateModal form, #dcUpdateModal form');

        function categoryOf($form) {
            return String($form.find('[name="category_id"]').val() || '');
        }

        function chip(code) {
            var item = DC_SAFETY[code] || {
                label: code,
                picto: ''
            };

            return $('<span class="safety-chip">').attr('title', item.label).append(item.picto, document.createTextNode(item.label));
        }

        function pairsText(pairs) {
            return pairs.map(function(pair) {
                return (DC_SAFETY[pair[0]] || {}).label + ' ✕ ' + (DC_SAFETY[pair[1]] || {}).label;
            }).join(', ');
        }

        function placeText(place) {
            return place.location + ' · ' + (place.source === 'stock' ?
                'lô ' + (place.lot_code || '') + ' đang tồn' :
                'đã khai định khu');
        }

        function render($box, data, isCurrent) {
            $box.removeClass('is-ok is-warn is-danger').empty();

            var area = data.scope_label + (data.area ? ' "' + data.area + '"' : '');

            if (!data.comparable) {
                $box.append($('<div class="dc-compat-head">').append(
                    '<i class="fas fa-info-circle"></i>',
                    $('<span>').text('Hoá chất chưa khai Cảnh Báo An Toàn thuộc Sơ đồ lưu trữ GHS ở Danh Mục Hoá Chất Công Ty nên chưa đối chiếu được tương kỵ.')
                ));
            } else if (!data.conflicts.length) {
                $box.addClass('is-ok').append($('<div class="dc-compat-head">').append(
                    '<i class="fas fa-check-circle"></i>',
                    $('<span>').text('Không có hoá chất tương kỵ trong cùng ' + area +
                        (data.neighbours ? ' (đang có ' + data.neighbours + ' hoá chất khác).' : '.'))
                ));
            } else {
                $box.addClass(isCurrent ? 'is-warn' : 'is-danger').append($('<div class="dc-compat-head">').append(
                    '<i class="fas fa-exclamation-triangle"></i>',
                    $('<span>').text(isCurrent ?
                        'Định khu đang lưu nằm cùng ' + area + ' với ' + data.conflicts.length +
                        ' hoá chất tương kỵ - nên chuyển sang chỗ khác.' :
                        'Không được đặt ở đây: cùng ' + area + ' có ' + data.conflicts.length +
                        ' hoá chất tương kỵ. Hệ thống sẽ không cho lưu, vui lòng chọn định khu khác.')
                ));

                var $list = $('<ul class="dc-compat-list">');

                data.conflicts.forEach(function(conflict) {
                    var $chips = $('<span class="ml-1">');
                    conflict.warnings.forEach(function(code) {
                        $chips.append(chip(code), ' ');
                    });

                    $list.append($('<li>').append(
                        $('<div>').append($('<b>').text(conflict.code), document.createTextNode(' - ' + conflict.chem_name + ' '), $chips),
                        $('<div class="dc-compat-pairs">').text(pairsText(conflict.pairs)),
                        $('<div class="dc-compat-places">').text(conflict.places.map(placeText).join(' | '))
                    ));
                });

                $box.append($list);
            }

            if (data.warnings.length) {
                var $mine = $('<div class="dc-compat-mine">').append($('<span class="md-sub mr-1">').text('Hoá chất đang khai:'));
                data.warnings.forEach(function(code) {
                    $mine.append(chip(code));
                });
                $box.append($mine);
            }

            $box.prop('hidden', false);
        }

        function check($form) {
            var $box = $form.find('.dc-compat');
            var location = String($form.find('[name="default_location_id"]').val() || '');
            var category = categoryOf($form);
            var token = ($box.data('token') || 0) + 1;

            $box.data('token', token);

            if (!location || !category) {
                $box.prop('hidden', true).empty();
                return;
            }

            $box.removeClass('is-ok is-warn is-danger').prop('hidden', false)
                .html('<i class="fas fa-spinner fa-spin mr-1"></i> Đang đối chiếu tương kỵ...');

            $.getJSON(DC_COMPAT_URL, {
                category_id: category,
                location_id: location
            }).done(function(data) {
                if ($box.data('token') !== token) return;

                if (!data.checked) {
                    $box.prop('hidden', true).empty();
                    return;
                }

                var original = String($form.find('[name="original_location_id"]').val() || '');
                render($box, data, original !== '' && original === location);
            }).fail(function() {
                if ($box.data('token') !== token) return;
                $box.removeClass('is-ok is-warn is-danger').text('Không đối chiếu được tương kỵ, vui lòng thử lại.');
            });
        }

        // Đợi các xử lý của nút Sửa / Thêm (đổ hoá chất, định khu cũ) chạy xong rồi mới đối chiếu
        function scheduleCheck($form) {
            clearTimeout($form.data('compatTimer'));
            $form.data('compatTimer', setTimeout(function() {
                check($form);
            }, 30));
        }

        $forms.each(function() {
            var $form = $(this);

            // Danh sách định khu gửi kèm hoá chất đang khai để server khoá chỗ tương kỵ
            $form.find('select[name="default_location_id"]').data('lookupParams', function() {
                var category = categoryOf($form);
                return category ? {
                    category_id: category
                } : {};
            });

            $form.on('change', '[name="default_location_id"], select[name="category_id"]', function() {
                scheduleCheck($form);
            });

            // Modal mở lại sau lỗi validate: dựng lại khung theo giá trị vừa gửi
            scheduleCheck($form);
        });
    });
</script>
