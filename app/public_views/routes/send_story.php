<div class="info-layout"><section class="info-card"><h1>Envie sua notícia</h1><p class="muted">Conte à redação o que aconteceu em Iguatama e região. Nenhum envio é publicado automaticamente.</p>
<?php foreach($storyErrors as $error):?><p class="alert error" role="alert"><?=h($error)?></p><?php endforeach;?>
<?php if(!empty($_SESSION['story_success'])): unset($_SESSION['story_success']);?><p class="alert success" role="status">Notícia recebida! Nossa redação vai avaliar sua sugestão.</p><?php endif;?>
<form class="contact-form" action="/index.php?p=enviar-noticia" method="post" enctype="multipart/form-data">
<?=csrf_field()?>
<div class="contact-grid"><label>Seu nome<input name="name" required maxlength="120" autocomplete="name" value="<?=h(is_string($storyInput['name']??null)?$storyInput['name']:'')?>"></label>
<label>E-mail ou telefone<input name="contact" required maxlength="190" value="<?=h(is_string($storyInput['contact']??null)?$storyInput['contact']:'')?>"></label></div>
<label>Título da notícia<input name="title" required minlength="5" maxlength="220" value="<?=h(is_string($storyInput['title']??null)?$storyInput['title']:'')?>"></label>
<label>O que aconteceu?<textarea name="message" required minlength="20" maxlength="5000" rows="7" placeholder="Informe local, data e detalhes que possam ser verificados."><?=h(is_string($storyInput['message']??null)?$storyInput['message']:'')?></textarea></label>
<label>Fotos (opcional)<input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple><small>Até 2 fotos de 512 KiB cada. JPG, PNG ou WebP. Se houver erro, selecione novamente.</small></label>
<label class="contact-consent"><input type="checkbox" name="consent" value="1" required><span>Concordo com o tratamento dos dados para avaliação editorial e contato, conforme a <a href="/index.php?p=privacidade">Política de Privacidade</a>.</span></label>
<div class="contact-trap" aria-hidden="true"><label>Não preencha<input name="website" tabindex="-1" autocomplete="off"></label></div>
<button class="btn primary" type="submit">Enviar para a redação →</button></form></section>
<aside class="info-side"><span class="info-side-icon">✉</span><h3>Envio privado</h3><p>Seu contato e as fotos ficam disponíveis apenas para a equipe responsável pela análise. Envie somente imagens que você tem autorização para compartilhar.</p><p>Não inclua senhas, documentos ou dados sensíveis.</p></aside></div>
