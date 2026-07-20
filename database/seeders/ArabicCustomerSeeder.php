<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Webkul\Customer\Models\Customer;

/**
 * Seeder for sample Arabic customers.
 *
 * Creates five registered customers with Arabic names. The remaining details
 * (email, phone, gender, date of birth, password) are filled with sensible
 * default values. Idempotent: existing customers (matched by email) are skipped.
 */
class ArabicCustomerSeeder extends Seeder
{
    /**
     * Default customer group id for registered customers ("general").
     */
    const CUSTOMER_GROUP_ID = 2;

    /**
     * Default channel id.
     */
    const CHANNEL_ID = 1;

    /**
     * Default password for all sample customers.
     */
    const DEFAULT_PASSWORD = 'password123';

    /**
     * Sample customers with Arabic names and default details.
     *
     * @var array<int, array<string, string>>
     */
    protected array $customers = [
        [
            'first_name' => 'رشيد',
            'last_name' => 'غالب',
            'email' => 'rasheed.ghaleb@example.com',
            'phone' => '0500000001',
            'date_of_birth' => '1990-01-15',
        ],
        [
            'first_name' => 'صلاح',
            'last_name' => 'منصور',
            'email' => 'salah.mansour@example.com',
            'phone' => '0500000002',
            'date_of_birth' => '1988-05-22',
        ],
        [
            'first_name' => 'اكرم',
            'last_name' => 'الصبري',
            'email' => 'akram.alsabri@example.com',
            'phone' => '0500000003',
            'date_of_birth' => '1992-09-10',
        ],
        [
            'first_name' => 'محمد',
            'last_name' => 'مارش',
            'email' => 'mohammed.marsh@example.com',
            'phone' => '0500000004',
            'date_of_birth' => '1995-03-30',
        ],
        [
            'first_name' => 'جمال',
            'last_name' => 'الشرعبي',
            'email' => 'jamal.alsharabi@example.com',
            'phone' => '0500000005',
            'date_of_birth' => '1985-11-05',
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->customers as $data) {
            if (Customer::where('email', $data['email'])->exists()) {
                $this->command->warn("Customer '{$data['email']}' already exists, skipping.");

                continue;
            }

            Customer::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'gender' => 'Male',
                'date_of_birth' => $data['date_of_birth'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => Hash::make(self::DEFAULT_PASSWORD),
                'customer_group_id' => self::CUSTOMER_GROUP_ID,
                'channel_id' => self::CHANNEL_ID,
                'subscribed_to_news_letter' => 0,
                'status' => 1,
                'is_verified' => 1,
                'is_suspended' => 0,
            ]);

            $this->command->info("Created customer: {$data['first_name']} {$data['last_name']} ({$data['email']})");
        }
    }
}
