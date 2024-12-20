<x-filament-panels::page>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <div class="flex w-full gap-10 md:gap-5 flex-col-reverse md:flex-row box-border items-start">
        <!-- Menu Section -->
        <x-filament::section class="flex-1">
                <h2 class="text-lg font-bold mb-2">Pilih Menu</h2>

                <!-- Tombol Filter Kategori -->
                {{-- <div class="flex gap-2 mb-4">
                    <x-filament::button wire:click="setCategory('makanan')" :color="$currentCategory === 'makanan' ? 'primary' : 'secondary'">
                        Makanan
                    </x-filament::button>
                    <x-filament::button wire:click="setCategory('minuman')" :color="$currentCategory === 'minuman' ? 'primary' : 'secondary'">
                        Minuman
                    </x-filament::button>
                </div> --}}

                <div class="border-b border-gray-200 mb-3">
                    <nav class="-mb-px flex space-x-4" aria-label="Tabs">
                        <!-- Tab Makanan -->
                        <a href="javascript:void(0);"
                           wire:click="setCategory('makanan')"
                           class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm
                                  {{ $currentCategory === 'makanan' ? 'border-red-500 text-red-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            Makanan
                        </a>

                        <!-- Tab Minuman -->
                        <a href="javascript:void(0);"
                           wire:click="setCategory('minuman')"
                           class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm
                                  {{ $currentCategory === 'minuman' ? 'border-red-500 text-red-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            Minuman
                        </a>
                    </nav>
                </div>



                <!-- Input Pencarian -->
                <div class="mb-4">
                    <x-filament::input.wrapper>
                        <x-filament::input type="text" wire:model.live="search" placeholder="Cari menu..." />
                    </x-filament::input.wrapper>
                </div>

                <!-- Daftar Menu -->
                <div class="grid grid-cols-2 md:grid-cols-3 2xl:grid-cols-4  max-h-96 overflow-y-auto gap-5">
                    @forelse ($this->menus as $menu)
                        <div wire:click="addToCart({{ $menu->id }})"
                            class="w-full bg-white dark:bg-gray-800 shadow rounded hover:bg-gray-100 dark:hover:bg-gray-700 p-2 text-center cursor-pointer transition-all duration-200 active:scale-95 relative {{ isset($selectedMenus[$menu->id]) ? 'border-4 border-green-500' : '' }}">
                            <!-- Checkmark icon when selected -->
                            @if (isset($selectedMenus[$menu->id]))
                                <div
                                    class="absolute top-2 right-2 bg-green-500 text-white rounded-full w-6 h-6 flex items-center justify-center">
                                    ✓
                                </div>
                            @endif

                            <img src="{{ $menu->image ? asset('storage/' . $menu->image) : 'https://via.placeholder.com/150' }}"
                                alt="{{ $menu->name }}" class="w-full h-24 object-cover mb-2 rounded">
                            <p class="font-semibold">{{ $menu->name }}</p>
                            <p class="text-gray-600 dark:text-gray-400">
                                Rp {{ number_format($menu->price, 0, ',', '.') }}
                            </p>

                            <!-- Quantity controls -->
                            @if (isset($selectedMenus[$menu->id]))
                                <div class="flex justify-center items-center mt-2 space-x-2">
                                    <x-filament::icon-button icon="heroicon-m-minus" color="secondary"
                                        wire:click.stop="removeFromCart({{ $menu->id }})" type="button" />
                                    <span class="font-bold">{{ $cart[$menu->id]['quantity'] ?? 0 }}</span>
                                    <x-filament::icon-button icon="heroicon-m-plus" color="secondary"
                                        wire:click.stop="incrementCartItem({{ $menu->id }})" type="button" />
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="text-gray-500 col-span-4 text-center dark:text-gray-400">
                            Menu tidak ditemukan.
                        </p>
                    @endforelse
                </div>
        </x-filament::section>
        <x-filament::section class="w-full md:w-80">
            <!-- Cart Section -->
                    <h2 class="text-lg font-bold mb-2">Daftar Pesanan</h2>

                    <form wire:submit.prevent="saveTransaction space-y-5">
                        <div class="mb-3">
                            <label for="customer" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                ID Transaksi
                            </label>
                            <x-filament::input.wrapper>
                                <x-filament::input type="text" wire:model.live="transactionCode" readonly
                                    placeholder="Masukkan nama customer..." />
                            </x-filament::input.wrapper>
                        </div>
                        <div class="mb-3">
                            <label for="customer" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                Nama Customer
                            </label>
                            <x-filament::input.wrapper>
                                <x-filament::input type="text" wire:model.live="customerName"
                                    placeholder="Masukkan nama customer..." />
                            </x-filament::input.wrapper>
                        </div>

                        <!-- Daftar Pesanan -->
                        @if (count($cart) > 0)
                            <ul class="mt-4 w-full overflow-y-auto max-h-60">
                                @foreach ($cart as $menuId => $item)
                                    <li class="flex justify-between items-center mb-2 bg-gray-100 p-2 rounded">
                                        <div>
                                            <p class="font-semibold">
                                                {{ $item['name'] }} (x{{ $item['quantity'] }})
                                            </p>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                                Rp {{ number_format($item['price'] * $item['quantity'], 0, ',', '.') }}
                                            </p>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <x-filament::icon-button icon="heroicon-m-minus" color="secondary"
                                                wire:click="removeFromCart({{ $menuId }})" type="button" />
                                            <x-filament::icon-button icon="heroicon-m-x-mark" color="danger"
                                                wire:click="removeFromCart({{ $menuId }})" type="button" />
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-gray-500 dark:text-gray-400 mt-4 text-center">
                                Tidak ada pesanan.
                            </p>
                        @endif

                        <!-- Total dan Kembalian -->
                        <hr class="my-4">
                        <div class="space-y-3">
                            <p class="font-bold text-lg dark:text-white flex justify-between">
                                <span>Total:</span>
                                <span>Rp {{ number_format($this->total(), 0, ',', '.') }}</span>
                            </p>

                            <div class="mb-3">
                                <label for="payment_method"
                                    class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    Metode Pembayaran
                                </label>
                                <select name="payment_method" wire:model.live="paymentMethod" id="" class="focus:outline-red-500 w-full p-2 border border-gray-300 dark:border-gray-600 rounded focus:outline-none dark:bg-gray-800 dark:text-white">
                                    <option value="" disabled selected>Pilih Metode Pembayaran</option>
                                    <option value="cash">Cash</option>
                                    <option value="cashless">Cashless</option>
                                </select>
                            </div>

                            @if ($paymentMethod == 'cash')
                            <div class="mb-3">
                                <label for="moneyPaid"
                                    class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    Uang Pembayaran
                                </label>
                                <x-filament::input.wrapper>
                                    <x-filament::input type="number" wire:model.live="moneyPaid"
                                        placeholder="Masukkan uang pembayaran..." min="0" />
                                </x-filament::input.wrapper>
                            </div>
                            @endif

                            @if ($paymentMethod == 'cash')
                                <p class="font-bold text-lg dark:text-white flex justify-between">
                                    <span>Kembalian:</span>
                                    <span>Rp {{ number_format($this->change(), 0, ',', '.') }}</span>
                                </p>
                            @endif
                        </div>

                        <!-- Tombol Bayar -->
                        <x-filament::button wire:click="saveTransaction" type="submit" color="primary" class="w-full mt-3">
                            Bayar
                        </x-filament::button>
                    </form>
        </x-filament::section>
        </div>
</x-filament::page>
