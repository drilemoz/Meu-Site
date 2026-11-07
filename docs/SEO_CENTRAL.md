# IguaNews 1.0.25 | Central de SEO

## Uso no celular e desktop

- No painel administrativo, acesse **Notícias → SEO**. Os cartões exibem as até 40 notícias mais recentes dentro do filtro Todas, Rascunhos ou Publicadas/agendadas.
- Na edição, abra **Básico → SEO e destaque**. O checklist atualiza enquanto o texto é preenchido. As opções continuam recolhidas para não alongar a tela básica; o editor segue Básico → Texto → Foto.
- A nota de 0 a 100 é **indicador editorial local** com sete regras (título 20, descrição 15, slug 15, resumo 15, corpo/blocos 15, capa 10 e legenda/alt da capa 10). Não é indicador da API PageSpeed, Google Search Console ou posicionamento na busca.
- Os limites são heurísticas de clareza, não requisitos absolutos de indexação. Notícias podem ser salvas independentemente da pontuação. Não encha textos com palavras-chave apenas para obter 100.
- A unicidade do slug continua verificada pelo PHP/SQL quando o artigo é salvo, não pelo checklist digitado no cliente.
- O texto alternativo da foto pode ser preenchido na Biblioteca de Mídias. Se não existir, a página pública utiliza a legenda da notícia. Para capa nova, confira a legenda no passo Foto.

## Indexação e canônicas

- **Configurações → Geral e SEO:** definir domínio HTTPS oficial e, apenas quando tudo estiver revisado, permitir indexação. Antes disso, o portal permanece `noindex` por padrão.
- Modo de URLs amigáveis é opcional. No Termux, iniciar da raiz com `php -S 127.0.0.1:8000 -t public public/router.php`; em Apache, habilitar rewrite e servir somente `public/`.
- Histórico de slugs preserva os aliases e responde 301 apenas quando a notícia está publicada; o artigo tem link canônico para o slug atual. Verificar manualmente com `curl -I` em instalação real depois de trocar um slug.
- Notícias de demonstração nunca entram no sitemap. O Google decide se rastreia/indexa; um sitemap e uma nota 100 não garantem visibilidade.

## Verificações

```bash
php tests/seo_125.php
node tests/seo_125.js
php tests/seo_url_124.php
node tests/seo_url_124.js
python tests/seo_http_125.py  # opcional, requer Python no ambiente de desenvolvimento
```

`seo_http_125.py` sobe apenas um servidor local temporário e mede códigos HTTP sem banco. Respostas 303 para setup em ambiente não instalado **não** demonstram funcionamento de 301 após publicar. Testar no MariaDB/Termux, Apache e navegador real permanece obrigatório.

A atualização da 1.0.24 para 1.0.25 não acrescenta tabelas, não altera notícias existentes, imagens, slugs ou formatos de backup. Preserve `storage/config.php`, `storage/backups/`, `storage/media/`, `storage/analytics.key` e `public/uploads/`.

## Central de Sitemap e Indexação (1.0.100)

- Menu **Configurações → Sitemap e Indexação**. O XML público permanece em `/sitemap.php`, quando o domínio HTTPS e a indexação estiverem configurados. A tela administrativa permite inspecionar URLs elegíveis, inclusive quando o XML está desabilitado.
- Links são gerados com a mesma fonte e filtros no painel e XML. O botão “Verificar online” faz GETs para arquivos da mesma origem, somente ao tocar. O status HTTP não substitui a API Search Console nem a inspeção humana.
- `robots.txt` publica o endereço de sitemap do domínio IguatamaOnline. Para hospedar o projeto em outro domínio, revise essa linha. `ads.txt` continua sendo gerenciado na Publicidade, não faz parte dos ZIPs de atualização.
- Consulte [SITEMAP_INDEXACAO.md](SITEMAP_INDEXACAO.md).


## 1.0.109 — títulos de páginas e ícone do site

Em Configurações → SEO e compartilhamento, use **Títulos das páginas** para ajustar o `<title>` da página inicial e das rotas públicas fixas (ônibus, sobre, contato, anuncie, privacidade, termos). Um título preenchido substitui o título gerado por `%title% | %site%` apenas naquela página. Campo vazio mantém o comportamento antigo. Notícias têm título SEO próprio no wizard, categorias usam seu nome e aplicativos usam o editor próprio.

Em **Ícone do site (favicon)**, selecione uma imagem existente da biblioteca ou informe uma URL HTTPS de PNG/JPG/WebP/ICO. O campo é validado antes de salvar e o ícone é anunciado com `rel="icon"` no HTML público. Sem configuração, é usado `/assets/img/favicon.svg`. Prefira imagem quadrada de 48×48 ou superior; o navegador pode manter o ícone em cache.

O pop-up de ajustes técnicos mantém endereço oficial HTTPS, imagem social, indexação e URLs amigáveis. Os valores só são persistidos ao tocar em **Salvar configurações**.
