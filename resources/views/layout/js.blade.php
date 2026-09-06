
        <script src="{{asset ('dataTable/plugins/jquery/jquery.min.js')}}"></script>
        <script src="{{asset ('dataTable/plugins/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
        <script src="{{asset ('dataTable/plugins/datatables/jquery.dataTables.min.js')}}"></script>
        <script src="{{asset ('dataTable/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js')}}"></script>
        <script src="{{asset ('dataTable/plugins/datatables-responsive/js/dataTables.responsive.min.js')}}"></script>
        <script src="{{asset ('dataTable/plugins/datatables-responsive/js/responsive.bootstrap4.min.js')}}"></script>
        <script src="{{ asset('dataTable/dist/js/adminlte.min.js') }}"></script>

        <!-- Select2 -->
        <script src="{{asset ('dataTable/plugins/select2/js/select2.full.min.js')}}"></script>
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