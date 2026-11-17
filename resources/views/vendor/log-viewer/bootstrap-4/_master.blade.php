@extends('backend.layouts.app')

@section('title', __('Application Logs'))

@push('after-styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>

.brc-log-viewer .page-header { border-bottom: 1px solid var(--brc-logs-divider, #d8e0e8); padding-bottom: .5rem; }
.brc-log-viewer .page-header h1 { font-size: 1.5rem; font-weight: 600; margin: 0; }
.brc-log-viewer .box { display: block; min-height: 70px; background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,.08); border-radius: .35rem; overflow: hidden; }
.brc-log-viewer .box .box-icon { float: left; width: 68px; height: 70px; line-height: 70px; text-align: center; font-size: 32px; background: rgba(0,0,0,.16); }
.brc-log-viewer .box .box-content { margin-left: 68px; padding: 8px 12px; }
.brc-log-viewer .box .box-text { display: block; font-weight: 600; font-size: 1rem; }
.brc-log-viewer .box .box-number { display: block; font-size: .85rem; }
.brc-log-viewer .box .box-text, .brc-log-viewer .box .box-number, .brc-log-viewer .box .box-icon { color: #fff; }
.brc-log-viewer .box .progress { background: rgba(0,0,0,.15); margin: 5px -12px -8px; }
.brc-log-viewer .box .progress-bar { background: #fff; }
.brc-log-viewer .badge.badge-level-all, .brc-log-viewer .badge.badge-level-emergency,
.brc-log-viewer .badge.badge-level-alert, .brc-log-viewer .badge.badge-level-critical,
.brc-log-viewer .badge.badge-level-error, .brc-log-viewer .badge.badge-level-warning,
.brc-log-viewer .badge.badge-level-notice, .brc-log-viewer .badge.badge-level-info,
.brc-log-viewer .badge.badge-level-debug, .brc-log-viewer .badge.empty,
.brc-log-viewer .badge.badge-env { color: #fff; }
.brc-log-viewer .stack-content { font-family: Consolas, Menlo, monospace; white-space: pre-wrap; overflow-wrap: anywhere; font-size: .8rem; }
.brc-log-viewer .log-menu .disabled { cursor: not-allowed; opacity: .6; }
.brc-log-viewer .table-responsive { overflow-x: auto; }
.brc-log-viewer .table td, .brc-log-viewer .table th { vertical-align: middle; }
.brc-log-viewer .brc-log-ring { width: min(240px, 100%); aspect-ratio: 1; border-radius: 50%; display: grid; place-items: center; margin: 0 auto 1rem; }
.brc-log-viewer .brc-log-ring::after { content: ''; background: var(--brc-log-center, #fff); width: 56%; height: 56%; border-radius: 50%; }
@media (prefers-color-scheme: dark) {
    body.c-dark-theme .brc-log-viewer .box.empty { background: #687482; }
}

.brc-log-viewer .badge.badge-env,
        .brc-log-viewer .badge.badge-level-all,
        .brc-log-viewer .badge.badge-level-emergency,
        .brc-log-viewer .badge.badge-level-alert,
        .brc-log-viewer .badge.badge-level-critical,
        .brc-log-viewer .badge.badge-level-error,
        .brc-log-viewer .badge.badge-level-warning,
        .brc-log-viewer .badge.badge-level-notice,
        .brc-log-viewer .badge.badge-level-info,
        .brc-log-viewer .badge.badge-level-debug,
        .brc-log-viewer .badge.empty {
            color: #FFF;
            text-shadow: 0 1px 1px rgba(0, 0, 0, 0.3);
        }

        .brc-log-viewer .badge.badge-level-all,
        .brc-log-viewer .box.level-all {
            background-color: {{ log_styler()->color('all') }};
        }

        .brc-log-viewer .badge.badge-level-emergency,
        .brc-log-viewer .box.level-emergency {
            background-color: {{ log_styler()->color('emergency') }};
        }

        .brc-log-viewer .badge.badge-level-alert,
        .brc-log-viewer .box.level-alert  {
            background-color: {{ log_styler()->color('alert') }};
        }

        .brc-log-viewer .badge.badge-level-critical,
        .brc-log-viewer .box.level-critical {
            background-color: {{ log_styler()->color('critical') }};
        }

        .brc-log-viewer .badge.badge-level-error,
        .brc-log-viewer .box.level-error {
            background-color: {{ log_styler()->color('error') }};
        }

        .brc-log-viewer .badge.badge-level-warning,
        .brc-log-viewer .box.level-warning {
            background-color: {{ log_styler()->color('warning') }};
        }

        .brc-log-viewer .badge.badge-level-notice,
        .brc-log-viewer .box.level-notice {
            background-color: {{ log_styler()->color('notice') }};
        }

        .brc-log-viewer .badge.badge-level-info,
        .brc-log-viewer .box.level-info {
            background-color: {{ log_styler()->color('info') }};
        }

        .brc-log-viewer .badge.badge-level-debug,
        .brc-log-viewer .box.level-debug {
            background-color: {{ log_styler()->color('debug') }};
        }

        .brc-log-viewer .badge.empty,
        .brc-log-viewer .box.empty {
            background-color: {{ log_styler()->color('empty') }};
        }

        .brc-log-viewer .badge.badge-env {
            background-color: #6A1B9A;
        }
    </style>
@endpush

{{-- Backend's layout owns the navigation, account header, breadcrumbs,
     styles, and JS. LogViewer child views provide the content section. --}}
@push('after-scripts')
    @yield('modals')
    @yield('scripts')
@endpush
