# Contribuindo

Obrigado por melhorar o Financeiro.

1. Abra uma issue curta antes de mudanças grandes de domínio ou interface.
2. Crie uma branch focada e preserve o isolamento por `workspace_id`.
3. Nunca use `float` para dinheiro; valores persistidos são inteiros em centavos.
4. Não escreva strings visíveis diretamente em Vue ou PHP. Use os catálogos semânticos.
5. Inclua testes para comportamento novo, especialmente permissões, datas e cálculos.
6. Execute `composer test` e `npm run ci` antes do pull request.

Mudanças de schema precisam de migrações explícitas e reversíveis. Não altere migrações publicadas depois de uma release. Commits e pull requests devem explicar impacto, risco e forma de validação.

Ao contribuir, você concorda que sua contribuição será distribuída sob AGPL-3.0-only.
