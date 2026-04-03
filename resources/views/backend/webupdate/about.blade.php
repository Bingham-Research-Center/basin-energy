@extends('backend.layouts.app')

@section('title', 'Update About Page')

@section('content')
    <x-backend.card>
        <x-slot name="header">
            Update About Page
        </x-slot>

        <x-slot name="body">
            <form action="{{ route('admin.website-update.about.update') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="form-group mb-3">
                    <label>Hero Title</label>
                    <input type="text" name="hero_title" class="form-control"
                        value="{{ old('hero_title', $hero->title ?? '') }}">
                </div>

                <div class="form-group mb-3">
                    <label>Intro Heading</label>
                    <textarea name="intro_heading" class="form-control" rows="3">{{ old('intro_heading', $intro->title ?? '') }}</textarea>
                </div>

                <div class="form-group mb-3">
                    <label>About Heading</label>
                    <input type="text" name="about_heading" class="form-control"
                        value="{{ old('about_heading', $aboutSection->title ?? '') }}">
                </div>

                <div class="form-group mb-3">
                    <label>About Description</label>
                    <textarea name="about_description" class="form-control" rows="5">{{ old('about_description', $aboutSection->description ?? '') }}</textarea>
                </div>

                <div class="form-group mb-3">
                    <label>Button Text</label>
                    <input type="text" name="about_button_text" class="form-control"
                        value="{{ old('about_button_text', $aboutSection->button_text ?? '') }}">
                </div>

                <div class="form-group mb-3">
                    <label>Button Link</label>
                    <input type="text" name="about_button_link" class="form-control"
                        value="{{ old('about_button_link', $aboutSection->button_link ?? '') }}">
                </div>

                <div class="form-group mb-3">
                    <label>About Image</label>
                    <input type="file" name="about_image" class="form-control">

                    @if (!empty($aboutSection?->image))
                        <div class="mt-2">
                            <img src="{{ asset('storage/' . $aboutSection->image) }}" alt="About Image"
                                style="max-height: 120px;">
                        </div>
                    @endif
                </div>

                <div class="form-group mb-3">
                    <label>Team Section Title</label>
                    <input type="text" name="team_title" class="form-control"
                        value="{{ old('team_title', $teamHeader->title ?? '') }}">
                </div>

                <div class="form-group mb-3">
                    <label>Team Section Description</label>
                    <textarea name="team_description" class="form-control" rows="4">{{ old('team_description', $teamHeader->description ?? '') }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary">Save About Page</button>
            </form>
        </x-slot>
    </x-backend.card>

    <x-backend.card class="mt-3">
        <x-slot name="header">
            Team Members
        </x-slot>

        <x-slot name="headerActions">
            <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#createTeamMemberModal">
                Add Team Member
            </button>
        </x-slot>

        <x-slot name="body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Title</th>
                            <th>GitHub</th>
                            <th>Twitter</th>
                            <th>Instagram</th>
                            <th>LinkedIn</th>
                            <th>Order</th>
                            <th>Status</th>
                            <th width="180">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($teamMembers as $member)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    @if($member->image)
                                        <img src="{{ asset('storage/' . $member->image) }}" alt="{{ $member->title }}"
                                            style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px;">
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $member->title }}</td>
                                <td>{{ $member->subtitle }}</td>
                                <td>{{ $member->value['facebook'] ?? '-' }}</td>
                                <td>{{ $member->value['twitter'] ?? '-' }}</td>
                                <td>{{ $member->value['instagram'] ?? '-' }}</td>
                                <td>{{ $member->value['linkedin'] ?? '-' }}</td>
                                <td>{{ $member->sort_order }}</td>
                                <td>
                                    @if($member->is_active)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info"
                                        data-toggle="modal" data-target="#viewTeamMemberModal{{ $member->id }}">
                                        View
                                    </button>

                                    <button type="button" class="btn btn-sm btn-warning"
                                        data-toggle="modal" data-target="#editTeamMemberModal{{ $member->id }}">
                                        Edit
                                    </button>

                                    <form action="{{ route('admin.website-update.about.team-members.delete', $member->id) }}"
                                        method="POST"
                                        class="d-inline delete-team-member-form">
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
                                <td colspan="11" class="text-center">No team members found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-slot>
    </x-backend.card>

    <div class="modal fade" id="createTeamMemberModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <form action="{{ route('admin.website-update.about.team-members.store') }}"
                method="POST"
                enctype="multipart/form-data"
                class="modal-content">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title">Add Team Member</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Title</label>
                        <input type="text" name="designation" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Image</label>
                        <input type="file" name="image" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Facebook URL</label>
                        <input type="url" name="facebook_url" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Twitter URL</label>
                        <input type="url" name="twitter_url" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Instagram URL</label>
                        <input type="url" name="instagram_url" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>LinkedIn URL</label>
                        <input type="url" name="linkedin_url" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Sort Order</label>
                        <input type="number" name="sort_order" class="form-control" value="0">
                    </div>

                    <div class="form-group form-check">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" id="createTeamMemberActive" checked>
                        <label class="form-check-label" for="createTeamMemberActive">Active</label>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save Member</button>
                </div>
            </form>
        </div>
    </div>

    @foreach($teamMembers as $member)
        <div class="modal fade" id="viewTeamMemberModal{{ $member->id }}" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">View Team Member</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        @if($member->image)
                            <div class="mb-3">
                                <img src="{{ asset('storage/' . $member->image) }}" alt="{{ $member->title }}"
                                    style="max-width: 150px;">
                            </div>
                        @endif

                        <p><strong>Name:</strong> {{ $member->title }}</p>
                        <p><strong>Title:</strong> {{ $member->subtitle }}</p>
                        <p><strong>GitHub:</strong> {{ $member->value['facebook'] ?? '-' }}</p>
                        <p><strong>Twitter:</strong> {{ $member->value['twitter'] ?? '-' }}</p>
                        <p><strong>Instagram:</strong> {{ $member->value['instagram'] ?? '-' }}</p>
                        <p><strong>LinkedIn:</strong> {{ $member->value['linkedin'] ?? '-' }}</p>
                        <p><strong>Sort Order:</strong> {{ $member->sort_order }}</p>
                        <p><strong>Status:</strong> {{ $member->is_active ? 'Active' : 'Inactive' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="editTeamMemberModal{{ $member->id }}" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <form action="{{ route('admin.website-update.about.team-members.update', $member->id) }}"
                    method="POST"
                    enctype="multipart/form-data"
                    class="modal-content">
                    @csrf
                    @method('PUT')

                    <div class="modal-header">
                        <h5 class="modal-title">Edit Team Member</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" name="name" class="form-control" value="{{ $member->title }}" required>
                        </div>

                        <div class="form-group">
                            <label>Title</label>
                            <input type="text" name="designation" class="form-control" value="{{ $member->subtitle }}">
                        </div>

                        <div class="form-group">
                            <label>Image</label>
                            <input type="file" name="image" class="form-control">
                        </div>

                        <div class="form-group">
                            <label>Facebook URL</label>
                            <input type="url" name="facebook_url" class="form-control" value="{{ $member->value['facebook'] ?? '' }}">
                        </div>

                        <div class="form-group">
                            <label>Twitter URL</label>
                            <input type="url" name="twitter_url" class="form-control" value="{{ $member->value['twitter'] ?? '' }}">
                        </div>

                        <div class="form-group">
                            <label>Instagram URL</label>
                            <input type="url" name="instagram_url" class="form-control" value="{{ $member->value['instagram'] ?? '' }}">
                        </div>

                        <div class="form-group">
                            <label>LinkedIn URL</label>
                            <input type="url" name="linkedin_url" class="form-control" value="{{ $member->value['linkedin'] ?? '' }}">
                        </div>

                        <div class="form-group">
                            <label>Sort Order</label>
                            <input type="number" name="sort_order" class="form-control" value="{{ $member->sort_order }}">
                        </div>

                        <div class="form-group form-check">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input"
                                id="active{{ $member->id }}" {{ $member->is_active ? 'checked' : '' }}>
                            <label class="form-check-label" for="active{{ $member->id }}">Active</label>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endsection

@push('after-scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.delete-team-member-form').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                Swal.fire({
                    title: 'Are you sure?',
                    text: 'This team member will be deleted permanently.',
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