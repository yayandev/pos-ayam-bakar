<?php

namespace App\Filament\Resources\TransactionResource\Widgets;
use Filament\Widgets\Actions\Action;

use App\Models\Transaction;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class MonthlyTransactionChartWidget extends ChartWidget
{
    use HasWidgetShield;
    protected static ?string $heading = 'Pendapatan Perbulan Tahun Ini' ;

    // Mendapatkan data untuk chart
    protected function getData(): array
    {
        $user = Auth::user();

        // Ambil transaksi per bulan dari tahun sekarng
        $monthlyTransactions = Transaction::query()
            ->selectRaw('MONTH(transaction_date) as month, YEAR(transaction_date) as year, SUM(total_amount) as total')
            ->whereYear('transaction_date', now()->year)
            ->groupBy('month', 'year')
            ->get();

        if($user->hasRole('kasir')) {
            $monthlyTransactions = Transaction::query()
            ->selectRaw('MONTH(transaction_date) as month, YEAR(transaction_date) as year, SUM(total_amount) as total')
            ->whereYear('transaction_date', now()->year)
            ->where('user_id', $user->id)
            ->groupBy('month', 'year')
            ->get();
        }

        // Persiapkan data untuk chart
        $months = [];
        $totals = [];

        foreach ($monthlyTransactions as $transaction) {
            $months[] = date('F', mktime(0, 0, 0, $transaction->month, 1, $transaction->year)); // Format bulan
            $totals[] = $transaction->total; // Total transaksi per bulan
        }

        return [
            'labels' => $months, // Label x-axis: bulan
            'datasets' => [
                [
                    'label' => 'Total Pendapatan',
                    'data' => $totals, // Data y-axis: total Pendapatan
                    'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                    'borderColor' => 'rgba(75, 192, 192, 1)',
                    'borderWidth' => 1,
                ]
            ],
        ];
    }

    // Menentukan tipe chart (line chart)
    protected function getType(): string
    {
        return 'bar';
    }

}
