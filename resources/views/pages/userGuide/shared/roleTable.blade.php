{{--
| Bảng "vai trò được làm gì" trong một tài liệu, đọc thẳng cấu hình Nhóm Quyền hiện tại.
| Biến vào: $ugRoleSummary từ UserGuideController::show() (App\Support\PermissionMatrix::roleSummary)
| Chỉ liệt kê quyền có hiệu lực: quyền xem tab cần đi kèm quyền xem màn hình.
--}}
@if (empty($ugRoleSummary))
    <div class="ug-callout is-warn">
        <i class="fas fa-exclamation-triangle"></i>
        <div>Chưa có nhóm quyền nào được cấp quyền trong phần này.</div>
    </div>
@else
    <div class="table-responsive">
        <table class="table table-bordered ug-table ug-role-table">
            <thead>
                <tr>
                    <th>Vai trò</th>
                    <th>Được làm gì</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($ugRoleSummary as $item)
                    <tr>
                        <td>
                            {{ $item['role']->name }}
                            @if ($item['role']->description)
                                <span class="ug-role-desc">{{ $item['role']->description }}</span>
                            @endif
                        </td>
                        <td style="font-weight: 400">
                            @if ($item['isAdmin'])
                                <span class="ug-chip is-success">Toàn quyền</span>
                                Mọi màn hình, mọi thao tác, ở mọi phòng ban.
                            @else
                                @if ($item['onlyView'])
                                    <div class="ug-role-line"><span class="ug-chip">Chỉ xem</span></div>
                                @endif
                                @foreach ($item['groups'] as $groupName => $group)
                                    <div class="ug-role-line">
                                        <span class="ug-role-group">{{ $groupName }}:</span>
                                        {{ $group['actions'] ? implode(' · ', $group['actions']) : 'Không có thao tác' }}
                                        @if ($group['tabs'])
                                            <div class="ug-role-tabs">Xem tab: {{ implode(', ', $group['tabs']) }}</div>
                                        @endif
                                    </div>
                                @endforeach
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
