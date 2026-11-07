# Validação IguaNews v1.0.9 (ambiente local)

- `php -l` em todo PHP: aprovado.
- `php tests/ads.php`: seis posições, URLs e identificadores verificados, datas válidas, extensões dos backups v1/v5/v6, checagem de integração e consentimento: aprovado.
- `php tests/smoke.php`, `bus.php`, `bus_import.php`, `bus_wizard.php`, `content.php`, `installer.php`, `portal_108.php`, `setup_diagnostics.php`: aprovados.
- `node --check` em scripts JS: aprovado.
- Integridade CRC do ZIP e conferência de caminhos: aprovado.
- **Não realizado:** migração em banco MariaDB real, testes de e-mail, anúncio AdSense real (depende de aprovação e ID real), interação browser em Android e campanha com redirecionamento em produção.
- O frontend e o banco são protegidos por padrão: publicidade desligada, vagas inativas e nenhuma tag AdSense externa antes de consentimento.
