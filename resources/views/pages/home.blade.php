@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    @php
        // Số lượng bỏ đuôi 0 thừa, đúng cách các màn hình Tồn đang hiển thị
        $num = fn($value) => rtrim(rtrim(number_format((float) $value, 4, '.', ','), '0'), '.');
        $ago = function ($value) {
            if (!$value) {
                return '';
            }
            try {
                return \Carbon\Carbon::parse($value)->diffForHumans();
            } catch (\Throwable $e) {
                return '';
            }
        };
    @endphp

    <style>
        /* ============ NỀN & KHUNG CHUNG ============ */
        .dashboard {
            padding: 20px 20px 32px;
        }

        .dash-card {
            background: #fff;
            border: 1px solid rgba(var(--primary-rgb), 0.10);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-sm);
            height: 100%;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .dash-card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 16px 20px;
            border-bottom: 1px solid rgba(var(--primary-rgb), 0.10);
            background: linear-gradient(180deg, var(--primary-soft) 0%, #fff 100%);
        }

        .dash-card-head h2 {
            margin: 0;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 9px;
        }

        .dash-card-head h2 i {
            font-size: 15px;
            color: var(--primary-light);
        }

        .dash-card-body {
            flex: 1;
            padding: 8px 0;
            overflow-y: auto;
            max-height: 430px;
        }

        .dash-card-foot {
            padding: 12px 20px;
            border-top: 1px solid rgba(var(--primary-rgb), 0.08);
            background: #FBFDFF;
            font-size: 13px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .dash-link {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .dash-link:hover {
            color: var(--primary-dark);
            text-decoration: none;
        }

        /* ============ THẺ TỔNG QUAN ============ */
        .stat-tile {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 18px 20px;
            background: #fff;
            border: 1px solid rgba(var(--primary-rgb), 0.10);
            border-left: 4px solid var(--primary);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-sm);
            text-decoration: none;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            height: 100%;
        }

        .stat-tile:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            text-decoration: none;
        }

        .stat-tile .stat-icon {
            width: 48px;
            height: 48px;
            flex: 0 0 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--border-radius-md);
            background: var(--primary-soft);
            color: var(--primary);
            font-size: 20px;
        }

        .stat-tile .stat-value {
            font-size: 26px;
            font-weight: 700;
            line-height: 1.1;
            color: var(--text-main);
        }

        .stat-tile .stat-label {
            font-size: 13px;
            color: var(--text-main);
            opacity: 0.7;
        }

        .stat-tile.is-warning {
            border-left-color: #F59E0B;
        }

        .stat-tile.is-warning .stat-icon {
            background: #FEF6E7;
            color: #B45309;
        }

        .stat-tile.is-danger {
            border-left-color: #DC2626;
        }

        .stat-tile.is-danger .stat-icon {
            background: #FDECEC;
            color: #B91C1C;
        }

        .stat-tile.is-accent {
            border-left-color: var(--accent);
        }

        .stat-tile.is-accent .stat-icon {
            background: #E6F8FB;
            color: #0E8FA5;
        }

        /* ============ DÒNG DANH SÁCH ============ */
        .dash-item {
            display: flex;
            align-items: flex-start;
            gap: 13px;
            padding: 13px 20px;
            border-bottom: 1px solid rgba(var(--primary-rgb), 0.07);
            text-decoration: none;
            color: inherit;
            transition: background 0.2s ease;
        }

        .dash-item:last-child {
            border-bottom: none;
        }

        a.dash-item:hover {
            background: var(--primary-soft);
            text-decoration: none;
            color: inherit;
        }

        .dash-item .item-icon {
            width: 34px;
            height: 34px;
            flex: 0 0 34px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: var(--primary-soft);
            color: var(--primary);
            font-size: 14px;
            margin-top: 2px;
        }

        .dash-item .item-body {
            flex: 1;
            min-width: 0;
        }

        .dash-item .item-title {
            font-weight: 600;
            color: var(--text-main);
            font-size: 14px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
        }

        .dash-item .item-sub {
            font-size: 12.5px;
            color: var(--text-main);
            opacity: 0.65;
            margin-top: 3px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .dash-item .item-side {
            flex: 0 0 auto;
            text-align: right;
            font-size: 12px;
            color: var(--text-main);
            opacity: 0.6;
            white-space: nowrap;
            margin-top: 3px;
        }

        .dash-item.is-unread {
            background: rgba(var(--primary-rgb), 0.04);
        }

        /* ============ NHÃN TRẠNG THÁI ============ */
        .dash-tag {
            display: inline-block;
            padding: 2px 9px;
            border-radius: 999px;
            font-size: 11.5px;
            font-weight: 600;
            white-space: nowrap;
            background: var(--primary-soft);
            color: var(--primary-dark);
        }

        .dash-tag.tag-danger {
            background: #FDECEC;
            color: #B91C1C;
        }

        .dash-tag.tag-warning {
            background: #FEF6E7;
            color: #B45309;
        }

        .dash-tag.tag-success {
            background: #E7F6EC;
            color: #15803D;
        }

        .dash-tag.tag-me {
            background: var(--accent);
            color: #fff;
        }

        /* ============ THANH TIẾN ĐỘ TỒN ============ */
        .stock-bar {
            height: 6px;
            border-radius: 999px;
            background: rgba(var(--primary-rgb), 0.12);
            overflow: hidden;
            margin-top: 7px;
            max-width: 220px;
        }

        .stock-bar span {
            display: block;
            height: 100%;
            border-radius: 999px;
            background: #F59E0B;
        }

        .stock-bar.is-out span {
            background: #DC2626;
        }

        /* ============ TAB ============ */
        .dash-tabs {
            border: none;
            gap: 6px;
        }

        .dash-tabs .nav-link {
            border: none;
            border-radius: 999px;
            padding: 6px 16px;
            font-size: 13px;
            font-weight: 600;
            color: var(--primary-dark);
            background: var(--primary-soft);
            transition: all 0.2s ease;
        }

        .dash-tabs .nav-link.active {
            background: var(--primary);
            color: #fff;
        }

        /* ============ TRẠNG THÁI RỖNG ============ */
        .dash-empty {
            padding: 46px 20px;
            text-align: center;
            color: var(--text-main);
            opacity: 0.55;
        }

        .dash-empty i {
            font-size: 34px;
            color: var(--primary-lighter);
            display: block;
            margin-bottom: 12px;
        }

        .dash-empty p {
            margin: 0;
            font-size: 13.5px;
        }

        @media (max-width: 767px) {
            .dashboard {
                padding: 14px 12px 24px;
            }

            .stat-tile .stat-value {
                font-size: 22px;
            }
        }
    </style>

    <div class="content-wrapper">
        <div class="dashboard">

            {{-- ============ THẺ TỔNG QUAN ============ --}}
            <div class="row">
                <div class="col-6 col-xl mb-3">
                    <a href="#approvalCard" class="stat-tile">
                        <div class="stat-icon"><i class="fas fa-tasks"></i></div>
                        <div>
                            <div class="stat-value">{{ $approvalTotal }}</div>
                            <div class="stat-label">Mục cần duyệt</div>
                        </div>
                    </a>
                </div>

                <div class="col-6 col-xl mb-3">
                    <a href="#approvalCard" class="stat-tile is-accent">
                        <div class="stat-icon"><i class="fas fa-signature"></i></div>
                        <div>
                            <div class="stat-value">{{ $waitingMeTotal }}</div>
                            <div class="stat-label">Đang chờ bạn xử lý</div>
                        </div>
                    </a>
                </div>

                <div class="col-6 col-xl mb-3">
                    <a href="#stockCard" class="stat-tile {{ $expiredTotal > 0 ? 'is-danger' : 'is-warning' }}">
                        <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
                        <div>
                            <div class="stat-value">{{ $expiryTotal }}</div>
                            <div class="stat-label">
                                Sắp / quá hạn dùng
                                @if ($expiredTotal > 0)
                                    <span class="dash-tag tag-danger">{{ $expiredTotal }} đã quá hạn</span>
                                @endif
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-6 col-xl mb-3">
                    <a href="#stockCard" class="stat-tile is-warning">
                        <div class="stat-icon"><i class="fas fa-warehouse"></i></div>
                        <div>
                            <div class="stat-value">{{ $lowStockTotal }}</div>
                            <div class="stat-label">Tồn dưới định mức</div>
                        </div>
                    </a>
                </div>

                {{-- Bấm thẻ này thì mở luôn tab Đánh giá của khối nhắc nhở bên dưới --}}
                <div class="col-6 col-xl mb-3">
                    <a href="#stockCard"
                        class="stat-tile js-open-assess {{ $assessOverdueTotal > 0 ? 'is-danger' : 'is-accent' }}">
                        <div class="stat-icon"><i class="fas fa-clipboard-check"></i></div>
                        <div>
                            <div class="stat-value">{{ $assessmentTotal }}</div>
                            <div class="stat-label">
                                Cần đánh giá hạn dùng
                                @if ($assessOverdueTotal > 0)
                                    <span class="dash-tag tag-danger">{{ $assessOverdueTotal }} đã quá hạn</span>
                                @endif
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <div class="row">

                {{-- ============ MỤC CẦN DUYỆT ============ --}}
                <div class="col-xl-7 mb-3">
                    <div class="dash-card" id="approvalCard">
                        <div class="dash-card-head">
                            <h2><i class="fas fa-file-signature"></i> Mục Cần Duyệt</h2>
                            @if ($waitingMeTotal > 0)
                                <span class="dash-tag tag-me">{{ $waitingMeTotal }} việc của bạn</span>
                            @endif
                        </div>

                        <div class="dash-card-body">
                            @forelse ($approvals as $item)
                                <a href="{{ $item['url'] }}" class="dash-item">
                                    <div class="item-icon"><i class="{{ $item['icon'] }}"></i></div>
                                    <div class="item-body">
                                        <div class="item-title">
                                            {{ $item['code'] }}
                                            @if ($item['waiting_me'])
                                                <span class="dash-tag tag-me">Chờ bạn ký</span>
                                            @endif
                                        </div>
                                        <div class="item-sub">
                                            {{ $item['label'] }} &middot; {{ $item['title'] }}
                                        </div>
                                    </div>
                                    <div class="item-side">
                                        <span class="dash-tag tag-warning">{{ $item['status_label'] }}</span>
                                        <div class="mt-1">{{ $ago($item['since']) }}</div>
                                    </div>
                                </a>
                            @empty
                                <div class="dash-empty">
                                    <i class="fas fa-check-circle"></i>
                                    <p>Không có phiếu nào đang chờ duyệt.</p>
                                </div>
                            @endforelse
                        </div>

                        @if ($approvalTotal > count($approvals))
                            <div class="dash-card-foot">
                                <span>Đang hiện {{ count($approvals) }} / {{ $approvalTotal }} mục</span>
                                <a class="dash-link" href="{{ route('pages.estimate.chemicalEstimate.list') }}">
                                    Mở màn hình Dự Trù <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- ============ THÔNG BÁO & HOẠT ĐỘNG ============ --}}
                <div class="col-xl-5 mb-3">
                    <div class="dash-card">
                        <div class="dash-card-head">
                            <h2><i class="fas fa-bell"></i> Thông Báo</h2>
                            @if ($unreadTotal > 0)
                                <span class="dash-tag tag-me">{{ $unreadTotal }} chưa đọc</span>
                            @endif
                        </div>

                        <div class="dash-card-body">
                            @forelse ($notifications as $notification)
                                <a href="{{ $notification->url ?: '#' }}"
                                    class="dash-item {{ $notification->is_read ? '' : 'is-unread' }}">
                                    <div class="item-icon"><i class="fas fa-bell"></i></div>
                                    <div class="item-body">
                                        <div class="item-title">{{ $notification->activity_type }}</div>
                                        <div class="item-sub">{{ $notification->message }}</div>
                                        <div class="item-sub">
                                            {{ $notification->sender_name ?: 'Hệ thống' }} &middot;
                                            {{ $ago($notification->created_at) }}
                                        </div>
                                    </div>
                                </a>
                            @empty
                                <div class="dash-empty">
                                    <i class="fas fa-inbox"></i>
                                    <p>Chưa có thông báo nào.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                {{-- ============ NHẮC NHỞ TỒN KHO ============ --}}
                <div class="col-12 mb-3">
                    <div class="dash-card" id="stockCard">
                        <div class="dash-card-head">
                            <h2><i class="fas fa-warehouse"></i> Nhắc Nhở Tồn Kho</h2>
                            <ul class="nav dash-tabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" data-toggle="tab" href="#tabExpiry" role="tab">
                                        Hạn dùng ({{ $expiryTotal }})
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-toggle="tab" href="#tabLowStock" role="tab">
                                        Tồn thấp ({{ $lowStockTotal }})
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-toggle="tab" href="#tabAssessment" role="tab">
                                        Đánh giá ({{ $assessmentTotal }})
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <div class="dash-card-body tab-content">

                            {{-- Hạn dùng: theo từng lô còn tồn --}}
                            <div class="tab-pane fade show active" id="tabExpiry" role="tabpanel">
                                @forelse ($expiryAlerts as $lot)
                                    <a href="{{ route($lot['route']) }}" class="dash-item">
                                        <div class="item-icon"><i class="{{ $lot['icon'] }}"></i></div>
                                        <div class="item-body">
                                            <div class="item-title">
                                                {{ $lot['code'] }}
                                                <span class="dash-tag">{{ $lot['kind_label'] }}</span>
                                            </div>
                                            <div class="item-sub">
                                                {{ $lot['item_name'] }} &middot; còn
                                                {{ $num($lot['remaining']) }} {{ $lot['unit'] }}
                                            </div>
                                        </div>
                                        <div class="item-side">
                                            <span class="dash-tag {{ $lot['level'] === 'expired' ? 'tag-danger' : 'tag-warning' }}">
                                                {{ $lot['level_label'] }}
                                            </span>
                                            <div class="mt-1">
                                                {{ \Carbon\Carbon::parse($lot['expiry'])->format('d/m/Y') }}
                                            </div>
                                        </div>
                                    </a>
                                @empty
                                    <div class="dash-empty">
                                        <i class="fas fa-check-circle"></i>
                                        <p>Không có lô nào quá hạn hoặc còn dưới {{ $nearExpiryDays }} ngày sử dụng.</p>
                                    </div>
                                @endforelse
                            </div>

                            {{-- Tồn thấp: cộng dồn các lô của cùng một mặt hàng --}}
                            <div class="tab-pane fade" id="tabLowStock" role="tabpanel">
                                @forelse ($lowStockAlerts as $stock)
                                    <a href="{{ route($stock['route']) }}" class="dash-item">
                                        <div class="item-icon"><i class="{{ $stock['icon'] }}"></i></div>
                                        <div class="item-body">
                                            <div class="item-title">
                                                {{ $stock['item_name'] }}
                                                <span class="dash-tag">{{ $stock['kind_label'] }}</span>
                                                @if ($stock['code'])
                                                    <span class="dash-tag">{{ $stock['code'] }}</span>
                                                @endif
                                            </div>
                                            <div class="item-sub">
                                                Còn {{ $num($stock['remaining']) }} {{ $stock['unit'] }} /
                                                {{ $stock['has_min_stock'] ? 'định mức' : 'mức tham chiếu' }}
                                                {{ $num($stock['threshold']) }} {{ $stock['unit'] }}
                                                &middot; {{ $stock['lots'] }} lô
                                            </div>
                                            <div class="stock-bar {{ $stock['level'] === 'out' ? 'is-out' : '' }}">
                                                <span style="width: {{ $stock['percent'] }}%"></span>
                                            </div>
                                        </div>
                                        <div class="item-side">
                                            <span class="dash-tag {{ $stock['level'] === 'out' ? 'tag-danger' : 'tag-warning' }}">
                                                {{ $stock['level_label'] }}
                                            </span>
                                        </div>
                                    </a>
                                @empty
                                    <div class="dash-empty">
                                        <i class="fas fa-check-circle"></i>
                                        <p>Mọi mặt hàng đều còn tồn trên định mức của phòng ban.</p>
                                    </div>
                                @endforelse
                            </div>

                            {{-- Đánh giá hạn dùng: mốc chưa làm, đã quá hạn hoặc đến hạn trong ít ngày tới --}}
                            <div class="tab-pane fade" id="tabAssessment" role="tabpanel">
                                @forelse ($assessmentAlerts as $item)
                                    <a href="{{ route('pages.stabilityAssessment.standardStability.detail', ['id' => $item['list_id']]) }}"
                                        class="dash-item">
                                        <div class="item-icon"><i class="{{ $item['icon'] }}"></i></div>
                                        <div class="item-body">
                                            <div class="item-title">
                                                {{ $item['code'] }}
                                                <span class="dash-tag">{{ $item['point'] }} · {{ $item['point_name'] }}</span>
                                            </div>
                                            <div class="item-sub">
                                                {{ $item['item_name'] }}
                                                @if ($item['batch_no'])
                                                    &middot; lô {{ $item['batch_no'] }}
                                                @endif
                                                @if ($item['category_code'])
                                                    &middot; {{ $item['category_code'] }}
                                                @endif
                                            </div>
                                        </div>
                                        <div class="item-side">
                                            <span class="dash-tag {{ $item['level'] === 'overdue' ? 'tag-danger' : 'tag-warning' }}">
                                                {{ $item['level_label'] }}
                                            </span>
                                            <div class="mt-1">
                                                {{ \Carbon\Carbon::parse($item['due_date'])->format('d/m/Y') }}
                                            </div>
                                        </div>
                                    </a>
                                @empty
                                    <div class="dash-empty">
                                        <i class="fas fa-check-circle"></i>
                                        <p>Không có mục chuẩn nào phải đánh giá hạn dùng trong
                                             {{ $assessDueDays }} ngày tới.</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <div class="dash-card-foot">
                            <span>
                                Ngưỡng nhắc hạn dùng: {{ $nearExpiryDays }} ngày &middot;
                                đánh giá: {{ $assessDueDays }} ngày
                            </span>
                            <a class="dash-link js-foot-link" data-tab="#tabAssessment"
                                href="{{ route('pages.stabilityAssessment.assessmentPlan.list') }}" style="display: none">
                                Mở Kế Hoạch Đánh Giá <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                            <a class="dash-link js-foot-link" data-tab="#tabExpiry,#tabLowStock"
                                href="{{ route('pages.inventory.chemicalInventory.list') }}">
                                Mở màn hình Tồn Kho <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            /* Thẻ "Cần đánh giá hạn dùng" mở thẳng tab Đánh giá chứ không chỉ cuộn xuống */
            $(document).on('click', '.js-open-assess', function() {
                $('.dash-tabs a[href="#tabAssessment"]').tab('show');
            });

            /*
            | Đường dẫn dưới chân khối nhắc nhở đổi theo tab đang mở: xem hạn dùng / tồn
            | thấp thì sang Tồn Kho, xem đánh giá thì sang Kế Hoạch Đánh Giá.
            */
            $('.dash-tabs a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
                var current = $(e.target).attr('href');

                $('.js-foot-link').each(function() {
                    var tabs = ($(this).data('tab') || '').split(',');

                    $(this).toggle(tabs.indexOf(current) !== -1);
                });
            });
        });
    </script>
@endsection
