<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = [
            [
                'full_name' => 'Cali Axmed Salaad',
                'phone' => '0615112233',
                'address' => 'Xamar Weyne, Muqdisho',
                'status' => 'active',
            ],
            [
                'full_name' => 'Faadumo Jaamac',
                'phone' => '0615998877',
                'address' => 'Hodan, Muqdisho',
                'status' => 'active',
            ],
            [
                'full_name' => 'maanka',
                'phone' => '683632095',
                'status' => 'active',
            ],
        ];

        foreach ($customers as $customer) {
            Customer::updateOrCreate(
                ['phone' => $customer['phone']],
                $customer
            );
        }
    }
}
