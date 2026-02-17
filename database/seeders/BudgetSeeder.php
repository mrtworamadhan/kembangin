<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Budget;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BudgetSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Budget::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $user = User::first(); 

        if (!$user) {
            $this->command->info('Tidak ada user di database. Buat user dulu.');
            return;
        }

        $catDapur = Category::where('name', 'Belanja Dapur & Makan')->first();
        $catListrik = Category::where('name', 'Listrik, Air & Internet (Rumah)')->first();
        $catPendidikan = Category::where('name', 'Pendidikan / SPP Anak')->first();

        $budgets = [];

        if ($catDapur) {
            $budgets[] = [
                'user_id' => $user->id,
                'category_id' => $catDapur->id,
                'amount' => 3000000,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($catListrik) {
            $budgets[] = [
                'user_id' => $user->id,
                'category_id' => $catListrik->id,
                'amount' => 1000000,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($catPendidikan) {
            $budgets[] = [
                'user_id' => $user->id,
                'category_id' => $catPendidikan->id,
                'amount' => 1500000,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Insert ke database
        Budget::insert($budgets);
        
        $this->command->info('Seeder Budget berhasil dijalankan!');
    }
}