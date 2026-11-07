<?php
declare(strict_types=1);
/** IguaNews: serviço editorial, módulo schema. Requer bootstrap.php no ponto de entrada. */

function migrate_news_schema(PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS article_categories (
      article_id INT UNSIGNED NOT NULL, category_id INT UNSIGNED NOT NULL,
      PRIMARY KEY(article_id,category_id),
      FOREIGN KEY(article_id) REFERENCES articles(id) ON DELETE CASCADE,
      FOREIGN KEY(category_id) REFERENCES categories(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->exec("CREATE TABLE IF NOT EXISTS article_blocks (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      article_id INT UNSIGNED NOT NULL, sort_order INT UNSIGNED NOT NULL,
      kind VARCHAR(20) NOT NULL, heading VARCHAR(180) NOT NULL DEFAULT '',
      content TEXT NOT NULL, collapsed TINYINT(1) NOT NULL DEFAULT 0,
      INDEX idx_article_blocks(article_id,sort_order),
      FOREIGN KEY(article_id) REFERENCES articles(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->exec("CREATE TABLE IF NOT EXISTS article_media (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      article_id INT UNSIGNED NOT NULL, kind ENUM('image','attachment') NOT NULL,
      filename VARCHAR(90) NOT NULL, original_name VARCHAR(200) NOT NULL,
      mime VARCHAR(140) NOT NULL, bytes INT UNSIGNED NOT NULL,
      caption VARCHAR(360) NOT NULL DEFAULT '', credit VARCHAR(180) NOT NULL DEFAULT '',
      sort_order INT UNSIGNED NOT NULL DEFAULT 0,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_article_media(article_id,kind,sort_order),
      FOREIGN KEY(article_id) REFERENCES articles(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->exec("CREATE TABLE IF NOT EXISTS article_drafts (
      user_id INT UNSIGNED NOT NULL, article_id INT UNSIGNED NOT NULL DEFAULT 0,
      payload MEDIUMTEXT NOT NULL, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY(user_id,article_id),
      FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->exec("CREATE TABLE IF NOT EXISTS article_import_logs (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      user_id INT UNSIGNED NULL, source_url VARCHAR(600) NOT NULL,
      result_title VARCHAR(220) NOT NULL DEFAULT '', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->exec("CREATE TABLE IF NOT EXISTS article_revisions (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      article_id INT UNSIGNED NOT NULL, user_id INT UNSIGNED NULL,
      snapshot_json MEDIUMTEXT NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_revision_article(article_id,created_at),
      FOREIGN KEY(article_id) REFERENCES articles(id) ON DELETE CASCADE,
      FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    migrate_media_schema($db);
}

