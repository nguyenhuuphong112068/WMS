{{--
    Cỡ trang trên tablet / điện thoại (WmsPageZoom).
    Chrome Android không cho chụm thu nhỏ dưới mức vừa khít màn hình (viewport width=device-width),
    nên trang tự đổi bề rộng viewport: width = bề rộng màn hình / tỉ lệ -> trình duyệt tự co / phóng cả trang
    như thu phóng thật (không đụng bố cục, toạ độ JS vẫn đúng). Trình duyệt bỏ qua thẻ viewport (chế độ
    "Trang web cho máy tính") thì dự phòng bằng CSS zoom trên <html>.
    Lưu theo từng thiết bị (localStorage 'wms.pageZoom'); nút − % + trên topNAV chỉ hiện khi con trỏ chính là cảm ứng.
    Đặt ngay sau thẻ <meta name="viewport" id="wms-viewport"> để áp dụng trước lần vẽ đầu.
--}}
<script>
    (function() {
        var KEY = 'wms.pageZoom';
        var LEVELS = [0.5, 0.67, 0.75, 0.8, 0.9, 1, 1.1, 1.25, 1.5];
        var BASE = 'initial-scale=1, maximum-scale=5, user-scalable=yes';
        var touch = !!(window.matchMedia && window.matchMedia('(pointer: coarse)').matches);
        var current = 1;

        function read() {
            try {
                var v = parseFloat(localStorage.getItem(KEY));
                return LEVELS.indexOf(v) >= 0 ? v : 1;
            } catch (e) {
                return 1;
            }
        }

        function save(z) {
            try {
                if (z === 1) localStorage.removeItem(KEY);
                else localStorage.setItem(KEY, String(z));
            } catch (e) { /* trình duyệt chặn lưu: chỉ áp dụng cho trang đang mở */ }
        }

        function meta() {
            return document.getElementById('wms-viewport') || document.querySelector('meta[name="viewport"]');
        }

        function screenWidth() {
            var w = window.screen ? window.screen.width : 0;
            var h = window.screen ? window.screen.height : 0;
            // Vài trình duyệt không đảo screen.width khi xoay ngang: lấy theo hướng đang cầm
            var landscape = window.matchMedia && window.matchMedia('(orientation: landscape)').matches;
            if (w && h) w = landscape ? Math.max(w, h) : Math.min(w, h);
            return w || document.documentElement.clientWidth;
        }

        function label() {
            document.querySelectorAll('[data-wms-zoom-value]').forEach(function(el) {
                el.textContent = Math.round(current * 100) + '%';
            });
            document.querySelectorAll('[data-wms-zoom="out"]').forEach(function(el) {
                el.disabled = current <= LEVELS[0];
            });
            document.querySelectorAll('[data-wms-zoom="in"]').forEach(function(el) {
                el.disabled = current >= LEVELS[LEVELS.length - 1];
            });
        }

        function apply(z) {
            current = z;
            var root = document.documentElement;
            var m = meta();
            root.style.zoom = '';
            if (!touch || !m) {
                label();
                return;
            }
            if (z === 1) {
                m.setAttribute('content', 'width=device-width, ' + BASE);
                label();
                return;
            }
            var target = Math.round(screenWidth() / z);
            m.setAttribute('content', 'width=' + target + ', maximum-scale=5, user-scalable=yes');
            // Kiểm tra trình duyệt có nhận thẻ viewport không; không nhận thì dùng CSS zoom
            requestAnimationFrame(function() {
                requestAnimationFrame(function() {
                    if (current !== z) return;
                    if (Math.abs(root.clientWidth - target) > 24) {
                        m.setAttribute('content', 'width=device-width, ' + BASE);
                        root.style.zoom = String(z);
                    }
                    window.dispatchEvent(new Event('resize'));
                });
            });
            label();
        }

        function step(dir) {
            var i = LEVELS.indexOf(current);
            if (i < 0) i = LEVELS.indexOf(1);
            i = Math.min(LEVELS.length - 1, Math.max(0, i + dir));
            save(LEVELS[i]);
            apply(LEVELS[i]);
        }

        window.WmsPageZoom = {
            get: function() { return current; },
            set: function(z) { z = LEVELS.indexOf(z) >= 0 ? z : 1; save(z); apply(z); },
            step: step
        };

        apply(read());

        function reapply() {
            if (current !== 1) apply(current);
        }
        window.addEventListener('orientationchange', function() { setTimeout(reapply, 300); });
        if (window.screen && window.screen.orientation && window.screen.orientation.addEventListener) {
            window.screen.orientation.addEventListener('change', function() { setTimeout(reapply, 300); });
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (touch) {
                document.querySelectorAll('.wms-zoom').forEach(function(el) { el.classList.remove('d-none'); });
            }
            label();
        });

        document.addEventListener('click', function(event) {
            var btn = event.target.closest && event.target.closest('[data-wms-zoom]');
            if (!btn) return;
            event.preventDefault();
            var act = btn.getAttribute('data-wms-zoom');
            if (act === 'reset') window.WmsPageZoom.set(1);
            else step(act === 'in' ? 1 : -1);
        });
    })();
</script>
