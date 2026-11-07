# IguaNews 1.0.28: relatório de arquivos e linhas

Gerado em 2026-10-30 16:45 UTC.

## Alterações

- Lista administrativa: títulos limitados a duas linhas no celular; ações alinhadas à direita.
- Situação da notícia: ícone para publicar ou voltar a rascunho, mediante confirmação e permissão editorial.
- Biblioteca de mídias: botão de pesquisa compacto em dispositivos móveis.
- Upload da biblioteca: envio automático ao selecionar imagens, com opção manual mantida.
- Publicidade: mantém seis anúncios fictícios opcionais de teste da versão 1.0.27.

## Validação registrada na preparação

- 65 arquivos PHP passaram na verificação sintática.
- 12 arquivos JS passaram na verificação sintática.
- 26 suítes de testes PHP, 4 suítes JS e 1 teste Python de HTTP foram aprovados.
- Testes reais no Android com MariaDB continuam pendentes.

## Inventário

162 arquivos de código, documentação e recursos, sem logs operacionais ou bancos privados. Inventário detalhado no CSV anexo.

## Cuidados ao atualizar

Preservar `storage/config.php`, `storage/backups/`, `storage/media/`, `storage/analytics.key` e `public/uploads/`. Não substituir esses locais pelos diretórios da distribuição.
