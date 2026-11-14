<?php
declare(strict_types=1);
/** Renderização privada, preservando HTML da versão 1.0.37. */
function bus_admin_view_routes(PDO $db,string $action): void {
    if($action==='bus') {
        $routes=$db->query('SELECT r.*, (SELECT COUNT(*) FROM bus_stops WHERE route_id=r.id) AS stop_count,(SELECT COUNT(*) FROM bus_departures WHERE route_id=r.id) AS dep_count, (SELECT city FROM bus_stops WHERE route_id=r.id ORDER BY stop_order ASC LIMIT 1) AS origin_city, (SELECT city FROM bus_stops WHERE route_id=r.id ORDER BY stop_order DESC LIMIT 1) AS destination_city FROM bus_routes r ORDER BY r.active DESC,r.name')->fetchAll();
        $origins=[];
        foreach($routes as $route){$city=trim((string)($route['origin_city']??''));if($city!=='')$origins[$city]=$city;}
        natcasesort($origins);
        $selectedOrigin=isset($_GET['origin']) && is_string($_GET['origin']) ? trim($_GET['origin']) : '';
        $selectedOrigin=isset($origins[$selectedOrigin])?$selectedOrigin:'';
        $visibleRoutes=$selectedOrigin===''?$routes:array_values(array_filter($routes,static fn($route)=>($route['origin_city']??'')===$selectedOrigin));
        ?><section class="panel bus-admin-overview"><div class="bus-admin-heading"><div><h2>Linhas e horários</h2><p class="muted">Cadastre saídas por etapas, consulte horários existentes e proteja linhas fixas contra importações oficiais.</p></div></div><div class="bus-admin-actions"><a class="btn outline small" href="/admin.php?action=bus_import">↓ Importar DER / ANTT</a><a class="btn primary small" href="/admin.php?action=bus_edit">+ Novo horário</a></div>
        <?php $teixeiraSummary=bus_teixeira_2026_validate(); ?>
        <div class="panel bus-teixeira-import"><h3>🚌 Tabela Teixeira · 14/09/2026</h3>
        <p>Transcrição da fotografia recebida: <?= (int)$teixeiraSummary['routes'] ?> trajetos, <?= (int)$teixeiraSummary['departures'] ?> grupos de saídas. Origem e destino: rodoviárias. Duração e tarifa não informadas.</p>
        <p class="muted">Sábados e domingos são cadastrados separadamente; <strong>feriados em dias úteis exigem confirmação</strong>, pois o calendário do portal ainda não substitui horários por feriado. Confira os horários com a empresa antes de viajar.</p>
        <form method="post" action="/admin.php?action=bus_import_teixeira" class="bus-teixeira-confirm"><?=csrf_field() ?><label><input type="checkbox" name="teixeira_confirm" value="1" required> Conferi a tabela e autorizo cadastrar estas linhas</label><button type="submit" class="btn primary small" data-confirm="Cadastrar os horários da Teixeira informados na foto? Linhas existentes serão preservadas, sem sobreposição.">Cadastrar tabela Teixeira</button></form>
        </div>
        <div class="alert warning">Os horários exibidos ao público são <strong>programados e informados por você</strong>, não GPS ou confirmação da empresa. Verifique informações oficiais antes de publicar.</div>
        <?php if($routes): ?>
        <form class="bus-admin-origin-filter" method="get" action="/admin.php" aria-label="Filtrar linhas pela cidade de origem">
          <input type="hidden" name="action" value="bus">
          <label for="busAdminOrigin">Cidade de origem
            <select id="busAdminOrigin" name="origin"><option value="">Todas as cidades (<?=count($routes)?> linhas)</option>
              <?php foreach($origins as $city): ?><option value="<?=h($city)?>" <?= $selectedOrigin===$city?'selected':'' ?>><?=h($city)?></option><?php endforeach; ?>
            </select>
          </label>
          <button type="submit" class="btn primary small">Filtrar</button>
          <?php if($selectedOrigin!==''): ?><a href="/admin.php?action=bus" class="btn outline small">Limpar</a><?php endif; ?>
        </form>
        <div class="bus-admin-list-header"><h3>Linhas cadastradas</h3><small><?=count($visibleRoutes)?> de <?=count($routes)?> linha(s)</small></div>
        <div class="bus-admin-route-list">
          <?php foreach($visibleRoutes as $r): ?>
          <article class="bus-admin-route-card" aria-label="<?=h($r['name'])?>">
            <div class="bus-admin-route-head"><div class="bus-admin-route-title"><strong><?=h($r['name'])?></strong>
              <?php if(!empty($r['origin_city'])): ?><span class="bus-admin-route-journey">De <?=h($r['origin_city'])?> para <?=h($r['destination_city']??'Destino não informado')?></span><?php else: ?><span class="bus-admin-route-journey">Origem a conferir na edição</span><?php endif; ?>
            </div><span class="bus-admin-route-status <?= (int)($r['active']??0)?'is-active':'is-inactive' ?>"><?= (int)($r['active']??0)?'Ativa':'Inativa' ?></span></div>
            <div class="bus-admin-route-metadata">
              <span><small>Empresa</small><?=h($r['company']?:'Não informada')?></span>
              <span><small>Paradas</small><?=(int)$r['stop_count']?></span>
              <span><small>Horários</small><?=(int)$r['dep_count']?></span>
              <span><small>Cadastro</small><?=h(['antt'=>'ANTT','der_mg'=>'DER-MG','manual'=>'Manual'][$r['source_type']]??'Manual')?></span>
            </div>
            <div class="bus-admin-route-footer"><span class="bus-admin-route-lock"><?=($r['sync_mode']??'')==='fixed'?'🔒 Linha fixa':'Atualizável'?></span><div class="bus-admin-route-buttons">
              <a href="/admin.php?action=bus_edit&amp;id=<?=(int)$r['id']?>" class="btn outline small" aria-label="Editar percurso e origem da linha <?=h($r['name'])?>">Editar percurso</a>
              <?php if(($r['source_type']??'manual')!=='manual'): ?><form method="post" action="/admin.php?action=bus_toggle_fixed"><?=csrf_field()?><input type="hidden" name="id" value="<?=(int)$r['id']?>"><input type="hidden" name="mode" value="<?=($r['sync_mode']??'')==='fixed'?'auto':'fixed'?>"><button class="btn outline small" title="Definir proteção da linha"><?=($r['sync_mode']??'')==='fixed'?'Permitir revisão':'🔒 Fixar'?></button></form><?php endif; ?>
            </div></div>
          </article>
          <?php endforeach; ?>
        </div>
        <?php if(!$visibleRoutes): ?><p class="muted">Nenhuma linha encontrada para esta origem.</p><?php endif; ?>
        <?php else:?><div class="bus-empty"><div class="bus-empty-icon">🚌</div><h3>Nenhuma linha cadastrada</h3><p>Cadastre a cidade de saída, as paradas com horários e, no último cartão, a empresa e os dias da semana.</p><a class="btn primary" href="/admin.php?action=bus_edit">Cadastrar primeiro horário</a></div><?php endif;?></section><?php
        return;
    }
}
