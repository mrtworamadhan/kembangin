<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Keuangan Keluarga</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; color: #333; line-height: 1.4; margin: 0; }
        .header { text-align: center; border-bottom: 2px solid #3b82f6; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { margin: 0; color: #3b82f6; font-size: 24px; text-transform: uppercase; }
        .header p { margin: 5px 0 0 0; color: #777; font-size: 14px; }

        .section-title { font-size: 14px; font-weight: bold; background-color: #f3f4f6; padding: 8px; border-left: 4px solid #3b82f6; margin-top: 20px; margin-bottom: 10px; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #e5e7eb; padding: 6px 8px; text-align: left; }
        th { background-color: #f9fafb; font-weight: bold; color: #555; text-transform: uppercase; font-size: 10px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .summary-table td { font-size: 13px; padding: 8px; }
        .summary-label { font-weight: bold; width: 60%; }
        .summary-value { text-align: right; width: 40%; font-family: monospace; font-size: 14px;}

        .highlight-total { background-color: #eff6ff; font-weight: bold; font-size: 14px; }
        .surplus-row { background-color: #ecfdf5; font-weight: bold; }
        .deficit-row { background-color: #fef2f2; font-weight: bold; }
    </style>
</head>
<body>

    <div class="header">
        <h1>Laporan Arus Kas Keluarga</h1>
        <p>Periode: <strong>{{ $monthName }} {{ $year }}</strong></p>
    </div>

    <div class="section-title">A. RINGKASAN ARUS KAS BULAN INI</div>
    <table class="summary-table">
        <tr>
            <td class="summary-label">Total Pemasukan (Income)</td>
            <td class="summary-value" style="color: #059669;">+ Rp {{ number_format($totalIncome, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="summary-label">Total Pengeluaran (Expense)</td>
            <td class="summary-value" style="color: #dc2626;">- Rp {{ number_format($totalExpense, 0, ',', '.') }}</td>
        </tr>
        <tr class="{{ $netCashflow >= 0 ? 'surplus-row' : 'deficit-row' }}">
            <td class="summary-label">SURPLUS / (DEFISIT) BULAN INI</td>
            <td class="summary-value" style="color: {{ $netCashflow >= 0 ? '#059669' : '#dc2626' }};">
                Rp {{ number_format($netCashflow, 0, ',', '.') }}
            </td>
        </tr>
    </table>

    <div class="section-title">B. POSISI KAS & SALDO REKENING SAAT INI</div>
    <table class="summary-table">
        @php $grandTotalSaldo = 0; @endphp
        @forelse($accounts as $acc)
            @php $grandTotalSaldo += $acc->current_calculated_balance; @endphp
            <tr>
                <td class="summary-label" style="font-weight: normal; color: #555;">
                    <strong>{{ $acc->name }}</strong> {{ $acc->account_number ? '('.$acc->account_number.')' : '' }}<br>
                    <span style="font-size: 10px; color: #888;">Pemilik: {{ $acc->user->name ?? 'Keluarga' }}</span>
                </td>
                <td class="summary-value">Rp {{ number_format($acc->current_calculated_balance, 0, ',', '.') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="2" class="text-center" style="font-size: 12px; color: #999;">Belum ada data rekening terdaftar.</td>
            </tr>
        @endforelse
        <tr class="highlight-total">
            <td class="summary-label">TOTAL UANG KAS (ASET LIKUID)</td>
            <td class="summary-value">Rp {{ number_format($grandTotalSaldo, 0, ',', '.') }}</td>
        </tr>
    </table>

    <div class="section-title">C. ANALISIS KUALITAS PENGELUARAN</div>
    <table width="100%" style="border: none; margin-bottom: 0;">
        <tr>
            <td width="50%" style="border: none; vertical-align: top; padding-right: 10px;">
                <strong>Berdasarkan Prioritas (Nature):</strong>
                <table class="summary-table" style="margin-top: 5px;">
                    <tr>
                        <td>Kebutuhan Pokok</td>
                        <td class="text-right">Rp {{ number_format($needs, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>Gaya Hidup (Wants)</td>
                        <td class="text-right">Rp {{ number_format($wants, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>Tabungan & Aset</td>
                        <td class="text-right">Rp {{ number_format($savings, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </td>
            <td width="50%" style="border: none; vertical-align: top; padding-left: 10px;">
                <strong>Berdasarkan Kualitas (Productivity):</strong>
                <table class="summary-table" style="margin-top: 5px;">
                    <tr>
                        <td>Produktif (Aset/SDM)</td>
                        <td class="text-right">Rp {{ number_format($productive, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>Netral (Kewajiban)</td>
                        <td class="text-right">Rp {{ number_format($neutral, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>Konsumtif (Hangus)</td>
                        <td class="text-right" style="color: #dc2626; font-weight: bold;">Rp {{ number_format($consumptive, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="section-title">D. RINCIAN PEMASUKAN BULAN INI</div>
    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="15%">Tanggal</th>
                <th width="25%">Kategori & Sumber</th>
                <th width="35%">Deskripsi / Catatan</th>
                <th width="20%" class="text-right">Nominal (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($incomes as $index => $income)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ \Carbon\Carbon::parse($income->date)->format('d/m/Y') }}</td>
                <td>
                    <strong>{{ $income->category->name }}</strong><br>
                    <span style="font-size: 9px; color: #3b82f6; font-weight: bold; text-transform: uppercase;">
                        Oleh: {{ $income->user->name ?? '-' }}
                    </span>
                </td>
                <td>{{ $income->description ?: '-' }}</td>
                <td class="text-right" style="color: #059669; font-weight: bold;">{{ number_format($income->amount, 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center">Tidak ada pemasukan tercatat di periode ini.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">E. RINCIAN PENGELUARAN BULAN INI</div>
    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="15%">Tanggal</th>
                <th width="30%">Kategori & Pelaku</th>
                <th width="30%">Deskripsi / Catatan</th>
                <th width="20%" class="text-right">Nominal (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($expenses as $index => $expense)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ \Carbon\Carbon::parse($expense->date)->format('d/m/Y') }}</td>
                <td>
                    <strong>{{ $expense->category->name }}</strong><br>
                    <span style="font-size: 9px; color: #777; text-transform: uppercase;">
                        {{ $expense->category->nature }} | {{ $expense->category->productivity }}
                    </span><br>
                    <span style="font-size: 9px; color: #dc2626; font-weight: bold; text-transform: uppercase;">
                        Oleh: {{ $expense->user->name ?? '-' }}
                    </span>
                </td>
                <td>{{ $expense->description ?: '-' }}</td>
                <td class="text-right">{{ number_format($expense->amount, 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center">Tidak ada pengeluaran di periode ini.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 40px; text-align: right; font-size: 11px; color: #999;">
        * Dokumen internal keluarga, dicetak otomatis pada {{ now()->format('d/m/Y H:i') }} pada aplikasi Kembangin by salakatech.com.
    </div>

</body>
</html>