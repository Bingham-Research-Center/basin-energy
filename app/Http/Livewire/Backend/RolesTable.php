<?php

namespace App\Http\Livewire\Backend;

use App\Domains\Auth\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class RolesTable extends DataTableComponent
{
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

    public function columns(): array
    {
        return [
            Column::make(__('Type'), 'type')
                ->sortable(),

            Column::make(__('Name'), 'name')
                ->searchable()
                ->sortable(),

            Column::make(__('Permissions'))
                ->label(fn ($row) => $row->permissions->pluck('name')->join(', ') ?: '-'),

            Column::make(__('Number of Users'))
                ->label(fn ($row) => (string) $row->users_count)
                ->sortable(fn (Builder $query, string $direction) => $query->orderBy('users_count', $direction)),

            Column::make(__('Actions'))
                ->label(function ($row) {
                    $id = $row->id;

                    if (! $id) {
                        return '';
                    }

                    return '<a href="' . e(route('admin.auth.role.edit', ['role' => $id])) . '" class="btn btn-primary btn-sm mr-1">Edit</a>';
                })
                ->html(),
        ];
    }
}