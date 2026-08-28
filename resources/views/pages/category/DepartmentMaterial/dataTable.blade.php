@include('pages.category.shared.assets')

<div class="card md-card">
    <div class="card-body">

        <div class="md-toolbar">
            <button type="button" class="btn btn-primary btn-md-create" data-modal="#dmCreateModal">
                <i class="fas fa-plus mr-1"></i> Khai vật tư
            </button>
            <p class="hint">
                <i class="fas fa-info-circle mr-1"></i>
                Mỗi dòng ở đây cũng là lời khai <b>"phòng tôi có dùng vật tư này"</b>, hiện ở cột
                <b>Phòng Ban Đang Dùng</b> của tab Danh Mục Vật Tư Công Ty.
            </p>
        </div>

        <div class="table-responsive">
            <table id="dmTable" class="table table-bordered table-hover w-100 md-table">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 55px">STT</th>
                        <th>Tên Vật Tư</th>
                        <th>Nhà Sản Xuất</th>
                        <th>Thông Tin Kỹ Thuật</th>
                        <th style="width: 150px">Phân Loại</th>
                        <th class="text-center" style="width: 90px">Đơn Vị</th>
                        <th class="text-right" style="width: 140px">Ngưỡng Tồn Tối Thiểu</th>
                        <th style="width: 170px">Ghi Chú</th>
                        <th class="text-center" style="width: 100px">Sử Dụng</th>
                        <th class="text-center" style="width: 110px">Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($datas as $row)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td class="font-weight-bold">{{ $row->material_name ?: '—' }}</td>
                            <td class="md-sub">
                                @if ($row->manufacturer_name)
                                    {{ $row->manufacturer_name }}
                                    @if ($row->manufacturer_short_name)
                                        <br><span class="md-tag">{{ $row->manufacturer_short_name }}</span>
                                    @endif
                                @else
                                    <span class="md-empty">—</span>
                                @endif
                            </td>
                            <td class="md-sub">{{ $row->category_technical_specification ?: '—' }}</td>
                            <td>
                                @if ($row->classification_name)
                                    <span class="cat-chip" title="{{ $row->classification_name }}">
                                        {{ $row->classification_name }}
                                    </span>
                                @else
                                    <span class="md-empty">Chưa phân loại</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($row->unit_short_name || $row->unit_name)
                                    <span class="md-tag"
                                        title="{{ $row->unit_name }}">{{ $row->unit_short_name ?: $row->unit_name }}</span>
                                @else
                                    <span class="md-empty" title="Phòng chưa khai đơn vị tính">—</span>
                                @endif
                            </td>
                            <td class="text-right" data-order="{{ $row->min_stock ?? -1 }}">
                                @if ($row->min_stock !== null)
                                    <span class="font-weight-bold">{{ $dmNum($row->min_stock) }}</span>
                                    <span class="md-sub">{{ $row->unit_short_name ?: $row->unit_name }}</span>
                                @else
                                    <span class="md-empty">Chưa khai</span>
                                @endif
                            </td>
                            <td class="md-sub">
                                @if ($row->note)
                                    <span class="md-note" title="{{ $row->note }}">{{ $row->note }}</span>
                                @else
                                    <span class="md-empty">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($row->status_id == 1)
                                    <span class="badge badge-success">Đang dùng</span>
                                @else
                                    <span class="badge badge-danger">Đã khoá</span>
                                @endif
                            </td>
                            <td>
                                <div class="md-actions">
                                    <button type="button" class="btn btn-sm btn-warning btn-md-edit" title="Sửa"
                                        data-modal="#dmUpdateModal"
                                        data-row="{{ json_encode([
                                            'id' => $row->id,
                                            'category_id' => $row->category_id,
                                            'classification_id' => $row->classification_id,
                                            'unit_id' => $row->unit_id,
                                            'min_stock' => $row->min_stock,
                                            'note' => $row->note,
                                            'material_name' => $row->material_name,
                                            'manufacturer_name' => $row->manufacturer_name,
                                        ]) }}">
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <form class="form-md-confirm d-inline"
                                        action="{{ route($mdRoute . 'deActive') }}" method="POST"
                                        data-title="{{ $row->status_id == 1 ? 'Khoá' : 'Mở khoá' }} {{ $mdLabel }}?"
                                        data-text="{{ $row->status_id == 1 ? 'Sau khi khoá, vật tư' : 'Sau khi mở khoá, vật tư' }} &quot;{{ $row->material_name }}&quot; {{ $row->status_id == 1 ? 'sẽ không còn tính là phòng đang dùng.' : 'sẽ dùng lại khai báo riêng của phòng.' }}"
                                        data-danger="{{ $row->status_id == 1 ? '1' : '' }}">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $row->id }}">
                                        <button type="submit"
                                            class="btn btn-sm btn-{{ $row->status_id == 1 ? 'secondary' : 'primary' }}"
                                            title="{{ $row->status_id == 1 ? 'Khoá' : 'Mở khoá' }}">
                                            <i class="fas fa-{{ $row->status_id == 1 ? 'lock' : 'unlock' }}"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
