# Meu Site v1.0.87 — Integração autorizada com Google AdSense

- AdSense Management API v2: autorização separada por OAuth 2.0 de usuário, com escopo somente de leitura e consentimento Google. O AdSense não aceita credenciais de conta de serviço.
- Cliente OAuth privado configurável com senha administrativa e HTTPS; callback autorizado com state de 256 bits, PKCE SHA-256, vinculação de sessão e consumo único em até 10 minutos.
- Relatório manual de 30 dias completos: receita estimada na moeda retornada pelo Google, impressões, cliques e visualizações de páginas da conta, não apenas do portal.
- Verificação de conta retornada pela API correspondente ao ca-pub configurado; sem inventar dados em respostas vazias.
- Tokens e segredos ficam somente no storage privado; consulta com TLS, endpoints fixos, timeouts, limite de resposta, CSRF, ACL e intervalos entre requisições.
- Banco continua no schema 36. GA4, Search Console, PageSpeed, consentimento e anúncios existentes foram preservados.

**Pacotes:** `Atualizacao-v1.0.87.zip` (hospedagem) e `Meu-Site-v1.0.87-fonte-completo.zip` (GitHub Manager).

**Validação pendente:** autorização real com uma conta AdSense e testes em navegador/servidor com PHP cURL e HTTPS. A integração não publica anúncios nem comprova aprovação da conta.
