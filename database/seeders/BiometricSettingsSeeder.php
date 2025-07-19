<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class BiometricSettingsSeeder extends Seeder
{
    public function run(): void
    {
        Setting::updateOrCreate(
            ['key' => 'biometric_api_key'],
            ['value' => 'BMT_' . bin2hex(random_bytes(16))] // Generates: BMT_1a2b3c4d5e6f...
        );
    }
}