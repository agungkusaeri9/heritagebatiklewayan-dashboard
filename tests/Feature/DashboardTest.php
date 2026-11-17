<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $user = User::factory()->create();
        $team = $user->currentTeam;

        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = User::factory()->create();
        $team = $user->currentTeam;

        $response = $this
            ->actingAs($user)
            ->get(route('dashboard'));

        $response->assertOk();
    }

    public function test_admin_role_does_not_receive_orders_or_revenue_on_dashboard(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customerUser = User::factory()->create();
        $customer = Customer::create([
            'user_id' => $customerUser->id,
            'phone' => '08123456789',
            'is_active' => true,
        ]);

        Order::create([
            'order_number' => 'ORD-DASH-001',
            'customer_id' => $customer->id,
            'subtotal' => 150000,
            'shipping_fee' => 0,
            'total_amount' => 150000,
            'status' => 'success',
            'province_id' => 1,
            'city_id' => 1,
            'subdistrict_id' => 1,
            'address' => 'Jl. Slamet Riyadi No. 1',
            'shipping_name' => 'JNE Regular',
        ]);

        $this->assertFalse($admin->can('view orders'));
        $this->assertFalse($admin->can('view admin dashboard'));

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('summary.totalOrders', 0)
                ->where('summary.totalRevenue', 0)
                ->where('summary.totalCustomers', 1)
                ->has('recentOrders', 0)
            );
    }

    public function test_user_with_view_admin_dashboard_permission_sees_full_dashboard(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super admin');

        $customerUser = User::factory()->create();
        $customer = Customer::create([
            'user_id' => $customerUser->id,
            'phone' => '08123456789',
            'is_active' => true,
        ]);

        Order::create([
            'order_number' => 'ORD-DASH-003',
            'customer_id' => $customer->id,
            'subtotal' => 175000,
            'shipping_fee' => 0,
            'total_amount' => 175000,
            'status' => 'success',
            'province_id' => 1,
            'city_id' => 1,
            'subdistrict_id' => 1,
            'address' => 'Jl. Slamet Riyadi No. 3',
            'shipping_name' => 'JNE Regular',
        ]);

        $this->assertTrue($superAdmin->can('view admin dashboard'));

        $this->actingAs($superAdmin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('summary.totalOrders', 1)
                ->where('summary.totalRevenue', fn ($value) => (float) $value === 175000.0)
                ->where('summary.totalCustomers', 1)
                ->has('recentOrders', 1)
            );
    }

    public function test_admin_store_does_not_receive_global_orders_dashboard(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $seller = User::factory()->create();
        $seller->assignRole('admin store');

        $customerUser = User::factory()->create();
        $customer = Customer::create([
            'user_id' => $customerUser->id,
            'phone' => '08123456789',
            'is_active' => true,
        ]);

        Order::create([
            'order_number' => 'ORD-DASH-002',
            'customer_id' => $customer->id,
            'subtotal' => 200000,
            'shipping_fee' => 0,
            'total_amount' => 200000,
            'status' => 'success',
            'province_id' => 1,
            'city_id' => 1,
            'subdistrict_id' => 1,
            'address' => 'Jl. Slamet Riyadi No. 2',
            'shipping_name' => 'JNE Regular',
        ]);

        $this->actingAs($seller)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('summary.totalOrders', 0)
                ->where('summary.totalCustomers', 0)
                ->has('recentOrders', 0)
            );
    }
}
