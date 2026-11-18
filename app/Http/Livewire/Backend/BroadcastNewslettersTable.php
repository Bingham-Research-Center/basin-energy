<?php

namespace App\Http\Livewire\Backend;

use App\Models\BroadcastNewsletter;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class BroadcastNewslettersTable extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setAdditionalSelects([
            'broadcast_newsletters.id', 'broadcast_newsletters.title',
            'broadcast_newsletters.issue_month', 'broadcast_newsletters.status',
            'broadcast_newsletters.scheduled_at', 'broadcast_newsletters.sent_at',
        ]);
        $this->setConfigurableAreas([]);
    }

    public function builder(): Builder
    {
        return BroadcastNewsletter::query()->withCount([
            'deliveries as total_recipients',
            'deliveries as sent_recipients' => fn ($q) => $q->where('status', 'sent'),
        ]);
    }

    public function columns(): array
    {
        return [
            Column::make('Month', 'issue_month')
                ->sortable()
                ->label(fn ($row) => $row->issue_month->format('F Y')),
            Column::make('Title', 'title')->searchable()->sortable(),
            Column::make('Status', 'status')->sortable()
                ->label(fn ($row) => ucfirst(str_replace('_', ' ', $row->status))),
            Column::make('Scheduled', 'scheduled_at')
                ->sortable()
                ->label(fn ($row) => $row->scheduled_at
                    ? $row->scheduled_at->timezone('America/Denver')->format('M j, Y g:i A T') : '-'),
            Column::make('Sent', 'sent_at')->sortable()
                ->label(fn ($row) => optional($row->sent_at)->format('M j, Y H:i') ?: '-'),
            Column::make('Recipients')
                ->label(fn ($row) => $row->sent_recipients.' / '.$row->total_recipients),
            Column::make('Actions')->label(function ($row) {
                $url = e(route('admin.broadcast.newsletters.edit', $row->id));
                $view = $row->isEditable()
                    ? '<a class="btn btn-sm btn-primary mr-1" href="'.$url.'" data-broadcast-edit="newsletter">Edit draft</a>'
                    : '<a class="btn btn-sm btn-info mr-1" href="'.$url.'">View</a>';
                if (! $row->isEditable()) {
                    return $view;
                }

                return $view.'<form class="d-inline" data-broadcast-delete="newsletter"'.
                    ' data-broadcast-id="'.$row->id.'" method="POST" action="'.
                    e(route('admin.broadcast.newsletters.delete', $row->id)).'">'.
                    csrf_field().method_field('DELETE').
                    '<button class="btn btn-sm btn-outline-danger" type="submit"'.
                    '>Delete</button></form>';
            })->html(),
        ];
    }
}
