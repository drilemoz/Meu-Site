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
