# Frontend — Amar Assist

SPA Vue 3 que consome a API Laravel. O painel cobre autenticação, listagem e cadastro de transações, importação de planilha e o dashboard financeiro.

Valores chegam da API em **centavos** e são exibidos/editados em **reais (BRL)**.

## Stack

| Tecnologia | Versão | Uso |
| --- | --- | --- |
| Vue 3 | 3.5 | UI com `<script setup>` |
| TypeScript | 6 | Tipagem (`vue-tsc`) |
| Vite | 8 | Dev server, HMR e build |
| Vue Router | 5 | Rotas (`/` autenticado, `/login`, `/register`) |
| Pinia | 4 | Estado (`auth`, `financeImport`) |
| Tailwind CSS | 4 | Estilo (plugin Vite) |
| shadcn-vue | 2.8 | Componentes em `src/components/ui` |
| Reka UI | 2.10 | Primitivos acessíveis (base do shadcn-vue) |
| Lucide | 1.47 | Ícones |
| Unovis | 1.7 | Gráfico de receitas x despesas |
| VueUse | 15 | Utilitários |
| Laravel Echo + pusher-js | 2.5 / 8.6 | Progresso da importação via Reverb |
| vue-sonner | 2 | Toasts |

Fonte: **Inter**. Tema shadcn: **reka-nova**, cor base **neutral**, variáveis CSS em `src/style.css`.

## Como a API é chamada

`src/lib/api.ts` envia o Bearer token do Sanctum (`localStorage`).

Com `VITE_API_URL` vazio, o Vite encaminha `/api` para o Laravel (`http://127.0.0.1:8000` no host, ou `VITE_PROXY_TARGET` no Docker).

Rotas autenticadas usam `AppLayout` (sidebar, breadcrumb). Visitantes usam `AuthLayout`.

## Estrutura

```text
src/
├── components/        # Filter, FinanceForm, layout
│   └── ui/            # shadcn-vue
├── layouts/
├── lib/               # api, money (BRL), echo
├── stores/            # auth, financeImport
├── types/
├── views/
│   ├── auth/
│   ├── dashboard/
│   └── finances/
└── router/
```

## Telas

- Início — atalhos em cards
- Financeiro — lista, busca e filtros avançados
- Nova / editar transação — validação em português no cliente
- Importar — upload `.xlsx`/`.csv` e progresso em tempo real
- Dashboard — gráfico Unovis e KPIs do período

## Scripts

```bash
cp .env.example .env.local
npm install
npm run dev          # http://localhost:5173
npm run typecheck    # vue-tsc
npm run build        # typecheck + bundle
npm run preview      # serve o build
```

## Variáveis (`.env.local`)

| Variável | Função |
| --- | --- |
| `VITE_API_URL` | Vazio = proxy do Vite. Ou URL absoluta da API |
| `VITE_REVERB_APP_KEY` | Chave do app Reverb |
| `VITE_REVERB_HOST` | Host do WebSocket (`localhost`) |
| `VITE_REVERB_PORT` | Porta (`8080`) |
| `VITE_REVERB_SCHEME` | `http` ou `https` |

Para subir o frontend junto com a API e o Reverb, use o Docker da [raiz do repositório](../README.md).
