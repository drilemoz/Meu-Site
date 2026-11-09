<?php
/** Ferramentas > Diagnóstico de erros: sem comandos shell nem acesso a arquivos arbitrários. */
$diagnostics=igua_error_diagnostics_snapshot($db);
?>
<section class="panel error-diagnostics-page">
 <div class="panel-heading"><h2>Diagnóstico de erros</h2><a class="btn outline small" href="<?=h(admin_link('error_diagnostics'))?>">↻ Atualizar</a></div>
 <p class="muted">Verificações do PHP, biblioteca de mídias, Gemini e eventos técnicos recentes. Apenas dados disponíveis ao portal. Nenhum teste externo é executado automaticamente.</p>
 <div class="error-diag-grid">
 <?php foreach($diagnostics as $check): ?><article class="error-diag-item error-diag-<?=h($check['state'])?>"><strong><?=h($check['name'])?></strong><p><?=h($check['text'])?></p></article><?php endforeach; ?>
 </div>
 <div class="panel-heading"><h3>Eventos recentes</h3><details class="compact-help"><summary aria-label="Ajuda sobre os eventos técnicos">?</summary><div class="compact-help-content">Captura falhas PHP registradas e erros JavaScript relatados pelo painel, apenas códigos técnicos sanitizados. Não captura erros anteriores, falhas bloqueadas pelo Nginx antes do PHP nem todas as ocorrências. Não armazena IP, senhas, respostas de API ou texto de notícias.</div></details></div>
 <?php $events=igua_health_log_lines(60); if($events): ?><div class="health-log error-diag-log" role="region" aria-label="Eventos técnicos privados"><?php foreach($events as $line): ?><code><?=h($line)?></code><?php endforeach; ?></div><?php else: ?><p class="muted">Nenhum erro registrado. Isso não comprova ausência de falhas.</p><?php endif; ?>
 <details class="compact-help"><summary>Como interpretar os códigos dos eventos?</summary><div class="compact-help-content"><p><code>MEDIA_TIMEOUT</code>: biblioteca demorou demais. <code>MEDIA_HTTP</code>: servidor retornou erro ou resposta inesperada. <code>GEMINI_TIMEOUT</code>: prazo da consulta expirou. <code>GEMINI_HTTP</code>: falha na solicitação. <code>JS_RUNTIME</code>/<code>JS_PROMISE</code>: erro do navegador. <code>PHP_FATAL</code>/<code>PHP_WARNING</code>: erro detectado pelo PHP. Para a causa exata, consulte logs restritos no servidor.</p></div></details>
 <p><a class="btn outline small" href="<?=h(admin_link('health'))?>">Saúde do sistema</a> <a class="btn outline small" href="<?=h(admin_link('integrations'))?>">Verificar APIs</a> <a class="btn outline small" href="<?=h(admin_link('server_info'))?>">Informações do servidor</a></p>
 <details class="compact-help"><summary>O que ainda precisa ser visto por SSH?</summary><div class="compact-help-content"><p>Consulte os erros do Nginx e do PHP-FPM com as permissões corretas (por exemplo, <code>journalctl -u php8.3-fpm</code> e <code>sudo tail /var/log/nginx/error.log</code>). Não disponibilize arquivos de log completos publicamente.</p></div></details>
</section>
