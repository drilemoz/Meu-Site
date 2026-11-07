# Resultados Eleitorais — fase 1 (Meu Site 1.0.41)

## Escopo entregue

- Admin → Resultados Eleitorais com ativação desativada por padrão (`settings.elections_enabled`), só papel `admin` (`manage_settings`).
- Cadastros de eleições por ano, âmbito (municipal/geral), cargo, turno, UF e cidade; data, código IBGE e URL de referência opcionais; candidaturas com nome, nome na urna, número, partido e estado cadastral.
- Alterações com POST/CSRF, SQL preparado, escaping HTML, validação servidor, unique por (eleição, número). Arquivamento de pleito não remove dados.
- **Sem apuração, percentuais, fontes importadas, resultados oficiais, publicação ou sincronização automática.** Os dados digitados manualmente não são certificados pela Justiça Eleitoral.
- Migração idempotente `igua_schema_revision=35`: cria `elections` e `election_candidates` e depois registra marcador. Exige `CREATE TABLE` para o usuário SQL.
- Backup JSON/GZIP **v11** inclui as duas tabelas; versões antigas 1–10 continuam aceitas e preenchem as tabelas ausentes com arrays vazios. O pacote de mídias mantém manifesto v2. Sessões persistentes continuam fora dos backups.

## Próximas fases planejadas (não implementadas)

- 1.0.42: resultados por abrangência e cargo, com proveniência, estados, auditoria, visualização pública e verificação de apuração.
- 1.0.43: importação de arquivos oficiais do TSE com validação de layout, identificação do pleito e idempotência.
- 1.0.44: sincronização limitada com cache, registro de origem e de horários, sem consultar o TSE a cada visitante.
- 1.0.45: comparações históricas e testes integrados.

## Fontes oficiais para desenvolvimento futuro

- https://www.tse.jus.br/eleicoes/informacoes-tecnicas-sobre-a-divulgacao-de-resultados
- https://dadosabertos.tse.jus.br/

Em 2026, os dados de divulgação são publicados em arquivos JSON segundo os documentos EA10–EA20. O modelo de eleições gerais é diferente dos dados municipais históricos, portanto importar não consiste em aceitar CSV/JSON arbitrário sem conferência. Verifique documentação vigente a cada pleito.

## Testes pendentes

Executar migração, CRUD e backup v11 com MariaDB real e registrar cenários de erro de FK e duplicidade. Testar interface em Android antes de produção. Faça backup externo antes de atualizar um banco existente.
