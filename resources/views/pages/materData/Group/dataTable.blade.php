@include('pages.materData.shared.assets')

<div class="content-wrapper">
    <div class="md-page">
        <div class="card md-card">
            <div class="card-body">

                <div class="md-toolbar">
                    @perm('materData_create')
                        <button type="button" class="btn btn-primary btn-md-create">
                            <i class="fas fa-plus mr-1"></i> Thêm tổ mới
                        </button>
                    @endperm
                    <p class="hint">
                        <i class="fas fa-info-circle mr-1"></i>
                        Đang hoạt động {{ $datas->where('status_id', 1)->count() }}/{{ $datas->count() }} tổ.
                    </p>
                </div>

                <div class="table-responsive">
                    <table id="mdTable" class="table table-bordered table-hover w-100">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 60px">STT</th>
                                <th>Tên Tổ</th>
                                <th style="width: 200px">Phòng Ban</th>
                                <th style="width: 140px">Người Tạo</th>
                                <th class="text-center" style="width: 110px">Ngày Tạo</th>
                                <th class="text-center" style="width: 110px">Trạng Thái</th>
                                <th class="text-center" style="width: 120px">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($datas as $row)
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td class="font-weight-bold">{{ $row->name }}</td>
                                    <td>
                                        <span class="md-tag">{{ $row->department_short ?: ($row->department_name ?: '—') }}</span>
                                    </td>
                                    <td class="md-sub">{{ $row->created_by ?: '—' }}</td>
                                    <td class="text-center md-sub">
                                        {{ $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('d/m/Y') : '—' }}
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
                                                @perm('materData_update')
                                                    <button type="button" class="btn btn-sm btn-warning btn-md-edit"
                                                        title="Sửa tổ {{ $row->name }}"
                                                        data-row="{{ json_encode([
                                                            'id' => $row->id,
                                                            'name' => $row->name,
                                                            'department_id' => $row->department_id,
                                                        ]) }}">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                @endperm

                                                {{-- Badge số lần thay đổi, bấm vào để xem lịch sử --}}
                                                @include('pages.materData.shared.historyBadge', [
                                                    'count' => $historyCounts[$row->id] ?? 0,
                                                    'url' => route('pages.materData.group.history', ['id' => $row->id]),
                                                    'title' => $row->name,
                                                ])
                                            </span>

                                            @perm('materData_deActive')
                                                <form class="form-md-confirm d-inline"
                                                    action="{{ route('pages.materData.group.deActive') }}" method="POST"
                                                    data-title="{{ $row->status_id == 1 ? 'Khoá' : 'Mở khoá' }} tổ {{ $row->name }}?"
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
