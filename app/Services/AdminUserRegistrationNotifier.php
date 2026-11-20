<?php

namespace App\Services;

use App\Domains\Auth\Models\User;
use App\Events\UserRegistrationAlertCreated;
use App\Models\UserRegistrationAdminAlert;
use Illuminate\Support\Facades\Log;
use Throwable;

class AdminUserRegistrationNotifier
{
    /**
     * Called only after self-registration has committed successfully.
     * Account creation and email verification must not depend on Reverb.
     */
    public function notify(User $newUser): void
    {
        if (! $newUser->exists || ! $newUser->isUser()) {
            return;
        }

        $createdFor = [];

        User::query()
            ->where('type', User::TYPE_ADMIN)
            ->where('active', true)
            ->select('id')
            ->chunkById(200, function ($admins) use ($newUser, &$createdFor) {
                foreach ($admins as $admin) {
                    // Match the User Management permission boundary.
                    if (! $admin->can('admin.access.user.list')) {
                        continue;
                    }

                    try {
                        $alert = UserRegistrationAdminAlert::firstOrCreate([
                            'admin_id' => $admin->id,
                            'user_id' => $newUser->id,
                        ]);

                        if ($alert->wasRecentlyCreated) {
                            $createdFor[] = $admin->id;
                        }
                    } catch (Throwable $exception) {
                        report($exception);
                        Log::warning('Unable to persist registration alert for an administrator', [
                            'admin_id' => $admin->id,
                            'user_id' => $newUser->id,
                        ]);
                    }
                }
            });

        if ($createdFor) {
            // Reverb is a best-effort refresh hint. The admin bell's existing
            // 20-second polling also works when Reverb is unavailable.
            try {
                event(new UserRegistrationAlertCreated($createdFor));
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }
}
