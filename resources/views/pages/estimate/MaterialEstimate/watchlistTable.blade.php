{{--
| BẢNG "VẬT TƯ CẦN ..." - dùng chung cho hai màn hình, chọn bằng $wlMode:
|
|   estimate (mặc định): DỰ TRÙ VẬT TƯ, tab "Danh sách vật tư cần dự trù" - vật tư Cung Ứng mua
|                        (MaterialWatchlist::forEstimate), chọn nhiều dòng rồi Lập phiếu dự trù.
|   vpp                : SỬ DỤNG VẬT TƯ, tab Đề nghị VPP > "Danh sách vật tư cần đề nghị Hành Chính"
|                        - vật tư Hành Chính mua (MaterialWatchlist::forVpp), chọn nhiều dòng rồi
|                        Lập đề nghị VPP gửi phòng Hành Chính (modal #vppWatchlistModal).
|
| Biến vào: $items, $wlMode. Hai màn hình không bao giờ cùng trang nên id bảng / nút giữ nguyên.
--}}
@php
    use App\Support\MaterialClassification;
    use App\Support\MaterialVpp;
    use App\Support\MaterialWatchlist;

    $wlMode = ($wlMode ?? 'estimate') === 'vpp' ? 'vpp' : 'estimate';
    $wlIsVpp = $wlMode === 'vpp';
    $wlSources = MaterialWatchlist::sourceLabels();

    // Phòng Hành Chính tự dự trù cả vật tư Hành Chính mua nên mới cần cột Bộ Phận Mua Hàng
    $wlShowBuyer = ! $wlIsVpp && MaterialVpp::isVppDepartment((int) (session('user')['selected_department_id'] ?? 0));
    $wlActionCol = $wlShowBuyer ? 11 : 10;

    $wlEstRoute = 'pages.estimate.materialEstimate.';
    $wlDismissRoute = $wlIsVpp ? 'pages.export.materialExport.watchlistDismiss' : $wlEstRoute . 'watchlistDismiss';
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

        @if ($wlIsVpp)
            @perm('export_material_request')
                <button type="button" class="btn btn-primary btn-sm btn-wl-vpp" disabled>
                    <i class="fas fa-paper-plane mr-1"></i> Lập đề nghị cấp phát VPP đến HC <span class="wl-count"></span>
                </button>
            @endperm
        @else
            @perm('estimate_material_create')
                <button type="button" class="btn btn-primary btn-sm btn-wl-estimate" disabled>
                    <i class="fas fa-clipboard-check mr-1"></i> Lập phiếu dự trù <span class="wl-count"></span>
                </button>
            @endperm
        @endif
    </div>

    <div class="md-filter">
        <label for="wlProgressFilter"><i class="fas fa-filter mr-1"></i> Trạng thái</label>
        <select id="wlProgressFilter" class="form-control form-control-sm">
            <option value="open">{{ $wlIsVpp ? 'Chưa đề nghị' : 'Chưa lập phiếu' }}</option>
            <option value="done">{{ $wlIsVpp ? 'Đã đề nghị' : 'Đã lập phiếu' }}</option>
            <option value="all">Tất cả</option>
        </select>

        <label for="wlSourceFilter" class="ml-2"><i class="fas fa-filter mr-1"></i> Nguồn</label>
        <select id="wlSourceFilter" class="form-control form-control-sm">
            <option value="all">Tất cả</option>
            @foreach ($wlSources as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
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
                <th class="text-right" style="width: 105px">SL Đề Nghị</th>
                @if ($wlShowBuyer)
                    <th style="width: 125px">Bộ Phận Mua Hàng</th>
                @endif
                <th style="width: 190px">Nguồn</th>
                <th style="width: 200px">{{ $wlIsVpp ? 'Lý Do Đề Nghị' : 'Lý Do Dự Trù' }}</th>
                <th class="text-center" style="width: 80px">Thao Tác</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                @php
                    $wlPurchasing = $item->purchasing_department ?: null;
                    $wlPurchasingLabel = MaterialClassification::purchasingLabel($wlPurchasing);
                @endphp
                <tr data-sources="{{ implode(' ', $item->sources) }}"
                    data-month="{{ $item->forecast->month ?? '' }}"
                    data-category-id="{{ $item->category_id }}"
                    data-name="{{ $item->material_name }}"
                    data-spec="{{ $item->technical_specification }}"
                    data-unit="{{ $item->unit }}"
                    data-unit-id="{{ $item->unit_id }}"
                    {{-- Chưa có SL đề nghị thì mặc định bằng ngưỡng tối thiểu của phòng --}}
                    data-quantity="{{ $item->quantity !== null ? $wlNum($item->quantity) : ($item->min_stock ? $wlNum($item->min_stock) : '') }}"
                    data-note="{{ $item->note ?: ($item->forecast ? $wlSources[MaterialWatchlist::SOURCE_FORECAST] : '') }}"
                    data-progress="{{ $item->in_progress ? 'done' : 'open' }}"
                    class="{{ $item->in_progress ? 'is-wl-progress' : '' }}">
                    <td class="text-center">
                        @if ($item->in_progress)
                            {{-- Đã có phiếu chưa xong - không chọn lại được --}}
                            <i class="fas fa-lock wl-lock" title="{{ $wlIsVpp ? 'Đã đề nghị, chờ xử lý xong mới đề nghị lại được' : 'Đã lập phiếu, chờ xử lý xong mới dự trù lại được' }}"></i>
                        @else
                            <input type="checkbox" class="js-wl-check" value="{{ $loop->index }}">
                        @endif
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
                    <td class="text-right font-weight-bold">{{ $item->quantity !== null ? $wlNum($item->quantity) : '—' }}</td>
                    @if ($wlShowBuyer)
                        <td>
                            @if ($wlPurchasingLabel)
                                <span class="wl-buyer {{ $wlPurchasing }}">{{ $wlPurchasingLabel }}</span>
                            @else
                                <span class="md-empty">—</span>
                            @endif
                        </td>
                    @endif
                    <td>
                        @if ($item->below_threshold)
                            <span class="md-badge rejected">Dưới ngưỡng tồn tối thiểu</span>
                        @endif
                        @foreach ($item->in_progress as $ref)
                            <div class="md-sub mt-1">
                                @if ($ref['type'] === 'estimate')
                                    <a href="{{ route($wlEstRoute . 'detail', ['id' => $ref['id']]) }}" class="md-badge wl-progress" title="Mở phiếu dự trù">
                                        <i class="fas fa-file-signature mr-1"></i>Đã lập {{ $ref['code'] }} · {{ $ref['status'] }}
                                    </a>
                                @else
                                    <a href="{{ route('pages.export.materialExport.list', ['tab' => $ref['tab'] ?? 'transfer']) }}" class="md-badge wl-progress"
                                        title="{{ $ref['label'] }} - theo dõi ở Sử Dụng Vật Tư">
                                        <i class="fas fa-share-square mr-1"></i>Đã đề nghị {{ $ref['code'] }} · {{ $ref['status'] }}
                                    </a>
                                @endif
                            </div>
                        @endforeach
                        @if ($item->forecast)
                            <div class="md-sub mt-1">
                                <span class="md-badge wl-forecast"
                                    title="Khả dụng {{ $wlNum($item->forecast->available) }} - Nhu cầu chu kỳ {{ $wlNum($item->forecast->demand) }} = Thiếu {{ $wlNum(-$item->forecast->gap) }}">
                                    {{ $wlSources[MaterialWatchlist::SOURCE_FORECAST] }}
                                </span>
                            </div>
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
                                <br><small>{{ $item->remembered_at ? \Carbon\Carbon::parse($item->remembered_at)->format('d/m/Y H:i') : '' }}</small>
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
                            <form action="{{ route($wlDismissRoute) }}" method="POST" class="form-md-confirm-cancel d-inline"
                                data-title="Bỏ ghi nhớ vật tư này?" data-text="Vật tư sẽ chỉ còn hiện lại ở đây nếu tồn xuống dưới ngưỡng tối thiểu hoặc thiếu tồn khả dụng cho tháng tới.">
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

    .md-badge.wl-progress {
        background: #DCFCE7;
        color: #15803D;
        border: 1px solid #86EFAC;
        text-decoration: none;
        transition: background 0.2s;
    }

    a.md-badge.wl-progress:hover {
        background: #BBF7D0;
    }

    #mdWatchlistTable tr.is-wl-progress > td {
        background: #F8FAFC;
    }

    #mdWatchlistTable .wl-lock {
        color: var(--primary-lighter);
        cursor: help;
    }

    .md-badge.wl-forecast {
        background: var(--primary-soft);
        color: var(--primary-dark);
        border: 1px solid var(--primary-lighter);
        cursor: help;
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

    /* Cột ô chọn: cố định hẹp, không để DataTables / trình duyệt chia thêm chỗ trống */
    #mdWatchlistTable thead th.wl-check-col,
    #mdWatchlistTable tbody td:first-child:not(.dataTables_empty) {
        width: 42px !important;
        min-width: 42px;
        max-width: 42px;
        padding-left: 4px;
        padding-right: 4px;
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
            // Giữ bề rộng khai ở <th> - autoWidth chia lại cột và làm cột ô chọn phình to
            "autoWidth": false,
            "order": [],
            "columnDefs": [{ "orderable": false, "targets": [0, {{ $wlActionCol }}] }],
            "language": {
                "url": "{{ asset('dataTable/plugins/datatables/i18n/vi.json') }}",
                "emptyTable": @json($wlIsVpp ? 'Không có vật tư nào cần đề nghị Hành Chính' : 'Không có vật tư nào cần dự trù')
            }
        });

        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
        });

        /* ---------- Lọc theo trạng thái + nguồn ---------- */
        var wlSourceWant = 'all';
        var wlProgressWant = 'open';

        $.fn.dataTable.ext.search.push(function(settings, data, index) {
            if (settings.nTable.id !== 'mdWatchlistTable') return true;

            var $tr = $(settings.aoData[index].nTr);

            if (wlProgressWant !== 'all' && $tr.attr('data-progress') !== wlProgressWant) {
                return false;
            }

            // Một dòng có thể thuộc nhiều nguồn cùng lúc
            return wlSourceWant === 'all' || ($tr.attr('data-sources') || '').split(' ').indexOf(wlSourceWant) !== -1;
        });

        // Đổi bộ lọc là đổi hẳn tập vật tư đang nhìn, giữ lại các dòng đã tick nhưng bị ẩn
        // sẽ khiến phiếu lập ra có cả vật tư người dùng không còn thấy
        $(document).on('change', '#wlSourceFilter', function() {
            wlSourceWant = this.value;
            wlTable.draw();
            wlClearPicked();
        });

        $(document).on('change', '#wlProgressFilter', function() {
            wlProgressWant = this.value;
            wlTable.draw();
            wlClearPicked();
        });

        // Trình duyệt có thể giữ lại giá trị ô lọc khi tải lại trang mà không phát 'change'
        wlSourceWant = $('#wlSourceFilter').val() || 'all';
        wlProgressWant = $('#wlProgressFilter').val() || 'open';
        wlTable.draw();

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
                // Số lượng đã khai lúc đề nghị dự trù, ',' ngăn nghìn của $wlNum phải bỏ trước khi điền vào ô số
                quantity: ($tr.attr('data-quantity') || '').replace(/,/g, ''),
                note: $tr.attr('data-note') || '',
                // Tháng của nguồn "Thiếu tồn khả dụng" - điền sẵn tháng cần dùng của phiếu dự trù
                month: $tr.attr('data-month') || ''
            };
        }

        function wlClearPicked() {
            $(wlTable.rows().nodes()).each(function() {
                $(this).removeClass('is-wl-picked').find('.js-wl-check').prop('checked', false);
            });

            wlSyncButtons();
        }

        function wlSyncButtons() {
            var count = wlPicked().length;

            $('.wl-count').text(count ? count : '');
            $('.btn-wl-estimate, .btn-wl-vpp').prop('disabled', count === 0);

            // "Chọn tất cả" chỉ tính trên các dòng còn chọn được - dòng đã lập phiếu không có ô chọn
            var total = $(wlTable.rows({ search: 'applied' }).nodes())
                .filter(function() {
                    return $(this).find('.js-wl-check').length > 0;
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

        $(document).on('click', '.btn-wl-select-all', function() {
            if ($(this).attr('data-mode') !== 'all') {
                wlClearPicked();

                return;
            }

            $(wlTable.rows({ search: 'applied' }).nodes()).each(function() {
                $(this).find('.js-wl-check').prop('checked', true);
                $(this).toggleClass('is-wl-picked', $(this).find('.js-wl-check').length > 0);
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
                    .find('[data-field="amount"]').val(row.quantity).end()
                    .find('[data-field="for_month_year"]').val(function(i, val) { return row.month || val; }).end()
                    .find('[data-field="purpose"]').val(row.note).end();
            });
        });

        $(document).on('click', '.btn-wl-vpp', function() {
            wlFill('#vppWatchlistModal', function(row, index) {
                var tpl = $('#vppWatchlistModal').find('.wl-row-template').html();

                return $(tpl.replace(/__INDEX__/g, index))
                    .find('[data-field="category_id"]').val(row.category_id).end()
                    .find('[data-field="requested_unit"]').val(row.unit).end()
                    .find('[data-field="display_name"]').text(row.name || '—').end()
                    .find('[data-field="display_spec"]').text(row.spec || '—').end()
                    .find('[data-field="display_unit"]').text(row.unit || '—').end()
                    .find('[data-field="requested_amount"]').val(row.quantity).end()
                    .find('[data-field="note"]').val(row.note).end();
            });
        });

        wlSyncButtons();
    });
</script>
