# Publicação em hospedagem PHP · IguaNews

## Estrutura do servidor

A raiz acessível via navegador deve ser **somente `IguaNews/public`**. Deixe `app/`, `bin/`, `storage/` e `docs/` um nível acima da pasta pública. Em hospedagens Apache com estrutura de subpastas, ajuste o domínio/subdomínio para apontar a `public`.

Se o plano não permitir mudar o document root, peça orientação ao suporte para manter credenciais e backup fora de `public_html`. **Não envie o ZIP inteiro para uma pasta pública e exponha o conteúdo das outras pastas.**

PHP necessário: >= 8.1; PDO MySQL; Fileinfo; JSON e sessões; zlib opcional. O instalador `/setup.php` testa todos os itens antes da conexão ao banco e informa correções para cada falha. MariaDB/MySQL devem aceitar conexão e `CREATE TABLE`. Configure `display_errors=Off` em produção, HTTPS, atualizações de segurança e permissões restritas.

## Ambiente sem `storage/config.php`

A conexão também aceita estas variáveis, úteis em contêineres:

```ini
IGUANEWS_DB_HOST=127.0.0.1
IGUANEWS_DB_PORT=3306
IGUANEWS_DB_NAME=iguanews
IGUANEWS_DB_USER=iguanews
IGUANEWS_DB_PASSWORD=senha-secreta
```

**Observação importante:** se `IGUANEWS_DB_NAME` existir, o projeto considera o setup já concluído. Crie a estrutura e a conta administrativa primeiro em um servidor PHP com diretório persistente. Nunca inclua senhas reais no Git ou no ZIP público.

## Vercel

O runtime comunitário de PHP na Vercel não é suporte oficial. Este projeto **não inclui `vercel.json`**, nem fluxo de inicialização stateless testado. Na Vercel, `storage/config.php`, fotos enviadas e arquivos de backup não seriam persistidos localmente; também é preciso MySQL remoto. Para instalar/testar a versão inicial, use Termux e, depois, hospedagem PHP convencional.

## Publicação responsável

As notícias de exemplo são fictícias. O front exibe aviso e etiqueta de exemplo. Antes de divulgar o endereço como veículo de notícias, substitua todas as amostras por informações verificadas. As miniaturas do mockup são somente ilustrativas.

## Instalação na hospedagem e migração (v1.0.2)

O modo **Criar automaticamente** só funciona no MariaDB **local**, quando o instalador é aberto via `127.0.0.1` e há uma credencial com privilégios administrativos. **Não espere que o site crie contas MySQL em cPanel**: crie uma base vazia e usuário pelo painel e escolha **Usar banco existente** no setup.

Na etapa **Conteúdo**, para migrar um portal IguaNews anterior, escolha **Importar portal anterior** e envie backup `.json/.json.gz` da instalação antiga. Isso restaura usuários (mesmo login), categorias, matérias e configurações. Transfira também `public/uploads/` para recuperar fotos e confira tudo antes de apontar o domínio. Verifique limites de upload PHP e veja `docs/MIGRACAO.md`.
