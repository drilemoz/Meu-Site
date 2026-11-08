# IguaNews 1.0.31: CSS modular sem alteração visual

## Motivação

O CSS original estava concentrado em `public/assets/css/style.css`, com cerca de 91 kB e regras de diversos módulos. Para reduzir conflitos ao manter o código, a versão 1.0.31 separa os **arquivos-fonte CSS** sem alterar a cascata existente. Não foram removidas nem reorganizadas declarações repetidas nesta etapa, pois mudanças de especificidade e ordem podem quebrar o layout.

## Estrutura

Os arquivos de origem ficam em `public/assets/css/modules/`, numerados para preservar **rigorosamente a ordem anterior**:

1. `01-foundation.css`: variáveis, base global, layout inicial e cabeçalho do portal/painel.
2. `02-installer.css`: instalador e diagnóstico inicial.
3. `03-editorial-articles.css`: artigos públicos e navegação editorial.
4. `04-transport.css`: ônibus, horários, importações e assistentes.
5. `05-site-layout-contact.css`: responsividade do portal, rodapé e contato.
6. `06-advertising.css`: espaços, anúncios locais e consentimento.
7. `07-editor-structured.css`: editor de blocos e componentes das notícias.
8. `08-analytics.css`: gráficos e métricas.
9. `09-editorial-admin.css`: formulários simplificados, lista e paginação administrativa.
10. `10-health.css`: painel de diagnóstico.
11. `11-editor-visual.css`: editor visual e prévias.
12. `12-media-library.css`: gerenciador de mídias.
13. `13-editor-mobile.css`: formulário de mídia, editor e notícias no celular, etapas.
14. `14-seo.css`: editor de URL e central de auditoria SEO.
15. `15-archive.css`: filtros e paginação no portal público.

A divisão respeita blocos contíguos do CSS original. Como certas regras históricas afetam mais de uma área, alguns módulos contêm estilos compartilhados. **Não mude a ordem dos arquivos** sem validação visual e de especificidade.

## Desenvolvimento

Editar o arquivo da área correspondente em `public/assets/css/modules/` e depois, na raiz do projeto:

```sh
python3 tools/build_css.py
python3 tools/build_css.py --check
php tests/css_modules_131.php
```

O script concatena os arquivos por uma lista explícita; ele não minifica, reordena ou transforma seletores. O `--check` apenas valida se `style.css` corresponde aos módulos, com código de saída diferente de zero quando há divergência.

**Produção e Termux:** somente o `public/assets/css/style.css` é requisitado no navegador. Ele já vem pronto no ZIP. Não é necessário Python, `npm`, preprocessador CSS nem chamadas HTTP extras para os módulos; mantenha a referência ao `style.css` nas páginas existentes.

## Garantias e limites

- O arquivo consolidado da 1.0.31 é **byte a byte igual** ao arquivo da 1.0.30 (SHA-256 `a71d0dd4ccefffbae4b94d67c7a926898d60cd11c6678931253d2298fac9073a`).
- Isso preserva regras, media queries, especificidade e comportamento esperado nos mesmos navegadores e evita solicitações separadas via `@import`.
- O teste `tests/css_modules_131.php` compara integralmente o conteúdo montado com o distribuído, verifica a ordem e mantém os pontos de inclusão PHP.
- O teste real de aparência no Chrome Android e o funcionamento de imagens/contatos na instalação Termux **não foram executados** no ambiente de geração.
- Banco de dados, backup lógico v10, pacote de mídias v2, rotas administrativas e três etapas do editor não foram alterados.

- v1.0.121: `18-file-manager.css` traz os estilos de **Ferramentas → Gerenciador de Arquivos**. Atualize o `ORDER` no `tools/build_css.py` ao adicionar novos módulos.

- v1.0.126: `19-server-info.css` estiliza o novo submenu Ferramentas → Informações do Servidor. O bundle continua servido em um único `style.css`.
