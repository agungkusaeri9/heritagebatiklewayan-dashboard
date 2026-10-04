<?php

namespace App\Services;

use App\Models\PaymentMethod;
use App\Repositories\Contracts\PaymentMethodRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PaymentMethodService
{
    public function __construct(
        protected PaymentMethodRepositoryInterface $paymentMethodRepository
    ) {}

    public function getPaymentMethodById(int $id): PaymentMethod
    {
        $paymentMethod = $this->paymentMethodRepository->findById($id);
        
        if (!$paymentMethod) {
            throw new \Exception('Metode Pembayaran tidak ditemukan.');
        }
        
        return $paymentMethod;
    }

    public function createPaymentMethod(array $data): PaymentMethod
    {
        return DB::transaction(function () use ($data) {
            if (isset($data['logo']) && $data['logo'] instanceof \Illuminate\Http\UploadedFile) {
                $data['logo'] = $data['logo']->store('payment_methods', 'public');
            }

            return $this->paymentMethodRepository->create([
                'name' => $data['name'],
                'account_number' => $data['account_number'],
                'account_name' => $data['account_name'],
                'logo' => $data['logo'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);
        });
    }

    public function updatePaymentMethod(int $id, array $data): bool
    {
        $paymentMethod = $this->getPaymentMethodById($id);

        return DB::transaction(function () use ($paymentMethod, $data) {
            if (isset($data['logo']) && $data['logo'] instanceof \Illuminate\Http\UploadedFile) {
                // Hapus logo lama jika ada
                if ($paymentMethod->logo) {
                    Storage::disk('public')->delete($paymentMethod->logo);
                }
                $data['logo'] = $data['logo']->store('payment_methods', 'public');
            } else {
                unset($data['logo']); // Jangan ubah jika tidak ada upload baru
            }

            return $this->paymentMethodRepository->update($paymentMethod, $data);
        });
    }

    public function deletePaymentMethod(int $id): bool
    {
        $paymentMethod = $this->getPaymentMethodById($id);

        return DB::transaction(function () use ($paymentMethod) {
            if ($paymentMethod->logo) {
                Storage::disk('public')->delete($paymentMethod->logo);
            }
            return $this->paymentMethodRepository->delete($paymentMethod);
        });
    }
}
