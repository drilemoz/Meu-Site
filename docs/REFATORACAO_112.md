# Refatoração da Central de Integrações — 1.0.112

## Escopo

A versão 1.0.111 possuía `app/admin_views/routes/integrations.php` com 500 linhas e cerca de 69 KB. A divisão na 1.0.112 mantém o ponto de entrada e distribui o conteúdo em nove templates PHP estáticos:

| Arquivo | Responsabilidade |
|---|---|
| `integrations/ga4.php` | Medição, consulta GA4 e credencial da conta de serviço |
| `integrations/adsense.php` | Identificador, relatório, autorização OAuth e desconexão |
| `integrations/search_console.php` | Relatórios da busca, verificação DNS e metatag |
| `integrations/pagespeed.php` | Métricas Lighthouse por dispositivo |
| `integrations/gemini.php` | Configuração, teste e remoção do Gemini |
| `integrations/diagnostics.php` | Estados das integrações e histórico local da sessão |
| `integrations/page_inspector.php` | Diagnóstico de URLs e consulta de indexação |
| `integrations/catalog.php` | Catálogo de serviços futuros |
| `integrations/dialogs.php` | Diálogos reutilizados pelo JavaScript |

Os `require` têm caminhos estáticos baseados em `__DIR__`, sem entradas de usuário. Os componentes herdam o escopo do template principal. Nenhum formulário, controle, rota POST ou ação de publicação é alterado.

## Validação

- `tests/support/integrations_source.php` recompõe os nove trechos para as verificações estáticas antigas, sem executar código PHP.
- `tests/integrations_refactor_212.php` valida por SHA-256 que a recomposição corresponde exatamente ao template da 1.0.111. Alterações futuras intencionais no template precisarão revisar esse contrato.
- Continuam obrigatórios a suíte PHP/JavaScript, o lint PHP, os geradores CSS/editor em modo `--check`, a auditoria do projeto e a verificação dos ZIPs.
- O teste das APIs externas e da interface na hospedagem não é comprovado pela comparação do texto-fonte: validar em sessão de administrador no Plesk antes de considerar o deploy aprovado.

## Segurança e atualização

Sem migração de banco: schema 37 e backup v12 preservados. Não alterar `storage/`, uploads e credenciais. O ZIP de atualização contém os novos templates no mesmo caminho `app/admin_views/routes/integrations/` e substitui o arquivo principal. Instalação em lote e backup prévio são recomendados para evitar estados com template novo e parciais ausentes.
