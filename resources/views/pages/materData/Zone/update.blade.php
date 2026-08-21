@foreach ($zoneMeta as $key => $meta)
    @php $bag = $errors->getBag('update_' . $key); @endphp

    <div class="modal fade zone-modal" id="updateModal-{{ $key }}" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="{{ $meta['icon'] }}"></i> Cập Nhật {{ $meta['label'] }}</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>

                <form action="{{ route($zoneRoute . 'update', $key) }}" method="POST">
                    @csrf
                    <input type="hidden" name="id" class="inp-id">

                    <div class="modal-body">

                        <div class="form-group">
                            <label>Phòng Ban</label>
                            <div class="zone-dept">
                                <i class="fas fa-building mr-1"></i>
                                {{ session('user')['selected_department'] }}
                            </div>
                        </div>

                        @foreach ($meta['parents'] as $parent)
                            @php $p = $zoneParents[$parent]; @endphp
                            <div class="form-group">
                                <label>{{ $p['label'] }} <span class="text-danger">*</span></label>
                                <select name="{{ $p['field'] }}"
                                    class="form-control {{ $p['class'] }} {{ $bag->has($p['field']) ? 'is-invalid' : '' }}"
                                    data-placeholder="{{ $p['placeholder'] }}" required>
                                    <option value="">{{ $p['placeholder'] }}</option>
                                </select>
                                @if ($bag->has($p['field']))
                                    <span class="zone-error">{{ $bag->first($p['field']) }}</span>
                                @endif
                            </div>
                        @endforeach

                        <div class="form-group">
                            <label>Mã {{ $meta['label'] }} <span class="text-danger">*</span></label>
                            <input type="text" name="code" maxlength="50"
                                class="form-control inp-code {{ $bag->has('code') ? 'is-invalid' : '' }}" required>
                            @if ($bag->has('code'))
                                <span class="zone-error">{{ $bag->first('code') }}</span>
                            @endif
                        </div>

                        <div class="form-group mb-0">
                            <label>Tên {{ $meta['label'] }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" maxlength="255"
                                class="form-control inp-name {{ $bag->has('name') ? 'is-invalid' : '' }}" required>
                            @if ($bag->has('name'))
                                <span class="zone-error">{{ $bag->first('name') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sync-alt mr-1"></i> Cập nhật
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach
