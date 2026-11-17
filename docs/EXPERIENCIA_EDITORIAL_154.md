# Ações das matérias e navegação pública — 1.0.154

## Site público

- A linha de assinatura mostra autor, data e visualizações, com singular quando a contagem é 1. A assinatura editorial selecionada prevalece sobre o nome da conta. Se ambos faltarem, usa-se a Redação.
- Compartilhar, Copiar link e tempo estimado de leitura ficam juntos nessa ordem. As duas ações são ícones com rótulos acessíveis, área de toque de 44 px e estilo comum ao relógio. O compartilhamento nativo e a cópia manual de contingência são preservados.
- **Voltar ao topo** fica fixo no canto direito das páginas públicas, acima da barra inferior no celular. É uma âncora que funciona sem JavaScript e respeita a preferência por movimento reduzido.

## Editor e categoria

- Na etapa **Classificação**, selecione **Autor da notícia** ou toque em **+ Autor**. O novo perfil é selecionado no formulário depois de salvar. Isso não cria conta de acesso nem publica a matéria. A criação exige a permissão de taxonomia e segue o POST com CSRF. Biografias continuam editáveis na tela **Autores**.
- **Economia** faz parte da lista inicial ao instalar um portal novo. O seed não sobrescreve registros existentes e a carga de amostras também reaproveita a categoria, se já cadastrada. Atualizações de código não inserem categorias automaticamente no banco em produção. Em portal já instalado sem Economia, crie a categoria pelo painel ou pelo editor.
- Schema 37, backup v12, mídias e conteúdo existente são preservados pelo ZIP de atualização. O ZIP completo é para código-fonte, não para o formulário de atualização.

## Verificação

- PHP local: `tests/editorial_navigation_254.php` e `tests/public_article_250.php`.
- Chromium: `tests/public_article_ui_250.py`, `tests/public_ux_248.py` e `tests/author_taxonomy_254.py` com ação administrativa simulada. O teste no servidor de produção e no Android físico deve ser feito após instalar.
