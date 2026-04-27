<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ValuationItem extends Model
{
    use HasFactory;

    protected $fillable = ['valuation_id', 'grams', 'image_path'];

    // Link back to the main Valuation
    public function valuation()
    {
        return $this->belongsTo(Valuation::class);
    }
}