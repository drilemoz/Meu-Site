# Gerenciador de Arquivos — 1.0.124

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

## Ajustes visuais na versão 1.0.124

A interface foi refinada para celular. Agora há um cartão de contexto no topo para indicar se a pasta atual é apenas de leitura ou se permite edição. Na raiz do projeto, o atalho para uploads fica visualmente separado da ação de baixar ZIP, evitando competir com o comando principal.

Na pasta gerenciada, a barra de ações foi reorganizada em uma grade mobile-first: copiar, recortar e compactar aparecem em botões alinhados, o contador de itens selecionados virou um selo visual e o campo **Nome do ZIP** ocupa a linha correta. O menu recolhido passou a se chamar **Novo arquivo ou pasta**.

## Comandos acrescentados na versão 1.0.123

Na **raiz do projeto** e diretórios de leitura, marque até 20 itens e pressione **Baixar seleção em ZIP**. O ZIP é gerado para download autenticado em pasta temporária privada, não na raiz pública. Entradas bloqueadas/ocultas e symlinks não entram na exportação. Limites: 1.000 entradas e 64 MiB de conteúdo; `ZipArchive` necessário.

Em `public/uploads/arquivos/` e subpastas, a barra de seleção oferece copiar, recortar e compactar. As opções `⋮` por item oferecem cópia/recorte, ZIP, extração ZIP, renomear, mudar permissões seguras e excluir. O botão **Colar nesta pasta** aparece quando há itens copiados/recortados na sessão; expira em 15 minutos. A seleção em lote permite excluir arquivos e pastas depois de digitar `EXCLUIR`. Não há lixeira: o backup externo é indispensável.

ZIP enviado e ZIP extraído aceitam somente JPG/JPEG/PNG/WebP/PDF/TXT/CSV/ZIP, proibindo arquivos executáveis, caminhos relativos, links simbólicos e duplicatas. A extração é feita em pasta nova `<nome>-extraido`, sem sobrescrever existentes. Operações do depósito são limitadas a 300 itens/32 MiB totais e 8 MiB por arquivo. O ZIP compactado fica limitado a 8 MiB. ZIP exige a extensão PHP `ZipArchive`.

Permissões permitidas: arquivo `0644` ou `0600`; diretório `0755` ou `0700`. Permissões dos arquivos do restante do projeto podem ser consultadas, não alteradas. O gerenciador não edita PHP, JavaScript ou CSS do portal nem arquivos internos. Toda gravação permanece sujeita à permissão real do usuário PHP na hospedagem.

### Testes

- `php tests/file_manager_223.php` verifica movimentação, colisões, recusa de symlink/travessia, exclusão em lote, permissões e contrato de interface.
- `php tests/file_manager_224.php` verifica a presença dos blocos visuais novos e o contrato estrutural do layout do explorador.
- A extensão ZIP não estava disponível no ambiente local de desenvolvimento: compactar, extrair e exportar ZIP necessitam de teste real na hospedagem ou em CI com `ZipArchive`.
