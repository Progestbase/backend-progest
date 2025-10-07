<?php

namespace Database\Seeders;

use App\Models\UnidadeMedida;
use Illuminate\Database\Seeder;

class UnidadeMedidaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $unidades = [
            'Comprimido',
            'Cartela',
            'Grama',
            'Rolo',
            'Pacote',
            'Ampulheta',
            'Ml',
        ];

        foreach ($unidades as $nome) {
            UnidadeMedida::updateOrCreate(
                ['nome' => $nome],
                [
                    'quantidade_unidade_minima' => 1,
                    'status' => 'A',
                ]
            );
        }
    }
}
