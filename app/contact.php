<?php
declare(strict_types=1);
/** Contato/Anúncios sem exposição do destinatário ao HTML. */
function igua_contact_send(array $input): void {
    $name=trim((string)($input['name']??''));$email=trim((string)($input['email']??''));
    $subject=trim((string)($input['subject']??'Contato'));$message=trim((string)($input['message']??''));
    if(($input['website']??'')!=='')throw new RuntimeException('Não foi possível verificar a solicitação.');
    if(!isset($input['consent']))throw new RuntimeException('É necessário concordar com o tratamento dos dados para responder ao contato.');
    if(strlen($name)<3 || strlen($name)>120 || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>190 || strlen($subject)>100 || strlen($message)<20 || strlen($message)>5000)throw new RuntimeException('Revise nome, e-mail, assunto e mensagem (mínimo 20, máximo 5.000 caracteres).');
    if(preg_match('/[
]/',$email.$name.$subject))throw new RuntimeException('Dados de contato inválidos.');
    // Não registra IP identificável em arquivo. Contador efêmero por hash de HMAC.
    $ip=(string)($_SERVER['REMOTE_ADDR']??'0');
    $secret=hash('sha256',(string)config()['password'].'|IguaNews-contact-rate-limit');
    $hash=hash_hmac('sha256',$ip,$secret);
    $folder=IGUA_STORAGE.'/contact-rate';
    if(!is_dir($folder) && !@mkdir($folder,0700,true) && !is_dir($folder))throw new RuntimeException('Proteção antiabuso indisponível.');
    $file=$folder.'/'.$hash.'.json';
    $handle=fopen($file,'c+');if(!$handle)throw new RuntimeException('Não foi possível validar o limite de envios.');
    try {
        if(!flock($handle,LOCK_EX))throw new RuntimeException('Proteção temporariamente indisponível.');
        $raw=stream_get_contents($handle);
        $log=json_decode($raw?:'[]',true)?:[];
        $now=time();$log=array_values(array_filter($log,static fn($timestamp)=>is_int($timestamp) && $timestamp>$now-3600));
        if(count($log)>=4)throw new RuntimeException('Limite de mensagens atingido. Tente novamente em uma hora.');
        igua_contact_deliver($name,$email,$subject,$message);
        $log[]=$now;ftruncate($handle,0);rewind($handle);fwrite($handle,json_encode($log));fflush($handle);
    }finally{flock($handle,LOCK_UN);fclose($handle);}
}
function igua_contact_deliver(string $name,string $email,string $subject,string $message): void {
    $host=trim((string)getenv('IGUANEWS_SMTP_HOST'));
    $username=trim((string)getenv('IGUANEWS_SMTP_USER'));
    $password=(string)getenv('IGUANEWS_SMTP_PASSWORD');
    $from=trim((string)getenv('IGUANEWS_SMTP_FROM'));
    $port=(int)(getenv('IGUANEWS_SMTP_PORT')?:587);
    if($host==='' || $username==='' || $password==='' || !filter_var($from,FILTER_VALIDATE_EMAIL) || $port<1 || $port>65535)throw new RuntimeException('Envio ainda não configurado. O administrador precisa configurar SMTP na hospedagem.');
    $autoload=IGUA_ROOT.'/vendor/autoload.php';
    if(!is_file($autoload))throw new RuntimeException('Envio indisponível: dependências Composer/PHPMailer ainda não instaladas.');
    require_once $autoload;
    if(!class_exists('PHPMailer\PHPMailer\PHPMailer'))throw new RuntimeException('Biblioteca de e-mail indisponível.');
    $mail=new PHPMailer\PHPMailer\PHPMailer(true);
    try{
        $mail->isSMTP();$mail->Host=$host;$mail->SMTPAuth=true;$mail->Username=$username;$mail->Password=$password;
        $mail->Port=$port;$mail->SMTPSecure=$port===465 ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Timeout=12;$mail->CharSet='UTF-8';$mail->setFrom($from,igua_brand_name());
        $mail->addAddress(IGUA_DEVELOPER_EMAIL);
        $mail->addReplyTo($email,$name);
        $mail->Subject='['.igua_brand_name().'] '.preg_replace('/[^\pL\pN .,_-]/u','',$subject);
        $mail->Body="Nome: {$name}\nE-mail para resposta: {$email}\nAssunto: {$subject}\n\n{$message}";
        $mail->send();
    }catch(Throwable $e){throw new RuntimeException('Não foi possível enviar agora. Verifique a configuração SMTP e tente novamente.');}
}
function igua_contact_page(string $route,array $input=[],array $errors=[]): void {
    $advertising=$route==='anuncie';$contact=$route==='contato';
    $title=$advertising?'Anuncie no '.igua_brand_name():'Fale com '.igua_brand_name();
    ?>
    <div class="info-hero"><span class="info-eyebrow"><?= h(igua_brand_name()) ?> / <?= $advertising?'PUBLICIDADE':'CONTATO' ?></span><h1><?=htmlspecialchars($title)?></h1><p><?= $advertising?'Divulgue seu negócio para leitores de Iguatama e região. Envie uma proposta sem precisar abrir um aplicativo de e-mail.':'Sugestões de pauta, correções e solicitações da comunidade.'?></p></div>
    <div class="info-layout"><section class="info-card"><h2><?= $advertising?'Solicitar proposta':'Enviar mensagem' ?></h2><p class="muted">Preencha o formulário. Seus dados são usados somente para responder à solicitação.</p>
    <?php foreach($errors as $err):?><p class="alert error" role="alert"><?=htmlspecialchars($err,ENT_QUOTES,'UTF-8')?></p><?php endforeach;?>
    <?php if(!empty($_SESSION['contact_success'])):unset($_SESSION['contact_success']);?><p class="alert success" role="status">Mensagem enviada. Nossa equipe poderá responder pelo e-mail informado.</p><?php endif;?>
    <form class="contact-form" method="post" action="/index.php?p=<?=$advertising?'anuncie':'contato'?>">
    <?=csrf_field()?><div class="contact-grid"><label>Seu nome<input required name="name" maxlength="120" autocomplete="name" value="<?=h($input['name']??'')?>"></label>
    <label>Seu e-mail para resposta<input required name="email" type="email" autocomplete="email" maxlength="190" value="<?=h($input['email']??'')?>"></label></div>
    <label>Assunto<select name="subject"><option value="<?=$advertising?'Publicidade':'Sugestão de notícia'?>"><?=$advertising?'Publicidade':'Sugestão de notícia'?></option><option value="Correção de conteúdo">Correção de conteúdo</option><option value="Contato geral">Contato geral</option><option value="Parceria">Parceria</option></select></label>
    <label>Mensagem<textarea name="message" required minlength="20" maxlength="5000" rows="6" placeholder="Descreva sua solicitação e, se for anúncio, informe formato, período e objetivo."><?=h($input['message']??'')?></textarea></label>
    <label class="contact-consent"><input required type="checkbox" name="consent" value="1"><span>Concordo com o uso dos meus dados para resposta, conforme a <a href="/index.php?p=privacidade">Política de Privacidade</a>.</span></label>
    <div class="contact-trap" aria-hidden="true"><label>Não preencha <input name="website" type="text" autocomplete="off" tabindex="-1"></label></div>
    <button class="btn primary" type="submit">Enviar mensagem →</button></form></section>
    <aside class="info-side"><span class="info-side-icon">✉</span><h3>Comunicação direta</h3><p>Seu endereço e a mensagem não ficam públicos no portal. Não publique senhas ou informações bancárias no formulário.</p><p class="muted">O envio requer serviço SMTP configurado no servidor. Se estiver em desenvolvimento local sem SMTP, você verá uma mensagem de indisponibilidade.</p></aside></div>
    <?php
}
