<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UnidadesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Garantir que a tabela esteja limpa antes de repovoar (evita duplicatas e registros indesejados)
        DB::table('unidades')->truncate();

        $unidades = [
            ['nome' => 'Hospital Geral', 'status' => 'A'],
            ['nome' => 'Hospital Afrânio Peixoto', 'status' => 'A'],
            ['nome' => 'Crescêncio Silveira', 'status' => 'A'],
            ['nome' => 'UPA', 'status' => 'A'],
        ];

        $now = Carbon::now();

        foreach ($unidades as $unidade) {
            DB::table('unidades')->insert([
                'nome' => $unidade['nome'],
                'status' => $unidade['status'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
