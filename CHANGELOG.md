# Changelog

Todas as mudanças relevantes serão registradas aqui. O formato segue Keep a Changelog e o projeto pretende usar versionamento semântico.

## [Unreleased]

## [1.0.3] - 2026-09-02

### Fixed

- Campos de valor removem caracteres inválidos durante a digitação e colagem, preservando a formatação monetária.
- Novos lançamentos exigem a seleção explícita de uma categoria, com placeholder e sem seleção automática.
- Transferências ficam desabilitadas quando há menos de duas contas; a explicação aparece ao clicar, passar o mouse ou navegar pelo teclado.
- Campos de uma linha usam altura padrão de 44 px nos formulários de lançamentos e cadastros.

## [1.0.2] - 2026-09-02

### Fixed

- Títulos de página e remetente de e-mail usam sempre Financeiro, independentemente das variáveis de nome no build ou no servidor.

### Changed

- Setup self-hosted simplificado para `APP_KEY`, `DB_PASSWORD` e `APP_URL`, com geração de segredos sem exibi-los e arquivo `.env` protegido.
- PostgreSQL, banco e usuário `financeiro` passam a ser os padrões da aplicação.
- Desenvolvimento usa as mesmas variáveis `DB_*` na aplicação e no Compose; Redis fica opcional via profile.
- Ambientes de desenvolvimento com `DEV_DB_*` ou `DEV_REDIS_PORT` personalizados devem migrar para `DB_*` ou `REDIS_PORT`.
- README atualizado com instalação mínima e opções avançadas separadas.

## [1.0.1] - 2026-09-02

### Changed

- README reescrito em pt-BR com instalação self-hosted em Docker Compose.
- Stack Docker passa a ser só imagem + variáveis de ambiente, sem arquivo `.env` no container.
- Input de valor formatado durante a digitação.

### Added

- Migração automática na subida do container e volume persistente de `storage`.

## [1.0.0] - 2026-09-02

### Added

- Fundação Laravel 13, Inertia 3 e Vue 3.
- Setup do primeiro proprietário e convites privados.
- Espaços, contas, categorias e lançamentos isolados.
- Receitas, despesas, transferências, recorrências e parcelamentos.
- Joinha pendente/realizado e resumo mensal.
- PWA online-first e internacionalização `pt-BR`.
- Docker Compose com FrankenPHP, PostgreSQL e scheduler.
- Backup, restore, testes e CI.
- Publicação da imagem Docker no GHCR e GitHub Releases a partir de tags `v*.*.*`.
- CI gera as rotas do Wayfinder e o build do Vite antes dos testes.
- A imagem Docker copia o helper Wayfinder para o build do frontend.

[Unreleased]: https://github.com/valtlfelipe/financeiro/compare/v1.0.3...HEAD
[1.0.3]: https://github.com/valtlfelipe/financeiro/compare/v1.0.2...v1.0.3
[1.0.2]: https://github.com/valtlfelipe/financeiro/compare/v1.0.1...v1.0.2
[1.0.1]: https://github.com/valtlfelipe/financeiro/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/valtlfelipe/financeiro/releases/tag/v1.0.0
