<?php

namespace Tests\Feature;

use App\Models\AppVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AppVersionTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_cannot_access_app_versions(): void
    {
        $response = $this->get(route('app-versions.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_app_versions_list(): void
    {
        $user = User::factory()->create();
        AppVersion::factory()->create([
            'type' => 'android',
            'version' => '9.0.1',
        ]);

        $response = $this->actingAs($user)->get(route('app-versions.index'));

        $response->assertOk();
    }

    public function test_authenticated_users_can_create_app_version_with_apk_file(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $file = UploadedFile::fake()->create('batik-lewayan-v2.0.0.apk', 1024, 'application/vnd.android.package-archive');

        $response = $this->actingAs($user)->post(route('app-versions.store'), [
            'name' => 'Heritage Batik Lewayan',
            'type' => 'android',
            'version' => '2.0.0',
            'version_code' => 20,
            'min_version' => '1.5.0',
            'file' => $file,
            'release_notes' => 'Pembaruan fitur katalog & checkout.',
            'is_force_update' => true,
            'is_maintenance' => false,
            'is_active' => true,
        ]);

        $response->assertRedirect();

        $appVersion = AppVersion::where('type', 'android')->where('version', '2.0.0')->first();
        $this->assertNotNull($appVersion);
        $this->assertSame('batik-lewayan-v2.0.0.apk', $appVersion->file_name);
        $this->assertTrue($appVersion->is_force_update);
        Storage::disk('public')->assertExists($appVersion->file_path);
    }

    public function test_authenticated_users_can_update_and_delete_app_version(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $oldPath = UploadedFile::fake()->create('old.apk', 500)->storeAs('apps', 'old.apk', 'public');

        $appVersion = AppVersion::factory()->create([
            'type' => 'android',
            'version' => '2.1.0',
            'version_code' => 21,
            'file_path' => $oldPath,
            'file_name' => 'old.apk',
        ]);

        $newFile = UploadedFile::fake()->create('new-ios.ipa', 2048);

        $response = $this->actingAs($user)->put(route('app-versions.update', $appVersion), [
            'name' => 'Heritage Batik Lewayan iOS',
            'type' => 'ios',
            'version' => '2.1.1',
            'version_code' => 22,
            'min_version' => '2.0.0',
            'file' => $newFile,
            'release_notes' => 'Rilis iOS.',
            'is_force_update' => false,
            'is_maintenance' => true,
            'maintenance_message' => 'Sedang maintenance.',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $appVersion->refresh();

        $this->assertSame('ios', $appVersion->type);
        $this->assertSame('2.1.1', $appVersion->version);
        $this->assertSame('new-ios.ipa', $appVersion->file_name);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($appVersion->file_path);

        $filePathToDelete = $appVersion->file_path;
        $deleteResponse = $this->actingAs($user)->delete(route('app-versions.destroy', $appVersion));

        $deleteResponse->assertRedirect();
        $this->assertDatabaseMissing('app_versions', ['id' => $appVersion->id]);
        Storage::disk('public')->assertMissing($filePathToDelete);
    }

    public function test_api_can_check_latest_app_version(): void
    {
        Storage::fake('public');
        $apkPath = UploadedFile::fake()->create('arbatik-v2.5.0.apk', 1024)->storeAs('apps', 'arbatik-v2.5.0.apk', 'public');

        $v1 = AppVersion::factory()->create([
            'type' => 'android',
            'version' => '3.0.0',
            'version_code' => 30,
            'min_version' => '2.5.0',
            'is_latest' => false,
            'is_force_update' => false,
            'is_active' => true,
        ]);

        $v2 = AppVersion::factory()->create([
            'type' => 'android',
            'version' => '2.5.0',
            'version_code' => 25,
            'min_version' => '2.5.0',
            'file_path' => $apkPath,
            'file_name' => 'arbatik-v2.5.0.apk',
            'is_latest' => true,
            'is_force_update' => false,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/app-versions/latest?type=android&version=2.0.0');

        $response->assertOk()
            ->assertJsonPath('data.id', $v2->id)
            ->assertJsonPath('data.type', 'android')
            ->assertJsonPath('data.version', '2.5.0')
            ->assertJsonPath('data.is_latest', true)
            ->assertJsonPath('data.needs_update', true)
            ->assertJsonPath('data.force_update', true);

        $downloadResponse = $this->get('/api/v1/app-versions/latest/download?type=android');
        $downloadResponse->assertOk();

        $user = User::factory()->create();
        $this->actingAs($user)->post(route('app-versions.set-latest', $v1))->assertRedirect();

        $this->assertTrue($v1->fresh()->is_latest);
        $this->assertFalse($v2->fresh()->is_latest);
    }
}
