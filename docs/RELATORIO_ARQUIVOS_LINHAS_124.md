# IguaNews 1.0.24 | Relatório de arquivos e alterações

## Resumo
- Base verificada no Drive: 1.0.23 (ZIP 1.464.177 bytes).
- Entrega: **1.0.24**, código completo em ZIP.
- Novos slugs editoriais com nomes reservados em histórico; tabela incremental `article_slug_redirects`.
- URLs bonitas opcionais por Apache ou roteador para servidor PHP no Termux; formato querystring permanece padrão por compatibilidade.
- Backup lógico v10 com leitura dos formatos v1–v10; pacote de mídias v2 inalterado.
- Nenhuma notícia, usuário, configuração ou mídia real foi migrada neste ambiente.
- Verificações: lint 58 arquivos PHP + 10 JavaScript; 23 suítes PHP + 3 suítes JS; teste básico de roteamento HTTP local sem banco.
- Pendentes: MariaDB real, restore v10 com tabelas reais, Apache em hospedagem, navegador Android, redirecionamento 301 publicado.

## Arquivos editados e linhas de referência
- `AGENTS.md`: modificado; 61 linhas. Alterações: L60–61
- `CHANGELOG.md`: modificado; 205 linhas. Alterações: L1–9
- `README.md`: modificado; 307 linhas. Alterações: L3–12
- `VERSION`: modificado; 1 linhas. Alterações: L1
- `app/article_workflow.php`: modificado; 100 linhas. Alterações: L14–63,L87
- `app/backup.php`: modificado; 304 linhas. Alterações: L11–12,L22,L70–77,L83,L104
- `app/bootstrap.php`: modificado; 150 linhas. Alterações: L55–62
- `app/news_admin_view.php`: modificado; 105 linhas. Alterações: L45–47
- `app/schema.php`: modificado; 220 linhas. Alterações: L48–49
- `docs/BACKUP.md`: modificado; 98 linhas. Alterações: L1,L7,L22,L48,L78,L93–98
- `docs/MIGRACAO.md`: modificado; 45 linhas. Alterações: L42–45
- `docs/SEO_URLS_124.md`: adicionado; 45 linhas. Alterações: (arquivo novo)
- `docs/USUARIOS_E_SEO.md`: modificado; 33 linhas. Alterações: L30–33
- `docs/VALIDACAO_124.md`: adicionado; 13 linhas. Alterações: (arquivo novo)
- `public/.htaccess`: modificado; 11 linhas. Alterações: L6–11
- `public/admin.php`: modificado; 536 linhas. Alterações: L36,L51,L198,L204,L254,L265,L388
- `public/assets/css/style.css`: modificado; 458 linhas. Alterações: L451–458
- `public/assets/js/news-editor.js`: modificado; 272 linhas. Alterações: L156–171
- `public/index.php`: modificado; 180 linhas. Alterações: L5,L58–73,L77
- `public/router.php`: adicionado; 15 linhas. Alterações: (arquivo novo)
- `tests/health.php`: modificado; 31 linhas. Alterações: L15,L20
- `tests/media_118.php`: modificado; 21 linhas. Alterações: L13–14
- `tests/seo_url_124.js`: adicionado; 21 linhas. Alterações: (arquivo novo)
- `tests/seo_url_124.php`: adicionado; 60 linhas. Alterações: (arquivo novo)

## Diretórios persistentes protegidos
O ZIP não inclui `storage/config.php`, `storage/backups/`, `storage/media/`, `storage/analytics.key` nem mídias em `public/uploads/` do ambiente do usuário. Ao atualizar o Termux, **preserve essas pastas/arquivos**. Nenhum backup foi apagado.

## Inventário
- **145 arquivos inventariados**, 129 textuais, **10509 linhas textuais** (antes dos relatórios desta versão).
- A tabela completa de nomes, bytes, tipo e linhas está em `RELATORIO_ARQUIVOS_LINHAS_124.csv`. Os dois relatórios da própria v1.0.24 não entram em suas contagens, para evitar autorreferência circular.

## Testes e limitações
- PHP CLI 8.4.24, Node.js 22.16.0; sintaxe e suites aprovadas. Simulações de PDO não equivalem à execução no MariaDB.
- HTTP local sem banco: CSS 200; `/noticia/teste` e `/categoria/cidade` alcançam setup (303 porque não há instalação); URL desconhecida 404. Não foi possível testar 301 com notícia real.
- Veja `docs/SEO_URLS_124.md` e `docs/VALIDACAO_124.md` para ativação e testes manuais.
