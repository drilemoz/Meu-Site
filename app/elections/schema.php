<?php
declare(strict_types=1);
/** Cadastro reutilizável de pleitos e candidaturas. Não contém votos nem atesta registro no TSE. */
function meu_elections_schema(PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS elections (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(180) NOT NULL,
        election_year SMALLINT UNSIGNED NOT NULL,
        election_scope ENUM('municipal','general') NOT NULL,
        office VARCHAR(32) NOT NULL,
        round_number TINYINT UNSIGNED NOT NULL DEFAULT 1,
        state_code CHAR(2) NOT NULL DEFAULT '',
        municipality VARCHAR(120) NOT NULL DEFAULT '',
        municipality_ibge CHAR(7) NOT NULL DEFAULT '',
        election_date DATE NULL,
        source_url VARCHAR(400) NOT NULL DEFAULT '',
        status ENUM('draft','archived') NOT NULL DEFAULT 'draft',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_election_lookup (election_year,election_scope,state_code,status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->exec("CREATE TABLE IF NOT EXISTS election_candidates (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        election_id INT UNSIGNED NOT NULL,
        full_name VARCHAR(160) NOT NULL,
        ballot_name VARCHAR(120) NOT NULL,
        candidate_number VARCHAR(6) NOT NULL,
        party_abbr VARCHAR(30) NOT NULL DEFAULT '',
        party_name VARCHAR(120) NOT NULL DEFAULT '',
        status ENUM('listed','withdrawn') NOT NULL DEFAULT 'listed',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_election_number (election_id,candidate_number),
        CONSTRAINT fk_election_candidate FOREIGN KEY(election_id) REFERENCES elections(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}
