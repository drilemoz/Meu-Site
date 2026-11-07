# Aplicativos e Páginas Legais — IguatamaOnline (v1.0.98)

## Objetivo

O painel **Aplicativos e Páginas Legais** reúne vários aplicativos próprios, sem criar outro sistema administrativo. Cada aplicativo tem uma URL própria, página de apresentação, Política de Privacidade e Termos de Serviço, além de páginas personalizadas. Todo o conteúdo vem do MariaDB e pode ser atualizado **sem deploy**, após a instalação inicial do módulo.

### Exemplo de rotas públicas

- `https://iguatama.online/uaicortes`
- `https://iguatama.online/uaicortes/privacidade`
- `https://iguatama.online/uaicortes/termos`
- `https://iguatama.online/outro-app` e páginas adicionais `/outro-app/nome-da-pagina`

Essas URLs são resolvidas pelo `public/.htaccess` (Apache com mod_rewrite) ou pelo `public/router.php` (servidor embutido do PHP). O documento raiz HTTP deve apontar para `public/`. A publicação final deve ser testada diretamente no domínio.

## Fluxo administrativo

1. Entre com uma conta de **administrador ativo** e abra **Aplicativos e Páginas Legais** no menu.
2. O **Uai Cortes** já aparece como **rascunho inicial**, se o banco não tinha aplicativos; nenhum conteúdo jurídico é publicado por padrão.
3. Cadastre ou edite nome, slug, descrições, imagem, responsável, suporte e link oficial. Alterar o nome não altera o slug.
4. Abra uma das páginas vinculadas, escreva título, corpo em Markdown seguro e descrição SEO. Use **Pré-visualizar** para conferir antes de salvar.
5. Clique em **Salvar rascunho** para guardar uma alteração sem mexer no conteúdo público.
6. **Publicar página** exige confirmação e cria uma revisão imutável do texto; apenas uma revisão é visível publicamente por vez.
7. Publique o aplicativo quando a **Apresentação** e a **Política de Privacidade** já estiverem publicadas, com responsável e e-mail válido. Os termos podem ser publicados separadamente, mas precisam estar disponíveis quando o serviço exigir.
8. Cada cartão oferece **Editar**, **Prévia** (quando é rascunho) ou **Ver página** (quando aplicativo e página estão publicados), e **Copiar URL**. A prévia abre a visualização do rascunho salvo dentro do painel autenticado em outra aba; nunca torna o rascunho público. O endereço público abre em outra aba e a URL não muda quando o texto muda.
9. Em **Histórico**, uma revisão anterior pode ser restaurada **somente para o rascunho**. Publique novamente se desejar trocar a versão pública.

### Formatação

O campo de conteúdo aceita títulos (`#`/`##`/`###`), parágrafos, listas (`-` e `1.`), **negrito** (`**texto**`), *itálico* (`*texto*`) e links `[nome](https://exemplo.com)` (ou `mailto:` e caminhos internos seguros). O backend não executa o HTML escrito pelo administrador: escapa HTML e só renderiza marcação permitida. A prévia é um recurso de ajuda; a renderização pública é feita inteiramente no servidor, sem exigir JS.

### Segurança e privacidade

- URLs dos aplicativos são públicas quando **aplicativo e página** estiverem publicados. Conteúdos despublicados ou em rascunho retornam 404.
- Somente administradores com a sessão autenticada podem criar, editar, publicar, restaurar ou excluir. POSTs usam a proteção CSRF existente, confirmação para ações destrutivas e SQL preparado.
- Slugs usam letras minúsculas ASCII, números e hífens. Slugs já ocupados, aliases antigos e rotas do portal são rejeitados.
- Mudança explícita de slug exige confirmação e registra redirecionamento **301** da URL antiga para a nova, somente para páginas publicadas.
- Logotipos passam pela validação de imagem existente (JPG/PNG/WebP, MIME real, até 4 MB) e pela biblioteca de mídia, incluída na restauração completa.
- O histórico registra publicações, não cada salvamento de rascunho. Excluir um aplicativo é permanente e requer digitar o slug exato.
- Modelos iniciais contêm marcadores **PREENCHER** e não podem ser publicados enquanto não forem substituídos. São guias, **não textos legais prontos**.

## Google OAuth e YouTube

As páginas publicadas são HTML público sobre HTTPS, com título/descrição SEO, URL canônica, sem login e sem depender de JavaScript para o conteúdo. Uma página publicada pode ser encontrada por mecanismos de busca, desde que a configuração do servidor/CDN permita. Não há garantia de aprovação OAuth: revisar requisitos atuais do Google Cloud, domínio verificado, conteúdo real da política, contatos, marca e escopos OAuth antes de solicitar revisão. O site não cria nem armazena automaticamente permissões OAuth para os aplicativos cadastrados.

## Dados, banco e migração

**Schema 37**, com quatro tabelas novas, sem eliminar as anteriores:

- `legal_apps`: metadados e estado de cada aplicativo.
- `legal_app_pages`: rascunhos, estados e referência à revisão pública.
- `legal_app_revisions`: snapshots imutáveis da publicação.
- `legal_app_slug_redirects`: histórico de identificadores antigos.

A migração é aditiva/idempotente e tem marcador `igua_schema_revision=37`. O backup lógico agora é **v12**, com essas quatro tabelas e leitura de backups v1–v11 para migração. O pacote completo conserva fotos através das referências à biblioteca de mídia. Nada disso substitui um backup integral externo do banco e arquivos.

**Atenção: passagem do schema 36 para 37 exige instalação assistida**. O atualizador automático do portal só instala pacotes cuja revisão do banco seja compatível com a versão já instalada; não aceite um ZIP 1.0.96 via painel antigo 1.0.95 como se não houvesse migração. Leia [MIGRACAO.md](MIGRACAO.md) e [ATUALIZADOR.md](ATUALIZADOR.md).

## Testes

- `php tests/legal_apps_196.php` verifica regras de slugs, rotas, HTML seguro, segurança e integração estrutural offline.
- `php tests/integration/legal_apps_mariadb_196.php` requer banco **local descartável** `igua_ci_*`, `pdo_mysql`, opt-in explícito, e é executado após a criação do banco no workflow GitHub Actions. Exercita dois aplicativos, isolamento de publicação, revisões, redirects e backup/restauração v12.
- Ainda é necessário validar manualmente em uma hospedagem de testes a migração 36→37, as URLs públicas com status 200/404, HTTPS e o formulário no Chrome Android antes de produção.

## Modelos de Uai Cortes (v1.0.97)

O arquivo `app/legal_uai_examples.php` fornece exemplos de apresentação, Política de Privacidade e Termos baseados no código do Uai Cortes. Na criação inicial do Uai Cortes, esses textos são gravados no banco como rascunhos e nunca publicados automaticamente.

Para Uai Cortes previamente criado pela v1.0.96, acesse **Aplicativos e Páginas Legais → Uai Cortes → Carregar exemplos do Uai Cortes**. A ação altera apenas páginas em rascunho com texto inicial intacto ou vazio e completa campos de cadastro vazios. Conteúdo já editado ou publicado não é substituído.

As instruções `PREENCHER:` sobre dados pessoais, retenção e revogação devem ser revisadas e removidas após confirmação dos fluxos reais. O backend bloqueia publicar páginas que ainda contêm essa marca. O conteúdo atual não constitui parecer jurídico e a plataforma Google pode exigir comprovações adicionais.
