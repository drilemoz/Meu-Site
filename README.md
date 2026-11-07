# Meu Site — Portal Iguatama Online

**Versão atual do código-fonte: 1.0.102** · PHP 8.1+ · MySQL/MariaDB · schema 37.

Portal de notícias locais com editor, categorias, autores/fontes, mídias, horários de ônibus, eleições, publicidade, métricas e administração com segurança, backups e atualizador de código. O sistema publicado pode estar numa versão anterior até o administrador instalar o pacote na hospedagem.

## Estrutura

- `public/`: arquivos públicos PHP, CSS, JS e recursos; a raiz web deve apontar aqui (ou equivalente configurado conforme [DEPLOY](docs/DEPLOY.md)).
- `app/`: lógica de aplicação, autorização, templates, schema e módulos.
- `storage/`: dados privados em produção (configuração, logs, backups e credenciais); no fonte somente arquivos vazios `.gitkeep`.
- `bin/`: rotinas CLI, como agendamento de backup do **banco**.
- `tools/`: geradores de CSS/editor, pacotes e auditoria.
- `tests/`: regressões e fixture de ZIP deliberadamente inválido.
- `.github/workflows/integration-mariadb.yml`: integração com MariaDB e PHP zip no CI.

**Identidade do projeto:** exclusivamente web/PHP, repositório `drilemoz/Meu-Site`; não incluir arquivos Android, Uai Cortes, Kotlin, Gradle ou APK. `github-manager.json` e `app_identity.json` acompanham `VERSION`.

## Versão 1.0.102 — Central visual de SEO e Compartilhamento

- Configurações gerais preservadas; SEO ganha prévias locais em abas Google e WhatsApp/Facebook, com atualização enquanto se digita.
- Descrição, título, URL pública, imagem e indexação são editados em diálogos centralizados, sem painel expansível, reutilizando o formulário protegido por CSRF.
- Diagnóstico local não realiza requisições remotas nem promete indexação; URL social inválida ou sem arquivo aparece como pendência.
- Mudar o interruptor de indexação de ativo para desativado exige confirmação antes de salvar. A imagem escolhida na biblioteca atualiza a prévia.
- Banco schema 37 e backup v12 preservados; testes reais em hospedagem ainda necessários.

## Versão 1.0.101 — Cadastro de categorias/fontes e destaques

- Corrigidas as ações AJAX `news_create_category` e `news_create_source` na lista central de rotas administrativas: criação dentro do wizard deixa de retornar HTTP 404. Mantidos login, CSRF, permissão `manage_taxonomy`, SQL preparado e respostas JSON seguras em erros de validação.
- O destaque principal da home exige `featured=1`; notícias publicadas sem marcação não entram automaticamente como destaque. Quando não há nenhuma marcada, a área apresenta chamada institucional neutra, sem promover outra reportagem por engano.
- O plano EXPLAIN e testes automatizados foram atualizados. Não altera registros existentes, schema 37, backup v12 ou outras áreas do portal.

## Versão 1.0.100 — Central de Sitemap e Indexação

- Nova seção **Configurações → Sitemap e Indexação**: resumo de URLs elegíveis, busca, filtros por tipo, páginas de resultados e datas `lastmod` fundamentadas em registros publicados. Cópia, abertura e teste HTTP individual de URL.
- O sitemap público e a lista administrativa usam a mesma fonte de dados, sem rascunhos, agendamentos ou notícias de demonstração. O XML respeita 50.000 URLs e o painel alerta quando o volume ultrapassar esse limite.
- Diagnóstico local de domínio HTTPS, indexação, diretiva robots.txt e correspondência do ads.txt AdSense. Botão separado faz testes HTTP pelo navegador sob demanda, sem criar chamadas para a API Google e sem presumir indexação.
- `robots.txt` anuncia `https://iguatama.online/sitemap.php`. `ads.txt` da hospedagem não é alterado pela atualização. Nenhuma mudança de schema 37 ou de backup v12. Consultar [docs/SITEMAP_INDEXACAO.md](docs/SITEMAP_INDEXACAO.md).

## Versão 1.0.99 — Páginas legais, publicação e datas automáticas

- **Última atualização** das páginas públicas vem da data real de criação da revisão publicada no MariaDB. Salvar rascunho não altera a data pública. Na edição, a revisão ativa e a data são informadas automaticamente, sem campos manuais.
- **Aplicativos:** cartão com Páginas, Copiar e Publicar; Informações em tela própria. Na tela de páginas, Editar, Visualizar/Prévia, Copiar e Publicar dividem uma linha no celular.
- **Publicar tudo:** publicação transacional de apresentação, privacidade e termos. Valida texto, dados de responsável e e-mail antes de gravar qualquer revisão. Reutiliza a revisão publicada quando seu conteúdo não mudou; exige confirmação explícita.
- **Uai Cortes:** rascunhos novos sem marcações PREENCHER ou datas fictícias, mantendo somente informações técnicas fundamentadas. O botão Atualizar modelos originais reconhece rascunhos *intactos* das versões anteriores, sem substituir páginas personalizadas ou publicadas. Os textos exigem revisão do responsável antes de disponibilização pública.
- **Pop-ups administrativos:** estilo compartilhado com altura intrínseca e máximo de 82dvh, centrado em telas móveis e com rolagem somente para conteúdo extenso. Sem mudança de schema (37) ou backup (v12).

## Versão 1.0.98 — Visualizar páginas de aplicativos

- Cartões de **Aplicativos e Páginas Legais** agora oferecem **Ver página** em nova aba somente quando aplicativo e página estão publicados.
- Para rascunhos, **Prévia** abre uma aba administrativa autenticada com a visualização do texto salvo, sem expor a página em `/slug` antes da publicação.
- Botões Editar, Ver página/Prévia e Copiar URL ficam lado a lado no celular. Nenhuma migração, mudança de dados ou publicação automática (schema 37 / backup v12).

## Versão 1.0.97 — Atualizações e modelos legais

- **Pop-ups compactos:** GitHub e Enviar ZIP agora usam altura do conteúdo e ficam centralizados, sem o grande painel vazio no celular.
- **Diagnóstico GitHub:** a consulta retorna orientação categorizada para problemas de cURL, DNS, TLS, tempo limite, respostas HTTP e erros na hospedagem; não revela mensagens brutas. A release v1.0.96 já foi encontrada no repositório público, mas o acesso PHP da hospedagem precisa ser verificado.
- **Diagnóstico ZIP sem instalar:** inspeciona o manifesto, revisões do banco, integridade e pré-requisitos antes de exibir a confirmação. Não exige senha para a simulação; instalar ainda exige CSRF, senha e consentimento.
- **Uai Cortes:** apresentação, política de privacidade e termos pré-preenchidos em novos rascunhos, com SEO. Para registros anteriores, botão **Carregar exemplos do Uai Cortes**, limitado a páginas intactas. Texto jurídico continua sujeito à revisão e ao bloqueio `PREENCHER:`.
- **Importante:** schema 37 e backup lógico v12 continuam inalterados. A atualização do schema 36→37 NÃO é permitida pelo instalador ZIP comum: faça instalação assistida.

## Recursos recentes e limites

- **Aplicativos e Páginas Legais (v1.0.96):** módulo para vários aplicativos com slug permanente, apresentação, Política de Privacidade, Termos, páginas adicionais, editor Markdown seguro, rascunho independente de versão pública, revisões, prévia, restauração e redirecionamentos 301 para slugs antigos. O Uai Cortes é semeado como rascunho, **sem declarações jurídicas fictícias**. Rotas públicas PHP e links para OAuth/YouTube após revisão humana. Schema 37, backup v12. **Exige migração assistida; o atualizador antigo não pode instalar uma mudança de banco.** Consulte [docs/APLICATIVOS_LEGAIS.md](docs/APLICATIVOS_LEGAIS.md).

- **Datas relativas corrigidas (v1.0.95):** o PHP 8.3 do CI falhava quando a regressão simulava uma data inválida em `news_cards.php`. A conversão agora é não-excepcional e rejeita datas malformadas ou impossíveis, exibindo “Data indisponível” sem derrubar o painel. Testes de regressão adicionados; a integração MariaDB precisa ser reexecutada no GitHub Actions. Schema 36 inalterado.

- **Wizard e cartões editoriais (v1.0.94):** o cabeçalho mantém Sair ao lado do título e a importação por URL saiu da interface do novo cadastro. Seleção de capa, galeria, ZIP e anexos sem botões nativos duplicados (mesmos inputs, com proteção de upload e limites preservados). SEO usa quatro ícones e janelas com edição temporária: cancelar descarta alterações, salvar aplica aos campos do wizard sem publicar; diagnóstico oferece correção por etapa e edição SEO local. O painel inicial e a lista de notícias compartilham cartões responsivos, ações com ícone de lixeira e categoria + data relativa no fuso America/São_Paulo. Schema 36 preservado; testar na hospedagem/Android.


- **Pop-ups unificados (v1.0.94):** novo componente visual e controlador compartilhados para janelas de integrações, atualizador, publicidade, avisos e editor; no celular, modais centralizados com margens, altura limitada e rolagem interna, sem ocupar 100% da tela. PageSpeed agora alterna Celular/Computador em abas acessíveis sem consultas automáticas. Sem alteração de banco.
- **Atualizações em janela única (v1.0.92):** botão **Visualizar** consulta GitHub sob demanda e apresenta novidades, senha/confirmação e progresso da instalação em pop-up central. Exibe etapas efetivamente registradas pelo PHP, sem porcentagem artificial. Só apresenta sucesso após resposta confirmada, com **Atualizar página**; erros, timeouts e estado ambíguo requerem conferência do histórico. Link Releases e cartões Fonte oficial/Servidor removidos da tela, mas ferramentas e verificações continuam nas opções avançadas. Sem migração do banco.


- **Wizard aprimorado (v1.0.91):** topo sem ajuda longa; rascunho recuperável mostrado uma vez por aba quando tratado, com opção Ignorar; navegação compacta; barra de edição em uma linha e ferramentas extras em menu; novos artigos sem painel de cartões antigos; botões compactos de capa/biblioteca/limpeza e envio Capa/Fotos/ZIP; cadastro de nova categoria e fonte sem abandonar o editor, respeitando `manage_taxonomy`; status SEO resumido com barra, diagnóstico no diálogo centralizado, e endereço/SEO manuais recolhidos. Ampliação de anexos para formatos comuns, com verificação de MIME/assinatura e atualização de backup/restauração. **Fontes registradas por notícia:** uma fonte principal, sem vínculo múltiplo nesta versão. Schema continua 36. Integração Gemini não ativada.**


- **Wizard de notícias (v1.0.90):** nova tela exclusiva com nove passos (título, descrição, matéria, fotos, créditos, anexos, classificação, SEO e revisão/publicação), prévias de arquivos do celular, galerias múltiplas, ZIP de imagens com inspeção/limites, capa automática se não houver outra, resumo SEO local sem reticências artificiais e escolha final de salvar, publicar ou agendar. A API Gemini ainda **não está conectada**. Autosave mantém apenas campos de texto; fotografias, ZIPs e anexos devem ser enviados ao salvar, com os limites da hospedagem. Schema permanece 36.


- **Central de Integrações em janelas (v1.0.89):** corrige o atributo quebrado das abas; GA4, AdSense, Search Console e PageSpeed abrem em diálogo centralizado no desktop e em tela inteira no celular. Formulários, credenciais, relatórios, sugestões, histórico, verificação por metatag e os botões `?` usam navegação interna com Voltar e Fechar, sem rolagem forçada da página. Guias oficiais explicam onde obter IDs, permissões e como habilitar as APIs; preserva consultas apenas sob demanda e a autorização dos provedores.

- **Atualizações:** botão **Enviar ZIP** abre diálogo central com confirmação e porcentagem real **apenas do upload**. O processamento do servidor é indeterminado. O ZIP v2 sem assinatura exige senha administrativa e manifesto SHA-256, que **não autentica a autoria**. Veja [ATUALIZADOR](docs/ATUALIZADOR.md).
- **Diagnóstico unificado (v1.0.88):** sete indicadores separados de preparação local, última operação manual e categoria genérica de erros para GA4, AdSense, Search Console (relatório e indexação), PageSpeed (celular e PC) e DNS. Histórico de até 12 operações em sessão administrativa, sem tokens ou conteúdo bruto de respostas. Abrir o painel não chama APIs e nunca garante aprovação do Google.
- **AdSense API (v1.0.87):** OAuth2 do proprietário com credenciais privadas e consulta manual de receita estimada/atividade, independente de GA4 (AdSense não aceita service account). Veja [INTEGRACOES](docs/INTEGRACOES.md).
- **Métricas:** Hoje, 24h, 7, 30 e 90 dias; GA4 opcional com consentimento. A Data API usa uma conta de serviço privada e pode consultar eventos apenas quando configurada. Veja [INTEGRACOES](docs/INTEGRACOES.md).
- **GA4 — Audiência:** relatório autorizado e manual dos últimos 28 dias completos, com usuários ativos, sessões, visualizações, páginas populares por título, canais de origem e dispositivos. O resultado da propriedade não necessariamente representa só este site; sem novos dados, não inventa números. Veja [INTEGRACOES](docs/INTEGRACOES.md).
- **Search Console:** DNS TXT e metatag são verificações locais. Os relatórios autorizados exibem cliques, impressões, CTR e principais pesquisas/páginas; **a v1.0.85 permite inspecionar individualmente o estado de indexação de notícias publicadas**, pela API oficial, na aba Páginas ou via atalho do editor. O resultado é do índice conhecido pelo Google, **não** teste ao vivo nem pedido de indexação. Veja [INTEGRACOES](docs/INTEGRACOES.md).
- **PageSpeed Insights (v1.0.83):** análises Lighthouse **separadas para celular e computador**, sob demanda e restritas à URL HTTPS oficial, sem chave obrigatória para uso eventual. Notas, horários, métricas e intervalos de consulta independentes por dispositivo, com cache temporário na sessão. Não são dados reais de visitantes. A Central mantém os atalhos e a cópia de identificadores públicos.
- **Segurança:** histórico e bloqueios administrativos parciais pelo PHP; não substitui logs do servidor ou WAF.
- **Releases automáticas:** envios para `main` via GitHub Manager passam pelos testes MariaDB/PHP/JS. Aprovados, o workflow gera e publica `Atualizacao-vX.Y.Z.zip` em GitHub Releases; uma versão já publicada não é sobrescrita. Veja [TESTES_CI](docs/TESTES_CI.md) e [ATUALIZADOR](docs/ATUALIZADOR.md).
- **CI MariaDB:** corrigido na 1.0.77 o teste de cookie de login persistente, simulando HTTPS e preservando headers. A aplicação continua rejeitando cookies persistentes sob HTTP remoto. Na 1.0.78, o teste eleitoral passou a importar o módulo de migrações. Na 1.0.79, o teste de índices importa Publicidade, Editor e Métricas antes de simular a migração SQL 35→36, com preflight anterior ao DDL.
- **Backup:** a operação **Gerar backup agora** salva **somente o banco**; **Baixar pacote completo ZIP** reúne banco e mídias **referenciadas no banco**. Nenhuma das duas opções é backup integral da hospedagem. Veja [BACKUP](docs/BACKUP.md).

## Instalação e atualização

1. Preparar PHP 8.1+, PDO MySQL, MariaDB/MySQL e HTTPS. Instalar `phpmailer/phpmailer` conforme `composer.lock` quando a hospedagem necessitar do SMTP; não publicar credenciais.
2. Publicar o projeto fora da raiz pública, expondo somente `public/`/equivalente, mantendo `storage/` privado e persistente.
3. **Atualizações de código sem migração de schema** usam `Atualizacao-vX.Y.Z.zip` no painel. A **primeira atualização para 1.0.96 (schema 37)** é uma exceção: requer **instalação assistida do código por SFTP/gerenciador**, nunca o botão de atualização do schema 36. O ZIP `Meu-Site-vX.Y.Z-fonte-completo.zip` serve para GitHub e desenvolvimento. Veja [a migração assistida](docs/MIGRACAO.md).
4. Antes de atualizar, gerar backup externo **do banco e dos arquivos**. Em caso de erro de rede, conferir a versão instalada antes de reenviar.

## Desenvolvimento e verificação

```sh
for f in tests/*.php; do php "$f" || exit 1; done
for f in tests/*.js; do node "$f" || exit 1; done
python3 tools/build_css.py --check
python3 tools/build_news_editor.py --check
python3 tools/audit_project.py
python3 tools/build_update_package.py --output /tmp/Atualizacao-v1.0.102.zip
python3 tools/build_source_package.py --output /tmp/Meu-Site-v1.0.102-fonte-completo.zip
```

Para testes que exigem o MariaDB real, `pdo_mysql`, `ZipArchive` e para a instalação em navegador Android, utilize ambiente compatível. Confira [TESTES_CI](docs/TESTES_CI.md), [documentação atual](docs/INDICE.md) e [auditoria 1.0.77](docs/AUDITORIA_176.md).
