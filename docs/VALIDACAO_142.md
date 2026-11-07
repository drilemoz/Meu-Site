# Validação da refatoração 1.0.42

## Escopo

1. `app/auth_throttle.php` e `public/admin.php`: `REMOTE_ADDR` validado como IP, hash SHA-256 como nome de arquivo privado, lock exclusivo ao consultar credenciais e atualizar contador, cinco falhas seguidas implicam bloqueio de 15 minutos; sucesso libera o contador; falha do armazenamento impede login.
2. `app/settings_service.php` e `app/admin_controllers/system.php`: validação antecipada, transação única para os 10 campos; rollback em erro SQL. Tabela `settings` já é InnoDB na base suportada.
3. `app/media_library.php`, `app/bootstrap.php`, `app/news_editor/media.php`, `app/admin_controllers/news.php`: tracking do caminho de cada upload novo, inclusive capa agora salva dentro da transação; remoção condicionada a rollback confirmado, preservando imagens existentes e arquivos em falha de COMMIT com status incerto.
4. CI: mudanças em `public/**` e `tools/**` passam a disparar workflow de integração, além de `app/**` e `tests/**`. Sintaxe dos oito trechos JS não é checada isoladamente, pois são fragmentos da mesma IIFE; checar script gerado e `build_news_editor.py --check`.

## Rodar localmente

```sh
php tests/refactor_142.php
php tests/smoke.php
find app public tests -type f -name '*.php' -exec php -l '{}' \;
for file in tests/*.php; do php "$file" || exit 1; done
for file in tests/*.js; do node "$file" || exit 1; done
python3 tools/build_css.py --check
python3 tools/build_news_editor.py --check
```

## Testes necessários na homologação

- Em uma instalação **de teste** com MariaDB: errar senha cinco vezes, tentar com outra sessão e confirmar bloqueio, esperar a expiração e entrar; testar usuário em outra rede e a opção Manter conectado.
- Salvar Configurações com um campo final inválido e comparar valores antes e depois; simular falha SQL durante gravação usando banco **isolado**, sem comprometer a produção.
- Enviar capa, fotos e PDF de teste; forçar erro posterior à primeira gravação e verificar que não há registros incompletos no banco nem arquivos órfãos. Confirmar que imagens já vinculadas a outras notícias continuam acessíveis.
- Testar restauração real de backup v11 e pacotes de mídia v2 usando apenas banco vazio isolado, como especificado em `docs/INTEGRACAO_MARIADB_134.md`.
- Verificar navegação do editor Básico → Texto → Foto no celular e reiniciar servidor PHP após instalação da versão.

## Limites conhecidos

- Limite por origem IP não impede ataques distribuídos por IPs diferentes, e usuários na mesma rede NAT compartilham bloqueio. Reverse proxy exige configuração explícita.
- Os estados de login são arquivos privados; fazer limpeza operacional de estados antigos em manutenção futura, sem apagar estado ativo inadvertidamente.
- Rollback SQL e sistema de arquivos não formam transação atômica. Falha/indefinição de COMMIT pode preservar arquivo que exige revisão manual posterior.
- Testes locais sem extensão PHP `pdo_mysql` e `zip` não comprovam MariaDB ou restauração de ZIP real.
