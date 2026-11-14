# Auditoria de arquivos, compatibilidade e backup — versão 1.0.76

## Escopo e procedimento

Fonte examinada: `Meu-Site-v1.0.75-fonte-completo.zip` (432 arquivos). Auditoria offline de inventário, regras de empacotamento, documentos, controladores PHP, testes, workflow CI, módulos front-end, banco/backup e metadados. Foram realizados testes de regressão PHP e JavaScript, checagens sintáticas, verificação dos 16 módulos CSS e 8 partes do editor JS e validação do conteúdo dos ZIPs novos.

## Arquivos de outro projeto

Nenhum Kotlin, Gradle, APK, AndroidManifest ou arquivo do projeto Uai Cortes foi encontrado na fonte 1.0.75. O arquivo `tests/fixtures/backup133_duplicate_names.zip` contém entradas intencionalmente duplicadas para **teste negativo de segurança**, não é um backup do usuário nem pertence à hospedagem. O workflow MariaDB é próprio deste projeto PHP.

`storage/` e `public/uploads/` preservam apenas placeholders no código-fonte; dados de produção não entram nos pacotes. O teste de pasta privada foi aplicado ao conteúdo gerado, não pressupõe que os logs runtime da hospedagem sejam vazios.

## Compatibilidade das funcionalidades existentes

| Área | Verificação estática/automatizada | Limitação pendente |
|---|---|---|
| Admin mobile e modal de atualização | Testes PHP/JS, CSS e estados de upload; manifesto ZIP v2 | Navegador Android e PHP-FPM reais |
| Atualização do site | Revisão de código, reautenticação, CSRF, manifesto, tamanho e hash; schema 36 mantido | Instalação real no provedor |
| Backup lógico e ZIP completo | APIs, formatos e testes de contrato/regressão; fixture de entradas duplicadas | Criar/restaurar pacote real com ZipArchive + MariaDB na hospedagem/CI |
| Google Analytics 4 | Identidades, controle de consentimento e testes locais da Data API | Recebimento real exige credenciais e acesso autorizado ao Google |
| Search Console | Metatag, DNS TXT e verificador local de páginas | Propriedade verificada/indexação dependem do Google |
| Segurança | Rotas e controles de sessão, eventos e permissões existentes | Não substitui logs de servidor/CDN |
| Conteúdo e mídia | Fallback de imagens e isolamento dos arquivos privados preservados | Testar conteúdo, fotos e permissões reais após publicar |

## O que o backup realmente protege

- **Gerar backup agora**: somente os registros das tabelas MySQL; não inclui bytes das fotos ou anexos.
- **Baixar pacote completo ZIP**: JSON/GZIP do banco e arquivos **referenciados** por matérias, galeria, biblioteca de mídia e anúncios; exige PHP `zip`.
- **Nenhuma opção** copia código, configuração do servidor, credenciais privadas e todos os uploads não referenciados. É necessário backup externo integral separado.

Veja [BACKUP.md](BACKUP.md) para limites e procedimento de recuperação.

## Limpeza documental e compatibilidade

Foram removidos **77 arquivos Markdown históricos ou substituídos**, especialmente relatórios `VALIDACAO_*`, inventários por versão, atualizadores e instalação dual antigos. Permanecem guias funcionais e um índice canônico, [INDICE.md](INDICE.md). `README.md`, `AGENTS.md`, `RELEASE_NOTES.md` e `CHANGELOG.md` foram atualizados, preservando no CHANGELOG os registros históricos, sem presumir que guias antigos continuem válidos.

## O que estes testes NÃO comprovam

Sem `pdo_mysql`/`ZipArchive` e sem acesso administrativo à hospedagem, não houve restauração real em MariaDB, teste de interface no Chrome Android, login real, upload real na produção nem verificação de recepção dos dados Google. Os resultados locais são evidência de regressão estática/automatizada, não certificação de ausência de vulnerabilidades ou falhas em produção.

## Revisão v1.0.86 — Relatórios GA4
- Adicionado relatório sob demanda com quatro consultas em lote à Analytics Data API, sem alterar banco schema 36, consentimento público, Search Console, PageSpeed e atualização do site.
- Validações offline de JWT/escopo, classificação das métricas, tratamento de ausência, TTL, ACL/CSRF e escaping de dados externos. O ambiente local não acessa uma propriedade real do Google nem substitui o teste de hospedagem ou no celular.

### Verificação 1.0.91

Foram adicionados testes de contrato de anexos e da UI simplificada, além de executar os conjuntos PHP/JS e validação do build de CSS/editor. O ambiente local não tem `pdo_mysql` nem `ZipArchive`; por isso, uploads HTTP reais, autenticação MariaDB, extração do ZIP/Office e testes de interface num Android real **não** foram validados. Esses testes permanecem necessários após implantar na hospedagem.

### Revisão 1.0.93 — Pop-ups do painel

A regra de integrações que definia `100vw` e `100dvh` no celular foi substituída, para os diálogos com classe `igua-modal`, por cartão centralizado com altura limitada e rolagem interna. O componente é CSS/JS puro, sem chamadas externas. O PageSpeed foi dividido em abas acessíveis, mantendo os formulários e limites existentes. Testes locais validam estrutura e comandos; testes de responsividade via Chromium headless com fixture demonstraram largura 366px para viewport 390px, sem rolagem horizontal da página. O resultado não garante identidade visual em aparelhos Android específicos, e os fluxos autenticados GA4/PageSpeed na hospedagem permanecem pendentes.


### Revisão 1.0.94 — Wizard e publicações

Os seletores nativos de arquivos do wizard não recebem a interface global duplicada. SEO utiliza rascunhos temporários em pop-ups; títulos, slugs e descrições são aplicados somente quando o usuário confirma e não publicam a notícia. Os cartões de Notícias e Visão geral são gerados pelo mesmo componente PHP, com datas relativas calculadas no fuso America/São_Paulo e conferência de CSRF/ACL. Foram aprovados testes PHP/JS locais, sintaxe e integridade de pacotes. O teste em Chromium foi impedido por bloqueio de navegação neste ambiente, portanto não afirmar validação visual automatizada do novo layout; continuam pendentes testes físicos Android, instalação e uploads reais na hospedagem.

### Revisão 1.0.95 — Data inválida na listagem / compatibilidade PHP 8.3

O GitHub Actions falhou antes dos testes com MariaDB real, em `tests/news_wizard_194.php`, ao processar a string `invalid` na função de data relativa. Corrigida a conversão para `date_create_immutable()` com verificação de retorno `false` e avisos. `tests/news_relative_date_195.php` cobre datas válidas, ausentes e impossíveis. Schema 36 e rotinas de backup/restauração não foram modificados; reexecução de CI e validação em produção continuam pendentes.


### Revisão 1.0.96 — Aplicativos e Páginas Legais

- O novo módulo integra o painel e as rotas públicas, com migração aditiva **schema 37**, quatro tabelas, snapshots publicados e backup lógico **v12**.
- Testes offline: `tests/legal_apps_196.php`, PHP/JS legado, CSS/editor e auditoria estrutural. O novo teste MariaDB real está em `tests/integration/legal_apps_mariadb_196.php` e no workflow CI, mas sua execução não é comprovada pelo ambiente sem PDO MySQL.
- **Pendente antes de produção:** testar migração real a partir de schema 36 com cópia do banco; login/ACL/CSRF; gravação/recuperação de revisões e backup/restore; renderização móvel e reescrita Apache/HTTPS com status HTTP 200/404; validação de logotipo; revisão jurídica do conteúdo e requisitos OAuth atuais.
- **Não instalar via atualizador normal com schema divergente**; siga `docs/MIGRACAO.md`. Sem migração na hospedagem, as páginas novas não ficam disponíveis.

## Nota de integração CI (v1.0.135)

O workflow 47 falhou em `installer_create_mariadb_231.php` por entrada pela ponte Docker (`172.18.0.1`), não por permissão insuficiente da conta local criada. O teste passou a executar no namespace de rede do MariaDB com validação da origem MySQL, sem abrir privilégios remotos. A reexecução em runner GitHub com `sudo nsenter` e a etapa posterior do atualizador ainda não foram comprovadas. Veja `docs/CI_MARIADB_LOCAL_135.md`.
