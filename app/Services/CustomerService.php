<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\User;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CustomerService
{
    public function __construct(
        protected CustomerRepositoryInterface $customerRepository
    ) {}

    public function getCustomerById(int $id): Customer
    {
        $customer = $this->customerRepository->findById($id);

        if (! $customer) {
            throw new \Exception('Customer tidak ditemukan.');
        }

        return $customer;
    }

    public function createCustomer(array $data): Customer
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'username' => $data['username'] ?? Str::slug($data['name']).'-'.rand(1000, 9999),
                'password' => Hash::make($data['password']),
            ]);

            return $this->customerRepository->create([
                'user_id' => $user->id,
                'phone' => $data['phone'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);
        });
    }

    public function updateCustomer(int $id, array $data): bool
    {
        $customer = $this->getCustomerById($id);

        return DB::transaction(function () use ($customer, $data) {
            $userData = [
                'name' => $data['name'],
                'email' => $data['email'],
            ];

            if (! empty($data['password'])) {
                $userData['password'] = Hash::make($data['password']);
            }

            $customer->user->update($userData);

            return $this->customerRepository->update($customer, [
                'phone' => $data['phone'] ?? $customer->phone,
                'is_active' => $data['is_active'] ?? $customer->is_active,
            ]);
        });
    }

    public function deleteCustomer(int $id): bool
    {
        $customer = $this->getCustomerById($id);

        return DB::transaction(function () use ($customer) {
            $user = $customer->user;
            $this->customerRepository->delete($customer);

            return $user->delete();
        });
    }
}
