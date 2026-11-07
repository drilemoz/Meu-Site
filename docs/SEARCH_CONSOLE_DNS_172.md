# Search Console — Verificação DNS TXT (v1.0.72)

## O que mudou

A Central Google deixa de considerar a ausência de metatag como prova de configuração pendente. Um domínio pode ter sido verificado por DNS TXT no Search Console sem qualquer metatag no HTML. A área oferece consulta DNS sob demanda, sem API Google e sem segredos.

## Como usar

1. Em **Configurações → Geral e SEO**, confirme a URL oficial HTTPS, por exemplo `https://iguatama.online` (exemplo do projeto, não aplicado automaticamente).
2. Em **Configurações → Integrações e APIs → Google → Search Console**, abra a opção **DNS (TXT)**.
3. Se você conhece o valor completo fornecido pelo Google (`google-site-verification=...`), cole e salve. Este campo é opcional.
4. Toque em **Consultar DNS**. Um registro Google público encontrado aparecerá como **TXT detectado**. Se o valor esperado foi salvo, o painel confirma se a correspondência é **exata**.
5. Acesse o [Google Search Console](https://search.google.com/search-console) para confirmar propriedade na conta Google. Sem OAuth, o portal **não** consegue consultar a situação oficial da propriedade.

## Interpretação

- `matched`: TXT esperado encontrado no DNS público, ainda sem atestar aprovação do Google.
- `found`: registro Google encontrado, sem garantia de que é o registro da conta esperada.
- `mismatch`: registro esperado não está disponível; pode ser propagação, erro de DNS ou token incorreto.
- `missing`: não há registros Google visíveis na consulta.
- `unavailable`: PHP/servidor não conseguiu consultar o DNS; **não** equivale a ausência de registro.
- `no_domain`: falta configurar uma URL HTTPS canônica confiável.

Não remova o registro DNS depois da verificação do Google. Mudanças DNS podem exigir propagação. O teste consulta a zona pública vista pelo resolvedor da hospedagem, que pode ter cache. Para propriedades de prefixo de URL, a alternativa por **metatag** permanece.

## Segurança e privacidade

- O host do teste é derivado exclusivamente da URL `seo_site_url` salva no banco. Não é possível fazer consultas a hosts arbitrários por parâmetro HTTP.
- POST exige autenticação administrativa, permissão de configuração e CSRF; consultas são feitas manualmente, com cache curto na sessão.
- O valor do TXT Google é público por natureza, mas não pode ser confundido com uma chave secreta de API nem utilizado como credencial de acesso ao Search Console.
- Sem migração MySQL; utiliza a tabela `settings`. Nenhuma alteração em backups, arquivos de usuários ou rastreamento público.

Fontes oficiais: https://support.google.com/webmasters/answer/9008080 e https://developers.google.com/site-verification/v1/invoking
