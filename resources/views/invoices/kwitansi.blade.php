<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kwitansi #{{ $order->number }}</title>
    <style>
        /* KUNCI A5 LANDSCAPE: Margin diset 30px buat safe zone printer fisik */
        @page { size: a5 landscape; margin: 30px; }
        
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 13px; color: #333; line-height: 1.4; margin: 0; padding: 0; }
        
        /* FIX DOMPDF BUG: Hapus width: 100% di sini biar padding nggak tumpah ke kanan */
        .container { border: 3px double {{ $color }}; padding: 15px; }
        
        .header-table { width: 100%; border-bottom: 2px solid {{ $color }}; padding-bottom: 10px; margin-bottom: 15px; }
        .title { font-size: 24px; font-weight: bold; color: {{ $color }}; text-transform: uppercase; letter-spacing: 2px; margin: 0; }
        
        .content-table { width: 100%; margin-bottom: 15px; }
        .content-table td { padding: 6px 0; vertical-align: middle; }
        .label-col { width: 22%; font-weight: bold; color: #555; }
        .colon-col { width: 3%; font-weight: bold; }
        .value-col { width: 75%; font-size: 14px; border-bottom: 1px dotted #ccc; }
        
        .terbilang-box { background-color: #f4f4f4; padding: 8px 10px; font-style: italic; font-weight: bold; border-left: 4px solid {{ $color }}; }
        
        .footer-table { width: 100%; margin-top: 10px; }
        .nominal-amount { background-color: {{ $color }}; color: #fff; font-size: 20px; font-weight: bold; padding: 10px 20px; border-radius: 5px; }
    </style>
</head>
<body>

    @php
        // LOGIKA HARGA (Diskon vs Normal)
        $gross_grand_total = 0;
        foreach($order->items as $item) {
            $gross_grand_total += ($item->quantity * $item->unit_price);
        }
        $final_display_total = $show_discount ? $order->total_amount : $gross_grand_total;

        // FUNGSI TERBILANG
        if (!function_exists('penyebut')) {
            function penyebut($nilai) {
                $nilai = abs($nilai);
                $huruf = array("", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas");
                $temp = "";
                if ($nilai < 12) {
                    $temp = " ". $huruf[$nilai];
                } else if ($nilai < 20) {
                    $temp = penyebut($nilai - 10). " Belas";
                } else if ($nilai < 100) {
                    $temp = penyebut($nilai/10)." Puluh". penyebut($nilai % 10);
                } else if ($nilai < 200) {
                    $temp = " Seratus" . penyebut($nilai - 100);
                } else if ($nilai < 1000) {
                    $temp = penyebut($nilai/100) . " Ratus" . penyebut($nilai % 100);
                } else if ($nilai < 2000) {
                    $temp = " Seribu" . penyebut($nilai - 1000);
                } else if ($nilai < 1000000) {
                    $temp = penyebut($nilai/1000) . " Ribu" . penyebut($nilai % 1000);
                } else if ($nilai < 1000000000) {
                    $temp = penyebut($nilai/1000000) . " Juta" . penyebut($nilai % 1000000);
                }
                return $temp;
            }
        }
        $terbilang = trim(penyebut($final_display_total)) . " Rupiah";
    @endphp

    <div class="container">
        
        <table class="header-table">
            <tr>
                <td width="60%" valign="middle">
                    @if($logo)
                        <img src="{{ public_path('storage/' . $logo) }}" style="height: 50px; margin-bottom: 5px;">
                    @else
                        <h2 style="margin: 0; color: {{ $color }};">{{ $order->business->name }}</h2>
                    @endif
                    <div style="font-size: 11px; color: #777;">
                        {{ $order->business->address }} | Telp: {{ $order->business->phone ?? '-' }}
                    </div>
                </td>
                <td width="40%" valign="middle" style="text-align: right;">
                    <h1 class="title">KWITANSI</h1>
                    <div style="font-size: 12px; color: #777; margin-top: 5px;">No: PYI-{{ $order->number }}</div>
                </td>
            </tr>
        </table>

        <table class="content-table">
            <tr>
                <td class="label-col">Telah Terima Dari</td>
                <td class="colon-col">:</td>
                <td class="value-col"><strong>{{ $order->customer->name }}</strong></td>
            </tr>
            <tr>
                <td class="label-col">Uang Sejumlah</td>
                <td class="colon-col">:</td>
                <td class="value-col" style="border-bottom: none; padding-top: 10px; padding-bottom: 10px;">
                    <div class="terbilang-box">
                        ## {{ $terbilang }} ##
                    </div>
                </td>
            </tr>
            <tr>
                <td class="label-col">Untuk Pembayaran</td>
                <td class="colon-col">:</td>
                <td class="value-col">
                    <strong>Pelunasan Invoice Tagihan #{{ $order->number }}</strong>
                </td>
            </tr>
        </table>

        <table class="footer-table">
            <tr>
                <td width="50%" valign="bottom">
                    <span class="nominal-amount">
                        Rp {{ number_format($final_display_total, 0, ',', '.') }}
                    </span>
                </td>
                <td width="50%" valign="bottom" style="text-align: right;">
                    <p style="margin: 0 0 5px 0;">{{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</p>
                    <p style="color: #777; font-size: 12px; margin: 0;">Penerima,</p>
                    
                    @if($order->business->signature)
                        <img src="{{ public_path('storage/' . $order->business->signature) }}" style="height: 50px; margin: 5px 0;">
                    @else
                        <br><br><br>
                    @endif
                    
                    <p style="text-decoration: underline; font-weight: bold; margin: 0;">
                        {{ $order->business->signer_name ?? $order->business->name }}
                    </p>
                </td>
            </tr>
        </table>

    </div>

</body>
</html>