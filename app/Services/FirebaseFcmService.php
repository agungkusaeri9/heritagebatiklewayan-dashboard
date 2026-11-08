<?php

namespace App\Services;

use App\Models\AppConfig;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebaseFcmService
{
    /**
     * Kirim Push Notification ke satu atau banyak FCM token menggunakan FCM HTTP v1 API.
     *
     * @param string|array $fcmTokens
     * @param string $title
     * @param string $body
     * @param array $data
     * @return bool
     */
    public function sendNotification(string|array $fcmTokens, string $title, string $body, array $data = []): bool
    {
        $tokens = is_array($fcmTokens) ? $fcmTokens : [$fcmTokens];
        $tokens = array_filter(array_unique($tokens));

        if (empty($tokens)) {
            return false;
        }

        $serviceAccountJson = AppConfig::where('key', 'firebase_service_account_json')->value('value');
        if (! $serviceAccountJson) {
            Log::warning('FCM: Service Account JSON belum dikonfigurasi di AppConfig.');
            return false;
        }

        $credentials = json_decode($serviceAccountJson, true);
        if (! $credentials || ! isset($credentials['private_key'], $credentials['client_email'], $credentials['project_id'])) {
            Log::error('FCM: Format Service Account JSON tidak valid.');
            return false;
        }

        $accessToken = $this->getAccessToken($credentials);
        if (! $accessToken) {
            Log::error('FCM: Gagal mendapatkan OAuth2 Access Token dari Google.');
            return false;
        }

        $projectId = $credentials['project_id'];
        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        $success = true;
        foreach ($tokens as $token) {
            $payload = [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => array_map('strval', $data),
                    'android' => [
                        'notification' => [
                            'sound' => 'default',
                            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        ],
                    ],
                ],
            ];

            $response = Http::withToken($accessToken)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, $payload);

            if (! $response->successful()) {
                Log::error("FCM Error for token {$token}: " . $response->body());
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Generate Google OAuth2 Access Token dari Service Account JSON menggunakan JWT (tanpa google/apiclient external package).
     */
    protected function getAccessToken(array $credentials): ?string
    {
        try {
            $now = time();
            $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
            $payload = json_encode([
                'iss' => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => $credentials['token_uri'],
                'iat' => $now,
                'exp' => $now + 3600,
            ]);

            $base64UrlHeader = $this->base64UrlEncode($header);
            $base64UrlPayload = $this->base64UrlEncode($payload);

            $signatureInput = $base64UrlHeader . "." . $base64UrlPayload;
            $signature = '';

            $privateKey = $credentials['private_key'];
            if (! openssl_sign($signatureInput, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
                return null;
            }

            $base64UrlSignature = $this->base64UrlEncode($signature);
            $jwt = $signatureInput . "." . $base64UrlSignature;

            $response = Http::asForm()->post($credentials['token_uri'], [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if ($response->successful()) {
                return $response->json('access_token');
            }

            Log::error('FCM OAuth2 Error: ' . $response->body());
            return null;
        } catch (\Throwable $e) {
            Log::error('FCM OAuth2 Exception: ' . $e->getMessage());
            return null;
        }
    }

    protected function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
