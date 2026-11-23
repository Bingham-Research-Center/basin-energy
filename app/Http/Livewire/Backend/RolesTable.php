<?php

namespace App\Http\Livewire\Backend;

use App\Domains\Auth\Models\Role;
use App\Domains\Auth\Services\DefaultUserRoleService;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class RolesTable extends DataTableComponent
{
    /**
     * Column definitions are set up before Livewire actions execute. Look up
     * the default lazily when rendering labels, and invalidate the cached
     * value immediately after a successful toggle so the same response
     * renders the new selected/off state without a browser refresh.
     */
    private bool $defaultRoleLoadedForRender = false;

    private ?int $defaultRoleIdForRender = null;

    private function defaultRoleIdForRender(): ?int
    {
        if (! $this->defaultRoleLoadedForRender) {
            $this->defaultRoleIdForRender = app(DefaultUserRoleService::class)->currentRoleId();
            $this->defaultRoleLoadedForRender = true;
        }

        return $this->defaultRoleIdForRender;
    }

    public function configure(): void
    {
        $this->setPrimaryKey('id');

        $this->setAdditionalSelects([
            'roles.id',
            'roles.type',
            'roles.name',
        ]);

        $this->setConfigurableAreas([]);
    }

    public function builder(): Builder
    {
        return Role::query()
            ->with(['permissions:id,name'])
            ->withCount('users');
    }

    /**
     * Livewire actions are not covered by the page route's role middleware.
     * Recheck authorization server-side for every toggle request.
     */
    public function toggleDefaultRole(int $roleId): void
    {
        abort_unless(auth()->user()?->hasAllAccess(), 403);

        $selectedRoleId = app(DefaultUserRoleService::class)->toggle($roleId);

        // The columns were prepared before this action, but their labels are
        // evaluated when the table re-renders. Update the render cache now.
        $this->defaultRoleIdForRender = $selectedRoleId;
        $this->defaultRoleLoadedForRender = true;

        $message = $selectedRoleId === null
            ? __('Default user role cleared.')
            : __(':role is now the default user role.', [
                'role' => Role::query()->whereKey($selectedRoleId)->value('name'),
            ]);

        // Scoped browser event; toast is shown only once the DB write succeeds.
        $this->dispatch('brc-default-role-updated', message: $message);
    }

    public function columns(): array
    {
        return [
            Column::make(__('Type'), 'type')
                ->sortable()
                ->label(fn ($row) => view('backend.includes.table-pill', [
                    'text' => ucfirst((string) $row->type),
                    'tone' => $row->type === 'admin' ? 'admin' : 'neutral',
                ])->render())->html(),

            Column::make(__('Name'), 'name')
                ->searchable()
                ->sortable(),

            Column::make(__('Permissions'))
                ->label(fn ($row) => $row->permissions->pluck('name')->join(', ') ?: '-'),

            Column::make(__('Default User Role'))
                ->label(fn ($row) => view('backend.auth.role.includes.default-role-toggle', [
                    'role' => $row,
                    'selected' => $this->defaultRoleIdForRender() === (int) $row->id,
                ])->render())
                ->html()
                ->excludeFromColumnSelect(),

            Column::make(__('Number of Users'))
                ->label(fn ($row) => view('backend.includes.table-pill', [
                    'text' => (string) $row->users_count,
                    'tone' => 'info',
                ])->render())->html()
                ->sortable(fn (Builder $query, string $direction) => $query->orderBy('users_count', $direction)),

            Column::make(__('Actions'))
                ->label(function ($row) {
                    $id = $row->id;

                    if (! $id) {
                        return '';
                    }

                    return '<a href="' . e(route('admin.auth.role.edit', ['role' => $id])) . '" class="btn btn-primary btn-sm mr-1">Edit</a>';
                })
                ->html()
                ->excludeFromColumnSelect(),
        ];
    }
}