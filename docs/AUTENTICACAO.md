# Login administrativo — 1.0.40

## Experiência
- Campo de senha oculto ao abrir e botão acessível Mostrar/Ocultar.
- O navegador decide sobre salvar as credenciais, com `autocomplete` padrão.
- Checkbox **Manter conectado por até 7 dias neste dispositivo** desligado por padrão.

## Segurança
- Chave aleatória de 256 bits, vinculada a seletor aleatório; somente SHA-256 do validador armazenado no banco.
- Cookie `HttpOnly`/`SameSite=Lax`, `Secure` em HTTPS. Em HTTP, apenas conexões loopback do próprio Termux são admitidas.
- Token é validado com `hash_equals`, renovado por rotação após reautenticação, sem prorrogar validade absoluta (sete dias).
- Logout revoga o token atual, alteração de senha e desativação revogam todos os tokens do usuário.
- Backup não inclui tokens, mantendo o formato de backup v10 e mídia ZIP v2.
- Uma cópia de arquivo PHP por si só não ativa um login persistente: exige migração de banco revisão 34 com CREATE TABLE e HTTPS público.

## Verificação
`php tests/remember_login_140.php`, `node tests/admin_login_140.js`, `python3 tools/build_css.py --check`. Requer teste funcional em MariaDB/Android para validar cookies reais. O limitador de tentativas de senha existente é por sessão e pode ser contornado por novas sessões; fortalecer em futuro desenvolvimento público.
