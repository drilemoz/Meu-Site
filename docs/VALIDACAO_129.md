# IguaNews 1.0.29: refatoração do gerenciador de mídias

## Escopo

- Separação do controle de requisições e da fila de uploads em `public/assets/js/media-state.js`.
- Correção das referências ausentes no `media-library.js` da 1.0.28.
- Corrida de requisições: um resultado antigo não pode substituir o novo, ainda que o servidor responda depois; cancelar requisições anteriores quando suportado.
- Recuperação de upload parcial: após confirmar cada foto, remove-a da fila. Se a próxima falhar, mantém apenas as não confirmadas no botão **Reenviar pendentes**.
- Campo de foto já estilizado não é estilizado novamente pelo `file-inputs.js`.
- Sem mudança visual intencional, API, esquema SQL, backups (lógico v10/pacote de mídias v2) nem editor de três etapas.

## Comprovações

- `node tests/media_state_129.js`: corrida com respostas fora de ordem, invalidação por fechamento, upload parcial/retry, proteção contra concorrência e integração estática da página.
- `php tests/media_ui_128.php`: compatibilidade dos controles do gerenciador.
- Executar testes de regressão PHP/Node, `php -l`, `node --check` e teste HTTP do roteador.

## Limitações

- Testes Node simulam filas e respostas, não autenticam em servidor MariaDB.
- Em desconexão após gravação mas antes da resposta, não há garantia de deduplicação; conferir fotos na biblioteca antes de reenviar as pendentes.
- Requer validação física no navegador Android e no Termux com fotos reais.
