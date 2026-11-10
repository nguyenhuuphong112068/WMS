{{--
| Modal "Thêm Danh Mục Hoá Chất Công Ty Mới" dạng bảng: mỗi dòng là một mã hoá chất (Tên + Loại + Nhà
| sản xuất), lưu một lần được nhiều mã - xem ChemicalCategoryController::store(). Mã C00001... cấp theo
| thứ tự dòng lúc lưu, cột Mã chỉ là mã dự kiến.
|
| Hàng "Điền nhanh" (Loại, Nhà sản xuất, Điều kiện bảo quản, Cảnh báo an toàn) áp
| cho mọi dòng đang có và làm giá trị sẵn của dòng thêm sau; nút nhân bản chép cả dòng trừ Tên.
| Nút "Chọn từ dữ liệu gốc" (chemNamePicker) chọn được nhiều tên liên tiếp, mỗi tên điền vào dòng
| còn trống đầu tiên hoặc thêm dòng mới (sự kiện cbt:pick).
--}}
@php
    $bag = $errors->getBag('createErrors');

    // Trang có nhiều form dùng chung kho old() (modal Khai Hoá Chất Cho Phòng cũng gửi items[]):
    // chỉ dựng lại các dòng vừa nhập khi chính form này báo lỗi.
    $ccbRows = $bag->any() ? array_values(array_filter((array) old('items', []), 'is_array')) : [];

    $ccbNameLabels = collect($batchNames ?? [])
        ->mapWithKeys(fn($n) => [(string) $n->id => \App\Support\CategoryLookup::nameLabel($n)])
        ->all();
    $ccbMfrLabels = collect($manufacturers)->mapWithKeys(fn($m) => [(string) $m->id => $m->name])->all();
    $ccbMfrItems = collect($manufacturers)
        ->map(fn($m) => ['id' => (string) $m->id, 'text' => $m->name, 'search' => trim($m->name . ' ' . ($m->short_name ?? ''))])
        ->values();

    // Hình đồ GHS của từng cảnh báo cho ô chọn nhiều
    $ccbSafety = collect($safetyWarnings)
        ->map(fn($label, $code) => [
            'label' => $label,
            'picto' => trim(view('pages.shared.safetyPictogram', ['code' => $code, 'size' => 18])->render()),
        ])
        ->all();

    $ccbRowVars = [
        'nameLabels' => $ccbNameLabels,
        'mfrLabels' => $ccbMfrLabels,
    ];
@endphp

@include('pages.category.shared.batchTable')

<div class="modal fade md-modal cbt-modal" id="createModal" tabindex="-1" role="dialog"
    data-next-code="{{ $nextCode }}" data-manufacturers="{{ json_encode($ccbMfrItems, JSON_UNESCAPED_UNICODE) }}"
    data-safety="{{ json_encode($ccbSafety, JSON_UNESCAPED_UNICODE) }}">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="{{ $mdIcon }}"></i> Thêm {{ $mdTitle }} Mới</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($mdRoute . 'store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    @include('pages.category.shared.batchErrors', ['bag' => $bag])

                    <div class="cbt-toolbar">
                        <div class="cbt-toolbar-left">
                            <button type="button" class="btn btn-sm btn-primary btn-cbt-add">
                                <i class="fas fa-plus mr-1"></i> Thêm dòng
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary cat-pick-chem"
                                data-target-form="#createModal">
                                <i class="fas fa-flask mr-1"></i> Chọn từ dữ liệu gốc
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal"
                                data-target="#storageChartModal">
                                <i class="fas fa-th mr-1"></i> Sơ đồ tương kỵ
                            </button>
                        </div>
                        <span class="cbt-count"></span>
                    </div>

                    <datalist id="ccbTypeList">
                        @foreach ($types as $type)
                            <option value="{{ $type }}"></option>
                        @endforeach
                    </datalist>

                    <div class="cbt-scroll">
                        <table class="table cbt-table" data-no-datatable style="min-width: 1390px">
                            <colgroup>
                                <col style="width: 44px">
                                <col style="width: 90px">
                                <col>
                                <col style="width: 140px">
                                <col style="width: 190px">
                                <col style="width: 95px">
                                <col style="width: 150px">
                                <col style="width: 260px">
                                <col style="width: 84px">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th class="text-center">#</th>
                                    <th>Mã</th>
                                    <th>Tên Hoá Chất <span class="text-danger">*</span></th>
                                    <th>Loại</th>
                                    <th>Nhà Sản Xuất <span class="text-danger">*</span></th>
                                    <th class="text-right">Tỉ Trọng d (g/ml)</th>
                                    <th>Điều Kiện Bảo Quản</th>
                                    <th>Cảnh Báo An Toàn</th>
                                    <th class="text-center">Thao Tác</th>
                                </tr>
                                <tr class="cbt-fill">
                                    <th colspan="3">
                                        <span class="cbt-fill-label">
                                            <i class="fas fa-fill-drip"></i> Điền nhanh cho mọi dòng
                                        </span>
                                    </th>
                                    <th>
                                        <input type="text" class="form-control" list="ccbTypeList" maxlength="30"
                                            data-fill="type" placeholder="Loại">
                                    </th>
                                    <th>
                                        <select class="form-control cbt-select ccb-mfr" data-fill="manufacturers_id">
                                            <option value="">-- Nhà sản xuất --</option>
                                        </select>
                                    </th>
                                    <th></th>
                                    <th>
                                        <select class="form-control" data-fill="storage_condition_id">
                                            <option value="">-- Bảo quản --</option>
                                            @foreach ($storageConditions as $option)
                                                <option value="{{ $option->id }}">{{ $option->name }}</option>
                                            @endforeach
                                        </select>
                                    </th>
                                    <th>
                                        <select class="form-control ccb-safety" multiple data-fill="safety_warning">
                                            @foreach ($safetyWarnings as $code => $name)
                                                <option value="{{ $code }}">{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </th>
                                    <th></th>
                                </tr>
                            </thead>
                            @foreach ($ccbRows as $index => $row)
                                @include('pages.category.ChemicalCategory.createRow', ['index' => $index, 'row' => $row] + $ccbRowVars)
                            @endforeach
                            <tbody class="cbt-empty" @if (count($ccbRows)) style="display:none" @endif>
                                <tr>
                                    <td colspan="9">
                                        <i class="fas fa-flask"></i>
                                        Chưa có dòng nào. Bấm <b>Thêm dòng</b> hoặc <b>Chọn từ dữ liệu gốc</b>.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <template id="ccbRowTpl">
                        @include('pages.category.ChemicalCategory.createRow', ['index' => '', 'row' => [], 'nameLabels' => [], 'mfrLabels' => []])
                    </template>

                    <div class="md-hint mt-3 mb-0">
                        <i class="fas fa-info-circle mr-1"></i>
                        Mã danh mục sinh tự động. Không khai báo trùng tổ hợp <b>Tên hoá chất - Loại - Nhà sản xuất</b>
                        (kể cả giữa các dòng). Nhóm NĐ 24/2026 hiện dưới tên sau khi chọn. Cảnh báo an toàn chọn nhiều
                        được, in ra nhãn lô hàng và dùng để cảnh báo khi xếp chung tầng với hoá chất tương kỵ. Bản ghi mới ở
                        trạng thái <b>Chờ duyệt</b>, cần được duyệt trước khi dùng.
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

<style>
    #createModal .ccb-cls {
        margin-top: 4px;
        font-size: 0.75rem;
    }

    #createModal .ccb-cls .cat-chips {
        gap: 4px;
    }

    #createModal .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        font-size: 0.74rem;
        margin-top: 4px;
    }

    .ccb-safety-opt {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var $modal = $('#createModal');
        var safety = $modal.data('safety') || {};

        function safetyTemplate(item) {
            var info = item.id ? safety[item.id] : null;
            return info ? $('<span class="ccb-safety-opt">').append(info.picto, document.createTextNode(info.label)) : item.text;
        }

        /* Nhóm NĐ 24/2026 của tên đang chọn ở dòng (chemNamePicker nạp sau nên gọi muộn) */
        function renderCls($item, groups) {
            var id = String($item.find('.ccb-name').val() || '');
            $item.find('.ccb-cls').empty().append(id && window.cnpClsChips ? cnpClsChips(id, groups) : null);
        }

        var api = cbtBatch({
            modal: '#createModal',
            template: '#ccbRowTpl',
            local: {
                '.ccb-mfr': $modal.data('manufacturers') || []
            },
            blankStart: true,
            initRow: function($root) {
                $root.find('select.ccb-safety').each(function() {
                    $(this).select2({
                        theme: 'bootstrap4',
                        width: '100%',
                        dropdownParent: $modal,
                        closeOnSelect: false,
                        placeholder: '-- Cảnh báo --',
                        templateResult: safetyTemplate,
                        templateSelection: safetyTemplate
                    });
                });
            }
        });

        $modal.on('change', '.ccb-name', function() {
            var picked = ($(this).select2('data') || [])[0] || {};
            renderCls($(this).closest('tbody.cbt-item'), Array.isArray(picked.groups) ? picked.groups : undefined);
        });

        /* Bảng "Chọn Tên Hoá Chất Từ Dữ Liệu Gốc": mỗi tên điền vào dòng chưa có tên đầu tiên, hết thì thêm dòng */
        $modal.on('cbt:pick', function(event, picked) {
            var $item = api.$table.children('tbody.cbt-item').filter(function() {
                return !$(this).find('.ccb-name').val();
            }).first();
            if (!$item.length) $item = api.newRow();

            var $name = $item.find('.ccb-name');
            wmsEnsureOption($name, picked.id, picked.text);
            $name.val(String(picked.id)).trigger('change.select2');
            renderCls($item, picked.groups);
        });

        setTimeout(function() {
            api.$table.children('tbody.cbt-item').each(function() {
                renderCls($(this));
            });
        }, 0);
    });
</script>

@if ($bag->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('#createModal').modal('show');
        });
    </script>
@endif
