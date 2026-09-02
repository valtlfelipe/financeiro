---
paths:
  - compose.yaml
  - '**'
  - package.json
---

# General

## Self-host stack is image plus env vars
compose.yaml is the Portainer/Dockhand stack: published image only, no build, no env_file. Laravel reads process environment variables; do not require a .env inside the container. APP_KEY and DB_PASSWORD are mandatory and interpolated by Compose. Local image builds use compose.build.yaml as an overlay. The app entrypoint runs migrate --force; the scheduler must not.

## English Conventional Commits and branch names
Always write commit messages in English using Conventional Commits: `<type>(<optional-scope>): <short description>`, for example `fix: require a transaction category` or `docs(git): document naming conventions`. Keep any commit body in English too.
When creating a branch, use a short English description in lowercase kebab-case with a relevant type prefix, for example `fix/transaction-category-validation` or `feat/account-transfers`. Preserve any required repository or tool namespace; with `codex/`, use `codex/fix-transaction-category-validation`. Conventional Commits syntax applies to commits; branch names use slashes and hyphens, without colons or spaces.

## Keep Vue type checking compatible during dependency upgrades
The TypeScript dependency uses Microsoft's @typescript/typescript6 compatibility alias because vue-tsc embeds the legacy compiler API; TypeScript 7 cannot currently replace it. Before changing the alias, verify support in the installed vue-tsc and run npm run types:check without disabling checks or adding forced overrides. Keep @types/node aligned with the Node major used in Docker and CI, not npm's latest major.
