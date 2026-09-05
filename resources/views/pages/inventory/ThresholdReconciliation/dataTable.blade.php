@include('pages.inventory.shared.assets')

@php
    $trNum ??= fn($value) => rtrim(rtrim(number_format((float) $value, 3, '.', ','), '0'), '.');
    $trDate = fn($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : null;

    $trLevelBadge = [
        'exceeded' => 'inv-badge-expired',
        'warn' => 'inv-badge-low',
        'ok' => 'inv-badge-in',
        'no_threshold' => 'inv-badge-out',
    ];
    // Trạng thái lấy theo tỉ lệ ĐỈNH (đã từng đạt) nên nhãn warn/exceeded thêm chữ "Đã"
    $trLevelLabel = [
        'exceeded' => 'Đã vượt ngưỡng',
        'warn' => 'Đã sắp chạm ngưỡng',
        'ok' => 'Trong ngưỡng',
        'no_threshold' => 'Chưa có ngưỡng',
    ];
@endphp

<div class="content-wrapper">
    <div class="md-page">

        <div class="tr-intro">
            <i class="fas fa-scale-balanced mr-1"></i>
            Đối chiếu <b>tổng tồn trữ của các phòng ban thuộc công ty
                @if (!empty($companyName))
                    <b>"{{ $companyName }}"</b>
                @else
                    đang chọn
                @endif
            </b>
            theo từng hoạt chất với
            <b>"Ngưỡng khối lượng hoá chất tồn trữ lớn nhất tại một thời điểm (kg)"</b>
            - Phụ lục IV Nghị định 24/2026/NĐ-CP. Chỉ xét mã danh mục hoá chất đã phân loại
            <b>Nhóm 9 (PL IV Bảng A)</b>, <b>Nhóm 10 (PL IV Bảng B)</b> hoặc <b>hoá chất cấm</b>.
            Cột <b>Tồn Thực Tế Cao Nhất</b> là mức tồn quy ra kg lớn nhất đã từng đạt, dựng lại
            từ chứng từ nhập - xuất - cân đối theo ngày.
            <b>Trạng Thái</b> lấy theo <b>Tỉ Lệ Đỉnh</b> (Tồn cao nhất / ngưỡng) - đúng nghĩa "lớn nhất
            tại một thời điểm": đã từng chạm/vượt ngưỡng thì cơ sở phải xây dựng Kế hoạch phòng ngừa,
            ứng phó sự cố hoá chất, dù nay đã xuất bớt. Cảnh báo vàng khi đạt từ
            {{ $warnPercent }}% ngưỡng trở lên.
            <span class="tr-intro-tip"><i class="fas fa-hand-pointer mr-1"></i>Bấm vào một ô tồn để xem chi
                tiết chứng từ tạo nên con số; bấm vào thẻ thống kê để lọc nhanh theo trạng thái.</span>
        </div>

        {{-- ================= CARD TỔNG TỈ LỆ NHÓM 9 + NHÓM 10 (Điều 33) ================= --}}
        @php
            $cb = $combined;
            $cbPeakPct = (int) round($cb['sum_peak_ratio'] * 100);
            $cbCurPct = (int) round($cb['sum_current_ratio'] * 100);
            $cbExceededItems = $summary['exceeded'] + $tableBSummary['exceeded'];
        @endphp
        <div class="card md-card tr-card" id="trCardTotal">
            <div class="card-header tr-card-header">
                <h5 class="tr-card-title">
                    <i class="fas fa-calculator mr-2"></i>
                    Tổng tỉ lệ hoá chất nguy cơ tồn trữ — nhóm 9 và nhóm 10 (Điều 33)
                </h5>
                <button type="button" class="btn btn-tool tr-card-toggle" data-target="trCardTotal"
                    title="Ẩn / hiện nội dung">
                    <i class="fas fa-chevron-up"></i>
                </button>
            </div>
            <div class="card-body">

                <div class="tr-total-formula">
                    <b>qx₁ / QUX₁ + qx₂ / QUX₂ + … + qxᵢ / QUXᵢ</b>
                    — với <i>qxᵢ</i> = khối lượng tồn trữ lớn nhất tại một thời điểm của hoá chất
                    nguy hiểm <i>i</i> thuộc nhóm 9 (Bảng A) hoặc nhóm 10 (Bảng B), <i>QUXᵢ</i> =
                    ngưỡng tương ứng quy định tại Phụ lục IV. Cộng trên
                    @if (!empty($companyName))
                        công ty <b>"{{ $companyName }}"</b>
                    @else
                        công ty đang chọn
                    @endif
                    - theo khoản 2 Điều 33 NĐ 24/2026/NĐ-CP.
                </div>

                <div class="tr-total-grid">
                    <div class="tr-total-fig lv-{{ $cb['level'] }}">
                        <span class="lbl">Tổng tỉ lệ theo <b>tồn cao nhất</b> (đỉnh) — căn cứ chính</span>
                        <span class="val">{{ $trNum($cb['sum_peak_ratio']) }}</span>
                        <span class="sub">= {{ $cbPeakPct }}% của mốc 1 · gộp {{ count($cb['rows']) }}
                            hoạt chất / hỗn hợp đã có ngưỡng</span>
                    </div>
                    <div class="tr-total-fig">
                        <span class="lbl">Tổng tỉ lệ theo <b>tồn hiện tại</b></span>
                        <span class="val">{{ $trNum($cb['sum_current_ratio']) }}</span>
                        <span class="sub">= {{ $cbCurPct }}% của mốc 1</span>
                    </div>
                </div>

                @if ($cb['level'] === 'exceeded')
                    <div class="tr-total-verdict lv-exceeded">
                        <i class="fas fa-triangle-exclamation mr-1"></i>
                        Tổng tỉ lệ ≥ 1 → cơ sở <b>phải xây dựng Kế hoạch phòng ngừa, ứng phó sự cố hoá chất</b>
                        (điểm b khoản 2 Điều 33), kể cả khi chưa có hoạt chất / hỗn hợp đơn lẻ nào vượt ngưỡng.
                    </div>
                @elseif ($cb['level'] === 'warn')
                    <div class="tr-total-verdict lv-warn">
                        <i class="fas fa-circle-exclamation mr-1"></i>
                        Tổng tỉ lệ đã đạt {{ $cbPeakPct }}% mốc 1 — sắp chạm ngưỡng gộp. Cân nhắc kỹ trước khi
                        nhập thêm hoá chất nhóm 9 / nhóm 10.
                    </div>
                @else
                    <div class="tr-total-verdict lv-ok">
                        <i class="fas fa-circle-check mr-1"></i>
                        Tổng tỉ lệ &lt; 1 — chưa thuộc điểm b khoản 2 Điều 33.
                    </div>
                @endif

                <div class="tr-total-caveat">
                    <i class="fas fa-circle-info mr-1"></i>
                    Vẫn phải lập Kế hoạch nếu có <b>ít nhất 1</b> hoạt chất Bảng A hoặc <b>1</b> hỗn hợp Bảng B
                    có tồn trữ lớn nhất ≥ ngưỡng (điểm a khoản 2 Điều 33).
                    @if ($cbExceededItems > 0)
                        <b class="text-danger">Hiện đã có {{ $cbExceededItems }} đối tượng vượt ngưỡng.</b>
                    @endif
                </div>

                <div class="table-responsive mt-3">
                    <table class="table table-bordered tr-total-table w-100">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 50px">STT</th>
                                <th style="width: 90px">Nhóm</th>
                                <th>Hoạt Chất / Hỗn Hợp</th>
                                <th class="text-right" style="width: 140px">Ngưỡng QUXᵢ (kg)</th>
                                <th class="text-right" style="width: 150px">Tồn Cao Nhất qxᵢ (kg)</th>
                                <th class="text-right" style="width: 120px">qxᵢ / QUXᵢ</th>
                                <th class="text-right" style="width: 130px">Theo Tồn Hiện Tại</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($cb['rows'] as $item)
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td>
                                        <span class="tr-grp-badge {{ $item->group === 9 ? 'g9' : 'g10' }}">
                                            Nhóm {{ $item->group }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="font-weight-bold">{{ $item->name }}</span>
                                        @if ($item->sub)
                                            <div class="md-sub">{{ $item->sub }}</div>
                                        @endif
                                    </td>
                                    <td class="text-right">{{ $trNum($item->threshold_kg) }}</td>
                                    <td class="text-right">{{ $trNum($item->peak_kg) }}</td>
                                    <td class="text-right">
                                        <b
                                            class="{{ $item->level === 'exceeded' ? 'text-danger' : ($item->level === 'warn' ? 'text-warning' : '') }}">
                                            {{ $trNum($item->peak_ratio) }}
                                        </b>
                                        <div class="md-sub">{{ (int) round($item->peak_ratio * 100) }}%</div>
                                    </td>
                                    <td class="text-right">
                                        {{ $trNum($item->ratio) }}
                                        <div class="md-sub">{{ (int) round($item->ratio * 100) }}%</div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-3">
                                        Chưa có hoạt chất nhóm 9 hoặc hỗn hợp nhóm 10 nào được khai ngưỡng Phụ lục IV.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if (count($cb['rows']))
                            <tfoot>
                                <tr>
                                    <td colspan="5" class="text-right">Σ ( qxᵢ / QUXᵢ )</td>
                                    <td class="text-right">{{ $trNum($cb['sum_peak_ratio']) }}</td>
                                    <td class="text-right">{{ $trNum($cb['sum_current_ratio']) }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        {{-- ================= CARD NHÓM 9 (PL IV Bảng A) ================= --}}
        <div class="card md-card tr-card" id="trCardA">
            <div class="card-header tr-card-header">
                <h5 class="tr-card-title">
                    <i class="fas fa-flask-vial mr-2"></i> Nhóm 9 — đối chiếu theo từng hoạt chất
                </h5>
                <button type="button" class="btn btn-tool tr-card-toggle" data-target="trCardA"
                    title="Ẩn / hiện nội dung">
                    <i class="fas fa-chevron-up"></i>
                </button>
            </div>
            <div class="card-body">

                <div class="tr-summary" data-tr-target="mdTable">
                    <div class="tr-sum tr-sum-exceeded" data-tr-level="exceeded" role="button" tabindex="0">
                        <span class="n">{{ $summary['exceeded'] }}</span> Vượt ngưỡng
                    </div>
                    <div class="tr-sum tr-sum-warn" data-tr-level="warn" role="button" tabindex="0">
                        <span class="n">{{ $summary['warn'] }}</span> Sắp chạm ngưỡng
                    </div>
                    <div class="tr-sum tr-sum-ok" data-tr-level="ok" role="button" tabindex="0">
                        <span class="n">{{ $summary['ok'] }}</span> Trong ngưỡng
                    </div>
                    <div class="tr-sum tr-sum-none" data-tr-level="no_threshold" role="button" tabindex="0">
                        <span class="n">{{ $summary['no_threshold'] }}</span> Chưa có ngưỡng
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="mdTable" class="table table-bordered table-hover w-100">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 55px">STT</th>
                                <th>Hoạt Chất</th>
                                <th style="width: 120px">Số CAS</th>
                                <th style="width: 120px">Công Thức</th>
                                <th class="text-right" style="width: 130px">Ngưỡng (kg)</th>
                                <th class="text-right" style="width: 150px">Tồn Thực Tế Toàn Công Ty (kg)</th>
                                <th class="text-right" style="width: 155px"
                                    title="Mức tồn trữ quy ra kg cao nhất đã từng đạt, dựng lại từ chứng từ nhập - xuất - cân đối theo ngày">
                                    Tồn Thực Tế Cao Nhất (kg)</th>
                                <th class="text-right" style="width: 95px" title="Tồn thực tế hiện tại / Ngưỡng">Tỉ Lệ Hiện Tại</th>
                                <th class="text-right" style="width: 95px" title="Tồn thực tế cao nhất / Ngưỡng - căn cứ xác định Trạng Thái">Tỉ Lệ Đỉnh</th>
                                <th class="text-center" style="width: 140px"
                                    title="Theo tỉ lệ ĐỈNH: đã từng chạm/vượt ngưỡng thì phải xây dựng Kế hoạch phòng ngừa dù nay đã xuất bớt">
                                    Trạng Thái</th>
                                <th>Phòng Ban Đóng Góp</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                <tr data-tr-level="{{ $row->level }}">
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td>
                                        <span class="font-weight-bold">{{ $row->ai_name }}</span>
                                        <span class="badge badge-danger ml-1"
                                            title="Nhóm 9 · Phụ lục IV Bảng A NĐ 24/2026/NĐ-CP">Nhóm 9</span>
                                        @if ($row->ai_code)
                                            <div class="md-sub">{{ $row->ai_code }}</div>
                                        @endif
                                        @if ($row->has_unconvertible)
                                            <div class="md-sub" style="color: var(--warning, #F59E0B)"
                                                title="{{ collect($row->unconvertible)->map(fn($u) => $u->category_code . ' — ' . $u->reason)->implode('; ') }}">
                                                * còn {{ count($row->unconvertible) }} lô chưa quy đổi được ra kg
                                            </div>
                                        @endif
                                    </td>
                                    <td class="md-sub">{{ $row->cas_no ?: '—' }}</td>
                                    <td class="md-sub">—</td>
                                    <td class="text-right">
                                        @if ($row->threshold_kg !== null)
                                            {{ $trNum($row->threshold_kg) }}
                                        @else
                                            <span class="md-empty">Chưa có</span>
                                        @endif
                                    </td>
                                    <td class="text-right" data-order="{{ $row->total_kg }}">
                                        <button type="button" class="tr-thr-btn" data-id="{{ $row->ai_id }}"
                                            data-table="A" data-focus="onhand"
                                            title="Bấm để xem các mã xuất nhập tạo nên con số này">
                                            <b>{{ $trNum($row->total_kg) }}</b> <i class="fas fa-list-ul"></i>
                                        </button>
                                    </td>
                                    <td class="text-right" data-order="{{ $row->peak_kg }}">
                                        <button type="button" class="tr-thr-btn" data-id="{{ $row->ai_id }}"
                                            data-table="A" data-focus="peak"
                                            title="Bấm để xem diễn biến chứng từ tạo nên mức cao nhất">
                                            <b>{{ $trNum($row->peak_kg) }}</b> <i class="fas fa-list-ul"></i>
                                        </button>
                                        @if ($trDate($row->peak_date))
                                            <div class="md-sub">ngày {{ $trDate($row->peak_date) }}</div>
                                        @endif
                                    </td>
                                    <td class="text-right" data-order="{{ $row->ratio === null ? -1 : $row->ratio }}">
                                        {{ $row->ratio === null ? '—' : (int) round($row->ratio * 100) . '%' }}
                                    </td>
                                    <td class="text-right" data-order="{{ $row->peak_ratio === null ? -1 : $row->peak_ratio }}">
                                        <b>{{ $row->peak_ratio === null ? '—' : (int) round($row->peak_ratio * 100) . '%' }}</b>
                                    </td>
                                    <td class="text-center" data-order="{{ $row->peak_ratio === null ? -1 : $row->peak_ratio }}">
                                        <span class="inv-badge {{ $trLevelBadge[$row->level] ?? 'inv-badge-out' }}">
                                            {{ $trLevelLabel[$row->level] ?? $row->level }}
                                        </span>
                                    </td>
                                    <td class="md-sub">
                                        @forelse ($row->by_department as $dept)
                                            <span class="tr-dept">{{ $dept->department_name }}:
                                                {{ $trNum($dept->kg) }} kg</span>
                                        @empty
                                            <span class="md-empty">Không có tồn</span>
                                        @endforelse
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center text-muted py-4">
                                        Chưa có mã danh mục hoá chất nào phân loại Nhóm 9 / Nhóm 10 / hoá chất cấm kèm
                                        hoạt chất Phụ lục IV. Vào "Danh Mục › Hoá Chất" để phân loại, "Dữ Liệu Gốc › Tên Hoá Chất"
                                        để gắn hoạt chất, và "Dữ Liệu Gốc › Tên Hoạt Chất" để khai ngưỡng.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ================= CARD NHÓM 10 (PL IV Bảng B) ================= --}}
        <div class="card md-card tr-card" id="trCardB">
            <div class="card-header tr-card-header">
                <h5 class="tr-card-title">
                    <i class="fas fa-layer-group mr-2"></i> Nhóm 10 — đối chiếu theo hỗn hợp
                </h5>
                <button type="button" class="btn btn-tool tr-card-toggle" data-target="trCardB"
                    title="Ẩn / hiện nội dung">
                    <i class="fas fa-chevron-up"></i>
                </button>
            </div>
            <div class="card-body">

                <div class="tr-summary" data-tr-target="mdTableB">
                    <div class="tr-sum tr-sum-exceeded" data-tr-level="exceeded" role="button" tabindex="0">
                        <span class="n">{{ $tableBSummary['exceeded'] }}</span> Vượt ngưỡng
                    </div>
                    <div class="tr-sum tr-sum-warn" data-tr-level="warn" role="button" tabindex="0">
                        <span class="n">{{ $tableBSummary['warn'] }}</span> Sắp chạm ngưỡng
                    </div>
                    <div class="tr-sum tr-sum-ok" data-tr-level="ok" role="button" tabindex="0">
                        <span class="n">{{ $tableBSummary['ok'] }}</span> Trong ngưỡng
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="mdTableB" class="table table-bordered table-hover w-100 md-table">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 55px">STT</th>
                                <th>Hỗn Hợp</th>
                                <th style="width: 160px">Nhóm Nguy Hại Đã Tick</th>
                                <th class="text-right" style="width: 150px">Ngưỡng Thấp Nhất (kg)</th>
                                <th class="text-right" style="width: 150px">Tồn Thực Tế Thô Toàn Công Ty (kg)</th>
                                <th class="text-right" style="width: 155px"
                                    title="Mức tồn trữ thô quy ra kg cao nhất đã từng đạt, dựng lại từ chứng từ nhập - xuất - cân đối theo ngày">
                                    Tồn Thực Tế Cao Nhất (kg)</th>
                                <th class="text-right" style="width: 95px" title="Tồn thô hiện tại / Ngưỡng">Tỉ Lệ Hiện Tại</th>
                                <th class="text-right" style="width: 95px" title="Tồn thô cao nhất / Ngưỡng - căn cứ xác định Trạng Thái">Tỉ Lệ Đỉnh</th>
                                <th class="text-center" style="width: 140px"
                                    title="Theo tỉ lệ ĐỈNH: đã từng chạm/vượt ngưỡng thì phải xây dựng Kế hoạch phòng ngừa dù nay đã xuất bớt">
                                    Trạng Thái</th>
                                <th>Phòng Ban Đóng Góp</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($tableBRows as $row)
                                <tr data-tr-level="{{ $row->level }}">
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td>
                                        <span class="font-weight-bold">{{ $row->chem_name }}</span>
                                        <span class="badge badge-danger ml-1"
                                            title="Nhóm 10 · Phụ lục IV Bảng B NĐ 24/2026/NĐ-CP">Nhóm 10</span>
                                        @if ($row->has_unconvertible)
                                            <div class="md-sub" style="color: var(--warning, #F59E0B)"
                                                title="{{ collect($row->unconvertible)->map(fn($u) => $u->category_code . ' — ' . $u->reason)->implode('; ') }}">
                                                * còn {{ count($row->unconvertible) }} lô chưa quy đổi được ra kg
                                            </div>
                                        @endif
                                    </td>
                                    <td class="md-sub">{{ implode(', ', $row->hazard_labels) }}</td>
                                    <td class="text-right" data-order="{{ $row->min_threshold_kg ?? -1 }}">
                                        @if ($row->min_threshold_kg !== null)
                                            {{ $trNum($row->min_threshold_kg) }}
                                            <div class="md-sub">nhóm {{ $row->strictest_group }}</div>
                                        @else
                                            <span class="md-empty">—</span>
                                        @endif
                                    </td>
                                    <td class="text-right" data-order="{{ $row->total_kg }}">
                                        <button type="button" class="tr-thr-btn" data-id="{{ $row->chem_names_id }}"
                                            data-table="B" data-focus="onhand"
                                            title="Bấm để xem các mã xuất nhập tạo nên con số này">
                                            <b>{{ $trNum($row->total_kg) }}</b> <i class="fas fa-list-ul"></i>
                                        </button>
                                    </td>
                                    <td class="text-right" data-order="{{ $row->peak_kg }}">
                                        <button type="button" class="tr-thr-btn" data-id="{{ $row->chem_names_id }}"
                                            data-table="B" data-focus="peak"
                                            title="Bấm để xem diễn biến chứng từ tạo nên mức cao nhất">
                                            <b>{{ $trNum($row->peak_kg) }}</b> <i class="fas fa-list-ul"></i>
                                        </button>
                                        @if ($trDate($row->peak_date))
                                            <div class="md-sub">ngày {{ $trDate($row->peak_date) }}</div>
                                        @endif
                                    </td>
                                    <td class="text-right"
                                        data-order="{{ $row->ratio === null ? -1 : $row->ratio }}">
                                        {{ $row->ratio === null ? '—' : (int) round($row->ratio * 100) . '%' }}
                                    </td>
                                    <td class="text-right"
                                        data-order="{{ $row->peak_ratio === null ? -1 : $row->peak_ratio }}">
                                        <b>{{ $row->peak_ratio === null ? '—' : (int) round($row->peak_ratio * 100) . '%' }}</b>
                                    </td>
                                    <td class="text-center"
                                        data-order="{{ $row->peak_ratio === null ? -1 : $row->peak_ratio }}">
                                        <span class="inv-badge {{ $trLevelBadge[$row->level] ?? 'inv-badge-out' }}">
                                            {{ $trLevelLabel[$row->level] ?? $row->level }}
                                        </span>
                                    </td>
                                    <td class="md-sub">
                                        @forelse ($row->by_department as $dept)
                                            <span class="tr-dept">{{ $dept->department_name }}:
                                                {{ $trNum($dept->kg) }} kg</span>
                                        @empty
                                            <span class="md-empty">Không có tồn</span>
                                        @endforelse
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-4">
                                        Chưa có hỗn hợp nào đủ điều kiện Nhóm 10 (cần hoạt chất thuộc Nhóm 9 + tick nhóm
                                        nguy hại
                                        trên màn "Dữ Liệu Gốc › Tên Hoá Chất" + mã danh mục phân loại Nhóm 9 / Nhóm 10 / hoá
                                        chất cấm).
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@once
    {{-- =========================================================================
     |  MODAL: xem chi tiết dữ liệu tạo nên "Tồn thực tế" và "Tồn cao nhất".
     |  Dữ liệu lấy qua AJAX từ ThresholdReconciliationController::thresholdDetail().
     ========================================================================= --}}
    <div class="modal fade md-modal" id="thrDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title"><i class="fas fa-scale-balanced mr-2"></i>Chi tiết đối chiếu Ngưỡng Tồn
                            Trữ PL IV</h5>
                        <div class="thr-detail-subtitle md-sub"></div>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span
                            aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="thr-detail-body">
                        <div class="thr-detail-loading"><i class="fas fa-spinner fa-spin mr-1"></i> Đang tải dữ liệu...
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>
@endonce

<script>
    document.addEventListener('DOMContentLoaded', function() {
        /* ---------- Sắp xếp mặc định: theo Tỉ Lệ Đỉnh giảm dần (đã từng vượt ngưỡng lên đầu) ---------- */
        if ($.fn.dataTable.isDataTable('#mdTable')) {
            $('#mdTable').DataTable().order([8, 'desc']).draw();
        }
        if ($.fn.dataTable.isDataTable('#mdTableB')) {
            $('#mdTableB').DataTable().order([7, 'desc']).draw();
        }

        /* ---------- Ẩn / hiện nội dung cả card ---------- */
        $(document).on('click', '.tr-card-toggle', function() {
            var $card = $('#' + $(this).data('target'));
            $card.toggleClass('tr-collapsed');
            // Bảng bị ẩn lúc đo -> khi mở lại phải cho DataTables tính lại bề rộng cột
            if (!$card.hasClass('tr-collapsed')) {
                $card.find('table').each(function() {
                    if ($.fn.dataTable.isDataTable(this)) {
                        $(this).DataTable().columns.adjust().responsive.recalc();
                    }
                });
            }
        });

        /* ---------- Lọc theo trạng thái khi bấm thẻ thống kê ---------- */
        var trFilter = {
            mdTable: null,
            mdTableB: null
        };

        $.fn.dataTable.ext.search.push(function(settings, data, index) {
            var id = settings.nTable.id;
            if (id !== 'mdTable' && id !== 'mdTableB') return true;
            var want = trFilter[id];
            if (!want) return true;
            var tr = settings.aoData[index].nTr;
            return !!tr && tr.getAttribute('data-tr-level') === want;
        });

        $(document).on('click keydown', '.tr-sum[data-tr-level]', function(e) {
            if (e.type === 'keydown' && e.key !== 'Enter' && e.key !== ' ') return;
            e.preventDefault();

            var $chip = $(this);
            var $group = $chip.closest('.tr-summary');
            var target = $group.data('tr-target');
            var level = $chip.data('tr-level');

            if (trFilter[target] === level) {
                trFilter[target] = null;
                $chip.removeClass('is-active');
            } else {
                trFilter[target] = level;
                $group.find('.tr-sum').removeClass('is-active');
                $chip.addClass('is-active');
            }

            if ($.fn.dataTable.isDataTable('#' + target)) {
                $('#' + target).DataTable().draw();
            }
        });

        /* ---------- Modal chi tiết đối chiếu ngưỡng ---------- */
        var THR_URL = @json(route('pages.inventory.thresholdReconciliation.thresholdDetail'));

        // Đưa modal ra thẳng body để không bị kẹt z-index
        $('#thrDetailModal').appendTo('body');

        $(document).on('click', '.tr-thr-btn', function() {
            var id = $(this).data('id');
            var table = $(this).data('table');
            var focus = table + '-' + $(this).data('focus');

            $('#thrDetailModal').find('.thr-detail-subtitle').text('');
            $('#thrDetailModal').find('.thr-detail-body').html(
                '<div class="thr-detail-loading"><i class="fas fa-spinner fa-spin mr-1"></i> Đang tải dữ liệu...</div>'
            );
            $('#thrDetailModal').modal('show');

            fetch(THR_URL + '?table=' + encodeURIComponent(table) + '&id=' + encodeURIComponent(id), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(function(r) {
                    if (!r.ok) throw new Error('http');
                    return r.json();
                })
                .then(function(data) {
                    if (!data.ok) throw new Error(data.reason || 'err');
                    renderThrDetail(data, focus);
                })
                .catch(function() {
                    $('#thrDetailModal').find('.thr-detail-body').html(
                        '<div class="thr-detail-empty">Không tải được dữ liệu chi tiết. Vui lòng thử lại.</div>'
                    );
                });
        });

        function cell(tag, text, cls) {
            var $c = $('<' + tag + '>').text(text == null ? '' : text);
            if (cls) $c.addClass(cls);
            return $c;
        }

        function buildTable(headers, rows) {
            var $t = $('<table>').addClass('table table-bordered table-sm');
            var $tr = $('<tr>');
            headers.forEach(function(h) {
                $tr.append(cell('th', h));
            });
            $t.append($('<thead>').append($tr));
            var $tb = $('<tbody>');
            rows.forEach(function(r) {
                var $row = $('<tr>');
                if (r._peak) $row.addClass('is-peak');
                if (r._unconv) $row.addClass('thr-detail-unconv');
                r.cells.forEach(function(c) {
                    $row.append(cell('td', c));
                });
                if (r._peak) $row.children().last().append($('<span>').addClass('thr-peak-tag').text('ĐỈNH'));
                $tb.append($row);
            });
            $t.append($tb);
            return $t;
        }

        function section(title, focusKey, wantFocus, $content) {
            var $s = $('<div>').addClass('thr-detail-section');
            if (focusKey === wantFocus) $s.addClass('is-focus');
            $s.attr('data-focus', focusKey);
            $s.append($('<div>').addClass('hd').text(title));
            $s.append($content);
            return $s;
        }

        function renderCard(row, wantFocus) {
            var $card = $('<div>').addClass('thr-detail-card');

            $card.append($('<h6>').text((row.table === 'A' ? 'Nhóm 9 — ' : 'Nhóm 10 — ') + row.title));
            if (row.subtitle) $card.append($('<div>').addClass('md-sub').text(row.subtitle));

            var $fig = $('<div>').addClass('thr-detail-figures');
            [
                ['Ngưỡng', row.threshold_kg + ' kg'],
                ['Tồn thực tế', row.total_kg + ' kg (' + row.ratio_percent + ')'],
                ['Gộp từ', row.onhand_count + ' mã xuất nhập còn tồn'],
                ['Tồn cao nhất', row.peak_kg + ' kg (' + row.peak_ratio_percent + ')'],
                ['Ngày đạt đỉnh', row.peak_date],
                ['Dựng từ', row.timeline_count + ' chứng từ (' + row.import_count + ' lần nhập)'],
                ['Trạng thái (theo đỉnh)', row.level_label],
                ['Trạng thái hiện tại', row.current_level_label]
            ].forEach(function(f) {
                $fig.append($('<div>').addClass('fig')
                    .append($('<span>').text(f[0]))
                    .append($('<b>').text(f[1])));
            });
            $card.append($fig);

            /* ----- Tồn thực tế: các mã xuất nhập được cộng lại ----- */
            var $onhandWrap = $('<div>');
            if (row.onhand_rows.length) {
                $onhandWrap.append(buildTable(
                    ['Mã xuất nhập', 'Ngày nhập', 'Mã danh mục', 'Phòng ban', 'SL nhập', 'Cân đối', 'Đã xuất',
                        'Tồn còn lại', 'Tồn còn lại (kg)'
                    ],
                    row.onhand_rows.map(function(o) {
                        return {
                            cells: [o.ref, o.date, o.category_code, o.department_name, o.imported, o.balanced,
                                o.exported, o.on_hand, o.on_hand_kg
                            ]
                        };
                    })
                ));
                $onhandWrap.append($('<div>').addClass('thr-detail-note').text(
                    'Tồn thực tế = tổng "Tồn còn lại (kg)" của ' + row.onhand_count + ' mã xuất nhập ở trên = ' +
                    row.total_kg + ' kg.'
                ));
            } else {
                $onhandWrap.append($('<div>').addClass('thr-detail-empty').text('Không có mã xuất nhập nào còn tồn.'));
            }
            if (row.by_department.length) {
                $onhandWrap.append($('<div>').addClass('thr-detail-note')
                    .text('Cộng theo phòng: ' + row.by_department.map(function(d) {
                        return d.department_name + ' = ' + d.kg + ' kg';
                    }).join(' · ')));
            }
            if (row.unconvertible.length) {
                $onhandWrap.append($('<div>').addClass('thr-detail-note')
                    .css('color', '#B45309').text('Chưa quy đổi được ra kg (không tính vào con số trên):'));
                $onhandWrap.append(buildTable(['Mã danh mục', 'Hoá chất', 'Lý do'],
                    row.unconvertible.map(function(u) {
                        return {
                            _unconv: true,
                            cells: [u.category_code, u.chem_name, u.reason]
                        };
                    })));
            }
            $card.append(section('Tồn thực tế — ' + row.onhand_count + ' mã xuất nhập được cộng lại',
                row.table + '-onhand', wantFocus, $onhandWrap));

            /* ----- Tồn cao nhất: diễn biến chứng từ ----- */
            var $peakWrap = $('<div>');
            if (row.timeline.length) {
                $peakWrap.append(buildTable(
                    ['Ngày', 'Loại', 'Mã chứng từ', 'Mã danh mục', 'Phòng ban', 'Biến động', 'Biến động (kg)',
                        'Luỹ kế (kg)'
                    ],
                    row.timeline.map(function(t) {
                        return {
                            _peak: t.is_peak,
                            cells: [t.date, t.type_label, t.ref, t.category_code, t.department_name, t.delta,
                                t.delta_kg, t.running_kg
                            ]
                        };
                    })
                ));
                $peakWrap.append($('<div>').addClass('thr-detail-note').text(
                    'Dựng lại từ ' + row.timeline_count + ' chứng từ đang hiệu lực (' + row.import_count +
                    ' lần nhập, còn lại là xuất / cân đối), cộng dồn theo ngày. Dòng tô vàng là lúc tồn chạm mức cao nhất. ' +
                    'Chứng từ bị khoá về sau không còn trong chuỗi; cùng một ngày thì cộng (nhập) trước, trừ (xuất) sau.'
                ));
            } else {
                $peakWrap.append($('<div>').addClass('thr-detail-empty').text(
                    'Chưa có chứng từ nào để dựng diễn biến.'));
            }
            $card.append(section('Tồn cao nhất — ' + row.timeline_count + ' chứng từ',
                row.table + '-peak', wantFocus, $peakWrap));

            return $card;
        }

        function renderThrDetail(data, wantFocus) {
            var $body = $('#thrDetailModal').find('.thr-detail-body').empty();
            $('#thrDetailModal').find('.thr-detail-subtitle')
                .text((data.category_code && data.category_code !== '—' ? 'Mã ' + data.category_code + ' · ' : '') +
                    data.chem_name + ' · cảnh báo vàng từ ' + data.warn_percent + '% ngưỡng');

            var cards = [];
            (data.tableA || []).forEach(function(r) {
                cards.push(r);
            });
            if (data.tableB) cards.push(data.tableB);

            if (!cards.length) {
                $body.append($('<div>').addClass('thr-detail-empty')
                    .text('Không có dữ liệu đối chiếu ngưỡng PL IV cho dòng này.'));
                return;
            }

            cards.forEach(function(r) {
                $body.append(renderCard(r, wantFocus));
            });

            var $focus = $body.find('.thr-detail-section.is-focus').first();
            if ($focus.length) {
                $focus[0].scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }
    });
</script>

@once
    <style>
        .tr-intro {
            background: var(--primary-soft);
            border: 1px dashed var(--primary-lighter);
            border-radius: var(--border-radius-md);
            padding: 12px 14px;
            font-size: 0.88rem;
            color: var(--primary-dark);
            line-height: 1.6;
            margin-bottom: 16px;
        }

        .tr-intro-tip {
            display: block;
            margin-top: 6px;
            font-style: italic;
            color: var(--primary);
        }

        /* ---- Card Nhóm 9 / Nhóm 10 ---- */
        .tr-card {
            margin-bottom: 18px;
        }

        .tr-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #fff;
            border-bottom: 1px solid var(--primary-soft, #EAF3FC);
            padding: 16px 20px;
        }

        .tr-card-title {
            margin: 0;
            color: var(--primary-dark);
            font-weight: 700;
            letter-spacing: 0.3px;
        }

        .tr-card-toggle {
            color: var(--primary);
            transition: transform 0.25s ease, color 0.2s ease;
        }

        .tr-card-toggle:hover {
            color: var(--primary-dark);
        }

        .tr-card.tr-collapsed .card-body {
            display: none;
        }

        .tr-card.tr-collapsed .tr-card-toggle i {
            transform: rotate(180deg);
        }

        /* ---- Thẻ thống kê -> bộ lọc ---- */
        .tr-summary {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 16px;
        }

        .tr-sum {
            border-radius: var(--border-radius-md);
            padding: 8px 14px;
            font-size: 0.82rem;
            font-weight: 600;
            border: 1px solid transparent;
            cursor: pointer;
            user-select: none;
            transition: all 0.2s ease;
        }

        .tr-sum[role="button"]:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-sm, 0 1px 4px rgba(0, 0, 0, 0.12));
        }

        .tr-sum.is-active {
            box-shadow: 0 0 0 2px var(--primary), var(--shadow-sm, 0 1px 4px rgba(0, 0, 0, 0.12));
        }

        .tr-sum .n {
            font-size: 1.05rem;
            font-weight: 800;
            margin-right: 4px;
        }

        .tr-sum-exceeded {
            background: #FEE2E2;
            color: #B91C1C;
            border-color: #FCA5A5;
        }

        .tr-sum-warn {
            background: #FEF3C7;
            color: #B45309;
            border-color: #FCD34D;
        }

        .tr-sum-ok {
            background: #DCFCE7;
            color: #15803D;
            border-color: #86EFAC;
        }

        .tr-sum-none {
            background: #E2E8F0;
            color: #475569;
            border-color: #CBD5E1;
        }

        .tr-dept {
            display: inline-block;
            background: rgba(var(--primary-rgb), 0.08);
            border: 1px solid var(--primary-lighter);
            border-radius: 6px;
            padding: 1px 7px;
            margin: 2px 4px 2px 0;
            font-size: 0.8rem;
            white-space: nowrap;
        }

        /* ---- Nút mở modal ở ô tồn ---- */
        .tr-thr-btn {
            border: 1px solid transparent;
            background: transparent;
            color: var(--text-main);
            border-radius: var(--border-radius-md, 8px);
            padding: 2px 8px;
            font-size: 0.86rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .tr-thr-btn b {
            color: var(--primary-dark);
        }

        .tr-thr-btn i {
            color: var(--primary-lighter);
            margin-left: 4px;
            font-size: 0.75rem;
        }

        .tr-thr-btn:hover {
            background: var(--primary-soft);
            border-color: var(--primary-lighter);
        }

        .tr-thr-btn:hover i {
            color: var(--primary);
        }

        /* ---- Modal chi tiết ---- */
        .thr-detail-card {
            border: 1px solid var(--primary-lighter);
            border-radius: var(--border-radius-lg, 12px);
            padding: 16px 18px;
            margin-bottom: 18px;
        }

        .thr-detail-card>h6 {
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 4px;
        }

        .thr-detail-figures {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 12px 0;
        }

        .thr-detail-figures .fig {
            flex: 1 1 150px;
            background: var(--bg-neutral, #F5F9FD);
            border: 1px solid var(--primary-lighter);
            border-radius: var(--border-radius-md, 8px);
            padding: 8px 12px;
        }

        .thr-detail-figures .fig span {
            display: block;
            font-size: 0.75rem;
            color: #64748b;
        }

        .thr-detail-figures .fig b {
            font-size: 1.05rem;
            color: var(--primary-dark);
        }

        .thr-detail-section {
            margin-top: 16px;
        }

        .thr-detail-section>.hd {
            font-weight: 700;
            color: var(--primary);
            border-left: 3px solid var(--primary);
            padding-left: 8px;
            margin-bottom: 8px;
        }

        .thr-detail-section.is-focus>.hd {
            background: var(--primary-soft);
            border-radius: 0 6px 6px 0;
        }

        .thr-detail-section table {
            width: 100%;
            font-size: 0.82rem;
            margin-bottom: 0;
        }

        .thr-detail-section table th {
            background: var(--primary-soft);
            color: var(--primary-dark);
            font-weight: 600;
            white-space: nowrap;
        }

        .thr-detail-section table td,
        .thr-detail-section table th {
            padding: 5px 8px;
            border: 1px solid #dbe6f2;
        }

        .thr-detail-section tr.is-peak td {
            background: #FEF3C7;
            font-weight: 700;
        }

        .thr-peak-tag {
            background: #F59E0B;
            color: #fff;
            border-radius: 4px;
            font-size: 0.68rem;
            padding: 1px 5px;
            margin-left: 4px;
        }

        .thr-detail-unconv td {
            background: #FEF2F2;
        }

        .thr-detail-note {
            font-size: 0.78rem;
            color: #64748b;
            margin-top: 8px;
        }

        .thr-detail-empty {
            color: #94a3b8;
            padding: 8px 0;
        }

        /* ---- Card Tổng tỉ lệ nhóm 9 + nhóm 10 (Điều 33) ---- */
        .tr-total-formula {
            background: #fff;
            border: 1px dashed var(--primary-lighter);
            border-radius: var(--border-radius-md);
            padding: 10px 14px;
            margin-bottom: 14px;
            font-size: 0.86rem;
            line-height: 1.6;
            color: var(--primary-dark);
        }

        .tr-total-formula b {
            font-size: 0.95rem;
            letter-spacing: 0.3px;
        }

        .tr-total-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            margin-bottom: 14px;
        }

        .tr-total-fig {
            flex: 1 1 240px;
            border: 1px solid var(--primary-lighter);
            border-radius: var(--border-radius-md);
            padding: 14px 16px;
            background: var(--bg-neutral);
        }

        .tr-total-fig .lbl {
            display: block;
            font-size: 0.78rem;
            color: #64748b;
            margin-bottom: 6px;
        }

        .tr-total-fig .val {
            display: block;
            font-size: 1.7rem;
            font-weight: 800;
            line-height: 1.1;
            color: var(--primary-dark);
        }

        .tr-total-fig .sub {
            display: block;
            margin-top: 4px;
            font-size: 0.76rem;
            color: #64748b;
        }

        .tr-total-fig.lv-exceeded {
            background: #FEE2E2;
            border-color: #FCA5A5;
        }

        .tr-total-fig.lv-exceeded .val {
            color: #B91C1C;
        }

        .tr-total-fig.lv-warn {
            background: #FEF3C7;
            border-color: #FCD34D;
        }

        .tr-total-fig.lv-warn .val {
            color: #B45309;
        }

        .tr-total-fig.lv-ok {
            background: #DCFCE7;
            border-color: #86EFAC;
        }

        .tr-total-fig.lv-ok .val {
            color: #15803D;
        }

        .tr-total-verdict {
            border-radius: var(--border-radius-md);
            padding: 10px 14px;
            font-size: 0.86rem;
            font-weight: 600;
            line-height: 1.55;
        }

        .tr-total-verdict.lv-exceeded {
            background: #FEE2E2;
            color: #B91C1C;
            border: 1px solid #FCA5A5;
        }

        .tr-total-verdict.lv-warn {
            background: #FEF3C7;
            color: #B45309;
            border: 1px solid #FCD34D;
        }

        .tr-total-verdict.lv-ok {
            background: #DCFCE7;
            color: #15803D;
            border: 1px solid #86EFAC;
        }

        .tr-total-caveat {
            margin-top: 8px;
            font-size: 0.8rem;
            font-weight: 500;
            color: #64748b;
            line-height: 1.55;
        }

        .tr-total-table {
            font-size: 0.84rem;
        }

        .tr-total-table thead th {
            background: var(--primary-soft);
            color: var(--primary-dark);
            font-weight: 600;
            vertical-align: middle;
        }

        .tr-total-table tfoot td {
            background: var(--primary-soft);
            color: var(--primary-dark);
            font-weight: 800;
        }

        .tr-grp-badge {
            display: inline-block;
            border-radius: 999px;
            padding: 1px 9px;
            font-size: 0.72rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .tr-grp-badge.g9 {
            background: var(--primary-soft);
            color: var(--primary-dark);
            border: 1px solid var(--primary-lighter);
        }

        .tr-grp-badge.g10 {
            background: rgba(var(--primary-rgb), 0.16);
            color: var(--primary-dark);
            border: 1px solid var(--primary);
        }
    </style>
@endonce
