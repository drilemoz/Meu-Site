<?php
declare(strict_types=1);
/** Renderização privada, preservando HTML da versão 1.0.37. */
function bus_admin_view_routes(PDO $db,string $action): void {
    if($action==='bus') {
        $routes=$db->query('SELECT r.*, (SELECT COUNT(*) FROM bus_stops WHERE route_id=r.id) AS stop_count,(SELECT COUNT(*) FROM bus_departures WHERE route_id=r.id) AS dep_count FROM bus_routes r ORDER BY r.active DESC,r.name')->fetchAll();
        ?><section class="panel bus-admin-overview"><div class="bus-admin-heading"><div><h2>Linhas e horários</h2><p class="muted">Cadastre saídas por etapas, consulte horários existentes e proteja linhas fixas contra importações oficiais.</p></div></div><div class="bus-admin-actions"><a class="btn outline small" href="/admin.php?action=bus_import">↓ Importar DER / ANTT</a><a class="btn primary small" href="/admin.php?action=bus_edit">+ Novo horário</a></div>
        <?php $teixeiraSummary=bus_teixeira_2026_validate(); ?>
        <div class="panel bus-teixeira-import"><h3>🚌 Tabela Teixeira · 14/09/2026</h3>
        <p>Transcrição da fotografia recebida: <?= (int)$teixeiraSummary['routes'] ?> trajetos, <?= (int)$teixeiraSummary['departures'] ?> grupos de saídas. Origem e destino: rodoviárias. Duração e tarifa não informadas.</p>
        <p class="muted">Sábados e domingos são cadastrados separadamente; <strong>feriados em dias úteis exigem confirmação</strong>, pois o calendário do portal ainda não substitui horários por feriado. Confira os horários com a empresa antes de viajar.</p>
        <form method="post" action="/admin.php?action=bus_import_teixeira" data-confirm="Cadastrar os horários da Teixeira informados na foto? Linhas existentes serão preservadas, sem sobreposição." class="bus-teixeira-confirm"><?=csrf_field() ?><label><input type="checkbox" name="teixeira_confirm" value="1" required> Conferi a tabela e autorizo cadastrar estas linhas</label><button type="submit" class="btn primary small">Cadastrar tabela Teixeira</button></form>
        </div>
        <div class="alert warning">Os horários exibidos ao público são <strong>programados e informados por você</strong>, não GPS ou confirmação da empresa. Verifique informações oficiais antes de publicar.</div>
        <?php if($routes):?><div class="table-wrap"><table><thead><tr><th>Linha</th><th>Empresa</th><th>Paradas</th><th>Horários</th><th>Origem</th><th>Proteção</th><th>Status</th><th>Ações</th></tr></thead><tbody>
        <?php foreach($routes as $r): ?><tr><td><strong><?=h($r['name'])?></strong></td><td><?=h($r['company']?:'Não informada')?></td><td><?= (int)$r['stop_count']?></td><td><?= (int)$r['dep_count']?></td><td><?=h(['antt'=>'ANTT','der_mg'=>'DER-MG','manual'=>'Manual'][$r['source_type']]??'Manual')?></td><td><?= $r['sync_mode']==='fixed'?'🔒 Fixa':'Atualizável'?></td><td><?= (int)$r['active']?'Ativa':'Inativa'?></td><td class="action-cell"><a href="/admin.php?action=bus_edit&id=<?= (int)$r['id']?>">Editar →</a><?php if(($r['source_type']??'manual')!=='manual'):?><form method="post" action="/admin.php?action=bus_toggle_fixed"><?=csrf_field()?><input type="hidden" name="id" value="<?=(int)$r['id']?>"><input type="hidden" name="mode" value="<?=$r['sync_mode']==='fixed'?'auto':'fixed'?>"><button title="Definir proteção da linha"><?=$r['sync_mode']==='fixed'?'Permitir revisão':'🔒 Fixar'?></button></form><?php endif;?></td></tr><?php endforeach;?></tbody></table></div>
        <?php else:?><div class="bus-empty"><div class="bus-empty-icon">🚌</div><h3>Nenhuma linha cadastrada</h3><p>Cadastre a cidade de saída, as paradas com horários e, no último cartão, a empresa e os dias da semana.</p><a class="btn primary" href="/admin.php?action=bus_edit">Cadastrar primeiro horário</a></div><?php endif;?></section><?php
        return;
    }
}
