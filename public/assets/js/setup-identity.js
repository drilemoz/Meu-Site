/* Pré-visualização local da identidade sem enviar dados ao servidor. */
(() => {
  const form = document.querySelector('[data-setup-identity]');
  if (!form) return;
  const name = form.querySelector('[name="site_name"]');
  const tagline = form.querySelector('[name="site_tagline"]');
  const description = form.querySelector('[name="seo_default_description"]');
  const preview = form.querySelector('[data-site-preview]');
  const previewTag = form.querySelector('[data-tagline-preview]');
  const previewDesc = form.querySelector('[data-description-preview]');
  const count = form.querySelector('[data-description-count]');
  const render = () => {
    preview.textContent = name.value.trim() || 'Seu portal';
    previewTag.textContent = tagline.value.trim() || 'Uma frase sobre seu portal';
    previewDesc.textContent = description.value.trim() || 'Uma breve descrição para mecanismos de busca.';
    count.textContent = `${[...description.value].length}/320`;
  };
  [name, tagline, description].forEach(el => el.addEventListener('input', render));
  render();
})();
