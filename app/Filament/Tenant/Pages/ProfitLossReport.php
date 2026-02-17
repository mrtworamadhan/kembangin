<?php

namespace App\Filament\Tenant\Pages;

use App\Models\Category;
use App\Models\Transaction;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms;
use Filament\Facades\Filament;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use UnitEnum;

class ProfitLossReport extends Page implements HasForms, HasTable, HasInfolists
{
    use InteractsWithForms;
    use InteractsWithTable;
    use InteractsWithInfolists;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartBar;

    protected static string | UnitEnum | null $navigationGroup = 'Report';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Laporan Laba Rugi';
    protected static ?string $title = 'Laporan Laba Rugi';
    protected string $view = 'filament.tenant.pages.profit-loss-report';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'startDate' => now()->startOfMonth(),
            'endDate' => now()->endOfMonth(),
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        DatePicker::make('startDate')
                            ->label('Dari Tanggal')
                            ->required(),
                        DatePicker::make('endDate')
                            ->label('Sampai Tanggal')
                            ->required(),
                        Actions::make([
                            Action::make('filter')
                                ->label('Tampilkan Laporan')
                                ->action(fn () => $this->refreshReport()),
                        ]),
                    ])
                    ->columns(3),
            ])
            ->statePath('data');
    }

    public function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->state($this->getStats()) 
            ->schema([
                Section::make('Ringkasan Keuangan')
                    ->schema([
                        TextEntry::make('income')
                            ->label('Total Pemasukan')
                            ->money('IDR')
                            ->color('success')
                            ->size(TextSize::Large)
                            ->weight('bold'),
                        
                        TextEntry::make('expense')
                            ->label('Total Pengeluaran')
                            ->money('IDR')
                            ->color('danger')
                            ->size(TextSize::Large)
                            ->weight('bold'),
                        
                        TextEntry::make('profit')
                            ->label('Laba Bersih')
                            ->money('IDR')
                            ->color(fn ($state) => $state >= 0 ? 'primary' : 'danger')
                            ->size(TextSize::Large)
                            ->weight('bold'),
                    ])->columns(3),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                $tenantId = Filament::getTenant()->id;
                $startDate = $this->data['startDate'] ?? now()->startOfMonth();
                $endDate = $this->data['endDate'] ?? now()->endOfMonth();

                return Category::query()
                    ->where('business_id', $tenantId)
                    ->where('type', 'expense')
                    ->withSum(['transactions' => function ($query) use ($startDate, $endDate) {
                        $query->whereBetween('date', [$startDate, $endDate]);
                    }], 'amount')
                    ->having('transactions_sum_amount', '>', 0); // Hanya tampilkan yg ada isinya
            })
            ->columns([
                TextColumn::make('name')
                    ->label('Kategori Biaya')
                    ->searchable(),
                
                TextColumn::make('transactions_sum_amount')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('percentage')
                    ->label('% dari Total')
                    ->state(function (Category $record) {
                        $totalExpense = $this->getStats()['expense'];
                        $amount = $record->transactions_sum_amount;
                        
                        if ($totalExpense == 0) return '0%';
                        return round(($amount / $totalExpense) * 100, 1) . '%';
                    })
                    ->badge()
                    ->color('gray'),
            ])
            ->paginated(false); 
    }

    protected function getStats(): array
    {
        $tenantId = Filament::getTenant()->id;
        $startDate = $this->data['startDate'] ?? now()->startOfMonth();
        $endDate = $this->data['endDate'] ?? now()->endOfMonth();

        $query = Transaction::query()
            ->where('business_id', $tenantId)
            ->whereBetween('date', [$startDate, $endDate]);

        $income = (clone $query)->whereHas('category', fn ($q) => $q->where('type', 'income'))->sum('amount');
        $expense = (clone $query)->whereHas('category', fn ($q) => $q->where('type', 'expense'))->sum('amount');
        
        return [
            'income' => $income,
            'expense' => $expense,
            'profit' => $income - $expense,
        ];
    }
}