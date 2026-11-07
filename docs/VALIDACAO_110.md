# Validação 1.0.10

Execuções no ambiente local em 19/10/2026:

- Análise sintática em todos os PHP (`app`, `bin`, `public`, `tests`): passou.
- Node.js `--check` para JavaScript, incluindo novo editor: passou.
- Regressões: `tests/ads.php`, `bus.php`, `bus_import.php`, `bus_wizard.php`, `content.php`, `installer.php`, `portal_108.php`, `setup_diagnostics.php`, `smoke.php`: passaram.
- `tests/news_editor.php`: valida blocos, modelos, resumo SEO, parser de Open Graph/JSON-LD, URL HTTPS segura, bloqueio de redes privadas, CGNAT e restauração de backup v6/v7: passou.
- Renderização HTML isolada do novo editor: PHP retornou HTML sem erro fatal. A renderização Chromium automatizada foi bloqueada por restrição de acesso a endereços locais do ambiente.
- Estrutura e CRC do ZIP: verificados.

**Pendências externas de integração**: `pdo_mysql`, PHP `curl`, PHP `zip` e GD não estão presentes neste ambiente. Testar MariaDB real no Termux (migração/CRUD/backup), URL pública autorizada e upload/restauração do pacote completo na hospedagem. Importação por URL e backup ZIP exibem aviso/limitação quando falta a extensão. Nenhuma notícia foi publicada automaticamente.

**Plano de aceitação no Termux**: fazer backup v6 antes de atualizar; entrar no painel para criar tabelas; cadastrar notícia com 2 categorias e 3 blocos; anexar JPG+PDF; salvar e visualizar como público; criar/agendar uma notícia para data futura e conferir que permanece invisível; editar e restaurar uma revisão; gerar backup v7, exportar e restaurar pacote ZIP num banco de teste; importar somente metadados de URL confiável.
