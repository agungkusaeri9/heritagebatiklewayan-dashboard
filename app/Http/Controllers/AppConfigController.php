<?php

namespace App\Http\Controllers;

use App\Http\Requests\AppConfig\IndexAppConfigRequest;
use App\Http\Requests\AppConfig\StoreAppConfigRequest;
use App\Http\Requests\AppConfig\UpdateAppConfigRequest;
use App\Models\AppConfig;
use App\Services\AppConfigService;
use App\Services\FashnAiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class AppConfigController extends Controller
{
    public function __construct(
        protected AppConfigService $appConfigService
    ) {}

    /**
     * Display a listing of app configurations.
     */
    public function index(IndexAppConfigRequest $request): Response
    {
        $configs = $this->appConfigService->getPaginatedConfigs(
            (int) $request->input('per_page', 10),
            $request->input('search'),
            (string) $request->input('sort', 'created_at'),
            (string) $request->input('direction', 'desc')
        );

        return Inertia::render('app-configs/Index', [
            'configs' => $configs,
            'filters' => $request->only(['search', 'sort', 'direction', 'per_page']),
        ]);
    }

    /**
     * Store a newly created configuration.
     */
    public function store(StoreAppConfigRequest $request): RedirectResponse
    {
        try {
            $this->appConfigService->createConfig($request->validated());

            return redirect()->back()->with('success', 'Konfigurasi berhasil ditambahkan.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Gagal menambahkan konfigurasi: '.$e->getMessage())->withInput();
        }
    }

    /**
     * Update the specified configuration.
     */
    public function update(UpdateAppConfigRequest $request, AppConfig $app_config): RedirectResponse
    {
        try {
            $this->appConfigService->updateConfig($app_config->id, $request->validated());

            return redirect()->back()->with('success', 'Konfigurasi berhasil diperbarui.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Gagal memperbarui konfigurasi: '.$e->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified configuration.
     */
    public function destroy(AppConfig $app_config): RedirectResponse
    {
        try {
            $this->appConfigService->deleteConfig($app_config->id);

            return redirect()->back()->with('success', 'Konfigurasi berhasil dihapus.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Gagal menghapus konfigurasi: '.$e->getMessage());
        }
    }

    /**
     * Show RajaOngkir configuration page.
     */
    public function rajaongkir(): Response
    {
        $config = AppConfig::firstOrCreate(
            ['key' => 'rajaongkir_api_key'],
            [
                'value' => null,
                'description' => 'API Key untuk layanan integrasi RajaOngkir / Komerce',
            ]
        );

        return Inertia::render('configurations/RajaOngkir', [
            'config' => $config,
        ]);
    }

    /**
     * Update RajaOngkir configuration.
     */
    public function updateRajaongkir(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'api_key' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->appConfigService->setOrUpdate(
            'rajaongkir_api_key',
            $validated['api_key'] ?? null,
            $validated['description'] ?? 'API Key untuk layanan integrasi RajaOngkir / Komerce'
        );

        return redirect()->back()->with('success', 'Konfigurasi RajaOngkir berhasil diperbarui.');
    }

    /**
     * Show Fashn AI configuration page.
     */
    public function fashnAi(): Response
    {
        $keys = [
            'fashn_api_key' => 'API Key untuk layanan integrasi Fashn AI',
            'fashn_api_base_url' => 'Base URL endpoint API untuk layanan Fashn AI',
            'is_product_virtual_try_on' => 'Status aktif fitur Product Virtual Try-On',
            'is_product_virtual_try_on_photo' => 'Status aktif fitur Virtual Try-On mode Foto',
            'is_product_virtual_try_on_video' => 'Status aktif fitur Virtual Try-On mode Video',
            'fashn_video_duration' => 'Durasi video generasi Fashn AI (5 atau 10 detik)',
            'fashn_video_resolution' => 'Resolusi video generasi Fashn AI (480p, 720p, 1080p)',
            'virtual_try_on_daily_limit_per_user' => 'Batas maksimal percobaan virtual try-on per user per hari',
            'virtual_try_on_weekly_limit_all_users' => 'Batas kuota maksimal percobaan virtual try-on seluruh pengguna per minggu',
        ];

        $defaultValues = [
            'fashn_api_key' => null,
            'fashn_api_base_url' => 'https://api.fashn.ai',
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

        return Inertia::render('configurations/FashnAi', [
            'config' => $configs['fashn_api_key'],
            'configs' => $configs,
        ]);
    }

    /**
     * Update Fashn AI configuration.
     */
    public function updateFashnAi(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'api_key' => ['nullable', 'string', 'max:500'],
            'base_url' => ['nullable', 'url', 'max:500'],
            'is_product_virtual_try_on' => ['nullable', 'boolean'],
            'is_product_virtual_try_on_photo' => ['nullable', 'boolean'],
            'is_product_virtual_try_on_video' => ['nullable', 'boolean'],
            'video_duration' => ['nullable', 'integer', 'in:5,10'],
            'video_resolution' => ['nullable', 'string', 'in:480p,720p,1080p'],
            'virtual_try_on_daily_limit_per_user' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'virtual_try_on_weekly_limit_all_users' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->appConfigService->setOrUpdate(
            'fashn_api_key',
            $validated['api_key'] ?? null,
            $validated['description'] ?? 'API Key untuk layanan integrasi Fashn AI'
        );

        $this->appConfigService->setOrUpdate(
            'fashn_api_base_url',
            ! empty($validated['base_url']) ? rtrim($validated['base_url'], '/') : 'https://api.fashn.ai',
            'Base URL endpoint API untuk layanan Fashn AI'
        );

        $this->appConfigService->setOrUpdate(
            'is_product_virtual_try_on',
            isset($validated['is_product_virtual_try_on']) && $validated['is_product_virtual_try_on'] ? '1' : '0',
            'Status aktif fitur Product Virtual Try-On'
        );

        $this->appConfigService->setOrUpdate(
            'is_product_virtual_try_on_photo',
            isset($validated['is_product_virtual_try_on_photo']) && $validated['is_product_virtual_try_on_photo'] ? '1' : '0',
            'Status aktif fitur Virtual Try-On mode Foto'
        );

        $this->appConfigService->setOrUpdate(
            'is_product_virtual_try_on_video',
            isset($validated['is_product_virtual_try_on_video']) && $validated['is_product_virtual_try_on_video'] ? '1' : '0',
            'Status aktif fitur Virtual Try-On mode Video'
        );

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

        // Clear and pre-warm Fashn AI cache
        $fashnService = app(FashnAiService::class);
        $fashnService->clearCache();
        $fashnService->getVideoDuration();
        $fashnService->getVideoResolution();

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

        // Clear and pre-warm Fashn AI cache
        $fashnService = app(\App\Services\FashnAiService::class);
        $fashnService->clearCache();
        $fashnService->getVideoDuration();
        $fashnService->getVideoResolution();

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

        return redirect()->back()->with('success', 'Konfigurasi Fashn AI berhasil diperbarui.');
    }

    /**
     * Show Geoapify configuration page.
     */
    public function geoapify(): Response
    {
        $config = AppConfig::firstOrCreate(
            ['key' => 'geoapify_api_key'],
            [
                'value' => null,
                'description' => 'API Key untuk layanan Geocoding dan Peta Geoapify',
            ]
        );

        return Inertia::render('configurations/Geoapify', [
            'config' => $config,
        ]);
    }

    /**
     * Update Geoapify configuration.
     */
    public function updateGeoapify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'api_key' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->appConfigService->setOrUpdate(
            'geoapify_api_key',
            $validated['api_key'] ?? null,
            $validated['description'] ?? 'API Key untuk layanan Geocoding dan Peta Geoapify'
        );

        return redirect()->back()->with('success', 'Konfigurasi Geoapify berhasil diperbarui.');
    }
}
