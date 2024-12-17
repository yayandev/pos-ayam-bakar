<x-filament-panels::page>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Menu Section -->
        <div class="col-span-3">
            <h2 class="text-lg font-bold mb-2">Pilih Menu</h2>
            <!-- Input Pencarian -->
            <div class="mb-4">
                <input type="text" wire:model="search"
                       placeholder="Cari menu..."
                       class="w-full px-4 py-2 border border-gray-300 rounded shadow-sm"
                >
            </div>

            <!-- Daftar Menu -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @forelse ($this->menus as $menu)
                    <div class="bg-white shadow rounded p-2 text-center">
                        <img src="{{ asset('storage/' . $menu->image) ?? 'https://via.placeholder.com/100' }}"
                             alt="{{ $menu->name }}"
                             class="w-full h-24 object-cover mb-2 rounded">
                        <p class="font-semibold">{{ $menu->name }}</p>
                        <p class="text-gray-600">Rp {{ number_format($menu->price, 0, ',', '.') }}</p>
                        <button wire:click="addToCart({{ $menu->id }})"
                                class="bg-red-500 text-white px-3 py-1 rounded mt-2">
                            Tambah
                        </button>
                    </div>
                @empty
                    <p class="text-gray-500 col-span-4 text-center">Menu tidak ditemukan.</p>
                @endforelse
            </div>
        </div>

        <!-- Cart Section -->
        <div class="col-span-1">
            <h2 class="text-lg font-bold mb-2">Daftar Pesanan</h2>
            <div class="bg-white shadow rounded p-4">

                @if (count($this->cart) > 0)
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-1" for="customerName">
                        Nama Customer
                    </label>
                    <input type="text" wire:model="customerName" id="customerName"
                           placeholder="Masukkan Nama Customer"
                           class="w-full border-gray-300 rounded shadow-sm">
                </div>
                    <ul>
                        @foreach ($this->cart as $menuId => $item)
                            <li class="flex justify-between mb-2">
                                <div>
                                    <p class="font-semibold">{{ $item['name'] }} (x{{ $item['quantity'] }})</p>
                                    <p class="text-sm text-gray-600">
                                        Rp {{ number_format($item['price'] * $item['quantity'], 0, ',', '.') }}
                                    </p>
                                </div>
                                <button wire:click="removeFromCart({{ $menuId }})"
                                        class="text-red-500 text-sm">
                                    Hapus
                                </button>
                            </li>
                        @endforeach
                    </ul>
                    <hr class="my-2">
                    <p class="font-bold text-lg">
                        Total: Rp {{ number_format($this->total, 0, ',', '.') }}
                    </p>
                    <button wire:click="saveTransaction()"
                            class="bg-red-500 text-white px-4 py-2 rounded w-full mt-2">
                        Bayar
                    </button>
                @else
                    <p class="text-gray-500">Belum ada pesanan.</p>
                @endif
            </div>
        </div>
    </div>
</x-filament-panels::page>
