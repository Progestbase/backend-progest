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
        $now = Carbon::now();

        // Buscar IDs dos polos
        $poloHGVC = DB::table('polo')->where('nome', 'Hospital Geral')->first()->id;
        $poloHAP = DB::table('polo')->where('nome', 'Hospital Afrânio Peixoto')->first()->id;
        $poloCresencio = DB::table('polo')->where('nome', 'Crescêncio Silveira')->first()->id;
        $poloUPA = DB::table('polo')->where('nome', 'UPA')->first()->id;

        $unidades = [
            // ========================================
            // UNIDADES COM ESTOQUE (Farmácias)
            // ========================================

            // HGVC - Farmácia de Dispensação e Satélite da Emergência
            [
                'polo_id' => $poloHGVC,
                'nome' => 'Farmácia de Dispensação',
                'descricao' => 'Central de Abastecimento Farmacêutico',
                'tipo' => 'Medicamento',
                'estoque' => true,
                'status' => 'A',
            ],
            [
                'polo_id' => $poloHGVC,
                'nome' => 'Satélite da Emergência',
                'descricao' => 'Farmácia Satélite do Setor de Emergência',
                'tipo' => 'Medicamento',
                'estoque' => true,
                'status' => 'A',
            ],

            // HAP - Farmácia Central (que atende as clínicas) e Satélite da UTI
            [
                'polo_id' => $poloHAP,
                'nome' => 'Farmácia Central',
                'descricao' => 'Farmácia Central que atende as clínicas',
                'tipo' => 'Medicamento',
                'estoque' => true,
                'status' => 'A',
            ],
            [
                'polo_id' => $poloHAP,
                'nome' => 'Satélite da UTI',
                'descricao' => 'Farmácia Satélite da UTI',
                'tipo' => 'Medicamento',
                'estoque' => true,
                'status' => 'A',
            ],

            // Crescêncio Silveira
            [
                'polo_id' => $poloCresencio,
                'nome' => 'Farmácia',
                'descricao' => 'Farmácia do Crescêncio Silveira',
                'tipo' => 'Medicamento',
                'estoque' => true,
                'status' => 'A',
            ],

            // ========================================
            // UNIDADES SEM ESTOQUE - HGVC
            // ========================================
            [
                'polo_id' => $poloHGVC,
                'nome' => 'Centro Cirúrgico',
                'descricao' => 'Centro Cirúrgico',
                'tipo' => 'Medicamento',
                'estoque' => false,
                'status' => 'A',
            ],
            [
                'polo_id' => $poloHGVC,
                'nome' => 'TI - Tecnologia da Informação',
                'descricao' => 'Tecnologia da Informação - Almoxarifado, TI...',
                'tipo' => 'Material',
                'estoque' => false,
                'status' => 'A',
            ],
            [
                'polo_id' => $poloHGVC,
                'nome' => 'Almoxarifado',
                'descricao' => 'Almoxarifado Geral',
                'tipo' => 'Material',
                'estoque' => false,
                'status' => 'A',
            ],
            [
                'polo_id' => $poloHGVC,
                'nome' => 'Clínica Médica',
                'descricao' => 'Clínica Médica',
                'tipo' => 'Medicamento',
                'estoque' => false,
                'status' => 'A',
            ],
            [
                'polo_id' => $poloHGVC,
                'nome' => 'UTI',
                'descricao' => 'Unidade de Terapia Intensiva',
                'tipo' => 'Medicamento',
                'estoque' => false,
                'status' => 'A',
            ],
            [
                'polo_id' => $poloHGVC,
                'nome' => 'Emergência',
                'descricao' => 'Setor de Emergência',
                'tipo' => 'Medicamento',
                'estoque' => false,
                'status' => 'A',
            ],

            // ========================================
            // UNIDADE DE TESTE - HAP TI
            // ========================================
            [
                'polo_id' => $poloHAP,
                'nome' => 'TI - Tecnologia da Informação',
                'descricao' => 'TI do Hospital Afrânio Peixoto',
                'tipo' => 'Material',
                'estoque' => false,
                'status' => 'A',
            ],
        ];

        foreach ($unidades as $unidade) {
            DB::table('unidades')->insert([
                'polo_id' => $unidade['polo_id'],
                'nome' => mb_strtoupper($unidade['nome']),
                'descricao' => $unidade['descricao'],
                'tipo' => $unidade['tipo'],
                'estoque' => $unidade['estoque'],
                'status' => $unidade['status'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
