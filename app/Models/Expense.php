<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    public const CATEGORIES = ['Rent', 'Salaries', 'Utilities', 'Delivery', 'Marketing', 'Supplies', 'Maintenance', 'Other'];

    protected $fillable = ['title', 'category', 'amount', 'spent_on', 'notes', 'user_id'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'spent_on' => 'date'];
    }
}
