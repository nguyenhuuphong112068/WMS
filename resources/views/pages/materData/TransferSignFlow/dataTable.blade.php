@include('pages.materData.shared.assets')

@php
    use App\Support\MaterialTransferSignFlow;
@endphp

<div class="content-wrapper">
    <div class="md-page">

        <div class="card md-card">
            <div class="card-body">

                <div class="md-toolbar">
                    @perm('materData_material_create')
                        <button type="button" class="btn btn-primary btn-md-create">
                            <i class="fas fa-plus mr-1"></i> Thêm bước ký
                        </button>
                    @endperm
                </div>

                <div class="table-responsive">
                    <table id="mdTable" class="table table-bordered table-hover w-100">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 60px">STT</th>
                                <th style="width: 210px">Loại Đề Nghị</th>
                                <th style="width: 200px">Công Ty Phòng Đề Nghị</th>
                                <th class="text-center" style="width: 80px">Bước</th>
                                <th style="width: 180px">Vai Trò Ký</th>
                                <th>Phạm Vi Phòng Ban</th>
                                <th style="width: 140px">Người Tạo</th>
                                <th class="text-center" style="width: 110px">Sử Dụng</th>
                                <th class="text-center" style="width: 120px">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($datas as $row)
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td data-order="{{ $row->kind }}">
                                        <span class="tsf-kind {{ $row->kind }}">{{ MaterialTransferSignFlow::KINDS[$row->kind] ?? $row->kind }}</span>
                                    </td>
                                    <td>{{ $row->company_short ?: ($row->company_name ?: 'NA') }}</td>
                                    <td class="text-center" data-order="{{ $row->step_no }}">
                                        <span class="tsf-step">{{ $row->step_no }}</span>
                                    </td>
                                    <td class="font-weight-bold">{{ $row->role_name ?: 'NA' }}</td>
                                    <td>
                                        {{ MaterialTransferSignFlow::SCOPES[$row->scope] ?? $row->scope }}
                                        @if ($row->scope === MaterialTransferSignFlow::SCOPE_DEPARTMENT)
                                            : <b>{{ $row->department_name ?: 'NA' }}</b>
                                        @endif
                                    </td>
                                    <td class="md-sub">
                                        {{ $row->created_by ?: 'NA' }}
                                        <br><small>{{ $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('d/m/Y') : '' }}</small>
                                    </td>
                                    <td class="text-center">
                                        @if ($row->status_id == 1)
                                            <span class="badge badge-success">Hoạt động</span>
                                        @else
                                            <span class="badge badge-danger">Đã khoá</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="md-actions">
                                            <span class="md-btn-wrap">
                                                @perm('materData_material_update')
                                                    <button type="button" class="btn btn-sm btn-warning btn-md-edit" title="Sửa"
                                                        data-row="{{ json_encode([
                                                            'id' => $row->id,
                                                            'kind' => $row->kind,
                                                            'company_id' => $row->company_id,
                                                            'step_no' => $row->step_no,
                                                            'role_id' => $row->role_id,
                                                            'scope' => $row->scope,
                                                            'department_id' => $row->department_id,
                                                        ]) }}">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                @endperm

                                                @include('pages.materData.shared.historyBadge', [
                                                    'count' => $historyCounts[$row->id] ?? 0,
                                                    'url' => route($mdRoute . 'history', ['id' => $row->id]),
                                                    'title' => 'Bước ' . $row->step_no,
                                                ])
                                            </span>

                                            @perm('materData_material_deActive')
                                                <form class="form-md-confirm d-inline" data-require-reason="1"
                                                    action="{{ route($mdRoute . 'deActive') }}" method="POST"
                                                    data-title="{{ $row->status_id == 1 ? 'Khoá' : 'Mở khoá' }} {{ $mdLabel }} {{ $row->step_no }}?"
                                                    data-text="{{ $row->status_id == 1 ? 'Sau khi khoá, đề nghị gửi mới sẽ không qua bước ký này.' : 'Sau khi mở khoá, đề nghị gửi mới sẽ qua bước ký này.' }}"
                                                    data-danger="{{ $row->status_id == 1 ? '1' : '' }}">
                                                    @csrf
                                                    <input type="hidden" name="id" value="{{ $row->id }}">
                                                    <button type="submit"
                                                        class="btn btn-sm btn-{{ $row->status_id == 1 ? 'secondary' : 'primary' }}"
                                                        title="{{ $row->status_id == 1 ? 'Khoá' : 'Mở khoá' }}">
                                                        <i class="fas fa-{{ $row->status_id == 1 ? 'lock' : 'unlock' }}"></i>
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
    </div>
</div>

<style>
    .tsf-kind {
        display: inline-block;
        border-radius: 999px;
        padding: 2px 11px;
        font-size: 0.78rem;
        font-weight: 700;
        background: var(--primary-soft);
        color: var(--primary-dark);
        border: 1px solid var(--primary-lighter);
    }

    .tsf-kind.vpp {
        background: #FEF3C7;
        color: #B45309;
        border-color: #FCD34D;
    }

    .tsf-step {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 26px;
        height: 26px;
        border-radius: 50%;
        background: var(--primary);
        color: #fff;
        font-weight: 700;
        font-size: 0.8rem;
    }
</style>
