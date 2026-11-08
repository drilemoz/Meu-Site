# Meu Site — Portal Iguatama Online

**Versão atual do código-fonte: 1.0.120** · PHP 8.1+ · MySQL/MariaDB · schema 37.

Portal de notícias locais com editor, categorias, autores/fontes, mídias, horários de ônibus, eleições, publicidade, métricas e administração com segurança, backups e atualizador de código. O sistema publicado pode estar numa versão anterior até o administrador instalar o pacote na hospedagem.

## Estrutura

- `public/`: arquivos públicos PHP, CSS, JS e recursos; a raiz web deve apontar aqui (ou equivalente configurado conforme [DEPLOY](docs/DEPLOY.md)).
- `app/`: lógica de aplicação, autorização, templates, schema e módulos.
- `storage/`: dados privados em produção (configuração, logs, backups e credenciais); no fonte somente arquivos vazios `.gitkeep`.
- `bin/`: rotinas CLI, como agendamento de backup do **banco**.
- `tools/`: geradores de CSS/editor, pacotes e auditoria.
- `tests/`: regressões e fixture de ZIP deliberadamente inválido.
- `.github/workflows/integration-mariadb.yml`: integração com MariaDB e PHP zip no CI.

**Identidade do projeto:** exclusivamente web/PHP, repositório `drilemoz/Meu-Site`; não incluir arquivos Android, Uai Cortes, Kotlin, Gradle ou APK. `github-manager.json` e `app_identity.json` acompanham `VERSION`.

## Versão 1.0.120 — Leitura sem aviso fixo do GA4

- Retirado o cartão flutuante de autorização Google Analytics que cobria notícias e rodapé no celular. A autorização **não aparece automaticamente** em nenhuma página.
- No rodapé, **Preferências de métricas** direciona à seção correspondente da **Política de Privacidade**, com escolha opcional para autorizar ou recusar o GA4. Sem escolha, a tag externa não é carregada.
- Consentimentos antigos válidos permanecem respeitados; opt-out, GPC/DNT e revogação continuam bloqueando o envio externo. A coleta interna do próprio portal não foi modificada.
- Não há mudanças na propriedade/ID GA4, consultas da API, schema 37, banco ou backups. O relatório Tempo real deve ser verificado depois que um navegador autorizar; remover o aviso não faz o GA4 coletar dados automaticamente.

## Versão 1.0.119 — Clareza no Google Analytics 4

- Reorganizada a janela GA4 para separar **rastreamento público com consentimento** de **consultas opcionais pela Analytics Data API**. O formulário identifica a finalidade de cada ID, e o estado visual não afirma coleta confirmada quando só há valores salvos.
- Incluído acesso à confirmação de coleta pelo relatório **Tempo real** no Google Analytics, sem exigir credencial JSON. A seção de relatórios internos ao painel inicia recolhida e explica quando é necessária uma conta de serviço.
- Botões de API realmente desabilitados deixam de parecer ativos; UI e estilos mobile-first alinhados. Formulários e rotas continuam protegidos, sem alterações na coleta, consentimento, consultas, schema 37 ou backups.
- Conferência por testes de código/HTML e simulação das respostas da API. A coleta real na propriedade 563104989 continua pendente de verificação com acesso ao Google Analytics e consentimento de um visitante.

## Versão 1.0.118 — Correção do teste integrado de ZIP adulterado

- Os logs do workflow MariaDB #33 confirmaram instalação, backup e rollback em sandbox; a falha restante era do teste de adulteração, que mudava também o tamanho do arquivo e exigia, indevidamente, erro SHA-256.
- O teste `updater_mariadb_217.php` agora cobre duas alterações independentes: tamanho divergente, rejeitado antes da extração, e conteúdo divergente de mesmo tamanho, rejeitado por SHA-256.
- Reforçado o teste de contrato offline e documentado o resultado real do CI. Motor do atualizador, schema 37, backup v12 e aplicações públicas sem alteração funcional. Validar novamente o workflow após publicar o código.

## Versão 1.0.117 — Ensaio integrado do ciclo de atualização

- Novo `tests/integration/updater_mariadb_217.php` exercita, em CI com MariaDB e ZipArchive reais, instalação do ZIP v2, criação de backup lógico, verificação de integridade, rollback manual e rejeição de ZIP adulterado, **somente em diretório efêmero**.
- O workflow existente agora executa o cenário após os demais testes de integração com o banco `igua_ci_backup134`. A regressão offline `tests/updater_ci_contract_217.php` verifica a configuração de segurança, a ordem e o isolamento.
- O teste não executa SQL de restauração nem altera o portal publicado. A execução real depende do GitHub Actions; localmente, PHP sem `pdo_mysql` e `ZipArchive` só permite lint e verificações estáticas. Ver [Integração do atualizador 1.0.117](docs/INTEGRACAO_ATUALIZADOR_117.md).
- Schema 37, backup v12, formato ZIP v2 e código do atualizador inalterados. Não afirmar homologação no Plesk apenas com um CI aprovado.

## Versão 1.0.116 — Rollback que respeita mudanças externas

- O atualizador verifica **os arquivos de destino** antes de reverter uma atualização interrompida, além de conferir snapshots; só aceita o conteúdo antigo, o novo ou a ausência esperada de arquivos recém-criados.
- Se alguém editar um arquivo durante a interrupção, a restauração automática é bloqueada antes da primeira troca, em vez de sobrescrever essa edição. A recuperação de arquivos parcialmente revertidos continua suportada.
- Adicionado `tests/updater_foreign_changes_216.php` com reprodução do defeito e testes de retomada, sem usar dados reais. Consulte [Validação do rollback 1.0.116](docs/ROLLBACK_EXTERNO_116.md).
- Nenhuma alteração no schema 37, backup v12 ou formatos ZIP. Homologação do Plesk com MariaDB e ZipArchive continua necessária.

## Versão 1.0.115 — Recuperação segura do atualizador

- A restauração de código verifica **todas** as cópias de segurança antes de alterar o primeiro arquivo. Ausência, symlink ou adulteração bloqueiam a operação sem sobrescrever destinos.
- O rollback manual ativa o modo de manutenção e grava `recovery_needed` antes das substituições; uma recuperação interrompida pode ser retomada. O marcador só é removido quando o processo termina e o journal é registrado como `rolled_back`.
- `VERSION` segue como último arquivo da atualização. Falhas para remover o marcador de manutenção são relatadas; operações não são dadas como bem-sucedidas apenas porque os arquivos foram trocados.
- Novo `tests/updater_failure_215.php` faz injeção de falhas e valida recuperação **em diretório temporário, sem MariaDB ou hospedagem real**. Consulte [Falhas do atualizador 1.0.115](docs/RECUPERACAO_FALHAS_115.md).
- Schema 37, formato ZIP v2 e backup v12 inalterados. A instalação na hospedagem exige backup externo e validação após deploy.

## Versão 1.0.114 — Segurança de backup e recuperação

- `list_backups()` e `backup_path()` agora recusam links simbólicos em arquivos e na pasta de backups, mantendo acesso normal aos backups regulares com nome válido.
- Nova suíte `tests/recovery_safety_214.php` verifica GZIP e estruturas inválidas, administradores ativos, limites do atualizador e tentativas de usar links simbólicos/caminhos indevidos.
- Documento [Segurança de recuperação 1.0.114](docs/SEGURANCA_RECUPERACAO_114.md) distingue testes offline, CI com MariaDB real isolado e cenários que ainda exigem homologação.
- **Sem alteração no formato de backup v12, no schema 37, nas credenciais, nos arquivos privados ou na restauração transacional.** A atualização é somente de código, não instala nem restaura nada automaticamente.

## Versão 1.0.113 — Refatoração do controlador do Wizard de Notícias

- `app/admin_controllers/news.php` foi reduzido a uma entrada de cinco inclusões fixas: taxonomia, Gemini, rascunhos/importação/revisões, salvamento transacional e ações de publicação/duplicação/exclusão.
- A lógica original é reconstruída byte a byte pelos testes, incluindo permissões, CSRF no ponto de entrada, upload de arquivos/ZIP, revisão, rollback e tratamento de commit incerto. O JavaScript modular e a interface do wizard permanecem sem alterações.
- Acrescentados teste de equivalência `tests/news_controller_refactor_213.php` e leitura dos fragmentos para regressões antigas. Não altera dados existentes, permissões, formulários, campos, schema 37 ou backup v12. A publicação no Plesk permanece sob responsabilidade do administrador.

## Versão 1.0.112 — Refatoração de Integrações e APIs

- A visualização administrativa `app/admin_views/routes/integrations.php` foi dividida em nove componentes de apresentação, separados por GA4, AdSense, Search Console, PageSpeed, Gemini, diagnóstico, inspeção de páginas, catálogo e janelas.
- Preservados o HTML original, os formulários, as URLs/ações POST, identificadores do DOM, JavaScript, credenciais no armazenamento privado, CSRF, ACL e as chamadas sob demanda. Nenhuma mudança visual pretendida.
- Os testes que conferem o código-fonte da view agora recompõem os componentes; o teste `tests/integrations_refactor_212.php` compara o template reconstituído com a versão original por SHA-256.
- Schema 37, backup v12, registros e configurações existentes permanecem inalterados. A integração real na hospedagem ainda precisa ser verificada após instalar.

## Versão 1.0.111 — Refatoração interna de métricas e SEO

- Rotas, identificação de automações, privacidade (GPC/DNT) e normalização de acessos foram isoladas em `app/analytics_helpers.php`, mantendo `app/analytics.php` como interface de entrada e responsável por SQL, séries e relatórios. Não modifica classificação, armazenamento, tabelas, contadores nem dados existentes.
- A lógica das prévias de SEO foi organizada em três funções internas (títulos/descrições, imagem de compartilhamento e favicon), preservando os controles, mensagens, atualizações e cancelamento de pop-ups. Nenhuma mudança visual planejada.
- Inclusão de testes de compatibilidade PHP e testes JavaScript executados com DOM simulado. Banco schema 37, backup v12 e autorização existentes preservados; sem migração.

## Versão 1.0.110 — SEO por página e métricas sem certeza artificial

- SEO: a imagem de redes sociais e o favicon agora são selecionados na biblioteca, com miniatura e botões de troca/limpeza. Os caminhos continuam armazenados internamente, mas não aparecem como texto no pop-up avançado.
- Cada página fixa (Ônibus, Sobre, Contato, Anuncie, Privacidade, Termos) aceita metadescrição individual opcional. O conteúdo público usa a descrição específica ou mantém o fallback existente. A descrição geral permanece na página inicial e deixa de ser truncada silenciosamente de 320 para 260 caracteres.
- Métricas: painéis distinguem visualizações não classificadas como automação de pessoas comprovadas, que não podem ser identificadas por este sistema. Acrescentados novos sinais de User-Agent e um detector conservador de rajadas (30 ou mais requisições no mesmo minuto, por identificador diário pseudonimizado). Nenhuma visita histórica é reclassificada.
- Rotas amigáveis de notícias e categorias e URLs públicas de aplicativos agora podem aparecer individualmente no relatório quando a solicitação chega por rota validada; consultas arbitrárias não são armazenadas. O armazenamento continua sem IP bruto ou User-Agent bruto; respeita GPC/DNT.
- Schema 37 e backup v12 preservados, sem migração. A classificação é heurística; não comprova que um visitante é humano nem que um bot é legítimo.

## Versão 1.0.109 — Busca de ônibus, títulos das páginas e favicon

- Na página pública de ônibus, o formulário grande foi recolhido em **Buscar ônibus**, uma lupa expansível acessível via elemento `<details>`; buscas com destino ou origem personalizada começam abertas. Nenhum horário, linha ou regra foi alterado.
- Configurações → SEO: **Títulos das páginas** permite definir o `<title>` completo da página inicial e das páginas fixas (ônibus, sobre, contato, anuncie, privacidade, termos). Em branco, mantém o título padrão existente. Artigos, categorias e páginas de aplicativos mantêm seus próprios editores/nomes.
- Favicon configurável pela biblioteca ou URL HTTPS segura; ícone SVG padrão inspirado na identidade visual do portal. Configuração salva no banco de settings, sem schema novo.
- Pop-ups SEO com cabeçalho e ações fixas, corpo de rolagem independente, campos de URL dentro dos limites no Android. Nenhuma alteração no banco ou indexação automática.

## Versão 1.0.108 — Sitemap compacto e link do desenvolvedor

- A Central de Sitemap mostra os indicadores e a listagem de URLs na página principal; verificações, IndexNow e Bing abrem em pop-ups compactos, com formulários originais, CSRF e diagnósticos preservados.
- A lista mostra caminho curto e pagina 12 resultados por vez; as ações Abrir, Copiar e Testar HTTP continuam usando a URL completa original do banco.
- O contato do desenvolvedor ganhou o link externo HTTPS `https://adriedsonlemos.vercel.app`, sem mudar os canais de e-mail ou telefone.
- Schema 37 preservado, nenhuma migração nem alteração de conteúdo da hospedagem. As consultas externas são voluntárias.

## Versão 1.0.107 — Bing verificado por XML e ônibus responsivos

- O arquivo de verificação **BingSiteAuth.xml** fornecido pelo administrador está incluído sem alterações em `public/` e deve ficar acessível em `https://iguatama.online/BingSiteAuth.xml` após instalar o pacote, desde que o domínio aponte para a raiz pública correta. A Central de Sitemap ganhou link para conferência, mas a verificação é finalizada no próprio Bing Webmaster Tools.
- O menu lateral mostra **Páginas** no lugar do nome longo. O módulo e todas as permissões permanecem os mesmos.
- Em **Ônibus**, a tabela horizontal foi substituída por cartões responsivos com resumo de empresa, paradas, horários, status e proteção. Um filtro por cidade de origem permite alternar a lista; **Editar percurso** abre o mesmo assistente para mudar a origem com revisão das paradas e dos horários.
- Não modifica banco de dados, viagens ou conteúdo; mantém schema 37 e backup v12. Verifique a versão na hospedagem antes de solicitar a validação ao Bing.


## Versão 1.0.106 — Importação Teixeira corrigida e Bing Webmaster

- A confirmação da tabela Teixeira agora submete de fato o formulário com CSRF e aceite, em vez de tentar clicar em `<form>`. Compatibilidade também corrigida para confirmações em outros formulários. Após a instalação, clicar novamente em **Cadastrar tabela Teixeira** para gravar as linhas; não há importação automática no deploy.
- Bing Webmaster Tools integrado à **Central de Sitemap e Indexação**, com chave de API armazenada em `storage/integrations/`, administrada apenas com senha; botão para consultar a propriedade HTTPS verificada e os sitemaps conhecidos pelo Bing. Resultados resumidos só na sessão. Reutiliza IndexNow para envio de URLs, sem duplicar notificações.
- Teste PHP offline para chave, domínio, erros e respostas; teste de navegador Chromium para confirmação Teixeira e novo teste MariaDB incluído no CI. Schema 37/backup v12 preservados. O teste real da API Bing depende de chave e hospedagem.

## Versão 1.0.104 — Ônibus Teixeira e teste Gemini

- Na seção **Ônibus**, o administrador pode conferir e cadastrar a tabela fotografada da Teixeira (14/09/2026): 10 trajetos, 31 grupos de partidas, rodoviárias como pontos e nenhuma duração ou tarifa inventadas. A importação é explícita, transacional e não substitui linhas existentes. Atenção: feriados em dias úteis não são alterados automaticamente pela máscara semanal do calendário.
- Gemini está na aba **Google**, não no Catálogo. Salvar configuração e Testar API estão na mesma linha; a remoção ganhou bloco separado com senha. Testar abre pop-up de resultado e código HTTP real, com orientações para 503/429/404, sem revelar credenciais.
- A instalação dos arquivos não grava automaticamente horários no banco. Depois de atualizar, use **Ônibus → Cadastrar tabela Teixeira** e confirme. Sem migração, schema 37.

## Versão 1.0.103 — Google Gemini opcional

- **Configurações → Integrações e APIs → Google → Google Gemini** agora permite cadastrar chave de API em armazenamento privado, configurar modelo, ativar/desativar, remover e executar um **Teste da API** real sob demanda. A chave não aparece no HTML nem no código-fonte.
- No wizard de notícia os botões Gemini exibem apenas o símbolo **✦**, com acessibilidade por nome e pop-up de revisão da sugestão. O Gemini pode sugerir manchete, resumo, revisão da matéria e descrição SEO, sem preencher ou publicar automaticamente.
- O Gemini envia texto à API somente depois do clique, com limite de tamanho e frequência; respostas devem ser conferidas, pois a IA pode errar. Modelos, planos, cotas e custos dependem do Google e podem mudar. Sem schema novo, mantendo backup v12.

## Versão 1.0.102 — Central visual de SEO e Compartilhamento

- Configurações gerais preservadas; SEO ganha prévias locais em abas Google e WhatsApp/Facebook, com atualização enquanto se digita.
- Descrição, título, URL pública, imagem e indexação são editados em diálogos centralizados, sem painel expansível, reutilizando o formulário protegido por CSRF.
- Diagnóstico local não realiza requisições remotas nem promete indexação; URL social inválida ou sem arquivo aparece como pendência.
- Mudar o interruptor de indexação de ativo para desativado exige confirmação antes de salvar. A imagem escolhida na biblioteca atualiza a prévia.
- Banco schema 37 e backup v12 preservados; testes reais em hospedagem ainda necessários.

## Versão 1.0.101 — Cadastro de categorias/fontes e destaques

- Corrigidas as ações AJAX `news_create_category` e `news_create_source` na lista central de rotas administrativas: criação dentro do wizard deixa de retornar HTTP 404. Mantidos login, CSRF, permissão `manage_taxonomy`, SQL preparado e respostas JSON seguras em erros de validação.
- O destaque principal da home exige `featured=1`; notícias publicadas sem marcação não entram automaticamente como destaque. Quando não há nenhuma marcada, a área apresenta chamada institucional neutra, sem promover outra reportagem por engano.
- O plano EXPLAIN e testes automatizados foram atualizados. Não altera registros existentes, schema 37, backup v12 ou outras áreas do portal.

## Versão 1.0.100 — Central de Sitemap e Indexação

- Nova seção **Configurações → Sitemap e Indexação**: resumo de URLs elegíveis, busca, filtros por tipo, páginas de resultados e datas `lastmod` fundamentadas em registros publicados. Cópia, abertura e teste HTTP individual de URL.
- O sitemap público e a lista administrativa usam a mesma fonte de dados, sem rascunhos, agendamentos ou notícias de demonstração. O XML respeita 50.000 URLs e o painel alerta quando o volume ultrapassar esse limite.
- Diagnóstico local de domínio HTTPS, indexação, diretiva robots.txt e correspondência do ads.txt AdSense. Botão separado faz testes HTTP pelo navegador sob demanda, sem criar chamadas para a API Google e sem presumir indexação.
- `robots.txt` anuncia `https://iguatama.online/sitemap.php`. `ads.txt` da hospedagem não é alterado pela atualização. Nenhuma mudança de schema 37 ou de backup v12. Consultar [docs/SITEMAP_INDEXACAO.md](docs/SITEMAP_INDEXACAO.md).

## Versão 1.0.99 — Páginas legais, publicação e datas automáticas

- **Última atualização** das páginas públicas vem da data real de criação da revisão publicada no MariaDB. Salvar rascunho não altera a data pública. Na edição, a revisão ativa e a data são informadas automaticamente, sem campos manuais.
- **Aplicativos:** cartão com Páginas, Copiar e Publicar; Informações em tela própria. Na tela de páginas, Editar, Visualizar/Prévia, Copiar e Publicar dividem uma linha no celular.
- **Publicar tudo:** publicação transacional de apresentação, privacidade e termos. Valida texto, dados de responsável e e-mail antes de gravar qualquer revisão. Reutiliza a revisão publicada quando seu conteúdo não mudou; exige confirmação explícita.
- **Uai Cortes:** rascunhos novos sem marcações PREENCHER ou datas fictícias, mantendo somente informações técnicas fundamentadas. O botão Atualizar modelos originais reconhece rascunhos *intactos* das versões anteriores, sem substituir páginas personalizadas ou publicadas. Os textos exigem revisão do responsável antes de disponibilização pública.
- **Pop-ups administrativos:** estilo compartilhado com altura intrínseca e máximo de 82dvh, centrado em telas móveis e com rolagem somente para conteúdo extenso. Sem mudança de schema (37) ou backup (v12).

## Versão 1.0.98 — Visualizar páginas de aplicativos

- Cartões de **Aplicativos e Páginas Legais** agora oferecem **Ver página** em nova aba somente quando aplicativo e página estão publicados.
- Para rascunhos, **Prévia** abre uma aba administrativa autenticada com a visualização do texto salvo, sem expor a página em `/slug` antes da publicação.
- Botões Editar, Ver página/Prévia e Copiar URL ficam lado a lado no celular. Nenhuma migração, mudança de dados ou publicação automática (schema 37 / backup v12).

## Versão 1.0.97 — Atualizações e modelos legais

- **Pop-ups compactos:** GitHub e Enviar ZIP agora usam altura do conteúdo e ficam centralizados, sem o grande painel vazio no celular.
- **Diagnóstico GitHub:** a consulta retorna orientação categorizada para problemas de cURL, DNS, TLS, tempo limite, respostas HTTP e erros na hospedagem; não revela mensagens brutas. A release v1.0.96 já foi encontrada no repositório público, mas o acesso PHP da hospedagem precisa ser verificado.
- **Diagnóstico ZIP sem instalar:** inspeciona o manifesto, revisões do banco, integridade e pré-requisitos antes de exibir a confirmação. Não exige senha para a simulação; instalar ainda exige CSRF, senha e consentimento.
- **Uai Cortes:** apresentação, política de privacidade e termos pré-preenchidos em novos rascunhos, com SEO. Para registros anteriores, botão **Carregar exemplos do Uai Cortes**, limitado a páginas intactas. Texto jurídico continua sujeito à revisão e ao bloqueio `PREENCHER:`.
- **Importante:** schema 37 e backup lógico v12 continuam inalterados. A atualização do schema 36→37 NÃO é permitida pelo instalador ZIP comum: faça instalação assistida.

## Recursos recentes e limites

- **Aplicativos e Páginas Legais (v1.0.96):** módulo para vários aplicativos com slug permanente, apresentação, Política de Privacidade, Termos, páginas adicionais, editor Markdown seguro, rascunho independente de versão pública, revisões, prévia, restauração e redirecionamentos 301 para slugs antigos. O Uai Cortes é semeado como rascunho, **sem declarações jurídicas fictícias**. Rotas públicas PHP e links para OAuth/YouTube após revisão humana. Schema 37, backup v12. **Exige migração assistida; o atualizador antigo não pode instalar uma mudança de banco.** Consulte [docs/APLICATIVOS_LEGAIS.md](docs/APLICATIVOS_LEGAIS.md).

- **Datas relativas corrigidas (v1.0.95):** o PHP 8.3 do CI falhava quando a regressão simulava uma data inválida em `news_cards.php`. A conversão agora é não-excepcional e rejeita datas malformadas ou impossíveis, exibindo “Data indisponível” sem derrubar o painel. Testes de regressão adicionados; a integração MariaDB precisa ser reexecutada no GitHub Actions. Schema 36 inalterado.

- **Wizard e cartões editoriais (v1.0.94):** o cabeçalho mantém Sair ao lado do título e a importação por URL saiu da interface do novo cadastro. Seleção de capa, galeria, ZIP e anexos sem botões nativos duplicados (mesmos inputs, com proteção de upload e limites preservados). SEO usa quatro ícones e janelas com edição temporária: cancelar descarta alterações, salvar aplica aos campos do wizard sem publicar; diagnóstico oferece correção por etapa e edição SEO local. O painel inicial e a lista de notícias compartilham cartões responsivos, ações com ícone de lixeira e categoria + data relativa no fuso America/São_Paulo. Schema 36 preservado; testar na hospedagem/Android.


- **Pop-ups unificados (v1.0.94):** novo componente visual e controlador compartilhados para janelas de integrações, atualizador, publicidade, avisos e editor; no celular, modais centralizados com margens, altura limitada e rolagem interna, sem ocupar 100% da tela. PageSpeed agora alterna Celular/Computador em abas acessíveis sem consultas automáticas. Sem alteração de banco.
- **Atualizações em janela única (v1.0.92):** botão **Visualizar** consulta GitHub sob demanda e apresenta novidades, senha/confirmação e progresso da instalação em pop-up central. Exibe etapas efetivamente registradas pelo PHP, sem porcentagem artificial. Só apresenta sucesso após resposta confirmada, com **Atualizar página**; erros, timeouts e estado ambíguo requerem conferência do histórico. Link Releases e cartões Fonte oficial/Servidor removidos da tela, mas ferramentas e verificações continuam nas opções avançadas. Sem migração do banco.


- **Wizard aprimorado (v1.0.91):** topo sem ajuda longa; rascunho recuperável mostrado uma vez por aba quando tratado, com opção Ignorar; navegação compacta; barra de edição em uma linha e ferramentas extras em menu; novos artigos sem painel de cartões antigos; botões compactos de capa/biblioteca/limpeza e envio Capa/Fotos/ZIP; cadastro de nova categoria e fonte sem abandonar o editor, respeitando `manage_taxonomy`; status SEO resumido com barra, diagnóstico no diálogo centralizado, e endereço/SEO manuais recolhidos. Ampliação de anexos para formatos comuns, com verificação de MIME/assinatura e atualização de backup/restauração. **Fontes registradas por notícia:** uma fonte principal, sem vínculo múltiplo nesta versão. Schema continua 36. Integração Gemini não ativada.**


- **Wizard de notícias (v1.0.90):** nova tela exclusiva com nove passos (título, descrição, matéria, fotos, créditos, anexos, classificação, SEO e revisão/publicação), prévias de arquivos do celular, galerias múltiplas, ZIP de imagens com inspeção/limites, capa automática se não houver outra, resumo SEO local sem reticências artificiais e escolha final de salvar, publicar ou agendar. A API Gemini ainda **não está conectada**. Autosave mantém apenas campos de texto; fotografias, ZIPs e anexos devem ser enviados ao salvar, com os limites da hospedagem. Schema permanece 36.


- **Central de Integrações em janelas (v1.0.89):** corrige o atributo quebrado das abas; GA4, AdSense, Search Console e PageSpeed abrem em diálogo centralizado no desktop e em tela inteira no celular. Formulários, credenciais, relatórios, sugestões, histórico, verificação por metatag e os botões `?` usam navegação interna com Voltar e Fechar, sem rolagem forçada da página. Guias oficiais explicam onde obter IDs, permissões e como habilitar as APIs; preserva consultas apenas sob demanda e a autorização dos provedores.

- **Atualizações:** botão **Enviar ZIP** abre diálogo central com confirmação e porcentagem real **apenas do upload**. O processamento do servidor é indeterminado. O ZIP v2 sem assinatura exige senha administrativa e manifesto SHA-256, que **não autentica a autoria**. Veja [ATUALIZADOR](docs/ATUALIZADOR.md).
- **Diagnóstico unificado (v1.0.88):** sete indicadores separados de preparação local, última operação manual e categoria genérica de erros para GA4, AdSense, Search Console (relatório e indexação), PageSpeed (celular e PC) e DNS. Histórico de até 12 operações em sessão administrativa, sem tokens ou conteúdo bruto de respostas. Abrir o painel não chama APIs e nunca garante aprovação do Google.
- **AdSense API (v1.0.87):** OAuth2 do proprietário com credenciais privadas e consulta manual de receita estimada/atividade, independente de GA4 (AdSense não aceita service account). Veja [INTEGRACOES](docs/INTEGRACOES.md).
- **Métricas:** Hoje, 24h, 7, 30 e 90 dias; GA4 opcional com consentimento. A Data API usa uma conta de serviço privada e pode consultar eventos apenas quando configurada. Veja [INTEGRACOES](docs/INTEGRACOES.md).
- **GA4 — Audiência:** relatório autorizado e manual dos últimos 28 dias completos, com usuários ativos, sessões, visualizações, páginas populares por título, canais de origem e dispositivos. O resultado da propriedade não necessariamente representa só este site; sem novos dados, não inventa números. Veja [INTEGRACOES](docs/INTEGRACOES.md).
- **Search Console:** DNS TXT e metatag são verificações locais. Os relatórios autorizados exibem cliques, impressões, CTR e principais pesquisas/páginas; **a v1.0.85 permite inspecionar individualmente o estado de indexação de notícias publicadas**, pela API oficial, na aba Páginas ou via atalho do editor. O resultado é do índice conhecido pelo Google, **não** teste ao vivo nem pedido de indexação. Veja [INTEGRACOES](docs/INTEGRACOES.md).
- **PageSpeed Insights (v1.0.83):** análises Lighthouse **separadas para celular e computador**, sob demanda e restritas à URL HTTPS oficial, sem chave obrigatória para uso eventual. Notas, horários, métricas e intervalos de consulta independentes por dispositivo, com cache temporário na sessão. Não são dados reais de visitantes. A Central mantém os atalhos e a cópia de identificadores públicos.
- **Segurança:** histórico e bloqueios administrativos parciais pelo PHP; não substitui logs do servidor ou WAF.
- **Releases automáticas:** envios para `main` via GitHub Manager passam pelos testes MariaDB/PHP/JS. Aprovados, o workflow gera e publica `Atualizacao-vX.Y.Z.zip` em GitHub Releases; uma versão já publicada não é sobrescrita. Veja [TESTES_CI](docs/TESTES_CI.md) e [ATUALIZADOR](docs/ATUALIZADOR.md).
- **CI MariaDB:** corrigido na 1.0.77 o teste de cookie de login persistente, simulando HTTPS e preservando headers. A aplicação continua rejeitando cookies persistentes sob HTTP remoto. Na 1.0.78, o teste eleitoral passou a importar o módulo de migrações. Na 1.0.79, o teste de índices importa Publicidade, Editor e Métricas antes de simular a migração SQL 35→36, com preflight anterior ao DDL.
- **Backup:** a operação **Gerar backup agora** salva **somente o banco**; **Baixar pacote completo ZIP** reúne banco e mídias **referenciadas no banco**. Nenhuma das duas opções é backup integral da hospedagem. Veja [BACKUP](docs/BACKUP.md).

## Instalação e atualização

1. Preparar PHP 8.1+, PDO MySQL, MariaDB/MySQL e HTTPS. Instalar `phpmailer/phpmailer` conforme `composer.lock` quando a hospedagem necessitar do SMTP; não publicar credenciais.
2. Publicar o projeto fora da raiz pública, expondo somente `public/`/equivalente, mantendo `storage/` privado e persistente.
3. **Atualizações de código sem migração de schema** usam `Atualizacao-vX.Y.Z.zip` no painel. A **primeira atualização para 1.0.96 (schema 37)** é uma exceção: requer **instalação assistida do código por SFTP/gerenciador**, nunca o botão de atualização do schema 36. O ZIP `Meu-Site-vX.Y.Z-fonte-completo.zip` serve para GitHub e desenvolvimento. Veja [a migração assistida](docs/MIGRACAO.md).
4. Antes de atualizar, gerar backup externo **do banco e dos arquivos**. Em caso de erro de rede, conferir a versão instalada antes de reenviar.

## Desenvolvimento e verificação

```sh
for f in tests/*.php; do php "$f" || exit 1; done
for f in tests/*.js; do node "$f" || exit 1; done
python3 tools/build_css.py --check
python3 tools/build_news_editor.py --check
python3 tools/audit_project.py
python3 tools/build_update_package.py --output /tmp/Atualizacao-v1.0.103.zip
python3 tools/build_source_package.py --output /tmp/Meu-Site-v1.0.103-fonte-completo.zip
```

Para testes que exigem o MariaDB real, `pdo_mysql`, `ZipArchive` e para a instalação em navegador Android, utilize ambiente compatível. Confira [TESTES_CI](docs/TESTES_CI.md), [documentação atual](docs/INDICE.md) e [auditoria 1.0.77](docs/AUDITORIA_176.md).


## Integração IndexNow — v1.0.105

Na Central de Sitemap e Indexação, o administrador pode gerar uma chave de domínio com arquivo público e enviar manualmente até dez URLs de notícias ou páginas legais que já constem do sitemap. A configuração é registrada fora da pasta pública, a API é chamada apenas por clique, com CSRF, ACL e intervalo entre envios. O Google não participa do IndexNow. A chamada HTTP 200/202 indica recebimento, não indexação. A hospedagem deve permitir criar o arquivo TXT na raiz pública. Nenhuma notificação é disparada ao publicar sem ação administrativa.
