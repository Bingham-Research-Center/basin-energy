@extends('frontend.layouts.web')

@section('title', 'Newsletter Unsubscribed')

@section('content')
<section class="section"><div class="container py-5">
    <h1>Subscription updated</h1>
    <p>You have been removed from future BasinEnergy monthly newsletters.</p>
    <a href="{{ route('frontend.index') }}" class="btn btn-primary">Return home</a>
</div></section>
@endsection
