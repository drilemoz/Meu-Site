# Diagnóstico de DNS do portal (1.0.136)

Acesse **Painel → Ferramentas → Diagnóstico de DNS**. Ao abrir, o PHP obtém automaticamente o domínio da opção **Geral e SEO → Endereço público oficial (HTTPS)**. Se o endereço ainda não estiver configurado, a página exibe um link de configuração. Não é possível consultar endereços arbitrários.

## Consultas

- **Hospedagem:** `dns_get_record()` para A (IPv4), AAAA (IPv6), CNAME (alias) e NS (servidores DNS) do domínio principal e informações locais do `www` ou domínio sem `www` correspondente.
- **Google DNS / Cloudflare:** DNS-over-HTTPS em URLs fixas com HTTPS verificado, sem redirecionamento, até aproximadamente 4,5 segundos para as consultas em paralelo. Requer extensão PHP `curl` e saída HTTPS da hospedagem.
- A comparação de registros de mesmo tipo exibe **Concordam**, **Diferentes** ou **Sem comparação** quando faltam fontes confiáveis. Uma resposta válida sem registros é **Sem registro**; timeout, erro de DNS ou ausência de rede é **Consulta indisponível**.
- **Atualizar** executa nova leitura. Não existe agendamento, histórico ou alerta persistente; o navegador não monitora a página fechada.

## Limitações

O DNS público não revela necessariamente o IP real da hospedagem atrás de proxy ou CDN (inclusive Cloudflare com proxy). Resolvers podem devolver respostas diferentes por cache, TTL, roteamento, balanceamento ou propagação. Ausência de registro `AAAA` não implica falha do site; a delegação NS pode divergir de caches. Esta ferramenta não troca nameservers, registros, hospedagem ou DNS, nem testa conectividade HTTP do site.

## Segurança e testes

A rota exige `manage_settings`; a consulta é somente de leitura e não expõe token. O nome do host é validado; não usar `HTTP_HOST`, entrada GET ou URL fornecida pelo visitante para consultas de DNS. O acesso aos provedores usa HTTPS fixo, não redireciona e possui limite de tempo/tamanho. Testes em `tests/dns_diagnostic_236.php` usam registros simulados, sem requerer rede. Conferir no servidor real a disponibilidade da resolução local e DoH, a aparência no celular e a diferença entre DNS proxy e origem.
