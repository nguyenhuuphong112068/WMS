{{--
| Bảng chọn NHIỀU vật tư cùng lúc để đổ vào danh sách đề nghị theo chu kỳ.
| Biến vào: $type (internal|external), $categories (= ô chọn vật tư của modal thêm / sửa)
|
| Mở từ nút ".pr-open-picker" trong modal thêm / sửa; JS ở assets.blade.php ghi nhớ modal
| đang mở, khoá sẵn các vật tư đã có trong danh sách, bấm "Thêm vào danh sách" thì điền vào
| các dòng còn trống trước rồi mới thêm dòng mới.
|
| Nội bộ: vật tư phòng đã khai ở tab "Vật Tư Của Phòng". Liên phòng ban: vật tư đã duyệt
| của danh mục công ty (phòng mình đang thiếu nên mới xin phòng khác).
--}}
@php
    $prKey = $type === 'external' ? 'External' : 'Internal';
    $isExternal = $type === 'external';
@endphp

<div class="modal fade md-modal pr-picker" id="periodic{{ $prKey }}PickerModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog pr-picker-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-th-list"></i>
                    {{ $isExternal ? 'Danh Mục Vật Tư Công Ty' : 'Danh Mục Vật Tư Của Phòng' }}
                </h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <div class="modal-body">
                <div class="d-flex align-items-center justify-content-between flex-wrap mb-3" style="gap: 10px">
                    <div class="input-group pr-picker-search-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" class="form-control pr-picker-search"
                            placeholder="Tìm theo mã, tên vật tư, nhà sản xuất, thông tin kỹ thuật...">
                    </div>
                    <span class="md-tag pr-picker-visible">{{ $categories->count() }} vật tư</span>
                </div>

                <div class="table-responsive pr-picker-wrap">
                    <table class="table table-bordered table-hover table-sm mb-0 pr-picker-table" data-no-datatable>
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 42px">
                                    <input type="checkbox" class="pr-picker-all" title="Chọn tất cả vật tư đang hiện">
                                </th>
                                <th class="text-center" style="width: 50px">STT</th>
                                <th style="width: 110px">Mã Vật Tư</th>
                                <th style="min-width: 220px">Tên Vật Tư</th>
                                <th style="min-width: 160px">Nhà Sản Xuất</th>
                                <th style="min-width: 200px">Thông Tin Kỹ Thuật</th>
                                <th class="text-center" style="width: 80px">Đơn Vị</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($categories as $category)
                                <tr class="pr-picker-row" data-id="{{ $category->id }}"
                                    data-search="{{ mb_strtolower(implode(' ', array_filter([
                                        $category->code,
                                        $category->material_name,
                                        $category->manufacturer_name,
                                        $category->manufacturer_short_name,
                                        $category->technical_specification,
                                    ]))) }}">
                                    <td class="text-center">
                                        <input type="checkbox" class="pr-picker-check" value="{{ $category->id }}">
                                    </td>
                                    <td class="text-center md-sub">{{ $loop->iteration }}</td>
                                    <td><span class="md-tag">{{ $category->code ?: '—' }}</span></td>
                                    <td>
                                        <span class="font-weight-bold">{{ $category->material_name ?: '—' }}</span>
                                        <span class="badge badge-success pr-picker-added">Đã có trong danh sách</span>
                                    </td>
                                    <td class="md-sub">{{ $category->manufacturer_name ?: '—' }}</td>
                                    <td class="md-sub">{{ $category->technical_specification ?: '—' }}</td>
                                    <td class="text-center">{{ $category->unit_short_name ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center md-empty py-4">
                                        {{ $isExternal
                                            ? 'Danh mục công ty chưa có vật tư nào đã duyệt.'
                                            : 'Phòng chưa khai vật tư nào ở tab "Vật Tư Của Phòng".' }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer d-flex justify-content-between align-items-center">
                <span class="pr-picker-selected"><i class="fas fa-check-circle mr-1"></i> Đã chọn: <b>0</b> vật tư</span>
                <div>
                    <button type="button" class="btn btn-light" data-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-primary pr-picker-confirm">
                        <i class="fas fa-plus-circle mr-1"></i> Thêm vào danh sách
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
