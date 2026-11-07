# Conteúdo jornalístico de referência

As 100 entradas de `app/sample_news.json` são sínteses editoriais para visualizar o portal e testar o CMS. As pautas derivam dos títulos e informações públicas consultados em outubro de 2026.

**Não são reportagens locais apuradas pela redação, não substituem leitura da fonte original.** Não publique automaticamente o acervo como jornalismo próprio, e verifique correções ou mudanças antes de publicar. Alguns links encaminham para a editoria de origem em vez do artigo específico. Os textos foram escritos para o projeto, não copiados integralmente da fonte.

A maioria dos registros usa editorias da [Agência Brasil](https://agenciabrasil.ebc.com.br/), com referência às fontes estaduais [SES-MG](https://www.saude.mg.gov.br/categoria/noticias/) e [Planejamento MG](https://www.mg.gov.br/planejamento/noticias). O conteúdo precisa de curadoria e atualização humana. A Agência Brasil autoriza reprodução jornalística com citação de fonte, mas seus materiais parceiros e imagens podem ter regras próprias.

As imagens incluídas em `public/assets/img/*.webp` são **geradas por IA**. Não representam acontecimentos reais, pessoas reais ou locais de Iguatama. Uma legenda e crédito aparecem abaixo de cada foto, também personalizáveis no painel.

## Acervo em instalações já existentes

A instalação 1.0.2 contém dez notícias fictícias antigas. Para trocá-las pelo acervo revisado: faça login, abra Notícias, informe a senha na opção Carregar 100 notícias de amostra. É feito backup lógico antes da troca. Notícias cujo marcador `is_demo` foi removido ao editar são mantidas. Os arquivos de imagem enviados são mantidos.

## Integração da migração

No primeiro login do painel 1.0.3, o aplicativo executa `ALTER TABLE` para adicionar quatro campos. Se a hospedagem não permitir `ALTER TABLE`, conceda a permissão temporariamente ou rode os `ALTER TABLE` manualmente, após backup, antes de salvar notícias. Backups 1.0.2 são importáveis em 1.0.3; campos novos recebem valores vazios.
