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
| Vật tư "Cần Hiệu Chuẩn Trước Khi Dùng" ($g['calibration'] khác null) không cấp ngay:
|   - chưa gửi QA: cùng form chọn lô nhưng gửi tới materialCalibration.store (nút [Đề nghị HC],
|     submit thường, tải lại trang);
|   - đang chờ QA: chỉ xem lô đã gửi, nút Cấp Phát khoá;
|   - đã đạt: form khoá đúng mã xuất nhập + số đạt (item_plan do controller dựng), cấp xong tải lại.
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
    // Dòng đề nghị liên phòng ban / VPP: quyền cấp phát liên phòng ban (transferIssueStore)
    $prepCanTransfer = user_can('export_material_transfer');
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

    /* Vật tư cần hiệu chuẩn trước khi dùng */
    .prep-cal {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        margin-bottom: 6px;
        padding: 2px 9px;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 700;
    }

    .prep-cal.need { background: #FFFBEB; color: #B45309; }
    .prep-cal.pending { background: #FFF7ED; color: #C2410C; }
    .prep-cal.passed { background: #F0FDF4; color: #16A34A; }
    .prep-cal.failed { background: #FEF2F2; color: #DC2626; }
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
                <th style="width:150px" data-orderable="false">Định Khu</th>
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
                    // Mục đề nghị liên phòng ban / VPP phòng khác gửi tới: cấp ở modal chi tiết phiếu đó
                    $isTransfer = ($g['kind'] ?? 'internal') === 'transfer';
                    $transferModal = $isTransfer ? 'matTransferDetailModal_' . $g['transfer_request_id'] : null;
                    $transferHref = $isTransfer ? route($expRoute . 'list', ['tab' => $g['transfer_tab'], 'open' => $transferModal]) : null;

                    // Cần hiệu chuẩn trước khi dùng: null | request (gửi QA) | pending (chờ QA) | passed (cấp phần đạt)
                    $cal = $g['calibration'] ?? null;
                    $calState = $cal['state'] ?? null;
                    $calMode = null;
                    if ($cal) {
                        $calMode = ($calState && $calState['passed_total'] > 0.00005) ? 'passed'
                            : (($calState && $calState['pending'] > 0.00005) ? 'pending' : 'request');
                    }
                    $formNeeded = match ($calMode) {
                        'passed' => $d['left'],
                        'request' => $cal['to_request'],
                        default => $d['left'],
                    };
                @endphp
                <tr data-prep-short="{{ $isShort ? 1 : 0 }}" data-category-id="{{ $g['category_id'] }}"
                    data-left="{{ $g['needed'] }}" data-unit="{{ $dUnit }}" data-calib-held="{{ $cal['held'] ?? 0 }}">
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
                    <td data-order="{{ $isTransfer ? sprintf('9%09d-%010d', $d['request_list_id'], $d['item_id']) : sprintf('%010d-%010d', $d['request_list_id'], $d['item_id']) }}">
                        @if ($isTransfer)
                            <a href="{{ $transferHref }}" class="prep-req btn-prep-transfer" data-modal="{{ $transferModal }}"
                                title="Mở phiếu đề nghị liên phòng ban">{{ $d['request_code'] }}</a>
                            <div class="prep-topup"><span class="badge badge-info">Liên phòng ban</span></div>
                        @else
                        <a href="{{ route($expRoute . 'list', ['tab' => $d['request_type'], ($prepTabPrefix[$d['request_type']] ?? 'reqt_') . 'unissued' => 1]) }}"
                            class="prep-req" title="{{ $prepTypeLabel[$d['request_type']] ?? $d['request_type'] }} — mở tab đề nghị">{{ $d['request_code'] }}</a>
                        @endif
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
                        @if ($isTransfer)
                            <div class="font-weight-bold text-dark"><i class="fas fa-people-arrows mr-1 text-info"></i>{{ $dPurpose }}</div>
                            {{ $d['request_name'] ?: '' }}
                        @else
                        @if ($dObject !== '')
                            <div class="font-weight-bold text-dark"><i class="fas fa-crosshairs mr-1 text-primary"></i>{{ $dObject }}</div>
                        @endif
                        {{ $dPurpose !== '' ? $dPurpose : ($dObject !== '' ? '' : '—') }}
                        @endif
                    </td>
                    <td class="prep-issue-cell">
                        @if ($calMode === 'passed')
                            <div class="prep-cal passed" title="Chỉ cấp đúng mã xuất nhập và số lượng QA xác nhận đạt">
                                <i class="fas fa-check-circle"></i> Đạt hiệu chuẩn · {{ implode(', ', $calState['passed_codes']) }}
                            </div>
                        @elseif ($calMode === 'pending')
                            <div class="prep-cal pending"><i class="fas fa-hourglass-half"></i> Chờ QA hiệu chuẩn · {{ $calState['pending_code'] }}</div>
                        @elseif ($calMode === 'request')
                            @if ($calState && $calState['failed'])
                                <div class="prep-cal failed" title="{{ $calState['failed']['note'] }}">
                                    <i class="fas fa-times-circle"></i> {{ $calState['failed']['code'] }} không đạt - gửi lô khác
                                </div>
                            @else
                                <div class="prep-cal need"><i class="fas fa-ruler-combined"></i> Cần hiệu chuẩn trước khi dùng</div>
                            @endif
                        @endif

                        @if ($calMode === 'pending')
                            {{-- Đang ở QA: chỉ xem các lô đã gửi --}}
                            @foreach ($itemLines as $line)
                                <div class="prep-lot">
                                    <span class="prep-lot-code">{{ $line['import_code'] }}</span>
                                    <span>{{ $expNum($line['suggested_amount']) }} {{ $line['unit'] ?: $dUnit }}</span>
                                </div>
                            @endforeach
                        @elseif (!$g['category_id'])
                            <span class="text-danger md-sub">Vật tư ngoài danh mục nên kho không có mã xuất nhập nào để chọn.</span>
                        @elseif (!count($itemLines))
                            <span class="text-danger md-sub">Không còn lô nào còn hạn và còn hứa được</span>
                        @elseif ($isTransfer ? !$prepCanTransfer : !$prepCanIssue)
                            {{-- Không có quyền cấp phát: chỉ xem lô nên lấy --}}
                            @foreach ($itemLines as $line)
                                <div class="prep-lot">
                                    <span class="prep-lot-code">{{ $line['import_code'] }}</span>
                                    <span>{{ $expNum($line['suggested_amount']) }} {{ $line['unit'] ?: $dUnit }}</span>
                                </div>
                            @endforeach
                        @else
                            {{-- Liên phòng ban: cùng form, gửi tới transferIssueStore và chỉ một mã xuất nhập (data-single) --}}
                            @php
                                $formAction = $calMode === 'request'
                                    ? route('pages.export.materialCalibration.store')
                                    : route($expRoute . ($isTransfer ? 'transferIssueStore' : 'issueStore'));
                                $formNeedLabel = match ($calMode) {
                                    'passed' => $dPartial ? 'Cần cấp thêm' : 'Cần cấp',
                                    'request' => 'Cần gửi hiệu chuẩn',
                                    default => $dPartial ? 'Cần cấp thêm' : 'Đề nghị',
                                };
                            @endphp
                            <form action="{{ $formAction }}" method="POST"
                                id="prepIssueForm_{{ $loop->index }}"
                                class="me-issue-form" data-item-id="{{ $d['item_id'] }}"
                                data-single="{{ $isTransfer ? '1' : '0' }}"
                                data-category-id="{{ $g['category_id'] }}"
                                data-material="{{ $g['name'] }}"
                                data-needed="{{ $formNeeded }}" data-unit="{{ $dUnit }}"
                                data-need-label="{{ $formNeedLabel }}"
                                data-calibrate="{{ $calMode === 'request' ? '1' : '0' }}"
                                data-calib-state="{{ $calMode }}"
                                data-lot-locked="{{ $prepLotLocked || $calMode === 'passed' ? '1' : '0' }}"
                                {{-- Gửi hiệu chuẩn / cấp phần đạt: người cấp phát tự quyết số lượng (mặc định = số cần cấp) --}}
                                data-amount-locked="{{ $prepAmountLocked && !$calMode ? '1' : '0' }}"
                                data-plan="{{ json_encode($itemLines) }}">
                                @csrf
                                <input type="hidden" name="item_id" value="{{ $d['item_id'] }}">
                                <input type="hidden" name="return_tab" value="prepare">
                                <input type="hidden" name="prep_from" value="{{ $prepRange['from'] }}">
                                <input type="hidden" name="prep_to" value="{{ $prepRange['to'] }}">
                                <input type="hidden" name="issued_unit" value="{{ $dUnit }}">

                                <div class="me-issue-lines"></div>

                                {{-- Gửi bằng nút [Cấp Phát] / [Đề nghị HC] ở cột Thao Tác --}}
                                @unless ($prepLotLocked || ($prepAmountLocked && !$calMode) || $isTransfer || $calMode === 'passed')
                                    <div class="me-issue-foot">
                                        <button type="button" class="btn btn-xs btn-outline-primary me-issue-add">
                                            <i class="fas fa-plus mr-1"></i>Thêm mã xuất nhập
                                        </button>
                                    </div>
                                @endunless
                            </form>
                        @endif
                    </td>
                    {{-- Định khu của các mã xuất nhập đang chọn ở cột Cấp Phát - JS cập nhật khi đổi mã (prepUpdateLocation) --}}
                    <td class="prep-cell-location">
                        @foreach ($itemLines as $line)
                            <div>{{ \App\Support\ZoneType::tag($line['location_code'], $line['location_zone_type'] ?? null, 'Chưa xếp vị trí') }}</div>
                        @endforeach
                        @unless (count($itemLines))
                            <span class="text-muted">—</span>
                        @endunless
                    </td>
                    <td class="text-center">
                        <div class="md-actions">
                            @if ($isTransfer)
                                <a href="{{ $transferHref }}" class="btn btn-sm btn-primary btn-prep-transfer" data-modal="{{ $transferModal }}" title="Xem phiếu đề nghị liên phòng ban">
                                    <i class="fas fa-list-ul"></i>
                                </a>
                                @if ($prepCanTransfer)
                                    @if (count($itemLines))
                                        <button type="submit" form="prepIssueForm_{{ $loop->index }}"
                                            class="btn btn-sm btn-success btn-prep-issue" title="Cấp phát theo mã xuất nhập và số lượng ở cột Cấp Phát">
                                            <i class="fas fa-check mr-1"></i>Cấp Phát
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-sm btn-success" disabled title="Không còn lô nào còn hạn và còn hứa được">
                                            <i class="fas fa-check mr-1"></i>Cấp Phát
                                        </button>
                                    @endif
                                @endif
                            @else
                            @perm('export_material_issue')
                                @if ($calMode === 'pending')
                                    <button type="button" class="btn btn-sm btn-success" disabled title="Chờ QA xác nhận đạt hiệu chuẩn mới cấp phát được">
                                        <i class="fas fa-check mr-1"></i>Cấp Phát
                                    </button>
                                @elseif ($calMode === 'request' && count($itemLines))
                                    <button type="submit" form="prepIssueForm_{{ $loop->index }}"
                                        class="btn btn-sm btn-warning btn-prep-issue" title="Gửi QA hiệu chuẩn các mã xuất nhập và số lượng ở cột Cấp Phát - đạt mới cấp phát được">
                                        <i class="fas fa-ruler-combined mr-1"></i>Đề nghị HC
                                    </button>
                                @elseif ($g['category_id'] && count($itemLines))
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
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="11" class="text-center text-muted">{{ $prepPrintQuery ? 'Không có vật tư nào cần trong khoảng ngày đã chọn.' : 'Không còn vật tư nào chờ cấp phát.' }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Dòng liên phòng ban: phiếu đã có modal chi tiết trên trang thì mở luôn, chưa có thì đi tới
        // tab liên phòng ban kèm ?open= để tự bật modal (MaterialExport/list)
        $(document).on('click', '.btn-prep-transfer', function (e) {
            var $modal = $('#' + $(this).data('modal'));

            if ($modal.length) {
                e.preventDefault();
                $modal.modal('show');
            }
        });

        var table = $.fn.DataTable.isDataTable('#mePrepTable') ? $('#mePrepTable').DataTable() : null;

        if (!table) return;

        // Cột Định Khu: vị trí của từng mã xuất nhập đang chọn trong form cột Cấp Phát
        function prepUpdateLocation($form) {
            var $tr = $form.closest('tr');
            var lots = (window.meIssueLots || {})[String($form.data('category-id'))] || [];
            var html = [];

            $form.find('.me-issue-lot').each(function () {
                var id = String($(this).val() || '');
                var lot = id ? lots.filter(function (l) { return String(l.id) === id; })[0] : null;

                if (lot) {
                    html.push('<div>' + window.wmsZoneTag(lot.location, lot.zone, 'Chưa xếp vị trí') + '</div>');
                }
            });

            $tr.find('.prep-cell-location').html(html.length ? html.join('') : '<span class="text-muted">—</span>');
        }

        // syncForm (issueLotPickerModal) phát 'me:synced' mỗi khi dòng lô đổi: chọn mã, bảng chọn lô, dựng lại
        $(document).on('me:synced', '#mePrepTable .me-issue-form', function () {
            prepUpdateLocation($(this));
        });

        // Lần đầu: gồm cả hàng ở trang khác của DataTables (đã tách khỏi DOM, sự kiện không nổi lên document)
        table.rows().nodes().to$().find('.me-issue-form').each(function () {
            prepUpdateLocation($(this));
        });

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

        // Một lô (đề nghị liên phòng ban) - cùng planFrom(..., false): lô MỘT MÌNH đủ, không có thì lô tốt nhất còn hàng
        function planSingle(categoryId, needed) {
            var lots = lotsOf(categoryId).filter(function (lot) { return lot.selectable; });
            var lot = lots.filter(function (l) { return l.available + EPS >= needed; })[0] || lots[0];

            return lot ? [{ import_id: lot.id, suggested_amount: round4(Math.min(needed, lot.available)) }] : [];
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
                // Phần đã gửi QA / đạt hiệu chuẩn đang giữ chỗ riêng, không chia từ tồn khả dụng
                var left = Math.max(round4((parseFloat($tr.attr('data-left')) || 0) - (parseFloat($tr.attr('data-calib-held')) || 0)), 0);
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

                if ($form.length && $form.data('calib-state') !== 'passed') {
                    var need = parseFloat($form.data('needed')) || left;
                    var plan = String($form.data('single')) === '1' ? planSingle(categoryId, need) : planFor(categoryId, need);

                    $tr.find('.prep-no-lot').remove();

                    if (plan.length) {
                        window.meIssueRebuild($form, plan);
                        prepUpdateLocation($form);
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
            var $form = $(this);

            // Đề nghị hiệu chuẩn: gửi thường, máy chủ quay lại tab này kèm thông báo
            if (String($form.data('calibrate')) === '1') return;

            e.preventDefault();

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

                // Cấp phần đạt hiệu chuẩn: trạng thái hiệu chuẩn của dòng đổi theo, tải lại cho đúng
                if ($form.data('calib-state') === 'passed') {
                    Swal.fire({ title: 'Thành công!', text: res.message, icon: 'success', timer: 1500, showConfirmButton: false })
                        .then(function () { window.location.reload(); });

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
