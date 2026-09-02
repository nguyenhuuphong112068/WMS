<!-- Modal: cấp 1 quyền bất kỳ (ngoài nhóm quyền) cho 1 user -->
<div class="modal fade" id="UserPermissionModal" tabindex="-1" role="dialog"
    aria-labelledby="UserPermissionLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document" style="max-width: 90%">

        <div class="modal-content" style="border-radius: var(--border-radius-lg, 12px); border: none">
            <div class="modal-header" style="border-bottom: 1px solid var(--primary-soft, #EAF3FC)">
                <a href="{{ route('pages.home') }}">
                    <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8 ; max-width:45px;">
                </a>

                <h4 class="modal-title w-100 text-center" id="UserPermissionLabel"
                    style="color: var(--primary, #2E7BC4); font-weight: 700; letter-spacing: 1px">
                    QUYỀN RIÊNG - <span id="userPermissionName"></span>
                </h4>

                <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body" style="padding: 20px">

                <input type="hidden" id="userPermissionUserId" value="">

                <div class="alert" role="alert"
                    style="background-color: var(--primary-soft, #EAF3FC); color: var(--text-main, #2D3748);
                           border: 1px solid var(--primary-lighter, #9CC7EE); border-radius: var(--border-radius-md, 8px); font-size: 14px">
                    <b>Theo nhóm quyền</b>: giữ nguyên kết quả từ các nhóm quyền của user.
                    <b>Cho phép</b> / <b>Từ chối</b>: cấp riêng cho user này, ghi đè nhóm quyền.
                </div>

                <div class="form-group">
                    <input type="text" class="form-control" id="userPermissionSearch"
                        placeholder="Tìm quyền theo tên..." style="border-radius: var(--border-radius-md, 8px)">
                </div>

                <div style="max-height: 65vh; overflow-y: auto">
                    <table class="table table-bordered" id="userPermissionTable" style="font-size: 15px">
                        <thead style="position: sticky; top: 0; z-index: 1020">
                            <tr>
                                <th style="min-width: 280px">Quyền</th>
                                <th class="text-center" style="width: 140px">Từ Nhóm Quyền</th>
                                <th class="text-center" style="width: 340px">Cấp Riêng</th>
                                <th class="text-center" style="width: 110px">Kết Quả</th>
                            </tr>
                        </thead>
                        <tbody id="userPermissionBody">
                        </tbody>
                    </table>
                </div>

            </div>

            <div class="modal-footer" style="border-top: 1px solid var(--primary-soft, #EAF3FC)">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<style>
    #userPermissionTable thead th {
        background-color: var(--primary-soft, #EAF3FC);
        color: var(--primary, #2E7BC4);
        font-weight: 700;
        border-bottom: 2px solid var(--primary-lighter, #9CC7EE);
    }

    #userPermissionTable tbody tr.permission-row:hover {
        background-color: var(--primary-soft, #EAF3FC);
    }

    #userPermissionTable tbody tr.permission-group-row td {
        background-color: var(--primary, #2E7BC4);
        color: #fff;
        font-weight: 700;
        letter-spacing: .5px;
    }

    #userPermissionTable .permission-code {
        font-size: 12px;
        color: #94A3B8;
    }

    #userPermissionTable .form-check-input {
        accent-color: var(--primary, #2E7BC4);
    }
</style>

<script>
    (function() {
        var listUrl = "{{ url('user/userPermission') }}";
        var saveUrl = "{{ route('pages.user.userPermission.store_or_update') }}";

        function escapeHtml(text) {
            return $('<div>').text(text === null || text === undefined ? '' : text).html();
        }

        function resultBadge(fromRole, state) {
            var allowed = state === 'inherit' ? fromRole : state === 'allow';

            return allowed ?
                '<span class="badge badge-success">Có</span>' :
                '<span class="badge badge-secondary">Không</span>';
        }

        function renderRow(item) {
            var name = 'state-' + item.id;

            var options = [
                ['inherit', 'Theo nhóm quyền'],
                ['allow', 'Cho phép'],
                ['deny', 'Từ chối'],
            ];

            var radios = '';
            options.forEach(function(option) {
                radios +=
                    '<div class="form-check form-check-inline">' +
                    '<input class="form-check-input user-permission-state" type="radio"' +
                    ' name="' + name + '" id="' + name + '-' + option[0] + '"' +
                    ' data-permission="' + item.id + '" value="' + option[0] + '"' +
                    (item.state === option[0] ? ' checked' : '') +
                    '{{ user_can('userPermission_manage') ? '' : ' disabled' }}' + '>' +
                    '<label class="form-check-label" for="' + name + '-' + option[0] + '">' +
                    option[1] + '</label>' +
                    '</div>';
            });

            return '<tr class="permission-row" data-permission-row="' + item.id + '"' +
                ' data-from-role="' + (item.from_role ? 1 : 0) + '" data-state="' + item.state + '">' +
                '<td>' + escapeHtml(item.name) +
                '<div class="permission-code">' + escapeHtml(item.code) + '</div></td>' +
                '<td class="text-center">' + (item.from_role ? 'Có' : 'Không') + '</td>' +
                '<td>' + radios + '</td>' +
                '<td class="text-center user-permission-result">' +
                resultBadge(item.from_role, item.state) + '</td>' +
                '</tr>';
        }

        function renderGroupRow(groupName) {
            return '<tr class="permission-group-row"><td colspan="4">' + escapeHtml(groupName) + '</td></tr>';
        }

        $(document).on('click', '.btn-user-permission', function() {
            var userId = $(this).data('id');

            $('#userPermissionUserId').val(userId);
            $('#userPermissionName').text($(this).data('fullname'));
            $('#userPermissionSearch').val('');
            $('#userPermissionBody').html('<tr><td colspan="4" class="text-center">Đang tải...</td></tr>');

            $.ajax({
                url: listUrl + '/' + userId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    var rows = '';
                    var currentGroup = null;

                    response.datas.forEach(function(item) {
                        if (item.group_name !== currentGroup) {
                            currentGroup = item.group_name;
                            rows += renderGroupRow(currentGroup);
                        }
                        rows += renderRow(item);
                    });

                    $('#userPermissionBody').html(rows ||
                        '<tr><td colspan="4" class="text-center">Chưa có quyền nào trong hệ thống</td></tr>');
                },
                error: function() {
                    $('#userPermissionBody').html(
                        '<tr><td colspan="4" class="text-center text-danger">Không tải được danh sách quyền</td></tr>'
                    );
                }
            });
        });

        $(document).on('change', '.user-permission-state', function() {
            var input = $(this);
            var row = input.closest('tr');
            var state = input.val();

            $.ajax({
                url: saveUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    _token: '{{ csrf_token() }}',
                    user_id: $('#userPermissionUserId').val(),
                    permission_id: input.data('permission'),
                    state: state
                },
                success: function() {
                    row.attr('data-state', state);
                    row.find('.user-permission-result')
                        .html(resultBadge(row.attr('data-from-role') == '1', state));
                },
                error: function(xhr) {
                    var message = (xhr.responseJSON && xhr.responseJSON.error) ?
                        xhr.responseJSON.error : 'Không lưu được quyền';

                    Swal.fire({
                        title: 'Lỗi!',
                        text: message,
                        icon: 'error'
                    });

                    // Trả radio về trạng thái đã lưu trước đó
                    row.find('.user-permission-state').prop('checked', false);
                    row.find('.user-permission-state[value="' + row.attr('data-state') + '"]')
                        .prop('checked', true);
                }
            });
        });

        // Lọc theo tên quyền, ẩn luôn tiêu đề nhóm không còn dòng nào
        $(document).on('keyup', '#userPermissionSearch', function() {
            var keyword = $(this).val().toLowerCase();

            $('#userPermissionBody tr.permission-row').each(function() {
                var name = $(this).find('td:first').text().toLowerCase();
                $(this).toggle(name.indexOf(keyword) !== -1);
            });

            $('#userPermissionBody tr.permission-group-row').each(function() {
                $(this).toggle($(this).nextUntil('.permission-group-row', '.permission-row:visible').length > 0);
            });
        });
    })();
</script>
