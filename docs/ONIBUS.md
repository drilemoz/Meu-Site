# Horários de ônibus · IguaNews 1.0.7

## Uso no celular e na home

- O item **Ônibus** substitui integralmente o antigo **Mais** na navegação móvel. O endereço antigo redireciona para o novo módulo.
- Ao abrir a página, a origem padrão vem de `Configurações → Geral → Cidade`, inicialmente Iguatama. Sem escolher um destino, o visitante vê os **próximos ônibus cadastrados saindo da cidade**, mostrando o destino final de cada linha. Pode filtrar por outra cidade/trecho a qualquer momento.
- Cada partida indica horário, dia civil (Hoje/Amanhã/Em N dias), contagem regressiva, empresa e, quando informada, tarifa **do trajeto integral**. O ponto de embarque é mostrado conforme cadastro; NÃO presumimos Rodoviária para dados importados sem ponto confirmado.
- O horário mostrado é **programado**, não comprova operação atual. Paradas com hora desconhecida mostram `A confirmar`.

## Wizard de cadastro (novo horário)

1. **Saída:** escolha a cidade, ponto de embarque (ex.: Rodoviária) e horário local da primeira partida.
2. **Parada seguinte:** indique a próxima cidade, local e horário. Escolha o dia (Mesmo dia, Dia seguinte etc.) se ultrapassar a meia-noite. Se o horário for desconhecido, marque a opção apropriada. Use **+ Outra parada** para continuar a viagem, ou **Última cidade** para concluir.
3. **Finalização:** informe empresa, tarifa opcional em reais referente ao trajeto integral, dias de circulação, observações e publicação. **Salvar horário** é a única etapa que grava no MySQL.
4. **Edição:** abra uma linha existente para editar um dos horários. Editar as paradas afeta todos os horários daquela linha; alterações manuais de linhas importadas ficam `🔒 fixas`. Para nova partida de percurso existente, use **+ Novo horário nesta linha**.

O backend revalida horários, dias, paradas e tarifa e grava tudo em **transação InnoDB**, para não deixar linha sem partida quando houver erro. Não calcula horários desconhecidos.

## Fontes oficiais

Continuam disponíveis **Ônibus → Importar DER / ANTT**, a fila de revisão humana e linhas fixas que não podem ser sobrescritas. DER requer arquivo CSV/TXT oficial baixado manualmente. ANTT tem importador de CSV e edição preconfigurada de setembro/2026. Não é sincronização em tempo real e não garante que a linha opere.

## Migração, backup e limitações

- Migração aditiva cria `bus_routes.fare_amount DECIMAL(9,2) NULL` sem eliminar dados anteriores.
- Backup lógico v5 inclui a tarifa, os usuários, as notícias e as linhas/paradas/partidas, e aceita restaurar v1–v4. Fotos enviadas devem ser copiadas separadamente.
- Ainda faltam teste integrado no MariaDB do Termux, verificação visual real no telefone e confronto dos horários com os operadores de ônibus.

## Importação da tabela da Viação Teixeira (v1.0.104)

O painel **Ônibus → Cadastrar tabela Teixeira** carrega, mediante confirmação explícita, 10 rotas e 31 grupos de partidas transcritos da tabela fotografada e datada 14/09/2026. A origem e destino são as rodoviárias de cada cidade, informadas pelo usuário. Chegadas/durações e tarifas permanecem desconhecidas (`NULL`), não estimadas.

A importação é transacional, usa identificadores estáveis e preserva linhas existentes, inclusive linhas manuais Teixeira com o mesmo nome e empresa. Executá-la de novo não duplica seus próprios registros. Após instalar o código, o administrador precisa abrir o painel e confirmar a importação. O ZIP não executa alterações em dados durante a atualização.

**Feriados:** o quadro indica que viagens de sábado, domingo e feriado compartilham horários em certos sentidos. A máscara semanal atual do portal **não possui calendário especial de feriados**, portanto nessas rotas foram incluídos sábados e domingos e registrada observação explícita para confirmar viagens em feriados que caiam em dias úteis. Não interpretar a lista publicada como escala garantida de feriado. Como qualquer fotografia de horários, a tabela pode ficar desatualizada; verifique diretamente com a viação.
