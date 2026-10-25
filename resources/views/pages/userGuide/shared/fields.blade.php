{{--
| Bảng giải thích các ô nhập của form Thêm mới / Cập nhật.
| Biến vào: $fields = [['Tên ô', bắt buộc (bool), 'Giải thích - được phép chứa HTML'], ...]
--}}
<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead>
            <tr>
                <th style="width: 210px">Ô nhập</th>
                <th class="text-center" style="width: 90px">Bắt buộc</th>
                <th>Cách nhập</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($fields as $field)
                <tr>
                    <td>{{ $field[0] }}</td>
                    <td class="text-center">
                        @if ($field[1])
                            <span class="ug-req" title="Bắt buộc nhập">*</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>{!! $field[2] !!}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
