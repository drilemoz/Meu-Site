# Recuperação do atualizador e injeção de falhas — 1.0.115

## Problema encontrado

Na 1.0.114, o rollback verificava a cópia original de **cada arquivo imediatamente antes de restaurá-lo**. Um snapshot faltando no fim da lista permitia que outros arquivos já tivessem sido revertidos antes da falha ser descoberta. Na recuperação manual de uma atualização concluída, uma interrupção durante a própria reversão também podia impedir uma segunda tentativa porque o journal permanecia `complete` enquanto alguns arquivos já haviam sido restaurados.

## Alterações e garantias locais

1. `igua_updater_rollback_plan` valida **todos** os snapshots, caminhos e hashes antes de substituir qualquer arquivo; cópias adulteradas, ausentes e links simbólicos são rejeitados.
2. Uma atualização aplica `VERSION` por último. O callback de falha é opcional e utilizado apenas nos testes CLI, nunca por HTTP.
3. O rollback manual valida o estado `complete` e qualquer alteração posterior antes de registrar `recovery_needed`; ativa o marcador de manutenção e registra o estado recuperável **antes** de escrever no código. Se parar no meio, a segunda tentativa não depende de todos os destinos ainda estarem na versão nova.
4. O marcador só é encerrado com remoção verificada. Erro ao removê-lo causa falha explícita e, na recuperação manual, mantém o estado recuperável.
5. O índice `last.json` só é aceito se o ID corresponder ao journal privado do último job.

## Testes automatizados offline

`php tests/updater_failure_215.php` usa um diretório temporário separado da raiz do projeto. Os cenários cobrem:

- Falha injetada após **1, 3 e 4** substituições. `VERSION` continua por último. O rollback recupera originais e apaga arquivo criado na tentativa.
- Snapshot de `VERSION` adulterado: **nenhum arquivo de destino** é sobrescrito durante a tentativa de rollback.
- Arquivo alterado manualmente após instalação `complete`: rollback inteiro é bloqueado.
- Interrupção deliberada após 2 arquivos restaurados: nova tentativa de rollback é idempotente e conclui a restauração.
- Recuperação manual via journal/último job, incluindo instalação concluída, falha durante aplicação, entrada em manutenção e saída confirmada.
- Marcador de manutenção não removível: função não anuncia sucesso.

**Não** foram usados MariaDB, o banco de produção, uploads reais, credenciais nem servidor Plesk. Os testes não executam o caminho completo `igua_updater_install_zip` com banco + ZIP real, nem simulam `SIGKILL`, falta real de disco ou ausência de energia.

## Limitações e homologação

- A cópia de arquivos continua sendo sequencial, não há transação de sistema de arquivos. Pode haver falhas entre operações, inclusive falha ao persistir o próprio journal.
- Testes com MariaDB e ZipArchive devem rodar em servidor isolado e nunca com dados de produção. As rotinas existentes para backup lógico e mídia permanecem independentes de rollback do código.
- Para produção, obter **backup externo confirmado** do banco, do código, de `storage/` e das mídias; validar restauração em homologação e reservar janela de manutenção.
- Se uma implantação tiver resultado incerto, **não reenviar o mesmo POST**. Consultar `VERSION`, histórico, arquivos em `storage/updater/jobs/` e estado de manutenção. Não eliminar snapshots/journals sem documentação e validação do estado.
- O manifesto SHA-256 em pacote ZIP v2 sem assinatura comprova consistência com os próprios hashes, mas **não autentica a origem**. Usar fonte de confiança.

## Compatibilidade

Schema 37, backup lógico v12 e ZIP de atualização v2 mantidos. Esta versão não contém migrações e não muda dados, registros de notícias ou arquivos privados na hospedagem.
