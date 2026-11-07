# Atualizador 1.0.53 — vistoria e simulação sem instalar

## Escopo

A versão 1.0.53 adiciona no painel **Atualizações** uma vistoria somente leitura do ambiente e o upload de um ZIP assinado para **simular** a atualização. O diagnóstico enumera PHP, sodium, ZipArchive, cURL (opcional no modo manual), PDO MySQL, proteção de `storage`, layout `app/`+`public/`, escrita nas pastas e nos arquivos, conexão `SELECT 1`, manutenção pendente, espaço de disco reportado e limites PHP.

O diagnóstico não escreve no banco nem substitui código. A simulação extrai o ZIP para uma **pasta privada temporária** que depois é removida, para conferir o manifesto assinado, hashes SHA-256 de cada arquivo, versão maior e revisão de schema inalterada. Ela **não** executa backup, migração ou deploy.

Antes de realmente instalar, o motor repete a vistoria da lista de arquivos da release e **bloqueia** atualização se detectar permissão insuficiente, symlink, extensão essencial ausente, banco indisponível ou modo de manutenção ativo. Os relatórios diferenciam **falha** (bloqueio) e **aviso** (limites não confirmados). Não há promessa de espaço/cota real: `disk_free_space()` pode reportar o volume inteiro e não a cota contratual da hospedagem.

## Implantar sobre a 1.0.52

No painel da versão 1.0.52, selecione "Enviar um ZIP assinado" e use `Meu-Site-update-v1.0.53.zip` do release. Confirme senha administrativa. Como alternativa, use o patch Plesk v1.0.53 sobre os arquivos existentes com backup; preserve `storage/config.php`, `public/uploads`, banco e backups.

**Não** envie o pacote de hospedagem completo no campo de atualizações: somente o pacote assinado com `release.json` e `release.sig` é compatível.

Importante: corrigida a inclusão acidental de `public/uploads/.htaccess` e `.gitkeep` pelo gerador da 1.0.52, que não passavam na política de caminhos do verificador. O pacote 1.0.53 é validado contra a chave pública fixa da 1.0.52.

A extensão cURL só é necessária para consultar/baixar Releases; o modo manual exige sodium e ZipArchive. A mesma revisão do banco (36) é obrigatória. Não há migrações automatizadas, job Cron, atualizações sem aprovação ou restauração automática do banco.

## Testes

- `php tests/updater_152.php`
- `php tests/updater_153.php`
- `php tests/setup_recovery_151.php`
- `php tests/smoke.php`
- `php -l app/updater.php` e controladores/templates alterados.

Teste no PHP-FPM da BoaHost antes de usar em produção: vistoria, simulação com ZIP válido e adulterado, aplicação real da 1.0.53, rollback e preservação de fotos/credenciais. Uma simulação aprovada não garante que o deploy real seja concluído.
