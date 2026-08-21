<?php

namespace Database\Seeders;

use App\Models\masterData\ProductName\ProductNameModel;
use App\Models\User;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        //User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
        // ProductName::factory(10)->create();

        $this->call([
            // Bộ đơn vị tính chuẩn kèm thông tin quy đổi, chạy lại nhiều lần vẫn an toàn
            UnitSeeder::class,
            ProductNameSeeder::class,
        ]);
        
    }
}
