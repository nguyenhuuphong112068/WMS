{{-- Nút in nhãn ở cột Thao Tác tab Sổ Cấp Phát: nhãn cấp phát / cách ly chờ quyết định / loại bỏ chờ huỷ.
     Cần $row, $expRoute, $meCanLabel, $lblTitle. --}}
@if ($meCanLabel)
    <a class="btn btn-sm btn-outline-secondary" target="_blank" title="{{ $lblTitle }}"
        href="{{ route($expRoute . 'label', ['id' => $row->id]) }}">
        <i class="fas fa-qrcode"></i>
    </a>
@else
    <button type="button" class="btn btn-sm btn-outline-secondary" disabled
        title="Cần quyền In Nhãn Cấp Phát Vật Tư">
        <i class="fas fa-qrcode"></i>
    </button>
@endif
