@extends('log-viewer::bootstrap-4._master')

@section('content')
<div class="brc-log-viewer">
    <div class="card shadow-sm mb-4">
        <div class="card-body">
    <div class="page-header mb-4">
        <h1>@lang('Dashboard')</h1>
    </div>

    <div class="row">
        <div class="col-md-6 col-lg-3">
            @php
                $steps = [];
                $start = 0;
                // "all" is the sum of every level, not an independent segment.
                foreach ($percents as $level => $item) {
                    if ($level === 'all') {
                        continue;
                    }
                    $percent = max(0, min(100, (float) $item['percent']));
                    if ($percent <= 0) {
                        continue;
                    }
                    $end = min(100, $start + $percent);
                    $color = log_styler()->color($level);
                    $steps[] = $color.' '.$start.'% '.$end.'%';
                    $start = $end;
                }
                $ringStyle = count($steps)
                    ? 'conic-gradient('.implode(', ', $steps).')'
                    : '#d1d1d1';
            @endphp
            <div class="brc-log-ring" style="background: {{ $ringStyle }}" role="img"
                aria-label="{{ __('Log entries by severity') }}"></div>
        </div>

        <div class="col-md-6 col-lg-9">
            <div class="row">
                @foreach($percents as $level => $item)
                    <div class="col-sm-6 col-md-12 col-lg-4 mb-3">
                        <div class="box level-{{ $level }} {{ $item['count'] === 0 ? 'empty' : '' }}">
                            <div class="box-icon">
                                {!! log_styler()->icon($level) !!}
                            </div>

                            <div class="box-content">
                                <span class="box-text">{{ $item['name'] }}</span>
                                <span class="box-number">
                                    {{ $item['count'] }} @lang('entries') - {!! $item['percent'] !!} %
                                </span>
                                <div class="progress" style="height: 3px;">
                                    <div class="progress-bar" style="width: {{ $item['percent'] }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
