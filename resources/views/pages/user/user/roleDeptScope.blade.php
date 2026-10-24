{{--
| Phạm vi phòng ban cho từng role.
| - Chỉ hiện hàng của role đang được chọn ở ô "Phân Quyền" (JS toggle theo class d-none).
| - Chọn công ty = chỉ lọc danh sách phòng ban (tự chọn từng phòng); bỏ công ty = bỏ các phòng ban của nó.
|   Ô công ty chỉ để lọc/chọn nhanh, không gửi lên server (suy lại từ phòng ban khi mở modal).
| - Bỏ trống danh sách phòng ban = role đó áp dụng cho MỌI phòng ban.
| Biến vào: $roles, $allDepartments, $scopeCompanies
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
                <span class="text-muted">— để trống phòng ban nghĩa là áp dụng mọi phòng ban</span>
            </div>
            <div>
                <div class="mb-2">
                    <label class="mb-1" style="font-size: 13px">Công ty</label>
                    <select class="form-control select2-rolecompany" multiple data-placeholder="Mọi công ty">
                        @foreach ($scopeCompanies as $c)
                            <option value="{{ $c->id }}">{{ $c->short ?: $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1" style="font-size: 13px">Phòng ban</label>
                    <select class="form-control select2-roledept" name="roleDept[{{ $role->id }}][]" multiple
                        data-placeholder="Mọi phòng ban">
                        @include('layout.departmentOptions', ['departments' => $allDepartments, 'selected' => $preRD])
                    </select>
                </div>
            </div>
        </div>
    @endforeach
</div>
