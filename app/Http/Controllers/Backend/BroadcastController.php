<?php

namespace App\Http\Controllers\Backend;

use App\Domains\Auth\Models\Role;
use App\Domains\Auth\Models\User;
use App\Models\BroadcastAnnouncement;
use App\Models\BroadcastAnnouncementDelivery;
use App\Models\BroadcastNewsletter;
use App\Models\NewsletterAdminAlert;
use App\Services\BroadcastRichText;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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

    public function storeNewsletter(Request $request, BroadcastRichText $richText)
    {
        $data = $this->validateNewsletter($request, $richText);
        $month = Carbon::parse($data['issue_month'].'-01', self::TIMEZONE)->startOfMonth();
        abort_if($month->copy()->endOfMonth()->endOfDay()->isPast(), 422, 'Issue month is in the past.');

        BroadcastNewsletter::create([
            'title' => $data['title'],
            'body' => $data['body'],
            'body_format' => 'html',
            'issue_month' => $month->toDateString(),
            // Saving a draft never authorizes email delivery.
            'status' => BroadcastNewsletter::DRAFT,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.broadcast.newsletters.index')
            ->withFlashSuccess(__('Newsletter draft saved. Return to the draft and click Schedule when ready.'));
    }

    public function editNewsletter(Request $request, BroadcastNewsletter $newsletter, BroadcastRichText $richText)
    {
        if ($request->wantsJson()) {
            return response()->json([
                'id' => $newsletter->id,
                'title' => $newsletter->title,
                'body_html' => $richText->render($newsletter->body, $newsletter->body_format),
                'issue_month' => $newsletter->issue_month->format('Y-m'),
                'status' => $newsletter->status,
                'editable' => $newsletter->isEditable(),
                'update_url' => route('admin.broadcast.newsletters.update', $newsletter),
                'schedule_url' => $newsletter->isEditable()
                    ? route('admin.broadcast.newsletters.schedule', $newsletter) : null,
            ])->header('Cache-Control', 'private, no-store');
        }

        return view('backend.broadcast.newsletters.form', compact('newsletter'));
    }

    public function updateNewsletter(Request $request, BroadcastNewsletter $newsletter, BroadcastRichText $richText)
    {
        abort_unless($newsletter->isEditable(), 403);
        $data = $this->validateNewsletter($request, $richText, $newsletter);
        $month = Carbon::parse($data['issue_month'].'-01', self::TIMEZONE)->startOfMonth();
        abort_if($month->copy()->endOfMonth()->endOfDay()->isPast(), 422, 'Issue month is in the past.');

        DB::transaction(function () use ($newsletter, $data, $month) {
            $locked = BroadcastNewsletter::whereKey($newsletter->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->isEditable(), 403);
            $locked->update([
                'title' => $data['title'],
                'body' => $data['body'],
                'body_format' => 'html',
                'issue_month' => $month->toDateString(),
                // Editing an approved newsletter revokes that approval.
                'status' => BroadcastNewsletter::DRAFT,
                'scheduled_at' => null,
            ]);
        });

        return redirect()->route('admin.broadcast.newsletters.index')
            ->withFlashSuccess(__('Draft updated. Schedule again when ready.'));
    }

    public function scheduleNewsletter(BroadcastNewsletter $newsletter, BroadcastRichText $richText)
    {
        DB::transaction(function () use ($newsletter, $richText) {
            $locked = BroadcastNewsletter::whereKey($newsletter->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->isEditable(), 403);

            if ($locked->title === 'Untitled newsletter' || ! $richText->hasText($locked->body)) {
                throw ValidationException::withMessages([
                    'body' => __('Complete the newsletter title and content before scheduling.'),
                ]);
            }

            $endOfMonth = Carbon::parse($locked->issue_month->format('Y-m').'-01', self::TIMEZONE)
                ->endOfMonth()->setTime(18, 0, 0);
            abort_if($endOfMonth->isPast(), 422, 'Cannot schedule an issue for a past month.');

            $locked->update([
                'status' => BroadcastNewsletter::SCHEDULED,
                'scheduled_at' => $endOfMonth->utc(),
            ]);
        });

        return redirect()->route('admin.broadcast.newsletters.index')
            ->withFlashSuccess(__('Newsletter scheduled for the last day of the month at 6 PM Mountain time.'));
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

    private function validateNewsletter(
        Request $request,
        BroadcastRichText $richText,
        ?BroadcastNewsletter $newsletter = null
    ): array {
        $unique = Rule::unique('broadcast_newsletters', 'issue_month');
        if ($newsletter) {
            $unique->ignore($newsletter->id);
        }

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:180'],
            'body' => ['nullable', 'string', 'max:30000'],
            'issue_month' => ['required', 'date_format:Y-m'],
        ]);

        validator(['issue_month' => $data['issue_month'].'-01'], [
            'issue_month' => [$unique],
        ])->validate();

        $data['title'] = trim($data['title'] ?? '') ?: 'Untitled newsletter';
        $data['body'] = $richText->sanitize($data['body'] ?? '');

        return $data;
    }

    public function announcements()
    {
        return view('backend.broadcast.announcements.index');
    }

    private function userRoles()
    {
        return Role::where('type', User::TYPE_USER)->orderBy('name')->get();
    }

    public function createAnnouncement()
    {
        return view('backend.broadcast.announcements.create', [
            'roles' => $this->userRoles(),
            'announcement' => new BroadcastAnnouncement(),
        ]);
    }

    public function editAnnouncement(Request $request, BroadcastAnnouncement $announcement, BroadcastRichText $richText)
    {
        if ($request->wantsJson()) {
            return response()->json([
                'id' => $announcement->id,
                'title' => $announcement->title,
                'body_html' => $richText->render($announcement->body, $announcement->body_format),
                'audience' => $announcement->audience,
                'role_ids' => $announcement->role_ids ?? [],
                'status' => $announcement->status,
                'editable' => $announcement->isEditable(),
                'update_url' => route('admin.broadcast.announcements.update', $announcement),
                'publish_url' => $announcement->isEditable()
                    ? route('admin.broadcast.announcements.publish', $announcement) : null,
            ])->header('Cache-Control', 'private, no-store');
        }

        return view('backend.broadcast.announcements.create', [
            'roles' => $this->userRoles(),
            'announcement' => $announcement,
        ]);
    }

    public function storeAnnouncement(Request $request, BroadcastRichText $richText)
    {
        $publish = $request->input('intent', 'publish') === 'publish';
        $data = $this->validateAnnouncement($request, $richText, $publish);

        DB::transaction(function () use ($data, $publish, $request, $richText) {
            $announcement = BroadcastAnnouncement::create([
                'title' => $data['title'],
                'body' => $data['body'],
                'body_format' => 'html',
                'audience' => $data['audience'],
                'role_ids' => $data['role_ids'],
                'status' => BroadcastAnnouncement::DRAFT,
                'published_at' => null,
                'created_by' => $request->user()->id,
            ]);

            if ($publish) {
                $this->publishDraft($announcement, $richText);
            }
        });

        return redirect()->route('admin.broadcast.announcements.index')
            ->withFlashSuccess($publish
                ? __('Announcement published; email delivery will be processed by the scheduler.')
                : __('Announcement draft saved. You can finish and publish it later.'));
    }

    public function updateAnnouncement(
        Request $request,
        BroadcastAnnouncement $announcement,
        BroadcastRichText $richText
    ) {
        abort_unless($announcement->isEditable(), 403);
        $publish = $request->input('intent', 'draft') === 'publish';
        $data = $this->validateAnnouncement($request, $richText, $publish);

        DB::transaction(function () use ($announcement, $data, $publish, $richText) {
            $locked = BroadcastAnnouncement::whereKey($announcement->id)
                ->lockForUpdate()->firstOrFail();
            abort_unless($locked->isEditable(), 403);

            $locked->update([
                'title' => $data['title'],
                'body' => $data['body'],
                'body_format' => 'html',
                'audience' => $data['audience'],
                'role_ids' => $data['role_ids'],
            ]);

            if ($publish) {
                $this->publishDraft($locked, $richText);
            }
        });

        return redirect()->route('admin.broadcast.announcements.index')
            ->withFlashSuccess($publish
                ? __('Announcement published to the selected User groups.')
                : __('Announcement draft updated.'));
    }

    public function publishAnnouncement(BroadcastAnnouncement $announcement, BroadcastRichText $richText)
    {
        DB::transaction(function () use ($announcement, $richText) {
            $locked = BroadcastAnnouncement::whereKey($announcement->id)
                ->lockForUpdate()->firstOrFail();
            abort_unless($locked->isEditable(), 403);
            $this->publishDraft($locked, $richText);
        });

        return redirect()->route('admin.broadcast.announcements.index')
            ->withFlashSuccess(__('Announcement published to the selected User groups.'));
    }

    public function deleteAnnouncement(BroadcastAnnouncement $announcement)
    {
        DB::transaction(function () use ($announcement) {
            $locked = BroadcastAnnouncement::whereKey($announcement->id)
                ->lockForUpdate()->firstOrFail();
            abort_unless($locked->isEditable(), 403);
            $locked->delete();
        });

        return redirect()->route('admin.broadcast.announcements.index')
            ->withFlashSuccess(__('Announcement draft deleted.'));
    }

    private function validateAnnouncement(
        Request $request,
        BroadcastRichText $richText,
        bool $publish
    ): array {
        $data = $request->validate([
            'intent' => ['nullable', Rule::in(['draft', 'publish'])],
            'title' => [$publish ? 'required' : 'nullable', 'string', 'max:180'],
            'body' => [$publish ? 'required' : 'nullable', 'string', 'max:10000'],
            'audience' => ['required', Rule::in(['all_users', 'roles'])],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => [
                'integer',
                Rule::exists('roles', 'id')->where('type', User::TYPE_USER),
            ],
        ]);

        $ids = array_values(array_unique(array_map('intval', $data['role_ids'] ?? [])));
        if ($publish && $data['audience'] === 'roles' && $ids === []) {
            throw ValidationException::withMessages([
                'role_ids' => __('Select at least one User role before publishing.'),
            ]);
        }

        $data['role_ids'] = $data['audience'] === 'roles' ? $ids : [];
        $data['title'] = trim($data['title'] ?? '') ?: 'Untitled announcement';
        $data['body'] = $richText->sanitize($data['body'] ?? '');

        if ($publish && ! $richText->hasText($data['body'])) {
            throw ValidationException::withMessages([
                'body' => __('Write announcement content before publishing.'),
            ]);
        }

        return $data;
    }

    /**
     * Called inside a transaction after the draft is locked. The recipient
     * snapshot and published status become visible atomically at commit.
     */
    private function publishDraft(BroadcastAnnouncement $announcement, BroadcastRichText $richText): void
    {
        abort_unless($announcement->isEditable(), 403);

        if ($announcement->title === 'Untitled announcement'
            || ! $richText->hasText($announcement->body)) {
            throw ValidationException::withMessages([
                'body' => __('Complete the announcement before publishing.'),
            ]);
        }

        $ids = $announcement->role_ids ?? [];
        if ($announcement->audience === 'roles') {
            if ($ids === [] || Role::query()->where('type', User::TYPE_USER)
                ->whereIn('id', $ids)->count() !== count($ids)) {
                throw ValidationException::withMessages([
                    'role_ids' => __('Select valid User roles before publishing.'),
                ]);
            }
        }

        User::query()->where('type', User::TYPE_USER)
            ->where('active', true)->whereNotNull('email')
            ->whereNotNull('email_verified_at')
            ->when($announcement->audience === 'roles', function ($query) use ($ids) {
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

        $announcement->update([
            'status' => BroadcastAnnouncement::PUBLISHED,
            'published_at' => now(),
        ]);
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
