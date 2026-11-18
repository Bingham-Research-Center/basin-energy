<?php

namespace App\Http\Controllers\Backend;

use App\Domains\Auth\Models\Role;
use App\Domains\Auth\Models\User;
use App\Models\BroadcastAnnouncement;
use App\Models\BroadcastAnnouncementDelivery;
use App\Models\BroadcastNewsletter;
use App\Models\NewsletterAdminAlert;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Throwable;

class BroadcastController
{
    private const TIMEZONE = 'America/Denver';

    public function newsletters()
    {
        return view('backend.broadcast.newsletters.index');
    }

    public function createNewsletter()
    {
        return view('backend.broadcast.newsletters.form', ['newsletter' => new BroadcastNewsletter()]);
    }

    public function storeNewsletter(Request $request)
    {
        $data = $this->validateNewsletter($request);
        $month = Carbon::parse($data['issue_month'].'-01', self::TIMEZONE)->startOfMonth();
        abort_if($month->copy()->endOfMonth()->endOfDay()->isPast(), 422, 'Issue month is in the past.');

        $newsletter = BroadcastNewsletter::create([
            'title' => $data['title'],
            'body' => $data['body'],
            'issue_month' => $month->toDateString(),
            'status' => BroadcastNewsletter::DRAFT,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.broadcast.newsletters.edit', $newsletter)
            ->withFlashSuccess(__('Newsletter draft saved. Use Schedule to approve delivery.'));
    }

    public function editNewsletter(BroadcastNewsletter $newsletter)
    {
        return view('backend.broadcast.newsletters.form', compact('newsletter'));
    }

    public function updateNewsletter(Request $request, BroadcastNewsletter $newsletter)
    {
        abort_unless($newsletter->isEditable(), 403);
        $data = $this->validateNewsletter($request, $newsletter);

        $month = Carbon::parse($data['issue_month'].'-01', self::TIMEZONE)->startOfMonth();
        abort_if($month->copy()->endOfMonth()->endOfDay()->isPast(), 422, 'Issue month is in the past.');

        $newsletter->update([
            'title' => $data['title'],
            'body' => $data['body'],
            'issue_month' => $month->toDateString(),
            // Updating an approved issue returns it to a draft; the admin
            // must approve the new content by explicitly scheduling again.
            'status' => BroadcastNewsletter::DRAFT,
            'scheduled_at' => null,
        ]);

        return back()->withFlashSuccess(__('Draft updated. Schedule again when ready.'));
    }

    public function scheduleNewsletter(BroadcastNewsletter $newsletter)
    {
        DB::transaction(function () use ($newsletter) {
            $locked = BroadcastNewsletter::whereKey($newsletter->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->isEditable(), 403);

            $endOfMonth = Carbon::parse($locked->issue_month->format('Y-m').'-01', self::TIMEZONE)
                ->endOfMonth()->setTime(18, 0, 0);
            abort_if($endOfMonth->isPast(), 422, 'Cannot schedule an issue for a past month.');

            $locked->update([
                'status' => BroadcastNewsletter::SCHEDULED,
                'scheduled_at' => $endOfMonth->utc(),
            ]);
        });

        return back()->withFlashSuccess(__('Newsletter scheduled for the last day of the month at 6 PM Mountain time.'));
    }

    public function deleteNewsletter(BroadcastNewsletter $newsletter)
    {
        DB::transaction(function () use ($newsletter) {
            $locked = BroadcastNewsletter::whereKey($newsletter->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->isEditable(), 403);
            $locked->delete();
        });

        return redirect()->route('admin.broadcast.newsletters.index')
            ->withFlashSuccess(__('Draft or scheduled newsletter deleted.'));
    }

    private function validateNewsletter(Request $request, ?BroadcastNewsletter $newsletter = null): array
    {
        $unique = Rule::unique('broadcast_newsletters', 'issue_month');
        if ($newsletter) {
            $unique->ignore($newsletter->id);
        }

        // Issue months use YYYY-MM, stored as the first of the month.
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'body' => ['required', 'string', 'max:30000'],
            'issue_month' => ['required', 'date_format:Y-m'],
        ]);
        $month = $data['issue_month'].'-01';
        validator(
            ['issue_month' => $month],
            ['issue_month' => [$unique]]
        )->validate();

        return $data;
    }

    public function announcements()
    {
        return view('backend.broadcast.announcements.index');
    }

    public function createAnnouncement()
    {
        return view('backend.broadcast.announcements.create', [
            'roles' => Role::where('type', User::TYPE_USER)->orderBy('name')->get(),
        ]);
    }

    public function storeAnnouncement(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'body' => ['required', 'string', 'max:10000'],
            'audience' => ['required', Rule::in(['all_users', 'roles'])],
            'role_ids' => ['required_if:audience,roles', 'array'],
            'role_ids.*' => [
                'integer',
                Rule::exists('roles', 'id')->where('type', User::TYPE_USER),
            ],
        ]);

        $ids = array_values(array_unique(array_map('intval', $data['role_ids'] ?? [])));
        if ($data['audience'] === 'roles') {
            abort_if(empty($ids), 422, 'Select at least one User role.');
        } else {
            $ids = [];
        }

        $announcement = DB::transaction(function () use ($data, $ids, $request) {
            $announcement = BroadcastAnnouncement::create([
                'title' => $data['title'],
                'body' => $data['body'],
                'audience' => $data['audience'],
                'role_ids' => $ids,
                'published_at' => now(),
                'created_by' => $request->user()->id,
            ]);

            User::query()->where('type', User::TYPE_USER)
                ->where('active', true)->whereNotNull('email')
                ->whereNotNull('email_verified_at')
                ->when($data['audience'] === 'roles', function ($query) use ($ids) {
                    $query->whereHas('roles', fn ($roles) => $roles->whereIn('roles.id', $ids));
                })
                ->select('id', 'email')->chunkById(200, function ($users) use ($announcement) {
                    foreach ($users as $user) {
                        BroadcastAnnouncementDelivery::firstOrCreate(
                            ['announcement_id' => $announcement->id, 'user_id' => $user->id],
                            ['email' => $user->email, 'status' => 'pending']
                        );
                    }
                });

            return $announcement;
        });

        return redirect()->route('admin.broadcast.announcements.index')
            ->withFlashSuccess(__('Announcement published to the selected User roles. Email delivery will be processed by the scheduler.'));
    }

    public function alerts(Request $request)
    {
        return response()->json([
            'count' => NewsletterAdminAlert::where('admin_id', $request->user()->id)
                ->whereNull('read_at')->count(),
            'items' => NewsletterAdminAlert::where('admin_id', $request->user()->id)
                ->whereNull('read_at')->with('subscriber:id,name')
                ->latest('id')->take(5)->get()->map(function ($alert) {
                    return [
                        'id' => $alert->id,
                        'name' => $alert->subscriber->name ?? __('Deleted user'),
                        'created_at' => $alert->created_at->format('Y-m-d H:i'),
                    ];
                }),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function markAlertsRead(Request $request)
    {
        NewsletterAdminAlert::where('admin_id', $request->user()->id)
            ->whereNull('read_at')->update(['read_at' => now()]);

        return redirect()->route('admin.broadcast.newsletters.index')
            ->withFlashSuccess(__('Subscription notifications marked as read.'));
    }
}
