<?php

use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::get('/', function () {
    return redirect('/dashboard');
});

Route::get('/transactions/{id}/print', function ($id) {
    // Ambil data transaksi berdasarkan ID
    $transaction = Transaction::with('items.menu', 'user')->findOrFail($id);

    // Data yang akan dikirim ke view
    $data = [
        'transaction' => $transaction,
        'items' => $transaction->items,
    ];

    // Generate PDF dari view
    $pdf = Pdf::loadView('print', $data);

    // Set header untuk mencegah download
    return response($pdf->output())
        ->header('Content-Type', 'application/pdf')
        ->header('Content-Disposition', 'inline; filename="bukti-transaksi-' . Str::slug($transaction->code_transaction) . '.pdf"');
});


