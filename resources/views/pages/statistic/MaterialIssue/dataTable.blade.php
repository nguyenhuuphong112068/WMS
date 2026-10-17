@include('pages.materData.shared.assets')

@php
    use App\Http\Controllers\Pages\Statistic\MaterialIssueStatisticController as Stat;

    $stRoute = 'pages.statistic.materialIssue.';
    $stActiveTab = request()->query('tab') === 'detail' ? 'detail' : 'summary';
@endphp

<div class="content-wrapper">
    <div class="md-page">
        <div class="card md-card">
            <div class="card-body">

                {{-- ============ BỘ LỌC ============ --}}
                <form method="GET" action="{{ route($stRoute . 'list') }}" class="st-filter" id="stFilterForm">
                    <input type="hidden" name="tab" value="{{ $stActiveTab }}">

                    <div class="st-field">
                        <label for="stFrom">Từ ngày cấp</label>
                        <input type="date" id="stFrom" name="from_date" class="form-control form-control-sm" value="{{ $period['from'] }}">
                    </div>
                    <div class="st-field">
                        <label for="stTo">Đến ngày</label>
                        <input type="date" id="stTo" name="to_date" class="form-control form-control-sm" value="{{ $period['to'] }}">
                    </div>
                    <div class="st-field">
                        <label for="stType">Loại đối tượng</label>
                        <select id="stType" name="object_type" class="form-control form-control-sm">
                            <option value="">-- Tất cả loại --</option>
                            @foreach ($objectTypes as $typeKey => $typeLabel)
                                <option value="{{ $typeKey }}" {{ $typeFilter === $typeKey ? 'selected' : '' }}>{{ $typeLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="st-field st-field-object">
                        <label for="stObject">Đối tượng</label>
                        <select id="stObject" name="object" class="form-control form-control-sm">
                            <option value="">-- Tất cả đối tượng --</option>
                            <option value="{{ Stat::NO_OBJECT }}" {{ $objectFilter === Stat::NO_OBJECT ? 'selected' : '' }}>Chưa gắn đối tượng</option>
                            @if ($selectedObject)
                                <option value="{{ $selectedObject->id }}" selected>{{ $selectedObject->code }} - {{ $selectedObject->name }}</option>
                            @endif
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm st-apply">
                        <i class="fas fa-filter mr-1"></i> Lọc
                    </button>

                    {{-- Bấm mốc nhanh là điền sẵn hai ô ngày rồi gửi luôn --}}
                    <div class="st-quick">
                        @foreach ($presets as $preset)
                            @php
                                $pf = $preset['from']->format('Y-m-d');
                                $pt = $preset['to']->format('Y-m-d');
                            @endphp
                            <button type="button" class="st-chip {{ $pf === $period['from'] && $pt === $period['to'] ? 'is-active' : '' }}"
                                data-from="{{ $pf }}" data-to="{{ $pt }}">{{ $preset['label'] }}</button>
                        @endforeach
                    </div>
                </form>

                {{-- ============ TAB ============ --}}
                <div class="st-tabs">
                    <button type="button" class="st-tab {{ $stActiveTab === 'summary' ? 'is-active' : '' }}" data-tab="summary">
                        <i class="fas fa-layer-group mr-1"></i> Tổng hợp theo đối tượng
                        <span class="st-count">{{ count($groups) }}</span>
                    </button>
                    <button type="button" class="st-tab {{ $stActiveTab === 'detail' ? 'is-active' : '' }}" data-tab="detail">
                        <i class="fas fa-list-ul mr-1"></i> Chi tiết từng lần cấp
                        <span class="st-count">{{ $rows->count() }}</span>
                    </button>
                </div>

                {{-- ============ TỔNG HỢP THEO ĐỐI TƯỢNG ============ --}}
                <div class="st-pane {{ $stActiveTab === 'summary' ? 'is-active' : '' }}" data-pane="summary">
                    @forelse ($groups as $key => $group)
                        <div class="st-group">
                            <div class="st-group-head">
                                @if ($key === Stat::NO_OBJECT)
                                    <span class="st-group-icon is-none"><i class="fas fa-circle-question"></i></span>
                                    <span class="st-group-name">Chưa gắn đối tượng</span>
                                @else
                                    <span class="st-group-icon"><i class="fas fa-crosshairs"></i></span>
                                    <span class="st-group-name">{{ $group['code'] }} - {{ $group['name'] }}</span>
                                    @if ($group['type'])
                                        <span class="md-tag">{{ $group['type'] }}</span>
                                    @endif
                                    @if ($group['location'])
                                        <span class="st-group-loc"><i class="fas fa-location-dot mr-1"></i>{{ $group['location'] }}</span>
                                    @endif
                                @endif
                                <span class="st-group-meta">{{ count($group['items']) }} vật tư · {{ $group['issues'] }} lần cấp</span>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered table-sm st-table mb-0">
                                    <thead>
                                        <tr>
                                            <th class="text-center" style="width: 50px">STT</th>
                                            <th style="min-width: 200px">Vật Tư</th>
                                            <th style="min-width: 160px">Thông Tin Kỹ Thuật</th>
                                            <th class="text-right" style="width: 120px">SL Đã Cấp</th>
                                            <th style="width: 80px">ĐVT</th>
                                            <th class="text-center" style="width: 90px">Lần Cấp</th>
                                            <th class="text-center" style="width: 90px">Phiếu ĐN</th>
                                            <th style="min-width: 220px">Mục Đích</th>
                                            <th class="text-center" style="width: 110px">Cấp Gần Nhất</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($group['items'] as $item)
                                            <tr>
                                                <td class="text-center">{{ $loop->iteration }}</td>
                                                <td>
                                                    <div class="font-weight-bold">{{ $item['name'] }}</div>
                                                    @if ($item['code'])
                                                        <span class="md-tag">{{ $item['code'] }}</span>
                                                    @endif
                                                </td>
                                                <td class="md-sub">{{ $item['spec'] ?: '—' }}</td>
                                                <td class="text-right font-weight-bold st-amount">{{ $stNum($item['amount']) }}</td>
                                                <td>{{ $item['unit'] ?: '—' }}</td>
                                                <td class="text-center">{{ $item['issues'] }}</td>
                                                <td class="text-center">{{ $item['requests'] }}</td>
                                                <td>
                                                    @forelse ($item['purposes'] as $purpose)
                                                        <div class="st-purpose">{{ $purpose }}</div>
                                                    @empty
                                                        <span class="md-sub">—</span>
                                                    @endforelse
                                                </td>
                                                <td class="text-center md-sub">{{ $stDate($item['last_at']) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @empty
                        <div class="st-empty">
                            <i class="fas fa-box-open"></i>
                            Không có lần cấp phát vật tư nào trong kỳ {{ $stDate($period['from']) }} - {{ $stDate($period['to']) }}.
                        </div>
                    @endforelse
                </div>

                {{-- ============ CHI TIẾT TỪNG LẦN CẤP ============ --}}
                <div class="st-pane {{ $stActiveTab === 'detail' ? 'is-active' : '' }}" data-pane="detail">
                    <div class="table-responsive">
                        <table id="stDetailTable" class="table table-bordered table-hover table-sm w-100">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 110px">Ngày Cấp</th>
                                    <th style="min-width: 200px">Đối Tượng</th>
                                    <th style="min-width: 180px">Vật Tư</th>
                                    <th class="text-right" style="width: 100px">SL Cấp</th>
                                    <th style="width: 70px">ĐVT</th>
                                    <th style="width: 120px">Mã Xuất Nhập</th>
                                    <th style="width: 130px">Phiếu Đề Nghị</th>
                                    <th style="min-width: 200px">Mục Đích</th>
                                    <th style="width: 150px">Người Cấp</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $row)
                                    <tr>
                                        <td class="text-center" data-order="{{ $row->issued_at }}">{{ $stDateTime($row->issued_at) }}</td>
                                        <td>
                                            @if ($row->object_code)
                                                <span class="font-weight-bold">{{ $row->object_code }}</span> - {{ $row->object_name }}
                                            @else
                                                <span class="md-sub">Chưa gắn đối tượng</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $row->display_name ?: 'NA' }}
                                            @if ($row->category_code)
                                                <span class="md-tag">{{ $row->category_code }}</span>
                                            @endif
                                        </td>
                                        <td class="text-right font-weight-bold" data-order="{{ (float) $row->amount }}">{{ $stNum($row->amount) }}</td>
                                        <td>{{ $row->unit ?: '—' }}</td>
                                        <td class="md-sub">{{ $row->import_code }}</td>
                                        <td title="{{ $row->request_name }}">{{ $row->request_code }}</td>
                                        <td class="md-sub">{{ $row->purpose ?: '—' }}</td>
                                        <td class="md-sub">{{ $row->used_by ?: '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<style>
    /* ---------- Bộ lọc ---------- */
    .st-filter {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        gap: 10px 12px;
        padding: 14px 16px;
        margin-bottom: 16px;
        border: 1px solid var(--primary-lighter);
        border-radius: var(--border-radius-lg);
        background: var(--primary-soft);
    }

    .st-field {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .st-field label {
        margin: 0;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.4px;
        text-transform: uppercase;
        color: var(--primary-dark);
    }

    .st-field input,
    .st-field select {
        min-width: 150px;
    }

    .st-field-object {
        flex: 1 1 280px;
        max-width: 420px;
    }

    .st-quick {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        width: 100%;
    }

    .st-chip {
        border: 1px solid var(--primary-lighter);
        background: #fff;
        color: var(--primary-dark);
        border-radius: 999px;
        padding: 3px 12px;
        font-size: 0.76rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }

    .st-chip:hover {
        border-color: var(--primary);
    }

    .st-chip.is-active {
        background: var(--primary);
        border-color: var(--primary);
        color: #fff;
    }

    /* ---------- Tab ---------- */
    .st-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 14px;
    }

    .st-tab {
        border: 1px solid var(--primary-soft);
        background: #fff;
        color: var(--text-main);
        border-radius: var(--border-radius-md);
        padding: 8px 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }

    .st-tab.is-active {
        background: linear-gradient(135deg, var(--primary-light), var(--primary));
        color: #fff;
    }

    .st-count {
        display: inline-block;
        min-width: 22px;
        margin-left: 6px;
        padding: 0 7px;
        border-radius: 999px;
        background: rgba(var(--primary-rgb), 0.12);
        font-size: 0.74rem;
    }

    .st-tab.is-active .st-count {
        background: rgba(255, 255, 255, 0.25);
    }

    .st-pane {
        display: none;
    }

    .st-pane.is-active {
        display: block;
    }

    /* ---------- Nhóm theo đối tượng ---------- */
    .st-group {
        border: 1px solid #dbe6f2;
        border-radius: var(--border-radius-lg);
        overflow: hidden;
        margin-bottom: 16px;
        box-shadow: var(--shadow-sm);
    }

    .st-group-head {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
        padding: 10px 14px;
        background: var(--primary-soft);
        border-bottom: 1px solid #dbe6f2;
    }

    .st-group-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: var(--primary);
        color: #fff;
        font-size: 0.8rem;
    }

    .st-group-icon.is-none {
        background: #94a3b8;
    }

    .st-group-name {
        font-weight: 700;
        color: var(--primary-dark);
    }

    .st-group-loc {
        font-size: 0.8rem;
        color: #64748b;
    }

    .st-group-meta {
        margin-left: auto;
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--primary-dark);
    }

    .st-table th {
        background: #fff;
        color: var(--primary);
        font-size: 0.74rem;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        white-space: nowrap;
    }

    .st-amount {
        color: var(--primary-dark);
    }

    .st-purpose+.st-purpose {
        border-top: 1px dashed #e2e8f0;
        margin-top: 3px;
        padding-top: 3px;
    }

    .st-empty {
        text-align: center;
        padding: 40px 16px;
        color: #64748b;
        border: 1.5px dashed #cbd5e1;
        border-radius: var(--border-radius-lg);
    }

    .st-empty i {
        display: block;
        font-size: 2rem;
        color: var(--primary-lighter);
        margin-bottom: 8px;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var $form = $('#stFilterForm');

        /* ---------- Mốc nhanh: điền hai ô ngày rồi lọc luôn ---------- */
        $('.st-chip').on('click', function() {
            $('#stFrom').val($(this).data('from'));
            $('#stTo').val($(this).data('to'));
            $form.trigger('submit');
        });

        /* ---------- Tab: nhớ tab đang mở qua ô ẩn để lọc xong vẫn ở tab đó ---------- */
        $('.st-tab').on('click', function() {
            var tab = $(this).data('tab');

            $('.st-tab').removeClass('is-active').filter(this).addClass('is-active');
            $('.st-pane').removeClass('is-active').filter('[data-pane="' + tab + '"]').addClass('is-active');
            $form.find('[name="tab"]').val(tab);
            if (tab === 'detail' && detailTable) detailTable.columns.adjust();
        });

        /* ---------- Ô chọn Đối tượng: hàng nghìn dòng nên tìm qua AJAX, lọc theo loại đang chọn ---------- */
        $('#stObject').select2({
            theme: 'bootstrap4',
            width: '100%',
            allowClear: true,
            placeholder: '-- Tất cả đối tượng --',
            ajax: {
                url: @json(route($stRoute . 'objects')),
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return { q: params.term || '', page: params.page || 1, type: $('#stType').val() };
                },
                processResults: function(data, params) {
                    // Trang đầu chèn lựa chọn "Chưa gắn đối tượng" để vẫn chọn lại được sau khi tìm
                    if ((params.page || 1) === 1 && !params.term) {
                        data.results.unshift({ id: @json(Stat::NO_OBJECT), text: 'Chưa gắn đối tượng' });
                    }
                    return data;
                }
            },
            language: {
                searching: function() { return 'Đang tìm...'; },
                noResults: function() { return 'Không có đối tượng phù hợp'; },
                loadingMore: function() { return 'Đang tải thêm...'; }
            },
            templateResult: function(item) {
                if (!item.id) return item.text;
                return $('<div></div>').text(item.text)
                    .append(item.hint ? $('<div class="small text-muted"></div>').text(item.hint) : null);
            }
        });

        /* ---------- Bảng chi tiết ---------- */
        var detailTable = $('#stDetailTable').DataTable({
            order: [[0, 'desc']],
            pageLength: 50,
            lengthMenu: [[25, 50, 100, -1], [25, 50, 100, 'Tất cả']],
            language: {
                search: 'Tìm kiếm:',
                lengthMenu: 'Hiển thị _MENU_ dòng',
                info: 'Hiển thị _START_ đến _END_ của _TOTAL_ lần cấp',
                infoEmpty: 'Không có dữ liệu',
                infoFiltered: '(lọc từ _MAX_ lần cấp)',
                zeroRecords: 'Không tìm thấy dòng nào phù hợp',
                emptyTable: 'Không có lần cấp phát nào trong kỳ',
                paginate: { previous: 'Trước', next: 'Sau' }
            }
        });
    });
</script>
