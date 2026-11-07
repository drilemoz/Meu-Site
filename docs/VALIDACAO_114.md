# Validação do IguaNews 1.0.14

## Implementado
- Diagnósticos administrativos de ambiente, banco, diretórios, backup e dependências.
- Log restrito e resumido de falhas fatais e operações internas.
- Verificação manual do backup, sem restauração.
- Gravação atômica do backup e ordenação temporal corrigida.
- Script de backup agendado agora valida antes da rotação.

## Testes locais
- `php -l` em todos os PHP.
- `php tests/*.php`, inclusive novo `tests/health.php`.
- `node --check` em todos os JS.
- Verificação estrutural/CRC do ZIP.

## Limitações
- Sem driver `pdo_mysql` ou servidor MariaDB no ambiente de empacotamento: falta teste integrado do banco e de backup real.
- Sem SMTP/GeoLite2 reais ou navegador Android conectado: indicadores são verificações de configuração, não prova de operação.
- A tela da Central de Saúde requer conferência de aparência no dispositivo.
