<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductService
{
    public function __construct(
        protected ProductRepositoryInterface $productRepository
    ) {}

    /**
     * Create a new product with its images and variant values.
     */
    public function createProduct(array $data, array $variantValues = [], array $images = []): Product
    {
        return DB::transaction(function () use ($data, $variantValues, $images) {
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            // Ensure slug is unique
            $originalSlug = $data['slug'];
            $counter = 1;
            while (Product::where('slug', $data['slug'])->exists()) {
                $data['slug'] = $originalSlug.'-'.$counter;
                $counter++;
            }

            // Handle primary image upload
            if (isset($data['primary_image']) && $data['primary_image'] instanceof UploadedFile) {
                $data['primary_image'] = $data['primary_image']->store('products', 'public');
            }

            unset($data['variant_values']);
            unset($data['gallery_images']);
            unset($data['deleted_images']);

            $product = $this->productRepository->create($data);

            // Sync variant values
            if (! empty($variantValues)) {
                $product->variantValues()->sync($variantValues);
            }

            // Handle gallery images
            foreach ($images as $index => $imageFile) {
                if ($imageFile instanceof UploadedFile) {
                    $path = $imageFile->store('products/gallery', 'public');
                    $product->images()->create([
                        'image_path' => $path,
                        'sort_order' => $index,
                    ]);
                }
            }

            Log::info('Product created', ['product_id' => $product->id]);

            return $product;
        });
    }

    /**
     * Update an existing product.
     */
    public function updateProduct(Product $product, array $data, array $variantValues = [], array $newImages = [], array $deletedImageIds = []): Product
    {
        return DB::transaction(function () use ($product, $data, $variantValues, $newImages, $deletedImageIds) {
            // Check if slug changed
            if (! empty($data['slug']) && $data['slug'] !== $product->slug) {
                $originalSlug = $data['slug'];
                $counter = 1;
                while (Product::where('slug', $data['slug'])->where('id', '!=', $product->id)->exists()) {
                    $data['slug'] = $originalSlug.'-'.$counter;
                    $counter++;
                }
            }

            // Handle primary image replacement
            if (isset($data['primary_image']) && $data['primary_image'] instanceof UploadedFile) {
                // Delete old primary image if exists
                if ($product->primary_image && Storage::disk('public')->exists($product->primary_image)) {
                    Storage::disk('public')->delete($product->primary_image);
                }

                $data['primary_image'] = $data['primary_image']->store('products', 'public');
            } elseif (! isset($data['primary_image'])) {
                // If not provided in update, don't overwrite with null
                unset($data['primary_image']);
            }

            unset($data['variant_values']);
            unset($data['gallery_images']);
            unset($data['deleted_images']);

            $this->productRepository->update($product, $data);

            // Sync variant values
            if (isset($variantValues)) {
                $product->variantValues()->sync($variantValues);
            }

            // Handle deleted gallery images
            if (! empty($deletedImageIds)) {
                $imagesToDelete = $product->images()->whereIn('id', $deletedImageIds)->get();
                foreach ($imagesToDelete as $img) {
                    if (Storage::disk('public')->exists($img->image_path)) {
                        Storage::disk('public')->delete($img->image_path);
                    }
                    $img->delete();
                }
            }

            // Handle new gallery images
            $currentMaxOrder = $product->images()->max('sort_order') ?? -1;
            foreach ($newImages as $index => $imageFile) {
                if ($imageFile instanceof UploadedFile) {
                    $path = $imageFile->store('products/gallery', 'public');
                    $product->images()->create([
                        'image_path' => $path,
                        'sort_order' => $currentMaxOrder + 1 + $index,
                    ]);
                }
            }

            Log::info('Product updated', ['product_id' => $product->id]);

            return $product;
        });
    }

    /**
     * Delete a product and its images.
     */
    public function deleteProduct(Product $product): bool
    {
        return DB::transaction(function () use ($product) {
            // Delete primary image
            if ($product->primary_image && Storage::disk('public')->exists($product->primary_image)) {
                Storage::disk('public')->delete($product->primary_image);
            }

            // Delete gallery images
            foreach ($product->images as $img) {
                if (Storage::disk('public')->exists($img->image_path)) {
                    Storage::disk('public')->delete($img->image_path);
                }
            }

            $deleted = $this->productRepository->delete($product);

            if ($deleted) {
                Log::info('Product deleted', ['product_id' => $product->id]);
            }

            return $deleted;
        });
    }
}
