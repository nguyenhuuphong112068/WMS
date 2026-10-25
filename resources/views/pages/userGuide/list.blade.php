@extends ('layout.master')

@section('mainContent')
    @include('pages.userGuide.shared.assets')

    <div class="content-wrapper">
        <div class="ug-page">

            <div class="ug-hero">
                <div class="ug-hero-icon"><i class="fas fa-book-open"></i></div>
                <div>
                    <h1>Hướng Dẫn Sử Dụng</h1>
                    <p>Tài liệu hướng dẫn thao tác theo từng nhóm chức năng trên menu.</p>
                </div>
            </div>

            <div class="ug-cards">
                @foreach ($guides as $key => $guide)
                    <a href="{{ route('pages.userGuide.show', $key) }}" class="ug-card">
                        <div class="ug-section-icon"><i class="{{ $guide['icon'] }}"></i></div>
                        <h2>{{ $guide['title'] }}</h2>
                        <p>{{ $guide['summary'] }}</p>
                        <div class="ug-card-foot">
                            <span><i class="far fa-calendar mr-1"></i>Cập nhật
                                {{ \Carbon\Carbon::parse($guide['updated_at'])->format('d/m/Y') }}</span>
                            <span class="text-primary font-weight-bold">Xem <i class="fas fa-arrow-right ml-1"></i></span>
                        </div>
                    </a>
                @endforeach
            </div>

        </div>
    </div>
@endsection
