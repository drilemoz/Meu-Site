/* Visualização local do campo; não persiste nem transmite senha. */
(() => {
  const input = document.getElementById('admin-login-password');
  const button = document.getElementById('admin-login-reveal');
  if (!input || !button) return;
  button.addEventListener('click', () => {
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    button.setAttribute('aria-pressed', String(show));
    button.setAttribute('aria-label', show ? 'Ocultar senha' : 'Mostrar senha');
    button.title = show ? 'Ocultar senha' : 'Mostrar senha';
    button.querySelector('.password-visibility-label').textContent = show ? 'Ocultar' : 'Mostrar';
    input.focus({preventScroll: true});
  });
})();
