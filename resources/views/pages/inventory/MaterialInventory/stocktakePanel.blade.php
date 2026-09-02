{{--
|--------------------------------------------------------------------------
| NỘI DUNG TAB KIỂM KÊ ĐỊNH KỲ (phần được nạp lại bằng AJAX)
|--------------------------------------------------------------------------
| Tách khỏi stocktake.blade.php để mỗi thao tác (mở phiếu / lưu số đếm / chốt /
| huỷ) chỉ thay đúng vùng này, không tải lại trang - tải lại là nhảy về tab đầu.
|
| MaterialStocktakeController::respond() render riêng file này rồi trả về trong
| JSON, nên phải TỰ ĐỦ: mọi biến dùng ở đây đều dựng ngay trong khối PHP bên dưới,
| chỉ nhận từ ngoài đúng một biến $stocktake.
|
| Phần <style> và toàn bộ JS nằm ở stocktake.blade.php (chỉ nạp một lần), mọi
| handler ở đó đều gắn theo kiểu uỷ quyền nên vẫn chạy sau khi vùng này bị thay.
|
| LƯU Ý: không viết tên directive của Blade (dấu @ + php) trong khối chú thích này -
| Blade tách khối PHP thô TRƯỚC khi bỏ chú thích, gặp chữ đó nó cắt nhầm và nuốt
| luôn khối khai báo biến bên dưới.
--}}

@php
    $stTake = $stocktake['current'];
    $stItems = $stocktake['items'];
    $stProgress = $stocktake['progress'];
    $stCounting = $stTake && $stTake->state === 'counting';
    $stBag = $errors->getBag('stocktakeErrors');

    $stCycleLabels = ['completed' => 'Đã chốt', 'counting' => 'Đang đếm', 'pending' => 'Chưa mở', 'missed' => 'Bỏ sót'];

    // Dựng tại chỗ để render riêng lẻ vẫn có, không phụ thuộc khối @php của list.blade.php
    $invNum = fn($value) => rtrim(rtrim(number_format((float) $value, 4, '.', ','), '0'), '.');
    $invDate = fn($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';
@endphp

{{-- ================= THANH THÔNG TIN KỲ ================= --}}
<div class="st-bar">
    <span class="st-bar-title"><i class="fas fa-clipboard-check mr-1"></i>KỲ KIỂM KÊ {{ mb_strtoupper($stocktake['periodLabel']) }}</span>
    <span class="md-sub">{{ $stocktake['periodRange'] }}</span>

    @if ($stTake)
        <span class="st-state {{ $stTake->state }}">{{ $stocktake['states'][$stTake->state] ?? $stTake->state }}</span>
        <span class="md-tag">{{ $stTake->code }}</span>
        <span class="md-sub">
            Mở {{ \Carbon\Carbon::parse($stTake->opened_at)->format('d/m/Y H:i') }} — {{ $stTake->opened_by }}
            @if ($stTake->completed_at)
                · Chốt {{ \Carbon\Carbon::parse($stTake->completed_at)->format('d/m/Y H:i') }} — {{ $stTake->completed_by }}
            @endif
        </span>
    @else
        <span class="st-state pending">Chưa mở phiếu</span>
    @endif

    <div class="st-bar-right">
        {{-- Theo dõi chu kỳ: chọn một kỳ để xem lại, kỳ chưa kiểm kê thì báo bỏ sót --}}
        <div class="st-cycle-pick">
            <label class="mb-0 md-sub"><i class="fas fa-history mr-1"></i>Chu kỳ:</label>
            <select id="stCycleSelect" class="form-control form-control-sm">
                <option value="">{{ $stocktake['doneCount'] }}/{{ $stocktake['periodCount'] }} kỳ gần nhất đã kiểm kê@if ($stocktake['missedCount'] > 0), {{ $stocktake['missedCount'] }} kỳ bỏ sót @endif</option>
                @foreach ($stocktake['periods'] as $p)
                    <option value="{{ $p['id'] }}" data-label="{{ $p['label'] }}" data-range="{{ $p['range'] }}" data-state="{{ $p['state'] }}">
                        {{ $p['label'] }} · {{ $stCycleLabels[$p['state']] ?? $p['state'] }}@if ($p['id']) · {{ $p['counted'] }}/{{ $p['total'] }} dòng @endif
                    </option>
                @endforeach
            </select>
        </div>

        @if ($stocktake['canOpen'] && user_can('inventory_material_stocktake'))
            <form action="{{ route('pages.inventory.materialStocktake.open') }}" method="POST" class="st-ajax-form"
                data-title="Mở phiếu kiểm kê {{ $stocktake['periodLabel'] }}?"
                data-text="Hệ thống sẽ chốt danh sách các mã xuất nhập đang có số dư và ghi lại tồn sổ sách để đối chiếu.">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-folder-plus mr-1"></i> Mở phiếu {{ $stocktake['periodLabel'] }}
                </button>
            </form>
        @elseif ($stCounting && user_can('inventory_material_stocktake'))
            <form action="{{ route('pages.inventory.materialStocktake.deActive') }}" method="POST" class="st-ajax-form" data-danger="1"
                data-title="Huỷ phiếu kiểm kê {{ $stTake->code }}?"
                data-text="Toàn bộ số đã đếm của phiếu này sẽ không còn hiệu lực. Huỷ xong vẫn mở lại được phiếu mới cho {{ $stocktake['periodLabel'] }}.">
                @csrf
                <input type="hidden" name="stocktake_id" value="{{ $stTake->id }}">
                <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fas fa-ban mr-1"></i> Huỷ phiếu</button>
            </form>
        @endif
    </div>
</div>

@if ($stBag->any())
    <div class="alert alert-danger py-2 mb-2" style="border-radius: var(--border-radius-md); font-size: .84rem;">
        <i class="fas fa-triangle-exclamation mr-1"></i>
        @foreach ($stBag->all() as $message)
            <div>{{ $message }}</div>
        @endforeach
    </div>
@endif

{{-- ================= PHIẾU CỦA KỲ HIỆN TẠI ================= --}}
@if ($stTake)
    <div class="st-metrics">
        <span class="st-metric"><b>{{ $stProgress['total'] }}</b> Mã cần đếm</span>
        <span class="st-metric"><b>{{ $stProgress['counted'] }}</b> Đã đếm</span>
        <span class="st-metric {{ $stProgress['waiting'] > 0 ? 'warn' : '' }}"><b>{{ $stProgress['waiting'] }}</b> Chưa đếm</span>
        <span class="st-metric {{ $stProgress['diff'] > 0 ? 'warn' : '' }}"><b>{{ $stProgress['diff'] }}</b> Lệch sổ</span>
        @if ($stTake->state === 'completed')
            <span class="st-metric {{ $stProgress['skipped'] > 0 ? 'danger' : '' }}"><b>{{ $stProgress['skipped'] }}</b> Chờ xử lý</span>
        @endif
        <span class="st-progress"><div style="width: {{ $stProgress['percent'] }}%"></div></span>
        <span class="md-sub">{{ $stProgress['percent'] }}%</span>
    </div>

    {{-- Quét QR trên nhãn lô để nhảy thẳng tới dòng cần đếm - xem pages.shared.cameraScan --}}
    <div class="st-toolbar">
        <div class="st-scan scan-box">
            <i class="fas fa-qrcode text-primary"></i>
            <input type="text" id="stSearch" class="form-control form-control-sm" autocomplete="off" spellcheck="false"
                placeholder="Quét QR / gõ mã xuất nhập, vật tư, vị trí..."
                title="Gõ để lọc bảng. Quét mã QR trên nhãn lô (hoặc gõ mã rồi Enter) để nhảy thẳng tới dòng cần đếm.">
            <button type="button" class="btn btn-outline-primary btn-sm btn-camera-scan" title="Quét mã QR bằng camera">
                <i class="fas fa-camera"></i>
            </button>
            <button type="button" class="btn btn-primary btn-sm btn-st-find" title="Tìm dòng cần đếm">
                <i class="fas fa-search"></i>
            </button>
        </div>

        <span class="st-chip is-active" data-st-filter="">Tất cả</span>
        <span class="st-chip" data-st-filter="waiting">Chưa đếm</span>
        <span class="st-chip" data-st-filter="match">Khớp sổ</span>
        <span class="st-chip" data-st-filter="over">Thừa</span>
        <span class="st-chip" data-st-filter="short">Thiếu</span>

        <details class="st-note ml-auto">
            <summary><i class="fas fa-circle-info"></i> Quy tắc kiểm kê</summary>
            <div>
                Kho vật tư kiểm kê <b>{{ $stocktake['cycleMonths'] }} tháng 1 lần</b> theo quý, mỗi quý một phiếu.
                Tồn sổ sách được chốt tại thời điểm mở phiếu, số đếm nhập theo đơn vị của phòng, tối đa 4 số lẻ.
                Khi <b>chốt kiểm kê</b>, những dòng lệch được ghi một bản cân đối đúng bằng phần lệch để kéo tồn sổ sách về
                số thực đếm — vẫn giữ hạn mức <b>±{{ $stocktake['balancingMaxPercent'] }}%</b> lượng nhập của một mã.
                Dòng lệch vượt hạn mức không tự cân đối, phải xử lý riêng. Phiếu đã chốt không sửa lại được.
            </div>
        </details>

        <div class="st-scan-result" id="stScanResult"></div>
    </div>

    <form action="{{ route('pages.inventory.materialStocktake.count') }}" method="POST" id="stCountForm">
        @csrf
        <input type="hidden" name="stocktake_id" value="{{ $stTake->id }}">

        <div class="st-count-wrap">
            <table class="table table-bordered table-hover table-sm w-100 mb-0">
                <thead>
                    <tr>
                        <th class="text-center" style="width:42px">STT</th>
                        <th style="width:140px">Mã Xuất Nhập</th>
                        <th>Vật Tư</th>
                        <th style="width:95px">Vị Trí</th>
                        <th class="text-center" style="width:90px">Hạn Dùng</th>
                        <th class="text-right" style="width:105px">Tồn Sổ Sách</th>
                        <th class="text-center" style="width:125px">Thực Tế Đếm</th>
                        <th class="text-right" style="width:105px">Chênh Lệch</th>
                        <th style="width:190px">Ghi Chú</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($stItems as $row)
                        <tr class="st-row" data-st-state="{{ $row->diff_state }}"
                            data-st-code="{{ $row->code }}"
                            data-st-name="{{ $row->material_name }}"
                            data-st-search="{{ mb_strtolower($row->code . ' ' . $row->material_name . ' ' . $row->location_code) }}"
                            data-st-system="{{ (float) $row->system_amount }}">
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td><span class="inv-code font-weight-bold">{{ $row->code }}</span></td>
                            <td>
                                <div class="font-weight-bold">{{ $row->material_name ?: '—' }}</div>
                                <div class="md-sub small text-muted">
                                    {{ $row->manufacturer_short_name ?: '' }}
                                    @if ($row->technical_specification) · {{ $row->technical_specification }} @endif
                                </div>
                            </td>
                            <td class="md-sub">
                                @if ($row->location_code)
                                    <span class="md-tag">{{ $row->location_code }}</span>
                                @else <span class="text-muted">—</span> @endif
                            </td>
                            <td class="text-center md-sub">{{ $invDate($row->expired_date) }}</td>
                            <td class="text-right font-weight-bold">
                                {{ $invNum($row->system_amount) }} <span class="md-sub">{{ $row->unit }}</span>
                            </td>
                            <td class="text-center">
                                @if ($stCounting)
                                    <input type="number" step="0.0001" min="0" class="form-control form-control-sm text-right st-actual"
                                        name="items[{{ $row->id }}][actual_amount]"
                                        value="{{ $row->actual_amount === null ? '' : rtrim(rtrim(number_format((float) $row->actual_amount, 4, '.', ''), '0'), '.') }}"
                                        placeholder="Số đếm">
                                @else
                                    <span class="font-weight-bold">{{ $row->actual_amount === null ? '—' : $invNum($row->actual_amount) }}</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <span class="st-diff {{ $row->diff_state }}">
                                    @if ($row->actual_amount === null)
                                        Chưa đếm
                                    @else
                                        {{ $row->diff_amount > 0 ? '+' : '' }}{{ $invNum($row->diff_amount) }}
                                    @endif
                                </span>
                                @if ($row->balancing_id)
                                    <div class="md-sub small text-muted">Đã cân đối</div>
                                @elseif ($row->balancing_skipped)
                                    <div class="st-skip-note">{{ $row->balancing_note }}</div>
                                @endif
                            </td>
                            <td>
                                @if ($stCounting)
                                    <input type="text" class="form-control form-control-sm" maxlength="500"
                                        name="items[{{ $row->id }}][note]" value="{{ $row->note }}" placeholder="Lý do lệch, tình trạng...">
                                @else
                                    <span class="md-sub">{{ $row->note ?: '—' }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted">Phiếu kiểm kê này không có dòng nào.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="st-toolbar mt-2 mb-0">
            <span class="md-sub" id="stNoResult" style="display:none;">Không có dòng nào khớp bộ lọc.</span>
            @if ($stCounting && user_can('inventory_material_stocktake'))
                <div class="ml-auto">
                    <button type="submit" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-save mr-1"></i> Lưu số đếm
                    </button>
                    {{-- type="button": nút này gửi ngầm qua stPost() nên không submit biểu mẫu --}}
                    <button type="button" class="btn btn-primary btn-sm" id="stCompleteBtn"
                        data-url="{{ route('pages.inventory.materialStocktake.complete') }}">
                        <i class="fas fa-clipboard-check mr-1"></i> Chốt kiểm kê
                    </button>
                </div>
            @endif
        </div>
    </form>
@else
    <div class="md-hint">
        <i class="fas fa-info-circle mr-1"></i>
        {{ $stocktake['periodLabel'] }} ({{ $stocktake['periodRange'] }}) chưa mở phiếu kiểm kê. Kho vật tư kiểm kê
        <b>{{ $stocktake['cycleMonths'] }} tháng 1 lần</b> theo quý — bấm <b>Mở phiếu</b> để hệ thống chốt danh sách các mã
        xuất nhập đang có số dư, sau đó nhập số đếm thực tế cho từng mã rồi chốt phiếu.
    </div>
@endif

{{-- ================= LỊCH SỬ CÁC KỲ ================= --}}
<div class="st-section"><i class="fas fa-clock-rotate-left"></i> CÁC KỲ ĐÃ KIỂM KÊ</div>

<div class="table-responsive">
    <table id="stHistoryTable" class="table table-bordered table-hover w-100 md-table">
        <thead>
            <tr>
                <th class="text-center" style="width:45px">STT</th>
                <th style="width:150px">Mã Phiếu</th>
                <th style="width:170px">Kỳ Kiểm Kê</th>
                <th class="text-center" style="width:105px">Trạng Thái</th>
                <th class="text-center" style="width:95px">Đã Đếm</th>
                <th class="text-center" style="width:85px">Lệch Sổ</th>
                <th class="text-center" style="width:100px">Chờ Xử Lý</th>
                <th style="width:165px">Mở Phiếu</th>
                <th style="width:165px">Chốt Phiếu</th>
                <th class="text-center" style="width:85px">Chi Tiết</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($stocktake['history'] as $h)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td><span class="inv-code font-weight-bold">{{ $h->code }}</span></td>
                    <td>
                        <div class="font-weight-bold">{{ $h->period_label }}</div>
                        <div class="md-sub small text-muted">{{ $h->period_range }}</div>
                    </td>
                    <td class="text-center"><span class="st-state {{ $h->state }}">{{ $h->state_label }}</span></td>
                    <td class="text-center">{{ $h->counted }}/{{ $h->total }}</td>
                    <td class="text-center">
                        @if ($h->diff > 0)
                            <span class="badge badge-warning">{{ $h->diff }}</span>
                        @else <span class="text-muted">—</span> @endif
                    </td>
                    <td class="text-center">
                        @if ($h->skipped > 0)
                            <span class="badge badge-danger">{{ $h->skipped }}</span>
                        @else <span class="text-muted">—</span> @endif
                    </td>
                    <td class="md-sub">
                        {{ $h->opened_at ? \Carbon\Carbon::parse($h->opened_at)->format('d/m/Y H:i') : '—' }}
                        <div class="small text-muted">{{ $h->opened_by }}</div>
                    </td>
                    <td class="md-sub">
                        {{ $h->completed_at ? \Carbon\Carbon::parse($h->completed_at)->format('d/m/Y H:i') : '—' }}
                        <div class="small text-muted">{{ $h->completed_by }}</div>
                    </td>
                    <td class="text-center">
                        <button type="button" class="mi-act-btn btn-st-detail" title="Xem chi tiết kỳ kiểm kê"
                            data-id="{{ $h->id }}"><i class="fas fa-eye"></i></button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="10" class="text-center text-muted">Chưa có kỳ kiểm kê nào, hãy mở phiếu cho {{ $stocktake['periodLabel'] }}.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
