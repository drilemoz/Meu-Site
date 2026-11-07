# Validação IguaNews 1.0.32

- Origem: pacote v1.0.31 conferido, sem reescrever os módulos externos ao editor.
- Escopo: decomposição do serviço `app/news_editor.php` em 7 serviços menores; fachada com `require_once` para preservar todas as funções públicas.
- Compatibilidade: as 24 funções originais continuam definidas; não há migração SQL e são preservados editor com 3 etapas, URLs, permissões, modelo de dados, backup lógico v10 e bundle de mídia v2.
- Correção associada: ao restaurar revisão, nunca executar `is_demo=0`. O campo deve manter o valor atual no banco.
- `php -l`: **86** arquivos PHP aprovados.
- `node --check`: **14** arquivos JavaScript aprovados.
- `php tests/*.php`: **30/30** suítes aprovadas, incluindo `editor_modules_132.php` e `editor_revision_132.php`.
- `node tests/*.js`: **5/5** suítes aprovadas.
- `python3 tests/seo_http_125.py`: **8/8** cenários HTTP aprovados.
- `python3 tools/build_css.py --check`: aprovado; 15 módulos CSS e único CSS HTTP sincronizados.
- ZIP completo: teste de integridade CRC e conferência de arquivos privados excluídos.

## Limites de teste

- O banco de dados MariaDB/MySQL não foi conectado nesta execução: a lógica da restauração foi testada com substituto de PDO, não com instalação e migração reais.
- Não foi realizado teste de UI com Android/Termux ou navegador mobile real.
- O teste de esquema e restauração completa em um clone descartável da instalação continua recomendado antes da produção.

## Atualização segura

Não substituir `storage/config.php`, `storage/backups/`, `storage/media/`, `storage/analytics.key` nem `public/uploads/` da instalação. Instalar o ZIP em cópia de teste, executar a regressão, e só depois atualizar o código ativo. A ausência de nova migração não substitui um backup prévio.
