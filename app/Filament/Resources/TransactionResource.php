<?php

namespace App\Filament\Resources;

use App\Filament\Exports\TransactionExporter;
use App\Filament\Resources\TransactionResource\Pages;
use App\Filament\Resources\TransactionResource\RelationManagers;
use App\Models\Menu;
use App\Models\Transaction;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Actions\ExportBulkAction;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class TransactionResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (Auth::user()->hasRole('kasir')) {
            return $query->where('user_id', Auth::id());
        }

        return $query;
    }

    public static function getPermissionPrefixes(): array
    {
        return ['view', 'view_any', 'create', 'update', 'delete', 'delete_any', 'publish'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Card::make()
                    ->schema([
                        Hidden::make('user_id')->default(Auth::id()),
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('menu_id')
                                    ->label('Menu')
                                    ->options(Menu::pluck('name', 'id'))
                                    ->required()
                                    ->reactive()
                                    ->searchable()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $menu = Menu::find($state);
                                        if ($menu) {
                                            $set('price', $menu->price);
                                        }
                                    }),
                                Forms\Components\TextInput::make('quantity')
                                    ->numeric()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $items = $get('../../items') ?? [];
                                        $total = collect($items)->sum(fn($item) => ($item['quantity'] ?? 0) * ($item['price'] ?? 0));
                                        $set('../../total_amount', $total);
                                    }),
                                Forms\Components\TextInput::make('price')->numeric()->required()->readOnly()->prefix('Rp'),
                                Placeholder::make('subtotal')->content(function (callable $get) {
                                    return 'Rp ' . number_format(($get('quantity') ?? 0) * ($get('price') ?? 0), 2);
                                }),
                            ])
                            ->columns(4)
                            ->required(),
                    ])
                    ->columnSpan(['lg' => 2]),
                Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('customer_name')->required()->maxLength(255),
                        Forms\Components\DateTimePicker::make('transaction_date')->default(now())->required(),
                        Placeholder::make('total_amount_display')
                            ->label('Total Amount')
                            ->content(function (callable $get) {
                                $total = collect($get('items') ?? [])->sum(fn($item) => ($item['quantity'] ?? 0) * ($item['price'] ?? 0));
                                return 'Rp ' . number_format($total, 2);
                            }),
                        Hidden::make('total_amount')
                            ->reactive()
                            ->afterStateHydrated(function (Hidden $component, $state, callable $get) {
                                $items = $get('items') ?? [];
                                $total = collect($items)->sum(fn($item) => ($item['quantity'] ?? 0) * ($item['price'] ?? 0));
                                $component->state($total);
                            })
                            ->dehydrateStateUsing(fn($state) => $state ?: 0),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code_transaction')->label('Transaction ID')->searchable(),
                Tables\Columns\TextColumn::make('customer_name')->searchable(),
                Tables\Columns\TextColumn::make('transaction_date')->dateTime()->sortable()->label('Tanggal Transaksi'),
                Tables\Columns\TextColumn::make('total_amount')
                    ->formatStateUsing(fn(string $state): string => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('items_count')->label('Items')->counts('items'),
                Tables\Columns\TextColumn::make('payment_method')->searchable(),
                Tables\Columns\TextColumn::make('user.name')->searchable()->label('Kasir')->badge()
            ])
            ->filters([
                Tables\Filters\Filter::make('date_range')
                    ->label('Rentang Tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('start_date')->label('Tanggal Mulai')->required(),
                        Forms\Components\DatePicker::make('end_date')->label('Tanggal Akhir')->required()
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['start_date'] && $data['end_date'],
                            fn(Builder $query) => $query->whereBetween('transaction_date', [$data['start_date'], $data['end_date']])
                        );
                    }),
                SelectFilter::make('user_id')
                    ->options(User::pluck('name', 'id'))
                    ->label('Kasir'),
                SelectFilter::make('payment_method')
                    ->options([
                        'cash' => 'Cash',
                        'cashless' => 'Cashless',
                    ])
                    ->label('Metode Pembayaran'),
                Filter::make('total_amount')
                    ->form([
                        Forms\Components\TextInput::make('min')->label('Minimal'),
                        Forms\Components\TextInput::make('max')->label('Maksimal'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['min'], fn(Builder $query) => $query->where('total_amount', '>=', $data['min']))
                            ->when($data['max'], fn(Builder $query) => $query->where('total_amount', '<=', $data['max']));
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('print')
                    ->label('Print')
                    ->icon('heroicon-o-printer')
                    ->color('secondary')
                    ->url(fn($record) => "/transactions/{$record->id}/print"),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn() => auth()->user()->can('delete_transaction')),
                Tables\Actions\EditAction::make()
                    ->color('info')
                    ->url(fn($record) => "/dashboard/kasir?id={$record->id}")
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn() => auth()->user()->can('delete_any_transaction')),
                ExportBulkAction::make()->exporter(TransactionExporter::class)
            ])
            ->headerActions([
                ExportAction::make()->exporter(TransactionExporter::class)
            ])
            ->defaultSort('transaction_date', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['id', 'total_amount'];
    }
}
