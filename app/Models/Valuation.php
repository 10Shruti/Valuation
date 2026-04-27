<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Valuation extends Model
{
    use HasFactory;

    // These are the fields we can save
    protected $fillable = ['name', 'valuation_date', 'address'];

    // One Valuation has Many Items (Images/Grams)
    public function items()
    {
        return $this->hasMany(ValuationItem::class);
    }
}