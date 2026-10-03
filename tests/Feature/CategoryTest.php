<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_categories()
    {
        $response = $this->get(route('categories.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_categories_list()
    {
        $user = User::factory()->create();
        Category::factory()->count(3)->create();

        $response = $this->actingAs($user)->get(route('categories.index'));

        $response->assertOk();
    }

    public function test_authenticated_users_can_create_category_with_image()
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('batik-parang.jpg');

        $response = $this->actingAs($user)->post(route('categories.store'), [
            'name' => 'Batik Parang',
            'slug' => 'batik-parang',
            'description' => 'Kategori batik motif parang klasik',
            'image' => $file,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('categories.index'));
        $this->assertDatabaseHas('categories', [
            'name' => 'Batik Parang',
            'slug' => 'batik-parang',
            'description' => 'Kategori batik motif parang klasik',
            'is_active' => true,
        ]);

        $category = Category::where('slug', 'batik-parang')->first();
        $this->assertNotNull($category->image);
        Storage::disk('public')->assertExists($category->image);
    }

    public function test_authenticated_users_can_update_category()
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $category = Category::factory()->create([
            'name' => 'Batik Lawasan',
            'slug' => 'batik-lawasan',
        ]);

        $response = $this->actingAs($user)->put(route('categories.update', $category), [
            'name' => 'Batik Lawasan Modern',
            'slug' => 'batik-lawasan-modern',
            'description' => 'Deskripsi baru',
            'is_active' => false,
        ]);

        $response->assertRedirect(route('categories.index'));
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Batik Lawasan Modern',
            'slug' => 'batik-lawasan-modern',
            'is_active' => false,
        ]);
    }

    public function test_authenticated_users_can_delete_category()
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test.jpg');
        $path = $file->store('categories', 'public');

        $category = Category::factory()->create([
            'name' => 'Batik Kawung',
            'image' => $path,
        ]);

        Storage::disk('public')->assertExists($path);

        $response = $this->actingAs($user)->delete(route('categories.destroy', $category));

        $response->assertStatus(302);
        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);
        Storage::disk('public')->assertMissing($path);
    }
}
