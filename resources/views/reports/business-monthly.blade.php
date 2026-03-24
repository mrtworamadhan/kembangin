<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Bulanan - {{ $business->name }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; color: #333; line-height: 1.4; margin: 0; }
        .header { text-align: center; border-bottom: 2px solid #10b981; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { margin: 0; color: #10b981; font-size: 24px; text-transform: uppercase; }
        .header p { margin: 5px 0 0 0; color: #777; font-size: 14px; }
        
        .section-title { font-size: 14px; font-weight: bold; background-color: #f3f4f6; padding: 8px; border-left: 4px solid #10b981; margin-top: 20px; margin-bottom: 10px; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #e5e7eb; padding: 8px; text-align: left; }
        th { background-color: #f9fafb; font-weight: bold; color: #555; text-transform: uppercase; font-size: 10px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        
        /* Tabel Ringkasan Khusus */
        .summary-table td { font-size: 14px; padding: 10px; }
        .summary-label { font-weight: bold; width: 60%; }
        .summary-value { text-align: right; width: 40%; font-family: monospace; font-size: 15px;}
        
        .profit-row { background-color: #ecfdf5; font-weight: bold; }
        .loss-row { background-color: #fef2f2; font-weight: bold; color: #dc2626; }
        
        .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: bold; }
        .badge-green { background-color: #d1fae5; color: #065f46; }
        .badge-red { background-color: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>

    <div class="header">
        <h1>{{ $business->name }}</h1>
        <p>Laporan Keuangan & Operasional | Periode: <strong>{{ $monthName }} {{ $year }}</strong></p>
    </div>

    <div class="section-title">A. RINGKASAN LABA RUGI</div>
    <table class="summary-table">
        <tr>
            <td class="summary-label">1. Total Penjualan (Omzet)</td>
            <td class="summary-value">+ Rp {{ number_format($sales, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="summary-label">2. Harga Pokok Penjualan (HPP / Modal Barang Laku)</td>
            <td class="summary-value" style="color: #dc2626;">- Rp {{ number_format($hpp, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="summary-label">3. Biaya Operasional (Gaji, Listrik, Pemasaran, dll)</td>
            <td class="summary-value" style="color: #dc2626;">- Rp {{ number_format($opEx, 0, ',', '.') }}</td>
        </tr>
        <tr class="{{ $profit >= 0 ? 'profit-row' : 'loss-row' }}">
            <td class="summary-label">
                4. ESTIMASI PROFIT BERSIH 
                <span class="badge {{ $profit >= 0 ? 'badge-green' : 'badge-red' }}" style="margin-left: 10px;">
                    Margin: {{ $profitMargin }}%
                </span>
            </td>
            <td class="summary-value">Rp {{ number_format($profit, 0, ',', '.') }}</td>
        </tr>
    </table>

    <div class="section-title">B. RINCIAN PENDAPATAN (PENJUALAN)</div>
    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="15%">Tanggal</th>
                <th width="20%">No. Invoice</th>
                <th width="25%">Pelanggan</th>
                <th width="15%" class="text-center">Status</th>
                <th width="20%" class="text-right">Total (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $index => $order)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ \Carbon\Carbon::parse($order->order_date)->format('d/m/Y') }}</td>
                <td>{{ $order->number }}</td>
                <td>{{ $order->customer->name ?? 'Umum' }}</td>
                <td class="text-center">{{ $order->payment_status == 'paid' ? 'LUNAS' : 'BELUM LUNAS' }}</td>
                <td class="text-right">{{ number_format($order->total_amount, 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center">Tidak ada transaksi penjualan di periode ini.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">C. RINCIAN PENGELUARAN (OPERASIONAL & KULAKAN)</div>
    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="15%">Tanggal</th>
                <th width="30%">Kategori</th>
                <th width="30%">Deskripsi / Catatan</th>
                <th width="20%" class="text-right">Nominal (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($expenses as $index => $expense)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ \Carbon\Carbon::parse($expense->date)->format('d/m/Y') }}</td>
                <td>{{ $expense->category->name }}</td>
                <td>{{ $expense->description ?: '-' }}</td>
                <td class="text-right">{{ number_format($expense->amount, 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center">Tidak ada pengeluaran operasional di periode ini.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 40px; text-align: right; font-size: 11px; color: #999;">
        * Dokumen ini di-generate secara otomatis pada {{ now()->format('d/m/Y H:i') }}. oleh kembangin - by salakatech.com
    </div>

</body>
</html>