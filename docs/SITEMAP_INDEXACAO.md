# Sitemap e Indexação — IguatamaOnline v1.0.100

## Administração

Em **Configurações → Sitemap e Indexação**, administradores com `manage_settings` podem:

- Ver a quantidade de URLs elegíveis: início, ônibus ativo, categorias com notícias publicadas, notícias já publicadas e não demonstrativas e páginas de aplicativos efetivamente publicadas.
- Buscar por texto/URL, filtrar por tipo e percorrer a lista em páginas de 20 resultados. Abrir links públicos, copiá-los e testar o HTTP de **um link** manualmente.
- Ver o diagnóstico **local** do domínio HTTPS, a opção SEO de indexação, a existência de `robots.txt`, a diretiva Sitemap e a correspondência do `ads.txt` com o editor AdSense configurado.
- Acionar **Verificar online** para consultas HTTP sob demanda no próprio navegador e na mesma origem (`/sitemap.php`, `/robots.txt`, `/ads.txt`). Ausência de resposta informa falha; nunca presumir aprovação do AdSense, rastreamento ou indexação.

É uma central de inspeção e gerenciamento por publicação: para retirar uma URL, altere o status de publicação da notícia ou página pelo respectivo módulo, respeitando revisões e permissões. O sitemap não fornece controles arbitrários de bloqueio por URL.

## Publicação pública

- XML: `https://iguatama.online/sitemap.php`, disponível com `seo_index=1` e `seo_site_url` HTTPS válido, domínio raiz e banco acessível.
- `robots.txt` anuncia esse sitemap e impede rastreamento de `/admin.php` e `/setup.php`. Ao mudar o domínio oficial, revise a diretiva estática.
- `ads.txt` é um arquivo persistente, publicado em **Publicidade → Preparação para AdSense**. Ele deve conter o `pub-...` real. O ZIP de atualização **nunca** inclui nem substitui `public/ads.txt`.
- XML e lista são derivados do mesmo `app/sitemap_catalog.php`. `lastmod` somente é incluído quando há uma data válida no registro. URLs canônicas seguem o modo de URLs amigáveis configurado.
- O XML tem limite de 50.000 URLs, incluindo página inicial e categorias. Se houver mais URLs elegíveis, a central alerta. Antes de chegar a esse volume, deve ser implementada fragmentação em vários sitemaps com índice, conforme recomendações do Google.

## Busca e verificação

O sitemap é **uma sugestão de descoberta**, não a lista de URLs já indexadas. Para cobertura e eventuais erros reais do Google, confira [Search Console](https://search.google.com/search-console). Verificações HTTPS no navegador não realizam requisições à API do Google nem criam permissões OAuth.

Valide no domínio publicado: `https://iguatama.online/sitemap.php` (XML, HTTP 200), `https://iguatama.online/robots.txt` (diretiva correta), `https://iguatama.online/ads.txt` (quando AdSense configurado), além dos links públicos de notícias e páginas legais. Se a indexação estiver desligada, o sitemap responde 404 conforme o comportamento anterior.

## Segurança e implantação

A central exige login e permissão de configurações. Não grava no banco. Não faz crawling em lote e não permite URLs de destino arbitrárias; os testes do navegador aceitam apenas a mesma origem. Os filtros GET não são usados na composição de SQL. Nenhum arquivo privado, token ou credencial é exibido. A versão mantém **schema 37**, backup lógico **v12** e não exige migração de dados. Preserve backups externos antes da atualização.

## Testes

`php tests/sitemap_200.php`, `node tests/sitemap_200.js`, `python3 tools/build_css.py --check`, `python3 tools/audit_project.py`. A integração MariaDB e o HTTP na hospedagem permanecem verificações adicionais.

## Bing Webmaster Tools — v1.0.106

A integração de **leitura** está em **Configurações → Sitemap e Indexação → Bing Webmaster Tools**. Para obter a chave, adicione e verifique `https://iguatama.online` no [Bing Webmaster Tools](https://www.bing.com/webmasters), abra Configurações → API Access → API Key e gere uma chave do proprietário. Nunca compartilhe essa chave nem a inclua no GitHub.

Em **Salvar chave**, informe a chave e a senha administrativa. Ela será armazenada exclusivamente em `storage/integrations/bing-webmaster.json`, fora do público e dos backups. Clique em **Testar e consultar sitemaps** para consultar os métodos JSON documentados `GetUserSites` e `GetFeeds`; é uma chamada real, sob demanda. Falhas HTTP apresentam diagnóstico sem o corpo bruto e sem credenciais. A API do Bing pode alterar disponibilidade e formatos. A consulta só pode mostrar a propriedade HTTPS oficial definida em SEO.

O resultado retorna status da propriedade e até 30 feeds/sitemaps conhecidos. **Não prova que todas as URLs foram indexadas.** Para notificar novos links, utilize a integração **IndexNow** acima, evitando requisições duplicadas. É possível remover a chave com senha, sem alterar IndexNow. Não há migração de banco (schema 37).

Referências: https://learn.microsoft.com/en-us/bingwebmaster/getting-access e https://learn.microsoft.com/en-us/dotnet/api/microsoft.bing.webmaster.api.interfaces.iwebmasterapi.getfeeds?view=bing-webmaster-dotnet

## Verificação da propriedade no Bing (v1.0.107)

O arquivo `public/BingSiteAuth.xml` foi incluído no pacote a partir do documento original enviado pelo administrador. Após instalar, confira `https://iguatama.online/BingSiteAuth.xml` (arquivo acessível sem login, HTTP 200 e XML preservado) e toque em **Verify** no Bing Webmaster Tools. A Central de Sitemap possui um atalho **Ver BingSiteAuth.xml**. O XML é público por exigência do Bing; não é a chave privada da API Bing nem a chave IndexNow. O portal não consegue declarar a propriedade verificada sem a confirmação do serviço.

## Interface compacta (v1.0.108)

Os indicadores e a lista de URLs ficam visíveis. Os atalhos **Verificações**, **IndexNow** e **Bing** abrem pop-ups centrais no mesmo painel. É possível acessar diretamente `admin.php?action=sitemap#indexnow` ou `#bing-webmaster`; as âncoras abrem o diálogo correspondente. Nenhuma consulta HTTP ou notificação é iniciada ao abrir as janelas.

A listagem usa 12 URLs por página. Ela mostra o caminho para poupar espaço no celular, sem alterar o endereço canônico: Copiar e Abrir usam a URL inteira. Diagnósticos de rastreamento não comprovam indexação.
