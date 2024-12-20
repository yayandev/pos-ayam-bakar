<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    use HasFactory;
    protected $fillable = [
        'transaction_date',
        'total_amount',
        'customer_name',
        'payment_method',
        'money_paid',
        'code_transaction',
        'user_id'
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'total_amount' => 'decimal:2',
        'customer_name' => 'string'
    ];

    public function items()
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function itemsCount(): int
    {
        return $this->items()->count();
    }
}
