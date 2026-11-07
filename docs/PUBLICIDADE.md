**Atualização 1.0.59:** para o novo painel compacto e limpeza de anúncios de demonstração, consulte [PUBLICIDADE_159.md](PUBLICIDADE_159.md).

## Exemplos de demonstração

Na versão 1.0.27, o painel de Publicidade passou a oferecer um botão **Cadastrar 6 anúncios de exemplo**. Ele cria um backup preventivo, ativa o módulo geral, configura as posições P01 a P06 para **Anúncios locais** e cadastra uma campanha fictícia para cada espaço.

- Os exemplos são marcados como **EXEMPLO FICTÍCIO** e usam URLs em `example.com`, apenas para validação visual.
- Reexecutar o botão recria somente as campanhas de demonstração e preserva campanhas reais do usuário.
- As campanhas de exemplo não usam HTML arbitrário nem dependem de imagens; servem para testar a central, a listagem, a home, as matérias e o módulo de ônibus.

# IguaNews 1.0.9: publicidade

## Ativação segura

Acesse Admin > Publicidade. O módulo, as posições e as campanhas começam **desligados**. Somente administradores podem alterá-los. As áreas desativadas não deixam lacunas nem aparecem para o público.

1. Para publicidade local, cadastre a empresa, o título, a descrição, a imagem (opcional), o destino HTTPS, a data inicial, a data final (opcional) e a prioridade (0 a 100). Marque Ativa.
2. Na posição desejada, selecione **Anúncios locais**, habilite e salve.
3. Ative a publicidade geral no topo. Não há anúncio sem esses três critérios. Quando várias campanhas na mesma posição têm a maior prioridade, o sistema alterna a escolhida por dia, sem perfis pessoais ou cookies.
4. Para pausar, desligue a posição, campanha ou chave geral. O contador de cliques é agregado e aproximado; não é uma plataforma antifraude nem de faturamento.

## As seis posições

- P01: abaixo do principal destaque na página inicial.
- P02: após as quatro notícias recentes na página inicial.
- P03: antes do rodapé do portal.
- P04: após o segundo parágrafo das matérias (ou no final de matérias curtas).
- P05: na caixa de mais lidas do desktop, empilhado no celular.
- P06: abaixo das saídas seguintes no módulo de ônibus.

Evitar anúncios entre o horário e o contador de ônibus; não simular botões de notícia.

## Google AdSense

1. Crie uma conta e aguarde aprovação no AdSense. Na aba geral informe o identificador **ca-pub-1234567890123456**, usando seu valor real.
2. Para cada espaço selecione AdSense e digite o ID numérico do bloco. Habilite a posição e a chave geral.
3. Em ambiente de desenvolvimento, indexação desativada ou com notícias de demonstração publicadas, o IguaNews impede carregamento de anúncios Google.
4. O visitante escolhe permitir ou não antes de carregar a biblioteca remota do Google. Uma preferência é guardada no navegador localmente. O link de preferências fica no rodapé quando há blocos AdSense habilitados.
5. Para publicação, atualize `public/ads.txt` com a linha exibida no painel após conferir a configuração fornecida pelo AdSense. Não acrescente um ID fictício.
6. **Consentimento e conformidade:** esse mecanismo simples de escolha NÃO substitui uma plataforma de gerenciamento de consentimento certificada (CMP), exigida pelo Google em certos territórios e cenários. Confira regras atuais, LGPD e políticas antes de ativar. Nenhuma garantia de aprovação/receita.

## Outras redes

O projeto possui o provedor `external` e um campo para adaptadores específicos. Ele **não veicula** nem executa código desconhecido do administrador. Para Media.net, Google Ad Manager, Ezoic ou outras redes, implemente um adaptador próprio conforme a documentação oficial e o contrato do provedor, evitando combinações de tags incompatíveis. A interface recusa ativar uma posição `external` sem esse desenvolvimento.

## Privacidade e segurança

- Credenciais SMTP e dados sensíveis nunca devem ser incluídos nas tags de anúncio.
- Anúncios locais têm links patrocinados com redirecionamento pela rota `/ad_click.php`; nela se incrementa um contador agregado, sem gravar endereços IP, identificadores pessoais ou cookies próprios.
- Não são permitidos HTML/JS arbitrários, banners executáveis, upload de SVG, ou inserção de JavaScript externo em campos administrativos.
- Use as fontes e URLs reais dos anunciantes. Confirme licenças de imagens.
- Backups v6 incluem posicionamentos e campanhas, mas arquivos de imagem continuam no diretório `public/uploads/` e precisam ser copiados separadamente.

## Migração

Na versão 1.0.9, a rotina `migrate_ads_schema` cria as tabelas `ad_placements` e `ad_campaigns`, e preenche posições desligadas sem interferir nas notícias, ônibus ou configuração existente. Backups v1–v5 podem ser lidos; o v6 contém as tabelas novas.
