{{--
| LẬP ĐỀ NGHỊ VPP TỪ CÁC VPP ĐÃ CHỌN Ở TAB "DANH SÁCH VPP CẦN ĐỀ NGHỊ HÀNH CHÍNH"
|
| Dòng do JS của watchlistTable (chế độ vpp) nhân bản từ <template class="wl-row-template">.
| Phòng nhận cố định là phòng Hành Chính của công ty ($vppTarget, xem App\Support\VppStore)
| - server tự xác định lại, không nhận phòng gửi lên từ form. Phiếu ghi vào cùng bộ bảng
| vpp_transfer_requests với đề nghị liên phòng ban nên Hành Chính cấp phát / phòng mình
| nhận hàng y như luồng liên phòng ban.
|
| Hai cách lập (ô mode, nút trên thanh công cụ của bảng chọn sẵn):
|   new    : Tạo đề nghị mới - Tiêu đề / Ngày cần dùng / Ghi chú, Lưu tạm hoặc Gửi đề nghị.
|   append : Thêm vào đề nghị nháp - chọn một phiếu đang Lưu tạm của phòng gửi HC ($vppDrafts),
|            các VPP được thêm vào phiếu đó, phiếu vẫn Nháp.
--}}
@php
    $vppBag = $errors->getBag('vppWatchlistErrors');
    $vppDrafts = $vppDrafts ?? collect();
    $vppWlMode = $vppBag->any() && old('mode') === 'append' && $vppDrafts->isNotEmpty() ? 'append' : 'new';
@endphp

@if ($vppTarget)
    <div class="modal fade md-modal" id="vppWatchlistModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-paper-plane"></i> Lập Đề Nghị Cấp Phát VPP Đến HC</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>

                <form action="{{ route('pages.export.vppExport.vppWatchlistStore') }}" method="POST">
                    @csrf
                    <input type="hidden" name="action_type" class="vpp-wl-action-type" value="send">
                    <input type="hidden" name="mode" class="vpp-wl-mode-input" value="{{ $vppWlMode }}">

                    <div class="modal-body">
                        @if ($vppDrafts->isNotEmpty())
                            <div class="vpp-wl-modes mb-3" role="group">
                                <button type="button" class="btn btn-sm vpp-wl-mode-btn" data-mode="new">
                                    <i class="fas fa-plus mr-1"></i> Tạo đề nghị mới
                                </button>
                                <button type="button" class="btn btn-sm vpp-wl-mode-btn" data-mode="append">
                                    <i class="fas fa-folder-plus mr-1"></i> Thêm vào đề nghị nháp
                                    <span class="badge badge-light ml-1">{{ $vppDrafts->count() }}</span>
                                </button>
                            </div>

                            <div class="form-row vpp-wl-part" data-mode="append">
                                <div class="form-group col-md-8">
                                    <label>Đề Nghị Nháp <span class="text-danger">*</span></label>
                                    <select name="draft_id" required
                                        class="form-control {{ $vppBag->has('draft_id') ? 'is-invalid' : '' }}">
                                        @foreach ($vppDrafts as $draft)
                                            <option value="{{ $draft->id }}" @selected((string) old('draft_id') === (string) $draft->id)>
                                                {{ $draft->code }}{{ $draft->title ? ' - ' . $draft->title : '' }}
                                                · {{ $draft->item_count }} mục
                                                · {{ \Carbon\Carbon::parse($draft->updated_at ?: $draft->created_at)->format('d/m/Y') }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if ($vppBag->has('draft_id'))
                                        <span class="md-error">{{ $vppBag->first('draft_id') }}</span>
                                    @endif
                                </div>

                                <div class="form-group col-md-4">
                                    <label>Gửi Đến</label>
                                    <div class="form-control vpp-target" tabindex="-1">
                                        <i class="fas fa-building mr-1"></i>{{ $vppTarget->name }}{{ $vppTarget->shortName ? ' (' . $vppTarget->shortName . ')' : '' }}
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="form-row vpp-wl-part" data-mode="new">
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
                                        <th style="width: 260px">VPP</th>
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
                        <button type="submit" class="btn btn-outline-primary btn-vpp-wl-submit vpp-wl-part" data-mode="new" data-action="draft">
                            <i class="fas fa-save mr-1"></i> Lưu tạm
                        </button>
                        <button type="submit" class="btn btn-primary btn-vpp-wl-submit vpp-wl-part" data-mode="new" data-action="send">
                            <i class="fas fa-paper-plane mr-1"></i> Gửi đề nghị
                        </button>
                        @if ($vppDrafts->isNotEmpty())
                            <button type="submit" class="btn btn-primary btn-vpp-wl-submit vpp-wl-part" data-mode="append" data-action="draft">
                                <i class="fas fa-folder-plus mr-1"></i> Thêm vào nháp
                            </button>
                        @endif
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

        #vppWatchlistModal .vpp-wl-modes {
            display: inline-flex;
            padding: 3px;
            gap: 3px;
            border-radius: var(--border-radius-md);
            background: var(--primary-soft);
        }

        #vppWatchlistModal .vpp-wl-mode-btn {
            border: none;
            border-radius: 6px;
            color: var(--primary-dark);
            font-weight: 600;
            background: transparent;
            transition: all 0.2s;
        }

        #vppWatchlistModal .vpp-wl-mode-btn.active {
            background: var(--primary);
            color: #fff;
            box-shadow: var(--shadow-sm);
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
        /*
        | Đổi cách lập: hiện phần của cách đang chọn, tắt (disabled) ô của phần còn lại để
        | ô bắt buộc bị ẩn (Tiêu đề / Đề nghị nháp) không chặn gửi form.
        */
        window.vppWlSetMode = function(mode) {
            var $modal = $('#vppWatchlistModal');

            mode = mode === 'append' && $modal.find('select[name="draft_id"]').length ? 'append' : 'new';
            $modal.find('.vpp-wl-mode-input').val(mode);
            $modal.find('.vpp-wl-mode-btn').each(function() {
                $(this).toggleClass('active', $(this).data('mode') === mode);
            });
            $modal.find('.vpp-wl-part').each(function() {
                var on = $(this).data('mode') === mode;

                $(this).toggleClass('d-none', !on);
                $(this).find('input, select, textarea').prop('disabled', !on);
            });
        };

        document.addEventListener('DOMContentLoaded', function() {
            window.vppWlSetMode(@json($vppWlMode));

            $(document).on('click', '#vppWatchlistModal .vpp-wl-mode-btn', function() {
                window.vppWlSetMode($(this).data('mode'));
            });

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
