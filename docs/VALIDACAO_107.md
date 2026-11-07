# Validação técnica IguaNews 1.0.7

- `php -l`: todos os arquivos PHP aprovados.
- `node --check`: scripts JavaScript aprovados.
- `php tests/bus_wizard.php`: offsets por horário local, travessia da meia-noite, tarifa, dia Hoje/Amanhã/Em N dias e origem padrão.
- `php tests/bus.php`: sem regressões no cálculo de partidas.
- `php tests/bus_import.php`, `tests/smoke.php`, `tests/content.php`, `tests/installer.php`, `tests/setup_diagnostics.php`: aprovados, incluindo backups antigos v1–v4 e formato v5.
- ZIP: integridade CRC checada.
- **Pendente:** PHP/PDO MySQL não instalado no ambiente de testes; validar migração `fare_amount`, salvar via wizard e restaurar backup v5 contra MariaDB real no Termux. Navegador Chromium local não completou captura de tela neste ambiente; conferir aparência e botões em um telefone.
- O módulo nunca inclui horários reais inventados ou rastreamento GPS.
