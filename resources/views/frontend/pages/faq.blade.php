@extends('frontend.layouts.web')

@section('title', 'Frequently Asked Questions')

@section('content')

<section class="page-title bg-1">
    <div class="container">
        <div class="columns">
            <div class="column is-12">
                <div class="has-text-centered">
                    <h1 class="text-capitalize mb-4 text-lg">
                        Frequently Asked Questions
                    </h1>
                    <p class="text-white">
                        Answers to common questions about Basin Energy,
                        environmental data, and the Uintah Basin.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">

        <div class="columns is-justify-content-center mb-5">
            <div class="column is-8-desktop has-text-centered">
                <div class="section-title">
                    <h2 class="mb-4">Frequently Asked Questions</h2>
                    <p>
                        Find answers to common questions about the Basin
                        Energy website, environmental information, and
                        resources available for the Uintah Basin.
                    </p>
                </div>
            </div>
        </div>

        <div class="columns is-justify-content-center">
            <div class="column is-9-desktop">

                <div class="mb-5">
                    <h4 class="mb-3">
                        What is Basin Energy?
                    </h4>
                    <p>
                        Basin Energy provides information and resources
                        related to energy, emissions, air quality, and
                        environmental conditions in the Uintah Basin.
                    </p>
                </div>

                <div class="mb-5">
                    <h4 class="mb-3">
                        What information can I find on this website?
                    </h4>
                    <p>
                        The website provides access to environmental data,
                        basin conditions, research information, and external
                        resources related to the Uintah Basin.
                    </p>
                </div>

                <div class="mb-5">
                    <h4 class="mb-3">
                        Where does the environmental data come from?
                    </h4>
                    <p>
                        Environmental information comes from monitoring
                        stations and other data sources used to provide
                        information about conditions throughout the Basin.
                    </p>
                </div>

                <div class="mb-5">
                    <h4 class="mb-3">
                        Can I view current Basin conditions?
                    </h4>
                    <p>
                        Yes. The Basin Conditions section provides access to
                        current environmental measurements and monitoring
                        information when available.
                    </p>
                </div>

                <div class="mb-5">
                    <h4 class="mb-3">
                        Where can I find additional Basin resources?
                    </h4>
                    <p>
                        The Basin Resources page provides links to external
                        organizations and agencies, including the Bureau of
                        Land Management, Utah Division of Air Quality,
                        Utah Geological Survey, and other natural-resource
                        organizations.
                    </p>

                    <a href="{{ route('frontend.pages.brc-resources') }}"
                       class="btn btn-main mt-3">
                        View Basin Resources
                    </a>
                </div>

                <div class="mb-5">
                    <h4 class="mb-3">
                        Who can I contact if I have additional questions?
                    </h4>
                    <p>
                        If you have a question that is not answered here,
                        please visit the Contact page to get in touch with
                        the Basin Energy team.
                    </p>

                    <a href="{{ route('frontend.pages.contact') }}"
                       class="btn btn-main mt-3">
                        Contact Us
                    </a>
                </div>

            </div>
        </div>

    </div>
</section>

@endsection

