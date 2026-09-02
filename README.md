# Financeiro

Gerenciador financeiro pessoal **open source**, feito para rodar **no seu servidor**. Os lançamentos não vão para a nuvem de ninguém.

O app existe para responder duas perguntas, sem atrito: o que está previsto para o mês, e o que já recebeu o joinha de pago ou recebido.

[![CI](https://github.com/valtlfelipe/financeiro/actions/workflows/ci.yml/badge.svg)](https://github.com/valtlfelipe/financeiro/actions/workflows/ci.yml)
[![Release](https://img.shields.io/github/v/release/valtlfelipe/financeiro)](https://github.com/valtlfelipe/financeiro/releases)
[![Licença](https://img.shields.io/badge/licença-AGPL--3.0-blue.svg)](LICENSE)

## Sumário

- [Para quem é](#para-quem-é)
- [O que você consegue fazer](#o-que-você-consegue-fazer)
- [Instalar no seu servidor](#instalar-no-seu-servidor)
- [Atualizar](#atualizar)
- [Backup e restauração](#backup-e-restauração)
- [Desenvolvimento](#desenvolvimento)
- [Contribuir](#contribuir)
- [Licença](#licença)

## Para quem é

Para quem quer acompanhar o mês em casa, no NAS ou num VPS, sem fintech e sem planilha compartilhada.

O Financeiro **não** conecta no seu banco. Você registra as contas, as categorias e os lançamentos. O primeiro usuário nasce em `/setup`; depois disso não existe cadastro público.

## O que você consegue fazer

- criar o proprietário e o primeiro espaço financeiro
- convidar outras pessoas, com papéis de proprietário ou membro
- organizar contas e categorias, inclusive arquivadas
- lançar receitas, despesas e transferências, com valores em centavos
- repetir lançamentos por semana, mês ou ano
- parcelar de 2 a 120 vezes, sem perder centavos
- marcar pendente ou realizado com joinha, e desfazer se clicar errado
- ver o resumo do mês previsto e realizado
- instalar como PWA; os dados financeiros sempre vêm da rede, nunca de cache local

Idioma da interface: português brasileiro. A stack é Laravel 13, PHP 8.5, Inertia 3, Vue 3, PostgreSQL 17 e FrankenPHP. Não usa Redis.

## Instalar no seu servidor

Não precisa clonar o repositório nem de PHP na máquina. O Laravel em produção lê **variáveis de ambiente do processo** (Portainer, Dockhand ou o Compose). Não use um `.env` dentro do container.

**Obrigatórias:** `APP_KEY` e `DB_PASSWORD`. Gere uma vez e **não troque** a `APP_KEY` depois, senão sessões e dados criptografados quebram.

```bash
echo "base64:$(openssl rand -base64 32)"
openssl rand -base64 18
```

A primeira linha é a `APP_KEY`. A segunda, a `DB_PASSWORD`. Evite `$` na senha: o Compose trata `$` como interpolação.

### Portainer ou Dockhand

1. Crie uma stack / compose.
2. Cole o conteúdo de [`compose.yaml`](compose.yaml) (ou baixe [o arquivo raw](https://raw.githubusercontent.com/valtlfelipe/financeiro/main/compose.yaml)).
3. Defina as variáveis da stack:

| Variável | Exemplo |
| --- | --- |
| `APP_KEY` | `base64:...` gerada acima |
| `DB_PASSWORD` | senha gerada acima |
| `APP_URL` | `http://192.168.1.10:8080` ou `https://financeiro.exemplo.com` |
| `APP_PORT` | `8080` (porta no host) |
| `FINANCEIRO_IMAGE` | `ghcr.io/valtlfelipe/financeiro:latest` (opcional) |
| `TRUSTED_PROXIES` | redes do proxy, se houver |

4. Faça o deploy.
5. Abra `/setup` na URL e crie o proprietário.

O container aplica as migrações sozinho na subida. O `scheduler` espera o `app` ficar saudável.

### Docker Compose na linha de comando

Baixe só o compose e exporte as variáveis (não precisa de arquivo `.env`):

```bash
curl -fsSL -o compose.yaml https://raw.githubusercontent.com/valtlfelipe/financeiro/main/compose.yaml

export APP_KEY='base64:cole-aqui'
export DB_PASSWORD='cole-aqui'
export APP_URL='http://localhost:8080'

docker compose pull
docker compose up -d
```

Se quiser um arquivo local só para o Compose interpolar `${VAR}` no YAML, rode `./scripts/init-env.sh` num clone. Esse `.env` não entra no container.

Abra [http://localhost:8080/setup](http://localhost:8080/setup). Healthcheck: `/up`. Para parar: `docker compose down`. Os volumes `postgres_data` e `app_storage` permanecem; `docker compose down -v` apaga os dados.

### Variáveis que importam

| Variável | Função |
| --- | --- |
| `APP_KEY` | Chave da aplicação. Obrigatória e estável. |
| `APP_URL` | URL pública, com esquema. |
| `APP_PORT` | Porta publicada no host. Padrão `8080`. |
| `DB_PASSWORD` | Senha do PostgreSQL. Obrigatória. |
| `DB_DATABASE` / `DB_USERNAME` | Padrão `financeiro`. |
| `FINANCEIRO_IMAGE` | Imagem. Padrão `ghcr.io/valtlfelipe/financeiro:latest`. Pin: `ghcr.io/valtlfelipe/financeiro:1.0.0`. |
| `TRUSTED_PROXIES` | IPs ou redes do proxy, separados por vírgula. |
| `LOG_LEVEL` | Padrão `error`. |
| `MAIL_MAILER` | Padrão `log`. |

### Atrás de um proxy reverso

Coloque HTTPS na frente da porta `8080` e defina:

```env
APP_URL=https://financeiro.exemplo.com
TRUSTED_PROXIES=10.0.0.0/8,172.16.0.0/12,192.168.0.0/16
```

Ajuste `TRUSTED_PROXIES` para o endereço real do proxy, se ele não estiver nessas redes.

## Atualizar

```bash
docker compose pull
docker compose up -d
```

As migrações rodam de novo na subida, de forma idempotente. Faça backup antes de atualizar em produção.

## Backup e restauração

Num clone com Compose, o dump vai para `./backups`, fora do volume do PostgreSQL:

```bash
scripts/backup.sh
scripts/backup.sh /caminho/externo/financeiro
```

No Portainer, use o console do container `db` com `pg_dump -Fc` (mesmo formato) e baixe o arquivo.

O arquivo é um archive custom do `pg_dump`, próprio para `pg_restore`. A restauração apaga os objetos atuais do banco e por isso pede um sinal explícito:

```bash
scripts/restore.sh /caminho/financeiro-20260901T120000Z.dump --yes
```

Restaure só de arquivos confiáveis. Para validar sem risco, restaure numa instalação nova e vazia.

## Desenvolvimento

Para contribuir com código, o Laravel e o Vite rodam na máquina. O Compose sobe só a infraestrutura. Requer PHP 8.5, Composer 2 e Node.js 24. Redis é opcional nesta versão.

```bash
cp .env.development.example .env
docker compose -f compose.dev.yaml up -d

composer install
npm install
php artisan key:generate
php artisan migrate
composer dev
```

A app fica em `http://localhost:8000`. O PostgreSQL de desenvolvimento escuta em `127.0.0.1:5433` (`DEV_DB_PORT` se precisar). Para desligar: `docker compose -f compose.dev.yaml down`.

Verificações:

```bash
vendor/bin/pint --test
vendor/bin/phpstan analyse
php artisan test --compact
npm run ci
```

Novas strings da interface entram nos catálogos, nunca soltas no Vue ou no PHP. Veja [docs/localization.md](docs/localization.md).

## Contribuir

Leia [CONTRIBUTING.md](CONTRIBUTING.md) antes de abrir um pull request e [SECURITY.md](SECURITY.md) para reportar vulnerabilidades.

Bugs e ideias vão em [issues](https://github.com/valtlfelipe/financeiro/issues). Issues curtas ajudam antes de mudanças grandes de domínio ou de interface. Preservar o isolamento por `workspace_id` é regra.

## Licença

Copyright (C) 2026 contributors. Distribuído sob a [GNU Affero General Public License v3.0](LICENSE). Se você modificar o Financeiro e oferecer a versão alterada em rede, precisa disponibilizar o código-fonte correspondente.
