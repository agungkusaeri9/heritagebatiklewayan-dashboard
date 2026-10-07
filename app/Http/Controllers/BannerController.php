<?php

namespace App\Http\Controllers;

use App\Http\Requests\Banner\IndexBannerRequest;
use App\Http\Requests\Banner\StoreBannerRequest;
use App\Http\Requests\Banner\UpdateBannerRequest;
use App\Models\Banner;
use App\Models\Product;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Services\BannerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class BannerController extends Controller
{
    public function __construct(
        private BannerService $bannerService,
        private ProductRepositoryInterface $productRepository,
        private CategoryRepositoryInterface $categoryRepository
    ) {}

    public function index(IndexBannerRequest $request): Response
    {
        $banners = $this->bannerService->getBanners($request->filters(), $request->perPage());

        return Inertia::render('banners/Index', [
            'banners' => $banners->through(fn ($banner) => [
                'id' => $banner->id,
                'title' => $banner->title,
                'image' => $banner->image,
                'image_url' => $banner->image_url,
                'target_type' => $banner->target_type,
                'target_url' => $banner->target_url,
                'sort_order' => $banner->sort_order,
                'is_active' => (bool) $banner->is_active,
                'start_date' => $banner->start_date ? $banner->start_date->toISOString() : null,
                'end_date' => $banner->end_date ? $banner->end_date->toISOString() : null,
                'description' => $banner->description,
                'created_at' => $banner->created_at ? $banner->created_at->toISOString() : null,
                'product' => $banner->product ? [
                    'id' => $banner->product->id,
                    'name' => $banner->product->name,
                ] : null,
                'category' => $banner->category ? [
                    'id' => $banner->category->id,
                    'name' => $banner->category->name,
                ] : null,
            ]),
            'filters' => $request->filters(),
        ]);
    }

    public function create(): Response
    {
        $products = Product::where('is_active', true)
            ->select('id', 'name', 'price', 'primary_image')
            ->orderBy('name')
            ->get()
            ->map(fn ($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'price' => (string) $product->price,
            ]);

        $categories = $this->categoryRepository->getAllActive();

        return Inertia::render('banners/Create', [
            'products' => $products,
            'categories' => $categories,
        ]);
    }

    public function store(StoreBannerRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $this->bannerService->createBanner($validated, $request->file('image'));

            return redirect()->route('banners.index')->with('success', 'Banner promosi berhasil ditambahkan.');
        } catch (\Throwable $e) {
            Log::error('Gagal menambahkan banner: '.$e->getMessage(), ['exception' => $e]);

            return back()->with('error', 'Gagal menambahkan banner: '.$e->getMessage())->withInput();
        }
    }

    public function edit(Banner $banner): Response
    {
        $products = Product::where('is_active', true)
            ->select('id', 'name', 'price', 'primary_image')
            ->orderBy('name')
            ->get()
            ->map(fn ($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'price' => (string) $product->price,
            ]);

        $categories = $this->categoryRepository->getAllActive();

        return Inertia::render('banners/Edit', [
            'banner' => [
                'id' => $banner->id,
                'title' => $banner->title,
                'image' => $banner->image,
                'image_url' => $banner->image_url,
                'target_type' => $banner->target_type,
                'target_url' => $banner->target_url,
                'product_id' => $banner->product_id,
                'category_id' => $banner->category_id,
                'sort_order' => $banner->sort_order,
                'is_active' => (bool) $banner->is_active,
                'start_date' => $banner->start_date ? $banner->start_date->format('Y-m-d\TH:i') : '',
                'end_date' => $banner->end_date ? $banner->end_date->format('Y-m-d\TH:i') : '',
                'description' => $banner->description,
            ],
            'products' => $products,
            'categories' => $categories,
        ]);
    }

    public function update(UpdateBannerRequest $request, Banner $banner): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $this->bannerService->updateBanner($banner, $validated, $request->file('image'));

            return redirect()->route('banners.index')->with('success', 'Banner promosi berhasil diperbarui.');
        } catch (\Throwable $e) {
            Log::error('Gagal memperbarui banner: '.$e->getMessage(), ['exception' => $e]);

            return back()->with('error', 'Gagal memperbarui banner: '.$e->getMessage())->withInput();
        }
    }

    public function toggle(Banner $banner): RedirectResponse
    {
        try {
            $this->bannerService->toggleBannerStatus($banner);

            $status = $banner->fresh()->is_active ? 'diaktifkan' : 'dinonaktifkan';

            return back()->with('success', "Banner berhasil {$status}.");
        } catch (\Throwable $e) {
            Log::error('Gagal mengubah status banner: '.$e->getMessage(), ['exception' => $e]);

            return back()->with('error', 'Gagal mengubah status banner: '.$e->getMessage());
        }
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        try {
            $this->bannerService->deleteBanner($banner);

            return back()->with('success', 'Banner promosi berhasil dihapus.');
        } catch (\Throwable $e) {
            Log::error('Gagal menghapus banner: '.$e->getMessage(), ['exception' => $e]);

            return back()->with('error', 'Gagal menghapus banner: '.$e->getMessage());
        }
    }
}
