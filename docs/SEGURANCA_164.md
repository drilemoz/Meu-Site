# Central de Segurança — Meu Site v1.0.64

## O que foi implementado

- Nova rota administrativa **`/admin.php?action=security`**, exclusivamente para perfis com permissão `manage_settings` (administrador).
- Visão geral de eventos registrados em 1, 7 ou 30 dias; filtros por gravidade, tipo e IP exato; cartões com alertas e últimas 100 ocorrências correspondentes.
- Eventos reais de autenticação: sucesso/falha de login, bloqueio de login (limite existente de cinco falhas em 15 minutos por IP), sessão persistente, logout, CSRF rejeitado e permissões recusadas.
- Auditoria de solicitações de operações sensíveis e de alterações de usuários/configurações, restaurações concluídas e atualizações instaladas ou com falha.
- Detecção **limitada** de caminhos típicos de varredura quando a requisição passa pelo PHP; o sistema não lê logs de Apache, Nginx ou CDN e não inspeciona pacotes da rede.
- Bloqueio **manual** de IP público no acesso ao painel por 15 min, 1 h, 6 h ou 24 h; requer senha administrativa e CSRF. Desbloqueio também requer senha, nunca há bloqueio automático por suspeita. A própria origem do administrador não pode ser bloqueada. Bloqueios expiram sem cron.

## Onde são armazenados os dados

Os registros são acrescentados em `storage/security/monitor/AAAA-MM-DD.jsonl`, e os bloqueios em `storage/security/monitor/blocks.json`, protegidos por permissões privadas e fora de `public/`. Os registros incluem data/hora, tipo e gravidade, IP visto em `REMOTE_ADDR`, ID numérico do usuário e, quando aplicável, identificador fixo da ação. Não guardam e-mail, nome, senha, cookie, token, corpo POST, User-Agent nem URI completa.

- Retenção: **30 dias de calendário**, com limpeza em gravações subsequentes. Se não houver nova atividade, a limpeza aguardará a próxima gravação; a limpeza pode ser executada manualmente em manutenção.
- Limite por arquivo diário: **1 MiB**, para conter crescimento sob abuso. Após atingir o limite, novos registros do dia não entram no arquivo; contagens representam somente o que foi persistido.
- Sem alteração do MySQL, no esquema **36**, e sem mudanças no formato do backup lógico. Os logs e bloqueios não entram no ZIP de atualização nem são incluídos no backup do banco. Para preservar esses dados, faça uma cópia privada separada, nunca pública.
- `storage/` **não pode** ser acessível pela web. Confirme a raiz pública real do servidor, regras de Apache/Nginx e permissões de arquivos. Se os logs não puderem ser gravados, a tela avisa; a aplicação não interrompe publicações por falha do logger.

## Limitações e precauções

- O registro de um acesso a um caminho suspeito indica **possível varredura**, não invasão comprovada. Uma requisição negada antes do PHP não aparece aqui. Para monitoramento abrangente, usar logs do servidor ou solução WAF com integração separada.
- `REMOTE_ADDR` não equivale sempre ao IP original quando há proxy/CDN; cabeçalhos `X-Forwarded-For` fornecidos pelo cliente não são confiáveis por padrão. Bloquear uma origem atrás de NAT ou proxy pode afetar outros usuários.
- Bloqueios são restritos ao **painel PHP**; não substituem regras de firewall do provedor. Em caso de erro de acesso, o administrador pode usar o painel de hospedagem para restaurar o arquivo privado de bloqueios, desde que tenha backup seguro e permissões adequadas.
- O site não passa a ter proteção completa contra DDoS, SQL injection ou exploração zero-day por instalar essa tela. Continuam necessários atualizações, backups externos, permissões mínimas, HTTPS e testes reais.
- Como pacotes ZIP v2 são **sem assinatura**, o hash SHA-256 serve para integridade contra o manifesto do próprio ZIP, não para autenticação da procedência. Inspecione a origem e realize simulação e backup antes de instalar.

## Testes e atualização

1. `php tests/security_164.php` — testes funcionais de gravação, consulta, prazo de IP, retenção e integrações.
2. `python3 tools/build_css.py --check` — CSS integrado.
3. `php tests/smoke.php` e regressões existentes.
4. `python3 tools/build_update_package.py --output /caminho/Atualizacao-v1.0.64.zip` — gera ZIP v2 sem assinatura, com schema 36.
5. Na v1.0.63 instalada: **Configurações → Atualizações**, simule o pacote, faça backup e confirme com sua senha. Não é necessário alterar o banco.
6. Na hospedagem, verifique a página no navegador (celular), login legítimo e senha incorreta de teste, auditoria de ações e bloqueios, acesso à pasta privada e não exposição HTTP dos logs. **Não teste bloqueando seu próprio IP nem IP compartilhado importante**.

A integração com Apache/Nginx, Cloudflare, e-mail e 2FA ficou para versões futuras; nada disso foi ativado nesta versão.
