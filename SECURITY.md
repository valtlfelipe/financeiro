# Segurança

## Versões suportadas

Enquanto o projeto estiver antes da versão 1.0, apenas o commit mais recente da branch principal recebe correções de segurança.

## Reporte responsável

Não publique vulnerabilidades com dados financeiros, bypass de autenticação, quebra de isolamento entre espaços ou execução remota antes da correção. Abra um aviso privado de segurança no repositório que hospedar o projeto. Se esse recurso não estiver disponível, contate os mantenedores por um canal privado indicado no perfil do repositório.

Inclua versão/commit, impacto, passos mínimos de reprodução e mitigação conhecida. Não inclua dados pessoais ou dumps reais.

## Operação self-hosted

- Use HTTPS no proxy reverso.
- Defina `APP_DEBUG=false`, uma `APP_KEY` aleatória e senhas exclusivas.
- Restrinja PostgreSQL à rede interna do Compose.
- Configure somente proxies conhecidos em `TRUSTED_PROXIES`.
- Mantenha imagens e dependências atualizadas.
- Faça backups externos e valide a restauração em banco novo.
- Restaure apenas dumps de origem confiável.
