<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/** Resep per 1 porsi: menu_ingredients(menu_id, ingredient_id, qty_needed). */
#[Fillable(['menu_id', 'ingredient_id', 'qty_needed'])]
class MenuIngredient extends Model
{
    public $timestamps = false;
}
