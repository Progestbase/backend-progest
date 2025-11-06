# ProGest Backend - AI Agent Instructions

> Sistema de gestão hospitalar Laravel para controle de estoques de medicamentos e materiais com auto-provisionamento via Observers e validação de tipos por regras de negócio.

## Arquitetura Essencial

**ProGest** gerencia estoques hospitalares com dois fluxos críticos:

1. **Auto-provisionamento via Observers**: Quando produto/setor é criado, observers (`ProdutoObserver`, `SetoresObserver`) criam registros de `estoque` automaticamente se tipos compatíveis (registrados em `AppServiceProvider::boot()`)
2. **Controle por lote com unique constraint**: Entradas atualizam `estoque` (agregado) + `estoque_lote` (granular com vencimento, lote e unique composta)

**Entidades Core** (ver `database/migrations/`):

**Entidades Core** (ver migrations em `database/migrations/`):

-   `setores`: tem `tipo` enum('Medicamento','Material'), `estoque` boolean, `status` enum('A','I'), FK para `unidades` (polos). Usado como `unidade_id` em relações de estoque.
-   `produtos`: tem `grupo_produto_id` (FK) que define `tipo` via `grupo_produto.tipo` — DEVE casar com `setor.tipo` para operações de estoque
-   `grupo_produto`: define `tipo` enum('Medicamento','Material') e `status` enum('A','I') que propaga para produtos
-   `estoque`: agregação por `produto_id` + `unidade_id` (setor) com `quantidade_atual`, `quantidade_minima`, `status_disponibilidade` enum('D','I')
-   `estoque_lote`: controle granular com **unique composta** `(unidade_id, produto_id, lote)` — índice `unique_estoque_lote`. Tem `quantidade_disponivel`, `data_vencimento`, `data_fabricacao`

**Fluxo de entrada** (exemplo real em `EntradaController::add()`):

```php
// 1. Validar nota fiscal + itens (produto, quantidade, lote, vencimento)
// 2. Verificar setor.estoque = true
// 3. Transaction: criar entrada + itens_entrada
// 4. Para cada item:
//    - Validar produto.grupoProduto.tipo === setor.tipo (RuntimeException se falhar)
//    - firstOrCreate em estoque, incrementar quantidade_atual
//    - firstOrCreate em estoque_lote por unique (unidade+produto+lote), incrementar quantidade_disponivel
// 5. Auto-atualizar status_disponibilidade: 'D' se quantidade > 0
```

## Regras de Negócio Críticas

1. **Tipo Matching Obrigatório**: `grupo_produto.tipo` DEVE casar com `setor.tipo` em operações de estoque
   ❌ Erro: `throw new \RuntimeException('Produto "X" não é compatível com o tipo do setor')`

2. **Status ao invés de Soft Deletes**: use `status = 'A'|'I'` — NÃO há `deleted_at`
   Verificar: `where('status', 'A')` em queries

3. **Disponibilidade de Estoque**: `status_disponibilidade` usa 'D' (Disponível) ou 'I' (Indisponível)
   Auto-atualizado: se `quantidade_atual > 0` → 'D', senão 'I'

4. **Constraint Única em Lotes**: `estoque_lote(unidade_id, produto_id, lote)` — usar `firstOrCreate` para evitar duplicatas

5. **Auto-provisionamento via Observers** (registrado em `AppServiceProvider::boot()`):
    - `ProdutoObserver::created()` → chama `Estoque::criarEstoqueParaNovoProduto()`
    - `SetoresObserver::created()` → chama `Estoque::criarEstoqueInicialParaSetor()`
      **Importante**: só cria se `setor.estoque = true` E tipos compatíveis

## Convenções de API e Código

### API Response Pattern (SEMPRE seguir)

```php
// Sucesso
return ['status' => true, 'data' => $payload];
return ['status' => true, 'data' => $payload, 'message' => 'Operação concluída'];

// Erro de validação
return response()->json([
    'status' => false,
    'validacao' => true,
    'erros' => $validator->errors()
], 422);

// Erro de negócio
return response()->json(['status' => false, 'message' => 'Setor sem controle de estoque'], 400);
```

### Estrutura de Controllers

Métodos padrão esperados (ver `GrupoProdutoController`, `EntradaController`):

-   `add(Request $request)` — validação + criação + log de erros
-   `update(Request $request)` — validação + atualização
-   `listAll(Request $request)` — aceita `filters` (array), `paginate` (bool)
-   `delete($id ou Request)` — soft inativa (`status = 'I'`) ou hard delete conforme FK constraints
-   `toggleStatus(Request $request)` — alterna 'A'/'I'

### Validação Pattern

```php
$validator = Validator::make($data['nomeEntidade'], [
    'campo' => 'required|string|max:255',
    'tipo' => 'required|string|in:Medicamento,Material'
], [
    'campo.required' => 'Mensagem customizada em português',
]);

if ($validator->fails()) {
    return response()->json([
        'status' => false,
        'validacao' => true,
        'erros' => $validator->errors()
    ], 422);
}
```

### Eager Loading (SEMPRE usar para evitar N+1)

```php
// Exemplo real de Produto
Produto::with(['grupoProduto', 'unidadeMedida'])->where('status', 'A')->get();

// Entrada com relacionamentos
Entrada::with(['itens.produto', 'setor', 'fornecedor'])->find($id);
```

### Scopes Disponíveis (ver Models)

```php
// Estoque.php
->disponivel()  // where status_disponibilidade = 'D'
->porSetor($setorId)

// Produto.php (assumido - verificar implementação)
->ativo()  // where status = 'A'
->porGrupo($grupoId)
```

### Transações para Operações Multi-Tabela

```php
use Illuminate\Support\Facades\DB;

try {
    $resultado = DB::transaction(function () use ($data) {
        $entrada = Entrada::create([...]);
        foreach ($data['itens'] as $item) {
            ItensEntrada::create([...]);
            // Atualizar estoque + lotes
        }
        return $entrada;
    });
    return ['status' => true, 'data' => $resultado];
} catch (\Exception $e) {
    Log::error('Erro ao processar entrada: ' . $e->getMessage());
    return response()->json(['status' => false, 'message' => 'Erro interno'], 500);
}
```

## Rotas e Autenticação

**Padrão de Rotas**: POST para tudo (ver `routes/api.php`)

```php
Route::post('/entidade/add', [Controller::class, 'add']);
Route::post('/entidade/update', [Controller::class, 'update']);
Route::post('/entidade/list', [Controller::class, 'listAll']);  // POST mesmo para listas!
Route::post('/entidade/delete/{id}', [Controller::class, 'delete']);
```

**Autenticação**: Maioria das rotas SEM `auth:sanctum` (exceto `/user` e grupo `UsuarioSetor`)  
⚠️ Confirmar requisitos antes de adicionar middleware

## Desenvolvimento Local

```powershell
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
```

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

## Descobertas do Codebase (Padrões Específicos)

1. **Observers ativados via AppServiceProvider**: `Produto::observe(ProdutoObserver::class)` e `Setores::observe(SetoresObserver::class)` em `boot()`

2. **Métodos estáticos de Estoque para auto-provisionamento**:

    - `Estoque::criarEstoqueParaNovoProduto($produtoId)` busca setores com `estoque=true` e tipo compatível
    - `Estoque::criarEstoqueInicialParaSetor($setorId)` busca produtos ativos com tipo compatível

3. **Validação de entrada**: `EntradaController` valida `data_vencimento.after:today` e `data_fabricacao.before_or_equal:today`

4. **Unique constraint em estoque_lote**: índice `unique_estoque_lote` impede duplicatas — sempre usar `firstOrCreate` com `['unidade_id', 'produto_id', 'lote']`

5. **Nomes sempre uppercase**: `mb_strtoupper(trim($data['campo']))` em cadastros (nota_fiscal, lote, nome)

6. **Foreign Keys com restrict**: `->constrained('tabela')->onDelete('restrict')` — deletes devem inativar via `status='I'`

7. **User default criado via migration**: `2025_07_07_233833_create_default_admin_user.php` cria admin@admin.com / senha: admin

## Perguntas para Alinhar Nova Implementação

1. Endpoint precisa autenticação (`auth:sanctum`)?
2. Usar padrão POST para listagem (compatibilidade com frontend)?
3. Criar testes PHPUnit Feature com `RefreshDatabase`?
4. Relacionamentos precisam eager loading no response?
5. Operação multi-tabela? Então precisa transaction + rollback

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
