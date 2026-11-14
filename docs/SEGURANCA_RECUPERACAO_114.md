# Auditoria de recuperação e testes defensivos — 1.0.114

Escopo: backups lógicos v12, mídia em ZIP, download de backups e manifestos de atualizações do portal. **Não é uma restauração em produção** nem muda o schema 37.

## Ajuste no código

- `app/backup.php`: a listagem (`list_backups`) e a resolução de download (`backup_path`) rejeitam links simbólicos e uma pasta de backups que seja um link simbólico. O nome permitido continua restrito a `manual|daily|weekly`, data/hora, sufixo hexadecimal e `.json` ou `.json.gz`.
- Arquivos reais e regulares com nomes válidos continuam aparecendo e podendo ser baixados.
- Nenhum backup é removido, migrado ou regravado por esta atualização; a lógica de criação e restauração permanece igual.

## Regressão automatizada sem banco

`php tests/recovery_safety_214.php` executa, com arquivos de teste efêmeros:

1. Backup v12 válido e GZIP; rejeição de GZIP corrompido, tabela faltante, tabela extra, registro inválido e ausência de administrador ativo.
2. Lista/download não seguem link simbólico para arquivo externo; nomes contendo `../` não são aceitos; arquivo regular ainda funciona.
3. Atualizador: contrato de manifesto v2, recusa de caminhos repetidos, `public/uploads`, `public/ads.txt`, excesso de tamanho, versão mínima PHP incompatível e falta de assinatura em pacote legado v1.
4. O teste não abre conexão MySQL, não executa instalação, não carrega senha real, não substitui uploads e remove os artefatos temporários que criou.

## Validação com banco isolado e ext-zip

A CI executa `tests/integration/restore_mariadb_134.php` com MariaDB e ZipArchive reais, **somente em banco vazio de nome `igua_ci_*`**, mediante `IGUA_TEST_ALLOW_WRITE=YES_ISOLATED_DB`. O teste cobre exportação/importação, backup de mídia, hash do manifesto, rejeição de ZIP adulterado e rollback diante de chave estrangeira inválida.

A suíte local `tests/*.php` não substitui essa integração. Onde `ext-zip` ou `pdo_mysql` não estiver disponível, não afirmar que a restauração real ou a atualização real foi testada.

## Matriz de risco e próximos testes assistidos

| Falha | Cobertura | Limite |
|---|---|---|
| GZIP/estrutura inválida | Unitário offline | Sem SQL |
| Fuga por link simbólico no download | Unitário offline | Requer permissão para criar symlink |
| Manifesto ZIP inválido | Unitários de manifesto e parser de pacote | Instalação real não simulada |
| Banco com FK inválida | Teste MariaDB isolado no CI | Não é teste do Plesk |
| ZIP com mídia adulterada | Teste MariaDB + ZipArchive no CI | Depende de extensões |
| Falha durante substituição/rollback de código | Proteções no atualizador, testes estruturais existentes | Falta teste de instalação com falha injetada em hospedagem equivalente |
| Limite de disco, timeout, indisponibilidade do banco | Documentação e mensagens de erro | Precisa de teste assistido em ambiente de homologação |

Para homologação: faça um snapshot de código + banco + `public/uploads/` + `storage/media/`, reproduza a versão instalada, confirme que backups podem ser **restaurados** e só então tente falhas de atualização deliberadas. Nunca execute injeção de falhas na instância em produção.

**Alerta:** SHA-256 de pacote v2 sem assinatura comprova consistência dos arquivos com seu próprio manifesto, não autoria. A ausência de assinatura não deve ser anunciada como autenticidade.
