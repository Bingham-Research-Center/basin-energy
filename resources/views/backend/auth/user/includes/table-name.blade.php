@php
    $parts = preg_split('/\s+/u', trim((string) $user->name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $initials .= mb_strtoupper(mb_substr($part, 0, 1));
    }
    $initials = $initials ?: 'U';
@endphp

<div class="brc-table-person">
    <span class="brc-table-avatar" aria-hidden="true">{{ $initials }}</span>
    <span class="brc-table-person__details">
        <span class="brc-table-person__name">{{ $user->name }}</span>
        @if ($user->timezone)
            <span class="brc-table-person__meta">{{ $user->timezone }}</span>
        @endif
    </span>
</div>
