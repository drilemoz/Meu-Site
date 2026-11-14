<?php
declare(strict_types=1);
require_once __DIR__.'/news_editor.php';
/** Cenários exclusivamente fictícios. Nenhum é um relato real sobre Iguatama. */
function igua_editor_examples(): array { return [
 [
  'category'=>'Segurança','slug'=>'exemplo-editor-acidente-ficticio-112',
  'title'=>'EXEMPLO FICTÍCIO: colisão entre dois veículos em uma via imaginária',
  'summary'=>'Exemplo didático: dois veículos se envolveram em uma colisão simulada. A matéria demonstra fatos rápidos e detalhes recolhíveis.',
  'body'=>'Esta matéria é uma demonstração fictícia do editor do portal. Não descreve um acidente real. A via, os envolvidos e os horários foram inventados para ensinar a preencher os cartões.',
  'image'=>'demo-cidade.webp','image_caption'=>'Imagem ilustrativa gerada para demonstração; não retrata um acidente real.',
  'blocks'=>[
   ['kind'=>'fact','heading'=>'Local','content'=>'Via fictícia (exemplo)'],
   ['kind'=>'fact','heading'=>'Veículos','content'=>'Carro e caminhonete fictícios'],
   ['kind'=>'fact','heading'=>'Vítimas','content'=>'Informação não confirmada'],
   ['kind'=>'section','heading'=>'O que aconteceu?','content'=>'Neste cenário didático, dois veículos se envolvem em uma colisão. Nenhuma ocorrência verdadeira está sendo relatada.','collapsed'=>1],
   ['kind'=>'section','heading'=>'O que ainda precisa ser verificado?','content'=>'Local preciso, circunstâncias, atendimento e confirmação de vítimas. Não invente essas informações numa notícia real.','collapsed'=>1]
  ]
 ],
 [
  'category'=>'Eventos','slug'=>'exemplo-editor-evento-ficticio-112',
  'title'=>'EXEMPLO FICTÍCIO: programação cultural em uma praça imaginária',
  'summary'=>'Exemplo de notícia de evento com local, data, entrada e programação em seções curtas.',
  'body'=>'Texto fictício apenas para teste. Uma programação cultural imaginária foi criada para demonstrar um comunicado simples, seus horários e informações de acesso.',
  'image'=>'demo-eventos.webp','image_caption'=>'Imagem ilustrativa de apresentação cultural, gerada por IA.',
  'blocks'=>[
   ['kind'=>'fact','heading'=>'Local','content'=>'Praça Exemplo (fictícia)'],
   ['kind'=>'fact','heading'=>'Data','content'=>'Data a definir'],
   ['kind'=>'fact','heading'=>'Entrada','content'=>'Informação a confirmar'],
   ['kind'=>'section','heading'=>'Programação','content'=>'Atividades culturais ilustrativas. Preencha aqui a programação confirmada do evento verdadeiro.','collapsed'=>1],
   ['kind'=>'section','heading'=>'Como participar?','content'=>'Informe endereço, horário, acesso e o canal oficial depois de confirmar os dados.','collapsed'=>1]
  ]
 ],
 [
  'category'=>'Empregos','slug'=>'exemplo-editor-empregos-ficticio-112',
  'title'=>'EXEMPLO FICTÍCIO: vagas ilustrativas para demonstrar o editor',
  'summary'=>'Matéria didática com campos curtos para vagas, prazo, requisitos e inscrições. Não representa seleção de emprego aberta.',
  'body'=>'Exemplo fictício para aprendizagem. Nenhuma vaga está sendo anunciada. Antes de publicar oportunidades reais, confirme empresa, quantidade de vagas, salário e prazos.',
  'image'=>'demo-empregos.webp','image_caption'=>'Imagem ilustrativa de ambiente de trabalho, gerada por IA.',
  'blocks'=>[
   ['kind'=>'fact','heading'=>'Vagas','content'=>'Quantidade não definida (exemplo)'],
   ['kind'=>'fact','heading'=>'Inscrições','content'=>'Prazo não definido'],
   ['kind'=>'section','heading'=>'Quem pode se candidatar?','content'=>'Descreva requisitos confirmados, documentos e canal de inscrição oficial.','collapsed'=>1],
   ['kind'=>'section','heading'=>'Onde obter detalhes?','content'=>'Informe o link público oficial ou a fonte responsável pelas vagas.','collapsed'=>1]
  ]
 ]
]; }
/** Importação idempotente e voluntária, não altera matérias próprias e não apaga demos antigas. */
function igua_seed_editor_examples(PDO $db,int $userId): int {
    $samples=igua_editor_examples();$ids=$db->query('SELECT slug,id FROM categories')->fetchAll(PDO::FETCH_KEY_PAIR);
    $check=$db->prepare('SELECT id FROM articles WHERE slug=? LIMIT 1');
    $insert=$db->prepare("INSERT INTO articles (category_id,author_id,title,slug,summary,body,image,image_caption,image_credit,source_name,source_url,seo_title,seo_description,status,featured,is_demo,published_at) VALUES (?,?,?,?,?,?,?,?,?,?,?, ?,?,'published',0,1,NOW())");
    $own=!$db->inTransaction();if($own)$db->beginTransaction();$added=0;
    try {
      foreach($samples as $item){
        if(empty($ids[slug($item['category'])]))throw new RuntimeException('Crie a categoria '.$item['category'].' antes de carregar os exemplos.');
        $check->execute([$item['slug']]);if($check->fetchColumn())continue;
        $insert->execute([(int)$ids[slug($item['category'])],$userId,$item['title'],$item['slug'],$item['summary'],$item['body'],$item['image'],$item['image_caption'],igua_brand_name().' / imagem ilustrativa','Exemplo fictício para aprendizado','',$item['title'],news_seo_description($item['title'],$item['summary'],$item['body'])]);
        $id=(int)$db->lastInsertId();$blocks=normalize_news_blocks(json_encode($item['blocks'],JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE));
        save_article_extras($db,$id,[],$blocks);$added++;
      }
      if($own)$db->commit();return $added;
    }catch(Throwable $e){if($own&&$db->inTransaction())$db->rollBack();throw $e;}
}
