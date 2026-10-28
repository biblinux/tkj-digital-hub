<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResponsiveLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_pages_include_mobile_menu_and_table_helper(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->get('/dashboard')
            ->assertOk()
            ->assertSee('name="viewport"', false)
            ->assertSee('menu-toggle', false)
            ->assertSee('aria-controls="admin-menu"', false)
            ->assertSee('id="admin-menu"', false)
            ->assertSee('table.classList.add(\'stack\')', false);
    }

    public function test_stylesheet_has_phone_breakpoints_and_stacked_tables(): void
    {
        $css = file_get_contents(public_path('css/tkj.css'));

        $this->assertStringContainsString('@media (max-width: 820px)', $css);
        $this->assertStringContainsString('@media (max-width: 640px)', $css);
        $this->assertStringContainsString('table.stack', $css);
        $this->assertStringContainsString('attr(data-label)', $css);
    }

    public function test_guest_login_page_has_no_admin_menu(): void
    {
        $this->get('/login')->assertOk()->assertDontSee('id="admin-menu"', false);
    }
}
