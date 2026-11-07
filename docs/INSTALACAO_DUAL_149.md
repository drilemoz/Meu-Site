# Importação de backups de até 15 MiB — Termux e hospedagem (v1.0.49)

O instalador admite arquivos **.json** e **.json.gz** até 15 MiB (15 × 1.048.576 bytes). A configuração do **PHP que atende HTTP**, e não a do navegador, define se o arquivo chegará ao programa. Os limites mínimos são `upload_max_filesize >= 15M` e `post_max_size >= 16M` (recomendado **20M/24M**). O proxy e o servidor web podem impor limites adicionais.

## Termux

1. Pare o `php -S` anterior com **Ctrl+C**. Uma nova configuração não afeta o processo já em execução.
2. Na raiz privada do projeto rode `sh bin/start-termux.sh` (ou `sh bin/start-termux.sh 8080`). O comando utiliza `20M/24M`, com bind somente em `127.0.0.1`.
3. Abra **a porta exibida pelo comando**, `http://127.0.0.1:8000/setup.php`, e volte à etapa Requisitos. Não execute um segundo `php -S` sem as opções `-d`.

`public/.user.ini` **não é usado** pelo servidor embutido do PHP. `php -i` sem as flags verifica outro contexto (PHP CLI), não confirma a configuração HTTP.

## Hospedagem online

- **PHP-FPM/CGI:** publique `public/.user.ini` junto dos arquivos públicos. Em `public_html` fixo, copie também os arquivos iniciados por ponto. O exemplo já contém `upload_max_filesize=20M` e `post_max_size=24M` e pode levar alguns minutos até recarregar (`user_ini.cache_ttl`). O provedor pode impedir a substituição dessas diretivas. Confirme os limites reais na página de Requisitos do instalador.
- **Apache mod_php:** `.user.ini` é ignorado. Configure os limites no painel/VirtualHost do provedor, ou peça suporte. Não instale diretivas `php_value` indiscriminadamente em `.htaccess`, pois podem causar HTTP 500 sob PHP-FPM.
- **Nginx:** adicione uma regra no servidor para impedir acesso HTTP a arquivos ocultos, inclusive `.user.ini`; o `.htaccess` não tem efeito no Nginx. Configure `client_max_body_size` adequadamente, se aplicável.
- **Limite do provedor:** se o painel/provedor fixa 2M/8M, a aplicação não consegue elevá-los em tempo de execução; solicite alteração ou envie um backup menor/compactado.

## O que muda no instalador

- A etapa Requisitos mostra o **limite real lido pelo PHP HTTP** e a capacidade conservadora de envio com 1 MiB reservado ao formulário; continua sendo **opcional** para instalações novas.
- Na terceira etapa, o navegador avisa antes de enviar arquivo muito grande; a validação do lado do servidor continua sendo obrigatória. Se o PHP recusar ou interromper o upload, a mensagem orienta a correção.
- Se `post_max_size` for ultrapassado, o PHP pode descartar `$_POST` e `$_FILES`; a página explica isso antes de acusar erro de CSRF.
- A importação de mídias ZIP do painel administrativo tem seus próprios limites de PHP e hospedagem e **não** é aumentada automaticamente por esta alteração.

## Segurança e migração

Mantenha `app/` e `storage/` fora da raiz web, não exponha o PHP embutido à internet e evite publicar arquivos de backup e `config.php`. A nova configuração não requer migração SQL: esquema 36, backup lógico v11 e pacote ZIP v2 são mantidos.
