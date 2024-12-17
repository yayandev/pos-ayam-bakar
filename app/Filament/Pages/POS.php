<?php

namespace App\Filament\Pages;

use App\Models\Menu;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class POS extends Page
{
    protected static ?string $navigationLabel = 'Point of Sale';

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static string $view = 'filament.pages.p-o-s';

    public $menus;
    public $cart = [];
    public $total = 0;

    public $customerName; // Tambahkan ini
    public $search = '';

    public function mount()
    {
        // Ambil data menu
        $this->loadMenus();
    }

    public function loadMenus()
    {
        // Ambil menu berdasarkan kata kunci pencarian
        $this->menus = Menu::where('name', 'like', '%' . $this->search . '%')->get();
    }

    public function updatedSearch()
    {
        $this->loadMenus();
    }

    public function addToCart($menuId)
    {
        $menu = Menu::find($menuId);
        if ($menu) {
            // Tambah menu ke keranjang
            if (isset($this->cart[$menuId])) {
                $this->cart[$menuId]['quantity'] += 1;
            } else {
                $this->cart[$menuId] = [
                    'name' => $menu->name,
                    'price' => $menu->price,
                    'quantity' => 1,
                ];
            }

            $this->updateTotal();
        }
    }

    public function removeFromCart($menuId)
    {
        if (isset($this->cart[$menuId])) {
            unset($this->cart[$menuId]);
            $this->updateTotal();
        }
    }

    public function updateTotal()
    {
        $this->total = collect($this->cart)->sum(function ($item) {
            return $item['price'] * $item['quantity'];
        });
    }

    public function saveTransaction()
    {
        // Validasi input nama customer
        if (!$this->customerName) {
            Notification::make()->title('Nama Customer tidak boleh kosong.')->danger()->send();
            return;
        }

        DB::transaction(function () {
            $transaction = Transaction::create([
                'transaction_date' => now(),
                'total_amount' => $this->total,
                'customer_name' => $this->customerName, // Ambil nama dari input
            ]);

            foreach ($this->cart as $menuId => $item) {
                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'menu_id' => $menuId,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);
            }
        });

        // Reset keranjang dan nama customer
        $this->cart = [];
        $this->total = 0;
        $this->customerName = null;

        Notification::make()->title('Transaksi Berhasil!')->success()->send();
    }
}
