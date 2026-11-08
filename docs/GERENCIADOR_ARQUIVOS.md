# Gerenciador de Arquivos — 1.0.121

## Localização

Abra `Administração → Ferramentas → Gerenciador de Arquivos` (somente administrador).

## Áreas

- **Meus arquivos** em `public/uploads/arquivos`: criar pastas, enviar, listar/filtrar, baixar, renomear, excluir arquivo ou pasta vazia.
- **Arquivos essenciais** na raiz pública: somente `ads.txt` e `robots.txt`, editáveis com controle de alteração externa (SHA-256).
- **Mídias de notícias** permanecem no módulo Biblioteca de Mídias. **Backups** continuam na área específica com suas proteções.

## Limites e segurança

- Apenas usuário administrador (`manage_settings`), ações POST protegidas por CSRF; downloads por GET exigem sessão válida.
- Arquivos gerenciados são **públicos pela URL** `/uploads/arquivos/...`; nunca colocar senhas, documentos pessoais ou informações confidenciais.
- Upload: 8 MiB, somente imagens JPG/PNG/WebP válidas, PDF com cabeçalho esperado, TXT/CSV UTF-8. Tipos e extensões são conferidos no servidor; não há suporte a PHP, HTML, SVG, JS ou ZIP.
- Editor: somente TXT/CSV gerenciados, `ads.txt` e `robots.txt`; texto UTF-8 até 128 KiB e hash para detectar alteração após abrir. Evite editar `robots.txt` sem avaliar SEO.
- Não navega fora da pasta de arquivos gerenciados, não atravessa links simbólicos e não acessa `app/`, `storage/`, credenciais ou código de aplicação.
- Exclui permanentemente arquivos e pastas vazias; sem lixeira nesta etapa. Confirmação do navegador antes de excluir.
- Se a hospedagem negar escrita, ajustar as permissões no painel do host. Não usar permissões 777. Em Nginx, confirmar que a configuração de execução de PHP em uploads está bloqueada; o projeto só autoriza tipos não executáveis.
- O atualizador ignora `public/uploads/`; não insere a pasta nem os arquivos gerenciados em ZIPs de código. Faça backup dos arquivos importantes separadamente.

## Testes

`php tests/file_manager_221.php` testa escopo, travessia, symlinks, conflitos, operações de texto, ACL e integração de rotas. Testes locais não substituem homologação autenticada no servidor de produção.
