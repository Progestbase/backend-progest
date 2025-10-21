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
