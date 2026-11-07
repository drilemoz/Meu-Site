# Meu Site 1.0.90 — Novo assistente de notícias

A partir de **Nova notícia**, o cadastro abre um wizard com nove passos. O fluxo pergunta o título, a pequena descrição, a matéria, fotos, créditos, anexos, categoria e SEO antes de mostrar uma revisão completa. Somente na última etapa é feita a escolha entre salvar rascunho, publicar agora ou agendar.

**Fotos:** biblioteca, envio múltiplo, prévia local e opção de ZIP de até 10 imagens (JPG/PNG/WebP), validado pelo PHP. A primeira foto vinculada poderá virar a capa automaticamente. O servidor precisa de ZipArchive, suporte a uploads e limites compatíveis.

**SEO:** descrição local baseada em frases completas, sem cortes automáticos com reticências; prévia aproximada. Gemini ainda não está integrado.

**Compatibilidade:** schema MySQL 36 inalterado; nomes de campos, anexos, biblioteca, segurança, revisão e rascunhos existentes preservados. O autosave guarda texto, mas não guarda arquivos locais selecionados. Recomendado criar backup antes de atualizar e testar o fluxo completo em celular e hospedagem antes de usar em produção.
