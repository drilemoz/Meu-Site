# IguatamaOnline 1.0.142 — Preparação automática de VPS

Este instalador **prepara o atualizador GitHub após a primeira implantação do código**, na VPS Ubuntu 24.04 com Nginx, PHP 8.3/PHP-FPM, MariaDB local ou remoto e `www-data`. O caminho suportado é `/var/www/projetos/iguatamaonline` e o site de testes `/var/www/iguatama-teste` **não é modificado**. O portal e o banco devem existir e estar configurados antes de executar.

## Pré-requisitos e segurança

- Acesse a VPS como administrador por SSH, com `sudo` disponível. Publique **o ZIP de código-fonte completo** no caminho do portal, preservando `storage/`, `public/uploads/`, `public/ads.txt`, `public/robots.txt` e configurações específicas. `Atualizacao-v1.0.142.zip` não contém `deploy/`, `bin/`, testes nem arquivos `systemd`.
- Faça e confira **backup externo** do banco, `storage/` e `public/uploads/`, além de snapshot do disco, se possível. O instalador salva ainda cópias locais privadas do vhost e do pool em `/var/backups/iguatamaonline-installer/`, mas **isso não substitui backup dos dados**.
- Verifique se **não** há atualização ativa, `running.json`, `request.json`, marcador de manutenção privado ou legado. O instalador **aborta**, sem remover esses marcadores.
- Confirme que Nginx já atende este portal por **um vhost próprio** que declara `root /var/www/projetos/iguatamaonline/public;` e exatamente uma diretiva `fastcgi_pass` direta para o socket padrão PHP 8.3 ou para o pool dedicado. Ambiguidades, includes globais com upstream, vhost compartilhado, pool dedicado não gerenciado e versões PHP não suportadas interrompem a execução; ajustes manuais podem ser necessários.
- Pacotes esperados já instalados: Nginx, `php8.3-cli`, `php8.3-fpm`, extensões `zip`, `pdo_mysql`, `curl`, `posix`, `python3`, `rsync`, `systemd`, utilitários Unix. Não executa `apt upgrade` nem instala pacotes sem consentimento explícito.

## Um comando para preparar o serviço

No **terminal SSH**, após a cópia da versão completa e do backup:

```bash
cd /var/www/projetos/iguatamaonline
sudo bash deploy/aws/install.sh
```

A primeira execução pede digitar `BACKUP` para confirmar que as cópias externas foram verificadas. Também existe uma inspeção **sem alterar nada**:

```bash
sudo bash deploy/aws/install.sh --check
```

Para VPS preparada por automação que **já confirmou o backup**:

```bash
sudo bash deploy/aws/install.sh --ack-backup
```

Se o Nginx possuir várias configurações candidatas, identifique primeiro o vhost exclusivo do portal:

```bash
sudo bash deploy/aws/install.sh --check --nginx-conf /etc/nginx/sites-available/iguatamaonline
```

O caminho fornecido precisa corresponder a um vhost **ativo**. Informá-lo não ignora o verificador de raiz e upstream.

## O que é automatizado

1. Verifica Ubuntu, PHP-FPM 8.3, extensões, Nginx, vhost exclusivo e ausência de atualização pendente. Não executa SQL nem altera domínio, DNS ou TLS.
2. Cria `iguanews-deploy` (se necessário) e o adiciona ao grupo `www-data`, sem fazer PHP-FPM trabalhar como root.
3. Ajusta **somente** o código `app/`, `public/` (excluindo `public/uploads/`), `bin/`, `VERSION` e os diretórios necessários. Não usa `chmod 777`, não altera conteúdos do banco e não limpa backups ou journals.
4. Configura a fila compartilhada sticky `3770`, a área privada worker `2750`, os locks e `storage/backups/` em `2770`. Preserva estados e trabalhos antigos; se houver atualizações pendentes, aborta antes.
5. Cria o pool dedicado `/etc/php/8.3/fpm/pool.d/iguatamaonline.conf`, executando PHP como `www-data` com `IGUA_DEPLOY_VPS=1`. Muda **somente** o `fastcgi_pass` autorizado do vhost exclusivo; não edita o pool global `www.conf` nem o vhost de testes.
6. Salva cópias da configuração do Nginx e do pool, valida `php-fpm8.3 -t` e `nginx -t`, reinicia PHP-FPM e recarrega Nginx. Se falhar posteriormente, tenta restaurar os arquivos de configuração anteriores. Permissões já alteradas não são revertidas automaticamente.
7. Instala `iguatama-deploy.service` e `.timer` do código-fonte, executa `aws-deploy-check.php` como usuário dedicado, confirma que o `www-data` **não** escreve em código e que consegue escrever na fila e, se existir, em uploads.
8. Executa o serviço uma vez, verifica `worker.json` recente com `ready=true` e **somente então** habilita o timer.

O instalador pode ser reexecutado no mesmo ambiente quando nenhuma atualização estiver em andamento. Isso não remove segredos nem zera o banco. Ainda pode haver breve reinício do PHP-FPM; execute preferencialmente em horário de menor tráfego.

## Limitações e recuperação

- **Não é um migrador completo:** não transfere conteúdos do banco, arquivos ou mídia entre VPSs. Para migrar, restaure os backups na VPS nova e teste o site antes de trocar DNS. O script prepara somente o ambiente de deploy depois que a aplicação estiver instalada.
- **Não funciona pela interface web:** por segurança, é necessário `sudo` via SSH para criar usuários e configurar systemd/Nginx. Só a primeira preparação exige isso; depois as atualizações normais ocorrem pelo GitHub através do worker.
- Um Nginx com múltiplos `fastcgi_pass` (por exemplo, bloco virtual IndexNow separado) é rejeitado para evitar troca de socket incompleta. Ajuste-o manualmente conforme `docs/AWS_EC2_DEPLOY.md` e rode novamente a vistoria. O pool criado usa `IGUA_INDEXNOW_VIRTUAL=0` até que a rota Nginx específica seja configurada e testada.
- Se uma etapa falhar, **o timer não é habilitado por essa execução**. Caso já estivesse ativo, o instalador o interrompe antes de modificar o ambiente. As configurações do vhost e pool são restauradas na medida possível; backups das configurações permanecem em `/var/backups/iguatamaonline-installer/`. Conferir logs do PHP-FPM, `journalctl -u iguatama-deploy.service` e `nginx -t`.
- O modo `--check` verifica pré-requisitos e segurança de configuração, mas **não substitui** uma vistoria real de permissões; essa é feita pelo CLI dedicado durante a aplicação.
- Testes desta versão são **isolados e sem privilégios**, com vhosts fictícios. A execução real com Nginx, PHP-FPM, `systemd`, MariaDB, envio de mídia e atualização/rollback ainda precisa ser validada numa VPS de homologação.

## Verificações após instalar

```bash
sudo -u iguanews-deploy env IGUA_DEPLOY_VPS=1 /usr/bin/php8.3 \
  /var/www/projetos/iguatamaonline/bin/aws-deploy-check.php
systemctl status iguatama-deploy.timer --no-pager
systemctl status php8.3-fpm --no-pager
sudo nginx -t
```

A vistoria deve terminar em **`Vistoria da conta deploy aprovada.`**, e o site precisa abrir e permitir upload de mídia normalmente. Teste depois uma release **nova** e compatível com a versão atual. Não simule alteração na produção com ZIP de teste.
