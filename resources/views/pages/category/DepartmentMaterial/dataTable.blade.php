@include('pages.category.shared.assets')

@php
    // Danh sách nhóm để lọc bảng: chỉ lấy các nhóm THỰC SỰ đang xuất hiện trong bảng này.
    $dmFilterGroups = $datas
        ->filter(fn ($row) => $row->classification_id && $row->classification_name)
        ->unique('classification_id')
        ->pluck('classification_name', 'classification_id')
        ->sort();
@endphp

<div class="card md-card">
    <div class="card-body">

        <div class="md-toolbar">
            <div class="d-flex align-items-center flex-wrap" style="gap: 10px">
                @perm('category_material_dept_manage')
                    <button type="button" class="btn btn-primary btn-md-create" data-modal="#dmCreateModal">
                        <i class="fas fa-plus mr-1"></i> Thêm mới vật tư phòng
                    </button>
                @endperm

                <div class="dm-filter">
                    <label for="dmGroupFilter"><i class="fas fa-filter mr-1"></i> Nhóm</label>
                    <select id="dmGroupFilter" class="form-control form-control-sm">
                        <option value="all">Tất cả</option>
                        <option value="none">Chưa phân loại</option>
                        @foreach ($dmFilterGroups as $groupId => $groupName)
                            <option value="{{ $groupId }}">{{ $groupName }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
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
                        <th class="text-center" style="width: 46px">STT</th>
                        <th style="width: 100px">Mã Vật Tư</th>
                        <th style="width: 170px">Tên Vật Tư</th>
                        <th style="width: 150px">Nhà Sản Xuất</th>
                        <th>Thông Tin Kỹ Thuật</th>
                        <th style="width: 110px">Phân Loại</th>
                        <th class="text-center" style="width: 65px">Đơn Vị</th>
                        <th class="text-right" style="width: 120px">Ngưỡng Tồn Tối Thiểu</th>
                        <th style="width: 130px">Ghi Chú</th>
                        <th class="text-center" style="width: 90px">Sử Dụng</th>
                        <th class="text-center" style="width: 85px">Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($datas as $row)
                        <tr data-classification="{{ $row->classification_id }}">
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
                                    @perm('category_material_dept_manage')
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
                                    @endperm

                                    @perm('category_material_dept_manage')
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

@once
    <style>
        .dm-filter {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .dm-filter label {
            margin: 0;
            font-size: 0.83rem;
            font-weight: 700;
            color: var(--primary-dark);
            white-space: nowrap;
        }

        .dm-filter .form-control {
            width: auto;
            min-width: 170px;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var dmGroupWant = 'all';

            /* ---------- Lọc bảng Vật Tư Của Phòng theo nhóm phân loại ---------- */
            $.fn.dataTable.ext.search.push(function(settings, data, index) {
                if (settings.nTable.id !== 'dmTable') return true;
                if (dmGroupWant === 'all') return true;

                var classificationId = ($(settings.aoData[index].nTr).attr('data-classification') || '').trim();

                return dmGroupWant === 'none' ? classificationId === '' : classificationId === dmGroupWant;
            });

            $(document).on('change', '#dmGroupFilter', function() {
                dmGroupWant = this.value;
                $('#dmTable').DataTable().draw();
            });
        });
    </script>
@endonce
