# Métricas locais · comportamento na versão 1.0.140

## As três categorias (não comprovam identidade)

- **Público provável:** pageviews públicos de User-Agents sem indícios conhecidos de automação. Inclui potenciais bots que disfarçam a identidade. Não equivale a pessoas únicas.
- **Robôs declarados:** agentes que se identificam como Googlebot, Bingbot, GPTBot, ChatGPT-User, OAI-SearchBot, ClaudeBot, PerplexityBot, redes sociais etc. O nome é uma **declaração não verificada**; qualquer cliente pode imitá-lo. O portal não realiza DNS reverso ou consultas de IP por requisição.
- **Suspeitos:** sem User-Agent, headless, scripts, curl/wget, scanners e excesso conservador de pageviews por identificador em um minuto. Falsos positivos possíveis. Registros históricos com `Volume suspeito` são exibidos nesse grupo sem modificar a tabela.

O gráfico compara as três categorias para Hoje, 24 horas, 7, 30 ou 90 dias. As abas filtram as tabelas de páginas e origens por tipo de tráfego, sem mudar o gráfico global. Os cliques em links dependem de JavaScript e não comprovam interação humana. Identificadores distintos por dia são hashes HMAC de IP, User-Agent e dia; somá-los ao longo dos dias **não** produz pessoas únicas mensais.

## Horário e armazenamento

A partir de 1.0.140, os **novos eventos** recebem `occurred_at` e `event_day` do mesmo instante no PHP (`America/Sao_Paulo`), independentemente do fuso do MariaDB na VPS. Os limites de requisições por minuto usam o mesmo relógio. Eventos antigos não são corrigidos ou reprocessados: se o MariaDB tinha fuso diferente, gráficos históricos podem apresentar deslocamento.

Não há migração SQL: a versão mantém **schema 37**, `analytics_events` e **backup lógico v12**. A classificação suspeita é inferida de `is_bot` e `bot_name` (até 60 caracteres). As métricas são coletadas apenas em páginas públicas; cache CDN e bloqueadores podem impedir registros. Nenhum IP ou User-Agent completo é armazenado na tabela. HMAC diário em `storage/analytics.key`, arquivo privado e persistente. Retenção de eventos detalhados: 90 dias, com rotina `bin/analytics_prune.php` e limpeza oportunística.

## Privacidade

A coleta respeita GPC, DNT, `igua_analytics_optout` e exclusão de sessões administrativas. A geolocalização opcional usa a base offline MaxMind GeoLite2; a ausência dela é exibida como desconhecido, não é estimada artificialmente. O GA4 permanece independente, condicionado ao consentimento. Revise a política de privacidade conforme LGPD e legislação aplicável.

## Testes

`php tests/analytics.php`, `php tests/analytics_168.php` e `php tests/analytics_audience_240.php` cobrem compatibilidade, classificações, entradas malformadas, horário e consultas SQL. Em uma cópia de teste, use MariaDB e verifique os totais, a navegação no celular, as preferências de privacidade e o impacto de cache externo. **Não** altere o banco de produção para simular tráfego.

---

> **GA4 desde 1.0.120:** nenhum aviso fixo aparece durante a leitura. O visitante encontra a autorização opcional em **Política de Privacidade → Preferências do Google Analytics**, pelo link do rodapé. Sem consentimento, não há coleta pelo GA4. As métricas locais PHP continuam independentes.

> **Estado atual (v1.0.79):** filtros Hoje/24h/7d/30d/90d, gráfico por hora em Hoje/24h; GA4 opcional centralizado em Configurações → Integrações. Ver [INTEGRACOES.md](INTEGRACOES.md). O texto abaixo descreve a implementação original de métricas.

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

## Leitura dos relatórios após 1.0.110

- **Visualizações não identificadas como robôs:** solicitações HTTP que os filtros não classificaram como automação. Não há confirmação de que foram pessoas.
- **Identificadores/dia (estim.):** combinações pseudonimizadas de IP e User-Agent, renovadas a cada dia. Uma pessoa pode gerar vários identificadores; pessoas distintas podem compartilhar um.
- **Automação detectada:** identificadores em User-Agent e padrão conservador de 30 ou mais requisições no intervalo de um minuto do mesmo identificador diário. Podem ocorrer falsos positivos e negativos. O sistema não verifica autenticação do Googlebot/Bingbot.
- **Páginas mais acessadas:** rotas amigáveis conhecidas e páginas legais válidas são diferenciadas, mas eventos antigos permanecem com os caminhos históricos, sem reprocessamento retroativo.
- Logs de servidor da hospedagem e relatórios GA4 autorizados podem ajudar na análise; números GA4 e do PHP não são equivalentes. Nenhum IP ou User-Agent completo é salvo pelo módulo próprio.
