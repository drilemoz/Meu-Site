# Usuários, perfis editoriais e SEO (v1.0.4)

## Permissões

| Operação | Admin | Editor | Redator |
| --- | --- | --- | --- |
| Listar notícias | Todas | Todas | Somente próprias |
| Criar matéria | Sim | Sim | Rascunho |
| Editar matéria | Todas | Todas | Próprios rascunhos |
| Publicar, excluir e destacar | Sim | Sim | Não |
| Categorias, autores e fontes | Sim | Sim | Leitura na seleção da notícia |
| Usuários, SEO, backups | Sim | Não | Não |

Todos os endpoints de escrita usam sessão autenticada, CSRF e verificam autorização no servidor. Não existe registro público. Admin não pode desativar a própria conta, e o sistema impede perder o último administrador ativo. Não compartilhe contas; utilize senhas fortes e HTTPS na hospedagem.

**Autores** são assinaturas editoriais (nome e biografia) independentes de usuários; uma notícia pode receber um perfil de autor ou usar o nome da conta que a publicou. **Fontes** são referências de nome e URL HTTPS; a notícia salva uma cópia textual da fonte usada, preservando referência em alterações do catálogo.

## SEO

- Modelo de título `%title% | IguaNews`, descrição global e por notícia.
- URL base HTTPS habilita rel=canonical e o sitemap XML (`/sitemap.php`). Não configure `127.0.0.1` como URL oficial.
- Metatags Open Graph e Twitter card, dados estruturados `NewsArticle` com dados de publicação e autor.
- Indexação desativada por padrão, ativada em Configurações. Conteúdo demonstrativo permanece `noindex`.
- `robots.txt` restringe rotas administrativas, enquanto `meta name=robots` controla a indexação do conteúdo. Nunca dependa de robots para segurança.
- Sitemap contém páginas públicas e notícias não demonstrativas, até 50 mil URLs; configure na ferramenta de busca quando publicar.

## Backup e migração

O backup versão 2 inclui `author_profiles`, `editorial_sources`, `users.role`, `users.active` e campos SEO. A restauração da versão 1 continua aceita (usuários legados recebem papel administrador para manter acesso). Cópia dos uploads físicos continua separada.

## URLs de notícias na 1.0.24

Em **Básico → SEO e destaque**, edite o nome da URL (`url_slug`), com até 120 caracteres para endereços novos. O painel normaliza acentos e verifica colisões com outras matérias e URLs históricas. Alterar somente a manchete não altera o endereço. Ao mudar um slug publicado, o antigo vira redirecionamento HTTP 301. A configuração opcional **URLs amigáveis** requer Apache `mod_rewrite` ou o roteador PHP do Termux. O backup lógico v10 guarda os slugs anteriores. Veja [SEO_URLS_124.md](SEO_URLS_124.md).
