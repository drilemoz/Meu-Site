# Organização do painel administrativo — IguaNews 1.0.30

## Entrada e segurança

A página pública `public/admin.php` permanece o único ponto de entrada da administração (`/admin.php?action=...`). Ela carrega o bootstrap, verifica instalação, login, sessão atual e autorização de acesso a páginas. As rotas de mídia são executadas com checagens próprias de `require_ability`, CSRF e JSON; downloads preservam suas regras antigas. Qualquer POST geral passa pelo `check_csrf()` do ponto de entrada.

## Controladores privados

Os arquivos da pasta `app/admin_controllers/` ficam **fora** da raiz pública `public/`:

| Arquivo | Responsabilidade |
|---|---|
| `media.php` | Listagem, upload, metadados, lixeira, recuperação e download da biblioteca |
| `downloads.php` | Pacotes ZIP e backups |
| `post_routes.php` | Associação explícita de nomes de ação POST aos módulos |
| `news.php` | Autosave, importação editorial, revisão, edição, duplicação, status e exclusão |
| `editorial.php` | Editorias, autores, fontes e acervos fictícios de exemplo |
| `ads.php` | Configurações/campanhas de publicidade e exemplos fictícios |
| `bus.php` | Integração do controlador administrativo de ônibus existente |
| `system.php` | Configurações gerais, SEO, métricas e verificação de backup |
| `users.php` | Gestão de contas e permissões |
| `backups.php` | Criação e restauração de backup lógico e pacote completo |

A função `igua_admin_post_module()` retorna somente identificadores literais definidos internamente. Não existe inclusão de caminho montado com nomes não verificados recebidos do visitante.

## Contratos preservados

- URLs e nomes dos campos dos formulários não mudam.
- O controle de acesso por papel continua em `app/editorial.php` e nos próprios controladores; edições não podem contornar a publicação/autorização.
- O formulário de notícias permanece com **Básico, Texto e Foto**, sem etapa adicional.
- Respostas de mídia continuam JSON ou downloads autenticados, inclusive status de erro.
- Sem alteração no esquema SQL, backup lógico v10 e pacote de mídias v2.
- Controladores privados não devem ser expostos como URLs do servidor; a raiz pública correta continua sendo `public/`.

## Validação

Use `php tests/admin_routes_130.php`, `php tests/smoke.php` e toda a suíte `tests/*.php` e `tests/*.js`. Esses testes não equivalem à execução real com MariaDB, um usuário logado, browser Android e fluxos de backup/restauração.
