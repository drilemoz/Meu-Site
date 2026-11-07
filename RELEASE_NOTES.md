# Meu Site v1.0.81 — Central de Integrações aprimorada

- **PageSpeed Insights real:** novo cartão para análise de desempenho mobile do endereço oficial HTTPS do portal, sem chave de API obrigatória para consultas ocasionais. Apenas quando o administrador toca em **Medir desempenho**. Exibe nota de laboratório Lighthouse, horário e métricas disponíveis; não inventa resultados.
- **Segurança:** endpoint Google HTTPS fixo, cURL com TLS válido, sem redirecionamentos, sem URL arbitrária no POST, limite de resposta e tempo de consulta, sessões expiram e testes repetidos são limitados.
- **Interface mobile:** atalhos compactos para GA4, AdSense, Search Console e PageSpeed, botões de copiar IDs/TXT públicos, mensagens de andamento, estado não testado e explicação da diferença entre configuração e diagnóstico externo.
- **Compatibilidade:** mantém GA4 com consentimento, DNS TXT/metatag, AdSense, SEO local, autenticação, CSRF e revisão do banco MySQL 36. Não há migração. O código da automação de Releases da 1.0.80 é preservado.
- **Testes:** mocks determinísticos de PageSpeed e navegação/cópia; testes PHP/JS anteriores, auditoria do projeto e validação dos ZIPs. Não foi executada uma consulta PageSpeed real na hospedagem, nem teste visual num Android real.

**Instalação:** `Atualizacao-v1.0.81.zip` para a hospedagem; `Meu-Site-v1.0.81-fonte-completo.zip` para o GitHub Manager.
