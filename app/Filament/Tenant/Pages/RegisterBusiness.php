<?php

namespace App\Filament\Tenant\Pages;

use App\Models\Business;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class RegisterBusiness extends RegisterTenant
{

    public static function getLabel(): string
    {
        return 'Buat Bisnis Baru';
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
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

                        Toggle::make('use_stock_management')
                            ->label('Aktifkan Manajemen Stok?')
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
                            ->required()
                            ->columnSpanFull(),

                        FileUpload::make('signature')
                            ->label('Scan Tanda Tangan')
                            ->image()
                            ->directory('business-signatures') 
                            ->visibility('public')
                            ->maxSize(1024)
                            ->columnSpan(3)
                            ->helperText('Format: PNG Transparan lebih bagus.'),

                        TextInput::make('signer_name')
                            ->label('Nama')
                            ->placeholder('Contoh: Budi Santoso')
                            ->requiredWith('signature')
                            ->columnSpan(3), 

                        TextInput::make('signer_title')
                            ->label('Jabatan')
                            ->placeholder('Contoh: Owner / Finance Manager')
                            ->columnSpan(3),
                        
                        ColorPicker::make('invoice_color')
                            ->label('Warna Dominan')
                            ->helperText('Warna ini akan dipakai di Header & Total.')
                            ->default('#F59E0B') 
                            ->required()
                            ->columnSpan(3),
                    ])
                    ->columns(3),

                TextInput::make('currency')->default('IDR')->hidden(),
                TextInput::make('type')->default('business')->hidden(),
            ]);
    }

    protected function handleRegistration(array $data): Business
    {
        $data['slug'] = Str::slug($data['name']);
        $data['user_id'] = auth()->id();

        $business = Business::create($data);

        $business->members()->attach(auth()->user());

        return $business;
    }

}
