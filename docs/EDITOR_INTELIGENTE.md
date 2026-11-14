# IguaNews 1.0.10 — Editor inteligente

## O que mudou

- Editor em 4 etapas na mesma página: **Dados, Conteúdo, Mídias, SEO e revisão**.
- Blocos: fatos rápidos, parágrafos, subtítulos, listas, citações e seções expansíveis. Modelos para acidente, política, empregos, eventos, esportes e obras. Modelo é um formulário vazio, não inventa fatos.
- Categoria principal e categorias adicionais armazenadas em `article_categories`.
- Galeria com fotos JPG/PNG/WebP (até 10 fotos por envio, 8 MiB cada), legenda, crédito e ordenação. Miniaturas WebP se a extensão GD permitir, com fallback para originais.
- Anexos PDF/TXT/CSV (até 10 por envio, 12 MiB cada) ficam em `storage/media/`, fora da pasta `public/`. O download só é disponibilizado em matéria publicada, por `/download.php?id=...`.
- Geração automática **local, sem IA nem custo de API**, de metadescrição a partir do resumo e dos fatos já inseridos. Revisão humana obrigatória.
- Importação assistida de URL: apenas HTTPS público, cURL com DNS validado/pinado, limite de 800 KB, sem redirecionamentos, scripts externos nem download de imagens. Captura somente metadados Open Graph, HTML e JSON-LD para um **rascunho**, sem cópia integral de reportagem. Pode falhar em sites com paywall, JavaScript, bloqueios ou ausência de metadados. Antes de publicar, confirme dados, direito de uso, autoria e fonte.
- Rascunho automático de textos salvos na tabela `article_drafts` aproximadamente a cada 18 s, com botão Recuperar. Não inclui arquivos locais e requer sessão ativa e conexão com banco.
- Publicação agendada: selecione situação Publicar e uma data futura. A matéria somente aparecerá no site quando `published_at <= NOW()`. Não é preciso cron externo.
- Histórico de até 20 revisões por matéria; restaura texto, categorias e blocos, preservando a URL, o status, o horário e as mídias.
- As matérias antigas continuam exibidas pelo campo `body`; novos blocos têm precedência de apresentação, mas `body` é mantido para busca, restauração e compatibilidade.

## Instalação e atualização

1. Faça backup do banco e cópia de `public/uploads/` na instalação atual.
2. Pare o servidor PHP e atualize **somente o código**, preservando `storage/config.php`, `storage/backups/`, `storage/media/` e `public/uploads/`.
3. Reinicie e acesse o painel com sua conta administrativa. A migração acrescenta cinco tabelas, sem apagar colunas antigas. Seu usuário MariaDB precisa de `CREATE TABLE`.
4. Selecione Notícias → + Nova notícia. As etapas do editor aparecem numa única página, sem formulário enorme. Use Voltar/Avançar; na etapa final clique em Salvar.
5. Para importar fonte, cole URL HTTPS na barra do editor e clique em Analisar URL; a prévia oferece os metadados que puder recuperar.

### PHP e configurações de upload

Requer PHP 8.1+, PDO MySQL, fileinfo e sessões. Recomendável: cURL (importador por URL), GD (miniaturas) e ZipArchive (backup com mídias). Para anexos, ajuste `upload_max_filesize`, `post_max_size` e `max_file_uploads` conforme sua hospedagem.

Exemplo de servidor local Termux, ajustando limites **apenas durante os testes**:

```bash
php -d upload_max_filesize=16M -d post_max_size=60M -d max_file_uploads=20 -S 127.0.0.1:8000 -t public
```

Sem PHP cURL, o restante do editor funciona, mas a importação por URL exibe mensagem de dependência ausente. Sem ZipArchive, use backup `.json.gz` e copie `public/uploads/` e `storage/media/` separadamente.

## Backups e migração

- O backup lógico agora é **v7** e inclui as seis novas tabelas. A leitura dos backups v1–v6 continua disponível.
- O painel Configurações → Backups oferece `Baixar pacote completo ZIP` **se PHP ext-zip estiver habilitada**. Contém backup do banco, fotos próprias, anexos e hashes SHA-256 para verificação.
- A restauração completa ZIP exige confirmação explícita, senha do administrador, verifica os hashes e executa um backup preventivo antes de substituir o banco. Evite repetir a restauração se ela falhar após o banco ter sido gravado: confira primeiro a situação dos arquivos.
- Os pacotes contêm dados sensíveis, incluindo hashes de senhas, textos de rascunho e possivelmente documentos. Armazene-os em local privado e protegido. Não coloque backups ou chaves no GitHub.
- Miniaturas WebP podem ser recriadas a partir das imagens originais; o pacote completo transfere os originais.
- Não é compatível com hospedagem Vercel sem storage persistente para uploads e banco MySQL externo.

## Responsabilidade editorial

Os modelos de notícia são apenas campos para preenchimento, nunca dados factuais. O importador de URL **não faz scraping de texto integral protegido**, não contorna bloqueios e não confere fatos automaticamente. Informações sobre vítimas, mortes, infrações e causas precisam ser confirmadas antes da publicação.

## Validação

Sintaxe dos arquivos PHP, JavaScript e testes isolados em `php tests/news_editor.php` e regressão em `php tests/smoke.php`. O ambiente de desenvolvimento atual não tem `pdo_mysql`, `curl` nem `zip`, portanto a persistência em MariaDB, importação de URLs reais, miniaturas GD e exportação ZIP precisam ser testadas no Termux ou na hospedagem.
