<?php

namespace Database\Seeders;

use App\Models\GrupoProduto;
use Illuminate\Database\Seeder;

class GrupoProdutoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $grupos = [
            'Analgésico' => 'Medicamento',
            'Material de uso coletivo' => 'Medicamento',
            'Antibióticos' => 'Medicamento',
            'Vacina' => 'Medicamento',
            'Antidepressivo' => 'Medicamento',
            'Material de escritório' => 'Material',
            'Material de limpeza' => 'Material',
            'Outros materiais' => 'Material',
        ];

        foreach ($grupos as $nome => $tipo) {
            GrupoProduto::updateOrCreate(
                ['nome' => $nome],
                [
                    'tipo' => $tipo,
                    'status' => 'A',
                ]
            );
        }
    }
}
