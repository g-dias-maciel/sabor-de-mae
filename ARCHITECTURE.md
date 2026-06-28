# 🍲 Sabor de Mãe — Architecture Document

> **Living Document** — última atualização: 2026-06-28
>
> Stack: **TALL** (Tailwind CSS v4, Alpine.js, Laravel 11, Livewire 3) + MySQL 8.4
>
> Este documento é a **Única Fonte de Verdade** do projeto. Deve ser atualizado a cada nova feature, migration, componente ou alteração de regra de negócio.

---

## 1. Visão Geral do Projeto

**Sabor de Mãe** é um e-commerce semanal de marmitas caseiras. Toda semana um novo cardápio é montado pela administradora ("a Mãe"), com refeições que variam por dia, saladas opcionais, pacotes semanais com desconto e extras. Clientes montam seus pedidos durante a semana e recebem no domingo (sem entregas no domingo — entregas ocorrem nos dias úteis).

### Atores
- **Admin (a Mãe):** gerencia produtos, cardápios semanais, acompanha pedidos, lista de compras e entregas
- **Cliente:** visualiza o cardápio, monta carrinho com variações de tamanho e salada, faz checkout com PIX ou dinheiro, acompanha seus pedidos, gerencia perfil e endereços

---

## 2. Regras de Negócio Cruciais

### Cardápio Semanal
- Cada cardápio cobre uma **semana completa** (segunda a domingo)
- Status do menu: `planejamento` → `aberto` → `encerrado`
- Só pode haver **1 menu aberto** por vez
- Pedidos são aceitos **até sábado 23:59**; domingo não aceita pedidos
- Ao abrir um novo menu, o anterior é automaticamente encerrado

### Refeições e Dias
- Cada refeição é vinculada a um **dia específico da semana** (1=Seg, 2=Ter, ..., 7=Dom) via pivot `menu_product.day_of_week`
- **Nomes dos produtos são limpos** (ex: "Carne de Panela", não "🥩 Segunda-feira — Carne de Panela") — o dia da semana e emoji são gerados automaticamente pelo cardápio a partir do `day_of_week` do pivot
- Pacotes semanais e extras ficam disponíveis **todos os dias** (`day_of_week = null`)

### Saladas
- Saladas são produtos com `type = 'salada'`
- Cada salada é vinculada a um dia da semana (como as refeições)
- O cliente pode **opcionalmente** adicionar uma salada à sua refeição (+ R$ 10,00)
- A salada escolhida aparece no nome do item do carrinho e nas notas do pedido

### Preços e Tamanhos
- Refeições têm tamanhos **P** (Pequena, ~500g, R$19) e **G** (Grande, ~750g, R$25)
- Pacote semanal: P (R$72) e G (R$95) — 4 almoços com ~5% de desconto
- Saladas e extras: tamanho único **M** (R$10)
- Preços são gerenciados na tabela `product_prices`, permitindo variação por tamanho

### Carrinho e Checkout
- Carrinho é estado local no Livewire (não persiste no banco), compartilhado via eventos
- Checkout com autenticação seamless: login ou cadastro durante a finalização
- Pagamento: **PIX via Mercado Pago** (QR code real, código copia-e-cola, confirmação automática via webhook e polling) ou **Dinheiro** (na entrega)
- Endereço pode ser salvo como padrão para próximos pedidos

### Entrega
- Zonas de entrega com taxa variável (Centro grátis, bairros com R$5–10)
- Status de entrega: `pendente` → `em_producao` → `saiu_para_entrega` → `entregue`
- Sem entregas no domingo

### Repetir Pedido
- Cliente pode repetir um pedido anterior (restaura o carrinho da semana atual)

### Limite de Estoque
- `product_prices.stock_limit`: limite opcional de unidades por tamanho/produto por semana
- Sistema verifica estoque disponível ao exibir tamanhos no cardápio

### Comando Automatizado
- `php artisan menu:fechar-semanal`: encerra menu atual e cria o próximo copiando os produtos

---

## 3. Mapeamento do Banco de Dados

### 3.1 Diagrama de Tabelas

```
users (1) ──────< addresses (N)
users (1) ──────< orders (N)
menus (1) ──────< orders (N)
menus (N) ──────< menu_product >── (N) products
orders (1) ─────< order_items (N) >── (1) products
products (1) ───< product_prices (N)
delivery_zones (1) ──< orders (N)
```

### 3.2 Estrutura das Tabelas

#### `users` (Laravel default + extensão)
| Coluna | Tipo | Notas |
|---|---|---|
| id | bigint unsigned | PK |
| name | varchar(255) | |
| email | varchar(255) | unique |
| phone | varchar(255) | nullable, adicionado por migration |
| address | varchar(255) | nullable (legado) |
| is_admin | boolean | default false |
| email_verified_at | timestamp | nullable |
| password | varchar(255) | |
| remember_token | varchar(100) | |
| timestamps | | created_at, updated_at |

#### `menus`
| Coluna | Tipo | Notas |
|---|---|---|
| id | bigint unsigned | PK |
| start_date | date | Segunda-feira da semana |
| end_date | date | Domingo da semana |
| status | enum | 'planejamento', 'aberto', 'encerrado' |
| timestamps | | |

#### `products`
| Coluna | Tipo | Notas |
|---|---|---|
| id | bigint unsigned | PK |
| name | varchar(255) | |
| description | text | nullable |
| type | varchar(255) | 'refeicao', 'salada', 'pacote_semanal', 'extra' |
| image_path | varchar(255) | nullable |
| is_available | boolean | default true |
| timestamps | | |

#### `product_prices`
| Coluna | Tipo | Notas |
|---|---|---|
| id | bigint unsigned | PK |
| product_id | bigint unsigned | FK → products |
| size | char(1) | 'P', 'M', 'G' |
| price | decimal(10,2) | |
| stock_limit | int | nullable, limite semanal |
| timestamps | | |
| unique | (product_id, size) | |

#### `menu_product` (pivot)
| Coluna | Tipo | Notas |
|---|---|---|
| id | bigint unsigned | PK |
| menu_id | bigint unsigned | FK → menus |
| product_id | bigint unsigned | FK → products |
| day_of_week | tinyint | nullable, 1=Seg..7=Dom, null=todos |
| timestamps | | |
| unique | (menu_id, product_id, day_of_week) | |

#### `orders`
| Coluna | Tipo | Notas |
|---|---|---|
| id | bigint unsigned | PK |
| user_id | bigint unsigned | FK → users |
| menu_id | bigint unsigned | FK → menus |
| total | decimal(10,2) | default 0 |
| payment_method | varchar(255) | 'pix', 'dinheiro' |
| payment_status | string | 'pendente', 'pago', 'cancelado' (MySQL: enum; SQLite: string) |
| gateway_transaction_id | varchar(255) | nullable, ID da transação no Mercado Pago |
| pix_qr_code | text | nullable, código PIX copia-e-cola |
| pix_copy_paste | text | nullable, mesmo código para compatibilidade |
| delivery_status | enum | 'pendente', 'em_producao', 'saiu_para_entrega', 'entregue' |
| delivery_address | text | nullable |
| delivery_zone_id | bigint unsigned | nullable, FK → delivery_zones |
| timestamps | | |

#### `order_items`
| Coluna | Tipo | Notas |
|---|---|---|
| id | bigint unsigned | PK |
| order_id | bigint unsigned | FK → orders |
| product_id | bigint unsigned | FK → products |
| quantity | int | default 1 |
| size | varchar(1) | nullable, 'P', 'M', 'G' |
| price_at_purchase | decimal(10,2) | preço no momento da compra |
| notes | text | nullable, inclui salada se selecionada |
| timestamps | | |

#### `delivery_zones`
| Coluna | Tipo | Notas |
|---|---|---|
| id | bigint unsigned | PK |
| neighborhood | varchar(255) | |
| fee | decimal(10,2) | default 0 |
| timestamps | | |

#### `addresses`
| Coluna | Tipo | Notas |
|---|---|---|
| id | bigint unsigned | PK |
| user_id | bigint unsigned | FK → users |
| street | varchar(255) | |
| number | varchar(20) | |
| complement | varchar(255) | nullable |
| neighborhood | varchar(255) | |
| city | varchar(255) | |
| zip_code | varchar(20) | |
| is_default | boolean | default false |
| timestamps | | |

#### Tabelas Laravel padrão:
- `password_reset_tokens` — (email PK, token, created_at)
- `failed_jobs` — (id, uuid, connection, queue, payload, exception, failed_at)
- `personal_access_tokens` — (id, tokenable morph, name, token, abilities, last_used_at, expires_at, timestamps)

---

## 4. Mapeamento de Componentes e Arquivos

### 4.1 Models (`app/Models/`)

| Arquivo | Tabela | Relacionamentos |
|---|---|---|
| `User.php` | users | hasMany: orders, addresses |
| `Menu.php` | menus | belongsToMany: products (pivot: day_of_week); hasMany: orders |
| `Product.php` | products | belongsToMany: menus; hasMany: prices, orderItems |
| `ProductPrice.php` | product_prices | belongsTo: product |
| `Order.php` | orders | belongsTo: user, menu, deliveryZone; hasMany: items |
| `OrderItem.php` | order_items | belongsTo: order, product |
| `DeliveryZone.php` | delivery_zones | hasMany: orders |
| `Address.php` | addresses | belongsTo: user |

### 4.2 Livewire — Área do Cliente (`app/Livewire/`)

| Componente | Rota | View | Função |
|---|---|---|---|
| `ProductList.php` | `/` (cardapio) | `livewire/product-list.blade.php` | Cardápio semanal, carrinho, seleção de tamanho e salada |
| `Cart.php` | (embed em todas páginas) | `livewire/cart.blade.php` | Carrinho flutuante, taxa de entrega |
| `Checkout.php` | `/checkout` | `livewire/checkout.blade.php` | Checkout com auth seamless, endereço, PIX via Mercado Pago (QR code real, copia-e-cola com Alpine.js, polling wire:poll.3s) |
| `CustomerOrders.php` | `/meus-pedidos` | `livewire/customer-orders.blade.php` | Histórico de pedidos, repetir pedido |
| `Profile.php` | `/perfil` | `livewire/profile.blade.php` | Editar perfil, senha, endereços |

### 4.3 Livewire — Painel Admin (`app/Livewire/Admin/`)

| Componente | Rota | View | Função |
|---|---|---|---|
| `Dashboard.php` | `/admin` | `livewire/admin/dashboard.blade.php` | KPIs: faturamento, marmitas, status menu |
| `ShoppingList.php` | `/admin/lista-compras` | `livewire/admin/shopping-list.blade.php` | Agregação de itens por produto/tamanho |
| `DeliveryReport.php` | `/admin/entregas` | `livewire/admin/delivery-report.blade.php` | Status de entrega dos pedidos |
| `MenuManager.php` | `/admin/cardapios` | `livewire/admin/menu-manager.blade.php` | CRUD de cardápios com dropdowns por dia da semana (refeição + salada por dia, pacote semanal, extras) |
| `ProductManager.php` | `/admin/produtos` | `livewire/admin/product-manager.blade.php` | Top 5 produtos mais pedidos + dropdown de seleção para editar qualquer produto, CRUD de produtos e preços |

### 4.4 Services (`app/Services/`)

| Arquivo | Função |
|---|---|
| `CheckoutService.php` | Transação de checkout: cria Order + OrderItems, calcula total |
| `MercadoPagoService.php` | Integração com API Mercado Pago: criação de pagamento PIX, construção de payload, idempotência |

### 4.5 Commands (`app/Console/Commands/`)

| Comando | Função |
|---|---|
| `FecharMenuSemanal.php` | `menu:fechar-semanal` — encerra menu atual, cria próximo copiando produtos |

### 4.6 Controllers (`app/Http/Controllers/`)

| Arquivo | Rotas |
|---|---|
| `Auth/LoginController.php` | GET/POST `/login`, POST `/logout` |
| `MercadoPagoWebhookController.php` | POST `/webhooks/mercadopago` (sem CSRF) — processa notificações de pagamento do Mercado Pago |

### 4.7 Layouts e Views

| Arquivo | Descrição |
|---|---|
| `resources/views/layouts/app.blade.php` | Layout público (navbar, footer, carrinho flutuante) |
| `resources/views/layouts/admin.blade.php` | Layout admin (navbar com links do painel) |
| `resources/views/auth/login.blade.php` | Tela de login |

### 4.8 Estilos (`resources/css/`)

| Arquivo | Conteúdo |
|---|---|
| `app.css` | Tailwind v4 `@theme` (cores, fontes), classes custom (`.card-artisan`, `.btn-terracotta`, `.input-warm`, `.title-hand`, `.navbar-warm`, `.footer-warm`, `.leaf-divider`, etc.) |

### 4.9 Configuração

| Arquivo | Descrição |
|---|---|
| `tailwind.config.js` | Cores (cream, terracotta, olive, brown), fontes (Caveat, Nunito) |
| `vite.config.js` | Laravel Vite plugin |
| `config/livewire.php` | Layout padrão `layouts.app`, namespace `App\Livewire` |
| `config/services.php` | Mercado Pago (`public_key`, `access_token`, `base_url` via env) |
| `.env.example` | `MERCADO_PAGO_PUBLIC_KEY`, `MERCADO_PAGO_ACCESS_TOKEN` |
| `phpunit.xml` | `MERCADO_PAGO_PUBLIC_KEY`, `MERCADO_PAGO_ACCESS_TOKEN` para testes |

### 4.10 Factories

| Arquivo | Model |
|---|---|
| `database/factories/UserFactory.php` | User (admin, cliente, unverified) |
| `database/factories/MenuFactory.php` | Menu (semanaAtual, aberto, encerrado, planejamento) |
| `database/factories/ProductFactory.php` | Product (withPrices, withSinglePrice, indisponivel) |
| `database/factories/ProductPriceFactory.php` | ProductPrice |
| `database/factories/OrderFactory.php` | Order |
| `database/factories/OrderItemFactory.php` | OrderItem |
| `database/factories/DeliveryZoneFactory.php` | DeliveryZone |
| `database/factories/AddressFactory.php` | Address |

### 4.11 Migrations (ordem cronológica)

| Arquivo | Tabela |
|---|---|
| `2014_10_12_000000_create_users_table` | users |
| `2014_10_12_100000_create_password_reset_tokens_table` | password_reset_tokens |
| `2019_08_19_000000_create_failed_jobs_table` | failed_jobs |
| `2019_12_14_000001_create_personal_access_tokens_table` | personal_access_tokens |
| `2024_01_01_000001_create_menus_table` | menus |
| `2024_01_01_000002_create_products_table` | products |
| `2024_01_01_000003_create_menu_product_table` | menu_product |
| `2024_01_01_000004_create_delivery_zones_table` | delivery_zones |
| `2024_01_01_000005_add_columns_to_users_table` | ALTER users (+phone, +address, +is_admin) |
| `2024_01_01_000006_create_orders_table` | orders |
| `2024_01_01_000007_create_order_items_table` | order_items |
| `2026_06_27_100001_create_product_prices_table` | product_prices |
| `2026_06_27_100003_create_addresses_table` | addresses |
| `2026_06_28_160143_add_gateway_fields_to_orders_table` | ALTER orders (+gateway_transaction_id, +pix_qr_code, +pix_copy_paste, MODIFY payment_status) |

## 5. Estado Atual do Desenvolvimento

### ✅ Implementado
- [x] Estrutura base Laravel + Livewire + Tailwind v4
- [x] Autenticação (login/logout) com controller
- [x] Models e relacionamentos (User, Menu, Product, Order, OrderItem, ProductPrice, DeliveryZone, Address)
- [x] Migrations — 14 tabelas/arquivos
- [x] DatabaseSeeder com dados realistas (6 produtos, 4 saladas, 4 zonas, 2 usuários)
- [x] Cardápio semanal com agrupamento por dia e tipo
- [x] Seleção de tamanho (P/G) com Alpine.js
- [x] Seleção opcional de salada por dia
- [x] Carrinho flutuante com taxa de entrega por bairro
- [x] Checkout com auth seamless (login/cadastro inline)
- [x] Pagamento PIX real via Mercado Pago (QR code + copia-e-cola + webhook + polling)
- [x] Histórico de pedidos do cliente
- [x] Repetir pedido anterior
- [x] Perfil do cliente: dados pessoais, senha, endereços
- [x] Admin Dashboard (KPIs de faturamento e marmitas)
- [x] Admin Lista de Compras (agregação por produto/tamanho)
- [x] Admin Relatório de Entregas (troca de status inline)
- [x] Admin Gerenciador de Cardápios (CRUD menus + vínculo produtos)
- [x] Admin Gerenciador de Produtos (CRUD produtos + preços)
- [x] Comando `menu:fechar-semanal` para rotação automática
- [x] Limite de estoque por produto/tamanho
- [x] Design system completo com classes CSS custom (card-artisan, btn-terracotta, etc.)
- [x] Responsivo mobile-first com Tailwind breakpoints

### ⬜ Pendente
- [ ] Testes automatizados — 18 arquivos de teste existem mas precisam ser revisados/ativados
- [x] Integração real com Mercado Pago PIX (API de pagamento)
- [ ] Upload de imagens dos produtos
- [ ] Notificações (email/WhatsApp) de confirmação de pedido
- [ ] Sistema de avaliação de pedidos
- [ ] Dashboard com gráficos e histórico de faturamento
- [ ] CRUD de zonas de entrega no admin
- [ ] Filtro por data no relatório de entregas e lista de compras
- [ ] Paginação nas listas do admin
- [x] Confirmação automática de pagamento PIX (webhook Mercado Pago + polling)
- [ ] Deploy e CI/CD
