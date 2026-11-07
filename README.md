# Meu Site — Portal Iguatama Online

**Versão atual do código-fonte: 1.0.94** · PHP 8.1+ · MySQL/MariaDB · schema 36.

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

## Recursos recentes e limites

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
3. Para atualizar a hospedagem, usar **exclusivamente** `Atualizacao-vX.Y.Z.zip` e verificar o resultado no painel. O ZIP `Meu-Site-vX.Y.Z-fonte-completo.zip` serve para GitHub e desenvolvimento, não para o atualizador.
4. Antes de atualizar, gerar backup externo **do banco e dos arquivos**. Em caso de erro de rede, conferir a versão instalada antes de reenviar.

## Desenvolvimento e verificação

```sh
for f in tests/*.php; do php "$f" || exit 1; done
for f in tests/*.js; do node "$f" || exit 1; done
python3 tools/build_css.py --check
python3 tools/build_news_editor.py --check
python3 tools/audit_project.py
python3 tools/build_update_package.py --output /tmp/Atualizacao-v1.0.93.zip
python3 tools/build_source_package.py --output /tmp/Meu-Site-v1.0.93-fonte-completo.zip
```

Para testes que exigem o MariaDB real, `pdo_mysql`, `ZipArchive` e para a instalação em navegador Android, utilize ambiente compatível. Confira [TESTES_CI](docs/TESTES_CI.md), [documentação atual](docs/INDICE.md) e [auditoria 1.0.77](docs/AUDITORIA_176.md).
