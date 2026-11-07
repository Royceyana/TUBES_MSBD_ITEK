<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class IngredientController extends Controller
{
    public function index(): View
    {
        $ingredients = Ingredient::where('is_active', true)->orderBy('name')->get();

        return view('admin.ingredients', compact('ingredients'));
    }

    private function rules(?Ingredient $ing = null): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('ingredients', 'name')->ignore($ing?->id)],
            'unit' => ['required', 'string', 'max:20'],
            'min_stock' => ['required', 'numeric', 'min:0'],
            'cost_per_unit' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        Ingredient::create($request->validate($this->rules()) + ['stock_qty' => 0]);

        return back()->with('success', 'Bahan ditambahkan. Isi stok awalnya lewat Stok masuk.');
    }

    public function update(Request $request, Ingredient $ingredient): RedirectResponse
    {
        $ingredient->update($request->validate($this->rules($ingredient)));

        return back()->with('success', 'Bahan diperbarui.');
    }

    /** Bahan diarsipkan (is_active=false); menghapus akan ikut menghapus resep & riwayat stok (ON DELETE CASCADE). */
    public function destroy(Ingredient $ingredient): RedirectResponse
    {
        $ingredient->update(['is_active' => false]);

        return back()->with('success', "Bahan \"{$ingredient->name}\" diarsipkan.");
    }
}
