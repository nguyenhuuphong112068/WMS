@include('pages.category.shared.assets')

<div class="card md-card">
    <div class="card-body">

        <div class="md-toolbar">
            @perm('category_material_create')
                <button type="button" class="btn btn-primary btn-md-create">
                    <i class="fas fa-plus mr-1"></i> Thêm mới
                </button>
            @endperm
            <p class="hint">
                <i class="fas fa-info-circle mr-1"></i>
                Đang hoạt động {{ $datas->where('status_id', 1)->count() }}/{{ $datas->count() }} bản ghi.
                Phân loại và đơn vị tính khai ở tab <b>Vật Tư Của Phòng</b>.
            </p>
        </div>

        <div class="table-responsive">
            <table id="mdTable" class="table table-bordered table-hover w-100">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 60px">STT</th>
                        <th style="width: 110px">Mã Vật Tư</th>
                        <th>Tên Vật Tư</th>
                        <th>Nhà Sản Xuất</th>
                        <th>Thông Tin Kỹ Thuật</th>
                        <th style="width: 200px">Phòng Ban Đang Dùng</th>
                        <th style="width: 130px">Người Tạo</th>
                        <th class="text-center" style="width: 105px">Ngày Tạo</th>
                        <th class="text-center" style="width: 130px">Duyệt</th>
                        <th class="text-center" style="width: 105px">Sử Dụng</th>
                        <th class="text-center" style="width: 215px">Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($datas as $row)
                        @php $usingDepts = $departmentsByCategory[$row->id] ?? collect(); @endphp
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td class="font-weight-bold">{{ $row->code }}</td>
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
                            <td class="md-sub">{{ $row->technical_specification ?: '—' }}</td>
                            <td>
                                @if ($usingDepts->count())
                                    <div class="cat-chips">
                                        @foreach ($usingDepts as $dept)
                                            <span class="cat-chip dept"
                                                title="{{ $dept->name }}">{{ $dept->shortName ?: $dept->name }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="md-empty">Chưa phòng nào khai</span>
                                @endif
                            </td>
                            <td class="md-sub">{{ $row->updated_by ?: $row->created_by ?: '—' }}</td>
                            <td class="text-center md-sub">
                                {{ $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('d/m/Y') : '—' }}
                            </td>
                            <td class="text-center">
                                @include('pages.materData.shared.appStatus', ['row' => $row])
                            </td>
                            <td class="text-center">
                                @if ($row->status_id == 1)
                                    <span class="badge badge-success">Hoạt động</span>
                                @else
                                    <span class="badge badge-danger">Đã khoá</span>
                                @endif
                            </td>
                            <td>
                                @include('pages.category.shared.rowActions', [
                                    'prefix' => $mdRoute,
                                    'permPrefix' => 'category_material_',
                                    'row' => $row,
                                    'label' => $mdLabel,
                                    'title' => $row->material_name,
                                    'historyCount' => (int) ($historyCounts[$row->id] ?? 0),
                                    'editData' => [
                                        'id' => $row->id,
                                        'code' => $row->code,
                                        'material_names_id' => $row->material_names_id,
                                        'manufacturers_id' => $row->manufacturers_id,
                                        'technical_specification' => $row->technical_specification,
                                    ],
                                ])
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
