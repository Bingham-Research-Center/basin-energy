<?php

namespace Tests\Feature;

use App\Domains\Auth\Models\User;
use Tests\TestCase;

class DashboardBrandIdentityTest extends TestCase
{
    public function test_admin_dashboard_uses_bingham_research_center_branding(): void
    {
        $this->loginAsAdmin();

        $this->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('img/brand/brc-usu-logo.png')
            ->assertSee('css/dashboard-brand.css?v=2')
            ->assertSee('Bingham Research Center, Utah State University')
            ->assertSee('brc-dashboard-brand-minimized', false)
            ->assertSee('brc-mobile-brand', false)
            ->assertDontSee('img/brand/coreui.svg');
    }

    public function test_user_dashboard_uses_the_same_branding_without_coreui(): void
    {
        $this->actingAs(User::factory()->user()->create());

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('img/brand/brc-usu-logo.png')
            ->assertSee('css/dashboard-brand.css?v=2')
            ->assertSee('Bingham Research Center, Utah State University')
            ->assertSee('brc-dashboard-brand-minimized', false)
            ->assertSee('brc-mobile-brand', false)
            ->assertDontSee('img/brand/coreui.svg');
    }

    public function test_supplied_logo_asset_is_a_valid_png(): void
    {
        $path = public_path('img/brand/brc-usu-logo.png');
        $this->assertFileExists($path);

        $image = getimagesize($path);
        $this->assertNotFalse($image);
        $this->assertSame(IMAGETYPE_PNG, $image[2]);
        $this->assertSame(480, $image[0]);
        $this->assertSame(89, $image[1]);
    }

    public function test_all_four_dashboard_brand_locations_use_shared_partial(): void
    {
        foreach ([
            'backend/includes/sidebar.blade.php',
            'backend/includes/header.blade.php',
            'frontend/includes/sidebar.blade.php',
            'frontend/includes/header.blade.php',
        ] as $file) {
            $blade = file_get_contents(resource_path('views/'.$file));
            $this->assertStringContainsString(
                "includes.partials.dashboard-brand",
                $blade,
                $file.' must include the shared logo.'
            );
            $this->assertStringNotContainsString(
                "coreui.svg",
                $blade,
                $file.' should not render CoreUI branding.'
            );
        }
    }
    public function test_dashboard_logo_has_no_white_tile_and_adapts_to_dark_surfaces(): void
    {
        $css = file_get_contents(public_path('css/dashboard-brand.css'));
        $blade = file_get_contents(resource_path('views/includes/partials/dashboard-brand.blade.php'));

        $this->assertStringContainsString('background: transparent;', $css);
        $this->assertStringNotContainsString('background: #fff;', $css);
        $this->assertStringContainsString('.brc-sidebar-brand .brc-brand-image', $css);
        $this->assertStringContainsString('filter: brightness(0) invert(1);', $css);
        $this->assertStringContainsString('html[data-brc-resolved-theme="dark"] .brc-mobile-brand', $css);
        // The original transparent blue PNG is rendered once at each size.
        // CSS controls white/dark appearance; no duplicate black image.
        $this->assertSame(2, substr_count($blade, "asset('img/brand/brc-usu-logo.png')"));
        $this->assertStringNotContainsString('brc-brand-black', $blade);
    }

}
