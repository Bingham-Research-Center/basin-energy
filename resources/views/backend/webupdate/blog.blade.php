@extends('backend.layouts.app')

@section('title', 'Update Blog Page')

@section('content')
    <x-backend.card>
        <x-slot name="header">
            Update Blog Page
        </x-slot>

        <x-slot name="body">
            <form action="{{ route('admin.website-update.blog.update') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="form-group mb-3">
                    <label>Page Title</label>
                    <input type="text" name="hero_title" class="form-control"
                        value="{{ old('hero_title', $hero->title ?? '') }}">
                </div>

                <hr>

                <div class="form-group mb-3">
                    <label>Sidebar Author Name</label>
                    <input type="text" name="sidebar_author_name" class="form-control"
                        value="{{ old('sidebar_author_name', $sidebarAuthor->title ?? '') }}">
                </div>

                <div class="form-group mb-3">
                    <label>Sidebar Author Description</label>
                    <textarea name="sidebar_author_description" class="form-control" rows="4">{{ old('sidebar_author_description', $sidebarAuthor->description ?? '') }}</textarea>
                </div>

                <div class="form-group mb-3">
                    <label>Sidebar Author Image</label>
                    <input type="file" name="sidebar_author_image" class="form-control">
                    @if(!empty($sidebarAuthor?->image))
                        <div class="mt-2">
                            <img src="{{ asset('storage/' . $sidebarAuthor->image) }}" alt="Sidebar Author"
                                style="max-width: 120px; border-radius: 6px;">
                        </div>
                    @endif
                </div>

                <button type="submit" class="btn btn-primary">Save Blog Page</button>
            </form>
        </x-slot>
    </x-backend.card>

    <x-backend.card class="mt-3">
        <x-slot name="header">
            Blog Categories
        </x-slot>

        <x-slot name="headerActions">
            <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#createBlogCategoryModal">
                Add Category
            </button>
        </x-slot>

        <x-slot name="body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Order</th>
                            <th>Status</th>
                            <th width="180">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($blogCategories as $category)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $category->name }}</td>
                                <td>{{ $category->slug }}</td>
                                <td>{{ $category->sort_order }}</td>
                                <td>
                                    @if($category->is_active)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info"
                                        data-toggle="modal" data-target="#viewBlogCategoryModal{{ $category->id }}">
                                        View
                                    </button>

                                    <button type="button" class="btn btn-sm btn-warning"
                                        data-toggle="modal" data-target="#editBlogCategoryModal{{ $category->id }}">
                                        Edit
                                    </button>

                                    <form action="{{ route('admin.website-update.blog.categories.delete', $category->id) }}"
                                        method="POST"
                                        class="d-inline delete-blog-category-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">No categories found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-slot>
    </x-backend.card>

    <x-backend.card class="mt-3">
        <x-slot name="header">
            Blog Posts
        </x-slot>

        <x-slot name="headerActions">
            <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#createBlogPostModal">
                Add Blog Post
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
                            <th>Slug</th>
                            <th>Author</th>
                            <th>Published</th>
                            <th>Categories</th>
                            <th>Status</th>
                            <th width="180">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($blogPosts as $post)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    @if($post->featured_image)
                                        <img src="{{ asset('storage/' . $post->featured_image) }}" alt="{{ $post->title }}"
                                            style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px;">
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $post->title }}</td>
                                <td>{{ $post->slug }}</td>
                                <td>{{ $post->author_name }}</td>
                                <td>{{ optional($post->published_at)->format('Y-m-d') }}</td>
                                <td>{{ $post->categories->isNotEmpty() ? $post->categories->pluck('name')->implode(', ') : '-' }}</td>
                                <td>
                                    @if($post->is_active)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info"
                                        data-toggle="modal" data-target="#viewBlogPostModal{{ $post->id }}">
                                        View
                                    </button>

                                    <button type="button" class="btn btn-sm btn-warning"
                                        data-toggle="modal" data-target="#editBlogPostModal{{ $post->id }}">
                                        Edit
                                    </button>

                                    <form action="{{ route('admin.website-update.blog.posts.delete', $post->id) }}"
                                        method="POST"
                                        class="d-inline delete-blog-post-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center">No blog posts found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-slot>
    </x-backend.card>

    <div class="modal fade" id="createBlogCategoryModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form action="{{ route('admin.website-update.blog.categories.store') }}" method="POST" class="modal-content">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title">Add Blog Category</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>

                <div class="modal-body">
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Slug</label>
                        <input type="text" name="slug" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Sort Order</label>
                        <input type="number" name="sort_order" class="form-control" value="0">
                    </div>

                    <div class="form-group form-check">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" id="createBlogCategoryActive" checked>
                        <label class="form-check-label" for="createBlogCategoryActive">Active</label>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save Category</button>
                </div>
            </form>
        </div>
    </div>

    @foreach($blogCategories as $category)
        <div class="modal fade" id="viewBlogCategoryModal{{ $category->id }}" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">View Blog Category</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <p><strong>Name:</strong> {{ $category->name }}</p>
                        <p><strong>Slug:</strong> {{ $category->slug }}</p>
                        <p><strong>Sort Order:</strong> {{ $category->sort_order }}</p>
                        <p><strong>Status:</strong> {{ $category->is_active ? 'Active' : 'Inactive' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="editBlogCategoryModal{{ $category->id }}" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <form action="{{ route('admin.website-update.blog.categories.update', $category->id) }}"
                    method="POST" class="modal-content">
                    @csrf
                    @method('PUT')

                    <div class="modal-header">
                        <h5 class="modal-title">Edit Blog Category</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>

                    <div class="modal-body">
                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" name="name" class="form-control" value="{{ $category->name }}" required>
                        </div>

                        <div class="form-group">
                            <label>Slug</label>
                            <input type="text" name="slug" class="form-control" value="{{ $category->slug }}">
                        </div>

                        <div class="form-group">
                            <label>Sort Order</label>
                            <input type="number" name="sort_order" class="form-control" value="{{ $category->sort_order }}">
                        </div>

                        <div class="form-group form-check">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input"
                                id="blogCategoryActive{{ $category->id }}" {{ $category->is_active ? 'checked' : '' }}>
                            <label class="form-check-label" for="blogCategoryActive{{ $category->id }}">Active</label>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Update Category</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach

    <div class="modal fade" id="createBlogPostModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <form action="{{ route('admin.website-update.blog.posts.store') }}"
                method="POST"
                enctype="multipart/form-data"
                class="modal-content">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title">Add Blog Post</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>

                <div class="modal-body">
                    <div class="form-group">
                        <label>Title</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Slug</label>
                        <input type="text" name="slug" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Excerpt</label>
                        <textarea name="excerpt" class="form-control summernote" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Content</label>
                        <textarea name="content" class="form-control summernote" rows="8"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Featured Image</label>
                        <input type="file" name="featured_image" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Author Name</label>
                        <input type="text" name="author_name" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Published At</label>
                        <input type="datetime-local" name="published_at" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Categories</label>
                        <div>
                            @forelse($blogCategories as $category)
                                <div class="form-check">
                                    <input type="checkbox"
                                        name="category_ids[]"
                                        value="{{ $category->id }}"
                                        class="form-check-input"
                                        id="createBlogCategory{{ $category->id }}">
                                    <label class="form-check-label" for="createBlogCategory{{ $category->id }}">
                                        {{ $category->name }}
                                    </label>
                                </div>
                            @empty
                                <p class="text-muted mb-0">No categories available. Please create one first.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Sort Order</label>
                        <input type="number" name="sort_order" class="form-control" value="0">
                    </div>

                    <div class="form-group form-check">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" id="createBlogPostActive" checked>
                        <label class="form-check-label" for="createBlogPostActive">Active</label>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save Blog Post</button>
                </div>
            </form>
        </div>
    </div>

    @foreach($blogPosts as $post)
        <div class="modal fade" id="viewBlogPostModal{{ $post->id }}" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">View Blog Post</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>

                    <div class="modal-body">
                        @if($post->featured_image)
                            <div class="mb-3">
                                <img src="{{ asset('storage/' . $post->featured_image) }}" alt="{{ $post->title }}"
                                    style="max-width: 200px; border-radius: 6px;">
                            </div>
                        @endif

                        <p><strong>Title:</strong> {{ $post->title }}</p>
                        <p><strong>Slug:</strong> {{ $post->slug }}</p>
                        <p><strong>Author:</strong> {{ $post->author_name }}</p>
                        <p><strong>Published:</strong> {{ optional($post->published_at)->format('Y-m-d H:i') }}</p>
                        <p><strong>Categories:</strong> {{ $post->categories->isNotEmpty() ? $post->categories->pluck('name')->implode(', ') : '-' }}</p>
                        <p><strong>Excerpt:</strong></p>
                        <div class="border rounded p-3 mb-3">
                            {!! $post->excerpt !!}
                        </div>

                        <p><strong>Content:</strong></p>
                        <div class="border rounded p-3">
                            {!! $post->content !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="editBlogPostModal{{ $post->id }}" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <form action="{{ route('admin.website-update.blog.posts.update', $post->id) }}"
                    method="POST"
                    enctype="multipart/form-data"
                    class="modal-content">
                    @csrf
                    @method('PUT')

                    <div class="modal-header">
                        <h5 class="modal-title">Edit Blog Post</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>

                    <div class="modal-body">
                        <div class="form-group">
                            <label>Title</label>
                            <input type="text" name="title" class="form-control" value="{{ $post->title }}" required>
                        </div>

                        <div class="form-group">
                            <label>Slug</label>
                            <input type="text" name="slug" class="form-control" value="{{ $post->slug }}">
                        </div>

                        <div class="form-group">
                            <label>Excerpt</label>
                            <textarea name="excerpt" class="form-control summernote" rows="3">{{ $post->excerpt }}</textarea>
                        </div>

                        <div class="form-group">
                            <label>Content</label>
                            <textarea name="content" class="form-control summernote" rows="8">{{ $post->content }}</textarea>
                        </div>

                        <div class="form-group">
                            <label>Featured Image</label>
                            <input type="file" name="featured_image" class="form-control">
                            @if($post->featured_image)
                                <div class="mt-2">
                                    <img src="{{ asset('storage/' . $post->featured_image) }}" alt="{{ $post->title }}"
                                        style="max-width: 120px; border-radius: 6px;">
                                </div>
                            @endif
                        </div>

                        <div class="form-group">
                            <label>Author Name</label>
                            <input type="text" name="author_name" class="form-control" value="{{ $post->author_name }}">
                        </div>

                        <div class="form-group">
                            <label>Published At</label>
                            <input type="datetime-local" name="published_at" class="form-control"
                                value="{{ $post->published_at ? $post->published_at->format('Y-m-d\TH:i') : '' }}">
                        </div>

                        <div class="form-group">
                            <label>Categories</label>
                            <div>
                                @forelse($blogCategories as $category)
                                    <div class="form-check">
                                        <input type="checkbox"
                                            name="category_ids[]"
                                            value="{{ $category->id }}"
                                            class="form-check-input"
                                            id="editBlogPostCategory{{ $post->id }}_{{ $category->id }}"
                                            {{ $post->categories->contains('id', $category->id) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="editBlogPostCategory{{ $post->id }}_{{ $category->id }}">
                                            {{ $category->name }}
                                        </label>
                                    </div>
                                @empty
                                    <p class="text-muted mb-0">No categories available.</p>
                                @endforelse
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Sort Order</label>
                            <input type="number" name="sort_order" class="form-control" value="{{ $post->sort_order }}">
                        </div>

                        <div class="form-group form-check">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input"
                                id="blogPostActive{{ $post->id }}" {{ $post->is_active ? 'checked' : '' }}>
                            <label class="form-check-label" for="blogPostActive{{ $post->id }}">Active</label>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Update Blog Post</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endsection

@push('after-scripts')
<script>
    $(document).ready(function () {
        $('.summernote').summernote({
            height: 250,
            placeholder: 'Write here...',
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'underline', 'clear']],
                ['fontname', ['fontname']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link', 'picture', 'video']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ]
        });

        document.querySelectorAll('.delete-blog-category-form').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                Swal.fire({
                    title: 'Are you sure?',
                    text: 'This blog category will be deleted permanently.',
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

        document.querySelectorAll('.delete-blog-post-form').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                Swal.fire({
                    title: 'Are you sure?',
                    text: 'This blog post will be deleted permanently.',
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