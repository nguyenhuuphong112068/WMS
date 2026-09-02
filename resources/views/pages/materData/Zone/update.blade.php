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
                                <label>{{ $p['label'] }}</label>
                                <select name="{{ $p['field'] }}"
                                    class="form-control {{ $p['class'] }} {{ $bag->has($p['field']) ? 'is-invalid' : '' }}"
                                    data-placeholder="{{ $p['placeholder'] }}">
                                    <option value="">{{ $p['placeholder'] }}</option>
                                </select>
                                @if ($bag->has($p['field']))
                                    <span class="zone-error">{{ $bag->first($p['field']) }}</span>
                                @endif
                            </div>
                        @endforeach

                        @if (!empty($meta['hasType']))
                            <div class="form-group">
                                <label>Loại Lưu Trữ</label>
                                <select name="item_type"
                                    class="form-control sel-item-type {{ $bag->has('item_type') ? 'is-invalid' : '' }}">
                                    <option value="">-- Dùng chung cho mọi loại --</option>
                                    @foreach ($locationTypes as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">
                                    Đổi loại thì ô này chuyển sang màn hình Tồn Kho tương ứng. Hàng đang để ở đây vẫn
                                    hiện nguyên chỗ cũ, không bị mất.
                                </small>
                                @if ($bag->has('item_type'))
                                    <span class="zone-error">{{ $bag->first('item_type') }}</span>
                                @endif
                            </div>
                        @endif

                        <div class="form-group {{ ($meta['hasName'] ?? true) ? '' : 'mb-0' }}">
                            <label>Mã {{ $meta['label'] }} <span class="text-danger">*</span></label>
                            <input type="text" name="code" maxlength="50"
                                class="form-control inp-code {{ $bag->has('code') ? 'is-invalid' : '' }}" required>
                            @if ($bag->has('code'))
                                <span class="zone-error">{{ $bag->first('code') }}</span>
                            @endif
                            @if (!($meta['hasName'] ?? true))
                                <small class="form-text text-muted">
                                    {{ $meta['label'] }} chỉ dùng mã để nhận biết, không cần khai tên.
                                </small>
                            @endif
                        </div>

                        @if ($meta['hasName'] ?? true)
                            <div class="form-group mb-0">
                                <label>Tên {{ $meta['label'] }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" maxlength="255"
                                    class="form-control inp-name {{ $bag->has('name') ? 'is-invalid' : '' }}" required>
                                @if ($bag->has('name'))
                                    <span class="zone-error">{{ $bag->first('name') }}</span>
                                @endif
                            </div>
                        @endif
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
