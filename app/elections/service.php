<?php
declare(strict_types=1);
/** Validação desacoplada de $_POST, PDO e templates. Sem dados eleitorais simulados. */
function meu_election_offices(string $scope): array {
    return $scope==='municipal'
        ? ['mayor'=>'Prefeito(a)','councilor'=>'Vereador(a)']
        : ['president'=>'Presidente','governor'=>'Governador(a)','senator'=>'Senador(a)',
           'federal_deputy'=>'Deputado(a) federal','state_deputy'=>'Deputado(a) estadual/distrital'];
}
function meu_election_uf_valid(string $uf): bool {
    return in_array($uf, ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'],true);
}
function meu_election_clean_text(mixed $raw,int $limit,bool $required=true): string {
    if(!is_string($raw))throw new InvalidArgumentException('Campo de texto inválido.');
    $value=trim(preg_replace('/\s+/u',' ',$raw)??'');
    if(($required && $value==='') || (function_exists('mb_strlen')?mb_strlen($value,'UTF-8'):(function_exists('ulen')?ulen($value):strlen($value)))>$limit || preg_match('/[\x00-\x08\x0B-\x1F\x7F]/',$value))
        throw new InvalidArgumentException('Confira os campos de texto: há valores vazios, inválidos ou muito longos.');
    return $value;
}
function meu_election_validate(array $input): array {
    $title=meu_election_clean_text($input['title']??null,180);
    $year=filter_var($input['election_year']??null,FILTER_VALIDATE_INT);
    if($year===false || $year<1990 || $year>2100)throw new InvalidArgumentException('Informe um ano entre 1990 e 2100.');
    $scope=(string)($input['election_scope']??'');
    if(!in_array($scope,['municipal','general'],true))throw new InvalidArgumentException('Abrangência eleitoral inválida.');
    $office=(string)($input['office']??'');
    if(!array_key_exists($office,meu_election_offices($scope)))throw new InvalidArgumentException('Cargo incompatível com a abrangência.');
    $round=filter_var($input['round_number']??null,FILTER_VALIDATE_INT);
    if(!in_array($round,[1,2],true))throw new InvalidArgumentException('Turno inválido.');
    // Vereadores e deputados não disputam segundo turno.
    if($round===2 && in_array($office,['councilor','senator','federal_deputy','state_deputy'],true))
        throw new InvalidArgumentException('Este cargo não possui segundo turno.');
    $uf=strtoupper(trim((string)($input['state_code']??'')));
    if($uf!==''&&!meu_election_uf_valid($uf))throw new InvalidArgumentException('UF inválida.');
    $city=meu_election_clean_text($input['municipality']??'',120,false);
    $ibge=trim((string)($input['municipality_ibge']??''));
    if($ibge!==''&&!preg_match('/^[0-9]{7}$/D',$ibge))throw new InvalidArgumentException('Código IBGE deve conter sete dígitos.');
    if($scope==='municipal' && ($uf===''||$city===''))throw new InvalidArgumentException('Eleições municipais exigem UF e município.');
    if($scope==='general' && ($city!==''||$ibge!==''))throw new InvalidArgumentException('Use a abrangência municipal para informar cidade.');
    if($scope==='general' && $office!=='president' && $uf==='')throw new InvalidArgumentException('Informe a UF para este cargo.');
    $date=trim((string)($input['election_date']??''));
    if($date!==''){
        $parsed=DateTimeImmutable::createFromFormat('!Y-m-d',$date);
        if(!$parsed || $parsed->format('Y-m-d')!==$date || (int)$parsed->format('Y')!==$year)
            throw new InvalidArgumentException('Data inválida ou fora do ano eleitoral.');
    }
    $source=trim((string)($input['source_url']??''));
    if($source!=='' && (strlen($source)>400 || !filter_var($source,FILTER_VALIDATE_URL) || (parse_url($source,PHP_URL_SCHEME)!=='https') || parse_url($source,PHP_URL_USER)!==null || parse_url($source,PHP_URL_PASS)!==null))
        throw new InvalidArgumentException('A referência deve ser uma URL HTTPS válida, sem credenciais.');
    return compact('title','year','scope','office','round','uf','city','ibge','date','source');
}
function meu_candidate_validate(array $input): array {
    $full=meu_election_clean_text($input['full_name']??null,160);
    $ballot=meu_election_clean_text($input['ballot_name']??null,120);
    if((function_exists('mb_strlen')?mb_strlen($full,'UTF-8'):strlen($full))<2 || (function_exists('mb_strlen')?mb_strlen($ballot,'UTF-8'):strlen($ballot))<2)
        throw new InvalidArgumentException('Nome e nome na urna precisam ter ao menos dois caracteres.');
    $number=trim((string)($input['candidate_number']??''));
    if(!preg_match('/^[0-9]{1,6}$/D',$number))throw new InvalidArgumentException('Número eleitoral deve conter de 1 a 6 dígitos.');
    $abbr=meu_election_clean_text($input['party_abbr']??'',30,false);
    $party=meu_election_clean_text($input['party_name']??'',120,false);
    return compact('full','ballot','number','abbr','party');
}
