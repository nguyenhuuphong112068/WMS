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

    {{-- ============ TRANG CHỦ — ĐỀ NGHỊ CHỜ BẠN XEM XÉT (giao diện 3D, chỉ CSS + JS thuần) ============ --}}
    <style>
        .content-wrapper {
            background: var(--primary-soft);
        }

        .home-page {
            min-height: calc(100vh - 57px);
            display: flex;
            flex-direction: column;
            gap: 22px;
            padding: 22px;
            background-color: var(--bg-neutral);
            background-image:
                linear-gradient(rgba(var(--primary-rgb), 0.045) 1px, transparent 1px),
                linear-gradient(90deg, rgba(var(--primary-rgb), 0.045) 1px, transparent 1px);
            background-size: 56px 56px, 56px 56px;
        }

        /* ================= BANNER 3D ================= */
        .hero3d {
            position: relative;
            overflow: hidden;
            border-radius: 16px;
            padding: 28px 30px 30px;
            color: #fff;
            background:
                radial-gradient(760px 280px at 88% -30%, rgba(255, 255, 255, 0.26), transparent 62%),
                radial-gradient(520px 260px at 0% 120%, rgba(255, 255, 255, 0.12), transparent 60%),
                linear-gradient(135deg, var(--primary-light) 0%, var(--primary) 42%, var(--primary-dark) 100%);
            box-shadow:
                0 22px 44px -22px rgba(var(--primary-rgb), 0.85),
                inset 0 1px 0 rgba(255, 255, 255, 0.25);
            perspective: 900px;
            isolation: isolate;
        }

        /* Mặt sàn kho dạng lưới phối cảnh */
        .hero3d::before {
            content: '';
            position: absolute;
            left: -25%;
            right: -25%;
            top: 48%;
            height: 160%;
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.16) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.16) 1px, transparent 1px);
            background-size: 54px 54px;
            transform: rotateX(72deg);
            transform-origin: center top;
            -webkit-mask-image: linear-gradient(to bottom, rgba(0, 0, 0, 0.95), transparent 55%);
            mask-image: linear-gradient(to bottom, rgba(0, 0, 0, 0.95), transparent 55%);
            animation: hero-floor 6s linear infinite;
            pointer-events: none;
            z-index: -1;
        }

        @keyframes hero-floor {
            from { background-position: 0 0, 0 0; }
            to { background-position: 0 54px, 0 0; }
        }

        /* Thùng hàng 3D trôi nổi bên phải banner */
        .hero3d__scene {
            position: absolute;
            right: 3%;
            top: 0;
            bottom: 0;
            width: 360px;
            transform-style: preserve-3d;
            transform: rotateX(var(--scene-rx, 0deg)) rotateY(var(--scene-ry, 0deg));
            transition: transform 0.3s ease;
            pointer-events: none;
            z-index: -1;
        }

        .box3d {
            --s: 64px;
            position: absolute;
            width: var(--s);
            height: var(--s);
            transform-style: preserve-3d;
            animation: box-float 7s ease-in-out infinite;
        }

        .box3d__body {
            position: absolute;
            inset: 0;
            transform-style: preserve-3d;
            animation: box-spin 22s linear infinite;
        }

        .box3d__body > span {
            position: absolute;
            inset: 0;
            border: 1px solid rgba(255, 255, 255, 0.6);
            border-radius: 3px;
            background:
                linear-gradient(90deg, transparent 44%, rgba(255, 255, 255, 0.28) 44%, rgba(255, 255, 255, 0.28) 56%, transparent 56%),
                rgba(255, 255, 255, 0.10);
            box-shadow: inset 0 0 22px rgba(255, 255, 255, 0.22);
        }

        .box3d__body > span:nth-child(1) { transform: translateZ(calc(var(--s) / 2)); }
        .box3d__body > span:nth-child(2) { transform: rotateY(180deg) translateZ(calc(var(--s) / 2)); }
        .box3d__body > span:nth-child(3) { transform: rotateY(90deg) translateZ(calc(var(--s) / 2)); }
        .box3d__body > span:nth-child(4) { transform: rotateY(-90deg) translateZ(calc(var(--s) / 2)); }
        .box3d__body > span:nth-child(5) { transform: rotateX(90deg) translateZ(calc(var(--s) / 2)); }
        .box3d__body > span:nth-child(6) { transform: rotateX(-90deg) translateZ(calc(var(--s) / 2)); }

        .box3d--a { --s: 86px; right: 70px; top: 34px; }
        .box3d--b { --s: 52px; right: 230px; top: 26px; animation-delay: -2.5s; }
        .box3d--c { --s: 38px; right: 12px; top: 130px; animation-delay: -4s; }
        .box3d--b .box3d__body { animation-duration: 16s; animation-direction: reverse; }
        .box3d--c .box3d__body { animation-duration: 12s; }

        @keyframes box-spin {
            from { transform: rotateX(-24deg) rotateY(0deg); }
            to { transform: rotateX(-24deg) rotateY(360deg); }
        }

        @keyframes box-float {
            0%, 100% { transform: translate3d(0, 0, 0); }
            50% { transform: translate3d(0, -12px, 30px); }
        }

        .hero3d__content {
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 22px;
        }

        .hero3d__eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 5px 12px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.16);
            border: 1px solid rgba(255, 255, 255, 0.25);
            font-size: 12.5px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        /* layout/css.blade.php ép mọi h1 in hoa + màu primary: lời chào trên nền xanh phải ghi đè */
        .hero3d .hero3d__title {
            margin: 12px 0 4px;
            font-size: 26px;
            font-weight: 700;
            letter-spacing: 0.3px;
            text-transform: none;
            color: #fff;
            text-shadow: 0 2px 0 rgba(0, 0, 0, 0.12), 0 8px 18px rgba(0, 0, 0, 0.18);
        }

        .hero3d__sub {
            margin: 0;
            font-size: 14px;
            opacity: 0.9;
        }

        /* KPI dạng phím nổi: mặt trên kính trắng, cạnh dày phía dưới */
        .hero3d__kpis {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
            max-width: 980px;
        }

        .kpi3d {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 15px 16px;
            border-radius: 14px;
            background: linear-gradient(165deg, #fff 0%, var(--primary-soft) 100%);
            color: var(--text-main);
            text-decoration: none;
            transform: perspective(700px) rotateX(9deg);
            transform-origin: center bottom;
            box-shadow:
                0 7px 0 0 var(--primary-lighter),
                0 22px 26px -10px rgba(0, 0, 0, 0.38);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .kpi3d:hover {
            color: var(--text-main);
            text-decoration: none;
            transform: perspective(700px) rotateX(0deg) translateY(-4px);
            box-shadow:
                0 10px 0 0 var(--primary-lighter),
                0 28px 32px -12px rgba(0, 0, 0, 0.42);
        }

        .kpi3d:active {
            transform: perspective(700px) rotateX(0deg) translateY(4px);
            box-shadow:
                0 3px 0 0 var(--primary-lighter),
                0 10px 16px -8px rgba(0, 0, 0, 0.35);
        }

        .kpi3d__value {
            font-size: 28px;
            font-weight: 800;
            line-height: 1.05;
            color: var(--primary-dark);
        }

        .kpi3d__value small {
            font-size: 15px;
            font-weight: 700;
        }

        .kpi3d__label {
            margin-top: 3px;
            font-size: 12.5px;
            font-weight: 600;
            color: var(--text-muted);
        }

        /* ================= KHỐI ICON NỔI (dùng chung) ================= */
        .gem3d {
            --gem-from: var(--primary-light);
            --gem-to: var(--primary-dark);
            --gem-glow: rgba(var(--primary-rgb), 0.45);
            width: 46px;
            height: 46px;
            flex: 0 0 46px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 13px;
            background: linear-gradient(145deg, var(--gem-from) 0%, var(--gem-to) 100%);
            color: #fff;
            font-size: 18px;
            box-shadow:
                inset 0 2px 0 rgba(255, 255, 255, 0.35),
                inset 0 -4px 0 rgba(0, 0, 0, 0.18),
                0 8px 14px -4px var(--gem-glow);
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.25);
        }

        .gem3d--sm {
            width: 30px;
            height: 30px;
            flex-basis: 30px;
            border-radius: 9px;
            font-size: 13px;
        }

        .gem3d--accent {
            --gem-from: var(--accent);
            --gem-to: var(--primary);
            --gem-glow: rgba(var(--primary-rgb), 0.35);
        }

        .gem3d--warning {
            --gem-from: var(--state-warning);
            --gem-to: var(--state-warning-dark);
            --gem-glow: rgba(var(--state-warning-rgb), 0.45);
        }

        .gem3d--navy {
            --gem-from: var(--primary);
            --gem-to: var(--primary-dark);
        }

        /* ================= BẢNG ĐỀ NGHỊ ================= */
        .board3d {
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            background: #fff;
            border: 1px solid rgba(var(--primary-rgb), 0.10);
            border-radius: 16px;
            box-shadow: 0 1px 2px rgba(var(--primary-rgb), 0.06), 0 18px 40px -26px rgba(var(--primary-rgb), 0.55);
        }

        .board3d__head {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 18px 26px;
            border-bottom: 1px solid rgba(var(--primary-rgb), 0.08);
        }

        .board3d__head h2 {
            margin: 0;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--primary);
        }

        .board3d__body {
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            padding: 6px 26px 26px;
        }

        .board3d__section {
            padding-top: 18px;
            scroll-margin-top: 80px;
        }

        .board3d__section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            color: var(--primary-dark);
        }

        .board3d__section-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, rgba(var(--primary-rgb), 0.25), transparent);
        }

        .board3d__section-count {
            padding: 2px 10px;
            border-radius: 999px;
            background: var(--primary-soft);
            color: var(--primary-dark);
            font-size: 12px;
        }

        .board3d__grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 22px;
        }

        /* ================= THẺ 3D NGHIÊNG THEO CHUỘT ================= */
        .tile3d {
            perspective: 1000px;
            animation: tile-in 0.6s cubic-bezier(0.2, 0.7, 0.2, 1) both;
            animation-delay: calc(var(--i, 0) * 70ms);
        }

        @keyframes tile-in {
            from { opacity: 0; transform: translateY(22px) rotateX(16deg); }
            to { opacity: 1; transform: none; }
        }

        .tile3d__card {
            position: relative;
            height: 100%;
            display: flex;
            flex-direction: column;
            background: linear-gradient(180deg, #fff 0%, var(--bg-neutral) 100%);
            border: 1px solid rgba(var(--primary-rgb), 0.12);
            border-radius: 14px;
            box-shadow:
                0 1px 2px rgba(var(--primary-rgb), 0.08),
                0 14px 26px -16px rgba(var(--primary-rgb), 0.5);
            transform-style: preserve-3d;
            transform: rotateX(var(--rx, 0deg)) rotateY(var(--ry, 0deg));
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .tile3d:hover .tile3d__card {
            box-shadow:
                0 2px 4px rgba(var(--primary-rgb), 0.08),
                0 30px 44px -20px rgba(var(--primary-rgb), 0.6);
        }

        /* Vạch màu trên đỉnh thẻ */
        .tile3d__card::before {
            content: '';
            position: absolute;
            left: 18px;
            right: 18px;
            top: -1px;
            height: 3px;
            border-radius: 0 0 4px 4px;
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
        }

        .board3d__section--dept .tile3d__card::before {
            background: linear-gradient(90deg, var(--accent), var(--primary-light));
        }

        /* Vệt sáng chạy theo con trỏ */
        .tile3d__glare {
            position: absolute;
            inset: 0;
            border-radius: inherit;
            background: radial-gradient(circle at var(--mx, 50%) var(--my, 0%), rgba(var(--primary-rgb), 0.10), transparent 58%);
            opacity: 0;
            transition: opacity 0.25s ease;
            pointer-events: none;
        }

        .tile3d:hover .tile3d__glare {
            opacity: 1;
        }

        .tile3d__head {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 18px 18px 12px;
            color: var(--text-main);
            text-decoration: none;
            transform: translateZ(28px);
        }

        .tile3d__head:hover {
            color: var(--text-main);
            text-decoration: none;
        }

        .tile3d__label {
            flex: 1;
            min-width: 0;
            font-size: 14.5px;
            font-weight: 700;
            line-height: 1.3;
        }

        .tile3d__count {
            flex: 0 0 auto;
            min-width: 42px;
            padding: 4px 11px;
            border-radius: 11px;
            background: linear-gradient(180deg, #fff 0%, rgba(var(--state-warning-rgb), 0.18) 100%);
            border: 1px solid rgba(var(--state-warning-rgb), 0.35);
            color: var(--state-warning-dark);
            font-size: 19px;
            font-weight: 800;
            line-height: 1.2;
            text-align: center;
            box-shadow: 0 3px 0 rgba(var(--state-warning-rgb), 0.45), 0 8px 12px -6px rgba(var(--state-warning-rgb), 0.6);
            transform: translateZ(16px);
        }

        .tile3d__list {
            list-style: none;
            margin: 0;
            padding: 0 10px 10px;
            flex: 1;
            transform: translateZ(14px);
        }

        .tile3d__list a {
            display: flex;
            flex-direction: column;
            gap: 2px;
            padding: 8px 9px;
            border-radius: var(--border-radius-md);
            color: var(--text-main);
            text-decoration: none;
            transition: background 0.2s ease, transform 0.2s ease;
        }

        .tile3d__list a:hover {
            background: var(--primary-soft);
            color: var(--text-main);
            text-decoration: none;
            transform: translateX(3px);
        }

        .tile3d__line {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 10px;
        }

        .tile3d__code {
            font-size: 13px;
            font-weight: 700;
            color: var(--primary-dark);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .tile3d__ago {
            flex: 0 0 auto;
            font-size: 11.5px;
            color: var(--text-muted);
            white-space: nowrap;
        }

        .tile3d__sub {
            font-size: 12px;
            color: var(--text-muted);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .tile3d__more {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 11px 18px;
            border-top: 1px solid rgba(var(--primary-rgb), 0.08);
            font-size: 12.5px;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
            transform: translateZ(10px);
            transition: color 0.2s ease;
        }

        .tile3d__more:hover {
            color: var(--primary-dark);
            text-decoration: none;
        }

        /* ================= TRẠNG THÁI RỖNG: THÙNG HÀNG 3D ================= */
        .board3d__empty {
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 34px;
            padding: 60px 20px;
            text-align: center;
            perspective: 800px;
        }

        .board3d__empty .box3d {
            --s: 96px;
            position: relative;
        }

        .board3d__empty .box3d__body > span {
            display: flex;
            align-items: center;
            justify-content: center;
            border-color: rgba(var(--state-success-rgb), 0.55);
            /* Thùng đặc: nền trắng lót dưới, ẩn mặt khuất để không nhìn xuyên */
            background:
                linear-gradient(160deg, rgba(var(--state-success-rgb), 0.08), rgba(var(--state-success-rgb), 0.24)),
                #fff;
            box-shadow: inset 0 0 26px rgba(var(--state-success-rgb), 0.18);
            backface-visibility: hidden;
            color: var(--state-success);
            font-size: 34px;
        }

        .board3d__empty-shadow {
            width: 130px;
            height: 18px;
            margin-top: -18px;
            border-radius: 50%;
            background: radial-gradient(closest-side, rgba(var(--primary-rgb), 0.28), transparent);
        }

        .board3d__empty-text {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-main);
        }

        /* ================= MÀN HÌNH NHỎ / GIẢM CHUYỂN ĐỘNG ================= */
        @media (max-width: 1199px) {
            .hero3d__scene {
                opacity: 0.45;
            }
        }

        @media (max-width: 991px) {
            .hero3d__kpis {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 767px) {
            .home-page {
                min-height: calc(100vh - 54px);
                padding: 12px;
                gap: 14px;
            }

            .hero3d {
                padding: 20px 16px 22px;
            }

            .hero3d__scene {
                display: none;
            }

            .hero3d .hero3d__title {
                font-size: 20px;
            }

            .hero3d__kpis {
                gap: 14px;
            }

            .kpi3d {
                padding: 12px;
                gap: 10px;
            }

            .kpi3d .gem3d {
                width: 36px;
                height: 36px;
                flex-basis: 36px;
                font-size: 15px;
            }

            .kpi3d__value {
                font-size: 22px;
            }

            .board3d__head,
            .board3d__body {
                padding-left: 14px;
                padding-right: 14px;
            }

            .board3d__grid {
                grid-template-columns: 1fr;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .hero3d::before,
            .box3d,
            .box3d__body,
            .tile3d {
                animation: none;
            }

            .tile3d__card,
            .kpi3d {
                transition: none;
            }
        }
    </style>

    @php
        // Ứng dụng để locale 'en' nên phải chỉ định 'vi' cho chữ "2 ngày trước"
        $reviewAgo = function ($value, bool $short = false) {
            if (!$value) {
                return '';
            }
            try {
                return \Carbon\Carbon::parse($value)->locale('vi')->diffForHumans(null, $short);
            } catch (\Throwable $e) {
                return '';
            }
        };
        $signTotal = collect($reviewSections)->firstWhere('key', 'sign')['total'] ?? 0;
        $deptTotal = collect($reviewSections)->firstWhere('key', 'dept')['total'] ?? 0;
        $today = \Illuminate\Support\Str::ucfirst(\Carbon\Carbon::now()->locale('vi')->isoFormat('dddd, DD/MM/YYYY'));
    @endphp

    <div class="content-wrapper">
        <div class="home-page">
            {{-- ============ BANNER: lời chào + KPI ============ --}}
            <section class="hero3d" id="homeHero">
                <div class="hero3d__scene" aria-hidden="true">
                    @foreach (['a', 'b', 'c'] as $box)
                        <div class="box3d box3d--{{ $box }}">
                            <div class="box3d__body">
                                <span></span><span></span><span></span><span></span><span></span><span></span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="hero3d__content">
                    <div>
                        <span class="hero3d__eyebrow"><i class="far fa-calendar-alt"></i> {{ $today }}</span>
                        <h1 class="hero3d__title">Xin chào, {{ session('user')['fullName'] ?? 'bạn' }}</h1>
                        <p class="hero3d__sub">
                            {{ implode(' · ', array_filter([session('user')['selected_department'] ?? null, $companyName])) }}
                        </p>
                    </div>

                    <div class="hero3d__kpis">
                        <a href="#reviewBoard" class="kpi3d">
                            <span class="gem3d gem3d--navy"><i class="fas fa-layer-group"></i></span>
                            <span>
                                <span class="kpi3d__value d-block">{{ $reviewTotal }}</span>
                                <span class="kpi3d__label d-block">Tổng đề nghị chờ</span>
                            </span>
                        </a>
                        <a href="{{ $signTotal ? '#review-sign' : '#reviewBoard' }}" class="kpi3d">
                            <span class="gem3d"><i class="fas fa-signature"></i></span>
                            <span>
                                <span class="kpi3d__value d-block">{{ $signTotal }}</span>
                                <span class="kpi3d__label d-block">Chờ bạn ký duyệt</span>
                            </span>
                        </a>
                        <a href="{{ $deptTotal ? '#review-dept' : '#reviewBoard' }}" class="kpi3d">
                            <span class="gem3d gem3d--accent"><i class="fas fa-people-carry"></i></span>
                            <span>
                                <span class="kpi3d__value d-block">{{ $deptTotal }}</span>
                                <span class="kpi3d__label d-block">Chờ phòng ban xử lý</span>
                            </span>
                        </a>
                        <a href="{{ $reviewOldest['url'] ?? '#reviewBoard' }}" class="kpi3d"
                            @if ($reviewOldest) title="{{ $reviewOldest['label'] }} {{ $reviewOldest['code'] }}" @endif>
                            <span class="gem3d gem3d--warning"><i class="fas fa-hourglass-half"></i></span>
                            <span>
                                <span class="kpi3d__value d-block">
                                    @if ($reviewOldest)
                                        <small>{{ $reviewAgo($reviewOldest['since'], true) }}</small>
                                    @else
                                        —
                                    @endif
                                </span>
                                <span class="kpi3d__label d-block">Đề nghị chờ lâu nhất</span>
                            </span>
                        </a>
                    </div>
                </div>
            </section>

            {{-- ============ BẢNG ĐỀ NGHỊ THEO LOẠI ============ --}}
            <section class="board3d" id="reviewBoard">
                <div class="board3d__head">
                    <span class="gem3d gem3d--sm"><i class="fas fa-file-signature"></i></span>
                    <h2>Đề Nghị Chờ Bạn Xem Xét</h2>
                </div>

                <div class="board3d__body">
                    @forelse ($reviewSections as $section)
                        <div class="board3d__section board3d__section--{{ $section['key'] }}" id="review-{{ $section['key'] }}">
                            <div class="board3d__section-title">
                                <span class="gem3d gem3d--sm {{ $section['key'] === 'dept' ? 'gem3d--accent' : '' }}">
                                    <i class="{{ $section['icon'] }}"></i>
                                </span>
                                {{ $section['label'] }}
                                <span class="board3d__section-count">{{ $section['total'] }}</span>
                            </div>

                            <div class="board3d__grid">
                                @foreach ($section['types'] as $type)
                                    <div class="tile3d" style="--i: {{ $loop->index }}">
                                        <div class="tile3d__card">
                                            <span class="tile3d__glare"></span>

                                            <a href="{{ $type['url'] }}" class="tile3d__head">
                                                <span class="gem3d {{ $section['key'] === 'dept' ? 'gem3d--accent' : '' }}">
                                                    <i class="{{ $type['icon'] }}"></i>
                                                </span>
                                                <span class="tile3d__label">{{ $type['label'] }}</span>
                                                <span class="tile3d__count">{{ $type['count'] }}</span>
                                            </a>

                                            <ul class="tile3d__list">
                                                @foreach ($type['items'] as $item)
                                                    <li>
                                                        <a href="{{ $item['url'] }}"
                                                            title="{{ trim($item['code'] . ' ' . $item['title']) }}">
                                                            <span class="tile3d__line">
                                                                <span class="tile3d__code">{{ $item['code'] }}</span>
                                                                <span class="tile3d__ago">{{ $reviewAgo($item['since']) }}</span>
                                                            </span>
                                                            <span class="tile3d__sub">
                                                                {{ implode(' · ', array_filter([$item['title'], $item['meta']])) }}
                                                            </span>
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>

                                            <a href="{{ $type['url'] }}" class="tile3d__more">
                                                {{ $type['count'] > count($type['items']) ? 'Xem tất cả ' . $type['count'] . ' đề nghị' : 'Mở màn hình xử lý' }}
                                                <i class="fas fa-arrow-right"></i>
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="board3d__empty">
                            <div class="box3d">
                                <div class="box3d__body">
                                    @for ($face = 0; $face < 6; $face++)
                                        <span><i class="fas fa-check"></i></span>
                                    @endfor
                                </div>
                            </div>
                            <div class="board3d__empty-shadow"></div>
                            <div class="board3d__empty-text">Không có đề nghị nào đang chờ bạn xem xét</div>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>

    <script>
        // Hiệu ứng 3D theo con trỏ: thẻ đề nghị nghiêng + vệt sáng, cụm thùng hàng trên banner xoay nhẹ.
        // Bỏ qua trên màn hình cảm ứng và khi người dùng bật giảm chuyển động.
        (function() {
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches ||
                window.matchMedia('(hover: none)').matches) {
                return;
            }

            var MAX_TILT = 8;

            document.querySelectorAll('.tile3d').forEach(function(tile) {
                var card = tile.querySelector('.tile3d__card');

                tile.addEventListener('mousemove', function(e) {
                    var rect = tile.getBoundingClientRect();
                    var x = (e.clientX - rect.left) / rect.width;
                    var y = (e.clientY - rect.top) / rect.height;

                    card.style.setProperty('--ry', ((x - 0.5) * MAX_TILT * 2).toFixed(2) + 'deg');
                    card.style.setProperty('--rx', ((0.5 - y) * MAX_TILT * 2).toFixed(2) + 'deg');
                    card.style.setProperty('--mx', (x * 100).toFixed(1) + '%');
                    card.style.setProperty('--my', (y * 100).toFixed(1) + '%');
                });

                tile.addEventListener('mouseleave', function() {
                    card.style.setProperty('--rx', '0deg');
                    card.style.setProperty('--ry', '0deg');
                });
            });

            var hero = document.getElementById('homeHero');
            var scene = hero ? hero.querySelector('.hero3d__scene') : null;

            if (hero && scene) {
                hero.addEventListener('mousemove', function(e) {
                    var rect = hero.getBoundingClientRect();
                    var x = (e.clientX - rect.left) / rect.width - 0.5;
                    var y = (e.clientY - rect.top) / rect.height - 0.5;

                    scene.style.setProperty('--scene-ry', (x * 24).toFixed(2) + 'deg');
                    scene.style.setProperty('--scene-rx', (-y * 18).toFixed(2) + 'deg');
                });

                hero.addEventListener('mouseleave', function() {
                    scene.style.setProperty('--scene-ry', '0deg');
                    scene.style.setProperty('--scene-rx', '0deg');
                });
            }
        })();
    </script>
@endsection
