# Validação IguaNews 1.0.35

## Escopo

Refatoração dos **arquivos de origem** do editor JavaScript em oito módulos organizados por responsabilidade, montados num único `news-editor.js`. **Nenhuma lógica foi alterada**: o arquivo executado permanece exatamente igual ao da 1.0.34 (22.435 bytes; SHA-256 `150867e041ba12e94515ba66563c0a5b825059d40c60c6cc076b4ed40492f4f9`). O `tools/build_news_editor.py` monta e confere a integridade do arquivo final.

## Testes executados localmente

- `php -l`: 91 arquivos PHP aprovados (em `app`, `public`, `bin` e `tests`).
- `node --check`: 15 scripts JavaScript executáveis aprovados (arquivo consolidado, scripts públicos independentes e testes). Os 8 módulos são partes de um único escopo e não têm sintaxe executável isolada por design.
- 33 suítes PHP na raiz `tests/`: aprovadas.
- 6 suítes JavaScript na raiz `tests/`: aprovadas, inclusive `tests/news_editor_modules_135.js`.
- 8 cenários HTTP do `tests/seo_http_125.py`: aprovados.
- `python3 tools/build_css.py --check`: aprovado (CSS continua sincronizado).
- `python3 tools/build_news_editor.py --check`: aprovado.
- SHA-256 do JavaScript montado idêntico à 1.0.34: comprovado.

## Compatibilidade

- UI continua com **Básico → Texto → Foto**, sem quarta etapa.
- Autosave, recuperação de rascunhos, legendas/créditos de mídia, SEO e geração de slug preservados.
- Não foi adicionado script HTTP nem dependência em produção. Python só é necessário para regenerar o código em desenvolvimento.
- Banco e migrações: inalterados (revisão 33). Backup lógico v10 e pacote de mídias v2 inalterados.

## Pendências

- Não executados testes visuais em aparelho Android/Termux nem edição e gravação com MariaDB real nesta sessão.
- O workflow de integração adicionado na 1.0.34 não foi executado nesta sessão; verificação de recuperação real permanece pendente.
