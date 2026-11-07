<?php

namespace App\Services;

use App\Models\AppConfig;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeoapifyService
{
    /**
     * Get Geoapify API Base URL (cached in file driver).
     */
    public function getBaseUrl(): string
    {
        return Cache::store('file')->remember('geoapify_base_url', 86400, function () {
            $url = AppConfig::getValue('geoapify_base_url') ?? env('GEOAPIFY_BASE_URL', 'https://api.geoapify.com');

            return ! empty($url) ? rtrim($url, '/') : 'https://api.geoapify.com';
        });
    }

    /**
     * Get Geoapify API Key (cached in file driver).
     */
    public function getApiKey(): ?string
    {
        return Cache::store('file')->remember('geoapify_api_key', 86400, function () {
            $key = AppConfig::getValue('geoapify_api_key')
                ?? env('GEOAPIFY_API_KEY', 'a5eede7836f74f4aa012b9ff68d7b5ec');

            return ! empty($key) ? trim((string) $key) : 'a5eede7836f74f4aa012b9ff68d7b5ec';
        });
    }

    /**
     * Clear Geoapify configuration cache.
     */
    public function clearCache(): void
    {
        Cache::store('file')->forget('geoapify_base_url');
        Cache::store('file')->forget('geoapify_api_key');
    }

    /**
     * Geocode an address to get latitude and longitude.
     *
     * @param  string  $address  Address or place query.
     * @return array<string, mixed>
     */
    public function geocode(string $address): array
    {
        $apiKey = $this->getApiKey();
        if (empty($apiKey)) {
            throw new \RuntimeException('GEOAPIFY_API_KEY belum dikonfigurasi di file environment atau konfigurasi aplikasi.', 500);
        }

        $endpoint = "{$this->getBaseUrl()}/v1/geocode/search";

        $response = Http::timeout(10)->get($endpoint, [
            'text' => $address,
            'apiKey' => $apiKey,
            'format' => 'json',
        ]);

        if (! $response->successful()) {
            Log::error('Geoapify geocode failed', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            return [];
        }

        return $response->json() ?? [];
    }

    /**
     * Reverse geocode latitude and longitude to get address details.
     *
     * @param  float  $lat  Latitude.
     * @param  float  $lon  Longitude.
     * @return array<string, mixed>
     */
    public function reverseGeocode(float $lat, float $lon): array
    {
        $apiKey = $this->getApiKey();
        if (empty($apiKey)) {
            throw new \RuntimeException('GEOAPIFY_API_KEY belum dikonfigurasi di file environment atau konfigurasi aplikasi.', 500);
        }

        $endpoint = "{$this->getBaseUrl()}/v1/geocode/reverse";

        $response = Http::timeout(10)->get($endpoint, [
            'lat' => $lat,
            'lon' => $lon,
            'apiKey' => $apiKey,
            'format' => 'json',
        ]);

        if (! $response->successful()) {
            Log::error('Geoapify reverseGeocode failed', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            return [];
        }

        return $response->json() ?? [];
    }

    /**
     * Reverse geocode latitude and longitude and return structured full address.
     *
     * @return array<string, mixed>|null
     */
    public function getFullAddress(float $lat, float $lon): ?array
    {
        $raw = $this->reverseGeocode($lat, $lon);
        if (empty($raw)) {
            return null;
        }

        $item = null;
        if (! empty($raw['results']) && is_array($raw['results']) && isset($raw['results'][0])) {
            $item = $raw['results'][0];
        } elseif (! empty($raw['features']) && is_array($raw['features']) && isset($raw['features'][0]['properties'])) {
            $item = $raw['features'][0]['properties'];
        }

        if (! is_array($item)) {
            return null;
        }

        $formatted = $item['formatted']
            ?? trim(($item['address_line1'] ?? '').', '.($item['address_line2'] ?? ''), ', ');

        return [
            'formatted_address' => $formatted ?: null,
            'address_line1' => $item['address_line1'] ?? null,
            'address_line2' => $item['address_line2'] ?? null,
            'street' => $item['street'] ?? null,
            'housenumber' => $item['housenumber'] ?? null,
            'suburb' => $item['suburb'] ?? $item['quarter'] ?? $item['neighbourhood'] ?? null,
            'district' => $item['district'] ?? $item['city_district'] ?? null,
            'city' => $item['city'] ?? $item['county'] ?? $item['town'] ?? $item['village'] ?? null,
            'state' => $item['state'] ?? null,
            'postcode' => $item['postcode'] ?? null,
            'country' => $item['country'] ?? null,
            'country_code' => $item['country_code'] ?? null,
            'latitude' => (float) ($item['lat'] ?? $lat),
            'longitude' => (float) ($item['lon'] ?? $lon),
        ];
    }
}
