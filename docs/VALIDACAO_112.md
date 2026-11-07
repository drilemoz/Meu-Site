# Validação IguaNews 1.0.12

## Escopo

- Simplificação visual do editor em quatro etapas: Básico, Texto, Foto, Publicar.
- Campos avançados recolhidos por padrão, exceto matérias antigas com blocos, que abrem a edição estruturada para não ocultar o conteúdo principal.
- Guia PDF interno e arquivo Markdown no projeto, além de link de ajuda no editor.
- Três notícias de exemplo exclusivamente fictícias, publicadas apenas após ação autenticada, com backup preventivo, sem substituir registros próprios, sem duplicação por slug e marcadas para não indexação.
- Preservação do sinalizador `is_demo` ao editar notícias de exemplo.
- Sem migração MySQL e sem mudança no backup v8.

## Testes executados

- `php -l` de todos os arquivos PHP.
- `node --check` em todos os JS.
- `php tests/smoke.php` e demais scripts de tests/.
- `php tests/editor_examples.php`: 3 exemplos fictícios, blocos expansíveis válidos, imagens disponíveis e SEO.
- Integridade e conteúdo do ZIP verificados, PDF renderizado em 3 páginas para inspeção visual.

## Pendente

- Publicação integrada e gravação dos exemplos em MariaDB real no Termux.
- Navegação visual e rolagem em navegadores Android reais.
