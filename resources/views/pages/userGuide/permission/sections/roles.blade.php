<p>Các nhóm quyền đang có trong hệ thống và số quyền có hiệu lực của từng nhóm ở mỗi phần. Bấm vào tên phần để xem chi tiết
    trong bảng phân quyền chung.</p>

<div class="table-responsive">
    <table class="table table-bordered ug-table ug-role-table">
        <thead>
            <tr>
                <th>Vai trò</th>
                @foreach ($tree as $modelKey => $model)
                    <th class="text-center" style="width: 110px">
                        <a href="#{{ ['common' => 'bang-quyen-chung', 'material' => 'bang-vat-tu', 'chemical' => 'bang-hoa-chat', 'standard' => 'bang-chat-chuan'][$modelKey] ?? '' }}">{{ $model['label'] }}</a>
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($roles as $role)
                <tr>
                    <td>
                        {{ $role->name }}
                        @if ($role->description)
                            <span class="ug-role-desc">{{ $role->description }}</span>
                        @endif
                    </td>
                    @foreach ($tree as $modelKey => $model)
                        @php $count = $roleCounts[$role->id][$modelKey] ?? 0; @endphp
                        <td class="text-center" style="font-weight: 400; vertical-align: middle">
                            @if ($role->name === 'Admin')
                                <span class="ug-chip is-success">Toàn quyền</span>
                            @elseif ($count)
                                {{ $count }}
                            @else
                                <span style="color: var(--text-muted)">—</span>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<p class="mb-0 mt-2">Từng tài liệu hướng dẫn (Dữ Liệu Gốc, Vật Tư, Hoá Chất, Chất Chuẩn) có mục <b>Quyền Truy Cập</b> diễn giải
    mỗi vai trò được làm gì trong phần đó.</p>
