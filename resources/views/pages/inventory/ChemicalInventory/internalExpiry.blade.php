@php
    $bag = $errors->getBag('internalExpiryErrors');

    // Form lỗi validate thì modal mở lại, lấy sẵn dòng cũ để các ô chỉ đọc không bị trống
    $invIntRow = $bag->any() ? $datas->firstWhere('id', (int) old('import_id')) : null;
@endphp

<div class="modal fade md-modal" id="internalExpiryModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-hourglass-half"></i> Xác Định Hạn Dùng Nội Bộ</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route('pages.inventory.chemicalInventory.internalExpiry') }}" method="POST">
                @csrf
                <input type="hidden" name="import_id" value="{{ old('import_id') }}">
                {{-- Hai giá trị dưới đây chỉ để JS xem trước kết quả; Controller vẫn tính lại từ DB --}}
                <input type="hidden" name="shelf_life_months" value="{{ $invIntRow->shelf_life_months ?? '' }}">
                <input type="hidden" name="manufacturer_expiry" value="{{ $invIntRow->expired_date ?? '' }}">

                <div class="modal-body">

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Mã Xuất Nhập</label>
                            <input type="text" class="form-control inv-readonly inv-int-code" readonly
                                value="{{ $invIntRow->code ?? '' }}">
                        </div>

                        <div class="form-group col-md-8">
                            <label>Hoá Chất</label>
                            <input type="text" class="form-control inv-readonly inv-int-chem" readonly
                                value="{{ $invIntRow->chem_name ?? '' }}">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Hạn Dùng Mặc Định</label>
                            <input type="text" class="form-control inv-readonly inv-int-months" readonly
                                value="{{ $invIntRow ? $invIntRow->shelf_life_months . ' tháng' : '' }}">
                            <small class="md-sub">Lấy từ Danh Mục Hoá Chất.</small>
                        </div>

                        <div class="form-group col-md-4">
                            <label>Hạn Nhà Sản Xuất</label>
                            <input type="text" class="form-control inv-readonly inv-int-manu" readonly
                                value="{{ $invIntRow ? $invDate($invIntRow->expired_date) : '' }}">
                            <small class="md-sub">Hạn nội bộ không được vượt quá hạn này.</small>
                        </div>

                        <div class="form-group col-md-4">
                            <label>Hạn Nội Bộ Hiện Tại</label>
                            <input type="text" class="form-control inv-readonly inv-int-current" readonly
                                value="{{ $invIntRow ? $invDate($invIntRow->internal_expired_date) : '' }}">
                            <small class="md-sub">Trống nghĩa là chưa xác định lần nào.</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Ngày Xác Định <span class="text-danger">*</span></label>
                            <input type="date" name="determined_date"
                                class="form-control {{ $bag->has('determined_date') ? 'is-invalid' : '' }}"
                                value="{{ old('determined_date', now()->format('Y-m-d')) }}" required>
                            @if ($bag->has('determined_date'))
                                <span class="md-error">{{ $bag->first('determined_date') }}</span>
                            @endif
                            <small class="md-sub">Thường là ngày mở nắp / đưa vào sử dụng.</small>
                        </div>

                        <div class="form-group col-md-6">
                            <label>Hạn Dùng Nội Bộ Sau Khi Xác Định</label>
                            <input type="text" class="form-control inv-readonly inv-int-result" readonly value="">
                            <small class="md-sub inv-int-note"></small>
                        </div>
                    </div>

                    <div class="md-hint">
                        <i class="fas fa-info-circle mr-1"></i>
                        Hạn dùng nội bộ = <b>ngày xác định + số tháng hạn dùng</b> của hoá chất trong Danh Mục. Nếu cộng
                        xong <b>vượt quá hạn của nhà sản xuất</b> thì lấy chính hạn nhà sản xuất. Chỉ hoá chất có khai
                        báo hạn dùng mặc định mới xác định được; xác định lại nhiều lần được, mọi lần đều lưu trong
                        Audit Trail.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Lưu hạn nội bộ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if ($bag->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('#internalExpiryModal').modal('show');
        });
    </script>
@endif
