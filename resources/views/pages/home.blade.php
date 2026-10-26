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
                                    <a href="{{ route('pages.inventory.standardStability.detail', ['id' => $item['list_id']]) }}"
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
                                href="{{ route('pages.inventory.standardInventory.list', ['tab' => 'stabilityPlan']) }}" style="display: none">
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

    {{-- ============ TRANG CHỦ — ĐỀ NGHỊ CHỜ BẠN XEM XÉT: ma trận tổng quan + card màu theo đối tượng ============ --}}
    <style>
        .content-wrapper {
            background: var(--primary-soft);
        }

        .home-page {
            min-height: calc(100vh - 57px);
            display: flex;
            flex-direction: column;
            gap: 20px;
            padding: 22px;
            background-color: var(--bg-neutral);
            background-image:
                linear-gradient(rgba(var(--primary-rgb), 0.045) 1px, transparent 1px),
                linear-gradient(90deg, rgba(var(--primary-rgb), 0.045) 1px, transparent 1px);
            background-size: 56px 56px, 56px 56px;
        }

        /* Màu theo đối tượng: --c / --c-rgb dùng cho chip, viền card, icon, ô ma trận */
        .obj-vt { --c: var(--db-group-material); --c-rgb: var(--db-group-material-rgb); }
        .obj-hc { --c: var(--db-group-chemical); --c-rgb: var(--db-group-chemical-rgb); }
        .obj-cc { --c: var(--db-group-standard); --c-rgb: var(--db-group-standard-rgb); }
        .obj-dc { --c: var(--db-group-master); --c-rgb: var(--db-group-master-rgb); }

        /* ================= BANNER 3D ================= */
        .hero3d {
            position: relative;
            overflow: hidden;
            border-radius: 16px;
            padding: 24px 30px 26px;
            color: #fff;
            background:
                radial-gradient(760px 280px at 88% -30%, rgba(255, 255, 255, 0.26), transparent 62%),
                linear-gradient(135deg, var(--primary-light) 0%, var(--primary) 42%, var(--primary-dark) 100%);
            box-shadow: 0 22px 44px -22px rgba(var(--primary-rgb), 0.85), inset 0 1px 0 rgba(255, 255, 255, 0.25);
            perspective: 900px;
            isolation: isolate;
        }

        .hero3d::before {
            content: '';
            position: absolute;
            left: -25%;
            right: -25%;
            top: 40%;
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

        .hero3d__scene {
            position: absolute;
            right: 3%;
            top: 0;
            bottom: 0;
            width: 340px;
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

        .box3d--a { --s: 76px; right: 70px; top: 26px; }
        .box3d--b { --s: 46px; right: 215px; top: 18px; animation-delay: -2.5s; }
        .box3d--c { --s: 34px; right: 12px; top: 104px; animation-delay: -4s; }
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

        .hero3d__stats {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 16px;
        }

        .hero3d__stat {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 14px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.95);
            color: var(--text-main);
            font-size: 13px;
            font-weight: 600;
            box-shadow: 0 4px 0 var(--primary-lighter), 0 12px 18px -10px rgba(0, 0, 0, 0.4);
        }

        .hero3d__stat b {
            font-size: 18px;
            font-weight: 800;
            color: var(--primary-dark);
            font-variant-numeric: tabular-nums;
        }

        .hero3d__stat i {
            color: var(--primary);
        }

        /* ================= KHỐI ICON NỔI ================= */
        .gem3d {
            --gem-from: var(--primary-light);
            --gem-to: var(--primary-dark);
            width: 40px;
            height: 40px;
            flex: 0 0 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 11px;
            background: linear-gradient(145deg, var(--gem-from) 0%, var(--gem-to) 100%);
            color: #fff;
            font-size: 16px;
            box-shadow:
                inset 0 2px 0 rgba(255, 255, 255, 0.35),
                inset 0 -4px 0 rgba(0, 0, 0, 0.18),
                0 8px 14px -6px rgba(var(--primary-rgb), 0.55);
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.25);
        }

        .gem3d--sm {
            width: 28px;
            height: 28px;
            flex-basis: 28px;
            border-radius: 8px;
            font-size: 12px;
        }

        /* Icon card mang màu đối tượng */
        .gem3d.is-obj {
            background: linear-gradient(145deg, rgba(var(--c-rgb), 0.72) 0%, var(--c) 100%);
            box-shadow:
                inset 0 2px 0 rgba(255, 255, 255, 0.35),
                inset 0 -4px 0 rgba(0, 0, 0, 0.16),
                0 8px 14px -6px rgba(var(--c-rgb), 0.7);
        }

        /* ================= CHIP ================= */
        .obj-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 3px 10px 3px 4px;
            border-radius: 999px;
            border: 1px solid rgba(var(--c-rgb), 0.35);
            background: rgba(var(--c-rgb), 0.10);
            color: var(--c);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            white-space: nowrap;
            line-height: 1.4;
        }

        .obj-chip__dot {
            width: 18px;
            height: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: var(--c);
            color: #fff;
            font-size: 9px;
        }

        .actor-tag {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 2px 9px;
            border-radius: 6px;
            font-size: 11.5px;
            font-weight: 700;
            white-space: nowrap;
            color: var(--primary-dark);
            background: var(--primary-soft);
        }

        .actor-tag.is-dept {
            color: var(--accent);
            background: rgba(23, 184, 212, 0.10);
        }

        .dir-chip {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            padding: 0 7px;
            border-radius: 5px;
            border: 1px dashed currentColor;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        .dir-chip.is-in {
            color: var(--state-warning-dark);
        }

        .dir-chip.is-out {
            color: var(--primary);
        }

        /* ================= KHUNG CHUNG ================= */
        .panel3d {
            background: #fff;
            border: 1px solid rgba(var(--primary-rgb), 0.10);
            border-radius: 16px;
            box-shadow: 0 1px 2px rgba(var(--primary-rgb), 0.06), 0 18px 40px -26px rgba(var(--primary-rgb), 0.55);
        }

        .panel3d__head {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px 14px;
            padding: 16px 24px;
            border-bottom: 1px solid rgba(var(--primary-rgb), 0.08);
        }

        .panel3d__head h2 {
            margin: 0;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--primary);
        }

        /* ================= MA TRẬN ================= */
        .matrix-wrap {
            overflow-x: auto;
            padding: 6px 24px 18px;
        }

        .matrix3d {
            width: 100%;
            min-width: 640px;
            border-collapse: separate;
            border-spacing: 0;
        }

        .matrix3d th,
        .matrix3d td {
            padding: 9px 12px;
            text-align: center;
            border-bottom: 1px solid rgba(var(--primary-rgb), 0.08);
        }

        .matrix3d thead th {
            padding-top: 12px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: var(--text-muted);
        }

        .matrix3d thead th:first-child,
        .matrix3d tbody th,
        .matrix3d tfoot th {
            text-align: left;
        }

        .matrix3d tbody th {
            font-size: 13.5px;
            font-weight: 700;
            color: var(--text-main);
            white-space: nowrap;
        }

        .matrix3d tbody th i {
            width: 18px;
            margin-right: 6px;
            color: var(--primary-light);
            text-align: center;
        }

        .mx-cell {
            font-size: 18px;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
            color: var(--c, var(--primary-dark));
            border-radius: 10px;
            transition: background 0.2s ease, box-shadow 0.2s ease;
        }

        .mx-cell[data-clickable="1"] {
            cursor: pointer;
        }

        .mx-cell[data-clickable="1"]:hover {
            background: rgba(var(--c-rgb, var(--primary-rgb)), 0.08);
        }

        .mx-cell.is-zero {
            font-size: 14px;
            font-weight: 400;
            color: rgba(var(--primary-rgb), 0.25);
        }

        .mx-split {
            display: inline-flex;
            gap: 10px;
            font-size: 15px;
        }

        .mx-split .is-in {
            color: var(--state-warning-dark);
        }

        .mx-split .is-out {
            color: var(--primary);
        }

        .mx-total {
            color: var(--text-main);
        }

        .matrix3d tfoot th,
        .matrix3d tfoot td {
            border-bottom: 0;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
        }

        .matrix3d tfoot td {
            color: var(--c, var(--text-main));
        }

        .matrix3d tfoot td.is-zero {
            font-weight: 400;
            color: rgba(var(--primary-rgb), 0.25);
        }

        /* ================= NHÓM NGHIỆP VỤ + CARD ================= */
        .board3d {
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            scroll-margin-top: 70px;
        }

        .board3d__body {
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            padding: 4px 24px 26px;
        }

        .group3d {
            padding-top: 18px;
        }

        .group3d__title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 14px;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            color: var(--primary-dark);
        }

        .group3d__title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, rgba(var(--primary-rgb), 0.25), transparent);
        }

        .group3d__count {
            padding: 2px 10px;
            border-radius: 999px;
            background: var(--primary-soft);
            font-size: 12px;
            font-variant-numeric: tabular-nums;
        }

        .group3d__grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .tile3d {
            perspective: 1000px;
            animation: tile-in 0.6s cubic-bezier(0.2, 0.7, 0.2, 1) both;
            animation-delay: calc(var(--i, 0) * 60ms);
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
            border-left: 5px solid var(--c);
            border-radius: 14px;
            box-shadow: 0 1px 2px rgba(var(--primary-rgb), 0.08), 0 14px 26px -16px rgba(var(--c-rgb), 0.6);
            transform-style: preserve-3d;
            transform: rotateX(var(--rx, 0deg)) rotateY(var(--ry, 0deg));
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .tile3d:hover .tile3d__card {
            box-shadow: 0 2px 4px rgba(var(--primary-rgb), 0.08), 0 30px 44px -20px rgba(var(--c-rgb), 0.65);
        }

        /* Card được chỉ tới từ ô ma trận */
        .tile3d.is-flash .tile3d__card {
            animation: tile-flash 1.6s ease;
        }

        @keyframes tile-flash {
            0%, 100% { box-shadow: 0 1px 2px rgba(var(--primary-rgb), 0.08), 0 14px 26px -16px rgba(var(--c-rgb), 0.6); }
            25%, 60% { box-shadow: 0 0 0 3px rgba(var(--c-rgb), 0.55), 0 18px 30px -14px rgba(var(--c-rgb), 0.7); }
        }

        .tile3d__glare {
            position: absolute;
            inset: 0;
            border-radius: inherit;
            background: radial-gradient(circle at var(--mx, 50%) var(--my, 0%), rgba(var(--c-rgb), 0.10), transparent 58%);
            opacity: 0;
            transition: opacity 0.25s ease;
            pointer-events: none;
        }

        .tile3d:hover .tile3d__glare {
            opacity: 1;
        }

        .tile3d__top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 12px 16px 0;
            transform: translateZ(20px);
        }

        .tile3d__head {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 16px 8px;
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
            padding: 3px 11px;
            border-radius: 11px;
            background: linear-gradient(180deg, #fff 0%, rgba(var(--state-warning-rgb), 0.18) 100%);
            border: 1px solid rgba(var(--state-warning-rgb), 0.35);
            color: var(--state-warning-dark);
            font-size: 19px;
            font-weight: 800;
            line-height: 1.2;
            text-align: center;
            font-variant-numeric: tabular-nums;
            box-shadow: 0 3px 0 rgba(var(--state-warning-rgb), 0.45), 0 8px 12px -6px rgba(var(--state-warning-rgb), 0.6);
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
            background: rgba(var(--c-rgb), 0.07);
            color: var(--text-main);
            text-decoration: none;
            transform: translateX(3px);
        }

        .tile3d__line {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
        }

        .tile3d__code {
            min-width: 0;
            font-size: 13px;
            font-weight: 700;
            color: var(--primary-dark);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .tile3d__ago {
            margin-left: auto;
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
            padding: 10px 16px;
            border-top: 1px solid rgba(var(--primary-rgb), 0.08);
            font-size: 12.5px;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
            transform: translateZ(10px);
        }

        .tile3d__more:hover {
            color: var(--primary-dark);
            text-decoration: none;
        }

        /* ================= TRẠNG THÁI RỖNG ================= */
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

        @media (max-width: 767px) {
            .home-page {
                min-height: calc(100vh - 54px);
                padding: 12px;
                gap: 14px;
            }

            .hero3d {
                padding: 18px 16px 20px;
            }

            .hero3d__scene {
                display: none;
            }

            .hero3d .hero3d__title {
                font-size: 20px;
            }

            .panel3d__head,
            .board3d__body,
            .matrix-wrap {
                padding-left: 14px;
                padding-right: 14px;
            }

            .group3d__grid {
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

            .tile3d__card {
                transition: none;
            }
        }
    </style>

    @php
        // Ứng dụng để locale 'en' nên phải chỉ định 'vi' cho chữ "2 ngày trước"
        $reviewAgo = function ($value) {
            if (!$value) {
                return '';
            }
            try {
                return \Carbon\Carbon::parse($value)->locale('vi')->diffForHumans();
            } catch (\Throwable $e) {
                return '';
            }
        };
        // Tổng một ô ma trận khi chưa lọc (cộng cả hai phía ai phải làm)
        $cellAll = function ($group, $object) use ($reviewMatrix) {
            $sum = ['count' => 0, 'in' => 0, 'out' => 0];
            foreach ($reviewMatrix[$group][$object] as $part) {
                foreach ($sum as $k => $v) {
                    $sum[$k] += $part[$k];
                }
            }
            return $sum;
        };
        $signTotal = 0;
        $deptTotal = 0;
        foreach ($reviewMatrix as $byObject) {
            foreach ($byObject as $byActor) {
                $signTotal += $byActor['sign']['count'];
                $deptTotal += $byActor['dept']['count'];
            }
        }
        $objChip = fn($key) => '<span class="obj-chip obj-' . $key . '"><span class="obj-chip__dot"><i class="' . e($reviewObjects[$key]['icon']) . '"></i></span>' . e($reviewObjects[$key]['label']) . '</span>';
        $today = \Illuminate\Support\Str::ucfirst(\Carbon\Carbon::now()->locale('vi')->isoFormat('dddd, DD/MM/YYYY'));
    @endphp

    <div class="content-wrapper">
        <div class="home-page">
            {{-- ============ BANNER ============ --}}
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

                <span class="hero3d__eyebrow"><i class="far fa-calendar-alt"></i> {{ $today }}</span>
                <h1 class="hero3d__title">Xin chào, {{ session('user')['fullName'] ?? 'bạn' }}</h1>
                <p class="hero3d__sub">
                    {{ implode(' · ', array_filter([session('user')['selected_department'] ?? null, $companyName])) }}
                </p>
                <div class="hero3d__stats">
                    <span class="hero3d__stat"><i class="fas fa-layer-group"></i><b>{{ $reviewTotal }}</b> đề nghị chờ</span>
                    <span class="hero3d__stat"><i class="{{ $reviewActors['sign']['icon'] }}"></i><b>{{ $signTotal }}</b> {{ mb_strtolower($reviewActors['sign']['label']) }}</span>
                    <span class="hero3d__stat"><i class="{{ $reviewActors['dept']['icon'] }}"></i><b>{{ $deptTotal }}</b> {{ mb_strtolower($reviewActors['dept']['label']) }}</span>
                    @if ($reviewOldest)
                        <a href="{{ $reviewOldest['url'] }}" class="hero3d__stat" title="{{ $reviewOldest['label'] }} {{ $reviewOldest['code'] }}">
                            <i class="fas fa-hourglass-half"></i>Chờ lâu nhất <b>{{ \Carbon\Carbon::parse($reviewOldest['since'])->locale('vi')->diffForHumans(null, true) }}</b>
                        </a>
                    @endif
                </div>
            </section>

            @if ($reviewTotal > 0)
                {{-- ============ MA TRẬN TỔNG QUAN: hàng = nghiệp vụ, cột = đối tượng ============ --}}
                <section class="panel3d" id="reviewMatrix">
                    <div class="panel3d__head">
                        <span class="gem3d gem3d--sm"><i class="fas fa-th"></i></span>
                        <h2>Ma trận tổng quan</h2>
                    </div>

                    <div class="matrix-wrap">
                        <table class="matrix3d">
                            <thead>
                                <tr>
                                    <th scope="col">Nghiệp vụ</th>
                                    @foreach ($reviewObjects as $objectKey => $object)
                                        <th scope="col" class="obj-{{ $objectKey }}" data-col="{{ $objectKey }}">{!! $objChip($objectKey) !!}</th>
                                    @endforeach
                                    <th scope="col">Tổng</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($reviewGroupInfo as $groupKey => $group)
                                    @php $rowTotal = 0; @endphp
                                    <tr>
                                        <th scope="row"><i class="{{ $group['icon'] }}"></i>{{ $group['label'] }}</th>
                                        @foreach ($reviewObjects as $objectKey => $object)
                                            @php
                                                $cell = $cellAll($groupKey, $objectKey);
                                                $rowTotal += $cell['count'];
                                            @endphp
                                            <td class="mx-cell obj-{{ $objectKey }} {{ $cell['count'] ? '' : 'is-zero' }}"
                                                data-group="{{ $groupKey }}" data-object="{{ $objectKey }}"
                                                data-clickable="{{ $cell['count'] ? 1 : 0 }}"
                                                @if ($cell['count']) tabindex="0" role="button" @endif>
                                                @if (!$cell['count'])
                                                    –
                                                @elseif ($group['direction'])
                                                    <span class="mx-split">
                                                        <span class="is-in" title="Phòng khác đề nghị phòng bạn">←{{ $cell['in'] }}</span>
                                                        <span class="is-out" title="Phòng bạn đề nghị phòng khác">→{{ $cell['out'] }}</span>
                                                    </span>
                                                @else
                                                    {{ $cell['count'] }}
                                                @endif
                                            </td>
                                        @endforeach
                                        <td class="mx-cell mx-total {{ $rowTotal ? '' : 'is-zero' }}" data-group="{{ $groupKey }}" data-object="all"
                                            data-clickable="{{ $rowTotal ? 1 : 0 }}" @if ($rowTotal) tabindex="0" role="button" @endif>
                                            {{ $rowTotal ?: '–' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th scope="row">Tổng</th>
                                    @foreach ($reviewObjects as $objectKey => $object)
                                        @php $colTotal = collect(array_keys($reviewGroupInfo))->sum(fn($g) => $cellAll($g, $objectKey)['count']); @endphp
                                        <td class="obj-{{ $objectKey }} {{ $colTotal ? '' : 'is-zero' }}" data-col-total="{{ $objectKey }}">{{ $colTotal ?: '–' }}</td>
                                    @endforeach
                                    <td data-col-total="all">{{ $reviewTotal }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </section>
            @endif

            {{-- ============ CARD ĐỀ NGHỊ THEO NHÓM NGHIỆP VỤ ============ --}}
            <section class="panel3d board3d" id="reviewBoard">
                <div class="panel3d__head">
                    <span class="gem3d gem3d--sm"><i class="fas fa-file-signature"></i></span>
                    <h2>Đề Nghị Chờ Bạn Xem Xét</h2>
                </div>

                <div class="board3d__body">
                    @forelse ($reviewGroups as $group)
                        <div class="group3d" data-group="{{ $group['key'] }}">
                            <div class="group3d__title">
                                <span class="gem3d gem3d--sm"><i class="{{ $group['icon'] }}"></i></span>
                                {{ $group['label'] }}
                                <span class="group3d__count" data-group-count>{{ $group['total'] }}</span>
                            </div>

                            <div class="group3d__grid">
                                @foreach ($group['cards'] as $card)
                                    <div class="tile3d obj-{{ $card['object'] }}" style="--i: {{ $loop->index }}"
                                        data-group="{{ $card['group'] }}" data-object="{{ $card['object'] }}"
                                        data-actor="{{ $card['actor'] }}" data-count="{{ $card['count'] }}">
                                        <div class="tile3d__card">
                                            <span class="tile3d__glare"></span>

                                            <div class="tile3d__top">
                                                {!! $objChip($card['object']) !!}
                                                <span class="actor-tag {{ $card['actor'] === 'dept' ? 'is-dept' : '' }}">
                                                    <i class="{{ $reviewActors[$card['actor']]['icon'] }}"></i>
                                                    {{ $reviewActors[$card['actor']]['short'] }}
                                                </span>
                                            </div>

                                            <a href="{{ $card['url'] }}" class="tile3d__head">
                                                <span class="gem3d is-obj"><i class="{{ $reviewObjects[$card['object']]['icon'] }}"></i></span>
                                                <span class="tile3d__label">{{ $card['label'] }}</span>
                                                <span class="tile3d__count">{{ $card['count'] }}</span>
                                            </a>

                                            <ul class="tile3d__list">
                                                @foreach ($card['items'] as $item)
                                                    <li>
                                                        <a href="{{ $item['url'] }}" title="{{ trim($item['code'] . ' ' . $item['title']) }}">
                                                            <span class="tile3d__line">
                                                                <span class="tile3d__code">{{ $item['code'] }}</span>
                                                                @if ($item['direction'] === 'in')
                                                                    <span class="dir-chip is-in" title="Phòng {{ $item['partner'] }} đề nghị phòng bạn">← Từ {{ $item['partner'] ?: 'phòng khác' }}</span>
                                                                @elseif ($item['direction'] === 'out')
                                                                    <span class="dir-chip is-out" title="Phòng bạn đề nghị phòng {{ $item['partner'] }}">→ Đến {{ $item['partner'] ?: 'phòng khác' }}</span>
                                                                @endif
                                                                <span class="tile3d__ago">{{ $reviewAgo($item['since']) }}</span>
                                                            </span>
                                                            <span class="tile3d__sub">
                                                                {{ implode(' · ', array_filter([$item['title'], $item['meta']])) }}
                                                            </span>
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>

                                            <a href="{{ $card['url'] }}" class="tile3d__more">
                                                {{ $card['count'] > count($card['items']) ? 'Xem tất cả ' . $card['count'] . ' đề nghị' : 'Mở màn hình xử lý' }}
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
        (function() {
            /* ---------- Bấm ô ma trận: cuộn tới nhóm card tương ứng và làm nổi các card đó ---------- */
            var tiles = Array.prototype.slice.call(document.querySelectorAll('.tile3d[data-group]'));

            document.querySelectorAll('.mx-cell[data-clickable="1"]').forEach(function(td) {
                function go() {
                    var group = document.querySelector('.group3d[data-group="' + td.dataset.group + '"]');
                    if (!group) return;
                    group.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    tiles.forEach(function(tile) {
                        var hit = tile.dataset.group === td.dataset.group &&
                            (td.dataset.object === 'all' || tile.dataset.object === td.dataset.object);
                        tile.classList.remove('is-flash');
                        if (hit) {
                            void tile.offsetWidth;
                            tile.classList.add('is-flash');
                        }
                    });
                }
                td.addEventListener('click', go);
                td.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); go(); }
                });
            });

            /* ---------- Hiệu ứng 3D theo con trỏ (bỏ qua trên cảm ứng / giảm chuyển động) ---------- */
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches ||
                window.matchMedia('(hover: none)').matches) {
                return;
            }

            var MAX_TILT = 8;

            tiles.forEach(function(tile) {
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
                    scene.style.setProperty('--scene-ry', (((e.clientX - rect.left) / rect.width - 0.5) * 24).toFixed(2) + 'deg');
                    scene.style.setProperty('--scene-rx', (-((e.clientY - rect.top) / rect.height - 0.5) * 18).toFixed(2) + 'deg');
                });
                hero.addEventListener('mouseleave', function() {
                    scene.style.setProperty('--scene-ry', '0deg');
                    scene.style.setProperty('--scene-rx', '0deg');
                });
            }
        })();
    </script>
@endsection
