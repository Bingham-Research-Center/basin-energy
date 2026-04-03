@extends('backend.layouts.app')

@section('title', 'Update Service Page')

@section('content')
    <x-backend.card>
        <x-slot name="header">
            Update Service Page
        </x-slot>

        <x-slot name="body">
            <form action="{{ route('admin.website-update.service.update') }}" method="POST">
                @csrf

                <div class="form-group mb-3">
                    <label>Page Title</label>
                    <input type="text" name="hero_title" class="form-control"
                        value="{{ old('hero_title', $hero->title ?? '') }}">
                </div>

                <button type="submit" class="btn btn-primary">Save Service Page</button>
            </form>
        </x-slot>
    </x-backend.card>

    <x-backend.card class="mt-3">
        <x-slot name="header">
            Service Items
        </x-slot>

        <x-slot name="headerActions">
            <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#createServiceItemModal">
                Add Service Item
            </button>
        </x-slot>

        <x-slot name="body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Icon</th>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Order</th>
                            <th>Status</th>
                            <th width="180">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($serviceItems as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td><i class="{{ $item->icon ?: 'ti-desktop' }}"></i></td>
                                <td>{{ $item->title }}</td>
                                <td>{{ $item->description }}</td>
                                <td>{{ $item->sort_order }}</td>
                                <td>
                                    @if($item->is_active)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info"
                                        data-toggle="modal" data-target="#viewServiceItemModal{{ $item->id }}">
                                        View
                                    </button>

                                    <button type="button" class="btn btn-sm btn-warning"
                                        data-toggle="modal" data-target="#editServiceItemModal{{ $item->id }}">
                                        Edit
                                    </button>

                                    <form action="{{ route('admin.website-update.service.items.delete', $item->id) }}"
                                        method="POST"
                                        class="d-inline delete-service-item-form">
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
                                <td colspan="7" class="text-center">No service items found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-slot>
    </x-backend.card>

    <div class="modal fade" id="createServiceItemModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <form action="{{ route('admin.website-update.service.items.store') }}"
                method="POST"
                class="modal-content">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title">Add Service Item</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="form-group">
                        <label>Title</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="4"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Icon Class</label>
                        <input type="text" name="icon" class="form-control" placeholder="ti-desktop">
                        <small class="text-muted">Example: ti-desktop, ti-layers, ti-bar-chart. Refer your favorite icon name from <a href="https://themify.me/themify-icons" target="_blank">Themify Icons</a></small>
                    </div>

                    <div class="form-group">
                        <label>Sort Order</label>
                        <input type="number" name="sort_order" class="form-control" value="0">
                    </div>

                    <div class="form-group form-check">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" id="createServiceActive" checked>
                        <label class="form-check-label" for="createServiceActive">Active</label>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save Service Item</button>
                </div>
            </form>
        </div>
    </div>

    @foreach($serviceItems as $item)
        <div class="modal fade" id="viewServiceItemModal{{ $item->id }}" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">View Service Item</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <p><strong>Icon:</strong> <i class="{{ $item->icon ?: 'ti-desktop' }}"></i> </p>
                        <p><strong>Title:</strong> {{ $item->title }}</p>
                        <p><strong>Description:</strong> {{ $item->description }}</p>
                        <p><strong>Sort Order:</strong> {{ $item->sort_order }}</p>
                        <p><strong>Status:</strong> {{ $item->is_active ? 'Active' : 'Inactive' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="editServiceItemModal{{ $item->id }}" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <form action="{{ route('admin.website-update.service.items.update', $item->id) }}"
                    method="POST"
                    class="modal-content">
                    @csrf
                    @method('PUT')

                    <div class="modal-header">
                        <h5 class="modal-title">Edit Service Item</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="form-group">
                            <label>Title</label>
                            <input type="text" name="title" class="form-control" value="{{ $item->title }}" required>
                        </div>

                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="4">{{ $item->description }}</textarea>
                        </div>

                        <div class="form-group">
                            <label>Icon Class</label>
                            <input type="text" name="icon" class="form-control" value="{{ $item->icon }}">
                        </div>

                        <div class="form-group">
                            <label>Sort Order</label>
                            <input type="number" name="sort_order" class="form-control" value="{{ $item->sort_order }}">
                        </div>

                        <div class="form-group form-check">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input"
                                id="serviceActive{{ $item->id }}" {{ $item->is_active ? 'checked' : '' }}>
                            <label class="form-check-label" for="serviceActive{{ $item->id }}">Active</label>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Update Service Item</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endsection

@push('after-scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.delete-service-item-form').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                Swal.fire({
                    title: 'Are you sure?',
                    text: 'This service item will be deleted permanently.',
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