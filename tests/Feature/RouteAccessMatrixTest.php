<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/** Validate route declarations as well as the request-level guard tests in the feature suites. */
class RouteAccessMatrixTest extends TestCase
{
    public function test_private_route_groups_do_not_drift_from_the_role_matrix(): void
    {
        $checked = 0;
        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();
            $middleware = $route->gatherMiddleware();
            if (str_starts_with($uri, 'pelanggan/') || str_starts_with($uri, 'pesanan/')) {
                foreach (['auth:customer', 'active.customer'] as $guard) {
                    $this->assertContains($guard, $middleware, $uri);
                }
            } elseif ($uri === 'owner' || str_starts_with($uri, 'admin/') || str_starts_with($uri, 'staf/')) {
                $this->assertContains('auth:staff', $middleware, $uri);
                $expected = match (true) {
                    str_starts_with($uri, 'staf/produksi') => 'role:owner,admin,staf_produksi',
                    str_starts_with($uri, 'staf/kurir') => 'role:owner,admin,staf_kurir',
                    str_starts_with($uri, 'admin/katalog') && !str_ends_with($uri, '/manage-units'),
                    str_starts_with($uri, 'admin/laporan'), str_starts_with($uri, 'admin/aktivitas'),
                    str_starts_with($uri, 'admin/pengaturan/tujuan-bayar'),
                    in_array($uri, ['admin/pengaturan', 'admin/pengaturan/update'], true), str_ends_with($uri, '/potong-deposit') => 'role:owner',
                    default => 'role:owner,admin',
                };
                $this->assertContains($expected, $middleware, $uri);
            } else {
                continue;
            }
            $checked++;
        }
        $this->assertGreaterThan(50, $checked);
    }
}
