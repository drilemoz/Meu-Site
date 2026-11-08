# Backup e restauração — Meu Site 1.0.79

O portal possui **dois formatos distintos**; escolher o formato errado pode deixar imagens e anexos de fora.

| Opção no painel | Conteúdo | Restauração |
|---|---|---|
| **Gerar backup agora** | Somente as tabelas MySQL/MariaDB em JSON, geralmente GZIP (`.json.gz`). Inclui matérias, usuários, configurações, mídias **como registros**, anúncios, ônibus, eleições e métricas. | **Restaurar um backup** (`.json` ou `.json.gz`): substitui dados do banco, **não restaura fotos/anexos**. |
| **Baixar pacote completo ZIP** | Backup JSON/GZIP do banco **e arquivos de imagem/anexos referenciados** por matérias, galerias, campanhas e biblioteca, provenientes de `public/uploads/` e `storage/media/`. | **Restaurar pacote completo**: valida manifesto e hashes antes de preparar mídias e restaurar registros. |

## O que NÃO é incluído automaticamente

- Arquivos de imagem não referenciados por registros do banco, conteúdo solto em uploads, arquivos temporários, caches e arquivos órfãos.
- **Código PHP**, `.github`, configuração do PHP/servidor e outras pastas fora das mídias referenciadas.
- Credenciais e configurações privadas em `storage/config.php`, arquivos da conta de serviço GA4 em `storage/integrations/`, segredos SMTP do servidor, logs e registros privados de segurança.
- O pacote de atualização do código **não** é um backup de banco ou de fotos.

## Limites verificáveis no código

- JSON descompactado: **até 40 MiB**; até **100.000 registros por tabela**.
- ZIP completo: **até 350 MiB**, incluindo até **300 MiB de mídias** e **3.000 arquivos de mídia**. Requer extensão PHP `zip` (`ZipArchive`); a hospedagem também aplica seus limites de upload.
- O formulário de restauração **lógica** aceita envio de até **15 MiB**, mesmo que a exportação JSON possa ter limite descompactado maior.
- Os backups automáticos de banco guardam até **7 diários e 4 semanais**; backups manuais não são rotacionados automaticamente. O ZIP completo de download é temporário, mas o JSON/GZIP associado permanece em `storage/backups/`.

## Proteções e procedimento recomendado

Antes de restaurar, guarde cópia externa do banco e das pastas `public/uploads/` e `storage/media/`. Para recuperar o servidor inteiro, salve também o **código do projeto**, os arquivos de configuração/credenciais em armazenamento seguro e as definições da hospedagem. Teste os backups em **ambiente isolado** antes de substituir dados da produção.

A restauração do ZIP exige senha e confirmação; valida nomes/caminhos, duplicações e hashes e cria um backup lógico preventivo. Porém **arquivos e banco não fazem parte de uma única transação atômica**. Falhas de disco e banco podem exigir recuperação manual do backup externo.

### Código-fonte relevante

`app/backup.php`, `app/admin_controllers/backups.php`, `app/admin_controllers/downloads.php`, `app/admin_views/routes/backups.php`, `bin/backup.php`.

### Testes

`tests/backup_121.php`, `tests/backup_integrity_133.php`, `tests/backup_143.php` e integração `tests/integration/restore_mariadb_134.php` no GitHub Actions com MariaDB e ext-zip reais.


## v1.0.96 — Backup de aplicativos e páginas

O formato lógico **v12** acrescenta `legal_apps`, `legal_app_pages`, `legal_app_revisions` e `legal_app_slug_redirects`. Inclui dados dos apps, rascunhos, revisões publicadas, status e slugs antigos. A leitura aceita backups **v1–v11**, preenchendo as novas tabelas com listas vazias, sem executar scripts contidos nos backups. O backup completo de mídia inclui logotipos registrados na biblioteca; um backup apenas do banco **não contém os bytes das imagens**. Antes da migração 36→37, conserve o backup antigo também fora do servidor.

## Proteção complementar da versão 1.0.114

Arquivos de backup representados por links simbólicos e pastas de backup redirecionadas por links simbólicos não são listados nem aceitos para download pelo painel. Os arquivos regulares com nome válido continuam disponíveis. Isso não muda o formato v12 nem recupera arquivos privados que o ZIP completo não inclua. Veja [a matriz de recuperação](SEGURANCA_RECUPERACAO_114.md).

## Novo pacote portátil de hospedagem — 1.0.128

**Backups e restauração → Baixar pacote para migrar hospedagem** gera o arquivo `iguatama-migracao-AAAAmmdd-...zip` sem alterar os conteúdos atuais. Ele contém o código PHP do portal, `public/setup.php`, `app/`, `bin/`, assets públicos, `VERSION` e um backup interno `storage/migration/backup.zip` com banco e mídias referenciadas. Outras mídias permitidas em `public/uploads/` e `storage/media/` são incluídas em `storage/migration/extras/` com SHA-256 no `manifest.json`.

**Procedimento:** não extraia o ZIP dentro de `public/` nem sobre uma instalação existente. Extraia na raiz da nova instalação, configure docroot para a subpasta `public/`, crie um banco vazio e abra `https://seu-dominio/setup.php`. Escolha **Migrar meu portal** e informe host, porta, nome, usuário e senha do **novo** MySQL. Depois da conexão testada, o instalador detecta o backup interno, solicita a senha do administrador antigo para autorizar a transferência e o importa. O login administrativo antigo permanece. A opção **Instalação limpa** continua disponível. A importação manual `.json/.json.gz` é alternativa sem mídia.

O ZIP é **confidencial** e deve ser guardado fora do alcance público. Ele não inclui `storage/config.php`, JSON de contas de serviço, segredos privados ou logs da instalação antiga, mas contém usuários e configurações guardados no banco. Credenciais privadas de integrações externas, PHP/Apache/Nginx, cron, domínio, DNS, certificado HTTPS e e-mail requerem reconfiguração manual.

Limites: backup de mídia até 350 MiB, arquivos extras até 300 MiB, pacote final até 700 MiB. Requer PHP `ZipArchive`, `pdo_mysql`, espaço livre e permissões. Um arquivo PHP de código não é assinado apenas porque consta de manifesto SHA-256: transfira o ZIP por canal confiável. Faça teste isolado em nova hospedagem/banco vazio antes de alterar o DNS. Em interrupção de instalação, não repita sobre banco parcialmente preenchido; crie outro banco vazio e tente novamente.
