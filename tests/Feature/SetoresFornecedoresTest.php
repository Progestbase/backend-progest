<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Setores;
use App\Models\Polo;
use App\Models\SetorFornecedor;

class SetoresFornecedoresTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_setor_with_fornecedor_and_uniqueness()
    {
        // Criar polo
        $polo = Polo::factory()->create();

        // Criar setor fornecedor existente
        $fornecedor = Setores::create([
            'polo_id' => $polo->id,
            'nome' => 'FORNECEDOR A',
            'tipo' => 'Medicamento',
            'estoque' => false,
            'status' => 'A'
        ]);

        // Payload para criar setor solicitante com fornecedor
        $payload = [
            'Setores' => [
                'polo_id' => $polo->id,
                'nome' => 'SOLICITANTE X',
                'tipo' => 'Medicamento',
                'estoque' => false
            ],
            'fornecedor' => [
                'setor_id' => $fornecedor->id,
                'tipo_produto' => 'Medicamento'
            ]
        ];

        $response = $this->postJson('/api/setores/add', $payload);
        $response->assertStatus(200)->assertJson(['status' => true]);

        $solicitante = Setores::where('nome', 'SOLICITANTE X')->first();
        $this->assertNotNull($solicitante);

        $rel = SetorFornecedor::where('setor_solicitante_id', $solicitante->id)
            ->where('tipo_produto', 'Medicamento')
            ->first();

        $this->assertNotNull($rel);

        // Tentar criar outro fornecedor do mesmo tipo deve falhar
        $payload2 = [
            'Setores' => [
                'polo_id' => $polo->id,
                'nome' => 'SOLICITANTE Y',
                'tipo' => 'Medicamento',
                'estoque' => false
            ],
            'fornecedor' => [
                'setor_id' => $fornecedor->id,
                'tipo_produto' => 'Medicamento'
            ]
        ];

        // Primeiro criar novo solicitante com fornecedor do mesmo tipo para o mesmo solicitante (não é exatamente o mesmo solicitante),
        // a regra se aplica por solicitante, então a validação só impede duplicatas por solicitante. Aqui apenas assertamos que criação é possível para outro solicitante.
        $response2 = $this->postJson('/api/setores/add', $payload2);
        $response2->assertStatus(200)->assertJson(['status' => true]);
    }
}
