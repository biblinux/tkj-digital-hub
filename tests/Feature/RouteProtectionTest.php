<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Tests\TestCase;

/**
 * Memastikan setiap route non-publik menuntut login.
 */
class RouteProtectionTest extends TestCase
{
    use RefreshDatabase;

    private const PUBLIC_NAMES = [
        'home', 'pages.profile', 'pages.competencies', 'pages.organization', 'pages.it-team',
        'pages.topology', 'pages.gallery', 'pages.facilities', 'pages.contact',
        'suggestions.create', 'suggestions.store',
        'activation.show', 'activation.activate',
        'login', 'login.store',
    ];

    public function test_every_non_public_route_requires_authentication(): void
    {
        $checked = 0;

        /** @var Route $route */
        foreach (RouteFacade::getRoutes() as $route) {
            $name = $route->getName();

            // Lewati route internal framework (storage, health check).
            if (! $name || str_starts_with($name, 'storage.') || in_array($name, self::PUBLIC_NAMES, true)) {
                continue;
            }

            $this->assertContains('auth', $route->gatherMiddleware(), "Route '{$name}' tidak dilindungi middleware auth.");
            $this->assertContains('active', $route->gatherMiddleware(), "Route '{$name}' tidak memeriksa akun aktif.");
            $checked++;
        }

        $this->assertGreaterThan(15, $checked);
    }

    public function test_guests_are_redirected_from_protected_urls(): void
    {
        foreach (['/dashboard', '/laporan', '/laporan/1', '/kelola/laporan', '/kelola/saran', '/admin/users', '/admin/users/create', '/admin/users/1'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }

    public function test_debug_route_is_gone(): void
    {
        $this->get('/admin-test')->assertNotFound();
    }

    public function test_user_management_routes_have_permission_middleware(): void
    {
        foreach (RouteFacade::getRoutes() as $route) {
            if (str_starts_with((string) $route->getName(), 'admin.users.')) {
                $this->assertContains('permission:manage_users', $route->gatherMiddleware(), $route->getName());
            }
        }
    }
}
