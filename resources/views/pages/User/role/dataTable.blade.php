
<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
<style>
  .step-checkbox {
  width: 20px;
  height: 20px;
  cursor: pointer;
  accent-color: #007bff; /* màu xanh bootstrap */
  }

  .step-checkbox:checked {
    box-shadow: 0 0 5px #007bff;
  }
</style>

<div class="content-wrapper">
        <div class="card">
            <div class="card-header mt-4"></div>
            <div class="card-body">
              @php
                $roleAdmin = collect($datas)->firstWhere('id', 1);
                $allPermissions = $roleAdmin ? $roleAdmin['permissions'] : [];
              @endphp

              <table id="data_table_permission" class="table table-bordered table-striped" style="font-size: 16px">
                    <thead style = "position: sticky; top: 60px; background-color: white; z-index: 1020">
                        <tr>
                            <th >Permission</th>
                            @foreach ($datas as $role)
                                <th class="text-center">{{ $role['name'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                       @foreach ($allPermissions as $permissionId => $permissionName)
                          <tr>
                              <td>{{ $permissionName }}</td>
                              @foreach ($datas as $role)
                                  <td>
                                    <div class="form-check form-switch text-center">
                                      <input class="form-check-input step-checkbox"
                                            type="checkbox" role="switch"
                                            data-role="{{ $role['id'] }}"
                                            data-permission="{{ $permissionId }}"
                                            id="checkbox-{{ $permissionId }}-{{ $role['id'] }}"
                                            name="permission"
                                            {{ array_key_exists($permissionId, $role['permissions']) ? 'checked' : '' }}>
                                    </div>
                                  </td>
                              @endforeach
                          </tr>
                      @endforeach
                    </tbody>
              </table>
            </div>
              <!-- /.card-body -->
            </div>
            <!-- /.card -->
    <!-- /.content -->
  </div>


<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>
<script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>

@if (session('success'))
<script>
    Swal.fire({
        title: 'Thành công!',
        text: '{{ session('success') }}',
        icon: 'success',
        timer: 2000, // tự đóng sau 2 giây
        showConfirmButton: false
    });
</script>
@endif


<script>
  $(document).ready(function () {
      document.body.style.overflowY = "auto";
  })

  $(document).on('change', '.step-checkbox', function () {
      let roleId = $(this).data('role');
      let permissionId = $(this).data('permission');
      let checked = $(this).is(':checked');
      
      $.ajax({
        url: "{{ route('pages.user.role.store_or_update') }}",
        type: 'POST',
        dataType: 'json', // 👉 ép jQuery hiểu rõ kiểu dữ liệu trả về
        data: {
          _token: '{{ csrf_token() }}',
          role_id: roleId,
          permission_id: permissionId,
          checked: checked
        }
      });

  });
</script>


