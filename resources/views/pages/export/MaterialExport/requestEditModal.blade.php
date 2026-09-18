{{-- Một modal cho mỗi đề nghị đang Nháp / Bị từ chối. Biến vào: $req, $items --}}
<div class="modal fade md-modal" id="reqEditModal_{{ $req->id }}" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 92vw;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Sửa Đề Nghị {{ $req->code }}</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form action="{{ route($expRoute . 'requestUpdate') }}" method="POST" data-req-type="{{ $req->type }}">
                @csrf
                <input type="hidden" name="request_list_id" value="{{ $req->id }}">
                <input type="hidden" name="action_type" class="me-edit-action" value="draft">
                <div class="modal-body">

                    {{-- Thông tin chung + hai nút thao tác nằm gọn trên một hàng --}}
                    <div class="me-req-toolbar mb-3">
                        <div class="me-field flex-grow-1" style="min-width: 280px;">
                            <label>Tiêu đề đề nghị</label>
                            <input type="text" name="name" maxlength="255" class="form-control" value="{{ $req->name }}" placeholder="VD: Đề nghị vật tư bảo trì tháng 9...">
                        </div>
                        <div class="me-field">
                            <label>Ngày mong muốn</label>
                            <input type="date" name="needed_date" class="form-control" value="{{ $req->needed_date ? \Carbon\Carbon::parse($req->needed_date)->format('Y-m-d') : '' }}">
                        </div>

                        <button type="button" class="btn btn-sm btn-outline-info shadow-sm btn-open-me-picker" data-target-rows="#reqEditModal_{{ $req->id }} .me-edit-rows"
                            <i class="fas fa-boxes-stacked mr-1"></i>Danh mục tồn của phòng
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary shadow-sm me-edit-add">
                            <i class="fas fa-plus mr-1"></i>Thêm vật tư
                        </button>
                    </div>

                    <div class="table-responsive border rounded">
                        <table class="table table-bordered table-sm mb-0 me-req-table">
                            @include('pages.export.MaterialExport.requestRowsHead')
                            <tbody class="me-edit-rows" data-next-idx="{{ $items->count() + 100 }}">
                                @foreach ($items as $i => $it)
                                    <tr>
                                        <td>
                                            <select name="items[{{ $i }}][category_id]" class="form-control form-control-sm me-cat">
                                                <option value="">-- Ngoài danh mục --</option>
                                                @foreach ($categories as $c)
                                                    <option value="{{ $c->id }}" {{ $it->category_id == $c->id ? 'selected' : '' }}
                                                        data-unit="{{ $c->unit_short_name }}" data-spec="{{ $c->technical_specification }}"
                                                        data-stock="{{ $c->total_remaining ?? 0 }}" data-available="{{ $c->available_stock ?? ($c->total_remaining ?? 0) }}">
                                                        {{ $c->material_name }} — {{ $c->manufacturer_short_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><textarea name="items[{{ $i }}][material_name]" maxlength="255" rows="1" class="form-control form-control-sm me-autosize" placeholder="Vật tư ngoài danh mục...">{{ $it->material_name }}</textarea></td>
                                        <td><textarea name="items[{{ $i }}][technical_specification]" maxlength="255" rows="1" class="form-control form-control-sm me-autosize me-spec" placeholder="Thông tin kỹ thuật..." {{ $it->category_id ? 'readonly' : '' }}>{{ $it->technical_specification }}</textarea></td>
                                        <td><input type="text" inputmode="decimal" min="0.0001" name="items[{{ $i }}][requested_amount]" class="form-control form-control-sm text-right js-decimal" value="{{ rtrim(rtrim(number_format((float) $it->requested_amount, 4, '.', ''), '0'), '.') }}" required></td>
                                        <td>
                                            <select name="items[{{ $i }}][requested_unit]" class="form-control form-control-sm me-unit">
                                                <option value="">--</option>
                                                @foreach ($units as $u)
                                                    <option value="{{ $u->short_name }}" {{ $it->requested_unit === $u->short_name ? 'selected' : '' }}>{{ $u->short_name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="text-right">
                                            {{-- Tồn tại thời điểm dòng này được thêm/sửa danh mục lần gần nhất - giữ nguyên trừ khi đổi lại danh mục --}}
                                            <span class="me-stock-view">{{ $it->stock_at_request !== null ? rtrim(rtrim(number_format((float) $it->stock_at_request, 4, '.', ''), '0'), '.') : '—' }}</span>
                                            <input type="hidden" class="me-stock-hidden" name="items[{{ $i }}][stock_at_request]" value="{{ $it->stock_at_request ?? '' }}">
                                        </td>
                                        <td class="text-right"><span class="me-available">—</span></td>
                                        <td><textarea name="items[{{ $i }}][purpose]" maxlength="500" rows="1" class="form-control form-control-sm me-autosize" placeholder="Mục đích sử dụng...">{{ $it->purpose }}</textarea></td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-xs btn-outline-warning me-remember-row mr-1" title="Đề nghị dự trù vật tư"><i class="fas fa-bookmark"></i></button>
                                            <button type="button" class="btn btn-xs btn-outline-danger me-del-row" title="Xoá dòng">&times;</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        @include('pages.export.MaterialExport.signFlowFields')
                    </div>

                    <div class="form-group mt-3 mb-0">
                        <label style="font-size: 0.82rem; font-weight: 600; color: var(--text-main);">Ghi chú chung</label>
                        <textarea name="note" maxlength="500" rows="2" class="form-control me-autosize" placeholder="Ghi chú cho cả đề nghị...">{{ $req->note }}</textarea>
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
    document.addEventListener('DOMContentLoaded', function () {
        $(document).on('click', '#reqEditModal_{{ $req->id }} .me-edit-add', function () {
            window.meAddRequestRow('#reqEditModal_{{ $req->id }} .me-edit-rows');
        });
    });
</script>
