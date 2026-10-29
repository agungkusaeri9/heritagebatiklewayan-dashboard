<?php

namespace App\Http\Controllers;

use App\Models\AppConfig;
use App\Services\AppConfigService;
use App\Services\FashnAiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FashnAiSettingController extends Controller
{
    public function __construct(
        protected AppConfigService $appConfigService,
        protected FashnAiService $fashnAiService
    ) {}

    /**
     * Display the Fashn AI / Virtual Try-On settings page.
     */
    public function index(): Response
    {
        $keys = [
            'is_product_virtual_try_on' => 'Status aktif fitur Product Virtual Try-On',
            'is_product_virtual_try_on_photo' => 'Status aktif fitur Virtual Try-On mode Foto',
            'is_product_virtual_try_on_video' => 'Status aktif fitur Virtual Try-On mode Video',
            'fashn_video_duration' => 'Durasi video generasi Fashn AI (5 atau 10 detik)',
            'fashn_video_resolution' => 'Resolusi video generasi Fashn AI (480p, 720p, 1080p)',
            'virtual_try_on_daily_limit_per_user' => 'Batas maksimal percobaan virtual try-on per user per hari',
            'virtual_try_on_weekly_limit_all_users' => 'Batas kuota maksimal percobaan virtual try-on seluruh pengguna per minggu',
        ];

        $defaultValues = [
            'is_product_virtual_try_on' => '1',
            'is_product_virtual_try_on_photo' => '1',
            'is_product_virtual_try_on_video' => '0',
            'fashn_video_duration' => '5',
            'fashn_video_resolution' => '480p',
            'virtual_try_on_daily_limit_per_user' => '3',
            'virtual_try_on_weekly_limit_all_users' => '100',
        ];

        $configs = [];
        foreach ($keys as $k => $desc) {
            $configs[$k] = AppConfig::firstOrCreate(
                ['key' => $k],
                [
                    'value' => $defaultValues[$k] ?? null,
                    'description' => $desc,
                ]
            );
        }

        $apiKeyConfig = AppConfig::firstWhere('key', 'fashn_api_key');
        $baseUrlConfig = AppConfig::firstWhere('key', 'fashn_api_base_url');

        return Inertia::render('fashn-ai/Settings', [
            'configs' => $configs,
            'isApiConfigured' => ! empty($apiKeyConfig?->value),
            'apiConfig' => [
                'base_url' => $baseUrlConfig?->value ?? 'https://api.fashn.ai',
                'has_key' => ! empty($apiKeyConfig?->value),
            ],
        ]);
    }

    /**
     * Update the Fashn AI / Virtual Try-On settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'is_product_virtual_try_on' => ['nullable', 'boolean'],
            'is_product_virtual_try_on_photo' => ['nullable', 'boolean'],
            'is_product_virtual_try_on_video' => ['nullable', 'boolean'],
            'video_duration' => ['nullable', 'integer', 'in:5,10'],
            'video_resolution' => ['nullable', 'string', 'in:480p,720p,1080p'],
            'virtual_try_on_daily_limit_per_user' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'virtual_try_on_weekly_limit_all_users' => ['nullable', 'integer', 'min:0', 'max:1000000'],
        ]);

        if (array_key_exists('is_product_virtual_try_on', $validated)) {
            $this->appConfigService->setOrUpdate(
                'is_product_virtual_try_on',
                $validated['is_product_virtual_try_on'] ? '1' : '0',
                'Status aktif fitur Product Virtual Try-On'
            );
        }

        if (array_key_exists('is_product_virtual_try_on_photo', $validated)) {
            $this->appConfigService->setOrUpdate(
                'is_product_virtual_try_on_photo',
                $validated['is_product_virtual_try_on_photo'] ? '1' : '0',
                'Status aktif fitur Virtual Try-On mode Foto'
            );
        }

        if (array_key_exists('is_product_virtual_try_on_video', $validated)) {
            $this->appConfigService->setOrUpdate(
                'is_product_virtual_try_on_video',
                $validated['is_product_virtual_try_on_video'] ? '1' : '0',
                'Status aktif fitur Virtual Try-On mode Video'
            );
        }

        if (array_key_exists('video_duration', $validated)) {
            $this->appConfigService->setOrUpdate(
                'fashn_video_duration',
                (string) ($validated['video_duration'] ?? 5),
                'Durasi video generasi Fashn AI (5 atau 10 detik)'
            );
        }

        if (array_key_exists('video_resolution', $validated)) {
            $this->appConfigService->setOrUpdate(
                'fashn_video_resolution',
                $validated['video_resolution'] ?? '480p',
                'Resolusi video generasi Fashn AI (480p, 720p, 1080p)'
            );
        }

        if (array_key_exists('virtual_try_on_daily_limit_per_user', $validated)) {
            $this->appConfigService->setOrUpdate(
                'virtual_try_on_daily_limit_per_user',
                $validated['virtual_try_on_daily_limit_per_user'] !== null ? (string) $validated['virtual_try_on_daily_limit_per_user'] : '3',
                'Batas maksimal percobaan virtual try-on per user per hari'
            );
        }

        if (array_key_exists('virtual_try_on_weekly_limit_all_users', $validated)) {
            $this->appConfigService->setOrUpdate(
                'virtual_try_on_weekly_limit_all_users',
                $validated['virtual_try_on_weekly_limit_all_users'] !== null ? (string) $validated['virtual_try_on_weekly_limit_all_users'] : '100',
                'Batas kuota maksimal percobaan virtual try-on seluruh pengguna per minggu'
            );
        }

        // Clear and pre-warm Fashn AI cache
        $this->fashnAiService->clearCache();
        $this->fashnAiService->getVideoDuration();
        $this->fashnAiService->getVideoResolution();

        return redirect()->back()->with('success', 'Pengaturan Virtual Try-On berhasil diperbarui.');
    }
}
