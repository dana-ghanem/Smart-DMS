<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'General Documents', 'description' => 'General purpose documents'],
            ['name' => 'Contracts', 'description' => 'Legal contracts and agreements'],
            ['name' => 'Reports', 'description' => 'Business and financial reports'],
            ['name' => 'Invoices', 'description' => 'Invoices and billing documents'],
            ['name' => 'Presentations', 'description' => 'Presentation files and slides'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
