{{--
| Modal XÁC NHẬN KIỂM TRA LÔ HOÁ CHẤT ở bước NHẬN của đề nghị chuyển hoá chất liên phòng ban.
|
| Bước Nhận chính là bước Kiểm tra (cùng cách vật tư): lô nhận chuyển không qua tab "Chờ kiểm tra".
|   - Kiểm tra Đạt   : chọn định khu thật -> transferReceiveStore (lô vào tồn ngay, thông tin lô lấy từ lô nguồn).
|   - Không đạt      : = Từ chối nhận, bắt buộc kết luận -> transferReceiveReject (hoàn tồn phòng gửi).
| Mở bằng nút "Nhận" (.btn-trigger-chem-transfer-receive) trong phiếu chi tiết, JS ở
| transferDetailModal.blade.php. Modal con nằm trên phiếu chi tiết.
--}}
@if (user_can('export_chemical_transfer_receive'))
    <style>
        /* Cùng giao diện modal Xác nhận kiểm tra của màn Nhập (import/shared/assets) */
        #chemReceiveCheckModal .imp-receive-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 12px 20px;
            margin-bottom: 18px;
            padding: 14px 18px;
            border: 1px dashed var(--primary-lighter);
            border-radius: var(--border-radius-md);
            background: var(--primary-soft);
        }

        #chemReceiveCheckModal .imp-receive-info label {
            display: block;
            margin: 0 0 2px;
            font-size: 0.72rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        #chemReceiveCheckModal .imp-receive-info .val {
            font-weight: 700;
            color: var(--text-main);
        }

        #chemReceiveCheckModal .imp-check-result {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        #chemReceiveCheckModal .imp-check-opt {
            display: inline-flex;
            align-items: center;
            margin: 0;
            padding: 9px 18px;
            border: 1px solid #dbe6f2;
            border-radius: var(--border-radius-md);
            background: #fff;
            font-weight: 600;
            color: #64748b;
            cursor: pointer;
            transition: all 0.2s;
        }

        #chemReceiveCheckModal .imp-check-opt input {
            margin-right: 8px;
        }

        #chemReceiveCheckModal .imp-check-opt.is-passed.is-active {
            border-color: #16A34A;
            background: #DCFCE7;
            color: #15803D;
        }

        #chemReceiveCheckModal .imp-check-opt.is-failed.is-active {
            border-color: #DC2626;
            background: #FEE2E2;
            color: #B91C1C;
        }
    </style>

    <div class="modal fade md-modal" id="chemReceiveCheckModal" tabindex="-1" role="dialog" aria-hidden="true"
        style="z-index: 1060;">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document" style="max-width: 860px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-clipboard-check mr-2"></i>Xác nhận kiểm tra lô hoá chất</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="imp-receive-info">
                        <div>
                            <label>Mã phiếu nhập nguồn</label>
                            <div class="val crc-code">—</div>
                        </div>
                        <div>
                            <label>Hoá chất</label>
                            <div class="val crc-chemical">—</div>
                        </div>
                        <div>
                            <label>Số lượng nhận</label>
                            <div class="val crc-amount">—</div>
                        </div>
                        <div>
                            <label>Phòng gửi</label>
                            <div class="val crc-from">—</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Kết quả kiểm tra <span class="text-danger">*</span></label>
                        <div class="imp-check-result">
                            <label class="imp-check-opt is-passed is-active">
                                <input type="radio" name="crc_check_result" value="passed" checked>
                                <i class="{{ \App\Support\CheckStatus::icon('passed') }} mr-1"></i>Kiểm tra Đạt
                            </label>
                            @if (user_can('export_chemical_transfer_return'))
                                <label class="imp-check-opt is-failed">
                                    <input type="radio" name="crc_check_result" value="failed">
                                    <i class="{{ \App\Support\CheckStatus::icon('failed') }} mr-1"></i>Không đạt
                                </label>
                            @endif
                        </div>
                        <small class="text-danger js-crc-hint-failed d-none">
                            <i class="fas fa-triangle-exclamation mr-1"></i>
                            Không đạt = <b>từ chối nhận</b>: không tạo lô cho phòng mình, hoàn tồn cho phòng gửi.
                        </small>
                    </div>

                    <div class="form-group">
                        <label>Kết luận kiểm tra <span class="text-danger js-crc-note-star d-none">*</span></label>
                        <textarea name="check_note" rows="2" maxlength="500" class="form-control"
                            placeholder="Kết luận / lý do (bắt buộc khi Không đạt)..."></textarea>
                    </div>

                    <div class="js-crc-passed-only">
                        {{-- Thông tin lô lấy nguyên từ lô nguồn của phòng gửi, không sửa ở bước Nhận --}}
                        <div class="imp-receive-info">
                            <div>
                                <label>Số lô</label>
                                <div class="val crc-batch-no">—</div>
                            </div>
                            <div>
                                <label>Số hoá đơn</label>
                                <div class="val crc-invoice-number">—</div>
                            </div>
                            <div>
                                <label>Ngày ký hoá đơn</label>
                                <div class="val crc-invoice-date">—</div>
                            </div>
                            <div>
                                <label>Hạn sử dụng</label>
                                <div class="val crc-expired-date">—</div>
                            </div>
                            <div>
                                <label>Hạn dùng nội bộ</label>
                                <div class="val crc-internal-expired-date">—</div>
                            </div>
                        </div>

                        <div class="form-group mb-0">
                            <label>Định khu vị trí lưu trữ thật <span class="text-danger">*</span></label>
                            <select name="dest_location_id" class="form-control wms-zone-select">
                                <option value="">-- Chọn vị trí lưu trữ --</option>
                                @foreach ($transferOwnLocations as $loc)
                                    <option value="{{ $loc->id }}" data-zone="{{ $loc->zone_type ?? '' }}">
                                        {{ $loc->code }}@if ($loc->warehouse_name) — {{ $loc->warehouse_name }} / {{ $loc->shelf_name }} /
                                            {{ $loc->column_name }} / {{ $loc->tier_name }}@endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-success js-crc-submit">
                        <i class="fas fa-clipboard-check mr-1"></i> Xác nhận Kiểm tra Đạt &amp; Nhận
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
