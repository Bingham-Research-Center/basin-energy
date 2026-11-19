@if (Breadcrumbs::has() && !Route::is('frontend.index'))
    <ol class="breadcrumb border-0 m-0" aria-label="Breadcrumb">
        @foreach (Breadcrumbs::current() as $crumb)
            <li class="breadcrumb-item {{ $loop->last ? 'active' : '' }}"
                @if ($loop->last)
                    aria-current="page"
                @endif>
                @if ($crumb->url() && ! $loop->last)
                    <x-utils.link :href="$crumb->url()" :text="$crumb->title()" />
                @else
                    {{ $crumb->title() }}
                @endif
            </li>
        @endforeach
    </ol>
@endif
