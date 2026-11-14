# Endereços e SEO | IguaNews 1.0.24

## Objetivo

O portal mantém por padrão os links existentes `/index.php?p=noticia&slug=...` para funcionar no Termux sem alterar comandos. O editor permite personalizar o **nome da URL** sem trocar o título editorial. Em **Notícias → Editar ou Nova → Básico → SEO e destaque**, digite o slug ou clique em **Gerar da manchete**. O resultado é normalizado (somente minúsculas, números e hifens), com até 120 caracteres para novos slugs. Endereços anteriores longos são preservados ao editar.

## Redirecionamento seguro

Ao trocar um slug, o novo endereço é verificado contra URLs de notícias atuais e históricas. A troca ocorre na mesma transação da notícia: o slug anterior entra em `article_slug_redirects`, vinculado à notícia. Pedidos à URL antiga recebem HTTP **301** apontando para o slug atual somente enquanto a notícia estiver publicada e não agendada. Matérias excluídas removem seus históricos por CASCADE. Não são gerados redirecionamentos para notícias ainda em rascunho.

O SEO gera links canônicos a partir do HTTPS configurado em **Configurações → Geral e SEO**; nunca a partir do cabeçalho `Host`. O sitemap usa apenas endereço vigente. Não habilitar indexação antes da revisão editorial.

## URLs amigáveis opcionais

Com o seletor **Usar URLs amigáveis**, passam a ser emitidos links no formato:
- `/noticia/acidente-na-mg-050-iguatama`
- `/categoria/cidade`

**Termux:** na raiz do projeto execute:

```sh
php -S 127.0.0.1:8000 -t public public/router.php
```

O comando antigo `php -S 127.0.0.1:8000 -t public` continua válido com a opção desativada. **Não habilite links amigáveis se continuar executando esse comando**, porque o servidor PHP não faz o roteamento das novas URLs automaticamente. Não exponha o servidor embutido à internet.

**Apache:** DocumentRoot para `public/`, `mod_rewrite` habilitado e `AllowOverride FileInfo` (ou `All`) para ler a regra de `public/.htaccess`. Verifique suporte na hospedagem antes de ativar. Em Nginx, será necessária regra equivalente configurada pelo responsável pelo servidor; a aplicação não altera a configuração do Nginx.

Ao habilitar, os links antigos com querystring redirecionam ao endereço amigável e o sitemap passa a listar somente os endereços atuais. Desabilitar volta ao formato querystring sem apagar slugs/histórico. **Não habilite sem testar notícias e categorias no servidor real.**

## Banco, backup e migração

`article_slug_redirects(slug VARCHAR(240) PRIMARY KEY, article_id FK articles.id, created_at)` é criada automaticamente de forma não destrutiva pelo painel e no instalador novo. O backup lógico passou de **v9 para v10**, incluindo a tabela; restaura v1–v10 e mantém pacote de mídias v2. A restauração de v1–v9 não inventa URLs históricas anteriores. Backup v10 exige IguaNews 1.0.24 ou mais recente para restauração.

## Verificações práticas

1. Faça backup dos dados e guarde o ZIP anterior.
2. Crie uma notícia com nome de URL personalizado, publique e abra o link.
3. Edite somente a manchete e confirme que o endereço não muda.
4. Altere o slug e abra o endereço anterior, confirmando HTTP 301 e destino atual.
5. Tente reutilizar o slug antigo em outra notícia e confira que o painel rejeita.
6. Ative URLs amigáveis **após** configurar o roteamento; valide páginas, categorias, canônicos e `/sitemap.php`.
7. Gere backup v10 e teste restauração em banco isolado, com o histórico incluído.

**Limitação de validação local:** testes PHP CLI sem PDO MySQL e testes JS não comprovam funcionamento no MariaDB/Termux ou Apache. Esses testes precisam ser executados no dispositivo/hospedagem.

## Nginx (VPS/EC2)

O `.htaccess` não é lido pelo Nginx. Inclua `deploy/aws/nginx-friendly-urls.conf` no `server` HTTPS (substituindo o `location /` atual) e recarregue o Nginx antes de ativar **Usar URLs amigáveis**. Sem isso, links `/noticia/slug` retornam 404 e a notícia não abre ao clicar. Alternativa imediata: desativar a opção no painel (Configurações → SEO), que volta aos links `/index.php?p=noticia&slug=...`.
