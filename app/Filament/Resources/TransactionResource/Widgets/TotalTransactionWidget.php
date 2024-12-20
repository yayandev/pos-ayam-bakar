<?php

namespace App\Filament\Resources\TransactionResource\Widgets;

use App\Models\Menu;
use App\Models\Transaction;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class TotalTransactionWidget extends BaseWidget
{
    use HasWidgetShield;

    protected function getStats(): array
    {
        // Get the total count of transactions
        $user = Auth::user();
        $transactionCount = Transaction::count();
        $menuCount = Menu::count();

        $totalIncome = Transaction::sum('total_amount');



        if($user->hasRole('kasir')) {
            $transactionCount = Transaction::where('user_id', $user->id)->count();
            $menuCount = Menu::count();
            $totalIncome = Transaction::where('user_id', $user->id)->sum('total_amount');
        }


        return [
            Stat::make('Total Transactions', $transactionCount)->color('success')->icon('heroicon-o-credit-card'), // Change this to an appropriate icon
            Stat::make('Total Menus', $menuCount)->color('primary')->icon('heroicon-o-bars-3'),
            Stat::make('Total Pendapatan', 'Rp ' . number_format($totalIncome, 2))->color('warning')->icon('heroicon-o-credit-card'),
        ];
    }
}
