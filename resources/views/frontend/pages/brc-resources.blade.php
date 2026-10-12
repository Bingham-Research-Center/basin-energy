@extends('frontend.layouts.web')

@section('title', 'Basin Resources')

@section('content')

<section class="page-title bg-1">
    <div class="container">
        <div class="columns">
            <div class="column is-12">
                <div class="has-text-centered">
                    <h1 class="text-capitalize mb-4 text-lg">Basin Resources</h1>
                    <p class="text-white">
                        Research, data, and resources for the Uintah Basin.
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
                    <h2 class="mb-4">Explore Basin Resources</h2>
                    <p>
                        Access research, environmental information, and tools
                        related to energy and environmental conditions in the
                        Uintah Basin.
                    </p>
                </div>
            </div>
        </div>

        <div class="columns is-multiline">

            <div class="column is-4-desktop is-6-tablet">
                <div class="intro-item mb-5">
                    <i class="ti-bar-chart text-color text-md"></i>
                    <h4 class="mt-4 mb-3">Environmental Data</h4>
                    <p>
                        Explore environmental measurements and data collected
                        throughout the Uintah Basin.
                    </p>
                    <a href="{{ route('frontend.user.data.data') }}" class="btn btn-main mt-3">
    Explore Data
</a>
                </div>
            </div>

            <div class="column is-4-desktop is-6-tablet">
                <div class="intro-item mb-5">
                    <i class="ti-world text-color text-md"></i>
                    <h4 class="mt-4 mb-3">Basin Conditions</h4>
                    <p>
                        View current environmental conditions and measurements
                        from monitoring stations in the region.
                    </p>
                    <a href="{{ url('/') }}#basin-conditions" class="btn btn-main mt-3">
                        View Conditions
                    </a>
                </div>
            </div>

            <div class="column is-4-desktop is-6-tablet">
                <div class="intro-item mb-5">
                    <i class="ti-book text-color text-md"></i>
                    <h4 class="mt-4 mb-3">Research & Publications</h4>
                    <p>
                        Learn more about research and scientific work focused
                        on energy and environmental topics in the Basin.
                    </p>
                    <a href="https://www.usu.edu/binghamresearch/papers-and-reports"
   target="_blank"
   rel="noopener noreferrer"
   class="btn btn-main mt-3">
    View Research
</a>
                </div>
            </div>

        </div>

    </div>
</section>

<section class="section bg-gray">
    <div class="container">
        <div class="columns is-align-items-center">

            <div class="column is-7-desktop">
                <h2 class="mb-4 font-secondary">
                    Bingham Research Center
                </h2>

                <p class="mb-4">
                    The Bingham Research Center supports research and
                    environmental monitoring focused on the Uintah Basin.
                    Its work provides valuable information for understanding
                    energy development, air quality, and environmental
                    conditions in the region.
                </p>

                <a href="https://www.usu.edu/binghamresearch/"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="btn btn-main">
                    Visit the Research Center
                </a>
            </div>

            <div class="column is-5-desktop">
                <div class="about-item">
                    <h4 class="mb-3">Useful Resources</h4>

                    <ul>
                        <li class="mb-3">
                            <strong>Air Quality</strong> — Environmental
                            monitoring and air-quality information.
                        </li>
                        <li class="mb-3">
                            <strong>Energy Research</strong> — Research and
                            information about energy development.
                        </li>
                        <li>
                            <strong>Environmental Science</strong> —
                            Information about environmental conditions in the
                            Uintah Basin.
                        </li>
                    </ul>
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
                    <h2 class="mb-4">External Basin Resources</h2>
                    <p>
                        Helpful external websites and organizations providing
                        information about the Uintah Basin, environment,
                        energy, and natural resources.
                    </p>
                </div>
            </div>
        </div>

        <div class="columns is-multiline">

            <div class="column is-4-desktop is-6-tablet">
                <div class="intro-item mb-5">
                    <i class="ti-world text-color text-md"></i>
                    <h4 class="mt-4 mb-3">BLM – Vernal Field Office</h4>
                    <p>
                        Information about public lands, natural resources,
                        recreation, and land management in the Uintah Basin.
                    </p>
                    <a href="https://www.blm.gov/office/vernal-field-office"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="btn btn-main mt-3">
                        Learn More
                    </a>
                </div>
            </div>

            <div class="column is-4-desktop is-6-tablet">
                <div class="intro-item mb-5">
                    <i class="ti-pulse text-color text-md"></i>
                    <h4 class="mt-4 mb-3">Utah Division of Air Quality</h4>
                    <p>
                        Air-quality information, monitoring, forecasts,
                        emissions, and environmental resources for Utah.
                    </p>
                    <a href="https://deq.utah.gov/"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="btn btn-main mt-3">
                        Learn More
                    </a>
                </div>
            </div>

            <div class="column is-4-desktop is-6-tablet">
                <div class="intro-item mb-5">
                    <i class="ti-map-alt text-color text-md"></i>
                    <h4 class="mt-4 mb-3">Utah Geological Survey</h4>
                    <p>
                        Geological data, maps, publications, and information
                        about Utah's natural resources.
                    </p>
                    <a href="https://geology.utah.gov/"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="btn btn-main mt-3">
                        Learn More
                    </a>
                </div>
            </div>

            <div class="column is-4-desktop is-6-tablet">
                <div class="intro-item mb-5">
                    <i class="ti-settings text-color text-md"></i>
                    <h4 class="mt-4 mb-3">Utah Division of Oil, Gas & Mining</h4>
                    <p>
                        Information about oil, gas, mining, regulation,
                        reclamation, and natural-resource management.
                    </p>
                    <a href="https://ogm.utah.gov/"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="btn btn-main mt-3">
                        Learn More
                    </a>
                </div>
            </div>

            <div class="column is-4-desktop is-6-tablet">
                <div class="intro-item mb-5">
                    <i class="ti-home text-color text-md"></i>
                    <h4 class="mt-4 mb-3">Uintah County</h4>
                    <p>
                        Local government services, community information,
                        public resources, and county information.
                    </p>
                    <a href="https://www.uintah.gov/"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="btn btn-main mt-3">
                        Learn More
                    </a>
                </div>
            </div>

            <div class="column is-4-desktop is-6-tablet">
                <div class="intro-item mb-5">
                    <i class="ti-shield text-color text-md"></i>
                    <h4 class="mt-4 mb-3">U.S. Environmental Protection Agency</h4>
                    <p>
                        Federal environmental information, regulations,
                        research, and air-quality resources.
                    </p>
                    <a href="https://www.epa.gov/"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="btn btn-main mt-3">
                        Learn More
                    </a>
                </div>
            </div>

        </div>

    </div>
</section>

@endsection
