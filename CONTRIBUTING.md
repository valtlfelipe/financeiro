# Contribuindo

Obrigado por melhorar o Financeiro. A instalação self-hosted para quem só quer rodar o app está no [README](README.md).

1. Abra uma issue curta antes de mudanças grandes de domínio ou interface.
2. Crie uma branch focada e preserve o isolamento por `workspace_id`.
3. Nunca use `float` para dinheiro; valores persistidos são inteiros em centavos.
4. Não escreva strings visíveis diretamente em Vue ou PHP. Use os catálogos semânticos.
5. Inclua testes para comportamento novo, especialmente permissões, datas e cálculos.
6. Execute `composer test` e `npm run ci` antes do pull request.

A suíte roda em PostgreSQL, o mesmo banco de produção. Suba o serviço com `docker compose -f compose.dev.yaml up -d db`: ele cria o banco `financeiro_testing` usado por `phpunit.xml`, separado do banco de desenvolvimento. Em um volume que já existia antes dessa mudança, crie o banco uma vez com `createdb -h 127.0.0.1 -p 5433 -U financeiro financeiro_testing`.

Mudanças de schema precisam de migrações explícitas e reversíveis. Não altere migrações publicadas depois de uma release. Commits e pull requests devem explicar impacto, risco e forma de validação.

Para publicar, mova as entradas de `[Unreleased]` no `CHANGELOG.md` para `## [x.y.z] - AAAA-MM-DD`, faça o merge em `main` com o CI verde e envie uma tag `vX.Y.Z`. O workflow `Release` publica `ghcr.io/valtlfelipe/financeiro` e cria o GitHub Release.

Ao contribuir, você concorda que sua contribuição será distribuída sob AGPL-3.0-only.
