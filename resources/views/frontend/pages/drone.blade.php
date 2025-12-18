@extends('frontend.layouts.web')

@section('title', __('Drone Data Test Results'))

@section('content')
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <x-frontend.card>
                    <x-slot name="header">
                        Drone Test Data Visualizations
                    </x-slot>

                    <x-slot name="body">
                        <iframe 
                            src="{{ asset('assets/drone_test_data/drone_map.html') }}" 
                            width="100%" 
                            height="600" 
                            style="border:none;">
                        </iframe>
                    </x-slot>
                </x-frontend.card>
            </div><!--col-md-10-->
        </div><!--row-->
    </div><!--container-->
@endsection
