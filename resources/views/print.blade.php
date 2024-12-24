<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bukti Transaksi</title>
    <style>
        body {
            display: flex;
            justify-content: center
        }
        .container {
            font-family: Arial, sans-serif;
            font-size: 12px;
            width: 58mm; /* Ukuran kertas thermal */
            margin: 0;
            padding: 10px;
        }

        .header {
            width: 100%;
            border-bottom: 1px dashed black;
            border-collapse: collapse;
        }
        .header, .footer {
            text-align: center;
            margin-bottom: 10px;
        }
        .details, .items {
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 5px;
            text-align: left;
        }
        th {
            border-bottom: 1px dashed black;
        }

        .subheader {
            font-size: 16px;
            font-weight: bold;
        }

        .address {
            font-size: 14px;
            font-weight: 500;
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="header">
            <h2>Bukti Transaksi</h2>
            <p class="subheader">AYAM BAKAR MADU N.B.S</p>
            <p class="address">Jl. Raya Sentul - Nyapah Rt. 005/002 Ds. Pematang Masjid -Kragilan
            </p>
            <p class="address">Telp/Wa : 087871922271/085217956200</p>
        </div>

        <div class="details">
            <p><strong>ID:</strong> {{ $transaction->code_transaction }}</p>
            <p><strong>Nama:</strong> {{ $transaction->customer_name }}</p>
            <p><strong>Tanggal:</strong> {{ $transaction->transaction_date->format('d M Y H:i') }}</p>
            <p><strong>Kasir:</strong> {{ $transaction->user->name }}</p>
            <p><strong>Pembayaran:</strong> {{ ucfirst($transaction->payment_method) }}</p>
        </div>

        <div class="items">
            <h4>Items:</h4>
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Harga</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                    <tr>
                        <td>{{ $item->menu->name }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                    <tr>
                        <td ><strong>Total:</strong></td>
                        <td colspan="2"><span>Rp</span><span> {{ number_format($transaction->total_amount, 0, ',', '.') }}</span></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="footer">
            <p>Terima kasih atas kunjungan Anda!</p>
        </div>
    </div>

    <script>
        //window print
        window.print()
    </script>
</body>
</html>
