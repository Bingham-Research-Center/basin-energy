@extends('frontend.layouts.web')

@section('title', __('Home'))

@section('content')
        <main>
            <div class="container py-4">

                <div class="row justify-content-center">
                    <div class="col-md-12">

                        <div class="card">
                            <div class="card-header">
                                Bingham Research Center | Emission Website
                            </div>

                            <div class="card-body">

                                <div class="row">

                                    {{-- Left small column --}}
                                    <div class="col-md-2 mb-3">
                                        <div class="border rounded p-3 h-100">
                                            <h6>Left Menu</h6>
                                            <p class="small text-muted">
                                                Add info, stats, or filters here.
                                            </p>
                                        </div>
                                    </div>

                                    {{-- Center large column --}}
                                    <div class="col-md-8 mb-3">
                                        <div class="border rounded p-3 h-100">
                                            <h5>Main Content Area</h5>
                                            <p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. </p>
                                        </div>
                                    </div>

                                    {{-- Right small column --}}
                                    <div class="col-md-2 mb-3">
                                        <div class="border rounded p-3 h-100 d-flex flex-column">
                                            <h6>Tools</h6>
                                            <p class="small text-muted">View drone emissions map.</p>

                                            <a href="{{ route('frontend.pages.drone') }}"
                                            class="btn btn-primary btn-sm mt-auto"
                                            target="_blank">
                                                View drone emissions map.
                                            </a>
                                        </div>
                                    </div>

                                </div><!-- row -->

                            </div><!-- card-body -->
                        </div><!-- card -->

                    </div><!-- col -->
                </div><!-- row -->

            </div><!-- container -->
        </main>
@endsection