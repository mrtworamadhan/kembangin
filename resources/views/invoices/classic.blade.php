<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice #{{ $order->number }}</title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; font-size: 14px; color: #000; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 3px double {{ $color }}; padding-bottom: 15px; }
        .title { font-size: 24px; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; color: {{ $color }}; }
        .info-table { width: 100%; margin-bottom: 20px; }
        .info-label { font-weight: bold; width: 100px; }
        .items-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .items-table th, .items-table td { border: 1px solid #000; padding: 8px; }
        .items-table th { background-color: #f0f0f0; text-align: center; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-box { border: 2px solid {{ $color }}; padding: 10px; font-weight: bold; font-size: 16px; }
    </style>
</head>
<body>

    @php
        // Hitung total kotor (tanpa diskon apapun) untuk ditampilkan jika show_discount = false
        $gross_grand_total = 0;
        foreach($order->items as $item) {
            $gross_grand_total += ($item->quantity * $item->unit_price);
        }
        $final_display_total = $show_discount ? $order->total_amount : $gross_grand_total;
    @endphp

    <div class="header">
        @if($logo)
            <img src="{{ public_path('storage/' . $logo) }}" style="height: 80px; margin-bottom: 10px;">
        @else
            <h1 style="margin: 0;">{{ $order->business->name }}</h1>
        @endif
        <p style="margin: 5px 0;">
            {{ $order->business->address }}
            @if($order->business->phone) | Telp: {{ $order->business->phone }} @endif
            @if($order->business->email) | Email: {{ $order->business->email }} @endif
        </p>
    </div>

    <div class="text-center">
        <span class="title">INVOICE</span><br>
        <span>Nomor: #{{ $order->number }}</span>
    </div>

    <table class="info-table" style="margin-top: 20px;">
        <tr>
            <td width="50%" valign="top" style="border: 1px solid #000; padding: 10px;">
                <strong>KEPADA YTH:</strong><br>
                {{ $order->customer->name }}<br>
                {{ $order->customer->address }}<br>
                {{ $order->customer->phone }}
            </td>
            <td width="5%"></td>
            <td width="45%" valign="top">
                <table width="100%">
                    <tr>
                        <td class="info-label">Tanggal:</td>
                        <td>{{ \Carbon\Carbon::parse($order->order_date)->translatedFormat('d F Y') }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Status:</td>
                        <td style="color: {{ $order->payment_status == 'paid' ? 'green' : 'red' }}; font-weight: bold; text-transform: uppercase;">
                            {{ $order->payment_status == 'paid' ? 'LUNAS' : 'BELUM LUNAS' }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th width="5%">No.</th>
                <th>Deskripsi</th>
                <th width="10%">Qty</th>
                <th width="20%">Harga</th>
                @if($show_discount)
                <th width="15%">Diskon</th>
                @endif
                <th width="20%">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $index => $item)
            @php 
                $gross_subtotal = $item->quantity * $item->unit_price; 
            @endphp
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $item->product->name }}</td>
                <td class="text-center">{{ $item->quantity }}</td>
                
                {{-- Harga selalu harga normal --}}
                <td class="text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                
                @if($show_discount)
                <td class="text-right">Rp {{ number_format($item->discount_amount, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                @else
                {{-- Jika hide diskon, subtotal = qty * harga normal --}}
                <td class="text-right">Rp {{ number_format($gross_subtotal, 0, ',', '.') }}</td>
                @endif
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            @php $colspan = $show_discount ? 5 : 4; @endphp
            
            @if($show_discount && $order->discount_amount > 0)
            <tr>
                <td colspan="{{ $colspan }}" class="text-right" style="border: none; padding-top: 20px;">Subtotal</td>
                <td class="text-right" style="border: 1px solid #000; padding-top: 20px;">Rp {{ number_format($order->items->sum('subtotal'), 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td colspan="{{ $colspan }}" class="text-right" style="border: none;">Diskon Global</td>
                <td class="text-right" style="border: 1px solid #000; color: red;">- Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</td>
            </tr>
            @endif

            <tr>
                <td colspan="{{ $colspan }}" class="text-right" style="border: none; padding-top: 15px;"><strong>Total Tagihan</strong></td>
                <td class="text-right" style="border: 2px solid {{ $color }}; padding: 10px; font-weight: bold;">
                    Rp {{ number_format($final_display_total, 0, ',', '.') }}
                </td>
            </tr>
        </tfoot>
    </table>

    @if($order->business->signature || $order->business->signer_name)
    <div style="margin-top: 50px;">
        <table width="100%">
            <tr>
                <td width="60%"></td>
                <td width="40%" class="text-center">
                    <p>Hormat Kami,</p>
                    @if($order->business->signature)
                        <img src="{{ public_path('storage/' . $order->business->signature) }}" style="height: 70px;">
                    @else
                        <br><br><br>
                    @endif
                    <p style="text-decoration: underline; font-weight: bold;">{{ $order->business->signer_name }}</p>
                    <p>{{ $order->business->signer_title }}</p>
                </td>
            </tr>
        </table>
    </div>
    @endif

</body>
</html>