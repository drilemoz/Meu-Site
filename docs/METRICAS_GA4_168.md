# Métricas e audiência — 1.0.68

## Períodos

A página `Administração → Métricas` oferece Hoje, 24h, 7d, 30d e 90d, mantendo 30d como padrão.

- **Hoje:** meia-noite até agora, em `America/Sao_Paulo`.
- **24h:** janela móvel exata, de agora menos 24 horas até agora. O gráfico pode ter 25 faixas de relógio parciais nos limites.
- **7/30/90 dias:** dias corridos, incluindo o dia atual, até o instante atual.
- KPIs, gráfico, fontes, páginas e dispositivos usam **exatamente o mesmo filtro**.
- O gráfico agrupa por hora em Hoje/24h; por dia nos demais filtros. Horas/dias sem eventos valem zero.
- Visitas únicas são estimativas **por dia**; a mesma pessoa pode aparecer duas vezes ao cruzar a meia-noite. Não representam visitantes únicos reais de 24 horas ou do mês.

As consultas filtram `event_day` (aproveitando índices existentes) e `occurred_at` (recorte preciso). O servidor PHP usa `America/Sao_Paulo` e a conexão MySQL usa `-03:00`. Não há alteração de esquema: continua na revisão 36.

## Configuração do Google Analytics 4

1. Crie uma propriedade e um fluxo da Web no Google Analytics.
2. Copie o **ID de medição** `G-XXXXXXXXXX` do fluxo (não é `GTM-` nem `ca-pub-`).
3. Abra `Administração → Métricas → Configurações e integrações`.
4. Digite o ID, ative **Google Analytics 4 (GA4)** e salve. As métricas internas são independentes.
5. No portal público, visitantes recebem opção de consentimento separada da publicidade. Apenas quem permitir carrega a tag `gtag.js` e envia dados ao Google.
6. Os visitantes podem revisar essa escolha em `Preferências de métricas`, no rodapé. Os dados do Google devem ser consultados no painel do GA4; a API de relatórios do GA4 não faz parte desta versão.

O ID é validado no servidor e inserido no HTML escapado. **Nenhum código de rastreamento arbitrário é aceito.** O recurso fica desativado por padrão. As escolhas de consentimento são armazenadas em cookie técnico por 180 dias, associadas ao ID de medição (ao trocar o ID é preciso novo aceite); GPC/DNT e opt-out interno impedem a carga da tag. A revogação após o carregamento força recarregar a página para impedir carregamento futuro; ela não apaga dados históricos já coletados pelo Google.

Esta integração usa consentimento **básico**: não faz requisições ao Google antes da autorização. O consentimento de publicidade (AdSense) é independente e não é automaticamente concedido.

Documentação oficial de referência: https://developers.google.com/tag-platform/security/guides/consent?consentmode=basic

## Segurança e limites

- Persistência em chaves da tabela `settings`, sem nova tabela, migração ou alteração do backup.
- Apenas administradores com `manage_settings`, sessão válida e token CSRF podem alterar a configuração.
- A tag não é carregada no painel administrativo, e o ID não contém código ou segredos.
- O sistema não recupera dados de GA4 via API nem substitui um gerenciador de consentimento certificado quando exigido.
- O gráfico do site **não reflete automaticamente** os números reportados pelo Google, porque possuem metodologias e consentimentos distintos.
- Não inserir chave de API nem token do Google Analytics no campo ID.

## Testes

```
php tests/analytics.php
php tests/analytics_168.php
node tests/ga4_consent_168.js
python3 tools/build_css.py --check
php -l app/analytics.php
php -l app/analytics_admin.php
php -l app/admin_controllers/system.php
```

Para testes reais, validar MariaDB, PHP-FPM, navegador Android, cookie de consentimento, cookies do Analytics e a aba Rede (confirmar ausência de requisição Google antes do aceite). Nenhum teste local sem hospedagem comprova coleta efetiva na propriedade GA4.
