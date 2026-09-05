@include('pages.category.shared.assets')

<div class="card md-card">
    <div class="card-body">

        <div class="md-toolbar">
            <div class="d-flex flex-wrap align-items-center" style="gap: 10px">
                @perm('category_chemical_create')
                    <button type="button" class="btn btn-primary btn-md-create">
                        <i class="fas fa-plus mr-1"></i> Thêm mới
                    </button>
                @endperm
                <button type="button" class="btn btn-outline-primary" data-toggle="modal"
                    data-target="#classifyGuideModal">
                    <i class="fas fa-info-circle mr-1"></i> Chú thích Phụ lục &amp; Nhóm
                </button>
            </div>
            <p class="hint">
                <i class="fas fa-info-circle mr-1"></i>
                Đang hoạt động {{ $datas->where('status_id', 1)->count() }}/{{ $datas->count() }} bản ghi.
                Rê chuột vào mã phân loại để xem tên đầy đủ.
            </p>
        </div>

        @include('pages.shared.classificationFilter', ['clsTarget' => 'mdTable'])

        <div class="table-responsive">
            <table id="mdTable" class="table table-bordered table-hover w-100">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 55px">STT</th>
                        <th style="width: 130px">Mã Danh Mục</th>
                        <th>Tên Hoá Chất</th>
                        <th>Nhà Sản Xuất</th>
                        <th class="text-center" style="width: 105px">Tỉ Trọng d<br><small>(g/ml)</small></th>
                        <th style="width: 180px">Điều Kiện Bảo Quản</th>
                        <th style="width: 170px">Phân Loại</th>
                        <th style="width: 150px" title="Dòng cảnh báo in ở dải giữa nhãn dán lô hàng">
                            Cảnh Báo An Toàn</th>
                        <th class="text-center" style="width: 155px"
                            title="Đối chiếu tổng tồn trữ toàn công ty của hoạt chất với ngưỡng Phụ lục IV NĐ 24/2026/NĐ-CP">
                            Ngưỡng Tồn Trữ PL IV</th>
                        <th style="width: 170px"
                            title="Các phòng ban đã khai dùng hoá chất này (bảng chemical_department_categories)">
                            Phòng Ban Đang Dùng</th>
                        <th style="width: 120px">Người Tạo</th>
                        <th class="text-center" style="width: 125px">Duyệt</th>
                        <th class="text-center" style="width: 100px">Sử Dụng</th>
                        <th class="text-center" style="width: 255px">Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($datas as $row)
                        @php
                            // Nhóm NĐ 24/2026 suy tự động theo mã danh mục (không còn cột classification)
                            $codes = $classificationCodes[$row->id] ?? [];

                            $warningCodes = json_decode($row->safety_warning ?? '', true);
                            $warningCodes = is_array($warningCodes) ? $warningCodes : [];
                        @endphp
                        {{-- data-classification để bộ lọc Phụ lục / Nhóm hoá chất nhận ra dòng này --}}
                        <tr data-classification="{{ implode(',', $codes) }}">
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td>
                                <span class="font-weight-bold">{{ $row->code }}</span>
                                @if ($row->type)
                                    <br><span class="md-tag">{{ $row->type }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="font-weight-bold">{{ $row->chem_name ?: '—' }}</span>
                                @if ($row->cas_no)
                                    <br><small class="md-sub">CAS: {{ $row->cas_no }}</small>
                                @endif
                            </td>
                            <td class="md-sub">
                                @if ($row->manufacturer_name)
                                    {{ $row->manufacturer_name }}
                                    @if ($row->manufacturer_short_name)
                                        <br><span class="md-tag">{{ $row->manufacturer_short_name }}</span>
                                    @endif
                                @else
                                    <span class="md-empty">—</span>
                                @endif
                            </td>
                            <td class="text-center md-sub">
                                @if ($row->density !== null)
                                    {{ rtrim(rtrim($row->density, '0'), '.') }}
                                @else
                                    <span class="md-empty">—</span>
                                @endif
                            </td>
                            <td class="md-sub">
                                @if ($row->storage_condition_name)
                                    <span class="md-note"
                                        title="{{ $row->storage_condition_name }}">{{ $row->storage_condition_name }}</span>
                                @else
                                    <span class="md-empty">—</span>
                                @endif
                            </td>
                            <td>
                                @if ($codes)
                                    <div class="cat-chips">
                                        @foreach ($codes as $code)
                                            <span
                                                class="cat-chip {{ \App\Support\ChemicalClassification::toneOfCode($code) }}"
                                                title="{{ $classificationLabels[$code] ?? $code }}">{{ $code }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="md-empty" title="Suy từ Tên Hoạt Chất / Tên Hoá Chất - chưa thuộc nhóm nào của NĐ 24/2026">—</span>
                                @endif
                            </td>
                            <td class="md-sub">
                                @if ($warningCodes)
                                    <div class="cat-chips">
                                        @foreach ($warningCodes as $code)
                                            <span class="safety-chip"
                                                title="{{ $safetyWarnings[$code] ?? $code }}">
                                                @include('pages.shared.safetyPictogram', ['code' => $code, 'size' => 16])
                                                {{ $safetyWarnings[$code] ?? $code }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="md-empty">—</span>
                                @endif
                            </td>
                            {{-- Ngưỡng tồn trữ Phụ lục IV: mã danh mục chỉ xét MỘT bảng -
                                 đơn chất nhóm 9 -> Bảng A, hỗn hợp nhóm 10 -> Bảng B (lọc sẵn ở controller) --}}
                            @php
                                $thr = $thresholds[$row->id] ?? null;   // chỉ còn khi mã danh mục là đơn chất nhóm 9
                                $thrB = $thresholdsB[$row->id] ?? null;  // chỉ còn khi mã danh mục là hỗn hợp nhóm 10
                                $thrNum = fn($v) => rtrim(rtrim(number_format((float) $v, 3, '.', ','), '0'), '.');
                                $thrDate = fn($v) => $v ? \Carbon\Carbon::parse($v)->format('d/m/Y') : null;
                                $thrBadgeMap = ['ok' => 'badge-success', 'warn' => 'badge-warning', 'exceeded' => 'badge-danger'];
                                // level = theo ĐỈNH (đã từng đạt) nên nhãn thêm chữ "Đã"
                                $thrLabelMap = ['ok' => 'Trong ngưỡng', 'warn' => 'Đã sắp chạm ngưỡng', 'exceeded' => 'Đã vượt ngưỡng'];
                                $thrOrder = max($thr->peak_ratio ?? -1, $thrB->peak_ratio ?? -1);
                            @endphp
                            <td class="text-center" data-order="{{ number_format($thrOrder, 4, '.', '') }}">
                                @if (! $thr && ! $thrB)
                                    <span class="md-empty" title="Chỉ đối chiếu ngưỡng PL IV cho mã danh mục là đơn chất nhóm 9 (Bảng A) hoặc hỗn hợp nhóm 10 (Bảng B) đã khai ngưỡng">—</span>
                                @endif
                                @if ($thr)
                                    <div class="cat-thr-block">
                                        <span class="badge badge-danger" title="{{ $classificationLabels['N9'] ?? 'Nhóm 9 - Phụ lục IV Bảng A' }}">Nhóm 9</span>
                                        <span class="badge {{ $thrBadgeMap[$thr->level] ?? 'badge-secondary' }}" title="{{ $thr->ai_name }}">{{ $thrLabelMap[$thr->level] ?? $thr->level }}</span>
                                        <div class="md-sub mt-1">Ngưỡng: {{ $thrNum($thr->threshold_kg) }} kg</div>
                                        <button type="button" class="thr-chip" data-id="{{ $row->id }}" data-table="A" data-focus="onhand"
                                            title="Bấm để xem các mã xuất nhập tạo nên con số này">
                                            Tồn thực tế: <b>{{ $thrNum($thr->total_kg) }}</b> kg ({{ (int) round($thr->ratio * 100) }}%) <i class="fas fa-list-ul"></i>
                                        </button>
                                        <button type="button" class="thr-chip" data-id="{{ $row->id }}" data-table="A" data-focus="peak"
                                            title="Bấm để xem diễn biến chứng từ tạo nên mức cao nhất">
                                            Tồn cao nhất: <b>{{ $thrNum($thr->peak_kg) }}</b> kg ({{ (int) round($thr->peak_ratio * 100) }}%){{ $thrDate($thr->peak_date) ? ' · ' . $thrDate($thr->peak_date) : '' }} <i class="fas fa-list-ul"></i>
                                        </button>
                                        @if ($thr->has_unconvertible)
                                            <div class="md-sub" style="color: var(--warning, #F59E0B)" title="Một số lô chưa quy đổi được ra kg - con số chưa đầy đủ">* chưa đủ dữ liệu</div>
                                        @endif
                                    </div>
                                @endif
                                @if ($thrB)
                                    <div class="cat-thr-block">
                                        <span class="badge badge-danger" title="{{ $classificationLabels['N10'] ?? 'Nhóm 10 - Phụ lục IV Bảng B' }}">Nhóm 10</span>
                                        <span class="badge {{ $thrBadgeMap[$thrB->level] ?? 'badge-secondary' }}">{{ $thrLabelMap[$thrB->level] ?? $thrB->level }}</span>
                                        <div class="md-sub mt-1">Ngưỡng: {{ $thrNum($thrB->min_threshold_kg) }} kg (nhóm {{ $thrB->strictest_group }})</div>
                                        <button type="button" class="thr-chip" data-id="{{ $row->id }}" data-table="B" data-focus="onhand"
                                            title="Bấm để xem các mã xuất nhập tạo nên con số này">
                                            Tồn thực tế: <b>{{ $thrNum($thrB->total_kg) }}</b> kg ({{ (int) round($thrB->ratio * 100) }}%) <i class="fas fa-list-ul"></i>
                                        </button>
                                        <button type="button" class="thr-chip" data-id="{{ $row->id }}" data-table="B" data-focus="peak"
                                            title="Bấm để xem diễn biến chứng từ tạo nên mức cao nhất">
                                            Tồn cao nhất: <b>{{ $thrNum($thrB->peak_kg) }}</b> kg ({{ (int) round($thrB->peak_ratio * 100) }}%){{ $thrDate($thrB->peak_date) ? ' · ' . $thrDate($thrB->peak_date) : '' }} <i class="fas fa-list-ul"></i>
                                        </button>
                                        @if ($thrB->has_unconvertible)
                                            <div class="md-sub" style="color: var(--warning, #F59E0B)" title="Một số lô chưa quy đổi được ra kg - con số chưa đầy đủ">* chưa đủ dữ liệu</div>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            {{-- Phòng ban đang dùng: danh mục chung toàn công ty, phòng nào khai mới hiện --}}
                            @php
                                $catDepartments = $departmentsByCategory[$row->id] ?? collect();
                            @endphp
                            <td data-order="{{ $catDepartments->count() }}">
                                @if ($catDepartments->isNotEmpty())
                                    <div class="cat-chips">
                                        @foreach ($catDepartments as $dept)
                                            <span class="cat-chip dept"
                                                title="{{ $dept->name }}">{{ $dept->shortName ?: $dept->name }}</span>
                                        @endforeach
                                    </div>
                                    <div class="md-sub">{{ $catDepartments->count() }} phòng</div>
                                @else
                                    <span class="md-empty" title="Chưa phòng ban nào khai dùng hoá chất này">Chưa
                                        phòng nào dùng</span>
                                @endif
                            </td>
                            <td class="md-sub">
                                {{ $row->created_by ?: '—' }}
                                @if ($row->created_at)
                                    <br><small>{{ \Carbon\Carbon::parse($row->created_at)->format('d/m/Y') }}</small>
                                @endif
                            </td>
                            <td class="text-center">
                                @include('pages.materData.shared.appStatus', ['row' => $row])
                            </td>
                            <td class="text-center">
                                @if ($row->status_id == 1)
                                    <span class="badge badge-success">Hoạt động</span>
                                @else
                                    <span class="badge badge-danger">Đã khoá</span>
                                @endif
                            </td>
                            <td>
                                @include('pages.category.shared.rowActions', [
                                    'prefix' => $mdRoute,
                                    'permPrefix' => 'category_chemical_',
                                    'row' => $row,
                                    'label' => $mdLabel,
                                    'title' => $row->code,
                                    'showConvert' => true,
                                    'historyCount' => (int) ($historyCounts[$row->id] ?? 0),
                                    'editData' => [
                                        'id' => $row->id,
                                        'code' => $row->code,
                                        'type' => $row->type,
                                        'chem_names_id' => $row->chem_names_id,
                                        'manufacturers_id' => $row->manufacturers_id,
                                        'density' => $row->density !== null ? rtrim(rtrim($row->density, '0'), '.') : null,
                                        'ai_content_percent' => $row->ai_content_percent !== null ? rtrim(rtrim($row->ai_content_percent, '0'), '.') : null,
                                        'storage_condition_id' => $row->storage_condition_id,
                                        'doc_no' => $row->doc_no,
                                        'safety_warning' => $warningCodes,
                                    ],
                                ])
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@once
    {{-- =========================================================================
     |  MODAL: xem chi tiết dữ liệu tạo nên "Tồn thực tế" và "Tồn cao nhất"
     |  của cột Ngưỡng Tồn Trữ PL IV. Dữ liệu lấy qua AJAX từ thresholdDetail().
     ========================================================================= --}}
    <div class="modal fade md-modal" id="thrDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title"><i class="fas fa-scale-balanced mr-2"></i>Chi tiết đối chiếu Ngưỡng Tồn Trữ PL IV</h5>
                        <div class="thr-detail-subtitle md-sub"></div>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="thr-detail-body">
                        <div class="thr-detail-loading"><i class="fas fa-spinner fa-spin mr-1"></i> Đang tải dữ liệu...</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .thr-chip {
            display: block;
            width: 100%;
            text-align: left;
            border: 1px solid var(--primary-lighter);
            background: var(--primary-soft);
            color: var(--text-main);
            border-radius: var(--border-radius-md, 8px);
            padding: 4px 8px;
            margin-top: 4px;
            font-size: 0.8rem;
            line-height: 1.35;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .thr-chip:hover {
            background: #fff;
            border-color: var(--primary);
            transform: translateY(-1px);
            box-shadow: var(--shadow-sm, 0 1px 4px rgba(0, 0, 0, 0.08));
        }

        .thr-chip b {
            color: var(--primary-dark);
        }

        .thr-chip i {
            float: right;
            color: var(--primary-lighter);
            margin-top: 3px;
        }

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
    </style>

    <script>
        // Bọc trong DOMContentLoaded: script này nằm giữa body, jQuery nạp ở cuối body
        document.addEventListener('DOMContentLoaded', function() {
            var THR_URL = @json(route('pages.category.chemicalCategory.thresholdDetail'));

            // Đưa modal ra thẳng body để không bị kẹt z-index trong tab-pane
            $('#thrDetailModal').appendTo('body');

            $(document).on('click', '.thr-chip', function() {
                var id = $(this).data('id');
                var focus = $(this).data('table') + '-' + $(this).data('focus');

                $('#thrDetailModal').find('.thr-detail-subtitle').text('');
                $('#thrDetailModal').find('.thr-detail-body').html(
                    '<div class="thr-detail-loading"><i class="fas fa-spinner fa-spin mr-1"></i> Đang tải dữ liệu...</div>'
                );
                $('#thrDetailModal').modal('show');

                fetch(THR_URL + '?id=' + encodeURIComponent(id), {
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

                $card.append($('<h6>').text((row.table === 'A' ? 'Nhóm 9 (Bảng A) — ' : 'Nhóm 10 (Bảng B) — ') + row.title));
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
                        ['Mã xuất nhập', 'Ngày nhập', 'Mã danh mục', 'Phòng ban', 'SL nhập', 'Cân đối', 'Đã xuất', 'Tồn còn lại', 'Tồn còn lại (kg)'],
                        row.onhand_rows.map(function(o) {
                            return {
                                cells: [o.ref, o.date, o.category_code, o.department_name, o.imported, o.balanced, o.exported, o.on_hand, o.on_hand_kg]
                            };
                        })
                    ));
                    $onhandWrap.append($('<div>').addClass('thr-detail-note').text(
                        'Tồn thực tế = tổng "Tồn còn lại (kg)" của ' + row.onhand_count + ' mã xuất nhập ở trên = ' + row.total_kg + ' kg.'
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
                        ['Ngày', 'Loại', 'Mã chứng từ', 'Mã danh mục', 'Phòng ban', 'Biến động', 'Biến động (kg)', 'Luỹ kế (kg)'],
                        row.timeline.map(function(t) {
                            return {
                                _peak: t.is_peak,
                                cells: [t.date, t.type_label, t.ref, t.category_code, t.department_name, t.delta, t.delta_kg, t.running_kg]
                            };
                        })
                    ));
                    $peakWrap.append($('<div>').addClass('thr-detail-note').text(
                        'Dựng lại từ ' + row.timeline_count + ' chứng từ đang hiệu lực (' + row.import_count +
                        ' lần nhập, còn lại là xuất / cân đối), cộng dồn theo ngày. Dòng tô vàng là lúc tồn chạm mức cao nhất. ' +
                        'Chứng từ bị khoá về sau không còn trong chuỗi; cùng một ngày thì cộng (nhập) trước, trừ (xuất) sau.'
                    ));
                } else {
                    $peakWrap.append($('<div>').addClass('thr-detail-empty').text('Chưa có chứng từ nào để dựng diễn biến.'));
                }
                $card.append(section('Tồn cao nhất — ' + row.timeline_count + ' chứng từ',
                    row.table + '-peak', wantFocus, $peakWrap));

                return $card;
            }

            function renderThrDetail(data, wantFocus) {
                var $body = $('#thrDetailModal').find('.thr-detail-body').empty();
                $('#thrDetailModal').find('.thr-detail-subtitle')
                    .text('Mã ' + data.category_code + ' · ' + data.chem_name + ' · cảnh báo vàng từ ' + data.warn_percent + '% ngưỡng');

                var cards = [];
                (data.tableA || []).forEach(function(r) {
                    cards.push(r);
                });
                if (data.tableB) cards.push(data.tableB);

                if (!cards.length) {
                    $body.append($('<div>').addClass('thr-detail-empty')
                        .text('Mã danh mục này không thuộc diện đối chiếu ngưỡng PL IV.'));
                    return;
                }

                cards.forEach(function(r) {
                    $body.append(renderCard(r, wantFocus));
                });

                // Cuộn tới đúng phần người dùng bấm vào
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
@endonce
