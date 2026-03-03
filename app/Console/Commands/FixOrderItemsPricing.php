<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\OrderItem;
use App\Models\PurchaseItem;

class FixOrderItemsPricing extends Command
{
    protected $signature = 'app:fix-order-pricing';
    protected $description = 'Memperbaiki data order_item yang tidak punya base_price & sale_price';

    public function handle()
    {
        $this->info("Menyiapkan misi perbaikan HPP...");

        $items = OrderItem::whereNull('base_price')->with(['order', 'product'])->get();

        if ($items->isEmpty()) {
            $this->info("Semua data order_item sudah memiliki harga dasar. Aman!");
            return;
        }

        $bar = $this->output->createProgressBar(count($items));

        foreach ($items as $item) {
            $salePrice = $item->quantity > 0 ? ($item->subtotal / $item->quantity) : 0;

            $purchaseItem = PurchaseItem::where('product_id', $item->product_id)
                ->whereHas('purchase', function($q) use ($item) {
                    $q->where('date', '<=', $item->order->order_date ?? now());
                })
                ->latest('id') 
                ->first();

            if ($purchaseItem) {
                $basePrice = $purchaseItem->unit_cost; 
            } else {
                $basePrice = $item->product->base_price ?? 0; 
            }

            $item->update([
                'sale_price' => $salePrice,
                'base_price' => $basePrice,
                'total_base_price' => $basePrice * $item->quantity
            ]);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("BOOM! 💥 Data order_items berhasil di-upgrade! Laporan profit sekarang 100% akurat.");
    }
}