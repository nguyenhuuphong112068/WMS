@php
    $bag = $errors->getBag('balancingErrors');
    $invOldRow = $bag->any() ? $datas->firstWhere('id', (int) old('import_id')) : null;
    $invOldUnit = $invOldRow ? ($invOldRow->unit_short_name ?: $invOldRow->unit_name) : '';
@endphp

<div class="modal fade md-modal" id="balancingModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-scale-balanced"></i> Cân Đối Số Lượng Nhập</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route('pages.inventory.materialInventory.balancing') }}" method="POST">
                @csrf
                <input type="hidden" name="import_id" value="{{ old('import_id') }}">
                <input type="hidden" name="current_gap" value="{{ $invOldRow->gap ?? 0 }}">

                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group col-md-5">
                            <label>Mã Lô</label>
                            <input type="text" class="form-control inv-readonly mi-code-view" readonly value="{{ $invOldRow->code ?? '' }}">
                        </div>
                        <div class="form-group col-md-7">
                            <label>Vật Tư</label>
                            <input type="text" class="form-control inv-readonly mi-mat-view" readonly value="{{ $invOldRow->material_name ?? '' }}">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label>Số Lượng Nhập</label>
                            <input type="text" class="form-control inv-readonly mi-imported-view" readonly
                                value="{{ $invOldRow ? $invNum($invOldRow->imported) : '' }}">
                        </div>
                        <div class="form-group col-md-3">
                            <label>Tồn Hiện Tại</label>
                            <input type="text" class="form-control inv-readonly mi-gap-view" readonly
                                value="{{ $invOldRow ? $invNum($invOldRow->gap) . ' ' . $invOldUnit : '' }}">
                            <small class="md-sub">Tồn âm = đã xuất vượt lượng nhập.</small>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Hạn Mức Cân Đối</label>
                            <input type="text" class="form-control inv-readonly mi-limit-view" readonly
                                value="{{ $invOldRow ? '±' . $invNum($invOldRow->balancing_limit) . ' ' . $invOldUnit : '' }}">
                            <small class="md-sub mi-limit-hint">
                                {{ $invOldRow ? 'Lần này nhập từ ' . $invNum($invOldRow->balancing_min_input) . ' đến ' . $invNum($invOldRow->balancing_max_input) . '.' : '' }}
                            </small>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Số Lượng Cân Đối <span class="text-danger">*</span></label>
                            <input type="number" name="balancing_amount" step="0.0001"
                                min="{{ $invOldRow->balancing_min_input ?? '' }}" max="{{ $invOldRow->balancing_max_input ?? '' }}"
                                class="form-control {{ $bag->has('balancing_amount') ? 'is-invalid' : '' }}"
                                value="{{ old('balancing_amount') }}" placeholder="Ví dụ: 0.5 hoặc -0.5" required>
                            @if ($bag->has('balancing_amount'))
                                <span class="md-error text-danger small">{{ $bag->first('balancing_amount') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Người Cân Đối</label>
                            <input type="text" class="form-control inv-readonly" readonly value="{{ session('user')['fullName'] }}">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Thời Điểm Cân Đối <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="balancing_at"
                                class="form-control {{ $bag->has('balancing_at') ? 'is-invalid' : '' }}"
                                value="{{ old('balancing_at', now()->format('Y-m-d\TH:i')) }}" required>
                            @if ($bag->has('balancing_at'))
                                <span class="md-error text-danger small">{{ $bag->first('balancing_at') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="md-hint">
                        <i class="fas fa-info-circle mr-1"></i>
                        Nhập <b>số điều chỉnh</b>, không phải số lượng nhập mới: số dương là nhập thiếu, số âm là nhập dư.
                        <b>Tổng</b> các lần cân đối của một mã lô không vượt <b>±{{ $balancingMaxPercent }}%</b> số lượng nhập.
                        Mọi lần cân đối đều lưu Audit Trail.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu cân đối</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if ($bag->any())
    <script>document.addEventListener('DOMContentLoaded', function () { $('#balancingModal').modal('show'); });</script>
@endif
