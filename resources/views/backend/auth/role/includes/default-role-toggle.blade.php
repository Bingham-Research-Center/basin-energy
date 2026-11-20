@if($role->type === \App\Domains\Auth\Models\User::TYPE_USER)
    <div class="d-inline-flex align-items-center" wire:key="registration-default-role-{{ $role->id }}">
        <button
            type="button"
            class="brc-default-role-switch {{ $selected ? 'is-active' : '' }}"
            role="switch"
            aria-checked="{{ $selected ? 'true' : 'false' }}"
            aria-label="{{ $selected
                ? __('Remove :role as the default registration role', ['role' => $role->name])
                : __('Make :role the default registration role', ['role' => $role->name]) }}"
            title="{{ $selected ? __('Click to clear the default role') : __('Click to assign this role to new registrations') }}"
            wire:click="toggleDefaultRole({{ (int) $role->id }})"
            wire:loading.attr="disabled"
            wire:target="toggleDefaultRole"
        >
            <span class="brc-default-role-switch__thumb" aria-hidden="true"></span>
        </button>
        <span class="small ml-2 {{ $selected ? 'font-weight-bold' : 'text-muted' }}">
            {{ $selected ? __('Default') : __('Off') }}
        </span>
    </div>
@else
    <span class="text-muted" aria-label="{{ __('Not applicable to Administrator roles') }}">—</span>
@endif
