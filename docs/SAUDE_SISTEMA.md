# IguaNews 1.0.14 | Central de Saúde e segurança operacional

## Acesso

Entre como **Administrador** e abra **Saúde do sistema** no menu lateral ou `/admin.php?action=health`. A página requer sessão válida, usuário ativo e permissão `manage_settings`; a verificação de backups exige permissão `backup`, formulário POST e CSRF.

O diagnóstico não cria ou altera notícias, usuários, linhas de ônibus nem dados do banco. Exibe somente condições do ambiente PHP, disponibilidade do banco, tabelas principais, permissões de escrita, espaço livre, idade do backup mais recente, extensão ZIP, presença das configurações SMTP e do arquivo GeoIP. O SMTP e o GeoIP não são testados contra serviços externos.

## Registros técnicos seguros

Eventos internos são registrados em `storage/logs/health.log`, com rotação automática limitada a aproximadamente 512 KiB por arquivo e duas cópias anteriores. São armazenados **somente** data/hora, código do evento, classe de erro, nome básico do arquivo PHP e número de linha. Não são registrados texto das exceções, IP, URI, dados das matérias, corpo de formulários ou senhas.

Se houver falha fatal de PHP, o handler grava um registro resumido. Algumas exceções já tratadas internamente não geram um evento; ausência de registros não prova ausência de problemas. O servidor pode manter logs adicionais próprios, conforme sua configuração.

**Nunca configure a raiz pública do site para a pasta do projeto inteiro.** Apache/Nginx devem servir exclusivamente `public/`; `storage/`, `app/`, `.env` e quaisquer backups devem ficar fora da raiz pública. Evite `display_errors=On` em produção.

## Backups e restauração

O módulo gera inicialmente um arquivo temporário oculto em `storage/backups/`, confere a gravação completa e só depois o renomeia para `.json` ou `.json.gz`. A listagem foi corrigida para ordenar pela **data de modificação**, não pelo prefixo (`manual`, `daily`, `weekly`). O script `bin/backup.php` valida o arquivo gerado usando `parse_backup` antes de executar a rotação dos backups antigos.

Na Central de Saúde, a ação **Validar backup** lê o backup mais recente e confere GZIP, formato, versões suportadas, tabelas, limite de registros e a presença de administrador ativo. Mostra SHA-256 do arquivo para conferência. **Isto NÃO é restauração real, não garante coerência relacional no MySQL e não testa as fotos.** Uma restauração em ambiente separado continua obrigatória.

Para testar backups de verdade:

1. Gere um backup e baixe uma cópia para fora do celular/servidor.
2. Em ambiente de teste isolado, instale o IguaNews em um banco vazio.
3. Restaure o backup e confira algumas notícias, categorias, contas e imagens.
4. Não restaure dados de teste sobre o site real.

## Testes do ambiente

```bash
php -v
php -m | grep -Ei 'PDO|pdo_mysql|fileinfo|zlib|zip'
php tests/health.php
php tests/smoke.php
php -S 127.0.0.1:8000 -t public
```

Abra a Central com o navegador já autenticado. No Termux, o MariaDB deve estar iniciado. Alguns hosts compartilhados podem não permitir listar certas informações SQL. O painel sinaliza o problema, mas não tenta elevar privilégios.

## Limitações e tarefas futuras

- Não há automação externa de alerta nem serviço em segundo plano. A checagem é feita quando o administrador abre a página.
- O indicador de backup recente **não comprova** que o cron esteja ativo. Consulte o agendador da hospedagem.
- O teste de SMTP examina presença de configuração e dependências, não envia e-mails.
- A verificação de mídia é indireta (espaço/ZIP); uma exportação completa separada ainda é necessária.
- Sem `pdo_mysql` e um MariaDB real no ambiente de desenvolvimento, não é possível certificar a migração ou os dados existentes; execute testes finais no Termux antes do deploy.
