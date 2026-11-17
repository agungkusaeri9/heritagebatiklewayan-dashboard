<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRoutePrefixTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_and_home_routes_do_not_use_dashboard_prefix(): void
    {
        $this->assertSame('/login', route('login', absolute: false));
        $this->assertSame('/', route('home', absolute: false));

        $this->get('/')->assertOk();
        $this->get('/login')->assertOk();
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml');
    }

    public function test_admin_panel_routes_use_dashboard_prefix(): void
    {
        $this->assertSame('/dashboard', route('dashboard', absolute: false));

        $prefixedRouteNames = [
            'users.index' => '/dashboard/users',
            'customers.index' => '/dashboard/customers',
            'payment-methods.index' => '/dashboard/payment-methods',
            'couriers.index' => '/dashboard/couriers',
            'stores.index' => '/dashboard/stores',
            'store-profile.index' => '/dashboard/store-profile',
            'categories.index' => '/dashboard/categories',
            'banners.index' => '/dashboard/banners',
            'variant-types.index' => '/dashboard/variant-types',
            'products.index' => '/dashboard/products',
            'activity.index' => '/dashboard/activity',
            'locations.provinces' => '/dashboard/locations',
            'api.locations.cities' => '/dashboard/api/locations/cities',
            'api.locations.subdistricts' => '/dashboard/api/locations/subdistricts',
            'orders.index' => '/dashboard/orders',
            'store-settings.index' => '/dashboard/store-settings',
            'privacy-policy.index' => '/dashboard/privacy-policy-settings',
            'landing-page.index' => '/dashboard/landing-page-settings',
            'faqs.index' => '/dashboard/faqs',
            'app-versions.index' => '/dashboard/app-versions',
            'notifications.index' => '/dashboard/notifications',
            'notification-settings.index' => '/dashboard/notification-settings',
            'virtual-try-on-models.index' => '/dashboard/virtual-try-on-models',
            'product-virtual-try-ons.index' => '/dashboard/product-virtual-try-ons',
            'fashn-ai.settings' => '/dashboard/fashn-ai/settings',
            'configurations.index' => '/dashboard/configurations',
            'app-configs.index' => '/dashboard/app-configs',
            'roles.index' => '/dashboard/roles',
            'permissions.index' => '/dashboard/permissions',
            'profile.edit' => '/dashboard/settings/profile',
            'security.edit' => '/dashboard/settings/security',
            'appearance.edit' => '/dashboard/settings/appearance',
        ];

        foreach ($prefixedRouteNames as $routeName => $expectedPath) {
            $this->assertSame($expectedPath, route($routeName, absolute: false));
        }
    }

    public function test_dashboard_settings_redirects_to_prefixed_profile_settings(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/dashboard/settings')
            ->assertRedirect('/dashboard/settings/profile');
    }

    public function test_old_unprefixed_admin_urls_return_not_found(): void
    {
        $user = User::factory()->create();

        foreach (['/users', '/orders', '/products', '/settings', '/settings/profile'] as $legacyPath) {
            $this->actingAs($user)
                ->get($legacyPath)
                ->assertNotFound();
        }
    }
}
