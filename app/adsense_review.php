<?php
declare(strict_types=1);
/** Apoio à análise do AdSense: nunca aprova o site nem ativa publicidade. */
require_once __DIR__.'/ads.php';

function igua_adsense_verification_meta(string $publisher): string {
    // Defenda a página pública contra valores incorretos inseridos diretamente no banco.
    if (!preg_match('/^ca-pub-\d{16}$/D', $publisher)) return '';
    // Metatag de propriedade, sem carregar bibliotecas de publicidade ou cookies.
    return '<meta name="google-adsense-account" content="'.htmlspecialchars($publisher, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'">';
}

function igua_adsense_ads_txt_line(string $publisher): string {
    $publisher = ad_validate_publisher($publisher);
    if ($publisher === '') throw new RuntimeException('Informe o ID real do editor AdSense antes de gerar ads.txt.');
    return 'google.com, '.substr($publisher, 3).', DIRECT, f08c47fec0942fa0';
}

/** Só substitui linhas da própria conta Google; não apaga redes de terceiros. */
function igua_adsense_ads_txt_build(string $current, string $publisher): string {
    if (strlen($current) > 65536 || str_contains($current, "\0"))
        throw new RuntimeException('ads.txt existente é grande demais ou inválido. Revise-o manualmente.');
    $line = igua_adsense_ads_txt_line($publisher);
    $wanted = substr($publisher, 3);
    $kept = [];
    foreach (preg_split('/\r\n|\r|\n/', $current) ?: [] as $raw) {
        $trim = trim($raw);
        if ($trim === '') continue;
        if (preg_match('/^google\.com\s*,/i', $trim)) {
            $fields = array_map('trim', explode(',', $trim));
            if (($fields[1] ?? '') !== $wanted)
                throw new RuntimeException('ads.txt já possui uma conta Google diferente. Revise manualmente antes de substituir.');
            continue;
        }
        $kept[] = $raw;
    }
    $kept[] = $line;
    return implode("\n", $kept)."\n";
}

function igua_adsense_ads_txt_has_publisher(string $content, string $publisher): bool {
    if (!preg_match('/^ca-pub-\d{16}$/D', $publisher)) return false;
    $expected=igua_adsense_ads_txt_line($publisher);
    foreach (preg_split('/\r\n|\r|\n/', $content) ?: [] as $line)
        if (trim($line) === $expected) return true;
    return false;
}

function igua_adsense_ads_txt_write(string $publisher, string $root): void {
    $public = realpath($root);
    if ($public === false || !is_dir($public)) throw new RuntimeException('Pasta pública indisponível.');
    $target = $public . DIRECTORY_SEPARATOR . 'ads.txt';
    if (is_link($target) || (file_exists($target) && !is_file($target)))
        throw new RuntimeException('O caminho ads.txt não é um arquivo comum.');
    $current = file_exists($target) ? file_get_contents($target) : '';
    if (!is_string($current)) throw new RuntimeException('Falha ao ler ads.txt.');
    $content = igua_adsense_ads_txt_build($current, $publisher);
    if (!is_writable($public)) throw new RuntimeException('Sem permissão de escrita na raiz pública.');
    $tmp = tempnam($public, '.ads-');
    if ($tmp === false) throw new RuntimeException('Não foi possível preparar ads.txt.');
    try {
        if (file_put_contents($tmp, $content, LOCK_EX) !== strlen($content))
            throw new RuntimeException('Não foi possível gravar o ads.txt completo.');
        @chmod($tmp, 0644);
        if (!rename($tmp, $target)) throw new RuntimeException('Não foi possível publicar ads.txt.');
    } finally { if (is_file($tmp)) @unlink($tmp); }
}

/** Apenas checagens locais objetivas; análise de qualidade é obrigatoriamente humana. */
function igua_adsense_readiness(PDO $db, string $publisher, string $siteUrl, bool $indexing): array {
    $checks = [];
    $checks[] = ['ok' => (bool)preg_match('/^ca-pub-\d{16}$/D', $publisher), 'title' => 'Conta AdSense', 'detail' => $publisher !== '' ? 'ID informado; propriedade poderá ser verificada pela metatag.' : 'Informe o ca-pub real obtido no AdSense.'];
    $checks[] = ['ok' => $indexing, 'title' => 'Indexação do portal', 'detail' => $indexing ? 'Opção de indexação habilitada.' : 'Libere a indexação em Configurações e SEO somente quando o conteúdo estiver pronto.'];
    $checks[] = ['ok' => (bool)preg_match('~^https://[^/]+/?$~iD', $siteUrl), 'title' => 'URL canônica HTTPS', 'detail' => $siteUrl !== '' ? $siteUrl : 'Configure o endereço HTTPS em Geral e SEO.'];
    try {
        $stmt = $db->query("SELECT COUNT(*) AS total, SUM(CASE WHEN is_demo=1 THEN 1 ELSE 0 END) AS exemplos FROM articles WHERE status='published' AND published_at<=NOW()");
        $counts = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $total = (int)($counts['total'] ?? 0);
        $examples = (int)($counts['exemplos'] ?? 0);
        $checks[] = ['ok' => $total > $examples, 'title' => 'Notícias reais publicadas', 'detail' => ($total-$examples).' matéria(s) não demonstrativa(s). A qualidade, a autoria e a originalidade exigem revisão humana.'];
        $checks[] = ['ok' => $examples === 0, 'title' => 'Acervo de demonstração', 'detail' => $examples === 0 ? 'Sem matérias de demonstração publicadas.' : $examples.' matéria(s) de exemplo ainda publicadas; retire-as da publicação antes de solicitar revisão.'];
    } catch (Throwable) {
        $checks[] = ['ok' => false, 'title' => 'Notícias e demonstrações', 'detail' => 'Não foi possível conferir a situação das matérias; verifique o banco.'];
    }
    $checks[] = ['ok' => true, 'title' => 'Páginas institucionais', 'detail' => 'Sobre, Contato, Privacidade e Termos disponíveis; confira se os textos descrevem sua operação real.'];
    return $checks;
}
