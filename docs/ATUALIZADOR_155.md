# Atualizador 1.0.55 — histórico e auditoria

## Novidades
- `igua_updater_history(6)`: lê até seis jobs recentes do diretório privado sem mostrar segredos, sem modificar arquivos e sem SQL.
- `igua_updater_installed_integrity()`: compara hashes SHA-256 locais com os hashes armazenados no journal da última atualização concluída; se a versão instalada mudou fora do atualizador, mostra aviso de que o diagnóstico não é conclusivo.
- `igua_updater_file_changes()`: na simulação do ZIP assinado, conta arquivos novos, alterados e iguais, com amostra limitada de caminhos, sem modificar os arquivos.
- Interface: relatórios recolhidos por padrão para preservar o layout mobile compacto.

## Limites importantes
- Arquivos diferentes na simulação são esperados ao atualizar; não são prova de adulteração.
- O teste de integridade verifica apenas arquivos presentes no último pacote, não conteúdos criados por usuários nem toda a instalação.
- Histórico é somente leitura e não habilita rollback para versões arbitrárias. Apenas a última atualização pode ser restaurada pelo mecanismo existente, sujeita às verificações de hashes e autenticação.
- As rotinas não fazem backup nem restauração de banco. A instalação continua criando o backup como antes.
- Verificação HTTP/ZipArchive e rollback reais na hospedagem permanecem pendentes até testes em servidor.
