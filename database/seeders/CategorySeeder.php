<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // Disable foreign key checks sementara biar aman saat truncate data
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Category::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $categories = [
            // ==========================================
            // 1. RANAH BISNIS (GROUP: BUSINESS)
            // ==========================================
            
            // --- INCOME (PENDAPATAN) ---
            ['id' => 1, 'name' => 'Penjualan Produk', 'type' => 'income', 'group' => 'business', 'nature' => null, 'productivity' => null, 'business_id' => null, 'user_id' => null],
            ['id' => 2, 'name' => 'Penjualan Jasa', 'type' => 'income', 'group' => 'business', 'nature' => null, 'productivity' => null, 'business_id' => null, 'user_id' => null],
            ['id' => 3, 'name' => 'Suntikan Modal Tambahan', 'type' => 'income', 'group' => 'business', 'nature' => null, 'productivity' => null, 'business_id' => null, 'user_id' => null], // Bisnis nerima uang dari dompet pribadi

            // --- EXPENSE (PENGELUARAN OPERASIONAL) ---
            ['id' => 4, 'name' => 'Bahan Baku / Pembelian Stok', 'type' => 'expense', 'group' => 'business', 'nature' => 'need', 'productivity' => 'productive', 'business_id' => null, 'user_id' => null], // Aset berputar
            ['id' => 5, 'name' => 'Gaji Karyawan', 'type' => 'expense', 'group' => 'business', 'nature' => 'need', 'productivity' => 'productive', 'business_id' => null, 'user_id' => null], // Investasi SDM
            ['id' => 6, 'name' => 'Marketing & Iklan', 'type' => 'expense', 'group' => 'business', 'nature' => 'need', 'productivity' => 'productive', 'business_id' => null, 'user_id' => null], // Mendatangkan omzet
            ['id' => 7, 'name' => 'Sewa Tempat', 'type' => 'expense', 'group' => 'business', 'nature' => 'need', 'productivity' => 'neutral', 'business_id' => null, 'user_id' => null], // Kewajiban bulanan
            ['id' => 8, 'name' => 'Listrik, Air & Internet (Usaha)', 'type' => 'expense', 'group' => 'business', 'nature' => 'need', 'productivity' => 'neutral', 'business_id' => null, 'user_id' => null], // Kewajiban operasional
            
            // --- TRANSFER OUT (UANG KELUAR KE PEMILIK) ---
            ['id' => 9, 'name' => 'Penarikan Prive / Deviden', 'type' => 'expense', 'group' => 'business', 'nature' => 'want', 'productivity' => 'neutral', 'business_id' => null, 'user_id' => null], // Bisnis ngeluarin duit buat si Owner


            // ==========================================
            // 2. RANAH KELUARGA / DAPUR (GROUP: PERSONAL)
            // ==========================================
            
            // --- INCOME (PENDAPATAN KELUARGA) ---
            ['id' => 10, 'name' => 'Gaji / Penghasilan Utama', 'type' => 'income', 'group' => 'personal', 'nature' => null, 'productivity' => null, 'business_id' => null, 'user_id' => null],
            ['id' => 11, 'name' => 'Hasil Bisnis (Prive / Deviden)', 'type' => 'income', 'group' => 'personal', 'nature' => null, 'productivity' => null, 'business_id' => null, 'user_id' => null], // Dapur nerima uang dari toko

            // --- EXPENSE: KEBUTUHAN POKOK (NEED) ---
            ['id' => 12, 'name' => 'Belanja Dapur & Makan', 'type' => 'expense', 'group' => 'personal', 'nature' => 'need', 'productivity' => 'consumptive', 'business_id' => null, 'user_id' => null], // Lenyap dimakan
            ['id' => 13, 'name' => 'Listrik, Air & Internet (Rumah)', 'type' => 'expense', 'group' => 'personal', 'nature' => 'need', 'productivity' => 'consumptive', 'business_id' => null, 'user_id' => null], // Habis pakai
            ['id' => 14, 'name' => 'Bensin & Transportasi', 'type' => 'expense', 'group' => 'personal', 'nature' => 'need', 'productivity' => 'consumptive', 'business_id' => null, 'user_id' => null], // Habis pakai
            ['id' => 15, 'name' => 'Pendidikan / SPP Anak', 'type' => 'expense', 'group' => 'personal', 'nature' => 'need', 'productivity' => 'productive', 'business_id' => null, 'user_id' => null], // Investasi Otak
            ['id' => 16, 'name' => 'Cicilan Rumah / KPR', 'type' => 'expense', 'group' => 'personal', 'nature' => 'need', 'productivity' => 'neutral', 'business_id' => null, 'user_id' => null], // Menahan Aset
            ['id' => 17, 'name' => 'Cicilan Kendaraan', 'type' => 'expense', 'group' => 'personal', 'nature' => 'need', 'productivity' => 'neutral', 'business_id' => null, 'user_id' => null], // Kewajiban
            
            // --- EXPENSE: GAYA HIDUP & IMPIAN (WANT) ---
            ['id' => 18, 'name' => 'Makan di Luar / Ngopi / Jajan', 'type' => 'expense', 'group' => 'personal', 'nature' => 'want', 'productivity' => 'consumptive', 'business_id' => null, 'user_id' => null], // Bocor alus
            ['id' => 19, 'name' => 'Hiburan / Liburan / Belanja', 'type' => 'expense', 'group' => 'personal', 'nature' => 'want', 'productivity' => 'consumptive', 'business_id' => null, 'user_id' => null], // Konsumtif
            ['id' => 20, 'name' => 'Realisasi Target & Impian', 'type' => 'expense', 'group' => 'personal', 'nature' => 'want', 'productivity' => 'consumptive', 'business_id' => null, 'user_id' => null], // Saat target/goal dieksekusi/dibeli
            
            // --- EXPENSE: MASA DEPAN / INVESTASI (SAVING) ---
            ['id' => 21, 'name' => 'Investasi (Saham/Emas/Reksadana)', 'type' => 'expense', 'group' => 'personal', 'nature' => 'saving', 'productivity' => 'productive', 'business_id' => null, 'user_id' => null], // Aset bertumbuh
            ['id' => 22, 'name' => 'Suntik Modal ke Bisnis', 'type' => 'expense', 'group' => 'personal', 'nature' => 'saving', 'productivity' => 'productive', 'business_id' => null, 'user_id' => null], // Uang pribadi dikirim ke toko (Investasi)
            
            // --- TRANSFER / PENGELUARAN LAINNYA ---
            ['id' => 23, 'name' => 'Top Up ke Target / Impian', 'type' => 'transfer', 'group' => 'personal', 'nature' => 'saving', 'productivity' => 'productive', 'business_id' => null, 'user_id' => null], // Mutasi saldo dompet ke celengan
            ['id' => 24, 'name' => 'Pengeluaran Rumah Tangga Lainnya', 'type' => 'expense', 'group' => 'personal', 'nature' => 'need', 'productivity' => 'consumptive', 'business_id' => null, 'user_id' => null], // Fallback/Lain-lain
        ];

        foreach ($categories as $cat) {
            Category::create($cat);
        }
    }
}