# Publicidade — v1.0.59

## Nova estrutura do painel

- **+ Nova campanha**: escolha **Anúncio local** (empresa, título, posição, destino HTTPS, datas; imagem/descrição/prioridade ficam em Opções) ou **Bloco Google AdSense** (escolha posição e ID do bloco, após configurar `ca-pub` real).
- **Posições P01–P06**: cada linha abre uma janela com posição, provedor, ativação e ID do bloco. O site permite uma configuração ativa de provedor por posição. Quando desligadas, não ocupam espaço público.
- **Campanhas**: lista expansível sem tabela horizontal, com ações Editar, Pausar/Ativar e Excluir. Edição abre janela com dados preenchidos. Em caso de validação rejeitada, o formulário mantém os campos digitados, exceto uploads que devem ser selecionados novamente.
- **⚙ Configurações**: ativação geral, editor `ca-pub`, vistoria AdSense, ads.txt, criação/removal de anúncios fictícios.

## Ocupação e revezamento

A publicidade local usa a campanha ativa com maior prioridade. Havendo empate, a distribuição diária preexistente é mantida. Ao cadastrar/editar campanha local como ativa em data coincidente com outra ativa no mesmo espaço, marque explicitamente **Confirmo o revezamento**; se não marcar, o servidor rejeita a ativação sem modificar o banco. Projetos legados com mais de uma campanha coexistente continuam sendo exibidos como antes, sem mudança automática.

Se a posição está desativada, pode-se optar por prepará-la para publicidade local no formulário. Posições AdSense ativas só podem mudar de provedor em seu próprio gerenciador. Não é possível ativar AdSense sobre campanha local ativa: pause-a primeiro. O site não ativa a publicidade geral automaticamente; isso continua em ⚙.

## Limpeza Alpha dos anúncios demonstrativos

- Em **Publicidade → ⚙ → Testes** ou **Configurações → Manutenção**: confira a contagem, digite `REMOVER ANUNCIOS`, confirme a ação e aguarde conclusão do backup.
- O SQL exclui apenas anúncios com `target_url` começando com `https://example.com/iguanews-demo/`. Anúncios reais têm URLs diferentes e não são removidos.
- Uma posição configurada como local só é desabilitada se não tiver nenhuma campanha remanescente (inclusive campanhas pausadas). AdSense não é alterado; imagens físicas permanecem no servidor.
- Remover notícias fictícias **não** limpa publicidade fictícia; ambas são operações separadas.
- Recriar exemplos requer senha e só funciona se não houver campanhas reais nem posições AdSense/outras redes já ativas, para não alterar publicidade contratada.

## Segurança e validação

Painel restrito ao administrador/`manage_settings`, CSRF, validação PHP dos campos, transações e locks de posição/campanhas ao salvar, sem aceitar HTML externo. Backup lógico é obrigatório antes da limpeza. Nenhuma operação de exclusão é executada durante upgrade. O cadastro do Google e a revisão AdSense ainda exigem aprovação e obrigações de consentimento próprias.

Verificações locais: `php tests/ads_admin_159.php`, testes PHP em `tests/*.php`, `node --check public/assets/js/ads-admin.js`, `python3 tools/build_css.py --check`. Nenhuma validação local substitui integração MariaDB e teste visual no celular.
