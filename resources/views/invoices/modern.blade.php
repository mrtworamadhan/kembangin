<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice #{{ $order->number }}</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 14px; color: #333; line-height: 1.4; }
        .container { width: 100%; }
        .header-table { width: 100%; border-bottom: 2px solid {{ $color }}; padding-bottom: 20px; margin-bottom: 20px; }
        .info-table { width: 100%; margin-bottom: 20px; }
        .items-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .items-table th { background-color: {{ $color }}; color: #fff; padding: 10px; text-align: left; }
        .items-table td { padding: 10px; border-bottom: 1px solid #eee; }
        .text-right { text-align: right; }
        .text-bold { font-weight: bold; }
        .text-color { color: {{ $color }}; }
        .total-section { margin-top: 20px; text-align: right; }
        .total-label { font-size: 16px; font-weight: bold; margin-right: 20px; }
        .total-amount { font-size: 20px; font-weight: bold; color: {{ $color }}; }
    </style>
</head>
<body>

    @php
        $gross_grand_total = 0;
        foreach($order->items as $item) {
            $gross_grand_total += ($item->quantity * $item->unit_price);
        }
        $final_display_total = $show_discount ? $order->total_amount : $gross_grand_total;
    @endphp

    <table class="header-table">
        <tr>
            <td width="15%" valign="top">
                @if($logo)
                    <img src="{{ public_path('storage/' . $logo) }}" style="height: 70px; margin-bottom: 10px;">
                @else
                    <h1 style="color: {{ $color }}; margin: 0;">{{ $order->business->name }}</h1>
                @endif
                
            </td>
            <td width="55%" valign="top">
                
                <div style="font-size: 22px; color: #555;">
                    <strong>{{ $order->business->name }}</strong><br>
                </div>
                <div style="font-size: 12px; color: #555;">
                    {{ $order->business->address }}<br>
                    Email: {{ $order->business->email ?? '-' }} | Telp: {{ $order->business->phone ?? '-' }}
                </div>
            </td>
            <td width="30%" valign="top" class="text-right">
                <h1 style="color: {{ $color }}; margin: 0;">INVOICE</h1>
                <p style="margin: 5px 0;">#{{ $order->number }}</p>
                
            </td>
        </tr>
    </table>

    <table class="info-table">
        <tr>
            <td width="50%" valign="top">
                <span style="color: #777; font-size: 12px;">DITAGIHKAN KEPADA:</span><br>
                <strong style="font-size: 16px;">{{ $order->customer->name }}</strong><br>
                {{ $order->customer->address ?? 'Alamat tidak tersedia' }}<br>
                Telp: {{ $order->customer->phone ?? '-' }}<br>
                Email: {{ $order->customer->email ?? '-' }}
            </td>
            <td width="50%" valign="top" class="text-right">
                <table width="100%">
                    <tr>
                        <td class="text-right" style="color: #777;">Tanggal Invoice:</td>
                        <td class="text-right"><strong>{{ \Carbon\Carbon::parse($order->order_date)->translatedFormat('d F Y') }}</strong></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td class="text-right">
                            @if($order->payment_status == 'paid')
                                <div style="color: green; border: 2px solid green; display: inline-block; padding: 5px 10px; border-radius: 5px; font-weight: bold;">LUNAS</div>
                            @else
                                <div style="color: red; border: 2px solid red; display: inline-block; padding: 5px 10px; border-radius: 5px; font-weight: bold;">BELUM LUNAS</div>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th>Deskripsi Produk</th>
                <th width="10%" class="text-right">Qty</th>
                <th width="20%" class="text-right">Harga</th>
                @if($show_discount)
                <th width="15%" class="text-right">Diskon</th>
                @endif
                <th width="20%" class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $index => $item)
            @php 
                $gross_subtotal = $item->quantity * $item->unit_price; 
            @endphp
            <tr>
                <td>{{ $index + 1 }}</td>
                <td><strong>{{ $item->product->name }}</strong></td>
                <td class="text-right">{{ $item->quantity }}</td>
                <td class="text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                
                @if($show_discount)
                <td class="text-right">Rp {{ number_format($item->discount_amount, 0, ',', '.') }}</td>
                <td class="text-right text-bold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                @else
                <td class="text-right text-bold">Rp {{ number_format($gross_subtotal, 0, ',', '.') }}</td>
                @endif
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-section">
        <table width="50%" align="right">
            @if($show_discount && $order->discount_amount > 0)
            <tr>
                <td class="text-right" style="padding: 5px;">Subtotal:</td>
                <td class="text-right" style="padding: 5px;">Rp {{ number_format($order->items->sum('subtotal'), 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="text-right" style="padding: 5px;">Diskon Global:</td>
                <td class="text-right" style="padding: 5px; color: red;">- Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</td>
            </tr>
            @endif
            <tr>
                <td class="text-right total-label" style="border-top: 2px solid {{ $color }}; padding-top: 10px;">TOTAL TAGIHAN:</td>
                <td class="text-right total-amount" style="border-top: 2px solid {{ $color }}; padding-top: 10px;">
                    Rp {{ number_format($final_display_total, 0, ',', '.') }}
                </td>
            </tr>
        </table>
    </div>

    <div style="margin-top: 50px; border-top: 1px solid #eee; padding-top: 20px;">
        <strong>Catatan:</strong><br>
        <p style="color: #777; font-size: 12px;">
            {{ $order->notes ?? 'Terima kasih telah berbelanja di ' . $order->business->name . '.' }}
        </p>

        @if($accounts->count())
            <div style="margin-top: 15px; font-size: 12px;">
                <strong>Pembayaran dapat ditransfer ke rekening:</strong><br>
                @foreach($accounts as $acc)
                    {{ $acc->name }} - {{ $acc->account_number }}<br>
                @endforeach
            </div>
        @endif
    </div>

    @if($order->business->signature || $order->business->signer_name)
        <div style="width: 100%; margin-top: 50px;">
            <table width="100%">
                <tr>
                    <td width="60%"></td>
                    <td width="40%" align="center">
                        <p style="margin-bottom: 10px;">Hormat Kami,</p>
                        @if($order->business->signature)
                            <img src="{{ public_path('storage/' . $order->business->signature) }}" style="height: 80px; margin: 5px 0;">
                        @else
                            <br><br><br><br>
                        @endif
                        @if($order->business->signer_name)
                            <p style="font-weight: bold; text-decoration: underline; margin-bottom: 2px;">{{ $order->business->signer_name }}</p>
                        @endif
                        @if($order->business->signer_title)
                            <p style="font-size: 12px; margin-top: 0;">{{ $order->business->signer_title }}</p>
                        @endif
                    </td>
                </tr>
            </table>
        </div>
    @endif

</body>
</html>