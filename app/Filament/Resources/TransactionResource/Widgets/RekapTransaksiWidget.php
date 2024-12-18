<?php

namespace App\Filament\Resources\TransactionResource\Widgets;

use App\Models\Transaction; // Make sure to import the Transaction model
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RekapTransaksiWidget extends BaseWidget
{
    use HasWidgetShield;

    protected function getStats(): array
    {
        // Calculate the total for today
        $todayTotal = Transaction::whereDate('transaction_date', today())->sum('total_amount');

        // Calculate the total for this week
        $weekTotal = Transaction::whereBetween('transaction_date', [now()->startOfWeek(), now()->endOfWeek()])->sum('total_amount');

        // Calculate the total for this month
        $monthTotal = Transaction::whereMonth('transaction_date', now()->month)->sum('total_amount');


        return [
            Stat::make('Total Pendapatan Hari Ini', 'Rp ' . number_format($todayTotal, 2))
            ->description(now()->format('d F Y'))
            ->color('success')
                ->icon('heroicon-o-calendar'),

            Stat::make('Total Pendapatan Minggu Ini', 'Rp ' . number_format($weekTotal, 2))
                ->color('primary')
                ->description(now()->startOfWeek()->format('d F Y') . ' - ' . now()->endOfWeek()->format('d F Y'))
                ->icon('heroicon-o-calendar'),

            Stat::make('Total Pendapatan Bulan Ini', 'Rp ' . number_format($monthTotal, 2))
                ->description(now()->format('F Y'))
                ->color('warning')
                ->icon('heroicon-o-calendar'),

        ];
    }
}
