# Meu Site 1.0.39: instalação e ajuste no Termux

## Diagnóstico do screenshot

- `storage/backups/` ausente: é um problema de empacotamento do código anterior, agora tratado pelos arquivos `.gitkeep` e criação automática controlada.
- `upload_max_filesize`/`post_max_size` baixos (2 MiB): configuração do PHP no Termux. É **opcional** para instalar, mas não permite importar backup de 15 MB.
- `public/uploads/` gravável, gzip e UTF-8 disponíveis: não precisam de intervenção.

## Rodar em instalação nova

Descompacte o ZIP com pasta `Meu Site/`, coloque-o no armazenamento privado do Termux, e abra o terminal nessa pasta:

```sh
cd "$HOME/Meu Site"
mkdir -p storage/backups storage/media storage/logs public/uploads
chmod 700 storage/backups storage/media storage/logs
php -d upload_max_filesize=20M -d post_max_size=24M -S 127.0.0.1:8000 -t public public/router.php
```

Abra `http://127.0.0.1:8000/setup.php` no navegador. Não execute o servidor a partir de `/sdcard` nem o exponha na rede pública.

## Instalação existente / atualização segura

Não apague nem substitua `storage/config.php`, `storage/backups/`, `storage/media/`, `storage/analytics.key` ou `public/uploads/`. Faça cópia do banco e da pasta com uploads antes de atualizar. Manter o nome da pasta antiga é compatível com o código novo; não é preciso mover ou renomear sua instalação no Termux.

Se um servidor `php -S` antigo continuar ativo, encerre-o com `Ctrl+C` antes de iniciar com os novos parâmetros. Confira os valores com `php -i | grep -E 'upload_max_filesize|post_max_size'` apenas quando iniciar o PHP com as mesmas flags ou estiver usando o `php.ini` persistente. Os valores efetivos do servidor são mostrados na tela de requisitos.

## O que permanece antigo propositalmente

O nome do banco em instalações existentes, formatos de backup `iguanews-backup` e `iguanews-media-bundle`, variáveis `IGUANEWS_*` e a sessão PHP permanecem compatíveis com versões anteriores. Não altere essas chaves manualmente. O nome exibido ao público é uma configuração separada.
