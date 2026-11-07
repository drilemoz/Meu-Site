> **Versão 1.0.75:** envio manual de atualização em janela central, com confirmação antes do upload e progresso real. A instalação no servidor exibe atividade sem porcentagem inventada. Consulte `docs/ATUALIZADOR_175.md`.

> **Versão 1.0.74:** correção do teste de ZIP com nomes duplicados usado no workflow de integração MariaDB. Para corrigir o CI, envie o código-fonte completo ao repositório; o ZIP de atualização da hospedagem não contém testes. Consulte `tests/fixtures/README.md`.

**1.0.73 — Correção do pacote de código-fonte:** os arquivos Android inseridos por engano em versões anteriores foram excluídos. Os metadados de importação agora identificam este projeto exclusivamente como portal PHP, e o empacotador passou a usar uma lista de inclusão restritiva. O PHP do portal e o banco permanecem funcionalmente iguais à versão 1.0.72. A compatibilidade do GitHub Manager com projetos PHP precisa ser validada no aparelho.

# Meu Site 1.0.75 — Portal PHP/MySQL

**Novidade 1.0.72:** DNS TXT do Search Console detectável e comparável diretamente pelo painel de Integrações, sem afirmar propriedade Google verificada.

**Novidade 1.0.71:** teste de recebimento Google Analytics com consulta autorizada à Data API, sem expor credenciais e sem alterar o rastreamento ou o consentimento.

### Google Analytics Data API (1.0.71)
Em Configurações → Integrações e APIs → Google → Google Analytics 4, salve o ID **numérico da propriedade** (separado do G-). Ative a Analytics Data API no Google Cloud, crie uma conta de serviço, conceda **Leitor** a ela na propriedade GA4 e importe a chave JSON com a senha administrativa. Use **Testar recebimento** para consultar eventos nos últimos 30 minutos e 7 dias. O teste só ocorre sob demanda; números pertencem à propriedade, não identificam qual stream os gerou. O JSON fica privado em `storage/integrations/ga4-service-account.json`, fora de uploads, ZIPs e backups; mantenha backup confidencial da chave fora do repositório. Revogue a chave no Google Cloud se exposta.


**Novidade 1.0.70:** Central de Integrações ampliada com aba **Páginas**, seletor compacto de página inicial/notícias publicadas/categorias, diagnóstico local de URL canônica, indexação, títulos, descrições, imagens, dados estruturados e sitemap. Resultados classificados em OK local/Aviso/Erro/Não testado, com detalhes no `?` e acesso ao Search Console. Não rastreia sites externos nem finge confirmação de indexação. Permissões, consentimento, schema 36 e atualizador v2 preservados. Veja `docs/VERIFICADOR_PAGINAS_170.md`.

**Novidade 1.0.69:** Central de Integrações em Configurações, criada com desenho mobile-first e abas Google/Diagnóstico/Catálogo. A configuração dos IDs GA4 e AdSense passou a usar um único ponto de edição, sem criar valores duplicados: Métricas conserva os controles das estatísticas próprias e Publicidade conserva a operação dos anúncios. Metatag opcional de verificação Search Console, com entrada de valor validada e publicação no HTML público. Diagnóstico local, somente leitura, para URL HTTPS, sitemap, indexação, IDs e ads.txt; não certifica aprovação do Google nem faz testes remotos. Google Ads, Gemini, GTM, PageSpeed e outros estão no catálogo como **planejados**, sem chaves ou conexões ativas. Schema 36, consentimento GA4, backups e atualizador ZIP v2 sem assinatura preservados. Consulte `docs/INTEGRACOES_169.md`.

# Meu Site 1.0.68 — Portal PHP/MySQL

**Novidade 1.0.68:** audiência com filtros Hoje/24h/7d/30d/90d, gráfico por hora nas duas janelas curtas, e campo de Google Analytics 4 em Métricas → Configurações e integrações. O GA4 fica desativado por padrão e só carrega após consentimento do visitante. As métricas locais são independentes. Banco 36 e atualizador sem assinatura preservados. Use `Atualizacao-v1.0.68.zip` (simulação e backup antes de instalar). Consulte `docs/METRICAS_GA4_168.md`.

# Meu Site 1.0.65 — Portal PHP/MySQL

**Novidade 1.0.65:** primeira etapa da auditoria visual: imagens ausentes ou quebradas com fallback, tabelas de backups móveis, fontes compactas, modais de anúncios com cabeçalho fixo, Central de Segurança com ajuda contextual e rodapé público mais enxuto. Instalação pelo painel com `Atualizacao-v1.0.65.zip` (formato v2 sem assinatura), após simulação e backup. Sem mudanças no banco.

# Meu Site 1.0.64 — Portal PHP/MySQL

**Novidade 1.0.64:** Central de Segurança com registros privados de eventos, alertas por gravidade, auditoria administrativa e bloqueios manuais de IP para acesso ao painel. `docs/SEGURANCA_164.md` explica limitações e testes. Schema MySQL 36 permanece.


O menu do painel ocupa no máximo 248 px no celular (antes 310 px), preservando os grupos recolhíveis, a rolagem, os links e o botão de sair. O CSS consolidado foi recompilado e usa a versão para invalidar o cache. Não há alterações no banco, no sistema de mídias nem nas regras do atualizador. Para instalar sobre a v1.0.62, use `Atualizacao-v1.0.63.zip` em Configurações → Atualizações; o ZIP usa o formato v2 sem assinatura e exige a senha do administrador. Veja `docs/UI_163.md`.

## Meu Site 1.0.62 — Atualizações sem chave de assinatura

O painel aceita pacotes de atualização v2 sem `release.sig`, exigindo senha do administrador e conferência SHA-256 de todos os arquivos. **SHA-256 não autentica a origem do pacote**: instale somente releases cujo responsável você verificou. Pacotes assinados legados v1 continuam com assinatura obrigatória. Para migrar da versão antiga, faça a primeira instalação pela hospedagem, preservando `storage/`, `public/uploads/`, `public/ads.txt` e banco de dados. Manual: `docs/ATUALIZADOR_162.md`.

## Meu Site 1.0.61 — rodapé enxuto e biblioteca integrada

Rodapé público sem bloco de Projetos, folga móvel controlada e CSS público com cache-busting. O menu administrativo ficou mais estreito e a rota Mídias agora é parte da página do painel, mantendo o cabeçalho e o rodapé; a seleção de fotos nos editores continua em modal. **Sem migração de banco.** Consulte `docs/UI_161.md`.

## Meu Site 1.0.60 — painel administrativo compacto

Menu único à direita com categorias recolhíveis; barra secundária removida. Notícias têm lupa para filtros e quatro ações alinhadas no celular. Usuários e eleições abrem formulários quando solicitados; mídias continuam em tela cheia com upload recolhido. Confirmações administrativas usam janelas próprias, com interceptação antes do envio e sem transformar Escape em confirmação. O atualizador mostra mensagem específica para versão repetida ou anterior. **Sem migração MySQL (revisão 36).** Veja `docs/ADMIN_UI_160.md` e execute os testes antes do deploy.

## Meu Site 1.0.59 — Publicidade compacta e limpeza de demonstrações

A central **Publicidade** foi reorganizada para uso em telas pequenas: **+ Nova campanha** abre opções para anúncio local ou bloco Google AdSense, as seis posições abrem em janelas individuais e a engrenagem centraliza a ativação geral, o ID `ca-pub`, o ads.txt, a vistoria AdSense e os dados de teste. As campanhas são listadas em linhas expansíveis, sem tabela horizontal.

O servidor verifica conflitos entre campanhas locais ativas no mesmo período/posição. Quando há conflito, o administrador escolhe explicitamente o revezamento (prioridade máxima; empate alternado por dia) ou altera data/posição. A posição AdSense não pode ser ativada sobre campanhas locais ativas, e uma campanha local não substitui um bloco AdSense ativo sem ação explícita. Novas posições podem ser preparadas para publicidade local mediante confirmação; a chave geral não é ativada automaticamente.

**Remover anúncios de demonstração** está disponível em **Publicidade → ⚙ → Testes** e em **Configurações → Manutenção**, com confirmação textual e backup lógico antes de excluir. A exclusão usa exclusivamente o prefixo de URL dos anúncios fictícios, conserva anúncios reais, mídia, categorias e notícias e desabilita apenas posições locais que ficarem sem qualquer campanha. Limpar notícias de exemplo é uma ação independente.

Ações de remoção não ocorrem na atualização. O esquema SQL permanece na revisão **36**. Consulte `docs/PUBLICIDADE_159.md`, rode `php tests/ads_admin_159.php` e as regressões. Teste instalação e operações reais em MariaDB/BoaHost antes de produção.

## Meu Site 1.0.58 — Conteúdo demonstrativo e manutenção

A administração permite limpar os testes sem excluir categorias e fontes. A nova instalação é limpa por padrão; o aviso de acervo aparece apenas na página da matéria demonstrativa. Veja `docs/CONTEUDO_ALPHA_158.md`.

## Preparação para Google AdSense (v1.0.57)
Acesse Painel → Publicidade → Preparação para Google AdSense. Configure um ID real `ca-pub-XXXXXXXXXXXXXXXX` e salve. O portal exibirá a metatag `google-adsense-account` nas páginas públicas **sem ativar anúncios**. Se a conta solicitar ads.txt, use o botão para publicá-lo: `https://SEU-DOMINIO/ads.txt`. O editor nunca poderá substituir automaticamente linhas de outro ID Google, e futuras atualizações do código preservam o arquivo.

Antes de solicitar revisão no AdSense, publique notícias reais/originais, atribua autoria e fontes, verifique imagens/licenças, configure URL base HTTPS e indexação em Geral e SEO, reveja as páginas Sobre/Contato/Privacidade/Termos, retire conteúdos demonstrativos, valide HTTPS e navegação móvel. A vistoria interna é parcial e **não equivale à aprovação do Google**. Para regiões abrangidas, use CMP certificada antes de ativar serviços publicitários; a preferência de anúncios do portal não substitui todas as obrigações legais.

# Meu Site 1.0.63 — Portal PHP/MySQL

> **Histórico 1.0.56:** Fonte oficial e vistoria em janelas compactas, repositório `drilemoz/Meu-Site` configurado por metadados do portal e redução do recuo excessivo do rodapé no celular. Consulte `docs/ATUALIZADOR_156.md`.

> **Novidade 1.0.55:** histórico de atualizações, integridade do código instalado e comparação de arquivos antes de aplicar um ZIP assinado. Consulte `docs/ATUALIZADOR_155.md`.

> **Novidade 1.0.54:** atualizador compacto no celular, ajuda `?`, formulários avançados recolhidos, confirmação sem senha repetida e detecção de repositório quando houver metadados oficiais. Veja `docs/ATUALIZADOR_154.md`.

> **Novidade 1.0.53:** vistoria pré-atualização do ambiente e simulação segura de pacotes assinados, com diagnóstico de permissões, extensões, MySQL e limitações da hospedagem. Veja `docs/ATUALIZADOR_153.md`.

> **Novidade 1.0.52:** atualizador próprio com verificação de Releases do GitHub, pacote ZIP assinado Ed25519, backup antes da alteração e restauração de código. Modo manual autenticado (sem instalação em segundo plano). Veja `docs/ATUALIZADOR_152.md`.

> **Novidade 1.0.51:** pré-instalação com escrita/renomeação segura de `storage/config.php`, inventário completo das 25 tabelas e recuperação de instalações interrompidas com autenticação do administrador existente. Veja `docs/INSTALACAO_PORTATIL_151.md`.

> **Novidade 1.0.50:** instalação híbrida com envio automático e segmentado de backups até 15 MiB, inclusive no PHP 2M/8M quando uploads pequenos são aceitos. Veja `docs/INSTALACAO_DUAL_150.md`.

> **Novidade 1.0.49:** importação de backups de até 15 MiB com configuração PHP-FPM (`public/.user.ini`), script Termux atualizado, verificação antecipada do tamanho do arquivo e erros específicos. Em hospedagens onde não é permitido alterar o PHP, ajuste pelo painel/provedor. Veja `docs/INSTALACAO_DUAL_149.md`.

> **Novidade 1.0.48:** pré-instalação com verificação da cópia pública para Termux e hospedagem, diagnóstico correto do limite de uploads e ferramenta CLI `php bin/check-environment.php [--json]`. Consulte `docs/INSTALACAO_DUAL_148.md`.

# Meu Site 1.0.51 — Instalação recuperável em Termux, Plesk e cPanel

O mesmo código funciona em **Termux com MariaDB** e em **hospedagem PHP com MySQL/MariaDB**, desde que o servidor tenha PHP 8.1+, PDO MySQL, armazenamento persistente e uma raiz web isolada. O instalador identifica o ambiente PHP, adapta as orientações e bloqueia configurações que exponham pastas privadas. A instalação em hospedagem não cria usuários SQL automaticamente. Consulte **[docs/INSTALACAO_DUAL_147.md](docs/INSTALACAO_DUAL_147.md)** antes de publicar.

- Termux: `sh bin/start-termux.sh` na pasta do projeto, após iniciar MariaDB. Acesso local `http://127.0.0.1:8000/setup.php`.
- Hospedagem com DocumentRoot configurável: apontar o domínio diretamente para `Meu Site/public/`.
- Hospedagem com `public_html` fixo: copiar **apenas os arquivos contidos em** `public/` para `public_html/`, deixando `app/`, `storage/`, `bin/` e demais arquivos privados como irmãos de `public_html/`, fora do alcance HTTP.
- Antes de atualizar, preserve `storage/config.php`, backups, mídias, banco SQL e fotos. Mudanças de servidor exigem backup/restauração e revisão manual do domínio HTTPS/SMTP.
- Verificações: `php tests/runtime_147.php` e `php tests/smoke.php`. HTTPS, permissões SQL e suporte de extensões dependem do provedor; o site não pode habilitá-los por conta própria.

---

# Meu Site 1.0.46 — Índices públicos e diagnóstico EXPLAIN

## Novidades da 1.0.46

- **Migração SQL revisão 36:** dois índices adicionais em `articles` para ordenação dos destaques e notícias mais lidas. Criação idempotente, mantendo os índices atuais.
- **Pesquisa literal consistente:** o painel administrativo agora trata `%` e `_` como texto literal, assim como o acervo público. A pesquisa por trechos continua com `LIKE` e não foi trocada por FULLTEXT.
- **Diagnóstico de planos:** `php tools/news_explain.php` apresenta as estimativas de `EXPLAIN` para cinco padrões de consultas. Não grava dados nem expõe conteúdo das notícias.
- **Testes:** `php tests/news_indexes_146.php` e `tests/integration/news_indexes_mariadb_146.php` (banco isolado/CI). Ver [docs/VALIDACAO_146.md](docs/VALIDACAO_146.md).

**Atualização de banco:** exige permissões de `ALTER TABLE` e espaço livre para criar índices; faça backup e planeje a manutenção. O ganho real depende do `EXPLAIN` e de medições no MariaDB. Backup lógico v11 e pacote de mídias ZIP v2 permanecem inalterados.

## Histórico — 1.0.45

# Meu Site 1.0.45 — Consultas SQL e paginação

## Novidades da 1.0.45

- **Listas públicas leves:** cartões recentes, destaques e mais lidas carregam somente título, resumo, slug, capa e demais colunas usadas. O campo `articles.body` (`MEDIUMTEXT`) não é mais transferido nessas consultas, mas continua disponível na notícia completa.
- **Busca/paginação:** quando os filtros não encontram notícias, dispensa a segunda consulta de página. Quantidades continuam em 12 notícias/página no portal e 20 no painel; contagem exata, filtros, páginas e links anteriores preservados.
- **Painel:** parâmetros GET malformados de página ou pesquisa não provocam conversões de arrays em texto/número. Lista vazia dispensa consulta de linhas.
- **Consulta de demonstração:** verifica a existência de ao menos uma matéria de exemplo usando `LIMIT 1`, sem contar todas as correspondências; os avisos e restrições publicitárias continuam ativos quando há exemplos publicados.
- **Ordenação:** desempate explícito pelo ID no destaque e ranking de mais lidas, evitando alternância de itens com mesmo valor.
- **Regressão:** `php tests/news_queries_145.php`; documentação em `docs/VALIDACAO_145.md`. Os testes de composição da v1.0.44 tiveram o hash atualizado apenas para refletir alterações intencionais de SQL/paginação nos dois entrypoints.

**Não há migração SQL:** revisão 35, backup lógico JSON v11 e pacote de mídias ZIP v2. A velocidade real depende do tamanho do acervo, índices, disco e plano de consulta do MariaDB; não houve benchmark com banco real nesta entrega.

## Histórico — 1.0.44: arquitetura de views PHP

## Novidades da 1.0.44

- **Entradas leves:** `public/admin.php` e `public/index.php` mantêm as rotas HTTP e verificações essenciais. HTML e telas estão agora em `app/admin_views/` e `app/public_views/`, fora da pasta pública.
- **17 views de rota privadas:** nove rotas administrativas (painel, notícias, editor, categorias, autores, fontes, usuários, configurações e backups) e oito públicas (ônibus, contato, privacidade, termos, início, notícia, categoria e busca), além de layouts, login e saúde.
- **URLs e operações preservadas:** links `/admin.php?action=...` e `/index.php?p=...`, permissões, CSRF, formulários, layout responsivo, rastreamento de visitas e processamento POST funcionam pelos mesmos pontos de entrada.
- **Regressão de composição:** `php tests/refactor_144.php` verifica que os módulos reconstituem exatamente o código-fonte anterior da v1.0.43. Testes isolados de saída HTML também compararam 5 rotas sem mudanças.
- **Sem migração:** esquema 35, backup JSON v11 e pacote de mídias ZIP v2 permanecem. Validações: `docs/VALIDACAO_144.md`. Teste HTTP real em MariaDB/Android ainda é necessário.

## Histórico — 1.0.43: backups com menor consumo de memória

- **Exportação lógica por fluxo:** o JSON v11 é gravado incrementalmente em arquivo privado, tabela por tabela, sem manter todos os registros em arrays PHP. Mantidos o limite de 40 MiB descompactados, 100.000 registros/tabela, compressão GZIP quando disponível, leitura REPEATABLE READ e a publicação somente após conclusão.
- **Pacote completo ZIP:** criado primeiro como arquivo oculto temporário e só publicado após fechar/verificar o limite de 350 MiB. No download pelo painel, o ZIP gerado nesta requisição é apagado depois de transmitido; o backup lógico correspondente continua guardado.
- **Limpeza segura:** `rotate_backups()` remove temporários `.backup-XXXXXX` e `.bundle-XXXXXX` com mais de 24h, quando reconhecidos; não apaga backups manuais, pacotes completos antigos ou arquivos externos. Os limites de retenção de 7 diários e 4 semanais continuam.
- **Compatibilidade:** nenhum SQL novo, revisão 35, backups v1–v11 na importação e pacote de mídias v2. Veja `docs/VALIDACAO_143.md` e `docs/BACKUP.md`.

## Histórico — 1.0.42: Segurança e integridade

- **Login:** depois de cinco falhas da mesma origem IP, o servidor bloqueia novas tentativas dessa origem por 15 minutos, inclusive com outra sessão ou janela anônima. Estado fica somente em `storage/security/login/`, fora de `public/`, sem IP nem credencial em texto. Usa `REMOTE_ADDR` do servidor, não cabeçalhos encaminhados arbitrários; proxy reverso requer avaliação específica. Se a pasta privada não permitir gravação, o login falha de modo seguro. Origem compartilhada por muitos usuários pode ser bloqueada em conjunto; redes com IPs distintos não compartilham o limite.
- **Configurações:** valida todos os campos antes de salvar e grava tudo em transação InnoDB. Erros não deixam configurações parcialmente atualizadas.
- **Notícias e mídia:** capa nova é recebida dentro da transação da notícia; caminho de capas, imagens e anexos novos é rastreado, para excluir esses arquivos após rollback SQL confirmado. O upload também remove arquivos parciais quando a conversão ou o registro falhar. Em resultado incerto de COMMIT (ex.: perda de conexão), preserva arquivos em vez de apagar imagens possivelmente referenciadas pelo banco.
- **CI:** mudanças em `public/**` e `tools/**` também disparam a integração MariaDB; sintaxe JavaScript é testada no arquivo consolidado, pois os oito trechos do editor não formam scripts autônomos. Incluído `tests/refactor_142.php` e atualizado o teste antigo de marca para acompanhar o serviço de configurações.

**Sem migração SQL:** revisão do esquema **35**, backup lógico **v11**, pacote ZIP de mídias **v2**, autenticação persistente e módulo eleitoral preservados. Nenhum arquivo de produção é excluído na atualização; mantenha o backup anterior. Testes em MariaDB real e envio HTTP devem ser executados no ambiente final. Detalhes: `docs/VALIDACAO_142.md`.

## Histórico — 1.0.41: Resultados Eleitorais (fase 1)

## Autenticação administrativa — recursos preservados da 1.0.40

Acesso ao painel com controle **Mostrar/Ocultar senha** e opção desmarcada por padrão **Manter conectado por até 7 dias**. O navegador continua responsável por oferecer salvar senhas com seu gerenciador; o servidor nunca armazena a senha em cookies ou no `localStorage`.

O login persistente armazena apenas o hash SHA-256 de um segredo aleatório em `admin_remember_tokens`, vinculado a usuário ativo e à versão de sua credencial. Ele é revogado ao sair, trocar senha ou desativar o usuário. A persistência funciona com HTTPS ou acesso local em 127.0.0.1. Não habilitar em HTTP público.

**Recurso introduzido na 1.0.40:** tabela de tokens na revisão SQL **34**. Na versão atual, a revisão é **35** e o backup lógico é **v11**; os tokens de sessão continuam excluídos, por segurança. Para instalações existentes, atualize os arquivos e entre normalmente; a migração executa no login se o usuário SQL permitir CREATE TABLE. Antes de atualizar, salve cópia do banco e das mídias. Consulte `docs/AUTENTICACAO_140.md`.

## Resultados Eleitorais — 1.0.41 (fase administrativa)

Acesse **Administração → Resultados Eleitorais**, habilite o módulo e cadastre pleitos e candidaturas. Somente administradores possuem permissão. Cadastros permanecem como rascunho privado, sem votos, apuração, gráficos ou resultados públicos. **Não há sincronização nem dados oficiais do TSE nesta versão.**

Migração SQL **35** com `elections` e `election_candidates`, sem alterações destrutivas. Backup lógico **v11** exporta ambas; backups v1–v10 ainda podem ser restaurados. Leia `docs/ELEICOES_141.md`.

## Histórico: 1.0.39 — novo nome e correção da primeira instalação

O **projeto e o pacote** agora se chamam **Meu Site**. A marca pública pode ser **Tribuna**, **Viajando por Aí** etc., escolhida no assistente e posteriormente em Configurações. O padrão para uma instalação nova é **Meu Site**. Nomes já salvos no banco são mantidos.

O pacote inclui `storage/backups/.gitkeep`, `storage/media/.gitkeep` e `storage/logs/.gitkeep`. O instalador cria pastas ausentes sob o diretório privado do projeto quando o PHP tem permissão. Ele não segue symlinks nessas pastas nem altera permissões de pastas existentes.

**Termux:** a partir da raiz do projeto, inicie com upload maior usando `php -d upload_max_filesize=20M -d post_max_size=24M -S 127.0.0.1:8000 -t public public/router.php`. Reinicie o servidor após alterar esses parâmetros. O aviso de 2 MB é configuração do PHP, não um bug do upload. Veja `docs/INSTALACAO_TERMUX_139.md`.

**Compatibilidade:** os identificadores técnicos antigos `iguanews-backup`, `iguanews-media-bundle`, `IGUANEWS_DB_*`, `IGUANEWS_SMTP_*` e o cookie de sessão permanecem estáveis para evitar quebrar backups e configurações já existentes. Não renomeie o banco existente apenas para mudar o nome do portal.


## 1.0.38 — Módulo de ônibus e revisão de migrações

`app/bus_admin.php` mantém as funções públicas `bus_admin_post()` e `bus_admin_view()`, mas delega para cinco módulos em `app/bus_admin/`: `post_import.php`, `post_routes.php`, `post_departures.php`, `view_import.php` e `view_routes.php`. O HTML das telas de linhas e de importação não foi alterado nos cenários verificados. Todas as dez ações POST preservam autorização centralizada.

`app/schema_migrations.php` rejeita marcadores de revisão inválidos e impede que código antigo rebaixe a revisão de um banco mais novo. O número da revisão SQL continua **33**, pois esta versão não altera tabelas. Backups v10 e pacotes de mídia v2 preservados. Consulte `docs/VALIDACAO_138.md`.

## 1.0.37 — Manutenção do editor PHP

`app/news_admin_view.php` agora é uma fachada que mantém a função pública `news_admin_editor()` e carrega oito parciais privados em `app/news_admin_view/`: `prepare.php`, `header.php`, `details.php`, `blocks.php`, `media.php`, `footer.php`, `revisions.php` e `scripts.php`. Separadas a preparação dos dados, as três etapas, o histórico e os scripts, sem alterar o HTML servido. Testes com PDO simulado conferem o SHA-256 da renderização de notícia nova e existente contra a 1.0.36 e a composição exata das fontes. Não requer migração, mantém backup lógico v10/mídias v2 e esquema revisão 33. MariaDB real e testes visuais continuam pendentes.

Nova instalação: escolha **nome**, **frase**, **descrição de busca** e **região** na terceira etapa, com pré-visualização. O administrador pode alterar posteriormente o nome em **Configurações → Identidade do portal**. A marca é aplicada ao cabeçalho, títulos SEO, Open Graph, editorias, painel, transporte, páginas informativas e rodapé. Instalações antigas continuam com IguaNews até mudança manual.

Rodapé da versão aparece nas páginas públicas, painel, login e instalador. **Desenvolvido por Adriedson Lemos** abre um diálogo acessível com e-mail e chamada telefônica. Estes contatos são constantes do código-fonte e não possuem campos de edição na administração; administradores do servidor que controlam os arquivos PHP podem alterá-los (não é tecnicamente impossível). O e-mail público de atendimento editorial continua independente.

Sem mudança de esquema, sem mudança dos backups lógico v10 e de mídias v2. A versão é lida do arquivo `VERSION`. Veja `docs/VALIDACAO_136.md`.

# Meu Site · Portal personalizável

## Editor JavaScript modularizado (1.0.35)

O editor em três etapas continua igual no navegador. Os arquivos de origem agora estão em `public/assets/js/news-editor-modules/`; para modificá-los, executar `python3 tools/build_news_editor.py` e validar com `python3 tools/build_news_editor.py --check`. O JS gerado mantém os bytes e o hash da 1.0.34. Consulte `docs/EDITOR_JS_MODULOS_135.md`.

## Integração MariaDB em ambiente isolado (1.0.34)

Inclui um **executor de testes de integração**, ainda não executado com MariaDB real nesta sessão. Ele cobre exportação, rollback e restauração de bancos e mídias e possui workflow de GitHub Actions. Consulte `docs/INTEGRACAO_MARIADB_134.md`. O teste exige MariaDB e extensões PHP `pdo_mysql`/`zip`; nunca deve rodar em produção.

**Versão 1.0.45** · Projeto PHP 8.1+ + MySQL/MariaDB, criado para o portal de notícias locais de Iguatama e região.

## 1.0.30 — Controladores administrativos (30/10/2026)

- A rota pública `/admin.php` continua idêntica, mas o código de ações foi organizado em `app/admin_controllers/` por domínio (notícias, editorias, configurações, usuários, publicidade, ônibus, backups, mídias e downloads).
- Registro de ações POST explícito e controlado em `post_routes.php`; autenticação, permissões e CSRF centralizados na entrada.
- As telas e formulários continuam como eram; os dados e formatos de backups **v10/v2** não foram alterados.
- Validação em `docs/VALIDACAO_130.md`. Antes de atualizar o Termux, salve seus dados e não substitua `storage/config.php`, `storage/backups/`, `storage/media/`, `storage/analytics.key` nem `public/uploads/`.

## 1.0.29 — Refatoração da Biblioteca de Mídias (30/10/2026)

- Corrigida a inicialização das referências de pesquisa, nome de arquivo e upload do gerenciador que poderiam interromper o JavaScript na 1.0.28.
- Novo módulo independente `public/assets/js/media-state.js` para gerenciar requisições de listagem e fila de envio; carregado antes de `media-library.js` com scripts `defer`.
- Listagens obsoletas são canceladas quando possível e, de qualquer maneira, não substituem a pasta, pesquisa, paginação ou lixeira atual; fechar o modal também invalida requisições.
- O envio automático de até 10 fotos permanece. Quando parte do lote foi confirmada e outra falhou, só as fotos **não confirmadas** permanecem na fila para reenviar; o botão manual permite a tentativa novamente.
- Mensagem informa a limitação de rede ambígua: se o servidor gravou uma foto mas a resposta se perdeu, é necessário conferir a biblioteca antes de reenviar.
- Removido aprimoramento duplicado do seletor de arquivos já personalizado, mantendo acesso no celular.
- **Sem alteração do banco, das rotas da API ou dos formatos de backup**: lógico v10 e mídias v2. Editor continua Básico → Texto → Foto.
- Testes específicos: `node tests/media_state_129.js`, `php tests/media_ui_128.php`. Veja `docs/VALIDACAO_129.md`.

## 1.0.26 — Pesquisa, filtros e paginação pública (21/10/2026)

- **Notícias → Ver todas** e as páginas de **editorias** deixam de cortar a lista nos 30/24 primeiros resultados. O acervo público mostra 12 notícias por página, com paginação anterior, próxima e páginas numeradas compactas no celular.
- Pesquisa por título e resumo, filtro por editoria (incluindo editorias secundárias) e data de publicação nos últimos 7/30/90 dias; mantidos filtros, texto e página ao navegar. O painel administrativo continua com seus próprios 20 resultados por página, sem mudanças.
- `COUNT` e leitura paginada usam as mesmas condições e consultas preparadas; entradas inválidas são normalizadas e os caracteres `%`, `_` e `!` da busca são tratados literalmente para evitar resultados inesperados.
- O modo opcional de URL amigável preserva página e período no redirecionamento 301 das editorias e nas reescritas Apache (`QSA`); páginas paginadas de editoria recebem URL canônica própria. Páginas de busca/filtros temporais não são indexáveis quando a indexação geral estiver ligada.
- Nenhuma migração SQL, alteração de notícias ou mudança de backup. Permanecem backup lógico **v10**, pacote de mídia **v2**, o editor **Básico → Texto → Foto** e as URLs antigas. Veja `docs/VALIDACAO_126.md`.

## 1.0.25 — Central de SEO e auditoria de URLs (20/10/2026)

- O menu **Notícias → SEO** exibe o diagnóstico editorial das últimas 40 notícias do filtro; a pontuação de 0 a 100 é interna e **não representa score do Google nem garante indexação**. Redatores veem apenas suas notícias.
- Em **Nova/Editar → Básico → SEO e destaque**, o checklist acompanha as alterações da notícia sem impedir o salvamento. Título, resumo, descrição SEO, slug, texto/blocos, capa e texto alternativo são verificados.
- O `alt` da capa pública utiliza o texto alternativo cadastrado na Biblioteca de Mídias, com fallback para a legenda; não inventa descrição para fotos reais.
- Testes de URL cobrem compatibilidade de links legados, formato limpo, slugs malformados, domínio HTTPS, reescrita do roteador PHP, proteção a caminhos codificados e existência de redirecionamento 301 no código. **301 real com publicação requer MariaDB e navegador/hospedagem**.
- **Sem nova migração SQL**; backup lógico **v10** e pacote de mídias **v2** permanecem. Veja `docs/SEO_CENTRAL_125.md` e `docs/VALIDACAO_125.md`.

## 1.0.24 — Endereços de notícias e SEO (20/10/2026)

- **Básico → SEO e destaque:** campo para nome da URL (slug), botão de gerar da manchete e prévia do link, com normalização de acentos e verificação de nomes ocupados (inclusive históricos). A manchete pode mudar sem forçar mudança do endereço.
- Ao renomear uma notícia publicada, o nome anterior é guardado em `article_slug_redirects` e redireciona via **HTTP 301** para a URL nova, sem destruir outros registros. Rascunhos e agendadas não são expostos. Slugs legados extensos são preservados.
- Em **Configurações → Geral e SEO**, a opção **URLs amigáveis** habilita links `/noticia/nome` e `/categoria/nome`, incluindo sitemap e canonical. **Desativada por padrão** até configurar o roteamento. Endereços com querystring continuam aceitos e redirecionam quando ativada.
- No Termux, inicie opcionalmente: `php -S 127.0.0.1:8000 -t public public/router.php` (na raiz do projeto). Em Apache, o `.htaccess` possui `mod_rewrite`. Na hospedagem, confira `AllowOverride` e testes de rota antes de ativar.
- Migração idempotente que **acrescenta** tabela de histórico; backup lógico agora **v10**, compatível com restauração v1–v9 (backups v10 exigem aplicação atualizada). O ZIP de mídias permanece v2. Leia `docs/SEO_URLS_124.md` antes de migrar.
- Mantidos editor de três etapas, mídias, notícias, papéis de usuário e fluxos existentes. Teste PHP `tests/seo_url_124.php` e JS `tests/seo_url_124.js`; testes em MariaDB/Android pendentes.

## 1.0.23 — Recuperação confiável do rascunho automático (20/10/2026)

- Corrigida a recuperação de categorias adicionais, destaques e outros controles de marcação: campos desmarcados no rascunho não reaparecem marcados.
- Valores repetidos são restaurados na posição correta, sem transformar listas em um único texto separado por vírgulas.
- A recuperação não troca o ID da notícia nem o token CSRF e não tenta restaurar fotos/documentos locais ainda não enviados. O formulário avisa sobre essa limitação.
- O formato existente do autosave e o banco não foram alterados. O fluxo continua **Básico → Texto → Foto**, backup lógico **v9** e pacote de mídias **v2**.
- Testes adicionais: `php tests/editor_123.php` e `node tests/editor_draft_123.js`. Teste real com o Chrome do Android e MariaDB permanece pendente.

## 1.0.22 — Legendas de mídias e uploads confiáveis (20/10/2026)

- Na etapa **Foto** do editor, ao selecionar documentos depois de fotografias (ou vice-versa), as legendas e créditos já preenchidos no outro grupo não desaparecem. Após otimização de imagem assíncrona, os campos digitados também são preservados.
- Ao substituir uma seleção de arquivos, os metadados do grupo substituído são reiniciados para não atribuir crédito/legenda de uma foto anterior a outra.
- Se uma foto grande estiver sendo otimizada e o usuário selecionar outra antes do término, o resultado antigo não substitui a seleção mais recente. Tarefas de seleções descartadas também não bloqueiam o salvamento.
- Erros de otimização ficam associados ao respectivo campo: escolher outros arquivos não oculta um erro pendente da capa ou da galeria.
- Esses dados só são efetivamente publicados ao usar **Salvar notícia**; o salvamento automático de rascunho não transfere arquivos binários. **Sem alteração de banco ou de backup**: lógico v9, pacote de mídias v2 e fluxo Básico → Texto → Foto inalterados.
- Testes comportamentais simulados com Node.js: `node tests/editor_media_122.js`. Ainda é necessário testar no Chrome/Android com fotos e documentos reais.

## Restauração de pacote completo mais confiável (1.0.21)

Corrigida a divergência entre a exportação (até 300 MiB de mídias) e o limite do ZIP aceito na importação (agora até 350 MiB, incluindo banco e manifesto). A restauração verifica arquivos e SHA-256 em fluxo de 64 KiB, preparando arquivos temporários antes de trocar registros no banco; mídias já presentes com o mesmo hash são preservadas, e conflitos são recusados. Em caso de falha na transação do banco, a aplicação tenta retirar somente as mídias novas adicionadas nesta execução. Isso reduz o risco de perda parcial, mas **não substitui teste de recuperação com MariaDB e cópia externa das fotos**.

A exportação bloqueia mídias fora das pastas permitidas. O formato lógico continua v9, o pacote de mídias v2, sem migração SQL. Para grandes ZIPs, ajuste os limites de `upload_max_filesize` e `post_max_size` do PHP e reserve espaço temporário em disco; veja [docs/BACKUP.md](docs/BACKUP.md) e [docs/VALIDACAO_121.md](docs/VALIDACAO_121.md). **Antes de atualizar, preserve `storage/config.php`, `storage/backups/`, `storage/media/`, `storage/analytics.key` e `public/uploads/`.**

## Cadastro de notícias em três etapas (1.0.20)

Ao abrir **Notícias → Nova notícia** ou **Editar**, o painel apresenta **Básico → Texto → Foto** em cartões separados, com Voltar/Avançar. A antiga quarta tela “Confira e salve” não existe mais: no fim da etapa Foto há **Salvar notícia**. Manchete e categoria são validadas antes de avançar; o texto pode ser escrito no editor visual ou em blocos. O SEO é preenchido automaticamente ao salvar e seus controles manuais, assim como destaque, ficam em **Básico → SEO e destaque (opcional)**. Bibliotecas de mídia, galeria e documentos foram preservados. Sem migração de banco e backup lógico v9 intacto. Veja `docs/VALIDACAO_120.md`.

## Gerenciador de Mídias central (1.0.18)

O painel agora oferece a biblioteca de mídia em Notícias, Galerias, Publicidade e SEO. Novas fotos são organizadas por ano/mês e podem ser buscadas, reutilizadas, baixadas e catalogadas; edição de crédito/legenda e lixeira lógica para imagens sem vínculos. Backup v9, pacote ZIP v2 e compatibilidade com arquivos antigos. Consulte `docs/MIDIAS_118.md` e `docs/RELATORIO_ARQUIVOS_LINHAS_118.md`.

## Fotos otimizadas no editor (1.0.17)

O editor converte fotos grandes de capa e galeria para JPG leve **no navegador**, mantendo proporção, antes de enviar ao servidor. Há uma indicação do tamanho resultante; ainda é possível enviar JPG/PNG/WebP compatíveis diretamente. Os erros de upload mostram quando o PHP impõe um limite de 2M inferior aos 4 MB admitidos pela capa. O painel identifica `UPLOAD_ERR_INI_SIZE`, falhas temporárias e envios maiores que `post_max_size` com instruções para Termux. Sem alteração de banco nem backup v8. Veja `docs/VALIDACAO_117.md`.

## Correção de métricas (1.0.16)

O painel **Métricas** não emite mais `Warning: Undefined array key "days"` ao ser aberto sem filtro `?days=`. Sem parâmetro, exibe os últimos **30 dias**; os filtros aceitam somente **7, 30 ou 90** e entradas inválidas voltam a 30. O erro era apenas no controlador PHP da página administrativa, não nos eventos já gravados. Nenhuma consulta SQL, dado existente, backup ou tabela foi alterado. Consulte `docs/VALIDACAO_116.md`.

Para atualizar no Termux, preserve `storage/config.php`, `storage/analytics.key`, `storage/backups/`, `storage/media/` e `public/uploads/` e substitua **apenas os arquivos de código**. Reinicie o servidor local e abra `/admin.php?action=analytics` sem `days`, depois teste os três filtros. **Não apague o banco de dados** para corrigir esse aviso.

## Editor 2.0 (1.0.15)

Em **Notícias → Nova notícia**, avance nas três etapas (Básico, Texto, Foto) e salve na última. A barra do texto oferece formatação editorial restrita (negrito, itálico, subtítulo e lista) com prévia segura: o sistema não aceita HTML livre. A **Biblioteca de fotos** permite escolher como capa uma imagem já enviada ou marcá-la para galeria, sem copiar arquivo físico. Mídias permanecem no diretório de uploads e devem acompanhar os backups completos. Consulte `docs/EDITOR_2_0.md`.

## Central de Saúde (1.0.14)

Abra **Saúde do sistema** com uma conta administradora. O painel verifica MySQL/MariaDB, extensões PHP, pastas, espaço livre, existência/idade de backups e dependências opcionais. Também exibe um resumo de falhas locais sem mensagens de exceção, senhas, IPs ou dados de visitantes.

**Validar backup** confere a estrutura do arquivo mais recente, mas não substitui restauração em um banco de testes. O backup diário/agendado agora faz verificação antes de remover versões antigas e grava o arquivo por substituição atômica para evitar cópias parciais. Consulte `docs/SAUDE_SISTEMA.md`.

## Administração e publicidade

O painel também possui **Ônibus** (linhas, paradas, horários programados, dias de circulação e importação de arquivos DER-MG/ANTT). O último ícone do menu inferior móvel é **Ônibus** (substitui Mais); ele abre automaticamente as próximas saídas de Iguatama. A administração é acessada pela engrenagem discreta no rodapé (ou `/admin.php`).

O menu do painel tem **Publicidade** (seis espaços, AdSense e anunciantes locais, só para administradores) e concentra as publicações em **Notícias** (Todas, Nova notícia, Categorias, Nova categoria, Autores e Fontes), **Usuários**, **Configurações** (Geral/SEO, Backups) e Visão geral. O ícone ↗ à direita, junto ao botão Sair, abre o portal.

O sistema possui três funções: **Administrador** (todas as operações), **Editor** (publica e organiza notícias; sem contas, configuração e backup), **Redator** (cria e edita somente seus próprios rascunhos). As assinaturas de **Autores** são perfis editoriais independentes das credenciais de login. Não existe cadastro público de usuários nesta versão.

O SEO gera título e descrição, Open Graph, `NewsArticle` estruturado, meta robots e `sitemap.php`. Configure domínio HTTPS oficial em Configurações para obter canonical absoluto e sitemap. Indexação começa desativada para evitar exposição do site em testes; notícias marcadas como demonstração são `noindex`. Estes mecanismos não garantem resultados no Google.

### Atualizar uma instalação existente

1. Antes de substituir arquivos, faça backup com a versão antiga e guarde cópia de `storage/config.php`, `storage/backups/` e `public/uploads/`.
2. Atualize **apenas o código** e acesse `/admin.php`: o sistema criará as tabelas editoriais e de ônibus, acrescentando colunas sem apagar dados. O usuário SQL precisa de `CREATE TABLE` e `ALTER TABLE`.
3. A conta original permanece Administrador. Configure as permissões dos demais usuários no menu Usuários. Depois de atualizar, gere um backup lógico v10 ou pacote completo com mídias v2.
4. Quando houver domínio publicado, abra Configurações → SEO, indique URL HTTPS oficial e habilite a indexação. Para migração, copie também `public/uploads/`.
5. A restauração aceita `.json`/`.json.gz` dos formatos lógicos 1 a 10 (a compatibilidade deve ser verificada em uma instalação de teste). Os backups v10 **não são legíveis por versões anteriores à 1.0.24** do IguaNews.

## 🚌 Transporte regional (1.0.7)

A página Ônibus já abre com Iguatama como origem e lista as próximas saídas de todos os destinos, marcando Hoje, Amanhã ou Em N dias, além do contador regressivo. É possível filtrar origem e destino. O administrador ou editor usa **Ônibus → + Novo horário**, um wizard compacto em cartões (origem/hora → cidades/paradas sucessivas com horários → empresa/tarifa/dias → salvar). Pode adicionar várias paradas até escolher Última cidade. A tarifa é do percurso integral, não automaticamente de cada trecho intermediário. Não há horários preenchidos automaticamente: **cadastre apenas horários confirmados**. Mais detalhes em `docs/ONIBUS.md`. O backup lógico evoluiu para v5 e inclui as tabelas do módulo e fila de revisão.


### Importação DER-MG e ANTT · compatível com 1.0.7

- Abra **Ônibus → Importar DER / ANTT** no painel. Para DER-MG, baixe o TXT do [catálogo intermunicipal](https://www.consultas.der.mg.gov.br/grgx/sgti/acesso_informacao.xhtml) e envie-o no painel. Para ANTT, envie o CSV Horários SIGMA mais recente do [catálogo oficial](https://dados.antt.gov.br/dataset/gerenciamento-de-autorizacoes), ou experimente o download automático do CSV de setembro/2026 quando PHP cURL estiver disponível.
- Use **Filtro de cidade** (por padrão `Iguatama`) para reduzir os trajetos carregados. Somente pares de municípios que possam ser interpretados com segurança entram na fila.
- **Nenhum dado é publicado automaticamente:** as propostas aparecem no painel para revisão e aprovação. A importação de arquivos não autentica a origem do conteúdo enviado pelo usuário.
- Linhas importadas usam modo **Atualizável com revisão** ou **🔒 Fixa**. A proteção fixa impede que importações posteriores substituam a linha, suas paradas e partidas; uma edição manual no painel recomenda fixar automaticamente.
- Partidas intermediárias e chegadas sem informação oficial ficam **A confirmar**, sem cálculos inventados. Isso não é rastreamento em tempo real.
- O processo ainda depende de conferência das empresas, inclusive exceções de feriados e linhas paralisadas.

## O que está incluído

- Home responsiva inspirada no mockup aprovado: cabeçalho com marca IguaNews, busca, navegação, manchete grande, editorias em chips, cartões de últimas notícias, lista de mais lidas, seções Cidade/Política/Eventos e navegação inferior no celular.
- Página de notícia com título, resumo, imagem, autor, data e conteúdo.
- Filtro por categorias, pesquisa e contador interno de visualizações.
- Painel administrativo com login, papéis de usuário, autores e fontes reutilizáveis, notícias, rascunho e publicação, destaque, fotos e SEO.
- **Setup inicial em 3 etapas** (requisitos, banco e conteúdo) com provisionamento automático opcional de banco/usuário **local**, conexão manual alternativa e possibilidade de importar um backup IguaNews anterior antes da ativação.
- Backup manual, download, restauração protegida por senha e backup agendável por CLI, com retenção de até 7 diários + 4 semanais.
- Imagens ilustrativas locais; nenhuma imagem exige serviço de terceiros. Notícias de referência continuam identificadas como demonstração, sem alegação de apuração própria.

## Requisitos

- PHP 8.1 ou superior (PHP 8.2–8.4 recomendado).
- Extensão **pdo_mysql** e **fileinfo**. Extensão **zlib** opcional, mas recomendada para `.json.gz`.
- MySQL 5.7+/8.x ou MariaDB 10.4+, com tabelas InnoDB e codificação `utf8mb4`.
- Servidor Apache ou Nginx com a **raiz pública apontando obrigatoriamente para a pasta `public/`**.
- Permissão de gravação em `storage/`, `storage/backups/` e `public/uploads/`.
- Para backup automático, serviço de cron ou agendador do painel de hospedagem com PHP CLI.

> **Atenção:** hospedar o aplicativo PHP no runtime comunitário da Vercel não garante instalação nem backups automáticos, pois o filesystem da função não é persistente. Para este pacote, execute o instalador em **Termux ou hospedagem PHP convencional**. A Vercel também não oferece MySQL integrado; seria necessária uma instância remota. Este ZIP **não está preparado/validado para deploy Vercel**.

## Começando no Termux

1. Atualize e instale PHP e MariaDB:

   ```bash
   pkg update && pkg upgrade -y
   pkg install php mariadb -y
   php -m | grep -Ei 'PDO|pdo_mysql|fileinfo|zlib'
   ```

   Se `pdo_mysql` não aparecer, o PHP da sua instalação precisa ser ajustado antes de prosseguir. O setup mostra esse requisito em vermelho e impede prosseguir enquanto a extensão estiver ausente.

2. Inicialize o MariaDB **somente se ainda não tiver sido inicializado** e depois inicie o serviço (os nomes dos comandos podem variar por versão do pacote Termux):

   ```bash
   mariadb-install-db
   mariadbd-safe &
   mariadb -u root
   ```

3. Abra outra sessão do Termux e acesse `mariadb -u root` para conferir se o usuário administrativo do MariaDB consegue autenticar. **Não é preciso criar banco e usuário SQL manualmente se você usar o modo automático**, mas o MariaDB precisa estar ligado e o root deve ter permissão de criar bases e usuários. Caso não consiga autenticar pelo instalador, crie a base e o usuário pelo console ou painel e use o modo manual.

4. Descompacte o projeto e execute **a partir da pasta Meu Site**:

   ```bash
   cd "/caminho/para/Meu Site"
   php -d upload_max_filesize=20M -d post_max_size=24M -S 127.0.0.1:8000 -t public public/router.php
   ```

5. Abra `http://127.0.0.1:8000/setup.php` no navegador do mesmo celular e siga as três etapas:

   - **1, Requisitos:** dependências, arquivos graváveis e limites de importação.
   - **2, Banco:** **Criar automaticamente** usando usuário administrativo do MariaDB (ex.: `root`, desde que autenticável). O instalador cria um banco novo ou reaproveita um banco comprovadamente vazio se autorizado, além de usuário aleatório com senha forte e permissões restritas. A senha do root não é guardada. Para banco preexistente ou hospedagem, use **Usar banco existente**. A opção automática exige acesso local `127.0.0.1` e não substitui inicializar/iniciar o MariaDB.
   - **3, Conteúdo:** **Começar um portal novo**, criando a conta do painel, ou **Importar portal anterior**, usando um backup IguaNews `.json`/`.json.gz` de uma instalação antiga. Ao importar, entre depois com o mesmo login do portal antigo. **Fotos pessoais devem ser copiadas da pasta `public/uploads/` separadamente.**

   Se houver erro de conexão, confirme que o MariaDB está aceitando conexões TCP na porta 3306. Se o usuário root autenticar apenas por socket e não pelo PHP, use o modo manual e crie o usuário SQL no MariaDB (ou no painel da hospedagem).

6. Depois da instalação, acesse `http://127.0.0.1:8000/admin.php` para criar suas notícias.

> O servidor `php -S` serve **somente para desenvolvimento**. Não o exponha como hospedagem pública. Se abrir o projeto fora do diretório raiz, respeite as URLs absolutas `/assets` e `/index.php`, ou publique-o no raiz do domínio.

## Estrutura

```text
Meu Site/
├── app/
│   ├── bootstrap.php       Conexão PDO, sessões, segurança e utilitários
│   ├── schema.php          Tabelas e dados de demonstração
│   └── backup.php          Exportação e restauração portáteis
├── bin/
│   └── backup.php          Backup automático por linha de comando
├── public/                 DOCUMENT ROOT do servidor
│   ├── index.php           Portal público
│   ├── admin.php           Administração
│   ├── setup.php           Instalador (desativado após instalar)
│   ├── assets/             CSS, JS e fotos ilustrativas do mockup
│   └── uploads/            Imagens enviadas pela redação
├── storage/
│   ├── config.php          Gerado pelo setup, contém credenciais (NÃO COMMITAR)
│   └── backups/            Cópias protegidas (NÃO COMMITAR)
└── docs/
    ├── BACKUP.md           Backup/restauração, segurança e limites
    └── DEPLOY.md           Orientações para hospedagem PHP
```

## Segurança e privacidade

- SQL com prepared statements; dados públicos escapados como HTML.
- Sessão com cookie `HttpOnly` e `SameSite=Lax`; `Secure` quando houver HTTPS.
- Proteção CSRF nos formulários administrativos; senha armazenada via `password_hash()`.
- Upload com detecção MIME/assinatura de imagem, nomes aleatórios e limite de 4 MB.
- Diretório de armazenamento fora da raiz pública. **Nunca exponha `storage/config.php`.**
- Proteja o painel com HTTPS em produção. Configure backups também fora do servidor.
- Não publica automaticamente notícias de terceiros nem traz informações verificadas em tempo real.
- Não inclui cadastro público nem moderação de sugestões. O botão "Enviar notícia" abre o cliente de e-mail para o contato administrativo.
- O projeto é um ponto de partida funcional, não uma auditoria independente de segurança ou desempenho em produção.

## Limitações conhecidas da v1.0.2

- O backup é **lógico em JSON/JSON.GZ**, não um dump SQL nativo. É independente de `mysqldump`, e a restauração é feita no painel, validando tabelas e colunas esperadas. Não aceita comandos SQL arbitrários.
- Importação pelo setup só aceita **backup IguaNews** para um banco vazio. Não importa dumps SQL arbitrários ou migra bancos de outros CMS. Veja **docs/MIGRACAO.md**.
- Backup **não inclui a pasta de imagens**: copie `public/uploads/` separadamente antes de migrar/formatar. As imagens de demonstração ficam em `public/assets/img/`, incluídas no ZIP.
- Backup/restauração leem dados em memória. Para bases muito grandes, migrar para um sistema com streaming/`mysqldump` + armazenamento externo.
- Notificações push, contas com múltiplos papéis, edição avançada de matérias e envio de notícias por leitores não estão nesta versão.
- O cadastro inicial não deve ser deixado aberto em domínio público por muito tempo: faça o setup imediatamente após subir o sistema.

## Testes realizados

- Sintaxe PHP verificada com `php -l` nos arquivos PHP.
- Diagnóstico da instalação exercitado com `php tests/setup_diagnostics.php` (requisitos, avisos e mensagens para códigos de erro MySQL).
- Assistente de três etapas conferido com saída HTML para as telas de banco e conteúdo; botões e formulários de provisionamento e importação identificados.
- Testes isolados com `php tests/installer.php` verificam SQL de criação restrita, identificadores seguros, recusa de banco com dados e reutilização somente de banco vazio.
- Verificações estáticas de rotas, formulários e caminhos de imagens; integridade do arquivo ZIP.
- **Não foi executado teste de conexão/inserção real no MySQL** neste ambiente: a extensão `pdo_mysql` não estava disponível. O primeiro teste integrado deverá ser no Termux/hospedagem compatível.

## Licença e uso

Código inicial entregue para desenvolvimento do projeto IguaNews. As imagens de demonstração foram recortadas do mockup gerado para este projeto, e **não representam registros fotográficos de acontecimentos locais**.

## Atualizar da versão 1.0.0 ou 1.0.1 para 1.0.2

- Pare o servidor de desenvolvimento `php -S` (Ctrl+C); depois substitua os arquivos de código (`app/`, `public/setup.php`, `public/assets/css/style.css`, `docs/`, testes e metadados) pelos da versão 1.0.2, **sem excluir arquivos particulares**.
- **Não apague nem sobrescreva** `storage/config.php`, `storage/backups/`, nem `public/uploads/` de uma instalação já concluída. O ZIP não inclui credenciais nem seus arquivos enviados.
- Inicie novamente o servidor: `php -S 127.0.0.1:8000 -t public`. Se não tiver instalado, abra `/setup.php` e siga os três passos.
- O esquema SQL **não foi alterado**. Se já instalou, o novo assistente permanece bloqueado e o portal continua funcionando.
- Se a instalação anterior falhou por MariaDB desligado, não desinstale PHP nem apague bancos: primeiro confira `mariadb-admin --protocol=tcp -h 127.0.0.1 -P 3306 ping`.

## Migração online

Consulte **docs/MIGRACAO.md** para mover os dados do Termux para uma hospedagem PHP, incluindo banco, fotos, usuários e verificação antes de mudar o domínio.


---

## Novidades da versão 1.0.3

- Editorias da interface: inclui **Geral** no menu principal, mantendo todas as dez categorias no filtro horizontal.
- O instalador de banco novo carrega até **100 pautas de referência, 10 por categoria**, quando a opção de amostras está marcada. Não são reportagens locais inéditas; as notícias são sínteses editoriais baseadas em manchetes de fontes identificadas (Agência Brasil e canais públicos de MG). Elas **não devem ser republicadas automaticamente como notícias apuradas do IguaNews**. Algumas fontes apontam para a editoria, não para o item original.
- Na instalação já concluída, vá ao painel **Notícias** e use **Carregar 100 notícias de amostra**. Ação faz backup antes, elimina apenas notícias com `is_demo=1` e preserva notícias próprias. Requer confirmação da senha do administrador.
- Foto com legenda, crédito, fonte da notícia e URL editáveis; migração SQL aditiva executada quando entrar no painel. Imagens do acervo são **ilustrações geradas por IA**, não fotografias de acontecimentos.
- O banco continua usando backup portátil em JSON/GZIP e preserva as novas colunas. Ao migrar, copie `public/uploads/` separado.

## Versão 1.0.8: ônibus, cidades e contato

- A consulta de ônibus permite **digitar** origem e destino; após duas letras, a lista de municípios do IBGE é consultada pelo **servidor PHP**, com cache por 30 dias em `storage/municipios_ibge.json`. É necessária extensão PHP cURL e acesso HTTPS para popular a lista nacional. Se a API falhar, continuam disponíveis as cidades já cadastradas e uma lista mínima de referência; a falha de rede é lembrada por 1 hora. A sugestão de cidade **não significa linha disponível**.
- O card público calcula **Duração** a partir da diferença entre horários reais de partida e chegada do cadastro. Quando não há offset confiável, mostra *Duração a confirmar*.
- O importador DER-MG recebe **quadro_horario_inter.txt**, não `itinerario_inter.txt` nem arquivos de paradas. Procura automaticamente a linha de cabeçalho e aceita separadores de tabulação, ponto e vírgula, vírgula, barra vertical ou colunas separadas por espaços. O sistema continua exigindo aprovação de cada linha e não inventa trechos/chegadas. Se importar zero registros, confira o filtro de município e o relatório de registros ignorados. Outros layouts oficiais não documentados podem exigir uma amostra real do TXT para adaptação.
- Em **Termux**, se o DER-MG exceder o limite padrão de upload PHP (frequentemente 2 MB), reinicie o servidor com:

```bash
php -d upload_max_filesize=20M -d post_max_size=22M -S 127.0.0.1:8000 -t public
```

- Rodapé contém Privacidade, Termos, Contato, Anuncie, GitHub, portfólio e acesso administrativo discreto. Não exibe o destinatário do e-mail.

### E-mail do formulário Contato / Anuncie

A configuração SMTP é obrigatória para **envio real**. No Termux sem SMTP, o formulário retorna mensagem de indisponibilidade e **não informa sucesso**. O receptor está definido apenas no backend. No servidor, use variáveis de ambiente privadas:

```bash
export IGUANEWS_SMTP_HOST='smtp.seu-provedor.com'
export IGUANEWS_SMTP_PORT='587'
export IGUANEWS_SMTP_USER='usuario-smtp'
export IGUANEWS_SMTP_PASSWORD='senha-smtp-privada'
export IGUANEWS_SMTP_FROM='remetente-verificado@seu-dominio.com'
```

Instale Composer e execute `composer install --no-dev --optimize-autoloader` na raiz do projeto para obter PHPMailer. As configurações precisam ser carregadas antes de iniciar o PHP. Não use endereço de remetente que seu provedor não autorize, não versione credenciais e prefira HTTPS em hospedagem pública. O formulário aceita apenas texto, protege contra CSRF e cabeçalhos injetados, possui campo anti-bot invisível e limita mensagens por origem. A política de privacidade deve ser revisada quando a infraestrutura definitiva e os fornecedores de e-mail forem escolhidos.

Os backups existentes não foram modificados nesta versão. Em atualização, preserve `storage/config.php`, `storage/backups/`, `storage/municipios_ibge.json` (se existir) e `public/uploads/`.


### Publicidade 1.0.10
Menu Publicidade: ativações geral e por espaço, anúncios locais com datas, AdSense com ID de editor e bloco, e adaptadores de redes externas planejados (não habilitados sem desenvolvimento específico). Nenhuma publicação de anúncios por padrão. Consulte docs/PUBLICIDADE.md.


## Novidade 1.0.10: editor em blocos

Veja [docs/EDITOR_INTELIGENTE.md](docs/EDITOR_INTELIGENTE.md) para detalhes da criação de matérias por cartões, importação de metadados por URL, SEO automático, galerias, anexos e pacotes de migração. O conteúdo anterior não é removido.

## Métricas próprias (1.0.11)

Depois da atualização, entre no painel > **Métricas** (administrador). O módulo registra páginas visitadas, cliques, origem por domínio, dispositivos e possíveis crawlers. Os dados começam a contar agora: números anteriores não podem ser recuperados.

Não há geolocalização exata: instale, **opcionalmente**, a dependência `composer require geoip2/geoip2:^3.4` e coloque um arquivo atualizado `GeoLite2-City.mmdb` licenciado em `storage/geoip/` (nunca dentro de `public/`). Em redes locais (Termux/127.0.0.1), países e regiões são desconhecidos. A base offline evita enviar IP de leitores a terceiros. Confirme condições/licença e atualize a base regularmente.

A chave `storage/analytics.key` é criada com permissão restrita na primeira coleta, fora da pasta pública, e não acompanha ZIPs de código-fonte. Preserve esse arquivo nas atualizações para a consistência dos visitantes diários; no caso de reinstalação, se não houver a chave, uma nova será criada. O sistema não grava IP bruto, URLs com parâmetros de consulta livres nem User-Agent inteiro. Visitantes que usam DNT/GPC ou optam por não participar na Política de Privacidade não são contados. Os eventos são mantidos por 90 dias e eliminados oportunisticamente ou via `php bin/analytics_prune.php` no cron.

**Limites:** bots classificados pelo User-Agent podem se disfarçar; visitas únicas são calculadas *por dia*, não equivalem a usuários únicos mensais. Sem JavaScript, visualizações ainda são contadas, mas cliques não. Cliques podem ser bloqueados por extensões de privacidade. Em hospedagem com proxy, REMOTE_ADDR poderá ser do proxy, afetando únicos e GeoIP: configure um reverse proxy confiável antes de habilitar cabeçalhos de IP real; o IguaNews não confia automaticamente em X-Forwarded-For. Não interprete localização por IP como GPS.


## Editor simples e exemplos (1.0.12)

Abra **Notícias → Nova notícia**. Na versão histórica 1.0.12, as quatro etapas mostravam inicialmente apenas título, resumo, categoria, situação, texto e foto opcional; blocos, galerias, fonte, agendamento e SEO manual aparecem em painéis recolhíveis.

O botão **Como preencher** abre `/assets/docs/GUIA_EDITOR_IGUANEWS.pdf`, também disponível em `docs/GUIA_PREENCHIMENTO_NOTICIAS.md`. O PDF está incluído no ZIP do projeto. No painel **Notícias**, administradores podem clicar em **Adicionar 3 exemplos preenchidos**, confirmar a senha e criar exemplos totalmente fictícios de acidente, evento e empregos. A ação cria backup e não substitui nenhuma matéria existente. Exemplos repetidos não são duplicados. As matérias de demonstração continuam sinalizadas e sem indexação.

**Atualização**: preservar `storage/config.php`, `storage/backups/`, `storage/media/` e `public/uploads/`. Não requer alteração de esquema SQL nem de backup v8. Realizar backup antes da atualização. Publicação de exemplos requer teste no MariaDB real.

## Melhorias de redação (1.0.13)

Em **Notícias → Todas**, pesquise título ou resumo, filtre entre **Rascunhos**, **Publicadas** (já visíveis no site) e **Agendadas** (data de publicação futura). A lista apresenta 20 resultados por página, com botões **Anterior/Próxima**. Redatores continuam acessando somente seus próprios rascunhos.

A ação **Duplicar** cria uma notícia **não publicada** com texto, categorias, seções e dados editoriais da original. É necessário revisar os dados antes de publicar. A capa pode reutilizar o arquivo de origem; galeria e anexos não são duplicados. Exemplos fictícios permanecem marcados como demonstração. O acesso é verificado no servidor e a ação usa CSRF.

Alterar a manchete de uma notícia existente **não troca mais sua URL (`slug`)**, preservando compartilhamentos e resultados de busca. Para URLs antigas que já tenham mudado em versões anteriores, não existe redirecionamento automático retroativo.

O salvamento automático dos rascunhos agora tenta novamente após falhas de rede; arquivos de imagem selecionados continuam **fora** do autosave e exigem o botão **Salvar notícia**. Seu navegador/Termux deve continuar aberto para que o autosave seja executado.

Nenhuma tabela SQL nem formato de backup mudou: o backup continua em **v8**. Antes de atualizar, gere um backup e preserve `storage/config.php`, `storage/backups/`, `storage/media/`, `storage/analytics.key` e `public/uploads/`.

- Publicação rápida por ícone na lista de notícias, títulos compactos no celular e galeria de mídias com busca em ícone e envio automático.

### CSS modular (v1.0.31)

O portal carrega **um único** `/assets/css/style.css`, gerado a partir de 15 arquivos-fonte em `public/assets/css/modules/`. Para editar CSS, altere o módulo e execute `python3 tools/build_css.py`; valide com `python3 tools/build_css.py --check` e `php tests/css_modules_131.php`. Para instalar/rodar, o arquivo já vem montado e o Python não é exigido. Consulte `docs/CSS_MODULOS_131.md`.

### Organização do editor (v1.0.32)

A fachada `app/news_editor.php` carrega serviços menores em `app/news_editor/`: `schema.php`, `blocks.php`, `format.php`, `import.php`, `library.php`, `media.php` e `revisions.php`. A API de funções existente foi mantida, sem alteração de rotas, dados ou etapas do editor.

### Interface 1.0.67
Envios de ZIP para Atualizações mostram o progresso real do navegador; instalação no servidor não disponibiliza percentual e permanece indeterminada. Manutenção, publicidade e ônibus receberam compactação e ajuda contextual.
