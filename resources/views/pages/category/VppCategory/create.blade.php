{{--
| Modal "Thêm Danh Mục VPP Công Ty Mới" dạng bảng: mỗi dòng là một mã VPP (Tên + Nhà sản
| xuất), lưu một lần được nhiều mã - xem VppCategoryController::store(). Mã M00001... cấp
| theo thứ tự dòng lúc lưu, cột Mã chỉ là mã dự kiến.
|
| Hàng "Điền nhanh" ở đầu bảng: chọn Nhà sản xuất / đơn vị / nhóm dự trù là áp cho mọi dòng đang có và làm
| giá trị sẵn của dòng thêm sau. Nút nhân bản chép cả dòng trừ Tên VPP.
--}}
@php
    use App\Support\VppClassification;

    $bag = $errors->getBag('createErrors');

    // Trang có nhiều form dùng chung kho old() (cả modal Khai VPP Cho Phòng cũng gửi items[]):
    // chỉ dựng lại các dòng vừa nhập khi chính form này báo lỗi.
    $mcbRows = $bag->any() ? array_values(array_filter((array) old('items', []), 'is_array')) : [];
    $mcbGroups = VppClassification::formGroups();

    $mcbNameLabels = collect($batchNames ?? [])->mapWithKeys(fn($n) => [(string) $n->id => $n->name])->all();
    $mcbMfrLabels = collect($manufacturers)->mapWithKeys(fn($m) => [(string) $m->id => $m->name])->all();
    $mcbMfrItems = collect($manufacturers)
        ->map(fn($m) => [
            'id' => (string) $m->id,
            'text' => $m->name,
            'search' => trim($m->name . ' ' . ($m->short_name ?? '')),
        ])
        ->values();

    // Gom lỗi thành "Dòng N: ..." để biết dòng nào sai
    $mcbErrors = collect($bag->messages())
        ->map(fn($messages, $key) => preg_match('/^items\.(\d+)\./', $key, $m)
            ? 'Dòng ' . ($m[1] + 1) . ': ' . $messages[0]
            : $messages[0])
        ->unique()
        ->values();
@endphp

@include('pages.category.shared.batchTable')

<div class="modal fade md-modal cbt-modal" id="createModal" tabindex="-1" role="dialog"
    data-next-code="{{ $nextCode }}" data-manufacturers="{{ json_encode($mcbMfrItems, JSON_UNESCAPED_UNICODE) }}">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="{{ $mdIcon }}"></i> Thêm {{ $mdTitle }} Mới</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($mdRoute . 'store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-danger cbt-errors" @if ($mcbErrors->isEmpty()) style="display:none" @endif>
                        <ul>
                            @foreach ($mcbErrors as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="cbt-toolbar">
                        <div class="cbt-toolbar-left">
                            <button type="button" class="btn btn-sm btn-primary btn-cbt-add">
                                <i class="fas fa-plus mr-1"></i> Thêm dòng
                            </button>
                        </div>
                        <span class="cbt-count"></span>
                    </div>

                    <div class="cbt-scroll">
                        <table class="table cbt-table" data-no-datatable style="min-width: 1180px">
                            <colgroup>
                                <col style="width: 44px">
                                <col style="width: 90px">
                                <col>
                                <col style="width: 210px">
                                <col style="width: 170px">
                                @foreach ($mcbGroups as $group)
                                    <col style="width: 300px">
                                @endforeach
                                <col style="width: 84px">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th class="text-center">#</th>
                                    <th>Mã</th>
                                    <th>Tên VPP <span class="text-danger">*</span></th>
                                    <th>Nhà Sản Xuất <span class="text-danger">*</span></th>
                                    <th>Đơn Vị Tính <span class="text-danger">*</span></th>
                                    @foreach ($mcbGroups as $group)
                                        <th>{{ $group['label'] }} <span class="text-danger">*</span></th>
                                    @endforeach
                                    <th class="text-center">Thao Tác</th>
                                </tr>
                                <tr class="cbt-fill">
                                    <th colspan="3">
                                        <span class="cbt-fill-label">
                                            <i class="fas fa-fill-drip"></i> Điền nhanh cho mọi dòng
                                        </span>
                                    </th>
                                    <th>
                                        <select class="form-control cbt-select mcb-mfr" data-fill="manufacturers_id">
                                            <option value="">-- Nhà sản xuất --</option>
                                        </select>
                                    </th>
                                    <th>
                                        <select class="form-control cbt-select" data-fill="unit_id" data-placeholder="-- Đơn vị --">
                                            <option value="">-- Đơn vị --</option>
                                            @foreach ($units as $unit)
                                                <option value="{{ $unit->id }}">{{ $unit->short_name }} - {{ $unit->name }}</option>
                                            @endforeach
                                        </select>
                                    </th>
                                    @foreach ($mcbGroups as $group)
                                        <th>
                                            <select class="form-control" data-fill="{{ $group['errorKey'] }}"
                                                title="{{ $group['label'] }}">
                                                <option value="">--</option>
                                                @foreach ($group['options'] as $optionValue => $optionName)
                                                    <option value="{{ $optionValue }}">{{ $optionName }}</option>
                                                @endforeach
                                            </select>
                                        </th>
                                    @endforeach
                                    <th></th>
                                </tr>
                            </thead>
                            @foreach ($mcbRows as $index => $row)
                                @include('pages.category.VppCategory.createRow', [
                                    'index' => $index,
                                    'row' => $row,
                                    'groups' => $mcbGroups,
                                    'units' => $units,
                                    'nameLabels' => $mcbNameLabels,
                                    'mfrLabels' => $mcbMfrLabels,
                                ])
                            @endforeach
                            <tbody class="cbt-empty" @if (count($mcbRows)) style="display:none" @endif>
                                <tr>
                                    <td colspan="{{ 6 + count($mcbGroups) }}">
                                        <i class="fas fa-cubes"></i>
                                        Chưa có dòng nào. Bấm <b>Thêm dòng</b> để khai mã VPP mới.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <template id="mcbRowTpl">
                        @include('pages.category.VppCategory.createRow', [
                            'index' => '',
                            'row' => [],
                            'groups' => $mcbGroups,
                            'units' => $units,
                            'nameLabels' => [],
                            'mfrLabels' => [],
                        ])
                    </template>

                    <div class="md-hint mt-3 mb-0">
                        <i class="fas fa-info-circle mr-1"></i>
                        Ô chọn chỉ hiển thị dữ liệu gốc <b>đã duyệt</b> và <b>đang hoạt động</b>.
                        Bản ghi mới ở trạng thái <b>Chờ duyệt</b>, cần được duyệt trước khi dùng.
                        Đơn vị tính khai ở đây dùng chung cho mọi phòng.
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

{{-- Modal Cập nhật vẫn một dòng, chia 2 cột (thông tin bên trái, Phân Loại bên phải) --}}
<style>
    @media (min-width: 992px) {
        #updateModal .modal-dialog {
            max-width: 1180px;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        cbtBatch({
            modal: '#createModal',
            template: '#mcbRowTpl',
            local: {
                '.mcb-mfr': $('#createModal').data('manufacturers') || []
            },
            blankStart: true
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
