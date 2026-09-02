# Internacionalização

`pt-BR` é o catálogo-base e o fallback. O locale individual fica em `users.locale`; moeda e timezone pertencem ao espaço financeiro.

## Adicionar um idioma

1. Inclua o código, nome e equivalente do backend em `config/locales.php`.
2. Amplie o tipo `LocaleCode` em `resources/js/i18n.ts`.
3. Duplique `resources/js/i18n/locales/pt-BR` para o novo código.
4. Crie o diretório Laravel correspondente dentro de `lang/`.
5. Traduza valores, mantendo exatamente as chaves semânticas do catálogo-base.
6. Registre os JSONs do novo idioma em `resources/js/i18n.ts`.
7. Execute `npm run i18n:check` e `php artisan test --compact`.

Use interpolação e pluralização. Não concatene fragmentos de frases traduzidas. Valores e datas devem passar por `Intl.NumberFormat` e `Intl.DateTimeFormat` com locale do usuário, moeda e timezone do espaço.

O script de CI compara todos os catálogos com `pt-BR` e também procura chaves literais inexistentes nos componentes Vue. Uma chave ausente em outro idioma cai para `pt-BR`; componentes financeiros não devem mostrar a própria chave.
