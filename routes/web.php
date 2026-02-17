<?php

use App\Http\Controllers\AuthController;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::public.home')->name('home');
Route::livewire('/docs', 'pages::public.docs')->name('docs');


Route::middleware(['guest'])->group(function () {
    Route::livewire('/login', 'pages::app.auth.login')->name('login'); 
    Route::livewire('/register', 'pages::app.auth.register')->name('register'); 
    Route::livewire('/waiting', 'pages::app.auth.waiting')->name('waiting'); 
});

Route::middleware(['auth'])->group(function () {
    
    Route::livewire('/app/home', 'pages::app.home')->name('app.home');
    Route::livewire('/app/analytics', 'pages::app.analytics')->name('app.analytics');
    Route::livewire('/app/transaction', 'pages::app.transaction')->name('app.transaction');
    Route::livewire('/app/assets', 'pages::app.assets')->name('app.assets');
    Route::livewire('/app/ledger', 'pages::app.ledger')->name('app.ledger');

    Route::livewire('/app/profile', 'pages::app.auth.profile')->name('app.profile');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/invoice/{order}/preview', function (Order $order) {
    $business = $order->business;
    $theme = $order->business->invoice_theme ?? 'modern';
    $color = $business->invoice_color ?? '#F59E0B';

    $pdf = Pdf::loadView('invoices.' . $theme, [
        'order' => $order,
        'color' => $color,
        'logo' => $business->logo,
    ])->setPaper('a4', 'portrait');

    return $pdf->stream('Invoice-'.$order->number.'.pdf');
})->name('invoice.preview');

