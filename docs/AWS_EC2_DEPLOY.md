# VPS AWS EC2 Ubuntu 24.04 | Nginx | PHP 8.3 | MariaDB (v1.0.138)

## Arquitetura, limites e migração

O código-fonte permanece em `/var/www/projetos/iguatamaonline/`, com document root **somente** `public/`. A instalação de testes em `/var/www/iguatama-teste/` **não participa** deste procedimento e deve ficar intocada.

`www-data` **NÃO** altera `VERSION`, `app/`, `public/*.php` ou outros scripts. O painel autenticado registra um pedido simples em `storage/updater/vps/request.json`. Um serviço `systemd` executa PHP CLI como **`iguanews-deploy`** (sem root), consulta exclusivamente a última release de `drilemoz/Meu-Site`, valida ZIP, manifesto/versão/schema, prepara backups do código e MySQL, e substitui arquivos. O modo tradicional permanece disponível em hospedagens sem `IGUA_DEPLOY_VPS=1`.

O ZIP v2 tem SHA-256 para integridade, mas não obriga assinatura criptográfica. A confiança de origem do modo VPS depende de TLS/HTTPS, do repositório **fixo** no GitHub e da segurança da conta/release. Para exigir assinatura independente, providencie chave de assinatura e rejeição de ZIPs não assinados em uma evolução futura. Não configure URL ou repositório arbitrário no serviço.

O worker executa uma operação por vez. Se morrer entre a troca de arquivos, a requisição fica marcada como `running.json` e a próxima execução é bloqueada; não há repetição automática de operações ambíguas. A restauração automática do código é tentada em erros de aplicação; restauração manual é via SSH. O backup lógico MySQL anterior à implantação é preservado em `storage/backups/`; **a restauração do banco é separada** e não deve ser automática se o site recebeu novos dados. O esquema continua na revisão 37; versões com alteração de schema não são instaladas por este atualizador.

## 1. Preparação inicial, com acesso SSH e sudo

Antes de qualquer alteração, faça um snapshot EBS e cópia externa do banco e de `storage/`/uploads; registre o proprietário e modo atuais. **Não execute estes comandos em uma instalação ainda não testada.** Os comandos seguintes pressupõem que o projeto já está instalado e funcional na pasta acima e que o usuário `www-data` é o PHP-FPM e Nginx.

```bash
sudo apt-get update
sudo apt-get install -y nginx php8.3-cli php8.3-fpm php8.3-mysql php8.3-zip php8.3-curl php8.3-mbstring php8.3-gd php8.3-xml php8.3-intl mariadb-client
sudo adduser --system --group --home /nonexistent --no-create-home --disabled-login iguanews-deploy
sudo usermod -aG www-data iguanews-deploy
cd /var/www/projetos/iguatamaonline
# CONFERIR: não deve haver symlinks inesperados antes de alterar ownership.
find app public -type l -print
# www-data continua sem escrita no código; esta conta dedicada passará a ser dona.
sudo chown -R iguanews-deploy:iguanews-deploy app public bin VERSION
sudo find app public bin -type d -exec chmod 755 {} +
sudo find app public bin -type f -exec chmod 644 {} +
# Restaurar somente o diretório persistente de uploads para www-data.
sudo chown -R www-data:www-data public/uploads
sudo find public/uploads -type d -exec chmod 750 {} +
sudo find public/uploads -type f -exec chmod 640 {} +
# Garante que storage seja privado e gravável por PHP, com leitura dos itens
# explicitamente necessários pelo worker. NÃO publicar storage no Nginx.
sudo chown www-data:www-data storage
sudo chmod 750 storage
sudo chown www-data:www-data storage/config.php
sudo chmod 640 storage/config.php
# Permite ao worker somente a leitura do config.php, por grupo suplementar www-data.
# As pastas de backups e updater devem permitir criação por www-data e worker.
sudo install -d -o www-data -g www-data -m 2770 storage/backups storage/updater
sudo install -d -o iguanews-deploy -g www-data -m 3770 storage/updater/vps
sudo touch storage/updater/vps/.dispatch.lock
sudo chown iguanews-deploy:www-data storage/updater/vps/.dispatch.lock
sudo chmod 660 storage/updater/vps/.dispatch.lock
# Acesso ao storage pelos diretórios ancestrais: worker integra o grupo www-data.
```

Se o `storage/` e as pastas privadas existentes tiverem ACL, owners ou modos diferentes, **ajuste após inspeção**, sem `chmod -R 777`, sem alterar arquivos de outras aplicações, sem disponibilizar a configuração na web. Garanta que `storage/updater/` e `storage/backups/` sejam atravessáveis e graváveis por ambos (`www-data` e conta de deploy via grupo). Arquivos sensíveis de configurações devem continuar inacessíveis ao público.

**Verifique na prática:**

```bash
sudo -u www-data test ! -w /var/www/projetos/iguatamaonline/VERSION && echo 'www-data nao escreve VERSION'
sudo -u iguanews-deploy test -w /var/www/projetos/iguatamaonline/VERSION && echo 'deploy escreve VERSION'
sudo -u www-data test -w /var/www/projetos/iguatamaonline/storage/updater/vps && echo 'fila disponivel'
sudo -u iguanews-deploy test -r /var/www/projetos/iguatamaonline/storage/config.php
```

Se as verificações falharem, **não inicie o timer**. O código fonte precisa ser publicado **uma vez via SSH** antes de habilitar o modo VPS, pois o atualizador anterior não consegue substituir arquivos root-owned. Ao publicar, mantenha `storage/`, `public/uploads/` e `public/ads.txt` persistentes; não extraia ZIP-fonte diretamente sobre esses diretórios sem um procedimento de preservação.

## 2. Ativar PHP-FPM, worker e Nginx

Adicione estas variáveis na configuração do pool PHP-FPM que atende ao portal (por exemplo `/etc/php/8.3/fpm/pool.d/www.conf`):

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

**Nota:** `request_terminate_timeout` é parâmetro de pool, não diretiva `php_admin_value`. Se houver outros sites no mesmo pool, crie um pool PHP-FPM dedicado ao IguatamaOnline, com socket separado, em vez de mudar o comportamento de todos.

Instale as unidades fornecidas em `deploy/aws/` (revisando previamente os paths e nomes):

```bash
sudo install -m 644 deploy/aws/iguatama-deploy.service /etc/systemd/system/iguatama-deploy.service
sudo install -m 644 deploy/aws/iguatama-deploy.timer /etc/systemd/system/iguatama-deploy.timer
sudo systemctl daemon-reload
sudo systemctl enable --now iguatama-deploy.timer
sudo systemctl restart php8.3-fpm
sudo systemctl status iguatama-deploy.timer
sudo journalctl -u iguatama-deploy.service -n 60 --no-pager
```

O timer executa um PHP CLI curto quando não há fila, aproximadamente a cada 20 segundos; não mantém um daemon permanente, nem usa `sudo` no processo web. A conta de implantação precisa resolver e alcançar `api.github.com`/downloads oficiais pela rede. Não copie senhas MySQL para a unidade systemd: o backup lê `storage/config.php` com permissões restritas.

### IndexNow sem escrever em `public/`

Dentro do bloco `server` HTTPS do Nginx que atende `iguatama.online`, insira **antes da regra genérica `location ~ \.php$`** o conteúdo de `deploy/aws/nginx-indexnow.conf`. Essa rota só serve um nome hexadecimal de 32 caracteres e só responde a uma chave que confere com a configuração privada de IndexNow. Ela executa `public/indexnow-key.php`, sem gerar arquivo de texto em `public/`.

**Não substitua o server block inteiro**: mantenha TLS, `root`, rotas, redirecionamentos e as proteções já existentes. Em seguida:

```bash
sudo nginx -t && sudo systemctl reload nginx
```

Depois de configurar o IndexNow pelo painel, teste a URL gerada com HTTPS real e confirme conteúdo igual à chave e HTTP 200; um nome inválido deve retornar HTTP 404. Não suponha que o PHP detecte sozinho se o `location` Nginx foi aplicado corretamente.

## 3. Testes de recuperação e deploy

1. Confirme acesso ao painel e à página **Atualizações → Vistoria**, que deve informar **Serviço de implantação separado**, fila privada e **Escrita do código: somente pela conta dedicada**. A falta de escrita de `www-data` em `VERSION`, `app/updater.php` e `public/admin.php` não é falha nesse modo.
2. Selecione **Visualizar → Atualizar agora**. CSRF, senha de administrador e confirmação continuam exigidos. O pedido é registrado; o status chega por polling. Não reenvie se ficar incerto.
3. O worker instala somente releases posteriores e compatíveis com schema 37. Antes de tocar no código, mantém backup lógico MySQL e cópia de arquivos afetados. Revise `journalctl` em falhas.
4. Para recuperar código manualmente, desligue o timer, confira o journal e use somente após decidir restaurar:

```bash
sudo systemctl stop iguatama-deploy.timer
cd /var/www/projetos/iguatamaonline
sudo -u iguanews-deploy env IGUA_DEPLOY_VPS=1 /usr/bin/php8.3 bin/aws-deploy-worker.php --rollback
sudo systemctl start iguatama-deploy.timer
```

Se `running.json` permanecer após interrupção, **não apague automaticamente**. Investigue e faça recuperação assistida; confirme versão, código e backup antes de liberar outra solicitação. O rollback de código não reverte artigos novos, uploads, nem mudanças feitas no MySQL.

## 4. Limites, compatibilidade e segurança

- PHP-FPM com `memory_limit=256M` e `max_execution_time=60` atende ao painel; tarefas de instalação potencialmente demoradas são executadas pelo CLI (512M, sem limite PHP, mas `TimeoutStartSec=360`). Não aumente os limites do FPM para 5 ou 10 minutos para contornar implantação de código.
- Os limites de ZIP do atualizador continuam 32 MiB compactados, 80 MiB extraídos e 800 entradas; backup lógico máximo 40 MiB descompactado. Antes de crescer o portal, avalie esses limites com medições reais.
- Os backups ficam em `storage/backups/`, não são publicados. Mantenha também cópias externas, snapshot EBS e testes regulares de recuperação, especialmente se o banco não for transacional ou o host falhar durante o backup.
- Em hospedagens compartilhadas, **não defina** `IGUA_DEPLOY_VPS`; o modo de atualização direta e o IndexNow físico continuam existentes, sujeitos às permissões do provedor.
- O worker não roda como root, não recebe comandos, URL, shell ou caminhos do navegador. Na VPS, o upload manual de ZIP e a restauração de código pela página ficam desabilitados por segurança; a recuperação manual exige SSH.
- O usuário `www-data` não deve ter acesso de escrita em código, `.github/`, `bin/` ou `VERSION`.
- A conta de deploy possui permissão de alterar código e ler a configuração do banco para gerar backup. É uma conta privilegiada **no escopo do aplicativo**, mesmo sem ser root. Proteja o acesso SSH/arquivos, o repositório de releases e o serviço systemd.


**Atenção sobre pacote:** use o **ZIP de código-fonte completo** para obter `deploy/aws/` e arquivos do workflow; o ZIP de atualização web não inclui `deploy/`, `.github/` ou documentação de infraestrutura. Antes de executar comandos que alteram ownership em produção, faça a implantação inicial numa cópia da VPS e simule um deploy e um rollback.
