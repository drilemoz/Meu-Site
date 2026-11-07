# Atualizador de código — Meu Site 1.0.52

## Funcionalidade e modo de operação

A partir da v1.0.52, administradores possuem o menu **Atualizações** no painel. Com PHP 8.1+, as extensões `zip`, `sodium` e `curl`, podem informar o repositório do GitHub, verificar manualmente ou ao abrir a tela (cache de 30 min), e instalar uma versão posterior com uma confirmação por senha. Há envio manual de pacote ZIP **assinado**. Não há execução programada em segundo plano: sem Cron a detecção depende de abrir a tela do painel. O atualizador NÃO instala automaticamente apenas ao detectar uma versão nova.

A hospedagem não é fixa; basta PHP-FPM ou CGI com permissões de escrita dos arquivos, MySQL/MariaDB, acesso HTTPS ao GitHub e as extensões indicadas. O domínio da BoaHost não está codificado no mecanismo. Instale **v1.0.52 manualmente** pela primeira vez; a interface é usada para as próximas versões.

### Publicação das próximas versões

1. Guarde **a chave privada de assinatura** fora da pasta do projeto, do GitHub e da hospedagem, em armazenamento seguro e com backup. A v1.0.52 tem uma chave pública fixa, que não é secreta.
2. Com `VERSION` e documentação sincronizados, execute:

   `python3 tools/build_update_package.py --key-file /caminho/privado.key --output /tmp/Meu-Site-update-v1.0.53.zip`

   Necessário PHP CLI com `sodium` e Python 3. A chave privada em base64 contém 64 bytes de chave Ed25519. **Nunca coloque a chave privada no ZIP nem como arquivo do site.**
3. Faça o release público no GitHub com tag `v1.0.53` e anexo de nome **exato** `Meu-Site-update-v1.0.53.zip`. A versão do pacote e `VERSION` devem coincidir. Assinatura em `release.sig` cobre os bytes originais de `release.json`, que traz SHA-256/tamanho de cada arquivo.
4. No painel → Atualizações, cadastre proprietário e nome do repositório. A consulta usa `api.github.com/repos/{owner}/{repo}/releases/latest`. Em caso de falha da consulta, a instalação manual do mesmo ZIP assinado continua disponível.

### Proteções

- Só administrador autenticado com CSRF e revalidação da senha pode instalar/restaurar. Alterações dos arquivos passam por validação de caminhos, hashes por arquivo, versão crescente, chave Ed25519 fixa e bloqueio de symlinks. Pacotes de hospedagem tradicionais não são aceitos pelo atualizador.
- O motor faz um backup lógico do MySQL (`storage/backups/manual-*`) e cópias dos arquivos de código antes de trocar qualquer arquivo. As substituições usam arquivo temporário + `rename` por caminho, e o estado persistente é registrado sob `storage/updater/jobs/` (pasta privada).
- Imagens publicadas (`public/uploads/`), dados persistidos (`storage/`), segredos, e backups não entram na distribuição. Não há operação DROP/TRUNCATE/DELETE do banco pelo atualizador.
- Versões que alteram `IGUA_SCHEMA_REVISION` são **bloqueadas**; essas requerem migração assistida separada, pois DDL de MySQL pode ter auto-commit. Não há promessa de rollback automático do banco. Assim, a recuperação da v1.0.52 restaura somente código.
- Durante substituição, o site responde HTTP 503. Em caso de interrupção, o acesso ao login e à tela Atualizações permanece permitido para restaurar o código. Não apague `.update-maintenance` manualmente sem investigar o histórico.
- O histórico inicial guarda a última operação. A recuperação requer que o código não tenha sofrido mudanças posteriores a uma atualização concluída. Conserve o backup MySQL fora do servidor para desastres.

### Limitações documentadas

- ZipArchive, cURL e sodium são requisitos adicionais **do atualizador**. O portal permanece funcional se uma extensão faltar, mas atualizar fica indisponível.
- Apenas versões maiores e com a **mesma revisão do banco** são aceitas; atualização com mudança de schema não é executada automaticamente.
- GitHub privado e tokens de API não são suportados no primeiro motor; use Releases públicos. GitHub API pode impor cotas. Os downloads são limitados a 32 MB compactados e 80 MB descompactados.
- Não há exclusão automática de arquivos obsoletos. Se futura versão retirar um arquivo sensível, deverá ser aplicada por migração assistida, não por este mecanismo.
- Cada requisição depende dos limites reais de PHP-FPM (`max_execution_time`, memória, espaço em disco). Por isso, teste primeiro em uma segunda instância do site, com backup SQL restaurável e fotos de teste. Testes isolados não garantem atualização em hospedagem compartilhada.
- O atualizador não é WordPress, nem instala plugins/temas WordPress; replica apenas o fluxo de verificação, instalação e recuperação do núcleo da aplicação.

### Validação da release

`php -l app/updater.php && php -l app/admin_controllers/updater.php && php -l app/admin_views/routes/updates.php && php tests/updater_152.php && php tests/smoke.php`

Depois, validar com MariaDB real e PHP-FPM no Plesk. Verificar: conta com papel editor não atualiza; ZIP adulterado é recusado; versão antiga é recusada; pacote assinado novo da mesma revisão aplica; notícias, usuários e fotos persistem; atualização interrompida pode restaurar o código; erros ficam registrados sem credenciais em arquivos públicos.
