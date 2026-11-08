# Diagnóstico GA4 — v1.0.119

## Limites da verificação

Ter um ID G- salvo e o interruptor ativado **não confirma recebimento de dados**. A coleta pública usa `ga4-consent.js` e depende de consentimento do visitante, além de respeitar GPC/DNT e opt-out. A interface administrativa não envia pageviews ao GA4.

O botão manual **Verificar tag no site público** consulta apenas `/index.php` na mesma origem, compara o ID público informado no HTML (`#ga4Consent[data-ga4-id]`) com o ID apresentado no formulário e confirma que `ga4-consent.js` está referenciado. Ele não executa o script retornado, não liga consentimento, não faz chamada ao Google e não consegue confirmar `page_view` recebido; pode identificar cache/deploy incorreto, por exemplo ID divergente ou ausência da tag.

## Teste de ponta a ponta com consentimento

1. Acesse **Google Analytics → Administrador → Fluxos de dados → Web**, abra o fluxo `iguatama.online` e confira o **ID de medição G-**, que não é nem o ID numérico da propriedade nem o ID numérico do fluxo.
2. No portal público em navegador comum e sem bloqueadores, aceite **Permitir métricas** (não teste pela página administrativa). Se já rejeitou, use **Preferências de métricas** no rodapé. GPC/DNT e opt-out do portal impedem a tag intencionalmente.
3. Navegue para outra página pública e confira **Relatórios → Tempo real** no GA4. Para diagnóstico mais profundo use `https://tagassistant.google.com/` e **DebugView**, observando a necessidade de depuração e consentimento. Relatórios históricos podem sofrer atraso; Tempo real é mais apropriado para a verificação inicial.
4. Se o Google indicar nenhum dado nas últimas 48 horas, investigue versão efetivamente publicada, cache, ID G-, script entregue, bloqueios do navegador/CSP e consentimento. A existência de visitas no painel local não equivale a eventos GA4, que possuem bases de medição distintas.

## Consultas opcionais pela API

A conta de serviço JSON não é necessária para carregar a tag no público. Ela permite que somente administradores com autorização consultem dados agregados pela **Analytics Data API**, habilitada no projeto Google Cloud e com permissão de Leitor na propriedade correta. Nunca suba o JSON no GitHub, logs ou páginas públicas.

## Testes

A suíte offline valida os estados da interface, as ações POST/CSRF, os controles GPC/DNT e a lógica de disparo condicional. O Chromium local emula o HTML e o layout, mas não substitui o site real nem a confirmação no Google.

Referências oficiais:
- https://support.google.com/analytics/answer/9333790?hl=pt-BR
- https://support.google.com/analytics/answer/7201382?hl=pt-BR
- https://developers.google.com/tag-platform/security/guides/consent?hl=pt-BR
