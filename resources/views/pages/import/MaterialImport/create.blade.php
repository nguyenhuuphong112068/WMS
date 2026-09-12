@include('pages.shared.maxStockWarn')

@php $bag = $errors->getBag('createErrors'); @endphp

<div class="modal fade md-modal" id="createModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document" style="max-width: 860px;">
        <div class="modal-content">
            <form method="POST" action="{{ route('pages.import.materialImport.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="{{ $impIcon }} mr-2"></i>Nhập vật tư</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">

                    <div class="form-row">
                        <div class="form-group col-md-12">
                            <label>Vật tư <span class="text-danger">*</span></label>
                            <div class="d-flex align-items-center">
                                <button type="button" class="btn btn-outline-info mr-2" style="flex-shrink: 0;"
                                    data-toggle="modal" data-target="#selectMaterialModal"
                                    title="Mở danh mục vật tư phòng đang dùng để chọn">
                                    <i class="fas fa-list"></i>
                                </button>
                                <div style="flex: 1 1 auto; min-width: 0;">
                                    <select name="category_id"
                                        class="form-control imp-select mi-category {{ $bag->has('category_id') ? 'is-invalid' : '' }}"
                                        data-defaults='@json($categoryDefaults)' required>
                                        <option value="">-- Chọn vật tư phòng đang dùng --</option>
                                        @foreach ($categories as $c)
                                            <option value="{{ $c->id }}"
                                                {{ old('category_id') == $c->id ? 'selected' : '' }}>
                                                {{ $c->material_name }} —
                                                {{ $c->manufacturer_short_name ?: $c->manufacturer_name }}
                                                @if ($c->technical_specification)
                                                    ({{ $c->technical_specification }})
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            @if ($bag->has('category_id'))
                                <div class="md-error text-danger small mt-1">{{ $bag->first('category_id') }}</div>
                            @endif
                            <small class="text-muted">Mã xuất nhập (VT + mã phòng ban + chuỗi ngẫu nhiên) được cấp tự
                                động khi bấm Lưu.</small>
                            <div class="md-hint mi-info mt-1"></div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Số lượng / lô <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" inputmode="decimal" min="0.0001" name="amount"
                                    class="form-control js-decimal {{ $bag->has('amount') ? 'is-invalid' : '' }}"
                                    value="{{ old('amount') }}" required>
                                <div class="input-group-append"><span class="input-group-text mi-unit">—</span></div>
                            </div>
                            @if ($bag->has('amount'))
                                <div class="md-error text-danger small">{{ $bag->first('amount') }}</div>
                            @endif
                        </div>
                        <div class="form-group col-md-4">
                            <label>Số lần nhập</label>
                            <input type="number" min="1" max="50" name="quantity" class="form-control"
                                value="{{ old('quantity', 1) }}">
                            <small class="text-muted">Nhập nhiều lô cùng thông tin, mỗi lô một mã.</small>
                        </div>
                        <div class="form-group col-md-12 order-last">
                            <div class="js-max-stock-warn"></div>
                        </div>

                        <div class="form-group col-md-4">
                            <label>Hạn sử dụng</label>
                            <input type="date" name="expired_date"
                                class="form-control {{ $bag->has('expired_date') ? 'is-invalid' : '' }}"
                                value="{{ old('expired_date') }}">
                            <small class="text-muted">Có thể để trống nếu vật tư không có hạn.</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Số lô</label>
                            <input type="text" name="batch_no" maxlength="100"
                                class="form-control {{ $bag->has('batch_no') ? 'is-invalid' : '' }}"
                                value="{{ old('batch_no') }}" placeholder="Ví dụ: LOT-2026-018">
                            @if ($bag->has('batch_no'))
                                <div class="md-error text-danger small">{{ $bag->first('batch_no') }}</div>
                            @endif
                        </div>
                        <div class="form-group col-md-4">
                            <label>Số hoá đơn</label>
                            <input type="text" name="invoice_number" maxlength="100"
                                class="form-control {{ $bag->has('invoice_number') ? 'is-invalid' : '' }}"
                                value="{{ old('invoice_number') }}" placeholder="Ví dụ: HD-000125">
                            @if ($bag->has('invoice_number'))
                                <div class="md-error text-danger small">{{ $bag->first('invoice_number') }}</div>
                            @endif
                        </div>
                        <div class="form-group col-md-4">
                            <label>Ngày ký hoá đơn</label>
                            <input type="date" name="invoice_date"
                                class="form-control {{ $bag->has('invoice_date') ? 'is-invalid' : '' }}"
                                value="{{ old('invoice_date') }}">
                            @if ($bag->has('invoice_date'))
                                <div class="md-error text-danger small">{{ $bag->first('invoice_date') }}</div>
                            @endif
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-12">
                            <label>Định Khu Tạm (Biệt Trữ / Chờ Kiểm Tra)</label>
                            <select name="location_id"
                                class="form-control imp-select {{ $bag->has('location_id') ? 'is-invalid' : '' }}">
                                <option value="">-- Chưa định khu --</option>
                                @foreach ($quarantineLocations as $loc)
                                    <option value="{{ $loc->id }}"
                                        {{ old('location_id') == $loc->id ? 'selected' : '' }}>
                                        {{ $loc->code }} — {{ $loc->warehouse_name }} /
                                        {{ $loc->shelf_name }} / {{ $loc->column_name }} / {{ $loc->tier_name }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($bag->has('location_id'))
                                <div class="md-error text-danger small">{{ $bag->first('location_id') }}</div>
                            @endif
                            @if ($quarantineLocations->isEmpty())
                                <div class="text-danger small mt-1">
                                    <i class="fas fa-triangle-exclamation mr-1"></i>
                                    Phòng chưa khai vị trí nào thuộc phân loại <b>Biệt Trữ</b>. Vào Dữ Liệu Gốc →
                                    Định Khu, đặt phân loại "Biệt Trữ" cho khu chờ kiểm tra rồi quay lại.
                                </div>
                            @endif
                            <small class="text-muted">Lô mới nhập ở trạng thái <b>Chờ kiểm tra</b> nên chỉ xếp vào khu
                                Biệt Trữ; vị trí lưu trữ thật được định khu lại ở bước Xác nhận kiểm tra.</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Tài liệu đính kèm</label>
                        <input type="file" name="attachments[]" class="form-control-file" multiple>
                        <small class="text-muted">Tối đa 10MB / file.</small>
                    </div>

                    <div class="form-group">
                        <label>Mục đích sử dụng</label>
                        <textarea name="purpose" rows="2" maxlength="500"
                            class="form-control {{ $bag->has('purpose') ? 'is-invalid' : '' }}"
                            placeholder="Mục đích sử dụng...">{{ old('purpose') }}</textarea>
                        @if ($bag->has('purpose'))
                            <div class="md-error text-danger small">{{ $bag->first('purpose') }}</div>
                        @endif
                    </div>

                    <div class="form-group">
                        <label>Ghi chú</label>
                        <textarea name="note" rows="2" maxlength="500" class="form-control">{{ old('note') }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu phiếu
                        nhập</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        /*
        | $fillLocation = true khi người dùng vừa tự đổi vật tư: lúc đó mới điền lại ô vị
        | trí theo định khu. Lúc chỉ mở lại modal (kể cả mở lại sau khi báo lỗi) thì giữ
        | nguyên vị trí đang chọn, không đè lên thứ người dùng đã nhập.
        */
        function syncCat($sel, fillLocation) {
            var defaults = $sel.data('defaults') || {};
            var d = defaults[$sel.val()] || {};
            var $modal = $sel.closest('.modal');
            $modal.find('.mi-unit').text(d.unit_short_name || '—');
            $modal.find('.mi-info').html(d.info_html || '');

            /* Điền sẵn định khu phòng đã khai cho vật tư này; thủ kho vẫn đổi được */
            var $location = $modal.find('select[name="location_id"]');
            if (fillLocation && $location.length) {
                $location.val(d.location_id ? String(d.location_id) : '').trigger('change');
            }

            checkMaxStock($modal);
        }

        /* Cảnh báo trữ quá nhiều: tồn hiện tại + (số lượng x số lần nhập) so với ngưỡng tối đa */
        function checkMaxStock($modal) {
            var defaults = $modal.find('.mi-category').data('defaults') || {};
            var d = defaults[$modal.find('.mi-category').val()] || null;
            var amount = parseFloat(($modal.find('[name="amount"]').val() || '').replace(/,/g, ''));
            var times = parseInt($modal.find('[name="quantity"]').val(), 10);

            wmsMaxStockWarn($modal.find('.js-max-stock-warn'), d, (amount || 0) * (times > 0 ? times : 1));
        }

        $(document).on('input change', '#createModal [name="amount"], #createModal [name="quantity"]', function() {
            checkMaxStock($(this).closest('.modal'));
        });
        $(document).on('change', '#createModal .mi-category', function() {
            syncCat($(this), true);
        });
        $(document).on('click', '.btn-md-create', function() {
            setTimeout(function() {
                syncCat($('#createModal .mi-category'), false);
            }, 60);
        });
        @if ($bag->any())
            $(function() {
                $('#createModal').modal('show');
                syncCat($('#createModal .mi-category'), false);
            });
        @endif
    });
</script>

{{-- Modal Chọn Vật Tư (danh mục vật tư phòng đang dùng) --}}
<div class="modal fade" id="selectMaterialModal" tabindex="-1" role="dialog" style="z-index: 1060;"
    data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document" style="max-width: 60%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chọn Vật Tư</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body p-2">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover w-100" id="tableSelectMaterial">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 40px;" class="text-center">STT</th>
                                <th>VẬT TƯ</th>
                                <th>NHÀ SẢN XUẤT</th>
                                <th>QUY CÁCH</th>
                                <th>PHÂN LOẠI</th>
                                <th>ĐƠN VỊ</th>
                                <th style="width: 60px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($categories as $c)
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td><strong>{{ $c->material_name ?: '—' }}</strong></td>
                                    <td>
                                        <div class="md-sub">{{ $c->manufacturer_name ?: '—' }}</div>
                                        @if ($c->manufacturer_short_name)
                                            <span
                                                class="badge badge-light border">{{ $c->manufacturer_short_name }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $c->technical_specification ?: '—' }}</td>
                                    <td>
                                        @php $cClassification = \App\Support\MaterialClassification::summary($c->classification); @endphp
                                        @if ($cClassification !== '')
                                            <span class="badge badge-secondary">{{ $cClassification }}</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>{{ $c->unit_short_name ?: $c->unit_name ?: '—' }}</td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-primary btn-select-material"
                                            data-id="{{ $c->id }}">Chọn</button>
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
        $('#tableSelectMaterial').DataTable({
            pageLength: 10,
            lengthChange: false,
            language: {
                search: "Tìm kiếm:",
                // Phòng chưa khai vật tư nào thì nói rõ phải khai ở đâu, đừng để bảng trống trơn
                emptyTable: 'Phòng chưa khai vật tư nào ở tab "Vật Tư Của Phòng" nên chưa có gì để nhập.',
                zeroRecords: 'Không tìm thấy vật tư phù hợp trong danh mục của phòng.'
            },
            order: [
                [1, 'asc']
            ]
        });

        $(document).on('click', '.btn-select-material', function() {
            var id = $(this).data('id');
            $('#createModal select[name="category_id"]').val(id).trigger('change');
            $('#selectMaterialModal').modal('hide');
        });
    });
</script>
