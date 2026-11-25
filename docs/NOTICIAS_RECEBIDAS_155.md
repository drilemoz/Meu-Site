# Notícias recebidas — 1.0.155

- Menu público “Enviar notícia” abre `/index.php?p=enviar-noticia`. Envio POST com CSRF, consentimento, honeypot, campos validados e confirmação após gravação. Contato geral e anúncios continuam no SMTP antigo, fora do novo módulo.
- ADM → Notícias → Notícias recebidas. Administradores e editores podem ver dados, baixar fotos privadas, aceitar, rejeitar, arquivar, excluir e criar rascunho. Redatores não acessam a caixa. A visão geral mostra a contagem de novas sugestões; não há notificações por e-mail.
- Rascunho transacional, com vínculo único por sugestão, sem publicar ou incorporar dados de contato e fotos automaticamente. As fotos devem ser baixadas e revisadas antes de eventual inclusão manual na notícia. Excluir a sugestão não exclui o artigo.
- Até 2 fotos JPEG/PNG/WebP de 512 KiB cada, dimensões máximas 4096 × 4096, conteúdo inspecionado por fileinfo e getimagesize; não são publicadas nem gravadas em uploads. Arquivos originais podem conter metadados (EXIF), por isso revise antes de publicação. Envio total limitado a 1200 KiB. Limites do servidor podem ser menores.
- Limites: 3 envios por origem/dia, 1 por sessão/minuto, 500 registros e aproximadamente 8 MiB de dados na caixa (incluindo Base64). Excluir sugestões antigas libera espaço. IP não armazenado em claro pelo módulo. A chave pseudônima diária é derivada da credencial do banco; não confiar em cabeçalhos de proxy enviados pelo cliente. Os backups podem conter dados privados. A política de privacidade foi atualizada.

## Atualização de instalação existente

O projeto passa de schema 37 para 38, backup lógico v12 para v13. O atualizador interno e o worker recusam pacotes com schema diferente. Não falsifique `schema_revision`, nem desative o bloqueio. O AL Sistemas VPS não foi analisado nesta alteração; seu fluxo de atualização e backups deve ser verificado antes do deploy.

1. Fazer backup externo completo do banco, código e mídias; testar restauração em homologação.
2. Instalar o código 1.0.155 por procedimento assistido, preservando `storage/`, `public/uploads/`, configuração e permissões. Não enviar o ZIP de fonte ao atualizador interno.
3. Acessar o ADM para executar a migração idempotente, que cria `story_submissions` e só depois grava o marcador 38. Requer permissão CREATE TABLE; a rotina existente também verifica migrações antigas. O backup CLI também usa a migração registrada.
4. Conferir saúde, matérias anteriores, envio público, caixa, permissões, fotos, rascunho e backup/restauração v13. Backups v1–v12 podem ser importados no código novo (caixa vazia). Em caso de falha, restaurar código e banco juntos; não fazer rollback apenas do código.

Testes: `php tests/story_submissions_255.php`, `php tests/schema_migrations_133.php` e integração MariaDB `tests/integration/restore_mariadb_134.php` em banco isolado. Validação HTTP, servidor real e AL Sistemas ainda precisam ser executados.
