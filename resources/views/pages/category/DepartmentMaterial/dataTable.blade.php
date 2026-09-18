@include('pages.category.shared.assets')

<div class="card md-card">
    <div class="card-body">

        <div class="md-toolbar">
            @perm('category_material_dept_manage')
                <button type="button" class="btn btn-primary btn-md-create" data-modal="#dmCreateModal">
                    <i class="fas fa-plus mr-1"></i> Thêm mới vật tư phòng
                </button>
            @endperm
        </div>

        <div class="table-responsive">
            <table id="dmTable" class="table table-bordered table-hover w-100 md-table">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 46px">STT</th>
                        <th style="width: 100px">Mã Vật Tư</th>
                        <th style="width: 170px">Tên Vật Tư</th>
                        <th style="width: 150px">Nhà Sản Xuất</th>
                        <th>Thông Tin Kỹ Thuật</th>
                        <th style="width: 130px">Phân Loại</th>
                        <th class="text-center" style="width: 65px">Đơn Vị</th>
                        <th class="text-right" style="width: 120px">Ngưỡng Tồn Tối Thiểu</th>
                        <th class="text-right" style="width: 120px">Ngưỡng Tồn Tối Đa</th>
                        <th style="width: 190px">Định Khu</th>
                        <th style="width: 130px">Ghi Chú</th>
                        <th class="text-center" style="width: 90px">Sử Dụng</th>
                        <th class="text-center" style="width: 85px">Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($datas as $row)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td class="font-weight-bold">{{ $row->category_code ?: '—' }}</td>
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
                                    <span class="md-tag">{{ $row->classification_name }}</span>
                                @else
                                    <span class="md-empty">—</span>
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
                            <td class="text-right" data-order="{{ $row->max_stock ?? -1 }}">
                                @if ($row->max_stock !== null)
                                    <span class="font-weight-bold">{{ $dmNum($row->max_stock) }}</span>
                                    <span class="md-sub">{{ $row->unit_short_name ?: $row->unit_name }}</span>
                                @else
                                    <span class="md-empty">Chưa khai</span>
                                @endif
                            </td>
                            <td class="md-sub">
                                @if ($row->location_code)
                                    <div class="font-weight-bold">
                                        <span class="md-tag">{{ $row->location_code }}</span>
                                    </div>
                                    <div>{{ $dmPath($row) }}</div>
                                @else
                                    <span class="md-empty">Chưa định khu</span>
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
                                    @if ($row->status_id == 1)
                                        <button type="button" class="btn btn-sm btn-outline-warning btn-dm-watchlist" title="Đề nghị dự trù vật tư"
                                            data-category-id="{{ $row->category_id }}">
                                            <i class="fas fa-bookmark"></i>
                                        </button>

                                        @perm('category_material_dept_manage')
                                            <button type="button" class="btn btn-sm btn-warning btn-md-edit" title="Sửa"
                                                data-modal="#dmUpdateModal"
                                                data-row="{{ json_encode([
                                                    'id' => $row->id,
                                                    'category_id' => $row->category_id,
                                                    'classification_id' => $row->classification_id,
                                                    'unit_id' => $row->unit_id,
                                                    'min_stock' => $row->min_stock,
                                                    'max_stock' => $row->max_stock,
                                                    'default_location_id' => $row->default_location_id,
                                                    'default_location_id_text' => \App\Support\CategoryLookup::locationLabel($row, 'location_code'),
                                                    'note' => $row->note,
                                                    'material_name' => $row->material_name,
                                                    'manufacturer_name' => $row->manufacturer_name,
                                                ]) }}">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        @endperm
                                    @endif

                                    @perm('category_material_dept_manage')
                                        <form class="form-md-confirm d-inline" data-require-reason="1" action="{{ route($mdRoute . 'deActive') }}"
                                            method="POST"
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
                                    @endperm
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        /*
         | ĐỀ NGHỊ DỰ TRÙ VẬT TƯ - hỏi lý do dự trù rồi gửi AJAX ngay. Dòng đã khoá
         | sẽ không hiển thị nút này. Hiện lại ở tab "Danh sách vật tư cần dự trù" bên Dự Trù
         | Vật Tư - xem App\Support\MaterialWatchlist.
         */
        $(document).on('click', '.btn-dm-watchlist', function() {
            var $btn = $(this);
            var categoryId = $btn.data('category-id');

            Swal.fire({
                title: 'Đề nghị dự trù vật tư này?',
                input: 'textarea',
                inputLabel: 'Lý do dự trù',
                inputPlaceholder: 'Nêu rõ lý do cần dự trù vật tư này...',
                inputAttributes: { maxlength: '500' },
                showCancelButton: true,
                confirmButtonColor: '#2E7BC4',
                cancelButtonColor: '#94A3B8',
                confirmButtonText: 'Gửi đề nghị',
                cancelButtonText: 'Huỷ',
                preConfirm: function(value) {
                    if (!value || !value.trim()) {
                        Swal.showValidationMessage('Vui lòng nhập lý do dự trù');
                    }
                    return value;
                }
            }).then(function(result) {
                if (!result.isConfirmed) return;

                $btn.prop('disabled', true);

                $.post('{{ route($mdRoute . "watchlistRemember") }}', {
                    _token: '{{ csrf_token() }}',
                    category_id: categoryId,
                    note: result.value
                }).done(function(res) {
                    Swal.fire({
                        icon: res.success ? 'success' : 'error',
                        title: res.success ? 'Đã gửi đề nghị!' : 'Có lỗi xảy ra',
                        text: res.message || '',
                        timer: res.success ? 1800 : undefined,
                        showConfirmButton: !res.success
                    });
                }).fail(function() {
                    Swal.fire({ icon: 'error', title: 'Có lỗi xảy ra', text: 'Vui lòng thử lại.' });
                }).always(function() {
                    $btn.prop('disabled', false);
                });
            });
        });
    });
</script>

