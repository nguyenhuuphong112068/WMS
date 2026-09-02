@include('pages.category.shared.assets')

<style>
    /* Nguồn của giá trị đang hiệu lực: khai riêng hay lấy mặc định của danh mục */
    .dc-source {
        display: inline-block;
        margin-top: 2px;
        padding: 0 7px;
        border-radius: 999px;
        font-size: 0.68rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .dc-source.is-own {
        background: #DCFCE7;
        color: #15803D;
        border: 1px solid #86EFAC;
    }

    .dc-source.is-default {
        background: var(--primary-soft);
        color: var(--primary-dark);
        border: 1px solid var(--primary-lighter);
    }

    .dc-value {
        font-weight: 700;
        color: var(--text-main);
        white-space: nowrap;
    }

    .dc-value.is-none {
        color: #94A3B8;
        font-weight: 500;
    }
</style>

<div class="card md-card">
    <div class="card-body">

        <div class="md-toolbar">
            @perm('category_chemical_dept_manage')
                <button type="button" class="btn btn-primary btn-md-create" data-modal="#dcCreateModal">
                    <i class="fas fa-plus mr-1"></i> Khai hoá chất
                </button>
            @endperm
            <p class="hint">
                <i class="fas fa-info-circle mr-1"></i>
                Mỗi dòng ở đây cũng là lời khai <b>"phòng tôi có dùng chất này"</b>, hiện ở cột
                <b>Phòng Ban Đang Dùng</b> của tab Danh Mục Hoá Chất Công Ty.
            </p>
        </div>

        @include('pages.shared.classificationFilter', ['clsTarget' => 'dcTable'])

        <div class="table-responsive">
            <table id="dcTable" class="table table-bordered table-hover w-100 md-table">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 55px">STT</th>
                        <th style="width: 120px">Mã Danh Mục</th>
                        <th>Tên Hoá Chất</th>
                        <th>Nhà Sản Xuất</th>
                        <th class="text-center" style="width: 90px">Đơn Vị</th>
                        <th class="text-center" style="width: 105px">Tỉ Trọng d<br><small>(g/ml)</small></th>
                        <th style="width: 110px">Số Tài Liệu</th>
                        <th style="width: 170px">Phân Loại</th>
                        <th class="text-center" style="width: 135px">Hạn Dùng Nội Bộ<br><small>(tháng)</small>
                        </th>
                        <th class="text-right" style="width: 130px">Ngưỡng Tồn Tối Thiểu</th>
                        <th style="width: 200px">Vị Trí Quy Hoạch</th>
                        <th style="width: 170px">Điều Kiện Bảo Quản</th>
                        <th style="width: 160px">Ghi Chú</th>
                        <th class="text-center" style="width: 100px">Sử Dụng</th>
                        <th class="text-center" style="width: 110px">Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($datas as $row)
                        @php
                            $codes = json_decode($row->classification ?? '', true) ?: [];
                            // Giá trị đang thực sự có hiệu lực, theo đúng quy tắc fallback của hệ thống
                            $shelfLife = $row->shelf_life_months ?? $row->category_shelf_life_months;
                            $storage = $row->storage_condition_name ?? $row->category_storage_condition_name;
                        @endphp
                        <tr data-classification="{{ implode(',', $codes) }}">
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td>
                                <span class="md-tag">{{ $row->category_code ?: '—' }}</span>
                                @if ($row->category_type)
                                    <br><span class="md-tag">{{ $row->category_type }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="font-weight-bold">{{ $row->chem_name ?: '—' }}</div>
                                @if ($row->cas_no)
                                    <small class="md-sub">CAS: {{ $row->cas_no }}</small>
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
                                    <span class="md-empty" title="Phòng chưa khai đơn vị tính">—</span>
                                @endif
                            </td>
                            <td class="text-center md-sub">
                                @if ($row->category_density !== null)
                                    {{ rtrim(rtrim($row->category_density, '0'), '.') }}
                                @else
                                    <span class="md-empty">—</span>
                                @endif
                            </td>
                            <td class="md-sub">{{ $row->category_doc_no ?: '—' }}</td>
                            <td>
                                @if ($codes)
                                    <div class="cat-chips">
                                        @foreach ($codes as $code)
                                            <span
                                                class="cat-chip {{ in_array($code, $mdDangerCodes ?? []) ? 'danger' : '' }}"
                                                title="{{ $classifications[$code] ?? $code }}">{{ $code }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="md-empty">—</span>
                                @endif
                            </td>
                            <td class="text-center" data-order="{{ $shelfLife ?? 0 }}">
                                <span class="dc-value {{ $shelfLife ? '' : 'is-none' }}">
                                    {{ $shelfLife ?: 'Không khai' }}
                                </span>
                                @if ($row->shelf_life_months !== null)
                                    <br>
                                    <span class="dc-source is-own">Riêng phòng</span>
                                @endif
                            </td>
                            <td class="text-right" data-order="{{ $row->min_stock ?? -1 }}">
                                @if ($row->min_stock !== null)
                                    <span class="dc-value">{{ $dcNum($row->min_stock) }}</span>
                                    <span class="md-sub">{{ $row->unit_short_name ?: $row->unit_name }}</span>
                                @else
                                    <span class="dc-value is-none">Theo tỉ lệ 20%</span>
                                @endif
                            </td>
                            <td class="md-sub">
                                @if ($row->location_code)
                                    <div class="font-weight-bold">
                                        <span class="md-tag">{{ $row->location_code }}</span>
                                    </div>
                                    <div>{{ $dcPath($row) }}</div>
                                @else
                                    <span class="md-empty">Chưa quy hoạch</span>
                                @endif
                            </td>
                            <td class="md-sub">
                                @if ($storage)
                                    <span class="md-note"
                                        title="{{ $storage }}">{{ $storage }}</span>
                                    @if ($row->storage_condition_name === null)
                                        <div><span class="dc-source is-default">Theo danh mục</span></div>
                                    @endif
                                @else
                                    <span class="md-empty">—</span>
                                @endif
                            </td>
                            <td class="md-sub">
                                @if ($row->note)
                                    <span class="md-note"
                                        title="{{ $row->note }}">{{ $row->note }}</span>
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
                                    @perm('category_chemical_dept_manage')
                                        <button type="button" class="btn btn-sm btn-warning btn-md-edit"
                                            title="Sửa"
                                            data-modal="#dcUpdateModal"
                                            data-row="{{ json_encode([
                                                'id' => $row->id,
                                                'category_id' => $row->category_id,
                                                'unit_id' => $row->unit_id,
                                                'shelf_life_months' => $row->shelf_life_months,
                                                'min_stock' => $row->min_stock,
                                                'storage_condition_id' => $row->storage_condition_id,
                                                'default_location_id' => $row->default_location_id,
                                                'note' => $row->note,
                                                'category_code' => $row->category_code,
                                                'chem_name' => $row->chem_name,
                                                'unit' => $row->unit_short_name ?: $row->unit_name,
                                                'category_shelf_life_months' => $row->category_shelf_life_months,
                                            ]) }}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    @endperm

                                    @perm('category_chemical_dept_manage')
                                        <form class="form-md-confirm d-inline"
                                            action="{{ route($mdRoute . 'deActive') }}" method="POST"
                                            data-title="{{ $row->status_id == 1 ? 'Khoá' : 'Mở khoá' }} {{ $mdLabel }}?"
                                            data-text="{{ $row->status_id == 1 ? 'Sau khi khoá, hoá chất' : 'Sau khi mở khoá, hoá chất' }} &quot;{{ $row->chem_name }}&quot; {{ $row->status_id == 1 ? 'sẽ quay về dùng mặc định của danh mục và không còn tính là phòng đang dùng.' : 'sẽ dùng lại cấu hình riêng của phòng.' }}"
                                            data-danger="{{ $row->status_id == 1 ? '1' : '' }}">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $row->id }}">
                                            <button type="submit"
                                                class="btn btn-sm btn-{{ $row->status_id == 1 ? 'secondary' : 'primary' }}"
                                                title="{{ $row->status_id == 1 ? 'Khoá' : 'Mở khoá' }}">
                                                <i
                                                    class="fas fa-{{ $row->status_id == 1 ? 'lock' : 'unlock' }}"></i>
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
