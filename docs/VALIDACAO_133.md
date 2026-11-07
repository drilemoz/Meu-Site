# Validação da versão 1.0.33

## Escopo
- Formato lógico v10 mantido; exportação limitada a 40 MiB de JSON e 100.000 registros por tabela.
- Pacote de mídias v2 mantido; novas exportações incluem `db_sha256` no manifesto.
- Compatibilidade de leitura com ZIP v1/v2 antigos sem `db_sha256`, com verificação quando o campo estiver presente.
- Importador rejeita itens fora do manifesto, nomes duplicados e itens faltantes antes de promover arquivos.
- Migrações agora possuem registro de revisão 33 em `settings`, atualizado somente após todas concluírem.
- Central de Saúde verifica tabelas esperadas e revisão registrada.

## Testes locais
- `php -l` em **89 arquivos PHP**: aprovado.
- `node --check` em **14 arquivos JavaScript**: aprovado.
- Execução de **32 suítes PHP** (`tests/*.php`): aprovado.
- Execução de **5 testes JavaScript** (`tests/*.js`): aprovado.
- **8 cenários HTTP** (`tests/seo_http_125.py`): aprovado; as páginas sem instalação retornam 303 ao instalador.
- `python3 tools/build_css.py --check`: aprovado; 15 fontes CSS permanecem sincronizadas.
- `tests/backup_integrity_133.php`: arquivo JSON/GZIP real com PDO simulado, testes de hash, entradas extras/duplicadas/faltantes e limite exportação.
- `tests/schema_migrations_133.php`: execução, repetição sem DDL, falha intermediária e recuperação, usando PDO simulado.

## Pendências
- **Não foi executado MariaDB/MySQL real**: sem serviços e extensão `pdo_mysql` no ambiente local.
- **Não foi exercitada a extensão real ZipArchive**: `ext-zip` indisponível no ambiente; os testes do manifesto usaram um simulador.
- **Não foram executados testes de restauração em Android/Termux e interface de administração real**.
- Hash SHA-256 do manifesto detecta corrupção, não autentica o remetente. Só importar backups de fontes confiáveis.
- Atualizar a versão de esquema (`IGUA_SCHEMA_REVISION`) nas futuras migrações para não omitir alterações.
- Nenhuma alteração nos dados do usuário, no formato v10/v2, nas URLs e no editor de três etapas.
