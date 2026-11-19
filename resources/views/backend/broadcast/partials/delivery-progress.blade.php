@php
    $sent = (int) $sent;
    $total = (int) $total;
    $failed = (int) $failed;
    $skipped = (int) $skipped;
    $percent = $total > 0 ? min(100, (int) round(($sent / $total) * 100)) : 0;
@endphp
<div class="broadcast-progress" role="group" aria-label="Email delivery progress">
    <div class="broadcast-progress__label">
        <span>{{ $sent }} / {{ $total }} sent</span>
        <span>{{ $percent }}%</span>
    </div>
    <div class="broadcast-progress__track" role="progressbar"
         aria-valuemin="0" aria-valuemax="{{ $total }}" aria-valuenow="{{ $sent }}"
         aria-label="Emails sent">
        <div class="broadcast-progress__fill" style="width: {{ $percent }}%"></div>
    </div>
    @if ($failed > 0 || $skipped > 0)
        <div class="broadcast-progress__note">
            @if ($failed > 0)
                <span>{{ $failed }} failed</span>
            @endif
            @if ($skipped > 0)
                <span>{{ $skipped }} skipped</span>
            @endif
        </div>
    @endif
</div>
