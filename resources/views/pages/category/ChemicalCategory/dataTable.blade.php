@include('pages.category.shared.assets')

<div class="content-wrapper">
    <div class="md-page">

        <div class="md-hero">
            <div>
                <h1><i class="{{ $mdIcon }}"></i> {{ $mdTitle }}</h1>
                <p>Danh mục hoá chất chung hiện hành kèm nhóm phân loại theo quy định. Bản ghi phải được duyệt trước khi
                    sử dụng.</p>
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
                        Rê chuột vào mã phân loại để xem tên đầy đủ.
                    </p>
                </div>

                <div class="table-responsive">
                    <table id="mdTable" class="table table-bordered table-hover w-100">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 55px">STT</th>
                                <th style="width: 130px">Mã Danh Mục</th>
                                <th>Tên Hoá Chất</th>
                                <th>Nhà Sản Xuất</th>
                                <th class="text-center" style="width: 95px">Đơn Vị Tính</th>
                                <th class="text-center" style="width: 105px">Tỉ Trọng d<br><small>(g/ml)</small></th>
                                <th class="text-center" style="width: 95px">Hạn Dùng<br><small>(tháng)</small></th>
                                <th style="width: 180px">Điều Kiện Bảo Quản</th>
                                <th style="width: 110px">Số Tài Liệu</th>
                                <th style="width: 170px">Phân Loại</th>
                                <th style="width: 120px">Người Tạo</th>
                                <th class="text-center" style="width: 125px">Duyệt</th>
                                <th class="text-center" style="width: 100px">Sử Dụng</th>
                                <th class="text-center" style="width: 255px">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($datas as $row)
                                @php
                                    $codes = json_decode($row->classification ?? '', true);
                                    $codes = is_array($codes) ? $codes : [];
                                @endphp
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td>
                                        <span class="font-weight-bold">{{ $row->code }}</span>
                                        @if ($row->type)
                                            <br><span class="md-tag">{{ $row->type }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="font-weight-bold">{{ $row->chem_name ?: '—' }}</span>
                                        @if ($row->cas_no)
                                            <br><small class="md-sub">CAS: {{ $row->cas_no }}</small>
                                        @endif
                                    </td>
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
                                    <td class="text-center">
                                        @if ($row->unit_short_name || $row->unit_name)
                                            <span class="md-tag"
                                                title="{{ $row->unit_name }}">{{ $row->unit_short_name ?: $row->unit_name }}</span>
                                        @else
                                            <span class="md-empty">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center md-sub">
                                        @if ($row->density !== null)
                                            {{ rtrim(rtrim($row->density, '0'), '.') }}
                                        @else
                                            <span class="md-empty">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center md-sub">
                                        {{ $row->shelf_life_months ?: '—' }}
                                    </td>
                                    <td class="md-sub">
                                        @if ($row->storage_condition_name)
                                            <span class="md-note"
                                                title="{{ $row->storage_condition_name }}">{{ $row->storage_condition_name }}</span>
                                        @else
                                            <span class="md-empty">—</span>
                                        @endif
                                    </td>
                                    <td class="md-sub">{{ $row->doc_no ?: '—' }}</td>
                                    <td>
                                        @if ($codes)
                                            <div class="cat-chips">
                                                @foreach ($codes as $code)
                                                    <span
                                                        class="cat-chip {{ in_array($code, $mdDangerCodes) ? 'danger' : '' }}"
                                                        title="{{ $classifications[$code] ?? $code }}">{{ $code }}</span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="md-empty">—</span>
                                        @endif
                                    </td>
                                    <td class="md-sub">
                                        {{ $row->created_by ?: '—' }}
                                        @if ($row->created_at)
                                            <br><small>{{ \Carbon\Carbon::parse($row->created_at)->format('d/m/Y') }}</small>
                                        @endif
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
                                            'title' => $row->code,
                                            'showConvert' => true,
                                            'editData' => [
                                                'id' => $row->id,
                                                'code' => $row->code,
                                                'type' => $row->type,
                                                'chem_names_id' => $row->chem_names_id,
                                                'manufacturers_id' => $row->manufacturers_id,
                                                'unit_id' => $row->unit_id,
                                                'density' => $row->density !== null ? rtrim(rtrim($row->density, '0'), '.') : null,
                                                'shelf_life_months' => $row->shelf_life_months,
                                                'storage_condition_id' => $row->storage_condition_id,
                                                'doc_no' => $row->doc_no,
                                                'classification' => $codes,
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
