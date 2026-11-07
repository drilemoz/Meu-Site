# Meu Site 1.0.91 — Wizard editorial compacto

O cadastro de notícia foi refinado para uso em telas móveis: etapas recolhíveis, editor com uma linha de ferramentas, área de fotos em duas grades de três botões, sem guia no topo e com opções avançadas reduzidas.

**Rascunho:** aviso de recuperação com Recuperar/Ignorar e supressão durante a mesma sessão de aba após decisão. Esta ação não exclui o texto salvo no servidor; arquivos locais ainda não entram no autosave.

**Classificação:** administradores autorizados a gerenciar taxonomia podem cadastrar categorias e fontes em um diálogo sem sair do wizard. A fonte principal é única, mantendo o banco no schema 36; associações de múltiplas fontes à mesma notícia ainda dependem de desenvolvimento adicional.

**SEO:** barra de diagnóstico editorial 0-100, verificação detalhada em diálogo, ajuste manual opcional e prévia aproximada. Sem chamada à API Gemini ou garantia de exibição pelo Google.

**Anexos:** aceita documentos, imagens, áudio, MP4 e ZIP sob lista de formatos com verificação de MIME e estrutura. Arquivos executáveis/ativos não são aceitos. Uploads permanecem fora da raiz pública, com download forçado; backups completos incluem novos formatos. O servidor deve ter ZipArchive para ZIP/Office.

**Instalação:** atualização do PHP/CSS/JS sem migração de banco; faça backup do banco e das mídias antes de instalar. A importação de anexos e os diálogos ainda precisam de teste integrado na hospedagem e navegador Android. Gere o ZIP de atualização pelo script oficial e não instale o ZIP de código-fonte na hospedagem.
