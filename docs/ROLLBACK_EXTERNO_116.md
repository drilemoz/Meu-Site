# Rollback contra alterações externas — versão 1.0.116

## Defeito reproduzido

A versão 1.0.115 verificava os hashes dos arquivos **novos** antes de restaurar um job `complete`, mas nos estados `prepared`, `applying` e `recovery_needed` conferia apenas os snapshots privados. Portanto, uma edição manual do código enquanto a atualização estava interrompida podia ser substituída silenciosamente na recuperação. O teste adversarial `tests/updater_foreign_changes_216.php` falhou na base 1.0.115, reproduzindo esse comportamento.

## Correção

O plano de recuperação (`igua_updater_rollback_plan`) passa a verificar **todos os destinos antes de modificar qualquer arquivo**. Para instalações interrompidas, cada arquivo de destino precisa estar em um dos estados conhecidos:

- conteúdo original, confirmado pelo hash do snapshot; ou
- conteúdo da versão nova, confirmado pelo manifesto gravado no journal;
- ausente, **somente** se o arquivo ainda não existia antes da atualização.

Um arquivo inesperadamente ausente ou com hash de terceiros impede o rollback automático integral, evitando a perda de alterações manuais. O bloqueio também vale para arquivos novos que tenham sido modificados após a falha. Nos jobs `complete`, a regra anterior continua mais rígida: exige todos os arquivos iguais aos da versão instalada. Hashes e snapshots inválidos continuam bloqueados.

Essa mudança **não autoriza apagar o journal ou o marcador de manutenção**. Em caso de bloqueio, preservar os snapshots e resolver a divergência manualmente, com cópia externa, antes de tentar recuperação assistida.

## Testes e limites

`php tests/updater_foreign_changes_216.php` valida em pasta temporária: instalação interrompida com arquivo alterado manualmente, estado `prepared` com alteração externa, arquivo novo substituído por conteúdo estranho e recuperação legítima de estado misto antigo/novo. A suíte `tests/updater_failure_215.php` continua verificando os cenários anteriores, inclusive a retomada de rollback interrompido.

O teste **não** acessa banco, arquivos reais do portal, Plesk ou rede. Neste ambiente CLI não estão disponíveis `pdo_mysql` nem `ZipArchive`, e não foi possível executar a homologação integrada com MariaDB. Os testes não simulam disco cheio, travamento real do PHP ou queda de energia. A operação não é atômica.

Compatibilidade: schema 37, formato de backup v12 e pacote ZIP v2. Nenhuma migração ou mudança no armazenamento de notícias.
