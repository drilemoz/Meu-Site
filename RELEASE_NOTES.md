## IguatamaOnline 1.0.126

**Novo em Ferramentas → Informações do Servidor:** painel leve, pensado para celular, com cartões de PHP, servidor web, banco de dados e memória por processo. Mostra também limites de upload e POST, espaço disponível na partição do PHP, extensões essenciais e permissões de gravação de pastas.

**Limitações e segurança:** informação pontual, somente leitura e exclusiva do administrador. Espaço do disco não é a franquia do plano; memória por processo não é RAM física; CPU e uptime não são monitorados. A página não revela credenciais, caminhos internos ou IPs. Sem mudança no schema 37, dados ou integrações.

## IguatamaOnline 1.0.125

**Explorador mais limpo no celular:** a seleção de arquivos agora é ativada pelo botão **Selecionar**, sem exibir caixas e comandos por toda a tela desde a abertura. Cancelar desmarca os itens. A busca mostra contagem de resultados e aviso de nenhum arquivo encontrado.

**Correção de compatibilidade:** botões ZIP que a hospedagem desativou por falta de `ZipArchive` não são reativados pelo JavaScript. Navegação, downloads, controles de permissões e segurança permanecem como antes.

Nenhuma alteração no banco ou no sistema de autenticação. Exige instalação da atualização para aparecer no site e teste visual no Chrome Android.

## IguatamaOnline 1.0.124

**Gerenciador de Arquivos com visual mais limpo no celular:** reorganizado o topo com um cartão de contexto, separando melhor a navegação em leitura da pasta pública com edição. A área da raiz ficou menos carregada, com o atalho de uploads separado do botão de baixar ZIP.

**Ações em lote mais organizadas:** na pasta `public/uploads/arquivos`, copiar, recortar, compactar e o campo do nome do ZIP passam a usar uma grade mais estável e compacta. O contador de itens selecionados virou selo visual, e o menu de criação foi renomeado para **Novo arquivo ou pasta**.

**Segurança e dados:** nenhuma regra de acesso foi afrouxada. Código do portal continua somente leitura fora da área gerenciada; schema 37, backups, GA4, AdSense e demais integrações permanecem inalterados. Teste novo: `tests/file_manager_224.php`.

## IguatamaOnline 1.0.123

**Comandos úteis no Gerenciador de Arquivos:** seleção múltipla, copiar, recortar, colar, compactar, descompactar, upload ZIP, excluir arquivos e pastas (com confirmação), renomear e permissões limitadas para arquivos gerenciados. Fora dessa pasta, visualização e download de seleção em ZIP para arquivos permitidos do projeto. Ferramentas permanece o submenu de acesso.

**Segurança:** sem edição de executáveis, symlinks ou pastas privadas; limite de quantidade/tamanho, extração sempre em pasta nova, sem substituir nomes existentes. `ZipArchive` é necessário para ZIP; o ZIP não foi testado com extensão real no ambiente local. Manter backup externo antes de exclusões. Nenhuma mudança de banco.

## IguatamaOnline 1.0.122

- O submenu **Ferramentas → Gerenciador de Arquivos** agora mostra a estrutura do projeto desde a raiz, em vez de começar na pasta vazia de uploads. Entre em `app/`, `public/` e outras pastas disponíveis pelo celular.
- Tela dedicada, com pastas e arquivos em lista, caminho navegável, pesquisa rápida, paginação e prévia de códigos/textos e imagens. Removidos os cartões de Biblioteca de mídias, Backups e Arquivos essenciais desta tela.
- Arquivos PHP e demais arquivos do sistema podem ser lidos ou baixados por administradores, mas **não podem ser alterados por esse módulo**. `storage/` e segredos permanecem protegidos.
- Upload e gerenciamento continuam na pasta `public/uploads/arquivos/`, e a edição de `ads.txt` / `robots.txt` continua disponível ao navegar em `public/`. Nenhum dado ou banco foi alterado.
- Testes locais de PHP, JS, segurança de caminhos e pacotes; testar navegação, permissões e operações em aparelho/hospedagem após instalação.

## 1.0.121 — Novo Gerenciador de Arquivos em Ferramentas

O painel administrativo agora inclui **Ferramentas → Gerenciador de Arquivos** (somente administradores). Permite pastas, envio de arquivos JPG/PNG/WebP/PDF/TXT/CSV, downloads, renomear, excluir e editar textos; também permite editar `ads.txt` e `robots.txt` com segurança.

Os arquivos de `uploads/arquivos` são públicos; não guarde dados privados ali. Upload limitado a 8 MiB, texto a 128 KiB. Não permite scripts PHP nem acesso à configuração interna do servidor. Exige permissões de escrita na hospedagem. Nenhuma migração SQL.

## 1.0.120 — Navegação sem aviso de métricas

O aviso fixo “Google Analytics” deixa de aparecer sobre as notícias. A autorização opcional está na Política de Privacidade, acessível pelo rodapé. Consentimentos já registrados continuam válidos, e a preferência DNT/GPC do navegador permanece respeitada; não há ativação automática da tag para visitantes sem autorização. O painel de métricas internas não foi afetado. Sem alterações de banco, schema 37, backup v12 ou APIs Google. A publicação no site exige instalar a atualização e validar manualmente.

## 1.0.119 — Google Analytics mais claro

A janela do GA4 agora diferencia a coleta de visitantes mediante consentimento dos relatórios opcionais dentro do painel. Inclui orientações para verificar visitas no relatório Tempo real do Google, explica o papel dos IDs e da conta de serviço, recolhe as opções avançadas e deixa os botões indisponíveis com aparência coerente. Nenhuma migração de banco.

## IguatamaOnline 1.0.118

- Corrigido o ensaio integrado de ZIP adulterado após falha do workflow #33. Antes, um payload com tamanho diferente era rejeitado antes da verificação SHA-256; o teste agora separa as rejeições por tamanho e por hash (mantendo o tamanho original).
- O pipeline e o teste offline passam a proteger os dois cenários, sem alterar o atualizador ou as rotinas de banco, backup e restauração.
- Os logs comprovaram a instalação ZIP, o backup MariaDB e o rollback com sucesso no CI da 1.0.117. A execução dos cenários corrigidos em MariaDB/ZipArchive reais permanece pendente até um novo workflow.
- Schema 37, backup v12 e ZIP v2 preservados. Nenhuma instalação na hospedagem.

## IguatamaOnline 1.0.117

- Teste integrado de atualização ZIP v2 com MariaDB e ZipArchive reais em ambiente isolado de CI: instala arquivos de teste, gera backup lógico v12, verifica SHA-256, executa rollback e rejeita pacote adulterado.
- A suíte nova exige banco `igua_ci_*`, host local e autorização explícita; não toca nos arquivos publicados nem restaura dados reais. Inclui teste offline de contrato para assegurar que o workflow continua protegendo essa integração.
- Não houve mudanças no comportamento do portal, schema 37, backup v12 ou código de atualização. Ainda é necessária execução bem-sucedida do GitHub Actions e homologação no Plesk; os testes locais não podem substituí-las.

## IguatamaOnline 1.0.116

- Corrigido um risco de perda de alterações manuais ao recuperar uma atualização interrompida. Antes de reverter qualquer arquivo, o atualizador confirma que cada destino ainda está igual ao estado original ou ao novo estado registrado; conteúdo inesperado exige recuperação assistida, sem sobrescrevê-lo automaticamente.
- Teste adversarial reproduziu o problema na base 1.0.115 e validou a correção, inclusive na retomada de rollback parcial. Estados `complete`, snapshots privados e bloqueio de alterações posteriores continuam protegidos.
- Schema 37, backups v12, ZIP v2 e recursos do portal preservados. Nenhuma restauração real com banco ou instalação no Plesk. Faça backup externo antes da atualização.

## IguatamaOnline 1.0.115

- Atualizador revisado para validar todos os snapshots antes do rollback e evitar restauração parcial quando algum backup já está ausente/adulterado.
- Recuperação manual usa manutenção e journal recuperável antes de modificar arquivos. Se o processo parar durante o rollback, é possível reexecutar a recuperação; a versão só é considerada restaurada depois de registrar `rolled_back` e encerrar a manutenção.
- Adicionados testes offline de falhas injetadas após 1, 3 e 4 substituições e durante a restauração; teste adicional de edição posterior bloqueia o rollback de uma atualização concluída.
- O atualizador avisa quando não consegue retirar a manutenção, em vez de declarar sucesso. Schema 37, backups v12 e arquivos de dados existentes permanecem intactos.
- **Limite:** testes de arquivos temporários não substituem MariaDB, ZipArchive, falta de disco real nem interrupções de processo no Plesk. Faça backup externo antes de atualizar.

## IguatamaOnline 1.0.114

- Fortalecida a proteção no acesso administrativo aos backups: a lista e o download não seguem arquivos nem pasta de backup que sejam links simbólicos. Arquivos normais permanecem compatíveis.
- Adicionada regressão de cenários adversos: GZIP danificado, estrutura de banco inválida, ausência de administrador ativo, nomes de backup inseguros e manifestos de atualização malformados ou sem assinatura obrigatória no formato legado.
- Sem alteração dos dados, de rotinas de restauração, do schema 37 ou do backup v12. Os testes offline não comprovam restauração completa no Plesk; conferir backup e rollback em homologação antes de mudanças críticas.

## IguatamaOnline 1.0.113

- Melhor organização interna do Wizard de Notícias: ações de categorias/fontes, Gemini, rascunhos/importação, salvamento e publicação separadas em arquivos pequenos, com mesma rota administrativa.
- Fluxo da notícia, formulários, editor, mídias, categorias, fonte principal, SEO, agendamento, anexos e fotos ZIP não receberam alteração funcional. O controlador original foi reconstituído byte a byte para validar equivalência.
- Schema 37 e backup v12 preservados; sem migração e sem exclusão de mídia. Testes locais não substituem conferência no Plesk após a atualização.

## IguatamaOnline 1.0.112

- Refatoração de manutenção na Central de Integrações e APIs: o template principal chama componentes específicos de Google Analytics 4, AdSense, Search Console, PageSpeed Insights, Gemini, Diagnóstico, Inspeção de Páginas, Catálogo e Janelas.
- O template foi reconstruído byte a byte no teste de regressão; não foram redesenhados formulários, pop-ups, credenciais, rotas ou funções do Google/Bing.
- Sem novas permissões, migração ou mudança de dados. Schema 37, backup v12, arquivos privados e APIs existentes preservados.
- Testes offline não substituem validação visual e funcional no Plesk/Chrome Android.

## IguatamaOnline 1.0.111

- Refatoração interna de `app/analytics.php`: funções de privacidade, classificação, roteamento e leitura auxiliar isoladas em `app/analytics_helpers.php`; a inclusão é automática e a API de métricas não muda.
- A tela SEO mantém exatamente os mesmos campos e modais, mas atualiza prévias de texto, imagem social e favicon em funções separadas, facilitando manutenção futura.
- Novos testes PHP/JavaScript de compatibilidade e regressão. Não altera métricas históricas, classificação, notícias, credenciais, configurações nem SEO público.
- Schema 37 e backup v12 preservados. É necessário testar na hospedagem após instalar.

## IguatamaOnline 1.0.110

- Biblioteca de mídias agora é o caminho visual único para trocar imagem social e favicon, com prévia e limpeza. Os caminhos técnicos deixam de aparecer em Configurações avançadas.
- SEO de páginas fixas: títulos e metadescrições opcionais individuais; em branco, os padrões anteriores permanecem. A descrição salva de até 320 caracteres não é silenciosamente cortada a 260 pelo gerador público.
- Métricas com rótulos mais precisos: visualizações não identificadas como robôs não são equivalentes a pessoas; visitantes/dia são identificadores estimados. Sinais adicionais de automação e limiar de volume elevado; relatórios antigos não são reclassificados.
- URLs amigáveis passam a ser contabilizadas por página quando a rota válida está disponível. Logs completos do servidor podem ser necessários para investigar visitas passadas.
- Sem migração, schema 37 e backup v12. Testes externos e ambiente Plesk ainda requerem validação.

## IguatamaOnline 1.0.109

- Página de ônibus: lupa abre/recolhe o formulário de busca, sem alterar horários existentes.
- SEO: campo para título exato da página inicial e títulos de páginas institucionais, preservando títulos de notícias e categorias.
- Favicon: escolher imagem na biblioteca existente ou informar URL, com ícone padrão se vazio.
- Janela de configurações avançadas reparada para exibir campos completos e permitir rolagem interna no celular.
- Sem migração de banco (schema 37); requer instalação na hospedagem para validar a experiência real.

## IguatamaOnline 1.0.108

- Sitemap e Indexação redesenhado para economizar espaço no celular: resumo e URLs na página; Verificações, IndexNow e Bing em pop-ups centralizados com rolagem interna.
- Busca, filtros, envio IndexNow, teste Bing, diagnóstico HTTP e operações protegidas preservadas. Listagem de 12 URLs por página; link completo continua disponível para copiar/abrir.
- Contato do desenvolvedor ganhou **Meu site** com link para `https://adriedsonlemos.vercel.app`.
- Sem migração (schema 37). Recomendado backup antes de instalar. Testes de produção e integrações externas seguem pendentes.

## IguatamaOnline 1.0.107

- Verificação Bing via arquivo `BingSiteAuth.xml` fornecido pelo proprietário, a ser servido no endereço HTTPS público. A Central de Sitemap oferece acesso direto ao arquivo; finalize no botão **Verify** do Bing Webmaster Tools.
- Menu do painel simplificado para **Páginas**.
- Ônibus: lista de linhas organizada em cartões, filtro por cidade de origem e acesso para editar percurso/origem, sem rolagem horizontal no celular.
- Sem migração de banco (schema 37). Não altera linhas existentes; teste na hospedagem ainda necessário.

## Meu Site v1.0.106 — Teixeira e Bing Webmaster

- **Ônibus:** correção do pop-up que confirmava a tabela sem executar o POST. A seleção continua voluntária, autenticada e protegida por CSRF. Depois de instalar, é necessário confirmar novamente o cadastro dos 10 trajetos. Horários existentes não são substituídos.
- **Bing:** nova consulta manual de verificação da propriedade e de sitemaps em Configurações → Sitemap e Indexação. A chave API fica em storage privado, sem exposição no navegador; configuração e remoção exigem senha administrativa. Não envia URLs nem garante indexação.
- **Validação:** regressões offline e teste MariaDB no CI; API externa e hospedagem ainda não testadas. Schema 37 mantido, sem migração de banco.

## 1.0.105 — IndexNow

- A Central de Sitemap agora oferece configuração IndexNow e envio manual com validação das URLs.
- Chave de verificação em arquivo TXT público; dados de configuração no storage privado.
- Não envia URLs ao Google; participação é de Bing e outros mecanismos IndexNow.
- Sem nova migração de banco (schema 37).

# Meu Site v1.0.104 — Ônibus Teixeira e teste Gemini

- Ônibus: inclusão **manual, mediante confirmação no painel**, dos 10 trajetos da tabela fotografada da Teixeira em setembro/2026, com 31 grupos de saídas. Já cadastrados são preservados e não duplicados. Rodoviárias como origem/destino; chegada e tarifa não informadas. Feriados em dias úteis não são remapeados automaticamente.
- Gemini: serviço disponível na aba Google com Salvar configuração e Testar API lado a lado. Remoção separada, protegida por senha. Teste em pop-up com resultado real e orientações por código HTTP, em especial 503 por sobrecarga temporária. Sem expor chave.
- Schema 37/backup v12 sem mudanças, sem migração; manter backup antes de instalar. Não foi possível validar o fluxo real com MariaDB da hospedagem nem API Gemini com chave do proprietário.

---

# Meu Site v1.0.103 — Integração Gemini no portal

- Card Google Gemini já existente no catálogo foi ativado: credencial API no armazenamento privado, modelo configurável, ativar/desativar, teste manual real e remoção protegida por senha administrativa. API via HTTPS e host fixo; chaves nunca em HTML, URL de requisição ou GitHub.
- No wizard, quatro atalhos com **somente o símbolo ✦** sugerem manchete, resumo, revisão ortográfica do corpo e descrição SEO. Cada consulta requer clique explícito, tem limites de texto/cota por sessão e abre pop-up de revisão; nada é aplicado ou publicado automaticamente.
- Teste API não foi realizado com a conta do proprietário: é necessário cadastrar uma chave verdadeira e confirmar modelo/cota na hospedagem. Chamadas reais à API podem consumir créditos.
- Sem migração de banco (schema 37 e backup v12). Nenhuma notícia nem configuração existente foi modificada automaticamente.

---

# Meu Site v1.0.102 — Centro visual de SEO

- Prévia Google e compartilhamento social, com diagnóstico local de título, descrição, URL HTTPS, imagem e configurações de rastreamento.
- Formulário geral preservado; controles técnicos agora ficam em pop-ups centralizados, sem seções expansíveis.
- Ajuste de imagem social pelo gerenciador, alerta para URLs inadequadas e confirmação para desligar a indexação.
- Sem migração: schema 37 e backup v12. Nenhuma verificação HTTP ou de indexação é executada automaticamente.
- Testes na hospedagem e no navegador Android continuam necessários.

---

# Meu Site v1.0.101 — Correções do cadastro e do destaque principal

- Corrigido **HTTP 404** dos botões Nova categoria e Nova fonte durante a edição de notícias.
- Formulários exibem motivos de validação retornados pelo servidor, sem expor mensagens internas de banco de dados.
- Somente notícias marcadas como **Destacar na página inicial** podem ocupar o destaque principal; notícias comuns permanecem nas listas normais.
- Nenhum dado ou estrutura de banco alterado. Schema 37 e backup v12 preservados. Instalação e verificação na hospedagem continuam pendentes.

---

# Meu Site v1.0.100 — Central de Sitemap e Indexação

- **Configurações → Sitemap e Indexação:** consulte as URLs elegíveis para buscadores em listas filtráveis e paginadas, com datas de atualização verificáveis, botões Copiar, Abrir e Testar HTTP.
- **Verificar online:** consulta `sitemap.php`, `robots.txt` e `ads.txt` sob demanda pelo navegador, exibindo códigos HTTP e falhas sem expor credenciais. Não significa que o Google indexou os links.
- `robots.txt` agora anuncia o sitemap no domínio oficial. O XML usa o catálogo do painel; conteúdos não publicados e demonstrativos continuam excluídos.
- O arquivo `ads.txt` existente na hospedagem não é sobrescrito. Configuração AdSense continua em Publicidade; a Search Console pode ser acessada a partir da central.
- Sem alteração de banco, schema 37 e backup v12 mantidos. Testes no MariaDB e na hospedagem real ainda necessários.

---

# Meu Site v1.0.99 — Publicação de páginas legais e data automática

- Pop-ups de confirmação e conteúdo centralizados, com altura ajustada ao texto, rolagem interna quando necessária e sem ocupar desnecessariamente o celular.
- Separadas Informações e Páginas do aplicativo. Cartões com botões compactos de Editar, Prévia/Visualizar, Copiar e Publicar.
- **Publicar tudo** salva as três revisões principais numa única transação: erros não deixam publicação parcial. Rascunhos não alteram automaticamente a versão pública.
- A data de **Última atualização** deriva de `legal_app_revisions.created_at` (momento da publicação), sem ser digitada no texto. Reutilizar conteúdo idêntico não altera a data.
- Modelos novos do Uai Cortes revisados sem instruções PREENCHER nem datas fixas; opção de atualizar somente rascunhos antigos intactos, preservando edições existentes. A conformidade jurídica depende de revisão do administrador.
- Schema 37, backup v12 e recursos antigos preservados. Testes de integração em MariaDB/hospedagem ainda necessários.

---

# Meu Site v1.0.98 — Links para visualizar páginas de aplicativos

- Nos cartões de páginas legais, **Ver página** abre o endereço real em nova aba somente se aplicativo e página estiverem publicados.
- Enquanto houver rascunho, **Prévia** abre o editor autenticado com o modal de visualização já aberto; a URL pública permanece protegida.
- Editar, Prévia/Ver página e Copiar URL organizados em uma linha responsiva no celular.
- Sem alteração de banco (schema 37) ou backup (v12). Instalações ainda no schema 36 exigem migração assistida.

---

# Meu Site v1.0.97 — Diagnóstico de atualizações e modelos Uai Cortes

- Pop-ups GitHub e Enviar ZIP centralizados com altura proporcional ao conteúdo no celular.
- Diagnóstico controlado de consulta GitHub (DNS, TLS, cURL, timeout, status HTTP e falhas de armazenamento), com orientações e sem vazamento de erros brutos.
- Envio manual pode fazer simulação segura do ZIP antes da instalação. Validação de formato, assinatura opcional, hash, versão, schema e permissões; a simulação não grava conteúdo nem executa migrações.
- Modelos editáveis de apresentação, Política de Privacidade e Termos para Uai Cortes. Rascunhos novos recebem exemplos; os já existentes recebem botão de importação conservador, sem substituir edição prévia.
- Schema **37** preservado. Não desabilita o bloqueio para atualização da revisão 36→37: essa migração exige backup externo e instalação assistida.
- Testes de diagnósticos e modelos adicionados. PHP/MySQL real e upload na hospedagem ainda exigem validação; revisão jurídica dos modelos obrigatória.

---

# Meu Site v1.0.96 — Aplicativos e Páginas Legais

- Módulo integrado ao menu administrativo para gerenciar diversos aplicativos e suas páginas de apresentação, privacidade, termos e páginas extras, com edição livre do conteúdo no banco.
- Slugs permanentes e URLs públicas amigáveis; alterações explícitas de slug geram histórico e redirecionamentos 301.
- Formatação Markdown restrita com escaping de HTML no servidor. Prévia, descrição SEO, rascunhos isolados, publicações versionadas e restauração para rascunho.
- Uai Cortes cadastrado como **rascunho**, contendo instruções editáveis e não declarações de privacidade presumidas.
- Quatro novas tabelas, **schema 37** e **backup lógico v12**, com compatibilidade de leitura/restauração dos backups antigos.
- **IMPORTANTE:** como há migração do banco, não instale pela função normal de atualização da versão 1.0.95/schema 36. É necessária **instalação assistida**, após backup externo completo, seguindo `docs/APLICATIVOS_LEGAIS.md` e `docs/MIGRACAO.md`.
- Testes offline PHP/JS e integridade do ZIP executáveis localmente; o ensaio real de integração MariaDB foi acrescentado ao GitHub Actions e permanece pendente de execução neste ambiente. Publicação, SSL/rewrites e OAuth devem ser revisados em homologação; aprovação Google não é garantida.

---

# Meu Site v1.0.95 — Compatibilidade do teste PHP 8.3 no CI

- Corrigido erro fatal ao lidar com uma data inválida na renderização da lista de notícias. A data não é inventada; quando não puder ser interpretada, a interface informa “Data indisponível”.
- `tests/news_relative_date_195.php` amplia a cobertura para entradas inválidas e datas impossíveis; os testes do wizard 1.0.94 permanecem.
- Não houve migração de banco, alteração de dados, remoção de recursos nem mudança nas regras de segurança.
- O workflow anterior falhou antes da integração MariaDB/ZipArchive. Este pacote só deve ser considerado validado em CI depois de um novo run bem-sucedido.

---

# Meu Site v1.0.94 — Wizard, SEO e listas de notícias

- Botão Sair no canto direito do cabeçalho da notícia, sem importação por URL no assistente.
- Uploads de Capa/Fotos/ZIP/Anexos em botões exclusivos, sem controles globais duplicados.
- Ferramentas SEO com quatro ícones e pop-ups para slug, geração de descrição, edição de título/descrição e diagnóstico com correção.
- O diagnóstico permite alterar e aplicar ajustes ao rascunho, com botão Salvar à direita; Cancelar não modifica os campos da notícia.
- Notícias cadastradas e publicações recentes compartilham cartões responsivos, ícone de lixeira, categoria e tempo relativo conforme data real.
- Sem alteração do schema 36; testes na hospedagem e no Android pendentes.

---

# Meu Site v1.0.93 — Pop-ups padronizados no painel

- Todas as janelas de conteúdo principais do painel passam a seguir o mesmo padrão visual, usando um componente reutilizável.
- Corrigidas as janelas gigantes de Google Analytics 4, AdSense, Search Console e PageSpeed no celular: agora centralizadas, com margens, limite de altura e rolagem interna, sem deslocamento horizontal por campos compridos.
- PageSpeed agora mostra Celular e Computador em abas próprias; alternar abas não consulta a API e não altera resultados existentes.
- Janelas de atualização, publicidade, avisos e editor recebem os mesmos cantos, cores, dimensões e botão de fechar, preservando seus formulários e operações.
- Verificações de segurança, confirmação de senha, CSRF e progresso real de atualização permanecem. Banco schema 36 inalterado.
- Testes locais e simulação de layout em Chromium executados. Necessário validar em dispositivo Android real e hospedagem antes de considerar a implantação aprovada.

---

# Meu Site v1.0.92 — Atualizações pelo GitHub em janela única

- Tela principal mais enxuta: **Visualizar** e **Enviar ZIP**, sem link Releases nem cartões de origem/servidor.
- **Visualizar** consulta versões e novidades sob demanda; se houver release instalável, apresenta confirmação e senha na própria janela.
- Durante a instalação, mostra as etapas confirmadas pelo PHP (download, validação, vistoria, backup e aplicação), sem percentual artificial. Ao confirmar o sucesso: **Atualização concluída** e botão **Atualizar página**.
- Configuração do repositório, vistoria, importação manual, segurança, backups e recuperação mantidos.
- Código e testes locais atualizados. Schema 36 preservado. **Hospedagem real não testada nesta entrega**.

---

# Meu Site 1.0.91 — Wizard editorial compacto

O cadastro de notícia foi refinado para uso em telas móveis: etapas recolhíveis, editor com uma linha de ferramentas, área de fotos em duas grades de três botões, sem guia no topo e com opções avançadas reduzidas.

**Rascunho:** aviso de recuperação com Recuperar/Ignorar e supressão durante a mesma sessão de aba após decisão. Esta ação não exclui o texto salvo no servidor; arquivos locais ainda não entram no autosave.

**Classificação:** administradores autorizados a gerenciar taxonomia podem cadastrar categorias e fontes em um diálogo sem sair do wizard. A fonte principal é única, mantendo o banco no schema 36; associações de múltiplas fontes à mesma notícia ainda dependem de desenvolvimento adicional.

**SEO:** barra de diagnóstico editorial 0-100, verificação detalhada em diálogo, ajuste manual opcional e prévia aproximada. Sem chamada à API Gemini ou garantia de exibição pelo Google.

**Anexos:** aceita documentos, imagens, áudio, MP4 e ZIP sob lista de formatos com verificação de MIME e estrutura. Arquivos executáveis/ativos não são aceitos. Uploads permanecem fora da raiz pública, com download forçado; backups completos incluem novos formatos. O servidor deve ter ZipArchive para ZIP/Office.

**Instalação:** atualização do PHP/CSS/JS sem migração de banco; faça backup do banco e das mídias antes de instalar. A importação de anexos e os diálogos ainda precisam de teste integrado na hospedagem e navegador Android. Gere o ZIP de atualização pelo script oficial e não instale o ZIP de código-fonte na hospedagem.
