@include('pages.shared.maxStockWarn')

@php $bag = $errors->getBag('createErrors'); @endphp

<div class="modal fade md-modal" id="createModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered modal-lg" role="document" style="max-width: 1050px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="{{ $impIcon }}"></i> Nhập Hoá Chất</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($impRoute . 'store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">

                    <div class="form-row">
                        <div class="form-group col-md-12">
                            <label>Hoá Chất <span class="text-danger">*</span></label>
                            <div class="d-flex align-items-center">
                                <button type="button" class="btn btn-outline-info mr-2" style="flex-shrink: 0;" data-toggle="modal" data-target="#selectChemicalModal" title="Mở danh mục hoá chất phòng đang dùng để chọn">
                                    <i class="fas fa-list"></i>
                                </button>
                                <div style="flex: 1 1 auto; min-width: 0;">
                                    <select name="category_id" class="form-control imp-select {{ $bag->has('category_id') ? 'is-invalid' : '' }}"
                                        data-threshold-url="{{ route($impRoute . 'checkThreshold') }}" required>
                                        <option value="">-- Chọn hoá chất phòng đang dùng --</option>
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                                {{ $category->code }} - {{ $category->chem_name }}{{ $category->unit_short_name ? ' (' . $category->unit_short_name . ')' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            @if ($bag->has('category_id'))
                                <span class="md-error d-block mt-1">{{ $bag->first('category_id') }}</span>
                            @endif
                            <small class="md-sub">Chỉ hiện hoá chất phòng đã khai ở tab <b>Hoá Chất Của Phòng</b>. Chất chưa khai thì không nhập vào kho được. Mã xuất nhập (HC + mã phòng ban + chuỗi ngẫu nhiên) được cấp tự động khi bấm Lưu.</small>
                        </div>

                        <div class="col-md-12 mb-3 chem-info-box-wrap" style="display: none;">
                            <div class="alert alert-info py-2 px-3 mb-0 chem-info-box" style="font-size: 0.95rem; line-height: 1.5;"></div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label>Số Lượng <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" inputmode="decimal" name="amount" min="0.0001"
                                    class="form-control js-decimal {{ $bag->has('amount') ? 'is-invalid' : '' }}"
                                    value="{{ old('amount') }}" placeholder="Ví dụ: 25.5" required>
                                <div class="input-group-append"><span class="input-group-text chem-unit">—</span></div>
                            </div>
                            @if ($bag->has('amount'))
                                <span class="md-error">{{ $bag->first('amount') }}</span>
                            @endif
                            <small class="md-sub">Theo đơn vị gốc của hoá chất trong Danh Mục.</small>
                        </div>

                        <div class="form-group col-md-3">
                            <label>Số Lần Nhập <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" min="1" max="50"
                                class="form-control {{ $bag->has('quantity') ? 'is-invalid' : '' }}"
                                value="{{ old('quantity', 1) }}" required>
                            @if ($bag->has('quantity'))
                                <span class="md-error">{{ $bag->first('quantity') }}</span>
                            @endif
                            <small class="md-sub">Tách thành nhiều lô cùng thông tin, mỗi lô một mã xuất nhập.</small>
                        </div>

                        <div class="form-group col-md-12 order-last">
                            <div class="js-max-stock-warn"></div>
                        </div>

                        <div class="form-group col-md-3">
                            <label>Số Lô</label>
                            <input type="text" name="batch_no" maxlength="100"
                                class="form-control {{ $bag->has('batch_no') ? 'is-invalid' : '' }}"
                                value="{{ old('batch_no') }}" placeholder="Ví dụ: LOT-2026-018">
                            @if ($bag->has('batch_no'))
                                <span class="md-error">{{ $bag->first('batch_no') }}</span>
                            @endif
                        </div>

                        <div class="form-group col-md-3">
                            <label>Nhà Cung Cấp</label>
                            <select name="supplier_id" class="form-control imp-select {{ $bag->has('supplier_id') ? 'is-invalid' : '' }}">
                                <option value="">-- Chọn nhà cung cấp --</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                        {{ $supplier->name }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($bag->has('supplier_id'))
                                <span class="md-error">{{ $bag->first('supplier_id') }}</span>
                            @endif
                        </div>

                        <div class="col-md-12 imp-threshold-alerts"></div>
                    </div>

                    <div class="form-row">
                        {{-- Vị trí lưu trữ: chọn cấp sâu nhất, ba cấp Kho/Phòng/Kệ suy ra từ đó --}}
                        <div class="form-group col-md-12">
                            <label>Định Khu Tạm (Biệt Trữ / Chờ Kiểm Tra)</label>
                            <select name="location_id"
                                class="form-control imp-select {{ $bag->has('location_id') ? 'is-invalid' : '' }}">
                                <option value="">-- Chưa định khu --</option>
                                @foreach ($quarantineLocations as $location)
                                    <option value="{{ $location->id }}"
                                        {{ old('location_id') == $location->id ? 'selected' : '' }}>
                                        {{ $location->warehouse_name ?: '—' }} /
                                        {{ $location->shelf_name ?: '—' }} /
                                        {{ $location->column_name ?: '—' }} /
                                        {{ $location->tier_name ?: '—' }} /
                                        {{ $location->code }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($bag->has('location_id'))
                                <span class="md-error">{{ $bag->first('location_id') }}</span>
                            @endif
                            @if ($quarantineLocations->isEmpty())
                                <div class="text-danger small mt-1">
                                    <i class="fas fa-triangle-exclamation mr-1"></i>
                                    Phòng chưa khai vị trí nào thuộc phân loại <b>Biệt Trữ</b>. Vào Dữ Liệu Gốc →
                                    Định Khu, đặt phân loại "Biệt Trữ" cho khu chờ kiểm tra rồi quay lại.
                                </div>
                            @endif
                            <small class="md-sub">Lô mới nhập ở trạng thái <b>Chờ kiểm tra</b> nên chỉ xếp vào khu
                                Biệt Trữ; vị trí lưu trữ thật được định khu lại ở bước Xác nhận kiểm tra.</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Ngày Nhập</label>
                            <input type="text" class="form-control imp-readonly" readonly
                                value="{{ now()->format('d/m/Y') }}">
                            <small class="md-sub">Luôn là ngày bấm Lưu, không sửa được.</small>
                        </div>

                        <div class="form-group col-md-4">
                            <label>Hạn Sử Dụng</label>
                            <input type="date" name="expired_date"
                                class="form-control {{ $bag->has('expired_date') ? 'is-invalid' : '' }}"
                                value="{{ old('expired_date') }}">
                            @if ($bag->has('expired_date'))
                                <span class="md-error">{{ $bag->first('expired_date') }}</span>
                            @endif
                        </div>

                        <div class="form-group col-md-4">
                            <label>Người Nhập</label>
                            <input type="text" class="form-control imp-readonly" readonly
                                value="{{ session('user')['fullName'] }}">
                            <small class="md-sub">Luôn là người đang đăng nhập, không sửa được.</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Số Hoá Đơn</label>
                            <input type="text" name="invoice_number" maxlength="100"
                                class="form-control {{ $bag->has('invoice_number') ? 'is-invalid' : '' }}"
                                value="{{ old('invoice_number') }}" placeholder="Ví dụ: HD-000125">
                            @if ($bag->has('invoice_number'))
                                <span class="md-error">{{ $bag->first('invoice_number') }}</span>
                            @endif
                        </div>

                        <div class="form-group col-md-4">
                            <label>Ngày Hoá Đơn</label>
                            <input type="date" name="invoice_date"
                                class="form-control {{ $bag->has('invoice_date') ? 'is-invalid' : '' }}"
                                value="{{ old('invoice_date') }}">
                            @if ($bag->has('invoice_date'))
                                <span class="md-error">{{ $bag->first('invoice_date') }}</span>
                            @endif
                        </div>

                        <div class="form-group col-md-4">
                            <label>Phân Loại</label>
                            <label class="imp-switch {{ old('is_microbiological_chemicals') ? 'is-checked' : '' }}">
                                <input type="checkbox" name="is_microbiological_chemicals" value="1"
                                    {{ old('is_microbiological_chemicals') ? 'checked' : '' }}>
                                <span>Hoá chất vi sinh</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Tài Liệu Đính Kèm</label>
                        <input type="file" name="attachments[]" class="form-control-file" multiple>
                        <small class="md-sub">Tối đa 10MB / file.</small>
                    </div>

                    <div class="form-group">
                        <label>Ghi Chú</label>
                        <textarea name="note" rows="2" maxlength="500"
                            class="form-control {{ $bag->has('note') ? 'is-invalid' : '' }}"
                            placeholder="Ví dụ: Nhập bổ sung cho đợt kiểm nghiệm tháng 9">{{ old('note') }}</textarea>
                        @if ($bag->has('note'))
                            <span class="md-error">{{ $bag->first('note') }}</span>
                        @endif
                    </div>

                    <div class="md-hint">
                        <i class="fas fa-info-circle mr-1"></i>
                        Phiếu được ghi cho phòng ban <b>{{ session('user')['selected_department'] }}</b>. Mã xuất nhập được
                        cấp tự động khi bấm Lưu.
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

@if ($bag->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('#createModal').modal('show');
        });
    </script>
@endif

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var chemDefaults = @json($categoryDefaults ?? []);

        /*
        | fillLocation = true chỉ khi người dùng tự đổi hoá chất: lúc đó mới điền sẵn
        | định khu phòng đã khai cho hoá chất này. Mở lại modal sau lỗi validate thì
        | giữ nguyên định khu đang chọn, không đè lên thứ người dùng đã nhập.
        */
        /* Cảnh báo trữ quá nhiều: tồn hiện tại + (số lượng x số lần nhập) so với ngưỡng tối đa */
        function checkChemMaxStock($form) {
            var item = chemDefaults[$form.find('select[name="category_id"]').val()] || null;
            var amount = parseFloat(($form.find('[name="amount"]').val() || '').replace(/,/g, ''));
            var times = parseInt($form.find('[name="quantity"]').val(), 10);

            wmsMaxStockWarn($form.find('.js-max-stock-warn'), item, (amount || 0) * (times > 0 ? times : 1));
        }

        $(document).on('input change', '#createModal [name="amount"], #createModal [name="quantity"]', function() {
            checkChemMaxStock($(this).closest('form'));
        });

        function syncChemCategory($sel, fillLocation) {
            var item = chemDefaults[$sel.val()] || null;
            var $form = $sel.closest('form');

            $form.find('.chem-unit').text(item && item.unit ? item.unit : '—');

            if (item && item.info_html) {
                $form.find('.chem-info-box').html(item.info_html);
                $form.find('.chem-info-box-wrap').slideDown('fast');
            } else {
                $form.find('.chem-info-box-wrap').hide();
            }

            if (fillLocation) {
                $form.find('select[name="location_id"]')
                    .val(item && item.location_id ? String(item.location_id) : '')
                    .trigger('change');
            }

            checkChemMaxStock($form);
        }

        $(document).on('change', '#createModal select[name="category_id"]', function() {
            syncChemCategory($(this), true);
        });

        // Mở lại sau lỗi validate: dựng lại hộp thông tin, giữ nguyên định khu người dùng đã chọn
        if ($('#createModal select[name="category_id"]').val()) {
            syncChemCategory($('#createModal select[name="category_id"]'), false);
        }
    });
</script>

{{-- Modal Chọn Hoá Chất --}}
<div class="modal fade" id="selectChemicalModal" tabindex="-1" role="dialog" style="z-index: 1060;" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document" style="max-width: 60%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chọn Hoá Chất</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body p-2">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover w-100" id="tableSelectChemical">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 40px;" class="text-center">STT</th>
                                <th>MÃ HOÁ CHẤT</th>
                                <th>TÊN HOÁ CHẤT</th>
                                <th>SỐ CAS</th>
                                <th>NHÀ SẢN XUẤT</th>
                                <th>TỈ TRỌNG</th>
                                <th>BẢO QUẢN</th>
                                <th>PHÂN LOẠI</th>
                                <th>ĐƠN VỊ</th>
                                <th style="width: 60px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($categories as $category)
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td><strong>{{ $category->code }}</strong></td>
                                    <td>{{ $category->chem_name }}</td>
                                    <td>{{ $category->cas_no ?: '—' }}</td>
                                    <td>
                                        <div class="md-sub">{{ $category->manufacturer_name ?: '—' }}</div>
                                        @if ($category->manufacturer_short_name)
                                            <span class="badge badge-light border">{{ $category->manufacturer_short_name }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $category->density !== null ? $category->density : '—' }}</td>
                                    <td>{{ $category->storage_condition_name ?: '—' }}</td>
                                    <td>
                                        @php $catCls = ($classificationCodes ?? [])[$category->id] ?? []; @endphp
                                        @foreach($catCls as $c)
                                            <span class="badge badge-secondary"
                                                title="{{ ($classificationLabels ?? [])[$c] ?? $c }}">{{ $c }}</span>
                                        @endforeach
                                        @if (\App\Support\ChemicalClassification::isSpecialControl($catCls))
                                            <div class="mt-1">
                                                <span class="badge-special-control" title="Hoá chất kiểm soát đặc biệt (Phụ lục III NĐ 24/2026)">
                                                    <i class="fas fa-shield-alt"></i>Kiểm soát đặc biệt
                                                </span>
                                            </div>
                                        @endif
                                    </td>
                                    <td>{{ $category->unit_short_name }}</td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-primary btn-select-chemical" data-id="{{ $category->id }}">Chọn</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        $('#tableSelectChemical').DataTable({
            pageLength: 10,
            lengthChange: false,
            language: {
                search: "Tìm kiếm:",
                // Phòng chưa khai hoá chất nào thì nói rõ phải khai ở đâu, đừng để bảng trống trơn
                emptyTable: 'Phòng chưa khai hoá chất nào ở tab "Hoá Chất Của Phòng" nên chưa có gì để nhập.',
                zeroRecords: 'Không tìm thấy hoá chất phù hợp trong danh mục của phòng.'
            },
            order: [[0, 'asc']]
        });

        $(document).on('click', '.btn-select-chemical', function() {
            var id = $(this).data('id');
            $('#createModal select[name="category_id"]').val(id).trigger('change');
            $('#selectChemicalModal').modal('hide');
        });
    });
</script>
