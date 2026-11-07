# Atualizações do Meu Site 1.0.54

## Uso

Na aba **Atualizações** o administrador vê versão instalada, versão disponível e status. O botão **?** explica cada área. O detalhamento de diagnóstico, instalação manual e origem fica recolhido quando não necessário.

- Instalar uma versão oficial: estar autenticado como `admin`, acessar com HTTPS válido, marcar a confirmação explícita e clicar em Instalar. O controlador geral valida a sessão/CSRF; o motor verifica assinatura Ed25519, hashes SHA-256 e revisão de esquema e prepara backups. Não solicita novamente a senha.
- Instalação por arquivo: abrir **Instalar ZIP assinado manualmente**, escolher `Atualizacao-v1.0.54.zip` e confirmar. NÃO usar `Meu-Site-v1.0.54-hospedagem.zip`.
- Restaurar uma versão de código: abrir **Histórico e recuperação > Restaurar arquivos**, informar a senha administrativa. O banco de dados não é revertido.
- Fonte GitHub: usa configuração salva no servidor, ou metadados oficiais em `app/update-origin.json` (`{"repository":"owner/repo"}`), ou a variável de ambiente `IGUA_UPDATE_REPOSITORY`. Se a origem do projeto foi confirmada por `git remote origin` no próprio checkout GitHub, o gerador pode embuti-la no ZIP assinado; sem dados confiáveis, mostrar formulário manual. Não assumir que a conta do administrador ou metadados de outro aplicativo correspondam ao repositório do portal.
- Tag GitHub Releases `vX.Y.Z`. Anexar pacote com nome `Atualizacao-vX.Y.Z.zip`. Atualizador aceita ainda `Meu-Site-update-vX.Y.Z.zip` para compatibilidade. Repositórios privados não podem ser consultados sem token, o servidor não usa sua sessão GitHub automaticamente.

## Limites e segurança

Não habilita atualização automática em segundo plano. Permanece a verificação sob demanda/com cache de 30 minutos. Backups de SQL não equivalem a restauração testada. Migrações de esquema são bloqueadas. Fotos, credenciais, diretório `storage/` e backups não são substituídos.

Não utilizar os arquivos `github-manager.json` / `app_identity.json` deste pacote PHP para descobrir o repositório: eles contêm metadados de outro aplicativo. A aplicação não tem como descobrir com segurança o repositório pessoal correto sem uma associação validada.

## Validações

`php tests/updater_154.php`, `php tests/updater_153.php`, `php tests/updater_152.php`, `php tests/smoke.php` e verificação `php -l` nos arquivos PHP. Instalação com PHP-FPM, MariaDB e HTTPS reais ainda depende de teste na hospedagem.
