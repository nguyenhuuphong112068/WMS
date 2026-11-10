{{--
| Modal thêm / sửa gói "Sản phẩm - Chỉ tiêu" của tab "Danh sách ... theo sản phẩm" (Hoá chất / Chất chuẩn).
| Biến vào: $plType, $mode = create|update; dùng chung $plPurposes (Chỉ tiêu kiểm) và $plOptions
| (ProductList::options - mã đang khai cho phòng) của trang.
--}}
@php
    $plConfig = \App\Support\ProductList::config($plType);
    $plNoun = $plConfig['noun'];
    $plIsUpdate = $mode === 'update';
    $plModalId = $plIsUpdate ? 'plUpdateModal' : 'plCreateModal';
    $bag = $errors->getBag($plIsUpdate ? 'plUpdateErrors' : 'plCreateErrors');

    // Trang có nhiều form dùng chung kho old(): chỉ điền lại khi chính form này báo lỗi
    $old = fn($key, $default = null) => $bag->any() ? old($key, $default) : $default;
@endphp

<div class="modal fade md-modal pl-modal" id="{{ $plModalId }}" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width: 920px" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-box-open"></i>
                    {{ $plIsUpdate ? 'Cập Nhật Sản Phẩm - Chỉ Tiêu' : 'Thêm Sản Phẩm - Chỉ Tiêu' }}
                </h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route('pages.category.' . $plType . 'ProductList.' . ($plIsUpdate ? 'update' : 'store')) }}" method="POST">
                @csrf
                @if ($plIsUpdate)
                    <input type="hidden" name="id" value="{{ $old('id') }}">
                @endif

                <div class="modal-body">
                    @if ($bag->has('items'))
                        <div class="alert alert-danger">{{ $bag->first('items') }}</div>
                    @endif

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Tên Sản Phẩm <span class="text-danger">*</span></label>
                            <select name="product_name" required
                                class="form-control cat-select {{ $bag->has('product_name') ? 'is-invalid' : '' }}"
                                data-lookup-url="{{ route('pages.category.lookup.productNames') }}">
                                <option value="">-- Chọn tên sản phẩm --</option>
                                @if ($old('product_name'))
                                    <option value="{{ $old('product_name') }}" selected>{{ $old('product_name') }}</option>
                                @endif
                            </select>
                            @if ($bag->has('product_name'))
                                <span class="md-error">{{ $bag->first('product_name') }}</span>
                            @endif
                        </div>

                        <div class="col-md-6 form-group">
                            <label>Chỉ Tiêu <span class="text-danger">*</span></label>
                            <select name="purpose_id" required
                                class="form-control cat-select {{ $bag->has('purpose_id') ? 'is-invalid' : '' }}">
                                <option value="">-- Chọn chỉ tiêu --</option>
                                @foreach ($plPurposes as $purpose)
                                    <option value="{{ $purpose->id }}" {{ $old('purpose_id') == $purpose->id ? 'selected' : '' }}>
                                        {{ $purpose->name }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($bag->has('purpose_id'))
                                <span class="md-error">{{ $bag->first('purpose_id') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Thêm {{ $plConfig['title'] }} <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div style="flex: 1 1 0; min-width: 0">
                                <select class="form-control pl-item-picker" title="Chỉ gồm {{ $plNoun }} đang khai ở tab {{ ucfirst($plNoun) }} của phòng">
                                    <option value="">-- Gõ mã danh mục, tên {{ $plNoun }} hoặc nhà sản xuất để thêm --</option>
                                </select>
                            </div>
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-primary btn-pl-pick"
                                    title="Mở danh mục {{ $plNoun }} của phòng để chọn nhiều cùng lúc">
                                    <i class="fas fa-list-check mr-1"></i> Danh mục phòng
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm md-table mb-0 pl-items-table" data-no-datatable>
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 44px">#</th>
                                    <th style="width: 110px">Mã DM</th>
                                    <th>{{ $plType === \App\Support\ProductList::STANDARD ? 'Chất Chuẩn' : 'Tên Hoá Chất' }}</th>
                                    <th style="width: 150px">Nhà Sản Xuất</th>
                                    <th style="width: 220px" title="Lượng gợi ý điền sẵn khi dùng gói, để trống thì người dùng tự nhập">
                                        {{ $plType === \App\Support\ProductList::STANDARD ? 'Số Lượng ĐN Gợi Ý' : 'Lượng Dùng Gợi Ý' }}
                                    </th>
                                    <th style="width: 44px"></th>
                                </tr>
                            </thead>
                            <tbody class="pl-items-body"></tbody>
                        </table>
                    </div>
                    <div class="md-empty text-center py-3 pl-items-empty">Chưa có {{ $plNoun }} nào trong danh sách.</div>

                    <div class="form-group mt-3">
                        <label>Ghi Chú</label>
                        <textarea name="note" rows="2" maxlength="500"
                            class="form-control {{ $bag->has('note') ? 'is-invalid' : '' }}">{{ $old('note') }}</textarea>
                        @if ($bag->has('note'))
                            <span class="md-error">{{ $bag->first('note') }}</span>
                        @endif
                    </div>

                    @if ($plIsUpdate)
                        <div class="form-group">
                            <label>Lý Do Điều Chỉnh <span class="text-danger">*</span></label>
                            <textarea name="change_reason" rows="2" maxlength="500" required
                                class="form-control {{ $bag->has('change_reason') ? 'is-invalid' : '' }}"
                                placeholder="Nêu rõ lý do sửa bản ghi này">{{ $old('change_reason') }}</textarea>
                            @if ($bag->has('change_reason'))
                                <span class="md-error">{{ $bag->first('change_reason') }}</span>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Lưu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@once
    {{-- Hộp chọn nhiều từ danh mục của phòng (dùng chung cho modal Thêm / Cập nhật) --}}
    <div class="modal fade md-modal" id="plPickModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width: 860px" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-list-check"></i> Danh mục {{ $plNoun }} của phòng</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <input type="text" class="form-control mb-2" id="plPickSearch" autocomplete="off"
                        placeholder="Tìm theo mã, tên hoặc nhà sản xuất...">
                    <div class="table-responsive" style="max-height: 55vh; overflow-y: auto">
                        <table class="table table-bordered table-sm md-table mb-0" data-no-datatable>
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 40px"><input type="checkbox" id="plPickAll"></th>
                                    <th style="width: 110px">Mã DM</th>
                                    <th>{{ $plType === \App\Support\ProductList::STANDARD ? 'Chất Chuẩn' : 'Tên Hoá Chất' }}</th>
                                    <th style="width: 180px">Nhà Sản Xuất</th>
                                    <th style="width: 80px">ĐVT</th>
                                </tr>
                            </thead>
                            <tbody id="plPickBody"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-primary" id="plPickAdd">
                        <i class="fas fa-plus mr-1"></i> Thêm đã chọn (<span id="plPickCount">0</span>)
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var options = @json($plOptions);
            var noun = @json($plNoun);
            var byId = {};

            options.forEach(function(item) {
                byId[item.id] = item;
            });

            function esc(value) {
                return $('<div>').text(value == null ? '' : value).html();
            }

            function sync($modal) {
                var $rows = $modal.find('.pl-items-body tr');

                $modal.find('.pl-items-table').closest('.table-responsive').toggle($rows.length > 0);
                $modal.find('.pl-items-empty').toggle($rows.length === 0);
                $rows.each(function(i) {
                    $(this).find('.pl-row-no').text(i + 1);
                    // Đánh lại chỉ số để mảng items[] gửi lên đúng thứ tự trên màn hình
                    $(this).find('[data-field]').each(function() {
                        this.name = 'items[' + i + '][' + $(this).data('field') + ']';
                    });
                });
            }

            function addRow($modal, categoryId, amount) {
                categoryId = String(categoryId);

                var $exists = $modal.find('.pl-items-body tr[data-category-id="' + categoryId + '"]');

                if ($exists.length) {
                    $exists.find('.pl-amount').trigger('focus');
                    return;
                }

                // Mã đã khoá ở tab "... của phòng" vẫn hiện để người dùng thấy mà bỏ đi
                var item = byId[categoryId] || { code: '#' + categoryId, name: noun.charAt(0).toUpperCase() + noun.slice(1) + ' không còn khai cho phòng', unit: '' };

                $modal.find('.pl-items-body').append(
                    '<tr data-category-id="' + esc(categoryId) + '">' +
                    '<td class="text-center align-middle pl-row-no"></td>' +
                    '<td class="align-middle"><span class="md-tag">' + esc(item.code) + '</span>' +
                    '<input type="hidden" data-field="category_id" value="' + esc(categoryId) + '"></td>' +
                    '<td class="align-middle' + (byId[categoryId] ? '' : ' text-danger') + '">' + esc(item.name) + '</td>' +
                    '<td class="align-middle md-sub">' + esc(item.manufacturer || '—') + '</td>' +
                    '<td><div class="input-group input-group-sm">' +
                    '<input type="text" inputmode="decimal" data-field="amount" class="form-control js-decimal pl-amount" ' +
                    'autocomplete="off" placeholder="Không bắt buộc" value="' + esc(amount == null ? '' : amount) + '">' +
                    '<div class="input-group-append"><span class="input-group-text">' + esc(item.unit || '—') + '</span></div>' +
                    '</div></td>' +
                    '<td class="text-center align-middle"><button type="button" class="btn btn-sm btn-outline-danger pl-row-remove" title="Bỏ dòng này">' +
                    '<i class="fas fa-times"></i></button></td>' +
                    '</tr>'
                );

                sync($modal);
            }

            window.plFillItems = function($modal, items) {
                $modal.find('.pl-items-body').empty();
                (items || []).forEach(function(item) {
                    if (item && item.category_id) addRow($modal, item.category_id, item.amount);
                });
                sync($modal);
            };

            $('.pl-modal').each(function() {
                var $modal = $(this);
                var $picker = $modal.find('.pl-item-picker');

                $picker.select2($.extend({
                    theme: 'bootstrap4',
                    width: '100%',
                    dropdownParent: $modal,
                    placeholder: $picker.find('option[value=""]').text(),
                    language: {
                        noResults: function() {
                            return 'Không tìm thấy ' + noun + ' của phòng phù hợp';
                        }
                    }
                }, { ajax: window.wmsLocalLookup(options) }));

                $picker.on('select2:select', function(e) {
                    addRow($modal, e.params.data.id, null);
                    $picker.val(null).trigger('change');
                    $modal.find('.pl-items-body tr[data-category-id="' + e.params.data.id + '"] .pl-amount').trigger('focus');
                });

                sync($modal);
            });

            // ---- Chọn nhiều từ danh mục phòng ----
            var $pick = $('#plPickModal');
            var $pickBody = $('#plPickBody');
            var $pickTarget = null;

            function pickCount() {
                $('#plPickCount').text($pickBody.find('.pl-pick-check:checked:not(:disabled)').length);
            }

            function pickRender() {
                var html = options.map(function(item) {
                    var taken = $pickTarget.find('.pl-items-body tr[data-category-id="' + item.id + '"]').length > 0;
                    var hay = (item.code + ' ' + item.name + ' ' + (item.manufacturer || '')).toLowerCase();

                    return '<tr data-hay="' + esc(hay) + '">' +
                        '<td class="text-center"><input type="checkbox" class="pl-pick-check" value="' + esc(item.id) + '"' +
                        (taken ? ' checked disabled' : '') + '></td>' +
                        '<td><span class="md-tag">' + esc(item.code) + '</span></td>' +
                        '<td>' + esc(item.name) + (taken ? ' <span class="md-sub">(đã thêm)</span>' : '') + '</td>' +
                        '<td class="md-sub">' + esc(item.manufacturer || '—') + '</td>' +
                        '<td class="md-sub">' + esc(item.unit || '—') + '</td></tr>';
                }).join('');

                $pickBody.html(html || '<tr><td colspan="5" class="text-center md-empty">Danh mục của phòng chưa có ' + noun + ' nào đang dùng.</td></tr>');
                $('#plPickAll').prop('checked', false);
                pickCount();
            }

            $(document).on('click', '.btn-pl-pick', function() {
                $pickTarget = $(this).closest('.pl-modal');
                $('#plPickSearch').val('');
                pickRender();
                $pick.modal('show');
            });

            $('#plPickSearch').on('input', function() {
                var keyword = this.value.toLowerCase().trim();

                $pickBody.find('tr[data-hay]').each(function() {
                    $(this).toggle(!keyword || String($(this).data('hay')).indexOf(keyword) !== -1);
                });
            });

            $('#plPickAll').on('change', function() {
                $pickBody.find('tr:visible .pl-pick-check:not(:disabled)').prop('checked', this.checked);
                pickCount();
            });

            $pickBody.on('change', '.pl-pick-check', pickCount);

            $('#plPickAdd').on('click', function() {
                $pickBody.find('.pl-pick-check:checked:not(:disabled)').each(function() {
                    addRow($pickTarget, this.value, null);
                });
                $pick.modal('hide');
            });

            // Đóng hộp con thì modal cha vẫn phải cuộn được
            $pick.on('hidden.bs.modal', function() {
                if ($('.modal.show').length) $('body').addClass('modal-open');
            });

            $(document).on('click', '.pl-row-remove', function() {
                var $modal = $(this).closest('.pl-modal');

                $(this).closest('tr').remove();
                sync($modal);
            });

            $(document).on('click', '.btn-pl-create', function() {
                window.plFillItems($('#plCreateModal'), []);
            });

            $(document).on('click', '.btn-pl-edit', function() {
                window.plFillItems($('#plUpdateModal'), ($(this).data('row') || {}).items || []);
            });

            $('.pl-modal form').on('submit', function(e) {
                if (!$(this).find('.pl-items-body tr').length) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    alert('Vui lòng thêm ít nhất một ' + noun + '.');
                }
            });
        });
    </script>
@endonce

{{-- Đặt sau khối @once để plFillItems đã có khi dựng lại các dòng vừa nhập bị lỗi --}}
@if ($bag->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            window.plFillItems($('#{{ $plModalId }}'), @json(array_values((array) old('items', []))));
            $('#{{ $plModalId }}').modal('show');
        });
    </script>
@endif
