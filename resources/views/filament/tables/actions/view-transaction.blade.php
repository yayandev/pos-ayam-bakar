<div class="space-y-4 p-4">
    @if($record)

        {{-- ID Transaksi --}}
        <div class="flex justify-between">
            <span class="font-semibold">Transaction ID:</span>
            <span>{{ $record->code_transaction }}</span>
        </div>

        {{-- Nama Pelanggan --}}
        <div class="flex justify-between">
            <span class="font-semibold">Nama Pelanggan:</span>
            <span>{{ $record->customer_name }}</span>
        </div>

        {{-- Tanggal Transaksi --}}
        <div class="flex justify-between">
            <span class="font-semibold">Tanggal Transaksi:</span>
            <span>{{ $record->transaction_date->format('d M Y H:i') }}</span>
        </div>

        {{-- Total Transaksi --}}
        <div class="flex justify-between">
            <span class="font-semibold">Total Amount:</span>
            <span>Rp {{ number_format($record->total_amount, 0, ',', '.') }}</span>
        </div>

        {{-- Metode Pembayaran --}}
        <div class="flex justify-between">
            <span class="font-semibold">Metode Pembayaran:</span>
            <span>{{ ucfirst($record->payment_method) }}</span>
        </div>

        {{-- Kasir --}}
        <div class="flex justify-between">
            <span class="font-semibold">Kasir:</span>
            <span>{{ $record->user->name }}</span>
        </div>

        {{-- Daftar Item --}}
        <div>
            <span class="font-semibold">Items:</span>
            <ul class="list-disc list-inside mt-2">
                @foreach ($record->items as $item)
                    <li>
                        {{ $item->menu->name }} ({{ $item->quantity }} x {{ number_format($item->price, 0, ',', '.') }})
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- Tombol Cetak --}}
        {{-- <div class="mt-6 text-center">
            <x-filament::button href="/transactions/{{ $record->id }}/print" target="_blank" color="primary">
                Cetak Bukti Transaksi
            </x-filament::button>
        </div> --}}
    @else
        <div class="text-center text-gray-500">
            Data tidak tersedia
        </div>
    @endif
</div>
