<?php

namespace App\Filament\Resources;

use App\Filament\Exports\TransactionExporter;
use App\Filament\Resources\TransactionResource\Pages;
use App\Filament\Resources\TransactionResource\RelationManagers;
use App\Models\Menu;
use App\Models\Transaction;
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

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
{
    return $form
        ->schema([
                Card::make()
                ->schema([
                    Forms\Components\Repeater::make('items')
                        ->relationship()
                        ->schema([
                            Forms\Components\Select::make('menu_id')
                                ->label('Menu')
                                ->options(Menu::pluck('name', 'id')) // Mengambil menu dari tabel Menu
                                ->required()
                                ->reactive()
                                ->searchable()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    // Mengisi harga otomatis berdasarkan menu yang dipilih
                                    $menu = Menu::find($state);
                                    if ($menu) {
                                        $set('price', $menu->price); // Set harga berdasarkan menu yang dipilih
                                    }
                                }),
                            Forms\Components\TextInput::make('quantity')
                                ->numeric()
                                ->default(1)
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    // Menghitung total setelah kuantitas diubah
                                    $items = $get('../../items') ?? [];
                                    $total = collect($items)
                                        ->sum(fn ($item) => ($item['quantity'] ?? 0) * ($item['price'] ?? 0));
                                    $set('../../total_amount', $total); // Update total_amount berdasarkan kuantitas
                                }),
                            Forms\Components\TextInput::make('price')
                                ->numeric()
                                ->required()
                                ->readOnly()
                                ->prefix('Rp'),
                            Placeholder::make('subtotal')
                                ->content(function (callable $get) {
                                    // Menampilkan subtotal untuk setiap item (quantity * price)
                                    return 'Rp ' . number_format(($get('quantity') ?? 0) * ($get('price') ?? 0), 2);
                                }),
                        ])
                        ->columns(4)
                        ->required(),
                ])
                ->columnSpan(['lg' => 2]),
            Card::make()
                ->schema([
                    Forms\Components\TextInput::make('customer_name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\DateTimePicker::make('transaction_date')
                        ->default(now())
                        ->required(),
                    Placeholder::make('total_amount_display')
                        ->label('Total Amount')
                        ->content(function (callable $get) {
                            // Menghitung total amount berdasarkan item yang ditambahkan
                            $total = collect($get('items') ?? [])
                                ->sum(fn ($item) => ($item['quantity'] ?? 0) * ($item['price'] ?? 0));
                            return 'Rp ' . number_format($total, 2);
                        }),
                    Hidden::make('total_amount')
                        ->reactive()
                        ->afterStateHydrated(function (Hidden $component, $state, callable $get) {
                            // Menghitung total amount setelah form diisi
                            $items = $get('items') ?? [];
                            $total = collect($items)->sum(fn ($item) => ($item['quantity'] ?? 0) * ($item['price'] ?? 0));
                            $component->state($total); // Mengupdate state total_amount
                        })
                        ->dehydrateStateUsing(fn ($state) => $state ?: 0),

                ])
                ->columnSpan(['lg' => 1]),

        ])
        ->columns(3);
}



public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('id')
                ->label('Transaction ID')
                ->searchable(),
            Tables\Columns\TextColumn::make('customer_name')
                ->searchable(),
            Tables\Columns\TextColumn::make('transaction_date')
                ->dateTime()
                ->sortable(),
            Tables\Columns\TextColumn::make('total_amount')
                ->money('idr')
                ->sortable(),
            Tables\Columns\TextColumn::make('items_count')
                ->label('Items')
                ->counts('items'),
        ])
        ->filters([
            // Filter untuk rentang tanggal
            Tables\Filters\Filter::make('date_range')
                ->label('Rentang Tanggal')
                ->form([
                    Forms\Components\DatePicker::make('start_date')
                        ->label('Tanggal Mulai')
                        // ->default(today()->subMonth()) // Tanggal mulai default 1 bulan yang lalu
                        ->required(),
                    Forms\Components\DatePicker::make('end_date')
                        ->label('Tanggal Akhir')
                        // ->default(today()) // Tanggal akhir default hari ini
                        ->required(),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query->when(
                        $data['start_date'] && $data['end_date'],
                        fn (Builder $query) => $query->whereBetween('transaction_date', [
                            $data['start_date'],
                            $data['end_date'],
                        ])
                    );
                }),
        ])
        ->actions([
            Tables\Actions\ViewAction::make(),
            Tables\Actions\EditAction::make(),
        ])
        ->bulkActions([
            Tables\Actions\DeleteBulkAction::make(),
            ExportBulkAction::make()->exporter(TransactionExporter::class)
        ])
        ->headerActions([
            ExportAction::make()->exporter(TransactionExporter::class)
        ]);
}






    public static function getRelations(): array
    {
        return [
            //
        ];
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
