# Documentação atual do Meu Site (1.0.147)

- [Busca compacta 1.0.147](BUSCA_UI_147.md) — sugestões adaptadas ao teclado móvel, ícones por tipo e rolagem interna.
- [Home: ônibus compacto 1.0.146](HOME_ONIBUS_COMPACTO_146.md) — um horário, interface enxuta, preservando o quadro completo.
- [Home: busca e ônibus 1.0.145](HOME_BUSCA_ONIBUS_145.md) — troca dos filtros da página inicial por saídas programadas e sugestões automáticas.
- [Editor mobile 1.0.143](EDITOR_UI_143.md) — botões Gemini visíveis, seleção de imagens e publicação compacta.

Use os arquivos abaixo como guias principais. Os relatórios `VALIDACAO_*`, `RELATORIO_ARQUIVOS_LINHAS_*` e instruções de atualizadores antigos foram retirados do ZIP; seu histórico permanece nas versões anteriores do repositório, se disponíveis.

- [Instalação e publicação](DEPLOY.md), [criação do banco pelo instalador](CRIACAO_BANCO_INSTALADOR.md), [migração](MIGRACAO.md) e [Termux](INSTALACAO_TERMUX.md)
- [Gerenciador de Arquivos](GERENCIADOR_ARQUIVOS.md) — submenu Ferramentas, limites, privilégios e segurança
- [Sitemap e Indexação](SITEMAP_INDEXACAO.md) — central, XML, robots.txt, ads.txt e verificações sob demanda
- [Aplicativos e Páginas Legais](APLICATIVOS_LEGAIS.md) — rotas públicas, rascunhos, revisões, OAuth e instalação assistida
- [Correção do teste integrado do atualizador 1.0.118](CORRECAO_CI_118.md), [Teste integrado do atualizador 1.0.117](INTEGRACAO_ATUALIZADOR_117.md) e [Proteção contra alterações externas no rollback 1.0.116](ROLLBACK_EXTERNO_116.md), [Falhas e rollback do atualizador 1.0.115](RECUPERACAO_FALHAS_115.md), [Segurança da recuperação e testes 1.0.114](SEGURANCA_RECUPERACAO_114.md) e [Backup: banco ou banco + mídias](BACKUP.md)
- [Atualização do código e recuperação](ATUALIZADOR.md)
- [Integrações Google e verificações locais](INTEGRACOES.md)
- [Refatoração do controlador de notícias 1.0.113](REFATORACAO_113.md), [Refatoração das integrações 1.0.112](REFATORACAO_112.md), [Refatoração interna 1.0.111](REFATORACAO_111.md), [Auditoria da versão](AUDITORIA_176.md) e [testes/CI](TESTES_CI.md)
- [Publicidade](PUBLICIDADE.md), [métricas](METRICAS.md), [Segurança](SEGURANCA.md), [Central de Saúde](SAUDE_SISTEMA.md)
- [Arquitetura do admin](ARQUITETURA_ADMIN.md), [CSS](CSS_MODULOS.md), [editor JavaScript](EDITOR_JS_MODULOS.md), [mídias](MIDIAS.md)
- [Editor](EDITOR_2_0.md), [notícias e fontes](NOTICIAS_E_FONTES.md), [SEO](SEO_CENTRAL.md), [ônibus](ONIBUS.md) e [eleições](ELEICOES.md)

As documentações funcionais remanescentes podem registrar as versões em que cada recurso nasceu; isso não significa que a versão do código atual seja aquela. Em caso de divergência, validar a implementação e os testes atuais.

- Ferramentas: Informações do Servidor (v1.0.127). Dados consultados em `app/server_info.php`, visual em `app/admin_views/routes/server_info.php`; sem acesso a segredos.

- [Instalador seguro 1.0.132](INSTALADOR_SEGURO_132.md) — chave inicial, TLS remoto e tratamento de falhas parciais.

- [Galeria pública de fotos 1.0.134](GALERIA_PUBLICA_134.md) — visualização das fotos nas matérias, teclado, deslizar e acessibilidade.
- [Instalador passo a passo 1.0.133](INSTALADOR_WIZARD_133.md) — requisitos, modo, banco, confirmação e senhas.

- [Correção CI MariaDB local 1.0.135](CI_MARIADB_LOCAL_135.md) — falha de origem Docker, namespace de rede, validação e limitações.

- [Correção do painel administrativo 1.0.137](CORRECAO_PAINEL_137.md) — falha de inclusão, menu e fallback seguro em diagnósticos.

- [Instalador automático de VPS 1.0.142](INSTALADOR_VPS_142.md) — um comando no SSH, verificações, pool FPM exclusivo e restrições de migração.
- [Deploy seguro AWS EC2 1.0.138](AWS_EC2_DEPLOY.md) — conta dedicada, timer systemd, Nginx, PHP-FPM, rollback e IndexNow.

- [Diagnóstico de erros e melhorias editoriais 1.0.141](DIAGNOSTICO_ERROS_141.md) — mídias, Gemini, SEO, publicação, privacidade e testes.
