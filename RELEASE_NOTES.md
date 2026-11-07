# Meu Site v1.0.89 — Central de Integrações com janelas e ajudas completas

- Corrige o bug HTML das abas da Central de Integrações que permitia mostrar seções empilhadas.
- GA4, AdSense, Search Console e PageSpeed passam a abrir em pop-up central no desktop e em tela inteira no celular. Rolagem dentro da janela; cabeçalho e controles permanecem acessíveis.
- Pop-ups internos com Voltar para credenciais Google, OAuth AdSense, verificação por metatag, listas e relatórios, sugestões do PageSpeed, inspeção de indexação, histórico e botões `?`. Preserva formulários reais (não clonados) e fluxos CSRF.
- Quatro guias completos com links oficiais explicam onde encontrar IDs, como autorizar APIs, permissões e significado de resultados.
- Sem alterações de schema (36), dados privados, armazenamento de credenciais, integrações reais ou cotas. Nenhuma API é chamada automaticamente ao abrir uma janela.

**Pacotes:** `Atualizacao-v1.0.89.zip` (hospedagem) e `Meu-Site-v1.0.89-fonte-completo.zip` (GitHub Manager).

**Validação:** testes locais PHP/JS, sintaxe, CSS e manifestos ZIP. Teste visual em navegador Android e fluxos reais com hospedagem/Google continuam pendentes. O pacote v2 sem assinatura digital exige verificação de origem antes de instalar.
