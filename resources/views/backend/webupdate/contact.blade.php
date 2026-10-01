@extends('backend.layouts.app')

@section('title', 'Update Contact Page')

@section('content')
    <x-backend.card>
        <x-slot name="header">
            Update Contact Page
        </x-slot>

        <x-slot name="body">
            <form action="{{ route('admin.website-update.contact.update') }}" method="POST">
                @csrf

                <div class="form-group mb-3">
                    <label>Page Title</label>
                    <input type="text" name="hero_title" class="form-control"
                        value="{{ old('hero_title', $hero->title ?? '') }}">
                </div>

                <hr>

                <div class="form-group mb-3">
                    <label>Contact Lead Text</label>
                    <textarea name="contact_lead" class="form-control" rows="3">{{ old('contact_lead', $contactInfo->subtitle ?? '') }}</textarea>
                </div>

                <div class="form-group mb-3">
                    <label>Phone Number</label>
                    <input type="text" name="contact_phone" class="form-control"
                        value="{{ old('contact_phone', $contactInfo->title ?? '') }}">
                </div>

                <div class="form-group mb-3">
                    <label>Contact Description</label>
                    <textarea name="contact_description" class="form-control" rows="4">{{ old('contact_description', $contactInfo->description ?? '') }}</textarea>
                </div>

                <hr>

                <div class="form-group mb-3">
                    <label>Facebook URL</label>
                    <input type="url" name="facebook_url" class="form-control"
                        value="{{ old('facebook_url', $socialLinks->value['facebook'] ?? '') }}">
                </div>

                <div class="form-group mb-3">
                    <label>Twitter URL</label>
                    <input type="url" name="twitter_url" class="form-control"
                        value="{{ old('twitter_url', $socialLinks->value['twitter'] ?? '') }}">
                </div>

                <div class="form-group mb-3">
                    <label>LinkedIn URL</label>
                    <input type="url" name="linkedin_url" class="form-control"
                        value="{{ old('linkedin_url', $socialLinks->value['linkedin'] ?? '') }}">
                </div>

                <hr>

                <div class="form-group mb-3">
                    <label>Form Title</label>
                    <input type="text" name="form_title" class="form-control"
                        value="{{ old('form_title', $contactForm->title ?? '') }}">
                </div>

                <div class="form-group mb-3">
                    <label>Form Description</label>
                    <textarea name="form_description" class="form-control" rows="3">{{ old('form_description', $contactForm->description ?? '') }}</textarea>
                </div>

                <div class="form-group mb-3">
                    <label>Map Embed URL</label>
                    <textarea name="map_embed_url" class="form-control" rows="4">{{ old('map_embed_url', $map->value['embed_url'] ?? '') }}</textarea>
                    <small class="text-muted">Paste a Google Maps embed URL or iframe src value.</small>
                </div>

                <button type="submit" class="btn btn-primary">Save Contact Page</button>
            </form>
        </x-slot>
    </x-backend.card>

    <x-backend.card class="mt-3">
        <x-slot name="header">
            Contact Messages
        </x-slot>

        <x-slot name="body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Message</th>
                            <th>Received</th>
                            <th width="120">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($contactMessages as $message)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $message->name }}</td>
                                <td>{{ $message->email }}</td>
                                <td style="max-width: 320px; white-space: normal;">{{ $message->message }}</td>
                                <td>{{ $message->created_at ? $message->created_at->format('Y-m-d H:i') : '-' }}</td>
                                <td>
                                    <form action="{{ route('admin.website-update.contact.messages.delete', $message->id) }}"
                                        method="POST"
                                        class="d-inline delete-contact-message-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">No contact messages found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-slot>
    </x-backend.card>
@endsection

@push('after-scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.delete-contact-message-form').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                Swal.fire({
                    title: 'Are you sure?',
                    text: 'This contact message will be deleted permanently.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete it',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    });
</script>
@endpush