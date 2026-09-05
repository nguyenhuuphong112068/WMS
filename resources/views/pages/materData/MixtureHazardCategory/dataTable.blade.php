@include('pages.materData.shared.assets')

@php
    /** Ngưỡng kg: bỏ số 0 thừa ở phần thập phân (50000.000 -> 50000). */
    $kg = fn($value) => $value === null || $value === ''
        ? null
        : rtrim(rtrim(number_format((float) $value, 3, '.', ','), '0'), '.');
@endphp

<div class="content-wrapper">
    <div class="md-page">

        <div class="card md-card">
            <div class="card-body">

                <div class="md-toolbar">
                    @perm('materData_create')
                        <button type="button" class="btn btn-primary btn-md-create">
                            <i class="fas fa-plus mr-1"></i> Thêm mới
                        </button>
                    @endperm

                </div>

                <div class="table-responsive">
                    <table id="mdTable" class="table table-bordered table-hover w-100">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 55px">STT</th>
                                <th style="width: 90px">Mã</th>
                                <th style="width: 190px">Phần Bảng B</th>
                                <th>Nhóm Phân Loại</th>
                                <th class="text-right" style="width: 150px">Ngưỡng Tồn Trữ (kg)</th>
                                <th style="width: 130px">Người Tạo</th>
                                <th class="text-center" style="width: 100px">Ngày Tạo</th>
                                <th class="text-center" style="width: 125px">Duyệt</th>
                                <th class="text-center" style="width: 100px">Sử Dụng</th>
                                <th class="text-center" style="width: 170px">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($datas as $row)
                                @php $groupLabel = $groups[$row->hazard_group] ?? ''; @endphp
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td class="md-sub">{{ $row->code }}</td>
                                    <td
                                        data-order="{{ array_search($row->hazard_group, array_keys($groups)) }}{{ str_pad($row->ordinal, 3, '0', STR_PAD_LEFT) }}">
                                        <span class="mhc-group">{{ $row->hazard_group }}.{{ $row->ordinal }}</span>
                                        <div class="md-sub">{{ $groupLabel }}</div>
                                    </td>
                                    <td>
                                        <span class="mhc-name">{!! nl2br(e($row->name)) !!}</span>
                                    </td>
                                    <td class="text-right" data-order="{{ $row->threshold_kg }}">
                                        <span class="mhc-threshold">{{ $kg($row->threshold_kg) }}</span>
                                        @if ($row->threshold_basis === 'net')
                                            <div class="md-sub">khối lượng tịnh (net)</div>
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
                                        @include('pages.materData.shared.rowActions', [
                                            'prefix' => $mdRoute,
                                            'row' => $row,
                                            'historyCount' => $historyCounts[$row->id] ?? 0,
                                            'label' => $mdLabel,
                                            'title' => $row->hazard_group . '.' . $row->ordinal,
                                            'editData' => [
                                                'id' => $row->id,
                                                'hazard_group' => $row->hazard_group,
                                                'name' => $row->name,
                                                'threshold_kg' => $kg($row->threshold_kg),
                                                'threshold_basis' => $row->threshold_basis ?? '',
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

@once
    <style>
        .mhc-group {
            font-weight: 700;
            color: var(--primary-dark);
        }

        .mhc-name {
            white-space: normal;
            line-height: 1.5;
        }

        .mhc-threshold {
            font-weight: 700;
            color: var(--primary-dark);
        }
    </style>
@endonce
