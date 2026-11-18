@extends('frontend.layouts.web')

@section('title', 'Unsubscribe from Newsletter')

@section('content')
<section class="section"><div class="container py-5" style="max-width:620px">
    <h1>Unsubscribe from BasinEnergy newsletters</h1>
    <p>Stop receiving monthly newsletters at {{ $unsubscribeUser->email }}?</p>
    <form method="POST" action="{{ URL::temporarySignedRoute('frontend.newsletter.unsubscribe.email.submit', now()->addMinutes(30), ['user' => $unsubscribeUser->id]) }}">
        @csrf
        <button type="submit" class="btn btn-primary">Unsubscribe</button>
        <a href="{{ route('frontend.index') }}" class="btn btn-link">Cancel</a>
    </form>
</div></section>
@endsection
