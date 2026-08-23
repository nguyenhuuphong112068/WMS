{{--
| SỬ DỤNG - LẬP / SỬA ĐỢT XIN QUYẾT ĐỊNH HUỶ
|
| Hai modal dùng chung một bộ trường, đúng phần đầu và mục 1 "Tổng kết phế phẩm" của
| biểu mẫu QA/F/058-07. Danh sách phế phẩm không gõ tay: lấy từ các phiếu loại bỏ được
| tích chọn ở bảng "Hoá chất chờ huỷ", JS chép id vào các input ẩn của modal Lập đợt.
--}}

@php
    $dspBag = $errors->getBag('disposalErrors');
    $dspEditBag = $errors->getBag('disposalUpdateErrors');
    $dspYears = range(now()->year + 1, now()->year - 3);
@endphp

{{-- ============ LẬP ĐỢT XIN QUYẾT ĐỊNH HUỶ ============ --}}
<div class="modal fade md-modal" id="disposalModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-file-signature"></i> Xin Quyết Định Huỷ Hoá Chất</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($dspRoute . 'store') }}" method="POST">
                @csrf
                <div class="modal-body">

                    {{-- Id các phiếu loại bỏ được tích chọn, JS đổ vào ngay trước khi mở modal --}}
                    <div class="dsp-picked-inputs"></div>

                    <div class="dsp-picked-box">
                        <i class="fas fa-flask-vial mr-1"></i>
                        Gom <b><span class="dsp-picked">0</span></b> phiếu loại bỏ vào một đợt để trình
                        <b>TP. ĐBCL</b> và <b>Ban Giám Đốc</b> một lần.
                        <div class="dsp-picked-list md-sub mt-2"></div>
                        @if ($dspBag->has('export_ids'))
                            <span class="md-error">{{ $dspBag->first('export_ids') }}</span>
                        @endif
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label>Tháng <span class="text-danger">*</span></label>
                            <select name="period_month" class="form-control" required>
                                @for ($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}"
                                        {{ (int) old('period_month', now()->month) === $m ? 'selected' : '' }}>
                                        {{ str_pad($m, 2, '0', STR_PAD_LEFT) }}
                                    </option>
                                @endfor
                            </select>
                            @if ($dspBag->has('period_month'))
                                <span class="md-error">{{ $dspBag->first('period_month') }}</span>
                            @endif
                        </div>

                        <div class="form-group col-md-3">
                            <label>Năm <span class="text-danger">*</span></label>
                            <select name="period_year" class="form-control" required>
                                @foreach ($dspYears as $year)
                                    <option value="{{ $year }}"
                                        {{ (int) old('period_year', now()->year) === $year ? 'selected' : '' }}>
                                        {{ $year }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($dspBag->has('period_year'))
                                <span class="md-error">{{ $dspBag->first('period_year') }}</span>
                            @endif
                        </div>

                        <div class="form-group col-md-6">
                            <label>Bộ Phận Giao Phế Phẩm</label>
                            <input type="text" class="form-control exp-readonly" readonly
                                value="{{ session('user')['selected_department'] }}">
                            <small class="md-sub">Lấy theo phòng ban đang chọn.</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Người Tổng Kết</label>
                            <select name="summarized_by" class="form-control exp-select">
                                <option value="">-- Chọn người tổng kết --</option>
                                @foreach ($checkers as $checker)
                                    <option value="{{ $checker->fullName }}"
                                        {{ old('summarized_by', session('user')['fullName']) === $checker->fullName ? 'selected' : '' }}>
                                        {{ $checker->fullName }} ({{ $checker->userName }})
                                    </option>
                                @endforeach
                            </select>
                            <small class="md-sub">Ký ở mục 1 "Tổng kết phế phẩm" của biểu mẫu.</small>
                        </div>

                        <div class="form-group col-md-6">
                            <label>Ngày Tổng Kết</label>
                            <input type="date" name="summarized_at" class="form-control"
                                value="{{ old('summarized_at', now()->format('Y-m-d')) }}">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Nhân Viên Quản Lý Hoá Chất</label>
                            <select name="chemical_staff" class="form-control exp-select">
                                <option value="">-- Chọn nhân viên --</option>
                                @foreach ($checkers as $checker)
                                    <option value="{{ $checker->fullName }}"
                                        {{ old('chemical_staff') === $checker->fullName ? 'selected' : '' }}>
                                        {{ $checker->fullName }} ({{ $checker->userName }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group col-md-6">
                            <label>Ngày Kiểm Tra</label>
                            <input type="date" name="checked_at" class="form-control"
                                value="{{ old('checked_at') }}">
                            <small class="md-sub">Để trống nếu chưa kiểm tra, điền sau cũng được.</small>
                        </div>
                    </div>

                    <div class="md-hint">
                        <i class="fas fa-info-circle mr-1"></i>
                        Đợt vừa lập ở trạng thái <b>Đang gom phiếu</b>: còn thêm bớt phiếu và sửa thông tin được.
                        Bấm <b>Trình duyệt</b> thì danh sách khoá lại, chờ TP. ĐBCL và Ban Giám Đốc ra quyết định.
                        Đợt huỷ <b>không</b> tác động tồn kho - tồn đã trừ ngay từ lúc lập phiếu loại bỏ.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Lập đợt
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ============ SỬA THÔNG TIN ĐỢT (chỉ khi đang gom phiếu) ============ --}}
<div class="modal fade md-modal" id="disposalEditModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit"></i> Sửa Đợt Huỷ <span class="dsp-edit-code"></span>
                </h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($dspRoute . 'update') }}" method="POST">
                @csrf
                <input type="hidden" name="id">

                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label>Tháng <span class="text-danger">*</span></label>
                            <select name="period_month" class="form-control" required>
                                @for ($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}">{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}</option>
                                @endfor
                            </select>
                            @if ($dspEditBag->has('period_month'))
                                <span class="md-error">{{ $dspEditBag->first('period_month') }}</span>
                            @endif
                        </div>

                        <div class="form-group col-md-3">
                            <label>Năm <span class="text-danger">*</span></label>
                            <select name="period_year" class="form-control" required>
                                @foreach ($dspYears as $year)
                                    <option value="{{ $year }}">{{ $year }}</option>
                                @endforeach
                            </select>
                            @if ($dspEditBag->has('period_year'))
                                <span class="md-error">{{ $dspEditBag->first('period_year') }}</span>
                            @endif
                        </div>

                        <div class="form-group col-md-6">
                            <label>Bộ Phận Giao Phế Phẩm</label>
                            <input type="text" class="form-control exp-readonly" readonly
                                value="{{ session('user')['selected_department'] }}">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Người Tổng Kết</label>
                            <select name="summarized_by" class="form-control exp-select">
                                <option value="">-- Chọn người tổng kết --</option>
                                @foreach ($checkers as $checker)
                                    <option value="{{ $checker->fullName }}">{{ $checker->fullName }}
                                        ({{ $checker->userName }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group col-md-6">
                            <label>Ngày Tổng Kết</label>
                            <input type="date" name="summarized_at" class="form-control">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Nhân Viên Quản Lý Hoá Chất</label>
                            <select name="chemical_staff" class="form-control exp-select">
                                <option value="">-- Chọn nhân viên --</option>
                                @foreach ($checkers as $checker)
                                    <option value="{{ $checker->fullName }}">{{ $checker->fullName }}
                                        ({{ $checker->userName }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group col-md-6">
                            <label>Ngày Kiểm Tra</label>
                            <input type="date" name="checked_at" class="form-control">
                        </div>
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

@if ($dspBag->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('#disposalModal').modal('show');
        });
    </script>
@endif
