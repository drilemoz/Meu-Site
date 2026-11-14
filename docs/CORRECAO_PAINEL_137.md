# Correção de tela vazia no admin — 1.0.137

## Sintoma observado

No Chrome Android, `Ferramentas → Informações do Servidor` exibia o cabeçalho e o título, sem dados abaixo. O botão ☰ não respondia.

## Causa comprovada no código

`app/admin_views/routes/server_info.php` invoca `igua_server_info_snapshot($db)`. A definição estava em `app/server_info.php`, **mas `public/admin.php` não incluía este arquivo**. Os testes antigos (`server_info_226.php` e `server_info_227.php`) exigiam o módulo diretamente e não detectavam a falta no controlador. A exceção fatal interrompia a resposta no meio do layout, antes da tag de `admin-ui.js` no rodapé. Portanto o próprio menu também ficava inerte.

## Correções

1. `public/admin.php`: `require_once __DIR__.'/../app/server_info.php'` ao lado das demais dependências.
2. `app/admin_views/layout.php`: carrega `admin-ui.js` no `<head>` com `defer`, exatamente uma vez.
3. `app/admin_views/pages.php`: captura falhas inesperadas de `server_info` e `dns_diagnostic`, descarta o HTML incompleto e mostra mensagem genérica; a exceção é encaminhada ao log privado por `igua_health_log` e a resposta indica HTTP 500.
4. `app/server_info.php`: trata falta de `disk_free_space` / `disk_total_space` ou exceções, exibindo dados de disco como indisponíveis.
5. `tests/admin_diagnostic_render_237.php`: testa a dependência do controlador, carregamento antecipado do menu, renderização de Informações do Servidor e tratamento de uma falha simulada no DNS sem vazamento de detalhes.

## Aplicação e verificação

Instalar o ZIP `Atualizacao-v1.0.137.zip` pelo atualizador existente ou pelo procedimento de implantação seguro, preservando `storage/` e `public/uploads/`. Em seguida, carregar `admin.php?action=server_info` e abrir o botão ☰, inclusive após navegar para `admin.php?action=dns_diagnostic`. Confirmar que ambos apresentam conteúdo. Consultas DNS externas podem continuar indisponíveis se a hospedagem bloquear cURL ou saída HTTPS; isso não deve impedir o menu. Se houver erro, consultar a Central de Saúde e os logs privados.

A compilação PHP, os testes locais e a integridade ZIP **não comprovam** que o servidor de produção está atualizado, nem substituem a verificação real na hospedagem.
