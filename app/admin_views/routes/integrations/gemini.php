    <details class="int-service int-gemini-service" id="google-gemini">
      <summary><span class="int-service-icon igua-gemini-mark" aria-hidden="true">✦</span><span class="int-service-copy"><strong>Google Gemini</strong><small>Assistente editorial opcional</small></span><span class="int-state <?= $geminiConfig!==null && $geminiConfig['enabled']?'int-on':'int-off' ?>"><?= $geminiConfig===null?'Não configurado':($geminiConfig['enabled']?'Ativado':'Desativado') ?></span><span class="int-chevron" aria-hidden="true"></span></summary>
      <div class="int-service-body igua-gemini-config">
        <p class="muted">Chave guardada exclusivamente no servidor. Os textos são enviados ao Google apenas quando você solicita uma sugestão; nenhuma matéria é publicada automaticamente.</p>
        <div class="int-guide-row"><strong>Configurar Gemini</strong><details class="compact-help int-guide-help"><summary aria-label="Como obter a chave da API Gemini?" title="Como obter a chave?">?</summary><div class="compact-help-content"><p>Acesse <a href="https://aistudio.google.com/api-keys" target="_blank" rel="noopener noreferrer">Google AI Studio</a>, escolha um projeto, crie ou copie sua chave API e cole abaixo. Guarde a chave fora de arquivos públicos e do GitHub.</p><p>Se o teste retornar 404, o modelo escolhido pode não estar disponível na sua conta. Consulte <a href="https://ai.google.dev/gemini-api/docs/models" target="_blank" rel="noopener noreferrer">Modelos Gemini</a> e ajuste o nome. Testar API consome uma pequena quantidade de cota.</p></div></details></div>
        <div class="igua-gemini-status" role="status">
          <strong><?= $geminiConfig!==null?'Chave cadastrada (oculta)':'Chave ainda não cadastrada' ?></strong>
          <small>Modelo: <?=h($geminiConfig['model']??'gemini-3.8-flash')?></small>
          <?php if(is_array($geminiTest) && ($geminiTest['ok']??false)): ?><small>Último teste: conexão confirmada nesta sessão em <?=date('d/m/Y H:i',(int)$geminiTest['time'])?>. Não garante disponibilidade futura.</small><?php endif; ?>
        </div>
        <form method="post" action="/admin.php?action=integrations_gemini_save" id="int-gemini-save-form" class="int-form" autocomplete="off"><?=csrf_field() ?>
          <label class="int-switch"><input type="checkbox" name="gemini_enabled" value="1" <?=($geminiConfig['enabled']??false)?'checked':''?>><span>Ativar assistente Gemini</span></label>
          <label for="int-gemini-key">Chave de API <input type="password" id="int-gemini-key" name="gemini_key" maxlength="512" autocomplete="new-password" autocapitalize="off" spellcheck="false" placeholder="<?= $geminiConfig===null?'Cole a chave do Google AI Studio':'Deixe vazio para manter a chave atual' ?>"></label>
          <button type="button" class="btn outline small" data-gemini-visibility aria-controls="int-gemini-key" aria-label="Mostrar ou ocultar a chave digitada">Mostrar/ocultar</button>
          <label for="int-gemini-model">Modelo da API<input id="int-gemini-model" name="gemini_model" required maxlength="70" pattern="gemini-[a-z0-9][a-z0-9.\-]{2,63}" value="<?=h($geminiConfig['model']??'gemini-3.8-flash')?>" placeholder="gemini-3.8-flash" autocapitalize="off" spellcheck="false"></label>
          <label for="int-gemini-password">Senha administrativa<input id="int-gemini-password" type="password" name="confirm_password" required autocomplete="current-password"></label>
          <div class="igua-gemini-main-actions"><button type="submit" class="btn primary">Salvar configuração</button><button type="button" class="btn outline" data-gemini-test-open <?= !$geminiConfig || !$geminiConfig['enabled']?'disabled':'' ?> aria-haspopup="dialog" aria-controls="gemini-test-dialog">Testar API</button></div>
        </form>
        <form id="int-gemini-test-form" method="post" action="/admin.php?action=integrations_gemini_test" data-gemini-test-form><?=csrf_field() ?><input type="hidden" name="gemini_ajax" value="1"></form>
        <?php if($geminiConfig!==null): ?>
          <div class="igua-gemini-danger"><strong>Remover chave configurada</strong><p class="muted tiny">Exige confirmação da senha administrativa e não afeta os conteúdos salvos.</p>
            <form method="post" action="/admin.php?action=integrations_gemini_remove" data-confirm="Remover a chave Gemini desta hospedagem?" class="igua-gemini-danger-form"><?=csrf_field() ?><input type="hidden" name="confirm_remove" value="1"><label for="int-gemini-remove-password">Senha administrativa<input type="password" id="int-gemini-remove-password" name="confirm_password" required autocomplete="current-password" placeholder="Digite sua senha para remover"></label><button type="submit" class="btn outline danger-link" data-confirm="Remover a chave Gemini da hospedagem?">Remover chave</button></form>
          </div>
        <?php endif; ?>
        <p class="muted tiny">Teste real apenas sob seu comando. Pode consumir cota, sem expor chave ou conteúdo da resposta. Mensagens de erro aparecem no painel.</p>
      </div>
    </details>
