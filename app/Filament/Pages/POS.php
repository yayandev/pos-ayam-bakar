<?php

namespace App\Filament\Pages;

use App\Models\Menu;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;

class POS extends Page
{
    protected static ?string $navigationLabel = 'Point of Sale';

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static string $view = 'filament.pages.p-o-s';

    public $menus;
    public $cart = [];
    public $customerName = ''; // Default customer name
    public $currentCategory = 'makanan'; // Default kategori
    public $search = '';
    public $paymentMethod = 'cash'; // Default payment method
    public $moneyPaid = 0;
    public $selectedMenus = []; // Track selected menus
    public $transactionCode;


    public function generateTransactionCode()
    {
        // Ambil tahun sekarang
        $year = date('Y');

        // Cari transaksi terakhir yang dibuat di tahun yang sama
        $lastTransaction = Transaction::whereYear('transaction_date', $year)
            ->orderByDesc('id') // Urutkan berdasarkan id
            ->first();

        // Jika tidak ada transaksi, mulai dengan nomor urut 1
        $nextNumber = $lastTransaction ? (intval(substr($lastTransaction->transaction_code, -4)) + 1) : 1;

        // Format kode transaksi menjadi 'NBS/{tahun}/{nomor urut}'
        $transactionCode = 'NBS/' . $year . '/' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

        return $transactionCode;
    }

    public function mount()
    {
        $this->loadMenus();
        $this->customerName = ''; // Set default customer name on page load

        $this->transactionCode = $this->generateTransactionCode();
    }

    public function setCategory($category)
    {
        $this->currentCategory = $category;
        $this->loadMenus();
    }

    public function setPaymentMethod($method)
    {
        $this->paymentMethod = $method;

        // Automatically set money paid for cashless
        if ($method == 'cashless') {
            $this->moneyPaid = $this->total();
        }
    }

    public function loadMenus()
    {
        $query = Menu::where('category', $this->currentCategory);

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        $this->menus = $query->get();
    }

    public function updatedSearch()
    {
        $this->loadMenus();
    }

    public function addToCart($menuId)
    {
        $menu = Menu::find($menuId);
        if ($menu) {
            // If menu is already selected, toggle selection
            if (isset($this->selectedMenus[$menuId])) {
                unset($this->selectedMenus[$menuId]);

                // Remove from cart if unchecked
                if (isset($this->cart[$menuId])) {
                    unset($this->cart[$menuId]);
                }
            } else {
                // Select menu and add to cart
                $this->selectedMenus[$menuId] = true;

                if (isset($this->cart[$menuId])) {
                    $this->cart[$menuId]['quantity'] += 1;
                } else {
                    $this->cart[$menuId] = [
                        'name' => $menu->name,
                        'price' => $menu->price,
                        'quantity' => 1,
                    ];
                }
            }
        }
    }

    public function incrementCartItem($menuId)
    {
        if (isset($this->cart[$menuId])) {
            $this->cart[$menuId]['quantity'] += 1;
            $this->selectedMenus[$menuId] = true;
        }
    }

    public function removeFromCart($menuId)
    {
        if (isset($this->cart[$menuId])) {
            if ($this->cart[$menuId]['quantity'] > 1) {
                $this->cart[$menuId]['quantity'] -= 1;
            } else {
                unset($this->cart[$menuId]);
                unset($this->selectedMenus[$menuId]);
            }
        }
    }

    public function updatedMoneyPaid()
    {
        // Ensure money paid is non-negative
        $this->moneyPaid = max(0, $this->moneyPaid);
    }

    #[Computed]
    public function total()
    {
        return collect($this->cart)->sum(function ($item) {
            return $item['price'] * $item['quantity'];
        });
    }

    #[Computed]
    public function change()
    {
        return max(0, $this->moneyPaid - $this->total());
    }

    public function saveTransaction()
    {
        // Validate transaction
        if(count($this->cart) == 0) {
            Notification::make()->title('Keranjang belanja tidak boleh kosong.')->danger()->send();
            return;
        }

        // Handle cashless payment
        if ($this->paymentMethod == 'cashless') {
            $this->moneyPaid = $this->total();
        }

        // Validate cash payment
        if($this->paymentMethod == 'cash') {
            if($this->moneyPaid == 0) {
                Notification::make()->title('Uang yang dibayarkan tidak boleh kosong.')->danger()->send();
                return;
            }

            if ($this->moneyPaid < $this->total()) {
                Notification::make()->title('Uang yang dibayarkan tidak cukup.')->danger()->send();
                return;
            }
        }

        $this->updatedMoneyPaid();

        try {
            DB::transaction(function () {
                $transaction = Transaction::create([
                    'transaction_date' => now(),
                    'total_amount' => $this->total(),
                    'customer_name' => $this->customerName ?? '',
                    'payment_method' => $this->paymentMethod,
                    'money_paid' => $this->moneyPaid,
                    'code_transaction' => $this->transactionCode
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

            $this->resetCart();
            Notification::make()->title('Transaksi Berhasil!')->success()->send();

            return redirect('/dashboard/transactions');
        } catch (\Exception $e) {
            Notification::make()
                ->title('Gagal menyimpan transaksi')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function resetCart()
    {
        $this->cart = [];
        $this->moneyPaid = 0;
        $this->customerName = 'Umum';
        $this->paymentMethod = 'cash';
        $this->selectedMenus = []; // Reset selected menus
    }
}
