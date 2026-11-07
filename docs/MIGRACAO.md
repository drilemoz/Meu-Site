# Migração do IguaNews do Termux para uma hospedagem PHP

A versão 1.0.7 permite **restaurar um backup da instalação antiga DURANTE o primeiro setup** no servidor novo. Use apenas backups do IguaNews nos formatos `.json` ou `.json.gz` (versão de backup 1, 2, 3, 4 ou 5). Dumps SQL de outros sistemas não são executados por segurança.

## Antes de migrar

1. Na instalação antiga, entre em **Configurações → Backups e restauração → Gerar backup agora** e baixe o `.json.gz` (ou `.json` quando não houver zlib). Guarde também uma cópia fora do servidor.
2. Copie integralmente a pasta **`public/uploads/`** do portal antigo. O backup do banco não contém as fotos enviadas, mas mantém suas referências.
3. Salve uma cópia da pasta do projeto, sobretudo configurações de servidor e personalizações próprias. **Não coloque `storage/config.php` em GitHub nem divulgue suas senhas**.
4. Confira que o backup tem as notícias recentes e que sabe o e-mail/senha do administrador do portal antigo. A migração **preserva essa conta**, não redefine sua senha.

## No novo servidor

1. Faça upload dos arquivos do **IguaNews** com PHP 8.1+, PDO MySQL, fileinfo e banco MySQL/MariaDB. A raiz pública do domínio deve apontar **somente** para `public/`.
2. Configure HTTPS e armazene `storage/` fora da raiz pública. Não copie o `storage/config.php` antigo para o servidor novo.
3. Em cPanel ou hospedagem compartilhada, **crie primeiro o banco vazio e usuário no painel do provedor**, associando permissões. O modo automático do IguaNews é apenas para MariaDB local e exige credencial de administração com `CREATE DATABASE`, `CREATE USER` e `GRANT`.
4. Abra **`/setup.php` → Requisitos → Banco**. Escolha **Usar banco existente** e teste. O instalador recusa bancos com dados preexistentes, para evitar perda acidental.
5. Na etapa **Conteúdo**, abra **Importar portal anterior**, selecione o arquivo `.json`/`.json.gz` do IguaNews e importe. Isso cria as tabelas e restaura usuários, matérias, categorias, autores editoriais, fontes, configurações de SEO e logs de backup.
6. Após concluir, entre com **o mesmo e-mail e senha da instalação antiga**. Carregue `public/uploads/` da instalação antiga na pasta homônima do novo servidor (sem substituir fotos novas inadvertidamente).
7. Confira imagens, páginas, categorias, conta administrativa, edições, data/hora, e gere **novo backup** no servidor novo. Mantenha a instalação antiga até validar a cópia. Só depois altere DNS/domínio.

## Limites e riscos

- **É necessário um banco novo/vazio**. A importação do setup nunca mescla dados, e não é uma atualização SQL entre produtos diferentes.
- O backup do banco tem limite de **15 MiB para upload** e **40 MiB descompactado**. O servidor PHP também precisa permitir o upload em `upload_max_filesize` e `post_max_size`; se não permitir, peça ajuste ao provedor.
- **Fotos precisam ser migradas separadamente**. O backup `.json` não guarda os arquivos em `public/uploads/`.
- Backups contêm hashes de senhas, dados de usuários e conteúdo: trate-os como privados e não publique em links públicos.
- Se o banco antigo ainda estiver recebendo notícias durante a migração, gere um **backup final** e faça a importação novamente no banco de destino vazio, antes de apontar o domínio. Não use este instalador para restaurar por cima de um portal instalado.
- Para backups grandes, recomendamos solução com `mariadb-dump`/`mysqldump` e ferramenta própria de migração, fora deste instalador.

## Atualizando uma instalação v1.0.1 já instalada

**Não abra o setup para atualizar**: ele ficará bloqueado porque `storage/config.php` já existe. Atualize apenas os arquivos de código e **mantenha** `storage/config.php`, `storage/backups/` e `public/uploads/`. A versão 1.0.7 possui migração incremental, executada ao entrar no painel: tabelas de autores e fontes, papéis de usuários e campos SEO. O usuário MySQL precisa de CREATE TABLE e ALTER TABLE. Preserve os backups antigos.

## Domínio e SEO após a migração

No novo domínio com HTTPS, abra **Configurações → Geral e SEO**, informe a URL oficial, revise a descrição e ative a indexação somente quando as notícias demonstrativas estiverem removidas ou claramente isoladas. Confira `/sitemap.php` e cadastre-o no mecanismo de busca. Backups v5 exigem IguaNews v1.0.7 ou superior para restauração.

## Transporte intermunicipal

As linhas, paradas e partidas do módulo Ônibus fazem parte do backup **v5**. Ao importar um backup v1 ou v2, estas tabelas permanecerão vazias. Ao restaurar no servidor novo, instale IguaNews 1.0.7 ou superior.

## Atualização 1.0.24: URLs SEO e backup v10

A instalação atual gera backup lógico **v10** (inclui `article_slug_redirects`) e restaura backups v1–v10. O formato ZIP de mídias continua v2. Backups novos precisam de IguaNews 1.0.24 ou superior para restauração; as instruções de backups antigos acima são históricas. A migração incremental cria uma tabela sem alterar textos e fotos. Para URLs limpas, consulte [SEO_URLS.md](SEO_URLS.md), configure o roteamento no PHP/Apache e só então ative a opção nas configurações. Sem essa ativação, as URLs antigas com querystring continuam funcionando.

## Atualização 1.0.46: índices do acervo e esquema 36

A revisão 36 acrescenta dois índices de ordenação na tabela `articles` (`idx_public_featured` e `idx_public_popular`). Faça backup do banco e das mídias antes da atualização; **não** entre no instalador para atualizar um site existente. O usuário do MariaDB precisa poder executar `ALTER TABLE`. A criação dos índices pode consumir espaço e causar bloqueios conforme o volume/versão do servidor. Após atualizar, acesse o painel para a migração incremental, confira a Central de Saúde e use `php tools/news_explain.php` para inspecionar planos. Backups JSON v11 e ZIP v2 não mudam.


## v1.0.96 — Aplicativos e Páginas Legais (schema 36→37)

**Instalação assistida obrigatória:** o atualizador automático do site em schema 36 bloqueia corretamente a entrada de um pacote que declare schema 37. Não falsifique a versão do manifesto, não desative o bloqueio, e não use o ZIP completo diretamente no formulário de atualizações.

1. Guarde **backups externos verificáveis** do banco MariaDB, de `storage/`, de `public/uploads/`, `storage/media/` e do código 1.0.95. Teste a recuperação em ambiente isolado.
2. Ative uma janela de manutenção e confira a versão de PHP (>=8.1), as extensões e permissões MySQL (CREATE TABLE e alterações de schema). Faça primeiro em **homologação**.
3. Copie os diretórios `app/` e `public/` e o arquivo `VERSION` do pacote de atualização **via SFTP/gerenciador da hospedagem**, preservando todas as pastas privadas e arquivos enviados. A instalação não é uma operação atômica de banco e código: evite visitantes durante a substituição.
4. Ao acessar `/admin.php` com administrador ativo, a migração já existente `igua_migrate_schema()` adicionará quatro tabelas e registrará `igua_schema_revision=37` **somente após terminar todas as etapas**. Se o login depender de migração, verifique logs privados/saúde. Não executar SQL avulso de forma improvisada.
5. Confira: as notícias e demais módulos continuam acessíveis; o menu **Aplicativos e Páginas Legais** aparece; **Uai Cortes** está em rascunho; a política não é pública antes da publicação; backups v12 funcionam; e o Apache atende `/<slug>` e `/<slug>/privacidade`.
6. Depois de preencher a política com fatos reais do aplicativo, publique apresentação/política, confira HTTP 200 e links em navegador sem login, SEO e HTTPS; apenas então use as URLs na configuração OAuth.
7. Se houver falha na migração, registre o erro e **restaure código e banco do mesmo snapshot anterior**. Não faça rollback apenas do PHP se o banco já estiver no schema 37, pois o código antigo pode recusar a revisão maior.

A migração **não apaga notícias nem usuários**. DDL no MariaDB não é transacional em todos os cenários; novas tabelas são criadas com `IF NOT EXISTS` para permitir retomada após falha parcial. A primeira instalação também registra o aplicativo Uai Cortes **sem textos legais publicados**. Após a migração, versões posteriores com schema 37 podem voltar a utilizar o atualizador normal, desde que todos os critérios de segurança sejam atendidos. Veja [APLICATIVOS_LEGAIS.md](APLICATIVOS_LEGAIS.md).
