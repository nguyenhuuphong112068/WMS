@include('pages.category.shared.assets')

<style>
    /* Nguồn của giá trị đang hiệu lực: khai riêng hay lấy mặc định của danh mục */
    .dc-source {
        display: inline-block;
        margin-top: 2px;
        padding: 0 7px;
        border-radius: 999px;
        font-size: 0.68rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .dc-source.is-own {
        background: #DCFCE7;
        color: #15803D;
        border: 1px solid #86EFAC;
    }

    .dc-source.is-default {
        background: var(--primary-soft);
        color: var(--primary-dark);
        border: 1px solid var(--primary-lighter);
    }

    .dc-value {
        font-weight: 700;
        color: var(--text-main);
        white-space: nowrap;
    }

    .dc-value.is-none {
        color: #94A3B8;
        font-weight: 500;
    }

    /* Hoá chất tương kỵ đang nằm cùng phạm vi định khu (config chemical.incompatibility_scope) */
    .dc-incompat {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        margin-top: 4px;
        padding: 1px 8px;
        border-radius: 999px;
        font-size: 0.7rem;
        font-weight: 700;
        white-space: nowrap;
        background: #FEF2F2;
        color: #DC2626;
        border: 1px solid #FCA5A5;
        cursor: help;
        transition: background 0.2s;
    }

    .dc-incompat:hover,
    .dc-incompat.is-active {
        background: #FEE2E2;
        border-color: #DC2626;
    }

    .dc-incompat.is-active {
        box-shadow: 0 0 0 2px rgba(220, 38, 38, 0.18);
    }

    /* Trạng thái đang lọc theo badge tương kỵ */
    .dc-incompat-filter {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 5px 6px 5px 12px;
        border-radius: 999px;
        border: 1px solid #FCA5A5;
        background: #FEF2F2;
        color: #B91C1C;
        font-size: 0.82rem;
        font-weight: 600;
    }

    .dc-incompat-filter[hidden] {
        display: none !important;
    }

    .dc-incompat-filter .btn {
        border-radius: 999px;
        padding: 1px 10px;
        font-size: 0.75rem;
        transition: transform 0.2s;
    }

    .dc-incompat-filter .btn:hover {
        transform: translateY(-1px);
    }

    #dcTable tbody tr.dc-incompat-source > td {
        background: #FEF2F2 !important;
    }
</style>

<div class="card md-card">
    <div class="card-body">

        <div class="md-toolbar">
            <div class="d-flex flex-wrap align-items-center" style="gap: 10px">
                @perm('category_chemical_dept_manage')
                    <button type="button" class="btn btn-primary btn-md-create" data-modal="#dcCreateModal">
                        <i class="fas fa-plus mr-1"></i> Khai hoá chất
                    </button>
                @endperm
                <button type="button" class="btn btn-outline-primary" data-toggle="modal"
                    data-target="#classifyGuideModal">
                    <i class="fas fa-info-circle mr-1"></i> Chú thích Phụ lục &amp; Nhóm
                </button>
                <span class="dc-incompat-filter" id="dcIncompatFilter" hidden>
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>Tương kỵ với <b class="js-incompat-code"></b> (<span class="js-incompat-count"></span> mã)</span>
                    <button type="button" class="btn btn-sm btn-outline-danger js-incompat-clear">
                        <i class="fas fa-times mr-1"></i>Bỏ lọc
                    </button>
                </span>
            </div>

        </div>

        @include('pages.shared.classificationFilter', ['clsTarget' => 'dcTable'])

        <div class="table-responsive">
            <table id="dcTable" class="table table-bordered table-hover w-100 md-table" data-deferred>
                <thead>
                    <tr>
                        <th class="text-center" style="width: 55px">STT</th>
                        <th style="width: 120px">Mã Danh Mục</th>
                        <th>Tên Hoá Chất</th>
                        <th style="width: 130px">Số CAS</th>
                        <th>Nhà Sản Xuất</th>
                        <th class="text-center" style="width: 90px">Đơn Vị</th>
                        <th class="text-center" style="width: 105px">Tỉ Trọng d<br><small>(g/ml)</small></th>
                        <th style="width: 110px">Số Tài Liệu</th>
                        <th style="width: 170px">Phân Loại</th>
                        <th class="text-center" style="width: 135px">Hạn Dùng Nội Bộ<br><small>(tháng)</small>
                        </th>
                        <th class="text-right" style="width: 130px">Ngưỡng Tồn Tối Thiểu</th>
                        <th class="text-right" style="width: 130px">Ngưỡng Tồn Tối Đa</th>
                        <th style="width: 200px">Định Khu</th>
                        <th style="width: 170px">Điều Kiện Bảo Quản</th>
                        <th style="width: 160px">Ghi Chú</th>
                        <th class="text-center" style="width: 100px">Sử Dụng</th>
                        <th class="text-center" style="width: 110px">Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    @deferredRows('dcTable')
                    @foreach ($datas as $row)
                        @php
                            // Nhóm NĐ 24/2026 suy tự động theo mã danh mục (không còn cột classification)
                            $codes = ($classificationCodes ?? [])[$row->category_id] ?? [];
                            // Giá trị đang thực sự có hiệu lực, theo đúng quy tắc fallback của hệ thống
                            $shelfLife = $row->shelf_life_months ?? $row->category_shelf_life_months;
                            $storage = $row->storage_condition_name ?? $row->category_storage_condition_name;
                        @endphp
                        <tr data-classification="{{ implode(',', $codes) }}" data-cat-code="{{ $row->category_code }}">
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td>
                                <span class="md-tag">{{ $row->category_code ?: '—' }}</span>
                                @if ($row->category_type)
                                    <br><span class="md-tag">{{ $row->category_type }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="font-weight-bold">{{ $row->chem_name ?: '—' }}</div>
                            </td>
                            @include('pages.category.shared.casCell', ['casNo' => $row->cas_no])
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
                            <td class="text-center">
                                @if ($row->unit_short_name || $row->unit_name)
                                    <span class="md-tag"
                                        title="{{ $row->unit_name }}">{{ $row->unit_short_name ?: $row->unit_name }}</span>
                                    @if ($row->pack_weight_kg !== null)
                                        <div class="md-sub">1 {{ $row->unit_short_name ?: $row->unit_name }} =
                                            {{ rtrim(rtrim(number_format((float) $row->pack_weight_kg, 8, '.', ''), '0'), '.') }} kg</div>
                                    @endif
                                @else
                                    <span class="md-empty" title="Phòng chưa khai đơn vị tính">—</span>
                                @endif
                            </td>
                            <td class="text-center md-sub">
                                @if ($row->category_density !== null)
                                    {{ rtrim(rtrim($row->category_density, '0'), '.') }}
                                @else
                                    <span class="md-empty">—</span>
                                @endif
                            </td>
                            <td class="md-sub">{{ $row->category_doc_no ?: '—' }}</td>
                            <td>
                                @if ($codes)
                                    <div class="cat-chips">
                                        @foreach ($codes as $code)
                                            <span
                                                class="cat-chip {{ \App\Support\ChemicalClassification::toneOfCode($code) }}"
                                                title="{{ ($classificationLabels ?? [])[$code] ?? $code }}">{{ $code }}</span>
                                        @endforeach
                                    </div>
                                    @if (\App\Support\ChemicalClassification::isSpecialControl($codes))
                                        <div class="mt-1">
                                            <span class="badge-special-control" title="Hoá chất kiểm soát đặc biệt (Phụ lục III NĐ 24/2026)">
                                                <i class="fas fa-shield-alt"></i>Kiểm soát đặc biệt
                                            </span>
                                        </div>
                                    @endif
                                @else
                                    <span class="md-empty">—</span>
                                @endif
                            </td>
                            <td class="text-center" data-order="{{ $shelfLife ?? 0 }}">
                                <span class="dc-value {{ $shelfLife ? '' : 'is-none' }}">
                                    {{ $shelfLife ?: 'Không khai' }}
                                </span>
                                @if ($row->shelf_life_months !== null)
                                    <br>
                                    <span class="dc-source is-own">Riêng phòng</span>
                                @endif
                            </td>
                            <td class="text-right" data-order="{{ $row->min_stock ?? -1 }}">
                                @if ($row->min_stock !== null)
                                    <span class="dc-value">{{ $dcNum($row->min_stock) }}</span>
                                    <span class="md-sub">{{ $row->unit_short_name ?: $row->unit_name }}</span>
                                @else
                                    <span class="dc-value is-none">Theo tỉ lệ 20%</span>
                                @endif
                            </td>
                            <td class="text-right" data-order="{{ $row->max_stock ?? -1 }}">
                                @if ($row->max_stock !== null)
                                    <span class="dc-value">{{ $dcNum($row->max_stock) }}</span>
                                    <span class="md-sub">{{ $row->unit_short_name ?: $row->unit_name }}</span>
                                @else
                                    <span class="md-empty">Chưa khai</span>
                                @endif
                            </td>
                            <td class="md-sub">
                                @if ($row->location_code)
                                    <div class="font-weight-bold">
                                        {{ \App\Support\ZoneType::tag($row->location_code, $row->location_zone_type ?? null) }}
                                    </div>
                                    @if ($incompat = ($dcConflicts ?? [])[$row->id] ?? null)
                                        @php
                                            $incompatTitle = collect($incompat['conflicts'])->take(15)
                                                ->map(fn ($c) => $c['code'] . ' ' . $c['chem_name'] . ': '
                                                    . collect($c['pairs'])->map(fn ($p) => \App\Support\ChemicalCompatibility::shortLabel($p[0]) . ' × ' . \App\Support\ChemicalCompatibility::shortLabel($p[1]))->unique()->implode('; '))
                                                ->implode("\n")
                                                . (count($incompat['conflicts']) > 15 ? "\n… và " . (count($incompat['conflicts']) - 15) . ' hoá chất khác' : '');
                                        @endphp
                                        <span class="dc-incompat" role="button" tabindex="0"
                                            title="{{ $incompatTitle }}&#10;(Bấm để lọc các mã liên quan)"
                                            data-self="{{ $row->category_code }}"
                                            data-codes="{{ collect($incompat['conflicts'])->pluck('code')->implode(',') }}">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            Tương kỵ cùng {{ mb_strtolower($incompat['scope_label']) }} ({{ count($incompat['conflicts']) }})
                                        </span>
                                    @endif
                                @else
                                    <span class="md-empty">Chưa định khu</span>
                                @endif
                            </td>
                            <td class="md-sub">
                                @if ($storage)
                                    <span class="md-note" title="{{ $storage }}">{{ $storage }}</span>
                                    @if ($row->storage_condition_name === null)
                                        <div><span class="dc-source is-default">Theo danh mục</span></div>
                                    @endif
                                @else
                                    <span class="md-empty">—</span>
                                @endif
                            </td>
                            <td class="md-sub">
                                @if ($row->note)
                                    <span class="md-note" title="{{ $row->note }}">{{ $row->note }}</span>
                                @else
                                    <span class="md-empty">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($row->status_id == 1)
                                    <span class="badge badge-success">Đang dùng</span>
                                @else
                                    <span class="badge badge-danger">Đã khoá</span>
                                @endif
                            </td>
                            <td>
                                <div class="md-actions">
                                    @if ($row->status_id == 1)
                                        @perm('category_chemical_dept_manage')
                                            <button type="button" class="btn btn-sm btn-warning btn-md-edit" title="Sửa"
                                                data-modal="#dcUpdateModal"
                                                data-row="{{ json_encode([
                                                    'id' => $row->id,
                                                    'category_id' => $row->category_id,
                                                    'unit_id' => $row->unit_id,
                                                    'pack_weight_kg' => $row->pack_weight_kg,
                                                    'shelf_life_months' => $row->shelf_life_months,
                                                    'min_stock' => $row->min_stock,
                                                    'max_stock' => $row->max_stock,
                                                    'storage_condition_id' => $row->storage_condition_id,
                                                    'default_location_id' => $row->default_location_id,
                                                    'default_location_id_text' => \App\Support\CategoryLookup::locationLabel($row, 'location_code'),
                                                    'default_location_id_zone' => $row->location_zone_type ?? '',
                                                    'note' => $row->note,
                                                    'category_code' => $row->category_code,
                                                    'chem_name' => $row->chem_name,
                                                    'unit' => $row->unit_short_name ?: $row->unit_name,
                                                    'category_shelf_life_months' => $row->category_shelf_life_months,
                                                ]) }}">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        @endperm
                                    @endif

                                    @perm('category_chemical_dept_manage')
                                        <form class="form-md-confirm d-inline" data-require-reason="1" action="{{ route($mdRoute . 'deActive') }}"
                                            method="POST"
                                            data-title="{{ $row->status_id == 1 ? 'Khoá' : 'Mở khoá' }} {{ $mdLabel }}?"
                                            data-text="{{ $row->status_id == 1 ? 'Sau khi khoá, hoá chất' : 'Sau khi mở khoá, hoá chất' }} &quot;{{ $row->chem_name }}&quot; {{ $row->status_id == 1 ? 'sẽ quay về dùng mặc định của danh mục và không còn tính là phòng đang dùng.' : 'sẽ dùng lại cấu hình riêng của phòng.' }}"
                                            data-danger="{{ $row->status_id == 1 ? '1' : '' }}">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $row->id }}">
                                            <button type="submit"
                                                class="btn btn-sm btn-{{ $row->status_id == 1 ? 'secondary' : 'primary' }}"
                                                title="{{ $row->status_id == 1 ? 'Khoá' : 'Mở khoá' }}">
                                                <i class="fas fa-{{ $row->status_id == 1 ? 'lock' : 'unlock' }}"></i>
                                            </button>
                                        </form>
                                    @endperm
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    @enddeferredRows
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Bấm badge "Tương kỵ cùng tầng": bảng chỉ còn mã đang xem + các mã tương kỵ với nó
    document.addEventListener('DOMContentLoaded', function() {
        var incompat = null; // {self: 'C00021', codes: {C00021: true, ...}}
        var $chip = $('#dcIncompatFilter');

        $.fn.dataTable.ext.search.push(function(settings, data, index, rowData) {
            if (settings.nTable.id !== 'dcTable' || !incompat) return true;
            // Dòng nạp sau (data-deferred) chưa có <tr> nên đọc thuộc tính từ dữ liệu dòng
            var code = mdDeferred.rowAttr(rowData, settings.aoData[index].nTr, 'cat-code');
            return !!incompat.codes[code];
        });

        function redraw() {
            if (!$.fn.dataTable.isDataTable('#dcTable')) return;
            var table = $('#dcTable').DataTable();
            table.draw();

            // Chỉ các dòng đã dựng <tr> (deferRender) - sau draw thì dòng đang lọc đều đã có
            var nodes = table.rows().nodes().toArray().filter(Boolean);

            $(nodes).removeClass('dc-incompat-source');
            $('#dcTable .dc-incompat').removeClass('is-active');
            if (incompat) {
                $(nodes).filter('[data-cat-code="' + incompat.self + '"]').addClass('dc-incompat-source')
                    .find('.dc-incompat').addClass('is-active');
            }
        }

        function apply(self, list) {
            if (incompat && incompat.self === self) return clear();
            var codes = {};
            codes[self] = true;
            list.forEach(function(code) { if (code) codes[code] = true; });
            incompat = { self: self, codes: codes };
            $chip.find('.js-incompat-code').text(self);
            $chip.find('.js-incompat-count').text(list.length);
            $chip.prop('hidden', false);
            redraw();
        }

        function clear() {
            incompat = null;
            $chip.prop('hidden', true);
            redraw();
        }

        $(document).on('click keydown', '#dcTable .dc-incompat', function(e) {
            if (e.type === 'keydown' && e.key !== 'Enter' && e.key !== ' ') return;
            e.preventDefault();
            apply(String($(this).data('self')), String($(this).data('codes') || '').split(','));
        });

        $chip.on('click', '.js-incompat-clear', clear);
    });
</script>
