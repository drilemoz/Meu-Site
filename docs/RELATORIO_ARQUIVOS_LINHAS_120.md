# IguaNews v1.0.20 | Relatório de arquivos e alterações

## Resumo
- Base: IguaNews v1.0.19.
- Versão entregue: **1.0.20**.
- Arquivos inventariados (antes de incluir estes relatórios): **125**.
- Arquivos textuais: **109**.
- Linhas em arquivos textuais: **8719**.
- Banco: **sem migração**; backups lógicos v9 e mídias v2 preservados.

## Arquivos afetados e referências
- `app/news_admin_view.php`: 102 linhas; L22 (news-editor-steps), L43 (news-publish-options), L90 (news-editor-footer)
- `public/assets/js/news-editor.js`: 187 linhas; L38 (function changeTab), L56 (function canLeave), L68 (function navigateTo), L185 (form.addEventListener('submit')
- `public/assets/css/style.css`: 450 linhas; L436 (/* IguaNews 1.0.20)
- `tests/editor_119.php`: 24 linhas
- `tests/editor_120.php`: 22 linhas
- `README.md`: 275 linhas
- `CHANGELOG.md`: 172 linhas
- `AGENTS.md`: 53 linhas
- `docs/EDITOR_2_0.md`: 19 linhas
- `docs/VALIDACAO_120.md`: 17 linhas
- `VERSION`: 1 linhas

## Efeito funcional
- Fluxo **Nova notícia** e **Editar matéria** reduzido a três etapas: Básico → Texto → Foto.
- A etapa **4. Confira e salve** foi removida, assim como a opção inicial de página contínua.
- SEO e destaque seguem acessíveis dentro de Básico, em opções recolhidas. SEO automático continua executado antes de salvar.
- Botão **Salvar notícia** aparece no último cartão, após Foto; validação ao avançar.
- Nenhum campo de dados, mídia, fonte ou status removido do formulário.

## Validação
- Sintaxe PHP e JavaScript e 19 suítes de testes PHP aprovadas.
- A tentativa de prévia automatizada no navegador Chromium não concluiu; visual mobile e integração MariaDB exigem verificação no Termux.
- Estes relatórios não são incluídos nas contagens de arquivo/linha de origem, para evitar referências circulares.
