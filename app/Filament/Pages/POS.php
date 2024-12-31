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
    public $customerName = '';
    public $currentCategory = 'makanan';
    public $search = '';
    public $paymentMethod = 'cash';
    public $moneyPaid;
    public $selectedMenus = [];
    public $transactionCode;
    public $transactionId;
    public $isEditMode = false;
    public $id = null;

    public function generateTransactionCode()
    {
        return DB::transaction(function () {
            $year = date('Y');

            $lastTransaction = Transaction::whereYear('transaction_date', $year)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

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
        $this->customerName = '';

        $this->transactionCode = $this->generateTransactionCode();
        if ($this->id) {
            $this->loadTransaction($this->id);
        }
    }

    public function formatMoneyPaid($value)
    {
        // Hapus semua karakter non-digit
        $cleanValue = preg_replace('/[^0-9]/', '', $value);

        // Konversi ke integer jika ada nilai
        if ($cleanValue !== '') {
            $this->moneyPaid = (int)$cleanValue;
        } else {
            $this->moneyPaid = null;
        }
    }

    public function updatedMoneyPaid($value)
    {
        if ($value === '') {
            $this->moneyPaid = null;
            return;
        }

        // Hapus semua karakter non-digit
        $cleanValue = preg_replace('/[^0-9]/', '', $value);

        // Konversi ke integer jika ada nilai
        if ($cleanValue !== '') {
            $this->moneyPaid = (int)$cleanValue;
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

        $this->cart = $transaction->items->mapWithKeys(function ($item) {
            return [
                $item->menu_id => [
                    'name' => $item->menu->name,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                ],
            ];
        })->toArray();

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
            if (isset($this->selectedMenus[$menuId])) {
                unset($this->selectedMenus[$menuId]);

                if (isset($this->cart[$menuId])) {
                    unset($this->cart[$menuId]);
                }
            } else {
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
        if (count($this->cart) == 0) {
            Notification::make()->title('Keranjang belanja tidak boleh kosong.')->danger()->send();
            return;
        }

        if ($this->paymentMethod == 'cash') {
            if (empty($this->moneyPaid) || $this->moneyPaid <= 0) {
                Notification::make()->title('Uang yang dibayarkan tidak boleh kosong.')->danger()->send();
                return;
            }

            if ($this->moneyPaid < $this->total()) {
                Notification::make()->title('Uang yang dibayarkan tidak cukup.')->danger()->send();
                return;
            }
        }

        if ($this->paymentMethod == 'cashless') {
            $this->moneyPaid = $this->total();
        }

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

            Notification::make()->title('Transaksi Berhasil!')->success()->send();
            $this->resetCart();
            $this->transactionCode = $this->generateTransactionCode();

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
        $this->selectedMenus = [];
    }

    public function orderTransaction()
    {
        if (count($this->cart) == 0) {
            Notification::make()->title('Keranjang belanja tidak boleh kosong.')->danger()->send();
            return;
        }

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

            Notification::make()->title('Transaksi Berhasil!')->success()->send();
            $this->resetCart();
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
        if (count($this->cart) == 0) {
            Notification::make()->title('Keranjang belanja tidak boleh kosong.')->danger()->send();
            return;
        }

        $transaction = Transaction::findOrFail($this->transactionId);

        try {
            DB::transaction(function () use ($transaction) {
                $transaction->update([
                    'total_amount' => $this->total(),
                    'customer_name' => $this->customerName ?? '',
                    'payment_method' => $this->paymentMethod,
                    'money_paid' => $this->moneyPaid,
                    'code_transaction' => $this->transactionCode,
                    'user_id' => Auth::id(),
                ]);

                $transaction->items()->delete();

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
        if ($this->moneyPaid < $this->total()) {
            Notification::make()->title('Uang pembayaran tidak mencukupi.')->danger()->send();
            return;
        }

        $this->editTransaction();
        return redirect()->to('transactions/' . $this->transactionId . '/print');
    }
}
