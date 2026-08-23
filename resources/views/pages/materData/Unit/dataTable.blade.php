@include('pages.materData.shared.assets')

<div class="content-wrapper">
    <div class="md-page">

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
                                <th style="width: 160px">Ký Hiệu</th>
                                <th>Tên Đơn Vị Tính</th>
                                <th style="width: 140px">Nhóm</th>
                                <th class="text-right" style="width: 155px">Hệ Số Quy Đổi</th>
                                <th style="width: 130px">Người Tạo</th>
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
                                    <td><span class="md-tag">{{ $row->short_name }}</span></td>
                                    <td class="font-weight-bold">{{ $row->name }}</td>
                                    <td class="md-sub">{{ $groups[$row->unit_group]['label'] ?? $row->unit_group }}</td>
                                    <td class="text-right md-sub">
                                        1 {{ $row->short_name }} =
                                        <b>{{ rtrim(rtrim($row->factor_to_base, '0'), '.') }}</b>
                                        {{ $groups[$row->unit_group]['base'] ?? '' }}
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
                                                'short_name' => $row->short_name,
                                                'name' => $row->name,
                                                'unit_group' => $row->unit_group,
                                                'factor_to_base' => rtrim(rtrim($row->factor_to_base, '0'), '.'),
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

<script>
    /* Nhắc người dùng đơn vị gốc và hệ số mẫu của nhóm đang chọn */
    document.addEventListener('DOMContentLoaded', function() {

        function refreshHint($select) {
            var $hint = $select.closest('.modal-body').find('.unit-hint');

            if (!$hint.length) return;

            var group = $select.val();
            var base = $select.find('option:selected').data('base');
            var all = $hint.data('suggestions') || {};
            var samples = all[group] || {};

            var text = 'Đơn vị gốc của nhóm là ' + base + ', hệ số của ' + base + ' là 1.';
            var parts = Object.keys(samples).map(function(key) {
                return '1 ' + key + ' = ' + samples[key] + ' ' + base;
            });

            if (parts.length) text += ' Ví dụ: ' + parts.join(', ') + '.';

            if (group === 'count') {
                text = 'Đơn vị đếm/bao bì (thùng, chai, bao) để hệ số 1. ' +
                    'Nhóm này không tự quy đổi sang kg hay lít được, phải khai báo quy cách đóng gói cho từng mặt hàng.';
            }

            $hint.find('.unit-hint-text').text(text);
        }

        $(document).on('change', '.unit-group', function() {
            refreshHint($(this));
        });

        // Đổ dữ liệu xong mới vẽ lại gợi ý cho đúng nhóm của bản ghi đang sửa
        $(document).on('click', '.btn-md-edit', function() {
            $('#updateModal').find('.unit-group').trigger('change');
        });

        $(document).on('click', '.btn-md-create', function() {
            $('#createModal').find('.unit-group').trigger('change');
        });

        $('.unit-group').each(function() {
            refreshHint($(this));
        });
    });
</script>
