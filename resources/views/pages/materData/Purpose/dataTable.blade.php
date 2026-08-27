@include('pages.materData.shared.assets')

<div class="content-wrapper">
    <div class="md-page">
        <div class="card md-card">
            <div class="card-body">

                <div class="md-toolbar">
                    <button type="button" class="btn btn-primary btn-md-create">
                        <i class="fas fa-plus mr-1"></i> Thêm chỉ tiêu kiểm
                    </button>
                    <p class="hint">
                        <i class="fas fa-info-circle mr-1"></i>
                        Đang hoạt động {{ $datas->where('status_id', 1)->count() }}/{{ $datas->count() }} chỉ tiêu kiểm.
                    </p>
                </div>

                <div class="table-responsive">
                    <table id="mdTable" class="table table-bordered table-hover w-100">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 60px">STT</th>
                                <th>Tên Chỉ Tiêu Kiểm</th>
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
                                            <button type="button" class="btn btn-sm btn-warning btn-md-edit"
                                                title="Sửa chỉ tiêu {{ $row->name }}"
                                                data-row="{{ json_encode([
                                                    'id' => $row->id,
                                                    'name' => $row->name,
                                                ]) }}">
                                                <i class="fas fa-edit"></i>
                                            </button>

                                            <form class="form-md-confirm d-inline"
                                                action="{{ route('pages.materData.purpose.deActive') }}" method="POST"
                                                data-title="{{ $row->status_id == 1 ? 'Khoá' : 'Mở khoá' }} chỉ tiêu {{ $row->name }}?"
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
    </div>
</div>
