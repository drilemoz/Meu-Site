# IguaNews 1.0.22 | Relatório de arquivos e alterações

## Resumo
- Base: **1.0.21** (ZIP anexado, versão mais recente confirmada na pasta do Google Drive antes desta atualização).
- Versão: **1.0.22**. Código do projeto existente, sem recriação nem migração SQL.
- Inventário antes de incluir os dois relatórios internos: **133** arquivos, **117** textuais, **9538** linhas de texto, **16** binários.
- Base de dados: backup lógico **v9**, pacote de mídias **v2**. Nenhum arquivo de usuário ou segredo incluído.

## Arquivos alterados e linhas de referência
- `AGENTS.md`: modificado; 57 linhas; L56–57.
- `CHANGELOG.md`: modificado; 189 linhas; L1–8.
- `README.md`: modificado; 290 linhas; L3–12.
- `VERSION`: modificado; 1 linhas; L1–1.
- `docs/EDITOR_2_0.md`: modificado; 23 linhas; L13–16.
- `docs/VALIDACAO_122.md`: novo; 27 linhas.
- `public/assets/js/news-editor.js`: modificado; 218 linhas; L158–192.
- `public/assets/js/news-upload.js`: modificado; 74 linhas; L6–6, L33–37, L42–48, L51–51, L53–58, L65–65, L67–67.
- `tests/editor_122.php`: novo; 15 linhas.
- `tests/editor_media_122.js`: novo; 150 linhas.

## Efeito funcional
- Na etapa **Foto**, legendas e créditos preenchidos para galeria/anexos permanecem ao mudar o outro grupo, inclusive depois da otimização assíncrona de fotos. Substituir uma seleção reinicia somente os campos daquele grupo.
- Upload de fotos grandes ignora resultados de otimizações de seleções antigas. Uma tarefa descartada não impede envio após seleção atual ser preparada.
- Erro em um campo de imagem não desaparece por mudança no outro campo, evitando envio inadvertido.
- Interface continua com **Básico → Texto → Foto**. Uploads de arquivos ainda não são cobertos pelo autosave; só vão ao servidor ao clicar **Salvar notícia**.

## Validação executada
- `php -l` em **55/55 PHP**: aprovado.
- `node --check` em **8/8 JS**: aprovado.
- `php tests/*.php` executados individualmente: **21/21 suítes** aprovadas.
- `node tests/editor_media_122.js`: testes de comportamento simulados de preservação de campos, seleção substituída, race assíncrona, arquivo descartado e erro por campo aprovados.
- CRC de todos os membros do ZIP e inventário de entrega conferidos.

## Limitações
- **Não testados:** uploads físicos no Chrome Android/Termux, compressão de fotos reais em aparelhos diversos, MariaDB e restauração integral de backup; requerem validação em ambiente real.
- Este relatório e o CSV completo são fornecidos como arquivos externos e cópias dentro de `docs/` (não contabilizados no inventário para evitar circularidade).
