# Instalação em Termux e hospedagem PHP — 1.0.47

## O que é detectado automaticamente

O assistente `/setup.php` (etapas **Requisitos → Banco → Conteúdo**) identifica a **execução do PHP**, e não o navegador: Termux por caminhos privativos `/data/data/com.termux/files/`, hospedagem (Apache/PHP-FPM/CGI) ou servidor local PHP. A tela verifica PHP >= 8.1, sessões, PDO MySQL, Fileinfo, JSON, permissões de escrita, isolamento da raiz HTTP, exposição do servidor PHP embutido e, quando pertinente, sinaliza HTTPS ausente. Erros obrigatórios impedem a instalação. `PHP_SAPI` e `DocumentRoot` são obtidos no servidor, sem confiar em `HTTP_HOST`/User-Agent.

A aplicação usa URLs `/...`, por isso **a pasta pública deve ser a raiz do domínio/subdomínio**, não `/meu-site/public/` dentro de outra URL. O perfil não fica gravado em banco: ao migrar entre ambientes, o diagnóstico se adapta à execução atual. PHP, MariaDB, TLS, envio de e-mail e permissões devem ser provisionados pelo operador/provedor.

## Opção A: Termux Android

1. No Termux, instale `pkg install php mariadb`; não execute o projeto em `/sdcard`. Descompacte em uma pasta privada como `$HOME/Meu Site`.
2. Inicialize o banco somente se necessário e **inicie MariaDB separadamente**, de acordo com a versão instalada do Termux. Por exemplo, confirme `mariadb-admin --protocol=tcp -h 127.0.0.1 -P 3306 ping` quando estiver configurado para TCP.
3. Na raiz do projeto execute:

```sh
sh bin/start-termux.sh
```

Esse comando usa `127.0.0.1:8000`, cria somente diretórios internos e aplica `upload_max_filesize=20M`/`post_max_size=24M` àquela execução. Não publica o servidor na rede. É possível fornecer outra porta: `sh bin/start-termux.sh 8080`.
4. No mesmo aparelho acesse `http://127.0.0.1:8000/setup.php`, confira o diagnóstico e escolha o banco local automático (quando o administrador MariaDB permitir) ou a conexão manual. O assistente **não inicia MariaDB nem salva a senha administrativa SQL**.

## Opção B: hospedagem com alteração de DocumentRoot

Deixe o projeto em uma pasta privada, por exemplo `/home/usuario/meu-site/`. Aponte **somente** o domínio ou subdomínio para `/home/usuario/meu-site/public/`.

```text
/home/usuario/meu-site/
├── app/                 privado
├── bin/                 privado
├── storage/             privado, com permissão de escrita do PHP
├── public/              DOCUMENTROOT (única pasta exposta)
│   ├── index.php
│   ├── setup.php
│   ├── admin.php
│   ├── .htaccess
│   ├── assets/
│   └── uploads/
└── ...
```

## Opção C: hospedagem cPanel com `public_html` fixo

Quando não é possível alterar a raiz web, copie **somente o conteúdo de `public/`** (inclusive `.htaccess` e `assets/`) para `public_html/`. Instale `app/`, `storage/`, `bin/` e demais arquivos privados no diretório **pai** de `public_html/`, nunca dentro dele:

```text
/home/usuario/
├── app/                  privado
├── bin/                  privado
├── storage/              privado
├── public_html/          DOCUMENTROOT
│   ├── index.php
│   ├── setup.php
│   ├── admin.php
│   ├── .htaccess
│   ├── assets/
│   └── uploads/
├── docs/                 privado
└── ...
```

Também são aceitos os nomes públicos `www/` e `htdocs/`, quando realmente são a raiz HTTP. O PHP detecta o diretório pelo caminho verdadeiro de `SCRIPT_FILENAME`/`DOCUMENT_ROOT`; não usa cabeçalhos fornecidos pelo visitante. O caminho do banco e de backups permanece relativo a `app/` e `storage/`. Para cron/CLI, quando as duas pastas `public/` e `public_html/` existirem ao mesmo tempo, configure `IGUANEWS_PUBLIC_DIR=public_html` no ambiente do comando (somente nomes `public`, `public_html`, `www`, `htdocs` são aceitos); se houver apenas uma delas, a CLI a detecta automaticamente. Se o provedor não permitir arquivos privados fora da pasta web, **não instale nesse plano**: nenhum `.htaccess` garante a segurança em todos os servidores.

## Banco na hospedagem

Crie antes, pelo painel, um **banco vazio** e um usuário específico com as permissões necessárias a instalação/migrações, e escolha **Usar banco existente**. O instalador adapta os campos para o modo online: host sugerido `localhost` (confirme o host real do provedor), porta 3306, banco/usuário **sem valores presumidos** (muitos provedores usam prefixos). A senha não é exibida após o envio e a conexão só é salva ao concluir. Não informe credenciais administrativas globais.

Use certificado TLS/HTTPS, desative exibição de erros, mantenha arquivos privados graváveis para o usuário PHP sem `chmod 777`, confirme `pdo_mysql`, `fileinfo`, `session`, limites de upload, disponibilidade do banco e espaço para backups. O site apresenta os avisos, mas **não pode ativar PHP extensions, SSL nem serviços do provedor automaticamente**.

## Atualização e migração de dados

- Para atualizar no mesmo ambiente, preserve `storage/config.php`, `storage/`, `public/uploads/` (ou `public_html/uploads/`) e o banco; substitua apenas código, mantendo os direitos de escrita do usuário PHP.
- Para migrar de Termux para online (ou vice-versa), faça backup lógico ou pacote completo com mídias, restaure primeiro em uma base vazia de teste e confirme fotos/anexos antes de apontar o domínio.
- Após mudar de domínio, revise endereço HTTPS nas configurações de SEO, SMTP e rotas amigáveis. `seo_pretty_urls` só deve ser ativado com `mod_rewrite` no Apache ou com `public/router.php` no servidor PHP embutido.
- O servidor embutido `php -S` **não é adequado para hospedagem pública**; Termux deve permanecer restrito a loopback.

## Validação

`php tests/runtime_147.php` testa detecção Termux/hospedagem sem confiar no navegador, bloqueio de acesso privilegiado, `public_html` com arquivos privados fora da raiz HTTP e falhas de DocumentRoot. `php tests/smoke.php` e a suíte completa previnem regressões de PHP. Também testar HTTP com `-t public` (válido) e `-t .` (deve bloquear a instalação). A instalação completa com MariaDB e a navegação real em Android e na hospedagem precisam de testes no ambiente de destino; testes isolados não garantem compatibilidade com todos os provedores.
