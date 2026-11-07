# IguaNews 1.0.16 · Correção de métricas

## Origem do aviso

Na versão 1.0.15, `public/admin.php` fazia primeiro `$_GET['days']??30` na validação e, se fosse um período aceito, relia `$_GET['days']` diretamente. Acessando `/admin.php?action=analytics` (sem `days`), a condição era verdadeira com o padrão 30, mas o acesso direto emitia `Warning: Undefined array key "days"`.

## Correção

A rota chama `analytics_period($_GET)`; a função lê a entrada uma vez e valida estritamente os períodos permitidos: 7, 30 e 90 dias. Ausência, valores inesperados e arrays retornam 30. A função de consulta `analytics_admin_data()` permanece inalterada, assim como os registros de audiência e backup v8.

## Testes e limitações

- `php -l` em todo PHP; `php tests/*.php` para testes isolados existentes e novos casos de regressão.
- Verificar comportamento sem `days`, com `7`, `30`, `90`, `0`, `900`, `30teste` e valores não escalares.
- Verificar integridade ZIP e ausência de configuração privada no pacote.
- Teste integrado com MariaDB real e visual no navegador do Termux ainda dependem do aparelho.

## Verificação manual no Termux

1. Preserve arquivos privados em `storage/`, incluindo configurações e `analytics.key`, além das fotos em `public/uploads/`.
2. Substitua os arquivos de código e reinicie `php -S 127.0.0.1:8000 -t public`.
3. Abra `/admin.php?action=analytics` sem `days` e verifique “últimos 30 dias”, sem `Warning`.
4. Teste botões 7/30/90 dias; se não houver eventos, tabelas podem ficar vazias, mas a página deve funcionar.
