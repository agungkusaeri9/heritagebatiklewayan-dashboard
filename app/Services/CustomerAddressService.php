<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CustomerAddressService
{
    /**
     * @var list<string>
     */
    private const RELATIONS = ['province', 'city', 'subdistrict'];

    /**
     * Get all addresses belonging to the given user, primary first.
     *
     * @return Collection<int, CustomerAddress>
     */
    public function listForUser(User $user): Collection
    {
        return $this->resolveCustomer($user)
            ->addresses()
            ->with(self::RELATIONS)
            ->orderByDesc('is_primary')
            ->latest('id')
            ->get();
    }

    /**
     * Find a single address owned by the given user.
     *
     * @throws NotFoundHttpException
     */
    public function findForUser(User $user, int $addressId): CustomerAddress
    {
        $address = $this->resolveCustomer($user)
            ->addresses()
            ->with(self::RELATIONS)
            ->find($addressId);

        if (! $address) {
            throw new NotFoundHttpException('Alamat tidak ditemukan.');
        }

        return $address;
    }

    /**
     * Create a new address for the given user.
     *
     * @param  array{label?: string|null, recipient_name: string, phone: string, full_address: string, province_id: int, city_id: int, subdistrict_id: int, postal_code?: string|null, is_primary?: bool}  $data
     */
    public function createForUser(User $user, array $data): CustomerAddress
    {
        $customer = $this->resolveCustomer($user);

        return DB::transaction(function () use ($customer, $data) {
            $isFirstAddress = ! $customer->addresses()->exists();
            $isPrimary = $isFirstAddress || (bool) ($data['is_primary'] ?? false);

            if ($isPrimary) {
                $customer->addresses()->update(['is_primary' => false]);
            }

            $address = $customer->addresses()->create([
                ...$data,
                'label' => $data['label'] ?? 'Rumah',
                'is_primary' => $isPrimary,
            ]);

            return $address->load(self::RELATIONS);
        });
    }

    /**
     * Update an address owned by the given user.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateForUser(User $user, int $addressId, array $data): CustomerAddress
    {
        $address = $this->findForUser($user, $addressId);

        return DB::transaction(function () use ($address, $data) {
            if (array_key_exists('is_primary', $data)) {
                if ($data['is_primary']) {
                    $this->clearPrimary($address);
                } elseif ($address->is_primary) {
                    // Primary status can only be moved, not removed.
                    unset($data['is_primary']);
                }
            }

            $address->update($data);

            return $address->refresh()->load(self::RELATIONS);
        });
    }

    /**
     * Mark an address owned by the given user as primary.
     */
    public function setPrimaryForUser(User $user, int $addressId): CustomerAddress
    {
        $address = $this->findForUser($user, $addressId);

        return DB::transaction(function () use ($address) {
            $this->clearPrimary($address);
            $address->update(['is_primary' => true]);

            return $address->load(self::RELATIONS);
        });
    }

    /**
     * Delete an address owned by the given user, promoting another address to primary if needed.
     */
    public function deleteForUser(User $user, int $addressId): void
    {
        $address = $this->findForUser($user, $addressId);

        DB::transaction(function () use ($address) {
            $wasPrimary = $address->is_primary;
            $customerId = $address->customer_id;

            $address->delete();

            if ($wasPrimary) {
                CustomerAddress::where('customer_id', $customerId)
                    ->latest('id')
                    ->first()
                    ?->update(['is_primary' => true]);
            }
        });
    }

    /**
     * Unset the primary flag on all other addresses of the same customer.
     */
    private function clearPrimary(CustomerAddress $address): void
    {
        CustomerAddress::where('customer_id', $address->customer_id)
            ->whereKeyNot($address->getKey())
            ->update(['is_primary' => false]);
    }

    /**
     * Resolve the customer profile of the given user.
     *
     * @throws AccessDeniedHttpException
     */
    private function resolveCustomer(User $user): Customer
    {
        $customer = $user->customer;

        if (! $customer) {
            throw new AccessDeniedHttpException('Akun ini tidak memiliki profil customer.');
        }

        return $customer;
    }
}
