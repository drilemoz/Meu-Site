> **Atualização 1.0.142:** o código-fonte completo agora inclui `deploy/aws/install.sh`, que automatiza os preparativos detalhados abaixo. Consulte primeiro [Instalador VPS 1.0.142](INSTALADOR_VPS_142.md). As instruções manuais a seguir permanecem para auditoria e casos em que o instalador recuse a configuração por segurança. O script não migra dados ou instala pacotes ausentes.

# IguatamaOnline 1.0.139 — Implantação AWS EC2 Ubuntu 24.04

**Ambiente-alvo:** `/var/www/projetos/iguatamaonline/`, Nginx (`root .../public`), PHP-FPM 8.3 como `www-data`, MariaDB e PHP CLI 8.3 com worker systemd `iguanews-deploy`. **Não alterar** `/var/www/iguatama-teste/`. O procedimento não contém migração de banco.

## Princípio de segurança e limites

A interface de Atualizações continua no painel e o processo web **não** modifica arquivos PHP/VERSION. O painel grava somente `request.json` na fila privada compartilhada, protegida por diretório sticky (`3770`); o worker não-root executa download fixado em `drilemoz/Meu-Site`, valida manifesto/hashes, cria backup MySQL e snapshots de código e aplica arquivos. O **diretório raiz do projeto** também deve pertencer à conta de deploy (modo `0755`): `rename()` sobre `VERSION` exige permissão de escrita no **diretório pai**, não apenas no arquivo. `www-data` não pode escrever nesse diretório.

Controles exclusivos `storage/updater/vps/private/` pertencem ao worker (diretório `2750`): `.code.lock` e `maintenance`. O PHP-FPM só pode consultar o marcador, não apagá-lo nem modificá-lo. `storage/updater/jobs/` contém snapshots e journals controlados pelo worker; o painel consulta apenas metadados necessários. A fila `storage/updater/vps/` é a única superfície compartilhada de pedidos e estados, e **não aceita comandos, caminhos ou URLs do navegador**.

A autenticação da origem da release depende de GitHub/TLS e controle da conta GitHub. ZIP v2 sem assinatura tem integridade SHA-256 interna, mas **não assinatura de autoria**. Não anuncie isso como código assinado. Exigir assinaturas Ed25519 das futuras releases requer distribuição de releases assinadas e ativação obrigatória planejada, não é automática nesta versão.

## 1. Pré-condições e cópias de segurança

1. Pare quaisquer atualizações em andamento e **não habilite o timer** até completar a vistoria.
2. Faça snapshot EBS, exportação externa do banco, de `storage/` e `public/uploads/`, e garanta procedimento de recuperação via SSH.
3. Confirme se já existem `storage/.update-maintenance`, `storage/updater/vps/running.json`, `storage/updater/vps/request.json` e jobs antigos. Se houver atualização pendente, investigue e recupere ANTES de mudar owners. **Não apague estados/journals automaticamente.**
4. Confira os caminhos e os usuários reais antes de executar qualquer `chown`:

```bash
SITE=/var/www/projetos/iguatamaonline
sudo stat -c '%U:%G %a %n' "$SITE" "$SITE/VERSION" "$SITE/app" "$SITE/public" "$SITE/storage"
sudo find "$SITE/app" "$SITE/public" -type l -print
sudo ls -la "$SITE/storage/updater" "$SITE/storage/updater/vps" 2>/dev/null || true
```

O procedimento pressupõe que `www-data` é o usuário real do pool PHP-FPM. Se a configuração do pool utilizar outra conta/grupo, adapte nomes e comandos; não os execute literalmente.

## 2. Instalação inicial do código via SSH

A primeira implantação da 1.0.139 precisa ser executada via SSH/sudo porque a versão anterior pode ser de propriedade do `root`. Publique o **ZIP de fonte completo** numa pasta temporária privada, confira o conteúdo e atualize o código preservando integralmente `storage/`, `public/uploads/` e `public/ads.txt`. Não use o ZIP de atualização para instalar unidades systemd: ele não contém `deploy/aws/` nem `bin/`.

Exemplo a partir de um ZIP previamente transferido via SSH e verificado, **somente depois de fazer snapshot EBS e backup externo**, e depois de confirmar que não há atualização em andamento:

```bash
SITE=/var/www/projetos/iguatamaonline
STAGE=$(mktemp -d /tmp/igua139-stage-XXXXXXXX)
unzip -q /caminho/seguro/Meu-Site-v1.0.139-fonte-completo.zip -d "$STAGE"
test "$(cat "$STAGE/VERSION")" = '1.0.139' || exit 1
# Sem --delete, sem alterar armazenamento privado e sem publicar configurações antigas.
sudo rsync -a --no-owner --no-group --exclude '/storage/***' \
  --exclude '/public/uploads/***' --exclude '/public/ads.txt' \
  --exclude '/public/robots.txt' "$STAGE/" "$SITE/"
# Confira antes de apagar a área temporária. Atribua os owners conforme abaixo.
```

```bash
sudo apt-get update
sudo apt-get install -y nginx php8.3-cli php8.3-fpm php8.3-mysql php8.3-zip php8.3-curl php8.3-mbstring php8.3-gd php8.3-xml php8.3-intl mariadb-client
sudo adduser --system --group --home /nonexistent --no-create-home --disabled-login iguanews-deploy
sudo usermod -aG www-data iguanews-deploy
SITE=/var/www/projetos/iguatamaonline
cd "$SITE"

# Raiz obrigatoriamente gravável PELA CONTA DE DEPLOY, nunca por www-data.
sudo chown iguanews-deploy:iguanews-deploy "$SITE"
sudo chmod 0755 "$SITE"
sudo chown -R iguanews-deploy:iguanews-deploy app public bin
sudo find app public bin -type d -exec chmod 0755 {} +
sudo find app public bin -type f -exec chmod 0644 {} +
sudo chown iguanews-deploy:iguanews-deploy VERSION
sudo chmod 0644 VERSION

# Arquivos persistentes: navegador/site continuam com acesso a uploads.
sudo chown -R www-data:www-data public/uploads
sudo find public/uploads -type d -exec chmod 0750 {} +
sudo find public/uploads -type f -exec chmod 0640 {} +

# storage/ permanece exclusivo do PHP; o worker entra pelo grupo suplementar.
sudo chown www-data:www-data storage
sudo chmod 0750 storage
sudo chown www-data:www-data storage/config.php
sudo chmod 0640 storage/config.php

# Fila e backups compartilhados por grupo, com sticky bit nos pais.
sudo install -d -o www-data -g www-data -m 2770 storage/backups
sudo install -d -o iguanews-deploy -g www-data -m 3770 storage/updater
sudo install -d -o iguanews-deploy -g www-data -m 3770 storage/updater/vps
# Diretórios do trabalhador: grupo somente lê/travessa (não escreve).
sudo install -d -o iguanews-deploy -g www-data -m 2750 storage/updater/jobs
sudo install -d -o iguanews-deploy -g www-data -m 2750 storage/updater/vps/private
sudo touch storage/updater/vps/.dispatch.lock
sudo chown iguanews-deploy:www-data storage/updater/vps/.dispatch.lock
sudo chmod 0660 storage/updater/vps/.dispatch.lock
sudo touch storage/updater/vps/private/.code.lock
sudo chown iguanews-deploy:www-data storage/updater/vps/private/.code.lock
sudo chmod 0600 storage/updater/vps/private/.code.lock
```

**Migrar permissões antigas sem apagar backups:** após examinar jobs e confirmar que não há atualização em execução, ajuste a propriedade dos registros já existentes para a conta do worker. Preserve os snapshots; não altere suas permissões para tornar dados privados públicos. Quando houver `last.json` antigo, o worker precisa conseguir ler o apontador:

```bash
SITE=/var/www/projetos/iguatamaonline
cd "$SITE"
# Somente após INSPECIONAR os jobs antigos e confirmar ausência de atualização ativa:
sudo chown -R iguanews-deploy:www-data storage/updater/jobs
sudo find storage/updater/jobs -type d -exec chmod 2750 {} +
if [ -f storage/updater/last.json ]; then
  sudo chown iguanews-deploy:www-data storage/updater/last.json
  sudo chmod 0640 storage/updater/last.json
fi
# O .lock LEGADO em storage/updater/.lock não é usado pelo worker novo;
# não precisa apagá-lo. O worker usa vps/private/.code.lock.
```

**Nunca** execute `chmod 777`, `chown -R www-data` no código nem `sudo` dentro do PHP-FPM. Serviços existentes podem manter arquivos específicos de domínio/SEO em `public/` que precisam ser avaliados separadamente. O IndexNow não exige escrita direta na raiz pública.

## 3. Verificações obrigatórias antes de ativar o serviço

```bash
SITE=/var/www/projetos/iguatamaonline
sudo -u www-data test ! -w "$SITE" && echo 'OK: web sem escrita na raiz'
sudo -u www-data test ! -w "$SITE/VERSION" && echo 'OK: web sem escrita em VERSION'
sudo -u www-data test ! -w "$SITE/app" && echo 'OK: web sem escrita em app'
sudo -u www-data test ! -w "$SITE/public" && echo 'OK: web sem escrita em public'
sudo -u www-data test ! -w "$SITE/storage/updater/vps/private" && echo 'OK: web sem escrita no controle'
sudo -u www-data test -w "$SITE/storage/updater/vps" && echo 'OK: web grava pedidos na fila'
sudo -u iguanews-deploy test -r "$SITE/storage/config.php" && echo 'OK: deploy lê configuração privada'
# Comprova CRIAÇÃO + RENAME + EXCLUSÃO em root, app, public, jobs, backups, controle.
sudo -u iguanews-deploy env IGUA_DEPLOY_VPS=1 /usr/bin/php8.3 "$SITE/bin/aws-deploy-check.php"
```

**Se qualquer linha reprovar, não habilite o timer.** A vistoria do painel apenas lê o último resultado publicado pelo worker e **não pode demonstrar permissões Unix sozinha**. O comando CLI acima testa operações reais sobre arquivos descartáveis, sem renomear o `VERSION` real.

O diretório privado do worker precisa ter dono `iguanews-deploy`, modo `2750` (sem escrita pelo grupo). A fila compartilhada deve ter dono `iguanews-deploy` e modo `3770` com sticky bit; o sistema não permite que o `www-data` remova o status/lock criados pelo worker. Não considere o `www-data` uma conta confiável para proteção dos backups do MySQL: mantenha cópias externas com acesso independente.

## 4. PHP-FPM, systemd e Nginx

No pool específico de PHP-FPM 8.3 do portal (não no pool global de outros sites), configure:

```ini
env[IGUA_DEPLOY_VPS] = 1
env[IGUA_INDEXNOW_VIRTUAL] = 1
php_admin_value[display_errors] = Off
php_admin_value[log_errors] = On
php_admin_value[memory_limit] = 256M
php_admin_value[max_execution_time] = 60
php_admin_value[upload_max_filesize] = 24M
php_admin_value[post_max_size] = 32M
php_admin_value[opcache.validate_timestamps] = 1
php_admin_value[opcache.revalidate_freq] = 0
request_terminate_timeout = 90s
```

O worker usa CLI 8.3 com `memory_limit=512M`, `max_execution_time=0`, mas o `systemd` limita o tempo de cada execução a 360 s. O PHP-FPM não deve executar deploy demorado. Ajuste recursos apenas após medição.

```bash
SITE=/var/www/projetos/iguatamaonline
sudo install -m 0644 "$SITE/deploy/aws/iguatama-deploy.service" /etc/systemd/system/iguatama-deploy.service
sudo install -m 0644 "$SITE/deploy/aws/iguatama-deploy.timer" /etc/systemd/system/iguatama-deploy.timer
sudo systemd-analyze verify /etc/systemd/system/iguatama-deploy.service /etc/systemd/system/iguatama-deploy.timer
sudo systemctl daemon-reload
sudo systemctl restart php8.3-fpm
# Execute o serviço uma vez, sem timer, e CONFIRA O STATUS NO PAINEL:
sudo systemctl start iguatama-deploy.service
sudo journalctl -u iguatama-deploy.service -n 80 --no-pager
# Somente após a vistoria verde e revisão de serviços:
sudo systemctl enable --now iguatama-deploy.timer
```

### IndexNow em Nginx

Inclua `deploy/aws/nginx-indexnow.conf` no `server` HTTPS, **antes** do regex geral `location ~ \\.php$`. O snippet mostra `fastcgi_pass unix:/run/php/php8.3-fpm.sock` como **exemplo padrão**. Se o site usa pool dedicado, troque-o pelo socket `listen` real desse pool; não copie cegamente. Verifique com:

```bash
sudo grep -H -E '^\s*listen\s*=' /etc/php/8.3/fpm/pool.d/*.conf
sudo nginx -T | grep -E 'fastcgi_pass|iguatamaonline/public' | head -n 40
sudo nginx -t && sudo systemctl reload nginx
```

Confira a URL HTTPS `https://seudominio/<chave-indexnow>.txt` a partir de uma rede externa e garanta HTTP 200 com o texto exato da chave; chave diferente deve retornar 404. O serviço virtual de IndexNow mantém os arquivos da chave fora da raiz `public/`. O teste no painel não substitui a checagem no Nginx real.

## 5. Atualização, falhas e recuperação

1. Gere backup externo e snapshot EBS antes da primeira atualização real; confirme que MariaDB está acessível.
2. No painel, abra **Atualizações → Vistoria** e confira o heartbeat do worker e provas de criação/renomeação atômica. Em seguida use **Visualizar → Atualizar agora** para release posterior compatível com schema 37.
3. O worker consulta release oficial, valida limites e hashes, realiza backup lógico do banco e do código e atualiza arquivos. Os dados persistentes e imagens não são substituídos.
4. Em falha detectada durante aplicação, tenta rollback do código. Se `maintenance` ou `running.json` permanecerem, **não reenviar**: o processo pode ter sido interrompido após alterar arquivos.
5. Para recuperação assistida por SSH, depois de examinar os journals e snapshots e desativar o timer:

```bash
sudo systemctl stop iguatama-deploy.timer
SITE=/var/www/projetos/iguatamaonline
cd "$SITE"
sudo -u iguanews-deploy env IGUA_DEPLOY_VPS=1 /usr/bin/php8.3 bin/aws-deploy-worker.php --rollback
# Inspecione VERSION, arquivos restaurados, integridade, backup e autenticação.
# Se running.json permanecer, apure sua origem; não o remova automaticamente.
# Só reative o timer depois de concluir a investigação.
```

Um marcador LEGADO `storage/.update-maintenance` de versões anteriores também bloqueia novas atualizações. Não migre nem exclua esse marcador sem verificar o último journal, pois pode significar falha real. O rollback de código **não** restaura automaticamente publicações, uploads nem mudanças no banco ocorridas após o backup.

## 6. Compatibilidade e limitações

- Sem `IGUA_DEPLOY_VPS=1`, o atualizador direto da hospedagem compartilhada continua operando conforme permissões do provedor; não exige conta dedicada nem nova estrutura privada.
- O ZIP de atualização normal inclui `app/`, `public/` e `VERSION`, mas não inclui `deploy/`, `bin/`, `storage/` nem unidades systemd. A instalação inicial e futuras mudanças de infraestrutura exigem publicar o ZIP-fonte completo via SSH.
- O diretório do projeto pertencente à conta `iguanews-deploy` **não significa que o site web seja executado como esse usuário**. PHP-FPM permanece `www-data` sem escrita no código.
- O pacote de atualização v2 atual pode não ter assinatura digital. Para autenticidade independente do GitHub/TLS, ainda será necessário distribuir releases assinadas e exigir verificação. Não confunda hash interno com assinatura.
- Testes locais não substituem EC2: conferir permissões Unix, socket real, serviço systemd, MariaDB, deploy completo e rollback numa cópia isolada antes de liberar produção.
