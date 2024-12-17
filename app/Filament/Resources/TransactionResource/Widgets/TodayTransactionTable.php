<?php
namespace App\Filament\Resources\TransactionResource\Widgets;

use App\Models\Transaction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TodayTransactionTable extends BaseWidget
{
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Transaction::query()
                    ->whereDate('transaction_date', today())
                    ->orderBy('transaction_date', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('id'),
                Tables\Columns\TextColumn::make('customer_name'),
                Tables\Columns\TextColumn::make('transaction_date'),
                Tables\Columns\TextColumn::make('total_amount')->money('idr'),
                Tables\Columns\TextColumn::make('items')->counts('items'),
            ])->actions([
                Tables\Actions\ViewAction::make(),
            ]);
        }

        //title
        protected function getTitle(): string
        {
            return 'Transaksi Hari Ini';
        }
}
