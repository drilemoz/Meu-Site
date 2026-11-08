<?php
/** Ferramentas > Informações do Servidor. Dados apenas do processo PHP atual, sem acesso à infraestrutura do provedor. */
$serverInfo=igua_server_info_snapshot($db);
$disk=$serverInfo['disk'];
?>
<div class="server-info">
 <section class="server-info-hero" aria-labelledby="server-info-heading">
  <div class="server-info-intro">
   <span class="server-info-kicker">VISÃO DO AMBIENTE</span>
   <h2 id="server-info-heading">Informações do servidor</h2>
   <p>Dados fornecidos pelo PHP da hospedagem. Consulta pontual, sem alterar configurações ou arquivos.</p>
   <small>Atualizado em <?=h($serverInfo['collected_at'])?> · horário <?=h($serverInfo['timezone'])?></small>
  </div>
  <a class="btn outline small server-info-refresh" href="<?=h(admin_link('server_info'))?>">↻ Atualizar dados</a>
 </section>
 <div class="server-info-cards" aria-label="Resumo do ambiente">
  <section class="server-info-card"><span class="server-info-icon" aria-hidden="true">⌘</span><small>Versão do PHP</small><strong><?=h($serverInfo['php_version'])?></strong><span class="server-info-secondary"><?=h($serverInfo['php_sapi'])?></span></section>
  <section class="server-info-card"><span class="server-info-icon" aria-hidden="true">▣</span><small>Servidor web</small><strong><?=h($serverInfo['server_family'])?></strong><span class="server-info-secondary">Tipo informado pelo ambiente</span></section>
  <section class="server-info-card"><span class="server-info-icon" aria-hidden="true">◈</span><small>Banco de dados</small><strong><?=h($serverInfo['db']['status'])?></strong><span class="server-info-secondary"><?=h($serverInfo['db']['version']??'Versão indisponível')?></span></section>
  <section class="server-info-card"><span class="server-info-icon" aria-hidden="true">◷</span><small>Memória do PHP</small><strong><?=h($serverInfo['memory_limit'])?></strong><span class="server-info-secondary">Limite por processo PHP, não RAM do servidor</span></section>
 </div>
 <div class="server-info-panels">
  <section class="server-info-panel" aria-labelledby="server-disk-heading">
   <div class="server-info-panel-heading"><h3 id="server-disk-heading">Armazenamento</h3><span class="server-info-tag">Partição</span></div>
   <?php if($disk!==null): ?>
    <div class="server-info-space"><strong><?=h(igua_server_info_size($disk['free']))?></strong><span>livres de <?=h(igua_server_info_size($disk['total']))?></span></div>
    <div class="server-info-meter" role="progressbar" aria-label="Espaço ocupado" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?=h((string)$disk['percent'])?>"><span style="width:<?=h((string)$disk['percent'])?>%"></span></div>
    <p class="server-info-subnote"><?=h((string)$disk['percent'])?>% ocupado na partição acessível ao PHP. Isso <strong>não</strong> representa a franquia contratada no plano de hospedagem.</p>
   <?php else: ?><p class="server-info-subnote">O servidor não disponibilizou as medidas da partição.</p><?php endif; ?>
  </section>
  <section class="server-info-panel" aria-labelledby="server-limits-heading">
   <div class="server-info-panel-heading"><h3 id="server-limits-heading">Limites de execução</h3><span class="server-info-tag">PHP</span></div>
   <dl class="server-info-facts">
    <div><dt>Upload por arquivo</dt><dd><?=h($serverInfo['upload_limit'])?></dd></div>
    <div><dt>Tamanho total do POST</dt><dd><?=h($serverInfo['post_limit'])?></dd></div>
    <div><dt>Menor limite PHP aplicável</dt><dd><?=h(igua_server_info_size($serverInfo['max_upload_bytes']))?></dd></div>
    <div><dt>Tempo máximo de execução</dt><dd><?=h($serverInfo['execution_limit'])?></dd></div>
    <div><dt>Variáveis por requisição</dt><dd><?=h($serverInfo['max_input_vars'])?></dd></div>
    <div><dt>Pico de memória nesta consulta</dt><dd><?=h(igua_server_info_size($serverInfo['request_peak']))?></dd></div>
   </dl>
   <p class="server-info-subnote">O provedor, proxy e aplicativo podem impor limites adicionais.</p>
  </section>
  <section class="server-info-panel" aria-labelledby="server-extensions-heading">
   <div class="server-info-panel-heading"><h3 id="server-extensions-heading">Extensões PHP</h3><span class="server-info-tag">Disponibilidade</span></div>
   <div class="server-info-extensions">
    <?php foreach($serverInfo['extensions'] as $extension): ?>
     <div class="server-info-extension"><span><?=h($extension['label'])?></span><span class="server-info-pill <?=$extension['enabled']?'is-on':'is-off'?>"><?= $extension['enabled']?'Ativa':'Ausente' ?></span></div>
    <?php endforeach; ?>
   </div>
  </section>
  <section class="server-info-panel" aria-labelledby="server-environment-heading">
   <div class="server-info-panel-heading"><h3 id="server-environment-heading">Ambiente e pastas</h3><span class="server-info-tag">Leitura</span></div>
   <dl class="server-info-facts">
    <div><dt>HTTPS nesta requisição</dt><dd><?= $serverInfo['https']?'Detectado':'Não confirmado' ?></dd></div>
    <div><dt>Cache OPcache</dt><dd><?= $serverInfo['opcache']?'Ativo nesta execução':'Não confirmado' ?></dd></div>
    <div><dt>Armazenamento privado</dt><dd><?= $serverInfo['write_storage']?'Gravável':'Não gravável ou ausente' ?></dd></div>
    <div><dt>Pasta de uploads</dt><dd><?= $serverInfo['write_uploads']?'Gravável':'Não gravável ou ausente' ?></dd></div>
   </dl>
   <p class="server-info-subnote">CPU, RAM física, uptime e uso total da hospedagem não são medidos aqui. Para problemas de configuração, utilize a Central de Saúde.</p>
  </section>
 </div>
 <p class="server-info-footer">Apenas administradores podem abrir esta página. Nenhum comando de shell, varredura de rede ou diagnóstico externo é executado.</p>
</div>
