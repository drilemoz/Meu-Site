# IguaNews 1.0.36 · Instalação e identidade

## Escopo
- No passo 3 de instalação limpa, nome (2–80), frase (até 200), descrição (15–320) e cidade (até 100). Entrada HTML escapada, prévia em texto puro.
- Definições persistidas em `settings` junto ao usuário administrador, na transação de instalação. Para restauração por backup, identidade do backup é preservada.
- `app/branding.php` fornece marca, descrição e versão; contatos fixos do desenvolvedor só no código.
- SEO `%site%`, título legado `%title% | IguaNews` normalizado em renderização; metadados Open Graph e publisher JSON-LD dinâmicos.
- Rodapé com diálogo `<dialog>` que abre por botão, fecha por Escape (nativo), botão ou clique fora; telefone `tel:` e e-mail `mailto:`.
- Sem migrações nem alteração nos formatos de backup.

## Como testar no Android / Termux
1. Em instalação limpa com banco vazio, visite `/setup.php` e preencha nome `Viajando por Aí`, frase e descrição. Veja a prévia mudar.
2. Finalize a instalação, acesse páginas públicas, artigo, ônibus, contato, painel e login. Confirme a mesma marca.
3. Veja o rodapé (role até o fim no celular). Clique em **Desenvolvido por Adriedson Lemos**, selecione e-mail ou telefone e feche o pop-up.
4. Alterar nome em Admin → Configurações e conferir `<title>`, `og:site_name` e rodapé; editar sem permitir tags HTML.
5. Restaurar um backup antigo em **instalação isolada**, nunca banco de produção: a marca do backup deve ser mantida.

## Limitações
A integração MariaDB real e teste visual em dispositivos Android requerem execução externa. Contatos são imutáveis apenas pela interface; quem modifica o código PHP pode alterá-los.
