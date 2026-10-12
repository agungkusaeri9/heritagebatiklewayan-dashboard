<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Database\Seeders\LocationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LocationSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_provinces_cities_and_subdistricts(): void
    {
        $this->seed(LocationSeeder::class);

        $this->assertDatabaseCount('provinces', 34);
        $this->assertDatabaseCount('cities', 501);
        $this->assertDatabaseCount('subdistricts', 6994);

        $this->assertDatabaseHas('cities', [
            'city_id' => 23,
            'province_id' => 9,
            'city_name' => 'Kota Bandung',
            'postal_code' => '40111',
        ]);
        $this->assertDatabaseHas('subdistricts', [
            'subdistrict_id' => 6994,
            'city_id' => 501,
            'subdistrict_name' => 'Wirobrajan',
        ]);
    }

    public function test_it_unescapes_quoted_names(): void
    {
        $this->seed(LocationSeeder::class);

        $this->assertSame(
            "Lhoknga (Lho'nga)",
            DB::table('subdistricts')->where('subdistrict_id', 36)->value('subdistrict_name'),
        );
        $this->assertSame(
            "O'o'u (Oou)",
            DB::table('subdistricts')->where('subdistrict_id', 4395)->value('subdistrict_name'),
        );
    }

    public function test_it_is_idempotent_and_restores_modified_rows(): void
    {
        $this->seed(LocationSeeder::class);

        DB::table('cities')->where('city_id', 23)->update(['city_name' => 'Changed']);

        $this->seed(LocationSeeder::class);

        $this->assertDatabaseCount('cities', 501);
        $this->assertDatabaseCount('subdistricts', 6994);
        $this->assertDatabaseHas('cities', ['city_id' => 23, 'city_name' => 'Kota Bandung']);
    }

    public function test_database_seeder_includes_locations(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('provinces', 34);
        $this->assertDatabaseCount('cities', 501);
        $this->assertDatabaseCount('subdistricts', 6994);
    }
}
