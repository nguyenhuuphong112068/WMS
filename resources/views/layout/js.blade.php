
        <script src="{{asset ('dataTable/plugins/jquery/jquery.min.js')}}"></script>
        <script src="{{asset ('dataTable/plugins/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
        {{--
        | Hộp SweetAlert mở chồng lên modal Bootstrap: modal giữ con trỏ trong chính nó
        | (focusin.bs.modal) nên ô nhập của SweetAlert không gõ được. Chặn sự kiện focusin
        | phát ra từ trong SweetAlert trước khi tới handler của modal. Gắn ngay sau
        | Bootstrap để luôn chạy trước handler modal gắn lúc mở (jQuery chạy theo thứ tự gắn).
        --}}
        <script>
                $(document).on('focusin', function (e) {
                        if ($(e.target).closest('.swal2-container').length) {
                                e.stopImmediatePropagation();
                        }
                });
        </script>
        <script src="{{asset ('dataTable/plugins/datatables/jquery.dataTables.min.js')}}"></script>
        <script src="{{asset ('dataTable/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js')}}"></script>
        <script src="{{asset ('dataTable/plugins/datatables-responsive/js/dataTables.responsive.min.js')}}"></script>
        <script src="{{asset ('dataTable/plugins/datatables-responsive/js/responsive.bootstrap4.min.js')}}"></script>
        <script src="{{ asset('dataTable/dist/js/adminlte.min.js') }}"></script>

        <!-- Select2 -->
        <script src="{{asset ('dataTable/plugins/select2/js/select2.full.min.js')}}"></script>
        {{-- Ô chọn Định Khu toàn hệ thống: chấm màu cố định của phân loại (App\Support\ZoneType) trước nhãn.
             Option render sẵn khai data-zone="<zone_type>" (rỗng = chưa phân loại); kết quả AJAX trả kèm zone_type.
             Ô không có data-zone/zone_type giữ nguyên nhãn chữ. --}}
        <script>
            (function ($) {
                var colors = @json(\App\Support\ZoneType::DEFAULT_COLORS);
                var labels = @json(\App\Support\ZoneType::TYPES);

                window.wmsZoneDot = function (item) {
                    var type;
                    if (item && Object.prototype.hasOwnProperty.call(item, 'zone_type')) {
                        type = item.zone_type || '';
                    } else if (item && item.element && item.element.hasAttribute && item.element.hasAttribute('data-zone')) {
                        type = item.element.getAttribute('data-zone') || '';
                    } else {
                        return null;
                    }

                    var color = colors[type];
                    return $('<i class="zone-dot">')
                        .toggleClass('is-none', !color)
                        .attr('style', color ? '--dot:' + color : null)
                        .attr('title', labels[type] || 'Chưa phân loại');
                };

                // Thẻ mã vị trí dựng bằng JS - cùng giao diện với App\Support\ZoneType::tag() (chỉ mã vị trí, tô màu phân loại)
                window.wmsZoneTag = function (code, type, empty) {
                    if (!code || code === '—') return $('<span>').text(empty === undefined ? '—' : empty).html();

                    var color = colors[type];
                    var $tag = $('<span class="zone-tag">').text(code).attr('title', labels[type] || 'Chưa phân loại');
                    if (color) {
                        var n = parseInt(color.slice(1), 16);
                        var light = (0.299 * ((n >> 16) & 255) + 0.587 * ((n >> 8) & 255) + 0.114 * (n & 255)) > 160;
                        $tag.attr('style', '--zt:' + color + ';--zt-text:' + (light ? '#1F2937' : '#FFFFFF'));
                    } else {
                        $tag.addClass('is-none');
                    }

                    return $tag.prop('outerHTML');
                };

                window.wmsZoneTemplate = function (item) {
                    var dot = window.wmsZoneDot(item);
                    return dot ? $('<span>').append(dot, document.createTextNode(item.text)) : item.text;
                };

                if (!$ || !$.fn.select2) return;

                $.fn.select2.defaults.set('templateResult', window.wmsZoneTemplate);
                $.fn.select2.defaults.set('templateSelection', window.wmsZoneTemplate);

                /*
                | LỌC NHANH THEO PHÂN LOẠI: ô chọn định khu (option có data-zone, hoặc ô AJAX khai
                | data-zone-filter) hiện hàng chip phân loại dưới ô tìm kiếm. Chip đang chọn lưu ở
                | $select.data('zoneFilter') ('' = tất cả, '__none' = chưa phân loại): ô render sẵn lọc
                | qua matcher bên dưới, ô AJAX gửi kèm tham số zone_filter (category/shared/assets).
                */
                var baseMatcher = $.fn.select2.defaults.defaults.matcher;

                $.fn.select2.defaults.set('matcher', function (params, data) {
                    var select = data.element && data.element.closest ? data.element.closest('select') : null;
                    var filter = select ? $(select).data('zoneFilter') : '';

                    if (filter && data.element.tagName === 'OPTION') {
                        if (!data.element.hasAttribute('data-zone') || !data.element.value) return null;
                        if ((data.element.getAttribute('data-zone') || '__none') !== filter) return null;
                    }

                    return baseMatcher(params, data);
                });

                function isZoneSelect($select) {
                    return $select.is('[data-zone-filter]') || $select.find('option[data-zone]').length > 0;
                }

                function renderChips($select, $bar) {
                    var current = $select.data('zoneFilter') || '';
                    var local = !$select.is('[data-zone-filter]');
                    var counts = { '': 0, '__none': 0 };

                    if (local) {
                        $select.find('option[data-zone]').each(function () {
                            if (!this.value) return;
                            var zone = this.getAttribute('data-zone') || '__none';
                            counts[zone] = (counts[zone] || 0) + 1;
                            counts['']++;
                        });
                    }

                    var items = [{ key: '', label: 'Tất cả', color: null }]
                        .concat(Object.keys(labels).map(function (key) {
                            return { key: key, label: labels[key], color: colors[key] };
                        }))
                        .concat([{ key: '__none', label: 'Chưa phân loại', color: null, none: true }]);

                    $bar.empty();
                    items.forEach(function (item) {
                        // Ô render sẵn: ẩn phân loại không có vị trí nào để hàng chip gọn
                        if (local && item.key && !counts[item.key]) return;

                        var $chip = $('<button type="button" class="zone-filter-chip">')
                            .toggleClass('is-active', item.key === current)
                            .attr('data-zone-key', item.key)
                            .attr('style', item.color ? '--dot:' + item.color : null);

                        if (item.key) {
                            $chip.append($('<i class="zone-dot">').toggleClass('is-none', !!item.none)
                                .attr('style', item.color ? '--dot:' + item.color : null));
                        }
                        $chip.append(document.createTextNode(item.label));
                        if (local) $chip.append($('<b>').text(counts[item.key] || 0));
                        $bar.append($chip);
                    });

                    // Chỉ còn "Tất cả" + một phân loại thì lọc không có ý nghĩa
                    $bar.prop('hidden', local && $bar.children().length <= 2);
                }

                $(document).on('select2:open', 'select', function () {
                    var $select = $(this);
                    var instance = $select.data('select2');
                    if (!instance || !isZoneSelect($select)) return;

                    var $dropdown = instance.$dropdown.find('.select2-dropdown');
                    var $bar = $dropdown.children('.zone-filter-bar');
                    if (!$bar.length) {
                        $bar = $('<div class="zone-filter-bar">').data('zoneSelect', $select);
                        var $search = $dropdown.children('.select2-search');
                        $search.length ? $bar.insertAfter($search) : $dropdown.prepend($bar);
                    }
                    renderChips($select, $bar);
                });

                // mousedown: không để ô tìm kiếm mất focus / dropdown đóng lại khi bấm chip
                $(document).on('mousedown', '.zone-filter-chip', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                });

                $(document).on('click', '.zone-filter-chip', function (event) {
                    event.preventDefault();
                    event.stopPropagation();

                    var $bar = $(this).closest('.zone-filter-bar');
                    var $select = $bar.data('zoneSelect');
                    var instance = $select && $select.data('select2');
                    if (!instance) return;

                    $select.data('zoneFilter', String($(this).attr('data-zone-key') || ''));
                    renderChips($select, $bar);

                    var term = instance.$dropdown.find('.select2-search__field').val() || '';
                    instance.trigger('query', { term: term });
                });

                // Mở lại ô chọn thì bắt đầu từ "Tất cả"
                $(document).on('select2:close', 'select', function () {
                    $(this).removeData('zoneFilter');
                });

                /*
                | Ô chọn định khu chưa có select2 riêng của trang: gắn class "wms-zone-select" (option vẫn
                | khai data-zone) là tự thành select2 có chấm màu + chip lọc. Khởi tạo lúc tải trang và mỗi
                | lần mở modal (nội dung modal có thể vừa được dựng lại). Gọi window.wmsInitZoneSelects(root)
                | nếu trang tự chèn ô mới bằng JS.
                */
                window.wmsInitZoneSelects = function (root) {
                    $(root || document).find('select.wms-zone-select').each(function () {
                        var $select = $(this);
                        if ($select.hasClass('select2-hidden-accessible')) return;

                        var $modal = $select.closest('.modal');
                        $select.select2({
                            theme: 'bootstrap4',
                            width: '100%',
                            dropdownParent: $modal.length ? $modal : $(document.body)
                        });
                    });
                };

                $(function () { window.wmsInitZoneSelects(document); });
                $(document).on('shown.bs.modal', '.modal', function () { window.wmsInitZoneSelects(this); });

                /*
                | Ô CHỌN DANH SÁCH LỚN - không render sẵn hàng nghìn <option> trong modal.
                |
                | wmsLookupAjax($select): cấu hình ajax select2 gọi data-lookup-url (App\Support\CategoryLookup),
                |   tham số cố định khai ở data-lookup-query='{"type":"all"}', mỗi lần 30 dòng.
                | wmsLocalLookup(items): như trên nhưng tìm ngay trên mảng [{id, text, search?}] đã có ở trang
                |   (nhãn cần số liệu controller vừa tính) - không gọi server, chỉ dựng 30 option mỗi lần.
                | wmsEnsureOption($select, value, text, attrs): giá trị đặt bằng JS (nút Sửa, picker, điền sẵn)
                |   chưa có option thì tạo, attrs thành data-*.
                | wmsSelectedItems($select): dòng đang chọn kèm trường riêng của kết quả tìm (code, unit, name...)
                |   lẫn data-* của option render sẵn.
                */
                window.wmsLookupAjax = function ($select) {
                    return {
                        url: $select.data('lookup-url'),
                        dataType: 'json',
                        delay: 250,
                        cache: true,
                        data: function (params) {
                            var extra = $select.data('lookupParams');

                            return $.extend({
                                q: params.term || '',
                                page: params.page || 1,
                                zone_filter: $select.data('zoneFilter') || ''
                            }, $select.data('lookup-query') || {}, typeof extra === 'function' ? extra($select) : {});
                        }
                    };
                };

                window.wmsLocalLookup = function (items, pageSize) {
                    pageSize = pageSize || 30;

                    function fold(value) {
                        return String(value || '').toLowerCase().normalize('NFD')
                            .replace(/[̀-ͯ]/g, '').replace(/đ/g, 'd');
                    }

                    var index = (items || []).map(function (item) { return fold(item.search || item.text); });

                    return {
                        delay: 150,
                        data: function (params) {
                            return { q: params.term || '', page: params.page || 1 };
                        },
                        transport: function (params, success) {
                            var terms = fold(params.data.q).split(/\s+/).filter(Boolean);
                            var page = params.data.page || 1;
                            var hits = [];

                            for (var i = 0; i < index.length; i++) {
                                if (terms.every(function (term) { return index[i].indexOf(term) !== -1; })) hits.push(items[i]);
                            }

                            success({
                                results: hits.slice((page - 1) * pageSize, page * pageSize),
                                pagination: { more: hits.length > page * pageSize }
                            });

                            return { abort: function () {} };
                        }
                    };
                };

                window.wmsEnsureOption = function ($select, value, text, attrs) {
                    if (value === null || value === undefined || value === '') return;

                    value = String(value);

                    if ($select.find('option').filter(function () { return this.value === value; }).length) return;

                    var option = new Option(text || value, value, false, false);

                    $.each(attrs || {}, function (name, attr) {
                        if (attr !== null && attr !== undefined) option.setAttribute('data-' + name, attr);
                    });

                    $select.append(option);
                };

                window.wmsSelectedItems = function ($select) {
                    var items = $select.hasClass('select2-hidden-accessible') ? $select.select2('data') :
                        $select.find('option:selected').map(function () {
                            return { id: this.value, text: $(this).text(), element: this };
                        }).get();

                    return items.filter(function (item) { return item.id !== '' && item.id !== undefined; })
                        .map(function (item) {
                            return $.extend({}, item.element ? $(item.element).data() : {}, item);
                        });
                };
            })(window.jQuery);
        </script>
        <!-- Bootstrap4 Duallistbox -->
        <script src="{{asset ('dataTable/plugins/bootstrap4-duallistbox/jquery.bootstrap-duallistbox.min.js')}}"></script>
        <!-- InputMask -->
        <script src="{{asset ('dataTable/plugins/moment/moment.min.js')}}"></script>

        <script src="{{asset ('dataTable/plugins/inputmask/min/jquery.inputmask.bundle.min.js')}}"></script>
        <!-- date-range-picker -->
        <script src="{{asset ('dataTable/plugins/daterangepicker/daterangepicker.js')}}"></script>
        <!-- bootstrap color picker -->
        <script src="{{asset ('dataTable/plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js')}}"></script>
        <!-- Tempusdominus Bootstrap 4 -->
        <script src="{{asset ('dataTable/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js')}}"></script>
        <!-- Bootstrap Switch -->
        <script src="{{asset ('dataTable/plugins/bootstrap-switch/js/bootstrap-switch.min.js')}}"></script>

        
     
        <script src="{{ asset('dataTable/plugins/chart.js/Chart.min.js') }}"></script>
        <script src="{{ asset('dataTable/dist/js/demo.js') }}?v={{ filemtime(public_path('dataTable/dist/js/demo.js')) }}"></script>
        <script src="{{ asset('dataTable/dist/js/pages/dashboard3.js') }}?v={{ filemtime(public_path('dataTable/dist/js/pages/dashboard3.js')) }}"></script>

        <!-- DataTables JS (Local) -->
        <script src="{{ asset('dataTable/plugins/datatables/jquery.dataTables.min.js') }}"></script>  


        {{-- Chống double submit --}}
        <script>
                /**
                * Chặn double submit cho form
                * @param {string} formSelector - Selector của form (vd: "#myForm" hoặc ".ajax-form")
                * @param {string} buttonSelector - Selector của nút submit (vd: "#btnSave")
                */
                function preventDoubleSubmit(formSelector, buttonSelector) {
                       
                        const form = document.querySelector(formSelector);
                        const btn = document.querySelector(buttonSelector);

                        if (!form || !btn) return;

                        form.addEventListener("submit", function () {
                        if (btn.disabled) {
                                // đã disable rồi thì ngăn submit thêm lần nữa
                                event.preventDefault();
                                return;
                        }
                        btn.disabled = true;
                        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang...';
                        });
                }

                // Gọi hàm ở bất cứ form nào bạn muốn
                document.addEventListener("DOMContentLoaded", function () {
                        preventDoubleSubmit("form", "#btnSave");
                        // có thể gọi nhiều lần cho form khác:
                        // preventDoubleSubmit("#form2", "#btnUpdate");
                });
        </script>

        {{--
        | Chuẩn phân cách số toàn hệ thống: phần thập phân LUÔN dùng dấu ".", phần nghìn dùng dấu ",".
        |
        | <input type="number"> của Chrome/Edge hiển thị & nhận phím theo NGÔN NGỮ TRÌNH DUYỆT
        | (Chrome bỏ qua thuộc tính lang), nên máy cài tiếng Việt sẽ hiện "1,002" thay vì "1.002"
        | và không gõ được dấu ".". Vì vậy mọi ô số THẬP PHÂN được để type="text" inputmode="decimal"
        | kèm class "js-decimal" — ô text hiện đúng y chuỗi giá trị, không phụ thuộc locale.
        |
        | Khối JS dưới đây:
        |  - Lọc phím: chỉ cho nhập [0-9], 1 dấu "." và dấu "-" ở đầu; tự đổi "," thành "." khi gõ / dán.
        |  - Khi rời ô / trước khi gửi: bỏ dấu "." thừa ở đầu-cuối (".5" -> "0.5", "5." -> "5").
        |    KHÔNG tự kẹp min/max — để validate phía server báo lỗi đúng thông điệp như cũ.
        |  - Tự nhận cả ô <input type="number"> có step thập phân (kể cả dòng thêm bằng JS) và
        |    chuyển sang cơ chế trên, phòng khi còn sót ô chưa sửa ở blade.
        --}}
        <script>
                (function () {
                        var SEL = 'input.js-decimal, input[inputmode="decimal"]';

                        function cleanDecimal(raw) {
                                var s = String(raw == null ? '' : raw).replace(/,/g, '.');
                                var neg = s.charAt(0) === '-';           // cho phép số âm (ô cân đối kho)
                                s = s.replace(/[^0-9.]/g, '');
                                var i = s.indexOf('.');
                                if (i !== -1) {
                                        s = s.slice(0, i + 1) + s.slice(i + 1).replace(/\./g, '');
                                }
                                return (neg ? '-' : '') + s;
                        }

                        function sanitize(el) {
                                var before = el.value;
                                var after = cleanDecimal(before);
                                if (after === before) return;
                                var drop = before.length - after.length;
                                var pos = (el.selectionStart || 0) - drop;
                                el.value = after;
                                try { el.setSelectionRange(pos, pos); } catch (e) {}
                        }

                        function normalize(el) {
                                var v = cleanDecimal(el.value);
                                if (v === '' || v === '-') { el.value = ''; return; }
                                if (v === '.' || v === '-.') { el.value = ''; return; }
                                if (v.charAt(0) === '.') v = '0' + v;
                                else if (v.slice(0, 2) === '-.') v = '-0' + v.slice(1);
                                if (v.charAt(v.length - 1) === '.') v = v.slice(0, -1);
                                el.value = v;
                        }

                        // Ô type="number" có step thập phân mà chưa được chuyển ở blade -> chuyển tại đây.
                        function adopt(el) {
                                if (el.dataset.jsDecimalReady) return;
                                var step = el.getAttribute('step');
                                var isDecimal = el.classList.contains('js-decimal')
                                        || el.getAttribute('inputmode') === 'decimal'
                                        || (step && parseFloat(step) > 0 && parseFloat(step) < 1);
                                if (!isDecimal) return;
                                el.dataset.jsDecimalReady = '1';
                                if (el.getAttribute('type') === 'number') {
                                        el.setAttribute('type', 'text');
                                        el.setAttribute('inputmode', 'decimal');
                                        el.classList.add('js-decimal');
                                        el.removeAttribute('step');
                                }
                                sanitize(el);
                        }

                        function scan(root) {
                                (root && root.querySelectorAll ? root : document)
                                        .querySelectorAll('input[type="number"], ' + SEL)
                                        .forEach(adopt);
                        }

                        function boot() {
                                scan(document);

                                document.addEventListener('input', function (e) {
                                        var t = e.target;
                                        if (t && t.matches && t.matches(SEL)) sanitize(t);
                                }, true);

                                document.addEventListener('blur', function (e) {
                                        var t = e.target;
                                        if (t && t.matches && t.matches(SEL)) normalize(t);
                                }, true);

                                // Trước khi gửi form: chuẩn hoá mọi ô thập phân (đổi "," -> ".", bỏ "." thừa).
                                document.addEventListener('submit', function (e) {
                                        var form = e.target;
                                        if (form && form.querySelectorAll) form.querySelectorAll(SEL).forEach(normalize);
                                }, true);

                                if (!document.body || typeof MutationObserver === 'undefined') return;

                                new MutationObserver(function (mutations) {
                                        for (var i = 0; i < mutations.length; i++) {
                                                var nodes = mutations[i].addedNodes;
                                                for (var j = 0; j < nodes.length; j++) {
                                                        var node = nodes[j];
                                                        if (node.nodeType !== 1) continue;
                                                        if (node.matches && node.matches('input[type="number"], ' + SEL)) adopt(node);
                                                        if (node.querySelectorAll) scan(node);
                                                }
                                        }
                                }).observe(document.body, { childList: true, subtree: true });
                        }

                        if (document.readyState === 'loading') {
                                document.addEventListener('DOMContentLoaded', boot);
                        } else {
                                boot();
                        }
                })();
        </script>

        {{--
        | Khoá nút submit khi ô bắt buộc còn trống.
        |
        | Dùng cho các modal điều chỉnh: chưa nhập "Lý do điều chỉnh" thì nút
        | "Ghi nhận điều chỉnh" / "Lưu điều chỉnh" phải disable. Đánh dấu ô cần nhập
        | bằng thuộc tính data-require-fill, script tự tìm nút submit trong cùng form.
        --}}
        <script>
                (function () {
                        function sync(field) {
                                var form = field.form || (field.closest && field.closest('form'));
                                if (!form) return;
                                var btn = form.querySelector('button[type="submit"], input[type="submit"]');
                                if (!btn) return;
                                btn.disabled = String(field.value || '').trim() === '';
                        }

                        function scan(root) {
                                (root && root.querySelectorAll ? root : document)
                                        .querySelectorAll('[data-require-fill]').forEach(sync);
                        }

                        document.addEventListener('input', function (e) {
                                var t = e.target;
                                if (t && t.matches && t.matches('[data-require-fill]')) sync(t);
                        }, true);

                        // Mỗi lần mở modal, đồng bộ lại trạng thái nút vì ô lý do vừa được JS xoá trắng.
                        if (window.jQuery) {
                                jQuery(document).on('shown.bs.modal', function (e) { scan(e.target); });
                        }

                        if (document.readyState === 'loading') {
                                document.addEventListener('DOMContentLoaded', function () { scan(document); });
                        } else {
                                scan(document);
                        }
                })();
        </script>

        {{-- Ô chọn bộ phận (layout/departmentOptions): select2 có ô tìm kiếm, gom theo công ty --}}
        <script>
                (function ($) {
                        if (!$ || !$.fn.select2) return;

                        function deptSelects(root) {
                                return $(root).find('select').filter(function () {
                                        return $(this).children('optgroup[data-dept-group]').length > 0
                                                && !$(this).hasClass('mat-transfer-dept-fixed');
                                });
                        }

                        function init(root) {
                                deptSelects(root).each(function () {
                                        var $select = $(this);
                                        if ($select.hasClass('select2-hidden-accessible')) return; // trang đã tự bật select2
                                        var $modal = $select.closest('.modal');
                                        $select.select2({
                                                theme: 'bootstrap4',
                                                width: '100%',
                                                dropdownParent: $modal.length ? $modal : $(document.body),
                                                placeholder: $select.find('option[value=""]').first().text().trim() || '-- Chọn phòng ban --',
                                                language: { noResults: function () { return 'Không tìm thấy phòng ban phù hợp'; } }
                                        });
                                });
                        }

                        // Chạy sau script của từng trang để không giành quyền khởi tạo
                        $(function () { setTimeout(function () { init(document); }, 0); });

                        // JS mở modal hay gán .val() mà không trigger change -> làm mới chữ hiển thị
                        $(document).on('shown.bs.modal', function (e) {
                                init(e.target);
                                deptSelects(e.target).filter('.select2-hidden-accessible').trigger('change.select2');
                        });
                })(window.jQuery);
        </script>
