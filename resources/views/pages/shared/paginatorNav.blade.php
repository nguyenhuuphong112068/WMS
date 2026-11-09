{{-- Nút Trang đầu / Trước / Sau / Trang cuối của pages.shared.paginator (chữ đứng phía ngoài biểu tượng) --}}
@php
    $pgIconHtml = '<i class="fas ' . $pgIcon . '" aria-hidden="true"></i>';
    $pgTextHtml = $pgText ? '<span class="md-pager-text">' . e($pgText) . '</span>' : '';
    $pgInner = in_array($pgKey, ['next', 'last'], true) ? $pgTextHtml . $pgIconHtml : $pgIconHtml . $pgTextHtml;
@endphp
@if ($pgOn)
    <a class="md-pager-btn md-pager-nav md-pager-{{ $pgKey }}" href="{{ $pgItems->url($pgTarget) }}"
        title="{{ $pgLabel }}" aria-label="{{ $pgLabel }}">{!! $pgInner !!}</a>
@else
    <span class="md-pager-btn md-pager-nav md-pager-{{ $pgKey }} is-disabled" title="{{ $pgLabel }}"
        aria-label="{{ $pgLabel }}" aria-disabled="true">{!! $pgInner !!}</span>
@endif
