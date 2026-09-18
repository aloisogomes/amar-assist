# Amar Assist

Painel para gerenciar as finanças de um negócio: receitas, despesas, importação em planilha e um dashboard com o saldo do período.

A aplicação é um **SPA Vue 3** que consome uma **API Laravel 13**. Os valores são guardados em **centavos** no banco e exibidos em **reais (BRL)** na interface.

## Stack

### Backend (`backend/`)

| Tecnologia | Versão | Uso |
| --- | --- | --- |
| PHP | ^8.3 (Docker 8.4) | Runtime |
| Laravel | 13 | API REST |
| Laravel Sanctum | 4 | Token Bearer |
| MySQL | 8.4 | Persistência (valores em centavos) |
| Laravel Reverb | 1.11 | WebSockets da importação |
| Filas (`database`) | — | Job de importação |
| Redis | 7 | Cache do dashboard (TTL 10 min) |
| Sessão (`database`) | — | Sessões Laravel |
| Maatwebsite Excel | 4 | Template e importação `.xlsx`/`.csv` |
| Pest | 5 | Testes de feature |

Arquitetura **Controller → FormRequest → UseCase → Repository → Eloquent**, com DTOs e API Resources. Detalhes, rotas e setup: [backend/README.md](backend/README.md).

### Frontend (`frontend/`)

| Tecnologia | Uso |
| --- | --- |
| Vue 3 + TypeScript | SPA |
| Vite 8 | Build e servidor de desenvolvimento |
| Vue Router + Pinia | Rotas e estado |
| Tailwind CSS 4 + shadcn-vue | Interface |
| Unovis | Gráfico do dashboard |
| Laravel Echo + Reverb | Atualização em tempo real da importação |

O Vite encaminha `/api` para o Laravel, então o navegador fala só com a porta do frontend.

## O que o sistema faz

- Cadastro e login
- Listagem de transações com busca e filtros (tipo, período, valor)
- Cadastro e edição com validação em português
- Importação em lote por planilha (fila + progresso via WebSocket)
- Dashboard com gráfico de receitas x despesas e KPIs do período

## Estrutura

```text
amar-assist/
├── .github/workflows/ci.yml   # pipeline de testes
├── backend/                   # API Laravel
├── frontend/                  # SPA Vue
├── docker/                    # entrypoints e script de CI
├── Dockerfile
└── docker-compose.yml
```

## Requisitos

- [Docker](https://docs.docker.com/get-docker/) e [Docker Compose](https://docs.docker.com/compose/)

Não é necessário instalar PHP, Node, MySQL ou Redis no host.

## Subir com Docker

Na raiz do repositório:

```bash
docker compose up --build
```

Na primeira execução o backend instala as dependências, gera a `APP_KEY` se ela não existir e roda as migrations.

Quando os serviços estiverem prontos:

| Serviço | URL / porta | Função |
| --- | --- | --- |
| Frontend | http://localhost:5173 | Interface |
| API | http://localhost:8000 | Laravel (`/api`, health em `/up`) |
| Reverb | ws://localhost:8080 | WebSockets |
| MySQL | `localhost:3306` | Banco `amar_assist` |
| Redis | `localhost:6379` | Cache (`CACHE_STORE=redis`) |

Crie uma conta em http://localhost:5173/register e use o painel.

Para rodar em segundo plano:

```bash
docker compose up --build -d
```

### Credenciais do MySQL no Docker

| Variável | Valor |
| --- | --- |
| Host | `mysql` (dentro da rede Docker) ou `127.0.0.1` (no host) |
| Porta | `3306` |
| Banco | `amar_assist` |
| Usuário | `amar` |
| Senha | `secret` |

Se a porta **3306** já estiver em uso no host, altere o mapeamento em `docker-compose.yml` de `"3306:3306"` para `"3307:3306"`. O mesmo vale para **6379** do Redis (`"6380:6379"`).

### Cache Redis

O dashboard (`GET /api/finances/dashboard`) guarda a série e os KPIs no Redis (`CACHE_STORE=redis`, cliente **phpredis**).

- TTL de **10 minutos** por período (`from`/`to`)
- Chave versionada (`finances.dashboard.v{n}.{from}.{to}`); create, update, delete e import incrementam a versão e invalidam o cache antigo
- Sessão e fila **não** usam Redis: continuam no MySQL
- Pest no CI usa cache `array` e não sobe o Redis

No Compose, `REDIS_HOST=redis`. Sem senha. Banco lógico do cache: `REDIS_CACHE_DB=1`.

| Variável | Valor no Docker |
| --- | --- |
| Host | `redis` (rede interna) ou `127.0.0.1` (no host, porta 6379) |
| Porta | `6379` |
| Cliente | `phpredis` |
| `CACHE_STORE` | `redis` |

Limpar o cache da API:

```bash
docker compose exec backend php artisan cache:clear
```

### Comandos úteis

```bash
# logs
docker compose logs -f

# logs de um serviço
docker compose logs -f backend

# Artisan no container
docker compose exec backend php artisan migrate
docker compose exec backend php artisan tinker

# parar
docker compose down

# parar e apagar volumes (MySQL e Redis)
docker compose down -v
```

### Serviços do Compose

| Serviço | Função |
| --- | --- |
| `mysql` | Banco de dados |
| `redis` | Cache Redis |
| `backend` | API (`php artisan serve`) |
| `queue` | Worker da importação |
| `reverb` | Servidor WebSocket |
| `frontend` | Vite com hot reload |
| `test-backend` | Pest da API (perfil `ci`) |
| `test-frontend` | `vue-tsc` do SPA (perfil `ci`) |

O código de `backend/` e `frontend/` é montado nos containers. Alterações no host refletem no ambiente Docker.

## Testes e CI/CD

Os testes da API são **Pest** (SQLite em memória). O frontend é verificado com **vue-tsc**. Os dois rodam dentro do Docker, no mesmo ambiente da aplicação.

### Rodar localmente

Na raiz do repositório:

```bash
sh docker/ci.sh
```

O script constrói as imagens do perfil `ci` e executa, nesta ordem:

1. `php artisan test --compact` no serviço `test-backend`
2. `npm run typecheck` no serviço `test-frontend`

Equivalente, serviço a serviço:

```bash
docker compose --profile ci run --rm --no-deps test-backend
docker compose --profile ci run --rm --no-deps test-frontend
```

O perfil `ci` não sobe MySQL, Redis, fila nem Reverb: o Pest usa SQLite e cache `array`.

### Pipeline no GitHub Actions

O workflow [`.github/workflows/ci.yml`](.github/workflows/ci.yml) dispara em **push** e **pull request** para `main`/`master` e chama `docker/ci.sh`.

Para o CI passar no GitHub, o repositório precisa ter o Actions habilitado. O status aparece na aba **Actions** e no pull request.

## Desenvolvimento sem Docker

**Backend**

```bash
cd backend
cp .env.example .env
# ajuste DB_* para o MySQL local e suba o Redis (porta 6379)
# CACHE_STORE=redis e REDIS_HOST=127.0.0.1 (já estão no .env.example)
composer install
php artisan key:generate
php artisan migrate
php artisan serve
```

Em outro terminal, para importação e WebSockets:

```bash
cd backend
php artisan queue:work
php artisan reverb:start
```

**Frontend**

```bash
cd frontend
cp .env.example .env.local
npm install
npm run dev
```

Com `VITE_API_URL` vazio, o Vite encaminha `/api` para `http://127.0.0.1:8000`.
