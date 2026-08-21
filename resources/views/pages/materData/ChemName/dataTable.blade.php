@include('pages.materData.shared.assets')

@php
    /*
    | Đưa công thức về dạng chữ thường (H₂SO₄ -> H2SO4) để nạp vào data-search.
    | Nhờ vậy người dùng gõ "H2SO4" ở ô tìm kiếm vẫn tìm ra dòng dữ liệu.
    */
    $fxPlain = fn($value) => strtr((string) $value, [
        '₀' => '0', '₁' => '1', '₂' => '2', '₃' => '3', '₄' => '4',
        '₅' => '5', '₆' => '6', '₇' => '7', '₈' => '8', '₉' => '9',
        '₊' => '+', '₋' => '-', '₌' => '=', '₍' => '(', '₎' => ')',
        '⁰' => '0', '¹' => '1', '²' => '2', '³' => '3', '⁴' => '4',
        '⁵' => '5', '⁶' => '6', '⁷' => '7', '⁸' => '8', '⁹' => '9',
        '⁺' => '+', '⁻' => '-', '⁼' => '=', '⁽' => '(', '⁾' => ')',
        '·' => '.',
    ]);
@endphp

<div class="content-wrapper">
    <div class="md-page">

        <div class="md-hero">
            <div>
                <h1><i class="{{ $mdIcon }}"></i> {{ $mdTitle }}</h1>
                <p>Khai báo danh mục hoá chất dùng chung cho toàn hệ thống. Bản ghi phải được duyệt trước khi sử dụng.</p>
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
                                <th>Tên Hoá Chất</th>
                                <th>Tên Hoạt Chất</th>
                                <th style="width: 130px">Số CAS</th>
                                <th style="width: 130px">Số Tài Liệu</th>
                                <th style="width: 150px">Công Thức</th>
                                <th style="width: 140px">Người Tạo</th>
                                <th class="text-center" style="width: 105px">Ngày Tạo</th>
                                <th class="text-center" style="width: 130px">Duyệt</th>
                                <th class="text-center" style="width: 105px">Sử Dụng</th>
                                <th class="text-center" style="width: 175px">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($datas as $row)
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td class="font-weight-bold">{{ $row->name }}</td>
                                    <td class="md-sub">
                                        {{ $row->active_ingredient_name ?: '' }}
                                        @unless ($row->active_ingredient_name)
                                            <span class="md-empty">—</span>
                                        @endunless
                                    </td>
                                    <td>
                                        @if ($row->cas_no)
                                            <span class="md-tag">{{ $row->cas_no }}</span>
                                        @else
                                            <span class="md-empty">—</span>
                                        @endif
                                    </td>
                                    <td class="md-sub">{{ $row->doc_no ?: '—' }}</td>
                                    <td data-search="{{ $row->chemical_formula ? $row->chemical_formula . ' ' . $fxPlain($row->chemical_formula) : '' }}">
                                        @if ($row->chemical_formula)
                                            <span class="md-formula">{{ $row->chemical_formula }}</span>
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
                                        @include('pages.materData.shared.rowActions', [
                                            'prefix' => $mdRoute,
                                            'row' => $row,
                                            'label' => $mdLabel,
                                            'title' => $row->name,
                                            'editData' => [
                                                'id' => $row->id,
                                                'name' => $row->name,
                                                'active_ingredient_name' => $row->active_ingredient_name,
                                                'cas_no' => $row->cas_no,
                                                'doc_no' => $row->doc_no,
                                                'chemical_formula' => $row->chemical_formula,
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
