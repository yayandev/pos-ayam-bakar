<?php

namespace App\Filament\Resources\TransactionResource\Widgets;

use App\Models\Menu;
use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TotalTransactionWidget extends BaseWidget
{
    protected function getStats(): array
    {
        // Get the total count of transactions
        $transactionCount = Transaction::count();
        $menuCount = Menu::count();

        return [
            Stat::make('Total Transactions', $transactionCount)->color('success')->icon('heroicon-o-credit-card'), // Change this to an appropriate icon
            Stat::make('Total Menus', $menuCount)->color('primary')->icon('heroicon-o-bars-3'),
            Stat::make('Total Pendapatan', 'Rp ' . number_format(Transaction::sum('total_amount'), 2))->color('warning')->icon('heroicon-o-credit-card'),
        ];
    }
}
