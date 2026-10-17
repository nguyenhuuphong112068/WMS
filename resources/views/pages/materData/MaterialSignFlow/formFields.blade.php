{{--
| Khối khai báo một VAI TRÒ NHẬN TRÌNH KÝ đề nghị cấp phát vật tư.
| Dùng chung cho modal Thêm mới và modal Cập nhật.
|
| Biến vào:
| - $bag      : bag lỗi của form đang dựng
| - $initial  : dữ liệu dựng sẵn khi validate lỗi: ['signers' => [user_id], 'conditions' => [[['key','value']]]]
| - $roles, $signerOptions, $criteriaOptions: từ Controller
|
| Danh sách người ký và các nhóm điều kiện do JS dựng (xem dataTable): đọc data-initial
| của .sf-form khi mở trang, hoặc data-row của nút Sửa khi mở modal Cập nhật.
--}}

@php
    use App\Support\MaterialSignFlow;

    $initial = $initial ?? ['signers' => [], 'conditions' => []];

    // Nhãn của một người ký: "Họ tên — Vai trò chính · Phòng"
    $signerLabel = fn($person) => ($person->fullName ?: $person->userName)
        . ($person->role_name ? ' — ' . $person->role_name : '')
        . ($person->department_short ? ' · ' . $person->department_short : '');
@endphp

<div class="sf-form" data-initial="{{ json_encode($initial) }}">
    <div class="form-row">
        <div class="form-group col-md-8">
            <label>Vai Trò Nhận Trình Ký <span class="text-danger">*</span></label>
            <select name="role_id" class="form-control sf-role {{ $bag->has('role_id') ? 'is-invalid' : '' }}" required>
                <option value="">-- Chọn vai trò --</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}" {{ (string) old('role_id') === (string) $role->id ? 'selected' : '' }}>
                        {{ $role->name }}
                    </option>
                @endforeach
            </select>
            @if ($bag->has('role_id'))
                <span class="md-error">{{ $bag->first('role_id') }}</span>
            @endif
        </div>

        <div class="form-group col-md-4">
            <label>Cấp Ký <span class="text-danger">*</span></label>
            <input type="number" name="level" min="1" max="{{ MaterialSignFlow::MAX_LEVEL }}" step="1"
                class="form-control {{ $bag->has('level') ? 'is-invalid' : '' }}" value="{{ old('level') }}"
                placeholder="1 = ký đầu tiên" title="Đề nghị ký lần lượt từ cấp nhỏ tới cấp lớn" required>
            @if ($bag->has('level'))
                <span class="md-error">{{ $bag->first('level') }}</span>
            @endif
        </div>
    </div>

    <div class="form-group">
        <label>Người Ký <span class="text-danger">*</span>
            <small class="text-muted font-weight-normal">— chỉ cần 1 người ký là xong bước</small>
        </label>
        <div class="sf-users"></div>
        <button type="button" class="btn btn-sm btn-outline-primary sf-user-add">
            <i class="fas fa-user-plus mr-1"></i> Thêm người ký
        </button>
        @foreach ($bag->get('signers') as $message)
            <span class="md-error">{{ $message }}</span>
        @endforeach
    </div>

    <div class="form-group mb-0">
        <label>Điều Kiện Nhận Trình Ký</label>
        <div class="sf-conds"></div>
        <div class="sf-cond-always">
            <i class="fas fa-infinity"></i>
            <span>Không có nhóm điều kiện — vai trò này nhận trình ký <b>mọi đề nghị</b>.</span>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary sf-group-add" data-max="{{ MaterialSignFlow::MAX_GROUPS }}">
            <i class="fas fa-plus mr-1"></i> <span class="sf-group-add-text">Thêm nhóm điều kiện</span>
        </button>
        @foreach ($bag->get('conditions') as $message)
            <span class="md-error">{{ $message }}</span>
        @endforeach
    </div>
</div>

{{-- Mẫu một ô chọn người ký - để trong <script type="text/html"> nên không bị gửi lên cùng form. --}}
<script type="text/html" class="sf-user-template">
    <div class="sf-user-row">
        <select name="signers[]" class="form-control sf-user" required>
            <option value="">-- Chọn người ký --</option>
            @foreach ($signerOptions as $person)
                <option value="{{ $person->id }}" data-roles="{{ implode(',', $person->role_ids) }}"
                    title="{{ $signerLabel($person) }}">{{ $signerLabel($person) }}</option>
            @endforeach
        </select>
        <button type="button" class="btn btn-sm btn-outline-danger sf-user-del" title="Bớt người ký này">
            <i class="fas fa-minus"></i>
        </button>
    </div>
</script>
