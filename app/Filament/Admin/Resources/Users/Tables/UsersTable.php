<?php

namespace App\Filament\Admin\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('No. Wa')
                    ->searchable(),
                TextColumn::make('role')
                    ->searchable(),
                
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'active' => 'Aktif',
                    ]),
                SelectFilter::make('role')
                    ->options([
                        'owner' => 'Owner',
                        'staff' => 'Staff',
                    ]),
            ])
            ->recordActions([
                Action::make('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (User $record): bool => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Approve User Ini?')
                    ->modalDescription('User ini akan langsung bisa login ke PWA Kembangin setelah di-approve.')
                    ->modalSubmitActionLabel('Ya, Aktifkan!')
                    ->action(function (User $record) {
                        $record->update(['status' => 'active']);

                        $phone = $record->phone ?? '';
                        $phone = preg_replace('/[^0-9]/', '', $phone); 
                        if (str_starts_with($phone, '0')) {
                            $phone = '62' . substr($phone, 1);
                        }

                        $loginUrl = url('/login');
                        $message = urlencode("Halo {$record->name}! \n\nSelamat, akun Kembangin Anda sudah *AKTIF*.\n Selamat Berkembang bersama Kembangin, atur keuangan Usaha dan Rumah Tangga, *Usaha Berkembang Keluarga Tenang*.\n\nSilakan login ke aplikasi melalui link berikut:\n\n{$loginUrl}\n\nTerima kasih!");
                        
                        if (empty($phone)) {
                            Notification::make()
                                ->title('Berhasil Diaktifkan')
                                ->body('Akun aktif, tapi user ini tidak memiliki nomor HP.')
                                ->success()
                                ->send();
                            return;
                        }

                        $waUrl = "https://wa.me/{$phone}?text={$message}";

                        Notification::make()
                            ->title('Akun Berhasil Diaktifkan! ✅')
                            ->body('Klik tombol di bawah untuk mengirim pesan WhatsApp ke user.')
                            ->success()
                            ->persistent()
                            ->actions([
                                Action::make('kirim_wa')
                                    ->label('Kirim Notif WA Sekarang')
                                    ->button()
                                    ->color('success')
                                    ->url($waUrl, shouldOpenInNewTab: true),
                            ])
                            ->send();
                    }),
                EditAction::make()->label(''),
                DeleteAction::make()->label(''),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
