# Métricas IguaNews 1.0.11

## Objetivo e limites

O IguaNews coleta **métricas próprias e first-party** sem Google Analytics, cookies identificadores ou exportar IP a serviços externos. Este módulo não mede GPS ou posição exata. Seu painel usa o fuso do portal (`America/Sao_Paulo`), com filtros de 7, 30 e 90 dias.

- **Visualizações humanas:** registros GET de páginas públicas, exceto navegadores classificados como robôs e acessos de sessão administrativa. Recarregar conta novamente.
- **Visitantes diários estimados:** distintas combinações IP+navegador após HMAC com chave privada e data. **A soma para 30 dias não é pessoas únicas ao longo do mês.** NAT, IP dinâmico e user-agents podem causar desvios.
- **Robôs:** correspondência heurística do `User-Agent`, inclusive Googlebot e outros crawlers. Um agente malicioso pode imitar um robô; classificação não prova identidade. Para verificar crawlers legítimos, usar verificação reversa e listas oficiais do provedor.
- **Cliques:** links HTML `<a href>` rastreados via `navigator.sendBeacon`; não conta formulários, toques em elementos sem link, nem usuários com bloqueadores de scripts.
- **Origem:** `Referer` é reduzido a domínio e agrupado em busca, redes sociais, referência, campanha ou direto. Termos de busca, tokens e URLs externas completas não são armazenados. `utm_source` é aceito somente em formato limitado, sem parâmetros adicionais.
- **Localização:** `GeoLite2-City.mmdb`, **opcional e offline**, associada a uma dependência PHP opcional; classifica país e estado de maneira aproximada. Não produz nomes confiáveis de cidades, não identifica endereço e não funciona com IP local.

## Configuração da geolocalização (opcional)

1. Crie conta e obtenha licença adequada à base GeoLite2 da MaxMind: <https://www.maxmind.com/en/geolite/eula>. Respeite os termos atuais e atualização periódica da base.
2. No diretório do projeto execute `composer require geoip2/geoip2:^3.4` (é opcional, pode exigir `composer` no Termux).
3. Salve o arquivo binário **GeoLite2-City.mmdb** em `storage/geoip/`, fora de `public/`.
4. Confirme a permissão de leitura do PHP. Sem arquivo ou biblioteca o portal continua funcionando, mostrando localização desconhecida.

A MaxMind não garante correspondência exata entre IP e local físico. Se sua hospedagem estiver atrás de proxy/reverse proxy, `REMOTE_ADDR` poderá ser o IP do proxy. **O IguaNews não confia cegamente em `X-Forwarded-For` nem em cabeçalhos de localização fornecidos pelos visitantes.** Configure IP real apenas com proxy de confiança antes de adaptar o coletor.

## Privacidade e retenção

- Dados técnicos IP e User-Agent completos são processados só em memória para calcular identificador HMAC diário; não são armazenados na tabela `analytics_events`.
- O arquivo `storage/analytics.key` é privado e não deve entrar no controle de versões. Preserve nas atualizações; não está incluso no backup ZIP de código.
- Sinais GPC (`Sec-GPC: 1`), DNT (`DNT: 1`) e opt-out do visitante são respeitados. O controle de opt-out usa um cookie de preferência `igua_analytics_optout`, por até 1 ano.
- Eventos detalhados são retidos por até **90 dias**. Em tráfego ativo, a limpeza acontece ocasionalmente ao coletar; para garantir a rotina, agende `php bin/analytics_prune.php` uma vez por dia.
- Métricas podem constituir dados pessoais pseudonimizados sob a LGPD. Antes de colocar o portal online, revise sua base legal, avisos e política com orientação jurídica adequada. Guia ANPD: <https://www.gov.br/anpd/pt-br/centrais-de-conteudo/materiais-educativos-e-publicacoes/guia_orientativo_cookies_e_protecao_de_dados_pessoais>.
- O painel é exclusivo do administrador. Não use dados agregados para identificar leitores.

## Operação, migração e backups

- Atualize do 1.0.10 e entre no painel como administrador: as tabelas são criadas em migração aditiva e idempotente.
- A tela **Métricas** funciona sem serviços externos. Em testes no Termux via `127.0.0.1`, o país aparecerá como desconhecido, pois IP local não fornece geolocalização.
- Backup **v8** inclui `analytics_events`, mantendo importação de backups antigos v1 a v7. Atenção ao crescimento do banco: com muito tráfego, exportar todos os eventos poderá tornar os arquivos de backup grandes. A retenção de 90 dias e rotina de limpeza reduzem o risco.
- O módulo **não recupera** acessos históricos de antes da instalação.

## Testes manuais sugeridos

1. Acesse `/index.php` em janela anônima, depois abra `Métricas` no painel com sua sessão administrativa em outro navegador. Confira a diferença.
2. Abra uma matéria, clique em outra, filtre 7 dias; confira contagem de visualizações e cliques.
3. Abra Política de Privacidade, desative as métricas e recarregue; a nova visualização não deverá ser registrada.
4. Simule user-agent de bot em ambiente isolado e confirme classificação. Não use essa categoria como certeza de autenticidade.
5. Faça backup v8 e restaure numa cópia **de testes** do banco, sem afetar as notícias da instalação principal.
6. Configure GeoLite2 offline e teste com um endereço IP público conhecido (não em 127.0.0.1).
