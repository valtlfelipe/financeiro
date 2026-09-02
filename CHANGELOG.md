# Changelog

Todas as mudanças relevantes serão registradas aqui. O formato segue Keep a Changelog e o projeto pretende usar versionamento semântico.

## [Unreleased]

## [1.1.0] - 2026-09-02

### Added

- Menu do usuário com edição de perfil, opções de aparência e saída da sessão.
- Temas claro, escuro e automático, com preferência persistida entre visitas.
- Avatares de robôs gerados localmente com DiceBear, estáveis por usuário e usados também na lista de pessoas.
- Confirmação antes de arquivar contas e categorias.
- Edição do nome do espaço pelo proprietário nas preferências, mantendo a configuração de idioma.

### Changed

- Confirmações nativas do navegador substituídas por diálogos shadcn-vue, com o nome do recurso em destaque.
- Campo de pagamento ou recebimento do lançamento usa um switch com explicação do estado selecionado.
- Novas contas e categorias recebem uma cor aleatória ao abrir o formulário, preservando as cores dos registros em edição.
- Próximos lançamentos no resumo exibem a data no formato dia/mês no lugar do avatar.
- Cards de saldo ficam apenas na página de lançamentos, abaixo do seletor de mês e separados da pesquisa e dos filtros da lista.
- Botão de busca usa um ícone com nome acessível, e formulários receberam ajustes para telas pequenas.
- Dependências Composer e npm atualizadas, incluindo Laravel 13.30.1, VueUse 14 e DiceBear 10.
- TypeScript usa o pacote oficial de compatibilidade com a linha 6, necessário ao verificador Vue atual; tipos do Node acompanham a linha 24 usada no projeto.

### Fixed

- Feedback de sucesso e validação na edição do perfil.
- Ação duplicada de repetir removida dos detalhes do lançamento, mantendo apenas Copiar.

## [1.0.4] - 2026-09-02

### Changed

- Ícone próprio do Financeiro aplicado ao cabeçalho, às telas de acesso, ao favicon e aos ícones de instalação no celular.
- Cache dos ícones atualizado para substituir a identidade visual anterior.
- Screenshot do README atualizado com o novo ícone e uma conta de demonstração com dados fictícios, com a imagem versionada no repositório.
- Convenções de commits e branches em inglês documentadas nas regras do projeto.

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

[Unreleased]: https://github.com/valtlfelipe/financeiro/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/valtlfelipe/financeiro/compare/v1.0.4...v1.1.0
[1.0.4]: https://github.com/valtlfelipe/financeiro/compare/v1.0.3...v1.0.4
[1.0.3]: https://github.com/valtlfelipe/financeiro/compare/v1.0.2...v1.0.3
[1.0.2]: https://github.com/valtlfelipe/financeiro/compare/v1.0.1...v1.0.2
[1.0.1]: https://github.com/valtlfelipe/financeiro/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/valtlfelipe/financeiro/releases/tag/v1.0.0
