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
            width: 58mm;
            /* Ukuran kertas thermal */
            margin: 0;
            padding: 10px;
        }

        .header {
            width: 100%;
            border-bottom: 1px dashed black;
            border-collapse: collapse;
            padding-bottom: 5px;
        }

        .header,
        .footer {
            text-align: center;
            margin-bottom: 10px;
        }

        .details,
        .items {
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
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
            margin: 0;
            padding: 0;
        }

        .phone {
            font-size: 13px;
            font-weight: 500;
            margin: 0;
            padding: 0;
        }

        .border-top {
            width: 100%;
            border-top: 1px dashed black;
            border-collapse: collapse;
            padding-top: 5px;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="header">
            <h2>Bukti Transaksi</h2>
            <p class="subheader">AYAM BAKAR MADU N.B.S</p>
            <p class="address">Jl. Raya Sentul - Nyapah
            </p>
            <p class="address">Ds. Pematang Masjid -Kragilan
            </p>
            <p class="phone">
                087871922271 / 085217956200
            </p>
        </div>

        <div class="details">
            <table>
                <tr>
                    <td><strong>ID Transaksi</strong></td>
                    <td>:</td>
                    <td>{{ $transaction->code_transaction }}</td>
                </tr>
                <tr>
                    <td><strong>Nama</strong></td>
                    <td>:</td>
                    <td>{{ $transaction->customer_name }}</td>
                </tr>
                <tr>
                    <td><strong>Tanggal</strong></td>
                    <td>:</td>
                    <td>{{ $transaction->transaction_date->format('d M Y H:i') }}</td>
                </tr>
                <tr>
                    <td><strong>Kasir</strong></td>
                    <td>:</td>
                    <td>{{ $transaction->user->name }}</td>
                </tr>
                <tr>
                    <td><strong>Pembayaran</strong></td>
                    <td>:</td>
                    <td>{{ ucfirst($transaction->payment_method) }}</td>
                </tr>
            </table>
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
                            <td style="text-align: center;">{{ $item->quantity }}</td>
                            <td>Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    <tr class="border-top">
                        <td><strong>Total</strong></td>
                        <td style="text-align: start">:</td>
                        <td><span>Rp</span><span> {{ number_format($transaction->total_amount, 0, ',', '.') }}</span>
                        </td>
                    </tr>
                    @if ($transaction->payment_method == 'cash')
                        <tr>
                            <td><strong>Bayar</strong></td>
                            <td style="text-align: start">:</td>
                            <td><span>Rp</span><span> {{ number_format($transaction->money_paid, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    @if ($transaction->payment_method == 'cash')
                        <tr>
                            <td><strong>Kembalian</strong></td>
                            <td style="text-align: start">:</td>
                            <td><span>Rp</span><span>
                                    {{ number_format($transaction->money_paid - $transaction->total_amount, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <div class="footer">
            <p>Terima kasih atas kunjungan Anda!</p>
            <p style="font-size: 11px">#Rasa dan Kualitas adalah Prioritas Kami</p>
        </div>
    </div>

    <script>
        //window print
        window.print()
    </script>
</body>

</html>
