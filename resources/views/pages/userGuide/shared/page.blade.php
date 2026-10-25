{{--
| Khung chung của một trang hướng dẫn: tiêu đề, mục lục bên trái, các mục bên phải.
| index.blade.php của tài liệu chỉ @include file này; mục lục khai ở <khoá>/toc.php.
|
| Biến vào:
| - $guide, $guideKey : từ UserGuideController::show()
| - $ugGroups         : ['Tên nhóm' => [mục, ...]] từ toc.php (App\Support\UserGuideLinks::groups), mỗi mục:
|       id, file, title, icon            bắt buộc
|       path, route, params, perm, chips, keywords   tuỳ chọn
|       pane, helpRoutes                 tuỳ chọn, chỉ dùng cho nút "?" trên topNAV (xem UserGuideLinks)
| - $ugPathPrefix     : (tuỳ chọn) khi mục không khai path thì path = tiền tố + title,
|                       ví dụ 'Dữ Liệu Gốc › '
| Nội dung mục: resources/views/pages/userGuide/<guideKey>/sections/<file>.blade.php
--}}
@include('pages.userGuide.shared.assets')

<div class="content-wrapper">
    <div class="ug-page">

        <div class="ug-hero">
            <div class="ug-hero-icon"><i class="{{ $guide['icon'] }}"></i></div>
            <div>
                <h1>Hướng Dẫn Sử Dụng - {{ $guide['title'] }}</h1>
                <p>{{ $guide['summary'] }}</p>
            </div>
            <div class="ug-hero-meta">
                <div><i class="far fa-calendar mr-1"></i>Cập nhật
                    {{ \Carbon\Carbon::parse($guide['updated_at'])->format('d/m/Y') }}</div>
                <button type="button" class="btn btn-sm" onclick="window.print()">
                    <i class="fas fa-print mr-1"></i> In hướng dẫn
                </button>
            </div>
        </div>

        <div class="ug-layout">
            <aside class="ug-toc">
                <input type="search" class="form-control form-control-sm ug-toc-search"
                    placeholder="Tìm màn hình, thao tác...">
                @foreach ($ugGroups as $groupName => $items)
                    <div class="ug-toc-group">
                        <div class="ug-toc-group-title">{{ $groupName }}</div>
                        @foreach ($items as $item)
                            <a href="#{{ $item['id'] }}" data-keywords="{{ $item['keywords'] ?? '' }}">
                                <i class="{{ $item['icon'] }}"></i> {{ $item['title'] }}
                            </a>
                        @endforeach
                    </div>
                @endforeach
                <div class="ug-toc-empty" hidden>Không tìm thấy mục phù hợp.</div>
            </aside>

            <div class="ug-content">
                @foreach ($ugGroups as $groupName => $items)
                    @foreach ($items as $item)
                        <section class="ug-section" id="{{ $item['id'] }}">
                            @include('pages.userGuide.shared.sectionHead', [
                                'icon' => $item['icon'],
                                'title' => $item['title'],
                                'path' => $item['path'] ?? (isset($item['route'], $ugPathPrefix) ? $ugPathPrefix . $item['title'] : null),
                                'route' => $item['route'] ?? null,
                                'params' => $item['params'] ?? [],
                                'perm' => $item['perm'] ?? null,
                                'chips' => $item['chips'] ?? [],
                            ])
                            @include('pages.userGuide.' . $guideKey . '.sections.' . $item['file'])
                        </section>
                    @endforeach
                @endforeach
            </div>
        </div>

        <button type="button" class="ug-top" title="Lên đầu trang"><i class="fas fa-arrow-up"></i></button>
    </div>
</div>
