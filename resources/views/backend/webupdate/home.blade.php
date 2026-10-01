@extends('backend.layouts.app')

@section('title', 'Update Home Page')

@section('content')

    <x-backend.card>
        <x-slot name="header">
            Update Home Page
        </x-slot>

        <x-slot name="body">
            <form action="{{ route('admin.website-update.home.update') }}" method="POST">
                @csrf

                <h5 class="mb-3">Hero Section</h5>

                <div class="form-group mb-3">
                    <label for="hero_subtitle">Hero Subtitle</label>
                    <input
                        type="text"
                        name="hero_subtitle"
                        id="hero_subtitle"
                        class="form-control"
                        value="{{ old('hero_subtitle', $hero->description ?? 'Prepare for new future') }}"
                    >
                </div>

                <div class="form-group mb-3">
                    <label for="hero_title">Hero Title</label>
                    <textarea
                        name="hero_title"
                        id="hero_title"
                        class="form-control"
                        rows="3"
                    >{{ old('hero_title', $hero->title ?? 'All About Emissions, Energy, and Environmental Trends in Uintah Basin') }}</textarea>
                </div>

                <div class="form-group mb-3">
                    <label for="hero_button_text">Get Started Button Text</label>
                    <input
                        type="text"
                        name="hero_button_text"
                        id="hero_button_text"
                        class="form-control"
                        value="{{ old('hero_button_text', $hero->button_text ?? 'Get started...') }}"
                    >
                </div>

                <div class="form-group mb-4">
                    <label for="hero_button_link">Get Started Button Link</label>
                    <input
                        type="text"
                        name="hero_button_link"
                        id="hero_button_link"
                        class="form-control"
                        value="{{ old('hero_button_link', $hero->button_link ?? '#!') }}"
                    >
                </div>

                <hr>

                <h5 class="mb-3">Basin Weather Button</h5>

                <div class="form-group mb-3">
                    <label for="weather_button_text">Button Text</label>
                    <input
                        type="text"
                        name="weather_button_text"
                        id="weather_button_text"
                        class="form-control"
                        value="{{ old('weather_button_text', $weatherButton->button_text ?? 'Basin Weather Now') }}"
                    >
                </div>

                <div class="form-group mb-4">
                    <label for="weather_button_link">Button Link</label>
                    <input
                        type="text"
                        name="weather_button_link"
                        id="weather_button_link"
                        class="form-control"
                        value="{{ old('weather_button_link', $weatherButton->button_link ?? 'http://basinwx.com') }}"
                    >
                </div>

                <hr>

                <h5 class="mb-3">Platform Section</h5>

                <div class="form-group mb-3">
                    <label for="platform_heading">Heading</label>
                    <textarea
                        name="platform_heading"
                        id="platform_heading"
                        class="form-control"
                        rows="3"
                    >{{ old('platform_heading', $platform->title ?? 'Integrated, research-based platform that brings together emissions, energy, and environmental trends in Uintah Basin') }}</textarea>
                </div>

                <div class="form-group mb-3">
                    <label for="platform_description">Description</label>
                    <textarea
                        name="platform_description"
                        id="platform_description"
                        class="form-control"
                        rows="5"
                    >{{ old('platform_description', $platform->description ?? 'Scientists, technical staff, and students at the Bingham Research Center are dedicated to energy and environmental research in Utah and around the world. We specialize in the areas of air quality, energy, and environmental science.') }}</textarea>
                </div>

                <div class="form-group mb-3">
                    <label for="platform_button_text">Button Text</label>
                    <input
                        type="text"
                        name="platform_button_text"
                        id="platform_button_text"
                        class="form-control"
                        value="{{ old('platform_button_text', $platformButton->button_text ?? 'Bingham Research Center') }}"
                    >
                </div>

                <div class="form-group mb-4">
                    <label for="platform_button_link">Button Link</label>
                    <input
                        type="text"
                        name="platform_button_link"
                        id="platform_button_link"
                        class="form-control"
                        value="{{ old('platform_button_link', $platformButton->button_link ?? 'https://www.usu.edu/binghamresearch/') }}"
                    >
                </div>

                <button type="submit" class="btn btn-primary">
                    Save Home Page
                </button>
            </form>
        </x-slot>
    </x-backend.card>

@endsection