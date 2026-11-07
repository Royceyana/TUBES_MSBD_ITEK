<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\Menu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MenuController extends Controller
{
    public function index(): View
    {
        $categories = DB::table('categories')->orderBy('id')->get();

        $menus = DB::table('menus as m')
            ->leftJoin('categories as c', 'c.id', '=', 'm.category_id')
            ->leftJoin('v_menu_hpp as h', 'h.menu_id', '=', 'm.id')
            ->orderBy('c.id')->orderBy('m.name')
            ->get(['m.*', 'c.name as category', 'h.jumlah_bahan as recipe_count', 'h.hpp', 'h.margin_persen']);

        return view('admin.menus', compact('categories', 'menus'));
    }

    private function rules(?Menu $menu = null): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('menus', 'name')->ignore($menu?->id)],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'price' => ['required', 'integer', 'min:1', 'max:99999999'],
            'status' => ['required', 'in:active,inactive'],
            'image' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());
        $data['image'] = $request->file('image')?->store('menus', 'public');
        Menu::create($data);

        return back()->with('success', 'Menu ditambahkan. Atur resepnya supaya stok bahan terpotong.');
    }

    public function update(Request $request, Menu $menu): RedirectResponse
    {
        $data = $request->validate($this->rules($menu));
        if ($request->hasFile('image')) {
            if ($menu->image) {
                Storage::disk('public')->delete($menu->image);
            }
            $data['image'] = $request->file('image')->store('menus', 'public');
        } else {
            unset($data['image']);
        }
        $menu->update($data);

        return back()->with('success', 'Menu diperbarui.');
    }

    public function destroy(Menu $menu): RedirectResponse
    {
        // transaction_details.menu_id = ON DELETE RESTRICT: menu yang pernah terjual tidak boleh dihapus.
        if (DB::table('transaction_details')->where('menu_id', $menu->id)->exists()) {
            throw ValidationException::withMessages([
                'menu' => "Menu \"{$menu->name}\" sudah pernah terjual, jadi tidak bisa dihapus. Ubah statusnya menjadi nonaktif.",
            ]);
        }
        if ($menu->image) {
            Storage::disk('public')->delete($menu->image);
        }
        $menu->delete();

        return back()->with('success', 'Menu dihapus.');
    }

    public function recipe(Menu $menu): View
    {
        $menu->category_name = DB::table('categories')->where('id', $menu->category_id)->value('name');
        $ingredients = Ingredient::where('is_active', true)->orderBy('name')->get();
        $recipe = DB::table('menu_ingredients')->where('menu_id', $menu->id)->get();
        $hpp = DB::table('v_menu_hpp')->where('menu_id', $menu->id)->first();

        return view('admin.recipe', compact('menu', 'ingredients', 'recipe', 'hpp'));
    }

    public function saveRecipe(Request $request, Menu $menu): RedirectResponse
    {
        $data = $request->validate([
            'ingredient_id' => ['nullable', 'array'],
            'ingredient_id.*' => ['integer', 'distinct', 'exists:ingredients,id'],
            'qty_needed' => ['nullable', 'array'],
            'qty_needed.*' => ['numeric', 'gt:0', 'max:99999999'],
        ]);

        $ids = $data['ingredient_id'] ?? [];
        $qtys = $data['qty_needed'] ?? [];

        DB::transaction(function () use ($menu, $ids, $qtys) {
            DB::table('menu_ingredients')->where('menu_id', $menu->id)->delete();
            foreach ($ids as $i => $id) {
                DB::table('menu_ingredients')->insert([
                    'menu_id' => $menu->id,
                    'ingredient_id' => $id,
                    'qty_needed' => $qtys[$i],
                ]);
            }
        });

        return redirect()->route('admin.menus')->with('success', 'Resep "'.$menu->name.'" tersimpan.');
    }
}
