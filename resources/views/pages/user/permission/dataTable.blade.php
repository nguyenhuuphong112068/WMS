<style>
    .permission-list-card {
        border-radius: var(--border-radius-lg, 12px);
        box-shadow: var(--shadow-sm, 0 2px 6px rgba(0, 0, 0, .06));
        border: none;
    }

    .permission-list-title {
        text-transform: uppercase;
        font-weight: 700;
        letter-spacing: 1px;
        color: var(--primary, #2E7BC4);
        margin: 0;
    }

    #data_table_permission thead th {
        background-color: var(--primary-soft, #EAF3FC);
        color: var(--primary, #2E7BC4);
        font-weight: 700;
        border-bottom: 2px solid var(--primary-lighter, #9CC7EE);
    }

    #data_table_permission tbody tr:hover {
        background-color: var(--primary-soft, #EAF3FC);
    }

    .permission-group-badge {
        background-color: var(--primary-soft, #EAF3FC);
        color: var(--primary, #2E7BC4);
        border-radius: var(--border-radius-md, 8px);
        padding: 3px 10px;
        font-size: 13px;
        font-weight: 600;
        white-space: nowrap;
    }

    .permission-code {
        font-size: 12px;
        color: #94A3B8;
    }
</style>

<div class="content-wrapper">
    <!-- Main content -->
    <section class="content">
        <div class="card permission-list-card mt-3">

            <div class="card-header"
                style="background-color: #fff; border-bottom: 1px solid var(--primary-soft, #EAF3FC); padding: 20px">
                <h3 class="permission-list-title">
                    <i class="fas fa-key mr-2"></i> Danh Sách Quyền
                </h3>
            </div>

            <!-- /.card-Body -->
            <div class="card-body" style="padding: 20px">
                <table id="data_table_permission" class="table table-bordered table-striped" style="font-size: 15px">
                    <thead style="position: sticky; top: 60px; z-index: 1020">
                        <tr>
                            <th style="width: 60px">STT</th>
                            <th style="width: 200px">Nhóm Quyền</th>
                            <th>Phân Quyền</th>
                            <th>Diễn Giải</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($datas as $data)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td><span class="permission-group-badge">{{ $data->group_name ?: 'Khác' }}</span></td>
                                <td>
                                    <div>{{ $data->display_name ?: $data->name }}</div>
                                    <div class="permission-code">{{ $data->name }}</div>
                                </td>
                                <td>{{ $data->description }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <!-- /.card-body -->
        </div>
        <!-- /.card -->
    </section>
    <!-- /.content -->
</div>
