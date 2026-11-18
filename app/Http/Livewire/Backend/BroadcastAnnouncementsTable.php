<?php

namespace App\Http\Livewire\Backend;

use App\Models\BroadcastAnnouncement;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class BroadcastAnnouncementsTable extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setAdditionalSelects([
            'broadcast_announcements.id', 'broadcast_announcements.title',
            'broadcast_announcements.audience', 'broadcast_announcements.role_ids',
            'broadcast_announcements.published_at',
        ]);
        $this->setConfigurableAreas([]);
    }

    public function builder(): Builder
    {
        return BroadcastAnnouncement::query()->withCount([
            'deliveries as recipient_count',
            'deliveries as sent_count' => fn ($q) => $q->where('status', 'sent'),
        ]);
    }

    public function columns(): array
    {
        return [
            Column::make('Title', 'title')->searchable()->sortable(),
            Column::make('Audience', 'audience')
                ->label(fn ($row) => $row->audience === 'all_users'
                    ? 'All Users' : 'Selected User roles: '.implode(', ', $row->role_ids ?? [])),
            Column::make('Published', 'published_at')
                ->sortable()
                ->label(fn ($row) => $row->published_at->format('M j, Y H:i')),
            Column::make('Email sent')
                ->label(fn ($row) => $row->sent_count.' / '.$row->recipient_count),
            Column::make('Status')
                ->label(function ($row) {
                    $errors = $row->deliveries()->whereIn('status', ['failed', 'sending'])->count();
                    return $errors ? 'Some emails require review' : 'Published';
                }),
        ];
    }
}
