<?php

namespace Tests\Feature\Api;

use App\Models\Banner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BannerApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_fetch_active_banners(): void
    {
        Banner::factory()->create([
            'title' => 'Active Banner 1',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Banner::factory()->create([
            'title' => 'Inactive Banner',
            'is_active' => false,
            'sort_order' => 2,
        ]);

        $response = $this->getJson(route('api.v1.banners.index'));

        $response->assertOk()
            ->assertJsonPath('meta.status', 'success')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Active Banner 1');
    }
}
