<?php
declare(strict_types=1);
/**
 * Exemplos editoriais baseados na implementação pública do Uai Cortes (0.1.219).
 * NUNCA publicar automaticamente: tópicos de retenção/revogação exigem validação
 * do responsável. Não são termos jurídicos certificados.
 */
function igua_uai_legal_examples(): array {
    return [
        'home'=>[
            'title'=>'Uai Cortes — editor de vídeos Android',
            'seo'=>'Conheça o Uai Cortes, editor de vídeos Android com cortes, legendas, narração e integração opcional com YouTube e outras plataformas.',
            'body'=><<<'MD'
# Uai Cortes

**Edite vídeos, crie conteúdo e prepare publicações pelo Android.**

O Uai Cortes é um aplicativo de edição de vídeos para Android, desenvolvido por Adriedson Aparecido Lemos. Ele reúne ferramentas para criar, organizar e exportar conteúdo audiovisual.

## Funcionalidades

- **Edição de vídeos:** cortes, divisão de clipes, transições e ajustes de áudio.
- **Legendas:** criação, importação, estilos e animações.
- **Narração e IA:** recursos opcionais para voz e processamento de conteúdo com serviços externos.
- **Projetos:** armazenamento e recuperação de trabalhos para continuar editando.
- **Publicação:** exportação para o aparelho, compartilhamento Android e integrações de redes sociais quando disponíveis.

## Integração opcional com YouTube

O aplicativo utiliza a YouTube Data API para consultar o canal autorizado, obter informações de vídeos e enviar vídeos escolhidos pelo usuário. A conexão exige autorização Google e depende das permissões concedidas. O aplicativo não é um produto oficial do Google ou do YouTube.

## Privacidade e condições

[Política de Privacidade](/uaicortes/privacidade)

[Termos de Serviço](/uaicortes/termos)

## Desenvolvedor e contato

**Adriedson Aparecido Lemos**

Suporte: [drilemoz@gmail.com](mailto:drilemoz@gmail.com)

Código-fonte: [GitHub do Uai Cortes](https://github.com/drilemoz/UAI-Cortes)
MD
        ],
        'privacy'=>[
            'title'=>'Política de Privacidade do Uai Cortes',
            'seo'=>'Saiba como o Uai Cortes utiliza arquivos locais, autorizações Google, informações do YouTube e recursos opcionais de inteligência artificial.',
            'body'=><<<'MD'
# Política de Privacidade do Uai Cortes

**Modelo para revisão antes da publicação. Última atualização: PREENCHER: data da revisão final.**

Esta política descreve o uso de informações pelo aplicativo Android Uai Cortes, desenvolvido por Adriedson Aparecido Lemos. Contato: [drilemoz@gmail.com](mailto:drilemoz@gmail.com).

## 1. Arquivos e projetos

O aplicativo permite selecionar vídeos, imagens, áudios, legendas e outros arquivos para edição. Pode conservar projetos, referências e cópias de mídias no armazenamento do aparelho, conforme o recurso e a versão Android. O conteúdo enviado a serviços externos depende das ações do usuário.

## 2. Autorização Google e YouTube

Quando o usuário autoriza a integração, o Uai Cortes solicita os escopos `youtube.readonly` e `youtube.upload`. A integração pode consultar o identificador e o nome do canal, estatísticas de vídeos, e enviar vídeos selecionados com título, descrição e visibilidade definidos no processo de publicação.

O catálogo local de redes sociais registra identificadores de canais, dados de publicação e métricas. Na implementação analisada, o token OAuth usado pelo cliente YouTube é mantido em memória durante a operação e não é armazenado nesse catálogo. A autorização também pode ser gerenciada pelos serviços do Google.

## 3. Inteligência artificial e serviços externos

Funcionalidades opcionais de IA podem enviar os textos, legendas, áudios, imagens ou outras informações necessárias ao processamento para APIs como Google Gemini, conforme a ação escolhida. Plataformas de publicação e serviços de terceiros têm suas próprias políticas de privacidade.

## 4. Diagnóstico

O aplicativo oferece diagnóstico técnico local opcional. A implementação procura registrar estados, eventos e categorias de falhas sem incluir tokens nem o conteúdo dos projetos. PREENCHER: confirmar se há outros serviços de telemetria, suas finalidades e destinos, caso existam.

## 5. Armazenamento, retenção e exclusão

Projetos, preferências e registros de publicações podem permanecer no dispositivo. Vídeos publicados ficam sujeitos ao armazenamento e às regras da plataforma de destino. PREENCHER: confirmar períodos de retenção, como remover cada categoria de dado e eventuais cópias de segurança.

## 6. Revogar a autorização e solicitar exclusão

A autorização da Conta Google pode ser revista ou revogada em [Conexões com apps e serviços de terceiros](https://myaccount.google.com/connections). Revogar o acesso não apaga automaticamente vídeos publicados no YouTube nem todos os registros locais.

PREENCHER: documentar e testar o procedimento dentro do Uai Cortes para desconectar o YouTube, apagar dados autorizados e atender solicitações de exclusão. Não declarar que esse fluxo está pronto sem testá-lo.

Solicitações relacionadas a informações tratadas pelo responsável: [drilemoz@gmail.com](mailto:drilemoz@gmail.com).

## 7. Segurança e direitos

São utilizados os mecanismos de proteção disponíveis no Android e nas integrações implementadas. O usuário pode solicitar informações, correção ou exclusão conforme a legislação aplicável. Nenhum mecanismo elimina totalmente os riscos de segurança.

## 8. Atualizações

A política pode ser atualizada quando houver mudanças técnicas ou nas práticas de tratamento. A versão pública deve informar a data real da última revisão.

[Termos de Serviço](/uaicortes/termos)
MD
        ],
        'terms'=>[
            'title'=>'Termos de Serviço do Uai Cortes',
            'seo'=>'Consulte os termos do Uai Cortes sobre edição de vídeos, uso de conteúdo, integrações com YouTube e responsabilidades do usuário.',
            'body'=><<<'MD'
# Termos de Serviço do Uai Cortes

**Modelo para revisão antes da publicação. Última atualização: PREENCHER: data da revisão final.**

O Uai Cortes é um aplicativo Android de edição, exportação e preparação de conteúdo audiovisual, desenvolvido por Adriedson Aparecido Lemos.

## 1. Finalidade

O aplicativo oferece ferramentas para editar vídeos, trabalhar com áudio e legendas, organizar projetos e preparar publicações. Algumas funções utilizam serviços externos e exigem conexão com a internet e permissões específicas.

## 2. Conteúdos e direitos

O usuário é responsável por garantir que possui autorização para editar, usar e publicar vídeos, imagens, músicas, textos e demais materiais de terceiros. O aplicativo não concede licenças sobre material protegido por direitos autorais.

## 3. YouTube e autorização Google

A integração opcional utiliza os Serviços de API do YouTube. Ao utilizá-la, o usuário também está sujeito aos [Termos de Serviço do YouTube](https://www.youtube.com/t/terms).

A conexão depende de autorização da conta Google. O envio de vídeos é solicitado pelo usuário e pode estar sujeito a cotas, revisão, disponibilidade ou restrições do YouTube. O Uai Cortes não garante aprovação, publicação, monetização ou permanência de conteúdo.

## 4. Inteligência artificial e outras plataformas

Recursos de inteligência artificial podem produzir resultados incorretos; o usuário deve revisar o conteúdo. Serviços como Google Gemini e plataformas de publicação possuem condições próprias e podem alterar suas APIs, limites ou disponibilidade.

## 5. Projetos e cópias de segurança

O usuário deve manter cópias de segurança de seus arquivos e projetos importantes. Interrupções, falhas de armazenamento, arquivos indisponíveis ou incompatibilidade podem prejudicar a edição e exportação.

## 6. Uso adequado

O aplicativo não deve ser utilizado para atividades ilegais, violação de direitos de terceiros ou descumprimento das regras das plataformas integradas.

## 7. Alterações e disponibilidade

Recursos podem receber atualizações técnicas. Não há garantia de operação ininterrupta nem de compatibilidade com todos os dispositivos, serviços e formatos. Direitos previstos na legislação aplicável permanecem preservados.

## 8. Privacidade e suporte

Consulte a [Política de Privacidade](/uaicortes/privacidade).

Responsável: **Adriedson Aparecido Lemos**

Contato: [drilemoz@gmail.com](mailto:drilemoz@gmail.com)

Projeto: [Uai Cortes no GitHub](https://github.com/drilemoz/UAI-Cortes)

PREENCHER: confirmar eventuais condições adicionais, canais de suporte e a data final da publicação.
MD
        ],
    ];
}
