<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice #{{ $order->number }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 13px; color: #555; }
        
        .header-table { width: 100%; margin-bottom: 40px; }
        .brand-name { font-size: 20px; font-weight: bold; color: {{ $color }}; letter-spacing: 1px; }
        
        .invoice-title { font-size: 40px; font-weight: 300; color: #ddd; text-align: right; line-height: 1; }
        .invoice-number { font-size: 14px; color: #888; text-align: right; }

        .items-table { width: 100%; border-collapse: collapse; margin-top: 30px; }
        .items-table th { text-align: left; padding: 10px 0; border-bottom: 2px solid #eee; color: #888; text-transform: uppercase; font-size: 11px; }
        .items-table td { padding: 15px 0; border-bottom: 1px solid #eee; }
        
        .text-right { text-align: right; }
        .total-big { font-size: 24px; font-weight: bold; color: {{ $color }}; }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td valign="top">
                @if($logo)
                    <img src="{{ public_path('storage/' . $logo) }}" style="height: 60px; margin-bottom: 10px;">
                @else
                    <div class="brand-name">{{ $order->business->name }}</div>
                @endif
                <div style="font-size: 12px; color: #999; margin-top: 5px;">
                    {{ $order->business->address }}<br>
                    {{ $order->business->email }}
                </div>
            </td>
            <td valign="top" class="text-right">
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-number">#{{ $order->number }}</div>
                <div style="margin-top: 5px; font-size: 12px;">{{ \Carbon\Carbon::parse($order->order_date)->translatedFormat('d F Y') }}</div>
            </td>
        </tr>
    </table>

    <div style="margin-bottom: 30px;">
        <span style="color: #999; font-size: 11px; text-transform: uppercase;">Ditagihkan Kepada:</span><br>
        <strong style="font-size: 16px; color: #333;">{{ $order->customer->name }}</strong><br>
        <span style="color: #777;">{{ $order->customer->address }}</span>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th width="50%">Deskripsi</th>
                <th width="10%" class="text-right">Qty</th>
                <th width="20%" class="text-right">Harga</th>
                <th width="20%" class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr>
                <td>
                    <strong style="color: #333;">{{ $item->product->name }}</strong>
                </td>
                <td class="text-right">{{ $item->quantity }}</td>
                <td class="text-right">{{ number_format($item->unit_price, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table width="100%" style="margin-top: 20px;">
        <tr>
            <td width="60%"></td>
            <td width="40%" class="text-right">
                <table width="100%">
                    <tr>
                        <td style="padding: 5px; color: #777;">Total Tagihan</td>
                        <td style="padding: 5px;" class="text-right total-big">
                            Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    @if($order->business->signature || $order->business->signer_name)
    <div style="margin-top: 60px;">
        <table width="100%">
            <tr>
                <td width="70%" valign="bottom" style="font-size: 11px; color: #aaa;">
                    Terima kasih atas kepercayaan Anda.<br>
                    Dokumen ini sah dan diproses oleh komputer.
                </td>
                <td width="30%" class="text-right">
                    @if($order->business->signature)
                        <img src="{{ public_path('storage/' . $order->business->signature) }}" style="height: 60px; opacity: 0.8;">
                    @else
                        <br><br><br>
                    @endif
                    <div style="border-top: 1px solid #ddd; margin-top: 5px; padding-top: 5px;">
                        {{ $order->business->signer_name }}
                    </div>
                </td>
            </tr>
        </table>
    </div>
    @endif

</body>
</html>