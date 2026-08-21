@include('pages.category.shared.assets')

<div class="content-wrapper">
    <div class="md-page">

        <div class="md-hero">
            <div>
                <h1><i class="{{ $mdIcon }}"></i> {{ $mdTitle }}</h1>
                <p>Mỗi dòng là một tổ hợp Vật tư - Nhà sản xuất - Nhà cung cấp - Đơn vị tính. Bản ghi phải được duyệt
                    trước khi sử dụng.</p>
            </div>
            <div class="md-stats">
                <span class="stat"><i class="fas fa-list"></i> Tổng {{ $datas->count() }}</span>
                <span class="stat"><i class="fas fa-hourglass-half"></i> Chờ duyệt
                    {{ $datas->where('app_status', 'pending')->count() }}</span>
                <span class="stat"><i class="fas fa-check-circle"></i> Đã duyệt
                    {{ $datas->where('app_status', 'approved')->count() }}</span>
            </div>
        </div>

        <div class="card md-card">
            <div class="card-body">

                <div class="md-toolbar">
                    <button type="button" class="btn btn-primary btn-md-create">
                        <i class="fas fa-plus mr-1"></i> Thêm mới
                    </button>
                    <p class="hint">
                        <i class="fas fa-info-circle mr-1"></i>
                        Đang hoạt động {{ $datas->where('status_id', 1)->count() }}/{{ $datas->count() }} bản ghi.
                    </p>
                </div>

                <div class="table-responsive">
                    <table id="mdTable" class="table table-bordered table-hover w-100">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 60px">STT</th>
                                <th>Tên Vật Tư</th>
                                <th>Nhà Sản Xuất</th>
                                <th>Nhà Cung Cấp</th>
                                <th class="text-center" style="width: 110px">Đơn Vị Tính</th>
                                <th style="width: 130px">Người Tạo</th>
                                <th class="text-center" style="width: 105px">Ngày Tạo</th>
                                <th class="text-center" style="width: 130px">Duyệt</th>
                                <th class="text-center" style="width: 105px">Sử Dụng</th>
                                <th class="text-center" style="width: 215px">Thao Tác</th>
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
                                    <td class="md-sub">{{ $row->supplier_name ?: '—' }}</td>
                                    <td class="text-center">
                                        @if ($row->unit_short_name || $row->unit_name)
                                            <span class="md-tag"
                                                title="{{ $row->unit_name }}">{{ $row->unit_short_name ?: $row->unit_name }}</span>
                                        @else
                                            <span class="md-empty">—</span>
                                        @endif
                                    </td>
                                    <td class="md-sub">{{ $row->created_by ?: '—' }}</td>
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
                                            'row' => $row,
                                            'label' => $mdLabel,
                                            'title' => $row->material_name,
                                            'editData' => [
                                                'id' => $row->id,
                                                'material_names_id' => $row->material_names_id,
                                                'manufacturers_id' => $row->manufacturers_id,
                                                'suppliers_id' => $row->suppliers_id,
                                                'unit_id' => $row->unit_id,
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
    </div>
</div>
