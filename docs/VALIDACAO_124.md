# IguaNews 1.0.24 | Validação de URLs e SEO

- Base: ZIP v1.0.23 do Google Drive conectado.
- Alterações: slug manual, normalização, detecção de conflito, tabela de histórico, HTTP 301, links amigáveis opcionais, roteador PHP de desenvolvimento, regra Apache, backup lógico v10 e parser compatível v1–v10.
- Arquivos críticos: `app/article_workflow.php`, `app/bootstrap.php`, `app/schema.php`, `app/backup.php`, `app/news_admin_view.php`, `public/admin.php`, `public/index.php`, `public/router.php`, `public/.htaccess`, `public/assets/js/news-editor.js`, `public/assets/css/style.css`, `tests/seo_url_124.php` e `tests/seo_url_124.js`.
- Testes executados em PHP CLI 8.4.24 e Node 22.16.0: sintaxe de 58 arquivos PHP e 10 JS; 23 suítes PHP e 3 testes JavaScript aprovados.
- `php tests/seo_url_124.php`: normalização, conflitos atuais/históricos, troca de slug e compatibilidade de parser v9/v10 com banco simulado (não é MySQL real).
- `node tests/seo_url_124.js`: normalização usando função JS real, prévia de endereço e existência de campos.
- Teste HTTP local com servidor PHP embutido e roteador: CSS existente 200, rotas `/noticia/teste` e `/categoria/cidade` alcançam aplicação e redirecionam ao setup 303 (portal não instalado neste ambiente); rota desconhecida 404. **Não testado HTTP 301 de notícia publicada**, por falta de banco MariaDB.
- Integridade estrutural/CRC do ZIP final: verificação por `zipfile.testzip()`.

- Pendências: migração MySQL real, Apache, navegador Android/Termux, atualização de URLs externas e restauração de backup completo no banco.
- Atualização deve preservar `storage/config.php`, `storage/backups/`, `storage/media/`, `storage/analytics.key`, `public/uploads/`. Migração SQL é incremental (CREATE TABLE IF NOT EXISTS).
