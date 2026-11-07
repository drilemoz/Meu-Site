# IguaNews 1.0.21 | Backup de mídias seguro e econômico em RAM

## Alterações

- `app/backup.php`: novos limites unificados para exportação/importação (300 MiB de mídias, 350 MiB ZIP, 3000 arquivos); URLs e formato de dados existentes preservados.
- `media_bundle_safe_source()`: impede arquivar symlinks ou arquivos reais fora de `public/uploads/` e `storage/media/`.
- `media_bundle_stage_entry()`: lê ZIP em blocos de 64 KiB, valida SHA-256 e tamanho, usa arquivo temporário somente para mídia ainda não existente, protege pasta de destino.
- `restore_media_bundle()`: valida manifesto, caminhos e mídias antes de gravar qualquer alteração no banco; gera backup lógico preventivo, prepara arquivos físicos e executa transação SQL. Tenta retirar as mídias recém-promovidas se o banco falhar.
- `public/admin.php`: informa limites e natureza não atômica do processo na tela de backup.
- `tests/backup_121.php`: regressões isoladas de limites, streams, hashes, conflitos e origem dos arquivos; usa stream substituto quando ext-zip não está instalada.

## Compatibilidade

Backup lógico **v9** e pacote de mídia **v2** inalterados. Parser antigo v1–v9 preservado. Nenhum ALTER TABLE. Nenhum dos diretórios `storage/config.php`, `storage/backups/`, `storage/media/`, `storage/analytics.key` e `public/uploads/` deve ser sobrescrito em atualizações. A limpeza de mídia em falhas é melhor esforço; arquivos físicos e transações MySQL não formam uma operação indivisível.

## Validações locais e limitações

- Sintaxe PHP: rodar `php -l` em todos os arquivos PHP.
- Sintaxe JS: rodar `node --check` em todos os arquivos JavaScript.
- Suítes: `for f in tests/*.php; do php "$f"; done`.
- Integridade do ZIP de entrega e manifesto do inventário de arquivos/linhas.
- **Pendentes:** teste de geração e restauração ZIP com extensão PHP zip real, banco MariaDB de teste, restauração de mídia acima de 150 MiB, fluxo no Termux e UI em Android. Sem tais ensaios, não afirmar compatibilidade operacional plena.
