/* IguaNews 1.0.29: estado isolado de consultas e uploads da Biblioteca de Mídias.
 * Sem dependências de DOM, PHP ou bibliotecas externas. */
(function (root, factory) {
  'use strict';
  const api = factory();
  if (typeof module === 'object' && module.exports) module.exports = api;
  else root.IguaMediaState = api;
})(typeof globalThis !== 'undefined' ? globalThis : this, function () {
  'use strict';

  function createRequests() {
    let generation = 0, controller = null;
    function invalidate() {
      generation += 1;
      if (controller) controller.abort();
      controller = null;
    }
    return {
      next() {
        invalidate();
        controller = typeof AbortController === 'function' ? new AbortController() : null;
        return { generation, signal: controller?.signal };
      },
      isCurrent(ticket) { return ticket.generation === generation; },
      invalidate
    };
  }

  function createUploadQueue() {
    let pending = [], sending = false;
    return {
      select(files) {
        if (sending) return false;
        pending = Array.from(files || []);
        return true;
      },
      remaining() { return pending.slice(); },
      isSending() { return sending; },
      async send(sendOne, onSuccess) {
        if (sending) return { uploaded: 0, remaining: pending.length, busy: true, error: null };
        if (!pending.length) return { uploaded: 0, remaining: 0, busy: false, error: null };
        sending = true;
        let uploaded = 0, error = null;
        try {
          while (pending.length) {
            // Remove somente depois que o servidor confirmar a resposta de sucesso.
            await sendOne(pending[0]);
            pending.shift();
            uploaded += 1;
            if (onSuccess) onSuccess(uploaded, pending.length);
          }
        } catch (cause) {
          error = cause;
        } finally {
          sending = false;
        }
        return { uploaded, remaining: pending.length, busy: false, error };
      }
    };
  }

  return {createRequests, createUploadQueue};
});
