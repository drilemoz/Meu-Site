/* Aplicativos e páginas legais: pop-up de prévia seguro e edição móvel. */
(() => {
  'use strict';
  const announce = text => window.IguaAdminUI?.notify ? window.IguaAdminUI.notify(text) : window.alert(text);
  document.querySelectorAll('[data-legal-copy]').forEach(btn => btn.addEventListener('click', async () => {
    const value=btn.getAttribute('data-legal-copy')||'';
    try {
      if (navigator.clipboard?.writeText) await navigator.clipboard.writeText(value);
      else {
        const field=document.createElement('textarea');field.value=value;field.style.position='fixed';field.style.opacity='0';
        document.body.append(field);field.select();if(!document.execCommand('copy'))throw new Error('clipboard');field.remove();
      }
      announce('Link copiado.');
    } catch { announce('Não foi possível copiar automaticamente. Endereço: '+value); }
  }));
  const editor=document.getElementById('legal-body');
  document.querySelectorAll('[data-legal-insert]').forEach(btn => btn.addEventListener('click', () => {
    if (!editor) return;
    const start=editor.selectionStart,end=editor.selectionEnd;
    const current=editor.value.slice(start,end);const kind=btn.getAttribute('data-legal-insert');
    const replacement=kind==='heading'?'## '+(current||'Subtítulo'):
      kind==='bold'?'**'+(current||'texto')+'**':
      kind==='list'?'- '+(current||'Item da lista'):'['+(current||'nome do link')+'](https://exemplo.com)';
    editor.setRangeText(replacement,start,end,'select');editor.dispatchEvent(new Event('input',{bubbles:true}));editor.focus();
  }));
  const dlg=document.getElementById('legal-preview-dialog');
  const title=document.getElementById('legal-preview-heading');
  const panel=document.getElementById('legal-preview-content');
  // A prévia monta nós com textContent, sem interpretar HTML arbitrário.
  const addInline=(parent,str) => {
    const regex=/(\[[^\[\]\r\n]{1,160}\]\((?:https:\/\/[^\s()<>"']+|mailto:[^\s()<>"']+|\/[^\s()<>"']+)\)|\*\*[^*\r\n]+\*\*|\*[^*\r\n]+\*)/g;
    let last=0,m;
    while((m=regex.exec(str))!==null){
      if(m.index>last)parent.append(document.createTextNode(str.slice(last,m.index)));
      const part=m[0];const link=part.match(/^\[([^\[\]]+)\]\(([^\s()<>"']+)\)$/);
      if(link && ((link[2].startsWith('https://') && /^https:\/\/[^\s/]+/i.test(link[2])) || (link[2].startsWith('mailto:') && link[2].includes('@')) || (link[2].startsWith('/') && !link[2].startsWith('//') && !link[2].includes('%') && !link[2].includes('\\')))){
        const a=document.createElement('a');a.href=link[2];a.rel='noopener noreferrer';a.textContent=link[1];parent.append(a);
      } else if(part.startsWith('**') && part.endsWith('**')){const b=document.createElement('strong');b.textContent=part.slice(2,-2);parent.append(b);}
      else if(part.startsWith('*') && part.endsWith('*')){const i=document.createElement('em');i.textContent=part.slice(1,-1);parent.append(i);}
      else parent.append(document.createTextNode(part));
      last=regex.lastIndex;
    }
    if(last<str.length)parent.append(document.createTextNode(str.slice(last)));
  };
  const render=(source) => {
    const frag=document.createDocumentFragment();let paragraph=[],list=null;
    const flush=()=>{if(paragraph.length){const p=document.createElement('p');addInline(p,paragraph.join(' '));frag.append(p);paragraph=[];}};
    const close=()=>{list=null;};
    for(const raw of source.replace(/\r\n?/g,'\n').split('\n')){
      const line=raw.trim();if(!line){flush();close();continue;}
      const heading=line.match(/^(#{1,3})\s+(.+)$/);
      if(heading){flush();close();const el=document.createElement('h'+(heading[1].length+1));addInline(el,heading[2]);frag.append(el);continue;}
      const bullet=line.match(/^[-*]\s+(.+)$/),number=line.match(/^\d{1,3}\.\s+(.+)$/);
      if(bullet||number){flush();const tag=bullet?'ul':'ol';if(!list||list.tagName.toLowerCase()!==tag){list=document.createElement(tag);frag.append(list);}const li=document.createElement('li');addInline(li,(bullet||number)[1]);list.append(li);continue;}
      close();paragraph.push(line);
    }
    flush();return frag;
  };
  document.querySelector('[data-legal-preview-open]')?.addEventListener('click',e=>{
    if(!dlg||!panel||!title||!editor)return;
    title.textContent=document.querySelector('#legal-page-edit-form [name=title]')?.value || 'Sem título';
    panel.replaceChildren(render(editor.value));
    if(!window.IguaAdminModal?.open?.(dlg,e.currentTarget))dlg.showModal();
  });
  document.querySelectorAll('[data-legal-preview-close]').forEach(btn=>btn.addEventListener('click',()=>{
    if(!dlg)return;if(!window.IguaAdminModal?.close?.(dlg))dlg.close();
  }));
  dlg?.addEventListener('click',e=>{if(e.target===dlg)dlg.close();});
})();
