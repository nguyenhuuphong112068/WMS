{{--
|--------------------------------------------------------------------------
| TỒN KHO HOÁ CHẤT - TAB "ĐỐI CHIẾU NGƯỠNG PL IV" (nội dung)
|--------------------------------------------------------------------------
| Trả về qua AJAX bởi ThresholdReconciliationController::pane() rồi chèn vào
| #invPaneThreshold. JS / CSS / modal chi tiết nằm ở ThresholdReconciliation.script
| (nhúng sẵn trong màn hình Tồn Kho Hoá Chất).
--}}

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

<div class="tr-intro">
    <i class="fas fa-scale-balanced mr-1"></i>
    <b>Tổng tồn trữ của các phòng ban thuộc công ty
        @if (!empty($companyName))
            <b>"{{ $companyName }}"</b>
        @else
            đang chọn
        @endif
    </b>


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

            Tổng tỉ lệ hoá chất nguy cơ tồn trữ — nhóm 9 và nhóm 10 (Điều 33)
        </h5>
        <button type="button" class="btn btn-tool tr-card-toggle" data-target="trCardTotal"
            title="Ẩn / hiện nội dung">
            <i class="fas fa-chevron-up"></i>
        </button>
    </div>
    <div class="card-body">

        {{-- <div class="tr-total-formula">
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
        </div> --}}

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

            @if ($cbExceededItems > 0)
                <b class="text-danger">Hiện đã có {{ $cbExceededItems }} hoá chất vượt ngưỡng.</b>
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
            </i> Nhóm 9 — đối chiếu theo từng hoạt chất
        </h5>
        <button type="button" class="btn btn-tool tr-card-toggle" data-target="trCardA"
            title="Ẩn / hiện nội dung">
            <i class="fas fa-chevron-up"></i>
        </button>
    </div>
    <div class="card-body">

        <div class="tr-summary md-tablebar-item" data-tr-target="trTableA">
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
            <table id="trTableA" class="table table-bordered table-hover w-100 md-table">
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
                        <th class="text-right" style="width: 95px" title="Tồn thực tế hiện tại / Ngưỡng">Tỉ
                            Lệ Hiện Tại</th>
                        <th class="text-right" style="width: 95px"
                            title="Tồn thực tế cao nhất / Ngưỡng - căn cứ xác định Trạng Thái">Tỉ Lệ Đỉnh</th>
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
                                @if (!empty($row->members))
                                    <div class="md-sub"
                                        title="Mục gộp của nghị định - tồn của các chất thành viên được cộng chung vào ngưỡng này">
                                        Gộp tồn của: {{ implode(', ', $row->members) }}
                                    </div>
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
                            <td colspan="11" class="text-center text-muted py-4">
                                Chưa có mã danh mục hoá chất nào phân loại Nhóm 9 / Nhóm 10 / hoá chất cấm kèm
                                hoạt chất Phụ lục IV. Vào "Danh Mục › Hoá Chất" để phân loại, "Dữ Liệu Gốc › Tên
                                Hoá Chất"
                                để gắn hoạt chất, và "Dữ Liệu Gốc › Đơn Chất" để khai ngưỡng.
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
            Nhóm 10 — đối chiếu theo hỗn hợp
        </h5>
        <button type="button" class="btn btn-tool tr-card-toggle" data-target="trCardB"
            title="Ẩn / hiện nội dung">
            <i class="fas fa-chevron-up"></i>
        </button>
    </div>
    <div class="card-body">

        <div class="tr-summary md-tablebar-item" data-tr-target="trTableB">
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
            <table id="trTableB" class="table table-bordered table-hover w-100 md-table">
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
                        <th class="text-right" style="width: 95px" title="Tồn thô hiện tại / Ngưỡng">Tỉ Lệ
                            Hiện Tại</th>
                        <th class="text-right" style="width: 95px"
                            title="Tồn thô cao nhất / Ngưỡng - căn cứ xác định Trạng Thái">Tỉ Lệ Đỉnh</th>
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
                                <button type="button" class="tr-thr-btn"
                                    data-id="{{ $row->chem_names_id }}" data-table="B" data-focus="onhand"
                                    title="Bấm để xem các mã xuất nhập tạo nên con số này">
                                    <b>{{ $trNum($row->total_kg) }}</b> <i class="fas fa-list-ul"></i>
                                </button>
                            </td>
                            <td class="text-right" data-order="{{ $row->peak_kg }}">
                                <button type="button" class="tr-thr-btn"
                                    data-id="{{ $row->chem_names_id }}" data-table="B" data-focus="peak"
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
                                trên màn "Dữ Liệu Gốc › Tên Hoá Chất" + mã danh mục phân loại Nhóm 9 / Nhóm 10 /
                                hoá
                                chất cấm).
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
