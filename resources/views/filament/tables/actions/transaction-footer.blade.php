<div class="flex justify-end space-x-2">
    {{-- Tombol Cetak --}}
    <x-filament::button onclick="window.open('/transactions/{{ $record->id }}/print', '_blank')" target="_blank" color="primary">
        Cetak Bukti Transaksi
    </x-filament::button>
</div>
