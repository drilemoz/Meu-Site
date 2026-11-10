# Atualizador: permissões de código (1.0.144)

A vistoria anterior podia mostrar `Sem permissão ou caminho inseguro: VERSION, app/updater.php, public/admin.php` porque exigia `is_writable()` nos arquivos. O instalador usa `tempnam()` no **diretório de destino** e `rename()` para substituir. Portanto, o arquivo antigo pode estar somente leitura, desde que o instalador consiga ler o arquivo para backup, atravessar e escrever no diretório.

## O que mudou

- O painel testa os diretórios de destino e recusa links simbólicos ou arquivos não legíveis, mantendo a verificação de caminhos permitidos.
- Se o diretório não for gravável, a falha é real e o painel continua a bloquear a instalação em vez de fingir sucesso.
- Nada executa `chmod`, `chown` ou grava arquivos durante a vistoria.

## Em hospedagem compartilhada

Use o gerenciador de arquivos/SSH e verifique **proprietário e permissão de escrita** da raiz do projeto, `app/` e `public/` para o usuário que executa PHP. Se forem pastas de outro usuário, só administrador/provedor poderá corrigir. Evite `chmod 777` e não publique `storage/`. Se o FTP/SFTP for o único usuário com permissão, a atualização web direta não pode funcionar sem um mecanismo de implantação externo.

## Em AWS EC2/VPS

Não desative `IGUA_DEPLOY_VPS=1` para contornar o bloqueio e **não dê escrita de código ao www-data**. A arquitetura do projeto usa a conta `iguanews-deploy` e um worker dedicado. Após uma implantação inicial por SSH e backups externos, consulte `docs/INSTALADOR_VPS_142.md` e use a vistoria CLI dedicada de `bin/aws-deploy-check.php`. O instalador VPS da versão 1.0.142 pode preparar os serviços após checagem de segurança, sem alterar o site de testes.

## Uso e limites

Publicar um novo ZIP de atualização no GitHub **não modifica por si só** as permissões do servidor nem corrige uma versão anterior com pastas realmente inacessíveis. Primeiro implante o arquivo corrigido por um canal autorizado (SSH, FTP ou deploy dedicado), e só então use o atualizador nas próximas versões. A instalação exige manifesto ZIP v2, compatibilidade de banco, autenticação administrativa, backup e rollback.
