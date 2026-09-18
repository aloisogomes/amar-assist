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
| Filas (`database`) | — | Job `ProcessFinanceImport` |
| Redis | 7 | Cache do dashboard (`CACHE_STORE=redis`, TTL 10 min) |
| Sessão (`database`) | — | Sessões Laravel |
| Maatwebsite Excel | 4 | Template e importação `.xlsx`/`.csv` |
| Pest | 5 | Testes de feature |
| Laravel Pint | 1.32 | Formatação PHP |
| Laravel Boost | 2 | Ferramentas para agentes no Cursor |

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

## Cache Redis

O dashboard usa [`FinanceDashboardCache`](app/Support/FinanceDashboardCache.php) no store padrão (`CACHE_STORE=redis`).

- `Cache::remember()` grava a série e os KPIs por período (`from`/`to`) com TTL de **600 s**
- A chave inclui uma versão (`finances.dashboard.v{n}.{from}.{to}`)
- Create, update, delete e import chamam `bump()`: incrementam `finances.dashboard.version` (`Cache::forever`) e as chaves antigas deixam de ser lidas
- Cliente **phpredis**; conexão `cache` no Redis DB **1** (`REDIS_CACHE_DB`)
- Sessão (`SESSION_DRIVER`) e fila (`QUEUE_CONNECTION`) continuam no **MySQL**
- Pest força `CACHE_STORE=array` no `phpunit.xml` (não precisa de Redis nos testes)

No Docker, `REDIS_HOST=redis`. Fora do Docker, `REDIS_HOST=127.0.0.1` e o Redis precisa estar na porta **6379** (o Compose publica essa porta).

```bash
php artisan cache:clear
```

## Testes

Pest em `tests/Feature` (SQLite `:memory:`, cache `array`). Coverage principal: auth, CRUD, filtros, dashboard e importação.

```bash
php artisan test
php artisan test --compact tests/Feature/Finance
```

No Docker da raiz: `sh docker/ci.sh`.

## Subir localmente

```bash
cp .env.example .env
# ajuste DB_* para o MySQL
# Redis em 127.0.0.1:6379 (CACHE_STORE=redis no .env.example)
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
| `REDIS_CLIENT` | `phpredis` |
| `REDIS_HOST` | `127.0.0.1` no host; `redis` no Docker |
| `REDIS_PORT` | `6379` |
| `REDIS_CACHE_DB` | `1` (store `cache`; o default usa DB `0`) |
| `QUEUE_CONNECTION` | `database` |
| `SESSION_DRIVER` | `database` |
| `BROADCAST_CONNECTION` | `reverb` |
| `REVERB_APP_*` / `REVERB_HOST` / `REVERB_PORT` | Credenciais e bind do Reverb |

Para API + fila + Reverb + frontend juntos, use o Docker da [raiz do repositório](../README.md).
