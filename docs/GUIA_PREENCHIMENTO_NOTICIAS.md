# IguaNews 1.0.12 — Guia simples de cadastro de notícias

Este guia é para o painel **Notícias → Nova notícia**. A tela agora mostra primeiro só o essencial. Os campos de fontes, múltiplas categorias, blocos e SEO manual estão em **opções avançadas**, que só precisam ser abertas quando necessário.

## 1. Básico: sobre o que é a notícia?

- **Manchete:** diga o fato principal, sem exagero. Exemplo fictício: `Colisão entre dois veículos é registrada em via imaginária`.
- **Resumo:** escreva 1–2 frases respondendo o que aconteceu e onde. Não repita a manchete.
- **Categoria:** selecione a principal (Segurança, Cidade, Eventos, Empregos etc.).
- **Situação:** escolha `Rascunho` enquanto verifica os fatos. Somente use `Publicar` depois da checagem.
- **Mais opções:** se precisar, informe uma fonte cadastrada ou URL HTTPS, autor, outras categorias e hora de publicação.

## 2. Texto: comece simples

- No campo **Texto da notícia**, escreva o fato principal, o contexto e uma atualização verificável. Não é necessário preencher blocos para publicar.
- Se quiser facilitar a leitura, abra **Informações rápidas e seções expansíveis**. Adicione um cartão por fato confirmado e detalhes recolhíveis.
- Ao usar blocos, eles **têm prioridade na exibição pública**. O texto simples permanece no banco para compatibilidade, mas não é mostrado no lugar dos blocos.
- Os modelos `Acidente`, `Evento`, `Empregos`, `Política`, `Esporte` e `Obra` criam apenas campos de apoio, nunca fatos automáticos.

### Exemplos práticos de cartões (100% fictícios)

**Acidente:** Local = Via fictícia; Veículos = Carro e caminhonete; Vítimas = Não confirmado. Detalhe expansível = `O que aconteceu?`.

**Evento:** Onde = Praça Exemplo; Data = A confirmar; Entrada = A confirmar. Detalhe expansível = `Programação`.

**Empregos:** Vagas = Quantidade não confirmada; Prazo = A definir. Detalhe expansível = `Como participar?`.

> Estes cenários são demonstrativos, não acontecimentos de Iguatama. Não publique números de feridos, mortes, valores ou datas sem confirmação.

## 3. Foto (opcional)

- Escolha capa JPG, PNG ou WebP (até 4 MB).
- **Legenda:** descreve o que a foto efetivamente mostra, por exemplo: `Imagem ilustrativa do centro urbano; não retrata a ocorrência citada.`
- **Crédito:** fotógrafo, órgão público ou `Imagem ilustrativa gerada por IA`, conforme a origem.
- Abra **Adicionar mais fotos ou documentos** se precisar de galeria ou anexos PDF/TXT/CSV. Não envie material sem permissão.

## 4. Publicar e SEO

- Confira a prévia. A descrição SEO usa o resumo automaticamente quando o campo está vazio.
- O botão **Atualizar resumo para SEO** também pode gerar a descrição a partir do texto informado, mas revise antes de publicar.
- Use **Ajustar SEO manualmente** só quando desejar alterar o título ou a descrição exibidos nas redes/buscadores.
- Se tiver informações pendentes, deixe `Rascunho`. O salvamento automático não equivale à publicação.

## 5. Aprenda com matérias preenchidas

1. Entre no painel com conta Administrador ou Editor autorizado.
2. Abra **Notícias**; na seção **Exemplos do editor simples**, informe sua senha e selecione **Adicionar 3 exemplos preenchidos**.
3. O sistema cria um backup preventivo, insere os modelos de acidente, evento e empregos **sem substituir suas matérias** e identifica todos como `EXEMPLO FICTÍCIO`.
4. Abra uma das notícias em **Editar**, percorra as quatro etapas e veja como os cartões aparecem no portal.
5. Para escrever uma notícia real, prefira **Nova notícia**, sem reutilizar como fato os textos de demonstração. Repetir a importação não duplica os exemplos.

Os exemplos são voluntários, marcados como demonstração e não indexados; a ação exige senha e backup. Não são notícias verificadas e não devem ser redistribuídos como fatos.

## 6. Importar uma URL (opcional)

Em **Nova notícia**, abra **Importar informações de outro site**, cole URL HTTPS pública e clique em **Analisar URL**. O sistema extrai metadados disponíveis (título, resumo, fonte) para revisão, **não copia texto integral nem fotografias**. Leia a notícia original, confira a fonte, produza texto próprio e obtenha autorização para imagens de terceiros.

## Atualizando a versão no Termux

Antes de substituir o código, faça backup em **Configurações → Backups**. Preserve `storage/config.php`, `storage/backups/`, `storage/media/` e `public/uploads/`. A versão 1.0.12 não altera o esquema do MySQL nem o formato v8 dos backups. Reinicie o PHP e abra o painel. O botão **Como preencher** abre uma cópia deste guia em PDF.
