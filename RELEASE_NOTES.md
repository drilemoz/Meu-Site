## 1.0.74 — Integridade dos backups / GitHub Actions

Corrigida a regressão do backup que falhava em GitHub Actions: o teste de entradas com nomes duplicados passa a utilizar um ZIP verdadeiro com nomes repetidos, que deve ser recusado pelo validador. O teste antigo não criava essa condição porque a API PHP substituía o mesmo nome. O site e o banco não sofreram alterações funcionais.

**Atenção:** para corrigir o workflow no GitHub, publique o ZIP de **código-fonte completo** (incluindo `tests/fixtures/backup133_duplicate_names.zip`) no repositório. O pacote `Atualizacao-v1.0.74.zip` atualiza apenas a hospedagem e não substitui os testes no GitHub. Ainda é necessário executar novamente a integração MariaDB e ZipArchive no GitHub Actions.

## 1.0.73 — Código-fonte limpo para GitHub Manager
Correção do pacote: removidos arquivos Android de outro projeto, metadados alinhados ao portal PHP, e empacotamento protegido. Nenhuma mudança funcional no site, no banco ou na central de integrações.

## v1.0.72 — Verificação DNS no Search Console

A Central de Integrações ganhou verificação DNS TXT por consulta pública sob demanda. O painel agora detecta registros Google existentes mesmo sem metatag e permite informar o registro TXT esperado para comparação exata. O status confirma somente **presença no DNS**, não a propriedade aprovada no Search Console.

- Em Configurações → Integrações e APIs → Google → Search Console, configure a URL canônica HTTPS em Geral e SEO, informe o TXT opcional e toque em Consultar DNS.
- O teste exige resolução DNS habilitada no PHP da hospedagem, sem token OAuth nem consulta à conta Google.
- Se não existir valor esperado e houver qualquer TXT Google, o painel informa somente presença genérica; para vincular ao token esperado, copie o valor gerado no Search Console.
- A verificação oficial continua no próprio Google. Não remover o TXT da zona após confirmar a propriedade.
- Testes automatizados locais de resolução simulada, segurança, cache e regressão. Hospedagem e confirmação Google pendentes.

## v1.0.71 — Diagnóstico real GA4
Central de Integrações permite cadastrar o ID da propriedade GA4 e importar o JSON privado de conta de serviço do Google Cloud. Após autorização dessa conta como Leitor e ativação da Analytics Data API, o botão Testar recebimento consulta eventos recentes. A chave não é incluída no pacote ZIP, no banco, nem nos backups. Resultado vazio não representa falha do portal. Requer teste real em hospedagem e autorização Google.

## 1.0.70 — Verificador de Páginas mobile (06/11/2026)
- Aba Páginas na Central de Integrações: página inicial, últimas notícias publicadas e categorias cadastradas.
- Diagnóstico local: URL canônica, SEO editorial, indexação, miniaturas físicas, dados estruturados previstos e arquivos de rota/sitemap.
- Estados honestos: OK local, Revisar, Erro e Não testado; nenhum HTTP externo, consulta à API do Google, ou aprovação simulada.
- Layout mobile compacto com ajuda `?`, acesso à URL publicada e ao Search Console, sem bibliotecas externas.
- Seleção via GET com IDs permitidos e SQL parametrizado; não aceita URLs arbitrárias; nenhuma alteração de publicações.
- MySQL revisão 36, consentimento GA4 e AdSense, CSRF, backup e atualizador ZIP v2 preservados.

## 1.0.69 — Central de Integrações e verificador local (06/11/2026)
- Novo painel mobile-first em Configurações → Integrações e APIs: Google, Diagnóstico e Catálogo.
- IDs do Analytics 4 e AdSense centralizados sem duplicar configurações nem ativar publicidade automaticamente.
- Metatag do Search Console para propriedade por prefixo de URL; verificação final é feita no Google.
- Verificação local de HTTPS, sitemap, indexação, identificadores, `ads.txt` e publicação da metatag. Não faz consultas remotas nem garante aprovação.
- Gemini, Google Ads, Tag Manager, PageSpeed e ferramentas de terceiros exibidas somente como planejadas.
- Atualização ZIP v2 sem assinatura, esquema 36 e arquivos privados preservados. Simule, faça backup e confirme a instalação com a senha administrativa.

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

## Meu Site 1.0.64 — Central de Segurança

A administração ganhou **Central de Segurança**, com log de acessos e ações sensíveis, filtros por período/tipo/gravidade/IP, alertas sobre eventos críticos e bloqueios temporários manuais com senha. Os dados ficam em arquivos privados com limite diário e retenção de 30 dias. Somente ações que passaram pelo PHP podem aparecer; não substitui um WAF nem comprova invasão.

Sem migração de banco. Atualize com `Atualizacao-v1.0.64.zip` no painel v1.0.63, após simulação e backup. Manual: `docs/SEGURANCA_164.md`.

## Meu Site 1.0.63 — Menu do painel reduzido

- Menu lateral mais estreito no celular: de 310 px para 248 px, preservando navegação, botões e rolagem.
- Pacote `Atualizacao-v1.0.63.zip` gerado no formato v2 sem assinatura, para testar no painel da versão 1.0.62.
- Sem mudança de banco, arquivos privados, imagens, backups ou autenticação. Faça backup e simule o ZIP antes de instalar.

## Meu Site 1.0.62 — Atualizador sem assinatura obrigatória

- ZIPs v2 não precisam mais de chave privada Ed25519. Os hashes SHA-256, a validação de caminhos, a revisão do banco e os backups continuam.
- Administrador informa senha novamente para autorizar instalação manual ou por GitHub; cinco erros na sessão causam bloqueio temporário.
- Pacotes legados assinados v1 continuam protegidos; ZIP com assinatura inválida é recusado.
- Atualizador anterior não instala ZIP sem assinatura: a primeira passagem à 1.0.62 exige instalação manual segura pelo gerenciador de arquivos/FTP da hospedagem.

## Meu Site 1.0.61 — Ajustes de navegação e mídias

- Rodapé público sem menu Projetos e com espaçamento inferior móvel revisado; atualização de CSS sem cache antigo.
- Menu lateral administrativo mais estreito e compacto no celular.
- A Biblioteca de Mídias ocupa o conteúdo normal do painel, com navegação e rodapé visíveis; não expande como modal. O seletor de imagens nos outros módulos mantém o modal.
- Nenhuma alteração em dados, banco, backups ou autenticação. Validação de visual e uploads na hospedagem continua necessária.

## Meu Site 1.0.60 — Painel administrativo reorganizado

- Navegação única lateral à direita (Notícias, Mídias, Eleições, Publicidade, Configurações, etc.). Sem barras horizontais internas.
- Notícias: pesquisa em janela pelo ícone de lupa e quatro controles compactos, respeitando as permissões do usuário.
- Usuários, eleições e upload de mídias com formulários recolhidos até uso; mídia mantém tela cheia no celular.
- Mensagens e confirmações próprias do portal. Cancelar ou pressionar Escape não confirma operações destrutivas.
- Atualizações com aviso claro quando o mesmo ZIP já está instalado e rejeição de versão anterior.
- Nenhuma migração de banco; versão SQL 36. Requer validação visual e funcional na BoaHost. Consulte `docs/ADMIN_UI_160.md`.

## Meu Site 1.0.59 — Central de publicidade simplificada

- Interface compacta no celular: + Nova campanha, gerenciador de seis posições em janelas e lista de campanhas expansível, sem tabelas horizontais.
- Escolha entre anúncio local e bloco AdSense; configuração geral e preparação para revisão do Google ficam na engrenagem.
- Ocupação do espaço validada no PHP: AdSense/local não são ativados juntos na mesma posição; campanhas locais com datas coincidentes exigem autorização de revezamento.
- Remover publicidade fictícia disponível em Publicidade → ⚙ → Testes e Configurações → Manutenção, após backup e confirmação; preserva campanhas, mídias e notícias reais.
- Formulário mantém os dados em erros de validação. Sem alterações de banco (revisão 36); assinatura Ed25519 e atualização incremental preservadas.
- MariaDB/PHP-FPM e aparência no celular continuam pendentes de testes reais.

## Meu Site 1.0.58 — Demonstrações isoladas e manutenção Alpha

- O aviso global "Acervo de referência" foi retirado. Somente matérias `is_demo=1` exibem aviso de demonstração na página da notícia; cards podem ter a etiqueta "Exemplo". SEO e proteções do AdSense permanecem conservadores.
- Nova tela **Configurações → Manutenção** para remover apenas demonstrações ou todo o conteúdo editorial, preservando categorias, fontes, contas e arquivos físicos. As duas ações exigem administrador e backup lógico prévio.
- Reinstalação Alpha da mesma base MySQL pela administração ou pelo recuperador da primeira instalação: inventário fechado de 25 tabelas, senha do administrador, confirmação textual e backup antes de apagar. O reset não é atômico em MariaDB, por isso não deve ser usado em dados de produção.
- Instalação nova inicia sem notícias fictícias. As 10 categorias iniciais são criadas independentemente dessa escolha.
- O carregamento de notícias demonstrativas não executa DDL enquanto a transação de instalação estiver ativa, evitando COMMIT implícito em MariaDB.
- Schema 36 preservado; atualização não apaga nada por si só. Detalhes: `docs/CONTEUDO_ALPHA_158.md`.

## Meu Site 1.0.57 — Preparação para análise do AdSense
Nesta versão, o painel Publicidade permite configurar o ID real do editor, publicar ads.txt e acompanhar verificações de indexação e matérias. A página Sobre foi adicionada à navegação institucional, e a política de privacidade detalha os cookies do Google. Anúncios permanecem desligados até decisão expressa do administrador e os mecanismos de consentimento existentes.

**Importante:** a aprovação depende de análise do Google e conteúdo real e original. Não há declaração automática de conformidade, nem migração SQL.

## 1.0.56 — 06/11/2026
- Fonte oficial e vistoria do servidor em janelas compactas no painel de Atualizações.
- Repositório padrão confirmado: `drilemoz/Meu-Site`, configurável pelo administrador; GitHub sem release não é tratado como falha genérica.
- Rodapé móvel mais compacto, sem margem inferior duplicada.
- Mesmos mecanismos de assinatura Ed25519, backup, recuperação e revisão do banco 36.

## 1.0.55 — 06/11/2026
- Menu Atualizações: consultar até seis operações anteriores, com resultado e data.
- Inspecionar integridade dos arquivos instalados pela última atualização, sem alterar código ou MySQL.
- Simular o ZIP assinado e visualizar contagem de arquivos novos, substituídos e iguais antes da instalação.
- Mantido o atualizador com assinatura Ed25519 e recuperação protegida.

## 1.0.54 — 06/11/2026

**Melhoria:** tela de Atualizações compacta e responsiva. Informações detalhadas em ajuda `?`, formulários de origem e upload manual recolhidos. O administrador pode instalar atualizações oficiais com sessão e CSRF, confirmando a operação, sem redigitar a senha. Recuperação continua exigindo senha.

A fonte do GitHub é encontrada automaticamente apenas quando houver metadados confiáveis do próprio projeto; sem eles, a configuração de repositório permanece disponível. O nome dos novos pacotes é `Atualizacao-vX.Y.Z.zip`. Não há alterações no banco SQL.

## 1.0.53 — 06/11/2026

**Novo: Vistoria do ambiente e simulação de atualização.** O menu Atualizações agora verifica condições essenciais do servidor e permite selecionar um ZIP assinado para simular sua aplicação, verificando assinatura e integridade sem alterar arquivos ou banco. A instalação efetiva repete a vistoria, bloqueando falhas obrigatórias. Avisos sobre disco e timeout não representam garantia de sucesso.

**Importante:** a simulação não realiza backup real, alteração do banco ou substituição de arquivos. O atualizador ainda exige aprovação manual, mesma revisão SQL e testes no Plesk para validar o comportamento da hospedagem. Veja `docs/ATUALIZADOR_153.md`.

## 1.0.52 — 06/11/2026

**Novo: Atualizações no painel administrativo.** Verifique novas versões via GitHub Releases e atualize com um clique após confirmar a senha, ou envie um ZIP oficial assinado. Antes de modificar o código, o sistema cria backup do MySQL e cópia dos arquivos; em caso de problema permite restaurar a versão de código anterior. Fotos e configurações privadas ficam preservadas.

**Importante:** o pacote 1.0.52 deve ser instalado manualmente uma vez. Migrações do banco, execução automática sem aprovação e rollback do banco ainda não fazem parte do motor; use o procedimento assistido para alterações estruturais. Leia `docs/ATUALIZADOR_152.md`.

## 1.0.51 — 06/11/2026

**Instalador portátil e recuperação sem exclusão de dados.** Valida antecipadamente criação e renomeação do arquivo privado de configuração; reconhece 25 tabelas, evita novo cadastro quando há dados e permite recuperar uma instalação interrompida após comprovar o e-mail/senha do administrador. Mostra etapas técnicas de erro sem expor segredos. Nenhum dado existente é sobrescrito automaticamente.

**Para a BoaHost:** envie a atualização com a pasta `public/` como raiz do documento (`httpdocs/public` na estrutura atual), reabra o assistente e teste novamente o mesmo banco; se a base tiver as 25 tabelas e esquema 36, será oferecida a recuperação. Para novos testes, use banco vazio separado, após exportar backup.
