# IguaNews 1.0.17 | Fotos e upload

## Problema relatado

A capa exibia “Imagem inválida ou superior a 4 MB” até para PNG de 3 MB porque o PHP pode estar com `upload_max_filesize=2M`. O servidor preenchia `$_FILES['image']['error']=UPLOAD_ERR_INI_SIZE`, indistinguível no aviso antigo de uma foto inválida.

## Mudanças

- Diagnósticos específicos para limite por arquivo do PHP, envio parcial, diretório temporário, escrita, foto maior que 4 MB e `post_max_size` excedido. Mensagem legível antes da validação CSRF quando o corpo da requisição de publicação excede o limite total.
- Otimização no navegador de fotos grandes de capa e galeria: usa Canvas com dimensões de até 1600 px, converte a JPG, ajusta qualidade e mira até 1.450 KiB. Não modifica os arquivos originais do aparelho e não envia nada a terceiros. Processo pode falhar se navegador não suportar `createImageBitmap` ou `DataTransfer`, ou se dispositivo não tiver memória; a interface então pede JPG menor.
- Evita envio do formulário durante a conversão; mensagens de progresso e resultado. Sem JS, o upload normal permanece disponível, respeitando os limites do PHP.
- O total de arquivos continua limitado por `post_max_size`. Envie poucos anexos por vez, ou ajuste os limites do PHP.

### Termux (opcional, caso queira enviar fotos originais maiores)

Na pasta raiz do projeto:

```bash
php -d upload_max_filesize=8M -d post_max_size=32M -S 127.0.0.1:8000 -t public
```

A configuração precisa estar em vigor no **servidor web em execução**, não apenas no CLI de testes. Não conceder uploads executáveis nem remover validações de MIME.

## Validação

Testes de mensagem de upload + regressões PHP existentes e lint PHP/JS. A conversão real no navegador Android e o ciclo upload para MariaDB permanecem pendentes.

## Atualização

Preserve `storage/config.php`, `storage/backups/`, `storage/analytics.key`, `storage/media/`, `public/uploads/`. Sem alteração SQL ou backup v8.

**Jornalismo:** imagens de cenas recriadas por IA não são fotografias documentais do evento; identificar como ilustração quando usado e não atribuir a fotógrafo real.
