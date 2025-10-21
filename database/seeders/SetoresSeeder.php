<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SetoresSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $now = Carbon::now();
        // Limpar setores e relações antigas (seeder idempotente)
        DB::table('setor_fornecedor')->delete();
        DB::table('setores')->delete();

        // Buscar ID do polo HGVC (Todos os setores serão deste polo)
        $poloHGVC = DB::table('polo')->where('nome', 'Hospital Geral')->first()->id;

        // Inserir somente os 6 setores da imagem (todos no HGVC)
        $toInsert = [
            ['polo_id' => $poloHGVC, 'nome' => 'Farmácia Central', 'descricao' => 'Farmácia Central que atende as clínicas', 'tipo' => 'Medicamento', 'estoque' => true, 'status' => 'A'],
            ['polo_id' => $poloHGVC, 'nome' => 'Farmácia de Dispensação', 'descricao' => 'Central de Abastecimento Farmacêutico', 'tipo' => 'Medicamento', 'estoque' => true, 'status' => 'A'],
            ['polo_id' => $poloHGVC, 'nome' => 'Satélite da Emergência', 'descricao' => 'Farmácia Satélite do Setor de Emergência', 'tipo' => 'Medicamento', 'estoque' => true, 'status' => 'A'],
            ['polo_id' => $poloHGVC, 'nome' => 'Centro Cirúrgico', 'descricao' => 'Centro Cirúrgico', 'tipo' => 'Medicamento', 'estoque' => false, 'status' => 'A'],
            ['polo_id' => $poloHGVC, 'nome' => 'Clínica Médica', 'descricao' => 'Clínica Médica', 'tipo' => 'Medicamento', 'estoque' => false, 'status' => 'A'],
            ['polo_id' => $poloHGVC, 'nome' => 'Emergência', 'descricao' => 'Setor de Emergência', 'tipo' => 'Medicamento', 'estoque' => false, 'status' => 'A'],
        ];

        foreach ($toInsert as $row) {
            DB::table('setores')->insert([
                'polo_id' => $row['polo_id'],
                'nome' => mb_strtoupper($row['nome']),
                'descricao' => $row['descricao'],
                'tipo' => $row['tipo'],
                'estoque' => $row['estoque'],
                'status' => $row['status'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Recuperar IDs para criar relações de fornecedor
        $farmaciaCentral = DB::table('setores')->where('nome', mb_strtoupper('Farmácia Central'))->first();
        $farmDisp = DB::table('setores')->where('nome', mb_strtoupper('Farmácia de Dispensação'))->first();
        $satEmerg = DB::table('setores')->where('nome', mb_strtoupper('Satélite da Emergência'))->first();
        $centroCirc = DB::table('setores')->where('nome', mb_strtoupper('Centro Cirúrgico'))->first();
        $clinicaMed = DB::table('setores')->where('nome', mb_strtoupper('Clínica Médica'))->first();
        $emergencia = DB::table('setores')->where('nome', mb_strtoupper('Emergência'))->first();

        // Criar relações: todos apontam para Farmácia Central como fornecedor (tipo Medicamento)
        $relations = [];
        if ($farmDisp) $relations[] = ['setor_solicitante_id' => $farmDisp->id, 'setor_fornecedor_id' => $farmaciaCentral->id, 'tipo_produto' => 'Medicamento'];
        if ($satEmerg) $relations[] = ['setor_solicitante_id' => $satEmerg->id, 'setor_fornecedor_id' => $farmaciaCentral->id, 'tipo_produto' => 'Medicamento'];
        if ($centroCirc) $relations[] = ['setor_solicitante_id' => $centroCirc->id, 'setor_fornecedor_id' => $farmaciaCentral->id, 'tipo_produto' => 'Medicamento'];
        if ($clinicaMed) $relations[] = ['setor_solicitante_id' => $clinicaMed->id, 'setor_fornecedor_id' => $farmaciaCentral->id, 'tipo_produto' => 'Medicamento'];
        if ($emergencia) $relations[] = ['setor_solicitante_id' => $emergencia->id, 'setor_fornecedor_id' => $farmaciaCentral->id, 'tipo_produto' => 'Medicamento'];

        foreach ($relations as $r) {
            DB::table('setor_fornecedor')->insert([
                'setor_solicitante_id' => $r['setor_solicitante_id'],
                'setor_fornecedor_id' => $r['setor_fornecedor_id'],
                'tipo_produto' => $r['tipo_produto'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
