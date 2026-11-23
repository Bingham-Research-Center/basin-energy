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
                ->assertSee('dashboard-tables.css?v=1');
        }
    }

    public function test_role_management_table_uses_a_scoped_contrast_wrapper(): void
    {
        $this->loginAsAdmin();

        $this->get(route('admin.auth.role.index'))
            ->assertOk()
            ->assertSee('brc-admin-data-table');
    }

    public function test_scoped_livewire_table_css_has_light_dark_and_responsive_contrast(): void
    {
        $css = file_get_contents(public_path('css/dashboard-tables.css'));

        $this->assertNotFalse($css);
        $this->assertStringContainsString('.brc-admin-data-table {', $css);
        $this->assertStringContainsString('body.c-app.c-dark-theme .brc-admin-data-table', $css);
        $this->assertStringContainsString('--brc-grid-surface:', $css);
        $this->assertStringContainsString('--brc-grid-text:', $css);
        $this->assertStringContainsString('max-width: 767.98px', $css);
        $this->assertStringContainsString('prefers-reduced-motion: reduce', $css);

        $layout = file_get_contents(resource_path('views/backend/layouts/app.blade.php'));
        $this->assertStringContainsString('css/dashboard-tables.css', $layout);
        $this->assertStringNotContainsString('tailwind', $layout);
    }
}
