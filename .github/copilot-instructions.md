# ProGest Backend - AI Agent Instructions (atualizado)

> Contextualização gerada a partir das migrations presentes em database/migrations — use este documento como referência rápida para regras de domínio, convenções e pontos de atenção antes de pedir implementações.

## Visão Geral do Sistema

ProGest é um sistema de gestão hospitalar para controle de estoques de medicamentos e materiais. Backend em Laravel (projeto existente) com padrão de API que usa rotas via `routes/api.php` e convenções internas (status, enums, observers).

Base principal extraída das migrations lidas: entidades centrais — `users`, `setores` (unidades), `produtos`, `grupo_produto`, `unidade_medida`, `estoque`, `estoque_lote`, `entrada`, `itens_entrada`, `movimentacao`, `item_movimentacao`, `fornecedores`.

## Modelo de Dados (resumo das migrations)

-   `setores` (unidades): id, `polo_id`, `nome`, `descricao`, `status` ('A'/'I'), `estoque` (boolean), `tipo` ('Medicamento'|'Material'). Usada como `unidade` nas relações de estoque.
-   `grupo_produto`: id, `nome`, `status` ('A'/'I'), `tipo` ('Medicamento'|'Material') — define o tipo do produto.
-   `unidade_medida`: id, `nome`, `quantidade_unidade_minima`, `status` ('A'/'I').
-   `produtos`: id, `nome`, `marca`, `codigo_simpras`, `codigo_barras`, `grupo_produto_id`, `unidade_medida_id`, `status` ('A'/'I').
-   `estoque`: id, `produto_id`, `unidade_id` (setores), `quantidade_atual`, `quantidade_minima`, `localizacao`, `status_disponibilidade` ('D' Disponível, 'I' Indisponível').
-   `estoque_lote`: id, `unidade_id`, `produto_id`, `lote`, `quantidade_disponivel` (decimal), `data_vencimento`, `data_fabricacao` (nullable). Unique composta (unidade, produto, lote).
-   `entrada` / `itens_entrada`: notas fiscais + itens com lote, datas de fabricação/vencimento — usados para criar/atualizar `estoque_lote` e `estoque`.
-   `movimentacao` / `item_movimentacao`: registros de movimentações (transferência, devolução, saída) entre unidades com itens e quantidades (solicitada/liberada).

Relações importantes:

-   `produto.grupo_produto` (grupo define o tipo que deve casar com `setor.tipo`).
-   `estoque.produto` e `estoque.unidade` (setor).
-   `estoque_lote` refere-se a `produto` e `unidade` (controle por lote).

## Regras de Negócio Críticas (inferidas das migrations e codebase)

1. Tipo matching: produtos de `grupo_produto.tipo = 'Medicamento'` só são compatíveis com `setores.tipo = 'Medicamento'` quando `setor.estoque = true` — Observers automatizam provisionamento.
2. Não usar soft deletes do Eloquent; a aplicação marca `status = 'I'` para inativar registros.
3. `status_disponibilidade` em `estoque` usa 'D' (Disponível) e 'I' (Indisponível).
4. `estoque_lote` tem chave única (unidade, produto, lote) — cuidado ao inserir itens de entrada duplicados.

## Convenções de API e Código

-   Rotas: o projeto historicamente usa POST para operações CRUD e listagens via `routes/api.php`.
-   Responses: padrão JSON com chave `status` booleana, `data` para payload (quando sucesso) e `message` em strings; erros retornam `status: false` e código HTTP apropriado.
-   Eager loading: controllers costumam usar `with()` para trazer relacionamentos (ex.: Produto::with(['grupoProduto','unidadeMedida'])->...)
-   Scopes: modelos oferecem scopes (`ativo()`, `porGrupo()`, `disponivel()`, `porUnidade()`) — prefira usá-los.

## Observers e Auto-provisionamento

-   Observers existentes (ex.: `ProdutoObserver`, `SetoresObserver`) são registrados em `AppServiceProvider::boot()` para criar registros de `estoque` automaticamente:
    -   Ao criar um `produto`: cria `estoque` para todas as `setores` com `estoque=true` e `tipo` compatível.
    -   Ao criar/ativar um `setor` com `estoque=true`: cria `estoque` para todos os produtos compatíveis.

Funções úteis esperadas em Models/Service:

-   `Estoque::criarEstoqueParaNovoProduto(Produto $produto)`
-   `Estoque::criarEstoqueInicialParaUnidade(Setor $unidade)`

## Pontos de Atenção (operacionais)

-   Foreign keys usam `->constrained()` com `onDelete('restrict')` — migrações e seeders devem garantir integridade.
-   Rotas majoritariamente sem middleware `auth:sanctum` (exceto `/user`). Confirmar se novas rotas precisam de autenticação.
-   Paginação: `listAll()` espera `per_page` no request.
-   Filtros dinâmicos: controllers aceitam `filters` array com operadores básicos.
-   Log: use `Log::error()` em blocos catch antes de retornar resposta de erro.

## Checklist rápido antes de implementar tarefas

1. Qual entidade(s) será afetada? (produto, estoque, lote, movimentação, entrada)
2. O endpoint deve respeitar tipo matching (grupo_produto.tipo x setor.tipo)?
3. Precisa criar/atualizar `estoque` e/ou `estoque_lote` automaticamente ao processar entrada/movimentação?
4. Requisitos de autorização/usuário para a ação (middleware)?

## Próximos passos sugeridos

-   Me diga qual funcionalidade/endpoints você quer implementar hoje (ex.: endpoint para registrar entrada com atualização de estoque e lotes; endpoint para transferências entre unidades; listagem paginada de produtos por unidade). Eu já li as migrations e entendo onde cada campo está.
-   Posso começar criando controllers, requests de validação, serviços e testes mínimos para validar o fluxo.

## Perguntas rápidas para alinhar

1. Deseja que eu mantenha o padrão de usar POST para listagens e CRUD? (recomendado: sim, para compatibilidade)
2. Nova rota precisa de autenticação (`auth:sanctum`) ou continuará pública?
3. Quer que eu atualize/adicione tests PHPUnit para a funcionalidade?

---

Se quiser, eu já atualizo este arquivo com exemplos de payloads e contratos (inputs/outputs) para o endpoint que você escolher implementar agora.

## Atualizações rápidas (adicionadas)

Estas notas foram acrescentadas para ajudar agentes AI a iniciar rapidamente com workflows e decisões recorrentes do projeto.

-   Build / setup rápidos:

    -   Instalar dependências PHP: `composer install`
    -   Copiar env: `copy .env.example .env` (Windows PowerShell) e gerar key: `php artisan key:generate`
    -   Migrations + seeders (dev): `php artisan migrate --seed`
    -   Frontend (quando aplicável): `npm install` e `npm run dev`
    -   Rodar testes unitários: `vendor/bin/phpunit` ou `./vendor/bin/phpunit`
    -   Servir localmente: `php artisan serve`

-   Onde olhar primeiro (arquivos-chave):

    -   `routes/api.php` — todas as rotas de API (o projeto usa POST com frequência para listagens)
    -   `app/Http/Controllers/` — controllers seguem padrão de respostas JSON com `status`/`data`/`message`
    -   Models críticos: `app/Produto.php`, `app/Estoque.php`, `app/EstoqueLote.php`, `app/Setores.php`, `app/GrupoProduto.php`
    -   Observers: `app/Observers/ProdutoObserver.php`, `app/Observers/SetoresObserver.php` (registrados em `app/Providers/AppServiceProvider.php`)
    -   Migrations: `database/migrations/*` — verifique chaves únicas e constraints, ex: `estoque_lote` unique composta `(unidade_id, produto_id, lote)`.
    -   Seeders: `database/seeders/` (ex.: `SetoresSeeder.php`) são úteis para testes locais.

-   Padrões de respostas e logging:

    -   Sucesso: `{ status: true, data: <payload>, message?: <string> }`
    -   Erro: `{ status: false, message: <string> }` com HTTP status code adequado
    -   Sempre logar exceções relevantes com `Log::error()` antes de retornar erro ao cliente.

-   Convenções e regras encontradas no código (faça igual):

    -   Não usar soft deletes: em vez disso, inative com `status = 'I'` (ativa = `'A'`).
    -   `estoque.status_disponibilidade` usa valores 'D' (Disponível) e 'I' (Indisponível).
    -   Tipo matching: `grupo_produto.tipo` deve casar com `setor.tipo` para provisionamento de estoque (ver observers).
    -   `estoque_lote` tem unique composta; entradas duplicadas devem fazer upsert ou consolidar quantidades.
    -   Scopes úteis esperados: `ativo()`, `porGrupo()`, `porUnidade()`, `disponivel()` — procure implementação nos models antes de criar queries customizadas.

-   Padrões de implementação observados (exemplos concretos):
    -   Auto-provisionamento: `ProdutoObserver`/`SetoresObserver` chamam métodos de `Estoque` para criar registros iniciais — ver `app/Providers/AppServiceProvider.php`.
    -   Eager loading frequente: `Produto::with(['grupoProduto','unidadeMedida'])->...` para evitar N+1.
    -   Rotas e controllers frequentemente aceitam `filters` (array) e `per_page` para listagens paginadas (veja métodos `listAll()` nos controllers).

## Mini-contrato para novos endpoints

-   Input: JSON via POST; padrões comuns: `filters`, `per_page`, `data`.
-   Output: JSON com chaves `status`, `data`, `message`.
-   Erros: retornar `status: false`, logar com `Log::error()` e usar HTTP status code adequado.

## Checklist rápido antes de abrir PR

1. Altera modelos/migrations? Confirme constraints em `database/migrations` e atualize seeders.
2. Modifica lógica de estoque/lote? Garanta validação do tipo (`grupo_produto.tipo x setor.tipo`) e atualização correta de `estoque` e `estoque_lote`.
3. Mantém formato de resposta JSON e usa eager loading/scopes onde aplicável.
4. Execute `vendor/bin/phpunit` e inclua seeders necessários para testes que dependam de dados.

---

Se quiser, aplico exemplos de payloads e um contrato OpenAPI mínimo para um endpoint (ex.: registrar uma `entrada` que atualize `estoque_lote` e `estoque`). Indique qual endpoint e eu gero payloads, testes e um PR sugerido.
