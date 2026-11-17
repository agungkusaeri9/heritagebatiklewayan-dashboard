<?php

namespace App\Http\Controllers;

use App\Http\Resources\AppVersionResource;
use App\Http\Resources\BannerResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\StoreSettingResource;
use App\Models\AppVersion;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Faq;
use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class PublicLandingController extends Controller
{
    /**
     * Display the public Arbatik landing page.
     */
    public function index(Request $request): Response
    {
        $setting = StoreSetting::first();

        $banners = Banner::query()
            ->active()
            ->with([
                'product:id,name,slug,price,primary_image',
                'category:id,name,slug',
            ])
            ->orderBy('sort_order')
            ->orderBy('id', 'desc')
            ->get();

        $faqs = Faq::query()
            ->where('is_active', true)
            ->orderBy('order', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->map(fn (Faq $faq): array => [
                'question' => $faq->title,
                'answer' => $faq->description,
            ])
            ->values()
            ->all();

        $categories = Category::query()
            ->where('is_active', true)
            ->withCount(['products' => function ($query): void {
                $query->where('is_active', true);
            }])
            ->orderBy('name')
            ->get();

        $products = Product::query()
            ->where('is_active', true)
            ->with(['category', 'images', 'variantValues.variantType'])
            ->withCount('reviews')
            ->orderBy('stock', 'asc')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        $latestAndroid = $this->resolveLatestAppVersion('android', $request);
        $latestIos = $this->resolveLatestAppVersion('ios', $request);

        return Inertia::render('Welcome', [
            'settings' => $setting ? StoreSettingResource::make($setting)->resolve($request) : null,
            'banners' => BannerResource::collection($banners)->resolve($request),
            'faqs' => $faqs,
            'categories' => CategoryResource::collection($categories)->resolve($request),
            'products' => ProductResource::collection($products)->resolve($request),
            'latestApps' => [
                'android' => $latestAndroid,
                'ios' => $latestIos,
            ],
            'siteUrl' => url('/'),
        ]);
    }

    /**
     * Serve the XML sitemap for the landing page.
     */
    public function sitemap(): HttpResponse
    {
        $baseUrl = rtrim(url('/'), '/');
        $lastMod = now()->toDateString();

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
	<url>
		<loc>{$baseUrl}/</loc>
		<lastmod>{$lastMod}</lastmod>
		<changefreq>weekly</changefreq>
		<priority>1.0</priority>
	</url>
</urlset>
XML;

        return response($xml, 200, [
            'Content-Type' => 'application/xml',
            'Cache-Control' => 'max-age=0, s-maxage=3600',
        ]);
    }

    /**
     * Resolve the latest active app version payload for a given platform.
     *
     * @return array<string, mixed>|null
     */
    private function resolveLatestAppVersion(string $type, Request $request): ?array
    {
        $latest = AppVersion::query()
            ->where('is_active', true)
            ->where('type', $type)
            ->orderByDesc('is_latest')
            ->orderByDesc('version_code')
            ->orderByDesc('id')
            ->first();

        if (! $latest) {
            return null;
        }

        return AppVersionResource::make($latest)->resolve($request);
    }
}
