{{--
| Phạm vi phòng ban cho từng role.
| - Chỉ hiện hàng của role đang được chọn ở ô "Phân Quyền" (JS toggle theo class d-none).
| - Bỏ trống danh sách phòng ban = role đó áp dụng cho MỌI phòng ban.
| Biến vào: $roles, $allDepartments
--}}
<div class="form-group role-dept-wrap">
    <label class="mb-2" style="font-weight: 600; color: var(--text-main, #2D3748)">
        <i class="fas fa-building mr-1"></i> Phạm vi phòng ban của từng quyền
    </label>

    @foreach ($roles as $role)
        @php $preRD = array_map('strval', (array) old('roleDept.' . $role->id, [])); @endphp
        <div class="role-dept-row {{ is_array(old('userGroup')) && in_array($role->id, old('userGroup')) ? '' : 'd-none' }}"
            data-role="{{ $role->id }}"
            style="border-left: 3px solid var(--primary-lighter, #9CC7EE); padding: 6px 0 6px 12px; margin-bottom: 8px">
            <div style="font-size: 13px; margin-bottom: 4px">
                <strong>{{ $role->name }}</strong>
                <span class="text-muted">— để trống nghĩa là áp dụng mọi phòng ban</span>
            </div>
            <select class="form-control select2-roledept" name="roleDept[{{ $role->id }}][]" multiple
                data-placeholder="Mọi phòng ban">
                @foreach ($allDepartments as $d)
                    <option value="{{ $d->id }}" {{ in_array((string) $d->id, $preRD, true) ? 'selected' : '' }}>
                        {{ $d->shortName }} — {{ $d->name }}
                    </option>
                @endforeach
            </select>
        </div>
    @endforeach
</div>
