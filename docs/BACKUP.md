# Backup e restauração · Meu Site 1.0.43

## Backup manual

No painel, acesse **Configurações → Backups e restauração → Gerar backup agora**. Um arquivo `.json.gz` será salvo em `storage/backups` se a extensão zlib existir; caso contrário, será `.json`. Use **Baixar** para guardar uma cópia no celular, computador ou armazenamento externo.

O formato lógico atual é **v11** e preserva dados editoriais, publicidade, transporte, métricas, mídias catalogadas e configurações. Entre as tabelas estão:
- `users`: nomes, e-mails e **hashes** de senha (dados sensíveis);
- `categories`;
- `author_profiles`: perfis editoriais de assinatura;
- `editorial_sources`: catálogo de fontes;
- `articles`: títulos, conteúdo, slugs, identificadores de fotos, estatísticas;
- `settings`;
- `backup_logs`;
- `bus_routes`, `bus_stops` e `bus_departures`: linhas, tarifa integral opcional, paradas e partidas cadastradas;
- `bus_import_queue`: pendências de revisão de DER-MG/ANTT.
- `ad_placements`: posições e códigos de blocos de anúncio;
- `ad_campaigns`: campanhas locais, destinos, datas, status e contadores agregados.
- `article_categories`, `article_blocks`, `article_media`, `article_drafts`, `article_import_logs` e `article_revisions`: dados do editor e histórico.
- `analytics_events`: métricas internas.
- `media_library`: índice da biblioteca de imagens (não os arquivos físicos).
- `article_slug_redirects`: nomes de URL anteriores vinculados às notícias, para HTTP 301 após edição.
- Senhas SMTP e arquivos físicos de banners não são incluídos.

**O backup JSON não inclui os arquivos físicos de fotos e anexos.** Se não usar o pacote completo ZIP, arquive `public/uploads/` e `storage/media/` separadamente, por exemplo:

```bash
# Na raiz do projeto, se tiver tar instalado:
tar -czf iguanews-imagens.tar.gz -C public uploads
tar -czf iguanews-anexos.tar.gz -C storage media
```

Não inclua `storage/config.php` em backups públicos ou repositórios.

## Restauração

1. Faça backup manual do estado atual e baixe o arquivo.
2. Acesse **Configurações → Backups e restauração → Restaurar**, selecione um `.json` ou `.json.gz` gerado pelo IguaNews.
3. Digite a senha do administrador e confirme que deseja substituir os dados.
4. O sistema verifica formato, versão, colunas, limites e conta de administrador; cria um backup preventivo, restaura dados em transação InnoDB e encerra a sessão.
5. Faça login usando a credencial contida no backup restaurado.
6. Restaure à parte os arquivos de `public/uploads/` e `storage/media/`, caso tenha migrado a hospedagem usando somente JSON.

> A restauração substitui todas as linhas das tabelas de aplicação e pode apagar notícias recentes. Experimente o processo em ambiente de testes antes de depender dele.

## Pacote completo ZIP (mídias v2)

Na administração, **Backups → Baixar pacote completo ZIP** exporta o banco lógico v11, as fotos e os anexos referenciados. Exige a extensão PHP `zip`. A exportação admite **até 300 MiB de mídias e 3000 arquivos**; o importador aceita o ZIP de **até 350 MiB**, contando também o banco e manifesto. Para conjuntos maiores, use o JSON/GZIP e transporte as pastas de mídias por outro meio.

A restauração aceita pacotes de mídia **v1 e v2**, verifica o SHA-256 de cada arquivo em blocos de 64 KiB, prepara cópias temporárias de fotos novas e rejeita arquivos com nomes perigosos ou conteúdo diferente de um arquivo já existente. Só então cria um backup preventivo do banco e aplica a restauração. Se a transação do banco falhar, tenta retirar os arquivos novos adicionados. **Não há garantia de transação atômica entre MySQL e sistema de arquivos**, especialmente após falta de energia ou encerramento abrupto do PHP.

- Garanta espaço livre para o ZIP enviado, sua extração temporária e o banco; no celular, isso pode exigir mais de 600 MiB livres para um pacote de 300 MiB.
- Configure `upload_max_filesize` com valor superior ao ZIP e `post_max_size` um pouco maior. `memory_limit`, `max_execution_time` e as restrições da hospedagem também podem impedir uploads grandes.
- Teste a restauração em banco e diretórios isolados antes de sobrescrever uma instalação utilizada.
- Nunca atualize o portal copiando a pasta `storage/` ou apagando `public/uploads/` da instalação existente.

## Agendamento

O executável `bin/backup.php` gera um backup do dia via PHP CLI. Aos domingos usa o prefixo `weekly`, nos outros dias `daily`. Apaga backups excedentes, mantendo até **7 diários e 4 semanais**. A execução deve ser configurada **fora do código** pelo agendador do seu servidor:

```cron
0 3 * * * /usr/bin/php /CAMINHO/IguaNews/bin/backup.php >> /CAMINHO/iguanews-backup.log 2>&1
```

Em Termux, use o agendador disponível em seu ambiente, lembrando que as restrições de segundo plano do Android podem impedir a execução.

## Segurança e retenção

- O arquivo de backup contém hashes de senha e textos possivelmente confidenciais; armazene-o em local privado.
- `storage/backups/` precisa ficar **fora do document root**.
- Não dependa apenas de cópias no mesmo aparelho ou servidor.
- É recomendável criptografar os backups antes de sincronizar com nuvens externas; criptografia automática não está implementada nesta versão.
- Verifique o tamanho dos arquivos e restaure periodicamente uma cópia em uma instalação de teste.
- O sistema não faz backup automático no Google Drive. Esse envio precisa ser configurado separadamente, com autenticação e criptografia.

## Importação durante o primeiro setup (1.0.2)

Em um **servidor novo**, crie uma base vazia no MySQL/MariaDB e execute `/setup.php`. Após verificar a conexão, selecione **Importar portal anterior** e envie um backup IguaNews `.json`/`.json.gz` da instalação antiga. Isso importa dados e usuários sem necessidade de cadastrar novo administrador. Entre depois com as credenciais antigas. O recurso atual aceita backups IguaNews v1 a v11, não executa arquivos SQL arbitrários e **não importa fotos** da pasta `public/uploads/`. Consulte `docs/MIGRACAO.md`.

## v3: módulo ônibus

Desde 1.0.5, os backups incluem `bus_routes`, `bus_stops` e `bus_departures`. O parser aceita v1/v2 com esses três conjuntos vazios. Backups v3 requerem IguaNews 1.0.5+. As imagens permanecem fora do backup.

## v4: fontes oficiais de ônibus

Desde a 1.0.6, a fila `bus_import_queue` também é armazenada. A restauração de v1, v2 e v3 segue suportada. Para restaurar v4, use IguaNews 1.0.6+. A importação não dispensa o backup separado das fotos nem valida automaticamente a situação real das partidas.

Desde 1.0.7, o formato v5 inclui `bus_routes.fare_amount` e restaura formatos v1–v4. Backups v5 requerem IguaNews 1.0.7 ou superior.

## Compatibilidade atual e histórico

**Na versão 1.0.21:** o exportador produz backup lógico **v9**, o parser reconhece **v1 a v9** e o pacote completo de mídia permanece **v2** (restaura pacotes v1/v2). Não houve migração de esquema nesta versão. Backups novos não devem ser restaurados em versões antigas do portal. Os itens v3–v6 acima são histórico de evolução dos formatos anteriores; não definem os limites atuais. Senhas SMTP em variáveis de ambiente e `storage/config.php` não são incluídos em pacote público.

## Mudança de esquema e compatibilidade v10 (IguaNews 1.0.24)

A atualização cria apenas a tabela `article_slug_redirects` (slug único, artigo vinculado e data). Use conta de banco com `CREATE TABLE` ao entrar no painel após atualizar. **Não exclua** a tabela ao atualizar versões. O backup lógico v10 contém a tabela; restauração de backups v1–v9 inicializa o histórico vazio. Um backup v10 não pode ser aberto por um IguaNews anterior à 1.0.24. O pacote completo de mídia continua no formato v2.

Antes da atualização, faça um backup v9 da instalação anterior, guarde cópia externa das fotos e confirme `storage/config.php`, `storage/backups/`, `storage/media/`, `storage/analytics.key` e `public/uploads/`. Testes isolados não substituem restauração em MariaDB.

## Integridade e migrações na 1.0.33

O backup lógico **continua no formato v10**; a exportação recusa mais de 40 MiB de JSON descompactado ou mais de 100 mil registros em uma tabela, para não gerar um arquivo que a restauração atual não consiga ler. Na v1.0.33 o backup era estruturado em memória; desde a v1.0.43, a exportação grava as linhas progressivamente no arquivo. A importação ainda precisa decodificar o JSON e requer memória suficiente. O limite de upload da interface pode ser menor que 40 MiB; confira `upload_max_filesize` e `post_max_size` do PHP.

O pacote completo **continua v2**, mas novas exportações incluem `db_sha256` no `manifest.json`. O importador verifica o hash quando presente, recusa arquivos extras e duplicatas e preserva a compatibilidade de pacotes v1/v2 anteriores sem hash. Esses hashes detectam corrupção, **não autenticam o remetente**: importe somente pacotes confiáveis.

O painel mantém um registro de revisão de esquema na tabela `settings`, atualizado para `33` somente após completar as migrações. Isso reduz consultas de esquema nas visitas seguintes. O diagnóstico do painel ainda deve ser usado para conferir o banco e detectar alterações manuais. A restauração de backups antigos pode reintroduzir um marcador antigo, que será atualizado no login seguinte.

Sem MariaDB e `pdo_mysql` neste ambiente de desenvolvimento, os testes de integração de restauração e migrações reais são pendentes. Valide com uma **cópia do banco**, confira todas as fotos/anexos após restaurar e não use uma instalação de produção como primeiro teste.

## Ensaios de recuperação 1.0.34

A versão 1.0.34 fornece um teste integrado para MariaDB e ZipArchive, executável em banco isolado ou via GitHub Actions. Consulte `docs/INTEGRACAO_MARIADB_134.md`. Não execute o teste no banco de produção.

## Exportação com menor consumo de memória e ZIP transitório (Meu Site 1.0.43)

- **Formato lógico v11, aceitando restauração v1–v11.** O exportador percorre as tabelas sob transação `REPEATABLE READ` e escreve diretamente em um arquivo `.backup-XXXXXX`, usando GZIP em fluxo quando há zlib. O limite continua sendo **40 MiB descompactados** e **100.000 linhas por tabela**. O arquivo só recebe seu nome definitivo após a gravação e a transação completarem; em erro, o temporário é descartado. A biblioteca PDO/MySQL ainda pode manter buffers internos dependendo da configuração do driver; o maior ganho comprovado é evitar o segundo conjunto integral de dados em arrays PHP.
- **ZIP completo v2.** É preparado como `.bundle-XXXXXX` e só ganha nome público após estar íntegro e dentro do limite de 350 MiB. Durante **Baixar pacote completo ZIP**, o pacote recém-criado é excluído do servidor depois da resposta (inclusive por rotina de encerramento); o `.json.gz` gerado junto ao pacote **permanece em `storage/backups/`**. Se o download falhar, use o backup lógico e tente novamente o pacote; não confunda o arquivo lógico com cópia dos anexos/fotos.
- **Retenção:** continuam 7 backups `daily` e 4 `weekly`, executada na rotação; backups `manual` não são apagados automaticamente. Pacotes ZIP antigos, criados em versões anteriores, também **não são apagados** por esta atualização. A limpeza periódica em `rotate_backups()` atinge apenas temporários com nomes exatos `.backup-XXXXXX`/`.bundle-XXXXXX` e data de modificação de pelo menos 24h atrás, sem seguir links simbólicos.
- **Testes:** executar `php tests/backup_143.php`, suíte completa e o teste isolado de MariaDB/ZIP. A compatibilidade JSON está coberta por comparação do conteúdo exportado com a serialização anterior, mas testes locais não substituem validação de restauração em servidor.
