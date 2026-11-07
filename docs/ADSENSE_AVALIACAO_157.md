# Preparação para avaliação Google AdSense — Meu Site 1.0.57

## Feito no código

- Página pública Sobre, formulário Contato, Privacidade e Termos disponíveis no rodapé.
- `<meta name="google-adsense-account" content="ca-pub-...">` é renderizada apenas se houver um ID válido informado em Publicidade; não carrega JS do Google nem presume aprovação.
- O administrador pode publicar o `ads.txt` da própria conta com CSRF, sem sobrescrever entradas de outras redes ou trocar conta Google existente. O arquivo não entra mais no pacote de atualização e é preservado.
- Vistoria local de configurações de domínio, indexação, notícias não demonstrativas e exemplos. Não afirma que o Google aprovou o portal.

## Itens obrigatoriamente revisados por humano

1. Terminar identidade e dados de contato verificáveis; não atribuir uma redação ou endereço que não existem.
2. Publicar matérias originais de valor local, com textos completos, títulos objetivos, datas, fontes confiáveis e autoria responsável; checar consentimento e direito de uso de fotos.
3. Remover de publicação materiais de demonstração, páginas incompletas e links vazios. Revisar menus, erros 404 e funcionamento no Android.
4. Ativar `seo_index` quando pronto, configurar `seo_site_url` em HTTPS, conferir sitemap/robots e acesso público sem login.
5. Confirmar que Privacidade/Termos correspondem à operação real, e que CMP certificada está instalada quando exigida antes de veicular anúncios; não supor que simples aceite local baste.
6. Em AdSense > Sites, adicionar o domínio, obter seu ID de editor real e selecionar a metatag ou outro método; testar com inspeção do código HTML.
7. Depois de configurar o editor, conferir `https://DOMINIO/ads.txt` e conteúdo fornecido pelo AdSense. Não copiar IDs de exemplo.
8. Solicitar análise no AdSense apenas quando o site tiver conteúdo suficiente e original; o Google decide a aprovação.

Documentação oficial: https://support.google.com/adsense/answer/12169212?hl=pt-BR e https://support.google.com/adsense/answer/1348695?hl=pt-BR.
