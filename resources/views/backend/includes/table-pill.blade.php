@php
    $variant = $tone ?? 'neutral';
    if (! in_array($variant, ['neutral', 'success', 'admin', 'danger', 'warning', 'info'], true)) {
        $variant = 'neutral';
    }
@endphp

<span class="brc-table-pill brc-table-pill--{{ $variant }}"
    @if (! empty($title))
        title="{{ $title }}"
    @endif
>{{ $text }}</span>
