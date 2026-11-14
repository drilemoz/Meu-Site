<?php
declare(strict_types=1);
require_once __DIR__.'/bus.php';
/** Converte horários locais e dias explícitos em offsets. Nunca adivinha a passagem da meia-noite. */
function bus_wizard_offsets(string $start, array $hours, array $days): array {
    if (!bus_valid_time($start)) throw new InvalidArgumentException('Informe o horário da primeira saída.');
    if (count($hours)!==count($days) || count($hours)<2 || count($hours)>25) throw new InvalidArgumentException('Informe origem e ao menos um destino (máximo de 25 cidades).');
    [$h,$m]=array_map('intval',explode(':',$start));$first=$h*60+$m;
    $offsets=[];
    foreach($hours as $i=>$clock) {
        $clock=trim((string)$clock);$day=trim((string)$days[$i]);
        if($i>0 && $clock==='') { $offsets[]=''; continue; }
        if(!bus_valid_time($clock) || !preg_match('/^[0-7]$/D',$day))throw new InvalidArgumentException('Confira os horários e o dia de cada parada.');
        [$hh,$mm]=array_map('intval',explode(':',$clock));
        $diff=(int)$day*1440+$hh*60+$mm-$first;
        if($diff<0 || $diff>10080)throw new InvalidArgumentException('Uma parada ficou antes da partida. Se for após meia-noite, escolha Dia seguinte.');
        if($i===0 && ($diff!==0 || $day!=='0' || $clock!==$start))throw new InvalidArgumentException('O primeiro ponto precisa coincidir com o horário de saída.');
        $offsets[]=(string)$diff;
    }
    return $offsets;
}
function bus_wizard_fare(string $raw): ?string {
    $raw=trim($raw);
    if($raw==='')return null;
    if(!preg_match('/^\d{1,6}(?:[,.]\d{1,2})?$/D',$raw))throw new InvalidArgumentException('Tarifa inválida. Informe um valor em reais, por exemplo 12,50.');
    $value=(float)str_replace(',','.',$raw);
    if($value>999999.99)throw new InvalidArgumentException('Tarifa muito alta.');
    return number_format($value,2,'.','');
}
/** Uma única transação salva percurso e partida sem criar trajetos vazios. */
function bus_wizard_save(PDO $db,array $form): int {
    $id=(int)($form['id']??0);$departureId=(int)($form['departure_id']??0);
    $start=trim((string)($form['start_time']??''));
    $cities=(array)($form['stop_city']??[]);$labels=(array)($form['stop_label']??[]);
    $hours=(array)($form['stop_clock']??[]);$days=(array)($form['stop_day']??[]);
    $offsets=bus_wizard_offsets($start,$hours,$days);
    $stops=bus_clean_stops($cities,$labels,$offsets);
    $company=trim((string)($form['company']??''));$notes=trim((string)($form['notes']??''));
    if($company===''||mb_bus_len($company)>160||mb_bus_len($notes)>500)throw new InvalidArgumentException('Informe a empresa (até 160 caracteres) e revise as observações.');
    $fare=bus_wizard_fare((string)($form['fare']??''));
    $mask=bus_weekday_mask((array)($form['days']??[]));
    $active=isset($form['active'])?1:0;
    $name=implode(' → ',array_map(static fn($s)=>$s['city'],$stops));
    if(mb_bus_len($name)>160) $name=$stops[0]['city'].' → '.$stops[count($stops)-1]['city'];
    if(mb_bus_len($name)>160)throw new InvalidArgumentException('Nomes das cidades muito longos.');
    $db->beginTransaction();
    try {
        if($id>0) {
            $s=$db->prepare('SELECT id FROM bus_routes WHERE id=? FOR UPDATE');$s->execute([$id]);
            if(!$s->fetchColumn())throw new InvalidArgumentException('Esta linha não existe mais.');
            $db->prepare("UPDATE bus_routes SET name=?,company=?,notes=?,fare_amount=?,active=?,sync_mode='fixed' WHERE id=?")
                ->execute([$name,$company,$notes,$fare,$active,$id]);
            $db->prepare('DELETE FROM bus_stops WHERE route_id=?')->execute([$id]);
        } else {
            if($departureId>0)throw new InvalidArgumentException('Partida incompatível com a linha.');
            $db->prepare("INSERT INTO bus_routes (name,company,notes,fare_amount,active,sync_mode) VALUES (?,?,?,?,?,'fixed')")
                ->execute([$name,$company,$notes,$fare,$active]);
            $id=(int)$db->lastInsertId();
        }
        $insert=$db->prepare('INSERT INTO bus_stops (route_id,city,location_label,minutes_from_start,stop_order) VALUES (?,?,?,?,?)');
        foreach($stops as $s)$insert->execute([$id,$s['city'],$s['location_label'],$s['minutes_from_start'],$s['stop_order']]);
        if($departureId>0){
            $find=$db->prepare('SELECT id FROM bus_departures WHERE id=? AND route_id=? FOR UPDATE');$find->execute([$departureId,$id]);
            if(!$find->fetchColumn())throw new InvalidArgumentException('O horário informado não pertence à linha.');
            $db->prepare('UPDATE bus_departures SET start_time=?,days_mask=? WHERE id=? AND route_id=?')->execute([$start,$mask,$departureId,$id]);
        }else {
            $exists=$db->prepare('SELECT id FROM bus_departures WHERE route_id=? AND start_time=? AND days_mask=?');
            $exists->execute([$id,$start,$mask]);
            if($exists->fetchColumn())throw new InvalidArgumentException('Este mesmo horário já existe para os dias escolhidos.');
            $db->prepare('INSERT INTO bus_departures (route_id,start_time,days_mask) VALUES (?,?,?)')->execute([$id,$start,$mask]);
        }
        $db->commit();return $id;
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
function bus_wizard_view(PDO $db,int $id): void {
    $route=null;$stops=[];$departures=[];
    if($id>0){
        $s=$db->prepare('SELECT * FROM bus_routes WHERE id=?');$s->execute([$id]);$route=$s->fetch();
        if(!$route){http_response_code(404);echo '<div class="alert error">Linha não encontrada.</div>';return;}
        $s=$db->prepare('SELECT city,location_label,minutes_from_start FROM bus_stops WHERE route_id=? ORDER BY stop_order');$s->execute([$id]);$stops=$s->fetchAll();
        $s=$db->prepare('SELECT * FROM bus_departures WHERE route_id=? ORDER BY start_time,id');$s->execute([$id]);$departures=$s->fetchAll();
    }
    $depWanted=(int)($_GET['departure_id']??0);$dep=null;
    foreach($departures as $candidate){if($depWanted===(int)$candidate['id']){$dep=$candidate;break;}}
    if(!$dep && !$depWanted && empty($_GET['new_departure']))$dep=$departures[0]??null;
    if($depWanted && !$dep){echo '<div class="alert error">Horário não encontrado.</div>';return;}
    $payload=['routeId'=>$id,'departureId'=>(int)($dep['id']??0),'time'=>isset($dep['start_time'])?substr((string)$dep['start_time'],0,5):'','daysMask'=>(int)($dep['days_mask']??0),
        'company'=>(string)($route['company']??''),'notes'=>(string)($route['notes']??''),'fare'=>($route && $route['fare_amount']!==null)?number_format((float)$route['fare_amount'],2,',',''):'',
        'active'=>(bool)($route['active']??true),'stops'=>$stops];
    $draft=$_SESSION['bus_wizard_unsaved']??null;
    if(is_array($draft) && (int)($draft['id']??0)===$id){
        unset($_SESSION['bus_wizard_unsaved']);
        $payload['departureId']=(int)($draft['departure_id']??0);
        $payload['time']=(string)($draft['start_time']??'');
        $payload['company']=(string)($draft['company']??'');
        $payload['fare']=(string)($draft['fare']??'');
        $payload['notes']=(string)($draft['notes']??'');
        $payload['daysMask']=0;
        foreach((array)($draft['days']??[]) as $day)if(preg_match('/^[0-6]$/D',(string)$day))$payload['daysMask']|=1<<(int)$day;
        $payload['active']=isset($draft['active']);
        $payload['stops']=[];
        $cities=(array)($draft['stop_city']??[]);
        foreach($cities as $i=>$city){
            $clock=(string)($draft['stop_clock'][$i]??'');
            $day=(string)($draft['stop_day'][$i]??'0');
            $min=bus_valid_time($clock)&&bus_valid_time($payload['time'])?(((int)$day)*1440+(int)substr($clock,0,2)*60+(int)substr($clock,3,2)-((int)substr($payload['time'],0,2)*60+(int)substr($payload['time'],3,2))):null;
            $payload['stops'][]=['city'=>(string)$city,'location_label'=>(string)($draft['stop_label'][$i]??''),'minutes_from_start'=>$min];
        }
    }
    if(!$id && !$payload['stops'])$payload['stops']=[['city'=>bus_site_origin_city(setting('city','Iguatama - MG')),'location_label'=>'','minutes_from_start'=>0]];
    ?>
    <section class="bus-wizard-shell" aria-label="Assistente de horário de ônibus">
      <div class="bus-wizard-heading"><div><span class="bus-wizard-kicker">CADASTRO GUIADO · <?= h(igua_brand_name()) ?></span><h2><?= $id?'Editar horário e percurso':'Novo horário de ônibus' ?></h2><details class="bus-wizard-info"><summary aria-label="Ajuda sobre o assistente" title="Ajuda">?</summary><p>Preencha uma etapa por vez. Você pode voltar e corrigir os dados antes de salvar.</p></details></div><a class="btn outline small" href="/admin.php?action=bus">Fechar</a></div>
      <?php if($id):?><div class="bus-wizard-edit-note">Ao editar o percurso, as paradas são atualizadas para todos os horários desta linha. Dados oficiais alterados manualmente tornam a linha <strong>🔒 fixa</strong>.</div><?php endif;?>
      <div class="bus-wizard-progress" aria-label="Etapas do cadastro"><span class="current" data-wizard-dot="origin">1 <small>Saída</small></span><i></i><span data-wizard-dot="stop">2 <small>Trajeto</small></span><i></i><span data-wizard-dot="finish">3 <small>Finalizar</small></span></div>
      <form method="post" id="busWizardForm" action="/admin.php?action=save_bus_wizard" data-bus-wizard><?=csrf_field()?><input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="departure_id" value="<?=(int)($dep['id']??0)?>"><div id="busWizardHidden"></div>
        <div class="bus-wizard-step" data-wizard-step="origin">
          <div class="bus-wizard-icon">🚌</div><div class="bus-wizard-overline">ETAPA 1 · INÍCIO DA VIAGEM</div><h3>De onde sai o ônibus?</h3><details class="bus-wizard-info bus-wizard-step-info"><summary aria-label="Ajuda desta etapa" title="Ajuda">?</summary><p>Preencha a cidade, o ponto de embarque e a hora em que o veículo parte.</p></details>
          <div class="bus-wizard-fields"><label>Cidade de origem<input id="wizardOriginCity" type="text" placeholder="Ex.: Iguatama" maxlength="100" autocomplete="off" required></label><label>Ponto de saída<input id="wizardOriginLabel" type="text" placeholder="Ex.: Rodoviária" maxlength="150"></label><label>Horário de saída<input id="wizardStart" type="time" required></label></div>
          <div class="bus-wizard-footer"><span class="bus-wizard-help">Hora local de Minas Gerais</span><button type="button" class="btn primary" data-wizard-action="origin-next">Avançar →</button></div>
        </div>
        <div class="bus-wizard-step" data-wizard-step="stop" hidden>
          <div class="bus-wizard-icon">📍</div><div class="bus-wizard-overline" id="wizardStopOverline">ETAPA 2 · PRÓXIMA CIDADE</div><h3>Qual é a próxima parada?</h3><details class="bus-wizard-info bus-wizard-step-info"><summary aria-label="Ajuda desta etapa" title="Ajuda">?</summary><p>Inclua cada cidade na sequência do trajeto, com horário de passagem ou chegada.</p></details>
          <div class="bus-wizard-fields"><label>Cidade da parada<input id="wizardStopCity" maxlength="100" placeholder="Ex.: Arcos" required></label><label>Ponto / local<input id="wizardStopLabel" maxlength="150" placeholder="Ex.: Terminal rodoviário"></label><div class="bus-wizard-time-fields"><label>Horário previsto<input id="wizardStopClock" type="time"></label><label>Dia da viagem<select id="wizardStopDay"><option value="0">Mesmo dia</option><option value="1">Dia seguinte (+1)</option><option value="2">Após 2 dias</option><option value="3">Após 3 dias</option><option value="4">Após 4 dias</option><option value="5">Após 5 dias</option><option value="6">Após 6 dias</option><option value="7">Após 7 dias</option></select></label></div><label class="bus-wizard-check"><input type="checkbox" id="wizardStopUnknown"> <span>Horário ainda não confirmado pela empresa</span></label></div>
          <div class="bus-wizard-rail" id="wizardTrail" aria-live="polite"></div>
          <div class="bus-wizard-footer bus-wizard-footer-stops"><button type="button" class="btn outline" data-wizard-action="stop-back">← Voltar</button><div class="bus-wizard-stop-buttons"><button type="button" class="btn outline" data-wizard-action="stop-next">+ Outra parada</button><button type="button" class="btn primary" data-wizard-action="stop-finish">Última cidade →</button></div></div>
        </div>
        <div class="bus-wizard-step" data-wizard-step="finish" hidden>
          <div class="bus-wizard-icon">✅</div><div class="bus-wizard-overline">ETAPA 3 · REVISÃO FINAL</div><h3>Empresa, tarifa e circulação</h3><p class="bus-wizard-explain">Confirme os dias em que este horário circula. Horários são programados, não localização em tempo real.</p>
          <div class="bus-wizard-fields"><label>Empresa de ônibus<input id="wizardCompany" maxlength="160" placeholder="Nome da viação" required></label><label>Valor da passagem (R$) · trajeto completo<input id="wizardFare" type="text" maxlength="10" inputmode="decimal" placeholder="Ex.: 15,50 (opcional)"></label><div class="bus-wizard-days"><strong>Dias de circulação</strong><div class="bus-weekdays" id="wizardWeekdays"><?php foreach([1=>'Seg',2=>'Ter',3=>'Qua',4=>'Qui',5=>'Sex',6=>'Sáb',0=>'Dom'] as $number=>$label):?><label><input type="checkbox" value="<?=$number?>"><span><?=$label?></span></label><?php endforeach;?></div></div><label>Observações (opcional)<textarea id="wizardNotes" rows="2" maxlength="500" placeholder="Feriados, alterações, telefone da empresa..."></textarea></label><label class="bus-wizard-check"><input id="wizardActive" type="checkbox" checked><span>Linha visível no portal</span></label></div>
          <div class="bus-wizard-summary" id="wizardSummary" aria-live="polite"></div>
          <div class="bus-wizard-footer"><button class="btn outline" type="button" data-wizard-action="finish-back">← Voltar</button><button class="btn primary" type="button" data-wizard-action="save">Salvar horário ✓</button></div>
        </div>
        <p class="bus-wizard-error" id="wizardError" role="alert" hidden></p>
      </form>
      <noscript><div class="alert error">Ative o JavaScript no navegador para usar o cadastro guiado. Os horários já publicados continuam disponíveis.</div></noscript>
      <script type="application/json" id="busWizardData"><?=json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_THROW_ON_ERROR)?></script>
    </section>
    <?php if($id):?><div class="bus-wizard-schedules"><div class="panel-heading"><div><h3>Outros horários desta linha</h3><p class="muted">Cada horário tem seus próprios dias de circulação.</p></div><a class="btn outline small" href="/admin.php?action=bus_edit&id=<?=$id?>&new_departure=1">+ Novo horário nesta linha</a></div>
      <?php foreach($departures as $d):?><div class="bus-wizard-schedule"><div><strong><?=h(substr($d['start_time'],0,5))?></strong><small><?=h(bus_weekday_labels((int)$d['days_mask']))?> · <?=(int)$d['active']?'Ativo':'Pausado'?></small></div><div class="bus-wizard-schedule-actions"><a href="/admin.php?action=bus_edit&id=<?=$id?>&departure_id=<?=(int)$d['id']?>">Editar</a><form method="post" action="/admin.php?action=toggle_bus_departure"><?=csrf_field()?><input type="hidden" name="id" value="<?=(int)$d['id']?>"><button><?=(int)$d['active']?'Pausar':'Reativar'?></button></form><form method="post" action="/admin.php?action=delete_bus_departure"><?=csrf_field()?><input type="hidden" name="id" value="<?=(int)$d['id']?>"><button data-confirm="Excluir este horário?" class="danger-link">Excluir</button></form></div></div><?php endforeach;?></div>
      <div class="bus-wizard-danger"><form method="post" action="/admin.php?action=delete_bus_route"><?=csrf_field()?><input type="hidden" name="id" value="<?=$id?>"><button data-confirm="Excluir permanentemente a linha com todas as paradas e horários?" class="danger-link">Excluir linha e horários</button></form></div>
    <?php endif;?>
    <script src="/assets/js/bus-wizard.js" defer></script>
    <?php
}
