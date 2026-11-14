<?php
declare(strict_types=1);
// Ações administrativas privadas: executadas após autenticação e validação CSRF.
require __DIR__.'/news/taxonomy.php';
require __DIR__.'/news/gemini.php';
require __DIR__.'/news/drafts.php';
require __DIR__.'/news/save.php';
require __DIR__.'/news/lifecycle.php';
