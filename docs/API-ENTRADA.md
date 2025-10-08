# API – Módulo de Entradas

Documentação das rotas responsáveis por registrar e gerenciar entradas de produtos no estoque.

## Convenções Gerais

- Todas as rotas utilizam o verbo `POST` e retornam JSON.
- Os payloads devem ser enviados como `application/json`.
- As respostas seguem o padrão `{ "status": boolean, ... }`.
- A consistência do estoque é garantida automaticamente em cada operação.

## 1. Criar entrada

`POST /api/entrada/add`

### Payload
```json
{
  "nota_fiscal": "NF-123",
  "unidade_id": 1,
  "fornecedor_id": 2,
  "itens": [
    { "produto_id": 10, "quantidade": 5 },
    { "produto_id": 11, "quantidade": 3 }
  ]
}
```

| Campo              | Tipo     | Obrigatório | Observações |
|--------------------|----------|-------------|-------------|
| `nota_fiscal`      | string   | Sim         | Até 255 caracteres. |
| `unidade_id`       | integer  | Sim         | ID de uma unidade com `estoque = true`. |
| `fornecedor_id`    | integer  | Sim         | ID válido em `fornecedores`. |
| `itens`            | array    | Sim         | Mínimo 1 item. |
| `itens[].produto_id` | integer | Sim         | Produto compatível com o tipo da unidade. |
| `itens[].quantidade` | integer | Sim         | Valor mínimo 1. |

### Exemplo de resposta (201)
```json
{
  "status": true,
  "message": "Entrada registrada com sucesso.",
  "data": {
    "id": 25,
    "nota_fiscal": "NF-123",
    "unidade_id": 1,
    "fornecedor_id": 2,
    "created_at": "2025-10-08T14:25:33.000000Z",
    "updated_at": "2025-10-08T14:25:33.000000Z",
    "unidade": {
      "id": 1,
      "nome": "FARMÁCIA DE DISPENSAÇÃO",
      "tipo": "Medicamento"
    },
    "fornecedor": {
      "id": 2,
      "razao_social_nome": "Medic Supplies Brasil S.A.",
      "tipo_pessoa": "J",
      "status": "A"
    },
    "itens": [
      {
        "id": 40,
        "entrada_id": 25,
        "produto_id": 10,
        "quantidade": 5,
        "produto": {
          "id": 10,
          "nome": "PARACETAMOL 500MG",
          "marca": "GENÉRICO",
          "status": "A"
        }
      }
    ]
  }
}
```

### Erros comuns
- **422**: payload inválido (campo obrigatório ausente, quantidade < 1, etc.).
- **400**: unidade sem controle de estoque ou produto incompatível com o tipo.
- **500**: erro inesperado.

---

## 2. Listar entradas

`POST /api/entrada/list`

### Payload (opcional)
```json
{
  "filters": {
    "nota_fiscal": "NF-",
    "unidade_id": 1,
    "fornecedor_id": 2
  },
  "per_page": 15
}
```

| Campo             | Tipo    | Obrigatório | Observações |
|-------------------|---------|-------------|-------------|
| `filters`          | objeto  | Não         | Todos os campos internos são opcionais. |
| `filters.nota_fiscal` | string | Não | Busca parcial (LIKE). |
| `filters.unidade_id`  | integer | Não | Filtra por unidade. |
| `filters.fornecedor_id` | integer | Não | Filtra por fornecedor. |
| `per_page`         | integer | Não | Default 15. |

### Exemplo de resposta (200)
```json
{
  "status": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 25,
        "nota_fiscal": "NF-123",
        "created_at": "2025-10-08T14:25:33.000000Z",
        "unidade": {
          "id": 1,
          "nome": "FARMÁCIA DE DISPENSAÇÃO",
          "codigo_unidade": null,
          "tipo": "Medicamento"
        },
        "fornecedor": {
          "id": 2,
          "razao_social_nome": "Medic Supplies Brasil S.A.",
          "tipo_pessoa": "J",
          "status": "A"
        },
        "itens": [
          {
            "id": 40,
            "quantidade": 5,
            "produto": {
              "id": 10,
              "nome": "PARACETAMOL 500MG",
              "marca": "GENÉRICO",
              "status": "A",
              "grupo_produto": {
                "id": 1,
                "nome": "ANALGÉSICO",
                "tipo": "Medicamento"
              },
              "unidade_medida": {
                "id": 3,
                "nome": "Comprimido"
              }
            }
          }
        ]
      }
    ],
    "per_page": 15,
    "total": 1
  }
}
```

### Erros comuns
- **500**: erro inesperado.

---

## 3. Atualizar entrada

`POST /api/entrada/update`

### Payload
```json
{
  "id": 25,
  "nota_fiscal": "NF-123-REV",
  "unidade_id": 1,
  "fornecedor_id": 2,
  "itens": [
    { "produto_id": 10, "quantidade": 8 }
  ]
}
```

As mesmas regras e validações de criação se aplicam, com a inclusão obrigatória de `id` (entrada existente).

### Exemplo de resposta (200)
```json
{
  "status": true,
  "message": "Entrada atualizada com sucesso.",
  "data": {
    "id": 25,
    "nota_fiscal": "NF-123-REV",
    "unidade_id": 1,
    "fornecedor_id": 2,
    "created_at": "2025-10-08T14:25:33.000000Z",
    "updated_at": "2025-10-08T15:02:10.000000Z",
    "unidade": {
      "id": 1,
      "nome": "FARMÁCIA DE DISPENSAÇÃO",
      "tipo": "Medicamento"
    },
    "fornecedor": {
      "id": 2,
      "razao_social_nome": "Medic Supplies Brasil S.A.",
      "tipo_pessoa": "J",
      "status": "A"
    },
    "itens": [
      {
        "id": 41,
        "entrada_id": 25,
        "produto_id": 10,
        "quantidade": 8,
        "produto": {
          "id": 10,
          "nome": "PARACETAMOL 500MG",
          "marca": "GENÉRICO",
          "status": "A"
        }
      }
    ]
  }
}
```

### Erros comuns
- **422**: payload inválido.
- **400**: unidade sem estoque ou produto incompatível.
- **500**: erro inesperado.

---

## 4. Remover entrada

`POST /api/entrada/delete`

### Payload
```json
{ "id": 25 }
```

### Exemplo de resposta (200)
```json
{
  "status": true,
  "message": "Entrada removida com sucesso."
}
```

### Erros comuns
- **422**: ID ausente ou inválido.
- **500**: erro inesperado durante a remoção.

---

## Regras de Estoque

- Todas as operações são transacionais: se houver erro, nada é persistido.
- Ao atualizar ou excluir, as quantidades previamente inseridas são revertidas antes de aplicar as novas.
- Se o estoque ficar com quantidade 0 após remoção/atualização, o `status_disponibilidade` passa para `I` (indisponível); caso contrário, permanece `D` (disponível).
- Sempre que necessário, o estoque é criado via `firstOrCreate` para garantir que exista um registro para o produto/unidade.

---

## Fluxo sugerido para o front

1. **Criar entrada** via `/entrada/add` com os itens informados.
2. **Listar entradas** via `/entrada/list` usando filtros para exibir histórico.
3. **Atualizar** pelo `/entrada/update` quando houver ajustes na nota ou nas quantidades.
4. **Excluir** via `/entrada/delete` quando for necessário reverter totalmente uma entrada.

Essa documentação cobre os parâmetros esperados, exemplos de resposta e as regras mais importantes que impactam o frontend.
