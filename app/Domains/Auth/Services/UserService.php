<?php

namespace App\Domains\Auth\Services;

use App\Domains\Auth\Events\User\UserCreated;
use App\Domains\Auth\Events\User\UserDeleted;
use App\Domains\Auth\Events\User\UserDestroyed;
use App\Domains\Auth\Events\User\UserRestored;
use App\Domains\Auth\Events\User\UserStatusChanged;
use App\Domains\Auth\Events\User\UserUpdated;
use App\Domains\Auth\Models\User;
use App\Exceptions\GeneralException;
use App\Services\BaseService;
use App\Services\AdminUserRegistrationNotifier;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Class UserService.
 */
class UserService extends BaseService
{
    /**
     * UserService constructor.
     *
     * @param  User  $user
     */
    public function __construct(User $user)
    {
        $this->model = $user;
    }

    /**
     * @param $type
     * @param  bool|int  $perPage
     * @return mixed
     */
    public function getByType($type, $perPage = false)
    {
        if (is_numeric($perPage)) {
            return $this->model::byType($type)->paginate($perPage);
        }

        return $this->model::byType($type)->get();
    }

    /**
     * @param  array  $data
     * @return mixed
     *
     * @throws GeneralException
     */
    public function registerUser(array $data = []): User
    {
        DB::beginTransaction();

        try {
            $user = $this->createUser($data);
            $this->assignDefaultRegistrationRole($user);
        } catch (Exception $e) {
            DB::rollBack();

            throw new GeneralException(__('There was a problem creating your account.'));
        }

        DB::commit();

        $this->notifySelfRegistration($user);

        return $user;
    }

    /**
     * @param $info
     * @param $provider
     * @return mixed
     *
     * @throws GeneralException
     */
    public function registerProvider($info, $provider): User
    {
        $providerId = (string) $info->id;
        $email = mb_strtolower(trim((string) $info->email));

        // Provider identifiers are not globally unique across OAuth services.
        $user = $this->model::where('provider', $provider)
            ->where('provider_id', $providerId)
            ->first();

        if ($user) {
            return $user;
        }

        DB::beginTransaction();

        try {
            // Never silently link an OAuth identity to a local account by
            // email: existing owners must authenticate normally first.
            if ($this->model::where('email', $email)->exists()) {
                throw new GeneralException(__('This email is already registered. Please sign in using your existing account.'));
            }

            $user = $this->createUser([
                'name' => $info->name,
                'email' => $email,
                'provider' => $provider,
                'provider_id' => $providerId,
                'email_verified_at' => now(),
            ]);
            $this->assignDefaultRegistrationRole($user);
        } catch (GeneralException $e) {
            DB::rollBack();
            throw $e;
        } catch (Exception $e) {
            DB::rollBack();
            throw new GeneralException(__('There was a problem connecting to :provider', ['provider' => $provider]));
        }

        DB::commit();

        $this->notifySelfRegistration($user);

        return $user;
    }

    /**
     * @param  array  $data
     * @return User
     *
     * @throws GeneralException
     * @throws \Throwable
     */
    public function store(array $data = []): User
    {
        DB::beginTransaction();

        try {
            $user = $this->createUser([
                'type' => $data['type'],
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'email_verified_at' => isset($data['email_verified']) && $data['email_verified'] === '1' ? now() : null,
                'active' => isset($data['active']) && $data['active'] === '1',
            ]);

            $user->syncRoles($data['roles'] ?? []);

            if (! config('boilerplate.access.user.only_roles')) {
                $user->syncPermissions($data['permissions'] ?? []);
            }
        } catch (Exception $e) {
            DB::rollBack();

            throw new GeneralException(__('There was a problem creating this user. Please try again.'));
        }

        event(new UserCreated($user));

        DB::commit();

        // They didn't want to auto verify the email, but do they want to send the confirmation email to do so?
        if (! isset($data['email_verified']) && isset($data['send_confirmation_email']) && $data['send_confirmation_email'] === '1') {
            $user->sendEmailVerificationNotification();
        }

        return $user;
    }

    /**
     * @param  User  $user
     * @param  array  $data
     * @return User
     *
     * @throws \Throwable
     */
    public function update(User $user, array $data = []): User
    {
        DB::beginTransaction();

        try {
            $user->update([
                'type' => $user->isMasterAdmin() ? $this->model::TYPE_ADMIN : $data['type'] ?? $user->type,
                'name' => $data['name'],
                'email' => $data['email'],
            ]);

            if (! $user->isMasterAdmin()) {
                // Replace selected roles/permissions
                $user->syncRoles($data['roles'] ?? []);

                if (! config('boilerplate.access.user.only_roles')) {
                    $user->syncPermissions($data['permissions'] ?? []);
                }
            }
        } catch (Exception $e) {
            DB::rollBack();

            throw new GeneralException(__('There was a problem updating this user. Please try again.'));
        }

        event(new UserUpdated($user));

        DB::commit();

        return $user;
    }

    /**
     * @param  User  $user
     * @param  array  $data
     * @return User
     */
    public function updateProfile(User $user, array $data = []): User
    {
        $user->name = $data['name'];
        $newEmail = $data['email'] ?? $user->email;
        $emailChanged = $user->canChangeEmail() && $user->email !== $newEmail;

        if ($emailChanged) {
            $user->email = $newEmail;
            $user->email_verified_at = null;
        }

        // Persist before generating the verification URL and sending the mail.
        // Only name/email are modified here; type, roles and permissions stay intact.
        $user->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
            session()->flash('resent', true);
        }

        return $user;
    }

    /**
     * @param  User  $user
     * @param $data
     * @param  bool  $expired
     * @return User
     *
     * @throws \Throwable
     */
    public function updatePassword(User $user, $data, $expired = false): User
    {
        if (isset($data['current_password'])) {
            throw_if(
                ! Hash::check($data['current_password'], $user->password),
                new GeneralException(__('That is not your old password.'))
            );
        }

        // Reset the expiration clock
        if ($expired) {
            $user->password_changed_at = now();
        }

        $user->password = $data['password'];

        return tap($user)->update();
    }

    /**
     * @param  User  $user
     * @param $status
     * @return User
     *
     * @throws GeneralException
     */
    public function mark(User $user, $status): User
    {
        if ($status === 0 && auth()->id() === $user->id) {
            throw new GeneralException(__('You can not do that to yourself.'));
        }

        if ($status === 0 && $user->isMasterAdmin()) {
            throw new GeneralException(__('You can not deactivate the administrator account.'));
        }

        $user->active = $status;

        if ($user->save()) {
            event(new UserStatusChanged($user, $status));

            return $user;
        }

        throw new GeneralException(__('There was a problem updating this user. Please try again.'));
    }

    /**
     * @param  User  $user
     * @return User
     *
     * @throws GeneralException
     */
    public function delete(User $user): User
    {
        if ($user->id === auth()->id()) {
            throw new GeneralException(__('You can not delete yourself.'));
        }

        if ($this->deleteById($user->id)) {
            event(new UserDeleted($user));

            return $user;
        }

        throw new GeneralException('There was a problem deleting this user. Please try again.');
    }

    /**
     * @param  User  $user
     * @return User
     *
     * @throws GeneralException
     */
    public function restore(User $user): User
    {
        if ($user->restore()) {
            event(new UserRestored($user));

            return $user;
        }

        throw new GeneralException(__('There was a problem restoring this user. Please try again.'));
    }

    /**
     * @param  User  $user
     * @return bool
     *
     * @throws GeneralException
     */
    public function destroy(User $user): bool
    {
        if ($user->forceDelete()) {
            event(new UserDestroyed($user));

            return true;
        }

        throw new GeneralException(__('There was a problem permanently deleting this user. Please try again.'));
    }

    /**
     * Password and first-time provider signups share the same role policy.
     * Backend-created users keep the explicit roles chosen by the admin.
     */
    private function assignDefaultRegistrationRole(User $user): void
    {
        $role = app(DefaultUserRoleService::class)->roleForRegistration();

        if ($role) {
            $user->assignRole($role);
        }
    }

    /**
     * Alerts are generated after the account has committed, for both
     * password registration and new Google accounts. Admin-created users
     * use store() and never reach this path. Notification outages must not
     * prevent a successful registration.
     */
    private function notifySelfRegistration(User $user): void
    {
        try {
            app(AdminUserRegistrationNotifier::class)->notify($user);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    /**
     * @param  array  $data
     * @return User
     */
    protected function createUser(array $data = []): User
    {
        return $this->model::create([
            'type' => $data['type'] ?? $this->model::TYPE_USER,
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'password' => $data['password'] ?? null,
            'provider' => $data['provider'] ?? null,
            'provider_id' => $data['provider_id'] ?? null,
            'email_verified_at' => $data['email_verified_at'] ?? null,
            'active' => $data['active'] ?? true,
        ]);
    }
}
