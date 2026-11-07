# Guia para futuras modificações do Meu Site

1. Preservar a identidade visual aprovada: azul, branco, fontes legíveis, hero, mais lidas, chips e disposição responsiva desktop/mobile.
2. Preservar a identidade configurável do portal (fallback **Meu Site**); não fixar a marca em novos componentes.
3. Usar PHP 8.1+ e MySQL/MariaDB com consultas preparadas. Manter instalador de primeira execução e migrações de esquema quando necessário.
4. Nenhuma mudança deve comprometer backup, restauração e as fotografias enviadas. Evoluir o formato de backup de forma compatível ou incluir migração explícita.
5. Nunca versionar `storage/config.php`, segredos, arquivos de backup ou imagens privadas dos usuários.
6. Todas as matérias fictícias devem continuar marcadas como demonstração; não publicar dados inventados como notícias reais.
7. A cada release, atualizar `VERSION`, `CHANGELOG.md`, documentação afetada e executar `php -l` e `php tests/smoke.php`.
8. Preservar as três etapas do instalador e os diagnósticos de conexão, sem revelar credenciais nas mensagens e sem destruir bases existentes. A etapa 2 oferece provisionamento local restrito ou modo manual; a etapa 3 oferece instalação nova ou restauração do backup IguaNews apenas em banco vazio.
9. Não presumir que Vercel tenha filesystem permanente, suporte PHP oficial ou MySQL embutido.

10. No modo automático, limitar criação de contas MySQL a hosts locais e navegador localhost, com concessões restritas ao banco criado. Não guardar senha administrativa no projeto.

11. Na próxima versão, manter menu Notícias consolidado, funções admin/editor/redator, assinatura editorial e fontes reutilizáveis. Ao alterar o banco, conservar restauração de backups v1 e v2 e impedir perda do último administrador ativo.
12. SEO deve permanecer noindex até ativação explícita e não indexar acervo demonstrativo. Nunca montar URL canônica a partir de Host HTTP não confiável; exigir endereço base HTTPS configurado.

13. No módulo ônibus, cadastrar somente horários programados confirmados, jamais simulá-los como reais. Preservar sentido da rota, fuso America/Sao_Paulo, rotas intermediárias e avisos de feriados/atrasos. Backup v3 inclui linhas/paradas/partidas e deve continuar aceitando v1/v2.

14. Em integrações DER/ANTT, nunca sobrescrever linha `sync_mode=fixed`; aprovação humana antes de publicar; offsets desconhecidos são NULL; não inferir horários intermediários. Backup v4 inclui a fila de revisão.

15. A partir de 1.0.7, preservar o cadastro de partidas em wizard (sem formulário extenso), a origem pública padrão da cidade configurada, a navegação inferior Ônibus no lugar de Mais e a tarifa opcional do percurso integral. O formato de backup v5 inclui tarifa e deve aceitar v1–v4.
16. A partir da 1.0.8, preservar links institucionais e formulário com SMTP sem exibição do e-mail no HTML público; nunca guardar senhas SMTP no repositório.
17. A busca de cidades usa o IBGE somente para autocomplete. Municípios existentes sem linhas cadastradas não devem gerar horários fictícios. Cache privado em storage/.
18. Duração de ônibus é derivada da diferença entre horários cadastrados e nunca estimada quando o destino não tem minuto oficial.


### Publicidade 1.0.9
Menu Publicidade: ativações geral e por espaço, anúncios locais com datas, AdSense com ID de editor e bloco, e adaptadores de redes externas planejados (não habilitados sem desenvolvimento específico). Nenhuma publicação de anúncios por padrão. Consulte docs/PUBLICIDADE.md.

19. Publicidade sempre desativada no primeiro acesso; código HTML/JS de redes externas só pode ser integrado via adaptador auditado, nunca vindo cru de formulários. O arquivo ads.txt requer ID real, GDPR/LGPD e políticas devem ser revisados antes da produção. Backups v6 preservam anúncios e restaurações antigas.

20. A partir de 1.0.10, manter a compatibilidade de matérias legadas e backups v1-v7; importar apenas metadados de URLs externas com revisão editorial, proteção SSRF e sem cópia de texto integral. Anexos sempre fora de public/. Galerias exigem legenda/crédito editáveis e pacotes de migração quando ZipArchive existir.

21. A partir da 1.0.11, métricas são próprias, não expõem IPs e exigem limpeza em 90 dias. A localização requer GeoLite2 offline, não inventar cidade/país sem base local, e separar bots por heurística. Preservar o suporte a opt-out e GPC/DNT. Backup v8 inclui analytics_events e aceita versões anteriores.

22. A partir da 1.0.12, preservar o cadastro simples com os campos básicos em primeiro plano e funções avançadas opcionais. O guia está em docs/GUIA_PREENCHIMENTO_NOTICIAS.md e no PDF público; exemplos do editor são fictícios, inseridos apenas mediante confirmação por senha e backup, não substituem dados próprios e devem continuar marcados `is_demo=1` mesmo após edição. Backup v8 e banco inalterados.

23. A partir da 1.0.13, preservar os `slug` existentes ao editar manchetes, manter listagem com busca/filtros/paginação respeitando papéis, e duplicação exclusivamente como rascunho com autorização e `is_demo` herdado. A duplicação não copia galeria/anexos. Autosave precisa de confirmação de sucesso e reenvio após falhas, sem prometer salvamento de arquivos. Banco e backups v8 inalterados.

24. A partir da 1.0.14, a Central de Saúde é exclusiva de administradores, sem segredos nos logs ou nos avisos. Monitoramento local é sob demanda, não um serviço externo de alertas. Logs técnicos ficam em `storage/logs/` e nunca devem ser versionados. Backup gerado atomicamente é validado antes da rotação; a verificação de estrutura não substitui restauração real. Formato de backup v8 e esquema SQL permanecem inalterados.

25. A partir de 1.0.15, manter a alternância modo rápido/assistente, escapar integralmente HTML fornecido no texto formatado e rejeitar arquivos da biblioteca sem validação do caminho e do tipo real. A reutilização de fotos entre matérias não deve apagar os arquivos físicos ao desvincular uma imagem. A Biblioteca exibe apenas arquivos já associados a notícias/galerias do portal, com fonte e direito de uso sob responsabilidade editorial. Banco e backup v8 permanecem inalterados.

26. A partir da 1.0.16, a rota `/admin.php?action=analytics` usa `analytics_period($_GET)`; nunca acessar `$_GET['days']` sem fallback. Manter 30 dias como período padrão e aceitar apenas 7, 30 ou 90 dias. Adicionar casos de regressão sem parâmetro, parâmetros inválidos e arrays. Banco e backup v8 inalterados.

27. A partir de 1.0.17, indicar distintamente limites de upload do PHP e do app. A otimização JS de fotos deve preservar proporção e não iniciar o submit antes de terminar; arquivos sem suporte devem gerar indicação honesta, não serem publicados silenciosamente. Banco e backup v8 inalterados.

28. A partir da 1.0.18, mídias novas são organizadas em public/uploads/AAAA/MM/; manter referências legadas, thumbnails relativas, e media_library em backup v9. Exclusão na biblioteca exige verificar vínculos a matérias, galerias e anúncios e usa lixeira lógica. Compatibilidade v1–v8 obrigatória, sem mover fotos anteriores.

29. A partir de 1.0.19, o editor mostra todas as etapas em uma página inicialmente, com assistente por etapas opcional. A edição visual mantém sincronização com body em sintaxe simples validada e preserva alternativa de texto puro; uploads diretos e nomes dos campos continuam funcionais, mesmo com a biblioteca centralizada. A tela de notícias usa cards no mobile, sem romper o desktop. Sem migração e sem mudanças do backup v9.

30. A partir de 1.0.20, o usuário definiu a interface de notícias como assistente obrigatório de **três etapas** (Básico, Texto, Foto), tanto ao criar quanto ao editar. Não recriar a quarta tela “Confira e salve” nem tornar a página contínua padrão. Manter SEO/destaque em opções avançadas de Básico e botão Salvar notícia ao final de Foto. Preservar todos os campos da versão 1.0.19, backup v9 e arquivos de mídia.

31. A partir de 1.0.21, backup lógico continua v9 e pacote completo v2, sem migração de SQL. O pacote exportado permite até 300 MiB de mídias e o ZIP importado até 350 MiB totais, mantendo SHA-256 por arquivo, filtragem de caminhos e restauração com staging em disco. Não retorne ao carregamento integral de todas as mídias na RAM nem restaure o banco antes de validar e preparar os arquivos. Não presuma atomicidade entre filesystem e MySQL. Testes com MariaDB e Android continuam essenciais.

32. A partir de 1.0.22, na etapa Foto, preservar legendas e créditos digitados nos arquivos de um grupo ao selecionar anexos de outro grupo ou terminar otimização. Ao substituir a seleção do próprio grupo, não reaproveitar metadados de arquivos anteriores. A otimização de imagem deve descartar resultados de seleções anteriores e impedir que erro de uma imagem seja ocultado pela seleção de outro campo; upload não é incluído no autosave. Manter os três passos, uploads existentes, backup lógico v9 e mídias v2 sem migração SQL. Verificar `node tests/editor_media_122.js` e executar teste físico Android quando disponível.

33. A partir da versão 1.0.23, ao recuperar rascunhos, refletir fielmente checkboxes marcados e desmarcados e os valores repetidos sem trocar posições. Nunca sobrescrever ID/CSRF recuperando um rascunho nem prometer restauração de arquivos locais. Não alterar formato do payload do autosave sem migração compatível. Testar com `node tests/editor_draft_123.js` e `php tests/editor_123.php`; manter editor de três etapas, banco e backups v9/mídias v2.

34. Desde a 1.0.24, manter possibilidade de personalizar `url_slug` em SEO avançado, sem modificar URLs estáveis quando só a manchete mudar. Slugs históricos de matérias são exclusivos e redirecionados com HTTP 301 para o endereço atual, somente quando publicados. Nunca eliminar a tabela `article_slug_redirects` nas migrações. URLs limpas são opcionais (desativadas por padrão); manter queries legadas e instruções de roteamento para PHP CLI e Apache. Backups lógicos v10 devem aceitar v1–v9, com tabela de histórico incluída. Pacote de mídias v2 e editor Básico/Texto/Foto permanecem.

35. A partir de 1.0.25, manter a Central de SEO em Notícias → SEO, somente como auditoria editorial local e não como pontuação do Google. Preservar permissão de autores (ver apenas notícias próprias), filtros e o checklist na seção recolhida Básico → SEO e destaque; nunca impedir a publicação unicamente por nota. Usar o texto alternativo cadastrado da Biblioteca na foto de capa pública com fallback de legenda. Rejeitar caminhos escapados suspeitos no router do PHP CLI. Executar `php tests/seo_125.php`, `node tests/seo_125.js` e `python tests/seo_http_125.py` quando Python estiver disponível. Banco e backups lógico v10 e mídias v2 inalterados, sem quarta etapa de edição.

36. A partir da 1.0.26, o acervo público de notícias e as editorias exibem 12 matérias por página, com contagem, busca por título/resumo, filtro de período e de editoria incluindo vínculos secundários. Nunca voltar a truncar em 30/24 itens sem paginação; manter condições idênticas em `COUNT` e leitura, ordem estável por data+ID, `LIMIT/OFFSET` com `PDO::PARAM_INT`, texto escapado com `LIKE ESCAPE '!'` e parâmetros validados, sem interpolar valores externos em SQL. As URLs amigáveis de categorias devem preservar o filtro/página quando acionadas por querystring; `canonical` das páginas de editoria acompanha a página; pesquisa pública permanece não indexável. Preservar no mobile controles compactos e sem rolagem horizontal. Executar `php tests/archive_126.php` e regressão anterior. Banco e backup lógico v10 e pacote v2 inalterados.

38. A partir de 1.0.28, manter a lista administrativa de notícias otimizada para celular: títulos truncados, ações no topo e atalho de publicação rápida por ícone sem abrir a notícia. No gerenciador de mídias móvel, a busca pode ficar recolhida em ícone e o upload deve iniciar automaticamente após a seleção, mantendo fallback manual e sem rolagem horizontal.

39. Desde a 1.0.29, a Biblioteca de Mídias usa `media-state.js` antes de `media-library.js`: a requisição mais recente é a única que pode alterar a listagem, inclusive após mudança de busca/pasta ou fechamento. Upload automático mantém fila com apenas arquivos pendentes após falha parcial, sem reenviar arquivos cujo sucesso já foi confirmado. Em perda da resposta HTTP não é possível comprovar se o servidor salvou: orientar conferência manual. Não reintroduzir referências JavaScript não declaradas nem dupla personalização do input. Executar `node tests/media_state_129.js` e regressões. Backup lógico v10 e pacote v2 mantidos.

40. Desde a 1.0.30, as ações POST administrativas ficam em `app/admin_controllers/` com mapeamento explícito `post_routes.php`; `public/admin.php` permanece a entrada única de autenticação, CSRF, permissão e tratamento de falhas. Não permitir que o nome de controlador venha diretamente de GET ou POST. Preservar as URLs, o comportamento de download, os JSON de mídia e o editor de 3 etapas. Executar `php tests/admin_routes_130.php` e regressões. Backup lógico v10 e pacote de mídias v2 sem alterações.

41. Desde a 1.0.31, `public/assets/css/style.css` é o arquivo consolidado de 15 módulos em `public/assets/css/modules/`. Alterações CSS devem ser feitas no módulo e depois reconstruídas com `python3 tools/build_css.py`; executar `python3 tools/build_css.py --check` e `php tests/css_modules_131.php`. Não reordenar os módulos sem validação explícita da cascata. Manter apenas 1 recurso CSS carregado no navegador e não acrescentar `@import`/preprocessador obrigatório ao Termux. Nesta versão o CSS consolidado continua idêntico byte a byte ao da 1.0.30.

42. Desde a 1.0.32, `app/news_editor.php` é fachada de compatibilidade e carrega `app/news_editor/` com `require_once`. Novas mudanças de bloco, mídia, revisão, importação, formato e esquema devem ser feitas no respectivo módulo sem quebrar nomes/assinaturas públicos. Restauração de revisão deve preservar `articles.is_demo` e jamais converter matéria fictícia em fato real. Manter testes `tests/editor_modules_132.php` e `tests/editor_revision_132.php`, além da regressão completa. Backup lógico v10 e pacote de mídias v2 inalterados.

43. A partir da 1.0.33, o backup lógico continua v10, limitado a 40 MiB de JSON descompactado e 100.000 registros por tabela, tanto na exportação quanto na importação. Pacotes ZIP v2 novos incluem `db_sha256` e verificam nomes únicos/exatos; v1/v2 antigos sem hash permanecem aceitos. Migrações administrativas usam `igua_migrate_schema` e registram `igua_schema_revision=33` somente após sucesso; toda mudança futura de esquema deve aumentar `IGUA_SCHEMA_REVISION`. Fazer teste de restauração real em MariaDB antes de usar em produção; preservar backups preventivos e arquivos de mídia.

44. Na 1.0.34, executar `tests/integration/restore_mariadb_134.php` exclusivamente em banco MariaDB novo e vazio, com nome `igua_ci_*` e `IGUA_TEST_ALLOW_WRITE=YES_ISOLATED_DB`; nunca apontar para produção. O workflow `.github/workflows/integration-mariadb.yml` provisiona serviço temporário de CI. Não afirmar que a integração passou sem comprovação de execução real. Backup lógico v10, pacote ZIP v2 e revisão do esquema 33 são preservados.

45. Desde a 1.0.35, editar `public/assets/js/news-editor-modules/` e regenerar o único script público `public/assets/js/news-editor.js` com `python3 tools/build_news_editor.py`. Oito trechos de origem compartilham uma IIFE e dependem da ordem explícita; não carregá-los individualmente nem tratá-los como módulos ES. Antes de publicar, executar `python3 tools/build_news_editor.py --check`, `node tests/news_editor_modules_135.js` e regressões do editor, preservando Básico → Texto → Foto, SEO, autosave, capturas de mídia e proteção dos campos CSRF/ID. Nenhuma alteração de banco ou backups v10/v2 nesta versão.

46. A partir da 1.0.36, a identidade (`site_name`, `site_tagline`, `seo_default_description`) é configurada na instalação e editável em Admin → Configurações. Todo texto de marca em interface, SEO, OG, JSON-LD e rotas deve usar `igua_brand_name()`; não usar `IguaNews` como marca fixa. O rodapé `igua_developer_footer()` deve aparecer em público, painel, login e instalador, com versão de `VERSION`. Nome/contatos do desenvolvedor são constantes de `app/branding.php`, não expostas em formulários administrativos; isto não protege contra edição do código por quem tem acesso ao servidor. Sempre executar `php tests/branding_136.php`, `node tests/branding_136.js` e regenerar CSS consolidado. Banco, backups v10/v2 e revisão do esquema 33 permanecem inalterados.

47. Desde a 1.0.37, `app/news_admin_view.php` é uma fachada que carrega os oito parciais em `app/news_admin_view/` em ordem fixa. Alterações HTML devem ocorrer nos parciais e nunca devem remover IDs, nomes de campo, CSRF, escapes, permissões ou as três etapas Básico → Texto → Foto. `tests/support/news_editor_view_source.php` recompõe os parciais para os testes legados; ao alterar voluntariamente o markup, atualizar o hash de referência e os cenários dinâmicos da versão, documentando diferenças. Executar `php tests/news_admin_view_137.php` e regressões. Sem migração de esquema e backup v10/ZIP v2 inalterados.

48. Desde a 1.0.38, `app/bus_admin.php` é fachada com controle `manage_bus` obrigatório e despacha dez ações para os módulos privados `app/bus_admin/post_*.php`; as telas estão em `view_*.php`. Preservar aprovação humana, proteção de linhas fixas, URLs e HTML. Executar `php tests/bus_admin_138.php`. Em `app/schema_migrations.php`, nunca rebaixar banco de revisão superior nem aceitar marcador inválido; o esquema permanece na revisão 33 e backups v10/mídias v2. Executar `php tests/schema_safety_138.php` e testes reais de MariaDB quando disponíveis.

### 1.0.39 — renomeação e ambiente de instalação
- Projeto/pasta de entrega: `Meu Site`; identidade pública permanece configurável. Nomes gravados no banco não são substituídos automaticamente.
- Instalação: preservar e proteger `storage/backups/`, `storage/media/`, `storage/logs/` e `public/uploads/`; diretórios novos exigem `.gitkeep` no ZIP e criação segura quando ausentes.
- Identificadores técnicos `iguanews-backup`, `iguanews-media-bundle`, `IGUANEWS_*` e sessão são legados de compatibilidade: não renomear sem migração explícita.
- `upload_max_filesize` e `post_max_size` só mudam pelo PHP/Termux, não por código da aplicação.

### 1.0.40 — login persistente
- `admin_remember_tokens` tem revisão SQL 34. Tokens NÃO são exportados nos backups lógico v10 e pacote v2. Não colocar senhas, tokens completos ou hashes de credencial em logs.
- `app/auth_remember.php`: usar HTTPS ou servidor local 127.0.0.1; cookie HttpOnly/SameSite, token hash, expirando em sete dias; logout e alteração de senha revogam tokens. Não salvar senhas em `localStorage`/cookies.
- Campo de senha inicial oculto, alternância por `public/assets/js/admin-login.js`; checkbox opt-in desmarcado. CSS em módulo e regenerar consolidado.

53. Na versão 1.0.41, Resultados Eleitorais é módulo administrativo opcional e desligado inicialmente. Somente administradores podem habilitar, editar pleitos e candidaturas. Não inventar votos, situação oficial, filiação ou nome de candidato; cadastros manuais são rascunhos privados sem verificação TSE. As tabelas `elections` e `election_candidates` são incluídas no backup lógico v11, preservando restauração v1–v10; o esquema exige revisão 35. Mudanças futuras em resultados oficiais devem registrar proveniência e consulta/validação do TSE antes da publicação.

### 1.0.42 — limites de login, configurações e uploads
- Limite de login independente de sessão usa `app/auth_throttle.php`, arquivos JSON privados em `storage/security/login/` e `flock` exclusivo durante autenticação. Não usar `X-Forwarded-For` diretamente ou persistir IP/e-mail/senha; manter limite, expiração e falha fechada quando gravação indisponível. Compartilhamento de IP pode afetar múltiplos usuários.
- Configurações usam `app/settings_service.php` com validação completa antes da transação e rollback em erro. Não voltar a salvar cada campo no controlador antes de validar os demais.
- Novas mídias de notícias devem ser rastreadas e removidas apenas após rollback SQL confirmado; fotos existentes/reutilizadas nunca são apagadas. Em resultado incerto do COMMIT, preservar arquivos para evitar referências quebradas. Não confundir operação SQL com operação atômica de filesystem.
- Esquema rev. 35; backup lógico v11; pacote de mídia v2, editor de 3 etapas e módulos eleitorais preservados. Testar `php tests/refactor_142.php` e regressão integral. O CI também deve observar `public/**` e `tools/**`.

### 1.0.43 — streaming de backups e retenção conservadora
- `make_backup()` grava JSON v11 progressivamente em arquivo temporário privado; nunca acumular o banco todo em arrays em memória. Manter a transação REPEATABLE READ, limite exato de 40 MiB descompactados e 100 mil linhas por tabela, GZIP opcional e compatibilidade na leitura de v1–v11.
- `make_media_bundle()` cria ZIP v2 temporário antes de publicá-lo, e o download administrativo apaga somente o pacote gerado pela requisição. Preservar sempre o backup lógico gerado, backups manuais e pacotes ZIP históricos.
- `cleanup_backup_temporaries()` remove somente `.backup-XXXXXX`/`.bundle-XXXXXX` antigos (mais de 24h); nunca apagar arquivos por extensão genérica nem restaurar diretórios de dados ao atualizar. Rotação de diários/semanais preserva 7/4.
- Sem migração de banco: revisão 35, formato lógico 11 e pacote 2. Executar `php tests/backup_143.php` e a regressão anterior; validar exportação/restauração completa em MariaDB/ZipArchive real antes de produção.

### 1.0.44 — renderização modular no PHP
- As entradas HTTP oficiais permanecem `public/admin.php` e `public/index.php`, com rotas existentes. `app/admin_views/` e `app/public_views/` guardam templates privados, layouts, dispatchers e componentes de rotas. Não os exponha dentro de `public/` nem aceite nome de include diretamente de `$_GET`/`$_POST`.
- O fluxo de autenticação, permissões, CSRF, remember-me, requisições POST, segurança da mídia e redirecionamentos deve continuar antes da renderização; os `require` de views só usam caminhos literais do projeto.
- Testes de regressão de fonte devem consultar `tests/support/entry_view_source.php` em vez de presumir HTML em `public/*.php`. `tests/refactor_144.php` contém hashes de compatibilidade exatos com a v1.0.43: ao mudar intencionalmente telas, atualizar os contratos e acrescentar testes de comportamento, não simplesmente remover as verificações.
- Preservar esquema 35, backup v11, ZIP mídias v2, editor de três etapas, identidade, URLs antigas e todas as autorizações. Executar testes PHP/JS e validar rotas reais com MariaDB/HTTP e Android antes de produção.

### 1.0.46 — índices de notícias e planos EXPLAIN
- `IGUA_SCHEMA_REVISION=36` é obrigatório para os índices `idx_public_featured` e `idx_public_popular` em `articles`. `app/schema.php` deve criá-los para instalações novas; `igua_migrate_public_news_indexes()` cria apenas os ausentes em atualizações. MariaDB `ALTER TABLE` realiza auto-commit; o marcador de migração só muda após sucesso. Não remover os índices existentes sem EXPLAIN e testes reais.
- Preservar a correspondência de TRECHO literal da pesquisa pública e do painel. `LIKE '%termo%'` não vira FULLTEXT automaticamente; `%`, `_` e `!` devem ser escapados com `ESCAPE '!'` em ambos.
- Ferramenta `tools/news_explain.php` é CLI somente leitura e mostra estimativas, não benchmarks. Confirmar planos e tempos em MariaDB real antes de prometer melhora; testes unitários usam PDO simulado.
- CI deve executar `tests/integration/news_indexes_mariadb_146.php` apenas em banco novo reservado `igua_ci_*`, após os testes de restauração. Não executar integração de escrita em produção.
- Backups lógicos v11, ZIP v2, página de notícias, autenticação e configuração são preservados. Executar `tests/news_indexes_146.php`, regressão completa e verificadores dos bundles antes de release. Fazer backup antes da criação de índices em produção.

### 1.0.47 — execução híbrida Termux/hospedagem
- Manter `app/environment.php` e a detecção baseada no ambiente de execução PHP; nunca usar HTTP_HOST, User-Agent ou cabeçalhos proxy para conceder privilégios de banco.
- A criação automática de contas MariaDB é restrita ao Termux identificado e acesso local em loopback, validada também no POST. Na hospedagem, somente testar banco existente (sem alterar dados antes da etapa 3).
- Em `public/` como DocumentRoot ou `public_html/`, `www/`, `htdocs/` como diretórios públicos irmãos, manter `app/` e `storage/` privados; impedir instalações com DocumentRoot que exponha o projeto. Os caminhos de uploads e backups usam `IGUA_PUBLIC` e `IGUA_STORAGE`.
- As URLs absolutas existentes exigem site na raiz do domínio/subdomínio; não prometer instalação em subpasta HTTP, servidor PHP embutido público, Vercel sem armazenamento persistente ou aplicação funcional sem PDO MySQL.
- Preservar backups v1–v11, mídia ZIP v2 e esquema 36. Executar `tests/runtime_147.php`, testes PHP/JS e testes HTTP com raízes segura/insegura; integração MariaDB real continua obrigatória antes da publicação.

### 1.0.48 — pré-instalação híbrida e validação da cópia pública
- Sempre detectar arquivos essenciais na raiz web real, impedindo cópia incompleta em public_html, e verificar em Central de Saúde.
- Manter mensagens de upload precisas com margem multipart para backups de 15 MiB; nunca tratar POST grande como simples CSRF.
- Manter preflight CLI em `bin/check-environment.php` isolado do navegador, sem solicitar credenciais. Não aceitar links CLI externos ao projeto.
- Preservar esquema 36, backup JSON v11, mídias ZIP v2 e Termux loopback. Regressões: `php tests/runtime_147.php`, `php tests/runtime_148.php`, `php tests/setup_diagnostics.php`.

### 1.0.49 — upload de backups em PHP-FPM e Termux
- `public/.user.ini` define 20M/24M para PHP-FPM/CGI **apenas quando permitido** pelo provedor. Deve ser copiado para a pasta pública efetiva (ex.: `public_html/`) com os demais arquivos; **não** assumir que o PHP runtime ou o Nginx honrem esse arquivo. Apache e roteador CLI bloqueiam seu acesso HTTP; para Nginx exigir proteção de dotfiles no servidor.
- `bin/start-termux.sh` aplica parâmetros 20M/24M ao processo PHP local. O script **não** pode corrigir um servidor iniciado anteriormente; `php -i` no CLI sem flags também não comprova os valores WEB.
- Importação lógica segue estritamente limitada a 15 MiB e depende de `post_max_size` com margem; `IGUA_BACKUP_IMPORT_MAX_BYTES` é compartilhada entre app e diagnóstico. JS só alerta; PHP valida e rejeita uploads excessivos/erros, sem executar SQL quando o arquivo foi recusado.
- `tests/upload_149.php` cobre limites, arquivos PHP e bloqueio de acesso; executar regressão completa. Nenhuma migração SQL; revisão 36, backup lógico v11 e ZIP de mídias v2 permanecem.

### 1.0.50 — fallback segmentado no instalador
- Se o servidor não puder receber 15 MiB de uma só vez, usar fetch/Blob.slice com blocos pequenos, CSRF, sessão e staging privado, em vez de pedir ao usuário que altere php.ini; respeitar o limite lógico de 15 MiB.
- Não anunciar aumento de upload_max_filesize: o PHP pode continuar em 2M/8M. Sem JavaScript ou com file_uploads=Off o fallback não se aplica; mostrar restrições reais.
- Limpar arquivos temporários na conclusão ou após 1h, sem apagar backups já armazenados. Não alterar schema 36, formato JSON v11 ou mídia ZIP v2.

### 1.0.51 — recuperação segura de instalações parciais
- Não chamar instalação incompleta de banco vazio. Sincronizar `igua_setup_known_tables()` com todas as tabelas da revisão do esquema em `app/schema.php` e módulos. Nunca executar DROP/TRUNCATE/DELETE para reparar instalação automática.
- `igua_setup_config_write_probe()` valida escrita e renomeação no diretório privado antes de alterações em SQL; configuração com credenciais é criada em arquivo temporário privado **antes** do DDL e ativada apenas após sucesso, com limpeza de temporários em falhas.
- Modo Recuperar exige administrador ativo com senha válida, esquema atual e todas as tabelas esperadas; não reseta senha nem altera registros. Não inserir credenciais ou dumps no pacote distribuído.
- Instalação nova/backup em banco vazio; migração entre provedores deve usar backup validado e cópia separada de uploads. Executar `php tests/setup_recovery_151.php`, regressões e integração MariaDB em ambiente isolado.

### 1.0.52 — atualização assinada do código
- Preservar o motor em `app/updater.php` e a interface no painel com CSRF, papel `admin` e reautenticação por senha. Pacotes devem ser Ed25519 assinados e hash SHA-256 por arquivo.
- Impedir escrita em `storage/`, `public/uploads/` e quaisquer caminhos não permitidos; rejeitar arquivos simbólicos, versões antigas, mudança da revisão do banco e ZIPs adulterados.
- Fazer backup lógico antes de substituir código; manter manutenção e journal para restauração, sem executar rollback enganoso de DDL MySQL.
- Não carregar chave privada no código-fonte, GitHub ou hospedagem. Releases devem ter o nome `Meu-Site-update-vX.Y.Z.zip` e tag `vX.Y.Z`.
- Testar `php tests/updater_152.php`, testes de regressão e integração em servidor real. Sem cron, a verificação é sob demanda/cache.

### 1.0.54 — atualizador com interface compacta
- Usar o padrão `Atualizacao-vX.Y.Z.zip` para novas atualizações assinadas; aceitar nome antigo somente por compatibilidade. Não gerar pacote separado Plesk para próximas versões.
- Atualizações de código exigem sessão admin ativa, CSRF, confirmação explícita e assinatura Ed25519; restauração exige senha e mantém rollback apenas de arquivos.
- Repositório GitHub deve vir de `app/update-origin.json` verificado, ambiente explícito ou configuração manual salva; nunca de metadados Android não relacionados.
- Layout usa ajuda `?` e seções recolhidas, sem suprimir relatórios de segurança. Ver `docs/ATUALIZADOR_154.md` e executar `tests/updater_154.php`.

### 1.0.55 — histórico de atualizações e integridade
- A auditoria do código é somente leitura: compara SHA-256 com hashes do journal da última instalação concluída, não substitui diagnóstico do PHP-FPM real nem marca o banco como restaurado.
- Histórico limitado a seis operações recentes, sanitizado e extraído dos journals privados sem expor nomes de backup nem senhas.
- A simulação de ZIP fornece contagens de arquivos novos, diferentes e iguais; preserva assinatura Ed25519 e verificações do preflight.
- Nenhum código pode modificar uploads, `storage/config.php`, backup, MySQL ou schema 36 por meio desse diagnóstico.
- Testar `php tests/updater_155.php`, regressões legadas e ZIP assinado em servidor real.

### 1.0.56 — origem GitHub e vistoria em janelas compactas
- `app/update-origin.json` contém somente o repositório público confirmado `drilemoz/Meu-Site`; `storage/updater/source.json` (se houver) permanece a personalização de maior prioridade. O reset da origem exige admin+CSRF e restaura somente os metadados do portal.
- Não inferir releases existentes só porque o repositório está configurado; resposta 404 de `releases/latest` é "sem release", com cache, não erro SSL.
- Fonte e vistoria só abrem via botões com `data-updater-open`, modais `<dialog>` e fechamento acessível. Não recolocar cartões grandes permanentes na tela.
- No CSS móvel preservar apenas o espaço da navegação fixa (68px + safe-area). Não somar o padding de `.footer-new` ao de `.site-footer` novamente.
- Executar `php tests/updater_156.php`, regressões PHP/JS, `python tools/build_css.py --check` e integração de hospedagem antes de declarar funcionamento em produção. Schema 36 e dados privados inalterados.

### 1.0.57 — preparação para análise do Google AdSense
- Preservar publicidade desligada e consentimento prévio: configurar o ID `ca-pub-*` só publica metatag de verificação no `<head>`, sem inicializar anúncio ou cookie do Google.
- `app/adsense_review.php` faz a verificação editorial/técnica local no painel Publicidade; nenhum status garante aprovação do Google. Conteúdo real e original, qualidade, autoria e direitos de imagens são responsabilidade editorial.
- Ação administrativa `ads_prepare_ads_txt` exige sessão admin com `manage_settings` e token CSRF; publica atomicamente `public/ads.txt` para o editor real, não inventa ID e preserva outras redes. Bloquear se já houver outro ID Google.
- `public/ads.txt` pertence à instalação/conta, não pode integrar o ZIP de atualização nem ser sobrescrito pelo atualizador. Instaladores completos podem fornecer placeholder em instalações novas.
- Página Sobre acessível em `/index.php?p=sobre`, com links em rodapé. Política de privacidade informa cookies e saída para controles do Google; revisar texto jurídico conforme a operação e consentimento aplicável.
- `seo_index` continua desativado por padrão; o painel informa pendência em vez de ligar a indexação sozinho. Sem migrações MySQL, revisão 36 inalterada. Rodar `php tests/adsense_review_157.php` e as regressões.

### 1.0.58 — manutenção do conteúdo de teste e primeira instalação
- Nunca voltar a mostrar banner de demonstração global. O aviso individual fica no artigo com `is_demo=1`; etiquetas de exemplo só acompanham matérias, mantendo noindex e restrições do AdSense.
- `seed_initial_categories()` independente de `seed_demo()`; instalação sem amostras é o padrão. Não executar DDL durante a transação de criação do usuário/admin, pois MariaDB confirma automaticamente o DDL.
- Manutenção no painel exige administrador autenticado, CSRF, confirmação textual e backup lógico antes de exclusões; não remover categorias, fontes e arquivos de mídia nas limpezas editoriais.
- Reset de instalações Alpha requer senha administrativa e inventário exato das 25 tabelas antes de DROP; nunca mexer em tabelas de terceiros. Não prometer rollback DDL ou restauração automática de uploads.
- Ação destrutiva nunca é executada no upgrade automático. Esquema continua 36. Testar `tests/content_maintenance_158.php`, regressões e instalação MariaDB real em ambiente descartável.

### 1.0.59 — Publicidade compacta e limpeza independente
- Interface de Publicidade usa `app/admin_views/routes/ads_dashboard.php` e `public/assets/js/ads-admin.js`, com botões abrindo `<dialog>`. Não reintroduzir os formulários extensos ou a tabela horizontal na página principal.
- Toda ação de criação, edição, ativação e limpeza continua autenticada e protegida por CSRF; não confiar em avisos JavaScript para impor ocupação. Conflitos de datas devem ser validados no PHP; AdSense ativo e campanhas locais ativas não podem ocupar o mesmo espaço.
- `app/ads_management.php` identifica e remove demos **apenas** pelo prefixo `AD_DEMO_URL_PREFIX`; backup lógico, confirmação textual, transação e proteção das campanhas reais são obrigatórios. Manutenção editorial e publicidade são ações independentes.
- Gerar exemplos não pode reconfigurar espaços de publicidade real ou AdSense. Não remover mídias físicas; nenhum arquivo do `storage/` nem `public/ads.txt` entra em ZIP de atualização.
- Sem migração do MySQL 36 ou mudança do backup. Validar `tests/ads_admin_159.php`, demais testes, `tools/build_css.py --check`, atualização assinada e ambiente real antes de produção.

### 1.0.60 — Navegação administrativa e modais de confirmação
- Manter **um único menu** no painel, dentro de drawer `<dialog>` do lado direito; não recriar submenus horizontais. `app/admin_views/layout.php` usa `role_allowed` e o logout conserva POST + CSRF.
- Filtros de notícias ficam num `<dialog>` sob a lupa, e a linha de ações respeita ACL para publicar/excluir; URLs e parâmetros anteriores permanecem. Usuários e eleições usam `<details>` de acesso progressivo, abertos automaticamente no modo edição. Mídia permanece em tela cheia no celular com envio recolhido.
- `public/assets/js/admin-ui.js` intercepta `data-confirm` no *capture* antes de qualquer execução, com bloqueio por cancelamento/Escape; o servidor sempre valida CSRF, papéis e entradas. Não usar `confirm()` ou `alert()` para ações administrativas novas salvo fallback de compatibilidade.
- Não mudar schema 36, arquivos privados, contas ou assinaturas. Atualização repetida ou mais antiga é rejeitada antes de backup/escrita. Testar `tests/admin_ui_160.php`, `tests/admin_ui_160.js`, testes anteriores, CSS consolidado e pacote Ed25519. Teste integrado com PHP-FPM e MariaDB permanece necessário.

### 1.0.61 — biblioteca como componente e rodapé público
- A rota `media` em `app/admin_views/pages.php` utiliza `media_admin_modal(true)` dentro do conteúdo principal, sem fechar o cabeçalho/rodapé nem bloquear a rolagem. Em outras telas o mesmo componente é um modal para seleção de fotos; preservar estes dois modos, ACL e CSRF.
- O rodapé público exibe somente os grupos Explorar e institucional, sem Projetos; navegação móvel fixa e crédito do desenvolvedor permanecem. CSS público utiliza `igua_brand_version()` na URL de stylesheet para invalidar cache.
- Drawer do painel mantém `<dialog>` à direita, largura compacta e logout com CSRF. Sem alterações de schema 36 ou de formatos de backup.
- Regressão: `php tests/ui_161.php`, `php tests/admin_ui_160.php`, verificações CSS, PHP/JS e teste de uso real em dispositivo.

### 1.0.62 — Atualização via ZIP v2 sem assinatura obrigatória
- `meu-site-update-v2` pode omitir `release.sig`. Não alegar autenticidade por SHA-256: o manifesto também pertence ao ZIP. `meu-site-update-v1` exige assinatura Ed25519 e qualquer assinatura presente exige verificação válida.
- Reautenticação com senha do administrador em toda instalação (remota ou manual), com CSRF, confirmação explícita e limite de cinco falhas/15 minutos por sessão. Não remover os limites de caminhos/tamanhos, backups, rollback e bloqueio de migração de schema.
- A primeira atualização desde versão que exige assinatura deve ser aplicada manualmente na hospedagem, preservando `storage/`, `public/uploads/`, `public/ads.txt` e backups. Não tentar passar ZIP v2 no atualizador antigo.
- `tools/build_update_package.py --output ...` gera pacote v2 sem chave; `--key-file` é opcional. Rodar `tests/updater_162.php`, regressão e inspeção do ZIP. Validar PHP-FPM e MariaDB reais antes de produção.

### 1.0.63 — Drawer do painel menor
- Manter drawer `<dialog>` do lado direito, com largura máxima de 248 px em telas móveis e 270 px nas demais, respeitando folga lateral de pelo menos 72 px. Grupos Notícias e Configurações continuam recolhíveis e roláveis.
- Ao ajustar CSS, alterar o módulo `public/assets/css/modules/15-archive.css` e reconstruir `style.css` com `python3 tools/build_css.py`; nunca editar só o consolidado.
- Preservar as proteções de atualização ZIP v2 sem assinatura: reautenticação, CSRF, backup e hashes SHA-256. Sem migração do schema 36. Testar `tests/ui_163.php`, `tests/updater_162.php` e CSS consolidado.

### 1.0.64 — Central de Segurança
- Histórico de eventos PHP privado em `storage/security/monitor/`, sem migrar MySQL (schema 36); nunca registrar corpos, tokens, credenciais, e-mail, URL completa ou cabeçalhos não confiáveis.
- Bloqueios de IP apenas no admin, sempre temporários e manuais com senha + CSRF; não bloquear o próprio IP, manter login throttle existente e permitir recuperação pela hospedagem.
- A detecção de varreduras é parcial (somente requisições executadas pelo PHP), não prometer WAF nem auditoria dos logs de Apache/Nginx/CDN. Limitar armazenamento a 1 MiB/dia e retenção de 30 dias.
- Logs e bloqueios são privados, fora de atualizações/backup MySQL. Validar permissões de `storage/`; falha em logger não deve derrubar a aplicação. Teste `php tests/security_164.php`, CSS, PHP, regressão e instalação no provedor.

### 1.0.65 — primeira etapa da auditoria visual
- Preservar o formato 16 módulos CSS e recompilar `style.css` após edições. Nunca corrigir só o bundle.
- Manter fallback de imagens no servidor e no navegador; arquivos ausentes em `media_library` devem ser identificados sem apagar registros, referências, uploads ou miniaturas.
- Modais de publicidade devem manter botão Fechar visível durante a rolagem; backups devem adaptar-se a celulares sem perder nome/ação. Alertas de exclusão, restauração e bloqueio não devem ser escondidos em `?`.
- Na página inicial, ocultar apenas seções editoriais vazias; não eliminar navegação para as categorias. Manter a indicação correta de página ativa no menu inferior.
- Sem mudança de schema 36; pacote v2 sem assinatura, com SHA-256 e senha administrativa, sem modificar uploads ou armazenamento privado.

### 1.0.66 — segunda etapa de compactação
- Na Central de Saúde, erros e avisos ficam sempre visíveis; apenas diagnósticos normais e logs podem ficar recolhidos. Não apresentar status positivo como garantia de disponibilidade geral.
- Manter todas as permissões, regras contra autodesativação, formulários POST, CSRF, confirmação de exclusão e funções de usuários.
- Nas notícias, preservar 44×44 px como área de toque mínima das ações móveis; não alterar lógica de publicação, duplicação ou exclusão.
- SEO pode ficar em bloco expansível desde que todos os inputs permaneçam no mesmo formulário, sejam submetidos quando recolhidos e alertas essenciais de indexação/roteamento permaneçam acessíveis.
- Manter 16 módulos CSS, schema 36, ZIP v2 sem assinatura e layout do portal público intacto. Testar `tests/ui_166.php` e regressão antes da publicação.

### 1.0.67 — progresso e compactação de manutenção/ônibus/publicidade
- Progresso de upload usa apenas loaded/total de `XMLHttpRequest.upload`; 100% indica transmissão do navegador, nunca instalação. Durante validação/download/instalação mostrar estágio indeterminado, sem porcentagem falsa. Preservar fallback HTML e exibir falhas na interface.
- Não alterar CSRF, senha de administrador, reautenticação, autorização, limite de ZIP, revisão 36, backup, journal e rollback. Nunca enviar atualização automaticamente duas vezes após timeout.
- Em Manutenção manter avisos de ações irreversíveis visíveis; somente explicações opcionais ficam em `?`. Em ônibus manter campos do wizard e horários reais. Publicidade mantém ACL, anúncios desligados por padrão e validação de conflito.

### 1.0.68 — Filtros de audiência e GA4
- Manter os filtros `today`, `24h`, `7`, `30`, `90`, com padrão 30. Horário `America/Sao_Paulo` e SQL MySQL `-03:00`. Não misturar “Hoje” com 24h móveis.
- `analytics_time_range` deve filtrar **todas** as agregações, incluindo os detalhes, com o mesmo recorte de `occurred_at`. Gráficos por hora para Hoje/24h e por dia para 7/30/90. IDs visitantes mudam diariamente, não prometer usuário único entre dias.
- GA4 desativado por padrão: validar somente ID `G-...` no servidor, jamais aceitar script HTML bruto. Tag externa não pode carregar antes de consentimento explícito, nem com GPC/DNT ou opt-out de métricas. Não misturar consentimento GA4 com AdSense.
- Não esconder informações legais importantes apenas no botão `?`. Políticas e escolha de cookies devem permanecer acessíveis ao visitante. Sem GA4 nas páginas administrativas.
- Preservar schema 36 e backup, ZIP v2, senha, CSRF e não alterar mídias. Recompilar os 16 módulos CSS. Testar `php tests/analytics_168.php`, `node tests/ga4_consent_168.js`, regressões, sintaxe e ZIP.

### 1.0.69 — Central de Integrações
- Página privada `integrations` em Configurações, exclusivamente para `manage_settings`. Qualquer salvamento exige CSRF e permissão administrativa; usar as chaves **existentes** `ga4_measurement_id`, `ga4_enabled`, `adsense_publisher`, sem criar fontes de verdade concorrentes.
- O toggle local de métricas `analytics_enabled` continua em Métricas, e `ads_enabled`/posições/campanhas continuam em Publicidade. Um formulário legado que omita o ID nunca deve zerar as configurações centralizadas.
- `google_site_verification` armazena apenas conteúdo validado, nunca tags HTML livres; a metatag somente aparece no HTML público com escape de atributo. Publicação não comprova posse de propriedade; o proprietário confirma no Search Console e propriedades de domínio usam DNS.
- Diagnóstico inicial é local e somente leitura. Não realizar sondagem HTTP arbitrária (SSRF), consultas remotas automáticas, varredura de internet ou apresentar aprovação Google sem consulta autorizada.
- Ferramentas do catálogo são **planejadas**, não solicitar chaves sem integração real ou usar scripts externos. Consentimento GA4, GPC/DNT, AdSense, SQL 36, backup e ZIP v2 sem assinatura seguem como antes.
- O visual mobile usa o módulo CSS `16-security.css` compilado via `tools/build_css.py` e script `integrations-admin.js`; deve funcionar sem JavaScript exibindo todas as seções. Touch targets (incluindo ajuda `?`) mínimos de 44px.
- Validar `php tests/integrations_169.php`, `php tests/analytics_168.php`, `php tests/ads_admin_159.php`, `php tests/smoke.php`, `node tests/ga4_consent_168.js`, regressões e empacotamento. Testar em celular e hospedagem real antes de considerar verificação externa operacional.

### 1.0.70 — Verificador de Páginas
- A aba Páginas na Central de Integrações é exclusiva de `manage_settings` e usa GET somente para selecionar páginas internas por whitelist; nunca aceitar URL arbitrária ou realizar HTTP automático que possa causar SSRF.
- Os resultados são **locais**, não equivalem a teste HTTP, propriedade verificada, qualidade Google ou indexação. A API URL Inspection requer OAuth autorizado; não solicitar tokens sem implementação operacional.
- Verificar apenas notícias publicadas, categorias cadastradas e inicial. Não abrir rascunhos por um seletor manipulável, não alterar views/contadores nem fazer escrita durante inspeção.
- Preservar fallback de imagem, sem esconder erro de arquivo físico ausente. Marcar demos como noindex; contemplar configurações HTTPS e URLs amigáveis.
- Manter área de toque ~44px, ajuda `?`, HTML escapado e CSS nos 16 módulos recompilados. Esquema 36, ZIP v2 sem assinatura obrigatória, backups e consentimento seguem preservados.
- Testar `tests/page_inspector_170.php`, `tests/integrations_169.php`, regressões, lint, `tools/build_css.py --check` e pacote ZIP.

### 1.0.71 — Teste autenticado de recebimento no GA4
- Consulta Data API exclusivamente por clique do admin, com CSRF e permissão manage_settings. Não carregar bibliotecas Google no painel nem consultar API automaticamente.
- Configurar propriedade numérica em settings, separada do ID G- do stream. Conta de serviço Google Cloud deve ser importada como JSON privado em storage/integrations/, com permissão 0600; arquivo jamais integra ZIP, backup, repositório ou HTML público. Operações sobre credenciais exigem senha administrativa.
- OAuth de conta de serviço exige escopo analytics.readonly e autorização de Leitor na propriedade GA4. Conexões HTTPS somente com Google OAuth e Analytics Data API (hosts fixos e sem redirecionamentos), timeout e tamanho de resposta limitados. Nunca registrar JWT, access token, chave ou corpos HTTP.
- Realtime cobre últimos 30 minutos; relatório recente cobre 7 dias. Sem eventos não comprova falha, e eventos em propriedade não comprovam origem em um único stream G-. Erros 403/404/429 são explicados sem divulgar segredos.
- Preservar schema 36, consentimento GA4, backup, menus compactos e atualização ZIP v2 sem assinatura obrigatória. Validar tests/ga4_realtime_171.php, suite anterior, CSS e ZIP; teste API real exige credenciais autorizadas da hospedagem.

### 1.0.72 — Verificação DNS TXT do Search Console
- O status do Search Console não pode depender somente da metatag: DNS e metatag são métodos alternativos de propriedade, com resultados separados. "TXT detectado" significa apenas registro DNS público encontrado, **não** verificação da conta pelo Google.
- Consulta DNS TXT somente mediante POST administrativo com `manage_settings` e CSRF, restrita ao host HTTPS da configuração `seo_site_url`. Nunca aceitar domínio arbitrário por GET/POST nem usar HTTP remoto para o teste.
- `google_dns_verification_txt` é público no DNS e opcional; sem ele, mostrar somente registro Google genérico; com ele, comparar correspondência exata. Resultados armazenados apenas em sessão com expiração de 15 minutos e intervalo curto entre consultas.
- Ausência, erro de resolução e falta de URL canônica são estados diferentes. Conservar a metatag para propriedades de prefixo de URL e o link para a confirmação oficial no Search Console.
- Preservar versão 36 do SQL, backups, GA4, consentimento, AdSense, rotas, uploads e ZIP v2 sem assinatura obrigatória. Rodar `tests/search_dns_172.php`, regressão e teste na hospedagem com DNS disponível.

## Empacotamento obrigatório a partir da versão 1.0.73
- O portal é exclusivamente PHP/MySQL. Nunca incluir Gradle, Kotlin, AndroidManifest, caminhos `app/src/`, `design/` ou artefatos de outro projeto.
- Utilizar `tools/build_source_package.py` para o ZIP de código-fonte e `tools/build_update_package.py` para a atualização de hospedagem; verificar os dois pacotes após a geração.
- Manter `github-manager.json` e `app_identity.json` consistentes com `VERSION`, sem informações Android.

### 1.0.74 — regressão real de ZIP com entradas repetidas
- O teste `tests/backup_integrity_133.php` deve usar `tests/fixtures/backup133_duplicate_names.zip` com duplicatas reais; `ZipArchive::addFromString` padrão substitui nomes iguais e produz falsos negativos de teste.
- Com PHP `zip` instalado, conferir 4 nomes via `statIndex` antes de verificar que `media_bundle_verify_manifest()` lança exceção para entradas repetidas. No fallback sem extensão, usar stub apenas como teste de contrato.
- O empacotador de fonte só pode incluir esse ZIP de fixture, nunca backups reais, ZIPs de versão anterior ou outros projetos; a atualização de hospedagem continua sem a pasta `tests`.
- Corrigir falhas de GitHub Actions publicando o fonte atualizado no repositório, não apenas o pacote de atualização PHP.

### 1.0.75 — Upload manual em modal
- "Enviar atualização" deve ficar junto a Releases na barra superior. No celular, abreviação visual "Enviar ZIP" com aria-label completo; preservar atalho de verificar.
- Formulário de ZIP manual deve permanecer em `<dialog>` e só começar transmissão após etapa de confirmação deliberada, senha, confirmação de origem e CSRF. Não reinserir bloco expansível na página principal.
- Progresso `XMLHttpRequest.upload` só mostra % durante transferência; após envio, usar barra indeterminada até resposta oficial do PHP. Nenhum retorno 100% equivale a instalação antes do sucesso no servidor.
- Durante envio/instalação, o diálogo não fecha por botão, backdrop ou Escape. Quando servidor retornar, exibir sucesso ou erro no próprio modal, sem rolar nem redirecionar automaticamente; botão de resultado permite verificar a página.
- Em erros de resposta ambígua/timeout não repetir POST automaticamente. Permitir rever dados apenas quando o servidor confirmar erro. Preservar caminho de recuperação, backup, limites de ZIP, ACL, CSRF, revisão 36, código PHP e rastreamento.
- CSS no módulo `09-editorial-admin.css` compilado em `style.css`. Rodar testes `updater_modal_175.php/.js`, `updater_progress_167.js`, testes anteriores e checagem ZIP. Conferir no Chrome Android e PHP-FPM real.
