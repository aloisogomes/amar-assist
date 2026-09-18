#!/bin/sh
set -eu

ROOT="$(CDPATH= cd -- "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

echo ">> Construindo imagens de teste"
docker compose --profile ci build test-backend test-frontend

echo ">> Testes da API (Pest)"
docker compose --profile ci run --rm --no-deps test-backend

echo ">> Verificação do frontend (vue-tsc)"
docker compose --profile ci run --rm --no-deps test-frontend
