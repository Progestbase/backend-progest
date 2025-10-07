# ProGest Backend - AI Agent Instructions

## Sistema Overview

Sistema de gestão hospitalar (ProGest) para controle de estoque de medicamentos e materiais médicos. Laravel 8 API backend com autenticação Sanctum.

## Arquitetura Core

### Domínio Principal: Gestão de Estoque por Unidade Hospitalar

-   **Unidades** (`unidades`) possuem `tipo` (Medicamento|Material) e flag `estoque` (boolean)
-   **Produtos** (`produtos`) pertencem a **GrupoProduto** que tem `tipo` correspondente
-   **Estoque** (`estoque`) vincula Produto × Unidade - criado automaticamente via Observers
-   Regra crítica: Produtos só aparecem em estoques de unidades com tipo compatível

### Padrão de Auto-Provisionamento (Observers)

```php
// app/Observers/ProdutoObserver.php e UnidadesObserver.php
// Registrados em app/Providers/AppServiceProvider.php
```

-   Quando `Produto` criado → Observer cria registros em `estoque` para todas unidades compatíveis
-   Quando `Unidades` com `estoque=true` criada → Observer popula estoque com produtos compatíveis
-   Veja `Estoque::criarEstoqueParaNovoProduto()` e `Estoque::criarEstoqueInicialParaUnidade()`

## Convenções Específicas

### 1. Nomenclatura de Tabelas (Inconsistente - Manter)

-   Plural: `unidades`, `produtos`, `fornecedores`
-   Singular: `estoque`, `grupo_produto`, `unidade_medida`
-   Model `Unidades` → table `unidades` (plural no model e tabela)

### 2. Estrutura de Controllers

```
app/Http/Controllers/
├── AuthController.php (gestão de usuários + auth)
├── EstoqueController.php (operações de estoque)
└── Cadastros/ (CRUD genéricos)
    ├── ProdutoController.php
    ├── FornecedorController.php
    └── UnidadesController.php
```

### 3. Padrão de Rotas API (routes/api.php)

```php
// Todas usam POST, mesmo para listagem
Route::post('/produtos/add', [ProdutoController::class, 'add']);
Route::post('/produtos/update', [ProdutoController::class, 'update']);
Route::post('/produtos/list', [ProdutoController::class, 'listAll']);
Route::post('/produtos/listData', [ProdutoController::class, 'listData']); // obter 1 registro
Route::post('/produtos/delete', [ProdutoController::class, 'delete']);
Route::post('/produtos/toggleStatus', [ProdutoController::class, 'toggleStatus']);
```

**Importante**: Não use verbos HTTP RESTful padrão - manter POST para tudo.

### 4. Padrão de Response Controllers

```php
// Sucesso
return response()->json([
    'status' => true,
    'data' => $result,
    'message' => 'Mensagem opcional'
]);

// Erro
return response()->json([
    'status' => false,
    'message' => 'Descrição do erro'
], 400/404/500);
```

### 5. Status Enums (Pattern Consistente)

-   `'A'` = Ativo, `'I'` = Inativo (usuarios, produtos, unidades)
-   `'D'` = Disponível, `'I'` = Indisponível (status_disponibilidade em estoque)

### 6. Eager Loading Padrão

```php
// Controllers sempre usam with() para relacionamentos
Produto::with(['grupoProduto', 'unidadeMedida'])->get();
Estoque::with(['produto.grupoProduto', 'produto.unidadeMedida'])->get();
```

### 7. Scopes nos Models

Models definem scopes para filtros comuns:

```php
Produto::ativo()->porGrupo($id)->get();
Estoque::disponivel()->porUnidade($id)->get();
```

## Comandos Essenciais

```bash
# Setup inicial
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve

# Desenvolvimento
php artisan migrate:fresh --seed  # Reset completo
php artisan make:migration create_x_table
php artisan make:model NomeModel -m  # Model + Migration
php artisan make:controller NomeController

# Observers devem ser registrados em AppServiceProvider::boot()
```

## Regras de Negócio Críticas

1. **Tipo Matching**: Ao criar endpoints de listagem, sempre filtrar produtos por `grupo_produto.tipo` compatível com `unidade.tipo`
2. **Soft Delete via Status**: Não usar soft deletes do Laravel - mudar `status` para 'I'
3. **Validação CVEs**: Frontend espera campo `status` booleano no response
4. **Migrations**: Nome da classe deve corresponder ao nome do arquivo (padrão Laravel)

## Pontos de Atenção

-   **Sem autenticação em rotas**: Maioria das rotas não usa `auth:sanctum` middleware (apenas `/user`)
-   **Paginação**: `listAll()` methods usam paginação com `per_page` do request
-   **Filtros dinâmicos**: Controllers aceitam `filters` array no request para queries flexíveis
-   **Log de erros**: Sempre usar `Log::error()` em catch blocks antes de response
-   **Foreign keys**: Migrations usam `->constrained()` com `->onDelete('restrict')`

## Estrutura de Dados Auxiliar

```php
// GrupoProduto.tipo → Unidades.tipo → Estoque matching
// Exemplo fluxo:
1. Criar Produto com grupo_produto_id (tipo: 'Medicamento')
2. Observer detecta criação
3. Busca Unidades com tipo='Medicamento' e estoque=true
4. Cria registros em estoque para cada unidade encontrada
```

## Testing

PHPUnit configurado (`phpunit.xml`) mas sem testes implementados. Ao criar testes, seguir estrutura:

```
tests/Feature/ - testes de API
tests/Unit/ - testes de models/services
```
