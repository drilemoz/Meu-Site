  <dialog id="gemini-test-dialog" class="igua-modal igua-modal--narrow igua-gemini-test-dialog" aria-labelledby="gemini-test-title" aria-modal="true">
    <div class="igua-gemini-test-shell">
      <header><h2 id="gemini-test-title">Teste da API Gemini</h2><button type="button" class="btn outline" data-gemini-test-close aria-label="Fechar diagnóstico">×</button></header>
      <div class="igua-gemini-test-body"><p>O teste envia uma solicitação mínima ao modelo salvo. Pode consumir cota e não altera notícias.</p>
        <div data-gemini-test-result role="status" aria-live="polite">Toque em Iniciar teste para consultar a API.</div>
        <div class="igua-gemini-test-actions"><button type="button" class="btn outline" data-gemini-test-close>Fechar</button><button type="button" class="btn primary" data-gemini-test-run>Iniciar teste</button></div>
      </div>
    </div>
  </dialog>
  <dialog id="int-dialog" class="igua-modal igua-modal--medium int-dialog" aria-labelledby="int-dialog-title" aria-modal="true">
    <div class="int-dialog-shell">
      <header class="int-dialog-header">
        <button type="button" class="int-dialog-back" data-int-dialog-back aria-label="Voltar ao conteúdo anterior" hidden>←</button>
        <h2 id="int-dialog-title" data-int-dialog-title>Integração</h2>
        <button type="button" class="int-dialog-x" data-int-dialog-close aria-label="Fechar janela">×</button>
      </header>
      <div class="int-dialog-scroll" data-int-dialog-scroll><div class="int-dialog-panel" data-int-dialog-panel></div></div>
      <div class="int-dialog-feedback" data-int-dialog-feedback role="status" aria-live="polite" hidden></div>
      <footer class="int-dialog-footer"><button type="button" class="btn outline" data-int-dialog-close>Fechar</button></footer>
    </div>
  </dialog>

