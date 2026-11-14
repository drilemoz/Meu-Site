<?php if ($route==='app' && $legalPage): require __DIR__.'/routes/legal_app.php'; ?>
<?php elseif ($route==='onibus'): require __DIR__.'/routes/bus.php'; ?>
<?php elseif ($route==='anuncie' || $route==='contato'): require __DIR__.'/routes/contact.php'; ?>
<?php elseif ($route==='sobre'): require __DIR__.'/routes/about.php'; ?>
<?php elseif ($route==='privacidade'): require __DIR__.'/routes/privacy.php'; ?>
<?php elseif ($route==='termos'): require __DIR__.'/routes/terms.php'; ?>
<?php elseif ($route==='inicio'): require __DIR__.'/routes/home.php'; ?>
<?php elseif ($route==='noticia' && $article): require __DIR__.'/routes/article.php'; ?>
<?php elseif ($route==='categoria' && $category): require __DIR__.'/routes/category.php'; ?>
<?php elseif ($route==='buscar'): require __DIR__.'/routes/search.php'; ?>
<?php else: ?><div class="empty-text"><h1>Página não encontrada</h1><a href="/index.php">Voltar ao início</a></div><?php endif; ?>
