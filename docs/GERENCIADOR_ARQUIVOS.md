# Gerenciador de Arquivos — 1.0.122

## Acesso e visualização

Abra **Administração → Ferramentas → Gerenciador de Arquivos** como administrador. A tela começa na **raiz de instalação do portal** (IGUA_ROOT), não na pasta de upload. Mostra arquivos e diretórios reais: `app/`, `public/` e outros itens presentes na instalação. Pastas são abertas por toque; o caminho em breadcrumbs permite voltar; pesquisa filtra nomes da pasta atual. Listas grandes possuem páginas de 120 itens, com limite de varredura por segurança.

Arquivos de texto (PHP, JS, CSS, JSON, Markdown, TXT, CSV, XML etc.) têm visualização de **somente leitura** até 128 KiB. Imagens na pasta pública têm prévia. Downloads privados exigem sessão de administrador, sem cache, com limite de 8 MiB. Arquivos maiores podem ser geridos fora do portal via hospedagem.

## Gravação permitida

Apenas a pasta `public/uploads/arquivos/` e suas subpastas permitem ações de criar pasta, upload, renomear, excluir e editar TXT/CSV. O menu de criação é recolhido por padrão e só aparece nesse espaço. Upload até 8 MiB, limitado a JPG/JPEG/PNG/WebP/PDF/TXT/CSV com validação MIME e conteúdo. Pastas precisam estar vazias para exclusão. Arquivos da pasta gerenciada podem ser acessados publicamente por URL.

`public/ads.txt` e `public/robots.txt` podem ser editados via pasta `public/` com SHA-256 para detectar alterações concorrentes. Não há cartão separado de arquivos essenciais.

## Proteções

- Rotas acessíveis somente para administradores com `manage_settings`; ações POST requerem CSRF.
- `storage/`, credenciais, `config.php`, `.env`, chaves privadas, bancos/logs e caminhos por links simbólicos ficam inacessíveis. Nenhuma navegação pode escapar de `IGUA_ROOT`.
- Código PHP, JavaScript, CSS e arquivos de sistema fora da pasta gerenciada são **somente leitura**. Não implementado editor irrestrito de código da hospedagem.
- Os arquivos geridos em `public/uploads/` sobrevivem às atualizações e não entram nos ZIPs de código. Faça backup externo antes de operações importantes.
- Testes locais de segurança, ACL, paths e regressões não substituem homologação na hospedagem (Plesk/Apache/Nginx, permissões PHP, cache e mobile).

## Testes

- `php tests/file_manager_221.php` confirma operações antigas e bloqueios da pasta gerenciada.
- `php tests/file_manager_222.php` testa a raiz, diretórios visíveis, proibição de `storage`, symlinks, traversal, navegação e controles de visualização.
