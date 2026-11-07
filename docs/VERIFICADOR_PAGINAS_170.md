# Central de Integrações 1.0.70 — Verificador de Páginas

## Acesso e objetivo
Acesse **Configurações → Integrações e APIs → Páginas**. A interface é compacta e usa ajuda `?` para detalhes. Administradores com permissão `manage_settings` podem verificar a página inicial, até 35 notícias publicadas recentes e até 20 categorias da instalação.

O verificador fornece apenas um **checklist de preparação local**, baseado em dados publicados no MySQL e no armazenamento do portal. Não realiza buscas externas nem aceita URLs arbitrárias. É uma ferramenta de diagnóstico, não um scanner ativo de internet, auditoria WAF ou avaliação Google.

## Verificações
- Prévia da URL canônica sob a URL HTTPS configurada, respeitando URLs amigáveis e clássicas.
- Permissão de indexação considerando `seo_index`, demonstrações e regras do portal público.
- Notícia: título editorial, resumo/descrição, existência real de capa no armazenamento, texto alternativo, condições locais de `NewsArticle` e pontuação editorial existente.
- Categoria: slug, metadados do template.
- Página inicial: condições locais do template e da publicação.
- Em todos os tipos: presença dos arquivos `public/index.php` e `public/sitemap.php`.
- **Índice do Google: não testado**, pois a API de inspeção exige OAuth e propriedade verificada; não há conexão externa nesta versão.

Os indicadores dividem resultados em **OK local**, **Revisar**, **Erro** e **Não testado**. Ausência de imagem retorna erro visível; a imagem pública continua usando fallback, sem apagar registros. Uma URL formada no painel não confirma resposta HTTP 200, indexação, canonical visto pelo crawler ou entrega de sitemap.

## Segurança
- Somente GET autenticado com `manage_settings`. Nenhuma gravação ou alteração na notícia durante inspeção.
- Seleção restrita a `home`, `article:<ID>` ou `category:<ID>` com regex, consulta parametrizada e whitelist da lista oferecida.
- Não há downloads nem HTTP para URL fornecida pelo usuário; isso impede SSRF no verificador.
- Resultados escapados com `h()`, sem exibir segredos.
- Não armazenar laudos pessoais nem credenciais. Usar navegação do navegador para abrir página publicada e Search Console manualmente.

## Search Console e próximas fases
A metatag publicada na versão 1.0.69 continua configurável em **Google → Search Console**. Esta versão não realiza confirmação de posse nem inspeção remota. A API oficial de inspeção de URL requer `webmasters` ou `webmasters.readonly` via OAuth e informa somente o estado de indexação no Google, não um teste de URL ao vivo: https://developers.google.com/webmaster-tools/v1/urlInspection.index/inspect .

Uma fase futura pode integrar OAuth com credenciais seguras, callback HTTPS e gerenciamento de escopos, após validar a hospedagem e a autorização do proprietário.

## Testes e atualização
`php tests/page_inspector_170.php`, `php tests/integrations_169.php`, regressão, `python3 tools/build_css.py --check` e PHP lint. Schema 36; ZIP de atualização v2 sem assinatura, login + CSRF e senha de administrador preservados. O instalador exige simulação e backup.
