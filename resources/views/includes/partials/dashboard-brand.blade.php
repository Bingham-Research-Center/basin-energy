{{-- Shared BRC/USU dashboard identity for both Admin and User dashboards. --}}
<a href="{{ $destination }}"
   class="brc-dashboard-brand-link"
   aria-label="{{ __('Bingham Research Center, Utah State University — Dashboard') }}"
   title="{{ __('Bingham Research Center, Utah State University') }}">
    <span class="{{ $compact ? 'c-sidebar-brand-full ' : '' }}brc-dashboard-brand-full">
        <img src="{{ asset('img/brand/brc-usu-logo.png') }}"
             class="brc-brand-image brc-brand-blue" width="480" height="89"
             alt="{{ __('Bingham Research Center, Utah State University') }}">
        <img src="{{ asset('img/brand/brc-usu-logo.png') }}"
             class="brc-brand-image brc-brand-black" width="480" height="89"
             alt="" aria-hidden="true">
    </span>
    @if($compact)
        {{-- In CoreUI's minimized sidebar show the USU symbol from the
             supplied full-size artwork instead of shrinking the wordmark. --}}
        <span class="c-sidebar-brand-minimized brc-dashboard-brand-minimized" aria-hidden="true">
            <span class="brc-brand-symbol-crop">
                <img src="{{ asset('img/brand/brc-usu-logo.png') }}"
                     class="brc-brand-blue" width="480" height="89" alt="">
                <img src="{{ asset('img/brand/brc-usu-logo.png') }}"
                     class="brc-brand-black" width="480" height="89" alt="">
            </span>
        </span>
    @endif
</a>
