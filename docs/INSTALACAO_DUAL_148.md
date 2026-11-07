# Instalação híbrida Termux e hospedagem — v1.0.48

Este complemento atualiza o guia completo `docs/INSTALACAO_DUAL_147.md`. As instruções de estrutura das pastas, conexão MariaDB, TLS, backups e migração permanecem válidas.

## Verificação antes da instalação

No Termux ou via SSH na hospedagem, na raiz privada do projeto:

```sh
php bin/check-environment.php
php bin/check-environment.php --json
```

A ferramenta verifica PHP, extensões, escrita e presença dos arquivos de `public/` ou da pasta pública irmã configurada. Retorna código 0 quando os requisitos checáveis em CLI estão OK e código 2 quando há algum problema; **não** testa sessões HTTP, DocumentRoot real nem HTTPS. Não pede senha nem conecta no MySQL.

## Publicação em hospedagem sem controle do DocumentRoot

Se usar `public_html/`, copie **o conteúdo completo** de `public/` para essa pasta, incluindo PHP, `.htaccess`, `assets/` e `uploads/.htaccess`. Posicione `app/`, `storage/` e as demais pastas privadas fora dela, como irmãs da pasta pública. A etapa Requisitos agora sinaliza arquivos públicos essenciais ausentes, e a Central de Saúde revalida o conjunto após a instalação. Quando executar cron com mais de uma pasta pública presente, configure `IGUANEWS_PUBLIC_DIR=public_html`. Symlinks para caminhos fora do projeto são recusados no modo CLI.

## Envio de backup para restauração

Um backup de até **15 MiB** exige tanto `upload_max_filesize >= 15M` quanto `post_max_size >= 16M` como mínimo recomendado (o formulário HTTP tem overhead). A hospedagem pode impor um limite adicional no servidor web. No Termux use `sh bin/start-termux.sh`, que ajusta os dois valores para a sessão local. Se `post_max_size` for excedido, a etapa de conteúdo explica a falha sem simular um erro de CSRF.

## Segurança e limites da detecção

Em hospedagem, use HTTPS antes de informar senhas de banco e do administrador. O site não infere TLS a partir de cabeçalhos HTTP enviados por clientes. Proxies reversos com TLS precisam ser configurados corretamente pelo provedor para que o PHP reporte a conexão segura. O sistema não instala extensões PHP ou inicia o banco automaticamente em serviços da hospedagem.

## Testes

- `php tests/runtime_147.php` e `php tests/runtime_148.php` — layouts e diagnóstico
- `php tests/setup_diagnostics.php` — mensagens de conexão MySQL
- `php bin/check-environment.php --json` — preflight local, sem credenciais
- Regressão completa PHP e JavaScript + teste HTTP do wizard nos dois layouts

A criação de banco e a restauração com MariaDB real devem ser verificadas separadamente antes de implantação definitiva.
