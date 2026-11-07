# IguaNews 1.0.37 — Refatoração de visualização administrativa

## Escopo
- Fachada de compatibilidade `app/news_admin_view.php` com assinatura pública inalterada.
- Diretório privado `app/news_admin_view/` com `prepare.php`, `header.php`, `details.php`, `blocks.php`, `media.php`, `footer.php`, `revisions.php` e `scripts.php`.
- Inclusões PHP explícitas e fixas, sem caminhos controláveis pelo cliente. Templates `require` compartilham o mesmo escopo local da função original; não incluir arquivos via navegador.
- A composição dos sete templates HTML possui SHA-256 `94736bac19d1c39f62f99c6a726b3a468b2c8ab057a0d612460ae5cad9e9747b`, idêntico ao trecho HTML monolítico da 1.0.36.

## Teste de renderização comparativa
Use `php tests/news_admin_view_137.php`, que prepara cenários de notícia nova e edição com `PDO` simulado e verifica campos, permissões, CSRF, escaping e três etapas. Os hashes do HTML são comparados aos obtidos do arquivo da 1.0.36 com a mesma fixture. Esta equivalência não é um teste de banco real.

## Comandos de validação
```sh
php tests/news_admin_view_137.php
php tests/editor_120.php
php tests/branding_136.php
python3 tools/build_css.py --check
python3 tools/build_news_editor.py --check
node tests/news_editor_modules_135.js
```

## Compatibilidade
- Nenhum esquema SQL novo, revisão 33 mantida.
- Backup lógico v10 e pacote ZIP v2 inalterados.
- CSS e JavaScript públicos não modificados; mantidas rotas `admin.php?action=...` e identificação do site configurável.
- Pendente: instalação/edição no Termux com MariaDB real e testes de UI em navegador Android.
