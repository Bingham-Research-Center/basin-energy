@extends('frontend.layouts.app')

@push('after-styles')
<link
  rel="stylesheet"
  href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
/>
@endpush


@section('title', 'Carbon Mapper – Super Emitters')
@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="mb-0">Carbon Mapper – CH₄ Super-Emitters</h3>
    </div>

    <div class="card-body p-0">
        <div id="map" style="height: 600px;"></div>
    </div>
</div>
@endsection

@push('after-scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endpush
