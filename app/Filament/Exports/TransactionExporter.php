<?php

namespace App\Filament\Exports;

use App\Models\Transaction;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\CellVerticalAlignment;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;

class TransactionExporter extends Exporter
{
    protected static ?string $model = Transaction::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('transaction_date')->label('Tanggal'),
            ExportColumn::make('total_amount')->label('Total'),
            ExportColumn::make('customer_name')->label('Customer'),
            ExportColumn::make('itemsCount')->label('Total Item'),
            ExportColumn::make('created_at')->label('Dibuat'),
            ExportColumn::make('updated_at')->label('Diperbarui'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your transaction export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }


//     public function getXlsxHeaderCellStyle(): ?Style
// {
//     return (new Style())
//         ->setFontBold()
//         ->setFontItalic()
//         ->setFontSize(14)
//         ->setFontName('Consolas')
//         ->setFontColor(Color::rgb(255, 255, 77))
//         ->setBackgroundColor(Color::rgb(0, 0, 0))
//         ->setCellAlignment(CellAlignment::CENTER)
//         ->setCellVerticalAlignment(CellVerticalAlignment::CENTER);
// }
}
