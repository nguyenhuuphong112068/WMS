{{--
| LẬP ĐỀ NGHỊ VPP TỪ CÁC VẬT TƯ ĐÃ CHỌN Ở TAB "DANH SÁCH VẬT TƯ CẦN ĐỀ NGHỊ HÀNH CHÍNH"
|
| Dòng do JS của watchlistTable (chế độ vpp) nhân bản từ <template class="wl-row-template">.
| Phòng nhận cố định là phòng Hành Chính của công ty ($vppTarget, xem App\Support\MaterialVpp)
| - server tự xác định lại, không nhận phòng gửi lên từ form. Phiếu ghi vào cùng bộ bảng
| material_transfer_requests với đề nghị liên phòng ban nên Hành Chính cấp phát / phòng mình
| nhận hàng y như luồng liên phòng ban.
--}}
@php
    $vppBag = $errors->getBag('vppWatchlistErrors');
@endphp

@if ($vppTarget)
    <div class="modal fade md-modal" id="vppWatchlistModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-paper-plane"></i> Lập Đề Nghị Cấp Phát VPP Đến HC</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>

                <form action="{{ route('pages.export.materialExport.vppWatchlistStore') }}" method="POST">
                    @csrf
                    <input type="hidden" name="action_type" class="vpp-wl-action-type" value="send">

                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>Tiêu Đề Đề Nghị <span class="text-danger">*</span></label>
                                <input type="text" name="title" maxlength="255" required list="matTitleOptionsVpp" autocomplete="off"
                                    class="form-control {{ $vppBag->has('title') ? 'is-invalid' : '' }}"
                                    value="{{ $vppBag->any() ? old('title') : 'Đề nghị văn phòng phẩm ' . now()->format('m/Y') }}">
                                @if ($vppBag->has('title'))
                                    <span class="md-error">{{ $vppBag->first('title') }}</span>
                                @endif
                            </div>

                            <div class="form-group col-md-4">
                                <label>Gửi Đến</label>
                                <div class="form-control vpp-target" tabindex="-1">
                                    <i class="fas fa-building mr-1"></i>{{ $vppTarget->name }}{{ $vppTarget->shortName ? ' (' . $vppTarget->shortName . ')' : '' }}
                                </div>
                            </div>

                            <div class="form-group col-md-2">
                                <label>Ngày Cần Dùng</label>
                                <input type="date" name="needed_date"
                                    class="form-control {{ $vppBag->has('needed_date') ? 'is-invalid' : '' }}"
                                    value="{{ $vppBag->any() ? old('needed_date') : '' }}">
                                @if ($vppBag->has('needed_date'))
                                    <span class="md-error">{{ $vppBag->first('needed_date') }}</span>
                                @endif
                            </div>

                            <div class="form-group col-md-2">
                                <label>Ghi Chú</label>
                                <input type="text" name="note" maxlength="500"
                                    class="form-control {{ $vppBag->has('note') ? 'is-invalid' : '' }}"
                                    value="{{ $vppBag->any() ? old('note') : '' }}">
                                @if ($vppBag->has('note'))
                                    <span class="md-error">{{ $vppBag->first('note') }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered md-table wl-items-table w-100" data-no-datatable>
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width: 45px">STT</th>
                                        <th style="width: 260px">Vật Tư</th>
                                        <th>Quy Cách</th>
                                        <th style="width: 140px">Số Lượng Đề Nghị <span class="text-danger">*</span></th>
                                        <th class="text-center" style="width: 90px">ĐVT</th>
                                        <th style="width: 220px">Ghi Chú</th>
                                    </tr>
                                </thead>
                                <tbody class="wl-items-body"></tbody>
                            </table>
                        </div>

                        <template class="wl-row-template">
                            <tr>
                                <td class="text-center wl-stt"></td>
                                <td>
                                    <div class="font-weight-bold" data-field="display_name"></div>
                                    <input type="hidden" name="items[__INDEX__][category_id]" data-field="category_id">
                                    <input type="hidden" name="items[__INDEX__][requested_unit]" data-field="requested_unit">
                                </td>
                                <td class="md-sub" data-field="display_spec"></td>
                                <td>
                                    <input type="text" inputmode="decimal" required
                                        class="form-control form-control-sm text-right js-decimal" name="items[__INDEX__][requested_amount]"
                                        data-field="requested_amount">
                                </td>
                                <td class="text-center" data-field="display_unit"></td>
                                <td>
                                    <input type="text" maxlength="500" class="form-control form-control-sm"
                                        name="items[__INDEX__][note]" data-field="note">
                                </td>
                            </tr>
                        </template>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                        <button type="submit" class="btn btn-outline-primary btn-vpp-wl-submit" data-action="draft">
                            <i class="fas fa-save mr-1"></i> Lưu tạm
                        </button>
                        <button type="submit" class="btn btn-primary btn-vpp-wl-submit" data-action="send">
                            <i class="fas fa-paper-plane mr-1"></i> Gửi đề nghị
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        #vppWatchlistModal .modal-dialog {
            max-width: min(1200px, 96vw) !important;
            width: 96vw;
            margin: 1.5vh auto;
        }

        #vppWatchlistModal .modal-body {
            padding: 18px 22px;
            max-height: calc(97vh - 140px);
            overflow-y: auto;
        }

        #vppWatchlistModal .vpp-target {
            background: var(--primary-soft);
            color: var(--primary-dark);
            font-weight: 700;
            pointer-events: none;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        #vppWatchlistModal .wl-items-table {
            min-width: 900px;
            margin-bottom: 0;
        }

        #vppWatchlistModal .wl-items-table td {
            vertical-align: middle;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('#vppWatchlistModal').on('shown.bs.modal', function() {
                $(this).find('.wl-items-body tr').each(function(index) {
                    $(this).find('.wl-stt').text(index + 1);
                });
            });

            $(document).on('click', '.btn-vpp-wl-submit', function() {
                $(this).closest('form').find('.vpp-wl-action-type').val($(this).data('action') || 'send');
            });
        });
    </script>
@endif
