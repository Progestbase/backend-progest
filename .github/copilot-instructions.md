# ProGest Backend - AI Agent Instructions# ProGest Backend - AI Agent Instructions

> Sistema de gestão hospitalar Laravel para controle de estoques de medicamentos e materiais com auto-provisionamento via Observers e validação de tipos por regras de negócio.> Sistema de gestão hospitalar Laravel para controle de estoques de medicamentos e materiais com auto-provisionamento via Observers e validação de tipos por regras de negócio.

## Arquitetura Essencial## Arquitetura Essencial

**ProGest** gerencia estoques hospitalares com dois fluxos críticos:**ProGest** gerencia estoques hospitalares com dois fluxos críticos:

1. **Auto-provisionamento**: Quando produto/setor é criado, observers criam registros de `estoque` automaticamente se tipos compatíveis

1. **Auto-provisionamento**: Quando produto/setor é criado, observers criam registros de `estoque` automaticamente se tipos compatíveis2. **Controle por lote**: Entradas atualizam `estoque` (agregado) + `estoque_lote` (granular com vencimento)

1. **Controle por lote**: Entradas atualizam `estoque` (agregado) + `estoque_lote` (granular com vencimento)

**Entidades Core** (ver `database/migrations/`):

**Fluxo de entrada** (exemplo real em `EntradaController::add()`):- `setores` (unidades físicas): tem `tipo` (Medicamento|Material), `estoque` (bool), relaciona com `unidades` (polos)

```php- `produtos`: tem `grupo_produto_id`que define`tipo`(deve casar com`setor.tipo`)

// 1. Validar nota fiscal + itens (produto, quantidade, lote, vencimento)- `estoque`: agregação por produto+setor com `quantidade_atual`, `status_disponibilidade` ('D'/'I')

// 2. Verificar tipo matching: produto.grupoProduto.tipo === setor.tipo- `estoque_lote`: controle granular com unique(`unidade_id`, `produto_id`, `lote`)

// 3. Transaction: criar entrada + itens_entrada

// 4. Para cada item: firstOrCreate em estoque, incrementar quantidade_atual## Modelo de Dados (resumo das migrations)

// 5. Atualizar/criar estoque_lote (upsert por unique key)

```-   `setores`(unidades): id,`polo_id`, `nome`, `descricao`, `status`('A'/'I'),`estoque`(boolean),`tipo`('Medicamento'|'Material'). Usada como`unidade` nas relações de estoque.

-   `grupo_produto`: id, `nome`, `status` ('A'/'I'), `tipo` ('Medicamento'|'Material') — define o tipo do produto.

**Entidades Core** (ver `database/migrations/`):- `unidade_medida`: id, `nome`, `quantidade_unidade_minima`, `status` ('A'/'I').

-   `setores` (unidades físicas): `tipo` (Medicamento|Material), `estoque` (bool), FK para `unidades` (polos)- `produtos`: id, `nome`, `marca`, `codigo_simpras`, `codigo_barras`, `grupo_produto_id`, `unidade_medida_id`, `status` ('A'/'I').

-   `produtos`: `grupo_produto_id` define `tipo` que DEVE casar com `setor.tipo`- `estoque`: id, `produto_id`, `unidade_id` (setores), `quantidade_atual`, `quantidade_minima`, `localizacao`, `status_disponibilidade` ('D' Disponível, 'I' Indisponível').

-   `estoque`: agregação por produto+setor com `quantidade_atual`, `status_disponibilidade` ('D'/'I')- `estoque_lote`: id, `unidade_id`, `produto_id`, `lote`, `quantidade_disponivel` (decimal), `data_vencimento`, `data_fabricacao` (nullable). Unique composta (unidade, produto, lote).

-   `estoque_lote`: controle granular com unique(`unidade_id`, `produto_id`, `lote`)- `entrada` / `itens_entrada`: notas fiscais + itens com lote, datas de fabricação/vencimento — usados para criar/atualizar `estoque_lote` e `estoque`.

-   `movimentacao` / `item_movimentacao`: registros de movimentações (transferência, devolução, saída) entre unidades com itens e quantidades (solicitada/liberada).

## Regras de Negócio Críticas

Relações importantes:

1. **Tipo Matching Obrigatório**: `grupo_produto.tipo` DEVE casar com `setor.tipo` em operações de estoque

    ❌ Erro: `throw new \RuntimeException('Produto "X" não é compatível com o tipo do setor')`- `produto.grupo_produto` (grupo define o tipo que deve casar com `setor.tipo`).

-   `estoque.produto` e `estoque.unidade` (setor).

2. **Status ao invés de Soft Deletes**: use `status = 'A'|'I'` — NÃO há `deleted_at` - `estoque_lote` refere-se a `produto` e `unidade` (controle por lote).

    Verificar: `where('status', 'A')` em queries

## Regras de Negócio Críticas (inferidas das migrations e codebase)

3. **Disponibilidade de Estoque**: `status_disponibilidade` usa 'D' (Disponível) ou 'I' (Indisponível)

    Auto-atualizado: se `quantidade_atual > 0` → 'D', senão 'I'1. Tipo matching: produtos de `grupo_produto.tipo = 'Medicamento'` só são compatíveis com `setores.tipo = 'Medicamento'` quando `setor.estoque = true` — Observers automatizam provisionamento.

4. Não usar soft deletes do Eloquent; a aplicação marca `status = 'I'` para inativar registros.

5. **Constraint Única em Lotes**: `estoque_lote(unidade_id, produto_id, lote)` — usar upsert ou `firstOrCreate` para evitar duplicatas3. `status_disponibilidade` em `estoque` usa 'D' (Disponível) e 'I' (Indisponível).

6. `estoque_lote` tem chave única (unidade, produto, lote) — cuidado ao inserir itens de entrada duplicados.

7. **Auto-provisionamento via Observers** (registrado em `AppServiceProvider::boot()`):

    - `ProdutoObserver::created()` → chama `Estoque::criarEstoqueParaNovoProduto()`## Convenções de API e Código

    - `SetoresObserver::created()` → chama `Estoque::criarEstoqueInicialParaSetor()`

    **Importante**: só cria se `setor.estoque = true` E tipos compatíveis- Rotas: o projeto historicamente usa POST para operações CRUD e listagens via `routes/api.php`.

-   Responses: padrão JSON com chave `status` booleana, `data` para payload (quando sucesso) e `message` em strings; erros retornam `status: false` e código HTTP apropriado.

## Convenções de Código (Padrões Observados)- Eager loading: controllers costumam usar `with()` para trazer relacionamentos (ex.: Produto::with(['grupoProduto','unidadeMedida'])->...)

-   Scopes: modelos oferecem scopes (`ativo()`, `porGrupo()`, `disponivel()`, `porUnidade()`) — prefira usá-los.

### API Response Pattern (SEMPRE seguir)

````php## Observers e Auto-provisionamento

// Sucesso

return ['status' => true, 'data' => $payload];-   Observers existentes (ex.: `ProdutoObserver`, `SetoresObserver`) são registrados em `AppServiceProvider::boot()` para criar registros de `estoque` automaticamente:

return ['status' => true, 'data' => $payload, 'message' => 'Operação concluída'];    -   Ao criar um `produto`: cria `estoque` para todas as `setores` com `estoque=true` e `tipo` compatível.

    -   Ao criar/ativar um `setor` com `estoque=true`: cria `estoque` para todos os produtos compatíveis.

// Erro de validação

return response()->json([Funções úteis esperadas em Models/Service:

    'status' => false,

    'validacao' => true, -   `Estoque::criarEstoqueParaNovoProduto(Produto $produto)`

    'erros' => $validator->errors()-   `Estoque::criarEstoqueInicialParaUnidade(Setor $unidade)`

], 422);

## Pontos de Atenção (operacionais)

// Erro de negócio

return response()->json(['status' => false, 'message' => 'Setor sem controle de estoque'], 400);-   Foreign keys usam `->constrained()` com `onDelete('restrict')` — migrações e seeders devem garantir integridade.

```-   Rotas majoritariamente sem middleware `auth:sanctum` (exceto `/user`). Confirmar se novas rotas precisam de autenticação.

-   Paginação: `listAll()` espera `per_page` no request.

### Estrutura de Controllers-   Filtros dinâmicos: controllers aceitam `filters` array com operadores básicos.

Métodos padrão esperados (ver `GrupoProdutoController`, `EntradaController`):-   Log: use `Log::error()` em blocos catch antes de retornar resposta de erro.

- `add(Request $request)` — validação + criação + log de erros

- `update(Request $request)` — validação + atualização## Checklist rápido antes de implementar tarefas

- `listAll(Request $request)` — aceita `filters` (array), `paginate` (bool)

- `delete($id ou Request)` — soft inativa (`status = 'I'`) ou hard delete conforme FK constraints1. Qual entidade(s) será afetada? (produto, estoque, lote, movimentação, entrada)

- `toggleStatus(Request $request)` — alterna 'A'/'I'2. O endpoint deve respeitar tipo matching (grupo_produto.tipo x setor.tipo)?

3. Precisa criar/atualizar `estoque` e/ou `estoque_lote` automaticamente ao processar entrada/movimentação?

### Validação Pattern4. Requisitos de autorização/usuário para a ação (middleware)?

```php

$validator = Validator::make($data['nomeEntidade'], [## Próximos passos sugeridos

    'campo' => 'required|string|max:255',

    'tipo' => 'required|string|in:Medicamento,Material',-   Me diga qual funcionalidade/endpoints você quer implementar hoje (ex.: endpoint para registrar entrada com atualização de estoque e lotes; endpoint para transferências entre unidades; listagem paginada de produtos por unidade). Eu já li as migrations e entendo onde cada campo está.

], [-   Posso começar criando controllers, requests de validação, serviços e testes mínimos para validar o fluxo.

    'campo.required' => 'Mensagem customizada em português',

]);## Perguntas rápidas para alinhar



if ($validator->fails()) {1. Deseja que eu mantenha o padrão de usar POST para listagens e CRUD? (recomendado: sim, para compatibilidade)

    return response()->json([2. Nova rota precisa de autenticação (`auth:sanctum`) ou continuará pública?

        'status' => false, 3. Quer que eu atualize/adicione tests PHPUnit para a funcionalidade?

        'validacao' => true,

        'erros' => $validator->errors()---

    ], 422);

}Se quiser, eu já atualizo este arquivo com exemplos de payloads e contratos (inputs/outputs) para o endpoint que você escolher implementar agora.

````

## Atualizações rápidas (adicionadas)

### Eager Loading (SEMPRE usar para evitar N+1)

````phpEstas notas foram acrescentadas para ajudar agentes AI a iniciar rapidamente com workflows e decisões recorrentes do projeto.

// Exemplo real de Produto

Produto::with(['grupoProduto', 'unidadeMedida'])->where('status', 'A')->get();-   Build / setup rápidos:



// Entrada com relacionamentos    -   Instalar dependências PHP: `composer install`

Entrada::with(['itens.produto', 'setor', 'fornecedor'])->find($id);    -   Copiar env: `copy .env.example .env` (Windows PowerShell) e gerar key: `php artisan key:generate`

```    -   Migrations + seeders (dev): `php artisan migrate --seed`

    -   Frontend (quando aplicável): `npm install` e `npm run dev`

### Scopes Disponíveis (ver Models)    -   Rodar testes unitários: `vendor/bin/phpunit` ou `./vendor/bin/phpunit`

```php    -   Servir localmente: `php artisan serve`

// Estoque.php

->disponivel()  // where status_disponibilidade = 'D'-   Onde olhar primeiro (arquivos-chave):

->porSetor($setorId)

    -   `routes/api.php` — todas as rotas de API (o projeto usa POST com frequência para listagens)

// Produto.php (assumido - verificar implementação)    -   `app/Http/Controllers/` — controllers seguem padrão de respostas JSON com `status`/`data`/`message`

->ativo()  // where status = 'A'    -   Models críticos: `app/Produto.php`, `app/Estoque.php`, `app/EstoqueLote.php`, `app/Setores.php`, `app/GrupoProduto.php`

->porGrupo($grupoId)    -   Observers: `app/Observers/ProdutoObserver.php`, `app/Observers/SetoresObserver.php` (registrados em `app/Providers/AppServiceProvider.php`)

```    -   Migrations: `database/migrations/*` — verifique chaves únicas e constraints, ex: `estoque_lote` unique composta `(unidade_id, produto_id, lote)`.

    -   Seeders: `database/seeders/` (ex.: `SetoresSeeder.php`) são úteis para testes locais.

### Transações para Operações Multi-Tabela

```php-   Padrões de respostas e logging:

use Illuminate\Support\Facades\DB;

    -   Sucesso: `{ status: true, data: <payload>, message?: <string> }`

try {    -   Erro: `{ status: false, message: <string> }` com HTTP status code adequado

    $resultado = DB::transaction(function () use ($data) {    -   Sempre logar exceções relevantes com `Log::error()` antes de retornar erro ao cliente.

        $entrada = Entrada::create([...]);

        foreach ($data['itens'] as $item) {-   Convenções e regras encontradas no código (faça igual):

            ItensEntrada::create([...]);

            // Atualizar estoque + lotes    -   Não usar soft deletes: em vez disso, inative com `status = 'I'` (ativa = `'A'`).

        }    -   `estoque.status_disponibilidade` usa valores 'D' (Disponível) e 'I' (Indisponível).

        return $entrada;    -   Tipo matching: `grupo_produto.tipo` deve casar com `setor.tipo` para provisionamento de estoque (ver observers).

    });    -   `estoque_lote` tem unique composta; entradas duplicadas devem fazer upsert ou consolidar quantidades.

    return ['status' => true, 'data' => $resultado];    -   Scopes úteis esperados: `ativo()`, `porGrupo()`, `porUnidade()`, `disponivel()` — procure implementação nos models antes de criar queries customizadas.

} catch (\Exception $e) {

    Log::error('Erro ao processar entrada: ' . $e->getMessage());-   Padrões de implementação observados (exemplos concretos):

    return response()->json(['status' => false, 'message' => 'Erro interno'], 500);    -   Auto-provisionamento: `ProdutoObserver`/`SetoresObserver` chamam métodos de `Estoque` para criar registros iniciais — ver `app/Providers/AppServiceProvider.php`.

}    -   Eager loading frequente: `Produto::with(['grupoProduto','unidadeMedida'])->...` para evitar N+1.

```    -   Rotas e controllers frequentemente aceitam `filters` (array) e `per_page` para listagens paginadas (veja métodos `listAll()` nos controllers).



## Rotas e Autenticação## Mini-contrato para novos endpoints



**Padrão de Rotas**: POST para tudo (ver `routes/api.php`)-   Input: JSON via POST; padrões comuns: `filters`, `per_page`, `data`.

```php-   Output: JSON com chaves `status`, `data`, `message`.

Route::post('/entidade/add', [Controller::class, 'add']);-   Erros: retornar `status: false`, logar com `Log::error()` e usar HTTP status code adequado.

Route::post('/entidade/update', [Controller::class, 'update']);

Route::post('/entidade/list', [Controller::class, 'listAll']);  // POST mesmo para listas!## Checklist rápido antes de abrir PR

Route::post('/entidade/delete/{id}', [Controller::class, 'delete']);

```1. Altera modelos/migrations? Confirme constraints em `database/migrations` e atualize seeders.

2. Modifica lógica de estoque/lote? Garanta validação do tipo (`grupo_produto.tipo x setor.tipo`) e atualização correta de `estoque` e `estoque_lote`.

**Autenticação**: Maioria das rotas SEM `auth:sanctum` (exceto `/user`)  3. Mantém formato de resposta JSON e usa eager loading/scopes onde aplicável.

⚠️ Confirmar requisitos antes de adicionar middleware4. Execute `vendor/bin/phpunit` e inclua seeders necessários para testes que dependam de dados.



## Desenvolvimento Local---



```powershellSe quiser, aplico exemplos de payloads e um contrato OpenAPI mínimo para um endpoint (ex.: registrar uma `entrada` que atualize `estoque_lote` e `estoque`). Indique qual endpoint e eu gero payloads, testes e um PR sugerido.

# Setup inicial
composer install
copy .env.example .env
php artisan key:generate

# Configurar .env com DB (ex: progest2)
# Migrations + seeders
php artisan migrate --seed

# Rodar servidor
php artisan serve

# Testes
vendor/bin/phpunit
# ou com filtro
vendor/bin/phpunit --filter=SetoresFornecedoresTest
````

**Seeders importantes** (ver `DatabaseSeeder.php`):

-   `UnidadesSeeder` → cria polos
-   `SetoresSeeder` → cria setores (triggers observers!)
-   `GrupoProdutoSeeder` → define tipos
-   `ProdutosSeeder` → cria produtos (triggers auto-provisionamento)

## Testes (Padrão RefreshDatabase)

Exemplo real (`tests/Feature/SetoresFornecedoresTest.php`):

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class MeuTest extends TestCase {
    use RefreshDatabase;

    public function test_exemplo() {
        $unidade = Unidade::factory()->create();
        $payload = ['Setores' => [...], 'fornecedor' => [...]];

        $response = $this->postJson('/api/setores/add', $payload);
        $response->assertStatus(200)->assertJson(['status' => true]);

        $this->assertDatabaseHas('setores', ['nome' => 'X']);
    }
}
```

## Checklist Pré-Implementação

Antes de criar endpoint/funcionalidade:

1. **Entidades afetadas**: produto/estoque/lote/entrada/movimentacao?
2. **Tipo matching necessário**: `grupo_produto.tipo` x `setor.tipo`?
3. **Auto-provisionamento**: criar/atualizar `estoque` e `estoque_lote`?
4. **Validação**: mensagens em português, regras específicas (datas, enums)?
5. **Transaction**: operação multi-tabela requer `DB::transaction()`?
6. **Eager loading**: relacionamentos usados no response?
7. **Logging**: `Log::error()` em todos os catch blocks
8. **Foreign Keys**: `onDelete('restrict')` — verificar cascata lógica

## Arquivos-Chave para Consulta Rápida

```
app/Observers/
  ProdutoObserver.php      # Auto-cria estoque ao criar produto
  SetoresObserver.php      # Auto-cria estoque ao criar setor com estoque=true

app/Models/
  Estoque.php              # Métodos: criarEstoqueParaNovoProduto(), criarEstoqueInicialParaSetor()
  Produto.php              # Relacionamento: grupoProduto, unidadeMedida
  EstoqueLote.php          # Unique: (unidade_id, produto_id, lote)

app/Http/Controllers/
  EntradaController.php    # Exemplo completo: validação, transaction, upsert lotes
  Cadastros/GrupoProdutoController.php  # Exemplo CRUD simples com padrão response

routes/api.php             # Todas rotas POST (incluindo listas)

database/migrations/
  *_create_estoque_table.php       # Status 'D'/'I', FK constraints
  *_create_estoque_lote_table.php  # Unique composta
  *_create_grupo_produto_table.php # Enum tipo: Medicamento|Material

database/seeders/
  DatabaseSeeder.php       # Ordem correta de seeders
```

## Payloads de Exemplo (Contratos Reais)

**POST /api/entrada/add** (EntradaController):

```json
{
    "nota_fiscal": "NF123456",
    "unidade_id": 1,
    "fornecedor_id": 2,
    "itens": [
        {
            "produto_id": 10,
            "quantidade": 50,
            "lote": "L2025001",
            "data_vencimento": "2026-12-31",
            "data_fabricacao": "2025-01-15"
        }
    ]
}
```

**POST /api/grupoProduto/list** (filtros dinâmicos):

```json
{
    "filters": [{ "tipo": "Medicamento" }, { "status": "A" }],
    "paginate": false
}
```

## Perguntas para Alinhar Nova Implementação

1. Endpoint precisa autenticação (`auth:sanctum`)?
2. Usar padrão POST para listagem (compatibilidade com frontend)?
3. Criar testes PHPUnit Feature com `RefreshDatabase`?
4. Relacionamentos precisam eager loading no response?
5. Operação multi-tabela? Então precisa transaction + rollback
