<div class="modal fade md-modal" id="requestModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 1280px; width: 95%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-paper-plane mr-1"></i> Tạo Đề Nghị Cấp Phát Chuẩn Cho Tổ</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form action="{{ route('pages.export.standardExport.requestStore') }}" method="POST" autocomplete="off" id="formStdRequest">
                @csrf
                <div class="modal-body">
                    <div class="form-group mb-3" style="max-width: 420px;">
                        <label class="required font-weight-bold"><i class="fas fa-users mr-1"></i> Tổ Đề Nghị</label>
                        <select name="group_id" class="form-control font-weight-bold" required>
                            <option value="">-- Chọn tổ đề nghị --</option>
                            @foreach ($groups as $g)
                                <option value="{{ $g->id }}" {{ old('group_id') == $g->id ? 'selected' : '' }}>
                                    {{ $g->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('group_id', 'requestCreateErrors')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="card mt-2 border">
                        <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                            <span class="font-weight-bold text-primary">
                                <i class="fas fa-list mr-1"></i> Danh Sách Chất Chuẩn Đề Nghị Cấp Phát
                            </span>
                            <button type="button" class="btn btn-sm btn-outline-primary btn-add-req-row">
                                <i class="fas fa-plus mr-1"></i> Thêm chất chuẩn
                            </button>
                        </div>
                        <div class="card-body p-2">
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0" id="tableReqRows" style="font-size: 0.88rem;">
                                    <thead class="bg-light">
                                        <tr class="text-center">
                                            <th style="width: 22%">Chất Chuẩn <span class="text-danger">*</span></th>
                                            <th style="width: 10%">Qui Cách</th>
                                            <th style="width: 9%">Số Lượng ĐN <span class="text-danger">*</span></th>
                                            <th style="width: 8%">ĐVT</th>
                                            <th style="width: 18%">Tên Sản Phẩm</th>
                                            <th style="width: 12%">Chỉ Tiêu</th>
                                            <th style="width: 12%">Kiểm Nghiệm Viên</th>
                                            <th style="width: 6%">Ghi Chú</th>
                                            <th style="width: 3%" class="text-center">#</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="req-row">
                                            <td>
                                                <select name="items[0][category_id]" class="form-control form-control-sm select-req-category" required>
                                                    <option value="">-- Chọn chất chuẩn --</option>
                                                    @foreach ($standardCategories as $cat)
                                                        <option value="{{ $cat->id }}" data-unit="{{ $cat->unit_short_name ?: $cat->unit_name }}">
                                                            {{ $cat->standard_name }} ({{ $cat->code }} v{{ $cat->version }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" list="packSpecList" name="items[0][specification]" class="form-control form-control-sm" placeholder="100 mg...">
                                            </td>
                                            <td>
                                                <input type="number" step="0.0001" min="0.0001" name="items[0][requested_amount]" class="form-control form-control-sm text-right" placeholder="0.0000" required>
                                            </td>
                                            <td>
                                                <input type="text" name="items[0][requested_unit]" class="form-control form-control-sm input-req-unit" placeholder="mg/lọ...">
                                            </td>
                                            <td>
                                                <input type="text" list="productList" name="items[0][product_name]" class="form-control form-control-sm" placeholder="Tên SP...">
                                            </td>
                                            <td>
                                                <input type="text" list="criteriaList" name="items[0][test_criteria]" class="form-control form-control-sm" placeholder="Định tính...">
                                            </td>
                                            <td>
                                                <select name="items[0][analyst_id]" class="form-control form-control-sm">
                                                    <option value="">-- KNV --</option>
                                                    @foreach ($analysts as $an)
                                                        <option value="{{ $an->id }}">{{ $an->name }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" name="items[0][note]" class="form-control form-control-sm" placeholder="Ghi chú...">
                                            </td>
                                            <td class="text-center align-middle">
                                                <button type="button" class="btn btn-xs btn-danger btn-remove-req-row" disabled>
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <datalist id="productList">
                                @foreach ($productNames as $pn)
                                    <option value="{{ $pn->name }}">
                                @endforeach
                            </datalist>

                            <datalist id="packSpecList">
                                @foreach ($packagingSpecs as $ps)
                                    <option value="{{ $ps->name }}">
                                @endforeach
                                <option value="100 mg">
                                <option value="250 mg">
                                <option value="500 mg">
                                <option value="1 g">
                                <option value="20 ml">
                                <option value="250 ml">
                                <option value="500 ml">
                                <option value="1000 ml">
                            </datalist>

                            <datalist id="criteriaList">
                                <option value="Định tính">
                                <option value="Định lượng">
                                <option value="Độ tinh khiết">
                                <option value="Tạp chất liên quan">
                                <option value="Độ tan">
                                <option value="Điểm chảy">
                                <option value="Độ ẩm">
                                <option value="Hiệu chuẩn máy hàng ngày">
                                <option value="Kiểm tra máy hàng ngày">
                            </datalist>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane mr-1"></i> Gửi đề nghị</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var rowIdx = 1;

        $(document).on('change', '.select-req-category', function() {
            var unit = $(this).find('option:selected').data('unit') || '';
            var $unitInput = $(this).closest('tr').find('.input-req-unit');
            if (!$unitInput.val() || unit) {
                $unitInput.val(unit);
            }
        });

        $('.btn-add-req-row').click(function() {
            var $tbody = $('#tableReqRows tbody');
            var $firstRow = $tbody.find('tr:first');
            var $newRow = $firstRow.clone();

            $newRow.find('select, input').each(function() {
                var name = $(this).attr('name');
                if (name) {
                    $(this).attr('name', name.replace(/items\[\d+\]/, 'items[' + rowIdx + ']'));
                }
                $(this).val('');
            });

            $newRow.find('.btn-remove-req-row').prop('disabled', false);
            $tbody.append($newRow);
            rowIdx++;
            updateDeleteButtons();
        });

        $(document).on('click', '.btn-remove-req-row', function() {
            if ($('#tableReqRows tbody tr').length > 1) {
                $(this).closest('tr').remove();
                updateDeleteButtons();
            }
        });

        function updateDeleteButtons() {
            var rows = $('#tableReqRows tbody tr');
            if (rows.length <= 1) {
                rows.find('.btn-remove-req-row').prop('disabled', true);
            } else {
                rows.find('.btn-remove-req-row').prop('disabled', false);
            }
        }
    });
</script>
