@extends('backend.layouts.app')

@section('title', 'Update Portfolio Page')

@section('content')
    <x-backend.card>
        <x-slot name="header">
            Update Portfolio Page
        </x-slot>

        <x-slot name="body">
            <form action="{{ route('admin.website-update.portfolio.update') }}" method="POST">
                @csrf

                <div class="form-group mb-3">
                    <label>Page Title</label>
                    <input type="text" name="hero_title" class="form-control"
                        value="{{ old('hero_title', $hero->title ?? '') }}">
                </div>

                <div class="form-group mb-3">
                    <label>Portfolio Section Title</label>
                    <input type="text" name="portfolio_title" class="form-control"
                        value="{{ old('portfolio_title', $portfolioHeader->title ?? '') }}">
                </div>

                <div class="form-group mb-3">
                    <label>Portfolio Section Description</label>
                    <textarea name="portfolio_description" class="form-control" rows="4">{{ old('portfolio_description', $portfolioHeader->description ?? '') }}</textarea>
                </div>

                <hr>

                <div class="form-group mb-3">
                    <label>CTA Small Text</label>
                    <input type="text" name="cta_subtitle" class="form-control"
                        value="{{ old('cta_subtitle', $cta->subtitle ?? '') }}">
                </div>

                <div class="form-group mb-3">
                    <label>CTA Title</label>
                    <input type="text" name="cta_title" class="form-control"
                        value="{{ old('cta_title', $cta->title ?? '') }}">
                </div>

                <div class="form-group mb-3">
                    <label>CTA Primary Button Text</label>
                    <input type="text" name="cta_button_text" class="form-control"
                        value="{{ old('cta_button_text', $cta->button_text ?? '') }}">
                </div>

                <div class="form-group mb-3">
                    <label>CTA Primary Button Link</label>
                    <input type="text" name="cta_button_link" class="form-control"
                        value="{{ old('cta_button_link', $cta->button_link ?? '') }}">
                </div>

                <div class="form-group mb-3">
                    <label>CTA Secondary Button Text</label>
                    <input type="text" name="cta_secondary_button_text" class="form-control"
                        value="{{ old('cta_secondary_button_text', $cta->value['secondary_button_text'] ?? '') }}">
                </div>

                <div class="form-group mb-3">
                    <label>CTA Secondary Button Link</label>
                    <input type="text" name="cta_secondary_button_link" class="form-control"
                        value="{{ old('cta_secondary_button_link', $cta->value['secondary_button_link'] ?? '') }}">
                </div>

                <button type="submit" class="btn btn-primary">Save Portfolio Page</button>
            </form>
        </x-slot>
    </x-backend.card>

    <x-backend.card class="mt-3">
        <x-slot name="header">
            Portfolio Gallery Items
        </x-slot>

        <x-slot name="headerActions">
            <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#createPortfolioItemModal">
                Add Portfolio Item
            </button>
        </x-slot>

        <x-slot name="body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Categories</th>
                            <th>Order</th>
                            <th>Status</th>
                            <th width="180">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($portfolioItems as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    @if($item->image)
                                        <img src="{{ asset('storage/' . $item->image) }}" alt="{{ $item->title }}"
                                            style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px;">
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $item->title }}</td>
                                <td>{{ $item->description }}</td>
                                <td>
                                    @if(!empty($item->categories))
                                        {{ implode(', ', $item->categories) }}
                                    @else
                                        -
                                    @endif
                                </td>
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
                                        data-toggle="modal" data-target="#viewPortfolioItemModal{{ $item->id }}">
                                        View
                                    </button>

                                    <button type="button" class="btn btn-sm btn-warning"
                                        data-toggle="modal" data-target="#editPortfolioItemModal{{ $item->id }}">
                                        Edit
                                    </button>

                                    <form action="{{ route('admin.website-update.portfolio.items.delete', $item->id) }}"
                                        method="POST"
                                        class="d-inline delete-portfolio-item-form">
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
                                <td colspan="8" class="text-center">No portfolio items found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-slot>
    </x-backend.card>

    <div class="modal fade" id="createPortfolioItemModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <form action="{{ route('admin.website-update.portfolio.items.store') }}"
                method="POST"
                enctype="multipart/form-data"
                class="modal-content">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title">Add Portfolio Item</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="form-group">
                        <label>Title</label>
                        <input type="text" name="title" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="4"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Image</label>
                        <input type="file" name="image" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Categories</label>
                        <div>
                            <div class="form-check">
                                <input type="checkbox" name="categories[]" value="design" class="form-check-input" id="createCategoryDesign">
                                <label class="form-check-label" for="createCategoryDesign">UI/UX Design</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" name="categories[]" value="branding" class="form-check-input" id="createCategoryBranding">
                                <label class="form-check-label" for="createCategoryBranding">Branding</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" name="categories[]" value="illustration" class="form-check-input" id="createCategoryIllustration">
                                <label class="form-check-label" for="createCategoryIllustration">Illustration</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Sort Order</label>
                        <input type="number" name="sort_order" class="form-control" value="0">
                    </div>

                    <div class="form-group form-check">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" id="createPortfolioActive" checked>
                        <label class="form-check-label" for="createPortfolioActive">Active</label>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save Portfolio Item</button>
                </div>
            </form>
        </div>
    </div>

    @foreach($portfolioItems as $item)
        <div class="modal fade" id="viewPortfolioItemModal{{ $item->id }}" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">View Portfolio Item</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        @if($item->image)
                            <div class="mb-3">
                                <img src="{{ asset('storage/' . $item->image) }}" alt="{{ $item->title }}"
                                    style="max-width: 200px; border-radius: 6px;">
                            </div>
                        @endif

                        <p><strong>Title:</strong> {{ $item->title }}</p>
                        <p><strong>Description:</strong> {{ $item->description }}</p>
                        <p><strong>Categories:</strong> {{ !empty($item->categories) ? implode(', ', $item->categories) : '-' }}</p>
                        <p><strong>Sort Order:</strong> {{ $item->sort_order }}</p>
                        <p><strong>Status:</strong> {{ $item->is_active ? 'Active' : 'Inactive' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="editPortfolioItemModal{{ $item->id }}" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <form action="{{ route('admin.website-update.portfolio.items.update', $item->id) }}"
                    method="POST"
                    enctype="multipart/form-data"
                    class="modal-content">
                    @csrf
                    @method('PUT')

                    <div class="modal-header">
                        <h5 class="modal-title">Edit Portfolio Item</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="form-group">
                            <label>Title</label>
                            <input type="text" name="title" class="form-control" value="{{ $item->title }}">
                        </div>

                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="4">{{ $item->description }}</textarea>
                        </div>

                        <div class="form-group">
                            <label>Image</label>
                            <input type="file" name="image" class="form-control">
                            @if($item->image)
                                <div class="mt-2">
                                    <img src="{{ asset('storage/' . $item->image) }}" alt="{{ $item->title }}"
                                        style="max-width: 120px; border-radius: 6px;">
                                </div>
                            @endif
                        </div>

                        <div class="form-group">
                            <label>Categories</label>
                            <div>
                                <div class="form-check">
                                    <input type="checkbox" name="categories[]" value="design" class="form-check-input"
                                        id="editCategoryDesign{{ $item->id }}"
                                        {{ in_array('design', $item->categories ?? []) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="editCategoryDesign{{ $item->id }}">UI/UX Design</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" name="categories[]" value="branding" class="form-check-input"
                                        id="editCategoryBranding{{ $item->id }}"
                                        {{ in_array('branding', $item->categories ?? []) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="editCategoryBranding{{ $item->id }}">Branding</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" name="categories[]" value="illustration" class="form-check-input"
                                        id="editCategoryIllustration{{ $item->id }}"
                                        {{ in_array('illustration', $item->categories ?? []) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="editCategoryIllustration{{ $item->id }}">Illustration</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Sort Order</label>
                            <input type="number" name="sort_order" class="form-control" value="{{ $item->sort_order }}">
                        </div>

                        <div class="form-group form-check">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input"
                                id="portfolioActive{{ $item->id }}" {{ $item->is_active ? 'checked' : '' }}>
                            <label class="form-check-label" for="portfolioActive{{ $item->id }}">Active</label>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Update Portfolio Item</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endsection

@push('after-scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.delete-portfolio-item-form').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                Swal.fire({
                    title: 'Are you sure?',
                    text: 'This portfolio item will be deleted permanently.',
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