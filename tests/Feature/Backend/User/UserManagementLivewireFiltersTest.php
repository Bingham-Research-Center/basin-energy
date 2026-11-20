<?php

namespace Tests\Feature\Backend\User;

use App\Domains\Auth\Models\User;
use App\Http\Livewire\Backend\UsersTable;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementLivewireFiltersTest extends TestCase
{
    public function test_filter_keys_match_the_user_table_query_keys(): void
    {
        // Rappasoft v3 otherwise generates user_type and e_mail_verified
        // from labels, while UsersTable::builder() expects type/verified.
        $filters = collect((new UsersTable())->filters());

        $this->assertSame('type', $filters['type']->getKey());
        $this->assertSame('active', $filters['active']->getKey());
        $this->assertSame('verified', $filters['verified']->getKey());
    }

    public function test_user_type_filter_livewire_results_exclude_admin_accounts_and_can_be_cleared(): void
    {
        User::factory()->admin()->create([
            'name' => 'OnlyAdminFilterSentinel',
        ]);
        User::factory()->user()->create([
            'name' => 'OnlyRegularFilterSentinel',
        ]);

        $table = Livewire::actingAs($this->getMasterAdmin())
            ->test(UsersTable::class);

        // Demonstrate that filter selection changes the database result rows,
        // not merely the Applied Filters pill.
        $table->call('setFilter', 'type', User::TYPE_USER)
            ->assertSee('OnlyRegularFilterSentinel')
            ->assertDontSee('OnlyAdminFilterSentinel');

        $table->call('setFilter', 'type', User::TYPE_ADMIN)
            ->assertSee('OnlyAdminFilterSentinel')
            ->assertDontSee('OnlyRegularFilterSentinel');

        $table->call('setFilter', 'type', '')
            ->assertSee('OnlyAdminFilterSentinel')
            ->assertSee('OnlyRegularFilterSentinel');
    }

    public function test_email_verified_filter_restricts_actual_livewire_rows(): void
    {
        User::factory()->user()->create([
            'name' => 'VerifiedFilterSentinel',
            'email_verified_at' => now(),
        ]);
        User::factory()->user()->create([
            'name' => 'UnverifiedFilterSentinel',
            'email_verified_at' => null,
        ]);

        $table = Livewire::actingAs($this->getMasterAdmin())
            ->test(UsersTable::class);

        $table->call('setFilter', 'verified', 'yes')
            ->assertSee('VerifiedFilterSentinel')
            ->assertDontSee('UnverifiedFilterSentinel');

        $table->call('setFilter', 'verified', 'no')
            ->assertSee('UnverifiedFilterSentinel')
            ->assertDontSee('VerifiedFilterSentinel');

        $table->call('setFilter', 'verified', '')
            ->assertSee('VerifiedFilterSentinel')
            ->assertSee('UnverifiedFilterSentinel');
    }

    public function test_type_and_verification_filters_work_together(): void
    {
        User::factory()->admin()->create([
            'name' => 'AdminVerifiedCombinedSentinel',
            'email_verified_at' => now(),
        ]);
        User::factory()->user()->create([
            'name' => 'RegularVerifiedCombinedSentinel',
            'email_verified_at' => now(),
        ]);
        User::factory()->user()->create([
            'name' => 'RegularNotVerifiedCombinedSentinel',
            'email_verified_at' => null,
        ]);

        Livewire::actingAs($this->getMasterAdmin())
            ->test(UsersTable::class)
            ->call('setFilter', 'type', User::TYPE_USER)
            ->call('setFilter', 'verified', 'yes')
            ->assertSee('RegularVerifiedCombinedSentinel')
            ->assertDontSee('AdminVerifiedCombinedSentinel')
            ->assertDontSee('RegularNotVerifiedCombinedSentinel');
    }

    public function test_deactivated_user_view_respects_active_filter(): void
    {
        User::factory()->user()->inactive()->create([
            'name' => 'DeactivatedFilterSentinel',
        ]);
        User::factory()->user()->active()->create([
            'name' => 'ActiveFilterSentinel',
        ]);

        Livewire::actingAs($this->getMasterAdmin())
            ->test(UsersTable::class, ['status' => 'deactivated'])
            ->call('setFilter', 'active', 'no')
            ->assertSee('DeactivatedFilterSentinel')
            ->assertDontSee('ActiveFilterSentinel');
    }
}
