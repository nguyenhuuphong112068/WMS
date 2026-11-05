{{--
| Modal "Thêm Danh Mục Chất Chuẩn Công Ty Mới" dạng bảng: mỗi dòng là một mã chất chuẩn, lưu một lần
| được nhiều mã - xem StandardCategoryController::store(). Mã S00001... cấp theo thứ tự dòng lúc lưu,
| cột Mã chỉ là mã dự kiến. Trùng Tên + Nguồn gốc vẫn thêm được (lên Version mới).
|
| Hàng "Điền nhanh" (Nguồn gốc/NSX, Điều kiện bảo quản, Phân nhóm) áp cho mọi dòng
| đang có và làm giá trị sẵn của dòng thêm sau; nút nhân bản chép cả dòng trừ Tên và Số CAS.
--}}
@php
    $bag = $errors->getBag('createErrors');

    // Trang có nhiều form dùng chung kho old() (modal Khai Chất Chuẩn Cho Phòng cũng gửi items[]):
    // chỉ dựng lại các dòng vừa nhập khi chính form này báo lỗi.
    $scbRows = $bag->any() ? array_values(array_filter((array) old('items', []), 'is_array')) : [];

    $scbRowVars = [
        'nameLabels' => collect($batchNames ?? [])
            ->mapWithKeys(fn($n) => [(string) $n->id => \App\Support\CategoryLookup::nameLabel($n)])
            ->all(),
        'mfrLabels' => collect($manufacturers)->mapWithKeys(fn($m) => [(string) $m->id => $m->name])->all(),
    ];
    $scbMfrItems = collect($manufacturers)
        ->map(fn($m) => ['id' => (string) $m->id, 'text' => $m->name, 'search' => trim($m->name . ' ' . ($m->short_name ?? ''))])
        ->values();
@endphp

@include('pages.category.shared.batchTable')

<div class="modal fade md-modal cbt-modal" id="createModal" tabindex="-1" role="dialog"
    data-next-code="{{ $nextCode }}" data-manufacturers="{{ json_encode($scbMfrItems, JSON_UNESCAPED_UNICODE) }}">
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
                        </div>
                        <span class="cbt-count"></span>
                    </div>

                    <div class="cbt-scroll">
                        <table class="table cbt-table" data-no-datatable style="min-width: 1410px">
                            <colgroup>
                                <col style="width: 44px">
                                <col style="width: 90px">
                                <col>
                                <col style="width: 130px">
                                <col style="width: 190px">
                                <col style="width: 95px">
                                <col style="width: 150px">
                                <col style="width: 180px">
                                <col style="width: 140px">
                                <col style="width: 84px">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th class="text-center">#</th>
                                    <th>Mã</th>
                                    <th>Tên Chất Chuẩn <span class="text-danger">*</span></th>
                                    <th>Số CAS</th>
                                    <th>Nguồn Gốc / NSX <span class="text-danger">*</span></th>
                                    <th class="text-right">Tỷ Trọng (g/ml)</th>
                                    <th>Điều Kiện Bảo Quản</th>
                                    <th title="Chỉ chọn 1 nhóm. Lúc nhập kho, mã ống chuẩn lấy mã nhóm của nhóm được chọn.">
                                        Phân Nhóm Chuẩn <span class="text-danger">*</span>
                                    </th>
                                    <th>Ghi Chú</th>
                                    <th class="text-center">Thao Tác</th>
                                </tr>
                                <tr class="cbt-fill">
                                    <th colspan="4">
                                        <span class="cbt-fill-label">
                                            <i class="fas fa-fill-drip"></i> Điền nhanh cho mọi dòng
                                        </span>
                                    </th>
                                    <th>
                                        <select class="form-control cbt-select scb-mfr" data-fill="manufacturers_id">
                                            <option value="">-- Nguồn gốc / NSX --</option>
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
                                        <select class="form-control" data-fill="groups">
                                            <option value="">-- Phân nhóm --</option>
                                            @foreach ($groups as $code => $info)
                                                <option value="{{ $code }}">{{ $info['no'] }}. {{ $info['name'] }} ({{ $info['short'] }})</option>
                                            @endforeach
                                        </select>
                                    </th>
                                    <th colspan="2"></th>
                                </tr>
                            </thead>
                            @foreach ($scbRows as $index => $row)
                                @include('pages.category.StandardCategory.createRow', ['index' => $index, 'row' => $row] + $scbRowVars)
                            @endforeach
                            <tbody class="cbt-empty" @if (count($scbRows)) style="display:none" @endif>
                                <tr>
                                    <td colspan="10">
                                        <i class="fas fa-vial"></i>
                                        Chưa có dòng nào. Bấm <b>Thêm dòng</b> để khai mã chất chuẩn mới.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <template id="scbRowTpl">
                        @include('pages.category.StandardCategory.createRow', ['index' => '', 'row' => [], 'nameLabels' => [], 'mfrLabels' => []])
                    </template>

                    <div class="md-hint mt-3 mb-0">
                        <i class="fas fa-info-circle mr-1"></i>
                        Mã chất chuẩn sinh tự động dạng <b>S00001</b>. Số CAS điền sẵn theo tên đã chọn, sửa lại được.
                        Phân nhóm chuẩn chọn <b>1 nhóm</b> - mã ống chuẩn lúc nhập kho lấy mã của nhóm này. Bản ghi mới ở
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        cbtBatch({
            modal: '#createModal',
            template: '#scbRowTpl',
            local: {
                '.scb-mfr': $('#createModal').data('manufacturers') || []
            },
            blankStart: true
        });

        /* Đổi tên chất chuẩn thì điền sẵn số CAS của tên đó, nhưng không đè giá trị đã gõ tay */
        $('#createModal').on('change', '.scb-name', function() {
            var $cas = $(this).closest('tbody.cbt-item').find('.scb-cas');
            if ($cas.val().trim() !== '') return;

            var picked = ($(this).select2('data') || [])[0] || {};
            $cas.val(picked.cas_no || '');
        });
    });
</script>

@if ($bag->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('#createModal').modal('show');
        });
    </script>
@endif
