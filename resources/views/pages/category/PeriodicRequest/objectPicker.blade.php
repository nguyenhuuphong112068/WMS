{{--
| Bảng chọn 1 Đối Tượng (dữ liệu gốc consumption_objects) cho danh sách đề nghị nội bộ theo chu kỳ.
| Biến vào: $type (internal|external). Chỉ có ý nghĩa với danh sách nội bộ nên chỉ dựng khi $type = internal.
|
| Danh mục đối tượng có thể lên tới hàng nghìn dòng nên KHÔNG render sẵn ra bảng - bảng luôn
| bắt đầu rỗng, JS ở assets.blade.php gọi AJAX GET pages.category.periodicRequest.objects (50 dòng/
| trang) mỗi khi mở modal, gõ tìm hoặc đổi bộ lọc loại / tần suất; cuộn tới cuối bảng thì tải trang kế.
|
| Mở từ nút ".pr-open-object-picker" trong modal thêm / sửa (form.blade.php); bấm chọn 1 dòng rồi
| "Chọn đối tượng" thì gán vào ô Đối Tượng và bắn change để tự dựng lại tần suất đề nghị theo
| đối tượng đó.
--}}
@if ($type !== 'external')
    @php
        $objectTypes = \App\Http\Controllers\Pages\MaterData\ConsumptionObjectController::typeLabels();
        $objectFrequencies = \App\Http\Controllers\Pages\MaterData\ConsumptionObjectController::FREQUENCIES;
    @endphp

    <div class="modal fade md-modal pr-picker pr-object-picker" id="periodicInternalObjectPickerModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog pr-picker-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-crosshairs"></i> Dữ Liệu Gốc - Đối Tượng
                    </h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>

                <div class="modal-body">
                    <div class="d-flex align-items-center flex-wrap mb-3" style="gap: 10px">
                        <div class="input-group pr-picker-search-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                            </div>
                            <input type="text" class="form-control pr-object-picker-search"
                                placeholder="Tìm theo mã, tên, vị trí đối tượng...">
                        </div>

                        <select class="form-control pr-object-picker-type" style="max-width: 220px">
                            <option value="">-- Tất cả loại --</option>
                            @foreach ($objectTypes as $typeKey => $typeLabel)
                                <option value="{{ $typeKey }}">{{ $typeLabel }}</option>
                            @endforeach
                        </select>

                        <select class="form-control pr-object-picker-frequency" style="max-width: 200px">
                            <option value="">-- Tất cả tần suất --</option>
                            @foreach ($objectFrequencies as $freqKey => $freqLabel)
                                <option value="{{ $freqKey }}">{{ $freqLabel }}</option>
                            @endforeach
                        </select>

                        <span class="md-tag pr-object-picker-visible ml-auto">0 đối tượng</span>
                    </div>

                    <div class="table-responsive pr-picker-wrap">
                        <table class="table table-bordered table-hover table-sm mb-0 pr-picker-table" data-no-datatable>
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 42px"></th>
                                    <th class="text-center" style="width: 50px">STT</th>
                                    <th style="width: 110px">Mã Đối Tượng</th>
                                    <th style="min-width: 220px">Tên Đối Tượng</th>
                                    <th style="min-width: 150px">Loại</th>
                                    <th style="min-width: 150px">Vị Trí</th>
                                    <th style="min-width: 160px">Tần Suất</th>
                                </tr>
                            </thead>
                            {{-- JS đổ dòng qua AJAX (prObjectPickerLoad) - xem assets.blade.php --}}
                            <tbody class="pr-object-picker-tbody"></tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-footer d-flex justify-content-between align-items-center">
                    <span class="pr-picker-selected"><i class="fas fa-check-circle mr-1"></i> Đã chọn: <b>0</b> đối tượng</span>
                    <div>
                        <button type="button" class="btn btn-light" data-dismiss="modal">Đóng</button>
                        <button type="button" class="btn btn-primary pr-object-picker-confirm">
                            <i class="fas fa-check mr-1"></i> Chọn đối tượng
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
