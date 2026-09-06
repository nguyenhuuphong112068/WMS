@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    {{--
    | ============================================================================
    |  TẠM ẨN — BẢNG TỔNG HỢP TRANG CHỦ
    |  Thẻ tổng quan / mục cần duyệt / thông báo / nhắc nhở tồn kho được bọc trong
    |  @if (false) để tạm ngưng hiển thị. Bật lại: đổi thành @if (true) hoặc bỏ cặp
    |  @if ... @endif. Dữ liệu vẫn do HomeController cấp sẵn nên không cần sửa gì thêm.
    | ============================================================================
    --}}
    @if (false)
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
    @endif
    {{-- ============================ HẾT PHẦN TẠM ẨN ============================ --}}

    {{-- ============ MÀN HÌNH CHÀO MỪNG — NỀN CHỦ ĐỀ QUẢN LÝ KHO ============ --}}
    <style>
        .content-wrapper {
            background: var(--primary-soft);
        }

        .home-hero {
            min-height: calc(100vh - 57px);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 24px 24px 0;
            /* Nền lưới xanh mờ + ánh sáng trên đỉnh — đồng bộ với trang đăng nhập */
            background-color: var(--primary-soft);
            background-image:
                linear-gradient(rgba(var(--primary-rgb), 0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(var(--primary-rgb), 0.05) 1px, transparent 1px),
                radial-gradient(1200px 520px at 50% 0%, rgba(var(--primary-rgb), 0.14), transparent 70%),
                linear-gradient(135deg, var(--primary-soft) 0%, var(--primary-lighter) 165%);
            background-size: 60px 60px, 60px 60px, 100% 100%, 100% 100%;
            background-position: center top;
        }

        .home-hero__stage {
            flex: 1 1 auto;
            min-height: 0;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 8px 0 20px;
        }

        .home-hero__art {
            width: 100%;
            max-width: 975px;
        }

        .home-hero__art svg {
            display: block;
            width: 100%;
            height: auto;
            filter: drop-shadow(0 16px 30px rgba(var(--primary-rgb), 0.18));
        }

        .home-hero__text {
            flex: 0 0 auto;
            width: 100%;
            padding-bottom: 40px;
        }

        .home-hero h1 {
            margin: 0 0 10px;
            font-size: 26px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--primary);
        }

        .home-hero__sub {
            margin: 0 0 6px;
            font-size: 15px;
            font-weight: 600;
            color: var(--text-main);
        }

        .home-hero__hint {
            margin: 0 0 20px;
            font-size: 13.5px;
            color: var(--text-main);
            opacity: 0.65;
        }

        .home-hero__date {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 18px;
            border-radius: 999px;
            background: #fff;
            border: 1px solid rgba(var(--primary-rgb), 0.16);
            box-shadow: var(--shadow-sm);
            font-size: 13px;
            font-weight: 600;
            color: var(--primary-dark);
        }

        .home-hero__date i {
            color: var(--primary-light);
        }

        @media (max-width: 767px) {
            .home-hero {
                min-height: calc(100vh - 54px);
                padding: 16px 16px 0;
            }

            .home-hero__text {
                padding-bottom: 28px;
            }

            .home-hero h1 {
                font-size: 20px;
            }
        }
    </style>

    <div class="content-wrapper">
        <div class="home-hero">
            <div class="home-hero__stage">
                <div class="home-hero__art">
                    <svg viewBox="-150 0 1030 400" role="img"
                        aria-label="Minh hoạ kho: hai kệ pallet vật tư, kệ hóa chất, tủ chất chuẩn và nhân viên kiểm kho bằng máy tính bảng">

                    {{-- Vì kèo mái + đèn trần --}}
                    <g fill="none" stroke="var(--primary-lighter)" stroke-width="2" opacity=".5">
                        <path d="M-120 64 L-40 26 L40 64 L120 26 L200 64 L280 26 L360 64 L440 26 L520 64 L600 26 L680 64 L760 26 L840 64" />
                        <path d="M-120 64 H840" />
                        <path d="M-40 26 V64 M120 26 V64 M280 26 V64 M440 26 V64 M600 26 V64 M760 26 V64" />
                    </g>
                    <g stroke="var(--primary-lighter)" stroke-width="2">
                        <line x1="-30" y1="64" x2="-30" y2="82" />
                        <line x1="130" y1="64" x2="130" y2="82" />
                        <line x1="340" y1="64" x2="340" y2="82" />
                        <line x1="560" y1="64" x2="560" y2="82" />
                        <line x1="770" y1="64" x2="770" y2="82" />
                    </g>
                    <g fill="var(--primary-light)">
                        <path d="M-44 82 H-16 L-23 96 H-37 Z" />
                        <path d="M116 82 H144 L137 96 H123 Z" />
                        <path d="M326 82 H354 L347 96 H333 Z" />
                        <path d="M546 82 H574 L567 96 H553 Z" />
                        <path d="M756 82 H784 L777 96 H763 Z" />
                    </g>
                    <g fill="var(--accent)" opacity=".35">
                        <circle cx="-30" cy="102" r="5" />
                        <circle cx="130" cy="102" r="5" />
                        <circle cx="340" cy="102" r="5" />
                        <circle cx="560" cy="102" r="5" />
                        <circle cx="770" cy="102" r="5" />
                    </g>

                    {{-- Sàn kho --}}
                    <line x1="-136" y1="344" x2="856" y2="344" stroke="var(--primary-lighter)" stroke-width="3" />

                    {{-- Kệ pallet vật tư (bên trái, thêm mới) --}}
                    <g>
                        <rect x="-146" y="118" width="11" height="226" rx="3" fill="var(--primary)" />
                        <rect x="20" y="118" width="11" height="226" rx="3" fill="var(--primary)" />
                        <rect x="-146" y="188" width="177" height="9" fill="var(--primary-dark)" />
                        <rect x="-146" y="256" width="177" height="9" fill="var(--primary-dark)" />
                        <rect x="-146" y="326" width="177" height="9" fill="var(--primary-dark)" />

                        {{-- Tầng trên --}}
                        <rect x="-136" y="150" width="34" height="38" rx="2" fill="var(--primary-soft)"
                            stroke="var(--primary-light)" stroke-width="2" />
                        <rect x="-98" y="156" width="30" height="32" rx="2" fill="var(--primary-soft)"
                            stroke="var(--primary-light)" stroke-width="2" />
                        <rect x="-62" y="148" width="38" height="40" rx="2" fill="var(--primary-soft)"
                            stroke="var(--primary-light)" stroke-width="2" />
                        <rect x="-20" y="158" width="34" height="30" rx="2" fill="var(--primary-light)" />

                        {{-- Tầng giữa --}}
                        <rect x="-138" y="220" width="32" height="36" rx="2" fill="var(--primary-light)" />
                        <rect x="-100" y="214" width="40" height="42" rx="2" fill="var(--primary-soft)"
                            stroke="var(--primary-light)" stroke-width="2" />
                        <rect x="-54" y="226" width="30" height="30" rx="2" fill="var(--primary-soft)"
                            stroke="var(--primary-light)" stroke-width="2" />
                        <rect x="-18" y="222" width="32" height="34" rx="2" fill="var(--primary-soft)"
                            stroke="var(--primary-light)" stroke-width="2" />

                        {{-- Tầng dưới: kiện hàng bọc màng --}}
                        <rect x="-138" y="288" width="150" height="38" rx="2" fill="var(--primary-soft)"
                            stroke="var(--primary-light)" stroke-width="2" />
                        <line x1="-100" y1="288" x2="-100" y2="326" stroke="var(--primary-light)" stroke-width="3" />
                        <line x1="-40" y1="288" x2="-40" y2="326" stroke="var(--primary-light)" stroke-width="3" />
                    </g>

                    {{-- Kệ pallet (vật tư) --}}
                    <g>
                        <rect x="44" y="118" width="11" height="226" rx="3" fill="var(--primary)" />
                        <rect x="203" y="118" width="11" height="226" rx="3" fill="var(--primary)" />
                        <rect x="44" y="188" width="170" height="9" fill="var(--primary-dark)" />
                        <rect x="44" y="256" width="170" height="9" fill="var(--primary-dark)" />
                        <rect x="44" y="326" width="170" height="9" fill="var(--primary-dark)" />

                        <g transform="translate(58,188)">
                            <rect x="0" y="-13" width="62" height="5" fill="var(--primary-light)" />
                            <rect x="0" y="-6" width="62" height="4" fill="var(--primary-light)" />
                            <rect x="3" y="-9" width="7" height="3" fill="var(--primary-light)" />
                            <rect x="52" y="-9" width="7" height="3" fill="var(--primary-light)" />
                            <rect x="4" y="-42" width="26" height="29" rx="2" fill="var(--primary-soft)"
                                stroke="var(--primary-light)" stroke-width="2" />
                            <rect x="34" y="-38" width="24" height="25" rx="2" fill="var(--primary-soft)"
                                stroke="var(--primary-light)" stroke-width="2" />
                            <rect x="80" y="-13" width="62" height="5" fill="var(--primary-light)" />
                            <rect x="80" y="-6" width="62" height="4" fill="var(--primary-light)" />
                            <rect x="83" y="-9" width="7" height="3" fill="var(--primary-light)" />
                            <rect x="132" y="-9" width="7" height="3" fill="var(--primary-light)" />
                            <rect x="84" y="-40" width="26" height="27" rx="2" fill="var(--primary-soft)"
                                stroke="var(--primary-light)" stroke-width="2" />
                            <rect x="114" y="-45" width="24" height="32" rx="2" fill="var(--primary-soft)"
                                stroke="var(--primary-light)" stroke-width="2" />
                        </g>
                        <g transform="translate(58,256)">
                            <rect x="0" y="-13" width="62" height="5" fill="var(--primary-light)" />
                            <rect x="0" y="-6" width="62" height="4" fill="var(--primary-light)" />
                            <rect x="3" y="-9" width="7" height="3" fill="var(--primary-light)" />
                            <rect x="52" y="-9" width="7" height="3" fill="var(--primary-light)" />
                            <rect x="3" y="-40" width="56" height="27" rx="2" fill="var(--primary-soft)"
                                stroke="var(--primary-light)" stroke-width="2" />
                            <rect x="80" y="-13" width="62" height="5" fill="var(--primary-light)" />
                            <rect x="80" y="-6" width="62" height="4" fill="var(--primary-light)" />
                            <rect x="83" y="-9" width="7" height="3" fill="var(--primary-light)" />
                            <rect x="132" y="-9" width="7" height="3" fill="var(--primary-light)" />
                            <rect x="84" y="-38" width="26" height="25" rx="2" fill="var(--primary-soft)"
                                stroke="var(--primary-light)" stroke-width="2" />
                            <rect x="114" y="-42" width="24" height="29" rx="2" fill="var(--primary-soft)"
                                stroke="var(--primary-light)" stroke-width="2" />
                        </g>
                        <g transform="translate(58,326)">
                            <rect x="0" y="-13" width="62" height="5" fill="var(--primary-light)" />
                            <rect x="0" y="-6" width="62" height="4" fill="var(--primary-light)" />
                            <rect x="3" y="-9" width="7" height="3" fill="var(--primary-light)" />
                            <rect x="52" y="-9" width="7" height="3" fill="var(--primary-light)" />
                            <rect x="4" y="-41" width="54" height="28" rx="2" fill="var(--primary-soft)"
                                stroke="var(--primary-light)" stroke-width="2" />
                            <rect x="80" y="-13" width="62" height="5" fill="var(--primary-light)" />
                            <rect x="80" y="-6" width="62" height="4" fill="var(--primary-light)" />
                            <rect x="83" y="-9" width="7" height="3" fill="var(--primary-light)" />
                            <rect x="132" y="-9" width="7" height="3" fill="var(--primary-light)" />
                            <rect x="82" y="-43" width="58" height="30" rx="2" fill="var(--primary-soft)"
                                stroke="var(--primary-light)" stroke-width="2" />
                        </g>
                    </g>

                    {{-- Kệ hóa chất: can nhựa, phuy và chai có nhãn cảnh báo --}}
                    <g transform="translate(250,0)">
                        <rect x="6" y="116" width="208" height="228" fill="var(--primary-soft)" opacity=".35" />
                        <rect x="0" y="112" width="10" height="232" rx="2" fill="var(--primary-dark)" />
                        <rect x="210" y="112" width="10" height="232" rx="2" fill="var(--primary-dark)" />
                        <rect x="0" y="112" width="220" height="8" fill="var(--primary)" />
                        <rect x="0" y="170" width="220" height="8" fill="var(--primary)" />
                        <rect x="0" y="230" width="220" height="8" fill="var(--primary)" />
                        <rect x="0" y="290" width="220" height="8" fill="var(--primary)" />

                        <g transform="translate(14,170)">
                            <g>
                                <rect x="0" y="-30" width="24" height="30" rx="3" fill="var(--primary-light)" />
                                <rect x="7" y="-38" width="10" height="9" rx="1" fill="var(--primary-dark)" />
                                <rect x="3" y="-22" width="18" height="11" rx="1" fill="var(--primary-soft)" />
                            </g>
                            <g transform="translate(32,0)">
                                <rect x="0" y="-30" width="24" height="30" rx="3" fill="var(--primary-light)" />
                                <rect x="7" y="-38" width="10" height="9" rx="1" fill="var(--primary-dark)" />
                                <rect x="3" y="-22" width="18" height="11" rx="1" fill="var(--primary-soft)" />
                            </g>
                            <g transform="translate(66,0)">
                                <rect x="5" y="-33" width="8" height="7" fill="var(--primary-dark)" />
                                <rect x="0" y="-27" width="18" height="27" rx="3" fill="var(--primary-soft)"
                                    stroke="var(--primary-light)" stroke-width="2" />
                                <path d="M9 -20 l6 6 l-6 6 l-6 -6 z" fill="#F59E0B" />
                            </g>
                            <g transform="translate(92,0)">
                                <rect x="0" y="-30" width="24" height="30" rx="3" fill="var(--primary-light)" />
                                <rect x="7" y="-38" width="10" height="9" rx="1" fill="var(--primary-dark)" />
                                <rect x="3" y="-22" width="18" height="11" rx="1" fill="var(--primary-soft)" />
                            </g>
                            <g transform="translate(126,0)">
                                <rect x="5" y="-33" width="8" height="7" fill="var(--primary-dark)" />
                                <rect x="0" y="-27" width="18" height="27" rx="3" fill="var(--primary-soft)"
                                    stroke="var(--primary-light)" stroke-width="2" />
                                <path d="M9 -20 l6 6 l-6 6 l-6 -6 z" fill="#F59E0B" />
                            </g>
                            <g transform="translate(152,0)">
                                <rect x="0" y="-30" width="24" height="30" rx="3" fill="var(--primary-light)" />
                                <rect x="7" y="-38" width="10" height="9" rx="1" fill="var(--primary-dark)" />
                                <rect x="3" y="-22" width="18" height="11" rx="1" fill="var(--primary-soft)" />
                            </g>
                        </g>

                        <g transform="translate(14,230)">
                            <g>
                                <rect x="0" y="-34" width="28" height="34" rx="4" fill="var(--primary)" />
                                <ellipse cx="14" cy="-34" rx="14" ry="4" fill="var(--primary-light)" />
                                <rect x="0" y="-24" width="28" height="3" fill="var(--primary-dark)" opacity=".35" />
                                <rect x="0" y="-12" width="28" height="3" fill="var(--primary-dark)" opacity=".35" />
                            </g>
                            <g transform="translate(38,0)">
                                <rect x="0" y="-34" width="28" height="34" rx="4" fill="var(--primary)" />
                                <ellipse cx="14" cy="-34" rx="14" ry="4" fill="var(--primary-light)" />
                                <rect x="0" y="-24" width="28" height="3" fill="var(--primary-dark)" opacity=".35" />
                                <rect x="0" y="-12" width="28" height="3" fill="var(--primary-dark)" opacity=".35" />
                            </g>
                            <g transform="translate(82,0)">
                                <rect x="5" y="-33" width="8" height="7" fill="var(--primary-dark)" />
                                <rect x="0" y="-27" width="18" height="27" rx="3" fill="var(--primary-soft)"
                                    stroke="var(--primary-light)" stroke-width="2" />
                                <path d="M9 -20 l6 6 l-6 6 l-6 -6 z" fill="#F59E0B" />
                            </g>
                            <g transform="translate(106,0)">
                                <rect x="5" y="-33" width="8" height="7" fill="var(--primary-dark)" />
                                <rect x="0" y="-27" width="18" height="27" rx="3" fill="var(--primary-soft)"
                                    stroke="var(--primary-light)" stroke-width="2" />
                                <path d="M9 -20 l6 6 l-6 6 l-6 -6 z" fill="#F59E0B" />
                            </g>
                            <g transform="translate(132,0)">
                                <rect x="0" y="-34" width="28" height="34" rx="4" fill="var(--primary)" />
                                <ellipse cx="14" cy="-34" rx="14" ry="4" fill="var(--primary-light)" />
                                <rect x="0" y="-24" width="28" height="3" fill="var(--primary-dark)" opacity=".35" />
                                <rect x="0" y="-12" width="28" height="3" fill="var(--primary-dark)" opacity=".35" />
                            </g>
                            <g transform="translate(174,0)">
                                <rect x="5" y="-33" width="8" height="7" fill="var(--primary-dark)" />
                                <rect x="0" y="-27" width="18" height="27" rx="3" fill="var(--primary-soft)"
                                    stroke="var(--primary-light)" stroke-width="2" />
                                <path d="M9 -20 l6 6 l-6 6 l-6 -6 z" fill="#F59E0B" />
                            </g>
                        </g>

                        <g transform="translate(14,290)">
                            <g>
                                <rect x="0" y="-30" width="24" height="30" rx="3" fill="var(--primary-light)" />
                                <rect x="7" y="-38" width="10" height="9" rx="1" fill="var(--primary-dark)" />
                                <rect x="3" y="-22" width="18" height="11" rx="1" fill="var(--primary-soft)" />
                            </g>
                            <g transform="translate(32,0)">
                                <rect x="0" y="-30" width="24" height="30" rx="3" fill="var(--primary-light)" />
                                <rect x="7" y="-38" width="10" height="9" rx="1" fill="var(--primary-dark)" />
                                <rect x="3" y="-22" width="18" height="11" rx="1" fill="var(--primary-soft)" />
                            </g>
                            <g transform="translate(66,0)">
                                <rect x="0" y="-34" width="28" height="34" rx="4" fill="var(--primary)" />
                                <ellipse cx="14" cy="-34" rx="14" ry="4" fill="var(--primary-light)" />
                                <rect x="0" y="-24" width="28" height="3" fill="var(--primary-dark)" opacity=".35" />
                                <rect x="0" y="-12" width="28" height="3" fill="var(--primary-dark)" opacity=".35" />
                            </g>
                            <g transform="translate(108,0)">
                                <rect x="0" y="-30" width="24" height="30" rx="3" fill="var(--primary-light)" />
                                <rect x="7" y="-38" width="10" height="9" rx="1" fill="var(--primary-dark)" />
                                <rect x="3" y="-22" width="18" height="11" rx="1" fill="var(--primary-soft)" />
                            </g>
                            <g transform="translate(142,0)">
                                <rect x="5" y="-33" width="8" height="7" fill="var(--primary-dark)" />
                                <rect x="0" y="-27" width="18" height="27" rx="3" fill="var(--primary-soft)"
                                    stroke="var(--primary-light)" stroke-width="2" />
                                <path d="M9 -20 l6 6 l-6 6 l-6 -6 z" fill="#F59E0B" />
                            </g>
                            <g transform="translate(168,0)">
                                <rect x="0" y="-30" width="24" height="30" rx="3" fill="var(--primary-light)" />
                                <rect x="7" y="-38" width="10" height="9" rx="1" fill="var(--primary-dark)" />
                                <rect x="3" y="-22" width="18" height="11" rx="1" fill="var(--primary-soft)" />
                            </g>
                        </g>
                    </g>

                    {{-- Phuy hóa chất đặt dưới sàn --}}
                    <g transform="translate(206,300)">
                        <rect x="0" y="0" width="34" height="44" rx="5" fill="var(--primary)" />
                        <ellipse cx="17" cy="0" rx="17" ry="5" fill="var(--primary-light)" />
                        <rect x="0" y="14" width="34" height="4" fill="var(--primary-dark)" opacity=".35" />
                        <rect x="0" y="30" width="34" height="4" fill="var(--primary-dark)" opacity=".35" />
                        <path d="M9 20 l6 6 l-6 6 l-6 -6 z" fill="#F59E0B" />
                    </g>
                    <g transform="translate(240,312)">
                        <rect x="0" y="0" width="30" height="32" rx="4" fill="var(--primary-light)" />
                        <ellipse cx="15" cy="0" rx="15" ry="4" fill="var(--primary-soft)" />
                        <rect x="0" y="12" width="30" height="3" fill="var(--primary-dark)" opacity=".3" />
                    </g>

                    {{-- Tủ chất chuẩn: 2 tủ kính có lọ chuẩn bên trong --}}
                    <g transform="translate(614,0)">
                        <g>
                            <rect x="-4" y="106" width="100" height="14" rx="3" fill="var(--primary-dark)" />
                            <rect x="0" y="118" width="92" height="226" rx="6" fill="var(--primary)" />
                            <rect x="9" y="130" width="74" height="198" rx="4" fill="var(--primary-soft)" opacity=".6"
                                stroke="var(--primary-light)" stroke-width="2" />
                            <rect x="22" y="136" width="40" height="12" rx="2" fill="#fff" opacity=".85" />
                            <g fill="var(--primary-light)" opacity=".75">
                                <rect x="12" y="170" width="68" height="3" />
                                <rect x="12" y="212" width="68" height="3" />
                                <rect x="12" y="254" width="68" height="3" />
                                <rect x="12" y="296" width="68" height="3" />
                            </g>
                            <g fill="var(--primary-light)">
                                <rect x="16" y="158" width="6" height="12" rx="1" />
                                <rect x="25" y="158" width="6" height="12" rx="1" />
                                <rect x="34" y="158" width="6" height="12" rx="1" />
                                <rect x="52" y="158" width="6" height="12" rx="1" />
                                <rect x="61" y="158" width="6" height="12" rx="1" />
                                <rect x="16" y="200" width="6" height="12" rx="1" />
                                <rect x="25" y="200" width="6" height="12" rx="1" />
                                <rect x="41" y="200" width="6" height="12" rx="1" />
                                <rect x="59" y="200" width="6" height="12" rx="1" />
                                <rect x="68" y="200" width="6" height="12" rx="1" />
                                <rect x="16" y="242" width="6" height="12" rx="1" />
                                <rect x="32" y="242" width="6" height="12" rx="1" />
                                <rect x="41" y="242" width="6" height="12" rx="1" />
                                <rect x="57" y="242" width="6" height="12" rx="1" />
                                <rect x="66" y="242" width="6" height="12" rx="1" />
                                <rect x="20" y="284" width="6" height="12" rx="1" />
                                <rect x="36" y="284" width="6" height="12" rx="1" />
                                <rect x="52" y="284" width="6" height="12" rx="1" />
                                <rect x="61" y="284" width="6" height="12" rx="1" />
                            </g>
                            <rect x="76" y="222" width="5" height="34" rx="2" fill="var(--primary-dark)" />
                        </g>
                        <g transform="translate(104,0)">
                            <rect x="-4" y="106" width="100" height="14" rx="3" fill="var(--primary-dark)" />
                            <rect x="0" y="118" width="92" height="226" rx="6" fill="var(--primary)" />
                            <rect x="9" y="130" width="74" height="198" rx="4" fill="var(--primary-soft)" opacity=".6"
                                stroke="var(--primary-light)" stroke-width="2" />
                            <rect x="22" y="136" width="40" height="12" rx="2" fill="#fff" opacity=".85" />
                            <g fill="var(--primary-light)" opacity=".75">
                                <rect x="12" y="170" width="68" height="3" />
                                <rect x="12" y="212" width="68" height="3" />
                                <rect x="12" y="254" width="68" height="3" />
                                <rect x="12" y="296" width="68" height="3" />
                            </g>
                            <g fill="var(--primary-light)">
                                <rect x="16" y="158" width="6" height="12" rx="1" />
                                <rect x="25" y="158" width="6" height="12" rx="1" />
                                <rect x="41" y="158" width="6" height="12" rx="1" />
                                <rect x="50" y="158" width="6" height="12" rx="1" />
                                <rect x="66" y="158" width="6" height="12" rx="1" />
                                <rect x="16" y="200" width="6" height="12" rx="1" />
                                <rect x="32" y="200" width="6" height="12" rx="1" />
                                <rect x="41" y="200" width="6" height="12" rx="1" />
                                <rect x="57" y="200" width="6" height="12" rx="1" />
                                <rect x="66" y="200" width="6" height="12" rx="1" />
                                <rect x="16" y="242" width="6" height="12" rx="1" />
                                <rect x="25" y="242" width="6" height="12" rx="1" />
                                <rect x="34" y="242" width="6" height="12" rx="1" />
                                <rect x="52" y="242" width="6" height="12" rx="1" />
                                <rect x="61" y="242" width="6" height="12" rx="1" />
                                <rect x="20" y="284" width="6" height="12" rx="1" />
                                <rect x="36" y="284" width="6" height="12" rx="1" />
                                <rect x="45" y="284" width="6" height="12" rx="1" />
                                <rect x="61" y="284" width="6" height="12" rx="1" />
                            </g>
                            <rect x="11" y="222" width="5" height="34" rx="2" fill="var(--primary-dark)" />
                        </g>
                    </g>

                    {{-- Pallet trong lối đi --}}
                    <g transform="translate(556,320)">
                        <rect x="0" y="0" width="82" height="7" fill="var(--primary-light)" />
                        <rect x="0" y="11" width="82" height="5" fill="var(--primary-light)" />
                        <rect x="2" y="7" width="10" height="4" fill="var(--primary-light)" />
                        <rect x="36" y="7" width="10" height="4" fill="var(--primary-light)" />
                        <rect x="70" y="7" width="10" height="4" fill="var(--primary-light)" />
                        <rect x="6" y="-24" width="32" height="24" rx="2" fill="var(--primary-soft)"
                            stroke="var(--primary-light)" stroke-width="2" />
                        <rect x="44" y="-28" width="32" height="28" rx="2" fill="var(--primary-soft)"
                            stroke="var(--primary-light)" stroke-width="2" />
                        <line x1="6" y1="-12" x2="38" y2="-12" stroke="var(--primary-lighter)" stroke-width="3" />
                        <line x1="44" y1="-14" x2="76" y2="-14" stroke="var(--primary-lighter)" stroke-width="3" />
                    </g>

                    {{-- Nhân viên kiểm kho cầm máy tính bảng --}}
                    <g transform="translate(496,182)">
                        <ellipse cx="22" cy="160" rx="36" ry="6" fill="var(--primary)" opacity=".14" />
                        <rect x="12" y="92" width="14" height="66" rx="4" fill="var(--primary-dark)" />
                        <rect x="28" y="94" width="14" height="64" rx="4" fill="var(--primary)" />
                        <path d="M4 154 h22 a4 4 0 0 1 4 4 v3 h-30 v-3 a4 4 0 0 1 4 -4 z" fill="var(--text-main)" />
                        <path d="M24 156 h24 a4 4 0 0 1 4 4 v3 h-32 v-3 a4 4 0 0 1 4 -4 z" fill="var(--text-main)" />
                        {{-- Thân áo trắng --}}
                        <path d="M6 46 q0 -9 9 -9 h20 q9 0 9 9 v40 q0 6 -6 6 h-26 q-6 0 -6 -6 z" fill="#fff"
                            stroke="var(--primary-light)" stroke-width="2" />
                        <rect x="19" y="30" width="9" height="9" fill="var(--primary-soft)" />
                        <circle cx="23" cy="19" r="12" fill="var(--primary-soft)" stroke="var(--primary-light)" stroke-width="2" />
                        {{-- Nón bảo hộ công trình: chỏm tròn + gờ giữa + núm thông hơi + vành rộng --}}
                        <path d="M7 18 Q8 1 23 1 Q38 1 39 18 Z" fill="var(--primary)" />
                        <path d="M23 2 V18" stroke="var(--primary-dark)" stroke-width="1.5" opacity=".35" />
                        <rect x="19" y="-3" width="8" height="6" rx="2" fill="var(--primary)" />
                        <path d="M1 17 Q23 26 45 17 L43 12 Q23 20 3 12 Z" fill="var(--primary-dark)" />
                        <path d="M14 50 q-16 3 -24 15" fill="none" stroke="var(--primary-dark)" stroke-width="9"
                            stroke-linecap="round" />
                        <path d="M18 52 q-14 5 -22 16" fill="none" stroke="var(--primary)" stroke-width="8"
                            stroke-linecap="round" />
                        <circle cx="-10" cy="66" r="6" fill="var(--primary-soft)" />
                        <g transform="rotate(-14 -18 66)">
                            <rect x="-34" y="50" width="26" height="34" rx="3" fill="var(--primary-dark)" />
                            <rect x="-31" y="53" width="20" height="28" rx="1" fill="var(--primary-soft)" />
                            <rect x="-28" y="57" width="14" height="3" rx="1" fill="var(--primary-light)" />
                            <rect x="-28" y="63" width="11" height="3" rx="1" fill="var(--primary-light)" />
                            <rect x="-28" y="69" width="13" height="3" rx="1" fill="var(--primary-light)" />
                            <rect x="-28" y="75" width="8" height="3" rx="1" fill="var(--accent)" />
                        </g>
                        {{-- Logo Stellapharm giữa ngực như trái tim --}}
                        <svg x="16" y="52" width="18" height="16" viewBox="0 0 60 53" overflow="visible">
                            <path d="m116.5 2718.38-23.354 11.12v5.06a8.664 8.664 0 0 0 8.573 8.75h16.125a1.175 1.175 0 0 1 0 2.35h-16.125a8.664 8.664 0 0 0 -8.573 8.75v11.29a1.153 1.153 0 1 1 -2.305 0v-11.29a8.664 8.664 0 0 0 -8.572-8.75h-16.114a1.175 1.175 0 0 1 0-2.35h16.114a8.664 8.664 0 0 0 8.572-8.75v-5.06l-23.341-11.12a3.861 3.861 0 0 0 -5.5 3.56v29.21a5.167 5.167 0 0 0 2.52 4.45l24.963 14.7a4.944 4.944 0 0 0 5.038 0l24.963-14.7a5.167 5.167 0 0 0 2.519-4.45v-29.21a3.861 3.861 0 0 0 -5.503-3.56z"
                                fill="#cdc818" fill-rule="evenodd" transform="translate(-62 -2718)" />
                        </svg>
                    </g>
                    </svg>
                </div>
            </div>

            <div class="home-hero__text">
                <h1>Hệ Thống Quản Lý Kho</h1>
                <p class="home-hero__sub">
                    Xin chào {{ session('user')['fullName'] ?? 'bạn' }}
                    @if (!empty(session('user')['selected_department']))
                        &middot; {{ session('user')['selected_department'] }}
                    @endif
                </p>
                <p class="home-hero__hint">Chọn chức năng trên thanh menu bên trái để bắt đầu công việc.</p>
                <span class="home-hero__date">
                    <i class="far fa-calendar-alt"></i>
                    Hôm nay: {{ \Carbon\Carbon::now()->format('d/m/Y') }}
                </span>
            </div>
        </div>
    </div>
@endsection
