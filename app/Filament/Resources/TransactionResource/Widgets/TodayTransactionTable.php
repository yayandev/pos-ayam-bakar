<?php
namespace App\Filament\Resources\TransactionResource\Widgets;

use App\Models\Transaction;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class TodayTransactionTable extends BaseWidget
{

    use HasWidgetShield;

    public function table(Table $table): Table
    {

        $user = Auth::user();

        if($user->hasRole('kasir')) {
            return $table
            ->query(
                Transaction::query()
                    ->whereDate('transaction_date', today())
                    ->where('user_id', $user->id)
                    ->orderBy('transaction_date', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('id'),
                Tables\Columns\TextColumn::make('customer_name')->searchable(),
                Tables\Columns\TextColumn::make('transaction_date'),
                Tables\Columns\TextColumn::make('total_amount')->money('idr'),
                Tables\Columns\TextColumn::make('items_count')
                ->label('Items')
                ->counts('items'),
            ]);
        }

        return $table
            ->query(
                Transaction::query()
                    ->whereDate('transaction_date', today())
                    ->orderBy('transaction_date', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('id'),
                Tables\Columns\TextColumn::make('customer_name')->searchable(),
                Tables\Columns\TextColumn::make('transaction_date'),
                Tables\Columns\TextColumn::make('total_amount')->money('idr'),
                Tables\Columns\TextColumn::make('items_count')
                ->label('Items')
                ->counts('items'),
                Tables\Columns\TextColumn::make('user.name')->searchable()->label('Kasir')->badge(),
            ]);
        }
}
