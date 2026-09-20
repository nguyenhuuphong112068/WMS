@php
    use App\Support\MaterialClassification;

    // Bảng này chỉ dùng riêng cho Dự Trù Vật Tư nên $estRoute cố định trỏ đúng module này
    $wlEstRoute = 'pages.estimate.materialEstimate.';
    $wlNum = fn ($value) => rtrim(rtrim(number_format((float) $value, 4, '.', ','), '0'), '.');
    $wlSourceLabel = fn ($type) => match ($type) {
        'risk_assessment' => 'Đề Nghị Theo ĐG Rủi Ro',
        'regular' => 'Đề Nghị Thường Quy',
        'category' => 'Danh Mục Vật Tư Của Phòng',
        default => null,
    };
@endphp

<div class="md-toolbar">
    <div class="d-flex align-items-center flex-wrap" style="gap: 10px">
        <button type="button" class="btn btn-outline-primary btn-sm btn-wl-select-all" data-mode="all">
            <i class="far fa-check-square mr-1"></i> Chọn tất cả
        </button>

        @perm('estimate_material_create')
            <button type="button" class="btn btn-primary btn-sm btn-wl-estimate" disabled>
                <i class="fas fa-clipboard-check mr-1"></i> Lập phiếu dự trù <span class="wl-count"></span>
            </button>
        @endperm

        @perm('export_material_request')
            <button type="button" class="btn btn-info btn-sm btn-wl-transfer" disabled
                title="Chỉ dùng được khi mọi vật tư đã chọn đều do Hành Chánh mua và có trong danh mục">
                <i class="fas fa-share-square mr-1"></i> Đề nghị liên phòng ban <span class="wl-count"></span>
            </button>
        @endperm
    </div>

    <div class="md-filter">
        <label for="wlPurchasingFilter"><i class="fas fa-filter mr-1"></i> Bộ phận mua hàng</label>
        <select id="wlPurchasingFilter" class="form-control form-control-sm">
            <option value="all">Tất cả</option>
            @foreach ($purchasingDepartments as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
            <option value="none">Chưa khai</option>
        </select>
    </div>
</div>

<div class="table-responsive">
    <table id="mdWatchlistTable" class="table table-bordered table-hover w-100">
        <thead>
            <tr>
                <th class="text-center wl-check-col" style="width: 42px"></th>
                <th class="text-center" style="width: 55px">STT</th>
                <th style="width: 240px">Vật Tư</th>
                <th>Quy Cách</th>
                <th class="text-center" style="width: 80px">ĐVT</th>
                <th class="text-right" style="width: 100px">Tồn Hiện Tại</th>
                <th class="text-right" style="width: 115px">Ngưỡng Tối Thiểu</th>
                <th style="width: 125px">Bộ Phận Mua Hàng</th>
                <th style="width: 190px">Nguồn</th>
                <th style="width: 200px">Lý Do Dự Trù</th>
                <th class="text-center" style="width: 80px">Thao Tác</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                @php
                    $wlPurchasing = $item->purchasing_department ?: null;
                    $wlPurchasingLabel = MaterialClassification::purchasingLabel($wlPurchasing);
                @endphp
                <tr data-purchasing="{{ $wlPurchasing ?: 'none' }}"
                    data-category-id="{{ $item->category_id }}"
                    data-name="{{ $item->material_name }}"
                    data-spec="{{ $item->technical_specification }}"
                    data-unit="{{ $item->unit }}"
                    data-unit-id="{{ $item->unit_id }}"
                    data-note="{{ $item->note }}">
                    <td class="text-center">
                        <input type="checkbox" class="js-wl-check" value="{{ $loop->index }}">
                    </td>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>
                        <div class="font-weight-bold">{{ $item->material_name ?: '-' }}</div>
                        @if ($item->manufacturer_name)
                            <div class="md-sub">NSX: {{ $item->manufacturer_name }}</div>
                        @endif
                        @unless ($item->category_id)
                            <span class="est-outside">Ngoài danh mục</span>
                        @endunless
                    </td>
                    <td class="md-sub">{{ $item->technical_specification ?: '-' }}</td>
                    <td class="text-center">{{ $item->unit ?: '-' }}</td>
                    <td class="text-right">{{ $item->on_hand !== null ? $wlNum($item->on_hand) : '—' }}</td>
                    <td class="text-right">{{ $item->min_stock !== null ? $wlNum($item->min_stock) : '—' }}</td>
                    <td>
                        @if ($wlPurchasingLabel)
                            <span class="wl-buyer {{ $wlPurchasing }}">{{ $wlPurchasingLabel }}</span>
                        @else
                            <span class="md-empty">—</span>
                        @endif
                    </td>
                    <td>
                        @if ($item->below_threshold)
                            <span class="md-badge rejected">Dưới ngưỡng tồn tối thiểu</span>
                        @endif
                        @if ($item->watchlist_id)
                            <div class="md-sub mt-1">
                                <span class="md-badge pending">Đề nghị dự trù</span>
                                @if ($wlSourceLabel($item->source_type))
                                    <span class="ml-1">({{ $wlSourceLabel($item->source_type) }})</span>
                                @endif
                            </div>
                            <div class="md-sub">
                                {{ $item->remembered_by ?: '—' }}
                                <br><small>{{ $item->remembered_at ? \Carbon\Carbon::parse($item->remembered_at)->format('d/m/Y') : '' }}</small>
                            </div>
                        @endif
                    </td>
                    <td class="md-sub">
                        @if ($item->note)
                            <span class="md-note" title="{{ $item->note }}">{{ $item->note }}</span>
                        @else
                            <span class="md-empty">—</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if ($item->watchlist_id)
                            <form action="{{ route($wlEstRoute . 'watchlistDismiss') }}" method="POST" class="form-md-confirm-cancel d-inline"
                                data-title="Bỏ ghi nhớ vật tư này?" data-text="Vật tư sẽ chỉ còn hiện lại ở đây nếu tồn xuống dưới ngưỡng tối thiểu.">
                                @csrf
                                <input type="hidden" name="id" value="{{ $item->watchlist_id }}">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Bỏ ghi nhớ"><i class="fas fa-bookmark"></i></button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<style>
    /* Ô lọc nhanh trên thanh công cụ của tab */
    .md-filter {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .md-filter label {
        margin: 0;
        font-size: 0.83rem;
        font-weight: 700;
        color: var(--primary-dark);
        white-space: nowrap;
    }

    .md-filter .form-control {
        width: auto;
        min-width: 180px;
    }

    /* Nhãn bộ phận mua hàng trên bảng */
    .wl-buyer {
        display: inline-block;
        border-radius: 999px;
        padding: 2px 11px;
        font-size: 0.75rem;
        font-weight: 700;
        white-space: nowrap;
        background: var(--primary-soft);
        color: var(--primary-dark);
        border: 1px solid var(--primary-lighter);
    }

    .wl-buyer.admin {
        background: #FEF3C7;
        color: #B45309;
        border-color: #FCD34D;
    }

    .wl-buyer.it {
        background: #EDE9FE;
        color: #6D28D9;
        border-color: #C4B5FD;
    }

    #mdWatchlistTable .js-wl-check {
        width: 17px;
        height: 17px;
        accent-color: var(--primary);
        cursor: pointer;
    }

    #mdWatchlistTable tr.is-wl-picked > td {
        background: var(--primary-soft);
    }

    /* Dòng khác bộ phận mua hàng với dòng đang chọn - tạm khoá, không chọn kèm được */
    #mdWatchlistTable tr.is-wl-locked > td {
        opacity: 0.45;
    }

    #mdWatchlistTable .js-wl-check:disabled {
        cursor: not-allowed;
    }

    .md-toolbar .wl-count:not(:empty) {
        display: inline-block;
        background: rgba(255, 255, 255, 0.28);
        border-radius: 999px;
        padding: 0 8px;
        margin-left: 4px;
        font-weight: 700;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        if ($.fn.DataTable.isDataTable('#mdWatchlistTable')) {
            $('#mdWatchlistTable').DataTable().destroy();
        }

        var wlTable = $('#mdWatchlistTable').DataTable({
            "order": [],
            "columnDefs": [{ "orderable": false, "targets": [0, 10] }],
            "language": {
                "url": "{{ asset('dataTable/plugins/datatables/i18n/vi.json') }}",
                "emptyTable": "Không có vật tư nào cần dự trù"
            }
        });

        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
        });

        /* ---------- Lọc theo bộ phận mua hàng ---------- */
        var wlBuyerWant = 'all';

        $.fn.dataTable.ext.search.push(function(settings, data, index) {
            if (settings.nTable.id !== 'mdWatchlistTable') return true;
            if (wlBuyerWant === 'all') return true;

            return ($(settings.aoData[index].nTr).attr('data-purchasing') || 'none') === wlBuyerWant;
        });

        // Đổi bộ lọc là đổi hẳn tập vật tư đang nhìn, giữ lại các dòng đã tick nhưng bị ẩn
        // sẽ khiến phiếu lập ra có cả vật tư người dùng không còn thấy
        $(document).on('change', '#wlPurchasingFilter', function() {
            wlBuyerWant = this.value;
            wlTable.draw();
            wlClearPicked();
        });

        /* ---------- Chọn các mục ---------- */
        // Dòng ở trang khác của DataTables vẫn nằm trong bộ nhớ nên phải quét qua API,
        // không quét DOM đang hiển thị
        function wlPicked() {
            return $(wlTable.rows({ search: 'applied' }).nodes()).filter(function() {
                return $(this).find('.js-wl-check').prop('checked');
            });
        }

        function wlRowData($tr) {
            return {
                category_id: $tr.attr('data-category-id') || '',
                name: $tr.attr('data-name') || '',
                spec: $tr.attr('data-spec') || '',
                unit: $tr.attr('data-unit') || '',
                unit_id: $tr.attr('data-unit-id') || '',
                note: $tr.attr('data-note') || '',
                purchasing: $tr.attr('data-purchasing') || 'none'
            };
        }

        /*
        | MỖI LẦN CHỌN CHỈ ĐƯỢC MỘT BỘ PHẬN MUA HÀNG
        |
        | Hai lối đi sau khi chọn khác nhau theo bộ phận mua hàng (Cung Ứng / IT chỉ lập
        | phiếu dự trù, Hành Chánh còn có đề nghị liên phòng ban), nên trộn nhiều bộ phận
        | trong một lần chọn là không xử lý được. Tick dòng đầu tiên xong, các dòng khác bộ
        | phận bị khoá cho tới khi bỏ chọn hết.
        */
        function wlPickedBuyer() {
            var $rows = wlPicked();

            return $rows.length ? ($rows.first().attr('data-purchasing') || 'none') : null;
        }

        function wlLockOtherBuyers() {
            var buyer = wlPickedBuyer();

            $(wlTable.rows({ search: 'applied' }).nodes()).each(function() {
                var $tr = $(this);
                var same = buyer === null || ($tr.attr('data-purchasing') || 'none') === buyer;

                $tr.toggleClass('is-wl-locked', !same);
                $tr.find('.js-wl-check')
                    .prop('disabled', !same)
                    .attr('title', same ? null : 'Mỗi lần chỉ chọn vật tư của cùng một bộ phận mua hàng');
            });
        }

        function wlClearPicked() {
            $(wlTable.rows().nodes()).each(function() {
                $(this).removeClass('is-wl-picked').find('.js-wl-check').prop('checked', false);
            });

            wlSyncButtons();
        }

        // Nút "Đề nghị liên phòng ban" chỉ mở khi các dòng đã chọn đều do Hành Chánh mua và
        // có trong danh mục - phòng nhận cần category_id để tra tồn của mình
        function wlSyncButtons() {
            var $rows = wlPicked();
            var count = $rows.length;
            var allAdmin = count > 0;

            $rows.each(function() {
                var row = wlRowData($(this));

                if (row.purchasing !== 'admin' || !row.category_id) allAdmin = false;
            });

            wlLockOtherBuyers();

            $('.wl-count').text(count ? count : '');
            $('.btn-wl-estimate').prop('disabled', count === 0);
            $('.btn-wl-transfer').prop('disabled', !allAdmin);

            // "Chọn tất cả" chỉ tính trên các dòng còn chọn được, tức cùng bộ phận mua hàng
            var total = $(wlTable.rows({ search: 'applied' }).nodes())
                .filter(function() {
                    return !$(this).find('.js-wl-check').prop('disabled');
                }).length;

            $('.btn-wl-select-all')
                .attr('data-mode', count > 0 && count === total ? 'none' : 'all')
                .html(count > 0 && count === total
                    ? '<i class="far fa-square mr-1"></i> Bỏ chọn tất cả'
                    : '<i class="far fa-check-square mr-1"></i> Chọn tất cả');
        }

        $(document).on('change', '.js-wl-check', function() {
            $(this).closest('tr').toggleClass('is-wl-picked', this.checked);
            wlSyncButtons();
        });

        // Chưa chọn gì thì "Chọn tất cả" lấy bộ phận mua hàng của dòng đầu tiên đang hiển thị
        $(document).on('click', '.btn-wl-select-all', function() {
            var check = $(this).attr('data-mode') === 'all';
            var $visible = $(wlTable.rows({ search: 'applied' }).nodes());

            if (!check) {
                wlClearPicked();

                return;
            }

            var buyer = wlPickedBuyer();

            if (buyer === null && $visible.length) {
                buyer = $visible.first().attr('data-purchasing') || 'none';
            }

            $visible.each(function() {
                if (($(this).attr('data-purchasing') || 'none') !== buyer) return;

                $(this).find('.js-wl-check').prop('checked', true);
                $(this).addClass('is-wl-picked');
            });

            wlSyncButtons();
        });

        /* ---------- Đổ các dòng đã chọn vào modal ---------- */
        function wlFill(modal, builder) {
            var $body = $(modal).find('.wl-items-body').empty();

            wlPicked().each(function(index) {
                $body.append(builder(wlRowData($(this)), index));
            });

            $(modal).modal('show');
        }

        $(document).on('click', '.btn-wl-estimate', function() {
            wlFill('#watchlistEstimateModal', function(row, index) {
                var tpl = $('#watchlistEstimateModal').find('.wl-row-template').html();

                return $(tpl.replace(/__INDEX__/g, index))
                    .find('[data-field="category_id"]').val(row.category_id).end()
                    // Vật tư trong danh mục đã có sẵn quy cách ở danh mục chung, chỉ vật tư
                    // tự nhập mới cần chép tên + quy cách sang phiếu
                    .find('[data-field="material_name"]').val(row.category_id ? '' : row.name).end()
                    .find('[data-field="technical_information"]').val(row.category_id ? '' : row.spec).end()
                    .find('[data-field="display_name"]').text(row.name || '—').end()
                    .find('[data-field="display_spec"]').text(row.spec || '—').end()
                    .find('[data-field="unit_id"]').val(row.unit_id || '').end()
                    .find('[data-field="purpose"]').val(row.note).end();
            });
        });

        $(document).on('click', '.btn-wl-transfer', function() {
            wlFill('#watchlistTransferModal', function(row, index) {
                var tpl = $('#watchlistTransferModal').find('.wl-row-template').html();

                return $(tpl.replace(/__INDEX__/g, index))
                    .find('[data-field="category_id"]').val(row.category_id).end()
                    .find('[data-field="requested_unit"]').val(row.unit).end()
                    .find('[data-field="display_name"]').text(row.name || '—').end()
                    .find('[data-field="display_spec"]').text(row.spec || '—').end()
                    .find('[data-field="display_unit"]').text(row.unit || '—').end()
                    .find('[data-field="note"]').val(row.note).end();
            });
        });

        wlSyncButtons();
    });
</script>
