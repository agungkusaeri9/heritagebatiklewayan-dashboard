<?php

namespace App\Http\Controllers;

use App\Http\Requests\VirtualTryOnModel\IndexVirtualTryOnModelRequest;
use App\Http\Requests\VirtualTryOnModel\StoreVirtualTryOnModelRequest;
use App\Http\Requests\VirtualTryOnModel\UpdateVirtualTryOnModelRequest;
use App\Models\VirtualTryOnModel;
use App\Services\VirtualTryOnModelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class VirtualTryOnModelController extends Controller
{
    public function __construct(
        private VirtualTryOnModelService $modelService
    ) {}

    public function index(IndexVirtualTryOnModelRequest $request): Response
    {
        $models = $this->modelService->getModels($request->filters(), $request->perPage());

        return Inertia::render('fashn-ai/models/Index', [
            'models' => $models->through(fn ($model) => [
                'id' => $model->id,
                'name' => $model->name,
                'gender' => $model->gender,
                'photo' => $model->photo,
                'photo_url' => $model->photo_url,
                'is_active' => (bool) $model->is_active,
                'try_ons_count' => (int) ($model->try_ons_count ?? 0),
                'created_at' => $model->created_at ? $model->created_at->toISOString() : null,
            ]),
            'filters' => $request->filters(),
        ]);
    }

    /**
     * Generate model image using Fashn AI API and download to local system storage.
     */
    public function generateAi(Request $request): JsonResponse
    {
        if (! $request->user()?->can('create virtual try on models')) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki hak akses untuk generate model.'], 403);
        }

        $validated = $request->validate([
            'prompt' => ['required', 'string', 'max:1000'],
        ], [
            'prompt.required' => 'Prompt deskripsi model wajib diisi.',
            'prompt.max' => 'Prompt maksimal 1000 karakter.',
        ]);

        try {
            $result = $this->modelService->generateAiPhoto($validated['prompt']);

            return response()->json([
                'success' => true,
                'message' => 'Foto model berhasil digenerate oleh Fashn AI dan disimpan ke sistem.',
                'photo_path' => $result['local_path'],
                'photo_url' => $result['photo_url'],
                'task_id' => $result['task_id'],
            ]);
        } catch (\Throwable $e) {
            Log::error('Fashn AI generate model failed: '.$e->getMessage(), ['exception' => $e]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function store(StoreVirtualTryOnModelRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $photo = $request->file('photo');

        try {
            $data['is_active'] = $request->boolean('is_active', true);
            $this->modelService->createModel($data, $photo);

            return redirect()->route('virtual-try-on-models.index')->with('success', 'Model virtual try-on berhasil ditambahkan.');
        } catch (\Throwable $e) {
            Log::error('Gagal menambahkan model virtual try-on: '.$e->getMessage(), ['exception' => $e]);

            return back()->with('error', 'Gagal menambahkan model: '.$e->getMessage())->withInput();
        }
    }

    public function update(UpdateVirtualTryOnModelRequest $request, VirtualTryOnModel $virtualTryOnModel): RedirectResponse
    {
        $data = $request->validated();
        $photo = $request->file('photo');

        try {
            if ($request->has('is_active')) {
                $data['is_active'] = $request->boolean('is_active');
            }
            $this->modelService->updateModel($virtualTryOnModel, $data, $photo);

            return redirect()->route('virtual-try-on-models.index')->with('success', 'Model virtual try-on berhasil diperbarui.');
        } catch (\Throwable $e) {
            Log::error('Gagal memperbarui model virtual try-on: '.$e->getMessage(), ['exception' => $e]);

            return back()->with('error', 'Gagal memperbarui model: '.$e->getMessage())->withInput();
        }
    }

    public function destroy(VirtualTryOnModel $virtualTryOnModel): RedirectResponse
    {
        try {
            $this->modelService->deleteModel($virtualTryOnModel);

            return redirect()->route('virtual-try-on-models.index')->with('success', 'Model virtual try-on berhasil dihapus.');
        } catch (\Throwable $e) {
            Log::error('Gagal menghapus model virtual try-on: '.$e->getMessage(), ['exception' => $e]);

            return back()->with('error', 'Gagal menghapus model: '.$e->getMessage());
        }
    }
}
