<?php

namespace App\Services;

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CategoryService
{
    public function __construct(
        private CategoryRepositoryInterface $categoryRepository,
        private ActivityLogService $activityLogService
    ) {}

    public function getCategories(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->categoryRepository->getAllPaginated($filters, $perPage);
    }

    public function getActiveCategories(): Collection
    {
        return $this->categoryRepository->getAllActive();
    }

    public function getCategoryById(int $id): ?Category
    {
        return $this->categoryRepository->findById($id);
    }

    public function getCategoryBySlug(string $slug): ?Category
    {
        return $this->categoryRepository->findBySlug($slug);
    }

    public function createCategory(array $data, ?UploadedFile $image = null): Category
    {
        return DB::transaction(function () use ($data, $image) {
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            } else {
                $data['slug'] = Str::slug($data['slug']);
            }

            if ($image instanceof UploadedFile) {
                $data['image'] = $image->store('categories', 'public');
            }

            $category = $this->categoryRepository->create($data);

            if (! $category) {
                throw new \Exception('Gagal menyimpan data kategori ke database.');
            }

            $this->activityLogService->log(
                'Create Category',
                "Created category: {$category->name}",
                ['category_id' => $category->id, 'slug' => $category->slug]
            );

            return $category;
        });
    }

    public function updateCategory(Category $category, array $data, ?UploadedFile $image = null): bool
    {
        return DB::transaction(function () use ($category, $data, $image) {
            $existingCategory = $this->categoryRepository->findById($category->id);
            if (! $existingCategory) {
                throw new \Exception('Data kategori tidak ditemukan di sistem.');
            }

            $oldData = $existingCategory->only(['name', 'slug', 'description', 'image', 'is_active']);

            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            } else {
                $data['slug'] = Str::slug($data['slug']);
            }

            if ($image instanceof UploadedFile) {
                if ($existingCategory->image && Storage::disk('public')->exists($existingCategory->image)) {
                    Storage::disk('public')->delete($existingCategory->image);
                }
                $data['image'] = $image->store('categories', 'public');
            } else {
                // Jangan overwrite gambar lama jika tidak ada file baru
                unset($data['image']);
            }

            $updated = $this->categoryRepository->update($existingCategory, $data);

            if (! $updated) {
                throw new \Exception('Gagal memperbarui data kategori ke database.');
            }

            $this->activityLogService->log(
                'Update Category',
                "Updated category: {$existingCategory->name}",
                [
                    'category_id' => $existingCategory->id,
                    'old' => $oldData,
                    'new' => $existingCategory->fresh()->only(['name', 'slug', 'description', 'image', 'is_active']),
                ]
            );

            return true;
        });
    }

    public function deleteCategory(Category $category): bool
    {
        return DB::transaction(function () use ($category) {
            $existingCategory = $this->categoryRepository->findById($category->id);
            if (! $existingCategory) {
                throw new \Exception('Data kategori tidak ditemukan di sistem.');
            }

            $categoryName = $existingCategory->name;
            $categoryId = $existingCategory->id;
            $categoryImage = $existingCategory->image;

            $deleted = $this->categoryRepository->delete($existingCategory);

            if (! $deleted) {
                throw new \Exception('Gagal menghapus data kategori dari database.');
            }

            if ($categoryImage && Storage::disk('public')->exists($categoryImage)) {
                Storage::disk('public')->delete($categoryImage);
            }

            $this->activityLogService->log(
                'Delete Category',
                "Deleted category: {$categoryName}",
                ['deleted_category_id' => $categoryId]
            );

            return true;
        });
    }
}
