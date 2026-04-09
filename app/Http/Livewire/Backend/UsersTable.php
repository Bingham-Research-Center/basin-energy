<?php

namespace App\Http\Livewire\Backend;

use App\Domains\Auth\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class UsersTable extends DataTableComponent
{
    public $status = 'active';

    public array $sortNames = [
        'email_verified_at' => 'Verified',
    ];

    public array $filterNames = [
        'type' => 'User Type',
        'active' => 'Active',
        'verified' => 'E-mail Verified',
    ];

    public function mount($status = 'active'): void
    {
        $this->status = $status;
    }

    public function configure(): void
    {
        $this->setPrimaryKey('id');

        $this->setAdditionalSelects([
            'users.id',
            'users.type',
            'users.name',
            'users.email',
            'users.email_verified_at',
            'users.active',
            'users.deleted_at',
        ]);

        $this->setConfigurableAreas([]);
    }

    public function builder(): Builder
    {
        $query = User::query()
            ->with(['roles:id,name', 'permissions:id,name']);

        if ($this->status === 'deleted') {
            $query->onlyTrashed();
        } elseif ($this->status === 'deactivated') {
            $query->onlyDeactivated();
        } else {
            $query->onlyActive();
        }

        return $query
            ->when($this->getAppliedFilterWithValue('search'), fn (Builder $query, $term) => $query->search($term))
            ->when($this->getAppliedFilterWithValue('type'), fn (Builder $query, $type) => $query->where('type', $type))
            ->when($this->getAppliedFilterWithValue('active'), fn (Builder $query, $active) => $query->where('active', $active === 'yes'))
            ->when(
                $this->getAppliedFilterWithValue('verified'),
                fn (Builder $query, $verified) => $verified === 'yes'
                    ? $query->whereNotNull('email_verified_at')
                    : $query->whereNull('email_verified_at')
            );
    }

    public function filters(): array
    {
        return [
            'type' => SelectFilter::make('User Type')
                ->options([
                    '' => 'Any',
                    User::TYPE_ADMIN => 'Administrators',
                    User::TYPE_USER => 'Users',
                ]),

            'active' => SelectFilter::make('Active')
                ->options([
                    '' => 'Any',
                    'yes' => 'Yes',
                    'no' => 'No',
                ]),

            'verified' => SelectFilter::make('E-mail Verified')
                ->options([
                    '' => 'Any',
                    'yes' => 'Yes',
                    'no' => 'No',
                ]),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make(__('Type'), 'type')
                ->sortable(),

            Column::make(__('Name'), 'name')
                ->searchable()
                ->sortable(),

            Column::make(__('E-mail'), 'email')
                ->searchable()
                ->sortable(),

            Column::make(__('Verified'), 'email_verified_at')
                ->sortable()
                ->label(fn ($row) => $row->email_verified_at ? $row->email_verified_at->format('Y-m-d H:i:s') : '-'),

            Column::make(__('Roles'))
                ->label(fn ($row) => $row->roles->pluck('name')->join(', ') ?: '-'),

            Column::make(__('Additional Permissions'))
                ->label(fn ($row) => $row->permissions->pluck('name')->join(', ') ?: '-'),

            Column::make(__('Actions'))
    ->label(function ($row) {
        $id = $row->id;

        if (! $id) {
            return '';
        }

        $loggedInUser = auth()->user();
        $buttons = [];

        // Deleted users: only restore + permanent delete
        if ($this->status === 'deleted' || $row->trashed()) {
            if ($loggedInUser && $loggedInUser->hasAllAccess()) {
                $buttons[] = '
                    <form method="POST" action="' . e(route('admin.auth.user.restore', ['deletedUser' => $id])) . '" class="d-inline mr-1">
                        ' . csrf_field() . '
                        ' . method_field('PATCH') . '
                        <button type="submit" class="btn btn-warning btn-sm">Restore</button>
                    </form>
                ';

                if (config('boilerplate.access.user.permanently_delete')) {
                    $buttons[] = '
                        <form method="POST" action="' . e(route('admin.auth.user.permanently-delete', ['deletedUser' => $id])) . '" class="d-inline mr-1">
                            ' . csrf_field() . '
                            ' . method_field('DELETE') . '
                            <button type="submit" class="btn btn-danger btn-sm">Permanent Delete</button>
                        </form>
                    ';
                }
            }

            return implode(' ', $buttons);
        }

        if ($loggedInUser && $loggedInUser->hasAllAccess()) {
            $buttons[] = '<a href="' . e(route('admin.auth.user.show', ['user' => $id])) . '" class="btn btn-info btn-sm mr-1">View</a>';
            $buttons[] = '<a href="' . e(route('admin.auth.user.edit', ['user' => $id])) . '" class="btn btn-primary btn-sm mr-1">Edit</a>';
        }

        if (
            $loggedInUser &&
            $id !== $loggedInUser->id &&
            ! $row->isMasterAdmin() &&
            $loggedInUser->hasAllAccess()
        ) {
            $buttons[] = '
                <form method="POST" action="' . e(route('admin.auth.user.destroy', ['user' => $id])) . '" class="d-inline mr-1">
                    ' . csrf_field() . '
                    ' . method_field('DELETE') . '
                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                </form>
            ';
        }

        $moreItems = [];

        if ($loggedInUser && $loggedInUser->can('admin.access.user.change-password')) {
            $moreItems[] = '
                <a href="' . e(route('admin.auth.user.change-password', ['user' => $id])) . '" class="dropdown-item">
                    Change Password
                </a>
            ';
        }

        if (
            $loggedInUser &&
            $id !== $loggedInUser->id &&
            ! $row->isMasterAdmin()
        ) {
            if ($loggedInUser->can('admin.access.user.clear-session')) {
                $moreItems[] = '
                    <form method="POST" action="' . e(route('admin.auth.user.clear-session', ['user' => $id])) . '" class="m-0">
                        ' . csrf_field() . '
                        <button type="submit" class="dropdown-item">Clear Session</button>
                    </form>
                ';
            }

            if ($loggedInUser->can('admin.access.user.impersonate')) {
                $moreItems[] = '
                    <a href="' . e(route('impersonate', $id)) . '" class="dropdown-item">
                        Login As ' . e($row->name) . '
                    </a>
                ';
            }

            if ($row->isActive() && $loggedInUser->can('admin.access.user.deactivate')) {
                $moreItems[] = '
                    <form method="POST" action="' . e(route('admin.auth.user.mark', ['user' => $id, 'status' => 0])) . '" class="m-0">
                        ' . csrf_field() . '
                        ' . method_field('PATCH') . '
                        <button type="submit" class="dropdown-item">Deactivate</button>
                    </form>
                ';
            }

            if (! $row->isActive() && $loggedInUser->can('admin.access.user.reactivate')) {
                $moreItems[] = '
                    <form method="POST" action="' . e(route('admin.auth.user.mark', ['user' => $id, 'status' => 1])) . '" class="m-0">
                        ' . csrf_field() . '
                        ' . method_field('PATCH') . '
                        <button type="submit" class="dropdown-item">Reactivate</button>
                    </form>
                ';
            }
        }

        if (! empty($moreItems)) {
            $buttons[] = '
                <div class="dropdown d-inline-block mr-1">
                    <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false">
                        More
                    </button>
                    <div class="dropdown-menu">
                        ' . implode('', $moreItems) . '
                    </div>
                </div>
            ';
        }

        return implode(' ', $buttons);
    })
    ->html(),
        ];
    }
}