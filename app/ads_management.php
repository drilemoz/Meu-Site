<?php
declare(strict_types=1);
/** Operações de publicidade: sem migração e sem excluir anúncios de clientes. */
function igua_ad_dates_overlap(string $leftStart, ?string $leftEnd, string $rightStart, ?string $rightEnd): bool {
    return ($leftEnd===null || $leftEnd >= $rightStart) && ($rightEnd===null || $rightEnd >= $leftStart);
}
function igua_ad_overlapping_campaigns(array $campaigns,string $slot,string $start,?string $end,int $editingId=0): array {
    return array_values(array_filter($campaigns,static function(array $c)use($slot,$start,$end,$editingId):bool {
        return (string)($c['placement_slug']??'')===$slot && (int)($c['id']??0)!==$editingId
            && (int)($c['active']??0)===1
            && igua_ad_dates_overlap($start,$end,(string)$c['start_date'],isset($c['end_date'])&&$c['end_date']!==''?(string)$c['end_date']:null);
    }));
}
function igua_ad_demo_count(PDO $db):int {
    $stmt=$db->prepare('SELECT COUNT(*) FROM ad_campaigns WHERE target_url LIKE ?');
    $stmt->execute([AD_DEMO_URL_PREFIX.'%']);
    return (int)$stmt->fetchColumn();
}
/** Apaga só campanhas com a URL interna de demonstração; jamais remove mídias ou dados reais. */
function igua_ad_remove_demo_campaigns(PDO $db):int {
    $db->beginTransaction();
    try {
        // InnoDB: bloqueia as posições antes de apagar demos; inserts em campanhas
        // exigem FK para a posição, evitando desativar posição de anúncio real recém-criado.
        $db->query('SELECT slug FROM ad_placements ORDER BY slug FOR UPDATE')->fetchAll();
        $stmt=$db->prepare('DELETE FROM ad_campaigns WHERE target_url LIKE ?');
        $stmt->execute([AD_DEMO_URL_PREFIX.'%']);
        $count=$stmt->rowCount();
        // Posições locais sem NENHUMA campanha deixam de estar habilitadas;
        // AdSense e posições com campanhas reais (inclusive pausadas) não são alteradas.
        $db->exec("UPDATE ad_placements p SET enabled=0 WHERE p.provider='local' AND NOT EXISTS (SELECT 1 FROM ad_campaigns c WHERE c.placement_slug=p.slug)");
        $db->commit();
        return $count;
    } catch(Throwable $e) {
        if($db->inTransaction())$db->rollBack();
        throw $e;
    }
}
