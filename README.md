# Financeiro

Gerenciador financeiro pessoal open source, simples e self-hosted. O foco do v1 é responder duas perguntas sem atrito: o que está previsto para o mês e o que já recebeu o joinha de pago ou recebido.

## O que já está incluído

- primeiro proprietário criado em `/setup`, sem cadastro público depois disso;
- espaços compartilhados com papéis de proprietário e membro;
- contas e categorias arquiváveis;
- receitas, despesas e transferências atômicas;
- lançamentos recorrentes semanais, mensais e anuais;
- parcelamentos mensais de 2 a 120 vezes, sem perder centavos;
- estado pendente/realizado com joinha, atualização otimista e desfazer;
- resumo mensal previsto e realizado;
- PWA online-first, responsiva e com dados financeiros sempre network-only;
- português brasileiro e infraestrutura completa de internacionalização;
- imagem Docker com FrankenPHP, PostgreSQL 17 e scheduler, sem Redis.

## Stack

Laravel 13, PHP 8.5, Inertia 3, Vue 3, TypeScript, Tailwind CSS 4, PostgreSQL 17 e FrankenPHP/Caddy.

## Instalação com Docker Compose

Pré-requisitos: Docker Engine com o plugin Compose.

```bash
cp .env.example .env
php -r "echo 'APP_KEY=base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
```

Copie o valor exibido para `APP_KEY` em `.env` e troque `DB_PASSWORD`. Depois:

```bash
docker compose pull
docker compose run --rm app php artisan migrate --force
docker compose up -d
```

A imagem publicada fica em `ghcr.io/valtlfelipe/financeiro`. Para construir a partir do Dockerfile, use `docker compose build` no lugar do `pull`.

Abra `http://localhost:8080/setup`, crie o proprietário e o primeiro espaço financeiro. Para mudar a porta, ajuste `APP_PORT` e `APP_URL`. Para fixar uma versão, use `FINANCEIRO_IMAGE=ghcr.io/valtlfelipe/financeiro:1.2.3`.

O serviço `scheduler` mantém doze meses de recorrências futuras. O healthcheck HTTP fica em `/up`. Em um proxy reverso, configure `APP_URL` e informe os endereços confiáveis em `TRUSTED_PROXIES`, separados por vírgula.

## Desenvolvimento local

Requer PHP 8.5, Composer 2 e Node.js 24. PostgreSQL e Redis podem ficar somente no Docker; o Redis é opcional no v1 e fica disponível para futuras filas/cache.

```bash
cp .env.development.example .env
docker compose -f compose.dev.yaml up -d

composer install
npm install
php artisan key:generate
php artisan migrate
composer dev
```

Nesse modo, Laravel e Vite rodam diretamente na máquina (`http://localhost:8000`) e o Compose fornece apenas os serviços de infraestrutura. O PostgreSQL fica exposto em `127.0.0.1:5433` para evitar conflito com uma instalação local; altere `DEV_DB_PORT` se necessário. Para desligar os serviços, use `docker compose -f compose.dev.yaml down`.

Verificações completas:

```bash
vendor/bin/pint --test
vendor/bin/phpstan analyse
php artisan test --compact
npm run ci
```

## Backup e restauração

O backup padrão é criado em `./backups`, fora do volume PostgreSQL:

```bash
scripts/backup.sh
scripts/backup.sh /caminho/externo/financeiro
```

O resultado é um archive custom do `pg_dump`, próprio para `pg_restore`. A restauração limpa os objetos atuais do banco configurado e por isso exige um sinal explícito:

```bash
scripts/restore.sh /caminho/financeiro-20260901T120000Z.dump --yes
```

Faça restore somente de arquivos confiáveis. Para validar sem risco, restaure em uma instalação nova e vazia. O PostgreSQL documenta que archives podem executar instruções presentes no dump durante a restauração.

## Idiomas

Nenhuma string de interface nova deve ser escrita diretamente em componentes. Veja [docs/localization.md](docs/localization.md) para adicionar um idioma e executar a verificação de completude.

## Publicar uma versão

1. Mova as entradas de `[Unreleased]` em [CHANGELOG.md](CHANGELOG.md) para `## [x.y.z] - AAAA-MM-DD`.
2. Faça o merge em `main` com o CI verde.
3. Crie e envie a tag:

```bash
git tag v1.0.0
git push origin v1.0.0
```

O workflow `Release` constrói `linux/amd64` e `linux/arm64`, publica em `ghcr.io/valtlfelipe/financeiro` (`1.0.0`, `1.0`, `1` e `latest`) e abre o GitHub Release. Tags com sufixo (`v1.0.0-rc.1`) saem como pré-release. Um rebuild manual usa `workflow_dispatch`. Na primeira publicação, se o `pull` pedir login, torne o pacote público em GitHub → Packages.

## Segurança e contribuições

Leia [SECURITY.md](SECURITY.md) para reportar vulnerabilidades e [CONTRIBUTING.md](CONTRIBUTING.md) antes de enviar mudanças.

## Licença

Copyright (C) 2026 contributors. Distribuído sob a [GNU Affero General Public License v3.0](LICENSE). Modificações oferecidas por rede devem disponibilizar o código-fonte correspondente conforme a licença.
