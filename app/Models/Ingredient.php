<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'unit', 'stock_qty', 'min_stock', 'cost_per_unit', 'is_active'])]
class Ingredient extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
