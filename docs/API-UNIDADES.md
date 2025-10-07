# 📘 Guia Rápido - API Unidades

## 🔗 Base URL

```
http://localhost:8000/api
```

---

## 📍 Rotas Disponíveis

### 1️⃣ **Criar Unidade**

```http
POST /unidades/add
```

**Body (JSON):**

```json
{
    "unidades": {
        "polo_id": 1,
        "nome": "Farmácia Central",
        "descricao": "Farmácia principal do hospital",
        "status": "A",
        "estoque": true,
        "tipo": "Medicamento"
    }
}
```

**Campos:**

-   `polo_id` ⚠️ **obrigatório** - ID do polo
-   `nome` ⚠️ **obrigatório** - Nome da unidade
-   `descricao` - Descrição (opcional)
-   `status` - `"A"` (Ativo) ou `"I"` (Inativo) - padrão: `"A"`
-   `estoque` - `true` ou `false` - padrão: `false`
-   `tipo` - `"Medicamento"` ou `"Material"` - padrão: `"Material"`

**Response (Sucesso - 200):**

```json
{
    "status": true,
    "data": {
        "id": 1,
        "polo_id": 1,
        "nome": "FARMÁCIA CENTRAL",
        "descricao": "Farmácia principal do hospital",
        "status": "A",
        "estoque": true,
        "tipo": "Medicamento",
        "created_at": "2025-10-06T...",
        "updated_at": "2025-10-06T..."
    }
}
```

**Response (Erro - 422):**

```json
{
    "status": false,
    "validacao": true,
    "erros": {
        "polo_id": ["O campo polo id é obrigatório."]
    }
}
```

---

### 2️⃣ **Atualizar Unidade**

```http
POST /unidades/update
```

**Body (JSON):**

```json
{
    "unidades": {
        "id": 1,
        "polo_id": 1,
        "nome": "Farmácia Central Atualizada",
        "descricao": "Nova descrição",
        "status": "A",
        "estoque": true,
        "tipo": "Medicamento"
    }
}
```

**Campos:** Mesmos do `add` + `id` obrigatório

**Response (Sucesso - 200):**

```json
{
    "status": true,
    "data": {
        "id": 1,
        "polo_id": 1,
        "nome": "FARMÁCIA CENTRAL ATUALIZADA",
        "status": "A",
        "estoque": true,
        "tipo": "Medicamento"
    }
}
```

**Response (Erro - 404):**

```json
{
    "status": false,
    "message": "Unidade não encontrada."
}
```

---

### 3️⃣ **Listar Todas as Unidades**

```http
POST /unidades/list
```

**Body (JSON) - Sem filtros:**

```json
{}
```

**Body (JSON) - Com filtros:**

```json
{
    "filters": [
        { "tipo": "Medicamento" },
        { "status": "A" },
        { "estoque": true }
    ]
}
```

**Body (JSON) - Com paginação:**

```json
{
    "paginate": true,
    "per_page": 20
}
```

**Body (JSON) - Filtros + Paginação:**

```json
{
    "filters": [{ "tipo": "Medicamento" }, { "status": "A" }],
    "paginate": true,
    "per_page": 15
}
```

**Response (Sucesso - 200):**

```json
{
    "status": true,
    "data": [
        {
            "id": 1,
            "polo_id": 1,
            "nome": "FARMÁCIA CENTRAL",
            "descricao": "Farmácia principal",
            "status": "A",
            "estoque": true,
            "tipo": "Medicamento",
            "polo": {
                "id": 1,
                "nome": "Hospital Geral",
                "status": "A"
            }
        },
        {
            "id": 2,
            "polo_id": 2,
            "nome": "ALMOXARIFADO GERAL",
            "descricao": "Almoxarifado de materiais",
            "status": "A",
            "estoque": true,
            "tipo": "Material",
            "polo": {
                "id": 2,
                "nome": "Hospital Afrânio Peixoto",
                "status": "A"
            }
        }
    ]
}
```

**Response com Paginação:**

```json
{
  "status": true,
  "data": {
    "current_page": 1,
    "data": [...],
    "first_page_url": "http://localhost:8000/api/unidades/list?page=1",
    "from": 1,
    "last_page": 3,
    "last_page_url": "http://localhost:8000/api/unidades/list?page=3",
    "next_page_url": "http://localhost:8000/api/unidades/list?page=2",
    "path": "http://localhost:8000/api/unidades/list",
    "per_page": 20,
    "prev_page_url": null,
    "to": 20,
    "total": 45
  }
}
```

---

### 4️⃣ **Obter Uma Unidade Específica**

```http
POST /unidades/listData
```

**Body (JSON):**

```json
{
    "id": 1
}
```

**Response (Sucesso - 200):**

```json
{
    "status": true,
    "data": {
        "id": 1,
        "polo_id": 1,
        "nome": "FARMÁCIA CENTRAL",
        "descricao": "Farmácia principal do hospital",
        "status": "A",
        "estoque": true,
        "tipo": "Medicamento",
        "created_at": "2025-10-06T10:30:00.000000Z",
        "updated_at": "2025-10-06T10:30:00.000000Z",
        "polo": {
            "id": 1,
            "nome": "Hospital Geral",
            "status": "A",
            "created_at": "2025-10-06T10:00:00.000000Z",
            "updated_at": "2025-10-06T10:00:00.000000Z"
        }
    }
}
```

**Response (Erro - 404):**

```json
{
    "status": false,
    "message": "Unidade não encontrada."
}
```

---

### 5️⃣ **Deletar Unidade**

```http
POST /unidades/delete/{id}
```

**Exemplo:**

```http
POST /unidades/delete/1
```

**Sem body necessário**

**Response (Sucesso - 200):**

```json
{
    "status": true,
    "message": "Unidade excluída com sucesso."
}
```

**Response (Erro - 404):**

```json
{
    "status": false,
    "message": "Unidade não encontrada."
}
```

**Response (Erro - 422 - Tem vínculos):**

```json
{
    "status": false,
    "message": "Não é possível excluir esta unidade pois ela possui registros relacionados no sistema.",
    "references": [
        "estoque (15 itens)",
        "movimentações de origem (3)",
        "movimentações de destino (7)"
    ]
}
```

---

### 6️⃣ **Alternar Status (A ↔ I)** ⭐ NOVO

```http
POST /unidades/toggleStatus
```

**Body (JSON):**

```json
{
    "id": 1
}
```

**Response (Sucesso - 200):**

```json
{
    "status": true,
    "data": {
        "id": 1,
        "polo_id": 1,
        "nome": "FARMÁCIA CENTRAL",
        "descricao": "...",
        "status": "I",
        "estoque": true,
        "tipo": "Medicamento",
        "created_at": "2025-10-06T10:30:00.000000Z",
        "updated_at": "2025-10-06T15:45:00.000000Z"
    },
    "message": "Status atualizado com sucesso"
}
```

**Response (Erro - 400):**

```json
{
    "status": false,
    "message": "ID da unidade não fornecido"
}
```

**Response (Erro - 404):**

```json
{
    "status": false,
    "message": "Unidade não encontrada"
}
```

---

## 🎯 Validações

| Campo       | Regras                                     |
| ----------- | ------------------------------------------ |
| `polo_id`   | Obrigatório, deve existir na tabela `polo` |
| `nome`      | Obrigatório, máx 255 caracteres            |
| `tipo`      | Apenas `"Medicamento"` ou `"Material"`     |
| `status`    | Apenas `"A"` ou `"I"`                      |
| `estoque`   | Booleano (`true`/`false`)                  |
| `descricao` | Opcional, texto livre                      |

---

## ⚠️ Regras de Negócio

### 1. **Proteção de Delete**

Não pode deletar unidade que possui:

-   ❌ Itens no estoque
-   ❌ Movimentações como origem
-   ❌ Movimentações como destino

### 2. **Auto-conversão**

-   Nome é convertido automaticamente para **MAIÚSCULAS**
-   Exemplo: `"farmácia"` → `"FARMÁCIA"`

### 3. **Tipo Importante**

-   Define quais produtos aparecem no estoque da unidade
-   `tipo: "Medicamento"` → apenas produtos de grupos tipo "Medicamento"
-   `tipo: "Material"` → apenas produtos de grupos tipo "Material"

### 4. **Flag Estoque**

-   `estoque: true` → Unidade controla estoque (Observer cria registros automaticamente)
-   `estoque: false` → Unidade não controla estoque

### 5. **Relacionamento com Polo**

-   Sempre retorna dados do polo junto (`eager loading`)
-   Polo deve existir e estar ativo

---

## 📦 Exemplos de Uso

### JavaScript (Axios)

```javascript
import axios from "axios";

const API_URL = "http://localhost:8000/api";

// ✅ Criar unidade
const criarUnidade = async () => {
    try {
        const response = await axios.post(`${API_URL}/unidades/add`, {
            unidades: {
                polo_id: 1,
                nome: "Farmácia Central",
                estoque: true,
                tipo: "Medicamento",
            },
        });

        console.log("✅ Unidade criada:", response.data.data);
    } catch (error) {
        console.error("❌ Erro:", error.response.data);
    }
};

// ✅ Listar com filtros
const listarUnidades = async () => {
    const response = await axios.post(`${API_URL}/unidades/list`, {
        filters: [{ tipo: "Medicamento" }, { status: "A" }],
        paginate: true,
        per_page: 20,
    });

    return response.data.data;
};

// ✅ Obter uma unidade
const obterUnidade = async (id) => {
    const response = await axios.post(`${API_URL}/unidades/listData`, {
        id: id,
    });

    return response.data.data;
};

// ✅ Atualizar unidade
const atualizarUnidade = async (id) => {
    const response = await axios.post(`${API_URL}/unidades/update`, {
        unidades: {
            id: id,
            polo_id: 1,
            nome: "Farmácia Central Atualizada",
            status: "A",
            estoque: true,
            tipo: "Medicamento",
        },
    });

    return response.data.data;
};

// ✅ Toggle status (A ↔ I)
const toggleStatus = async (id) => {
    const response = await axios.post(`${API_URL}/unidades/toggleStatus`, {
        id: id,
    });

    console.log("✅ Status alterado:", response.data.data.status);
};

// ✅ Deletar unidade
const deletarUnidade = async (id) => {
    try {
        const response = await axios.post(`${API_URL}/unidades/delete/${id}`);
        console.log("✅ Unidade deletada");
    } catch (error) {
        if (error.response.status === 422) {
            console.error(
                "❌ Unidade possui vínculos:",
                error.response.data.references
            );
        }
    }
};
```

---

### React Hook (Exemplo)

```jsx
import { useState, useEffect } from "react";
import axios from "axios";

const useUnidades = () => {
    const [unidades, setUnidades] = useState([]);
    const [loading, setLoading] = useState(false);

    const listar = async (filtros = {}) => {
        setLoading(true);
        try {
            const response = await axios.post("/api/unidades/list", {
                filters: filtros.filters || [],
                paginate: filtros.paginate,
                per_page: filtros.per_page || 50,
            });
            setUnidades(response.data.data);
        } catch (error) {
            console.error("Erro ao listar unidades:", error);
        } finally {
            setLoading(false);
        }
    };

    const criar = async (dadosUnidade) => {
        const response = await axios.post("/api/unidades/add", {
            unidades: dadosUnidade,
        });
        return response.data.data;
    };

    const atualizar = async (id, dadosUnidade) => {
        const response = await axios.post("/api/unidades/update", {
            unidades: { ...dadosUnidade, id },
        });
        return response.data.data;
    };

    const toggleStatus = async (id) => {
        const response = await axios.post("/api/unidades/toggleStatus", { id });
        return response.data.data;
    };

    const deletar = async (id) => {
        await axios.post(`/api/unidades/delete/${id}`);
    };

    return {
        unidades,
        loading,
        listar,
        criar,
        atualizar,
        toggleStatus,
        deletar,
    };
};

export default useUnidades;
```

---

## ✅ Status HTTP

| Código  | Significado                                 |
| ------- | ------------------------------------------- |
| **200** | ✅ Sucesso                                  |
| **400** | ⚠️ Requisição inválida (falta parâmetro)    |
| **404** | ❌ Unidade não encontrada                   |
| **422** | ⚠️ Erro de validação ou vínculos existentes |
| **500** | 🔥 Erro interno do servidor                 |

---

## 🎨 Padrão de Response

### ✅ Sucesso

```json
{
  "status": true,
  "data": { ... },
  "message": "Mensagem opcional"
}
```

### ❌ Erro

```json
{
    "status": false,
    "message": "Descrição do erro"
}
```

### ⚠️ Erro de Validação

```json
{
    "status": false,
    "validacao": true,
    "erros": {
        "campo": ["Mensagem de erro"]
    }
}
```

---

## 🔍 Filtros Disponíveis

Os filtros funcionam como **AND** (todas as condições devem ser verdadeiras):

```json
{
    "filters": [
        { "tipo": "Medicamento" },
        { "status": "A" },
        { "estoque": true },
        { "polo_id": 1 }
    ]
}
```

**Campos filtráveis:**

-   `id`
-   `polo_id`
-   `nome`
-   `status`
-   `estoque`
-   `tipo`

---

## 📊 Tipos de Unidade

| Tipo          | Descrição                            | Produtos Permitidos                          |
| ------------- | ------------------------------------ | -------------------------------------------- |
| `Medicamento` | Unidade de medicamentos (Farmácias)  | Apenas produtos de grupos tipo "Medicamento" |
| `Material`    | Unidade de materiais (Almoxarifados) | Apenas produtos de grupos tipo "Material"    |

---

## 💡 Dicas

1. **Sempre valide o response** - Verifique `status: true` antes de processar
2. **Use eager loading** - A API já retorna o polo junto (otimizado)
3. **Trate erros 422** - Podem indicar validações ou vínculos
4. **Toggle é mais simples** - Use em vez de update só para status
5. **Paginação recomendada** - Use para listas grandes (>50 itens)

---

## 🐛 Troubleshooting

### Erro: "polo_id é obrigatório"

```json
// ❌ Errado
{ "unidades": { "nome": "Teste" } }

// ✅ Correto
{ "unidades": { "polo_id": 1, "nome": "Teste" } }
```

### Erro 422 ao deletar

```json
// Verifique os vínculos em response.data.references
// Remova estoque e movimentações antes de deletar
```

---

## 📞 Suporte

-   **Backend:** Laravel 8
-   **Auth:** Sanctum (maioria das rotas não requer auth)
-   **Documentação:** `/docs/API-UNIDADES.md`
