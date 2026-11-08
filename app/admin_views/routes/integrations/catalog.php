  <section class="int-section" id="catalogo" data-int-section aria-labelledby="int-catalog-title">
    <div class="int-section-head"><div><span class="int-eyebrow">EXPANSÃO MODULAR</span><h3 id="int-catalog-title">Próximas integrações</h3></div><details class="compact-help"><summary aria-label="Ajuda sobre futuras integrações">?</summary><div class="compact-help-content">Estes serviços ainda não possuem conexão ativa, tokens cadastráveis nem testes de API. Cada um precisará de autenticação e validação separadas.</div></details></div>
    <div class="int-catalog-grid">
      <a class="int-future" href="/admin.php?action=sitemap#bing-webmaster" aria-label="Gerenciar Bing Webmaster na Central de Sitemap"><span aria-hidden="true">⌕</span><div><strong>Bing Webmaster</strong><small>Propriedade verificada e sitemaps no Bing</small></div><em>Configurar ↗</em></a>
      <a class="int-future" href="/admin.php?action=sitemap#indexnow" aria-label="Abrir integração IndexNow na Central de Sitemap"><span aria-hidden="true">⇪</span><div><strong>IndexNow</strong><small>Avisos de URLs publicadas a buscadores participantes</small></div><em>Configurar ↗</em></a>
      <?php foreach([
        ['▤','Google Tag Manager','Gerenciamento de tags'],['↗','Google Ads','Campanhas e conversões'],['▥','Google Notícias','Dados estruturados'],['◈','Microsoft Clarity','Análise de experiência'],['⬡','Cloudflare','Proteção e desempenho']
      ] as $item): ?><div class="int-future"><span aria-hidden="true"><?=h($item[0])?></span><div><strong><?=h($item[1])?></strong><small><?=h($item[2])?></small></div><em>Em breve</em></div><?php endforeach; ?>
    </div>
  </section>
