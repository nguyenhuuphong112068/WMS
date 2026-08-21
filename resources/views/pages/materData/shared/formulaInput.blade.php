{{--
|--------------------------------------------------------------------------
| DỮ LIỆU GỐC - Ô NHẬP CÔNG THỨC HOÁ HỌC (chỉ số trên / chỉ số dưới)
|--------------------------------------------------------------------------
| Cách dùng trong modal create / update:
|
|   @include('pages.materData.shared.formulaInput', ['bag' => $bag])
|
| Tham số tuỳ chọn: 'field' (mặc định chemical_formula), 'label', 'placeholder'.
|
| Chỉ số được lưu thẳng vào CSDL dưới dạng ký tự Unicode (H₂SO₄, Ca²⁺) nên hiển
| thị đúng ở mọi nơi: bảng dữ liệu, ô tìm kiếm, xuất Excel, in ấn — không phải
| lưu thẻ HTML nên cũng không có rủi ro chèn mã vào trang.
--}}

@php
    $fxField = $field ?? 'chemical_formula';
    $fxLabel = $label ?? 'Công Thức Hoá Học';
    $fxPlaceholder = $placeholder ?? 'Ví dụ: CH₃CN, H₂SO₄, CuSO₄·5H₂O';
@endphp

<div class="form-group fx-group">
    <label>{{ $fxLabel }}</label>

    <div class="fx-tools">
        <button type="button" class="fx-btn" data-fx="sub" title="Chỉ số dưới - Ctrl + mũi tên xuống">
            X<sub>2</sub>
        </button>
        <button type="button" class="fx-btn" data-fx="sup" title="Chỉ số trên - Ctrl + mũi tên lên">
            X<sup>2</sup>
        </button>
        <span class="fx-sep"></span>
        <button type="button" class="fx-btn" data-fx="dot" title="Dấu chấm giữa cho muối ngậm nước">·</button>
        <button type="button" class="fx-btn fx-btn-wide" data-fx="auto"
            title="Tự động hạ chỉ số các con số đứng sau ký hiệu nguyên tố">
            <i class="fas fa-magic mr-1"></i>Tự động
        </button>
        <button type="button" class="fx-btn" data-fx="plain" title="Bỏ định dạng chỉ số">
            <i class="fas fa-eraser"></i>
        </button>
        <span class="fx-state"></span>
    </div>

    <input type="text" name="{{ $fxField }}" maxlength="255" autocomplete="off" data-fx-input
        class="form-control fx-input {{ $bag->has($fxField) ? 'is-invalid' : '' }}" value="{{ old($fxField) }}"
        placeholder="{{ $fxPlaceholder }}">

    @if ($bag->has($fxField))
        <span class="md-error">{{ $bag->first($fxField) }}</span>
    @endif

    <small class="fx-tip">
        Gõ <b>H2SO4</b> rồi bấm <b>Tự động</b> để ra <b>H₂SO₄</b>, hoặc bôi đen phần cần đổi rồi bấm X₂ / X².
    </small>
</div>

@once
    <style>
        /* ---------- Thanh công cụ của ô công thức ---------- */
        .fx-tools {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 7px;
        }

        .fx-btn {
            min-width: 34px;
            height: 30px;
            padding: 0 9px;
            border: 1px solid #dbe6f2;
            border-radius: var(--border-radius-md);
            background: #fff;
            color: #475569;
            font-size: 0.82rem;
            font-weight: 700;
            line-height: 1;
            transition: all var(--transition-fast);
        }

        .fx-btn:hover {
            background: var(--primary-soft);
            border-color: var(--primary-light);
            color: var(--primary-dark);
            transform: translateY(-1px);
        }

        .fx-btn:focus {
            outline: none;
        }

        .fx-btn.active {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
            box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.15);
        }

        .fx-btn-wide {
            font-weight: 600;
            font-size: 0.78rem;
        }

        .fx-btn sub,
        .fx-btn sup {
            font-size: 0.68em;
        }

        .fx-sep {
            width: 1px;
            height: 18px;
            background: #e2e8f0;
        }

        .fx-state {
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--primary);
            margin-left: 2px;
        }

        .fx-input {
            letter-spacing: 0.4px;
        }

        .fx-tip {
            display: block;
            margin-top: 6px;
            color: #94a3b8;
            font-size: 0.78rem;
        }
    </style>

    <script>
        /* ---------------------------------------------------------------
         | Ô nhập công thức hoá học
         | - Bấm X₂ / X² (hoặc Ctrl + ↓ / Ctrl + ↑) để bật chế độ gõ chỉ số.
         | - Đang bôi đen chữ mà bấm nút thì đổi ngay phần đang bôi đen.
         | - Nút "Tự động" hạ chỉ số mọi con số đứng sau ký hiệu nguyên tố.
         --------------------------------------------------------------- */
        (function() {
            var MAX_LENGTH = 255;

            var SUB = {
                '0': '₀', '1': '₁', '2': '₂', '3': '₃', '4': '₄',
                '5': '₅', '6': '₆', '7': '₇', '8': '₈', '9': '₉',
                '+': '₊', '-': '₋', '=': '₌', '(': '₍', ')': '₎'
            };

            var SUP = {
                '0': '⁰', '1': '¹', '2': '²', '3': '³', '4': '⁴',
                '5': '⁵', '6': '⁶', '7': '⁷', '8': '⁸', '9': '⁹',
                '+': '⁺', '-': '⁻', '=': '⁼', '(': '⁽', ')': '⁾'
            };

            // Bản đồ ngược: từ ký tự chỉ số về ký tự thường
            var PLAIN = {};
            Object.keys(SUB).forEach(function(key) {
                PLAIN[SUB[key]] = key;
            });
            Object.keys(SUP).forEach(function(key) {
                PLAIN[SUP[key]] = key;
            });

            /** Đổi một đoạn chữ sang chỉ số theo map, map = null nghĩa là trả về chữ thường. */
            function convert(text, map) {
                return text.split('').map(function(char) {
                    var base = PLAIN[char] || char;
                    return map && map[base] ? map[base] : base;
                }).join('');
            }

            function replaceRange(input, text, start, end) {
                input.value = input.value.slice(0, start) + text + input.value.slice(end);
            }

            /** Bật / tắt chế độ gõ chỉ số và cập nhật trạng thái nút. */
            function setMode(input, mode) {
                var tools = $(input).closest('.fx-group').find('.fx-tools');

                $(input).data('fxMode', mode || '');
                tools.find('.fx-btn').removeClass('active');

                if (mode) {
                    tools.find('.fx-btn[data-fx="' + mode + '"]').addClass('active');
                }

                tools.find('.fx-state').text(
                    mode === 'sub' ? 'Đang gõ chỉ số dưới' : (mode === 'sup' ? 'Đang gõ chỉ số trên' : '')
                );
            }

            function apply(input, action) {
                var start = input.selectionStart || 0;
                var end = input.selectionEnd || 0;
                var mode = $(input).data('fxMode');

                // Dấu chấm giữa của muối ngậm nước: CuSO₄·5H₂O
                if (action === 'dot') {
                    if (input.value.length >= MAX_LENGTH && start === end) return;

                    replaceRange(input, '·', start, end);
                    input.selectionStart = input.selectionEnd = start + 1;
                    return;
                }

                // Tự động: số đứng ngay sau ký hiệu nguyên tố hoặc dấu đóng ngoặc thì hạ chỉ số
                if (action === 'auto') {
                    input.value = input.value.replace(/([A-Za-z\)\]])(\d+)/g, function(all, before, digits) {
                        return before + convert(digits, SUB);
                    });
                    input.selectionStart = input.selectionEnd = input.value.length;
                    setMode(input, null);
                    return;
                }

                var map = action === 'sub' ? SUB : (action === 'sup' ? SUP : null);

                // Đang bôi đen: đổi ngay phần đang chọn
                if (end > start) {
                    var text = convert(input.value.slice(start, end), map);

                    replaceRange(input, text, start, end);
                    input.selectionStart = start;
                    input.selectionEnd = start + text.length;
                    setMode(input, null);
                    return;
                }

                // Không bôi đen mà bấm cục tẩy: bỏ chỉ số của cả ô
                if (action === 'plain') {
                    input.value = convert(input.value, null);
                    setMode(input, null);
                    return;
                }

                setMode(input, mode === action ? null : action);
            }

            // jQuery được nạp ở cuối trang (layout.js) nên phải chờ DOM sẵn sàng mới gắn sự kiện
            document.addEventListener('DOMContentLoaded', function() {

                // Giữ con trỏ đang ở trong ô nhập khi bấm nút trên thanh công cụ
                $(document).on('mousedown', '.fx-btn', function(e) {
                    e.preventDefault();
                });

                $(document).on('click', '.fx-btn', function() {
                    var input = $(this).closest('.fx-group').find('input[data-fx-input]')[0];

                    if (!input) return;

                    input.focus();
                    apply(input, $(this).data('fx'));
                });

                $(document).on('keydown', 'input[data-fx-input]', function(e) {
                    if (!e.ctrlKey || e.altKey) return;

                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        apply(this, 'sub');
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        apply(this, 'sup');
                    }
                });

                // Đang bật chế độ chỉ số thì ký tự gõ vào được đổi ngay
                $(document).on('keypress', 'input[data-fx-input]', function(e) {
                    if (e.ctrlKey || e.altKey || e.metaKey) return;

                    var mode = $(this).data('fxMode');

                    if (!mode) return;

                    var map = mode === 'sub' ? SUB : SUP;
                    var char = e.key;

                    if (!char || char.length !== 1 || !map[char]) return;

                    e.preventDefault();

                    var start = this.selectionStart;
                    var end = this.selectionEnd;

                    if (this.value.length >= MAX_LENGTH && start === end) return;

                    replaceRange(this, map[char], start, end);
                    this.selectionStart = this.selectionEnd = start + 1;
                });

                // Mở lại modal thì trả thanh công cụ về trạng thái ban đầu
                $(document).on('show.bs.modal', '.md-modal', function() {
                    $(this).find('input[data-fx-input]').each(function() {
                        setMode(this, null);
                    });
                });
            });
        })();
    </script>
@endonce
