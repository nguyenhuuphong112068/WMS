{{--
| SỬ DỤNG VẬT TƯ - TAB "CẤP PHÁT VẬT TƯ"
|
| Mọi dòng đề nghị đã XONG QUY TRÌNH KÝ DUYỆT mà kho chưa cấp đủ, MỖI DÒNG ĐỀ NGHỊ MỘT HÀNG
| (cùng vật tư nhưng khác phiếu không gộp), xếp theo số phiếu đề nghị. Cấp ngay tại bảng:
|   - cột "Cấp Phát": các dòng lô + số lượng của form cấp phát ở phiếu chi tiết (JS dựng
|     dòng ở issueLotPickerModal) - đổi được lô / số lượng, cấp một phần;
|   - nút [Cấp Phát] ở Thao Tác gửi form của hàng đó (thuộc tính form="...") lên issueStore
|     bằng AJAX - không tải lại trang nên giữ nguyên ô tìm kiếm / trang / thứ tự của bảng.
|     Cấp xong JS tự trừ tồn các lô (window.meIssueLots) rồi tính lại mọi hàng cùng vật tư.
| Tình trạng Đủ / Thiếu chia tồn lần lượt theo số phiếu - xem preparationData().
|
| Bộ lọc "Ngày mong muốn" (prep_from / prep_to) chạy ở server; nút In phiếu soạn hàng in
| đúng khoảng đang lọc.
|
| Dữ liệu vào: $prepGroups, $prepShortageCount, $prepRange - xem
| MaterialExportController::preparationData().
--}}

@php
    $prepToday = now()->startOfDay();

    // Nhãn ngắn của 3 loại đề nghị - đúng tên 3 tab đề nghị bên cạnh
    $prepTypeLabel = ['periodic' => 'Định kỳ', 'risk_assessment' => 'ĐG rủi ro', 'regular' => 'Thường quy'];

    // Tiền tố bộ lọc của 3 tab đề nghị - bấm mã phiếu mở đúng tab, lọc phiếu chờ cấp phát
    $prepTabPrefix = ['periodic' => 'reqp_', 'risk_assessment' => 'reqk_', 'regular' => 'reqt_'];

    // Chọn nhanh theo ngày mong muốn: nhìn về phía trước, khác bộ nút mặc định (30 ngày, tháng này...)
    $prepQuick = [
        ['label' => 'Quá hạn', 'from' => '', 'to' => now()->subDay()->format('Y-m-d')],
        ['label' => 'Hôm nay', 'from' => now()->format('Y-m-d'), 'to' => now()->format('Y-m-d')],
        ['label' => 'Ngày mai', 'from' => now()->addDay()->format('Y-m-d'), 'to' => now()->addDay()->format('Y-m-d')],
        ['label' => '7 ngày tới', 'from' => now()->format('Y-m-d'), 'to' => now()->addDays(6)->format('Y-m-d')],
        ['label' => 'Tháng này', 'from' => now()->startOfMonth()->format('Y-m-d'), 'to' => now()->endOfMonth()->format('Y-m-d')],
        ['label' => 'Tất cả', 'from' => '', 'to' => ''],
    ];

    // In phiếu soạn hàng theo đúng khoảng đang lọc
    $prepPrintQuery = array_filter(['prep_from' => $prepRange['from'], 'prep_to' => $prepRange['to']]);

    $prepCanIssue = user_can('export_material_issue');
    // Không có quyền điều chỉnh: khoá lô theo đề xuất, chỉ sửa được số lượng (issueStore kiểm lại)
    $prepLotLocked = !user_can(\App\Http\Controllers\Pages\Export\MaterialExportController::LOT_ADJUST_PERMISSION);
    // Không có quyền điều chỉnh số lượng: ô số lượng khoá đúng số đề xuất (issueStore kiểm lại)
    $prepAmountLocked = !user_can(\App\Http\Controllers\Pages\Export\MaterialExportController::AMOUNT_ADJUST_PERMISSION);
@endphp

<style>
    /* ---------- Cấp phát vật tư ---------- */
    .prep-lot {
        display: flex;
        align-items: baseline;
        gap: 6px;
        font-size: 0.78rem;
        line-height: 1.6;
    }

    .prep-lot+.prep-lot {
        border-top: 1px dashed var(--primary-lighter);
    }

    .prep-lot .prep-lot-code {
        font-weight: 700;
        color: var(--primary-dark);
        letter-spacing: 0.3px;
    }

    .prep-loc {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 1px 7px;
        border-radius: 999px;
        background: var(--primary-soft);
        color: var(--primary-dark);
        font-weight: 600;
        font-size: 0.72rem;
    }

    .prep-req {
        display: inline-block;
        margin: 0 4px 4px 0;
        padding: 1px 8px;
        border: 1px solid var(--primary-lighter);
        border-radius: 999px;
        background: #fff;
        color: var(--primary-dark);
        font-size: 0.72rem;
        font-weight: 600;
    }

    a.prep-req {
        transition: background 0.2s ease, transform 0.2s ease;
    }

    a.prep-req:hover {
        background: var(--primary-soft);
        text-decoration: none;
        transform: translateY(-1px);
    }

    /* Cột Cấp Phát: form cấp phát của dòng đề nghị */
    #mePrepTable .prep-issue-cell {
        min-width: 440px;
    }

    .prep-need {
        font-size: 1rem;
        font-weight: 700;
        color: var(--primary-dark);
    }

    .prep-due-late {
        color: #B91C1C;
        font-weight: 700;
    }
</style>

@include('pages.shared.rangeFilter', [
    'rfRoute' => $expRoute . 'list',
    'rfTab' => 'prepare',
    'rfPrefix' => 'prep_',
    'rfInBar' => true,
    'rfRange' => $prepRange,
    'rfSearch' => false,
    'rfShowPerPage' => false,
    'rfDateLabel' => 'Ngày mong muốn',
    'rfQuick' => $prepQuick,
])

<div class="md-toolbar">
    <a href="{{ route($expRoute . 'prepPrint', $prepPrintQuery) }}" target="_blank" class="btn btn-primary">
        <i class="fas fa-print mr-1"></i> In phiếu soạn hàng
    </a>

    @if ($prepShortageCount > 0)
        <button type="button" class="btn btn-outline-warning text-dark btn-prep-short"
            style="border-color: #d97706; background-color: #fffbeb;"
            title="Chỉ hiện vật tư kho không đủ hàng để cấp">
            <i class="fas fa-exclamation-triangle mr-1 text-warning"></i> Thiếu hàng
            <span class="badge badge-warning ml-1" style="font-size: 0.8rem;">{{ $prepShortageCount }}</span>
        </button>
    @endif
</div>

<div class="table-responsive">
    {{-- data-no-responsive: dòng con của DataTables Responsive chép HTML ô, form cấp phát chép sang sẽ hỏng --}}
    <table id="mePrepTable" class="table table-bordered table-hover w-100 md-table" data-no-responsive>
        <thead>
            <tr>
                <th class="text-center" style="width:45px">STT</th>
                <th>Vật Tư</th>
                <th style="width:130px">Mã Phiếu Đề Nghị</th>
                <th class="text-right" style="width:110px">Cần Soạn</th>
                <th class="text-right" style="width:110px">Tồn Khả Dụng</th>
                <th class="text-center" style="width:130px">Tình Trạng</th>
                <th class="text-center" style="width:110px">Ngày Mong Muốn</th>
                <th style="width:200px">Đối Tượng / Mục Đích</th>
                <th class="prep-issue-cell" data-orderable="false">Cấp Phát</th>
                <th class="text-center" style="width:130px">Thao Tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($prepGroups as $g)
                @php
                    // Mỗi hàng là đúng một dòng đề nghị
                    $d = $g['demands'][0];
                    $dUnit = $d['unit'] ?: $g['unit'];
                    $dPartial = (float) $d['issued'] > 0.00005;
                    $dObject = $d['object_id'] ? collect([$d['object_code'], $d['object_name']])->filter()->implode(' - ') : '';
                    $dPurpose = trim((string) $d['purpose']);
                    $itemLines = $g['item_plan']['lines'];

                    $shortage = (float) $g['shortage'];
                    $isShort = $shortage > 0.00005;
                    $due = $g['needed_date'] ? \Carbon\Carbon::parse($g['needed_date'])->startOfDay() : null;
                    $isLate = $due && $due->lt($prepToday);
                @endphp
                <tr data-prep-short="{{ $isShort ? 1 : 0 }}" data-category-id="{{ $g['category_id'] }}"
                    data-left="{{ $g['needed'] }}" data-unit="{{ $dUnit }}">
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>
                        <div class="font-weight-bold">{{ $g['name'] ?: '—' }}</div>
                        @if ($g['category_code'])
                            <div class="md-sub"><span class="md-tag">{{ $g['category_code'] }}</span></div>
                        @endif
                        @if ($g['specification'])
                            <div class="md-sub small text-muted">{{ $g['specification'] }}</div>
                        @endif
                        @unless ($g['category_id'])
                            <span class="badge badge-secondary">Ngoài danh mục</span>
                        @endunless
                    </td>
                    {{-- Sắp theo thứ tự lập phiếu: mã <phòng><ddmmyy>_<stt> sắp theo chuỗi sẽ sai khi qua tháng --}}
                    <td data-order="{{ sprintf('%010d-%010d', $d['request_list_id'], $d['item_id']) }}">
                        <a href="{{ route($expRoute . 'list', ['tab' => $d['request_type'], ($prepTabPrefix[$d['request_type']] ?? 'reqt_') . 'unissued' => 1]) }}"
                            class="prep-req" title="{{ $prepTypeLabel[$d['request_type']] ?? $d['request_type'] }} — mở tab đề nghị">{{ $d['request_code'] }}</a>
                        @if ($dPartial)
                            <div class="prep-topup"><span class="badge badge-warning">Cấp tiếp</span></div>
                        @endif
                    </td>
                    <td class="text-right prep-cell-need" data-order="{{ $g['needed'] }}">
                        <span class="prep-need">{{ $expNum($g['needed']) }}</span>
                        <span class="md-sub">{{ $dUnit }}</span>
                    </td>
                    <td class="text-right prep-cell-available" data-order="{{ $g['available'] }}">
                        <span class="prep-available">{{ $expNum($g['available']) }}</span> <span class="md-sub">{{ $g['unit'] }}</span>
                    </td>
                    <td class="text-center prep-cell-status">
                        @if ($isShort)
                            <span class="badge badge-danger" title="Tồn chia lần lượt theo số phiếu, phiếu trước giữ tồn trước">Thiếu {{ $expNum($shortage) }} {{ $dUnit }}</span>
                        @else
                            <span class="badge badge-success">Đủ hàng</span>
                        @endif
                    </td>
                    <td class="text-center md-sub" data-order="{{ $g['needed_date'] ?: '9999-12-31' }}">
                        @if ($due)
                            <span class="{{ $isLate ? 'prep-due-late' : '' }}">{{ $due->format('d/m/Y') }}</span>
                        @else
                            —
                        @endif
                    </td>
                    <td class="md-sub">
                        @if ($dObject !== '')
                            <div class="font-weight-bold text-dark"><i class="fas fa-crosshairs mr-1 text-primary"></i>{{ $dObject }}</div>
                        @endif
                        {{ $dPurpose !== '' ? $dPurpose : ($dObject !== '' ? '' : '—') }}
                    </td>
                    <td class="prep-issue-cell">
                        @if (!$g['category_id'])
                            <span class="text-danger md-sub">Vật tư ngoài danh mục nên kho không có mã xuất nhập nào để chọn.</span>
                        @elseif (!count($itemLines))
                            <span class="text-danger md-sub">Không còn lô nào còn hạn và còn hứa được</span>
                        @elseif (!$prepCanIssue)
                            {{-- Không có quyền cấp phát: chỉ xem lô nên lấy --}}
                            @foreach ($itemLines as $line)
                                <div class="prep-lot">
                                    <span class="prep-lot-code">{{ $line['import_code'] }}</span>
                                    <span class="prep-loc"><i class="fas fa-map-marker-alt"></i>{{ $line['location_code'] ?: 'Chưa xếp vị trí' }}</span>
                                    <span>{{ $expNum($line['suggested_amount']) }} {{ $line['unit'] ?: $dUnit }}</span>
                                </div>
                            @endforeach
                        @else
                            <form action="{{ route($expRoute . 'issueStore') }}" method="POST"
                                id="prepIssueForm_{{ $loop->index }}"
                                class="me-issue-form" data-item-id="{{ $d['item_id'] }}"
                                data-category-id="{{ $g['category_id'] }}"
                                data-material="{{ $g['name'] }}"
                                data-needed="{{ $d['left'] }}" data-unit="{{ $dUnit }}"
                                data-need-label="{{ $dPartial ? 'Cần cấp thêm' : 'Đề nghị' }}"
                                data-lot-locked="{{ $prepLotLocked ? '1' : '0' }}"
                                data-amount-locked="{{ $prepAmountLocked ? '1' : '0' }}"
                                data-plan="{{ json_encode($itemLines) }}">
                                @csrf
                                <input type="hidden" name="item_id" value="{{ $d['item_id'] }}">
                                <input type="hidden" name="return_tab" value="prepare">
                                <input type="hidden" name="prep_from" value="{{ $prepRange['from'] }}">
                                <input type="hidden" name="prep_to" value="{{ $prepRange['to'] }}">
                                <input type="hidden" name="issued_unit" value="{{ $dUnit }}">

                                <div class="me-issue-lines"></div>

                                {{-- Gửi bằng nút [Cấp Phát] ở cột Thao Tác --}}
                                @unless ($prepLotLocked || $prepAmountLocked)
                                    <div class="me-issue-foot">
                                        <button type="button" class="btn btn-xs btn-outline-primary me-issue-add">
                                            <i class="fas fa-plus mr-1"></i>Thêm mã xuất nhập
                                        </button>
                                    </div>
                                @endunless
                            </form>
                        @endif
                    </td>
                    <td class="text-center">
                        <div class="md-actions">
                            <button type="button" class="btn btn-sm btn-primary" data-toggle="modal"
                                data-target="#prepDetailModal_{{ $loop->index }}" title="Chi tiết soạn hàng">
                                <i class="fas fa-list-ul"></i>
                            </button>
                            @perm('export_material_issue')
                                @if ($g['category_id'] && count($itemLines))
                                    <button type="submit" form="prepIssueForm_{{ $loop->index }}"
                                        class="btn btn-sm btn-success btn-prep-issue" title="Cấp phát theo mã xuất nhập và số lượng ở cột Cấp Phát">
                                        <i class="fas fa-check mr-1"></i>Cấp Phát
                                    </button>
                                @else
                                    <button type="button" class="btn btn-sm btn-success" disabled
                                        title="{{ $g['category_id'] ? 'Không còn lô nào còn hạn và còn hứa được' : 'Vật tư ngoài danh mục, kho không có mã xuất nhập để cấp' }}">
                                        <i class="fas fa-check mr-1"></i>Cấp Phát
                                    </button>
                                @endif
                            @endperm
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="10" class="text-center text-muted">{{ $prepPrintQuery ? 'Không có vật tư nào cần trong khoảng ngày đã chọn.' : 'Không còn vật tư nào chờ cấp phát.' }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var table = $.fn.DataTable.isDataTable('#mePrepTable') ? $('#mePrepTable').DataTable() : null;

        if (!table) return;

        // Theo số phiếu đề nghị (DataTables mặc định sắp theo cột 1 - tên vật tư)
        table.order([2, 'asc']).draw();

        /* ---------- Cấp phát chạy ngầm (AJAX) ---------- */

        var EPS = 0.00005;
        var NO_LOT = 'Không còn lô nào còn hạn và còn hứa được';
        var BTN_HTML = '<i class="fas fa-check mr-1"></i>Cấp Phát';

        function round4(value) {
            return Math.round((parseFloat(value) || 0) * 10000) / 10000;
        }

        // Cùng cách hiển thị số với $expNum: phân cách hàng nghìn bằng dấu phẩy, bỏ số 0 thừa
        function fmt(value) {
            var parts = String(round4(value)).split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');

            return parts.join('.');
        }

        function esc(value) {
            return $('<div>').text(value == null ? '' : value).html();
        }

        function lotsOf(categoryId) {
            return (window.meIssueLots || {})[String(categoryId)] || [];
        }

        // Kế hoạch lô cho một nhu cầu - cùng phép chia MaterialPicking::planFrom() ở server
        function planFor(categoryId, needed) {
            var lines = [];
            var need = needed;

            lotsOf(categoryId).forEach(function (lot) {
                if (need <= EPS || !lot.selectable) return;

                var take = Math.min(need, lot.available);

                lines.push({ import_id: lot.id, suggested_amount: round4(take) });
                need -= take;
            });

            return lines;
        }

        // Trừ tồn các lô vừa cấp, rồi xếp lại lô còn cấp được + thứ tự ưu tiên
        function deductLots(categoryId, issued) {
            var lots = lotsOf(categoryId);
            var rank = 0;

            (issued || []).forEach(function (line) {
                lots.forEach(function (lot) {
                    if (String(lot.id) !== String(line.import_id)) return;

                    lot.available = Math.max(round4(lot.available - line.amount), 0);
                    lot.remaining = Math.max(round4(lot.remaining - line.amount), 0);
                });
            });

            lots.forEach(function (lot) {
                lot.selectable = lot.level !== 'expired' && lot.available > EPS;
                lot.rank = lot.selectable ? ++rank : 0;
            });
        }

        /*
        | Tính lại mọi hàng của một vật tư theo tồn mới - giống preparationData(): Tồn khả dụng,
        | Tình trạng chia tồn lần lượt theo số phiếu (thứ tự hàng lúc tải trang), và dựng lại
        | lô gợi ý của từng form theo kế hoạch riêng của hàng đó.
        */
        function refreshCategory(categoryId) {
            var total = 0;

            lotsOf(categoryId).forEach(function (lot) {
                if (lot.selectable) total += lot.available;
            });

            var pool = total;

            table.rows({ order: 'index' }).nodes().to$().filter('[data-category-id="' + categoryId + '"]').each(function () {
                var $tr = $(this);
                var left = parseFloat($tr.attr('data-left')) || 0;
                var unit = $tr.attr('data-unit') || '';
                var short = Math.max(round4(left - pool), 0);

                pool = Math.max(pool - left, 0);

                $tr.attr('data-prep-short', short > EPS ? '1' : '0');
                $tr.find('.prep-cell-available').attr('data-order', round4(total)).find('.prep-available').text(fmt(total));
                $tr.find('.prep-cell-status').html(short > EPS
                    ? '<span class="badge badge-danger" title="Tồn chia lần lượt theo số phiếu, phiếu trước giữ tồn trước">Thiếu ' + fmt(short) + ' ' + esc(unit) + '</span>'
                    : '<span class="badge badge-success">Đủ hàng</span>');

                var $form = $tr.find('.me-issue-form');
                var $btn = $tr.find('.btn-prep-issue');

                if ($form.length) {
                    var plan = planFor(categoryId, left);

                    $tr.find('.prep-no-lot').remove();

                    if (plan.length) {
                        window.meIssueRebuild($form, plan);
                        $form.show();
                        $btn.prop('disabled', false).attr('title', 'Cấp phát theo mã xuất nhập và số lượng ở cột Cấp Phát');
                    } else {
                        $form.hide().after('<span class="text-danger md-sub prep-no-lot">' + NO_LOT + '</span>');
                        $btn.prop('disabled', true).attr('title', NO_LOT);
                    }
                }

                table.row(this).invalidate();
            });
        }

        // Số trên tab + nút "Thiếu hàng" theo các hàng còn lại
        function refreshCounts() {
            var rows = table.rows().nodes().to$();
            var $tabCount = $('.exp-tab[data-pane="mePanePrepare"] .exp-tab-count');

            if (rows.length) {
                $tabCount.text(rows.length);
            } else {
                $tabCount.remove();
            }

            $('.btn-prep-short .badge').text(rows.filter('[data-prep-short="1"]').length);
        }

        function showError(message) {
            Swal.fire({
                title: 'Không thực hiện được',
                text: message,
                icon: 'error',
                confirmButtonColor: '#2E7BC4',
                confirmButtonText: 'Đã hiểu'
            });
        }

        /*
        | Bắt submit của form trong bảng (bấm [Cấp Phát] ở Thao Tác, hoặc Enter trong ô số lượng)
        | để gửi ngầm thay vì tải lại trang. Chưa nhập số lượng thì để handler của
        | issueLotPickerModal báo như ở phiếu chi tiết.
        */
        $(document).on('submit', '#mePrepTable .me-issue-form', function (e) {
            e.preventDefault();

            var $form = $(this);
            var $tr = $form.closest('tr');
            var $btn = $tr.find('.btn-prep-issue');
            var total = 0;

            $form.find('.me-issue-amount').each(function () {
                total += parseFloat($(this).val()) || 0;
            });

            if (total <= EPS || $form.data('busy')) return;

            $form.data('busy', true);
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Đang cấp phát...');

            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: $form.serialize(),
                dataType: 'json'
            }).done(function (res) {
                if (!res || !res.success) {
                    $btn.prop('disabled', false).html(BTN_HTML);
                    showError((res && res.message) || 'Chưa cấp phát được, vui lòng tải lại trang rồi thử lại.');

                    return;
                }

                var categoryId = $tr.attr('data-category-id');
                var short = round4(res.data.short_amount);

                deductLots(categoryId, res.data.lots);

                if (short > EPS) {
                    // Cấp một phần: hàng ở lại với phần còn thiếu, lần sau là "cấp thêm"
                    $tr.attr('data-left', short);
                    $tr.find('.prep-cell-need').attr('data-order', short).find('.prep-need').text(fmt(short));
                    $form.data('needed', short).data('need-label', 'Cần cấp thêm');

                    if (!$tr.find('.prep-topup').length) {
                        $tr.find('.prep-req').after('<div class="prep-topup"><span class="badge badge-warning">Cấp tiếp</span></div>');
                    }

                    $btn.html(BTN_HTML);
                } else {
                    table.row($tr).remove();
                }

                refreshCategory(categoryId);
                refreshCounts();
                // draw(false): giữ nguyên trang, ô tìm kiếm và thứ tự đang xem
                table.draw(false);

                Swal.fire({
                    title: 'Thành công!',
                    text: res.message,
                    icon: 'success',
                    timer: 1800,
                    showConfirmButton: false
                });
            }).fail(function (xhr) {
                $btn.prop('disabled', false).html(BTN_HTML);
                showError(xhr.status === 419
                    ? 'Phiên làm việc đã hết hạn, vui lòng tải lại trang.'
                    : ((xhr.responseJSON && xhr.responseJSON.message) || 'Chưa cấp phát được (có thể phiên đăng nhập đã hết hạn), vui lòng tải lại trang.'));
            }).always(function () {
                $form.data('busy', false);
            });
        });

        // Lọc nhanh các vật tư kho không đủ hàng - chỉ áp cho đúng bảng này
        var onlyShort = false;

        $.fn.dataTable.ext.search.push(function (settings, data, index) {
            if (settings.nTable.id !== 'mePrepTable' || !onlyShort) return true;

            return $(settings.aoData[index].nTr).attr('data-prep-short') === '1';
        });

        $(document).on('click', '.btn-prep-short', function () {
            onlyShort = !onlyShort;
            $(this).toggleClass('btn-warning', onlyShort).toggleClass('btn-outline-warning', !onlyShort);
            table.draw();
        });
    });
</script>
