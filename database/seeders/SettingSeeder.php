<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'outlet_name' => 'Magezz Coffee',
            'outlet_address' => 'Jl. Kopi Nikmat No. 1, Indonesia',
            'outlet_phone' => '0812-0000-0001',
            'receipt_footer' => 'Terima kasih atas kunjungan Anda. Sampai jumpa lagi!',
            'tax_rate' => '10',
            'tax_enabled' => '1',
        ];

        foreach ($settings as $key => $value) {
            Setting::set($key, $value);
        }
    }
}
