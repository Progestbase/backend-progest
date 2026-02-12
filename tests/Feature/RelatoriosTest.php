<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Entrada;
use App\Models\ItensEntrada;
use App\Models\Movimentacao;
use App\Models\ItemMovimentacao;
use App\Models\Setores;
use App\Models\Fornecedor;
use App\Models\Produto;
use App\Models\GrupoProduto;
use App\Models\UnidadeMedida;
use App\Models\Unidade;
use App\Models\User;

class RelatoriosTest extends TestCase
{
    use RefreshDatabase;

    public function test_endpoint_entradas_existe_e_retorna_json()
    {
        $response = $this->postJson('/api/relatorios/entradas/list', [
            'per_page' => 10
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'data',
                    'total',
                    'per_page',
                    'current_page'
                ]
            ]);
    }

    public function test_endpoint_movimentacoes_existe_e_retorna_json()
    {
        $response = $this->postJson('/api/relatorios/movimentacoes/list', [
            'per_page' => 10
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'data',
                    'total',
                    'per_page',
                    'current_page'
                ]
            ]);
    }

    public function test_validacao_data_final_anterior_a_inicial()
    {
        $response = $this->postJson('/api/relatorios/entradas/list', [
            'filters' => [
                'date_from' => '2025-12-31',
                'date_to' => '2025-01-01'
            ]
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'validacao' => true
            ]);
    }

    public function test_validacao_setor_inexistente()
    {
        $response = $this->postJson('/api/relatorios/entradas/list', [
            'filters' => [
                'setor_id' => 99999
            ]
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'validacao' => true
            ]);
    }

    public function test_validacao_tipo_movimentacao_invalido()
    {
        $response = $this->postJson('/api/relatorios/movimentacoes/list', [
            'filters' => [
                'tipo' => 'tipo_invalido'
            ]
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'validacao' => true
            ]);
    }

    public function test_per_page_acima_do_limite()
    {
        $response = $this->postJson('/api/relatorios/entradas/list', [
            'per_page' => 150
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'validacao' => true
            ]);
    }

    public function test_filtro_data_aceita_formato_correto()
    {
        $response = $this->postJson('/api/relatorios/entradas/list', [
            'filters' => [
                'date_from' => '2025-01-01',
                'date_to' => '2025-12-31'
            ],
            'per_page' => 10
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => true]);
    }

    public function test_resposta_contem_estrutura_de_paginacao()
    {
        $response = $this->postJson('/api/relatorios/entradas/list', [
            'per_page' => 25,
            'page' => 1
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');
        
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('total', $data);
        $this->assertArrayHasKey('per_page', $data);
        $this->assertArrayHasKey('current_page', $data);
        $this->assertArrayHasKey('last_page', $data);
    }

    public function test_filtros_opcionais_funcionam()
    {
        $response = $this->postJson('/api/relatorios/movimentacoes/list', [
            'filters' => [
                'tipo' => 'transferencia'
            ],
            'per_page' => 10
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => true]);
    }

    public function test_mensagem_de_sucesso_esta_presente()
    {
        $response = $this->postJson('/api/relatorios/entradas/list');

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Relatório de entradas recuperado com sucesso');

        $response2 = $this->postJson('/api/relatorios/movimentacoes/list');

        $response2->assertStatus(200)
            ->assertJsonPath('message', 'Relatório de movimentações recuperado com sucesso');
    }
}
