<?php
declare(strict_types=1);
/** Central de Segurança: integra o mesmo layout, navegação e rodapé do admin. */
function igua_security_admin_view(): void {
    $days=is_string($_GET['days']??null)?(int)$_GET['days']:7;
    if(!in_array($days,[1,7,30],true))$days=7;
    $level=is_string($_GET['level']??null)?$_GET['level']:'all';
    $type=is_string($_GET['type']??null)?$_GET['type']:'all';
    $filterIp=is_string($_GET['ip']??null)?trim($_GET['ip']):'';
    if($filterIp!==''&&igua_security_ip($filterIp)!==$filterIp)$filterIp='';
    $data=igua_security_history($days,$level,$type,$filterIp);
    $count=$data['summary'];$types=igua_security_types();
    $blocks=igua_security_blocks_read();
    $store=igua_security_root();
    ?>
    <section class="panel security-intro">
      <div class="panel-heading"><div class="compact-heading"><strong>Monitoramento do painel</strong><details class="compact-help"><summary aria-label="Ajuda sobre monitoramento" title="Como funciona?">?</summary><div class="compact-help-content">Registra acessos, tentativas suspeitas e operações administrativas observadas pelo PHP. Isso não equivale a monitoramento do firewall ou da hospedagem.</div></details></div><a class="btn outline small" href="<?=h('/admin.php?action=security&days='.$days)?>">↻ Atualizar</a></div>
      <div class="muted tiny security-updated">Atualizado às <?=h(date('H:i'))?> · <?= $days ?> dia(s) <details class="compact-help"><summary aria-label="Detalhes dos registros" title="Detalhes dos registros">?</summary><div class="compact-help-content">Retenção: <?=IGUA_SECURITY_RETENTION_DAYS?> dias. Horário de Brasília. Dados locais do PHP, com limites de observação.</div></details></div>
      <?php if($store===null): ?><div class="alert error">O diretório privado <code>storage/security/monitor</code> não está disponível para escrita. Eventos não estão sendo persistidos; confira as permissões e a proteção da pasta.</div><?php endif; ?>
      <div class="security-summary">
        <div><span>Eventos registrados</span><strong><?=number_format($count['total'],0,',','.')?></strong></div>
        <div><span>Alertas de atenção</span><strong><?=number_format($count['warning'],0,',','.')?></strong></div>
        <div><span>Eventos críticos</span><strong><?=number_format($count['critical'],0,',','.')?></strong></div>
        <div><span>Bloqueios ativos</span><strong><?=count($blocks)?></strong></div>
      </div>
      <details class="compact-help security-metric-help"><summary aria-label="Como interpretar os indicadores" title="Como interpretar os números?">?</summary><div class="compact-help-content">Contagem de eventos gravados nos últimos <?= $days ?> dia(s), antes dos filtros. A falta de alertas não comprova ausência de invasões.</div></details>
    </section>
    <section class="panel"><h2>Alertas e detecção</h2>
      <div class="security-alerts">
        <?php if($count['login_limited']>0): ?><p class="alert warning"><strong>Limite de login acionado:</strong> <?= $count['login_limited'] ?> tentativa(s) bloqueada(s) por excesso de falhas no período. Verifique o histórico abaixo.</p><?php endif; ?>
        <?php if($count['critical']>0): ?><p class="alert warning"><strong>Eventos críticos:</strong> <?= $count['critical'] ?> registro(s). Esta categoria representa risco ou bloqueio, não confirmação de invasão.</p><?php endif; ?>
        <?php if($count['warning']===0&&$count['critical']===0): ?><p class="muted">Nenhum evento de atenção ou crítico no período consultado. Monitoramento limitado às rotas executadas em PHP.</p><?php endif; ?>
      </div>
      <details class="compact-help"><summary aria-label="Explicação sobre detecção" title="Limites da detecção">?</summary><div class="compact-help-content">São permitidas até 5 falhas de login a cada 15 minutos por IP. Bloqueios manuais afetam apenas o painel. Requisições barradas antes do PHP não aparecem no histórico.</div></details>
    </section>
    <section class="panel"><div class="panel-heading"><div><h2>Histórico de eventos</h2><p class="muted">Mostrando até 100 eventos recentes dos <?=number_format($data['matched'],0,',','.')?> correspondentes aos filtros.</p></div></div>
      <form method="get" action="/admin.php" class="security-filters">
        <input type="hidden" name="action" value="security">
        <label>Período<select name="days"><option value="1" <?=$days===1?'selected':''?>>24 horas</option><option value="7" <?=$days===7?'selected':''?>>7 dias</option><option value="30" <?=$days===30?'selected':''?>>30 dias</option></select></label>
        <label>Gravidade<select name="level"><option value="all">Todas</option><?php foreach(['info'=>'Informativo','warning'=>'Atenção','critical'=>'Crítico'] as $key=>$label): ?><option value="<?=h($key)?>" <?=$level===$key?'selected':''?>><?=h($label)?></option><?php endforeach; ?></select></label>
        <label>Tipo<select name="type"><option value="all">Todos</option><?php foreach($types as $key=>$value): ?><option value="<?=h($key)?>" <?=$type===$key?'selected':''?>><?=h($value['label'])?></option><?php endforeach; ?></select></label>
        <label>IP exato<input name="ip" type="text" inputmode="text" maxlength="45" autocomplete="off" placeholder="Ex.: 203.0.113.1" value="<?=h($filterIp)?>"></label>
        <button class="btn primary" type="submit">Filtrar</button><a class="btn outline" href="/admin.php?action=security">Limpar</a>
      </form>
      <?php if(!$data['rows']): ?><p class="muted">Nenhum evento encontrado para os filtros. Os registros começam após a instalação desta versão.</p>
      <?php else: ?><div class="security-events" role="region" aria-label="Eventos recentes">
        <?php foreach($data['rows'] as $r): ?>
          <article class="security-event">
            <div class="security-event-main"><span class="security-level security-level-<?=h($r['level'])?>"><?=h(['info'=>'Informativo','warning'=>'Atenção','critical'=>'Crítico'][$r['level']])?></span><strong><?=h($types[$r['type']]['label'])?></strong></div>
            <div class="security-event-details"><time><?=h($r['at'])?></time><span>IP: <?=h($r['ip']?:'não identificado')?></span><?php if($r['actor']): ?><span>Usuário #<?=(int)$r['actor']?></span><?php endif; ?><?php if($r['target']): ?><span>Ação: <code><?=h($r['target'])?></code></span><?php endif; ?></div>
          </article>
        <?php endforeach; ?>
      </div><?php endif; ?>
    </section>
    <section class="panel"><div class="panel-heading"><div><h2>Bloqueios temporários de IP</h2><p class="muted">Bloqueia o acesso a <code>/admin.php</code> no aplicativo. Não bloqueia visitas públicas nem tráfego no servidor/CDN.</p></div></div>
      <form class="security-block-form" method="post" action="/admin.php?action=security_block" data-confirm="Aplicar bloqueio temporário ao IP informado? Confirme que você não depende desse IP para acessar o painel.">
        <?=csrf_field()?>
        <label>IP público<input name="ip" required maxlength="45" placeholder="Ex.: 8.8.8.8" autocomplete="off"></label>
        <label>Duração<select name="minutes"><option value="15">15 minutos</option><option value="60" selected>1 hora</option><option value="360">6 horas</option><option value="1440">24 horas</option></select></label>
        <label>Senha administrativa<input name="confirm_password" required type="password" autocomplete="current-password"></label>
        <button class="btn primary" type="submit">Bloquear IP</button>
      </form>
      <?php if($blocks): ?><div class="security-block-list">
      <?php foreach($blocks as $ip=>$block): ?><div class="security-block-row"><div><strong><?=h($ip)?></strong><small class="muted"> Até <?=h(date('d/m/Y H:i',$block['until']))?> · por usuário #<?=(int)$block['by']?></small></div>
        <form method="post" action="/admin.php?action=security_unblock" data-confirm="Desbloquear este IP para acesso ao painel?"><?=csrf_field()?><input type="hidden" name="ip" value="<?=h($ip)?>"><label class="security-unblock-password">Senha <input type="password" name="confirm_password" required autocomplete="current-password" aria-label="Senha administrativa para desbloquear"></label><button type="submit" class="btn outline small">Desbloquear</button></form>
      </div><?php endforeach; ?></div>
      <?php else: ?><p class="muted">Nenhum IP com bloqueio manual ativo.</p><?php endif; ?>
      <p class="muted tiny">Não existe bloqueio automático por suspeita: a limitação de login é independente e continua ativa. Um IP pode representar vários visitantes atrás de NAT ou proxy. Não confie em cabeçalhos de IP enviados pelo navegador.</p>
    </section>
    <section class="panel security-privacy"><details class="security-privacy-details"><summary>Privacidade e limites de detecção <span aria-hidden="true">?</span></summary><p class="muted">Os logs registram data, categoria, IP de origem observado pelo PHP, identificador numérico do usuário e ação conhecida. Não coletam senha, cookie, token, e-mail, corpo de formulário ou URL completa. Os arquivos privados não entram nos ZIPs de atualização e são eliminados após 30 dias quando houver novos eventos. Para enxergar ataques barrados antes do PHP, integre logs da hospedagem ou WAF futuramente.</p></details></section>
    <?php
}
