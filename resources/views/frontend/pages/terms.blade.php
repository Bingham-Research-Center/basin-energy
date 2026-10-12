@extends('frontend.layouts.web')

@section('title', 'Terms & Conditions')

@section('content')

<section class="page-title bg-1">
    <div class="container">
        <div class="columns">
            <div class="column is-12">
                <div class="has-text-centered">
                    <h1 class="text-capitalize mb-4 text-lg">
                        Terms & Conditions
                    </h1>
                    <p class="text-white">
                        Terms governing the use of the Basin Energy website
                        and its resources.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">

    <div class="columns is-justify-content-center">
        <div class="column is-9-desktop">

            <div class="section-title mb-5">
                <h2 class="mb-4">Terms & Conditions</h2>
                <p>
                    Welcome to Basin Energy. By accessing or using this
                    website, you agree to the terms and conditions
                    described below. Please read these terms carefully
                    before using the website.
                </p>
            </div>

            <div class="mb-5">
                <h4 class="mb-3">1. Acceptance of Terms</h4>
                <p>
                    By accessing and using the Basin Energy website, you
                    agree to comply with these Terms & Conditions and all
                    applicable laws and regulations. If you do not agree
                    with these terms, please do not use this website.
                </p>
            </div>

            <div class="mb-5">
                <h4 class="mb-3">2. Use of the Website</h4>
                <p>
                    The Basin Energy website is provided to give users
                    access to information, environmental data, research,
                    and other resources related to the Uintah Basin.
                </p>
                <p>
                    You agree to use the website for lawful purposes and
                    not to interfere with its operation, security, or
                    availability.
                </p>
            </div>

            <div class="mb-5">
                <h4 class="mb-3">3. Data and Information</h4>
                <p>
                    Basin Energy provides environmental, energy, and
                    resource-related information for informational
                    purposes. Data displayed on this website may be
                    collected from monitoring systems, research activities,
                    public sources, and other data providers.
                </p>
                <p>
                    While reasonable efforts are made to provide accurate
                    and useful information, Basin Energy does not guarantee
                    that all information or data is complete, current, or
                    error-free.
                </p>
            </div>

            <div class="mb-5">
                <h4 class="mb-3">4. External Links</h4>
                <p>
                    This website may contain links to websites operated
                    by government agencies, organizations, institutions,
                    and other third parties. These links are provided as
                    additional resources and for user convenience.
                </p>
                <p>
                    Basin Energy is not responsible for the content,
                    availability, privacy practices, or policies of
                    external websites.
                </p>
            </div>

            <div class="mb-5">
                <h4 class="mb-3">5. Intellectual Property</h4>
                <p>
                    Unless otherwise indicated, content created for the
                    Basin Energy website, including text, graphics,
                    designs, and other materials, may be protected by
                    applicable intellectual property laws.
                </p>
                <p>
                    Content belonging to third-party organizations remains
                    the property of its respective owners and is subject
                    to their applicable terms and conditions.
                </p>
            </div>

            <div class="mb-5">
                <h4 class="mb-3">6. Disclaimer</h4>
                <p>
                    Information available through this website is provided
                    for general informational and educational purposes.
                    Basin Energy does not guarantee the accuracy,
                    completeness, reliability, or availability of
                    information presented on the website.
                </p>
                <p>
                    Users should independently verify information before
                    relying on it for decisions, research, professional
                    activities, or other purposes.
                </p>
            </div>

            <div class="mb-5">
                <h4 class="mb-3">7. Privacy</h4>
                <p>
                    Your use of this website may be subject to applicable
                    privacy practices and policies. Information collected
                    through forms or other website features will be
                    handled in accordance with applicable requirements.
                </p>
            </div>

            <div class="mb-5">
                <h4 class="mb-3">8. Changes to These Terms</h4>
                <p>
                    Basin Energy may update these Terms & Conditions from
                    time to time. Changes may be made to reflect updates
                    to the website, services, resources, or applicable
                    requirements.
                </p>
                <p>
                    Continued use of the website after changes are posted
                    constitutes acceptance of the updated terms.
                </p>
            </div>

            <div class="mb-5">
                <h4 class="mb-3">9. Contact Us</h4>
                <p>
                    If you have questions about these Terms & Conditions
                    or the use of this website, please contact the Basin
                    Energy team.
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
