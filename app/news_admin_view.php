<?php
declare(strict_types=1);
require_once __DIR__.'/news_editor.php';
/** Renderiza o editor preservando a API pública e os nomes dos campos. */
function news_admin_editor(PDO $db,array $row,array $me,array $profiles,array $sources,array $cats): void {
    // Inclusões explícitas: nenhuma rota ou caminho é controlado pelo usuário.
    require __DIR__.'/news_admin_view/prepare.php';
    require __DIR__.'/news_admin_view/header.php';
    require __DIR__.'/news_admin_view/details.php';
    require __DIR__.'/news_admin_view/blocks.php';
    require __DIR__.'/news_admin_view/media.php';
    require __DIR__.'/news_admin_view/footer.php';
    require __DIR__.'/news_admin_view/revisions.php';
    require __DIR__.'/news_admin_view/scripts.php';
}
