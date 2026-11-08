    <details class="int-service int-ads" id="google-adsense">
      <summary><span class="int-service-icon" aria-hidden="true">◇</span><span class="int-service-copy"><strong>Google AdSense</strong><small>Identidade da conta</small></span><span class="int-state <?= $adOn?'int-ready':'int-off' ?>"><?= $adOn?'ID salvo':'Pendente' ?></span><span class="int-chevron" aria-hidden="true"></span></summary>
      <div class="int-service-body">
        <div class="int-guide-row"><strong>Configurar Google AdSense</strong><details class="compact-help int-guide-help"><summary aria-label="Guia completo: ID ca-pub, OAuth e relatórios AdSense" title="Como configurar o AdSense?">?</summary><div class="compact-help-content">
          <h3>Onde obter o ca-pub</h3><ol><li>Acesse <a href="https://www.google.com/adsense/" target="_blank" rel="noopener noreferrer">Google AdSense</a> com a conta responsável.</li><li>Abra <strong>Conta → Configurações → Informações da conta</strong> e copie o ID do editor (<code>pub-...</code>); neste painel use <code>ca-pub-...</code>.</li><li>Salvar o ID não ativa anúncios e não significa que o site foi aprovado. Gerencie posições separadamente em Publicidade.</li></ol>
          <h3>Para consultar receita e impressões</h3><ol><li>No <a href="https://console.cloud.google.com/apis/library/adsense.googleapis.com" target="_blank" rel="noopener noreferrer">Google Cloud</a>, habilite a AdSense Management API.</li><li>Configure a tela de consentimento OAuth e crie um <strong>cliente OAuth 2.0 para aplicativo da Web</strong> em <a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener noreferrer">Credenciais</a>.</li><li>Copie exatamente a URL de retorno exibida em <strong>Conectar conta AdSense</strong> para os URIs autorizados.</li><li>Salve ID/segredo do cliente com senha administrativa e autorize a conta Google.</li></ol>
          <p><strong>Importante:</strong> AdSense não usa a conta de serviço do GA4. O acesso do portal usa <code>adsense.readonly</code>. Receita estimada não representa pagamento recebido.</p>
          <p><a href="https://developers.google.com/adsense/management/direct_requests" target="_blank" rel="noopener noreferrer">Documentação oficial OAuth do AdSense</a></p>
        </div></details></div>

        <div class="int-hint-row"><span>O ID é compartilhado com Publicidade</span><details class="compact-help"><summary aria-label="Ajuda do AdSense">?</summary><div class="compact-help-content">Salvar o ID publica apenas a identificação para análise. Não ativa anúncios nem garante aprovação. A ativação das posições continua em Publicidade.</div></details></div>
        <form method="post" action="/admin.php?action=integrations_save_adsense" class="int-form"><?= csrf_field() ?>
          <label for="int-adsense-id">ID do editor<input id="int-adsense-id" name="adsense_publisher" value="<?=h($cfg['adsense_publisher'])?>" maxlength="23" pattern="ca-pub-[0-9]{16}" placeholder="ca-pub-1234567890123456"></label>
          <div class="int-service-actions"><button class="btn primary" type="submit">Salvar AdSense</button><button type="button" class="btn outline" data-int-copy="int-adsense-id" aria-label="Copiar ID AdSense">Copiar ID</button><a href="<?=h(admin_link('ads'))?>">Gerenciar anúncios ↗</a></div>
        </form>
        <section class="int-ga-report" aria-label="Relatórios autorizados Google AdSense">
          <div class="int-live-heading"><span class="int-live-icon" aria-hidden="true">▤</span><div><strong>Receitas e desempenho · AdSense</strong><small>Últimos 30 dias completos · conta do editor, não somente este site</small></div><details class="compact-help"><summary aria-label="Ajuda para OAuth AdSense">?</summary><div class="compact-help-content">O AdSense exige autorização do proprietário com OAuth 2.0, não aceita a conta de serviço usada pelo GA4. O escopo é apenas adsense.readonly. Os valores são estimados e não equivalem a pagamentos recebidos. Não revela números publicamente nem executa consultas em segundo plano.</div></details></div>
          <div class="int-live-status"><span class="int-live-badge <?=($adsenseConnected??false)?'is-ready':'is-waiting'?>"><?=($adsenseConnected??false)?'Conta Google autorizada':(($adsenseOAuth??null)!==null?'Cliente OAuth salvo; autorização pendente':'Cliente OAuth não configurado')?></span></div>
          <?php if(($adsenseConnected??false)): ?>
            <form method="post" action="/admin.php?action=integrations_adsense_report" class="int-ga-report-form"><?=csrf_field()?><button class="btn primary" type="submit">⌕ Consultar relatório AdSense</button></form>
          <?php else: ?><p class="int-sc-hint">A consulta real só será habilitada depois de cadastrar o cliente OAuth e autorizar a conta Google que possui o ID ca-pub informado acima.</p><?php endif; ?>
          <?php if(($adsenseReport??null)!==null): ?>
            <div class="int-ga-report-results" role="status">
              <small><?=h($adsenseReport['account'])?> · consulta <?=date('d/m/Y H:i',(int)$adsenseReport['checked_at'])?> · moeda <?=h($adsenseReport['currency']?:'não informada')?> · conta inteira</small>
              <?php if($adsenseReport['totals']!==null): ?><div class="int-ga-report-totals">
                <div><strong><?=h($adsenseReport['currency']?:'') ?> <?=number_format((float)$adsenseReport['totals']['earnings'],2,',','.')?></strong><span>Receita estimada</span></div>
                <div><strong><?=number_format((int)$adsenseReport['totals']['impressions'],0,',','.')?></strong><span>Impressões</span></div>
                <div><strong><?=number_format((int)$adsenseReport['totals']['clicks'],0,',','.')?></strong><span>Cliques em anúncios</span></div>
                <div><strong><?=number_format((int)$adsenseReport['totals']['page_views'],0,',','.')?></strong><span>Visualizações de página (AdSense)</span></div>
              </div><?php else: ?><p class="int-sc-hint">O Google não retornou totais nesse período. Isso não comprova que a conta esteja com defeito.</p><?php endif; ?>
            </div>
          <?php endif; ?>
          <details class="int-credentials"><summary>Conectar conta AdSense via Google OAuth 2.0</summary>
            <div class="int-credentials-inner">
              <p>1. No <a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener noreferrer">Google Cloud → Credenciais</a>, habilite a <strong>AdSense Management API</strong> e crie um <strong>Cliente OAuth 2.0 tipo Aplicativo da Web</strong>.</p>
              <p>2. Configure a URL de retorno autorizada exatamente como abaixo. O Google pode exigir configuração da tela de consentimento e aprovação do acesso.</p>
              <div class="int-sc-identity"><label for="int-ads-callback">URL de retorno OAuth (pública)</label><div><input id="int-ads-callback" readonly value="<?php try{echo h(igua_adsense_callback_uri($cfg['seo_site_url']));}catch(Throwable){echo 'Configure a URL HTTPS oficial em Geral e SEO';} ?>"><button type="button" class="btn outline" data-int-copy="int-ads-callback">Copiar</button></div></div>
              <p>3. Salve as credenciais privadas, confirme com sua senha administrativa e conecte sua conta Google. Não cole esses segredos no GitHub ou em chats.</p>
              <form method="post" action="/admin.php?action=integrations_adsense_credentials" class="int-form"><?=csrf_field() ?>
                <label>ID do cliente OAuth<input type="text" name="adsense_client_id" required autocomplete="off" maxlength="240" placeholder="...apps.googleusercontent.com"></label>
                <label>Segredo do cliente OAuth<input type="password" name="adsense_client_secret" required autocomplete="new-password" maxlength="256"></label>
                <label>Senha administrativa<input type="password" name="confirm_password" required autocomplete="current-password"></label>
                <button class="btn outline" type="submit">Salvar cliente OAuth privado</button>
              </form>
              <?php if(($adsenseOAuth??null)!==null): ?>
                <form method="post" action="/admin.php?action=integrations_adsense_connect" class="int-form"><?=csrf_field() ?>
                  <button class="btn primary" type="submit" <?=($cfg['adsense_publisher']??'')===''?'disabled':''?>>↗ Autorizar com Google</button>
                </form>
                <form method="post" action="/admin.php?action=integrations_adsense_disconnect" class="int-form" data-confirm="Apagar as credenciais privadas do AdSense desta hospedagem?">
                  <?=csrf_field() ?><input type="hidden" name="confirm_remove" value="1">
                  <label>Senha administrativa<input type="password" name="confirm_password" required autocomplete="current-password"></label>
                  <button type="submit" class="btn outline">Remover conexão e credenciais</button>
                </form>
              <?php endif; ?>
              <p class="int-sc-hint">A autorização é independente de GA4/Search Console. Os tokens de renovação ficam em storage/integrations, fora dos pacotes ZIP. Relatórios não significam aprovação do site nem receita recebida.</p>
            </div>
          </details>
        </section>
      </div>
    </details>
