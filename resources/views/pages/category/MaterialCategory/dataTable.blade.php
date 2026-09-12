@include('pages.category.shared.assets')

@php
    use App\Support\MaterialClassification;
@endphp

<div class="card md-card">
    <div class="card-body">

        <div class="md-toolbar">
            <div class="d-flex align-items-center flex-wrap" style="gap: 10px">
                @perm('category_material_create')
                    <button type="button" class="btn btn-primary btn-md-create">
                        <i class="fas fa-plus mr-1"></i> Thêm mới
                    </button>
                @endperm

                <div class="md-filter">
                    <label for="mdClassFilter"><i class="fas fa-filter mr-1"></i> Phân loại</label>
                    <select id="mdClassFilter" class="form-control form-control-sm">
                        <option value="all">Tất cả</option>
                        <option value="none">Chưa phân loại</option>
                        @foreach (MaterialClassification::CRITERIA as $key => $criterion)
                            <optgroup label="{{ $criterion['label'] }}">
                                @foreach ($criterion['options'] as $value => $name)
                                    <option value="{{ $key }}:{{ $value }}">{{ $name }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table id="mdTable" class="table table-bordered table-hover w-100">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 55px">STT</th>
                        <th style="width: 100px">Mã Vật Tư</th>
                        <th>Tên Vật Tư</th>
                        <th>Nhà Sản Xuất</th>
                        <th>Thông Tin Kỹ Thuật</th>
                        <th style="width: 190px">Phân Loại</th>
                        <th style="width: 110px">Bộ Phận Mua Hàng</th>
                        <th class="text-center" style="width: 95px">Thời Gian Đặt Hàng</th>
                        <th style="width: 170px">Phòng Ban Đang Dùng</th>
                        <th style="width: 120px">Người Tạo</th>
                        <th class="text-center" style="width: 100px">Ngày Tạo</th>
                        <th class="text-center" style="width: 125px">Duyệt</th>
                        <th class="text-center" style="width: 100px">Sử Dụng</th>
                        <th class="text-center" style="width: 215px">Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($datas as $row)
                        @php
                            $usingDepts = $departmentsByCategory[$row->id] ?? collect();
                            $classification = MaterialClassification::decode($row->classification);
                            $chips = MaterialClassification::chips($row->classification);
                            // Chuỗi "tiêu chí:giá trị" để bộ lọc phía trên đọc được mà không phải dò chữ
                            $classKeys = collect($classification)->map(fn($value, $key) => $key . ':' . $value)->implode(' ');
                            // Màu nền mã theo tình trạng: khoá thắng duyệt, chưa duyệt thì vẫn vàng chờ
                            $catCodeStatus = $row->status_id == 0 ? 'locked' : ($row->app_status === 'approved' ? 'approved' : 'pending');
                        @endphp
                        <tr data-classification="{{ $classKeys }}">
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td><span class="cat-code {{ $catCodeStatus }}">{{ $row->code }}</span></td>
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
                                @if ($chips)
                                    <div class="cat-chips">
                                        @foreach ($chips as $chip)
                                            <span class="cat-chip {{ $chip['class'] }}"
                                                title="{{ $chip['label'] }}">{{ $chip['short'] }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="md-empty">Chưa phân loại</span>
                                @endif
                            </td>
                            <td class="md-sub">
                                {{ MaterialClassification::purchasingLabel($row->purchasing_department) ?: '—' }}
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
                                        'classification' => (object) $classification,
                                        'purchasing_department' => $row->purchasing_department,
                                        'lead_time_days' => $row->lead_time_days,
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

<style>
    /* Ô lọc nhanh trên thanh công cụ của bảng */
    .md-filter {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .md-filter label {
        margin: 0;
        font-size: 0.83rem;
        font-weight: 700;
        color: var(--primary-dark);
        white-space: nowrap;
    }

    .md-filter .form-control {
        width: auto;
        min-width: 200px;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var mdClassWant = 'all';

        /* ---------- Lọc bảng danh mục công ty theo một lựa chọn phân loại ---------- */
        $.fn.dataTable.ext.search.push(function(settings, data, index) {
            if (settings.nTable.id !== 'mdTable') return true;
            if (mdClassWant === 'all') return true;

            var keys = ($(settings.aoData[index].nTr).attr('data-classification') || '').trim();

            if (mdClassWant === 'none') return keys === '';

            return (' ' + keys + ' ').indexOf(' ' + mdClassWant + ' ') !== -1;
        });

        $(document).on('change', '#mdClassFilter', function() {
            mdClassWant = this.value;
            $('#mdTable').DataTable().draw();
        });
    });
</script>
