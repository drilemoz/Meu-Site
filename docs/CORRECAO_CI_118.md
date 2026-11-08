# Correção do teste de atualização no CI — 1.0.118

## Evidência dos logs do workflow #33 (8 de novembro de 2026)

O job `Integracao MariaDB backup e restore`, na etapa `Ciclo integrado do atualizador ZIP com MariaDB isolado`, registrou sucesso no preparo do schema 37, backup lógico v12, instalação de cinco arquivos de teste, integridade, modo de manutenção, journal e rollback manual. A falha ocorreu no final do arquivo `tests/integration/updater_mariadb_217.php`, ao exigir mensagem `SHA-256` para um pacote alterado.

A fixture antiga substituía `<?php // modulo novo\n` (21 bytes) por `<?php // adulterado\n` (20 bytes). `igua_updater_unzip_verified()` rejeitou antes da etapa SHA, na validação de tamanho da entrada ZIP. Portanto o teste falhou por uma asserção específica demais, não por evidência de aceitação do pacote.

## Mudança

O ensaio agora duplica o ZIP correto duas vezes e verifica explicitamente:

1. **Tamanho divergente:** remove um byte da entrada, exige rejeição de `arquivo inesperado ou incorreto` antes da extração.
2. **SHA-256 divergente com tamanho igual:** altera os bytes sem mudar o comprimento, confirma a diferença dos hashes, exige rejeição por `SHA-256` durante a extração verificada.

Após ambos os cenários, o teste confirma que não houve novo registro de backup, marcador de manutenção persistente nem mudança nos arquivos originais do sandbox. O teste offline `tests/updater_ci_contract_217.php` exige a presença das guardas de tamanho e SHA independentes.

## Limites

A execução real dos dois casos corrigidos precisa ocorrer no GitHub Actions com MariaDB e extensão `ZipArchive`. Os testes PHP locais e as verificações estáticas não equivalem à prova de integração. Avisos `Undefined variable $geminiConfig` e `$geminiTest` em cenários de view simulados, aviso de Node.js 20 do `actions/checkout@v4` e fallback `io_uring` do container MariaDB apareceram no log, mas não foram a causa do exit code 255. Tratar esses avisos em revisão separada, evitando confundir trabalho adicional com a correção do teste que falhou.

Sem alteração funcional do portal, banco, atualizador, format ZIP v2 nem backup v12.
