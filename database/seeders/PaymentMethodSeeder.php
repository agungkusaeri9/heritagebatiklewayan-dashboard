<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $methods = [
            [
                'name' => 'Bank Central Asia (BCA)',
                'account_name' => 'PT Batik Nusantara',
                'account_number' => '8830192841',
                'is_active' => true,
            ],
            [
                'name' => 'Bank Mandiri',
                'account_name' => 'PT Batik Nusantara',
                'account_number' => '1370019284712',
                'is_active' => true,
            ],
            [
                'name' => 'Bank Rakyat Indonesia (BRI)',
                'account_name' => 'PT Batik Nusantara',
                'account_number' => '034101000789562',
                'is_active' => true,
            ],
            [
                'name' => 'QRIS Instant Payment',
                'account_name' => 'Batik Nusantara Official',
                'account_number' => 'NMID-9382109283',
                'is_active' => true,
            ],
        ];

        foreach ($methods as $m) {
            PaymentMethod::firstOrCreate(
                ['name' => $m['name']],
                $m
            );
        }
    }
}
