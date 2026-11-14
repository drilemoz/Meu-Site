## Versão 1.0.152

Corrige o clique nas notícias quando as URLs amigáveis estão ativadas e o servidor não reescreve o endereço, como no `php -S ... -t public` do Termux sem `router.php`. Antes, `/noticia/nome-da-materia` mostrava a página inicial; agora o portal identifica a notícia pelo próprio caminho.

Sem alterações de schema 37 ou backup v12.

## Versão 1.0.151

Corrige notícias que não abriam ao clicar quando as URLs amigáveis estão ativadas em servidor Nginx. O `.htaccess` não é lido pelo Nginx; o novo arquivo `deploy/aws/nginx-friendly-urls.conf` traz as regras de reescrita equivalentes. Também é possível desativar "Usar URLs amigáveis" no painel como solução imediata.

Sem alterações de código PHP, schema 37 ou backup v12. Requer aplicar a regra no Nginx e recarregar o serviço.

## Versão 1.0.150 — Leitura e navegação entre notícias (2026-11-10)

O site público ganhou tipografia de matéria mais legível no celular, metadados agrupados, tempo de leitura aproximado calculado a partir do texto e até três notícias relacionadas da mesma editoria. As sugestões usam apenas matérias publicadas, excluem a matéria aberta e são omitidas se não houver outras. Imagens com carregamento sob demanda; sem chamadas externas, rastreamento, ou novo banco. Nenhuma tela ou arquivo administrativo alterado. Schema 37 e backup v12 preservados. Validado offline (PHP e Chromium); teste na EC2 e no Android real continua pendente. Detalhes em `docs/LEITURA_NOTICIAS_150.md`.

## Versão 1.0.149 — Resultados de notícias na busca (2026-11-10)

O painel automático da busca agora mostra novamente notícias e editorias quando o portal utiliza URLs amigáveis, além dos horários de ônibus. A validação permite apenas as rotas internas esperadas e continua bloqueando links externos. A correção é restrita ao JavaScript público, sem alterar a busca SQL, o ADM, o banco ou a infraestrutura. Foram incluídos testes de regressão para ambos os formatos de URL. A atualização do site na EC2 ainda precisa ser realizada.

## Versão 1.0.148 — Experiência pública (2026-11-10)

Visitantes podem compartilhar matérias via compartilhamento nativo do Android ou copiar o endereço com fallback manual. O site ganha atalho acessível ao conteúdo, indicação de foco, fechamento do menu móvel por Escape/toque externo e prioridade no carregamento das fotos principais. Não altera o ADM, banco, dados, horários, AWS nem configurações de publicação. Validação real na EC2 e no Chrome Android ainda pendente. Detalhes: `docs/EXPERIENCIA_PUBLICA_148.md`.

## Versão 1.0.147

A busca automática do cabeçalho ganhou painel compacto com ícones por tipo de resultado, títulos de até duas linhas, destaque seguro da correspondência, identificação discreta de Notícia/Editoria/Ônibus e link inferior para mais notícias. Menos espaços vazios e rolagem interna.

A área de sugestões adapta a altura ao espaço livre informado pelo `visualViewport` no Android, incluindo a abertura e o fechamento do teclado. Foram preservados o limite de seis sugestões, o atraso de 300 ms, cancelamento, navegação com setas/Enter/Esc, fallback sem JavaScript e URLs locais. Mensagens de busca sem resultado ou indisponível são exibidas sem bloquear o envio GET.

Não altera schema 37, backup v12, credenciais, rota de ônibus ou infraestrutura da EC2. Testes locais não representam validação no Chrome Android ou na AWS.

## Versão 1.0.146

O quadro de ônibus da página inicial foi reduzido: um único card “Próximo ônibus” exibe apenas a primeira saída programada, com horário, dia, destino e contagem até a partida. Foram removidos “TRANSPORTE REGIONAL”, “Saídas programadas”, empresa/ponto, partidas adicionais e o aviso extenso do rodapé. “Ver horários” continua abrindo a página completa.

A busca automática e as demais áreas do portal permanecem inalteradas. Sem mudanças em banco de dados (schema 37), backup v12 ou infraestrutura. Testes locais realizados; ainda falta validar no Chrome Android e na EC2.

## Versão 1.0.145

A página inicial deixa de exibir a faixa horizontal de categorias ("Todas", "Geral", "Cidade", etc.). Em seu lugar aparece "Próximos ônibus", com até três partidas programadas e links ao quadro completo. Só aparecem horários efetivamente cadastrados; não se trata de localização ao vivo.

A busca do topo passa a sugerir notícias publicadas, editorias e trajetos de ônibus durante a digitação. Continua possível pesquisar notícias pelo botão Buscar quando o JavaScript não funciona. Menus de categorias e barra inferior permanecem.

Compatível com schema 37/backup v12, sem migração SQL. Validação local automatizada; testar com MariaDB/Chrome Android e na EC2 antes da atualização de produção. Consulte `docs/HOME_BUSCA_ONIBUS_145.md`.

## Versão 1.0.144

**Atualizador:** a vistoria deixa de reprovar automaticamente arquivos PHP e `VERSION` somente leitura se a pasta de destino permite substituição atômica por `rename()`. Exibe o motivo exato quando o diretório não é gravável, o arquivo não pode ser lido para backup ou há caminho inseguro. Mantidas as proteções de manifesto, backup, rollback e a separação do worker em VPS.

**Atenção:** esta versão não altera permissões reais do servidor. Se o PHP não tiver escrita nos diretórios em hospedagem compartilhada, corrija o proprietário/permissões fora do painel. Na AWS EC2 use o worker `iguanews-deploy`, nunca dê escrita irrestrita ao `www-data`. Schema 37 e backup v12 inalterados; instalação real ainda precisa de teste no servidor.

## Versão 1.0.143

**Melhorias solicitadas após teste no celular:** os atalhos Gemini do título, resumo e SEO deixam de parecer botões vazios e passam a ser claramente identificados. As ferramentas SEO e os botões da biblioteca quebram/reorganizam o layout sem cortar palavras.

**Publicação no passo 9:** um único seletor estilizado com Publicar agora (padrão para quem pode publicar), Rascunho e Agendar fica ao lado do botão Publicar notícia/Salvar rascunho/Agendar notícia. Agendamentos anteriores permanecem agendados e redatores sem autorização não recebem opções indevidas. Nenhuma notícia é publicada sem tocar no botão final; rascunhos automáticos continuam privados.

**Compatibilidade:** nenhuma migração SQL (schema 37, backup v12), configuração de servidor ou alteração na API Gemini. Testes locais automatizados; validação no Chrome Android e na EC2 ainda necessária. Detalhes em `docs/EDITOR_UI_143.md`.

## Versão 1.0.142

**Instalador automático de VPS (com controle por SSH):** novo `deploy/aws/install.sh` no código-fonte completo reduz a configuração inicial do atualizador GitHub a um comando após a publicação do portal e confirmação de backup externo. Cria/prepara `iguanews-deploy`, diretórios seguros e locks, pool PHP-FPM 8.3 exclusivo, vhost Nginx e serviço/timer systemd. Só ativa o timer após testar permissões e heartbeat. Recusa vhosts compartilhados, configurações ambíguas e deploy interrompido; preserva `storage/`, imagens, MariaDB e site de testes.

**Escopo:** não é instalador de dependências nem migrador de dados; primeiro é preciso instalar o código e restaurar banco/mídias. Hospedagem compartilhada inalterada, schema 37 e backup v12 mantidos. O ZIP de atualização online não inclui `deploy/` nem `bin/`: use o ZIP de fonte completo para receber esse recurso. Os testes de script/Nginx são isolados; a execução end-to-end na EC2 ainda exige homologação. Consulte `docs/INSTALADOR_VPS_142.md`.

## Versão 1.0.141

**Correções no editor e central de diagnósticos.** A biblioteca de fotos apresenta capa/galeria no mesmo seletor, carrega com timeout e recuperação, e continua permitindo envio do celular. Diagnóstico SEO corrigido; botões Automático e Gemini IA agora são distinguíveis. Gemini não fica indefinidamente em carregamento, possui timeout/cancelamento e mostra erros. Na revisão final, Rascunho/Publicar/Agendar aparecem em uma linha no topo. Em Ferramentas há uma nova área de diagnóstico de erros técnicos do portal, de forma restrita e sem capturar dados privados.

**Não altera o banco**, schema 37/backup v12, permissões, rotas públicas ou procedimento de deploy AWS. Testes completos e validação dos ZIPs obrigatórios; comportamento em Chrome Android, MariaDB e chamadas Gemini reais deve ser validado no servidor.

## Versão 1.0.140

**Métricas locais reformuladas:** o painel apresenta público provavelmente humano (sem indícios detectados), robôs que se identificam no User-Agent e tráfego suspeito, com gráfico e porcentagens dos três grupos. É possível filtrar as tabelas por grupo e consultar páginas, origens, países/regiões, campanhas, agentes e cliques registrados. Novos agentes de rastreamento e automação passam a ser reconhecidos. Nenhum grupo representa comprovação de identidade, pois o User-Agent é falsificável.

**Mais precisão de horário na EC2:** novos eventos usam explicitamente data e hora do PHP em America/Sao_Paulo, sem depender do fuso da sessão SQL; eventos antigos permanecem intactos. A coleta segue first-party, com respeito a preferências de privacidade, sem IP bruto no banco. Não exige extensão nova, DDL nem alterações em schema 37/backup v12. A confirmação de contagens reais na VPS/MariaDB e a interface no Chrome Android permanecem testes de implantação.

## Versão 1.0.139

**Corrigido:** bloqueios de permissão do worker na EC2. A raiz do projeto deve permitir rename atômico pela conta `iguanews-deploy`; os controles de atualização agora ficam protegidos contra escrita pelo processo web. O painel só reconhece o serviço como pronto após checagens reais de criação e rename pelo worker.

**Instalação:** requer atualização inicial por SSH e execução da documentação `docs/AWS_EC2_DEPLOY.md`, incluindo `bin/aws-deploy-check.php`. O ZIP web não configura usuários, diretórios nem systemd. Preserve os dados da instalação. Não implemente diretamente em produção sem validar numa cópia.

**Segurança:** não executar web como root, não usar chmod 777, não expor segredos; releases v2 sem assinatura continuam dependendo de origem GitHub/TLS. Compatibilidade com hospedagem compartilhada preservada. Schema 37 inalterado.

## Versão 1.0.138

- Atualizador GitHub compatível com EC2 Ubuntu 24.04/Nginx/PHP-FPM/MariaDB, com worker separado não-root e permissões mínimas.
- Vistoria de permissões adaptada, backup antes do deploy e recuperação pelo terminal.
- IndexNow com rota Nginx sem escrita em raiz pública, além do comportamento original em hospedagens compartilhadas.
- A VPS exige implantação inicial e configuração de systemd por SSH; a mera atualização ZIP não altera ownership ou configurações do sistema.

## IguatamaOnline 1.0.137

**Correção urgente do painel de administração:** a página Informações do Servidor deixava o conteúdo em branco e o botão de menu sem resposta porque o módulo `app/server_info.php` não era carregado pelo controlador; a falha interrompia o HTML antes do JavaScript da navegação. A rota foi corrigida e o menu passou a carregar no início da página.

**Resiliência:** Informações do Servidor e Diagnóstico de DNS agora mostram uma mensagem simples com acesso à Central de Saúde quando algum erro interno impedir o diagnóstico, registrando detalhes apenas nos logs privados. Funções de disco bloqueadas pelo provedor deixam os dados como indisponíveis, sem quebrar o painel.

**Compatibilidade:** atualização de código, sem alteração do banco, dados, galeria, schema 37, backup v12 ou DNS. Depois de atualizar, recarregue a página no Chrome Android; a versão na URL dos scripts muda, evitando cache antigo. Testes locais não substituem inspeção no PHP da hospedagem.

## IguatamaOnline 1.0.136

**Ferramentas → Diagnóstico de DNS:** consulta automaticamente o domínio oficial configurado em Geral e SEO. Exibe IPv4 (A), IPv6 (AAAA), alias (CNAME) e servidores de nomes (NS), comparando a resolução PHP da hospedagem com Google Public DNS e Cloudflare. Mostra resultados divergentes e diferencia "Sem registro" de "Consulta indisponível". A tela é compacta e tem botão Atualizar e ajuda `?`.

**Segurança:** o domínio não é fornecido pelo visitante; URLs externas fixas HTTPS, sem redirecionamento, verificação TLS, tempo e tamanho de resposta limitados, resultados escapados, acesso `manage_settings`. A página não altera registros DNS nem consulta servidores de origem de proxy/CDN. Se cURL ou acesso externo estiver bloqueado, as consultas públicas aparecerão como indisponíveis; DNS local pode continuar funcionando. Não há monitoramento em segundo plano.

**Validação:** verificação estática e unitária sem dependência de rede; consultar em hospedagem real para conferir as respostas e a interface no celular. Schema e backup preservados.

## IguatamaOnline 1.0.135

**Correção do GitHub Actions, workflow de integração MariaDB:** o teste de criação de banco e usuário local da 1.0.134 falhou porque a conexão do runner para o MariaDB Docker era identificada como `172.18.0.1`. Agora o ensaio entra somente no namespace de rede do serviço MariaDB e usa `127.0.0.1` de verdade. O teste aborta se não conseguir identificar o contêiner ou se o próprio MySQL identificar o cliente como remoto. Não são concedidos privilégios a IPs externos ou usuários `%`.

**Sem mudanças funcionais no portal:** galeria, conteúdo, schema 37, backup lógico v12 e instalador preservados. Os testes que dependem do MariaDB real ainda precisam ser reexecutados no GitHub Actions com o código-fonte 1.0.135. Para corrigir o workflow no GitHub, envie o **código-fonte completo**; o ZIP de atualização da hospedagem só leva `app/`, `public/` e `VERSION`, não `.github/workflows/`.

## IguatamaOnline 1.0.134

**Galeria de fotos renovada nas matérias:** miniaturas ajustadas para celular e computador, contador, legendas e créditos. Ao tocar numa foto, abre um visualizador sobre a notícia, com navegação por setas, deslizar no celular, teclado e botão Fechar. A imagem original só é carregada quando selecionada; links permanecem funcionais com JavaScript desativado e há botão para abrir o arquivo original.

**Segurança e compatibilidade:** nenhuma alteração de banco de dados, links das fotos, permissões ou backups. Legendas e créditos são escapados no PHP e inseridos como texto no visualizador. Testes de estrutura e interação locais não substituem validação visual real no navegador móvel.

## IguatamaOnline 1.0.133

**Instalador renovado:** primeiro verifique os requisitos; quando os obrigatórios estiverem aprovados, use **Avançar** no topo ou fim da tela. Em seguida, escolha entre **Instalação limpa** e **Migrar meu portal** numa tela independente. A configuração MySQL e os dados finais são solicitados campo a campo, com Voltar/Avançar, ajuda contextual `?` e revisão antes de enviar.

**Senhas:** todos os campos de senha possuem Mostrar/Ocultar. Ao criar o administrador do portal, o instalador exige digitar a senha duas vezes; informa quantos caracteres faltam para atingir os 10 mínimos e mostra a força estimada. A confirmação é revalidada no PHP e nenhuma senha aparece no resumo do wizard.

**Compatibilidade:** sem JavaScript, formulários completos continuam disponíveis. Permanecem a chave privada exigida desde 1.0.132, CSRF, HTTPS, restrições de migração e schema 37/backup v12. A etapa visual não é progresso percentual real da restauração; valide o resultado em hospedagem de testes antes de migrar definitivamente.

## IguatamaOnline 1.0.132

**Instalador protegido:** antes de digitar credenciais MySQL na instalação limpa, crie uma chave com `php bin/setup-access.php` ou coloque uma chave privada aleatória de 48 caracteres hexadecimais em `storage/.setup-access.key`. O instalador exige essa chave para prosseguir. Em migrações novas, o próprio ZIP portátil traz uma chave aleatória na pasta privada; não use um ZIP antigo da 1.0.131 para o novo fluxo sem gerar outro backup.

**Criação parcial detectada:** se MariaDB/MySQL criar banco ou conta, mas falhar em GRANT, a tela informa quais etapas foram concluídas e recomenda recuperação manual, sem executar exclusão automática. A instalação no banco vazio agora testa operações essenciais de tabelas antes de criar a estrutura real.

**Credenciais separadas e TLS opcional para MySQL remoto:** permite fornecer usuário do portal diferente da conta que cria o banco, com aceitação explícita caso precise reutilizar privilégios amplos. TLS autenticado por CA privada pode ser ativado para banco remoto; sem TLS remoto, exige confirmação de rede privada confiável. A comunicação navegador → instalador continua exigindo HTTPS em hospedagem pública.

**Limites:** testes locais sem pdo_mysql nem ZipArchive não comprovam o fluxo integral. Realizar ensaio em servidor de testes antes de migrar DNS ou encerrar a hospedagem antiga.

## IguatamaOnline 1.0.131

**Criar banco sem abrir o painel da hospedagem:** na etapa Banco do instalador, agora é possível tentar criar um MySQL novo fornecendo servidor, porta, nome do banco, usuário e senha com privilégio `CREATE DATABASE`. Hospedagens que bloqueiam esse privilégio continuarão exigindo criar o banco no painel.

**Para VPS com MySQL local:** opção avançada para criar banco e usuário exclusivo de forma automática com senha aleatória, quando o administrador MySQL tem `CREATE USER` e `GRANT`. O usuário administrativo não é guardado no portal. Instalação limpa e migração portátil continuam com seus requisitos de segurança, e o modo de banco existente foi mantido.

**Atenção:** não há exclusão automática de um banco criado caso etapas posteriores falhem. O teste integrado de conexão/criação e privilégios reais depende do CI com MariaDB; teste em uma instalação nova antes de mudar a hospedagem de produção.

## IguatamaOnline 1.0.130

**Backups com tela compacta:** agora há duas opções em Configurações: **Backups e migração**, para criar, baixar e organizar; e **Restaurar dados**, exclusiva para recuperar JSON/GZIP ou ZIP. Textos longos foram movidos para as ajudas `?`, a listagem exibe os cinco mais recentes por padrão e o `?>` perdido no HTML foi corrigido.

**Menos armazenamento ocupado:** padrão de retenção automática de três diários e dois semanais, ajustável. Backups manuais e preventivos não são excluídos automaticamente. É possível proteger backups individuais ou excluir arquivos mediante senha e confirmação; não há lixeira. ZIPs completos e portáteis não deixam o JSON temporário acumulado depois da montagem.

**Segurança e compatibilidade:** nenhum dado é apagado por instalar esta atualização; a rotação age após futuros backups ou confirmação manual. O backup lógico continua v12, schema 37, e o fluxo de migração 1.0.129 permanece. Restauração completa entre provedores ainda exige teste isolado real com MySQL/MariaDB e ZipArchive.

## IguatamaOnline 1.0.129

**Migração portátil revisada:** o ZIP leva as configurações `.htaccess`/`.user.ini` necessárias, evita duplicar mídias referenciadas, inclui manifesto v2 com checksums por arquivo PHP/CSS/JS e valida banco e mídias antes de criar tabelas no destino. O instalador não permite contornar a autenticação administrativa escolhendo instalação limpa quando encontra um pacote portátil.

**Recuperação e transparência:** banco novo precisa estar totalmente vazio. Falha durante a preparação pode reverter apenas o esquema ainda vazio; não há rollback atômico entre MySQL e arquivos. Um novo campo HTTPS opcional permite atualizar o domínio oficial. A recuperação da configuração passa a aceitar banco restaurado com mais de um usuário, exigindo a conta de administrador antigo. Falha de limpeza do backup privado é destacada no login.

**Atenção:** pacotes portáteis da versão 1.0.128 não atendem às novas verificações; gere novo backup na 1.0.129. Extensões PHP ZipArchive e PDO MySQL são indispensáveis, e o teste real entre duas hospedagens ainda é obrigatório antes da migração definitiva.

## IguatamaOnline 1.0.128

**Backup de migração portátil** em Backups e Restauração. Um único ZIP leva os arquivos do portal, instalador PHP, dados MySQL e mídias permitidas para outra hospedagem. O instalador de destino pergunta se a instalação é limpa ou migração, encontra automaticamente o backup do pacote e restaura dados e mídias após testar o MySQL vazio e confirmar a conta administrativa antiga.

**Importante:** o ZIP contém dados pessoais e hashes de senhas; não inclua ou divulgue credenciais de hospedagem. Não substitui DNS, certificados HTTPS, tarefas cron ou chaves privadas externas. O processo depende de ZipArchive em ambos os servidores, espaço disponível e teste real na nova hospedagem. Nenhuma migração SQL de esquema; backup lógico v12 e schema 37 preservados.

## IguatamaOnline 1.0.127

**Mais informações do servidor:** uma nova área de compatibilidade mostra se este PHP oferece versão mínima, PDO MySQL, ZIP, GD, Fileinfo e upload de arquivos. Outra área resume diretivas de erros e preferências de cookies de sessão; a seção de limites também mostra quantos arquivos podem ser enviados e se o envio está habilitado.

**Consulta segura e realista:** os indicadores refletem apenas as configurações e extensões vistas pela requisição atual. Não fazem auditoria de segurança nem confirmam que todas as operações funcionam no provedor. Tudo segue restrito aos administradores e somente leitura. Nenhuma alteração no banco ou no restante do portal.

## IguatamaOnline 1.0.126

**Novo em Ferramentas → Informações do Servidor:** painel leve, pensado para celular, com cartões de PHP, servidor web, banco de dados e memória por processo. Mostra também limites de upload e POST, espaço disponível na partição do PHP, extensões essenciais e permissões de gravação de pastas.

**Limitações e segurança:** informação pontual, somente leitura e exclusiva do administrador. Espaço do disco não é a franquia do plano; memória por processo não é RAM física; CPU e uptime não são monitorados. A página não revela credenciais, caminhos internos ou IPs. Sem mudança no schema 37, dados ou integrações.

## IguatamaOnline 1.0.125

**Explorador mais limpo no celular:** a seleção de arquivos agora é ativada pelo botão **Selecionar**, sem exibir caixas e comandos por toda a tela desde a abertura. Cancelar desmarca os itens. A busca mostra contagem de resultados e aviso de nenhum arquivo encontrado.

**Correção de compatibilidade:** botões ZIP que a hospedagem desativou por falta de `ZipArchive` não são reativados pelo JavaScript. Navegação, downloads, controles de permissões e segurança permanecem como antes.

Nenhuma alteração no banco ou no sistema de autenticação. Exige instalação da atualização para aparecer no site e teste visual no Chrome Android.

## IguatamaOnline 1.0.124

**Gerenciador de Arquivos com visual mais limpo no celular:** reorganizado o topo com um cartão de contexto, separando melhor a navegação em leitura da pasta pública com edição. A área da raiz ficou menos carregada, com o atalho de uploads separado do botão de baixar ZIP.

**Ações em lote mais organizadas:** na pasta `public/uploads/arquivos`, copiar, recortar, compactar e o campo do nome do ZIP passam a usar uma grade mais estável e compacta. O contador de itens selecionados virou selo visual, e o menu de criação foi renomeado para **Novo arquivo ou pasta**.

**Segurança e dados:** nenhuma regra de acesso foi afrouxada. Código do portal continua somente leitura fora da área gerenciada; schema 37, backups, GA4, AdSense e demais integrações permanecem inalterados. Teste novo: `tests/file_manager_224.php`.

## IguatamaOnline 1.0.123

**Comandos úteis no Gerenciador de Arquivos:** seleção múltipla, copiar, recortar, colar, compactar, descompactar, upload ZIP, excluir arquivos e pastas (com confirmação), renomear e permissões limitadas para arquivos gerenciados. Fora dessa pasta, visualização e download de seleção em ZIP para arquivos permitidos do projeto. Ferramentas permanece o submenu de acesso.

**Segurança:** sem edição de executáveis, symlinks ou pastas privadas; limite de quantidade/tamanho, extração sempre em pasta nova, sem substituir nomes existentes. `ZipArchive` é necessário para ZIP; o ZIP não foi testado com extensão real no ambiente local. Manter backup externo antes de exclusões. Nenhuma mudança de banco.

## IguatamaOnline 1.0.122

- O submenu **Ferramentas → Gerenciador de Arquivos** agora mostra a estrutura do projeto desde a raiz, em vez de começar na pasta vazia de uploads. Entre em `app/`, `public/` e outras pastas disponíveis pelo celular.
- Tela dedicada, com pastas e arquivos em lista, caminho navegável, pesquisa rápida, paginação e prévia de códigos/textos e imagens. Removidos os cartões de Biblioteca de mídias, Backups e Arquivos essenciais desta tela.
- Arquivos PHP e demais arquivos do sistema podem ser lidos ou baixados por administradores, mas **não podem ser alterados por esse módulo**. `storage/` e segredos permanecem protegidos.
- Upload e gerenciamento continuam na pasta `public/uploads/arquivos/`, e a edição de `ads.txt` / `robots.txt` continua disponível ao navegar em `public/`. Nenhum dado ou banco foi alterado.
- Testes locais de PHP, JS, segurança de caminhos e pacotes; testar navegação, permissões e operações em aparelho/hospedagem após instalação.

## 1.0.121 — Novo Gerenciador de Arquivos em Ferramentas

O painel administrativo agora inclui **Ferramentas → Gerenciador de Arquivos** (somente administradores). Permite pastas, envio de arquivos JPG/PNG/WebP/PDF/TXT/CSV, downloads, renomear, excluir e editar textos; também permite editar `ads.txt` e `robots.txt` com segurança.

Os arquivos de `uploads/arquivos` são públicos; não guarde dados privados ali. Upload limitado a 8 MiB, texto a 128 KiB. Não permite scripts PHP nem acesso à configuração interna do servidor. Exige permissões de escrita na hospedagem. Nenhuma migração SQL.

## 1.0.120 — Navegação sem aviso de métricas

O aviso fixo “Google Analytics” deixa de aparecer sobre as notícias. A autorização opcional está na Política de Privacidade, acessível pelo rodapé. Consentimentos já registrados continuam válidos, e a preferência DNT/GPC do navegador permanece respeitada; não há ativação automática da tag para visitantes sem autorização. O painel de métricas internas não foi afetado. Sem alterações de banco, schema 37, backup v12 ou APIs Google. A publicação no site exige instalar a atualização e validar manualmente.

## 1.0.119 — Google Analytics mais claro

A janela do GA4 agora diferencia a coleta de visitantes mediante consentimento dos relatórios opcionais dentro do painel. Inclui orientações para verificar visitas no relatório Tempo real do Google, explica o papel dos IDs e da conta de serviço, recolhe as opções avançadas e deixa os botões indisponíveis com aparência coerente. Nenhuma migração de banco.

## IguatamaOnline 1.0.118

- Corrigido o ensaio integrado de ZIP adulterado após falha do workflow #33. Antes, um payload com tamanho diferente era rejeitado antes da verificação SHA-256; o teste agora separa as rejeições por tamanho e por hash (mantendo o tamanho original).
- O pipeline e o teste offline passam a proteger os dois cenários, sem alterar o atualizador ou as rotinas de banco, backup e restauração.
- Os logs comprovaram a instalação ZIP, o backup MariaDB e o rollback com sucesso no CI da 1.0.117. A execução dos cenários corrigidos em MariaDB/ZipArchive reais permanece pendente até um novo workflow.
- Schema 37, backup v12 e ZIP v2 preservados. Nenhuma instalação na hospedagem.

## IguatamaOnline 1.0.117

- Teste integrado de atualização ZIP v2 com MariaDB e ZipArchive reais em ambiente isolado de CI: instala arquivos de teste, gera backup lógico v12, verifica SHA-256, executa rollback e rejeita pacote adulterado.
- A suíte nova exige banco `igua_ci_*`, host local e autorização explícita; não toca nos arquivos publicados nem restaura dados reais. Inclui teste offline de contrato para assegurar que o workflow continua protegendo essa integração.
- Não houve mudanças no comportamento do portal, schema 37, backup v12 ou código de atualização. Ainda é necessária execução bem-sucedida do GitHub Actions e homologação no Plesk; os testes locais não podem substituí-las.

## IguatamaOnline 1.0.116

- Corrigido um risco de perda de alterações manuais ao recuperar uma atualização interrompida. Antes de reverter qualquer arquivo, o atualizador confirma que cada destino ainda está igual ao estado original ou ao novo estado registrado; conteúdo inesperado exige recuperação assistida, sem sobrescrevê-lo automaticamente.
- Teste adversarial reproduziu o problema na base 1.0.115 e validou a correção, inclusive na retomada de rollback parcial. Estados `complete`, snapshots privados e bloqueio de alterações posteriores continuam protegidos.
- Schema 37, backups v12, ZIP v2 e recursos do portal preservados. Nenhuma restauração real com banco ou instalação no Plesk. Faça backup externo antes da atualização.

## IguatamaOnline 1.0.115

- Atualizador revisado para validar todos os snapshots antes do rollback e evitar restauração parcial quando algum backup já está ausente/adulterado.
- Recuperação manual usa manutenção e journal recuperável antes de modificar arquivos. Se o processo parar durante o rollback, é possível reexecutar a recuperação; a versão só é considerada restaurada depois de registrar `rolled_back` e encerrar a manutenção.
- Adicionados testes offline de falhas injetadas após 1, 3 e 4 substituições e durante a restauração; teste adicional de edição posterior bloqueia o rollback de uma atualização concluída.
- O atualizador avisa quando não consegue retirar a manutenção, em vez de declarar sucesso. Schema 37, backups v12 e arquivos de dados existentes permanecem intactos.
- **Limite:** testes de arquivos temporários não substituem MariaDB, ZipArchive, falta de disco real nem interrupções de processo no Plesk. Faça backup externo antes de atualizar.

## IguatamaOnline 1.0.114

- Fortalecida a proteção no acesso administrativo aos backups: a lista e o download não seguem arquivos nem pasta de backup que sejam links simbólicos. Arquivos normais permanecem compatíveis.
- Adicionada regressão de cenários adversos: GZIP danificado, estrutura de banco inválida, ausência de administrador ativo, nomes de backup inseguros e manifestos de atualização malformados ou sem assinatura obrigatória no formato legado.
- Sem alteração dos dados, de rotinas de restauração, do schema 37 ou do backup v12. Os testes offline não comprovam restauração completa no Plesk; conferir backup e rollback em homologação antes de mudanças críticas.

## IguatamaOnline 1.0.113

- Melhor organização interna do Wizard de Notícias: ações de categorias/fontes, Gemini, rascunhos/importação, salvamento e publicação separadas em arquivos pequenos, com mesma rota administrativa.
- Fluxo da notícia, formulários, editor, mídias, categorias, fonte principal, SEO, agendamento, anexos e fotos ZIP não receberam alteração funcional. O controlador original foi reconstituído byte a byte para validar equivalência.
- Schema 37 e backup v12 preservados; sem migração e sem exclusão de mídia. Testes locais não substituem conferência no Plesk após a atualização.

## IguatamaOnline 1.0.112

- Refatoração de manutenção na Central de Integrações e APIs: o template principal chama componentes específicos de Google Analytics 4, AdSense, Search Console, PageSpeed Insights, Gemini, Diagnóstico, Inspeção de Páginas, Catálogo e Janelas.
- O template foi reconstruído byte a byte no teste de regressão; não foram redesenhados formulários, pop-ups, credenciais, rotas ou funções do Google/Bing.
- Sem novas permissões, migração ou mudança de dados. Schema 37, backup v12, arquivos privados e APIs existentes preservados.
- Testes offline não substituem validação visual e funcional no Plesk/Chrome Android.

## IguatamaOnline 1.0.111

- Refatoração interna de `app/analytics.php`: funções de privacidade, classificação, roteamento e leitura auxiliar isoladas em `app/analytics_helpers.php`; a inclusão é automática e a API de métricas não muda.
- A tela SEO mantém exatamente os mesmos campos e modais, mas atualiza prévias de texto, imagem social e favicon em funções separadas, facilitando manutenção futura.
- Novos testes PHP/JavaScript de compatibilidade e regressão. Não altera métricas históricas, classificação, notícias, credenciais, configurações nem SEO público.
- Schema 37 e backup v12 preservados. É necessário testar na hospedagem após instalar.

## IguatamaOnline 1.0.110

- Biblioteca de mídias agora é o caminho visual único para trocar imagem social e favicon, com prévia e limpeza. Os caminhos técnicos deixam de aparecer em Configurações avançadas.
- SEO de páginas fixas: títulos e metadescrições opcionais individuais; em branco, os padrões anteriores permanecem. A descrição salva de até 320 caracteres não é silenciosamente cortada a 260 pelo gerador público.
- Métricas com rótulos mais precisos: visualizações não identificadas como robôs não são equivalentes a pessoas; visitantes/dia são identificadores estimados. Sinais adicionais de automação e limiar de volume elevado; relatórios antigos não são reclassificados.
- URLs amigáveis passam a ser contabilizadas por página quando a rota válida está disponível. Logs completos do servidor podem ser necessários para investigar visitas passadas.
- Sem migração, schema 37 e backup v12. Testes externos e ambiente Plesk ainda requerem validação.

## IguatamaOnline 1.0.109

- Página de ônibus: lupa abre/recolhe o formulário de busca, sem alterar horários existentes.
- SEO: campo para título exato da página inicial e títulos de páginas institucionais, preservando títulos de notícias e categorias.
- Favicon: escolher imagem na biblioteca existente ou informar URL, com ícone padrão se vazio.
- Janela de configurações avançadas reparada para exibir campos completos e permitir rolagem interna no celular.
- Sem migração de banco (schema 37); requer instalação na hospedagem para validar a experiência real.

## IguatamaOnline 1.0.108

- Sitemap e Indexação redesenhado para economizar espaço no celular: resumo e URLs na página; Verificações, IndexNow e Bing em pop-ups centralizados com rolagem interna.
- Busca, filtros, envio IndexNow, teste Bing, diagnóstico HTTP e operações protegidas preservadas. Listagem de 12 URLs por página; link completo continua disponível para copiar/abrir.
- Contato do desenvolvedor ganhou **Meu site** com link para `https://adriedsonlemos.vercel.app`.
- Sem migração (schema 37). Recomendado backup antes de instalar. Testes de produção e integrações externas seguem pendentes.

## IguatamaOnline 1.0.107

- Verificação Bing via arquivo `BingSiteAuth.xml` fornecido pelo proprietário, a ser servido no endereço HTTPS público. A Central de Sitemap oferece acesso direto ao arquivo; finalize no botão **Verify** do Bing Webmaster Tools.
- Menu do painel simplificado para **Páginas**.
- Ônibus: lista de linhas organizada em cartões, filtro por cidade de origem e acesso para editar percurso/origem, sem rolagem horizontal no celular.
- Sem migração de banco (schema 37). Não altera linhas existentes; teste na hospedagem ainda necessário.

## Meu Site v1.0.106 — Teixeira e Bing Webmaster

- **Ônibus:** correção do pop-up que confirmava a tabela sem executar o POST. A seleção continua voluntária, autenticada e protegida por CSRF. Depois de instalar, é necessário confirmar novamente o cadastro dos 10 trajetos. Horários existentes não são substituídos.
- **Bing:** nova consulta manual de verificação da propriedade e de sitemaps em Configurações → Sitemap e Indexação. A chave API fica em storage privado, sem exposição no navegador; configuração e remoção exigem senha administrativa. Não envia URLs nem garante indexação.
- **Validação:** regressões offline e teste MariaDB no CI; API externa e hospedagem ainda não testadas. Schema 37 mantido, sem migração de banco.

## 1.0.105 — IndexNow

- A Central de Sitemap agora oferece configuração IndexNow e envio manual com validação das URLs.
- Chave de verificação em arquivo TXT público; dados de configuração no storage privado.
- Não envia URLs ao Google; participação é de Bing e outros mecanismos IndexNow.
- Sem nova migração de banco (schema 37).

# Meu Site v1.0.104 — Ônibus Teixeira e teste Gemini

- Ônibus: inclusão **manual, mediante confirmação no painel**, dos 10 trajetos da tabela fotografada da Teixeira em setembro/2026, com 31 grupos de saídas. Já cadastrados são preservados e não duplicados. Rodoviárias como origem/destino; chegada e tarifa não informadas. Feriados em dias úteis não são remapeados automaticamente.
- Gemini: serviço disponível na aba Google com Salvar configuração e Testar API lado a lado. Remoção separada, protegida por senha. Teste em pop-up com resultado real e orientações por código HTTP, em especial 503 por sobrecarga temporária. Sem expor chave.
- Schema 37/backup v12 sem mudanças, sem migração; manter backup antes de instalar. Não foi possível validar o fluxo real com MariaDB da hospedagem nem API Gemini com chave do proprietário.

---

# Meu Site v1.0.103 — Integração Gemini no portal

- Card Google Gemini já existente no catálogo foi ativado: credencial API no armazenamento privado, modelo configurável, ativar/desativar, teste manual real e remoção protegida por senha administrativa. API via HTTPS e host fixo; chaves nunca em HTML, URL de requisição ou GitHub.
- No wizard, quatro atalhos com **somente o símbolo ✦** sugerem manchete, resumo, revisão ortográfica do corpo e descrição SEO. Cada consulta requer clique explícito, tem limites de texto/cota por sessão e abre pop-up de revisão; nada é aplicado ou publicado automaticamente.
- Teste API não foi realizado com a conta do proprietário: é necessário cadastrar uma chave verdadeira e confirmar modelo/cota na hospedagem. Chamadas reais à API podem consumir créditos.
- Sem migração de banco (schema 37 e backup v12). Nenhuma notícia nem configuração existente foi modificada automaticamente.

---

# Meu Site v1.0.102 — Centro visual de SEO

- Prévia Google e compartilhamento social, com diagnóstico local de título, descrição, URL HTTPS, imagem e configurações de rastreamento.
- Formulário geral preservado; controles técnicos agora ficam em pop-ups centralizados, sem seções expansíveis.
- Ajuste de imagem social pelo gerenciador, alerta para URLs inadequadas e confirmação para desligar a indexação.
- Sem migração: schema 37 e backup v12. Nenhuma verificação HTTP ou de indexação é executada automaticamente.
- Testes na hospedagem e no navegador Android continuam necessários.

---

# Meu Site v1.0.101 — Correções do cadastro e do destaque principal

- Corrigido **HTTP 404** dos botões Nova categoria e Nova fonte durante a edição de notícias.
- Formulários exibem motivos de validação retornados pelo servidor, sem expor mensagens internas de banco de dados.
- Somente notícias marcadas como **Destacar na página inicial** podem ocupar o destaque principal; notícias comuns permanecem nas listas normais.
- Nenhum dado ou estrutura de banco alterado. Schema 37 e backup v12 preservados. Instalação e verificação na hospedagem continuam pendentes.

---

# Meu Site v1.0.100 — Central de Sitemap e Indexação

- **Configurações → Sitemap e Indexação:** consulte as URLs elegíveis para buscadores em listas filtráveis e paginadas, com datas de atualização verificáveis, botões Copiar, Abrir e Testar HTTP.
- **Verificar online:** consulta `sitemap.php`, `robots.txt` e `ads.txt` sob demanda pelo navegador, exibindo códigos HTTP e falhas sem expor credenciais. Não significa que o Google indexou os links.
- `robots.txt` agora anuncia o sitemap no domínio oficial. O XML usa o catálogo do painel; conteúdos não publicados e demonstrativos continuam excluídos.
- O arquivo `ads.txt` existente na hospedagem não é sobrescrito. Configuração AdSense continua em Publicidade; a Search Console pode ser acessada a partir da central.
- Sem alteração de banco, schema 37 e backup v12 mantidos. Testes no MariaDB e na hospedagem real ainda necessários.

---

# Meu Site v1.0.99 — Publicação de páginas legais e data automática

- Pop-ups de confirmação e conteúdo centralizados, com altura ajustada ao texto, rolagem interna quando necessária e sem ocupar desnecessariamente o celular.
- Separadas Informações e Páginas do aplicativo. Cartões com botões compactos de Editar, Prévia/Visualizar, Copiar e Publicar.
- **Publicar tudo** salva as três revisões principais numa única transação: erros não deixam publicação parcial. Rascunhos não alteram automaticamente a versão pública.
- A data de **Última atualização** deriva de `legal_app_revisions.created_at` (momento da publicação), sem ser digitada no texto. Reutilizar conteúdo idêntico não altera a data.
- Modelos novos do Uai Cortes revisados sem instruções PREENCHER nem datas fixas; opção de atualizar somente rascunhos antigos intactos, preservando edições existentes. A conformidade jurídica depende de revisão do administrador.
- Schema 37, backup v12 e recursos antigos preservados. Testes de integração em MariaDB/hospedagem ainda necessários.

---

# Meu Site v1.0.98 — Links para visualizar páginas de aplicativos

- Nos cartões de páginas legais, **Ver página** abre o endereço real em nova aba somente se aplicativo e página estiverem publicados.
- Enquanto houver rascunho, **Prévia** abre o editor autenticado com o modal de visualização já aberto; a URL pública permanece protegida.
- Editar, Prévia/Ver página e Copiar URL organizados em uma linha responsiva no celular.
- Sem alteração de banco (schema 37) ou backup (v12). Instalações ainda no schema 36 exigem migração assistida.

---

# Meu Site v1.0.97 — Diagnóstico de atualizações e modelos Uai Cortes

- Pop-ups GitHub e Enviar ZIP centralizados com altura proporcional ao conteúdo no celular.
- Diagnóstico controlado de consulta GitHub (DNS, TLS, cURL, timeout, status HTTP e falhas de armazenamento), com orientações e sem vazamento de erros brutos.
- Envio manual pode fazer simulação segura do ZIP antes da instalação. Validação de formato, assinatura opcional, hash, versão, schema e permissões; a simulação não grava conteúdo nem executa migrações.
- Modelos editáveis de apresentação, Política de Privacidade e Termos para Uai Cortes. Rascunhos novos recebem exemplos; os já existentes recebem botão de importação conservador, sem substituir edição prévia.
- Schema **37** preservado. Não desabilita o bloqueio para atualização da revisão 36→37: essa migração exige backup externo e instalação assistida.
- Testes de diagnósticos e modelos adicionados. PHP/MySQL real e upload na hospedagem ainda exigem validação; revisão jurídica dos modelos obrigatória.

---

# Meu Site v1.0.96 — Aplicativos e Páginas Legais

- Módulo integrado ao menu administrativo para gerenciar diversos aplicativos e suas páginas de apresentação, privacidade, termos e páginas extras, com edição livre do conteúdo no banco.
- Slugs permanentes e URLs públicas amigáveis; alterações explícitas de slug geram histórico e redirecionamentos 301.
- Formatação Markdown restrita com escaping de HTML no servidor. Prévia, descrição SEO, rascunhos isolados, publicações versionadas e restauração para rascunho.
- Uai Cortes cadastrado como **rascunho**, contendo instruções editáveis e não declarações de privacidade presumidas.
- Quatro novas tabelas, **schema 37** e **backup lógico v12**, com compatibilidade de leitura/restauração dos backups antigos.
- **IMPORTANTE:** como há migração do banco, não instale pela função normal de atualização da versão 1.0.95/schema 36. É necessária **instalação assistida**, após backup externo completo, seguindo `docs/APLICATIVOS_LEGAIS.md` e `docs/MIGRACAO.md`.
- Testes offline PHP/JS e integridade do ZIP executáveis localmente; o ensaio real de integração MariaDB foi acrescentado ao GitHub Actions e permanece pendente de execução neste ambiente. Publicação, SSL/rewrites e OAuth devem ser revisados em homologação; aprovação Google não é garantida.

---

# Meu Site v1.0.95 — Compatibilidade do teste PHP 8.3 no CI

- Corrigido erro fatal ao lidar com uma data inválida na renderização da lista de notícias. A data não é inventada; quando não puder ser interpretada, a interface informa “Data indisponível”.
- `tests/news_relative_date_195.php` amplia a cobertura para entradas inválidas e datas impossíveis; os testes do wizard 1.0.94 permanecem.
- Não houve migração de banco, alteração de dados, remoção de recursos nem mudança nas regras de segurança.
- O workflow anterior falhou antes da integração MariaDB/ZipArchive. Este pacote só deve ser considerado validado em CI depois de um novo run bem-sucedido.

---

# Meu Site v1.0.94 — Wizard, SEO e listas de notícias

- Botão Sair no canto direito do cabeçalho da notícia, sem importação por URL no assistente.
- Uploads de Capa/Fotos/ZIP/Anexos em botões exclusivos, sem controles globais duplicados.
- Ferramentas SEO com quatro ícones e pop-ups para slug, geração de descrição, edição de título/descrição e diagnóstico com correção.
- O diagnóstico permite alterar e aplicar ajustes ao rascunho, com botão Salvar à direita; Cancelar não modifica os campos da notícia.
- Notícias cadastradas e publicações recentes compartilham cartões responsivos, ícone de lixeira, categoria e tempo relativo conforme data real.
- Sem alteração do schema 36; testes na hospedagem e no Android pendentes.

---

# Meu Site v1.0.93 — Pop-ups padronizados no painel

- Todas as janelas de conteúdo principais do painel passam a seguir o mesmo padrão visual, usando um componente reutilizável.
- Corrigidas as janelas gigantes de Google Analytics 4, AdSense, Search Console e PageSpeed no celular: agora centralizadas, com margens, limite de altura e rolagem interna, sem deslocamento horizontal por campos compridos.
- PageSpeed agora mostra Celular e Computador em abas próprias; alternar abas não consulta a API e não altera resultados existentes.
- Janelas de atualização, publicidade, avisos e editor recebem os mesmos cantos, cores, dimensões e botão de fechar, preservando seus formulários e operações.
- Verificações de segurança, confirmação de senha, CSRF e progresso real de atualização permanecem. Banco schema 36 inalterado.
- Testes locais e simulação de layout em Chromium executados. Necessário validar em dispositivo Android real e hospedagem antes de considerar a implantação aprovada.

---

# Meu Site v1.0.92 — Atualizações pelo GitHub em janela única

- Tela principal mais enxuta: **Visualizar** e **Enviar ZIP**, sem link Releases nem cartões de origem/servidor.
- **Visualizar** consulta versões e novidades sob demanda; se houver release instalável, apresenta confirmação e senha na própria janela.
- Durante a instalação, mostra as etapas confirmadas pelo PHP (download, validação, vistoria, backup e aplicação), sem percentual artificial. Ao confirmar o sucesso: **Atualização concluída** e botão **Atualizar página**.
- Configuração do repositório, vistoria, importação manual, segurança, backups e recuperação mantidos.
- Código e testes locais atualizados. Schema 36 preservado. **Hospedagem real não testada nesta entrega**.

---

# Meu Site 1.0.91 — Wizard editorial compacto

O cadastro de notícia foi refinado para uso em telas móveis: etapas recolhíveis, editor com uma linha de ferramentas, área de fotos em duas grades de três botões, sem guia no topo e com opções avançadas reduzidas.

**Rascunho:** aviso de recuperação com Recuperar/Ignorar e supressão durante a mesma sessão de aba após decisão. Esta ação não exclui o texto salvo no servidor; arquivos locais ainda não entram no autosave.

**Classificação:** administradores autorizados a gerenciar taxonomia podem cadastrar categorias e fontes em um diálogo sem sair do wizard. A fonte principal é única, mantendo o banco no schema 36; associações de múltiplas fontes à mesma notícia ainda dependem de desenvolvimento adicional.

**SEO:** barra de diagnóstico editorial 0-100, verificação detalhada em diálogo, ajuste manual opcional e prévia aproximada. Sem chamada à API Gemini ou garantia de exibição pelo Google.

**Anexos:** aceita documentos, imagens, áudio, MP4 e ZIP sob lista de formatos com verificação de MIME e estrutura. Arquivos executáveis/ativos não são aceitos. Uploads permanecem fora da raiz pública, com download forçado; backups completos incluem novos formatos. O servidor deve ter ZipArchive para ZIP/Office.

**Instalação:** atualização do PHP/CSS/JS sem migração de banco; faça backup do banco e das mídias antes de instalar. A importação de anexos e os diálogos ainda precisam de teste integrado na hospedagem e navegador Android. Gere o ZIP de atualização pelo script oficial e não instale o ZIP de código-fonte na hospedagem.
