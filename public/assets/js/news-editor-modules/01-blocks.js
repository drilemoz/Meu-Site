(() => {
 'use strict';
 const form=document.querySelector('#news-form'); if(!form)return;
 const tabs=[...document.querySelectorAll('[data-tab]')],steps=[...document.querySelectorAll('[data-step]')];
 const content=document.querySelector('#news-block-list'),serialized=document.querySelector('#news-blocks-json');
 const initial=JSON.parse(document.querySelector('#news-blocks-initial')?.textContent||'[]');
 let blocks=initial.map(x=>({kind:x.kind,heading:x.heading||'',content:x.content||'',collapsed:!!x.collapsed}));
 const csrf=form.querySelector('[name=csrf]').value,articleId=form.querySelector('[name=id]').value;
 const names={fact:'Fato rápido',section:'Seção de detalhes',paragraph:'Parágrafo',heading:'Subtítulo',quote:'Citação',list:'Lista'};
 let active=0;let submitting=false;let lastJson='';let saving=false;
 const esc=x=>String(x).replace(/[&<>"']/g,ch=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch]));
 function syncInputs(){content.querySelectorAll('[data-idx]').forEach(el=>{
   const i=Number(el.dataset.idx);if(!blocks[i])return;
   blocks[i][el.dataset.field]=el.type==='checkbox'?el.checked:el.value;
 });serialized.value=JSON.stringify(blocks.filter(x=>x.heading.trim()||x.content.trim()));}
 function render(){
   content.innerHTML=blocks.map((x,i)=>`<div class="news-block" data-block="${i}"><div class="news-block-head"><strong>${esc(names[x.kind]||x.kind)} ${i+1}</strong><span><button type="button" data-move="up" data-i="${i}" aria-label="Mover para cima">↑</button><button type="button" data-move="down" data-i="${i}" aria-label="Mover para baixo">↓</button><button type="button" data-move="remove" data-i="${i}" aria-label="Excluir bloco">×</button></span></div>${x.kind==='paragraph'?'':`<label>${x.kind==='fact'?'Campo (ex.: Veículos)':x.kind==='section'?'Pergunta / seção':'Título'}<input data-idx="${i}" data-field="heading" maxlength="180" value="${esc(x.heading)}"></label>`}<label>${x.kind==='fact'?'Resposta curta':x.kind==='list'?'Um item por linha':'Conteúdo'}<textarea data-idx="${i}" data-field="content" rows="${x.kind==='fact'?2:4}" maxlength="6500">${esc(x.content)}</textarea></label>${x.kind==='section'?`<label class="news-mini-checkbox"><input type="checkbox" data-idx="${i}" data-field="collapsed" ${x.collapsed?'checked':''}> Recolher inicialmente no portal</label>`:''}</div>`).join('');
   syncInputs();
 }
 content.addEventListener('input',syncInputs);content.addEventListener('change',syncInputs);
 content.addEventListener('click',e=>{let button=e.target.closest('[data-move]');if(!button)return;syncInputs();const i=Number(button.dataset.i),what=button.dataset.move;
   if(what==='remove')blocks.splice(i,1);
   if(what==='up'&&i>0)[blocks[i-1],blocks[i]]=[blocks[i],blocks[i-1]];
   if(what==='down'&&i<blocks.length-1)[blocks[i+1],blocks[i]]=[blocks[i],blocks[i+1]];
   render();
 });
 document.querySelectorAll('[data-add-block]').forEach(b=>b.addEventListener('click',()=>{syncInputs();blocks.push({kind:b.dataset.addBlock,heading:'',content:'',collapsed:b.dataset.addBlock==='section'});render();}));
 const templates={
   acidente:[['fact','Local',''],['fact','Quando',''],['fact','Veículos',''],['fact','Vítimas','A confirmar'],['section','O que aconteceu?',''],['section','Estado das vítimas',''],['section','Causa do acidente','Ainda não confirmada']],
   politica:[['fact','Decisão',''],['fact','Quem decidiu',''],['section','O que muda?',''],['section','Próximos passos','']],
   empregos:[['fact','Quantidade de vagas',''],['fact','Prazo de inscrição',''],['section','Requisitos',''],['section','Como participar','']],
   evento:[['fact','Onde',''],['fact','Quando',''],['fact','Entrada',''],['section','Programação','']],
   esporte:[['fact','Equipes',''],['fact','Placar',''],['section','Destaques do jogo',''],['section','Próxima partida','']],
   obra:[['fact','Local',''],['fact','Prazo',''],['fact','Investimento',''],['section','O que será feito?',''],['section','Impactos aos moradores','']]
 };
 document.querySelector('#news-apply-template')?.addEventListener('click',()=>{syncInputs();let key=document.querySelector('#news-template').value;if(!templates[key])return;blocks.push(...templates[key].map(([kind,heading,content])=>({kind,heading,content,collapsed:kind==='section'})));render();});
