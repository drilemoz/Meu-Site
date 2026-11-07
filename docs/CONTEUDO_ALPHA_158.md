# Meu Site 1.0.58 — Manutenção Alpha, acervo de demonstração e reinstalação

## Comportamento do aviso

- O aviso genérico **Acervo de referência** não aparece mais em todas as páginas.
- Nas páginas individuais de matérias com `articles.is_demo=1`, permanece uma identificação específica (fictícia ou resumo de referência), próxima do título e corpo.
- Notícias sem `is_demo` não recebem esse aviso. Cards e listas podem indicar "Exemplo" somente junto à própria matéria.
- `noindex`, bloqueio de AdSense com demonstrações publicadas e exclusão das matérias de exemplo do sitemap **permanecem**, independentemente da exibição do aviso. Evitar confundir aparência pública com liberação editorial.

## Nova instalação

1. O instalador cria as categorias iniciais mesmo que não sejam carregadas matérias de demonstração.
2. A opção de 100 notícias de exemplo é **opt-in**, desmarcada por padrão.
3. O carregamento de amostras não executa `CREATE` / `ALTER` dentro da transação de criação do administrador. `install_schema` deve migrar a estrutura antes.
4. Se um banco possui instalação recuperável, `setup.php` pode autenticar o administrador anterior e, após confirmação `REINSTALAR`, fazer backup lógico e reiniciar a mesma base de testes. Bancos com tabelas desconhecidas são bloqueados.
5. Se a instalação já estiver ativa, o acesso a `setup.php` continua redirecionando ao painel; use a tela de Manutenção para reiniciar, sob sessão administrativa.

## Administração → Configurações → Manutenção

### Remover demonstrações

- Requer administrador autenticado, CSRF e confirmação textual `REMOVER EXEMPLOS`.
- Gera backup lógico privado **antes** de apagar dados.
- Exclui somente `articles.is_demo=1`, rascunhos associados e filhos com FKs `ON DELETE CASCADE`.
- Não remove artigos reais, categorias, fontes, contas, mídia física, biblioteca de mídia, horários de ônibus, publicidade nem configurações.

### Limpar conteúdo editorial

- Requer confirmação `LIMPAR NOTICIAS` e backup lógico.
- Exclui artigos e rascunhos de todos os tipos; mantém as demais tabelas e arquivos físicos.

### Reinstalar do zero (Alpha)

- Requer **senha do administrador e texto `REINSTALAR`** além de CSRF.
- Só é permitido com as **25 tabelas reconhecidas**, sem estruturas desconhecidas, e quando a configuração privada puder ser removida com segurança. Variáveis `IGUANEWS_DB_NAME` bloqueiam reinicialização via interface.
- Gera backup lógico e depois remove as tabelas do próprio portal; em seguida exclui `storage/config.php`, encerra a sessão e retorna ao instalador.
- **DDL MariaDB não é transacional**. Se falhar no meio, pode ser necessário recuperar o backup e corrigir a base pelo provedor. Teste em uma base sem conteúdo importante.
- **Não deleta arquivos do disco** (fotografias, mídias, backups, logs). O backup lógico JSON **não inclui binários de uploads**; faça cópia separada antes de reinstalar em outra hospedagem.
- O botão não cria um nome de banco novo nem credenciais novas; permite reutilizar a mesma base MySQL.

## Testes e limites

- Execute `php tests/content_maintenance_158.php` e todas as regressões PHP/JS.
- Validar manualmente em ambiente de teste com PHP-FPM + MariaDB: transações, FK em cascata, backup pré-exclusão, verificação de login, recuperação e indexação do portal.
- A atualização de código da 1.0.57 para a 1.0.58 não executa limpeza. Todas as operações destrutivas exigem ação administrativa explícita.
- Não há alteração da revisão SQL (36); nenhum upgrade de banco é necessário para instalar a 1.0.58.
