<?php

namespace App\Jobs;

use App\Models\ProductVirtualTryOn;
use App\Services\FashnAiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GenerateProductVirtualTryOnJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $productVirtualTryOnId)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(FashnAiService $fashnAi): void
    {
        $tryOn = ProductVirtualTryOn::with(['virtualModel', 'product.images'])->find($this->productVirtualTryOnId);

        if (! $tryOn) {
            Log::warning("GenerateProductVirtualTryOnJob: Record ProductVirtualTryOn #{$this->productVirtualTryOnId} not found.");

            return;
        }

        try {
            $tryOn->update([
                'status' => 'processing',
                'error_message' => null,
            ]);

            if (! $tryOn->virtualModel || empty($tryOn->virtualModel->photo)) {
                throw new \Exception('Foto avatar model virtual tidak ditemukan.');
            }

            // Get product image (primary_image or first gallery image)
            $productImage = $tryOn->product?->primary_image ?: $tryOn->product?->images->first()?->image;
            if (empty($productImage)) {
                throw new \Exception('Foto produk batik tidak ditemukan pada data produk.');
            }

            $cdnPhotoUrl = null;

            // Step 1: Generate Photo using tryon-max (model_image + product_image)
            if (empty($tryOn->photo)) {
                $modelInput = $fashnAi->prepareImageInput($tryOn->virtualModel->photo);
                $productInput = $fashnAi->prepareImageInput($productImage);

                if (empty($tryOn->task_id_photo)) {
                    $tryOn->update([
                        'progress' => 20,
                        'step_label' => 'Mengirim permintaan tryon-max ke Fashn AI...',
                    ]);

                    $tryonRun = $fashnAi->runTryOnMax($modelInput, $productInput);
                    $taskIdPhoto = $tryonRun['id'];
                    $tryOn->update(['task_id_photo' => $taskIdPhoto]);
                } else {
                    $taskIdPhoto = $tryOn->task_id_photo;
                }

                $tryOn->update([
                    'progress' => 35,
                    'step_label' => 'Men-generate busana pada model (tryon-max)...',
                ]);

                $photoCompleted = $fashnAi->waitForCompletion($taskIdPhoto, 180, 2);
                $cdnPhotoUrl = $photoCompleted['output'][0];

                $tryOn->update([
                    'progress' => 50,
                    'step_label' => 'Mengunduh foto hasil try-on ke penyimpanan lokal...',
                ]);

                $localPhotoPath = $fashnAi->downloadAndStoreImage($cdnPhotoUrl, 'product-virtual-try-ons/photos');

                $tryOn->update([
                    'photo' => $localPhotoPath,
                    'progress' => $tryOn->generate_video ? 60 : 100,
                    'step_label' => $tryOn->generate_video ? 'Foto try-on selesai dibuat. Melanjutkan ke video...' : 'Selesai',
                    'status' => $tryOn->generate_video ? 'processing' : 'completed',
                ]);
            }

            // Step 2: Generate Video if requested and not yet stored
            if ($tryOn->generate_video && empty($tryOn->video)) {
                $duration = $fashnAi->getVideoDuration();
                $resolution = $fashnAi->getVideoResolution();

                if (empty($tryOn->task_id_video)) {
                    $tryOn->update([
                        'progress' => 65,
                        'step_label' => "Mengirim permintaan image-to-video ({$resolution}, {$duration}s) ke Fashn AI...",
                    ]);

                    // Use CDN photo URL if available, or prepare image input from local photo
                    $imageForVideo = $cdnPhotoUrl ?: $fashnAi->prepareImageInput($tryOn->photo);

                    $videoRun = $fashnAi->runImageToVideo($imageForVideo, $duration, $resolution);
                    $taskIdVideo = $videoRun['id'];
                    $tryOn->update(['task_id_video' => $taskIdVideo]);
                } else {
                    $taskIdVideo = $tryOn->task_id_video;
                }

                $tryOn->update([
                    'progress' => 75,
                    'step_label' => "Men-generate video model (image-to-video, {$resolution}, {$duration}s)...",
                ]);

                $videoCompleted = $fashnAi->waitForCompletion($taskIdVideo, 300, 3);
                $cdnVideoUrl = $videoCompleted['output'][0];

                $tryOn->update([
                    'progress' => 90,
                    'step_label' => 'Mengunduh video hasil generasi ke penyimpanan lokal...',
                ]);

                $localVideoPath = $fashnAi->downloadAndStoreVideo($cdnVideoUrl, 'product-virtual-try-ons/videos');

                $tryOn->update([
                    'video' => $localVideoPath,
                    'progress' => 100,
                    'step_label' => 'Selesai',
                    'status' => 'completed',
                ]);
            }
        } catch (\Throwable $e) {
            Log::error("GenerateProductVirtualTryOnJob error on tryOn #{$this->productVirtualTryOnId}: {$e->getMessage()}", [
                'trace' => $e->getTraceAsString(),
            ]);

            $tryOn->update([
                'status' => 'failed',
                'progress' => 0,
                'step_label' => 'Gagal: '.Str::limit($e->getMessage(), 120),
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}
