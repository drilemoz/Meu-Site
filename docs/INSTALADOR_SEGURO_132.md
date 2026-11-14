# Instalador seguro — 1.0.132

## Antes de começar

**Instalação limpa:** na raiz do projeto, pelo terminal da hospedagem, execute `php bin/setup-access.php`. Guarde a chave de 48 caracteres hexadecimais; depois abra `https://seu-dominio/setup.php` e informe-a. Se não houver terminal, crie manualmente um arquivo privado `storage/.setup-access.key` com uma chave criptograficamente aleatória de 24 bytes em hexadecimal (48 caracteres), obtida por ferramenta confiável fora do navegador. Nunca divulgue a chave ou a coloque em `public/`.

**Migração portátil:** gere novamente o ZIP portátil na versão 1.0.132. Ele inclui `storage/.setup-access.key` com uma chave nova e aleatória. Depois de extrair o ZIP na hospedagem nova, consulte a chave no gerenciador de arquivos/terminal e use-a para destravar `/setup.php`. O ZIP com dados e a chave é confidencial e não deve ser publicado na pasta acessível por URL.

O desbloqueio expira em 30 minutos. Em hospedagem pública, TLS (HTTPS) entre navegador e instalador é obrigatório. Depois de instalar, a chave de bootstrap é removida; o instalador fica inacessível quando `storage/config.php` existe.

## Banco MySQL: modos e limites

- **Conectar a banco existente:** usa as credenciais do aplicativo em banco vazio; verifica permissões essenciais com uma tabela de diagnóstico criada e removida. Não remove tabelas reais.
- **Criar banco simples:** usa uma conta com `CREATE DATABASE`; informe um usuário de aplicação diferente já autorizado no banco. Como o instalador não concede GRANT nesse modo, pode ser necessário ajustar os privilégios no provedor. Se for inevitável reutilizar a conta administrativa, marque a confirmação explícita sabendo que ela poderá reter privilégios amplos.
- **Criar banco e usuário exclusivo:** apenas MariaDB/MySQL local em VPS/Termux; gera credenciais aleatórias, concede acesso limitado ao banco e nunca salva as credenciais administrativas. Se CREATE DATABASE/CREATE USER/GRANT falhar parcialmente, nenhum objeto é automaticamente excluído: o instalador exibe os objetos confirmados para recuperação manual.

## TLS para bancos remotos

Em banco remoto, escolha TLS autenticado e coloque o certificado da autoridade certificadora do MySQL em `storage/mysql-ca.pem` (nunca em `public/`), com permissão restrita. A opção exige suporte de PDO MySQL ao SSL e verificação do certificado do servidor. Se a hospedagem não oferecer TLS, use somente rede privada e confirme expressamente essa condição; HTTPS do navegador não protege o trecho PHP → MySQL. A CA NÃO entra no ZIP portátil e deverá ser instalada também na nova hospedagem.

## Testes e limites

A verificação de privilégios usa uma tabela nova e aleatória em um banco **completamente vazio** para testar operações SQL comuns. Em caso de falha de DROP, a instalação é bloqueada e informa o nome da tabela residual. O teste não garante recursos além dos testados nem substitui integração real. O workflow em `.github/workflows/integration-mariadb.yml` testa o banco MariaDB isolado, mas só vale após execução bem-sucedida. Nenhuma etapa migra automaticamente DNS, certificado HTTPS, arquivos de CA, cron ou segredos externos.
