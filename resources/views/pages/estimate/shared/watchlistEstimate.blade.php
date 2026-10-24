{{--
| LẬP PHIẾU DỰ TRÙ TỪ CÁC MÃ ĐÃ CHỌN Ở TAB "DANH SÁCH HOÁ CHẤT / CHẤT CHUẨN CẦN DỰ TRÙ"
| (shared/watchlistTable) - cùng cách làm với MaterialEstimate/watchlistEstimate.
|
| Các dòng trong bảng do JS của watchlistTable nhân bản từ <template class="wl-row-template">
| theo đúng những mã người dùng tick chọn. Mỗi mã khai ĐÚNG MỘT dòng số lượng; muốn dự trù
| nhiều tháng thì bổ sung ở trang chi tiết phiếu sau khi lưu.
|
| Biến vào: $wlKind ('chemical' | 'standard'), $estRoute, $estIcon, $nextCode, $units,
|           $groups (chất chuẩn).
--}}
@php
    $wlIsChemical = $wlKind === 'chemical';
    $wlHead = $wlIsChemical ? 'Hoá Chất' : 'Chất Chuẩn';
    $wlGroups = $wlIsChemical ? [] : ($groups ?? config('standard.groups'));
    $wlBag = $errors->getBag('watchlistEstimateErrors');
    $wlMonthNow = (int) old('month', now()->month);
    $wlYearNow = (int) old('year', now()->year);
    $wlPeriodNow = now()->format('Y-m');
@endphp

<div class="modal fade md-modal" id="watchlistEstimateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="{{ $estIcon }}"></i> Lập Phiếu Dự Trù Từ {{ $wlHead }} Đã Chọn</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($estRoute . 'watchlistEstimateStore') }}" method="POST">
                @csrf
                <div class="modal-body">

                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label>Tháng Dự Trù <span class="text-danger">*</span></label>
                            <select name="month" class="form-control {{ $wlBag->has('month') ? 'is-invalid' : '' }}" required>
                                @for ($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" {{ $wlMonthNow == $m ? 'selected' : '' }}>Tháng {{ $m }}</option>
                                @endfor
                            </select>
                            @if ($wlBag->has('month'))
                                <span class="md-error">{{ $wlBag->first('month') }}</span>
                            @endif
                        </div>

                        <div class="form-group col-md-3">
                            <label>Năm <span class="text-danger">*</span></label>
                            <input type="number" name="year" min="2020" max="2100" required
                                class="form-control {{ $wlBag->has('year') ? 'is-invalid' : '' }}"
                                value="{{ $wlYearNow }}">
                            @if ($wlBag->has('year'))
                                <span class="md-error">{{ $wlBag->first('year') }}</span>
                            @endif
                        </div>

                        <div class="form-group col-md-3">
                            <label>Mã Phiếu</label>
                            <input type="text" class="form-control est-readonly" readonly value="{{ $nextCode }}">
                        </div>

                        <div class="form-group col-md-3">
                            <label>Ghi Chú</label>
                            <input type="text" name="note" maxlength="500"
                                class="form-control {{ $wlBag->has('note') ? 'is-invalid' : '' }}"
                                value="{{ old('note') }}" placeholder="Ví dụ: Dự trù bổ sung {{ mb_strtolower($wlHead) }} dưới ngưỡng">
                            @if ($wlBag->has('note'))
                                <span class="md-error">{{ $wlBag->first('note') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered md-table wl-items-table w-100" data-no-datatable>
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 55px">STT</th>
                                    <th style="width: 280px">{{ $wlHead }}</th>
                                    @unless ($wlIsChemical)
                                        <th style="width: 190px">Nhóm Chuẩn Mong Muốn</th>
                                    @endunless
                                    <th style="width: 130px">Số Lượng <span class="text-danger">*</span></th>
                                    <th style="width: 130px">Đơn Vị <span class="text-danger">*</span></th>
                                    <th style="width: 170px">Tháng Cần Dùng <span class="text-danger">*</span></th>
                                    <th>Mục Đích Sử Dụng</th>
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
                                <div class="md-sub" data-field="display_meta"></div>
                                <input type="hidden" name="items[__INDEX__][category_id]" data-field="category_id">
                            </td>
                            @unless ($wlIsChemical)
                                <td>
                                    <select class="form-control form-control-sm" name="items[__INDEX__][group_key]" data-field="group_key">
                                        <option value="">-- Không chỉ định --</option>
                                        @foreach ($wlGroups as $groupKey => $group)
                                            <option value="{{ $groupKey }}">{{ $group['short'] }} - {{ $group['name'] }}</option>
                                        @endforeach
                                    </select>
                                </td>
                            @endunless
                            <td>
                                <input type="number" step="0.0001" min="0.0001" required
                                    class="form-control form-control-sm" name="items[__INDEX__][amount]"
                                    data-field="amount">
                            </td>
                            <td>
                                <select class="form-control form-control-sm" name="items[__INDEX__][unit_id]"
                                    data-field="unit_id" required>
                                    <option value="">-- Chọn --</option>
                                    @foreach ($units as $unit)
                                        <option value="{{ $unit->id }}">{{ $unit->short_name ?: $unit->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="month" required class="form-control form-control-sm"
                                    name="items[__INDEX__][for_month_year]" data-field="for_month_year"
                                    value="{{ $wlPeriodNow }}">
                            </td>
                            <td>
                                <textarea rows="1" maxlength="1000" class="form-control form-control-sm wl-autogrow"
                                    name="items[__INDEX__][purpose]" data-field="purpose"></textarea>
                            </td>
                        </tr>
                    </template>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Lưu phiếu dự trù
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* modal-xl của bản Bootstrap đang dùng không đủ rộng cho bảng - đặt theo id như #itemCreateModal */
    #watchlistEstimateModal .modal-dialog {
        max-width: 90vw !important;
        width: 90vw;
        margin: 2vh auto;
    }

    #watchlistEstimateModal .modal-body {
        padding: 18px 22px;
        max-height: calc(96vh - 140px);
        overflow-y: auto;
    }

    #watchlistEstimateModal .wl-items-table {
        table-layout: fixed;
        min-width: 1100px;
        margin-bottom: 0;
    }

    #watchlistEstimateModal .wl-items-table thead th {
        white-space: nowrap;
        vertical-align: middle;
    }

    #watchlistEstimateModal .wl-items-table td {
        vertical-align: middle;
        height: auto;
        white-space: normal;
        word-break: break-word;
    }

    #watchlistEstimateModal .wl-items-table .form-control {
        width: 100%;
        height: auto;
        min-height: 34px;
        padding: 6px 10px;
        font-size: 0.875rem;
        line-height: 1.45;
    }

    #watchlistEstimateModal .wl-items-table select.form-control {
        padding-right: 26px;
        text-overflow: ellipsis;
    }

    #watchlistEstimateModal .wl-items-table textarea.wl-autogrow {
        resize: none;
        overflow: hidden;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        function wlAutoGrow(el) {
            el.style.height = 'auto';
            el.style.height = el.scrollHeight + 2 + 'px';
        }

        $(document).on('input', '#watchlistEstimateModal .wl-autogrow', function() {
            wlAutoGrow(this);
        });

        // Đánh lại số thứ tự hiển thị mỗi lần modal mở (tên ô đã do JS của bảng đánh số)
        $('#watchlistEstimateModal').on('shown.bs.modal', function() {
            $(this).find('.wl-items-body tr').each(function(index) {
                $(this).find('.wl-stt').text(index + 1);
            });

            $(this).find('.wl-autogrow').each(function() {
                wlAutoGrow(this);
            });
        });
    });
</script>
