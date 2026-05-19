<?php

namespace Database\Seeders;

use App\Models\Purok;
use Illuminate\Database\Seeder;

class PurokSeeder extends Seeder
{
    public function run(): void
    {
        $puroks = [
            ['name' => 'Purok Avocado Evergreen', 'description' => 'Purok Avocado Evergreen - Barangay Purisima', 'sort_order' => 1],
            ['name' => 'Purok Avocado Maroon', 'description' => 'Purok Avocado Maroon - Barangay Purisima', 'sort_order' => 2],
            ['name' => 'Purok Calamansi', 'description' => 'Purok Calamansi - Barangay Purisima', 'sort_order' => 3],
            ['name' => 'Purok Citrus 1', 'description' => 'Purok Citrus 1 - Barangay Purisima', 'sort_order' => 4],
            ['name' => 'Purok Citrus 2', 'description' => 'Purok Citrus 2 - Barangay Purisima', 'sort_order' => 5],
            ['name' => 'Purok Grapes', 'description' => 'Purok Grapes - Barangay Purisima', 'sort_order' => 6],
            ['name' => 'Purok Mangga 1', 'description' => 'Purok Mangga 1 - Barangay Purisima', 'sort_order' => 7],
            ['name' => 'Purok Mangga 2', 'description' => 'Purok Mangga 2 - Barangay Purisima', 'sort_order' => 8],
            ['name' => 'Purok Tambis', 'description' => 'Purok Tambis - Barangay Purisima', 'sort_order' => 9],
        ];

        foreach ($puroks as $purok) {
            Purok::firstOrCreate(['name' => $purok['name']], array_merge($purok, ['is_active' => true]));
        }
    }
}
