
<div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
   
    <form 
      action="{{route('pages.user.user.store')}}" 
      method="POST">
      @csrf

      <div class="modal-content">
        <div class="modal-header">
          <a href="{{ route ('pages.home') }}">
              <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8 ; max-width:45px;">
          </a>

          <h4 class="modal-title w-100 text-center" id="ModalLabel" style="color: #CDC717">
              {{'Cập Nhật User' }}
          </h4>

          <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body">
        
        <input type="hidden" class="form-control" name="id" value="">
        {{-- USER NAME --}}
              
          <div class="row">
              <div class="col-md-6">
                  <div class="form-group">
                    <label for="userName">Tên Đăng Nhập</label>
                    <input type="text" class="form-control" name="userName"  
                      value="{{ old('userName') }}" placeholder="Mã Số Nhân Viên">
                  </div>
                  @error('userName','createErrors')
                      <div class="alert alert-danger">{{ $message }}</div>
                  @enderror

              </div>
              <div class="col-md-6">
                  {{-- PW--}}
                  <div class="form-group">
                    <label for="passWord">Mật Khẩu</label>
                    <div class="input-group">
                        <input type="password" class="form-control" name="passWord" id="passWordCreate" 
                               value="Abc@123">
                        <div class="input-group-append">
                            <span class="input-group-text toggle-password" style="cursor: pointer;" data-target="#passWordCreate">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>
                  </div>
                  @error('passWord','createErrors')
                      <div class="alert alert-danger">{{ $message }}</div>
                  @enderror
            </div>
          </div>

           {{-- Full Name--}}
            <div class="form-group">
              <label for="fullName">Tên Người Dùng</label>
              <input type="text" class="form-control" name="fullName"  placeholder="Tên Đầy Đủ"
                value="{{ old('fullName') }}">
            </div>
            @error('fullName','createErrors')
                <div class="alert alert-danger">{{ $message }}</div>
            @enderror

            {{-- USER GROUP --}}
              <div class="form-group">
                  <label for="userGroupcreate">Phân Quyền (Chọn 1 hoặc nhiều)</label>
                  <select class="form-control select2" name="userGroup[]" id="userGroupcreate" multiple="multiple" data-placeholder="Chọn phân quyền">
                      @foreach ($roles as $role)
                          <option value="{{ $role->id }}" 
                              {{ (is_array(old('userGroup')) && in_array($role->id, old('userGroup'))) ? 'selected' : '' }}>
                              {{ $role->name }}
                          </option>
                      @endforeach
                  </select>
                  @error('userGroup','createErrors')
                        <div class="alert alert-danger mt-1">{{ $message }}</div>
                  @enderror
              </div>


          <div class="form-group">
              {{-- DEPARTMENT--}}
              <label for="deparmentcreate">Phòng Ban</label>
              <select class="form-control" name="deparment" id="deparmentcreate">
                  <option value="">-- Chọn phòng ban --</option>

                  @foreach ($deparments as $department)
                      <option value="{{ $department->shortName }}"
                          {{ old('deparment') == $department->shortName ? 'selected' : '' }}>
                          {{ $department->name }}
                      </option>
                  @endforeach
              </select>

              @error('deparment','createErrors')
                  <div class="alert alert-danger mt-1">{{ $message }}</div>
              @enderror
          </div>

            {{-- Mail--}}
            <div class="form-group">
              <label for="mail"> {{'Mail (Nếu Có)'}}</label>
              <input type="text" class="form-control" name="mail" placeholder="Không Bắt Buốc"
                value="{{ old('mail') }}">
            </div>
            @error('mail','createErrors')
                <div class="alert alert-danger">{{ $message }}</div>
            @enderror

        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
          <button type="submit" class="btn btn-primary">
              Lưu
          </button>
        </div>
      </div>
    </form>
  </div>
</div>


{{-- //Show modal nếu có lỗi validation --}}
@if ($errors->createErrors->any())
<script>
    $(document).ready(function () {
        $('#createModal').modal('show');
    });
</script>
@endif
