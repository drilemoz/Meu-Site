# Recebimento de dados Google Analytics — 1.0.71

## Fluxo
1. No GA4, identifique o **ID numérico da propriedade** (não é o ID G-). Na Central de Integrações → Google → Google Analytics 4 salve os IDs.
2. No Google Cloud, habilite a **Google Analytics Data API**. Crie uma **conta de serviço** (service account) para leitura do GA4 e gere um JSON de chave privada com RSA de no mínimo 2048 bits.
3. No Google Analytics, em Administrador → Gerenciamento de acesso à propriedade, adicione o e-mail da conta de serviço com papel **Leitor**.
4. Importe a chave JSON pelo formulário da Central (exige senha administrativa e CSRF); o arquivo é gravado em `storage/integrations/ga4-service-account.json` com permissões 0600 e diretório 0700. Não faça esse envio sem HTTPS.
5. Clique em **Testar recebimento**. A página consulta a Data API somente nessa ação, usando OAuth 2.0 JWT `analytics.readonly`, o relatório realtime (30 min) e um relatório de 7 dias. O resultado exibe número de eventos, usuários ativos e último dia com eventos. Nenhuma consulta roda automaticamente nem a cada visitante.
6. Se zero eventos, verifique consentimento do visitante, bloqueadores, o G-ID, as permissões e a propriedade escolhida; zero por si só não significa falha.

## Segurança
- O JSON privado NÃO é incluído nos ZIPs, nos backups, nem é armazenado em `settings` do banco. Não envie a chave por chat, e-mail, GitHub ou captura de tela; revogue-a no Google Cloud se comprometida.
- Não ativa o GA4 público e não altera as preferências de cookies: a tag só carrega mediante consentimento prévio válido.
- O painel não expõe tokens, JWT, chave privada ou respostas HTTP brutas. Ações de credenciais exigem sessão admin, CSRF, senha administrativa e confirmação para remoção.
- TLS verificado e hosts fixos `oauth2.googleapis.com` e `analyticsdata.googleapis.com`, sem redirecionamentos, timeout 15s por chamada e resposta limitada a 128 KiB. Necessita extensões PHP `openssl` e `curl`, certificado CA confiável e conexões de saída ao Google.
- As métricas pertencem à **propriedade** e podem incluir vários streams, portanto não demonstram isoladamente que o site é a origem dos eventos. A associação exata com o stream exige verificações adicionais.
- Se o provedor configura a raiz pública fora de `public/`, confirme que a pasta `storage/` continua inacessível via HTTP. `.htaccess` não protege Nginx: configure a hospedagem adequadamente. Não teste segredo em ambiente de hospedagem não confiável.

## Limites
- A Google Analytics Data API deve estar habilitada; o acesso da conta de serviço à propriedade é obrigatório.
- Uma propriedade nova pode não ter eventos nos 30 min/7 dias. Relatórios históricos podem ter atraso de processamento.
- A versão 1.0.71 não implementa OAuth de usuário final, não usa API de administração para cruzar o G-ID com a propriedade, não consulta AdSense, Google Ads ou Search Console e não compara IDs entre streams.
- Em hospedagem sem cURL, o diagnóstico exibe erro de extensão indisponível. Não é possível confirmar funcionamento real sem credenciais autorizadas e acesso à rede da hospedagem.

Documentação Google: https://developers.google.com/analytics/devguides/reporting/data/v1/quickstart e https://developers.google.com/analytics/devguides/reporting/data/v1/realtime-basics
