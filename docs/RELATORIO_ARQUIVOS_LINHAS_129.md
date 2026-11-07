# IguaNews 1.0.29: alterações, inventário e validações

## Escopo

Refatoração específica da Biblioteca de Mídias e correção de defeitos da v1.0.28. Base: ZIP 1.0.28.

## Mudanças em relação à 1.0.28

| Arquivo | Tipo | Antes (linhas) | Depois (linhas) | Adições | Remoções |
|---|---|---:|---:|---:|---:|
| AGENTS.md | alterado | 67 | 69 | 2 | 0 |
| CHANGELOG.md | alterado | 222 | 232 | 10 | 0 |
| README.md | alterado | 325 | 336 | 12 | 1 |
| VERSION | alterado | 1 | 1 | 1 | 1 |
| app/media_admin.php | alterado | 19 | 19 | 1 | 1 |
| docs/VALIDACAO_129.md | novo | 0 | 22 | 22 | 0 |
| public/admin.php | alterado | 560 | 560 | 1 | 1 |
| public/assets/js/media-library.js | alterado | 109 | 139 | 49 | 19 |
| public/assets/js/media-state.js | novo | 0 | 63 | 63 | 0 |
| tests/media_state_129.js | novo | 0 | 65 | 65 | 0 |

**Arquivos de runtime privados excluídos do ZIP:** `storage/logs/health.log`, `storage/logs/.lock`. Não alterar logs reais da instalação ao atualizar.

## Inventário

- Arquivos de código/documentação inventariados: 167 (antes dos dois relatórios 1.0.29).
- CSV completo na raiz do relatório de linhas, com tamanho e SHA-256 de cada arquivo antes da inclusão dos relatórios.
- Mudanças/novos arquivos contra a versão 1.0.28: 10.

## Testes

- Sintaxe PHP: 65 arquivos sem erros.
- Sintaxe JavaScript: 14 arquivos sem erros.
- Testes PHP: 26 suítes aprovadas.
- Testes Node.js: 5 suítes aprovadas, inclusive concorrência de busca e reenvio parcial.
- Teste HTTP do roteador: 8 cenários aprovados.
- ZIP: integridade CRC verificada após empacotamento.

## Pendências

- Integração do módulo com MariaDB real e fotos reais no Termux/Android.
- Em desconexão após gravação mas antes da confirmação HTTP, não é possível garantir deduplicação: conferir a biblioteca antes de repetir.
- Não houve migração SQL; backup lógico v10 e pacote de mídia v2 preservados.
