<?php

namespace App\Http\Controllers;

use App\Http\Requests\AppVersion\StoreAppVersionRequest;
use App\Http\Requests\AppVersion\UpdateAppVersionRequest;
use App\Models\AppVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AppVersionController extends Controller
{
    public function index(Request $request): Response
    {
        $perPage = (int) $request->input('per_page', 10);
        $search = $request->input('search');
        $type = $request->input('type');
        $status = $request->input('status');

        $query = AppVersion::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('version', 'like', "%{$search}%")
                    ->orWhere('file_name', 'like', "%{$search}%")
                    ->orWhere('release_notes', 'like', "%{$search}%");
            });
        }

        if ($type && $type !== 'all') {
            $query->where('type', $type);
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $appVersions = $query->orderByDesc('is_latest')
            ->orderByDesc('version_code')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('app-versions/Index', [
            'appVersions' => $appVersions,
            'filters' => $request->only(['search', 'type', 'status', 'per_page']),
        ]);
    }

    public function store(StoreAppVersionRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $file = $request->file('file');

        unset($validated['file']);

        if ($file) {
            $ext = strtolower($file->getClientOriginalExtension());
            $filename = Str::slug($validated['name']).'-'.$validated['type'].'-v'.$validated['version'].'-'.time().'.'.$ext;
            $validated['file_path'] = $file->storeAs('apps', $filename, 'public');
            $validated['file_name'] = $file->getClientOriginalName();
            $validated['file_size'] = $file->getSize();
        }

        $hasLatestForType = AppVersion::where('type', $validated['type'])
            ->where('is_latest', true)
            ->exists();

        $validated['is_latest'] = (bool) ($validated['is_latest'] ?? ! $hasLatestForType);
        $validated['is_force_update'] = (bool) ($validated['is_force_update'] ?? false);
        $validated['is_maintenance'] = (bool) ($validated['is_maintenance'] ?? false);
        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);
        $validated['released_at'] = $validated['released_at'] ?? now();

        if ($validated['is_latest']) {
            AppVersion::where('type', $validated['type'])->update(['is_latest' => false]);
        }

        AppVersion::create($validated);

        return redirect()->back()->with('success', 'Versi aplikasi berhasil ditambahkan.');
    }

    public function update(UpdateAppVersionRequest $request, AppVersion $app_version): RedirectResponse
    {
        $validated = $request->validated();
        $file = $request->file('file');

        unset($validated['file']);

        if ($file) {
            if ($app_version->file_path && Storage::disk('public')->exists($app_version->file_path)) {
                Storage::disk('public')->delete($app_version->file_path);
            }

            $ext = strtolower($file->getClientOriginalExtension());
            $filename = Str::slug($validated['name']).'-'.$validated['type'].'-v'.$validated['version'].'-'.time().'.'.$ext;
            $validated['file_path'] = $file->storeAs('apps', $filename, 'public');
            $validated['file_name'] = $file->getClientOriginalName();
            $validated['file_size'] = $file->getSize();
        }

        $validated['is_latest'] = (bool) ($validated['is_latest'] ?? $app_version->is_latest);
        $validated['is_force_update'] = (bool) ($validated['is_force_update'] ?? false);
        $validated['is_maintenance'] = (bool) ($validated['is_maintenance'] ?? false);
        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);
        $validated['released_at'] = $validated['released_at'] ?? $app_version->released_at ?? now();

        if ($validated['is_latest']) {
            AppVersion::where('type', $validated['type'])
                ->where('id', '!=', $app_version->id)
                ->update(['is_latest' => false]);
        }

        $app_version->update($validated);

        return redirect()->back()->with('success', 'Versi aplikasi berhasil diperbarui.');
    }

    public function destroy(AppVersion $app_version): RedirectResponse
    {
        $wasLatest = $app_version->is_latest;
        $type = $app_version->type;

        if ($app_version->file_path && Storage::disk('public')->exists($app_version->file_path)) {
            Storage::disk('public')->delete($app_version->file_path);
        }

        $app_version->delete();

        if ($wasLatest) {
            $nextLatest = AppVersion::where('type', $type)
                ->where('is_active', true)
                ->orderByDesc('version_code')
                ->orderByDesc('id')
                ->first();

            $nextLatest?->update(['is_latest' => true]);
        }

        return redirect()->back()->with('success', 'Versi aplikasi berhasil dihapus.');
    }

    public function toggle(AppVersion $app_version): RedirectResponse
    {
        $app_version->update([
            'is_active' => ! $app_version->is_active,
        ]);

        return redirect()->back()->with('success', 'Status versi aplikasi berhasil diubah.');
    }

    public function setLatest(AppVersion $app_version): RedirectResponse
    {
        $app_version->markAsLatest();

        return redirect()->back()->with('success', "Versi v{$app_version->version} dijadikan versi utama ({$app_version->type}).");
    }

    public function toggleForceUpdate(AppVersion $app_version): RedirectResponse
    {
        $app_version->update([
            'is_force_update' => ! $app_version->is_force_update,
        ]);

        return redirect()->back()->with('success', 'Status wajib update berhasil diubah.');
    }

    public function download(AppVersion $app_version): StreamedResponse|RedirectResponse
    {
        if (! $app_version->file_path || ! Storage::disk('public')->exists($app_version->file_path)) {
            return redirect()->back()->with('error', 'File aplikasi tidak ditemukan di penyimpanan.');
        }

        return Storage::disk('public')->download(
            $app_version->file_path,
            $app_version->file_name ?: basename($app_version->file_path)
        );
    }
}
