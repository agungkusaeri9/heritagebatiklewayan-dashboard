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
            return AppConfig::getValue('geoapify_api_key') ?? env('GEOAPIFY_API_KEY');
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
}
