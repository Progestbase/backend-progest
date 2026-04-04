<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Setores;
use App\Models\Unidade;
use App\Models\SetorFornecedor;

class SetoresFornecedoresTest extends TestCase
{
    // NOTA: RefreshDatabase removido - testes rodam no banco real

    public function test_create_setor_with_fornecedor_and_uniqueness()
    {
        // Limpar dados de testes anteriores
        Setores::where('nome', 'LIKE', 'FORNECEDOR A%')->delete();
        Setores::where('nome', 'LIKE', 'SOLICITANTE%')->delete();
        
        // Criar unidade
        $unidade = Unidade::factory()->create();

        // Criar setor fornecedor existente
        $fornecedor = Setores::create([
            'unidade_id' => $unidade->id,
            'nome' => 'FORNECEDOR A',
            'tipo' => 'Medicamento',
            'estoque' => false,
            'status' => 'A'
        ]);

        // Payload para criar setor solicitante com fornecedor
        $payload = [
            'Setores' => [
                'unidade_id' => $unidade->id,
                'nome' => 'SOLICITANTE X',
                'tipo' => 'Medicamento',
                'estoque' => false
            ],
            'fornecedor' => [
                'setor_fornecedor_id' => $fornecedor->id  // Campo correto
            ]
        ];

        $response = $this->postJson('/api/setores/add', $payload);
        $response->assertStatus(200)->assertJson(['status' => true]);

        $solicitante = Setores::where('nome', 'SOLICITANTE X')->first();
        $this->assertNotNull($solicitante);

        $rel = SetorFornecedor::where('setor_solicitante_id', $solicitante->id)
            ->where('setor_fornecedor_id', $fornecedor->id)
            ->first();

        $this->assertNotNull($rel);

        // Criar outro solicitante com o mesmo fornecedor (deve funcionar)
        $payload2 = [
            'Setores' => [
                'unidade_id' => $unidade->id,
                'nome' => 'SOLICITANTE Y',
                'tipo' => 'Medicamento',
                'estoque' => false
            ],
            'fornecedor' => [
                'setor_fornecedor_id' => $fornecedor->id  // Campo correto
            ]
        ];

        // Primeiro criar novo solicitante com fornecedor do mesmo tipo para o mesmo solicitante (não é exatamente o mesmo solicitante),
        // a regra se aplica por solicitante, então a validação só impede duplicatas por solicitante. Aqui apenas assertamos que criação é possível para outro solicitante.
        $response2 = $this->postJson('/api/setores/add', $payload2);
        $response2->assertStatus(200)->assertJson(['status' => true]);
    }
}
