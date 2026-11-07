# Validação da v1.0.50

- PHP lint de todos os arquivos em `app/`, `public/`, `tests/`, `tools/`, `bin/`.
- Execução de todas as suítes `tests/*.php` e `tests/*.js`.
- `tools/build_css.py --check`, `tools/build_news_editor.py --check`.
- Upload HTTP real (`tools/test_chunked_upload_150.py --full`) sob `php -d upload_max_filesize=2M -d post_max_size=8M`: 15.728.640 bytes recebidos em 30 partes de 524.288 bytes, arquivo reconstituído e SHA-256 conferido.
- Não testado: instalação fim a fim com MariaDB real, alojamento web comercial específico e uso no aparelho Android com backup real.
- Não modificado: revisão SQL 36, backup JSON v11 e mídias ZIP v2.
