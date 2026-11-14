# ZIP de regressão com entradas duplicadas

`backup133_duplicate_names.zip` é um fixture intencionalmente não confiável.
Ele contém quatro entradas reais no diretório central do ZIP, duas delas
com exatamente o mesmo nome de mídia (mas conteúdos distintos). Foi criado
com Python `zipfile.writestr`, que mantém nomes duplicados, ao contrário de
`ZipArchive::addFromString` na configuração padrão (que sobrescreve a entrada).

O teste `tests/backup_integrity_133.php` deve abrir este arquivo com a extensão
PHP `zip` real instalada no CI e confirmar os quatro nomes via `statIndex()`
**antes** de chamar `media_bundle_verify_manifest`. Esse validador deve recusar
nomes duplicados antes de tocar no banco de dados ou nos arquivos do usuário.

O fixture é dado de teste público; não contém banco real, senhas ou uploads.
