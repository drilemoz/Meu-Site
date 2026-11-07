# Atualizações do Meu Site 1.0.62 — sem chave de assinatura

## Mudança de formato

O pacote de atualização usa `release.json` no formato `meu-site-update-v2`, contendo a versão, PHP mínimo, revisão de schema e lista de caminhos permitidos com tamanho/sha256. Não requer mais `release.sig`. Se um pacote v2 contiver `release.sig`, **a assinatura deve ser válida**. Pacotes legados `meu-site-update-v1` continuam exigindo `release.sig` Ed25519; não é permitido retirá-la.

SHA-256 apenas confirma que os arquivos extraídos coincidem com o manifesto incluído no mesmo ZIP. Quem produz um ZIP malicioso consegue também calcular novos hashes; **não é prova de origem ou segurança**. Usar somente ZIP próprio ou recebido de fonte verificada, HTTPS, acesso exclusivo ao admin, senha forte e autenticação em dois fatores quando disponível na hospedagem.

## Uso normal a partir de 1.0.62

1. Executar `python3 tools/build_update_package.py --output /tmp/Atualizacao-v1.0.62.zip` na raiz do código; não precisa de `--key-file`. O nome deve seguir a versão do manifesto.
2. Acessar **Admin → Configurações → Atualizações**. Escolher upload do ZIP v2, ou publicar uma GitHub Release com tag `vX.Y.Z` e arquivo `Atualizacao-vX.Y.Z.zip` e clicar no botão de instalação.
3. Conferir a procedência do ZIP, marcar confirmação, informar **a senha administrativa atual** e instalar. O backend verifica sessão, ACL, CSRF, senha, manifesto, hashes, caminhos, versão estritamente superior, mesma revisão MySQL, preflight, backup de código e do banco e executa rollback se necessário.
4. O instalador não modifica `storage/`, `public/uploads/` nem `public/ads.txt`. Não aplica alterações de revisão de banco; requer outro procedimento assistido.

## Migração única desde 1.0.61 ou 1.0.60

O atualizador antigo exige assinatura Ed25519 e **não aceita** o novo `Atualizacao-v1.0.62.zip`. Para instalar 1.0.62 pela primeira vez:

1. Faça backup completo dos arquivos e do banco pelo painel/hospedagem. Confirme que consegue recuperar os backups.
2. Na hospedagem, identifique a raiz real do projeto que contém `app/`, `public/`, `storage/` e `VERSION`. Não confunda com a pasta pública `httpdocs`/`public_html` quando a configuração separa diretórios.
3. Use `Meu-Site-v1.0.62-instalar-manualmente.zip` **apenas nessa raiz**, mesclando/substituindo `app/`, `public/` (somente caminhos presentes no ZIP) e `VERSION`. Não apagar pastas antigas e **não** usar opção de substituir pasta inteira. O ZIP não inclui `storage/`, `public/uploads/`, `public/ads.txt`, `config.php`, imagens privadas nem banco.
4. Confirme a nova versão no rodapé e em Admin → Atualizações; teste navegação, administração e uploads. Se o PHP tiver OPcache, invalide-o pelo painel/reinício PHP-FPM ou aguarde a invalidação configurada.
5. Somente para atualizações **posteriores** use os ZIPs `Atualizacao-vX.Y.Z.zip` pelo novo painel (v1.0.62 ou mais recente).

Não envie o ZIP completo do código-fonte no campo Atualizações; ele contém materiais de desenvolvimento sem `release.json` na raiz. O pacote manual não deve ser usado no campo Atualizações.

## Limitações

Validações locais de PHP/manifesto e estrutura ZIP não substituem uma instalação em PHP-FPM + MySQL/MariaDB reais. O upload é limitado a 32 MiB pelo código e também pelos parâmetros de PHP. Requer extensão ZipArchive. A assinatura opcional de pacotes novos, quando usada, depende da chave privada correspondente à chave pública embutida no programa; uma chave nova exige uma troca deliberada do certificado confiável.
