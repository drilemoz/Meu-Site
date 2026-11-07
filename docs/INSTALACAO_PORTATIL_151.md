# Instalando o Meu Site 1.0.51 em qualquer hospedagem PHP + MySQL

## Objetivo e requisitos

O código **não depende da BoaHost, Squarespace ou outro registrador**. Exige PHP 8.1+ (recomendado PHP 8.3), `PDO`, `pdo_mysql`, `fileinfo`, `json`, sessões PHP, MySQL/MariaDB e escrita de `storage/` pelo processo PHP. A hospedagem deve permitir `.htaccess` / roteamento equivalente; se usar Nginx, configure o bloqueio de arquivos ocultos e a raiz do documento corretamente.

## Estrutura segura

Exemplo Plesk atual:

```text
Diretório do domínio/
  httpdocs/
    app/        (PRIVADO, fora do DocumentRoot)
    storage/    (PRIVADO, fora do DocumentRoot)
    bin/        (scripts CLI privados)
    public/     (DocumentRoot: httpdocs/public)
      index.php
      setup.php
      assets/
      uploads/
    VERSION
```

Para cPanel, também é possível usar `public_html/` como DocumentRoot e manter `app/`/`storage/` acima dele; nesse layout copie o **conteúdo** de `public/` para `public_html/` e ajuste as pastas privadas para o layout suportado pelo projeto. A raiz segura é obrigatória: não exponha todo o arquivo ZIP pela web. Em Termux, `bin/start-termux.sh` utiliza `public/` como pasta pública.

## Diagnóstico antes de instalar

1. Abra `https://SEU-DOMINIO/setup.php`. Verifique a versão PHP, extensões, sessões, DocumentRoot protegido e arquivos do portal.
2. A verificação **Configuração privada pode ser ativada** precisa estar verde. Ela realmente cria e renomeia arquivos de teste em `storage/` e limpa tudo. Não testa escrevendo senhas.
3. Corrija erros de permissão com dono correto da conta PHP, `open_basedir`, PHP-FPM ou suporte do provedor. **Nunca use `chmod 777`.**
4. Crie um banco e usuário exclusivo no painel Plesk/cPanel, com permissão CREATE/ALTER/INDEX para o esquema; o servidor e porta são os dados do provedor, não valores universais.
5. Informe host, porta, banco, usuário e senha. O assistente inventaria as **25 tabelas** sem modificar SQL e escolhe a rota segura.

## Os estados do banco

- **Vazio:** nova instalação ou importar backup.
- **Estrutura parcial vazia e reconhecida:** repetir criação idempotente de tabelas, sem duplicá-las.
- **25 tabelas conhecidas, esquema atual, 1 usuário e configurações:** modo **Recuperar instalação**; confirme com as credenciais do administrador existente; o sistema recria **somente** `storage/config.php`.
- **Estrutura incompleta com dados, esquema antigo ou tabelas desconhecidas:** bloqueado; preserve um backup e corrija em ambiente de teste, sem apagar manualmente.

## Por que o erro da BoaHost foi corrigido

Em 1.0.50, o instalador gravava usuários/configurações no banco e só então tentava criar `storage/config.php`. Uma falha de permissão nesse último momento deixava banco preenchido e sistema incompleto. A 1.0.51 testa escrita+renomeação na etapa 1 e prepara configuração privada antes de qualquer alteração SQL. Se a ativação ainda falhar (por exemplo, falta de espaço durante a instalação), a rota Recuperar evita perder dados. O código registra somente **fase**, **classe de exceção** e **código numérico** no log; nunca senha.

## Reiniciar totalmente o teste sem perda desnecessária

1. Exporte o banco atual no phpMyAdmin e verifique que o backup foi salvo fora do servidor. Copie também arquivos de mídia/uploads, se houver.
2. **Preferível:** crie um *novo banco vazio*, com usuário próprio, no painel. Aponte o instalador para esse novo banco; não altere o banco antigo até testar o novo.
3. Se desejar excluir definitivamente um banco de testes, faça isso somente manualmente no Plesk, após verificar o nome e validar o backup. O portal não executa exclusão automática.
4. Não compartilhe senhas nem publique `.env`, dumps `.sql`, `config.php` ou arquivos de log na pasta pública.

## Migração de hospedagem

1. Faça backup do banco pelo módulo do portal (JSON/JSON.GZ até 15 MiB no importador) e cópia separada de `public/uploads/` (mídias). Um dump SQL por phpMyAdmin também pode ser útil para restauração manual, mas não deve ser enviado ao importador JSON.
2. Prepare nova hospedagem PHP/MySQL; configure DocumentRoot e storage fora da raiz pública, DNS/HTTPS e versão PHP.
3. Instale em **banco vazio** pela opção de restauração de backup compatível; reenvie `uploads/` depois.
4. Não copie `storage/config.php` do provedor anterior: o novo assistente gera credenciais do novo banco.
5. Valide login, matérias, imagens, links, HTTPS e backups antes de mudar o DNS do domínio.

## Limitações e testes

- Backups JSON do portal e backups SQL pelo phpMyAdmin são formatos diferentes. A importação de JSON é limitada a 15 MiB compactados de acordo com as regras já existentes.
- DDL do MariaDB faz auto-commit: não existe rollback integral do esquema. Por isso a auditoria impede duplicações e bloqueia dados parciais inesperados.
- A confirmação da conexão MySQL não garante privilégios DDL: eles são exercitados durante a instalação. Erros técnicos permanecem nos logs sem segredos.
- PHPMailer/SMTP depende da biblioteca Composer, opcional ao funcionamento básico do portal. Certifique-se de instalar dependências no destino ou usar pacote vendor quando necessário.
- Validações locais não substituem teste real de MariaDB nem implantação na BoaHost.

## Testes locais do código

```sh
php -l public/setup.php
php tests/setup_recovery_151.php
php tests/setup_diagnostics.php
php tests/installer.php
php tests/smoke.php
```

Para validar MariaDB real utilize os testes de `tests/integration/` em banco isolado sob responsabilidade do operador (não de produção).
