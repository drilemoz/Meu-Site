# Meu Site v1.0.86 — Audiência e conteúdo via Google Analytics 4

- Na **Central de Integrações → Google Analytics 4**, novo botão **Consultar audiência**. Recupera métricas autorizadas dos **28 dias completos anteriores a hoje**: usuários ativos, sessões e visualizações, além de páginas populares (por título), canais de aquisição e dispositivos.
- Apenas um OAuth de conta de serviço e um `batchRunReports` com quatro relatórios por consulta. Requer Analytics Data API ativa, ID numérico de propriedade e conta de serviço com permissão de leitura.
- Resultados somente na sessão administrativa por até **1 hora**, com espera entre tentativas (90 segundos) e após relatório válido (3 minutos). Nenhuma consulta automática, alteração de banco, nova chave ou token exposto no HTML.
- Dados são da **propriedade GA4**, que pode ter vários fluxos. Visitas e usuários dependem do consentimento, configuração e processamento do Google. Páginas são agregadas pelo **título**, não uma contagem única de notícias.
- Mantidas as APIs do Search Console (inclusive inspeção individual), PageSpeed e AdSense, os testes de recebimento GA4 e o consentimento público. Schema 36 preservado.
- Testes offline usam respostas simuladas e cobertura de JWT, lote, cabeçalhos, TTL, limites, permissões de rota/CSRF e escaping de dados externos. A execução real no Google e UI Android continuam pendentes.

**Pacotes:** `Atualizacao-v1.0.86.zip` (hospedagem) e `Meu-Site-v1.0.86-fonte-completo.zip` (GitHub Manager).
