{{--
| LOGO CẢNH BÁO AN TOÀN HOÁ CHẤT - kiểu GHS (hình thoi viền đỏ, nền trắng, biểu tượng đen)
|
| Dùng ở: modal Thêm/Sửa Danh Mục Hoá Chất, bảng danh mục, modal lịch sử thay đổi.
| $code là một mã trong config('chemical.safety_warnings'). Mã lạ (chưa có @case riêng)
| vẫn vẽ được khung thoi kèm dấu chấm than mặc định, không làm vỡ giao diện.
|
| Cách dùng: @include('pages.shared.safetyPictogram', ['code' => 'TOXIC', 'size' => 26])
--}}
@php
    $size = $size ?? 26;
@endphp
<svg class="safety-picto" width="{{ $size }}" height="{{ $size }}" viewBox="0 0 100 100"
    xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
    <rect x="18" y="18" width="64" height="64" rx="8" fill="#fff" stroke="#DC2626" stroke-width="7"
        transform="rotate(45 50 50)" />
    @switch($code)
        @case('TOXIC')
            {{-- Đầu lâu xương chéo --}}
            <circle cx="50" cy="42" r="15" fill="#000" />
            <circle cx="44" cy="40" r="3.6" fill="#fff" />
            <circle cx="56" cy="40" r="3.6" fill="#fff" />
            <path d="M46 49 L50 54 L54 49 Z" fill="#fff" />
            <line x1="35" y1="65" x2="65" y2="77" stroke="#000" stroke-width="4" stroke-linecap="round" />
            <line x1="65" y1="65" x2="35" y2="77" stroke="#000" stroke-width="4" stroke-linecap="round" />
        @break

        @case('CORROSIVE')
            {{-- Hai ống nghiệm đổ chất lỏng ăn mòn xuống thanh ngang --}}
            <g stroke="#000" stroke-width="3.5" fill="none" stroke-linecap="round">
                <line x1="38" y1="24" x2="30" y2="42" />
                <line x1="30" y1="42" x2="41" y2="48" />
                <line x1="58" y1="24" x2="66" y2="42" />
                <line x1="66" y1="42" x2="55" y2="48" />
            </g>
            <line x1="24" y1="56" x2="76" y2="56" stroke="#000" stroke-width="4" stroke-linecap="round" />
            <path d="M30 56 Q34 65 28 74" stroke="#000" stroke-width="3.5" fill="none" stroke-linecap="round" />
            <path d="M56 56 Q60 67 52 78" stroke="#000" stroke-width="3.5" fill="none" stroke-linecap="round" />
        @break

        @case('FLAMMABLE')
            {{-- Ngọn lửa --}}
            <path
                d="M50 20 C41 33 32 43 32 56 C32 70 40 80 50 80 C60 80 68 70 68 56 C68 47 62 43 59 37 C58 45 53 49 49 45 C45 41 47 33 50 20 Z"
                fill="#000" />
        @break

        @case('OXIDIZING')
            {{-- Ngọn lửa trên vòng tròn --}}
            <circle cx="50" cy="63" r="10" fill="#000" />
            <path
                d="M50 22 C43 33 37 41 37 50 C37 60 43 66 50 66 C57 66 63 60 63 50 C63 44 58 41 56 36 C56 42 52 45 49 42 C46 39 47 33 50 22 Z"
                fill="#000" />
        @break

        @case('IRRITANT')
            {{-- Dấu chấm than --}}
            <rect x="46" y="24" width="8" height="32" rx="3" fill="#000" />
            <circle cx="50" cy="67" r="5.5" fill="#000" />
        @break

        @case('ENV_HAZARD')
            {{-- Cây khô + cá --}}
            <g stroke="#000" stroke-width="3" stroke-linecap="round">
                <line x1="35" y1="72" x2="35" y2="38" />
                <line x1="35" y1="44" x2="26" y2="35" />
                <line x1="35" y1="51" x2="45" y2="42" />
                <line x1="35" y1="58" x2="28" y2="51" />
            </g>
            <path d="M48 66 Q61 55 74 66 Q61 77 48 66 Z" fill="#000" />
            <polygon points="47,66 38,59 38,73" fill="#000" />
            <circle cx="67" cy="64" r="1.8" fill="#fff" />
        @break

        @case('COMPRESSED_GAS')
            {{-- Bình khí nén --}}
            <g fill="none" stroke="#000" stroke-width="4" stroke-linejoin="round">
                <rect x="38" y="34" width="24" height="42" rx="5" />
                <rect x="43" y="24" width="14" height="10" rx="2" />
            </g>
            <line x1="46" y1="24" x2="46" y2="18" stroke="#000" stroke-width="3" stroke-linecap="round" />
            <line x1="54" y1="24" x2="54" y2="18" stroke="#000" stroke-width="3" stroke-linecap="round" />
        @break

        @case('EXPLOSIVE')
            {{-- Nổ toé --}}
            <polygon fill="#000"
                points="50,19 58,38 73,27 62,46 83,50 62,54 73,73 58,62 50,81 42,62 27,73 38,54 17,50 38,46 27,27 42,38" />
        @break

        @default
            <rect x="46" y="30" width="8" height="24" rx="3" fill="#000" />
            <circle cx="50" cy="62" r="5" fill="#000" />
    @endswitch
</svg>
