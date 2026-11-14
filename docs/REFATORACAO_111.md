# Refatoração interna 1.0.111

## Objetivo

Reduzir acoplamento sem mudar recursos, banco, rotas públicas, métricas persistidas ou comportamento de formulário. A base é a 1.0.110. Nenhuma reclassificação de eventos ou revisão de algoritmo foi introduzida.

## Código

- `app/analytics_helpers.php` (novo): leitura de IP de `REMOTE_ADDR`, chave privada, hash diário, regras heurísticas de automação, dispositivos, normalização segura de URLs, referenciadores, campanhas, geolocalização offline opcional, GPC/DNT e configuração de métricas.
- `app/analytics.php`: preserva schema SQL, `analytics_log`, limpeza, agregações, consultas do painel e função GA4; inclui helpers com caminho fixo por `require_once`. Assim, `require app/analytics.php` continua suficiente.
- `public/assets/js/seo-settings.js`: a função `summary()` delega para `updateTextPreviews`, `updateSocialPreview` e `updateFaviconPreview`. A biblioteca de mídias continua alimentando campos ocultos; não há requisições novas.

## Verificações

- `tests/refactor_211.php`: API anterior, rotas permitidas, não registro de URLs privadas, bot/UA, consentimento GPC/DNT e hash por dia.
- `tests/refactor_211.js`: executa o JavaScript com elementos DOM simulados e verifica título, descrição, social, favicon, diagnóstico e abertura de diálogo.
- Toda a suíte `tests/*.php` e `tests/*.js`, sintaxe e geradores CSS/editor e auditoria do projeto devem continuar passando.
- A migração MariaDB real, comportamento visual no Chrome Android e a instalação no Plesk permanecem pendentes.

## Compatibilidade e segurança

Sem migração (schema 37, backup v12). `storage/`, uploads, credenciais, `ads.txt` e configurações persistentes não entram no ZIP de atualização. Não altera consentimento, segurança, autorização, intervalo temporal, contadores, algoritmos de reconhecimento de automações ou dados históricos.
