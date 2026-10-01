@if ($user->trashed() && $logged_in_user->hasAllAccess())
    <x-utils.form-button
        :action="$restoreUrl"
        method="patch"
        button-class="btn btn-info btn-sm"
        icon="fas fa-sync-alt"
        name="confirm-item"
    >
        @lang('Restore')
    </x-utils.form-button>

    @if (config('boilerplate.access.user.permanently_delete'))
        <x-utils.delete-button
            :href="$permanentlyDeleteUrl"
            :text="__('Permanently Delete')" />
    @endif
@else
    @if ($logged_in_user->hasAllAccess())
        <x-utils.view-button :href="$showUrl" />
        <x-utils.edit-button :href="$editUrl" />
    @endif

    @if (! $user->isActive())
        <x-utils.form-button
            :action="$reactivateUrl"
            method="patch"
            button-class="btn btn-primary btn-sm"
            icon="fas fa-sync-alt"
            name="confirm-item"
            permission="admin.access.user.reactivate"
        >
            @lang('Reactivate')
        </x-utils.form-button>
    @endif

    @if ($user->id !== $logged_in_user->id && !$user->isMasterAdmin() && $logged_in_user->hasAllAccess())
        <x-utils.delete-button :href="$destroyUrl" />
    @endif

    @if ($user->isMasterAdmin() && $logged_in_user->isMasterAdmin())
        <div class="dropdown d-inline-block">
            <a class="btn btn-sm btn-secondary dropdown-toggle" id="moreMenuLink-{{ $user->id }}" href="#" role="button" data-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false">
                @lang('More')
            </a>

            <div class="dropdown-menu" aria-labelledby="moreMenuLink-{{ $user->id }}">
                <x-utils.link
                    :href="$changePasswordUrl"
                    class="dropdown-item"
                    :text="__('Change Password')"
                    permission="admin.access.user.change-password" />
            </div>
        </div>
    @elseif (
        !$user->isMasterAdmin() &&
        $user->isActive() &&
        $user->id !== $logged_in_user->id &&
        (
            $logged_in_user->can('admin.access.user.change-password') ||
            $logged_in_user->can('admin.access.user.clear-session') ||
            $logged_in_user->can('admin.access.user.impersonate') ||
            $logged_in_user->can('admin.access.user.deactivate')
        )
    )
        <div class="dropdown d-inline-block">
            <a class="btn btn-sm btn-secondary dropdown-toggle" id="moreMenuLink-{{ $user->id }}" href="#" role="button" data-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false">
                @lang('More')
            </a>

            <div class="dropdown-menu" aria-labelledby="moreMenuLink-{{ $user->id }}">
                <x-utils.link
                    :href="$changePasswordUrl"
                    class="dropdown-item"
                    :text="__('Change Password')"
                    permission="admin.access.user.change-password" />

                @if ($user->id !== $logged_in_user->id && !$user->isMasterAdmin())
                    <x-utils.form-button
                        :action="$clearSessionUrl"
                        name="confirm-item"
                        button-class="dropdown-item"
                        permission="admin.access.user.clear-session"
                    >
                        @lang('Clear Session')
                    </x-utils.form-button>

                    @canBeImpersonated($user)
                        <x-utils.link
                            :href="$impersonateUrl"
                            class="dropdown-item"
                            :text="__('Login As ' . $user->name)"
                            permission="admin.access.user.impersonate" />
                    @endCanBeImpersonated

                    <x-utils.form-button
                        :action="$deactivateUrl"
                        method="patch"
                        name="confirm-item"
                        button-class="dropdown-item"
                        permission="admin.access.user.deactivate"
                    >
                        @lang('Deactivate')
                    </x-utils.form-button>
                @endif
            </div>
        </div>
    @endif
@endif