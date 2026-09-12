@include('pages.category.shared.assets')

<div class="card md-card">
    <div class="card-body">

        <div class="md-toolbar">
            @perm('category_standard_create')
                <button type="button" class="btn btn-primary btn-md-create">
                    <i class="fas fa-plus mr-1"></i> Thêm mới
                </button>
            @endperm
        </div>

        @include('pages.shared.standardGroupFilter', ['sgrTarget' => 'mdTable'])

        <div class="table-responsive">
            <table id="mdTable" class="table table-bordered table-hover w-100">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 55px">STT</th>
                        <th style="width: 110px">Mã Chuẩn</th>
                        <th>Tên Chất Chuẩn</th>
                        <th style="width: 130px">Số CAS</th>
                        <th style="width: 160px">Nguồn Gốc / NSX</th>
                        <th class="text-center" style="width: 100px">Tỷ Trọng</th>
                        <th style="width: 165px">Điều Kiện Bảo Quản</th>
                        <th class="text-center" style="width: 95px">Thời Gian Đặt Hàng</th>
                        <th style="width: 200px" title="Nhóm chuẩn quyết định mã ống chuẩn khi nhập kho">
                            Phân Nhóm Chuẩn</th>
                        <th style="width: 170px"
                            title="Các phòng ban đã khai dùng chất chuẩn này (bảng standard_department_categories)">
                            Phòng Ban Đang Dùng</th>
                        <th style="width: 120px">Người Tạo</th>
                        <th class="text-center" style="width: 125px">Duyệt</th>
                        <th class="text-center" style="width: 100px">Sử Dụng</th>
                        <th class="text-center" style="width: 210px">Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($datas as $row)
                        @php
                            $sdCodes = $sdGroupsOf($row->groups);
                            $sdDepartments = $departmentsByCategory[$row->id] ?? collect();
                            // Màu nền mã theo tình trạng: khoá thắng duyệt, chưa duyệt thì vẫn vàng chờ
                            $catCodeStatus = $row->status_id == 0 ? 'locked' : ($row->app_status === 'approved' ? 'approved' : 'pending');
                        @endphp
                        {{-- data-groups để bộ lọc Phân nhóm chuẩn nhận ra dòng này --}}
                        <tr data-groups="{{ implode(',', $sdCodes) }}">
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td><span class="cat-code {{ $catCodeStatus }}">{{ $row->code }}</span></td>
                            <td>
                                <span class="font-weight-bold">{{ $row->standard_name ?: '—' }}</span>
                                @if ($row->doc_no)
                                    <br><small class="md-sub">Tài liệu: {{ $row->doc_no }}</small>
                                @endif
                            </td>
                            <td class="md-sub">{{ $row->cas_no ?: ($row->name_cas_no ?: '—') }}</td>
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
                                @if ($row->density !== null)
                                    {{ rtrim(rtrim((string) $row->density, '0'), '.') }}
                                @else
                                    <span class="md-empty">—</span>
                                @endif
                            </td>
                            <td class="md-sub">
                                @if ($row->storage_condition_name)
                                    <span class="md-note"
                                        title="{{ $row->storage_condition_name }}">{{ $row->storage_condition_name }}</span>
                                @else
                                    <span class="md-empty">—</span>
                                @endif
                            </td>
                            <td class="text-center" data-order="{{ $row->lead_time_days ?? -1 }}">
                                @if ($row->lead_time_days !== null)
                                    <span class="font-weight-bold">{{ $row->lead_time_days }}</span>
                                    <span class="md-sub">ngày</span>
                                @else
                                    <span class="md-empty">—</span>
                                @endif
                            </td>
                            <td>
                                @if ($sdCodes)
                                    <div class="sgr-chips">
                                        @foreach ($sdCodes as $code)
                                            <span class="sgr-chip"
                                                title="{{ $groups[$code]['name'] ?? $code }}">{{ $groups[$code]['short'] ?? $code }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="md-empty">—</span>
                                @endif
                            </td>
                            <td data-order="{{ $sdDepartments->count() }}">
                                @if ($sdDepartments->isNotEmpty())
                                    <div class="sgr-chips">
                                        @foreach ($sdDepartments as $dept)
                                            <span class="sgr-chip dept"
                                                title="{{ $dept->name }}">{{ $dept->shortName ?: $dept->name }}</span>
                                        @endforeach
                                    </div>
                                    <div class="md-sub">{{ $sdDepartments->count() }} phòng</div>
                                @else
                                    <span class="md-empty" title="Chưa phòng ban nào khai dùng chất chuẩn này">Chưa
                                        phòng nào dùng</span>
                                @endif
                            </td>
                            <td class="md-sub">
                                {{ $row->updated_by ?: $row->created_by ?: '—' }}
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
                                    'permPrefix' => 'category_standard_',
                                    'row' => $row,
                                    'label' => $mdLabel,
                                    'title' => $row->code,
                                    'historyCount' => (int) ($historyCounts[$row->id] ?? 0),
                                    'editData' => [
                                        'id' => $row->id,
                                        'code' => $row->code,
                                        'chem_names_id' => $row->chem_names_id,
                                        'cas_no' => $row->cas_no,
                                        'manufacturers_id' => $row->manufacturers_id,
                                        'density' => $row->density !== null ? rtrim(rtrim((string) $row->density, '0'), '.') : null,
                                        'storage_condition_id' => $row->storage_condition_id,
                                        'groups' => $sdCodes,
                                        'doc_no' => $row->doc_no,
                                        'lead_time_days' => $row->lead_time_days,
                                        'note' => $row->note,
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
