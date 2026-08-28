{{-- Một modal cho mỗi đề nghị đang Nháp / Bị từ chối. Biến vào: $req, $items --}}
<div class="modal fade md-modal" id="reqEditModal_{{ $req->id }}" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 92vw;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Sửa Đề Nghị {{ $req->code }}</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form action="{{ route($expRoute . 'requestUpdate') }}" method="POST">
                @csrf
                <input type="hidden" name="request_list_id" value="{{ $req->id }}">
                <input type="hidden" name="action_type" class="me-edit-action" value="draft">
                <div class="modal-body">

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Tổ đề nghị <span class="text-danger">*</span></label>
                            <select name="group_id" class="form-control" required>
                                @foreach ($groups as $g)
                                    <option value="{{ $g->id }}" {{ $req->group_id == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-4 d-flex align-items-end">
                            <label class="mb-2">
                                <input type="checkbox" name="needs_director" value="1" {{ $req->needs_director ? 'checked' : '' }}>
                                Cần Ban Giám Đốc phê duyệt
                            </label>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Ghi chú chung</label>
                            <input type="text" name="note" maxlength="500" class="form-control" value="{{ $req->note }}">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <b>Danh sách vật tư đề nghị</b>
                        <button type="button" class="btn btn-sm btn-outline-primary me-edit-add"><i class="fas fa-plus mr-1"></i>Thêm dòng</button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th style="width:200px">Vật tư (danh mục)</th>
                                    <th style="width:160px">Hoặc tên tự nhập</th>
                                    <th style="width:150px">Quy cách</th>
                                    <th style="width:100px">SL đề nghị</th>
                                    <th style="width:110px">Đơn vị</th>
                                    <th style="width:150px">Sản phẩm</th>
                                    <th>Mục đích</th>
                                    <th style="width:40px"></th>
                                </tr>
                            </thead>
                            <tbody class="me-edit-rows">
                                @foreach ($items as $i => $it)
                                    <tr>
                                        <td>
                                            <select name="items[{{ $i }}][category_id]" class="form-control form-control-sm">
                                                <option value="">-- Ngoài danh mục --</option>
                                                @foreach ($categories as $c)
                                                    <option value="{{ $c->id }}" {{ $it->category_id == $c->id ? 'selected' : '' }}>
                                                        {{ $c->material_name }} — {{ $c->manufacturer_short_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><input type="text" name="items[{{ $i }}][material_name]" maxlength="255" class="form-control form-control-sm" value="{{ $it->material_name }}"></td>
                                        <td><input type="text" name="items[{{ $i }}][technical_specification]" maxlength="255" class="form-control form-control-sm" value="{{ $it->technical_specification }}"></td>
                                        <td><input type="number" step="0.0001" min="0.0001" name="items[{{ $i }}][requested_amount]" class="form-control form-control-sm" value="{{ rtrim(rtrim(number_format((float) $it->requested_amount, 4, '.', ''), '0'), '.') }}" required></td>
                                        <td>
                                            <select name="items[{{ $i }}][requested_unit]" class="form-control form-control-sm">
                                                <option value="">--</option>
                                                @foreach ($units as $u)
                                                    <option value="{{ $u->short_name }}" {{ $it->requested_unit === $u->short_name ? 'selected' : '' }}>{{ $u->short_name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><input type="text" name="items[{{ $i }}][product_name]" maxlength="255" class="form-control form-control-sm" value="{{ $it->product_name }}"></td>
                                        <td><input type="text" name="items[{{ $i }}][purpose]" maxlength="500" class="form-control form-control-sm" value="{{ $it->purpose }}"></td>
                                        <td class="text-center"><button type="button" class="btn btn-xs btn-outline-danger me-del-row">&times;</button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-outline-primary" onclick="$(this).closest('form').find('.me-edit-action').val('draft')"><i class="fas fa-save mr-1"></i>Lưu tạm</button>
                    <button type="submit" class="btn btn-primary" onclick="$(this).closest('form').find('.me-edit-action').val('send')"><i class="fas fa-paper-plane mr-1"></i>Trình ký</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    (function () {
        var eidx = {{ $items->count() + 100 }};
        $(document).on('click', '#reqEditModal_{{ $req->id }} .me-edit-add', function () {
            var html = document.getElementById('meRowTemplate').innerHTML.replace(/__i__/g, eidx++);
            $('#reqEditModal_{{ $req->id }} .me-edit-rows').append(html);
        });
    })();
</script>
