# Meu Site v1.0.94 — Wizard, SEO e listas de notícias

- Botão Sair no canto direito do cabeçalho da notícia, sem importação por URL no assistente.
- Uploads de Capa/Fotos/ZIP/Anexos em botões exclusivos, sem controles globais duplicados.
- Ferramentas SEO com quatro ícones e pop-ups para slug, geração de descrição, edição de título/descrição e diagnóstico com correção.
- O diagnóstico permite alterar e aplicar ajustes ao rascunho, com botão Salvar à direita; Cancelar não modifica os campos da notícia.
- Notícias cadastradas e publicações recentes compartilham cartões responsivos, ícone de lixeira, categoria e tempo relativo conforme data real.
- Sem alteração do schema 36; testes na hospedagem e no Android pendentes.

---

# Meu Site v1.0.93 — Pop-ups padronizados no painel

- Todas as janelas de conteúdo principais do painel passam a seguir o mesmo padrão visual, usando um componente reutilizável.
- Corrigidas as janelas gigantes de Google Analytics 4, AdSense, Search Console e PageSpeed no celular: agora centralizadas, com margens, limite de altura e rolagem interna, sem deslocamento horizontal por campos compridos.
- PageSpeed agora mostra Celular e Computador em abas próprias; alternar abas não consulta a API e não altera resultados existentes.
- Janelas de atualização, publicidade, avisos e editor recebem os mesmos cantos, cores, dimensões e botão de fechar, preservando seus formulários e operações.
- Verificações de segurança, confirmação de senha, CSRF e progresso real de atualização permanecem. Banco schema 36 inalterado.
- Testes locais e simulação de layout em Chromium executados. Necessário validar em dispositivo Android real e hospedagem antes de considerar a implantação aprovada.

---

# Meu Site v1.0.92 — Atualizações pelo GitHub em janela única

- Tela principal mais enxuta: **Visualizar** e **Enviar ZIP**, sem link Releases nem cartões de origem/servidor.
- **Visualizar** consulta versões e novidades sob demanda; se houver release instalável, apresenta confirmação e senha na própria janela.
- Durante a instalação, mostra as etapas confirmadas pelo PHP (download, validação, vistoria, backup e aplicação), sem percentual artificial. Ao confirmar o sucesso: **Atualização concluída** e botão **Atualizar página**.
- Configuração do repositório, vistoria, importação manual, segurança, backups e recuperação mantidos.
- Código e testes locais atualizados. Schema 36 preservado. **Hospedagem real não testada nesta entrega**.

---

# Meu Site 1.0.91 — Wizard editorial compacto

O cadastro de notícia foi refinado para uso em telas móveis: etapas recolhíveis, editor com uma linha de ferramentas, área de fotos em duas grades de três botões, sem guia no topo e com opções avançadas reduzidas.

**Rascunho:** aviso de recuperação com Recuperar/Ignorar e supressão durante a mesma sessão de aba após decisão. Esta ação não exclui o texto salvo no servidor; arquivos locais ainda não entram no autosave.

**Classificação:** administradores autorizados a gerenciar taxonomia podem cadastrar categorias e fontes em um diálogo sem sair do wizard. A fonte principal é única, mantendo o banco no schema 36; associações de múltiplas fontes à mesma notícia ainda dependem de desenvolvimento adicional.

**SEO:** barra de diagnóstico editorial 0-100, verificação detalhada em diálogo, ajuste manual opcional e prévia aproximada. Sem chamada à API Gemini ou garantia de exibição pelo Google.

**Anexos:** aceita documentos, imagens, áudio, MP4 e ZIP sob lista de formatos com verificação de MIME e estrutura. Arquivos executáveis/ativos não são aceitos. Uploads permanecem fora da raiz pública, com download forçado; backups completos incluem novos formatos. O servidor deve ter ZipArchive para ZIP/Office.

**Instalação:** atualização do PHP/CSS/JS sem migração de banco; faça backup do banco e das mídias antes de instalar. A importação de anexos e os diálogos ainda precisam de teste integrado na hospedagem e navegador Android. Gere o ZIP de atualização pelo script oficial e não instale o ZIP de código-fonte na hospedagem.
