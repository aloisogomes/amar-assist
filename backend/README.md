# Backend — Amar Assist

API REST Laravel 13 usada pelo SPA Vue. Expõe autenticação, CRUD de transações, importação de planilha e o dashboard (receitas, despesas e saldo).

Valores são persistidos em **centavos** (inteiro). A data usa `Y-m-d`. O tipo é o enum `income` | `expense`.

## Stack

| Tecnologia | Versão | Uso |
| --- | --- | --- |
| PHP | ^8.3 (imagem Docker 8.4) | Runtime |
| Laravel | 13 | API, filas, cache, eventos |
| MySQL | 8.4 | Persistência (utf8mb4) |
| Laravel Sanctum | 4 | Token Bearer (`auth:sanctum`) |
| Laravel Reverb | 1.11 | WebSockets da importação |
| Filas (`redis`) | — | Job `ProcessFinanceImport` |
| Redis | 7 | Cache, filas e sessões (`phpredis`) |
| Sessão (`redis`) | — | Sessões Laravel |
| Maatwebsite Excel | 4 | Template e importação `.xlsx`/`.csv` |
| Pest | 5 | Testes de feature |
| Laravel Pint | 1.32 | Formatação PHP |
| Laravel Boost | 2 | Ferramentas para agentes no Cursor |
| Scramble | 0.13 | Documentação OpenAPI (`/docs/api`) |

Arquitetura: **Controller → FormRequest → UseCase → Repository (contrato) → Eloquent**. Dados entram e saem por **DTOs** e **API Resources**.

## Endpoints (`/api`)

Públicos:

| Método | Rota | Função |
| --- | --- | --- |
| `POST` | `/register` | Cadastro |
| `POST` | `/login` | Login (throttle 5/min) |

Autenticados:

| Método | Rota | Função |
| --- | --- | --- |
| `GET` | `/user` | Usuário atual |
| `POST` | `/logout` | Revoga o token |
| `GET` | `/finances` | Lista paginada (`q`, `type`, `from`, `to`, `min_amount`, `max_amount`) |
| `POST` | `/finances` | Cria transação |
| `GET` | `/finances/{uuid}` | Detalhe |
| `PUT` | `/finances/{uuid}` | Atualiza |
| `DELETE` | `/finances/{uuid}` | Remove |
| `GET` | `/finances/dashboard` | Série e KPIs do período |
| `GET` | `/finances/template` | Download do modelo |
| `POST` | `/finances/import` | Enfileira a planilha |

Health da aplicação: `GET /up`.

Broadcast (prefixo `api`, Sanctum): progresso e conclusão da importação.

## Documentação OpenAPI

A UI interativa (Scramble) fica em [http://localhost:8000/docs/api](http://localhost:8000/docs/api). O spec JSON está em `/docs/api.json`.

As rotas autenticadas usam Bearer: faça login ou cadastro, copie o `token` e cole no Authorize da UI (`Authorization: Bearer {token}`). A documentação só é servida com `APP_ENV=local`.

## Estrutura

```text
app/
├── Dto/
├── Enums/                 # FinanceType
├── Events/                # import progress / completed
├── Exports/               # template Excel
├── Http/
│   ├── Controllers/
│   ├── Requests/
│   └── Resources/
├── Imports/
├── Jobs/                  # ProcessFinanceImport
├── Models/
├── Repositories/
│   └── Contracts/
├── Support/               # FinanceDashboardCache
└── UseCases/Finance/
```

O `FinanceRepositoryInterface` é ligado à implementação Eloquent no `AppServiceProvider`.

## Redis (cache, filas e sessões)

Cliente **phpredis**. No Docker, `REDIS_HOST=redis`; no host, `127.0.0.1:6379`.

| Recurso | Variável | Redis DB |
| --- | --- | --- |
| Cache do dashboard | `CACHE_STORE=redis` | **1** (`REDIS_CACHE_DB`, conexão `cache`) |
| Filas | `QUEUE_CONNECTION=redis` | **0** (`REDIS_DB`, conexão `default`) |
| Sessões | `SESSION_DRIVER=redis` | **0** (`REDIS_DB`, conexão `default`) |

Jobs falhos seguem em `failed_jobs` no MySQL.

O dashboard usa [`FinanceDashboardCache`](app/Support/FinanceDashboardCache.php):

- `Cache::remember()` grava série e KPIs por período com TTL de **600 s**
- Chave versionada `finances.dashboard.v{n}.{from}.{to}`
- Create, update, delete e import chamam `bump()` e invalidam o cache antigo

Pest no `phpunit.xml`: `CACHE_STORE=array`, `QUEUE_CONNECTION=sync`, `SESSION_DRIVER=array`.

```bash
php artisan cache:clear
php artisan queue:work
```

## Testes

Pest em `tests/Feature` (SQLite `:memory:`, cache `array`, fila `sync`). Coverage principal: auth, CRUD, filtros, dashboard e importação.

```bash
php artisan test
php artisan test --compact tests/Feature/Finance
```

No Docker da raiz: `sh docker/ci.sh`.

## Subir localmente

```bash
cp .env.example .env
# ajuste DB_* para o MySQL
# Redis em 127.0.0.1:6379 (cache, filas e sessões no .env.example)
composer install
php artisan key:generate
php artisan migrate
php artisan serve          # http://localhost:8000
```

Importação e WebSockets (outros terminais):

```bash
php artisan queue:work
php artisan reverb:start
```

## Variáveis relevantes (`.env`)

| Variável | Função |
| --- | --- |
| `APP_URL` | URL da API (`http://localhost:8000`) |
| `DB_*` | MySQL (`amar_assist`) |
| `CACHE_STORE` | `redis` (Pest: `array`) |
| `QUEUE_CONNECTION` | `redis` (Pest: `sync`) |
| `SESSION_DRIVER` | `redis` (Pest: `array`) |
| `REDIS_CLIENT` | `phpredis` |
| `REDIS_HOST` | `127.0.0.1` no host; `redis` no Docker |
| `REDIS_PORT` | `6379` |
| `REDIS_DB` | `0` (filas e sessões) |
| `REDIS_CACHE_DB` | `1` (cache) |
| `BROADCAST_CONNECTION` | `reverb` |
| `REVERB_APP_*` / `REVERB_HOST` / `REVERB_PORT` | Credenciais e bind do Reverb |

Para API + fila + Reverb + frontend juntos, use o Docker da [raiz do repositório](../README.md).
