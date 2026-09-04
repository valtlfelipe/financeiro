# Changelog

Todas as mudanças relevantes serão registradas aqui. O formato segue Keep a Changelog e o projeto pretende usar versionamento semântico.

## [Unreleased]

## [1.4.5] - 2026-09-04

### Fixed

- Lançamentos quitados antes do vencimento passam a sair do saldo atual no momento da baixa, em vez de só entrarem na data de vencimento. A competência do mês, o saldo previsto e o encadeamento entre meses continuam iguais, e dar baixa em contas atrasadas segue mantendo o movimento na data de vencimento.

### Changed

- A suíte de testes passa a rodar apenas em PostgreSQL, o mesmo banco usado pela aplicação publicada, e o CI deixa de manter uma execução separada em SQLite com regras de integridade duplicadas.

## [1.4.4] - 2026-09-04

### Changed

- Screenshot do README atualizado para refletir o resumo mensal mais recente.

### Fixed

- Lançamentos anteriores à data de uma conta com saldo inicial zero agora compõem corretamente os saldos realizado e previsto, inclusive em recorrências, transferências e no carregamento entre meses.

## [1.4.3] - 2026-09-04

### Added

- Detalhamento expansível do resumo mensal com saldos atuais e previstos por conta.

### Changed

- Resumo dos lançamentos prioriza o saldo atual e mantém os detalhes abertos ou fechados ao navegar entre meses.
- Expansão do resumo usa uma transição sutil e respeita a preferência por movimento reduzido.
- Link de apoio ao projeto passa a usar a página própria do Financeiro, também disponível no GitHub Sponsors.

### Fixed

- Testes com PostgreSQL no CI recebem o build do Vite necessário para executar a suíte completa.

## [1.4.2] - 2026-09-04

### Added

- Escolha entre ícones predefinidos ao criar ou editar um espaço, com identificação visual no seletor de espaços.
- Exclusão permanente de espaços pelo proprietário, protegida pela digitação do nome e bloqueada quando é o último espaço do usuário.
- Escolha entre alterar ou excluir apenas um lançamento de série ou aquele lançamento e os próximos.

### Changed

- Resumo dos lançamentos passa a apresentar o fluxo completo entre saldo anterior, movimentação prevista, saldo previsto e saldo realizado, carregando corretamente a posição entre meses.
- Cálculos mensais e saldos do dashboard usam consultas agregadas e uma leitura consistente, sem crescer o número de consultas conforme novas contas são adicionadas.
- CI passa a executar a suíte PHP também no PostgreSQL 17 usado pela aplicação publicada.

### Fixed

- Saldo realizado considera a data financeira do lançamento, inclusive quando a confirmação ocorreu em outra data.
- Saldo inicial respeita sua data de referência e não aparece antecipadamente quando configurado para o futuro.
- Projeções partem da posição realizada sem contar lançamentos confirmados duas vezes.
- Parcelamentos distribuem todos os centavos exatamente, preservam o total nas edições futuras e rejeitam parcelas de valor zero.
- Exclusões pontuais de recorrências não são recriadas pelo gerador, e falhas em uma série não interrompem silenciosamente as demais.
- Datas mensais, recorrências e valores padrão respeitam o fuso horário do espaço.
- Contas só podem ser arquivadas após encerrar corretamente saldo, pendências e transferências relacionadas.
- Valores monetários permanecem exatos em PHP, JSON e JavaScript mesmo acima do limite numérico seguro do navegador.
- Restrições financeiras no banco impedem valores, tipos, transferências, parcelas e recorrências estruturalmente inválidos.

## [1.4.1] - 2026-09-03

### Fixed

- Cache da versão não atualizando corretamente após uma atualização.

## [1.4.0] - 2026-09-03

### Added

- Seletor de espaços no cabeçalho, com indicação do papel de proprietário ou membro e troca direta para a visão geral.
- Criação de espaços por usuários que já são proprietários, com conta principal, categorias iniciais, moeda BRL e fuso `America/Sao_Paulo`.

### Changed

- O espaço ativo passa a ser mantido por sessão, usando a preferência salva apenas para iniciar novas sessões e preservando escolhas independentes entre navegadores e dispositivos.
- Trocas com formulários alterados usam o diálogo da aplicação para confirmar o descarte, mantendo o rascunho quando canceladas.

### Fixed

- Mutações antigas ou sem o identificador do espaço são rejeitadas antes da gravação, evitando que abas desatualizadas alterem outro espaço.
- Cadastro inicial e aceite de convites inicializam o espaço da sessão, e vínculos removidos são revalidados em cada requisição.

## [1.3.0] - 2026-09-03

### Added

- Cancelamento de convites pendentes pelo proprietário, com confirmação e invalidação do link.
- Remoção de membros pelo proprietário, com confirmação, revogação dos convites antigos e preservação da conta, dos lançamentos e do acesso a outros espaços. Proprietários não podem ser removidos.

### Fixed

- Convites passam a rejeitar e-mails de membros do mesmo espaço e convites pendentes duplicados, sem diferenciar maiúsculas de minúsculas. Contas de outros espaços continuam podendo ser convidadas.
- Botão de criar convite permanece alinhado ao campo de e-mail quando há uma mensagem de validação.
- Membros removidos sem outro espaço recebem acesso negado, evitando um ciclo de redirecionamentos para a configuração inicial.

## [1.2.0] - 2026-09-03

### Added

- Tela Sobre como última opção dos Ajustes, com logo, descrição, autoria, licença e versão instalada.
- Verificação automática de novas versões estáveis no GitHub, com estados de carregamento, atualização disponível e falha na consulta. Resultados são compartilhados entre usuários por uma hora, ou por um minuto em caso de falha.
- Links para apoiar o projeto pelo GitHub Sponsors, acessar o repositório, reportar bugs e sugerir funcionalidades.
- Configuração do botão de Sponsors e formulários de issues em português para bugs e sugestões de funcionalidades no GitHub.
- Alteração de senha pelo perfil, com confirmação da senha atual e feedback de sucesso e validação.

### Changed

- Imagens Docker publicadas passam a incluir a versão da tag de release para exibição e comparação na tela Sobre.

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

[Unreleased]: https://github.com/valtlfelipe/financeiro/compare/v1.4.5...HEAD
[1.4.5]: https://github.com/valtlfelipe/financeiro/compare/v1.4.4...v1.4.5
[1.4.4]: https://github.com/valtlfelipe/financeiro/compare/v1.4.3...v1.4.4
[1.4.3]: https://github.com/valtlfelipe/financeiro/compare/v1.4.2...v1.4.3
[1.4.2]: https://github.com/valtlfelipe/financeiro/compare/v1.4.1...v1.4.2
[1.4.1]: https://github.com/valtlfelipe/financeiro/compare/v1.4.0...v1.4.1
[1.4.0]: https://github.com/valtlfelipe/financeiro/compare/v1.3.0...v1.4.0
[1.3.0]: https://github.com/valtlfelipe/financeiro/compare/v1.2.0...v1.3.0
[1.2.0]: https://github.com/valtlfelipe/financeiro/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/valtlfelipe/financeiro/compare/v1.0.4...v1.1.0
[1.0.4]: https://github.com/valtlfelipe/financeiro/compare/v1.0.3...v1.0.4
[1.0.3]: https://github.com/valtlfelipe/financeiro/compare/v1.0.2...v1.0.3
[1.0.2]: https://github.com/valtlfelipe/financeiro/compare/v1.0.1...v1.0.2
[1.0.1]: https://github.com/valtlfelipe/financeiro/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/valtlfelipe/financeiro/releases/tag/v1.0.0
