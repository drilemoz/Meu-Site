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
