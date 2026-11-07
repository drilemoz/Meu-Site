# Instalação híbrida e importação automática de backup — v1.0.50

A mesma instalação PHP/MySQL/MariaDB funciona em Termux ou hospedagem, com `public/` ou arquivos públicos em `public_html/`. O instalador identifica os limites **do processo PHP que responde à requisição**, sem confiar em informações do navegador ou em comandos executados separadamente.

## Importação lógica de até 15 MiB sem alterar PHP

- O usuário seleciona normalmente um arquivo `.json` ou `.json.gz` na etapa **3 — Importar portal anterior**.
- Se os limites HTTP permitirem, o instalador envia o arquivo em uma requisição como antes.
- Se `upload_max_filesize=2M` e `post_max_size=8M`, ou outros limites inferiores a 15 MiB, o JavaScript envia automaticamente partes de até **512 KiB** (ou menores, de acordo com os limites). Exemplo: 15 MiB = 30 partes.
- O PHP verifica **sessão, CSRF, tamanho declarado, nome permitido, ordem dos blocos, arquivo recebido via upload HTTP e tamanho de cada parte**. Os blocos ficam apenas em `storage/setup-imports/` (diretório privado, permissões 0700, arquivos 0600); o banco não é modificado durante o envio.
- Depois de concluir o upload, o formulário de instalação é enviado com o identificador privado vinculado à mesma sessão. O PHP valida o backup completo **antes** da criação das tabelas. Após a instalação bem-sucedida, o arquivo temporário da sessão é removido; arquivos abandonados há mais de 1h são limpos quando outro envio é iniciado.

**O PHP do provedor continua em 2M/8M se foi configurado assim.** Não existe mudança mágica no `php.ini`, no Apache, no Nginx ou no processo PHP já iniciado: apenas o tamanho das requisições é reduzido.

### Requisitos do fallback

JavaScript e cookies de sessão habilitados no navegador, `file_uploads=On`, `upload_max_filesize` e `post_max_size` de pelo menos 256 KiB, permissão de escrita em `storage/` e espaço livre para o arquivo temporário. Alguns WAFs, proxies ou hospedagens podem bloquear requisições mesmo pequenas; esses limites não podem ser burlados pelo site. Se JavaScript estiver desativado, o envio tradicional respeita os limites do PHP.

## Termux

Inicie normalmente o banco MariaDB e o site. `sh bin/start-termux.sh` continua iniciando o PHP local em `127.0.0.1` e configurando opcionalmente 20M/24M; porém a importação automática em partes já funciona se o PHP HTTP estiver em 2M/8M. **Não é necessário reiniciar um servidor apenas para elevar limites de upload**, desde que o fallback segmentado esteja disponível.

## Hospedagem PHP

Mantenha `app/`, `storage/` e os outros arquivos privados fora da raiz web. A pasta `public/` deve ser o DocumentRoot, ou os arquivos públicos devem estar em `public_html/` com os diretórios privados ao lado. O `.user.ini` incluído pode otimizar o envio direto em PHP-FPM quando permitido, mas **não é obrigatório para o fallback**. Configuração de banco de dados, HTTPS, extensões e permissões de armazenamento continuam dependentes do provedor.

## O que não mudou

- Limite lógico **15 MiB** para arquivo de backup, **40 MiB** descompactados e estrutura/versões de backup validadas. Não aceita dump SQL arbitrário.
- Banco na revisão 36, backup lógico v11, mídia ZIP v2, três etapas e proteção contra sobrescrever um banco com dados.
- Fotografias estão fora do backup lógico e devem ser transferidas separadamente; este fallback **não** altera a importação de pacotes ZIP de mídias no painel administrativo.

## Verificações

- `php tests/chunked_upload_150.php` — validação de limites, sessão e estrutura.
- `node tests/setup_backup_upload_150.js` — simulação do cliente em 6 partes e confirmação do formulário.
- `python3 tools/test_chunked_upload_150.py --full` — teste HTTP real com PHP 2M/8M, remonta 15 MiB e compara SHA-256, sem necessidade de MariaDB.
- Testar novamente no provedor e no Termux com backup real, banco vazio, sessão persistente e restauração antes de substituir dados em produção.
