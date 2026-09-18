{{--
| Khối khai báo một QUY TRÌNH TRÌNH KÝ ĐỀ NGHỊ CẤP PHÁT VẬT TƯ.
| Dùng chung cho modal Thêm mới và modal Cập nhật.
|
| Biến vào:
| - $bag            : bag lỗi của form đang dựng
| - $selected       : [tiêu chí => giá trị] của điều kiện phân loại danh mục chung
| - $selectedClass  : id phân loại của phòng đang chọn (rỗng = Tất cả)
| - $selectedSteps  : mảng role_id theo đúng thứ tự ký
| - $selectedSigners: [chỉ số bước => mảng user_id được ký bước đó], cùng chỉ số $selectedSteps
| - $classifications: phân loại của phòng đang hoạt động
| - $roles          : vai trò chọn làm người phê duyệt từng bước
| - $signerOptions  : người được chọn làm người ký, kèm role_ids để lọc theo vai trò
--}}

@php
    use App\Support\MaterialSignFlow;

    $selected = $selected ?? [];
    $selectedClass = $selectedClass ?? null;
    $selectedSteps = $selectedSteps ?: [null];
    $selectedSigners = $selectedSigners ?? [];
    $groups = MaterialSignFlow::formGroups($selected);

    // Nhãn của một người ký: "Họ tên — Vai trò chính · Phòng"
    $signerLabel = fn($person) => ($person->fullName ?: $person->userName)
        . ($person->role_name ? ' — ' . $person->role_name : '')
        . ($person->department_short ? ' · ' . $person->department_short : '');
@endphp

<div class="form-group">
    <label>Tên Quy Trình <span class="text-danger">*</span></label>
    <input type="text" name="name" maxlength="150"
        class="form-control {{ $bag->has('name') ? 'is-invalid' : '' }}" value="{{ old('name') }}"
        placeholder="Ví dụ: Vật tư giá cao - nhóm Thiết bị" required>
    @if ($bag->has('name'))
        <span class="md-error">{{ $bag->first('name') }}</span>
    @endif
</div>

<div class="form-group">
    <label>Điều Kiện Áp Dụng - Phân Loại Danh Mục Vật Tư Chung</label>
    <div class="sf-check-group {{ $bag->has('criteria') ? 'is-invalid' : '' }}">
        @foreach ($groups as $group)
            <div class="sf-subgroup">
                <span class="sf-subgroup-title">
                    <i class="{{ $group['icon'] }} mr-1"></i> {{ $group['label'] }}
                </span>

                <div class="sf-options">
                    @foreach ($group['options'] as $value => $name)
                        <label class="sf-check-item {{ $group['value'] === (string) $value ? 'is-checked' : '' }}">
                            <input type="radio" class="sf-check-input" name="{{ $group['name'] }}"
                                value="{{ $value }}" {{ $group['value'] === (string) $value ? 'checked' : '' }}>
                            <span>{{ $name }}</span>
                        </label>
                    @endforeach
                </div>

                @if ($bag->has($group['errorKey']))
                    <span class="md-error">{{ $bag->first($group['errorKey']) }}</span>
                @endif
            </div>
        @endforeach
    </div>
    @if ($bag->has('criteria'))
        <span class="md-error">{{ $bag->first('criteria') }}</span>
    @endif
</div>

<div class="form-group">
    <label>Điều Kiện Áp Dụng - Phân Loại Danh Mục Vật Tư Của Phòng</label>
    <select name="classification_id" class="form-control {{ $bag->has('classification_id') ? 'is-invalid' : '' }}">
        <option value="">{{ MaterialSignFlow::ANY_LABEL }}</option>
        @foreach ($classifications as $classification)
            <option value="{{ $classification->id }}"
                {{ (string) $selectedClass === (string) $classification->id ? 'selected' : '' }}>
                {{ $classification->name }}
            </option>
        @endforeach
    </select>
    @if ($bag->has('classification_id'))
        <span class="md-error">{{ $bag->first('classification_id') }}</span>
    @endif
</div>

<div class="form-group">
    <label>Các Bước Trình Ký <span class="text-danger">*</span></label>

    <div class="sf-steps" data-max="{{ MaterialSignFlow::MAX_STEPS }}">
        @foreach ($selectedSteps as $index => $roleId)
            @php $stepSigners = array_values((array) ($selectedSigners[$index] ?? [])) ?: [null]; @endphp
            <div class="sf-step">
                <div class="sf-step-head">
                    <span class="sf-step-no">{{ $loop->iteration }}</span>
                    <select name="steps[]" class="form-control sf-step-role" required>
                        <option value="">-- Chọn vai trò phê duyệt --</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}"
                                {{ (string) $roleId === (string) $role->id ? 'selected' : '' }}>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
                    <button type="button" class="btn btn-sm btn-outline-primary sf-user-add" title="Thêm người ký cho bước này">
                        <i class="fas fa-user-plus"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger sf-step-del" title="Bớt bước này">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="sf-step-users">
                    @foreach ($stepSigners as $userId)
                        <div class="sf-step-user-row">
                            <select name="signers[{{ $index }}][]" class="form-control sf-step-user" required>
                                <option value="">-- Chọn người ký --</option>
                                @foreach ($signerOptions as $person)
                                    <option value="{{ $person->id }}" data-roles="{{ implode(',', $person->role_ids) }}"
                                        title="{{ $signerLabel($person) }}"
                                        {{ (string) $userId === (string) $person->id ? 'selected' : '' }}>
                                        {{ $signerLabel($person) }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-sm btn-outline-danger sf-user-del" title="Bớt người ký này">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <button type="button" class="btn btn-sm btn-outline-primary sf-step-add">
        <i class="fas fa-plus mr-1"></i> Thêm bước ký
    </button>

    @foreach (['steps', 'signers'] as $field)
        @if ($bag->has($field))
            <span class="md-error">{{ $bag->first($field) }}</span>
        @endif
    @endforeach
    @foreach ($selectedSteps as $index => $roleId)
        @foreach (array_merge($bag->get('steps.' . $index), $bag->get('signers.' . $index), $bag->get('signers.' . $index . '.*')) as $message)
            <span class="md-error">{{ $message }}</span>
        @endforeach
    @endforeach
</div>

{{-- Mẫu một dòng bước ký cho JS nhân bản. Để trong <script type="text/html"> nên nội dung
     không được trình duyệt dựng thành ô nhập, không bị gửi lên cùng form. --}}
<script type="text/html" class="sf-step-template">
    <div class="sf-step">
        <div class="sf-step-head">
            <span class="sf-step-no"></span>
            <select name="steps[]" class="form-control sf-step-role" required>
                <option value="">-- Chọn vai trò phê duyệt --</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                @endforeach
            </select>
            <button type="button" class="btn btn-sm btn-outline-primary sf-user-add" title="Thêm người ký cho bước này">
                <i class="fas fa-user-plus"></i>
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger sf-step-del" title="Bớt bước này">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="sf-step-users"></div>
    </div>
</script>

{{-- Mẫu một ô chọn người ký. Tên ô (signers[i][]) do JS gán lại theo vị trí của bước. --}}
<script type="text/html" class="sf-user-template">
    <div class="sf-step-user-row">
        <select name="signers[][]" class="form-control sf-step-user" required>
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
