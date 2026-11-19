<?php

namespace App\Http\Livewire\Backend;

use App\Domains\Auth\Models\Role;
use App\Domains\Auth\Models\User;
use App\Models\BroadcastAnnouncement;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class BroadcastAnnouncementsTable extends DataTableComponent
{
    /**
     * Role names are resolved once per Livewire request, rather than
     * querying the roles table for every announcement row.
     */
    private ?array $userRoleNames = null;

    private function audienceLabel(BroadcastAnnouncement $announcement): string
    {
        if ($announcement->audience === 'all_users') {
            return 'All Users';
        }

        if ($this->userRoleNames === null) {
            $this->userRoleNames = Role::query()
                ->where('type', User::TYPE_USER)
                ->pluck('name', 'id')
                ->all();
        }

        $names = [];
        foreach ($announcement->role_ids ?? [] as $id) {
            $id = (int) $id;
            $names[] = $this->userRoleNames[$id] ?? 'Unavailable role (ID '.$id.')';
        }

        $names = array_values(array_unique($names));

        return 'Selected User roles: '.($names ? implode(', ', $names) : 'None');
    }

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setAdditionalSelects([
            'broadcast_announcements.id', 'broadcast_announcements.title',
            'broadcast_announcements.audience', 'broadcast_announcements.role_ids',
            'broadcast_announcements.published_at', 'broadcast_announcements.status',
        ]);
        $this->setConfigurableAreas([]);
    }

    public function builder(): Builder
    {
        return BroadcastAnnouncement::query()->withCount([
            'deliveries as recipient_count',
            'deliveries as sent_count' => fn ($q) => $q->where('status', 'sent'),
            'deliveries as failed_count' => fn ($q) => $q->where('status', 'failed'),
            'deliveries as skipped_count' => fn ($q) => $q->where('status', 'skipped'),
        ]);
    }

    public function columns(): array
    {
        return [
            Column::make('Title', 'title')->searchable()->sortable(),
            Column::make('Audience', 'audience')
                ->label(fn ($row) => $this->audienceLabel($row)),
            Column::make('Published', 'published_at')
                ->sortable()
                ->label(fn ($row) => $row->status === BroadcastAnnouncement::PUBLISHED
                    ? optional($row->published_at)->format('M j, Y H:i') : '-'),
            Column::make('Email sent')
                ->label(fn ($row) => $row->status === BroadcastAnnouncement::PUBLISHED
                    && (int) $row->recipient_count === 0
                    ? '<span class="text-warning" role="status">No eligible recipients</span>'
                    : view('backend.broadcast.partials.delivery-progress', [
                        'sent' => $row->sent_count,
                        'total' => $row->recipient_count,
                        'failed' => $row->failed_count,
                        'skipped' => $row->skipped_count,
                    ])->render())->html(),
            Column::make('Status', 'status')
                ->sortable()
                ->label(fn ($row) => $row->status === BroadcastAnnouncement::DRAFT
                    ? 'Draft' : 'Published'),
            Column::make('Actions')
                ->label(function ($row) {
                    $url = e(route('admin.broadcast.announcements.edit', $row->id));
                    if (! $row->isEditable()) {
                        return '<a class="btn btn-sm btn-info" href="'.$url.'">View</a>';
                    }
                    return '<a class="btn btn-sm btn-primary mr-1" href="'.$url.
                        '" data-broadcast-edit="announcement">Edit draft</a>'.
                        '<form class="d-inline" data-broadcast-delete="announcement"'.
                        ' data-broadcast-id="'.$row->id.'" method="POST" action="'.
                        e(route('admin.broadcast.announcements.delete', $row->id)).'">'.
                        csrf_field().method_field('DELETE').
                        '<button type="submit" class="btn btn-sm btn-outline-danger"'.
                        '>Delete</button></form>';
                })->html(),
        ];
    }
}
