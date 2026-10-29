<?php

namespace App\Services;

use App\Models\AppConfig;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RajaOngkirService
{
    protected string $apiKey;

    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = (string) (AppConfig::getValue('rajaongkir_api_key') ?? config('services.rajaongkir.api_key') ?? env('RAJA_ONGKIR_API_KEY', ''));
        $this->baseUrl = rtrim((string) (AppConfig::getValue('rajaongkir_url') ?? config('services.rajaongkir.url') ?? env('RAJA_ONGKIR_URL', 'https://rajaongkir.komerce.id')), '/');
    }

    /**
     * Calculate domestic shipping cost using RajaOngkir Komerce API.
     *
     * @param  array{
     *     origin: int|string,
     *     destination: int|string,
     *     weight: int|float,
     *     courier: string,
     *     price?: bool|string
     * }  $params
     * @return array<string, mixed>
     */
    public function calculateDomesticCost(array $params): array
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('RAJA_ONGKIR_API_KEY belum dikonfigurasi di file environment.', 500);
        }

        $endpoint = "{$this->baseUrl}/api/v1/calculate/domestic-cost";

        $price = $params['price'] ?? 'lowest';
        $priceParam = 'lowest';

        if ($price === true || $price === 'true' || $price === 1 || $price === '1' || $price === 'lowest') {
            $priceParam = 'lowest';
        } elseif ($price === 'highest') {
            $priceParam = 'highest';
        } elseif ($price === false || $price === 'false' || $price === 0 || $price === '0') {
            $priceParam = null;
        } elseif (in_array(strtolower((string) $price), ['lowest', 'highest'], true)) {
            $priceParam = strtolower((string) $price);
        }

        $payload = [
            'origin' => (string) $params['origin'],
            'destination' => (string) $params['destination'],
            'weight' => (int) $params['weight'],
            'courier' => (string) $params['courier'],
        ];

        if ($priceParam !== null) {
            $payload['price'] = $priceParam;
        }

        /** @var Response $response */
        $response = Http::asForm()
            ->withHeaders([
                'key' => $this->apiKey,
            ])
            ->timeout(15)
            ->connectTimeout(5)
            ->post($endpoint, $payload);

        $responseData = $response->json();

        if ($response->failed() || (isset($responseData['meta']['code']) && $responseData['meta']['code'] >= 400)) {
            $errorMessage = $responseData['meta']['message']
                ?? $responseData['message']
                ?? 'Gagal menghitung ongkos kirim dari RajaOngkir.';

            $statusCode = $response->status() >= 400 ? $response->status() : (int) ($responseData['meta']['code'] ?? 400);

            Log::error('RajaOngkir calculateDomesticCost failed', [
                'endpoint' => $endpoint,
                'payload' => $payload,
                'status' => $statusCode,
                'response' => $responseData,
            ]);

            throw new \RuntimeException($errorMessage, $statusCode);
        }

        return $responseData ?? [];
    }
}
