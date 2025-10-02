# 📚 Documentação de Rotas API - ProGest Backend

**Base URL:** `http://localhost:8000/api`

**Formato de Response Padrão:**

```json
// Sucesso
{
  "status": true,
  "data": {...},
  "message": "Mensagem opcional"
}

// Erro
{
  "status": false,
  "message": "Descrição do erro",
  "errors": {...} // opcional, para erros de validação
}
```

---

## 🔐 Autenticação

### Login

**POST** `/login`

```json
Request:
{
  "email": "usuario@example.com",
  "password": "senha123"
}

Response:
{
  "user": {...},
  "token": "token-sanctum"
}
```

### Registro

**POST** `/register`

```json
Request:
{
  "name": "Nome do Usuário",
  "email": "usuario@example.com",
  "password": "senha123",
  "password_confirmation": "senha123"
}

Response:
{
  "message": "Usuário registrado com sucesso!",
  "token": "token-sanctum"
}
```

### Logout

**POST** `/logout`

```json
Response:
{
  "message": "Logout realizado com sucesso!"
}
```

### Obter Usuário Autenticado

**GET** `/user` (requer autenticação: `auth:sanctum`)

```json
Response: User object
```

---

## 👥 Usuários

### Criar Usuário

**POST** `/user/add`

```json
Request:
{
  "name": "Nome do Usuário",
  "email": "usuario@example.com",
  "password": "senha123",
  // outros campos conforme necessário
}
```

### Atualizar Usuário

**POST** `/user/update`

```json
Request:
{
  "id": 1,
  "name": "Nome Atualizado",
  "email": "novoemail@example.com",
  // outros campos
}
```

### Listar Usuários

**POST** `/user/list`

```json
Request:
{
  "per_page": 15,
  "filters": [
    {
      "name": "João",
      "status": "A"
    }
  ]
}

Response:
{
  "status": true,
  "data": {
    "current_page": 1,
    "data": [...],
    "total": 50,
    "per_page": 15
  }
}
```

### Obter Usuário Específico

**POST** `/user/listData`

```json
Request:
{
  "id": 1
}
```

### Deletar Usuário

**POST** `/user/delete/{id}`

---

## 🏥 Polo

### Criar Polo

**POST** `/polo/add`

```json
Request:
{
  "nome": "Polo Norte",
  "status": "A" // A = Ativo, I = Inativo (opcional, default: A)
}
```

### Atualizar Polo

**POST** `/polo/update`

```json
Request:
{
  "id": 1,
  "nome": "Polo Norte Atualizado",
  "status": "A"
}
```

### Listar Polos

**POST** `/polo/list`

```json
Request:
{
  "per_page": 15,
  "filters": [
    {
      "nome": "Norte",
      "status": "A"
    }
  ]
}
```

### Obter Polo Específico

**POST** `/polo/listData`

```json
Request:
{
  "id": 1
}
```

### Deletar Polo

**POST** `/polo/delete/{id}`

> ⚠️ Não permite deletar se houver unidades vinculadas

### Alternar Status do Polo

**POST** `/polo/toggleStatus`

```json
Request:
{
  "id": 1
}
```

---

## 🏢 Unidades

### Criar Unidade

**POST** `/unidades/add`

```json
Request:
{
  "polo_id": 1,
  "nome": "Farmácia Central",
  "codigo_unidade": "FC001",
  "descricao": "Descrição da unidade",
  "status": "A", // A = Ativo, I = Inativo
  "estoque": true, // boolean
  "tipo": "Medicamento" // Medicamento, Material, Medicamento_Material
}
```

### Atualizar Unidade

**POST** `/unidades/update`

```json
Request:
{
  "id": 1,
  "polo_id": 1,
  "nome": "Farmácia Central Atualizada",
  "codigo_unidade": "FC001",
  "descricao": "Nova descrição",
  "status": "A",
  "estoque": true,
  "tipo": "Medicamento_Material"
}
```

### Listar Unidades

**POST** `/unidades/list`

```json
Request:
{
  "per_page": 15,
  "filters": [
    {
      "nome": "Farmácia",
      "tipo": "Medicamento",
      "status": "A"
    }
  ]
}
```

### Obter Unidade Específica

**POST** `/unidades/listData`

```json
Request:
{
  "id": 1
}
```

### Deletar Unidade

**POST** `/unidades/delete/{id}`

---

## 🔗 Tipo Vínculo

### Criar Tipo Vínculo

**POST** `/tipoVinculo/add`

### Atualizar Tipo Vínculo

**POST** `/tipoVinculo/update`

### Listar Tipos Vínculo

**POST** `/tipoVinculo/list`

### Obter Tipo Vínculo Específico

**POST** `/tipoVinculo/listData`

### Deletar Tipo Vínculo

**POST** `/tipoVinculo/delete/{id}`

---

## 📦 Unidade de Medida

### Criar Unidade de Medida

**POST** `/unidadeMedida/add`

```json
Request:
{
  "nome": "Caixa",
  // outros campos conforme model
}
```

### Atualizar Unidade de Medida

**POST** `/unidadeMedida/update`

### Listar Unidades de Medida

**POST** `/unidadeMedida/list`

### Obter Unidade de Medida Específica

**POST** `/unidadeMedida/listData`

### Deletar Unidade de Medida

**POST** `/unidadeMedida/delete/{id}`

---

## 📊 Grupo Produto

### Criar Grupo Produto

**POST** `/grupoProduto/add`

```json
Request:
{
  "nome": "Antibióticos",
  "tipo": "Medicamento", // Medicamento ou Material
  "status": "A"
}
```

### Atualizar Grupo Produto

**POST** `/grupoProduto/update`

### Listar Grupos Produto

**POST** `/grupoProduto/list`

### Obter Grupo Produto Específico

**POST** `/grupoProduto/listData`

### Deletar Grupo Produto

**POST** `/grupoProduto/delete/{id}`

---

## 🏭 Fornecedores

### Criar Fornecedor

**POST** `/fornecedores/add`

```json
Request:
{
  "nome": "Fornecedor XYZ",
  "cnpj": "12.345.678/0001-90",
  "razao_social": "XYZ LTDA",
  "tipo_pessoa": "J", // J = Jurídica, F = Física
  "status": "A"
  // outros campos conforme model
}
```

### Atualizar Fornecedor

**POST** `/fornecedores/update`

### Listar Fornecedores

**POST** `/fornecedores/list`

### Obter Fornecedor Específico

**POST** `/fornecedores/listData`

### Deletar Fornecedor

**POST** `/fornecedores/delete`

### Alternar Status do Fornecedor

**POST** `/fornecedores/toggleStatus`

```json
Request:
{
  "id": 1
}
```

---

## 💊 Produtos

### Criar Produto

**POST** `/produtos/add`

```json
Request:
{
  "nome": "Paracetamol 500mg",
  "marca": "Marca XYZ",
  "codigo_simpras": "123456",
  "codigo_barras": "7891234567890",
  "grupo_produto_id": 1,
  "unidade_medida_id": 1,
  "status": "A"
}
```

### Atualizar Produto

**POST** `/produtos/update`

```json
Request:
{
  "id": 1,
  "nome": "Paracetamol 500mg Atualizado",
  "marca": "Nova Marca",
  "codigo_simpras": "123456",
  "codigo_barras": "7891234567890",
  "grupo_produto_id": 1,
  "unidade_medida_id": 1,
  "status": "A"
}
```

### Listar Produtos

**POST** `/produtos/list`

```json
Request:
{
  "per_page": 15,
  "filters": [
    {
      "nome": "Paracetamol",
      "grupo_produto_id": 1,
      "status": "A"
    }
  ]
}
```

### Obter Produto Específico

**POST** `/produtos/listData`

```json
Request:
{
  "id": 1
}
```

### Deletar Produto

**POST** `/produtos/delete`

### Alternar Status do Produto

**POST** `/produtos/toggleStatus`

```json
Request:
{
  "id": 1
}
```

### Obter Dados Auxiliares (Grupos e Unidades de Medida)

**POST** `/produtos/dadosAuxiliares`

```json
Response:
{
  "status": true,
  "data": {
    "grupos_produto": [...],
    "unidades_medida": [...]
  }
}
```

---

## 📦 Estoque

### Criar Estoque (Compatibilidade)

**POST** `/estoque/add`

### Atualizar Estoque (Compatibilidade)

**POST** `/estoque/update`

### Listar Estoque (Compatibilidade)

**POST** `/estoque/list`

### Obter Estoque Específico (Compatibilidade)

**POST** `/estoque/listData`

### Deletar Estoque (Compatibilidade)

**POST** `/estoque/delete/{id}`

### Listar Estoque por Unidade

**GET** `/estoque/unidade/{unidadeId}`

```json
Response:
{
  "success": true,
  "data": [
    {
      "estoque_id": 1,
      "quantidade_atual": 100,
      "quantidade_minima": 10,
      "status_disponibilidade": "D",
      "status_disponibilidade_texto": "Disponível",
      "abaixo_minimo": false,
      "produto": {
        "id": 1,
        "nome": "Paracetamol 500mg",
        "nome_completo": "Paracetamol 500mg - Marca XYZ",
        "marca": "Marca XYZ",
        "codigo_simpras": "123456",
        "codigo_barras": "7891234567890",
        "status": "A",
        "grupo_produto": {...},
        "unidade_medida": {...}
      }
    }
  ]
}
```

### Obter Estoque por ID

**GET** `/estoque/{id}`

### Atualizar Quantidade Mínima

**PUT** `/estoque/{id}/quantidade-minima`

```json
Request:
{
  "quantidade_minima": 20
}
```

### Atualizar Status do Estoque

**PUT** `/estoque/{id}/status`

```json
Request:
{
  "status_disponibilidade": "D" // D = Disponível, I = Indisponível
}
```

---

## 🏥 Paciente

### Criar Paciente

**POST** `/paciente/add`

```json
Request:
{
  "nome": "João da Silva",
  "cpf": "123.456.789-00",
  "prontuario": "P001234" // opcional
}
```

### Atualizar Paciente

**POST** `/paciente/update`

```json
Request:
{
  "id": 1,
  "nome": "João da Silva Atualizado",
  "cpf": "123.456.789-00",
  "prontuario": "P001234"
}
```

### Listar Pacientes

**POST** `/paciente/list`

```json
Request:
{
  "per_page": 15,
  "filters": [
    {
      "nome": "João",
      "cpf": "123.456"
    }
  ]
}
```

### Obter Paciente Específico

**POST** `/paciente/listData`

```json
Request:
{
  "id": 1
}
```

### Deletar Paciente

**POST** `/paciente/delete/{id}`

> ⚠️ Não permite deletar se houver movimentações vinculadas

---

## 📝 Observações Importantes

### Padrão de Filtros

Todas as rotas de listagem (`/list`) aceitam filtros no formato:

```json
{
    "per_page": 15,
    "filters": [
        {
            "campo1": "valor1",
            "campo2": "valor2"
        }
    ]
}
```

### Status Enums

-   **Geral (Ativo/Inativo):** `'A'` = Ativo, `'I'` = Inativo
-   **Disponibilidade Estoque:** `'D'` = Disponível, `'I'` = Indisponível

### Tipos de Unidade

-   `'Medicamento'`
-   `'Material'`
-   `'Medicamento_Material'`

### Headers Necessários

```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {token} // quando autenticação for necessária
```

### Códigos de Status HTTP

-   `200` - Sucesso
-   `201` - Criado com sucesso
-   `400` - Erro de validação
-   `404` - Recurso não encontrado
-   `500` - Erro interno do servidor

---

**Última atualização:** 02/10/2025
