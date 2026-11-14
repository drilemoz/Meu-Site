# IguaNews 1.0.18 | Gerenciador de Mídias

## O que mudou

Em **Notícias → Nova notícia / Editar**, **Publicidade → Campanhas** e **Configurações → SEO**, a escolha de imagens usa o mesmo Gerenciador de Mídias. Há também um menu próprio **Mídias**. Um botão ou o campo de seleção de capa abre a janela central. Em telas pequenas, a janela se adapta à largura do celular.

Na janela:
1. Toque em **Enviar novas fotos** e escolha até 10 imagens; acompanhe cada envio.
2. Navegue em **Biblioteca → 2026 → Outubro**, ou pesquise por nome, título, legenda ou crédito.
3. Toque em uma miniatura para usá-la como capa, imagem de galeria ou banner, conforme o formulário aberto.
4. Em **Detalhes**, edite título, legenda, crédito e texto alternativo; use a seta de download para obter o original armazenado.
5. **Lixeira** apenas desindexa imagens que não estejam vinculadas a notícias, galerias ou campanhas; os arquivos permanecem fisicamente salvos por segurança. A foto pode ser restaurada.

## Armazenamento

Imagens novas são gravadas em `public/uploads/AAAA/MM/nomerandômico.webp` quando WebP/GD está disponível. Exemplo: `public/uploads/2026/10/0123...abcd.webp`. Os nomes de pastas na interface são localizados (2026 → Outubro), mas no disco permanecem numerados para evitar problemas com acentos e ordenação.

Imagens anteriores continuam na raiz de `public/uploads/`, sem movimentação ou alteração de URL. A migração SQL cria `media_library` e indexa os arquivos antigos uma única vez. A extensão GD é opcional: quando não disponível, a imagem validada é mantida em JPG/PNG/WebP sem a otimização do servidor. O navegador tenta compactar fotos grandes localmente antes do upload. PNGs com transparência não devem ser achatados; quando processados pela biblioteca do navegador são exportados em WebP se suportados.

O modo padrão equilibra peso e nitidez (até 1920 px na aresta maior, qualidade WebP 85, miniatura 700 px/78 quando GD). **Não existe garantia de qualidade matematicamente sem perdas**: em caso de conversão e recompressão, pequenas diferenças visuais podem ocorrer. As imagens originais do aparelho não são modificadas. Metadados da cópia retida dependem do formato e das bibliotecas disponíveis; não presuma remoção universal do GPS quando ocorrer fallback para o original.

## Segurança

Os diretórios são limitados pelo padrão de nomes e por `realpath`, impedindo travessia `../` ou links simbólicos para fora de `uploads/`. MIME real, resolução e tamanho são validados antes da gravação; arquivos executáveis são recusados. O acesso ao gerenciador exige sessão administrativa e permissão de escrita; envio e alterações usam CSRF. O compartilhamento de arquivos já publicados segue sendo público.

A remoção de vínculo em notícia **não** apaga o arquivo, que pode estar reutilizado. A lixeira da biblioteca impede remoção enquanto houver vínculo. Não há limpeza física automática nesta versão.

## Backup, instalação e atualização

- Backup lógico **v9** acrescenta `media_library`, sem alterar ou apagar as tabelas de notícias e anúncios.
- O pacote ZIP completo (manifesto **v2**) preserva `assets/images/AAAA/MM/`, além dos caminhos antigos, e segue aceitando pacote v1.
- O restaurador lógico aceita backups v1–v9. O manifesto do pacote exige hashes SHA-256 para detectar alterações.
- Backups JSON/GZIP **não incluem os bytes das fotos**; escolha o pacote completo ZIP com extensão PHP `zip`, ou copie `public/uploads/` e `storage/media/` separadamente.
- Não exclua `storage/config.php`, `storage/backups/`, `storage/media/`, `storage/analytics.key`, `public/uploads/` ao atualizar. Faça um backup completo antes.
- No Termux, se precisar receber fotografias maiores, inicie o PHP na raiz do projeto com `php -d upload_max_filesize=8M -d post_max_size=32M -S 127.0.0.1:8000 -t public`.
- O upload multipart é limitado por `upload_max_filesize` / `post_max_size` do servidor PHP, mesmo quando a interface aceita um valor maior.

## Verificações realizadas e limitações

- `php -l` e `node --check` nos fontes relevantes; testes PHP existentes mais `tests/media_118.php`.
- Sintaxe e contratos de backup v7/v8/v9, resolução segura de caminhos e preservação dos endereços legados.
- **Pendente:** fluxo completo com MariaDB/MySQL instalado, teste real de upload via HTTP, GD/WebP do Termux, restauração ZIP com fotos, navegação visual em navegadores Android e desempenho em biblioteca numerosa. Faça atualização inicialmente em cópia de testes quando possível.

## Anexos da 1.0.91

A lista explícita de extensões permitidas inclui PDF, TXT, CSV, JSON, RTF, DOCX, XLSX, PPTX, ODT, ODS, ODP, JPG, JPEG, PNG, WEBP, MP3, WAV, MP4 e ZIP. O backend confere MIME, cabeçalho PDF e estrutura ZIP/Office; não aceita qualquer extensão, HTML, SVG, scripts ou executáveis. Até 10 arquivos de no máximo 12 MiB por envio, sujeitos aos limites de PHP `upload_max_filesize`/`post_max_size`. Todos os anexos ficam em `storage/media/` e são entregues apenas por `public/download.php` como download. `ZipArchive` é obrigatório para pacotes ZIP/Office. O backup completo (DB + mídias referenciadas) reconhece a lista nova; restaure e teste antes de depender exclusivamente de backup.
