@if ($general->where('type', $type)->count())
    <h5 class="mb-3">@lang('General Permissions')</h5>

    <div class="row">
        <div class="col">
            @foreach($general->where('type', $type) as $permission)
                <span class="d-block">
                        <input type="checkbox" name="permissions[]" {{ in_array($permission->id, $usedPermissions ?? [], true) ? 'checked' : '' }} value="{{ $permission->id }}" id="{{ $permission->id }}" x-bind:disabled="userType !== '{{ $type }}'" />
                        <label for="{{ $permission->id }}">{{ $permission->description ?? $permission->name }}</label>
                    </span>
            @endforeach
        </div><!--col-->
    </div><!--row-->
@endif

@if ($general->where('type', $type)->count() && $categories->where('type', $type)->count())
    <hr/>
@endif

@if ($categories->where('type', $type)->count())
    @if ($type === $model::TYPE_ADMIN)
        <h5 class="mb-3">@lang('Permission Categories')</h5>
    @endif

    <ul class="permission-tree m-0 p-0 list-unstyled">
        @foreach($categories->where('type', $type) as $permission)
            <li>
                @if ($type === $model::TYPE_USER && $permission->name === 'user.data')
                    {{-- Data Categories is a heading, not a broad permission grant. --}}
                    <h5 class="mb-3">{{ $permission->description ?? 'Data Categories' }}</h5>
                @else
                    <input type="checkbox" name="permissions[]" {{ in_array($permission->id, $usedPermissions ?? [], true) ? 'checked' : '' }} value="{{ $permission->id }}" id="{{ $permission->id }}" x-bind:disabled="userType !== '{{ $type }}'" />
                    <label for="{{ $permission->id }}">{{ $permission->description ?? $permission->name }}</label>
                @endif

                @if($permission->children->count())
                    @include('backend.auth.role.includes.children', ['children' => $permission->children, 'type' => $type, 'flush' => $type === $model::TYPE_USER && $permission->name === 'user.data'])
                @endif
            </li>
        @endforeach
    </ul>
@endif

@if (!$general->where('type', $type)->count() && !$categories->where('type', $type)->count())
    <p class="mb-0"><em>@lang('There are no additional permissions to choose from for this type.')</em></p>
@endif
