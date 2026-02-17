<?php

namespace App\Filament\Tenant\Resources\Businesses;

use App\Filament\Tenant\Resources\Businesses\Pages\ManageBusinesses;
use App\Models\Business;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use UnitEnum;

class BusinessResource extends Resource
{
    protected static ?string $model = Business::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string | UnitEnum | null $navigationGroup = 'Settings';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas & Branding')
                    ->schema([
                        FileUpload::make('logo')
                            ->label('Logo Usaha')
                            ->image() 
                            ->disk('public')
                            ->directory('business-logos') 
                            ->visibility('public') 
                            ->maxSize(2048) 
                            ->columnSpanFull(),

                        TextInput::make('name')
                            ->label('Nama Usaha')
                            ->required(),

                        TextInput::make('email')
                        ->label('Email Resmi')
                        ->email()
                        ->placeholder('admin@tokokita.com'),

                        TextInput::make('phone')
                            ->label('No. Telepon / WA')
                            ->tel()
                            ->placeholder('0812xxxx'),
                            
                        Textarea::make('address')
                            ->label('Alamat Lengkap')
                            ->rows(3)
                            ->columnSpanFull(),

                        Select::make('business_type_id')
                            ->label('Bidang Usaha')
                            ->relationship('businessType', 'name')
                            ->required(),

                        TextInput::make('currency')
                            ->label('Mata Uang')
                            ->default('IDR')
                            ->readOnly(),

                        Toggle::make('use_stock_management')
                            ->label('Aktifkan Manajemen Stok?')
                            ->helperText('Jika MATI: Penjualan tidak akan mengurangi stok, dan Pembelian tidak menambah stok.')
                            ->onColor('success')
                            ->offColor('danger')
                            ->default(true),
                    ])->columns(2),

                
                Section::make('Pengaturan Invoice')
                    ->schema([
                        Radio::make('invoice_theme')
                            ->label('Pilih Tema Invoice')
                            ->options([
                                'modern' => 'Modern',
                                'classic' => 'Classic',
                                'simple' => 'Simple',
                            ])
                            ->descriptions([
                                'modern' => new HtmlString('
                                    <div class="mt-2">
                                        <img src="/images/themes/modern.png" alt="Modern" class="w-full rounded-lg border border-gray-200 hover:border-amber-500 transition shadow-sm">
                                        <p class="text-xs text-gray-500 mt-1">Cocok untuk brand kekinian, dominan warna.</p>
                                    </div>
                                '),
                                'classic' => new HtmlString('
                                    <div class="mt-2">
                                        <img src="/images/themes/classic.png" alt="Classic" class="w-full rounded-lg border border-gray-200 hover:border-amber-500 transition shadow-sm">
                                        <p class="text-xs text-gray-500 mt-1">Formal, resmi, hemat tinta.</p>
                                    </div>
                                '),
                                'simple' => new HtmlString('
                                    <div class="mt-2">
                                        <img src="/images/themes/simple.png" alt="Simple" class="w-full rounded-lg border border-gray-200 hover:border-amber-500 transition shadow-sm">
                                        <p class="text-xs text-gray-500 mt-1">Minimalis, fokus ke angka.</p>
                                    </div>
                                '),
                            ])
                            ->default('modern')
                            ->columnSpan(2)
                            ->required(),

                        FileUpload::make('signature')
                            ->label('Scan Tanda Tangan')
                            ->image()
                            ->directory('business-signatures') 
                            ->visibility('public')
                            ->maxSize(1024) 
                            ->helperText('Format: PNG Transparan lebih bagus.'),

                        TextInput::make('signer_name')
                            ->label('Nama Penandatangan')
                            ->placeholder('Contoh: Budi Santoso')
                            ->requiredWith('signature'), 

                        TextInput::make('signer_title')
                            ->label('Jabatan')
                            ->placeholder('Contoh: Owner / Finance Manager'),
                        
                        ColorPicker::make('invoice_color')
                            ->label('Warna Dominan')
                            ->helperText('Warna ini akan dipakai di Header & Total.')
                            ->default('#F59E0B') 
                            ->required()
                            ->columnSpan(1),
                    ])
                    ->columns(3),

                    
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('currency')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageBusinesses::route('/'),
        ];
    }
}
