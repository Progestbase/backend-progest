<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PolosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $polos = [
            ['nome' => 'Hospital Geral', 'status' => 'A'],
            ['nome' => 'Hospital Afrânio Peixoto', 'status' => 'A'],
            ['nome' => 'Crescêncio Silveira', 'status' => 'A'],
            ['nome' => 'UPA', 'status' => 'A'],
        ];

        $now = Carbon::now();

        foreach ($polos as $polo) {
            DB::table('polo')->insert([
                'nome' => $polo['nome'],
                'status' => $polo['status'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
