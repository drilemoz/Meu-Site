# Validação da 1.0.25

- Base de código: versão 1.0.24 da pasta IguaNews no Google Drive, com ZIP local idêntico em tamanho (1.480.050 bytes).
- Alterações: módulo `app/seo_audit.php`, cartões da Central de SEO, checklist JS no editor, `alt` público da capa vindo da Biblioteca de Mídias, bloqueio de caminhos codificados suspeitos no roteador PHP, testes e documentação.
- Banco de dados: **sem migração** e sem mutação dos artigos. Backup lógico **v10** e pacote de mídias **v2** mantidos.
- Testes automatizados: `php -l` de todos os PHP, `node --check` de todos os JS, todas as suítes PHP e JS da pasta tests, mais HTTP isolado real via `python tests/seo_http_125.py`.
- O teste HTTP verifica 200 (CSS), 404 (rotas inválidas/tentativas de navegação fora de public) e 303 para setup em instalação local sem config. Não foi validado HTTP 301 contra artigo real, porque o MariaDB não estava instalado nesta execução.
- Pendentes: testes em MariaDB para links atuais/históricos, backup e restauração real, validação Apache, acessibilidade/renderização do editor em Android, SEO em domínio HTTPS público, e Google Search Console após publicação.

## Resultado local

- Sintaxe PHP: 61 arquivos, 61 aprovados.
- Sintaxe JS: 12 arquivos, 12 aprovados.
- Suítes PHP: 24, 24 aprovadas.
- Suítes JS: 4, 4 aprovadas.
