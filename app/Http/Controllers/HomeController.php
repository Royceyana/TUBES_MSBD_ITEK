<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HomeController extends Controller
{
    /** Landing publik: menu asli dari database, dikelompokkan per kategori. */
    public function index(): View
    {
        $categories = DB::table('categories')->orderBy('id')->get();
        $menus = DB::table('menus')->where('status', 'active')->orderBy('name')->get(['id', 'category_id', 'name', 'price']);

        return view('welcome', compact('categories', 'menus'));
    }
}
