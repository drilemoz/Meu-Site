# Validação técnica · IguaNews 1.0.6

- `php -l` em todos os PHP: passou.
- `php tests/bus.php`: aprovado para offsets manuais, cronologia e dias.
- `php tests/bus_import.php`: aprovado com amostras estruturadas no formato DER-MG e ANTT, direções, máscara semanal, filtros e duração desconhecida.
- `php tests/smoke.php`: aprovado, incluindo leitura dos backups v1 a v4.
- `php tests/content.php`, `php tests/installer.php`, `php tests/setup_diagnostics.php`: aprovados.
- Teste com arquivos **reais baixados no Termux** e servidor MariaDB real: pendente. O ambiente de geração não oferece `pdo_mysql` nem acesso externo para baixar o CSV/TXT completo.
- A conexão automática da ANTT usa recurso oficial fixo de setembro/2026 com cURL, certificado SSL validado, sem seguir redirecionamentos e limite de 12 MiB. Se falhar, envie o CSV salvo no painel.
- DER-MG: integração por arquivo TXT do quadro intermunicipal baixado pelo administrador no portal oficial. Não há endpoint direto verificado para atualização automática.
- Itinerários com formato ambíguo são ignorados, e horários de chegada desconhecidos ficam em NULL. A fonte enviada por upload não tem autenticidade conferida automaticamente.
- Nunca divulgar horários importados sem aprovação manual. O modo FIXO protege as alterações locais da importação.
