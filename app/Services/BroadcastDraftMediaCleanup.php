<?php

namespace App\Services;

use App\Models\BroadcastAnnouncement;
use App\Models\BroadcastNewsletter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Remove media that belonged exclusively to a discarded Broadcast draft.
 * Never delete images still referenced by other drafts or sent messages.
 */
class BroadcastDraftMediaCleanup
{
    public function deleteUnreferenced(?string $html): void
    {
        if (! $html || ! preg_match_all(
            '~/(?:broadcast/images)/([a-f0-9]{40}\.(?:jpg|png|gif|webp))~i',
            $html,
            $matches
        )) {
            return;
        }

        foreach (array_unique($matches[1]) as $filename) {
            if (BroadcastNewsletter::where('body', 'like', '%'.$filename.'%')->exists()
                || BroadcastAnnouncement::where('body', 'like', '%'.$filename.'%')->exists()) {
                continue;
            }

            try {
                Storage::disk('local')->delete('broadcast/images/'.$filename);
            } catch (Throwable $error) {
                // The draft has already been deleted; log an orphaned image
                // for later cleanup rather than resurrecting the draft.
                Log::warning('Broadcast draft media cleanup failed', [
                    'filename' => $filename,
                    'error' => $error->getMessage(),
                ]);
            }
        }
    }
}
