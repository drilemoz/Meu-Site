    <details class="int-service int-ga" id="google-ga4">
      <summary><span class="int-service-icon" aria-hidden="true">▥</span><span class="int-service-copy"><strong>Google Analytics 4</strong><small>Medição e consentimento</small></span><span class="int-state <?= $gaOn?'int-on':'int-off' ?>"><?= $gaOn?'Configurado':'Desligado' ?></span><span class="int-chevron" aria-hidden="true"></span></summary>
      <div class="int-service-body">
        <div class="int-guide-row"><strong>Configurar Google Analytics 4</strong><details class="compact-help int-guide-help"><summary aria-label="Guia completo: onde obter IDs e configurar Google Analytics 4" title="Como configurar o GA4?">?</summary><div class="compact-help-content">
          <h3>Onde obter os identificadores</h3>
          <ol><li>Acesse <a href="https://analytics.google.com/" target="_blank" rel="noopener noreferrer">Google Analytics</a> e selecione a propriedade do portal.</li><li>Em <strong>Administrador → Fluxos de dados → Web</strong>, copie o ID de medição no formato <code>G-...</code>.</li><li>Em <strong>Administrador → Detalhes da propriedade</strong>, copie o <strong>ID numérico da propriedade</strong>. É diferente do ID de medição.</li></ol>
          <h3>Para consultar dados pela API</h3><ol><li>No <a href="https://console.cloud.google.com/apis/library/analyticsdata.googleapis.com" target="_blank" rel="noopener noreferrer">Google Cloud</a>, ative a Analytics Data API.</li><li>Em <a href="https://console.cloud.google.com/iam-admin/serviceaccounts" target="_blank" rel="noopener noreferrer">Contas de serviço</a>, crie uma conta e gere uma chave JSON privada.</li><li>Na propriedade GA4, em <strong>Gerenciamento de acesso à propriedade</strong>, adicione o e-mail da conta de serviço como <strong>Leitor</strong>.</li><li>Abra <strong>Conta de serviço Google</strong> nesta janela e importe o JSON com sua senha administrativa.</li></ol>
          <p><strong>Privacidade:</strong> o rastreamento público permanece sujeito ao consentimento do visitante; relatórios são dados da propriedade e podem incluir outros fluxos. Nunca publique o JSON no GitHub.</p>
          <p><a href="https://support.google.com/analytics/answer/12270356?hl=pt-BR" target="_blank" rel="noopener noreferrer">Ajuda oficial: localizar o ID G-</a> · <a href="https://developers.google.com/analytics/devguides/reporting/data/v1/quickstart" target="_blank" rel="noopener noreferrer">Guia oficial da Data API</a></p>
        </div></details></div>

        <div class="int-ga-collection-state" role="note">
          <div class="int-ga-collection-top"><strong><?= $gaOn?'Rastreamento habilitado no portal':'Rastreamento desativado no portal' ?></strong><span class="int-ga-collection-badge <?= $ga4Live!==null && in_array(($ga4Live['status']??''),['realtime','recent'],true)?'is-confirmed':'' ?>"><?= $ga4Live!==null && in_array(($ga4Live['status']??''),['realtime','recent'],true)?'Eventos retornados pela API':'Recebimento ainda não verificado' ?></span></div>
          <p><?= $gaOn?'A tag está configurada, mas isso não confirma que o Google recebeu visitas.':'Ative o GA4 e salve um ID de medição válido para habilitar a tag pública.' ?> O portal público só carrega o Google Analytics após consentimento do visitante e respeita GPC/DNT. O painel administrativo não dispara visualizações GA4.</p>
        </div>
        <form method="post" action="/admin.php?action=integrations_save_ga4" class="int-form"><?= csrf_field() ?>
          <label class="int-switch"><input type="checkbox" name="ga4_enabled" value="1" <?=$gaOn?'checked':''?>><span>Ativar GA4</span></label>
          <label for="int-ga-id">ID de medição<input id="int-ga-id" name="ga4_measurement_id" value="<?=h($cfg['ga4_measurement_id'])?>" maxlength="22" pattern="G-[a-zA-Z0-9]{4,20}" autocapitalize="characters" spellcheck="false" placeholder="G-XXXXXXXXXX"></label>
          <label for="int-ga-property">Propriedade GA4 (ID numérico)<input id="int-ga-property" name="ga4_property_id" inputmode="numeric" value="<?=h($cfg['ga4_property_id'])?>" maxlength="20" pattern="[0-9]{4,20}" autocomplete="off" placeholder="123456789"></label>
          <div class="int-service-actions"><button class="btn primary" type="submit">Salvar GA4</button><button type="button" class="btn outline" data-int-copy="int-ga-id" aria-label="Copiar ID de medição GA4">Copiar ID</button><a href="https://analytics.google.com/" target="_blank" rel="noopener noreferrer">Abrir Google ↗</a></div>
        </form>
        <details class="int-ga-verify-guide">
          <summary><span>Como saber se o GA4 está recebendo visitas?</span><small>Verificação no Google sem conta de serviço</small></summary>
          <div class="int-ga-verify-inner">
            <ol>
              <li><strong>Compare o ID G-.</strong> No Google Analytics, abra Administrador → Fluxos de dados → Web → fluxo do IguatamaOnline. O ID de medição precisa ser igual ao informado acima. O ID numérico do fluxo não é o ID da propriedade.</li>
              <li><strong>Teste no site público.</strong> Abra o portal em um navegador sem bloqueador de rastreamento, escolha <em>Permitir métricas</em> quando solicitado e navegue para outra página. Se GPC ou DNT estiverem ativados, a tag permanece bloqueada.</li>
              <li><strong>Confira o Google Analytics.</strong> Em Relatórios → Tempo real, procure o seu acesso. Para um diagnóstico mais específico, utilize o Tag Assistant e o DebugView. Dados históricos podem levar mais tempo.</li>
            </ol>
            <div class="int-ga-public-probe"><button type="button" class="btn outline" data-ga4-public-test>Verificar tag no site público</button><p data-ga4-public-result role="status" aria-live="polite" hidden></p><small>Consulta somente o HTML da página inicial do seu domínio. Não envia evento ao Google nem verifica visitas recebidas.</small></div>
            <div class="int-ga-help-links"><a href="https://tagassistant.google.com/" target="_blank" rel="noopener noreferrer">Abrir Tag Assistant ↗</a><a href="https://support.google.com/analytics/answer/9333790?hl=pt-BR" target="_blank" rel="noopener noreferrer">Guia oficial do Google ↗</a></div>
            <p>Se o Google informar que nenhum dado chegou nas últimas 48 horas, o rastreamento ainda não está comprovado. Verifique ID, consentimento, bloqueadores, cache e publicação efetiva do código.</p>
          </div>
        </details>
        <details class="int-ga-api-details" <?= $ga4Live!==null||$ga4Audience!==null?'open':'' ?>>
          <summary><span>Relatórios pela API <small>Opcional: consultar dados sem sair do portal</small></span><span class="int-ga-api-state"><?= $ga4CredentialsReady?'Credencial cadastrada':'Requer conta de serviço' ?></span></summary>
          <div class="int-ga-api-content">
            <p class="int-ga-api-note">Esta seção <strong>não ativa a coleta</strong> nem envia eventos. Ela consulta dados já recebidos na propriedade Google. Para funcionar, exige conta de serviço, autorização de Leitor e a Analytics Data API habilitada.</p>
            <?php if(!$ga4CredentialsReady): ?><p class="int-ga-api-notice">Sem credencial para a API. O rastreamento público pode funcionar normalmente mesmo assim. Abra <strong>Conta de serviço Google</strong> abaixo apenas se quiser consultar relatórios aqui.</p><?php endif; ?>
        <div class="int-live-panel" aria-labelledby="int-live-title">
          <div class="int-live-heading"><span class="int-live-icon" aria-hidden="true">◉</span><div><strong id="int-live-title">1. Conferir eventos recebidos</strong><small>Consulta à propriedade · Últimos 30 minutos e 7 dias</small></div><details class="compact-help"><summary aria-label="Ajuda para o diagnóstico GA4">?</summary><div class="compact-help-content">O ID G- identifica o fluxo do site; a propriedade é numérica. A API verifica eventos da propriedade, que pode ter outros fluxos. Zero eventos não prova falha. É necessário habilitar a Analytics Data API e dar acesso de Leitor à conta de serviço.</div></details></div>
          <div class="int-live-status">
            <span class="int-live-badge <?= $ga4CredentialsReady?'is-ready':'is-waiting' ?>"><?= $ga4CredentialsReady?'Conta de serviço cadastrada':'Sem credencial para API' ?></span>
            <span class="int-live-badge <?= $cfg['ga4_property_id']!==''?'is-ready':'is-waiting' ?>"><?= $cfg['ga4_property_id']!==''?'Propriedade informada':'Propriedade pendente' ?></span>
          </div>
          <?php if($ga4Live!==null): ?>
            <div class="int-live-result" role="status" aria-live="polite">
              <div class="int-live-result-title"><span class="int-live-pulse" aria-hidden="true"></span>
                <strong><?= $ga4Live['status']==='realtime'?'Eventos confirmados em tempo real':($ga4Live['status']==='recent'?'Dados confirmados nos últimos 7 dias':'Nenhum evento retornado no período') ?></strong>
              </div>
              <div class="int-live-values">
                <div><strong><?= (int)$ga4Live['realtime_events'] ?></strong><span>Eventos · 30 min</span></div>
                <div><strong><?= (int)$ga4Live['realtime_users'] ?></strong><span>Usuários ativos</span></div>
                <div><strong><?= (int)$ga4Live['recent_events'] ?></strong><span>Eventos · 7 dias</span></div>
              </div>
              <small><?= ($ga4Live['last_event_date']??'')!==''?'Último dia com eventos: '.h($ga4Live['last_event_date']).' · ':'' ?>Consulta <?=h($ga4Live['checked_at'])?> · Totais da propriedade, não apenas deste site.</small>
            </div>
          <?php endif; ?>
          <form method="post" action="/admin.php?action=integrations_ga4_test" data-ga4-test-form><?=csrf_field() ?>
            <button class="btn primary" type="submit" <?= !$ga4CredentialsReady||$cfg['ga4_property_id']===''?'disabled':'' ?>>↗ Testar recebimento</button>
            <span data-ga4-wait role="status" aria-live="polite" hidden>Consultando Google…</span>
          </form>
          <div class="int-ga-report" aria-label="Relatório de audiência do Google Analytics">
            <div class="int-live-heading"><span class="int-live-icon" aria-hidden="true">▤</span><div><strong>2. Relatórios de audiência e conteúdo</strong><small>Últimos 28 dias completos · toda a propriedade</small></div><details class="compact-help"><summary aria-label="Ajuda do relatório de audiência GA4">?</summary><div class="compact-help-content">O GA4 apresenta dados agregados de uma propriedade, que pode ter mais de um fluxo. Usuários ativos não são o mesmo que sessões ou visualizações; algumas pessoas podem usar vários dispositivos. Os dados dependem do consentimento e podem sofrer atraso ou limiares de privacidade. Uma consulta é feita somente ao tocar no botão, com resultado temporário por até 1 hora.</div></details></div>
            <form method="post" action="/admin.php?action=integrations_ga4_report" class="int-ga-report-form"><?=csrf_field() ?>
              <button type="submit" class="btn primary" <?= !$ga4CredentialsReady||$cfg['ga4_property_id']===''?'disabled':'' ?>>⌕ Consultar audiência</button>
              <?php if(!$ga4CredentialsReady||$cfg['ga4_property_id']===''): ?><small>Informe a propriedade e cadastre a conta de serviço para habilitar.</small><?php endif; ?>
            </form>
            <?php if($ga4Audience!==null): ?>
              <div class="int-ga-report-results" role="status">
                <small>Propriedade <?=h($ga4Audience['property_id'])?> · consulta <?=date('d/m/Y H:i',(int)$ga4Audience['checked_at'])?> · valores GA4, não métricas internas</small>
                <?php if($ga4Audience['summary']!==null): ?>
                  <div class="int-ga-report-totals">
                    <div><strong><?=number_format((int)$ga4Audience['summary']['users'],0,',','.')?></strong><span>Usuários ativos</span></div>
                    <div><strong><?=number_format((int)$ga4Audience['summary']['sessions'],0,',','.')?></strong><span>Sessões</span></div>
                    <div><strong><?=number_format((int)$ga4Audience['summary']['views'],0,',','.')?></strong><span>Visualizações</span></div>
                  </div>
                <?php else: ?><p class="int-sc-hint">Nenhum total fornecido pela API para o período. Não significa que o site esteja com defeito.</p><?php endif; ?>
                <?php foreach(['pages'=>['Páginas populares','Visualizações'],'channels'=>['Canais de origem','Sessões'],'devices'=>['Dispositivos','Usuários ativos']] as $section=>$labels): ?>
                  <details class="int-sc-list" <?= $section==='pages'?'open':'' ?>><summary><?=h($labels[0])?> · <?=count($ga4Audience[$section])?></summary>
                    <?php if($ga4Audience[$section]===[]): ?><p class="int-sc-hint int-ga-empty">Nenhum registro disponível neste período.</p><?php endif; ?>
                    <?php foreach($ga4Audience[$section] as $item): ?><div class="int-sc-row"><span title="<?=h($item['label'])?>"><?=h($item['label'])?></span><small><?=number_format((int)$item['count'],0,',','.')?> <?=h($labels[1])?></small></div><?php endforeach; ?>
                  </details>
                <?php endforeach; ?>
                <p class="int-sc-hint">Páginas agrupadas pelo título registrado no GA4, inclusive se dois endereços tiverem o mesmo título. Os resultados podem incluir outros fluxos da propriedade.</p>
              </div>
            <?php else: ?><p class="int-sc-hint">Nenhuma consulta realizada nesta sessão. Isso não significa ausência de visitantes.</p><?php endif; ?>
          </div>
          <details class="int-credentials">
            <summary>3. Configurar acesso à API Google (GA4 e Search Console)</summary>
            <div class="int-credentials-inner">
              <p>Importe o JSON da conta de serviço do Google Cloud. A mesma conta pode consultar GA4 e Search Console, mas exige permissão separada em cada serviço. A chave fica privada no servidor, fora do site público e dos backups.</p>
              <form class="int-form" action="/admin.php?action=integrations_ga4_credentials" method="post" enctype="multipart/form-data">
                <?=csrf_field() ?>
                <label>Arquivo JSON privado<input type="file" name="ga4_credentials" accept=".json,application/json" required></label>
                <label>Senha administrativa<input type="password" name="confirm_password" required autocomplete="current-password"></label>
                <button class="btn primary" type="submit">Salvar credencial</button>
              </form>
              <?php if($ga4CredentialsReady): ?>
                <form class="int-form" action="/admin.php?action=integrations_ga4_remove" method="post" data-confirm="Remover a credencial privada do servidor? O rastreamento público continua configurado.">
                  <?=csrf_field() ?>
                  <label>Senha administrativa<input type="password" name="confirm_password" required autocomplete="current-password"></label>
                  <label class="int-confirm"><input type="checkbox" name="confirm_remove" value="1" required> Confirmo a remoção da credencial</label>
                  <button class="btn outline" type="submit">Remover credencial</button>
                </form>
              <?php endif; ?>
              <details class="compact-help"><summary aria-label="Como criar a conta de serviço">?</summary><div class="compact-help-content">No Google Cloud, ative a Google Analytics Data API e crie uma conta de serviço. No Google Analytics, adicione o e-mail dessa conta como Leitor na propriedade correta. Gere uma chave JSON e importe aqui. Nunca envie essa chave em conversa nem a salve no GitHub.</div></details>
            </div>
          </details>
        </div>
          </div>
        </details>
      </div>
    </details>
