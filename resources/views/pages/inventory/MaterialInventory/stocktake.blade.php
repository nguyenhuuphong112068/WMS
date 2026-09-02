{{--
|--------------------------------------------------------------------------
| TAB KIỂM KÊ ĐỊNH KỲ - TỒN KHO VẬT TƯ (chu kỳ 3 tháng 1 lần, kỳ = một quý)
|--------------------------------------------------------------------------
| Dữ liệu do MaterialStocktakeController::panel() dựng, gửi sang trong $stocktake.
|
| Bố cục gọn để nhường chỗ cho hai bảng dữ liệu: đầu tab chỉ một thanh thông tin kỳ,
| một dải số liệu inline và ô chọn Theo Dõi Chu Kỳ (danh sách 8 quý gần nhất). Phần
| giải thích quy tắc thu vào thẻ <details>, mở ra khi cần.
|
| Bảng đếm KHÔNG gắn class md-table: DataTables phân trang sẽ tháo các dòng ở trang
| sau ra khỏi DOM, bấm lưu là mất số vừa gõ. Ở đây lọc / tìm bằng JS ngay trên bảng.
|
| File này chỉ là KHUNG: <style> + toàn bộ JS, nạp đúng một lần. Nội dung thay đổi
| nằm ở stocktakePanel.blade.php trong #stPanel - mỗi thao tác gửi AJAX rồi thay
| riêng vùng đó, KHÔNG tải lại trang (tải lại là nhảy về tab đầu của màn hình tồn).
| Vì vùng đó bị thay liên tục nên MỌI handler dưới đây đều gắn theo kiểu uỷ quyền
| ($(document).on(...)), không bind thẳng vào phần tử.
--}}

@php
    $stStateLabels = ['waiting' => 'Chưa đếm', 'match' => 'Khớp sổ', 'over' => 'Thừa', 'short' => 'Thiếu'];
@endphp

<style>
    /* ---------- Thanh thông tin kỳ (1 dòng) ---------- */
    .st-bar {
        display: flex; flex-wrap: wrap; align-items: center; gap: 10px;
        padding: 10px 14px; margin-bottom: 10px;
        border: 1px solid var(--primary-soft); border-radius: var(--border-radius-lg);
        background: linear-gradient(135deg, var(--primary-soft), #fff);
    }
    .st-bar-title { font-size: .95rem; font-weight: 700; color: var(--primary); letter-spacing: .5px; }
    .st-bar-right { margin-left: auto; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .st-bar-right form { margin: 0; }

    .st-state { display: inline-block; border-radius: 999px; padding: 2px 10px; font-size: .74rem; font-weight: 700; white-space: nowrap; }
    .st-state.counting { background: #DBEAFE; color: #1E40AF; }
    .st-state.completed { background: #DCFCE7; color: #166534; }
    .st-state.pending { background: #FEF9C3; color: #854D0E; }
    .st-state.missed { background: #E2E8F0; color: #475569; }

    /* Ô chọn Theo Dõi Chu Kỳ - thay cho dải thẻ 12 tháng cũ, đỡ chiếm chỗ */
    .st-cycle-pick { display: flex; align-items: center; gap: 6px; }
    .st-cycle-pick select { min-width: 230px; height: 31px; padding: 2px 8px; font-size: .82rem; }

    /* ---------- Dải số liệu inline ---------- */
    .st-metrics {
        display: flex; flex-wrap: wrap; align-items: center; gap: 8px;
        margin-bottom: 10px;
    }
    .st-metric {
        display: inline-flex; align-items: baseline; gap: 5px;
        border: 1px solid var(--primary-soft); border-radius: 999px; background: #fff;
        padding: 3px 12px; font-size: .78rem; color: #64748B; font-weight: 600; white-space: nowrap;
    }
    .st-metric b { font-size: .98rem; color: var(--primary); font-weight: 700; }
    .st-metric.warn b { color: #B45309; }
    .st-metric.warn { border-color: #FDE68A; background: #FFFBEB; }
    .st-metric.danger b { color: #B91C1C; }
    .st-metric.danger { border-color: #FECACA; background: #FEF2F2; }

    .st-progress { flex: 1 1 140px; min-width: 120px; height: 8px; border-radius: 999px; background: var(--primary-soft); overflow: hidden; }
    .st-progress > div { height: 100%; background: linear-gradient(90deg, var(--primary-light), var(--primary)); transition: width .3s; }

    /* ---------- Thanh công cụ của bảng đếm ---------- */
    .st-toolbar { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-bottom: 8px; }

    /* ---------- Ô quét QR ---------- */
    .st-scan {
        display: flex; align-items: center; gap: 5px;
        padding: 3px 9px; border: 1px solid var(--primary-lighter); border-radius: var(--border-radius-md);
        background: var(--primary-soft);
    }
    .st-scan input { width: 250px; height: 29px; font-size: .82rem; font-weight: 600; letter-spacing: .3px; background: #fff; }
    .st-scan .btn { height: 29px; padding: 0 9px; display: inline-flex; align-items: center; font-size: .82rem; }

    .st-scan-result { display: none; width: 100%; margin: -2px 0 8px; padding: 4px 10px; border-radius: var(--border-radius-md); font-size: .8rem; }
    .st-scan-result.is-shown { display: block; }
    .st-scan-result.ok { background: #DCFCE7; border: 1px solid #86EFAC; color: #15803D; }
    .st-scan-result.fail { background: #FEE2E2; border: 1px solid #FCA5A5; color: #B91C1C; }

    /* Dòng vừa quét ra - tô sáng để mắt bắt được ngay giữa bảng dài */
    .st-row.st-hit > td { background: var(--primary-soft) !important; }
    .st-row.st-hit > td:first-child { box-shadow: inset 3px 0 0 var(--primary); }

    /* Giống .mi-chip nhưng để riêng một lớp: .mi-chip đã có sẵn handler lọc theo trạng
       thái tồn của tab đầu, dùng chung sẽ xoá mất bộ lọc của tab đó. */
    .st-chip {
        display: inline-block; border: 1px solid var(--primary-soft); background: #fff; border-radius: 999px;
        padding: 3px 11px; font-size: .78rem; font-weight: 600; cursor: pointer; transition: all .2s;
    }
    .st-chip:hover { border-color: var(--primary-lighter); }
    .st-chip.is-active { background: var(--primary); color: #fff; border-color: var(--primary); }

    /* ---------- Bảng đếm ---------- */
    .st-count-wrap { max-height: 62vh; overflow: auto; border: 1px solid var(--primary-soft); border-radius: var(--border-radius-lg); }
    .st-count-wrap table { margin-bottom: 0; }
    .st-count-wrap thead th {
        position: sticky; top: 0; z-index: 2;
        background: var(--primary-soft); color: var(--primary); font-weight: 700; font-size: .78rem;
    }
    .st-count-wrap tbody tr:hover { background: rgba(var(--primary-rgb), .05); }
    .st-count-wrap input.form-control-sm { height: 29px; font-size: .82rem; }
    .st-diff { font-weight: 700; }
    .st-diff.match { color: #16A34A; }
    .st-diff.over { color: #B45309; }
    .st-diff.short { color: #DC2626; }
    .st-diff.waiting { color: #94A3B8; }
    .st-row.is-hidden { display: none; }
    .st-skip-note { font-size: .72rem; color: #B91C1C; }

    /* ---------- Quy tắc kiểm kê (thu gọn) ---------- */
    .st-note { font-size: .8rem; color: var(--primary-dark); }
    .st-note > summary {
        cursor: pointer; font-weight: 600; color: var(--primary); list-style: none;
        display: inline-flex; align-items: center; gap: 5px;
    }
    .st-note > summary::-webkit-details-marker { display: none; }
    .st-note > div {
        margin-top: 6px; padding: 9px 12px; line-height: 1.55;
        background: var(--primary-soft); border: 1px dashed var(--primary-lighter);
        border-radius: var(--border-radius-md);
    }

    .st-section {
        display: flex; align-items: center; gap: 8px; margin: 16px 0 8px;
        font-size: .85rem; font-weight: 700; color: var(--primary); letter-spacing: .5px;
    }
</style>


{{-- Vùng nạp lại bằng AJAX sau mỗi thao tác - xem stRefreshPanel() ở cuối file --}}
<div id="stPanel">
    @include('pages.inventory.MaterialInventory.stocktakePanel')
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var stDiffLabels = @json($stStateLabels);
        var stFilter = '';

        function stNum(value) {
            var text = Number(value).toFixed(4).replace(/0+$/, '').replace(/\.$/, '');
            return text === '-0' ? '0' : text;
        }

        // Tính lại chênh lệch ngay khi gõ số đếm, khỏi phải lưu mới biết lệch bao nhiêu
        function stRefresh($row) {
            var system = parseFloat($row.data('st-system')) || 0;
            var raw = $row.find('.st-actual').val();
            var $diff = $row.find('.st-diff');

            if (raw === undefined || $.trim(raw) === '' || isNaN(parseFloat(raw))) {
                $row.attr('data-st-state', 'waiting');
                $diff.attr('class', 'st-diff waiting').text(stDiffLabels.waiting);
                return;
            }

            var diff = Math.round((parseFloat(raw) - system) * 10000) / 10000;
            var state = Math.abs(diff) < 0.00005 ? 'match' : (diff > 0 ? 'over' : 'short');

            $row.attr('data-st-state', state);
            $diff.attr('class', 'st-diff ' + state).text((diff > 0 ? '+' : '') + stNum(diff));
        }

        $(document).on('input change', '.st-actual', function () {
            stRefresh($(this).closest('.st-row'));
            stApply();
        });

        // Lọc theo tình trạng đếm + từ khoá, làm thẳng trên DOM vì bảng đếm không dùng DataTables
        function stApply() {
            var q = ($('#stSearch').val() || '').toString().trim().toLowerCase();
            var shown = 0;

            $('#stCountForm .st-row').each(function () {
                var okState = stFilter === '' || $(this).attr('data-st-state') === stFilter;
                var okText = q === '' || ($(this).data('st-search') || '').toString().indexOf(q) >= 0;
                $(this).toggleClass('is-hidden', !(okState && okText));
                if (okState && okText) shown++;
            });

            $('#stNoResult').css('display', shown === 0 ? '' : 'none');
        }

        $(document).on('click', '[data-st-filter]', function () {
            $('[data-st-filter]').removeClass('is-active');
            $(this).addClass('is-active');
            stFilter = $(this).data('st-filter') || '';
            stApply();
        });
        $(document).on('input', '#stSearch', stApply);

        /* ---------- Quét QR trên nhãn lô để nhảy tới dòng cần đếm ---------- */
        // Gõ vào ô này là lọc bảng như bình thường; QUÉT (máy đọc mã rời hoặc camera đều
        // kết thúc bằng phím Enter - xem pages.shared.cameraScan) thì bỏ hết bộ lọc, nhảy
        // thẳng tới đúng dòng của mã vừa quét và đặt con trỏ vào ô số đếm của dòng đó.
        function stScanResult(kind, build) {
            var $box = $('#stScanResult').removeClass('ok fail').addClass('is-shown ' + kind).empty();
            build($box);
        }

        function stScanFind() {
            var $input = $('#stSearch');
            var code = ($input.val() || '').trim();

            if (!code) {
                stScanResult('fail', function ($box) {
                    $box.append($('<i>').addClass('fas fa-exclamation-circle mr-1'))
                        .append(document.createTextNode('Hãy quét mã QR trên nhãn lô hoặc gõ mã rồi nhấn Enter.'));
                });
                return;
            }

            // Bỏ mọi bộ lọc đang bật để dòng vừa quét chắc chắn hiện ra
            stFilter = '';
            $('[data-st-filter]').removeClass('is-active').filter('[data-st-filter=""]').addClass('is-active');
            $input.val('');
            stApply();

            var $rows = $('#stCountForm .st-row');
            var needle = code.toUpperCase();

            // Ưu tiên khớp đúng mã xuất nhập (nội dung mã QR), không có mới tìm gần đúng
            var $hit = $rows.filter(function () {
                return ($(this).attr('data-st-code') || '').toUpperCase() === needle;
            });

            if (!$hit.length) {
                $hit = $rows.filter(function () {
                    return ($(this).data('st-search') || '').toString().indexOf(code.toLowerCase()) >= 0;
                });
            }

            $rows.removeClass('st-hit');

            if (!$hit.length) {
                stScanResult('fail', function ($box) {
                    $box.append($('<i>').addClass('fas fa-exclamation-circle mr-1'))
                        .append(document.createTextNode('Không có mã '))
                        .append($('<b>').text(code))
                        .append(document.createTextNode(
                            ' trong phiếu kiểm kê kỳ này. Lô đã dùng hết trước khi mở phiếu, thuộc phòng ban khác, hoặc nhãn không phải nhãn vật tư.'));
                });
                $input.focus();
                return;
            }

            var $row = $hit.first().addClass('st-hit');
            var $actual = $row.find('.st-actual');

            $row.get(0).scrollIntoView({ behavior: 'smooth', block: 'center' });

            // Phiếu đã chốt thì không còn ô nhập, chỉ tô sáng dòng cho dễ đối chiếu
            if ($actual.length) {
                $actual.focus().select();
            }

            stScanResult('ok', function ($box) {
                $box.append($('<i>').addClass('fas fa-check-circle mr-1'))
                    .append(document.createTextNode('Đã tới dòng '))
                    .append($('<b>').text($row.attr('data-st-code')))
                    .append(document.createTextNode(' · ' + ($row.attr('data-st-name') || '')))
                    .append(document.createTextNode(
                        $actual.length ? ' — nhập số đếm thực tế rồi quét mã tiếp theo.' : '.'));

                if ($hit.length > 1) {
                    $box.append(document.createTextNode(' (' + $hit.length + ' dòng khớp, đang ở dòng đầu tiên)'));
                }
            });
        }

        $(document).on('keydown', '#stSearch', function (e) {
            if (e.key !== 'Enter') return;

            e.preventDefault();
            stScanFind();
        });

        $(document).on('click', '.btn-st-find', stScanFind);

        // Chốt kiểm kê: hỏi lại rồi gửi chính biểu mẫu đếm sang route complete
        $(document).on('click', '#stCompleteBtn', function (e) {
            e.preventDefault();
            var waiting = $('#stCountForm .st-row').filter(function () {
                return $(this).attr('data-st-state') === 'waiting';
            }).length;

            if (waiting > 0) {
                Swal.fire({
                    title: 'Chưa đếm đủ',
                    text: 'Còn ' + waiting + ' dòng chưa nhập số đếm. Phải đếm đủ mọi dòng mới chốt được phiếu kiểm kê.',
                    icon: 'warning',
                    confirmButtonColor: '#2E7BC4',
                    confirmButtonText: 'Đã hiểu'
                });
                return;
            }

            var diff = $('#stCountForm .st-row').filter(function () {
                var s = $(this).attr('data-st-state');
                return s === 'over' || s === 'short';
            }).length;

            Swal.fire({
                title: 'Chốt phiếu kiểm kê?',
                text: diff > 0
                    ? 'Có ' + diff + ' dòng lệch sổ sẽ được ghi cân đối để kéo tồn sổ sách về số thực đếm. Phiếu đã chốt không sửa lại được.'
                    : 'Toàn bộ số đếm khớp sổ. Phiếu đã chốt không sửa lại được.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#2E7BC4',
                cancelButtonColor: '#94A3B8',
                confirmButtonText: 'Chốt kiểm kê',
                cancelButtonText: 'Huỷ'
            }).then(function (result) {
                if (result.isConfirmed) stPost($('#stCompleteBtn').data('url'), $('#stCountForm').serialize());
            });
        });

        /* ==========================================================
         |  GỬI NGẦM - mọi thao tác chỉ thay #stPanel, không tải lại trang
         ========================================================== */

        /**
         * Gửi một thao tác kiểm kê rồi thay lại đúng vùng #stPanel bằng HTML server trả về.
         *
         * Tải lại trang sẽ nhảy về tab đầu của màn hình Tồn Kho nên toàn bộ nút bấm của tab
         * này đi qua đây. Server trả JSON { status, message, html }.
         */
        function stPost(url, data) {
            var $panel = $('#stPanel');

            $panel.css('opacity', .55);

            $.ajax({
                url: url,
                type: 'POST',
                data: data,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                dataType: 'json'
            }).done(function (res) {
                stSwapPanel(res.html);

                if (res.status === 'success') {
                    Swal.fire({
                        title: 'Thành công!', text: res.message, icon: 'success',
                        timer: 1800, showConfirmButton: false
                    });
                } else if (res.message) {
                    Swal.fire({
                        title: 'Không thực hiện được', text: res.message, icon: 'error',
                        confirmButtonColor: '#2E7BC4', confirmButtonText: 'Đã hiểu'
                    });
                }
            }).fail(function (xhr) {
                var message = (xhr.responseJSON && xhr.responseJSON.message) ||
                    (xhr.status === 419 ? 'Phiên làm việc đã hết hạn, hãy tải lại trang rồi thao tác lại.' :
                        'Không gửi được yêu cầu lên máy chủ. Kiểm tra kết nối rồi thử lại.');

                Swal.fire({
                    title: 'Không thực hiện được', text: message, icon: 'error',
                    confirmButtonColor: '#2E7BC4', confirmButtonText: 'Đã hiểu'
                });
            }).always(function () {
                $panel.css('opacity', '');
            });
        }

        /** Thay nội dung tab, dựng lại DataTables của bảng lịch sử và bộ lọc đang bật. */
        function stSwapPanel(html) {
            if (!html) return;

            // Gỡ hẳn DataTables cũ trước khi bỏ bảng đi, tránh để lại phiên bản mồ côi
            if ($.fn.DataTable.isDataTable('#stHistoryTable')) {
                $('#stHistoryTable').DataTable().destroy();
            }

            $('#stPanel').html(html);

            // Hàm dùng chung của pages.materData.shared.assets - giữ đúng một cấu hình bảng
            if (window.mdInitTables) window.mdInitTables('#stPanel');

            stFilter = '';
            stApply();
        }

        // Mở phiếu / Huỷ phiếu: hỏi lại bằng SweetAlert2 rồi gửi ngầm, không submit thật
        $(document).on('submit', '.st-ajax-form', function (e) {
            e.preventDefault();

            var form = this;
            var $form = $(form);

            Swal.fire({
                title: $form.data('title'),
                text: $form.data('text'),
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: $form.data('danger') ? '#DC2626' : '#2E7BC4',
                cancelButtonColor: '#94A3B8',
                confirmButtonText: 'Đồng ý',
                cancelButtonText: 'Huỷ'
            }).then(function (result) {
                if (result.isConfirmed) stPost(form.action, $form.serialize());
            });
        });

        // Lưu số đếm - không cần hỏi lại, bấm là gửi luôn
        $(document).on('submit', '#stCountForm', function (e) {
            e.preventDefault();
            stPost(this.action, $(this).serialize());
        });

        /* ---------- Theo dõi chu kỳ ---------- */
        // Chọn một kỳ: có phiếu thì mở luôn chi tiết, chưa kiểm kê thì báo rõ kỳ bỏ sót
        $(document).on('change', '#stCycleSelect', function () {
            var $opt = $(this).find('option:selected');
            var id = $(this).val();

            if (!id) return;

            if ($opt.data('state') === 'missed' || $opt.data('state') === 'pending') {
                Swal.fire({
                    title: $opt.data('label') + ' chưa kiểm kê',
                    text: 'Kỳ ' + $opt.data('range') + ' không có phiếu kiểm kê nào.',
                    icon: 'info',
                    confirmButtonColor: '#2E7BC4',
                    confirmButtonText: 'Đã hiểu'
                });
                $(this).val('');
                return;
            }

            stOpenDetail(id);
            $(this).val('');
        });

        // Xem lại chi tiết một kỳ đã kiểm kê
        function stOpenDetail(id) {
            var $modal = $('#stocktakeDetailModal');
            var $tb = $modal.find('tbody').empty();

            $modal.find('.st-d-code, .st-d-period, .st-d-opened, .st-d-completed, .st-d-note').text('—');
            $tb.append('<tr><td colspan="8" class="text-center text-muted">Đang tải...</td></tr>');
            $modal.modal('show');

            $.getJSON($modal.data('url'), { stocktake_id: id })
                .done(function (d) {
                    $modal.find('.st-d-code').text(d.code || '—');
                    $modal.find('.st-d-period').text(d.period_label + ' (' + d.period_range + ') · ' + d.state_label);
                    $modal.find('.st-d-opened').text((d.opened_at || '—') + (d.opened_by ? ' — ' + d.opened_by : ''));
                    $modal.find('.st-d-completed').text(d.completed_at ? d.completed_at + ' — ' + (d.completed_by || '') : 'Chưa chốt');
                    $modal.find('.st-d-note').text(d.note || '—');

                    $tb.empty();
                    if (!d.items || !d.items.length) {
                        $tb.append('<tr><td colspan="8" class="text-center text-muted">Phiếu này không có dòng nào.</td></tr>');
                        return;
                    }

                    d.items.forEach(function (x, i) {
                        var $tr = $('<tr></tr>');
                        $tr.append($('<td class="text-center"></td>').text(i + 1));
                        $tr.append($('<td class="font-weight-bold"></td>').text(x.code));

                        var $mat = $('<td></td>').append($('<div class="font-weight-bold"></div>').text(x.material_name));
                        if (x.sub) $mat.append($('<div class="md-sub small text-muted"></div>').text(x.sub));
                        $tr.append($mat);

                        $tr.append($('<td class="md-sub"></td>').text(x.location_code));
                        $tr.append($('<td class="text-right"></td>').text(x.system_amount + ' ' + (x.unit || '')));
                        $tr.append($('<td class="text-right font-weight-bold"></td>').text(x.actual_amount));
                        $tr.append($('<td class="text-right"></td>').append(
                            $('<span></span>').addClass('st-diff ' + x.diff_state).text(x.diff_amount)
                        ));

                        var $note = $('<td class="md-sub"></td>').text(x.note || '—');
                        if (x.balancing_skipped && x.balancing_note) {
                            $note.append($('<div class="st-skip-note"></div>').text(x.balancing_note));
                        }
                        $tr.append($note);

                        $tb.append($tr);
                    });
                })
                .fail(function (xhr) {
                    $tb.empty().append(
                        $('<tr><td colspan="8" class="text-center text-danger"></td></tr>')
                            .find('td').text((xhr.responseJSON && xhr.responseJSON.message) || 'Không tải được chi tiết kỳ kiểm kê.').end()
                    );
                });
        }

        $(document).on('click', '.btn-st-detail', function () {
            stOpenDetail($(this).data('id'));
        });

        stApply();
    });
</script>
