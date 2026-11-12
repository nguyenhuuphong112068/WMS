@include('pages.category.shared.assets')

@php
    use App\Support\VppClassification;
@endphp

<div class="card md-card">
    <div class="card-body">

        <div class="md-toolbar">
            <div class="d-flex align-items-center flex-wrap" style="gap: 10px">
                @perm('category_vpp_create')
                    <button type="button" class="btn btn-primary btn-md-create">
                        <i class="fas fa-plus mr-1"></i> Thêm mới
                    </button>
                @endperm

                <div class="md-filter">
                    <label for="mdClassFilter"><i class="fas fa-filter mr-1"></i> Nhóm dự trù</label>
                    <select id="mdClassFilter" class="form-control form-control-sm" multiple
                        data-placeholder="Tất cả">
                        <option value="none">Chưa phân loại</option>
                        @foreach (VppClassification::formGroups() as $group)
                            @php $filterKey = str_replace('classification.', '', $group['errorKey']); @endphp
                            @foreach ($group['options'] as $value => $name)
                                <option value="{{ $filterKey }}:{{ $value }}">{{ $name }}</option>
                            @endforeach
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        @include('pages.category.shared.approvalFilter', ['apfTarget' => 'mdTable'])

        <div class="table-responsive">
            <table id="mdTable" class="table table-bordered table-hover w-100" data-deferred>
                <thead>
                    <tr>
                        <th class="text-center" style="width: 55px">STT</th>
                        <th style="width: 100px">Mã VPP</th>
                        <th>Tên VPP</th>
                        <th>Nhà Sản Xuất</th>
                        <th class="text-center" style="width: 80px">Đơn Vị</th>
                        <th style="width: 190px">Nhóm Dự Trù</th>
                        <th style="width: 120px">Người Tạo</th>
                        <th class="text-center" style="width: 100px">Ngày Tạo</th>
                        <th class="text-center" style="width: 125px">Duyệt</th>
                        <th class="text-center" style="width: 100px">Sử Dụng</th>
                        <th class="text-center" style="width: 215px">Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    @deferredRows('mdTable')
                    @foreach ($datas as $row)
                        @php
                            $classification = VppClassification::decode($row->classification);
                            $chips = VppClassification::chips($row->classification);
                            // Chuỗi "tiêu chí:giá trị" để bộ lọc phía trên đọc được mà không phải dò chữ
                            $classKeys = collect($classification)->map(fn($value, $key) => $key . ':' . $value)->implode(' ');
                            // Màu nền mã theo tình trạng: khoá thắng duyệt, chưa duyệt thì vẫn vàng chờ
                            $catCodeStatus = $row->status_id == 0 ? 'locked' : ($row->app_status === 'approved' ? 'approved' : 'pending');
                        @endphp
                        <tr data-classification="{{ $classKeys }}"
                            data-approval="{{ $row->status_id == 0 ? 'locked' : ($row->app_status ?: 'pending') }}"
                            data-purchasing="{{ $row->purchasing_department ? 'purchasing_department:' . $row->purchasing_department : '' }}">
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
                            <td class="text-center">
                                @if ($row->unit_short_name)
                                    <span class="md-tag" title="{{ $row->unit_name }}">{{ $row->unit_short_name }}</span>
                                @else
                                    <span class="md-empty">—</span>
                                @endif
                            </td>
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
                                    'permPrefix' => 'category_vpp_',
                                    'row' => $row,
                                    'label' => $mdLabel,
                                    'title' => $row->material_name,
                                    'historyCount' => (int) ($historyCounts[$row->id] ?? 0),
                                    'editData' => [
                                        'id' => $row->id,
                                        'code' => $row->code,
                                        'material_names_id' => $row->material_names_id,
                                        'material_names_id_text' => $row->material_name,
                                        'manufacturers_id' => $row->manufacturers_id,
                                        'unit_id' => $row->unit_id,
                                        'classification' => (object) $classification,
                                        'purchasing_department' => $row->purchasing_department,
                                    ],
                                ])
                            </td>
                        </tr>
                    @endforeach
                    @enddeferredRows
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

    .md-filter .select2-container {
        min-width: 320px;
        max-width: 640px;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var mdClassWant = [];

        $('#mdClassFilter').select2({
            theme: 'bootstrap4',
            width: 'style',
            placeholder: 'Tất cả',
            closeOnSelect: false,
            allowClear: true
        });

        /*
         * ---------- Lọc bảng danh mục công ty theo nhiều nhóm dự trù ----------
         * Các nhóm chọn cùng lúc nối bằng HOẶC (Nhóm 1 hoặc Nhóm 2).
         * "Chưa phân loại" cộng thêm (HOẶC) các dòng chưa khai phân loại.
         */
        $.fn.dataTable.ext.search.push(function(settings, data, index, rowData) {
            if (settings.nTable.id !== 'mdTable') return true;
            if (!mdClassWant.length) return true;

            // Dòng nạp sau (data-deferred) chưa có <tr> nên đọc thuộc tính từ dữ liệu dòng
            var tr = settings.aoData[index].nTr;
            var classKeys = (mdDeferred.rowAttr(rowData, tr, 'classification') || '').trim();
            var keys = ' ' + classKeys + ' ' + (mdDeferred.rowAttr(rowData, tr, 'purchasing') || '') + ' ';

            var wantNone = false;
            var groups = {};
            mdClassWant.forEach(function(value) {
                if (value === 'none') {
                    wantNone = true;
                    return;
                }
                var criterion = value.split(':')[0];
                (groups[criterion] = groups[criterion] || []).push(value);
            });

            if (wantNone && classKeys === '') return true;

            var criteria = Object.keys(groups);
            if (!criteria.length) return false;

            return criteria.every(function(criterion) {
                return groups[criterion].some(function(value) {
                    return keys.indexOf(' ' + value + ' ') !== -1;
                });
            });
        });

        $(document).on('change', '#mdClassFilter', function() {
            mdClassWant = $(this).val() || [];
            $('#mdTable').DataTable().draw();
        });
    });
</script>
