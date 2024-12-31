<?php

namespace App\Filament\Pages;

use App\Models\Menu;
use App\Models\Transaction;
use App\Models\TransactionItem;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;

class POS extends Page
{
    use HasPageShield;
    protected static ?string $navigationLabel = 'Kasir';

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static string $view = 'filament.pages.p-o-s';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'kasir';

    protected static ?string $title = 'Kasir';


    public $menus;
    public $cart = [];
    public $customerName = ''; // Default customer name
    public $currentCategory = 'makanan'; // Default kategori
    public $search = '';
    public $paymentMethod = 'cash'; // Default payment method
    public $moneyPaid;
    public $selectedMenus = []; // Track selected menus
    public $transactionCode;
    public $transactionId;
    public $isEditMode = false;
    public $id = null;

    public function generateTransactionCode()
    {
        return DB::transaction(function () {
            $year = date('Y');

            // Menggunakan nama kolom yang benar (code_transaction)
            $lastTransaction = Transaction::whereYear('transaction_date', $year)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            // Mengambil nomor dari code_transaction, bukan transaction_code
            $nextNumber = $lastTransaction
                ? (intval(substr($lastTransaction->code_transaction, -4)) + 1)
                : 1;

            $transactionCode = 'NBS/' . $year . '/' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            return $transactionCode;
        });
    }

    public function mount()
    {
        $this->id = request()->query('id');

        $this->loadMenus();
        $this->customerName = ''; // Set default customer name on page load

        $this->transactionCode = $this->generateTransactionCode();
        if ($this->id) {
            $this->loadTransaction($this->id);
        }
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


    public function loadTransaction($id)
    {
        $transaction = Transaction::with('items.menu')->findOrFail($id);

        $this->transactionId = $transaction->id;
        $this->transactionCode = $transaction->code_transaction;
        $this->customerName = $transaction->customer_name;
        $this->paymentMethod = $transaction->payment_method;
        $this->moneyPaid = $transaction->money_paid;
        $this->isEditMode = true;

        // Mengisi cart dari item transaksi
        $this->cart = $transaction->items->mapWithKeys(function ($item) {
            return [
                $item->menu_id => [
                    'name' => $item->menu->name,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                ],
            ];
        })->toArray();

        // Mengisi selectedMenus dari item transaksi
        $this->selectedMenus = $transaction->items->mapWithKeys(function ($item) {
            return [$item->menu_id => true];
        })->toArray();
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

    public function total()
    {
        return collect($this->cart)->sum(function ($item) {
            return $item['price'] * $item['quantity'];
        });
    }

    public function change()
    {
        return $this->moneyPaid - $this->total();
    }

    public function saveTransaction()
    {
        // Validasi terlebih dahulu
        if (count($this->cart) == 0) {
            Notification::make()->title('Keranjang belanja tidak boleh kosong.')->danger()->send();
            return;
        }

        if ($this->paymentMethod == 'cash' && $this->moneyPaid <= 0) {
            Notification::make()->title('Uang yang dibayarkan tidak boleh kosong.')->danger()->send();
            return;
        }

        if ($this->paymentMethod == 'cash' && $this->moneyPaid < $this->total()) {
            Notification::make()->title('Uang yang dibayarkan tidak cukup.')->danger()->send();
            return;
        }

        // Set moneyPaid untuk pembayaran cashless
        if ($this->paymentMethod == 'cashless') {
            $this->moneyPaid = $this->total();
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
                    'code_transaction' => $this->transactionCode,
                    'user_id' => Auth::id(),
                ]);

                $this->transactionId = $transaction->id;

                foreach ($this->cart as $menuId => $item) {
                    TransactionItem::create([
                        'transaction_id' => $transaction->id,
                        'menu_id' => $menuId,
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                    ]);
                }
            });

            // Pindahkan notifikasi dan redirect ke luar transaction
            Notification::make()->title('Transaksi Berhasil!')->success()->send();
            $this->resetCart();
            // Generate kode transaksi baru setelah reset
            $this->transactionCode = $this->generateTransactionCode();

            // redirect print receipt
            return redirect()->to('transactions/' . $this->transactionId . '/print');
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
        $this->moneyPaid = "";
        $this->customerName = '';
        $this->paymentMethod = 'cash';
        $this->selectedMenus = []; // Reset selected menus
    }

    public function orderTransaction()
    {
        // Validasi terlebih dahulu
        if (count($this->cart) == 0) {
            Notification::make()->title('Keranjang belanja tidak boleh kosong.')->danger()->send();
            return;
        }

        $this->updatedMoneyPaid();

        try {
            DB::transaction(function () {
                $transaction = Transaction::create([
                    'transaction_date' => now(),
                    'total_amount' => $this->total(),
                    'customer_name' => $this->customerName ?? '',
                    'payment_method' => $this->paymentMethod,
                    'money_paid' => 0,
                    'code_transaction' => $this->transactionCode,
                    'user_id' => Auth::id(),
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

            // Pindahkan notifikasi dan redirect ke luar transaction
            Notification::make()->title('Transaksi Berhasil!')->success()->send();
            $this->resetCart();
            // Generate kode transaksi baru setelah reset
            $this->transactionCode = $this->generateTransactionCode();

            return redirect()->to('/dashboard/transactions');
        } catch (\Exception $e) {
            Notification::make()
                ->title('Gagal menyimpan transaksi')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function editTransaction()
    {
        // Validasi terlebih dahulu
        if (count($this->cart) == 0) {
            Notification::make()->title('Keranjang belanja tidak boleh kosong.')->danger()->send();
            return;
        }

        // Temukan transaksi yang akan diedit
        $transaction = Transaction::findOrFail($this->transactionId);

        try {
            DB::transaction(function () use ($transaction) {
                // Update data transaksi
                $transaction->update([
                    'total_amount' => $this->total(),
                    'customer_name' => $this->customerName ?? '',
                    'payment_method' => $this->paymentMethod,
                    'money_paid' => $this->moneyPaid,
                    'code_transaction' => $this->transactionCode,
                    'user_id' => Auth::id(),
                ]);

                // Hapus item transaksi lama sebelum menyimpan item baru
                $transaction->items()->delete();

                // Simpan item baru
                foreach ($this->cart as $menuId => $item) {
                    TransactionItem::create([
                        'transaction_id' => $transaction->id,
                        'menu_id' => $menuId,
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                    ]);
                }
            });

            Notification::make()->title('Transaksi berhasil diperbarui!')->success()->send();
            return redirect()->to('/dashboard/transactions');
        } catch (\Exception $e) {
            Notification::make()
                ->title('Gagal memperbarui transaksi')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function editTransactionAndPrintReceipt()
    {
        // jika uang pembayaran dan kemblian minus tidak bisa
        if ($this->moneyPaid < $this->total()) {
            Notification::make()->title('Uang pembayaran tidak mencukupi.')->danger()->send();
            return;
        }

        $this->editTransaction();
        return redirect()->to('transactions/' . $this->transactionId . '/print');
    }
}
