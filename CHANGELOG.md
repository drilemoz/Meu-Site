## 1.0.78 — 07/11/2026 — Integração eleitoral e prévia de serviços no CI
- Corrigida a inclusão ausente de `app/schema_migrations.php` no teste eleitoral MariaDB (`elections_mariadb_141.php`), que falhava com `Call to undefined function igua_schema_revision()` após o teste de login persistente passar.
- Acrescentada regressão `tests/integration_bootstrap_178.php` que verifica a dependência antes do workflow real.
- Completados os dados sintéticos do renderizador `render_integrations_169.php`, evitando warnings de variáveis indefinidas; nenhuma consulta externa é executada.
- Corrigido o selo visual da Central de Integrações para refletir a versão real, em vez de permanecer em 1.0.72.
- Mantidos schema MySQL 36, cookies seguros, dados reais e pacotes sem arquivos privados. A execução do workflow MariaDB após a alteração ainda é necessária.

## 1.0.77 — 07/11/2026 — Correção da integração de login persistente no CI
- Corrigido `tests/integration/remember_mariadb_140.php`: o runner CLI agora simula explicitamente HTTPS antes de testar a emissão do cookie, sem liberar cookies em HTTP na aplicação.
- Saída de verificações do teste é bufferizada até a operação de cookie terminar, evitando `headers already sent`.
- Adicionado `tests/remember_cli_headers_177.php` para reproduzir e evitar regressões desse cenário sem depender de MariaDB.
- Os logs do workflow #4 confirmaram integração real MariaDB/ZipArchive de backup e restauração aprovada; a falha seguinte ocorreu somente no ensaio de login persistente.
- Sem mudanças de schema (36), regras de cookies de produção, interfaces ou arquivos privados. GitHub Actions real ainda precisa ser reexecutado.

## 1.0.76 — 07/11/2026 — Auditoria completa e documentação atual
- Inventariados código, scripts, GitHub Actions, configuração, testes, backups e pacotes; confirmada identidade web/PHP e ausência de Android/Uai Cortes.
- Limpeza de arquivos Markdown históricos (relatórios `VALIDACAO_*`, inventários `RELATORIO_*` e guias antigos) e centralização em `docs/INDICE.md`, `BACKUP.md`, `ATUALIZADOR.md`, `INTEGRACOES.md` e `TESTES_CI.md`.
- README, AGENTS e notas de release reescritos para refletir as funcionalidades atuais sem recomendações ultrapassadas.
- Explicação no painel dos dois formatos de backup e das mídias não incluídas; nenhuma mudança na lógica de restauração.
- Adicionados verificador de estrutura/documentação e regressão própria, além de etapa no GitHub Actions.
- Schema 36, ZIP v2, GA4, Search Console, mídia e interface mantidos. Tests locais documentados em `docs/AUDITORIA_176.md`.

## 1.0.75 — 06/11/2026 — Atualização por janela central
- "Enviar atualização" fica ao lado de "Releases", na barra de ações. Em telas estreitas, o botão apresenta "Enviar ZIP" sem perder o nome acessível da operação.
- O formulário manual saiu da expansão permanente e abre em `<dialog>` centralizado, com arquivo ZIP, senha e botão Enviar.
- Antes de transmitir o arquivo, surge uma confirmação explícita; o ZIP só é enviado quando o administrador marca a declaração e pressiona "Confirmar e enviar".
- O modal exibe bytes e percentual **reais apenas do upload**; durante validação/backup/instalação no PHP utiliza barra indeterminada. Bloqueia fechamento acidental da janela enquanto a requisição estiver ativa.
- Conclusão e erro permanecem no modal, com "Ver resultado" ou revisão de dados. Não há rolagem forçada nem redirecionamento automático na instalação manual; respostas ambíguas/timeout não geram nova tentativa automática.
- Mantidos CSRF, reautenticação, limites de ZIP, backup, rollback, revisão 36 do banco, atualização v2 sem assinatura e menus existentes. Sem alteração de schema ou dados privados.
- Testar `php tests/updater_modal_175.php`, `node tests/updater_modal_175.js`, regressão, PHP-FPM real e validação visual móvel.

## 1.0.74 — 06/11/2026 — Teste de integridade do ZIP corrigido
- Corrigido `tests/backup_integrity_133.php`: o caso de nomes duplicados agora usa um ZIP de teste com **duas entradas reais de mesmo nome** no diretório central, em vez de depender de `ZipArchive::addFromString`, que substituía a entrada anterior.
- O teste inspeciona `numFiles` e `statIndex()` antes de validar, evitando um falso positivo se o fixture perder a duplicidade.
- O empacotador do código-fonte permite **somente** esse ZIP de teste sob `tests/fixtures/`, mantendo o bloqueio para outros ZIPs, segredos e arquivos Android de projetos estranhos.
- O validador real `media_bundle_verify_manifest()` permanece intacto: já rejeita nomes repetidos; nenhuma mudança em backup/restauração, SQL 36, uploads ou formato do ZIP de atualização.
- A extensão PHP ZipArchive e a restauração MariaDB precisam ser reexecutadas no workflow GitHub Actions para confirmar a correção no CI com serviços reais.

## 1.0.73 — 06/11/2026 — Correção de identidade e empacotamento
- Removidos do código-fonte do Meu Site os arquivos Android/Gradle e a documentação do editor Uai Cortes, misturados em entregas anteriores.
- Corrigidos `app_identity.json` e `github-manager.json` para identificar o portal PHP 1.0.73, sem propriedades Android fictícias.
- Criado empacotador com inclusão restritiva e validações que rejeitam estrutura e versões incompatíveis.
- O gerador de atualização não inclui scripts Android nem Gradle. Mantido schema MySQL 36 e todas as funções PHP existentes.
- Importação efetiva no GitHub Manager e implantação PHP na hospedagem ainda requerem teste real.

## 1.0.72 — 06/11/2026
- Search Console permite verificação sob demanda de registros DNS TXT do domínio configurado em Geral e SEO. Detecta registro Google sem token salvo, ou compara o TXT completo esperado para indicar correspondência exata.
- O estado deixou de depender exclusivamente da metatag e agora mostra "Não testado", "TXT salvo", "TXT detectado" ou "Tag pronta" conforme a evidência; jamais declara propriedade aprovada sem autorização do Google.
- Novo campo opcional `google_dns_verification_txt`, persistido na tabela existente `settings`, sem migração MySQL. Consulta DNS limitada ao domínio HTTPS configurado, sem hosts arbitrários do usuário.
- UI móvel compacta com botão "Consultar DNS", status e data da consulta, ajuda contextual e metatag mantida como alternativa para prefixo de URL.
- Cache de consulta por sessão expira em 15 minutos, com intervalo mínimo entre consultas idênticas; possíveis falhas de resolução não são confundidas com ausência de registro.
- Esquema SQL permanece na revisão 36; ZIP v2 sem assinatura, backup, GA4, AdSense, uploads, consentimento e funções anteriores preservados.

## 1.0.71 — 06/11/2026
- **GA4:** consulta real via Data API, sob demanda, mostrando eventos e usuários ativos em 30 min e eventos dos últimos 7 dias.
- **Conta de serviço:** importação e remoção com senha admin e CSRF; chave JSON privada em storage/integrations/ (não distribuída), sem SQL novo.
- **Identidade:** campo separado para ID numérico da propriedade, sem substituir a medição G- nem o consentimento.
- **Interface:** resumo mobile compacto, teste em andamento, status que distingue dados recebidos de zero eventos.
- **Segurança:** hosts HTTPS fixos, tempo e corpo limitados, sem tokens em HTML/logs.

## 1.0.70 — Verificador de Páginas e diagnóstico editorial (06/11/2026)
- Criado `app/page_inspector.php` com seleção restrita de páginas locais, consultas preparadas, análise sem escrita e resumo por status.
- Integrações agora oferecem aba Páginas com visual responsivo mobile-first, seletor de páginas, cartões resumidos e `?` para explicações.
- Notícia: alerta sobre capa ausente, alt text, metadados, condição de geração `NewsArticle` e checklist SEO existente.
- Página inicial e categorias: configuração HTTPS, regras de indexação e templates; arquivos de rota/sitemap verificados apenas localmente.
- Google Search Console permanece opção de metatag + conferência manual; API OAuth não habilitada sem credenciais/autorização.
- Novos testes `tests/page_inspector_170.php`; CSS consolidado; sem migração SQL e sem alteração no backup ou assinatura opcional do ZIP.

## 1.0.69 — Central única de Integrações e diagnóstico mobile (06/11/2026)
- Novo menu Configurações → Integrações e APIs com layout mobile-first: hero em degradê, indicadores compactos, abas acessíveis, serviços expansíveis e ajuda contextual.
- GA4 e ID de editor AdSense agora usam edição única nesta central e as mesmas chaves `settings` existentes; Métricas e Publicidade mantêm seus recursos e links para a central, sem salvar valores em duplicidade.
- Search Console: edição validada do valor de metatag de propriedade por prefixo de URL. Publicação pública somente do valor escapado em `google-site-verification`. Não solicita conta Google nem comprova propriedade.
- Diagnóstico local somente leitura: HTTPS, existência de sitemap.php, opção de indexação, ID GA4 ativo, ID AdSense, ads.txt e token; nunca inventa aprovação do provedor.
- Catálogo visual para PageSpeed, Gemini, Tag Manager, Google Ads, Google Notícias, Bing, Clarity, Cloudflare e IndexNow — sem autenticação ativa ou código externo.
- CSRF, permissões administrativas, consultas preparadas, validação, transações, consentimento GA4, proteção de uploads e atualização ZIP v2 preservados.
- Sem alteração no esquema MySQL 36 ou dados privados; compilação CSS de 16 módulos. Testes em `tests/integrations_169.php` e regressões existentes; experiência real no celular e integração API externas ainda pendentes.

## 1.0.68 — Métricas por hora e Google Analytics 4 opcional (06/11/2026)
- Audiência: filtros Hoje (00h de Brasília), últimas 24 horas móveis, 7/30/90 dias; séries horárias nos filtros curtos, incluindo horas vazias; todas as tabelas usam o mesmo recorte.
- Central de Métricas compacta: 5 filtros, 4 KPIs, explicação de visitantes estimados por dia no botão `?` e configurações organizadas.
- GA4: ID `G-...` validado, ativação explícita pelo administrador com CSRF e salvamento transacional. Sem código JS livre e sem tag Google no admin.
- Privacidade: banner exclusivo de análise, bloqueio total da tag antes de consentimento, revogação e controles GPC/DNT/opt-out; Política de Privacidade ampliada.
- Nenhuma migração do banco (schema 36); ZIP v2 continua sem assinatura e com SHA-256. Testes de faixa horária/GA4 e regressão do projeto.

## 1.0.67 — progresso de atualização e terceira etapa visual (06/11/2026)
- Atualizações via ZIP: upload por XMLHttpRequest, porcentagem derivada de bytes transferidos e MiB enviados, sem valores inventados para processamento PHP. Após o upload, estado indeterminado explícito até receber a resposta do servidor.
- Instalação remota: indica processamento, sem porcentagem fictícia. Erros do PHP aparecem na interface e o botão de verificar resultado permite conferir o histórico; envio HTML tradicional continua como fallback.
- Manutenção: resumo compacto, ajuda contextual para explicações opcionais e avisos irreversíveis sempre visíveis.
- Ônibus: assistente mais compacto com ajuda contextual; campos e validações preservados. Publicidade: descrições secundárias em ajuda, ações existentes preservadas.
- Schema MySQL 36, backup, CSRF, autenticação, ZIP v2 sem assinatura e integridade SHA-256 mantidos; não altera dados da hospedagem.

## 1.0.66 — segunda etapa de compactação do painel (06/11/2026)
- Central de Saúde reorganizada: problemas e avisos sempre visíveis, verificações normais recolhidas, informações técnicas em `?` e logs sob expansão.
- Usuários em cartões compactos no celular, e-mails legíveis, ações com toque de 44 px e ajuda contextual para funções; permissões, CSRF e confirmações preservados.
- Notícias com ações móveis de 44 × 44 px em uma linha própria, eliminando a colisão com manchetes e status.
- Configurações de SEO agrupadas sem remover campos do formulário nem avisos de indexação e roteamento. Visão geral compactada.
- Banco/schema 36 e formato de backup mantidos; atualização ZIP v2 sem assinatura com senha e hashes SHA-256.
- Testes: `php tests/ui_166.php`, `php tests/smoke.php`, `python3 tools/build_css.py --check`, `php -l` e `node --check`.

## 1.0.65 — primeira etapa da revisão visual (06/11/2026)
- Biblioteca: mostra estado de arquivos de mídia ausentes e tenta imagem original se o thumbnail falhar; fallback local para imagem indisponível, sem ocultar referência original em banco.
- Portal público: fallback para capas/galerias que falham ao carregar; não renderiza seções editoriais vazias na página inicial; navegação inferior mostra ícones distintos e item ativo correto.
- Backups: tabela convertida em cartões móveis com nome de arquivo legível; ajuda técnica do pacote ZIP sob `?`, alertas críticos preservados.
- Fontes: mostra domínio em vez de URL enorme, com endereço integral em ajuda.
- Publicidade: diálogos com cabeçalho de fechamento fixo e rolagem interna, especialmente em celular.
- Segurança: indicadores e avisos informativos compactados; explicações transferidas para ajuda `?`, mantendo alertas e confirmações importantes visíveis.
- Ajustes de espaçamento em cartões e rodapé mobile, sem alterar MySQL (schema 36), autenticação, backup e o formato de atualização v2 sem assinatura.
- Testes: executar regressão PHP/JS, análise de sintaxe, sincronização CSS e verificação do ZIP. Funcionalidade na hospedagem ainda deve ser testada.

## 1.0.64 — 06/11/2026 — Central de Segurança
- Nova Central de Segurança no painel com estatísticas de 1/7/30 dias, filtros por evento, gravidade e IP e alertas derivados de eventos reais.
- Registro privado de logins, bloqueios de autenticação, acessos negados, falhas de CSRF, pedidos administrativos sensíveis, mudanças em usuários/configurações, instalação de updates e restaurações.
- Bloqueios temporários manuais de IP público apenas no admin, com senha, CSRF, proteção para não bloquear o próprio IP e prazo de expiração.
- Armazenamento em JSONL privado, limite de 1 MiB por dia, 30 dias de retenção, sem alterar schema SQL 36 nem o backup lógico.
- Compatível com ZIP de atualização v2 sem assinatura; hashes conferem integridade, não autoria. Ver `docs/SEGURANCA.md`.
- Validação local de código e rotas; testes visuais e hospedagem reais pendentes.

## 1.0.63 — 06/11/2026 — Menu lateral mais estreito
- Drawer do painel: largura máxima de 248 px no celular (antes 310 px); para telas maiores, 270 px. Margem mínima de 72 px preserva área do painel visível.
- Recuos e espaçamentos internos revistos, com alvos de toque de pelo menos 40 px e rolagem para acessar as opções restantes.
- CSS compilado e entregue no atualizador ZIP v2, sem assinatura, compatível com o painel v1.0.62.
- Sem alterações no banco (schema 36), módulos, mídias, autenticação, backups ou validação do atualizador.
- Testes sintáticos, CSS, UI e pacote executados; validação visual no celular e instalação real na hospedagem ainda pendentes.

## 1.0.62 — 06/11/2026 — Atualizador sem chave privada obrigatória
- Novo ZIP `meu-site-update-v2` aceita `release.json` com SHA-256 por arquivo sem `release.sig`; o formato v1 continua exigindo assinatura Ed25519 válida e assinaturas presentes nunca são ignoradas.
- Instalação por upload ou GitHub requer novamente senha do administrador, além de permissão, CSRF e confirmação; cinco erros em 15 minutos bloqueiam novas tentativas na sessão.
- Mantidos limites de ZIP, validação de caminhos e hashes, versão estritamente superior, schema 36, preflight, backups, modo manutenção e rollback. Extensão Sodium só é requerida ao instalar pacote assinado.
- Gerador `tools/build_update_package.py` não exige chave; `--key-file` permanece opcional. Aviso: SHA-256 não autentica a origem de um ZIP sem assinatura.
- A v1.0.61/v1.0.60 não aceita esse ZIP pelo atualizador antigo. A primeira instalação exige troca manual segura dos arquivos públicos/de código pela hospedagem. Consultar `docs/ATUALIZADOR.md`.
- Testes PHP de manifesto e UI, validação sintática e arquivo ZIP; o fluxo PHP-FPM/MySQL na hospedagem permanece pendente.

## 1.0.61 — 06/11/2026 — Rodapé compacto, drawer estreito e biblioteca integrada
- Remove Projetos/GitHub/Portfólio do rodapé público; conserva navegação institucional e ajusta colunas.
- Normaliza a reserva inferior da navegação móvel e inclui versão na URL do CSS público para evitar cache obsoleto.
- Reduz o drawer administrativo de 370px para 310px (92vw para 88vw) e compacta itens.
- Biblioteca de Mídias é componente embutido na rota administrativa, mantendo menu, cabeçalho e rodapé; seleção nos editores segue em modal. Miniatura em modo biblioteca abre detalhes.
- Endpoints, CSRF, ACL, uploads, backups, schema SQL 36 e demais funções preservados. Veja `docs/ARQUITETURA_ADMIN.md`.

## 1.0.60 — 06/11/2026 — Navegação única e formulários compactos
- Menu lateral à direita em `<dialog>`, aberto pelo ícone superior, com seções recolhíveis; retiradas as duas barras horizontais administrativas. ACL e saída com CSRF preservadas.
- Notícias: ícone de pesquisa abre filtros em janela, quatro ações agrupadas em uma linha no celular e status separado. Exemplos editoriais recolhidos.
- Cadastro de usuários e eleições só abre ao solicitar ou editar; links diretos de edição permanecem válidos.
- Mídias permanecem em tela cheia no celular, com cabeçalho fixo e upload opcional recolhido.
- Interceptação de `data-confirm` na fase de captura, com diálogos próprios e prevenção de aprovação por Escape; avisos do envio de fotos personalizados.
- Atualizador diferencia ZIP com a versão já instalada e pacote antigo, recusando ambos antes de aplicar arquivos. Esquema 36, backups e assinaturas preservados.
- Testes específicos: `tests/admin_ui_160.php` e `tests/admin_ui_160.js`; testes reais no PHP-FPM da hospedagem continuam pendentes.

## 1.0.59 — 06/11/2026 — Publicidade simplificada
- Interface móvel de Publicidade compactada em duas listas (posições e campanhas); eliminada tabela larga e formulários permanentes.
- Botão + Nova campanha oferece criação guiada de publicidade local ou associação de bloco Google AdSense, com janelas nativas, avisos de ocupação e edição dos espaços P01–P06.
- Configurações gerais, editor `ca-pub`, `ads.txt`, vistoria Google e ferramentas de teste reunidos na engrenagem, sem remover nenhuma proteção de consentimento ou indexação.
- Backend impede campanha local sobre bloco AdSense ativo e AdSense sobre posição com campanhas locais ativas. Datas sobrepostas exigem consentimento explícito de revezamento; concorrência protegida por transações e locks.
- Botão Remover anúncios demonstrativos na Publicidade e na Manutenção, com contagem, confirmação textual, backup e exclusão filtrada pelo prefixo oficial de teste. Preserva todos os anúncios reais e seus banners.
- Geração dos seis anúncios fictícios não substitui posições comerciais existentes nem provedores ativos. Nenhuma limpeza automática durante a atualização.
- Sem alteração do schema MySQL 36 nem dos formatos de backup. Regressão `tests/ads_admin_159.php` e docs/PUBLICIDADE.md.

## 1.0.58 — 06/11/2026 — Manutenção Alpha e avisos localizados
## Meu Site 1.0.58 — Demonstrações isoladas e manutenção Alpha

- O aviso global "Acervo de referência" foi retirado. Somente matérias `is_demo=1` exibem aviso de demonstração na página da notícia; cards podem ter a etiqueta "Exemplo". SEO e proteções do AdSense permanecem conservadores.
- Nova tela **Configurações → Manutenção** para remover apenas demonstrações ou todo o conteúdo editorial, preservando categorias, fontes, contas e arquivos físicos. As duas ações exigem administrador e backup lógico prévio.
- Reinstalação Alpha da mesma base MySQL pela administração ou pelo recuperador da primeira instalação: inventário fechado de 25 tabelas, senha do administrador, confirmação textual e backup antes de apagar. O reset não é atômico em MariaDB, por isso não deve ser usado em dados de produção.
- Instalação nova inicia sem notícias fictícias. As 10 categorias iniciais são criadas independentemente dessa escolha.
- O carregamento de notícias demonstrativas não executa DDL enquanto a transação de instalação estiver ativa, evitando COMMIT implícito em MariaDB.
- Schema 36 preservado; atualização não apaga nada por si só. Detalhes: `docs/ARQUITETURA_ADMIN.md`.

## 1.0.57 — 06/11/2026 — Pré-avaliação Google AdSense
- Metatag de verificação da conta AdSense adicionada ao head somente com ID real configurado, independentemente dos anúncios, sem carregar script de terceiros.
- Painel Publicidade: diagnóstico de requisitos técnicos/editoriais, indicação de notícias demonstrativas, acompanhamento da indexação e links institucionais.
- Opção protegida para publicar ads.txt preservando outras redes e bloqueando alteração de um ID Google diferente; atualizador nunca substitui ads.txt.
- Nova página Sobre e links no rodapé; Política de Privacidade detalha cookies publicitários e controle de personalização.
- Regras do AdSense e revisão humana de notícias continuam externas; nenhuma aprovação é garantida. Esquema 36 inalterado.

## 1.0.56 · 2026-11-06 — origem e vistoria em modais; rodapé móvel compacto
- Configuração da origem GitHub e vistoria do servidor passam a abrir em janelas modais nativas com controles acessíveis, economizando altura na aba Atualizações.
- `drilemoz/Meu-Site` confirmado como repositório do projeto e gravado em `app/update-origin.json`, sem ler metadados Android; configuração manual anterior é preservada, com ação explícita para restaurar a origem oficial.
- Repositório sem GitHub Releases retorna estado sem atualização, com cache de 30 minutos, em vez de alerta genérico HTTP 404. Segurança Ed25519 e autorização não alteradas.
- Rodapé móvel: elimina a soma de dois recuos inferiores (105px + 74px), reservando apenas o necessário para a barra fixa de 68px, incluindo safe area.
- Sem mudanças no esquema do banco (revisão 36), em conteúdos, imagens ou backups.

## 1.0.55 · 2026-11-06 — histórico e integridade do atualizador
- Histórico somente leitura de até 6 operações, com status e data, armazenado em `storage/updater/jobs/` (sem novas tabelas).
- Verificação de integridade SHA-256 dos arquivos gravados na última operação concluída; alerta sobre alterações, ausências e divergência de versão.
- Simulação de ZIP assinado mostra quantos arquivos serão novos, substituídos ou idênticos, com exemplos (sem instalar).
- Mantido layout compacto, assinatura Ed25519, CSRF, confirmação de instalação, senha apenas na restauração e bloqueio de migrações do banco.
- Nenhum dado editorial, mídia, configuração privada ou backup foi alterado. Schema continua na revisão 36.

## 1.0.54 · 2026-11-06 — tela de atualizações compacta

- Redesenhada a aba Atualizações para celular: versão instalada, versão disponível e status resumidos; textos explicativos movidos para ajuda `?`; diagnóstico, simulação, instalação manual e recuperação em seções recolhidas.
- Atualização remota e upload manual deixam de solicitar novamente a senha do administrador, usando a sessão verificada no servidor, CSRF e confirmação explícita. A restauração de código anterior mantém reautenticação por senha.
- Fonte de Releases detectável automaticamente por metadados do próprio portal (`app/update-origin.json`) ou variável de ambiente `IGUA_UPDATE_REPOSITORY`. O empacotador inclui metadados quando executado no checkout GitHub legítimo. Sem fonte verificada, exibe configuração discreta; nunca assume repositório de outro projeto.
- Aceita o novo padrão `Atualizacao-v1.0.54.zip` em GitHub Releases, mantendo compatibilidade com o padrão anterior `Meu-Site-update-v...zip`.
- Evita cache HTTP da página de atualizações; mantém assinatura Ed25519, verificação SHA-256, proteção de uploads e credenciais, backup prévio e revisão SQL 36.
- Testes: `php tests/updater_154.php`, `php tests/updater_153.php`, `php tests/updater_152.php`, `php tests/smoke.php` e sintaxe PHP. Integração real deve ser validada na hospedagem.

## 1.0.53 · 2026-11-06 — diagnóstico de atualização e simulação de ZIP

- Adicionada vistoria no painel Atualizações: PHP, ZipArchive, Sodium, cURL (opcional para upload manual), leitura do MySQL, pastas privadas, estrutura pública, permissões de escrita no código e manutenção pendente.
- Corrigida divergência entre gerador e verificador: arquivos de `public/uploads/` agora são inteiramente excluídos dos pacotes de atualização assinados.
- Adicionada simulação autenticada por CSRF de ZIP oficial assinado, com validação Ed25519, SHA-256, versão, revisão do banco e permissões, sem substituir código ou gravar no MySQL.
- O motor bloqueia a instalação se detectar falhas obrigatórias na vistoria do pacote; avisos sobre cotas, execução e falta de cURL são informativos.
- Relatório diferencia "OK", "falha" e "aviso" e evita afirmar que uma cota de hospedagem foi verificada quando só há dados do sistema de arquivos.
- Sem migrações de banco: schema 36 e backup lógico v11 preservados. As assinaturas usam a chave pública Ed25519 original, sem gerar outra chave.
- Testes locais focados na vistoria, sintaxe PHP e empacotamento; execução de upgrade real no Plesk e teste com MariaDB permanecem pendentes.

## 1.0.52 · 2026-11-06 — atualizador seguro de código PHP

- Adicionado menu **Atualizações** exclusivo do administrador, com consulta GitHub Releases, verificação de novas versões e envio de ZIP oficial assinado.
- Verificação criptográfica Ed25519 com chave pública fixa e SHA-256 individual de arquivos, sem comandos remotos ou atualização de caminhos privados.
- Backup lógico MySQL antes da instalação, snapshots de código, modo manutenção e recuperação manual/automática dos arquivos após falhas.
- Mantidos esquema SQL 36, backup lógico v11, uploads e instalação portátil Plesk/cPanel/Termux; migrações SQL futuras bloqueadas no modo automático.
- Documentação do fluxo de publicação e teste de assinatura em `docs/ATUALIZADOR.md`.
- **Limites:** sem execução em segundo plano, sem cron, sem exclusão de arquivos obsoletos; requer ext-zip, ext-sodium, ext-curl no servidor.

## 1.0.51 · 2026-11-06 — instalação segura, recuperação e portabilidade

- Consertada instalação que deixava tabelas e usuário no MySQL, mas falhava na criação de `storage/config.php`: agora o instalador valida escrita + `rename` e prepara configuração privada antes do primeiro DDL, ativando-a só depois da persistência final.
- Inventário somente leitura identifica as **25 tabelas** do esquema atual, distingue banco vazio, estrutura vazia parcial, instalação recuperável, banco ocupado e tabelas de terceiro. Nunca limpa nem duplica dados existentes.
- Modo de recuperação exige revisão de esquema atual, conjunto completo de tabelas e senha do administrador ativo existente. Recria somente a configuração privada (sem criar ou alterar tabelas), com limite de tentativas por sessão.
- Instalação nova impede uso de bancos contendo registros; falhas indicam etapa técnica (`config`, `schema`, `admin`, `restore`) nos logs sem divulgar credenciais.
- Preserva upload segmentado, backup lógico v11, mídia ZIP v2, esquema SQL 36, instalação local Termux e hospedagem Plesk/cPanel.
- Adicionados testes `tests/setup_recovery_151.php` e guia `docs/DEPLOY.md`. Teste real no MariaDB/BoaHost segue pendente.

## 1.0.50 · 2026-11-02 — importação automática sem depender de php.ini

- Instalador envia backups `.json` e `.json.gz` até 15 MiB em blocos de até 512 KiB quando `upload_max_filesize`/`post_max_size` limitam um envio único (por exemplo, PHP 2M/8M).
- Arquivos temporários ficam em `storage/setup-imports/` (0700), com identificador aleatório, sessão autenticada pelo token CSRF, tamanho total limitado, sequência de blocos validada e limpeza após instalação ou expiração. Nunca são expostos na raiz web.
- Upload direto continua disponível quando permitido. Sem JavaScript, a importação convencional depende dos limites PHP vigentes. Não tenta alterar limites fixados pelo provedor nem cria banco automaticamente na hospedagem.
- Etapa Requisitos e wizard distinguem envio direto de envio segmentado; os limites de 15 MiB e 40 MiB descompactados, backup v11 e esquema 36 permanecem.
- Cobertura de testes unitários e integração HTTP com pequenos arquivos. Testes reais no provedor/Termux e importação de backup de produção ainda são recomendados.

## 1.0.49 · 2026-11-01 — limite de upload em Termux e hospedagem

- `public/.user.ini` solicita `upload_max_filesize=20M` e `post_max_size=24M` em PHP-FPM/CGI quando o provedor autoriza configurações por diretório. Não tem efeito no PHP embutido, Apache mod_php ou ambientes que bloqueiam overrides.
- `bin/start-termux.sh` mantém 20M/24M na execução do servidor local e informa que servidores antigos precisam ser encerrados; não tenta mudar o php.ini global.
- Diagnóstico exibe capacidade conservadora por upload, orientação para ambiente e código CLI apresenta a correção mesmo sendo opcional.
- Instalador mostra limite efetivo na etapa de importação e valida tamanho antecipadamente no navegador; no servidor diferencia erros de upload. A aceitação é determinada pelo PHP/HTTP efetivo.
- Protegido acesso HTTP ao `.user.ini` via Apache e roteador do PHP embutido. Nginx exige configuração própria para bloquear arquivos ocultos.
- Backups de até 15 MiB no instalador, esquema SQL 36, backup lógico v11 e ZIP de mídias v2 preservados. Sem migração de banco.

## 1.0.48 · 2026-11-01 — diagnóstico e integridade da instalação híbrida

- Verificação obrigatória dos arquivos públicos (entradas PHP, CSS e editor JS) na pasta efetivamente servida, inclusive no layout `public_html/`: cópia incompleta é diagnosticada antes de gravar credenciais.
- A Central de Saúde também informa arquivos ausentes na pasta pública e orienta refazer a publicação, sem expor dados internos.
- Diagnóstico corrigido de upload de backup até 15 MiB, distinguindo `upload_max_filesize` e `post_max_size` com margem de 1 MiB para multipart; POST excedido apresenta mensagem apropriada no instalador, em vez de erro CSRF enganoso.
- Aviso explícito de envio de credenciais sem HTTPS na etapa final da hospedagem, sem confiar em cabeçalhos de visitantes para afirmar TLS.
- `bin/check-environment.php` oferece pré-verificação CLI em Termux/SSH, incluindo modo `--json`, sem solicitar ou mostrar senhas; sessões e DocumentRoot são remetidos à verificação WEB.
- A seleção de `IGUANEWS_PUBLIC_DIR` por tarefas CLI não aceita links simbólicos que saiam da raiz privada do projeto. `bin/start-termux.sh` verifica `pdo_mysql` antes de iniciar o serviço.
- Compatível com esquema SQL revisão 36, backup lógico v11 e mídias ZIP v2; nenhuma migração no banco e nenhuma mudança de rotas.
- Testes específicos em `tests/runtime_148.php`. Testes com MariaDB e hospedagens reais permanecem necessários.

## 1.0.47 — 01/11/2026

- Adicionado `app/environment.php` para reconhecer Termux pelo prefixo/binário PHP e distinguir hospedagem PHP e desenvolvimento local, sem confiar em cabeçalhos enviados pelo navegador.
- Instalador mantém as 3 etapas, apresenta diagnóstico de raiz web, HTTPS e permissões; impede instalar quando `DocumentRoot` expõe a pasta privada ou não pode ser verificado, ou quando o servidor PHP embutido não está restrito ao loopback.
- Em hospedagem oferece conexão manual com MySQL/MariaDB e campos adequados; a criação privilegiada de contas SQL só é habilitada em Termux real com servidor/navegador em loopback. Revalida a permissão no POST.
- Suporte explícito a hospedagem com `public/` como DocumentRoot e hospedagem compartilhada com `public_html/`, `www/` ou `htdocs/` como pasta pública irmã de `app/` e `storage/`; mídias, backups e permissões usam a pasta pública efetiva, identificada pelo caminho real do script PHP.
- Central de Saúde mostra o ambiente PHP e sinaliza configuração insegura de raiz web ou HTTPS ausente na hospedagem. Mensagens de diagnóstico e de uploads deixam de presumir Termux.
- Novo iniciador privado `bin/start-termux.sh`, guia `docs/DEPLOY.md` e regressão `tests/runtime_147.php`, com cenários de falsificação de ambiente, raízes web e layouts de hospedagem.
- Nenhuma alteração nas rotas públicas, no esquema SQL 36, nos backups lógicos v11, ZIP de mídias v2, credenciais existentes ou na identidade visual. Falta validar instalação completa contra MariaDB real em Termux e hospedagem, além de navegar no Android.

## 1.0.46 — 01/11/2026
- Esquema SQL 36: índices novos `idx_public_featured(status,featured,published_at,id)` e `idx_public_popular(status,views,published_at,id)` para as ordens públicas. Migração idempotente consulta `SHOW INDEX` e registra revisão somente após sucesso; instalação nova já cria índices.
- Busca administrativa `LIKE` passa a escapar `%`, `_` e `!`, mantendo correspondência literal consistente com a busca pública. Sem troca para FULLTEXT: a semântica de trecho foi preservada.
- Ferramenta somente leitura `php tools/news_explain.php`: cinco padrões de SQL via `EXPLAIN`, sem escrever dados; índice efetivamente escolhido depende do MariaDB.
- Testes `tests/news_indexes_146.php` e integração isolada `tests/integration/news_indexes_mariadb_146.php` no workflow de CI. EXPLAIN/integração reais dependem da execução no banco de CI, não demonstrados no ambiente local.
- Backup lógico JSON v11, pacote de mídias v2, rotas, filtros, editor de três etapas e dados existentes preservados. Atualizações de tabelas grandes podem exigir janela de manutenção e espaço livre.

## 1.0.45 — 01/11/2026

- `app/news_queries.php`: consultas públicas de feed, destaque e mais lidas com projeção de colunas, sem carregar `body MEDIUMTEXT` nem relacionar usuários quando não exibidos. Página da notícia individual continua com dados completos.
- Busca de existência de publicações demonstrativas com `SELECT 1 ... LIMIT 1`, preservando o bloqueio de indexação e de anúncios quando houver exemplos.
- Desempates determinísticos no destaque e no ranking. Paginação pública e administrativa deixam de executar a busca das linhas quando `COUNT(*)` retorna zero.
- Painel valida página e pesquisa de modo estrito, sem conversão de arrays GET; mantém limites e filtros. Novo teste de regressão `tests/news_queries_145.php`, contrato de composição de v1.0.44 atualizado para as mudanças intencionais.
- Esquema SQL 35, backups v11/ZIP de mídia v2, rotas, CSS, formulários, editor e identidades preservados. Sem índice novo ou alegações de desempenho medido; análise `EXPLAIN` e carga real pendentes.

## 1.0.44 — 01/11/2026
- Refatoração da apresentação PHP sem mudança de endpoints: `public/admin.php` (de 271 para 126 linhas) e `public/index.php` (de 208 para 115 linhas) mantêm o fluxo de entrada, enquanto os layouts e visões passam a ficar em `app/admin_views/` e `app/public_views/`, fora do DocumentRoot.
- Login administrativo, página de saúde, cabeçalhos/rodapés e renderização de rotas separados. Criados 9 componentes de rotas administrativas e 8 componentes de rotas públicas, com dispatchers explícitos e sem `include` derivado de parâmetros do visitante.
- Testes estruturais existentes adaptados para ler a composição real dos entrypoints, não para exigir HTML inline nos arquivos `public/`. Teste `tests/refactor_144.php` verifica integridade da extração com hashes do fonte 1.0.43, presença das proteções CSRF/autenticação e localização privada dos templates.
- Saída HTML comparada com a v1.0.43 nas rotas estáticas de Privacidade, Termos, 404 e Biblioteca de Mídias, sem diferenças de bytes. Integração PHP/HTTP completa no MariaDB/Termux e teste de navegador Android permanecem pendentes.
- Sem mudança SQL (revisão 35), backup lógico v11, ZIP mídias v2, editor de três etapas, URLs e formulários existentes.

## 1.0.43 — 01/11/2026

- Refatoração do backup lógico para escrita progressiva por tabela, preservando formato v11, REPEATABLE READ e limites de tamanho e quantidade de linhas, reduzindo o pico de memória PHP durante a exportação.
- Geração de ZIP completo passa a usar temporário privado e publicação apenas após validação. ZIP gerado pelo endpoint de download é excluído após a transferência/encerramento da requisição; o backup lógico fica salvo para recuperação.
- Exclusão automática restrita a arquivos temporários reconhecidos e abandonados há pelo menos 24 horas; rotação existente dos diários/semanais inalterada. Pacotes ZIP antigos e backups manuais não são apagados automaticamente.
- Adicionados testes `tests/backup_143.php` e documentação `docs/TESTES_CI.md`. Sem mudança de esquema (revisão 35), do formato lógico (v11) ou do formato ZIP (v2).

## 1.0.42 — 01/11/2026
- Autenticação com limite por IP de 5 falhas / 15 minutos, independente de sessão PHP, arquivo privado com `flock` e sem IP/senhas em texto; bloqueio falha fechado quando armazenamento indisponível.
- Gravação de configurações agora usa serviço com validação de todos os valores antes de iniciar transação InnoDB, evitando salvamento parcial.
- Editor de notícias rastreia uploads inéditos (capa, galeria, anexos) e os elimina após rollback SQL confirmado. Exceções durante registro/conversão de imagem também limpam o arquivo parcial. Em falha ambígua no COMMIT, preserva arquivos e registra o evento sem dados sensíveis.
- Workflow MariaDB considera mudanças em `public/**` e `tools/**` e verifica sintaxe somente do JS consolidado (trechos-fonte do editor são parciais de uma IIFE); teste de branding acompanha novo serviço; nova regressão local `tests/refactor_142.php`.
- Sem alteração de esquema (rev. 35) nem de backup lógico v11 / pacote de mídia v2. Compatibilidade PHP 8.1+, MySQL/MariaDB e editor de três etapas mantida; teste real de HTTP/MariaDB/Android pendente.

## 1.0.41 — 01/11/2026
- Novo módulo administrativo **Resultados Eleitorais** (fase 1): cadastro de pleitos, cargos, turnos, municípios/UF e candidaturas, com controle de acesso de administrador e CSRF.
- Módulo opcional, inicialmente desativado; desativar não remove cadastros. Sem votos fictícios, publicação pública ou importação TSE nesta fase.
- Esquema revisão **35**: tabelas `elections` e `election_candidates` com FK e restrição de número por eleição; migração idempotente.
- Backup lógico **v11** inclui ambas as tabelas; restaura backups v1–v10 com tabelas eleitorais vazias e preserva ZIP de mídias v2. Tokens de sessão continuam fora do backup.
- Referências e arquitetura previstas para importação verificada TSE em futuras etapas, sem requisições a fontes externas nesta versão.
- Testes de regras, permissões, compatibilidade do backup e rotas. Integração real no MariaDB e Android ainda pendente.

## 1.0.40 — 01/11/2026
- Login administrativo com botão acessível para mostrar ou ocultar senha, sem armazenamento de senha pelo site.
- Checkbox opcional de sete dias, desmarcado por padrão. Token selector/validator aleatório, somente hash no banco, HttpOnly, SameSite, HTTPS ou localhost, revogação no logout e após troca de senha/desativação.
- Esquema revisão 34: tabela privada `admin_remember_tokens` com FK ON DELETE CASCADE. Backups v10/ZIP v2 inalterados e sem tokens em exportações.
- Testes de autenticação, integração e regressão; integração MariaDB real ainda requer execução no ambiente com driver.

## 1.0.39 — 31/10/2026
- Projeto e ZIP renomeados para **Meu Site**; o nome editorial continua configurável e o site já instalado mantém o valor salvo no banco.
- Corrigido bloqueio de instalação por `storage/backups/` ausente: marcadores `.gitkeep` e preparo de diretórios restrito à árvore do projeto, sem sobrescrever dados.
- Novo nome padrão de portal e banco em instalações novas; identificadores de backups, variáveis de ambiente e sessão antiga mantidos para compatibilidade.
- Diagnóstico de upload de 2 MB agora orienta reiniciar o PHP com limites suficientes no Termux (20M/24M), sem fingir que a aplicação pode alterar `php.ini`.
- Testes adicionais das pastas, proteção de upload, symlinks e retrocompatibilidade. Sem migração SQL; backup lógico v10 e mídias v2 preservados.

## 1.0.38 — 31/10/2026
- `app/bus_admin.php` virou fachada com controles de permissão e mapeamento explícito das dez ações POST. Importação oficial, rotas, partidas e visualizações foram separados em cinco módulos privados de `app/bus_admin/`.
- Telas de linhas e importação mantêm o HTML idêntico ao da 1.0.37 nos cenários de teste (SHA-256); rotas, formulários, dados, permissões e revisão humana de dados ANTT/DER-MG preservados.
- Migrações: marcador do esquema inválido ou revisão mais nova que a do código agora provoca erro explícito, em vez de converter valores indevidos em zero ou rebaixar o marcador de uma base mais recente. Revisão instalada permanece 33, sem novo DDL.
- Novas suítes `tests/bus_admin_138.php` e `tests/schema_safety_138.php`; backup lógico v10 e mídias v2 sem alteração. Testes reais em MariaDB e navegação Android continuam pendentes.

## 1.0.37 — 31/10/2026

- `news_admin_editor()` preservada; arquivo de visualização dividido em oito componentes sob `app/news_admin_view/`, por preparação, cabeçalho, etapas Básico/Texto/Foto, botões, revisões e scripts.
- Renderização da notícia nova e existente comparada byte a byte contra 1.0.36 usando PDO simulado; preservados nomes de campos, CSRF, permissões, SEO, uploads, histórico e três etapas.
- Testes legados agora inspecionam os parciais reais concatenados por ordem, não um arquivo PHP monolítico. Novo teste `tests/news_admin_view_137.php` e hash de referência de markup em `tools/news_editor_view.sha256`.
- Sem alterações em CSS/JS, banco, formatos de backup lógico v10 e mídias v2 ou versão de esquema 33. Integração com MariaDB/Android permanece pendente.

## 1.0.36 — 31/10/2026
- Instalador inicial com nome, frase, descrição SEO e cidade/região configuráveis, pré-visualização dinâmica e validação no servidor.
- Identidade aplicada às telas públicas e administrativas, títulos e metas de compartilhamento, transporte e rodapés, com edição posterior em Configurações.
- Rodapé com versão real do arquivo VERSION e crédito do desenvolvedor em todos os layouts; modal acessível para e-mail e telefone, sem alteração no painel.
- Mantidos banco/esquema revisão 33, backup v10 e pacote ZIP v2; sem migração SQL.
- Testes de validação e CSS modular recompilado.

## 1.0.35 — Fontes JS do editor por responsabilidades (30/10/2026)

- Refatoração de origem: `public/assets/js/news-editor.js` agora é montado a partir de oito arquivos em `public/assets/js/news-editor-modules/`.
- Preservados os bytes, ordem e SHA-256 do JavaScript executado na 1.0.34: nenhuma mudança de comportamento visual, formulários, três etapas, SEO ou autosave.
- Ferramenta `tools/build_news_editor.py` com modo `--check`; código pronto para produção no ZIP e sem novo script HTTP.
- Novos testes de composição e integridade `tests/news_editor_modules_135.js` e documentação `docs/EDITOR_JS_MODULOS.md`.
- Sem migração de banco, alteração de backups (lógico v10/mídias v2) ou revisão de esquema 33.
- Teste no navegador Android e MariaDB real permanece pendente.

## 1.0.33 — Verificação de backups e migrações (30/10/2026)

- Backup lógico v10 preservado; exportação interrompe de forma segura se o JSON ultrapassar 40 MiB ou qualquer tabela superar 100 mil linhas, usando os mesmos limites da importação. Sem publicação de arquivo parcial.
- Pacote ZIP de mídias v2 passa a registrar `db_sha256` para detectar adulteração/corrupção do JSON do banco. O importador continua aceitando pacotes v1/v2 antigos sem esse campo, mas valida o hash sempre que estiver presente.
- Importador rejeita arquivos não listados no manifesto, entradas duplicadas e arquivos ausentes antes de alterar o banco ou salvar mídias.
- Migrações centralizadas em `app/schema_migrations.php`, com revisão 33 registrada em `settings` após sucesso. O instalador também registra a revisão inicial; futuras alterações de esquema devem incrementar o marcador.
- Central de Saúde confere todas as tabelas esperadas e a revisão registrada do esquema.
- Testes novos de integridade do manifesto, compatibilidade, limite lógico e comportamento do registrador de migrações; regressão existente preservada.
- Pendente: testes de restauração em MariaDB e interação Android/Termux, pois o ambiente de validação não possui `pdo_mysql`, servidor MariaDB nem `ext-zip`.

## 1.0.32 - 2026-10-30
- Refatoração: `app/news_editor.php` virou fachada de compatibilidade para sete módulos em `app/news_editor/` (schema, blocks, format, import, library, media, revisions), mantendo assinaturas públicas e campos de banco.
- Segurança editorial: restauração de revisão não altera `is_demo` e nunca transforma notícia de demonstração em notícia real.
- Testes: adicionados testes de correspondência da API pública original e de preservação da marca fictícia durante restauração, sem MariaDB real.
- Sem migração SQL, alteração dos backups lógicos v10, pacote de mídias v2 ou etapas Básico/Texto/Foto.

## 1.0.31 — Fontes CSS modularizadas sem alteração visual (30/10/2026)

- O CSS foi separado em 15 módulos de origem sob `public/assets/css/modules/`, agrupando instalador, notícias, transporte, publicidade, editor, mídia, SEO e outras áreas.
- O arquivo `public/assets/css/style.css` continua sendo o único CSS servido ao navegador e mantém os mesmos **90.852 bytes** e SHA-256 da versão 1.0.30: `a71d0dd4ccefffbae4b94d67c7a926898d60cd11c6678931253d2298fac9073a`.
- Novo `tools/build_css.py` monta o arquivo na ordem original, com modo `--check` para detectar divergências sem modificá-lo; Termux não precisa executar o script para usar o portal.
- Novo `tests/css_modules_131.php` compara os bytes do CSS consolidado e o das fontes modulares e verifica as páginas que fazem o carregamento.
- Incluída documentação `docs/CSS_MODULOS.md`; nenhuma regra de estilo foi alterada, removida ou reordenada, nenhum script/rota/migração SQL foi modificado.
- Pendente: inspeção visual em Android e desktop. Backup lógico v10 e mídia v2 permanecem inalterados.

## 1.0.30 — Controladores administrativos por módulo (30/10/2026)

- Refatorado `public/admin.php` para separar APIs de mídia, downloads e ações POST em controladores privados `app/admin_controllers/`.
- Ações de notícias, editorias, configurações, usuários, publicidade, ônibus e backups passaram para arquivos independentes; mantidas as mesmas rotas `admin.php?action=...` e respostas antigas.
- Nova tabela de roteamento POST em código `post_routes.php`, com nomes de controladores explícitos; o nome do arquivo nunca é derivado diretamente da URL.
- Verificações de sessão, autorização, token CSRF e tratamento de erros permanecem antes/ao redor da execução dos controladores. Nenhuma mudança de esquema SQL ou backup lógico v10/mídias v2.
- Testes estruturais existentes passam a verificar os controladores corretos; novo teste `tests/admin_routes_130.php` confere registro, rejeição de ações desconhecidas e fronteiras de segurança.
- Pendente: teste de integração com sessão e MariaDB reais no Android/Termux. A separação de HTML do painel poderá ser feita em versão posterior.

## 1.0.29 — Biblioteca de Mídias: buscas concorrentes e uploads parciais (30/10/2026)

- Corrigido erro de referência de `searchBox`, `searchToggle`, `uploadName` e `uploading` ao abrir/usar a galeria da v1.0.28.
- Criado `public/assets/js/media-state.js` com coordenador de requisições (abort/geração) e fila de upload sequencial; módulos testados sem banco ou DOM real.
- Navegação rápida, pesquisa, troca de pasta e fechamento não aceitam resposta atrasada de listagem; resultados antigos não alteram conteúdo atual.
- Em falha parcial, preserva apenas arquivos sem confirmação de sucesso para retry; arquivos confirmados não são reenviados automaticamente.
- Corrigido enriquecimento visual duplo do campo de seleção de mídia; envio automático e botão manual permanecem.
- Novo teste Node `tests/media_state_129.js` cobre corridas de consultas, falha parcial, retry e bloqueio de envio concorrente.
- Sem migração SQL; backup lógico v10, pacote de mídia v2 e demais módulos preservados. Validação em navegador Android/MariaDB ainda pendente.

## 1.0.26 — Pesquisa e paginação de notícias (21/10/2026)

- Arquivos públicos de notícias e editorias agora usam consulta paginada de 12 itens, com contagem real, navegação de páginas acessível e filtros por palavras, editoria e período.
- Busca literal trata `%`, `_` e `!` como texto usando `LIKE ... ESCAPE '!'` com parâmetros PDO, e considera editorias secundárias; consulta limita colunas para reduzir RAM.
- Filtros persistem ao navegar; redirecionamento de categoria da querystring para URLs amigáveis e `RewriteRule [QSA]` preservam paginação e período; canonical de editoria acompanha a página.
- URLs públicas de busca ficam `noindex` e páginas de categoria com filtros temporais também, para reduzir duplicação no índice. Editor e central SEO continuam inalterados.
- Incluída suíte `tests/archive_126.php`. Sem migração de banco. Backup lógico v10, pacote de mídia v2, mídias e dados existentes intactos.

## 1.0.25 — Central de SEO editorial e testes de URLs (20/10/2026)

- Nova **Notícias → SEO**: revisão das últimas 40 notícias por situação, com diagnóstico de título, resumo, descrição, slug, texto/blocos, capa e texto alternativo; filtro por papel (redator só acessa as próprias notícias).
- Diagnóstico reativo na área recolhida **Básico → SEO e destaque**. Pontuação interna até 100, informativa, sem prometer ranking ou impedir salvar.
- Capa do artigo público passa a usar o texto alternativo da Biblioteca de Mídias, quando houver, com fallback da legenda existente; imagens não foram alteradas.
- Regras de auditoria com testes PHP/Node; novos testes HTTP reais no roteador PHP, incluindo 404 e recusas de caminhos codificados suspeitos. Proteção explícita do roteador contra `..`, barra invertida e codificação residual.
- Mantidos slugs manuais, histórico e HTTP 301, URLs canônicas HTTPS, URLs antigas, três etapas de edição e permissões. Sem migração SQL, backup lógico v10 e pacote de mídias v2 inalterados.
- **Pendente:** integração e redirecionamento de artigo publicado em MariaDB real, testes de Apache e comportamento visual no Android/Termux.

## 1.0.24 — Controle de URLs amigáveis e SEO (20/10/2026)

- Editor permite slug manual com prévia, normaliza acentos e barra nomes utilizados em matérias ativas ou em redirecionamentos históricos.
- Mudanças de slug criam redirecionamento permanente 301 para matérias publicadas; mudanças são transacionais com o salvamento do artigo. Links de matérias agendadas/rascunhos não são expostos.
- Formato opcional de URL limpa para notícias e categorias com roteador PHP de desenvolvimento e RewriteRule Apache; formato querystring mantém compatibilidade e segue padrão desativado.
- Nova tabela `article_slug_redirects` com migração incremental idempotente. Backups lógicos evoluídos **v9 → v10** com leitura dos formatos v1–v10, pacote ZIP de mídias v2 inalterado. Nunca restaurar um v10 em uma versão antiga do portal.
- Preservação de slugs antigos com mais de 120 caracteres. Ajuste de retorno à página de edição após erros de salvamento (inclusive conflitos de slug).
- Testes PHP de colisões e histórico, testes JS de normalização, regressão de suites e lint PHP/JS; implantação MariaDB e roteamento Android pendentes.

## 1.0.23 — Recuperação de rascunhos sem perda de seleção (20/10/2026)

- Restauração do autosave agora respeita grupos de checkboxes marcados e desmarcados e valores repetidos por posição; antes uma opção já desmarcada podia continuar marcada e campos repetidos podiam receber listas achatadas.
- Snapshot passa a excluir explicitamente ID da notícia, token CSRF e metadados de arquivos ainda não enviados. Recuperação não modifica os arquivos selecionados pelo navegador.
- Aviso de limitação no painel, validação de dados do rascunho e testes de comportamento do JavaScript + regressão PHP.
- Banco e APIs de autosave sem alterações, backup lógico v9 e pacote v2 preservados, editor com três etapas mantido.

## 1.0.22 — Confiabilidade de mídias na etapa Foto (20/10/2026)

- Corrigida perda de legendas e créditos de fotos/arquivos ao adicionar ou trocar arquivos em outro campo da prévia; uma nova seleção limpa apenas os próprios metadados.
- Otimização de fotos no navegador usa identificador de seleção para impedir substituição tardia dos arquivos atuais por tarefa anterior; processamento descartado não bloqueia salvamento.
- Erros de fotos permanecem associados ao campo onde ocorreram, mesmo após alterações em outros uploads.
- Adicionados testes comportamentais Node.js (`tests/editor_media_122.js`) e regressões PHP (`tests/editor_122.php`); atualizados README, AGENTS, guia e relatório de validação.
- Sem migração SQL, sem alterações de APIs de upload, com backup lógico v9 e pacote de mídias v2 inalterados.

## 1.0.21 — Segurança e confiabilidade do backup completo (20/10/2026)

- Corrigida incompatibilidade entre ZIP de exportação (mídias até 300 MiB) e importação antiga (150 MiB): a importação agora aceita até 350 MiB totais, incluindo banco/manifesto, mantendo limite de 300 MiB para mídias descompactadas.
- Restaurador de mídia usa streaming em blocos de 64 KiB com verificação SHA-256 incremental, sem guardar todas as fotos em memória.
- Mídias novas são preparadas em arquivos temporários e validadas antes da cópia preventiva e da transação de restauração; conflitos preservam mídia existente, e falha na transação provoca limpeza tentativa dos arquivos novos. Operações entre disco e banco não são uma transação única.
- Exportação verifica o caminho real dos arquivos para impedir inclusão de symlinks fora de `uploads/` ou `storage/media/`; limite explícito de 3000 mídias por pacote e verificação de falhas de adição ao ZIP.
- Atualizados README, manual de backup, regra de versionamento, informações do painel, testes e relatório de linhas.
- **Sem migração SQL**, backup lógico continua **v9**, pacote de mídia **v2**, compatibilidade de restauração v1–v9 preservada no parser. Testes locais não substituem um ciclo de restauração no MariaDB real.

## 1.0.20 — Notícias em três etapas, sem tela de revisão (20/10/2026)
- Novo fluxo padrão para criar e editar notícia: 1. Básico → 2. Texto → 3. Foto, com um cartão de cada vez.
- Removida a quarta etapa “Confira e salve” e a alternância inicial para página contínua. O botão Salvar notícia fica na última etapa.
- SEO automático permanece no salvamento; ajustes manuais e opção de destaque agora estão recolhidos em Básico, sem criar uma tela adicional.
- Navegação por etapas com progresso, validação de manchete/categoria e presença de conteúdo antes de avançar. Mantidos editor visual, blocos, biblioteca de mídia, galerias e anexos.
- Sem migração SQL. Compatibilidade com backup lógico v9 e pacote de mídia v2. Testes estruturais/regressão; testes visuais e MariaDB no Android ainda pendentes.

## 1.0.19 — Editor visual e responsividade (20/10/2026)
- Editor em página contínua por padrão tanto em Nova notícia como em Editar, com assistente opcional.
- Área de texto com formatação visual sincronizada com texto seguro, edição em modo simples e prévia.
- Botões Mídias, Limpar e Galeria em uma linha. Removida inserção automática de atalhos duplicados; upload direto e documentos ficam numa opção avançada.
- Arquivos com seletor visual consistente no painel e no instalador. Capa atual exibida compacta.
- Listagem de notícias em cartões responsivos no celular, sem exigir arraste horizontal.
- Sem alteração SQL e sem troca do backup v9; testes estruturais e regressão executados. MariaDB e Android reais pendentes.

## 1.0.18 — Biblioteca de Mídias (20/10/2026)
- Biblioteca central com navegação ano/mês, busca, seleção na notícia e publicidade, upload, downloads e lixeira com verificações de referências.
- Imagens novas em public/uploads/AAAA/MM, miniaturas e otimização progressiva WebP quando GD disponível. Fotos legadas preservadas.
- Backup lógico v9 com tabela media_library, pacote ZIP v2 recursivo e leitura de backups antigos v1–v8.
- Relatório completo de arquivos e linhas em docs/AUDITORIA_176.md e CSV externo.
- Sem mover nem apagar imagens de instalações existentes. Testes MariaDB reais ainda pendentes.

## 1.0.17 - Upload de fotos no editor (2026-10-19)
- Otimização opcional/automática no navegador para capa e galeria com fotos grandes: conversão para JPG leve, tamanho/alvo conservador para PHP padrão 2M, proteção contra envio antes da conclusão, proporção preservada.
- Mensagens de erro discriminam limite interno da capa (4 MB), limite de upload do PHP (geralmente 2M), post_max_size, falha parcial ou falha no armazenamento temporário.
- Limites de PHP visíveis no cadastro; testes isolados de diagnóstico e regressão sem modificar banco ou backup v8.
- Fotos recriadas artificialmente para demonstração não equivalem a registros fotográficos de fatos reais.

## 1.0.16 - Correção de Warning no painel Métricas (2026-10-19)

- Corrigida leitura de `$_GET['days']` sem parâmetro na rota `/admin.php?action=analytics`, que emitia `Undefined array key "days"`.
- Novo parser isolado para 7/30/90 dias, com padrão 30 e descarte de entradas inválidas ou não escalares.
- Testes de regressão do filtro e verificação do uso do parser pelo controlador; sem alteração do banco, modelos de dados, painel público ou formato de backup v8.
- Sinalização clara de que esse bug não requer reinicializar nem reinstalar MariaDB.

## 1.0.15 - Editor 2.0 e biblioteca de fotos (2026-10-19)

- Novo Modo rápido na criação de notícias: dados, texto e capa no mesmo formulário, sem exigir navegação entre quatro etapas. O assistente completo permanece disponível.
- Barra de formatação editorial restrita para texto simples: negrito, itálico, subtítulo e listas, com prévia HTML escapada; o conteúdo continua editável em texto e HTML arbitrário não é executado.
- Biblioteca de imagens locais já vinculadas a notícias ou galerias: reutilização na capa e na galeria sem duplicar arquivos; preserva crédito e legenda quando disponíveis.
- Validação de arquivo real no diretório de uploads, tipo MIME permitido, sem caminhos externos; vínculos compartilhados não apagam arquivos ao remover da notícia.
- Versão e documentação sincronizadas. Sem migração de tabelas; backup v8 permanece compatível.
- Testes: sintaxe PHP, JS, testes isolados de biblioteca e HTML escapado, regressão do projeto e integridade ZIP. MariaDB real e inspeção de celular pendentes.

## 1.0.14 - Estabilidade, backups e Central de Saúde (2026-10-19)

- Menu administrativo **Saúde do sistema**: banco, tabelas, PHP, gravação, espaço, backups, SMTP e GeoIP.
- Registros resumidos de falhas fatais e operações internas, sem mensagens, IP, credenciais nem conteúdo pessoal; rotação local.
- Verificação manual e não destrutiva do backup mais recente (JSON/GZIP, esquema e SHA-256).
- Backups gravados atomicamente por arquivo temporário, ordenados por data real; script CLI valida antes da retenção.
- Erros internos PDO no painel deixam de exibir mensagens técnicas. Em caso de falha de migração, a Central de Saúde pode continuar acessível.
- Sem alterações no esquema SQL ou no formato de backup v8. Testes de integração MariaDB no Termux continuam pendentes.

## 1.0.13 - Fluxo editorial e estabilidade (2026-10-19)

- Lista de notícias no painel ganhou busca por título/resumo, situação (todas, rascunhos, publicadas, agendadas) e paginação de 20 registros, respeitando permissões de redatores.
- Permite duplicar uma notícia como rascunho mediante CSRF e autorização; copia textos, categorias e blocos, conserva a marca de demonstração; não duplica galerias e anexos e não publica automaticamente.
- Edição de manchete preserva o `slug` anterior, evitando quebrar URLs públicas compartilhadas, links de busca e SEO.
- Salvamento automático do editor só considera um rascunho salvo depois de resposta JSON confirmada; falhas de rede voltam a ser tentadas. Impede requisições simultâneas de autosave.
- Estilos responsivos para filtros de administração. Sem alteração de esquema nem do backup v8.
- Testes isolados de filtros/slug/fluxo e todos os testes de regressão PHP/JavaScript aprovados; gravação integrada MariaDB e teste físico Android permanecem pendentes.

## 1.0.12 - Editor simplificado e guia (2026-10-19)
- Formulário de notícias em quatro cartões mais limpos: Básico, Texto, Foto e Publicar.
- Informações avançadas (fontes, mais categorias, publicação agendada, blocos, anexos e SEO manual) recolhidas por padrão. Edições com blocos anteriores os mantêm visíveis.
- Campo de texto principal visível; compatibilidade com notícias legadas e blocos expansíveis preservada.
- Guia de preenchimento em PDF acessível no editor, arquivo Markdown em docs/ e download independente.
- Botão opcional para acrescentar 3 matérias didáticas fictícias (acidente, evento, empregos), pré-backup, idempotente e sem alterar matérias próprias.
- Exemplos continuam identificados como fictícios e não indexáveis mesmo após edição.
- Banco e formato de backup v8 inalterados.

## 1.0.11 - Métricas próprias (2026-10-19)
- Menu **Métricas** para administradores com painel de 7/30/90 dias, visitas humanas, visitantes diários aproximados, cliques, origens, referenciadores, bots, dispositivos, países e regiões quando houver GeoLite2 instalado.
- Eventos no MySQL, HMAC diário sem IP gravado, limpeza de registros após 90 dias, respeito a GPC/DNT e opção de não participação na Política de Privacidade.
- Rastreio de cliques first-party com sendBeacon e defesa básica contra spam; bots classificados pelo User-Agent (heurística, não verificação oficial).
- Localização offline opcional, sem envio de IP a serviços externos. Dependências/documentação opcionais para GeoLite2.
- Backup v8 inclui analytics_events e mantém leitura dos backups v1–v7. Chave privada storage/analytics.key nunca vai no ZIP do código.
- Preservados layout, editor inteligente, transportes, publicidade, instalação e demais recursos da 1.0.10.

## 1.0.10 (19/10/2026)
- Editor de blocos, mídias, SEO automático e backup v7.

## 1.0.9 · 2026-10-18
- Publicidade gerenciável: seis posições, ativação global/por espaço, anúncios locais com upload seguro, empresa, datas, prioridade e contagem básica de cliques.
- Google AdSense manual integrado com ID de editor/bloco e carregamento apenas após escolha explícita do visitante; consentimento simples no Brasil, sem substituir CMP certificada em regiões onde exigida.
- Cadastro por painel restrito a administrador. Redes externas futuras requerem adaptador específico, sem injeção arbitrária de JavaScript no painel.
- Backups v6 incluem anúncios e configurações; importação v1–v5 mantida. Padrão totalmente desligado.
- Ajustada Política de Privacidade e documentação de anúncios/ads.txt.
- Testes unitários e lint local; homologação em MariaDB e inspeção mobile pendentes.

## 1.0.7 - Cadastro guiado de ônibus e partidas locais (2026-10-18)

- Cadastro manual de horários redesenhado para assistente em cartões: origem e hora, cidades/paradas sucessivas com horário previsto, empresa, tarifa do percurso completo e dias de circulação. Uma etapa por vez, botões Voltar/Avançar e revisão antes de salvar.
- Suporta paradas após a meia-noite com dia de viagem explícito; não inventa passagem quando o horário é desconhecido. Ao finalizar, cria linha, paradas e primeira partida em transação InnoDB, sem salvar percursos incompletos.
- Edição de trajetos/horários no mesmo assistente, além de lista compacta para alterar, pausar ou excluir saídas. Uma mudança nas paradas de uma linha existente afeta todas as suas partidas e deixa fontes oficiais como linha fixa.
- Página Ônibus abre por padrão os próximos ônibus saindo de Iguatama (cidade configurada), com destinos variados, dia Hoje/Amanhã/Em N dias, hora local e contador regressivo. Origem e destino continuam pesquisáveis.
- Navegação inferior móvel substitui Mais por Ônibus; o endereço antigo `?p=mais` redireciona para a consulta. Engrenagem do rodapé permanece para administração.
- Migração aditiva para `fare_amount` em `bus_routes`; preço exibido como tarifa do percurso integral, quando informado. Backup lógico v5, restauração compatível com v1–v4; fotos continuam fora do backup SQL/JSON.
- Verificações locais PHP, JavaScript e testes isolados de cronologia/virada de dia/backup. MariaDB real e UI em telefone ainda exigem homologação.

## 1.0.6 - Integração DER-MG e ANTT (2026-10-18)

- Tela administrativa para carregar CSV/TXT do DER-MG e ANTT, revisão de pendências e aprovação individual.
- Download direto do CSV ANTT de setembro/2026 com HTTPS e limite de tamanho; demais edições via upload oficial.
- Linhas oficiais com modo automático (revisão) ou fixo (não sobrescrever). Todas as linhas manuais são fixas.
- Horários de chegada não presentes na fonte permanecem desconhecidos, sem inventar deslocamentos.
- Histórico preservado; backup v4 cobre fila de revisão, mantendo leitura de v1/v2/v3.
- Migração aditiva de tabelas e campos.

## 1.0.4 · 2026-10-18
- Painel simplificado: Visão geral, Notícias, Usuários e Configurações. Gerenciamento editorial de Notícias com abas internas Todas, Nova notícia, Categorias, Nova categoria, Autores e Fontes. Ícone Ver portal junto de Sair.
- Perfis editoriais de autores e catálogo de fontes reutilizáveis. Matérias podem indicar assinatura, fonte cadastrada, SEO de título e descrição; mantém legendas e fotos.
- Sistema de usuários com papéis Administrador, Editor e Redator, autenticação existente reforçada por checagem de conta ativa e permissões no servidor; bloqueia exclusão do último administrador ativo e restringe redatores aos próprios rascunhos.
- Configurações de SEO: modelos de título, descrição geral, domínio HTTPS para canonical, Open Graph, Twitter card, JSON-LD NewsArticle, controle de indexação, sitemap XML. Indexação desativada inicialmente e notícias demonstrativas nunca indexadas.
- Migração aditiva e compatível de usuários e artigos, tabelas author_profiles e editorial_sources. Backups v2 incluem novos dados e restauração aceita backups v1.
- Tema público e instalador mantidos. Testes sintáticos e isolados; MariaDB real e interface no dispositivo continuam pendentes.

## 1.0.3 (2026-10-18)
- Acervo de 100 resumos originais de referência com fontes, dez em cada uma de dez categorias.
- Menu Geral e seis editorias em destaque no topo.
- Imagens ilustrativas com novo enquadramento, legendas e créditos; nenhum recorte estreito do mockup.
- Migração aditiva dos campos image_caption, image_credit, source_name e source_url.
- Importação de amostras em instalações existentes com backup prévio e proteção por senha.

# Histórico do IguaNews

## 1.0.2 · 2026-10-18

- Instalador modernizado sem alterar a home: cartões expansíveis e responsivos para **criação automática** ou conexão manual com MySQL/MariaDB.
- Provisionamento local automático: cria base **nova** (ou reutiliza uma **vazia**, se autorizado), usuário exclusivo aleatório com senha forte e permissões restritas somente à nova base. Não guarda a credencial administrativa no arquivo de configuração nem concede privilégios globais.
- A criação automática requer MariaDB iniciado, conta com privilégios `CREATE DATABASE`, `CREATE USER` e `GRANT`, e acesso pelo navegador no mesmo dispositivo (`127.0.0.1`). Hospedagens compartilhadas continuam com configuração manual.
- Terceira etapa permite **instalação nova** ou **importação de backup IguaNews** `.json`/`.json.gz` em banco vazio. Importação preserva usuários/senhas antigas, notícias, categorias e configurações; arquivos `public/uploads` são copiados separadamente.
- Mantidas barreiras contra sobrescrever bases com conteúdo; importação aceita apenas formato conhecido com tamanho limitado, sem executar SQL arbitrário.
- Novo guia `docs/MIGRACAO.md`, verificações de limite de upload e testes unitários do provisionamento SQL. Sem mudanças nas tabelas existentes.
- Necessário validar a criação automática e o fluxo completo com um MariaDB real no Termux; testes locais não substituem esse teste.

## 1.0.1 · 2026-10-18

- Novo assistente de instalação prévia em **três etapas**: requisitos do ambiente, teste de conexão ao banco e cadastro do administrador.
- Diagnóstico individual de PHP, PDO, pdo_mysql, Fileinfo, JSON, sessões e gravação em storage, backups e uploads.
- Avisos não bloqueantes para compressão GZIP e funções de texto UTF-8.
- Mensagens específicas para MariaDB/MySQL inacessível (2002/2003), usuário/senha rejeitados (1045), permissão insuficiente (1044), banco inexistente (1049) e hostname incorreto (2005), incluindo comandos úteis para o Termux.
- Teste de conexão sem alterar dados, identificação de bancos com tabelas ou registros existentes e prevenção de sobrescrita acidental.
- Armazenamento temporário das credenciais testadas apenas na sessão do instalador (expira em 20 min); senha não volta ao HTML do formulário.
- Instalador trava concorrência com flock, mantém a marca e o visual público da versão anterior.
- Novo teste isolado em tests/setup_diagnostics.php; teste com MariaDB real continua pendente.

## 1.0.0 · 2026-10-18

- Portal responsivo com design inspirado no mockup aprovado e marca IguaNews.
- Página inicial, pesquisa, editorias, artigos e mais lidas.
- Instalador inicial guiado em PHP + MySQL/MariaDB.
- Painel com login, estatísticas, notícias, fotos, editorias e configurações.
- Backup manual, restauração protegida e script de backup automático com retenção.
- Conteúdo demonstrativo explicitamente fictício.
- Documentação de implantação no Termux e em hospedagem PHP convencional.

## 1.0.5 · Horários de ônibus locais
- Módulo público de consulta por origem e destino, com próxima partida, contagem regressiva e aviso quando não houver horários hoje.
- Cadastro administrativo de trajetos em paradas sequenciais, horários e dias da semana; controle de ativação/exclusão de linhas e horários.
- Navegação móvel **Mais → Horários de ônibus**; acesso administrativo por engrenagem discreta no rodapé, preservando login seguro.
- Migração SQL aditiva com `bus_routes`, `bus_stops` e `bus_departures`; backup lógico v3 inclui o módulo e mantém leitura v1/v2.
- Sem horários fictícios nem dependência de API de transporte; chegadas são estimativas cadastrais.

## 1.0.8 | 18/10/2026
- Buscador de cidades editável e com autocompletar a partir de municípios do IBGE (via cache privado, com retorno ao cadastro local em falhas), incluindo destinos ainda sem linhas cadastradas.
- Cartões de ônibus mostram duração derivada de horários conhecidos; sem tempo previsto, informam explicitamente ausência da duração.
- Importação DER-MG aceita TXT de quadro de horários separado por tabulação, ponto e vírgula, CSV ou campos com espaçamento fixo, tolera texto antes do cabeçalho, trajeto separado por barra, horário HHhMM, identifica limites de upload do PHP e explica registros ignorados.
- Cabeçalho de Ônibus no painel reorganizado: apresentação no topo e botões em linha inferior; cartões, tabelas e espaçamento lateral adequados ao celular.
- Rodapé reestruturado com links institucionais, GitHub, portfólio e engrenagem discreta de administração alinhada à direita.
- Páginas de Política de Privacidade, Termos de Uso, Contato e Anuncie, incluindo formulário com validação, CSRF, antispam, limite de envios e entrega SMTP via PHPMailer após configuração da hospedagem, sem endereço de e-mail exposto ao visitante.
- Mantidos esquema do banco, assistente, backups v1–v5, login e proteção das linhas fixas. Sem migração de banco nesta atualização.
