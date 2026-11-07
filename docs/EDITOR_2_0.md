# IguaNews 1.0.20 | Editor por etapas

## Fluxo atual

A partir da versão 1.0.20, tanto **Nova notícia** quanto **Editar matéria** abrem no mesmo fluxo guiado de **três etapas**:

1. **Básico:** manchete, resumo, categoria e situação; opções de fonte, autor, publicação agendada, SEO e destaque ficam recolhidas.
2. **Texto:** editor visual de notícia, formatação segura, prévia e blocos opcionais.
3. **Foto:** gerenciador de mídias, capa, galeria, anexos opcionais e **Salvar notícia**.

Não existe uma quarta tela de revisão ou SEO. Se o campo de meta descrição estiver vazio, o editor o preenche automaticamente ao salvar. A opção manual de SEO continua disponível em **Básico → SEO e destaque**. A navegação só avança depois de preencher manchete, categoria e conteúdo. O rascunho automático continua ativo, mas só dados textuais e vínculos são recuperáveis, nunca arquivos de upload pendentes.

## Ajuste da etapa Foto na versão 1.0.22

Ao enviar fotos e documentos diretamente, as legendas e créditos dos itens escolhidos continuam preenchidos quando outro grupo de arquivos é alterado e depois da otimização local de imagens. Selecionar um conjunto diferente para o mesmo campo limpa apenas as legendas/créditos desse conjunto. Resultados atrasados de otimizações anteriores são ignorados. **Atenção:** autosave do rascunho não guarda arquivos selecionados; finalize a matéria com **Salvar notícia** para enviar imagens e documentos.

## Correção do rascunho automático na 1.0.23

O botão **Recuperar alterações** restaura categorias marcadas/desmarcadas, seletores, listas de valores, texto e blocos conforme o rascunho, mantendo o ID real da matéria e o CSRF atuais. Fotos e documentos selecionados no aparelho **não são guardados** pelo autosave; selecione novamente os arquivos antes de salvar a notícia. Os formatos de banco e backup não mudaram.

## Compatibilidade

A edição visual continua convertendo conteúdo a marcações seguras no campo `body`; não aceita scripts em HTML. Galerias, anexos, mídias antigas, blocos, agendamento e SEO permanecem funcionais. O esquema do banco e backup v9 / pacote v2 não mudaram.

## Nota histórica

Nas versões 1.0.15 a 1.0.19 havia um modo rápido de página única e uma quarta etapa de revisão. Essa interface foi substituída conforme solicitado. O editor visual criado na 1.0.19 foi mantido.
