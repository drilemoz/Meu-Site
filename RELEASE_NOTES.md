# Meu Site v1.0.88 — Diagnóstico central das Integrações e APIs

- Finalização da série de melhorias da Central de Integrações: GA4, AdSense, Search Console, indexação, DNS e PageSpeed celular/computador agora têm fichas de diagnóstico com última operação e seu horário.
- Diferencia **configuração preparada**, **consulta concluída**, **consulta com erro** e **não consultado**. Nenhum painel de terceiros é consultado ao carregar a tela.
- Histórico privado com no máximo 12 eventos nesta sessão administrativa. Erros classificados sem divulgar mensagens brutas de serviços externos, chaves privadas, tokens ou dados pessoais.
- Instruções práticas para problemas de autenticação, permissões, quota, rede ou configuração, com atalhos para cada seção.
- Testes offline de PHP/JS, regressões anteriores, integridade ZIP e manifesto SHA-256; sem nova migração MySQL (schema 36).

**Pacotes:** `Atualizacao-v1.0.88.zip` (hospedagem) e `Meu-Site-v1.0.88-fonte-completo.zip` (GitHub Manager).

**Limitação:** todas as integrações continuam sujeitas à autorização do provedor, quota e configuração real da hospedagem. Os testes automatizados usam respostas simuladas; não equivalem à validação com contas Google em produção. O histórico de diagnóstico existe apenas na sessão atual.
