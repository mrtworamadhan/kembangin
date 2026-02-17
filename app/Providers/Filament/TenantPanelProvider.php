<?php

namespace App\Providers\Filament;

use App\Filament\Tenant\Pages\Dashboard;
use App\Filament\Tenant\Pages\RegisterBusiness;
use App\Models\Business;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class TenantPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('tenant')
            ->path('tenant')
            
            ->favicon(asset('images/brand/icon-colour.png'))
            ->colors([
                'primary' => Color::Amber,
            ])
            ->tenant(Business::class, slugAttribute: 'slug')
            ->tenantRegistration(RegisterBusiness::class)
            ->discoverResources(in: app_path('Filament/Tenant/Resources'), for: 'App\Filament\Tenant\Resources')
            ->discoverPages(in: app_path('Filament/Tenant/Pages'), for: 'App\Filament\Tenant\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Tenant/Widgets'), for: 'App\Filament\Tenant\Widgets')
            ->widgets([
                
            ])
            ->userMenuItems([
                MenuItem::make()
                    ->label('Keuangan Keluarga')
                    ->url(fn (): string => route('app.home'))
                    ->icon('heroicon-o-home-modern')
                    ->visible(fn (): bool => auth()->user()->isOwner()) 
            ])
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Report')
                    ->collapsible(false),
                NavigationGroup::make()
                    ->label('Inventory')
                    ->collapsible(false),    
                NavigationGroup::make()
                    ->label('Transaction')
                    ->collapsible(false),
                NavigationGroup::make()
                    ->label('Settings')
                    ->collapsible(false),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
