<?php

namespace Tests\Feature\Backend;

use Tests\TestCase;

/**
 * Keep Rappasoft Livewire user/role table colors readable in Light and Dark
 * themes without overriding other dashboard tables.
 */
class ManagementTableThemeTest extends TestCase
{
    public function test_user_management_tables_use_a_scoped_contrast_wrapper(): void
    {
        $this->loginAsAdmin();

        foreach ([
            route('admin.auth.user.index'),
            route('admin.auth.user.deactivated'),
            route('admin.auth.user.deleted'),
        ] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('brc-admin-data-table')
                ->assertSee('dashboard-theme.css?v=2');
        }
    }

    public function test_role_management_table_uses_a_scoped_contrast_wrapper(): void
    {
        $this->loginAsAdmin();

        $this->get(route('admin.auth.role.index'))
            ->assertOk()
            ->assertSee('brc-admin-data-table');
    }

    public function test_dashboard_theme_css_provides_explicit_light_and_dark_grid_contrast(): void
    {
        $css = file_get_contents(public_path('css/dashboard-theme.css'));

        $this->assertNotFalse($css);
        $this->assertStringContainsString(
            'body.c-app:not(.c-dark-theme) .brc-admin-data-table table > tbody > tr > td',
            $css
        );
        $this->assertStringContainsString(
            'body.c-app.c-dark-theme .brc-admin-data-table table > tbody > tr > td',
            $css
        );
        $this->assertStringContainsString('prefers-reduced-motion: reduce', $css);
    }
}
