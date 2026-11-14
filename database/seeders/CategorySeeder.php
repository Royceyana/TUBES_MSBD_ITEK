<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SyncsSequence;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    use SyncsSequence;

    public function run(): void
    {
        $rows = [
            [1, 'Nasi'],
            [2, 'Mie'],
            [3, 'Snack'],
            [4, 'Teh'],
            [5, 'Kopi'],
            [6, 'Jus'],
            [7, 'Mix'],
        ];

        foreach ($rows as [$id, $name]) {
            DB::table('categories')->insertOrIgnore(['id' => $id, 'name' => $name]);
        }

        $this->syncSequence('categories');
    }
}
