# Validação técnica — IguaNews 1.0.30

**Escopo:** refatoração do controlador administrativo e separação de responsabilidades sem alterar URLs, telas ou esquema.

**Validações locais automatizadas:** as sintaxes PHP/JS, as suítes de testes PHP/Node, o teste HTTP de roteamento e a integridade CRC do ZIP foram executados: 76 sintaxes PHP válidas, 14 sintaxes JavaScript válidas, 27 suítes PHP, 5 suítes JavaScript e 8 cenários HTTP locais. A integridade do pacote ZIP foi checada após sua geração.

**Limitação:** sem MariaDB real e navegador Android neste ambiente, não é possível afirmar que as alterações foram testadas fim a fim. Verificar login, publicação rápida, upload, lixeira, configurações, backup/restauração e permissões dos papéis na instalação de teste antes de produção.

**Guia:** `docs/ARQUITETURA_ADMIN.md`.
