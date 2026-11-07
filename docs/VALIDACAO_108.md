# Validação IguaNews 1.0.8

- Syntax check com `php -l` em todos os arquivos PHP.
- Testes isolados originais em `tests/*.php`.
- Teste adicional `tests/portal_108.php`: duração de viagem, virada de dia, ausência de horário, importação DER em TXT/tabulação com preâmbulo, links institucionais e ausência do e-mail no frontend.
- `node --check` em todos os JavaScripts.
- ZIP testado por CRC, sem credenciais de acesso.

## Validações ainda necessárias em Termux/hospedagem

- Teste visual em Android e operações com MariaDB real; ambiente de build não possui `pdo_mysql`.
- Importação do arquivo TXT exato baixado pelo usuário no DER-MG; o layout oficial da publicação é conhecido, mas o arquivo individual não foi fornecido para teste.
- Busca de cidades pela API IBGE em rede real (neste ambiente há bloqueio de DNS externo).
- Entrega efetiva de e-mail com SMTP/PHPMailer depois de instalação via Composer, provedor e credenciais configurados.
