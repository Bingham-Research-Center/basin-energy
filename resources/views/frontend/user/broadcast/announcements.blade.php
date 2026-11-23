@extends('frontend.layouts.app')

@section('title', __('My Announcements'))

@section('content')
<div class="card">
    <div class="card-header"><strong>Announcements for your account</strong></div>
    <div class="card-body">
        @forelse ($deliveries as $delivery)
            @if ($delivery->announcement)
                <article class="border-bottom py-3">
                    <div class="small text-muted">{{ $delivery->announcement->published_at->format('M j, Y') }}</div>
                    <h4>{{ $delivery->announcement->title }}</h4>
                    <div class="broadcast-rich-content">{!! app(\App\Services\BroadcastRichText::class)->render($delivery->announcement->body, $delivery->announcement->body_format) !!}</div>
                </article>
            @endif
        @empty
            <p class="text-muted">You have no announcements yet.</p>
        @endforelse
        {{ $deliveries->links() }}
    </div>
</div>
@endsection
