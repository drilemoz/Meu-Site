# Validação IguaNews 1.0.11

- PHP `php -l` em todos os arquivos de app/, public/, tests/ e bin/: aprovado.
- Testes PHP isolados (incluindo `tests/analytics.php`, classificadores de bots, privacidade, URLs sanitizadas, compatibilidade de backups v1-v8): aprovado.
- JavaScript verificado com Node `--check`.
- ZIP completo: estrutura e CRC verificados.
- **Pendente:** teste real com MySQL/MariaDB da migração, inserção de eventos, painel gráfico e restauração v8. O ambiente local não tem `pdo_mysql` nem servidor MariaDB.
- **Pendente:** teste em navegador Android e geolocalização real por GeoLite2, que requer download/licença do usuário.

As métricas são estimativas; rastreamento não deve ser apresentado como dados absolutos ou identificação verificada de robôs.
