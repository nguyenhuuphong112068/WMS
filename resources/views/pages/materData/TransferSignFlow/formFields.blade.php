{{--
| Các ô của một bước ký (dùng chung modal Thêm / Sửa). Ô "Phòng cố định" chỉ bật khi phạm vi
| là "Phòng cố định". Biến vào: $bag (error bag), $prefix (tiền tố id để 2 modal không trùng).
--}}
@php
    use App\Support\MaterialTransferSignFlow;

    $tsfErr = fn ($field) => $bag->has($field) ? 'is-invalid' : '';
@endphp

<div class="form-row">
    <div class="form-group col-md-6">
        <label>Loại Đề Nghị <span class="text-danger">*</span></label>
        <select name="kind" class="form-control {{ $tsfErr('kind') }}" required>
            @foreach (MaterialTransferSignFlow::KINDS as $key => $label)
                <option value="{{ $key }}" {{ old('kind') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        @if ($bag->has('kind')) <span class="md-error">{{ $bag->first('kind') }}</span> @endif
    </div>

    <div class="form-group col-md-6">
        <label>Công Ty Của Phòng Đề Nghị <span class="text-danger">*</span></label>
        <select name="company_id" class="form-control {{ $tsfErr('company_id') }}" required>
            <option value="">-- Chọn công ty --</option>
            @foreach ($companies as $company)
                <option value="{{ $company->id }}" {{ (string) old('company_id') === (string) $company->id ? 'selected' : '' }}>
                    {{ $company->name }}{{ $company->short_name ? ' (' . $company->short_name . ')' : '' }}
                </option>
            @endforeach
        </select>
        @if ($bag->has('company_id')) <span class="md-error">{{ $bag->first('company_id') }}</span> @endif
    </div>
</div>

<div class="form-row">
    <div class="form-group col-md-3">
        <label>Thứ Tự Bước <span class="text-danger">*</span></label>
        <input type="number" name="step_no" min="1" max="10" class="form-control {{ $tsfErr('step_no') }}" value="{{ old('step_no', 1) }}" required>
        @if ($bag->has('step_no')) <span class="md-error">{{ $bag->first('step_no') }}</span> @endif
    </div>

    <div class="form-group col-md-9">
        <label>Vai Trò Ký <span class="text-danger">*</span></label>
        <select name="role_id" class="form-control {{ $tsfErr('role_id') }}" required>
            <option value="">-- Chọn vai trò --</option>
            @foreach ($roles as $role)
                <option value="{{ $role->id }}" {{ (string) old('role_id') === (string) $role->id ? 'selected' : '' }}>{{ $role->name }}</option>
            @endforeach
        </select>
        @if ($bag->has('role_id')) <span class="md-error">{{ $bag->first('role_id') }}</span> @endif
    </div>
</div>

<div class="form-row">
    <div class="form-group col-md-5">
        <label>Phạm Vi Phòng Ban <span class="text-danger">*</span></label>
        <select name="scope" class="form-control tsf-scope {{ $tsfErr('scope') }}" required>
            @foreach (MaterialTransferSignFlow::SCOPES as $key => $label)
                <option value="{{ $key }}" {{ old('scope') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        @if ($bag->has('scope')) <span class="md-error">{{ $bag->first('scope') }}</span> @endif
    </div>

    <div class="form-group col-md-7 tsf-department">
        <label>Phòng Cố Định <span class="text-danger">*</span></label>
        <select name="department_id" class="form-control {{ $tsfErr('department_id') }}">
            <option value="">-- Chọn phòng ban --</option>
            @foreach ($departments as $department)
                <option value="{{ $department->id }}" {{ (string) old('department_id') === (string) $department->id ? 'selected' : '' }}>
                    {{ $department->name }}{{ $department->company_short ? ' · ' . $department->company_short : '' }}
                </option>
            @endforeach
        </select>
        @if ($bag->has('department_id')) <span class="md-error">{{ $bag->first('department_id') }}</span> @endif
    </div>
</div>

@once
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Ô "Phòng cố định" chỉ dùng khi phạm vi là phòng cố định
            function tsfSync($form) {
                var fixed = $form.find('.tsf-scope').val() === @json(MaterialTransferSignFlow::SCOPE_DEPARTMENT);

                $form.find('.tsf-department').toggle(fixed)
                    .find('select').prop('required', fixed);
            }

            $(document).on('change', '.tsf-scope', function() {
                tsfSync($(this).closest('form'));
            });

            $(document).on('shown.bs.modal', '#createModal, #updateModal', function() {
                tsfSync($(this).find('form'));
            });

            $('#createModal form, #updateModal form').each(function() {
                tsfSync($(this));
            });
        });
    </script>
@endonce
