# Validação — Meu Site 1.0.43 (backups e limpeza)

## Escopo

- Backup lógico v11 escrito em fluxo em disco privado, com GZIP opcional, limite de 40 MiB JSON, 100 mil registros/tabela e `REPEATABLE READ`.
- ZIP completo v2 gerado em temporário e publicado após conclusão; download administrativo remove o ZIP produzido na requisição.
- Limpeza de temporários antigos sem remover arquivos manuais, ZIP antigos ou anexos.

## Verificação local

- Testes de sintaxe: `find app public tests bin -type f -name '*.php' -print0 | xargs -0 -n1 php -l`.
- Executar `for test in tests/*.php; do php "$test"; done` e `for test in tests/*.js; do node "$test"; done`.
- Executar `python3 tools/build_css.py --check` e `python3 tools/build_news_editor.py --check`.
- Regressão específica `php tests/backup_143.php`: valida serialização JSON idêntica ao formato v11 anterior, caracteres UTF-8, ordem das tabelas, rollback com falha de consulta, descarte de temporários e retenção de backups válidos.

## Validação ainda pendente

- Restauração do backup v11 contra MariaDB real e arquivo ZIP gerado por `ZipArchive` com mídias reais; extensão `pdo_mysql` e `zip` ausentes neste ambiente local.
- Testar interromper a resposta HTTP do ZIP no Termux/servidor e confirmar que o pacote transitório é removido, sem apagar o backup lógico.
- Verificar `memory_limit` e pico de memória com banco grande no ambiente real: PDO pode ainda manter buffering interno.

## Compatibilidade

Sem alteração de SQL, esquema rev. 35, backup lógico v11, ZIP de mídias v2, editor e configuração HTTP. Preservar `storage/`, `public/uploads/`, `storage/config.php`, backups e anexos na atualização.
