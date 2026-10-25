{{--
| Ma trận nhóm quyền × quyền của một model, chỉ xem (bản đọc của màn hình Phân Quyền › Nhóm Quyền).
| Biến vào: $modelKey + roles, tree, assigned, parentOf từ UserGuideController::permissionMatrix()
--}}
@php $mxModel = $tree[$modelKey] ?? null; @endphp

@if (!$mxModel)
    <div class="ug-callout is-warn">
        <i class="fas fa-exclamation-triangle"></i>
        <div>Chưa có quyền nào thuộc phần này.</div>
    </div>
@else
    <div class="ug-matrix-scroll">
        <table class="ug-matrix">
            <thead>
                <tr>
                    <th class="ug-mx-name">Quyền</th>
                    @foreach ($roles as $role)
                        <th class="ug-mx-role" title="{{ $role->name }}"><span>{{ $role->name }}</span></th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($mxModel['groups'] as $groupName => $permissions)
                    <tr class="ug-mx-group">
                        <td class="ug-mx-name">{{ $groupName }}</td>
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
