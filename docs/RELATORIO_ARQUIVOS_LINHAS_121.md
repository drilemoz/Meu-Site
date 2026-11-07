# IguaNews v1.0.21 | Relatório de arquivos e alterações

## Resumo
- Base: IguaNews v1.0.20 (ZIP original anexado e versão confirmada no Google Drive).
- Entrega: **1.0.21**, sem migração de banco.
- Backup lógico: **v9**; ZIP de mídias: **v2**.
- Inventário: **128 arquivos** (sem incluir este MD/CSV nem log de execução anterior).
- Arquivos textuais: **112**, linhas textuais: **9112**.
- Arquivos binários: **16**.
- `storage/logs/health.log` da versão de origem foi omitido intencionalmente (dado de execução privada).

## Arquivos alterados e intervalos de linhas
- `AGENTS.md`: 55 linhas; L54–55.
- `CHANGELOG.md`: 181 linhas; L1–9.
- `README.md`: 281 linhas; L3–9, L53, L55, L265.
- `VERSION`: 1 linhas; L1.
- `app/backup.php`: 297 linhas; L12–18, L116–124, L134, L153, L155, L157–160, L162, L165–168, L172–229, L232, L234, L236–238, L241, L244–248, L267 (+5 outros trechos).
- `docs/BACKUP.md`: 91 linhas; L1, L7, L19–21, L24, L29, L41, L44–54, L77, L89, L91.
- `docs/VALIDACAO_121.md`: 22 linhas; novo.
- `public/admin.php`: 532 linhas; L524, L526.
- `tests/backup_121.php`: 89 linhas; novo.

## Efeito funcional
- Corrige a antiga incompatibilidade entre exportação de mídias até 300 MiB e limite de importação ZIP 150 MiB; agora o importador admite 350 MiB de ZIP para comportar banco e mídias.
- Restauração de mídias feita em stream de 64 KiB, hash SHA-256 incremental e arquivo temporário para mídias novas; todos os arquivos são validados antes de trocar dados no banco.
- Recusa caminhos reais fora das pastas de mídias, conflitos, arquivos ilegíveis, excesso de arquivos e hashes inválidos.
- Mantém o editor de notícias de três etapas (Básico, Texto, Foto), banco existente e compatibilidade histórica de backups.

## Verificações
- `php -l`: **54/54** arquivos PHP aprovados.
- `node --check`: **7/7** arquivos JavaScript aprovados.
- `tests/*.php`: **20/20** suítes PHP aprovadas (inclui testes de stream com substituto de ZipArchive).
- `zipfile.testzip()`: integridade da entrega verificada após criação.
- Pendentes: ciclo real PHP ext-zip + MariaDB, teste de recuperação e UI no Termux/Android.
- Operações entre filesystem e MySQL não são totalmente atômicas; preservar backups externos.

## Inventário completo
Consultar `IguaNews-v1.0.21-Relatorio-Linhas.csv` para cada arquivo e sua contagem exata de linhas.
