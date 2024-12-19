<?php

namespace App\Providers\Filament;

use App\Filament\Resources\MenuResource;
use App\Filament\Resources\MenuResource\Widgets\TotalMenuWidget;
use App\Filament\Resources\TransactionResource;
use App\Filament\Resources\TransactionResource\Widgets\MonthlyTransactionChartWidget;
use App\Filament\Resources\TransactionResource\Widgets\RekapTransaksiWidget;
use App\Filament\Resources\TransactionResource\Widgets\TodayTransactionTable;
use App\Filament\Resources\TransactionResource\Widgets\TotalTransactionWidget;
use App\Filament\Resources\UserResource;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationItem;
use Filament\Navigation\NavigationGroup;
use Illuminate\Support\Facades\Auth;

use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class DashboardPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('dashboard')
            ->path('dashboard')
            ->login()
            ->brandLogo(env('APP_URL').'/logo.png')
            ->brandLogoHeight("55px")
            ->brandName('POS N.B.S')
            ->colors([
                'primary' => Color::Red,
                'secondary' => Color::Gray,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                TotalTransactionWidget::class,
                RekapTransaksiWidget::class,
                MonthlyTransactionChartWidget::class,
                TodayTransactionTable::class,

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
            ])
            ->databaseNotifications()
            ->plugins([
                \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make(),
            ])
            ->navigation(function (NavigationBuilder $builder): NavigationBuilder {
                return $this->buildNavigation($builder);
            });
    }


    protected function buildNavigation(NavigationBuilder $builder): NavigationBuilder
{
    $user = Auth::user();

    // Dashboard untuk semua role
    $builder->item(
        NavigationItem::make('Dashboard')
            ->icon('heroicon-o-home')
            ->url('/dashboard')
            ->isActiveWhen(fn () => request()->is('dashboard'))
    );

    // Menu Resource
    if ($user->can('view_menu')) {
        $builder->item(
            NavigationItem::make('Menu Management')
                ->icon('heroicon-o-squares-2x2')
                ->url('/dashboard/menus')
                ->isActiveWhen(fn () => request()->is('dashboard/menus*'))
        );
    }

    // Transaction Resource
    if ($user->can('view_transaction')) {
        $builder->item(
            NavigationItem::make('Transactions')
                ->icon('heroicon-o-currency-dollar')
                ->url('/dashboard/transactions')
                ->isActiveWhen(fn () => request()->is('dashboard/transactions*'))
        );
    }

    // User Resource
    if ($user->can('view_user')) {
        $builder->item(
            NavigationItem::make('User Management')
                ->icon('heroicon-o-users')
                ->url('/dashboard/users')
                ->isActiveWhen(fn () => request()->is('dashboard/users*'))
        );
    }

    // POS Page
    if ($user->can('page_POS')) {
        $builder->item(
            NavigationItem::make('POS')
                ->icon('heroicon-o-shopping-cart')
                ->url('/dashboard/kasir')
                ->isActiveWhen(fn () => request()->is('dashboard/kasir*'))
        );
    }

    //shield roles page
    if ($user->can('view_role')) {
        $builder->item(
            NavigationItem::make('Roles')
                ->icon('heroicon-o-shield-check')
                ->url('/dashboard/shield/roles')
                ->isActiveWhen(fn () => request()->is('dashboard/shield/roles*'))
        );
    }

    return $builder;
}
}
