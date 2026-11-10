{{--
| MODAL "KHAI ... CHO PHÒNG" DẠNG BẢNG NHIỀU DÒNG - dùng chung cho Vật Tư / Hoá Chất / Chất Chuẩn Của
| Phòng (DepartmentMaterial|Chemical|Standard/create.blade.php chỉ khai cấu hình rồi include file này).
| Mỗi dòng là một mã của danh mục công ty mà phòng chưa khai; lưu một lần được nhiều mã - xem
| Department*Controller::store() + trait StoresBatchItems.
|
| Mã vào bảng bằng ô tìm nhanh trên thanh công cụ (tìm trên mảng $cfg['items'], chọn liên tục không
| đóng ô) hoặc - với vật tư - bảng chọn nhiều "Chọn nhiều từ danh mục công ty" (gửi sự kiện cbt:add
| [{id, name, meta, unit}] về modal). Mỗi mã chỉ một dòng. Đơn vị tính tự gợi ý theo đơn vị phòng khác
| đang dùng (item.unit), vẫn đổi được.
|
| $cfg:
|   modalId, prefix (dmb|dcb|dsb), title, icon, action (URL lưu), bag (tên bag lỗi), word ('vật tư'...)
|   items          : [{id, text, search, name, meta, unit, shelf}] - mã phòng chưa khai
|   units          : đơn vị tính (unit_group nếu có, để hiện ô Khối lượng quy đổi)
|   classifications: phân loại riêng của phòng (null = không có cột)
|   shelf          : có cột Hạn Dùng Nội Bộ        packWeight: có ô Khối lượng quy đổi (hoá chất)
|   compat         : khung cảnh báo tương kỵ ở định khu (hoá chất - DepartmentChemical/compatibility)
|   locationType   : material|chemical|standard (lọc định khu)
|   locationLabel  : 'path' (mặc định: Kho / Kệ / Cột / Tầng / mã) | 'code' (chỉ mã vị trí)
|   batchLocations : định khu của các dòng vừa báo lỗi (dựng sẵn option)
|   unitsInUse / conversions : như shared/unitConversion
|   picker         : có nút bảng chọn nhiều (DepartmentMaterial/categoryPicker)
|   hint           : HTML dòng nhắc cuối modal         emptyIcon: icon dòng trống
--}}
@php
    $bag = $errors->getBag($cfg['bag']);

    // Trang có nhiều form dùng chung kho old() (modal Thêm danh mục công ty cũng gửi items[]):
    // chỉ dựng lại các dòng vừa nhập khi chính form này báo lỗi.
    $cbtRows = $bag->any() ? array_values(array_filter((array) old('items', []), 'is_array')) : [];

    $codeOnly = ($cfg['locationLabel'] ?? 'path') === 'code';
    $cfg['locationLookupUrl'] = route('pages.category.lookup.locations', array_filter([
        'type' => $cfg['locationType'],
        'label' => $codeOnly ? 'code' : null,
    ]));
    $cfg['locationLabels'] = collect($cfg['batchLocations'] ?? [])
        ->mapWithKeys(fn($l) => [(string) $l->id => [
            'text' => $codeOnly ? $l->code : \App\Support\CategoryLookup::locationLabel($l),
            'zone' => $l->zone_type ?? '',
        ]])
        ->all();

    $cfg['columns'] = 7 + ($cfg['classifications'] !== null ? 1 : 0) + ($cfg['shelf'] ? 1 : 0);
@endphp

@include('pages.category.shared.batchTable')

<div class="modal fade md-modal cbt-modal" id="{{ $cfg['modalId'] }}" tabindex="-1" role="dialog"
    data-categories="{{ json_encode($cfg['items'], JSON_UNESCAPED_UNICODE) }}">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="{{ $cfg['icon'] }}"></i> {{ $cfg['title'] }}</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ $cfg['action'] }}" method="POST">
                @csrf

                <div class="modal-body">
                    @include('pages.category.shared.batchErrors', ['bag' => $bag])

                    <div class="cbt-toolbar">
                        <div class="cbt-toolbar-left">
                            <div class="cbt-quick-wrap">
                                <select class="form-control cbt-quick">
                                    <option value="">Tìm {{ $cfg['word'] }} theo mã, tên, nhà sản xuất để thêm dòng...</option>
                                </select>
                            </div>
                            @if ($cfg['picker'])
                                <button type="button" class="btn btn-sm btn-outline-primary dmc-pick-category"
                                    data-target-form="#{{ $cfg['modalId'] }}">
                                    <i class="fas fa-cubes mr-1"></i> Chọn nhiều từ danh mục công ty
                                </button>
                            @endif
                        </div>
                        <span class="cbt-count"></span>
                    </div>

                    <div class="cbt-scroll">
                        <table class="table cbt-table" data-no-datatable
                            style="min-width: {{ 1180 + ($cfg['classifications'] !== null ? 180 : 0) + ($cfg['shelf'] ? 110 : 0) }}px"
                            data-soft-sync="cuc-{{ $cfg['prefix'] }}" data-units-in-use="{{ json_encode($cfg['unitsInUse']) }}"
                            data-conversions="{{ json_encode($cfg['conversions']) }}">
                            <colgroup>
                                <col style="width: 44px">
                                <col>
                                @if ($cfg['classifications'] !== null)
                                    <col style="width: 180px">
                                @endif
                                <col style="width: 180px">
                                @if ($cfg['shelf'])
                                    <col style="width: 110px">
                                @endif
                                <col style="width: 115px">
                                <col style="width: 115px">
                                <col style="width: 300px">
                                <col style="width: 180px">
                                <col style="width: 52px">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th class="text-center">#</th>
                                    <th>{{ \Illuminate\Support\Str::ucfirst($cfg['word']) }} <span class="text-danger">*</span></th>
                                    @if ($cfg['classifications'] !== null)
                                        <th>Phân Loại Của Phòng</th>
                                    @endif
                                    <th>Đơn Vị Tính <span class="text-danger">*</span></th>
                                    @if ($cfg['shelf'])
                                        <th class="text-right" title="Để trống thì lấy hạn dùng mặc định của danh mục">Hạn Dùng Nội Bộ (tháng)</th>
                                    @endif
                                    <th class="text-right">Tồn Tối Thiểu</th>
                                    <th class="text-right">Tồn Tối Đa</th>
                                    <th>
                                        Định Khu
                                        @if ($cfg['compat'])
                                            <button type="button" class="btn btn-xs btn-link p-0 ml-1" data-toggle="modal"
                                                data-target="#storageChartModal" title="Sơ đồ tương kỵ">
                                                <i class="fas fa-th"></i>
                                            </button>
                                        @endif
                                    </th>
                                    <th>Ghi Chú</th>
                                    <th></th>
                                </tr>
                                <tr class="cbt-fill">
                                    <th colspan="2">
                                        <span class="cbt-fill-label">
                                            <i class="fas fa-fill-drip"></i> Điền nhanh cho mọi dòng
                                        </span>
                                    </th>
                                    @if ($cfg['classifications'] !== null)
                                        <th>
                                            <select class="form-control" data-fill="classification_id">
                                                <option value="">-- Phân loại --</option>
                                                @foreach ($cfg['classifications'] as $classification)
                                                    <option value="{{ $classification->id }}">{{ $classification->name }}</option>
                                                @endforeach
                                            </select>
                                        </th>
                                    @endif
                                    <th>
                                        <select class="form-control cbt-select" data-fill="unit_id" data-placeholder="-- Đơn vị --">
                                            <option value="">-- Đơn vị --</option>
                                            @foreach ($cfg['units'] as $unit)
                                                <option value="{{ $unit->id }}" data-short="{{ $unit->short_name }}">
                                                    {{ $unit->short_name }} - {{ $unit->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </th>
                                    <th colspan="{{ $cfg['shelf'] ? 3 : 2 }}"></th>
                                    <th>
                                        <select class="form-control cbt-select cbt-location" data-fill="default_location_id"
                                            data-zone-filter
                                            data-lookup-url="{{ $cfg['locationLookupUrl'] }}">
                                            <option value="">-- Định khu --</option>
                                        </select>
                                    </th>
                                    <th colspan="2"></th>
                                </tr>
                            </thead>
                            @foreach ($cbtRows as $index => $row)
                                @include('pages.category.shared.deptBatchRow', ['index' => $index, 'row' => $row])
                            @endforeach
                            <tbody class="cbt-empty" @if (count($cbtRows)) style="display:none" @endif>
                                <tr>
                                    <td colspan="{{ $cfg['columns'] }}">
                                        <i class="{{ $cfg['emptyIcon'] }}"></i>
                                        Chưa có {{ $cfg['word'] }} nào. Tìm ở ô phía trên{{ $cfg['picker'] ? ' hoặc bấm' : '' }}
                                        @if ($cfg['picker'])
                                            <b>Chọn nhiều từ danh mục công ty</b>
                                        @endif.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <template id="{{ $cfg['prefix'] }}RowTpl">
                        @include('pages.category.shared.deptBatchRow', ['index' => '', 'row' => []])
                    </template>

                    <div class="md-hint mt-3 mb-0">
                        <i class="fas fa-info-circle mr-1"></i> {!! $cfg['hint'] !!}
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Lưu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@once
    <style>
        .cbt-quick-wrap {
            width: 460px;
            max-width: 100%;
        }

        .cbt-table .cbt-pack .input-group-text {
            font-size: 0.78rem;
            padding: 0 8px;
        }

        .cbt-table .cbt-pack .form-control {
            height: 30px;
        }

        .cbt-table .cbt-compat {
            margin: 0 0 8px;
        }

        /* Bảng nhiều dòng chỉ hiện khung tương kỵ khi có vấn đề - dòng nào cũng một khung xanh thì quá dài */
        .cbt-table .cbt-compat:not(.is-danger):not(.is-warn) {
            display: none !important;
        }
    </style>
@endonce

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var prefix = @json($cfg['prefix']);
        var $modal = $('#' + @json($cfg['modalId']));
        var categories = $modal.data('categories') || [];
        var compat = @json((bool) $cfg['compat']);

        var api = cbtBatch({
            modal: '#' + @json($cfg['modalId']),
            template: '#' + prefix + 'RowTpl',
            initRow: function($root) {
                // Danh sách định khu gửi kèm mã đang khai để server cảnh báo chỗ tương kỵ (hoá chất)
                if (!compat) return;
                $root.find('.cbt-location').each(function() {
                    var $select = $(this);
                    $select.data('lookupParams', function() {
                        var category = $select.closest('tbody.cbt-item').find('.cbt-category').val();
                        return category ? {
                            category_id: category
                        } : {};
                    });
                });
            }
        });
        var $table = api.$table;

        function rowOf(categoryId) {
            return $table.children('tbody.cbt-item').filter(function() {
                return $(this).find('.cbt-category').val() === String(categoryId);
            });
        }

        function flash($item) {
            $item.addClass('is-flash');
            setTimeout(function() {
                $item.removeClass('is-flash');
            }, 1200);
            $item[0].scrollIntoView({
                block: 'nearest'
            });
        }

        /* Ô Khối lượng quy đổi chỉ hiện khi đơn vị của dòng thuộc nhóm đếm / bao bì */
        function refreshUnit($item) {
            var $option = $item.find('.' + prefix + '-unit option:selected');
            $item.find('.cbt-pack').toggle($option.data('group') === 'count')
                .find('.cbt-unit-short').text($option.data('short') || 'đv');
            cucRefresh($item.find('.cuc-box'));
        }

        function checkCompat($item) {
            if (compat && window.dcCompatCheck) dcCompatCheck($item);
        }

        /**
         * Thêm một dòng cho mã { id, name, meta, unit, shelf } - mã đã có trong bảng thì chỉ nháy dòng
         * đó. Trả về số dòng thêm được (0 / 1).
         */
        function addCategory(category) {
            var $existing = rowOf(category.id);
            if ($existing.length) {
                flash($existing);
                return 0;
            }

            var $item = api.newRow();
            $item.find('.cbt-category, .cuc-category').val(category.id);
            $item.find('.cbt-text').val(category.name || '');
            $item.find('.cbt-meta-input').val(category.meta || '');
            $item.find('.cbt-shelf-default').val(category.shelf || '');
            $item.find('.cbt-mat-name').text(category.name || '');
            $item.find('.cbt-mat-meta').text(category.meta || '');
            $item.find('.cbt-shelf').attr('placeholder', category.shelf ? 'DM: ' + category.shelf : 'Theo DM');

            // Đơn vị các phòng khác đang dùng cho mã này được ưu tiên hơn ô Điền nhanh
            var $unit = $item.find('.' + prefix + '-unit');
            if (category.unit && $unit.find('option[value="' + category.unit + '"]').length) {
                $unit.val(String(category.unit)).trigger('change');
            }

            refreshUnit($item);
            checkCompat($item);

            return 1;
        }

        /* Bảng chọn nhiều (vật tư - categoryPicker) gửi danh sách đã tick */
        $modal.on('cbt:add', function(event, list) {
            (list || []).forEach(addCategory);
        });

        /* Ô tìm nhanh: chọn là thêm dòng, ô vẫn mở để chọn tiếp */
        var $quick = $modal.find('.cbt-quick');
        $quick.data('localLookup', categories);
        catSelect2($quick, $modal, {
            closeOnSelect: false
        });
        $quick.on('select2:select', function(event) {
            var data = event.params.data || {};
            if (addCategory(data)) flash(rowOf(data.id));
            $quick.val(null).trigger('change.select2');
        });

        $modal.on('change', '.' + prefix + '-unit', function() {
            refreshUnit($(this).closest('tbody.cbt-item'));
        });

        $modal.on('change', 'tbody.cbt-item .cbt-location', function() {
            checkCompat($(this).closest('tbody.cbt-item'));
        });

        // Dòng dựng lại sau lỗi validate: đợi khối quy đổi / tương kỵ (nạp sau) sẵn sàng
        setTimeout(function() {
            $table.children('tbody.cbt-item').each(function() {
                refreshUnit($(this));
                checkCompat($(this));
            });
        }, 0);
    });
</script>

@if ($bag->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('#' + @json($cfg['modalId'])).modal('show');
        });
    </script>
@endif
