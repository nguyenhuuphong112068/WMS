{{--
|--------------------------------------------------------------------------
| CẢNH BÁO VƯỢT NGƯỠNG TỒN TỐI ĐA - CSS + JS dùng chung
|--------------------------------------------------------------------------
| Ngưỡng tối đa khai ở tab "<Loại hàng> Của Phòng" (cột max_stock). Màn Nhập và màn Dự
| Trù của cả ba loại hàng dùng chung hàm dưới đây để câu chữ và cách tính giống nhau.
|
| Chỉ CẢNH BÁO, không chặn lưu: quyết định trữ bao nhiêu vẫn là của người dùng.
|
| Cách dùng:
|   wmsMaxStockWarn($('.js-max-stock-warn', $form), info, adding);
|
|   - $box   : ô hiện cảnh báo (thường là <div class="js-max-stock-warn"></div>)
|   - info   : { max_stock, on_hand, unit } của mã danh mục đang chọn, lấy từ
|              App\Support\MaxStockWarning (null / thiếu ngưỡng thì không cảnh báo)
|   - adding : số lượng sắp nhập / sắp dự trù, theo ĐƠN VỊ CỦA PHÒNG
|
| Bọc trong @once nên @include nhiều lần trên một trang vẫn chỉ in ra một bản.
--}}

@once
    <style>
        .js-max-stock-warn {
            display: none;
            margin-top: 6px;
            padding: 8px 11px;
            border: 1px solid #FCD34D;
            border-left: 4px solid #F59E0B;
            border-radius: var(--border-radius-md);
            background: #FEF3C7;
            color: #92400E;
            font-size: 0.83rem;
            line-height: 1.45;
        }

        .js-max-stock-warn.is-on {
            display: block;
        }

        .js-max-stock-warn b {
            color: #78350F;
        }
    </style>

    <script>
        (function() {
            /* Bỏ số 0 thừa ở phần thập phân: 12.5000 -> 12.5 */
            function fmt(value) {
                var number = Number(value || 0);

                return number.toLocaleString('vi-VN', {
                    maximumFractionDigits: 4
                });
            }

            /**
             * Hiện / ẩn cảnh báo vượt ngưỡng tồn tối đa.
             *
             * Trả về true khi đang vượt ngưỡng, để màn hình nào cần thì dùng thêm.
             */
            window.wmsMaxStockWarn = function($box, info, adding) {
                if (!$box || !$box.length) return false;

                var max = info && info.max_stock !== null && info.max_stock !== undefined ?
                    Number(info.max_stock) : null;
                var onHand = info ? Number(info.on_hand || 0) : 0;
                var unit = (info && info.unit) ? ' ' + info.unit : '';
                var add = Number(adding || 0);

                // Chưa khai ngưỡng, chưa nhập số lượng, hoặc số lượng không hợp lệ thì thôi
                if (max === null || isNaN(max) || isNaN(add) || add <= 0) {
                    $box.removeClass('is-on').empty();
                    return false;
                }

                var after = onHand + add;

                // Sai số 0.00005 cho khớp EPSILON của các màn tồn kho
                if (after <= max + 0.00005) {
                    $box.removeClass('is-on').empty();
                    return false;
                }

                $box.html(
                    '<i class="fas fa-triangle-exclamation mr-1"></i> Vượt <b>ngưỡng tồn tối đa</b> của phòng: ' +
                    'tồn hiện tại <b>' + fmt(onHand) + unit + '</b> + lần này <b>' + fmt(add) + unit + '</b> = <b>' +
                    fmt(after) + unit + '</b>, trong khi ngưỡng tối đa là <b>' + fmt(max) + unit + '</b>.'
                ).addClass('is-on');

                return true;
            };
        })();
    </script>
@endonce
