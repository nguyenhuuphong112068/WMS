{{--
| Ma trận nhóm quyền × quyền của một module leftNAV, chỉ xem (bản đọc của màn hình Phân Quyền › Nhóm Quyền).
| Cột vai trò xếp và gom theo loại phụ trách (Chung / Vật Tư / Hoá Chất / Chất Chuẩn) giống màn hình; rê chuột lên tên
| vai trò xem diễn giải + loại phụ trách, hàng đầu bảng là số quyền có hiệu lực của vai trò trong module.
| Biến vào: $moduleKey + roles, modules, kinds, roleKinds, kindOfGroup, assigned, parentOf, roleCounts
| từ UserGuideController::permissionMatrix()
--}}
<div data-ug-live>
@php $mxModule = $modules[$moduleKey] ?? null; @endphp

@if (!$mxModule)
    <div class="ug-callout is-warn">
        <i class="fas fa-exclamation-triangle"></i>
        <div>Chưa có quyền nào thuộc phần này.</div>
    </div>
@else
    <div class="ug-matrix-scroll">
        <table class="ug-matrix">
            <thead>
                <tr>
                    <th class="ug-mx-name" rowspan="2">Quyền</th>
                    @foreach ($roles->groupBy('kind') as $roleKind => $kindRoles)
                        <th class="ug-mx-kind is-{{ $roleKind }}" colspan="{{ $kindRoles->count() }}">
                            <i class="fas {{ $kinds[$roleKind]['icon'] }}"></i>
                            {{ $roleKind === 'common' ? 'Chung' : $kinds[$roleKind]['label'] }}
                        </th>
                    @endforeach
                </tr>
                <tr>
                    @foreach ($roles as $role)
                        <th class="ug-mx-role is-{{ $role->kind }}"
                            title="{{ $role->name }}{{ $role->description ? ' - ' . $role->description : '' }}&#10;Loại phụ trách: {{ implode(', ', array_map(fn ($kind) => $roleKinds[$kind], $role->kinds)) }}">
                            <span>{{ $role->name }}</span></th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                <tr class="ug-mx-total">
                    <td class="ug-mx-name">Số quyền có hiệu lực</td>
                    @foreach ($roles as $role)
                        @php $count = $roleCounts[$role->id][$moduleKey] ?? 0; @endphp
                        <td class="ug-mx-cell" title="{{ $role->name }}">
                            @if ($role->name === 'Admin')
                                Tất cả
                            @else
                                {{ $count ?: '—' }}
                            @endif
                        </td>
                    @endforeach
                </tr>
                @foreach ($mxModule['groups'] as $groupName => $permissions)
                    @php $groupKind = $kindOfGroup[$groupName] ?? 'common'; @endphp
                    <tr class="ug-mx-group">
                        <td class="ug-mx-name"><i class="fas {{ $kinds[$groupKind]['icon'] }}"></i> {{ $groupName }}</td>
                        <td colspan="{{ count($roles) }}"></td>
                    </tr>
                    @foreach ($permissions as $permission)
                        <tr>
                            <td class="ug-mx-name {{ $permission->parent_id ? 'ug-mx-child' : '' }}"
                                style="padding-left: {{ 8 + 18 * ($permission->depth ?? 0) }}px">
                                @if ($permission->parent_id)
                                    <i class="fas fa-level-up-alt fa-rotate-90"></i>
                                @endif
                                {{ $permission->display_name ?: $permission->name }}
                            </td>
                            @foreach ($roles as $role)
                                <td class="ug-mx-cell">
                                    @if ($role->name === 'Admin'
                                        || \App\Support\PermissionMatrix::isEffective($role->id, $permission->id, $parentOf, $assigned))
                                        <i class="fas fa-check ug-mx-on" title="{{ $role->name }}: có quyền"></i>
                                    @elseif (isset($assigned[$role->id . '-' . $permission->id]))
                                        <i class="fas fa-check ug-mx-idle"
                                            title="{{ $role->name }}: đã tick nhưng chưa có hiệu lực vì thiếu quyền cha"></i>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
@endif
</div>
