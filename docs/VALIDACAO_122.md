# IguaNews 1.0.22 | Validação do editor de mídias

## Correções entregues

- `public/assets/js/news-editor.js`: capturar e preservar os valores digitados nos campos de legenda/crédito por grupo (`gallery` / `attachment`) ao redesenhar a prévia. Substituição intencional reinicia apenas o grupo selecionado. Valores de atributos HTML continuam escapados.
- `public/assets/js/news-upload.js`: identificador de geração por input para evitar sobrescrita tardia por otimização antiga; pendências por campo para não aguardar tarefa de seleção descartada; erros separados por campo, sem ocultar falhas ao mudar outro input. Arquivos sem otimização não exigem `DataTransfer` para continuar.
- `tests/editor_media_122.js`: simulações reais das funções JS (eventos, File/DataTransfer e processamento assíncrono com respostas fora de ordem), sem dependências npm.
- `tests/editor_122.php`: regressões estruturais de três etapas, nomes de campos e mecanismos de proteção de upload.

## Compatibilidade e privacidade

- Fluxo do editor continua **Básico → Texto → Foto**; campos e rotas de upload não mudaram.
- Sem alteração de SQL, backup lógico **v9** e pacote de mídia **v2** preservados.
- Autosave de texto não faz backup de arquivos locais ainda não enviados. Seleções de arquivo só são enviadas quando o usuário usa **Salvar notícia**.
- Não modifica `storage/config.php`, `storage/backups/`, `storage/media/`, `storage/analytics.key` nem `public/uploads/`.

## Verificação

- `php -l` para todos os arquivos PHP.
- `node --check` para todos os arquivos JavaScript.
- `for f in tests/*.php; do php "$f"; done`.
- `node tests/editor_media_122.js` para testes de comportamento simulados.
- Integridade CRC do ZIP e inventário de arquivos/linhas.

## Pendências

A execução do editor em Chrome para Android/Termux com uploads reais, limites do PHP, banco MariaDB e validação visual de diferentes larguras de tela ainda precisa ser feita no aparelho. Os testes Node são simulados e não provam funcionamento em todos os navegadores.
