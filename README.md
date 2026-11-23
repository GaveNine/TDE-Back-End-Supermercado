# Sistema de Supermercado — PHP

Trabalho de Programação Back-End (CRUD de produtos, simulação de compra, baixa de estoque e desconto).

## Requisitos do trabalho × onde estão no código

| Requisito | Onde |
|---|---|
| CRUD de produtos (salvar, editar, deletar, listar) | `admin/produtos.php` (listar/excluir) e `admin/produto_form.php` (criar/editar) |
| Interface do cliente (simulação de compra) | `loja/index.php` (catálogo), `loja/carrinho.php` (carrinho), `loja/comprovante.php` |
| Baixa automática de estoque após a compra | `finalizar_compra()` em `loja/carrinho.php` (transação + `UPDATE ... WHERE estoque >= qtd`) |
| 5% de desconto em compras acima de R$ 200,00 | `calcular_totais()` em `src/helpers.php` (valores em `config.php`) |

Extras: histórico de vendas (`admin/vendas.php`), busca, proteção CSRF, escape de HTML (XSS), prepared statements (SQL injection) e valores em centavos (sem erro de ponto flutuante).

## Como rodar

Precisa do **PHP 8.1+** com a extensão `pdo_sqlite` (já vem no PHP padrão e no XAMPP).

```bash
cd supermercado
php -S localhost:8000
```

Abra http://localhost:8000. O banco SQLite e as tabelas são criados sozinhos na primeira execução, já com 12 produtos de exemplo.

### Usando MySQL (XAMPP/WAMP)

1. Crie o banco: `CREATE DATABASE supermercado CHARACTER SET utf8mb4;`
2. Em `config.php`, troque `'driver' => 'sqlite'` por `'mysql'` e ajuste usuário/senha.
3. As tabelas são criadas automaticamente.

## Estrutura

```
supermercado/
├── index.php              # página inicial
├── config.php             # banco e regra de desconto
├── admin/                 # CRUD de produtos e vendas
├── loja/                  # interface do cliente
├── src/                   # conexão, helpers, layout
└── assets/style.css
```

## Regra do desconto

- Subtotal **maior que** R$ 200,00 → 5% de desconto. Exatamente R$ 200,00 **não** tem desconto.
- Exemplo: subtotal R$ 250,00 → desconto R$ 12,50 → total R$ 237,50.

## Roteiro sugerido para o vídeo

1. Mostrar a lista de produtos e cadastrar um novo (Create).
2. Editar preço/estoque (Update) e excluir um produto (Delete).
3. Na loja, adicionar itens e mostrar o carrinho **sem** desconto (≤ R$ 200).
4. Aumentar o carrinho para passar de R$ 200 e mostrar o desconto de 5%.
5. Finalizar a compra, mostrar o comprovante e voltar em Produtos para ver o estoque reduzido.
6. Mostrar o histórico em Vendas.
7. Mostrar o código: `calcular_totais()` e `finalizar_compra()`.
