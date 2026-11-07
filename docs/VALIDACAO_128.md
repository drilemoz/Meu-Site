# Validação 1.0.28

## Objetivo
Aprimorar a experiência do painel no celular, principalmente na lista de notícias e no gerenciador de mídias, preservando a central de publicidade com anúncios de exemplo.

## Mudanças validadas
- Lista de notícias no admin com layout móvel mais compacto, títulos truncados em duas linhas e ícones de ação no topo direito.
- Atalho de publicação rápida por ícone, alternando entre rascunho e publicada sem abrir a notícia.
- Galeria de mídias com busca compactada em ícone no celular e campo expansível.
- Upload de imagens com envio automático após selecionar os arquivos, mantendo o botão manual como alternativa.
- Preservação do recurso para cadastrar 6 anúncios fictícios de exemplo na central de publicidade.

## Verificações executadas
- PHP lint em 65 arquivos PHP.
- Verificação sintática `node --check` em 12 arquivos JavaScript.
- Execução de 26 testes PHP em `tests/`.
- Execução de 4 testes JavaScript em `tests/`.
- Execução de 1 teste Python (`tests/seo_http_125.py`).

## Testes de destaque
- `tests/article_workflow.php`
- `tests/media_ui_128.php`
- `tests/ads.php`
- `tests/media_118.php`
- `tests/editor_119.php`
- `tests/editor_media_122.js`

## Pendências conhecidas
- Não houve teste visual humano em navegador Android real dentro deste ambiente.
- Não foi executado MariaDB/servidor PHP completo com interação manual de upload, embora a lógica e os testes estruturais tenham sido validados.
- O upload para o Google Drive não foi realizado neste ambiente porque a ferramenta de Drive não está disponível neste turno.
