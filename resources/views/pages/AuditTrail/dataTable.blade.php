<div class="content-wrapper">
    <!-- /.card-header -->
    <div class="card">

        <div class="card-header mt-4">
            <form action="{{ route('pages.auditTrail.list') }}" method="GET">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label for="startDate">Từ ngày</label>
                        <input type="date" name="startDate" id="startDate" class="form-control"
                            value="{{ $startDate }}" onchange="this.form.submit()">
                    </div>
                    <div class="col-md-3">
                        <label for="endDate">Đến ngày</label>
                        <input type="date" name="endDate" id="endDate" class="form-control"
                            value="{{ $endDate }}" onchange="this.form.submit()">
                    </div>
                </div>
            </form>
        </div>

        <!-- /.card-Body -->
        <div class="card-body">


            <table id="example1" class="table table-bordered table-striped">

                <thead style = "position: sticky; top: 60px; background-color: white; z-index: 1020">

                    <tr>
                        <th>STT</th>
                        <th>Thời Gian</th>
                        <th>Tên Người Dùng</th>
                        <th>Hoạt Động</th>
                        <th>Giá Trị Củ</th>
                        <th>Giá Trị Mới</th>
                    </tr>
                </thead>
                <tbody>

                    @foreach ($datas as $data)
                        <tr>
                            <td>{{ $loop->iteration }} </td>
                            <td>{{ \Carbon\Carbon::parse($data->created_at)->format('d/m/Y h:i') }}</td>
                            <td>{{ $data->fullName }}</td>
                            <td>{{ $data->action }}</td>
                            <td>{{ $data->old_values }}</td>
                            <td>{{ $data->new_values }}</td>
                        </tr>
                    @endforeach

                </tbody>
            </table>
        </div>
        <!-- /.card-body -->
    </div>
    <!-- /.card -->
    <!-- /.content -->
</div>


<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>
<script>
    $(document).ready(function() {
        document.body.style.overflowY = "auto";
    });
</script>
