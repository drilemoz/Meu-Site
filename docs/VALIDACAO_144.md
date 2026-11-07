# Validação — Meu Site 1.0.44 (refatoração de views)

## Escopo

Entradas `public/admin.php` e `public/index.php` tiveram exclusivamente organização e extração da renderização. A lógica de negócio, endereços de links, permissões, chamadas SQL, layouts visuais e formatos de backup não foram modificados.

| Parte | Local novo |
|---|---|
| Login e Central de Saúde | `app/admin_views/login.php`, `app/admin_views/health.php` |
| Shell administrativo, dispatcher e telas | `app/admin_views/layout.php`, `pages.php`, `routes/*.php` (9) |
| Shell público, dispatcher e telas | `app/public_views/layout.php`, `pages.php`, `routes/*.php` (8) |
| Fontes para inspeção estática | `tests/support/entry_view_source.php` |
| Contrato de recomposição v1.0.43 | `tests/refactor_144.php` |

### Testes sem banco real

Executar na raiz do projeto:

```bash
find app public tests -name '*.php' -print0 | xargs -0 -n1 php -l
for f in tests/*.php; do php "$f"; done
for f in tests/*.js; do node "$f"; done
python3 tools/build_css.py --check
python3 tools/build_news_editor.py --check
```

Os hashes SHA-256 fixos do teste 144 verificam que, reconstruindo os módulos extraídos, os arquivos PHP voltam exatamente aos bytes originais da versão 1.0.43. Hash não substitui renderização real. Foi feita também comparação isolada de HTML entre as versões para `privacidade`, `termos`, `404`, administração de mídias e fallback de seção administrativa, sem diferenças de bytes nos cinco casos.

### Pendências de implantação

- Executar CI de MariaDB 11.4 com `pdo_mysql` e `zip`, e validar o instalador, login, editor, upload e exportação/restauração em um banco de teste real.
- Exercitar a navegação no navegador Android e a renderização dos submenus, barra inferior e formulários.
- Fazer backup externo do banco, `public/uploads/` e `storage/media/` antes de atualizar uma instalação já em uso.
- Não apagar `storage/config.php`, conteúdos e backups existentes. O pacote de código-fonte deve conter somente marcadores do diretório de armazenamento, nunca segredos ou logs.

**Compatibilidade:** PHP 8.1+, MySQL/MariaDB, esquema 35, JSON v11, pacote ZIP v2. Não foram criadas migrações.
