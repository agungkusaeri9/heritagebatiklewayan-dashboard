<?php

namespace App\Services;

use App\Models\AppConfig;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FashnAiService
{
    /**
     * Get Fashn AI API Base URL (cached in file driver).
     */
    public function getBaseUrl(): string
    {
        return Cache::store('file')->remember('fashn_api_base_url', 86400, function () {
            $url = AppConfig::getValue('fashn_api_base_url', 'https://api.fashn.ai');

            return ! empty($url) ? rtrim($url, '/') : 'https://api.fashn.ai';
        });
    }

    /**
     * Get Fashn AI API Key (cached in file driver).
     */
    public function getApiKey(): ?string
    {
        return Cache::store('file')->remember('fashn_api_key', 86400, function () {
            return AppConfig::getValue('fashn_api_key');
        });
    }

    /**
     * Get configured video duration in seconds (cached in file driver).
     * Supported: 5 or 10. Default: 5.
     */
    public function getVideoDuration(): int
    {
        return (int) Cache::store('file')->remember('fashn_video_duration', 86400, function () {
            $val = (int) AppConfig::getValue('fashn_video_duration', 5);

            return in_array($val, [5, 10]) ? $val : 5;
        });
    }

    /**
     * Get configured video resolution (cached in file driver).
     * Supported: 480p, 720p, 1080p. Default: 480p.
     */
    public function getVideoResolution(): string
    {
        return Cache::store('file')->remember('fashn_video_resolution', 86400, function () {
            $val = (string) AppConfig::getValue('fashn_video_resolution', '480p');

            return in_array($val, ['480p', '720p', '1080p']) ? $val : '480p';
        });
    }

    /**
     * Clear Fashn AI configuration cache.
     */
    public function clearCache(): void
    {
        Cache::store('file')->forget('fashn_api_base_url');
        Cache::store('file')->forget('fashn_api_key');
        Cache::store('file')->forget('fashn_video_duration');
        Cache::store('file')->forget('fashn_video_resolution');
    }

    /**
     * Run model creation generation task.
     *
     * @param  string  $prompt  Prompt description of the model to create.
     * @return array Response array containing 'id' and 'error'.
     *
     * @throws \Exception
     */
    public function runModelCreate(string $prompt): array
    {
        $apiKey = $this->getApiKey();
        if (empty($apiKey)) {
            throw new \Exception('API Key Fashn AI belum dikonfigurasikan. Silakan isi API Key di menu Konfigurasi -> Fashn AI.');
        }

        $baseUrl = $this->getBaseUrl();

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(30)->post($baseUrl.'/v1/run', [
            'model_name' => 'model-create',
            'inputs' => [
                'prompt' => $prompt,
            ],
        ]);

        if (! $response->successful()) {
            $errorMsg = $response->json('error.message') ?? $response->body() ?? 'Gagal menghubungi Fashn AI API.';
            Log::error('Fashn AI runModelCreate failed: '.$errorMsg, [
                'status' => $response->status(),
                'prompt' => $prompt,
            ]);
            throw new \Exception('Fashn AI Error ('.$response->status().'): '.$errorMsg);
        }

        $result = $response->json();
        if (! empty($result['error'])) {
            $msg = is_array($result['error']) ? ($result['error']['message'] ?? json_encode($result['error'])) : $result['error'];
            throw new \Exception('Fashn AI Task Error: '.$msg);
        }

        return $result;
    }

    /**
     * Check status of a generation task.
     *
     * @param  string  $taskId  Task ID returned from run API.
     * @return array Status response with 'id', 'status', 'output', 'error'.
     *
     * @throws \Exception
     */
    public function checkStatus(string $taskId): array
    {
        $apiKey = $this->getApiKey();
        if (empty($apiKey)) {
            throw new \Exception('API Key Fashn AI belum dikonfigurasikan.');
        }

        $baseUrl = $this->getBaseUrl();

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$apiKey,
        ])->timeout(30)->get($baseUrl.'/v1/status/'.$taskId);

        if (! $response->successful()) {
            $errorMsg = $response->json('error.message') ?? $response->body() ?? 'Gagal memeriksa status task Fashn AI.';
            throw new \Exception('Fashn AI Status Error ('.$response->status().'): '.$errorMsg);
        }

        return $response->json();
    }

    /**
     * Poll until task is completed or timeout is reached.
     *
     * @param  string  $taskId  Task ID.
     * @param  int  $maxWaitSeconds  Maximum seconds to wait (default 60s).
     * @param  int  $intervalSeconds  Interval between polls in seconds (default 2s).
     * @return array The completed response.
     *
     * @throws \Exception
     */
    public function waitForCompletion(string $taskId, int $maxWaitSeconds = 60, int $intervalSeconds = 2): array
    {
        $startTime = time();

        while ((time() - $startTime) < $maxWaitSeconds) {
            $statusRes = $this->checkStatus($taskId);
            $status = strtolower($statusRes['status'] ?? '');

            if ($status === 'completed') {
                if (empty($statusRes['output']) || ! is_array($statusRes['output']) || count($statusRes['output']) === 0) {
                    throw new \Exception('Generasi Fashn AI selesai tetapi tidak menghasilkan output gambar.');
                }

                return $statusRes;
            }

            if ($status === 'failed') {
                $err = $statusRes['error']['message'] ?? $statusRes['error'] ?? 'Proses generasi Fashn AI gagal di server.';
                throw new \Exception('Generasi Fashn AI Gagal: '.(is_array($err) ? json_encode($err) : $err));
            }

            sleep($intervalSeconds);
        }

        throw new \Exception("Proses generasi Fashn AI melebihi batas waktu ({$maxWaitSeconds} detik). Silakan coba lagi.");
    }

    /**
     * Download image from CDN URL and store it into the system public storage.
     *
     * @param  string  $imageUrl  CDN URL of the image.
     * @param  string  $folder  Target storage folder.
     * @return string Relative file path on public storage disk.
     *
     * @throws \Exception
     */
    public function downloadAndStoreImage(string $imageUrl, string $folder = 'virtual-try-on-models'): string
    {
        $response = Http::timeout(60)->get($imageUrl);

        if (! $response->successful()) {
            throw new \Exception('Gagal mengunduh gambar hasil generasi dari CDN Fashn AI.');
        }

        $imageContent = $response->body();
        if (empty($imageContent)) {
            throw new \Exception('File gambar yang diunduh dari CDN Fashn AI kosong.');
        }

        $filename = Str::uuid().'.png';
        $path = $folder.'/'.$filename;

        Storage::disk('public')->put($path, $imageContent);

        return $path;
    }

    /**
     * Download video from CDN URL and store it into the system public storage.
     *
     * @param  string  $videoUrl  CDN URL of the video.
     * @param  string  $folder  Target storage folder.
     * @return string Relative file path on public storage disk.
     *
     * @throws \Exception
     */
    public function downloadAndStoreVideo(string $videoUrl, string $folder = 'product-virtual-try-ons/videos'): string
    {
        $response = Http::timeout(120)->get($videoUrl);

        if (! $response->successful()) {
            throw new \Exception('Gagal mengunduh video hasil generasi dari CDN Fashn AI.');
        }

        $videoContent = $response->body();
        if (empty($videoContent)) {
            throw new \Exception('File video yang diunduh dari CDN Fashn AI kosong.');
        }

        $filename = Str::uuid().'.mp4';
        $path = $folder.'/'.$filename;

        Storage::disk('public')->put($path, $videoContent);

        return $path;
    }

    /**
     * Prepare image input for Fashn AI API.
     * Handles local files or localhost URLs by converting them to base64 Data URI,
     * while preserving valid public URLs.
     */
    public function prepareImageInput(string $imagePathOrUrl): string
    {
        if (Str::startsWith($imagePathOrUrl, 'data:image/')) {
            return $imagePathOrUrl;
        }

        // Check if path exists in public storage
        if (Storage::disk('public')->exists($imagePathOrUrl)) {
            $content = Storage::disk('public')->get($imagePathOrUrl);
            $mime = Storage::disk('public')->mimeType($imagePathOrUrl) ?: 'image/jpeg';

            return 'data:'.$mime.';base64,'.base64_encode($content);
        }

        // If it's a URL
        if (Str::startsWith($imagePathOrUrl, ['http://', 'https://'])) {
            $parsedUrl = parse_url($imagePathOrUrl);
            $host = $parsedUrl['host'] ?? '';

            // If localhost or private IP, convert to data URI so Fashn AI can access it
            if (in_array($host, ['localhost', '127.0.0.1', '::1']) || Str::endsWith($host, '.local') || Str::endsWith($host, '.test')) {
                // Try to resolve from local storage via path
                $path = ltrim($parsedUrl['path'] ?? '', '/');
                if (Str::startsWith($path, 'storage/')) {
                    $storagePath = Str::after($path, 'storage/');
                    if (Storage::disk('public')->exists($storagePath)) {
                        $content = Storage::disk('public')->get($storagePath);
                        $mime = Storage::disk('public')->mimeType($storagePath) ?: 'image/jpeg';

                        return 'data:'.$mime.';base64,'.base64_encode($content);
                    }
                }

                // If not found in storage disk directly, attempt HTTP fetch
                try {
                    $res = Http::timeout(15)->get($imagePathOrUrl);
                    if ($res->successful()) {
                        $mime = $res->header('Content-Type') ?: 'image/jpeg';

                        return 'data:'.$mime.';base64,'.base64_encode($res->body());
                    }
                } catch (\Throwable $e) {
                    Log::warning('FashnAiService: failed to fetch local URL for base64 conversion: '.$e->getMessage());
                }
            }

            return $imagePathOrUrl;
        }

        // Fallback: check if exists in public directory
        $publicFilePath = public_path($imagePathOrUrl);
        if (file_exists($publicFilePath) && is_file($publicFilePath)) {
            $content = file_get_contents($publicFilePath);
            $mime = mime_content_type($publicFilePath) ?: 'image/jpeg';

            return 'data:'.$mime.';base64,'.base64_encode($content);
        }

        return $imagePathOrUrl;
    }

    /**
     * Run tryon-max virtual try-on generation task.
     * Combines model/person image and product/garment image.
     *
     * @param  string  $modelImage  Public URL or base64 Data URI of model image.
     * @param  string  $productImage  Public URL or base64 Data URI of product image.
     * @return array Response array containing 'id' and 'error'.
     *
     * @throws \Exception
     */
    public function runTryOnMax(string $modelImage, string $productImage): array
    {
        $apiKey = $this->getApiKey();
        if (empty($apiKey)) {
            throw new \Exception('API Key Fashn AI belum dikonfigurasikan. Silakan isi API Key di menu Konfigurasi -> Fashn AI.');
        }

        $baseUrl = $this->getBaseUrl();

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(45)->post($baseUrl.'/v1/run', [
            'model_name' => 'tryon-max',
            'inputs' => [
                'model_image' => $modelImage,
                'product_image' => $productImage,
            ],
        ]);

        if (! $response->successful()) {
            $errorMsg = $response->json('error.message') ?? $response->body() ?? 'Gagal menghubungi Fashn AI API (tryon-max).';
            Log::error('Fashn AI runTryOnMax failed: '.$errorMsg, [
                'status' => $response->status(),
            ]);
            throw new \Exception('Fashn AI Error ('.$response->status().'): '.$errorMsg);
        }

        $result = $response->json();
        if (! empty($result['error'])) {
            $msg = is_array($result['error']) ? ($result['error']['message'] ?? json_encode($result['error'])) : $result['error'];
            throw new \Exception('Fashn AI Task Error (tryon-max): '.$msg);
        }

        return $result;
    }

    /**
     * Run face-to-model generation task.
     *
     * @param  string  $faceImage  Public URL or base64 Data URI of headshot image.
     * @param  string  $outputFormat  Output format (default jpeg).
     * @return array Response array containing 'id' and 'error'.
     *
     * @throws \Exception
     */
    public function runFaceToModel(string $faceImage, string $outputFormat = 'jpeg'): array
    {
        $apiKey = $this->getApiKey();
        if (empty($apiKey)) {
            throw new \Exception('API Key Fashn AI belum dikonfigurasikan. Silakan isi API Key di menu Konfigurasi -> Fashn AI.');
        }

        $baseUrl = $this->getBaseUrl();

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(45)->post($baseUrl.'/v1/run', [
            'model_name' => 'face-to-model',
            'inputs' => [
                'face_image' => $faceImage,
                'output_format' => $outputFormat,
            ],
        ]);

        if (! $response->successful()) {
            $errorMsg = $response->json('error.message') ?? $response->body() ?? 'Gagal menghubungi Fashn AI API (face-to-model).';
            Log::error('Fashn AI runFaceToModel failed: '.$errorMsg, [
                'status' => $response->status(),
            ]);
            throw new \Exception('Fashn AI Error ('.$response->status().'): '.$errorMsg);
        }

        $result = $response->json();
        if (! empty($result['error'])) {
            $msg = is_array($result['error']) ? ($result['error']['message'] ?? json_encode($result['error'])) : $result['error'];
            throw new \Exception('Fashn AI Task Error (face-to-model): '.$msg);
        }

        return $result;
    }

    /**
     * Run image-to-video generation task.
     *
     * @param  string  $imageUrl  Public URL of the photo generated.
     * @param  int|null  $duration  Duration in seconds (5 or 10, default from config).
     * @param  string|null  $resolution  Resolution (480p, 720p, 1080p, default from config).
     * @return array Response array containing 'id' and 'error'.
     *
     * @throws \Exception
     */
    public function runImageToVideo(string $imageUrl, ?int $duration = null, ?string $resolution = null): array
    {
        $duration = $duration ?? $this->getVideoDuration();
        $resolution = $resolution ?? $this->getVideoResolution();

        $apiKey = $this->getApiKey();
        if (empty($apiKey)) {
            throw new \Exception('API Key Fashn AI belum dikonfigurasikan.');
        }

        $baseUrl = $this->getBaseUrl();

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(45)->post($baseUrl.'/v1/run', [
            'model_name' => 'image-to-video',
            'inputs' => [
                'image' => $imageUrl,
                'duration' => $duration,
                'resolution' => $resolution,
            ],
        ]);

        if (! $response->successful()) {
            $errorMsg = $response->json('error.message') ?? $response->body() ?? 'Gagal menghubungi Fashn AI API (image-to-video).';
            Log::error('Fashn AI runImageToVideo failed: '.$errorMsg, [
                'status' => $response->status(),
                'image_url' => $imageUrl,
            ]);
            throw new \Exception('Fashn AI Error ('.$response->status().'): '.$errorMsg);
        }

        $result = $response->json();
        if (! empty($result['error'])) {
            $msg = is_array($result['error']) ? ($result['error']['message'] ?? json_encode($result['error'])) : $result['error'];
            throw new \Exception('Fashn AI Task Error (image-to-video): '.$msg);
        }

        return $result;
    }

    /**
     * End-to-end: Run model creation, wait for completion, and download image to local storage.
     *
     * @param  string  $prompt  Prompt description.
     * @param  string  $folder  Target storage folder.
     * @return array Result containing 'task_id', 'original_url', 'local_path', and 'photo_url'.
     *
     * @throws \Exception
     */
    public function generateAndSaveModelImage(string $prompt, string $folder = 'virtual-try-on-models'): array
    {
        $runResult = $this->runModelCreate($prompt);
        $taskId = $runResult['id'];

        $completed = $this->waitForCompletion($taskId);
        $cdnUrl = $completed['output'][0];

        $localPath = $this->downloadAndStoreImage($cdnUrl, $folder);
        $photoUrl = Storage::disk('public')->url($localPath);

        return [
            'task_id' => $taskId,
            'original_url' => $cdnUrl,
            'local_path' => $localPath,
            'photo_url' => $photoUrl,
        ];
    }
}
