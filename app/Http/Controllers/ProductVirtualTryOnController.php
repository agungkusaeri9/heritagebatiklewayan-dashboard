<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductVirtualTryOn\IndexProductVirtualTryOnRequest;
use App\Http\Requests\ProductVirtualTryOn\StoreProductVirtualTryOnRequest;
use App\Models\Product;
use App\Models\ProductVirtualTryOn;
use App\Models\User;
use App\Models\VirtualTryOnModel;
use App\Services\ProductVirtualTryOnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ProductVirtualTryOnController extends Controller
{
    public function __construct(
        private ProductVirtualTryOnService $tryOnService
    ) {}

    public function index(IndexProductVirtualTryOnRequest $request): Response
    {
        $tryOns = $this->tryOnService->getTryOns($request->filters(), $request->perPage());

        $products = Product::select('id', 'name', 'sku', 'primary_image', 'price')
            ->with(['virtualTryOns' => fn ($q) => $q->whereNull('user_id')->where('status', '!=', 'failed')->select('id', 'product_id', 'gender', 'status')])
            ->orderBy('name')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'price' => (string) $p->price,
                'primary_image_url' => $p->primary_image_url,
                'has_default_male' => $p->virtualTryOns->contains('gender', 'male'),
                'has_default_female' => $p->virtualTryOns->contains('gender', 'female'),
            ]);

        $activeModels = VirtualTryOnModel::active()
            ->select('id', 'name', 'gender', 'photo')
            ->orderBy('name')
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'gender' => $m->gender,
                'photo_url' => $m->photo_url,
            ]);

        $users = User::select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return Inertia::render('fashn-ai/try-ons/Index', [
            'tryOns' => $tryOns->through(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product' => $item->product ? [
                    'id' => $item->product->id,
                    'name' => $item->product->name,
                    'slug' => $item->product->slug,
                    'sku' => $item->product->sku,
                    'price' => (string) $item->product->price,
                    'primary_image_url' => $item->product->primary_image_url,
                ] : null,
                'virtual_try_on_model_id' => $item->virtual_try_on_model_id,
                'virtual_model' => $item->virtualModel ? [
                    'id' => $item->virtualModel->id,
                    'name' => $item->virtualModel->name,
                    'gender' => $item->virtualModel->gender,
                    'photo_url' => $item->virtualModel->photo_url,
                ] : null,
                'user_id' => $item->user_id,
                'user' => $item->user ? [
                    'id' => $item->user->id,
                    'name' => $item->user->name,
                    'email' => $item->user->email,
                ] : null,
                'gender' => $item->gender ?? $item->virtualModel?->gender,
                'photo' => $item->photo,
                'photo_url' => $item->photo_url,
                'video' => $item->video,
                'video_url' => $item->video_url,
                'status' => $item->status,
                'progress' => (int) $item->progress,
                'step_label' => $item->step_label,
                'task_id_photo' => $item->task_id_photo,
                'task_id_video' => $item->task_id_video,
                'generate_video' => (bool) $item->generate_video,
                'error_message' => $item->error_message,
                'is_default' => (bool) $item->is_default,
                'created_at' => $item->created_at ? $item->created_at->toISOString() : null,
            ]),
            'filters' => $request->filters(),
            'products' => $products,
            'activeModels' => $activeModels,
            'users' => $users,
        ]);
    }

    public function store(StoreProductVirtualTryOnRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();

            $this->tryOnService->createDefaultTryOn($data);

            return redirect()->route('product-virtual-try-ons.index')
                ->with('success', 'Generasi model default virtual try-on telah dimasukkan ke antrean latar belakang.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        } catch (\Throwable $e) {
            Log::error('Gagal menambahkan model default try-on: '.$e->getMessage(), ['exception' => $e]);

            return back()->with('error', 'Gagal memproses generasi model default: '.$e->getMessage())->withInput();
        }
    }

    public function retry(ProductVirtualTryOn $productVirtualTryOn): RedirectResponse
    {
        if (! request()->user()?->can('create product virtual try ons')) {
            abort(403);
        }

        try {
            $this->tryOnService->retryGeneration($productVirtualTryOn);

            return redirect()->route('product-virtual-try-ons.index')
                ->with('success', 'Generasi model default sedang dicoba ulang di antrean.');
        } catch (\Throwable $e) {
            Log::error('Gagal mencoba ulang generasi try-on: '.$e->getMessage(), ['exception' => $e]);

            return back()->with('error', 'Gagal mencoba ulang generasi: '.$e->getMessage());
        }
    }

    public function show(ProductVirtualTryOn $productVirtualTryOn): JsonResponse|Response
    {
        if (request()->wantsJson()) {
            return response()->json([
                'id' => $productVirtualTryOn->id,
                'product' => $productVirtualTryOn->product,
                'virtual_model' => $productVirtualTryOn->virtualModel,
                'user' => $productVirtualTryOn->user,
                'gender' => $productVirtualTryOn->gender ?? $productVirtualTryOn->virtualModel?->gender,
                'photo_url' => $productVirtualTryOn->photo_url,
                'video_url' => $productVirtualTryOn->video_url,
                'status' => $productVirtualTryOn->status,
                'progress' => (int) $productVirtualTryOn->progress,
                'step_label' => $productVirtualTryOn->step_label,
                'error_message' => $productVirtualTryOn->error_message,
                'is_default' => $productVirtualTryOn->is_default,
                'created_at' => $productVirtualTryOn->created_at ? $productVirtualTryOn->created_at->toISOString() : null,
            ]);
        }

        return Inertia::render('fashn-ai/try-ons/Show', [
            'tryOn' => [
                'id' => $productVirtualTryOn->id,
                'product' => $productVirtualTryOn->product ? [
                    'id' => $productVirtualTryOn->product->id,
                    'name' => $productVirtualTryOn->product->name,
                    'slug' => $productVirtualTryOn->product->slug,
                    'sku' => $productVirtualTryOn->product->sku,
                    'price' => (string) $productVirtualTryOn->product->price,
                    'primary_image_url' => $productVirtualTryOn->product->primary_image_url,
                ] : null,
                'virtual_model' => $productVirtualTryOn->virtualModel ? [
                    'id' => $productVirtualTryOn->virtualModel->id,
                    'name' => $productVirtualTryOn->virtualModel->name,
                    'gender' => $productVirtualTryOn->virtualModel->gender,
                    'photo_url' => $productVirtualTryOn->virtualModel->photo_url,
                ] : null,
                'user' => $productVirtualTryOn->user ? [
                    'id' => $productVirtualTryOn->user->id,
                    'name' => $productVirtualTryOn->user->name,
                    'email' => $productVirtualTryOn->user->email,
                ] : null,
                'gender' => $productVirtualTryOn->gender ?? $productVirtualTryOn->virtualModel?->gender,
                'photo' => $productVirtualTryOn->photo,
                'photo_url' => $productVirtualTryOn->photo_url,
                'video' => $productVirtualTryOn->video,
                'video_url' => $productVirtualTryOn->video_url,
                'status' => $productVirtualTryOn->status,
                'progress' => (int) $productVirtualTryOn->progress,
                'step_label' => $productVirtualTryOn->step_label,
                'error_message' => $productVirtualTryOn->error_message,
                'is_default' => (bool) $productVirtualTryOn->is_default,
                'created_at' => $productVirtualTryOn->created_at ? $productVirtualTryOn->created_at->toISOString() : null,
            ],
        ]);
    }

    public function destroy(ProductVirtualTryOn $productVirtualTryOn): RedirectResponse
    {
        try {
            $this->tryOnService->deleteTryOn($productVirtualTryOn);

            return redirect()->route('product-virtual-try-ons.index')->with('success', 'Hasil virtual try-on berhasil dihapus.');
        } catch (\Throwable $e) {
            Log::error('Gagal menghapus hasil virtual try-on: '.$e->getMessage(), ['exception' => $e]);

            return back()->with('error', 'Gagal menghapus hasil virtual try-on: '.$e->getMessage());
        }
    }
}
