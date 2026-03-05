<?php

namespace App\Observers;

use App\Models\OrderItem;
use App\Models\PurchaseItem;
use App\Models\Product;

class OrderItemObserver
{
    public function creating(OrderItem $orderItem)
    {
        $latestPurchase = PurchaseItem::where('product_id', $orderItem->product_id)
            ->latest('id')
            ->first();

        $basePriceDefault = Product::where('id', $orderItem->product_id)
            ->value('base_price');

        $basePrice = $latestPurchase 
            ? $latestPurchase->unit_cost 
            : ($orderItem->product->cost ?? 0);

        $orderItem->sale_price = $orderItem->unit_price;    
        $orderItem->base_price = $basePrice;
        $orderItem->total_base_price = $basePrice * $orderItem->quantity;

        if (!$orderItem->sale_price) {
            $orderItem->sale_price = $orderItem->product->sale_price ?? $basePriceDefault?? 0;
        }
    }
}
