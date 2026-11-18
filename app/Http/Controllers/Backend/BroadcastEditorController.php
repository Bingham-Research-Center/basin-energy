<?php

namespace App\Http\Controllers\Backend;

use App\Models\BroadcastNewsletter;
use App\Models\BroadcastAnnouncement;
use App\Services\BroadcastRichText;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BroadcastEditorController
{
    /**
     * Autosaves are *always* drafts. They never schedule a newsletter,
     * publish an announcement, or enqueue delivery records.
     */
    public function autosave(Request $request, BroadcastRichText $richText)
    {
        $kind = $request->route('kind');
        abort_unless(in_array($kind, ['newsletter', 'announcement'], true), 404);

        if ($kind === 'newsletter') {
            $data = $request->validate([
                'id' => ['nullable', 'integer', 'exists:broadcast_newsletters,id'],
                'issue_month' => ['required', 'date_format:Y-m'],
                'title' => ['nullable', 'string', 'max:180'],
                'body' => ['nullable', 'string', 'max:30000'],
            ]);
            $month = Carbon::createFromFormat('!Y-m-d', $data['issue_month'].'-01', 'America/Denver');
            // Autosave only creates/updates a DRAFT. Historical or expired
            // issue months may still be edited without any email delivery.
            // scheduleNewsletter() is the sole gate for a send date.
            $draft = DB::transaction(function () use ($data, $month, $richText, $request) {
                $issue = isset($data['id'])
                    ? BroadcastNewsletter::whereKey($data['id'])->lockForUpdate()->firstOrFail()
                    : BroadcastNewsletter::whereDate('issue_month', $month->toDateString())->lockForUpdate()->first();

                if ($issue) {
                    // Never silently cancel an already scheduled issue, or
                    // edit content being emailed. Use explicit Save Draft.
                    abort_unless($issue->status === BroadcastNewsletter::DRAFT, 409);
                    if (! isset($data['id']) && $issue->created_by !== $request->user()->id) {
                        abort(409, 'A draft already exists for this issue month.');
                    }
                } else {
                    $issue = new BroadcastNewsletter();
                    $issue->issue_month = $month->toDateString();
                    $issue->status = BroadcastNewsletter::DRAFT;
                    $issue->created_by = $request->user()->id;
                }

                $issue->title = trim($data['title'] ?? '') ?: 'Untitled newsletter';
                $issue->body = $richText->sanitize($data['body'] ?? '');
                $issue->body_format = 'html';
                $issue->save();

                return $issue;
            });

            return response()->json([
                'id' => $draft->id,
                'status' => 'draft',
                'edit_url' => route('admin.broadcast.newsletters.edit', $draft),
                'schedule_url' => route('admin.broadcast.newsletters.schedule', $draft),
                'delete_url' => route('admin.broadcast.newsletters.delete', $draft),
                'saved_at' => now()->toIso8601String(),
            ])->header('Cache-Control', 'no-store');
        }

        $data = $request->validate([
            'id' => ['nullable', 'integer', 'exists:broadcast_announcements,id'],
            'title' => ['nullable', 'string', 'max:180'],
            'body' => ['nullable', 'string', 'max:10000'],
            'audience' => ['required', Rule::in(['all_users', 'roles'])],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => [
                'integer',
                Rule::exists('roles', 'id')->where('type', \App\Domains\Auth\Models\User::TYPE_USER),
            ],
        ]);

        $roles = $data['audience'] === 'roles'
            ? array_values(array_unique(array_map('intval', $data['role_ids'] ?? [])))
            : [];

        $draft = DB::transaction(function () use ($data, $roles, $richText, $request) {
            $item = isset($data['id'])
                ? BroadcastAnnouncement::whereKey($data['id'])->lockForUpdate()->firstOrFail()
                : new BroadcastAnnouncement();
            if ($item->exists) {
                abort_unless($item->status === BroadcastAnnouncement::DRAFT, 409);
            } else {
                $item->status = BroadcastAnnouncement::DRAFT;
                $item->created_by = $request->user()->id;
                // Legacy column is NOT NULL. Publication status is authoritative.
                $item->published_at = now();
            }
            $item->title = trim($data['title'] ?? '') ?: 'Untitled announcement';
            $item->body = $richText->sanitize($data['body'] ?? '');
            $item->body_format = 'html';
            $item->audience = $data['audience'];
            $item->role_ids = $roles;
            $item->save();

            return $item;
        });

        return response()->json([
            'id' => $draft->id,
            'status' => 'draft',
            'edit_url' => route('admin.broadcast.announcements.edit', $draft),
            'delete_url' => route('admin.broadcast.announcements.delete', $draft),
            'saved_at' => now()->toIso8601String(),
        ])->header('Cache-Control', 'no-store');
    }

    public function uploadImage(Request $request)
    {
        $data = $request->validate([
            'image' => ['required', 'file', 'image', 'mimes:jpeg,png,gif,webp', 'max:3072'],
        ]);
        $file = $data['image'];
        // Never trust the client filename or allow SVG/HTML uploads.
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];
        $mime = $file->getMimeType();
        abort_unless(isset($extensions[$mime]), 422, 'Unsupported image type.');
        $filename = bin2hex(random_bytes(20)).'.'.$extensions[$mime];
        Storage::disk('local')->putFileAs('broadcast/images', $file, $filename);

        return response()->json([
            'url' => route('frontend.broadcast.image', ['filename' => $filename]),
        ], 201)->header('Cache-Control', 'no-store');
    }

    public function showImage(string $filename)
    {
        abort_unless((bool) preg_match('/^[a-f0-9]{40}\.(jpg|png|gif|webp)$/', $filename), 404);
        $path = 'broadcast/images/'.$filename;
        abort_unless(Storage::disk('local')->exists($path), 404);
        $mime = [
            'jpg' => 'image/jpeg', 'png' => 'image/png',
            'gif' => 'image/gif', 'webp' => 'image/webp',
        ][pathinfo($filename, PATHINFO_EXTENSION)];

        return response(Storage::disk('local')->get($path), 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'public, max-age=2592000',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
