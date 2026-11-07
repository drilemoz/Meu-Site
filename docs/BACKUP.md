# Backup e restauração — Meu Site 1.0.76

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
