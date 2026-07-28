<div class="modal fade" id="finishRoutingModal" tabindex="-1" role="dialog"
    aria-labelledby="finishRoutingModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="finishRoutingModalLabel">
                    <i class="fas fa-archive"></i> Bước 3 &mdash; Kết thúc &amp; xác định vị trí lưu
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('pages.documentStorage.routing.finish') }}" method="POST">
                @csrf
                <input type="hidden" name="routing_id" id="finish_routing_id">
                <div class="modal-body">
                    <div class="alert alert-light border mb-3">
                        <b>Hồ sơ:</b> <span id="finish_doc_label"></span>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Kho <span class="text-danger">*</span></label>
                                <select id="finish_warehouse_id" class="form-control" required>
                                    <option value="">-- Chọn Kho --</option>
                                    @foreach ($warehouses as $wh)
                                        <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Phòng <span class="text-danger">*</span></label>
                                <select id="finish_room_id" class="form-control" required disabled>
                                    <option value="">-- Chọn Phòng --</option>
                                    @foreach ($rooms as $room)
                                        <option value="{{ $room->id }}" data-warehouse="{{ $room->warehouse_id }}">
                                            {{ $room->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Kệ <span class="text-danger">*</span></label>
                                <select id="finish_shelf_id" class="form-control" required disabled>
                                    <option value="">-- Chọn Kệ --</option>
                                    @foreach ($shelves as $shelf)
                                        <option value="{{ $shelf->id }}" data-room="{{ $shelf->room_id }}">
                                            {{ $shelf->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Vị trí chi tiết <span class="text-danger">*</span></label>
                                <select name="location_id" id="finish_location_id" class="form-control" required
                                    disabled>
                                    <option value="">-- Chọn Vị trí --</option>
                                    @foreach ($locations as $loc)
                                        <option value="{{ $loc->id }}" data-shelf="{{ $loc->shelf_id }}">
                                            {{ $loc->name }}</option>
                                    @endforeach
                                </select>
                                @if ($errors->finishErrors->has('location_id'))
                                    <span class="d-block text-danger small">{{ $errors->finishErrors->first('location_id') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Ghi chú kết thúc</label>
                        <textarea name="note" class="form-control" rows="2"
                            placeholder="Ghi chú khi đưa hồ sơ vào lưu trữ..."></textarea>
                    </div>

                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i>
                        Sau khi kết thúc, hồ sơ không thể chuyển tiếp nữa và chặng đang chờ (nếu có) sẽ được
                        đánh dấu đã nhận.
                    </small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-archive"></i> Kết thúc &amp; Lưu trữ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Lọc phân cấp Kho -> Phòng -> Kệ -> Vị trí
        const warehouseSelect = $('#finish_warehouse_id');
        const roomSelect = $('#finish_room_id');
        const shelfSelect = $('#finish_shelf_id');
        const locationSelect = $('#finish_location_id');

        warehouseSelect.on('change', function() {
            const whId = $(this).val();
            roomSelect.val('').prop('disabled', !whId);
            roomSelect.find('option').hide().filter((i, el) => !$(el).val() || $(el).attr(
                'data-warehouse') == whId).show();
            shelfSelect.val('').prop('disabled', true);
            locationSelect.val('').prop('disabled', true);
        });

        roomSelect.on('change', function() {
            const roomId = $(this).val();
            shelfSelect.val('').prop('disabled', !roomId);
            shelfSelect.find('option').hide().filter((i, el) => !$(el).val() || $(el).attr('data-room') ==
                roomId).show();
            locationSelect.val('').prop('disabled', true);
        });

        shelfSelect.on('change', function() {
            const shelfId = $(this).val();
            locationSelect.val('').prop('disabled', !shelfId);
            locationSelect.find('option').hide().filter((i, el) => !$(el).val() || $(el).attr(
                'data-shelf') == shelfId).show();
        });
    });
</script>
