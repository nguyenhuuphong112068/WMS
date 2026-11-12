@include('pages.category.shared.assets')

<div class="card md-card">
    <div class="card-body">

        @php
            $dmClassOptions = collect($datas)->filter(fn($r) => $r->classification_id)
                ->unique('classification_id')->sortBy('classification_name')
                ->pluck('classification_name', 'classification_id');
            $dmMakerOptions = collect($datas)->filter(fn($r) => $r->manufacturer_name)
                ->unique('manufacturer_name')->sortBy('manufacturer_name')
                ->pluck('manufacturer_name');
        @endphp

        <div class="md-toolbar">
            <div class="d-flex align-items-center flex-wrap" style="gap: 10px">

                <div class="dm-filter">
                    <label for="dmClassFilter"><i class="fas fa-filter mr-1"></i> Phân loại</label>
                    <select id="dmClassFilter" class="form-control form-control-sm dm-filter-select" multiple
                        data-placeholder="Tất cả">
                        <optgroup label="Phân loại của phòng">
                            <option value="dept:none">Chưa phân loại</option>
                            @foreach ($dmClassOptions as $id => $name)
                                <option value="dept:{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </optgroup>
                        @foreach (\App\Support\VppClassification::formGroups() as $group)
                            @php $filterKey = str_replace('classification.', '', $group['errorKey']); @endphp
                            <optgroup label="{{ $group['label'] }}">
                                @foreach ($group['options'] as $value => $name)
                                    <option value="{{ $filterKey }}:{{ $value }}">{{ $name }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                <div class="dm-filter">
                    <label for="dmMakerFilter">Nhà sản xuất</label>
                    <select id="dmMakerFilter" class="form-control form-control-sm dm-filter-select" multiple
                        data-placeholder="Tất cả">
                        @foreach ($dmMakerOptions as $name)
                            <option value="{{ $name }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="dm-filter">
                    <label for="dmZoneFilter">Định khu</label>
                    <select id="dmZoneFilter" class="form-control form-control-sm">
                        <option value="">Tất cả</option>
                        <option value="yes">Đã định khu</option>
                        <option value="no">Chưa định khu</option>
                    </select>
                </div>

                <div class="dm-filter">
                    <label for="dmStockFilter">Ngưỡng tồn</label>
                    <select id="dmStockFilter" class="form-control form-control-sm">
                        <option value="">Tất cả</option>
                        <option value="yes">Đã khai đủ</option>
                        <option value="no">Chưa khai</option>
                    </select>
                </div>

                <div class="dm-filter">
                    <label for="dmStatusFilter">Sử dụng</label>
                    <select id="dmStatusFilter" class="form-control form-control-sm">
                        <option value="">Tất cả</option>
                        <option value="1">Đang dùng</option>
                        <option value="pending">Chờ duyệt</option>
                        <option value="0">Đã khoá</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table id="dmTable" class="table table-bordered table-hover w-100 md-table">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 46px">STT</th>
                        <th style="width: 100px">Mã VPP</th>
                        <th style="width: 170px">Tên VPP</th>
                        <th style="width: 150px">Nhà Sản Xuất</th>
                        <th style="width: 170px">Nhóm Dự Trù</th>
                        <th style="width: 130px">Phân Loại Của Phòng</th>
                        <th class="text-center" style="width: 65px">Đơn Vị</th>
                        <th class="text-right" style="width: 120px">Ngưỡng Tồn Tối Thiểu</th>
                        <th class="text-right" style="width: 120px">Ngưỡng Tồn Tối Đa</th>
                        <th style="width: 190px">Định Khu</th>
                        <th style="width: 130px">Ghi Chú</th>
                        <th class="text-center" style="width: 90px">Sử Dụng</th>
                        <th class="text-center" style="width: 85px">Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($datas as $row)
                        @php
                            // Dòng phòng không khoá riêng: tình trạng đi theo mã ở danh mục chung
                            $dmState = $row->category_status_id == 0 ? '0' : ($row->category_app_status === 'approved' ? '1' : 'pending');
                        @endphp
                        <tr data-class="dept:{{ $row->classification_id ?: 'none' }} {{ \App\Support\VppClassification::filterKeys($row->category_classification, $row->category_purchasing_department) }}"
                            data-maker="{{ $row->manufacturer_name }}"
                            data-zone="{{ $row->location_code ? 'yes' : 'no' }}"
                            data-stock="{{ $row->min_stock !== null && $row->max_stock !== null ? 'yes' : 'no' }}"
                            data-status="{{ $dmState }}">
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td class="font-weight-bold">{{ $row->category_code ?: '—' }}</td>
                            <td class="font-weight-bold">{{ $row->material_name ?: '—' }}</td>
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
                            <td>
                                @php $dmChips = \App\Support\VppClassification::chips($row->category_classification); @endphp
                                @if ($dmChips)
                                    <div class="cat-chips">
                                        @foreach ($dmChips as $chip)
                                            <span class="cat-chip {{ $chip['class'] }}" title="{{ $chip['label'] }}">{{ $chip['short'] }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="md-empty">Chưa phân loại</span>
                                @endif
                            </td>
                            <td>
                                @if ($row->classification_name)
                                    <span class="md-tag">{{ $row->classification_name }}</span>
                                @else
                                    <span class="md-empty">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($row->unit_short_name || $row->unit_name)
                                    <span class="md-tag"
                                        title="{{ $row->unit_name }}">{{ $row->unit_short_name ?: $row->unit_name }}</span>
                                @else
                                    <span class="md-empty" title="Danh mục công ty chưa khai đơn vị tính">—</span>
                                @endif
                            </td>
                            <td class="text-right" data-order="{{ $row->min_stock ?? -1 }}">
                                @if ($row->min_stock !== null)
                                    <span class="font-weight-bold">{{ $dmNum($row->min_stock) }}</span>
                                    <span class="md-sub">{{ $row->unit_short_name ?: $row->unit_name }}</span>
                                @else
                                    <span class="md-empty">Chưa khai</span>
                                @endif
                            </td>
                            <td class="text-right" data-order="{{ $row->max_stock ?? -1 }}">
                                @if ($row->max_stock !== null)
                                    <span class="font-weight-bold">{{ $dmNum($row->max_stock) }}</span>
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
                                @else
                                    <span class="md-empty">Chưa định khu</span>
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
                                @if ($dmState === '1')
                                    <span class="badge badge-success">Đang dùng</span>
                                @elseif ($dmState === 'pending')
                                    <span class="badge badge-warning" title="Mã chưa được duyệt ở Danh Mục VPP Công Ty">Chờ duyệt</span>
                                @else
                                    <span class="badge badge-danger" title="Mã đã khoá ở Danh Mục VPP Công Ty">Đã khoá</span>
                                @endif
                            </td>
                            <td>
                                <div class="md-actions">
                                    @if ($dmState !== '0')
                                        <button type="button" class="btn btn-sm btn-outline-warning btn-dm-watchlist" title="Đề nghị dự trù VPP"
                                            data-category-id="{{ $row->category_id }}"
                                            data-unit="{{ $row->unit_short_name ?: $row->unit_name }}">
                                            <i class="fas fa-bookmark"></i>
                                        </button>

                                        @perm('category_vpp_dept_manage')
                                            <button type="button" class="btn btn-sm btn-warning btn-md-edit" title="Sửa"
                                                data-modal="#dmUpdateModal"
                                                data-row="{{ json_encode([
                                                    'id' => $row->id,
                                                    'category_id' => $row->category_id,
                                                    'classification_id' => $row->classification_id,
                                                    'unit_short_name' => $row->unit_short_name,
                                                    'unit_name' => $row->unit_name,
                                                    'min_stock' => $row->min_stock,
                                                    'max_stock' => $row->max_stock,
                                                    'default_location_id' => $row->default_location_id,
                                                    'default_location_id_text' => \App\Support\CategoryLookup::locationLabel($row, 'location_code'),
                                                    'default_location_id_zone' => $row->location_zone_type ?? '',
                                                    'note' => $row->note,
                                                    'material_name' => $row->material_name,
                                                    'manufacturer_name' => $row->manufacturer_name,
                                                ]) }}">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        @endperm
                                    @endif

                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    /* Ô lọc nhanh trên thanh công cụ của bảng VPP phòng */
    .dm-filter {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .dm-filter label {
        margin: 0;
        font-size: 0.83rem;
        font-weight: 700;
        color: var(--primary-dark);
        white-space: nowrap;
    }

    .dm-filter .form-control {
        width: auto;
        min-width: 130px;
    }

    .dm-filter .select2-container {
        min-width: 260px;
        max-width: 520px;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        /*
         * ---------- Lọc bảng VPP phòng ----------
         * Chọn nhiều trong cùng một ô: HOẶC; giữa các ô: VÀ.
         */
        var dmWant = { cls: [], maker: [], zone: '', stock: '', status: '' };

        $('.dm-filter-select').select2({
            theme: 'bootstrap4',
            width: 'style',
            placeholder: 'Tất cả',
            closeOnSelect: false,
            allowClear: true
        });

        /*
         * Phân loại: phân loại của phòng (dept:*) + các tiêu chí của danh mục công ty.
         * Cùng một tiêu chí: HOẶC; khác tiêu chí: VÀ.
         */
        function dmMatchClass(keys) {
            var groups = {};
            dmWant.cls.forEach(function(value) {
                var criterion = value.split(':')[0];
                (groups[criterion] = groups[criterion] || []).push(value);
            });

            return Object.keys(groups).every(function(criterion) {
                return groups[criterion].some(function(value) {
                    return keys.indexOf(' ' + value + ' ') !== -1;
                });
            });
        }

        $.fn.dataTable.ext.search.push(function(settings, data, index) {
            if (settings.nTable.id !== 'dmTable') return true;

            var $tr = $(settings.aoData[index].nTr);
            if (dmWant.cls.length && !dmMatchClass(' ' + ($tr.attr('data-class') || '') + ' ')) return false;
            if (dmWant.maker.length && dmWant.maker.indexOf($tr.attr('data-maker') || '') === -1) return false;
            if (dmWant.zone && $tr.attr('data-zone') !== dmWant.zone) return false;
            if (dmWant.stock && $tr.attr('data-stock') !== dmWant.stock) return false;
            if (dmWant.status && $tr.attr('data-status') !== dmWant.status) return false;
            return true;
        });

        $(document).on('change', '#dmClassFilter, #dmMakerFilter, #dmZoneFilter, #dmStockFilter, #dmStatusFilter', function() {
            dmWant.cls = $('#dmClassFilter').val() || [];
            dmWant.maker = $('#dmMakerFilter').val() || [];
            dmWant.zone = $('#dmZoneFilter').val() || '';
            dmWant.stock = $('#dmStockFilter').val() || '';
            dmWant.status = $('#dmStatusFilter').val() || '';
            $('#dmTable').DataTable().draw();
        });

        /*
         | ĐỀ NGHỊ DỰ TRÙ VPP - hỏi số lượng cần đề nghị (theo đơn vị của phòng) + lý do
         | dự trù rồi gửi AJAX ngay. Dòng đã khoá
         | sẽ không hiển thị nút này. Hiện lại ở tab "Danh sách VPP cần dự trù" bên Dự Trù
         | VPP - xem App\Support\VppWatchlist.
         */
        $(document).on('click', '.btn-dm-watchlist', function() {
            var $btn = $(this);
            var categoryId = $btn.data('category-id');

            var unit = $btn.data('unit') || '';

            Swal.fire({
                title: 'Đề nghị dự trù VPP này?',
                html:
                    '<div class="text-left">' +
                        '<label class="font-weight-bold mb-1" for="dmWlQty">Số lượng cần đề nghị <span class="text-danger">*</span></label>' +
                        '<div class="input-group mb-3">' +
                            '<input id="dmWlQty" type="text" inputmode="decimal" class="form-control text-right js-decimal" placeholder="0">' +
                            (unit ? '<div class="input-group-append"><span class="input-group-text"></span></div>' : '') +
                        '</div>' +
                        '<label class="font-weight-bold mb-1" for="dmWlNote">Lý do dự trù <span class="text-danger">*</span></label>' +
                        '<textarea id="dmWlNote" class="form-control" rows="4" maxlength="500" placeholder="Nêu rõ lý do cần dự trù VPP này..."></textarea>' +
                    '</div>',
                showCancelButton: true,
                confirmButtonColor: '#2E7BC4',
                cancelButtonColor: '#94A3B8',
                confirmButtonText: 'Gửi đề nghị',
                cancelButtonText: 'Huỷ',
                didOpen: function(popup) {
                    // Gán bằng .text() để tên đơn vị không bị hiểu thành HTML
                    $(popup).find('.input-group-text').text(unit);
                    $(popup).find('#dmWlQty').trigger('focus');
                },
                preConfirm: function() {
                    var qty = $.trim($('#dmWlQty').val()).replace(',', '.');
                    var note = $.trim($('#dmWlNote').val());

                    if (!qty || isNaN(qty) || parseFloat(qty) <= 0) {
                        Swal.showValidationMessage('Vui lòng nhập số lượng cần đề nghị lớn hơn 0');
                        return false;
                    }
                    if (!note) {
                        Swal.showValidationMessage('Vui lòng nhập lý do dự trù');
                        return false;
                    }
                    return { quantity: qty, note: note };
                }
            }).then(function(result) {
                if (!result.isConfirmed) return;

                $btn.prop('disabled', true);

                $.post('{{ route($mdRoute . "watchlistRemember") }}', {
                    _token: '{{ csrf_token() }}',
                    category_id: categoryId,
                    quantity: result.value.quantity,
                    note: result.value.note
                }).done(function(res) {
                    // VPP đang nằm trong phiếu chưa xong: không tự tắt để người dùng đọc mã phiếu
                    var autoClose = res.success && !res.in_progress;
                    Swal.fire({
                        icon: !res.success ? 'error' : (res.in_progress ? 'info' : 'success'),
                        title: res.success ? 'Đã gửi đề nghị!' : 'Có lỗi xảy ra',
                        text: res.message || '',
                        timer: autoClose ? 2500 : undefined,
                        showConfirmButton: !autoClose
                    });
                }).fail(function() {
                    Swal.fire({ icon: 'error', title: 'Có lỗi xảy ra', text: 'Vui lòng thử lại.' });
                }).always(function() {
                    $btn.prop('disabled', false);
                });
            });
        });
    });
</script>

