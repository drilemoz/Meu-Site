# Leitura de notícias — v1.0.150

## Escopo

Melhorias exclusivas da página pública individual de notícia. Não houve alteração no ADM, conteúdo persistido, schema 37, backup v12 ou configuração da AWS.

## Experiência

- Título com entrelinhas e quebras mais naturais; resumo e parágrafos com largura e espaçamento legíveis no celular e desktop.
- Autoria, data (com elemento HTML `time`), visualizações e tempo de leitura separados visualmente. Estimativa: contagem Unicode de palavras do conteúdo estruturado ou texto legado, dividida por 200 palavras/minuto, arredondada para cima, mínimo 1 minuto. Não é medição do tempo gasto pelo visitante.
- Ao final da matéria, `Leia também` apresenta até três notícias publicadas da mesma categoria, excluindo a notícia aberta. A função existente `fetch_news()` usa `published_filter()` e não publica rascunhos ou material agendado para o futuro. Se nenhuma notícia corresponder, o bloco não aparece.
- Títulos, links e datas passam por funções de escape e construção de URLs já existentes. Imagens relacionadas mantêm carregamento preguiçoso. Falha de consulta apenas omite sugestões e é registrada sem impedir a leitura.
- Galeria, anexos, fonte de referência, compartilhamento e retorno à home foram preservados.

## Testes e limites

- `php -d zend.assertions=1 -d assert.exception=1 tests/public_article_250.php` exercita o template com banco falso, contagem, links e escape HTML.
- `python3 tests/public_article_ui_250.py` verifica layout 360/390/768/1280 px no Chromium offline, sem overflow.
- `python3 tools/build_css.py --check`, `python3 tools/audit_project.py` e suíte PHP/JS geral devem continuar aprovados.

A validação em MariaDB real, Chrome Android e instalação AWS exige teste separado. O ZIP de atualização atualiza arquivos de código, não o banco nem o conteúdo das notícias.
