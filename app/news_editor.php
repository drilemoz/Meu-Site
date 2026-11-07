<?php
declare(strict_types=1);
/**
 * Fachada de compatibilidade do editor IguaNews.
 * Mantém os nomes públicos das funções usados por instalação, notícias,
 * importação, galeria, revisões e demais módulos do portal.
 * Requer app/bootstrap.php no ponto de entrada, como nas versões anteriores.
 */
require_once __DIR__.'/news_editor/schema.php';
require_once __DIR__.'/news_editor/blocks.php';
require_once __DIR__.'/news_editor/format.php';
require_once __DIR__.'/news_editor/import.php';
require_once __DIR__.'/news_editor/library.php';
require_once __DIR__.'/news_editor/media.php';
require_once __DIR__.'/news_editor/zip.php';
require_once __DIR__.'/news_editor/revisions.php';
