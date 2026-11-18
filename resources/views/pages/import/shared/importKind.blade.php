{{--
| Ô "Loại Nhập" của 4 sổ nhập (VT / HC / CC / VPP): Nhập mới, hoặc Nhận từ <phòng> khi lô do
| phòng ban khác cấp phát qua đề nghị liên phòng ban (controller join from_department_*).
| Biến vào: $row
--}}
@if (! empty($row->from_department_name))
    <span class="imp-kind is-transfer" title="Nhận chuyển từ {{ $row->from_department_name }}">
        <i class="fas fa-people-arrows mr-1"></i>Nhận từ {{ $row->from_department_short ?: $row->from_department_name }}
    </span>
@else
    <span class="imp-kind is-new"><i class="fas fa-plus mr-1"></i>Nhập mới</span>
@endif
