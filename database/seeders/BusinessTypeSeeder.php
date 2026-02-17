<?php

namespace Database\Seeders;

use App\Models\BusinessType;
use Illuminate\Database\Seeder;

class BusinessTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'Kuliner (F&B)',
            'Fashion & Retail',
            'Jasa & Layanan (Service)',
            'Teknologi & Digital',
            'Pertanian & Agrobisnis',
            'Kesehatan & Kecantikan',
            'Pendidikan',
            'Otomotif & Bengkel',
            'Lainnya',
        ];

        foreach ($types as $name) {
            BusinessType::create([
                'name' => $name,
                'slug' => str()->slug($name),
            ]);
        }
    }
}