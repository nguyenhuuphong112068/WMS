@include('pages.category.shared.assets')

<style>
    /* Nguồn của giá trị đang hiệu lực: khai riêng hay lấy mặc định của danh mục */
    .ds-source {
        display: inline-block;
        margin-top: 2px;
        padding: 0 7px;
        border-radius: 999px;
        font-size: 0.68rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .ds-source.is-own {
        background: #DCFCE7;
        color: #15803D;
        border: 1px solid #86EFAC;
    }

    .ds-source.is-default {
        background: var(--primary-soft);
        color: var(--primary-dark);
        border: 1px solid var(--primary-lighter);
    }

    .ds-value {
        font-weight: 700;
        color: var(--text-main);
        white-space: nowrap;
    }

    .ds-value.is-none {
        color: #94A3B8;
        font-weight: 500;
    }
</style>

<div class="card md-card">
    <div class="card-body">

        <div class="md-toolbar">
            <button type="button" class="btn btn-primary btn-md-create" data-modal="#dsCreateModal">
                <i class="fas fa-plus mr-1"></i> Khai chất chuẩn
            </button>
            <p class="hint">
                <i class="fas fa-info-circle mr-1"></i>
                Mỗi dòng ở đây cũng là lời khai <b>"phòng tôi có dùng chất chuẩn này"</b>, hiện ở cột
                <b>Phòng Ban Đang Dùng</b> của tab Danh Mục Chất Chuẩn Công Ty.
            </p>
        </div>

        @include('pages.shared.standardGroupFilter', ['sgrTarget' => 'dsTable'])

        <div class="table-responsive">
            <table id="dsTable" class="table table-bordered table-hover w-100 md-table">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 55px">STT</th>
                        <th style="width: 110px">Mã Chuẩn</th>
                        <th>Tên Chất Chuẩn</th>
                        <th style="width: 160px">Nguồn Gốc / NSX</th>
                        <th class="text-center" style="width: 90px">Đơn Vị</th>
                        <th class="text-center" style="width: 85px">Version</th>
                        <th style="width: 180px">Phân Nhóm Chuẩn</th>
                        <th class="text-center" style="width: 135px">Hạn Dùng Nội Bộ<br><small>(tháng)</small></th>
                        <th class="text-right" style="width: 130px">Ngưỡng Tồn Tối Thiểu</th>
                        <th style="width: 200px">Vị Trí Quy Hoạch</th>
                        <th style="width: 170px">Điều Kiện Bảo Quản</th>
                        <th style="width: 150px">Ghi Chú</th>
                        <th class="text-center" style="width: 100px">Sử Dụng</th>
                        <th class="text-center" style="width: 110px">Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($datas as $row)
                        @php
                            $dsCodes = $sdGroupsOf($row->groups);
                            // Giá trị đang thực sự có hiệu lực, theo đúng quy tắc fallback của hệ thống
                            $dsShelfLife = $row->shelf_life_months ?? $row->category_shelf_life_months;
                            $dsStorage = $row->storage_condition_name ?? $row->category_storage_condition_name;
                        @endphp
                        <tr data-groups="{{ implode(',', $dsCodes) }}">
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td><span class="md-tag">{{ $row->category_code ?: '—' }}</span></td>
                            <td>
                                <div class="font-weight-bold">{{ $row->standard_name ?: '—' }}</div>
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
                            <td class="text-center" data-order="{{ $row->category_version }}">
                                <span class="sgr-version">v{{ $row->category_version }}</span>
                            </td>
                            <td>
                                @if ($dsCodes)
                                    <div class="sgr-chips">
                                        @foreach ($dsCodes as $code)
                                            <span class="sgr-chip"
                                                title="{{ $groups[$code]['name'] ?? $code }}">{{ $groups[$code]['short'] ?? $code }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="md-empty">—</span>
                                @endif
                            </td>
                            <td class="text-center" data-order="{{ $dsShelfLife ?? 0 }}">
                                <span class="ds-value {{ $dsShelfLife ? '' : 'is-none' }}">
                                    {{ $dsShelfLife ?: 'Không khai' }}
                                </span>
                                @if ($row->shelf_life_months !== null)
                                    <br>
                                    <span class="ds-source is-own">Riêng phòng</span>
                                @endif
                            </td>
                            <td class="text-right" data-order="{{ $row->min_stock ?? -1 }}">
                                @if ($row->min_stock !== null)
                                    <span class="ds-value">{{ $dsNum($row->min_stock) }}</span>
                                    <span class="md-sub">{{ $row->unit_short_name ?: $row->unit_name }}</span>
                                @else
                                    <span class="ds-value is-none">Theo tỉ lệ 20%</span>
                                @endif
                            </td>
                            <td class="md-sub">
                                @if ($row->location_name)
                                    <div class="font-weight-bold">{{ $row->location_name }}
                                        <span class="md-tag">{{ $row->location_code }}</span>
                                    </div>
                                    <div>{{ $dsPath($row) }}</div>
                                @else
                                    <span class="md-empty">Chưa quy hoạch</span>
                                @endif
                            </td>
                            <td class="md-sub">
                                @if ($dsStorage)
                                    <span class="md-note" title="{{ $dsStorage }}">{{ $dsStorage }}</span>
                                    @if ($row->storage_condition_name === null)
                                        <div><span class="ds-source is-default">Theo danh mục</span></div>
                                    @endif
                                @else
                                    <span class="md-empty">—</span>
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
                                        data-modal="#dsUpdateModal"
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
                                            'standard_name' => $row->standard_name,
                                            'version' => $row->category_version,
                                            'unit' => $row->unit_short_name ?: $row->unit_name,
                                            'category_shelf_life_months' => $row->category_shelf_life_months,
                                        ]) }}">
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <form class="form-md-confirm d-inline" action="{{ route($mdRoute . 'deActive') }}"
                                        method="POST"
                                        data-title="{{ $row->status_id == 1 ? 'Khoá' : 'Mở khoá' }} {{ $mdLabel }}?"
                                        data-text="{{ $row->status_id == 1 ? 'Sau khi khoá, chất chuẩn' : 'Sau khi mở khoá, chất chuẩn' }} &quot;{{ $row->standard_name }}&quot; {{ $row->status_id == 1 ? 'sẽ quay về dùng mặc định của danh mục và không còn tính là phòng đang dùng.' : 'sẽ dùng lại cấu hình riêng của phòng.' }}"
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
