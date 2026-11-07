<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::withCount('menus')->orderBy('id')->get();

        return view('admin.categories', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:50', 'unique:categories,name']]);
        Category::create($data);

        return back()->with('success', 'Kategori ditambahkan.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:50', Rule::unique('categories', 'name')->ignore($category->id)]]);
        $category->update($data);

        return back()->with('success', 'Kategori diperbarui.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        // FK menus.category_id = ON DELETE SET NULL: menu tetap ada, hanya kehilangan kategori.
        $count = $category->menus()->count();
        $category->delete();

        return back()->with('success', $count ? "Kategori dihapus. {$count} menu kini tanpa kategori." : 'Kategori dihapus.');
    }
}
