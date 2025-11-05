PRAGMA foreign_keys=OFF; 

CREATE TABLE agenda( 
      id  INTEGER    NOT NULL  , 
      escritorio_id int   NOT NULL  , 
      profissional_id int   NOT NULL  , 
      nome text   NOT NULL  , 
      horario_inicial text   NOT NULL    DEFAULT '08:00', 
      horario_final text   NOT NULL    DEFAULT '18:00', 
      visualizacao_inicial varchar  (30)   NOT NULL    DEFAULT 'agendaWeek', 
      horario_inicio_intervalo text   , 
      horario_fim_intervalo text   , 
      duracao int   NOT NULL    DEFAULT 30, 
      dias text   NOT NULL  , 
      procedimento_id int   , 
      cor varchar  (10)   , 
      aceita_agendamento_online char  (1)     DEFAULT 'F', 
      publica char  (1)     DEFAULT 'F', 
      fl_permite_choque_horario char  (1)     DEFAULT 'T', 
      data_criacao datetime   , 
      criacao_user_id int  (100)   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(procedimento_id) REFERENCES procedimento(id),
FOREIGN KEY(escritorio_id) REFERENCES escritorio(id),
FOREIGN KEY(profissional_id) REFERENCES pessoa(id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE agendamento( 
      id  INTEGER    NOT NULL  , 
      cliente_id int   NOT NULL  , 
      estado_agenda_id int   NOT NULL  , 
      agenda_id int   NOT NULL  , 
      especialidade_id int   , 
      dt_inicial datetime   NOT NULL  , 
      dt_final datetime   NOT NULL  , 
      agendamento_original_id int   , 
      observacao text   , 
      ativo char  (1)     DEFAULT 'T', 
      ano_inicial text   , 
      mes_inicial text   , 
      ano_mes_inicial text   , 
      ano_final text   , 
      mes_final text   , 
      ano_mes_final text   , 
      online char  (1)     DEFAULT 'F', 
      link_atendimento_online text   , 
 PRIMARY KEY (id),
FOREIGN KEY(cliente_id) REFERENCES pessoa(id),
FOREIGN KEY(estado_agenda_id) REFERENCES estado_agenda(id),
FOREIGN KEY(agenda_id) REFERENCES agenda(id),
FOREIGN KEY(especialidade_id) REFERENCES especialidade(id)) ; 

CREATE TABLE agendamento_procedimento( 
      id  INTEGER    NOT NULL  , 
      agendamento_id int   NOT NULL  , 
      procedimento_id int   NOT NULL  , 
      parceiro_id int   NOT NULL  , 
      quantidade double   NOT NULL  , 
      valor double   , 
      valor_total double   , 
 PRIMARY KEY (id),
FOREIGN KEY(agendamento_id) REFERENCES agendamento(id),
FOREIGN KEY(procedimento_id) REFERENCES procedimento(id),
FOREIGN KEY(parceiro_id) REFERENCES parceiro(id)) ; 

CREATE TABLE agenda_profissional( 
      id  INTEGER    NOT NULL  , 
      profissional_id int   NOT NULL  , 
      agenda_id int   NOT NULL  , 
      fl_manipula_atendimento char   NOT NULL    DEFAULT 'N', 
 PRIMARY KEY (id),
FOREIGN KEY(profissional_id) REFERENCES pessoa(id),
FOREIGN KEY(agenda_id) REFERENCES agenda(id)) ; 

CREATE TABLE andamento( 
      id  INTEGER    NOT NULL  , 
      processo_id int   NOT NULL  , 
      tipo_andamento_id int   NOT NULL  , 
      data_andamento datetime   , 
      titulo text   NOT NULL  , 
      texto text   , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(tipo_andamento_id) REFERENCES tipo_andamento(id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(processo_id) REFERENCES processo(id)) ; 

CREATE TABLE anexo( 
      id  INTEGER    NOT NULL  , 
      atendimento_id int   NOT NULL  , 
      arquivo text   NOT NULL  , 
      observacao text   , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(atendimento_id) REFERENCES atendimento(id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE api_error( 
      id  INTEGER    NOT NULL  , 
      classe varchar  (255)   , 
      metodo varchar  (255)   , 
      url varchar  (500)   , 
      dados varchar  (3000)   , 
      error_message varchar  (3000)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE area( 
      id  INTEGER    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE assunto( 
      id  INTEGER    NOT NULL  , 
      area_id int   NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      descricao text   , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(area_id) REFERENCES area(id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE atendimento( 
      id  INTEGER    NOT NULL  , 
      agendamento_id int   , 
      cliente_id int   NOT NULL  , 
      profissional_id int   NOT NULL  , 
      tipo_atendimento_id int   NOT NULL  , 
      informacoes varchar  (500)   , 
      dt_inicio datetime   , 
      dt_final datetime   , 
      valor_total double   , 
      ano_inicial text   , 
      mes_inicial text   , 
      ano_mes_inicial text   , 
      mes_final text   , 
      ano_final text   , 
      ano_mes_final text   , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(profissional_id) REFERENCES pessoa(id),
FOREIGN KEY(agendamento_id) REFERENCES agendamento(id),
FOREIGN KEY(tipo_atendimento_id) REFERENCES tipo_atendimento(id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(cliente_id) REFERENCES pessoa(id)) ; 

CREATE TABLE atendimento_contrato( 
      id  INTEGER    NOT NULL  , 
      atendimento_id int   NOT NULL  , 
      contrato_id int   NOT NULL  , 
 PRIMARY KEY (id),
FOREIGN KEY(atendimento_id) REFERENCES atendimento(id),
FOREIGN KEY(contrato_id) REFERENCES contrato(id)) ; 

CREATE TABLE atendimento_historico( 
      id  INTEGER    NOT NULL  , 
      atendimento_id int   NOT NULL  , 
      historico text   NOT NULL  , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(atendimento_id) REFERENCES atendimento(id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE atendimento_material( 
      id  INTEGER    NOT NULL  , 
      material_id int   NOT NULL  , 
      atendimento_id int   NOT NULL  , 
      quantidade double   NOT NULL  , 
 PRIMARY KEY (id),
FOREIGN KEY(material_id) REFERENCES material(id),
FOREIGN KEY(atendimento_id) REFERENCES atendimento(id)) ; 

CREATE TABLE atendimento_procedimento( 
      id  INTEGER    NOT NULL  , 
      parceiro_id int   NOT NULL  , 
      atendimento_id int   NOT NULL  , 
      procedimento_id int   NOT NULL  , 
      quantidade double   NOT NULL  , 
      valor double   , 
      valor_total double   , 
 PRIMARY KEY (id),
FOREIGN KEY(atendimento_id) REFERENCES atendimento(id),
FOREIGN KEY(procedimento_id) REFERENCES procedimento(id),
FOREIGN KEY(parceiro_id) REFERENCES parceiro(id)) ; 

CREATE TABLE banco( 
      id  INTEGER    NOT NULL  , 
      codigo varchar  (10)   NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE bloqueio( 
      id  INTEGER    NOT NULL  , 
      agenda_id int   NOT NULL  , 
      dt_inicio datetime   NOT NULL  , 
      dt_final datetime   NOT NULL  , 
      observacao text   , 
      horario_bloqueio_original int   , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(agenda_id) REFERENCES agenda(id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE categoria_conta( 
      id  INTEGER    NOT NULL  , 
      tipo_conta_id int   NOT NULL  , 
      nome text   NOT NULL  , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(tipo_conta_id) REFERENCES tipo_conta(id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE cep_cache( 
      id  INTEGER    NOT NULL  , 
      cep varchar  (12)   NOT NULL  , 
      codigo_ibge text   , 
      rua text   , 
      cidade text   , 
      bairro text   , 
      uf text   , 
      cidade_id int   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE cidade( 
      id  INTEGER    NOT NULL  , 
      estado_id int   NOT NULL  , 
      nome text   NOT NULL  , 
      codigo_ibge text   NOT NULL  , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(estado_id) REFERENCES estado(id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE classificacoes( 
      id  INTEGER    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      data_criacao datetime   , 
      criacao_user_id int  (100)   , 
      data_modificacao datetime   , 
      modificacao_user_id int  (100)   , 
 PRIMARY KEY (id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE classificacoes_cliente( 
      id  INTEGER    NOT NULL  , 
      pessoa_id int   NOT NULL  , 
      classificacoes_id int   NOT NULL  , 
 PRIMARY KEY (id),
FOREIGN KEY(pessoa_id) REFERENCES pessoa(id),
FOREIGN KEY(classificacoes_id) REFERENCES classificacoes(id)) ; 

CREATE TABLE classificacoes_contraparte( 
      id  INTEGER    NOT NULL  , 
      contraparte_id int   , 
      pessoa_id int   NOT NULL  , 
      classificacoes_contraparte_dados_id int   NOT NULL  , 
 PRIMARY KEY (id),
FOREIGN KEY(contraparte_id) REFERENCES contraparte(id),
FOREIGN KEY(pessoa_id) REFERENCES pessoa(id),
FOREIGN KEY(classificacoes_contraparte_dados_id) REFERENCES classificacoes_contraparte_dados(id)) ; 

CREATE TABLE classificacoes_contraparte_dados( 
      id  INTEGER    NOT NULL  , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
      nome varchar  (255)   , 
 PRIMARY KEY (id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE clones( 
      id  INTEGER    NOT NULL  , 
      qtd int   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE comarca( 
      id  INTEGER    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE compromisso( 
      id  INTEGER    NOT NULL  , 
      agenda_id int   NOT NULL  , 
      tipo_compromisso_id int   NOT NULL  , 
      dt_inicio datetime   NOT NULL  , 
      dt_final datetime   NOT NULL  , 
      observacao text   , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(agenda_id) REFERENCES agenda(id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(tipo_compromisso_id) REFERENCES tipo_compromisso(id)) ; 

CREATE TABLE config_busca_a_partir( 
      id  INTEGER    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      add_dias int   NOT NULL  , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE config_busca_prazo( 
      id  INTEGER    NOT NULL  , 
      titulo varchar  (255)   NOT NULL  , 
      prazo int   NOT NULL  , 
      tipo_prazo_id int   NOT NULL  , 
      config_busca_a_partir_id int   NOT NULL  , 
      pont int     DEFAULT 0, 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(tipo_prazo_id) REFERENCES tipo_prazo(id),
FOREIGN KEY(config_busca_a_partir_id) REFERENCES config_busca_a_partir(id)) ; 

CREATE TABLE config_busca_prazo_texto( 
      id  INTEGER    NOT NULL  , 
      config_busca_prazo_id int   NOT NULL  , 
      texto text   , 
 PRIMARY KEY (id),
FOREIGN KEY(config_busca_prazo_id) REFERENCES config_busca_prazo(id)) ; 

CREATE TABLE conta( 
      id  INTEGER    NOT NULL  , 
      pessoa_id int   NOT NULL  , 
      categoria_conta_id int   NOT NULL  , 
      tipo_conta_id int   NOT NULL  , 
      escritorio_id int   NOT NULL  , 
      tipo_documento_financeiro_id int   NOT NULL  , 
      atendimento_id int   , 
      contrato_id int   , 
      profissional_id int   , 
      processo_id int   , 
      numero_documento varchar  (255)   , 
      data_emissao date   NOT NULL  , 
      total_parcelas int   NOT NULL    DEFAULT 1, 
      quitada char  (1)   NOT NULL    DEFAULT 'N', 
      descricao text   NOT NULL  , 
      conta_origem_id int   , 
      total_conta double   NOT NULL  , 
      mes text   , 
      ano text   , 
      ano_mes text   , 
      proximo_vencimento_lancamento date   , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(tipo_conta_id) REFERENCES tipo_conta(id),
FOREIGN KEY(pessoa_id) REFERENCES pessoa(id),
FOREIGN KEY(atendimento_id) REFERENCES atendimento(id),
FOREIGN KEY(escritorio_id) REFERENCES escritorio(id),
FOREIGN KEY(categoria_conta_id) REFERENCES categoria_conta(id),
FOREIGN KEY(tipo_documento_financeiro_id) REFERENCES tipo_documento_financeiro(id),
FOREIGN KEY(profissional_id) REFERENCES pessoa(id),
FOREIGN KEY(contrato_id) REFERENCES contrato(id),
FOREIGN KEY(processo_id) REFERENCES processo(id)) ; 

CREATE TABLE conta_caixa( 
      id  INTEGER    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      tipo_conta_caixa_id int   NOT NULL  , 
      dt_inicial datetime   NOT NULL  , 
      saldo_inicial double   NOT NULL  , 
      saldo_instantaneo double   , 
      saldo_nao_compensado double   , 
      ativo char  (1)   NOT NULL    DEFAULT 'S', 
      cor_nao_compensado varchar  (7)     DEFAULT '#FF0000', 
      cor_compensado varchar  (7)     DEFAULT '#00FF00', 
      banco_id int   , 
      codigo_agencia varchar  (10)   , 
      codigo_conta varchar  (30)   , 
      descricao_agencia varchar  (255)   , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(tipo_conta_caixa_id) REFERENCES tipo_conta_caixa(id),
FOREIGN KEY(banco_id) REFERENCES banco(id)) ; 

CREATE TABLE contraparte( 
      id  INTEGER    NOT NULL  , 
      processo_id int   NOT NULL  , 
      pessoa_id int   NOT NULL  , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(processo_id) REFERENCES processo(id),
FOREIGN KEY(pessoa_id) REFERENCES pessoa(id)) ; 

CREATE TABLE contrato( 
      id  INTEGER    NOT NULL  , 
      escritorio_id int   NOT NULL  , 
      tipo_processo_id int   , 
      area_id int   , 
      contrato_status_id int   , 
      assunto_id int   , 
      numero varchar  (30)   NOT NULL  , 
      objeto text   NOT NULL  , 
      valor double   , 
      quantidade_parcelas int   , 
      envolvimento_id int   , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(escritorio_id) REFERENCES escritorio(id),
FOREIGN KEY(envolvimento_id) REFERENCES envolvimento(id),
FOREIGN KEY(area_id) REFERENCES area(id),
FOREIGN KEY(assunto_id) REFERENCES assunto(id),
FOREIGN KEY(tipo_processo_id) REFERENCES tipo_processo(id),
FOREIGN KEY(contrato_status_id) REFERENCES contrato_status(id)) ; 

CREATE TABLE contrato_config( 
      id  INTEGER    NOT NULL  , 
      clausula_pagamento int   NOT NULL  , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE contrato_documento( 
      id  INTEGER    NOT NULL  , 
      contrato_id int   NOT NULL  , 
      modelo_documento_id int   NOT NULL  , 
      filename text   , 
      dt_preenchimento datetime   NOT NULL  , 
      autenticador text   , 
      dt_validade datetime   , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modelo_documento_id) REFERENCES modelo_documento(id),
FOREIGN KEY(contrato_id) REFERENCES contrato(id)) ; 

CREATE TABLE contrato_pagamento_evento( 
      id  INTEGER    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE contrato_pagamento_indexador( 
      id  INTEGER    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE contrato_pagamento_opcao( 
      id  INTEGER    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      descricao1 text   NOT NULL  , 
      descricaon text   NOT NULL  , 
      recebe_valor char  (1)   NOT NULL    DEFAULT 'N', 
      recebe_data char  (1)   NOT NULL    DEFAULT 'N', 
      recebe_evento char  (1)   NOT NULL    DEFAULT 'N', 
      recebe_indexador char  (1)   NOT NULL    DEFAULT 'N', 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE contrato_pagamento_parcela( 
      contrato_id int   NOT NULL  , 
      id  INTEGER    NOT NULL  , 
      contrato_opcao_pagamento_id int   NOT NULL  , 
      valor double   , 
      data_pagamento date   , 
      contrato_evento_id int   , 
      unidade_indexador_id int   , 
      complemento_indexador varchar  (255)   , 
      contrato_indexador_id int   , 
      descritivo text   , 
      numero_parcelas int   , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(contrato_opcao_pagamento_id) REFERENCES contrato_pagamento_opcao(id),
FOREIGN KEY(contrato_evento_id) REFERENCES contrato_pagamento_evento(id),
FOREIGN KEY(contrato_indexador_id) REFERENCES contrato_pagamento_indexador(id),
FOREIGN KEY(contrato_id) REFERENCES contrato(id),
FOREIGN KEY(unidade_indexador_id) REFERENCES unidade_indexador(id)) ; 

CREATE TABLE contrato_pessoa( 
      id  INTEGER    NOT NULL  , 
      contrato_id int   NOT NULL  , 
      cliente_id int   NOT NULL  , 
      percentual int   , 
 PRIMARY KEY (id),
FOREIGN KEY(cliente_id) REFERENCES pessoa(id),
FOREIGN KEY(contrato_id) REFERENCES contrato(id)) ; 

CREATE TABLE contrato_processo( 
      id  INTEGER    NOT NULL  , 
      contrato_id int   NOT NULL  , 
      processo_id int   NOT NULL  , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(contrato_id) REFERENCES contrato(id),
FOREIGN KEY(processo_id) REFERENCES processo(id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE contrato_repasse( 
      id  INTEGER    NOT NULL  , 
      contrato_id int   NOT NULL  , 
      pessoa_id int   NOT NULL  , 
      percentual int   , 
 PRIMARY KEY (id),
FOREIGN KEY(pessoa_id) REFERENCES pessoa(id),
FOREIGN KEY(contrato_id) REFERENCES contrato(id)) ; 

CREATE TABLE contrato_representante( 
      id  INTEGER    NOT NULL  , 
      contrato_id int   NOT NULL  , 
      representante_id int   NOT NULL  , 
 PRIMARY KEY (id),
FOREIGN KEY(contrato_id) REFERENCES contrato(id),
FOREIGN KEY(representante_id) REFERENCES pessoa(id)) ; 

CREATE TABLE contrato_status( 
      id  INTEGER    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      cor varchar  (10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE convidado( 
      id  INTEGER    NOT NULL  , 
      agendamento_id int   NOT NULL  , 
      agenda_id int   NOT NULL  , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(agenda_id) REFERENCES agenda(id),
FOREIGN KEY(agendamento_id) REFERENCES agendamento(id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE convidado_compromisso( 
      id  INTEGER    NOT NULL  , 
      compromisso_id int   NOT NULL  , 
      agenda_id int   NOT NULL  , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(agenda_id) REFERENCES agenda(id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(compromisso_id) REFERENCES compromisso(id)) ; 

CREATE TABLE documento( 
      id  INTEGER    NOT NULL  , 
      atendimento_id int   NOT NULL  , 
      modelo_documento_id int   , 
      filename text   , 
      observacao text   , 
      dt_preenchimento datetime   NOT NULL  , 
      autenticador text   , 
      dt_validade date   , 
      procedimento_id int   , 
      medico_assistente text   , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(modelo_documento_id) REFERENCES modelo_documento(id),
FOREIGN KEY(atendimento_id) REFERENCES atendimento(id),
FOREIGN KEY(procedimento_id) REFERENCES procedimento(id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE documento_base_contrato( 
      id  INTEGER    NOT NULL  , 
      area_id int   NOT NULL  , 
      modelo_documento_id int   NOT NULL  , 
 PRIMARY KEY (id),
FOREIGN KEY(area_id) REFERENCES area(id),
FOREIGN KEY(modelo_documento_id) REFERENCES modelo_documento(id)) ; 

CREATE TABLE email_config( 
      id  INTEGER    NOT NULL  , 
      escritorio_id int   NOT NULL  , 
      port text   , 
      username text   , 
      password text   , 
      host text   , 
      from_email text   , 
      from_name text   , 
      smtp_auth char  (1)     DEFAULT 'T::bpchar', 
 PRIMARY KEY (id)) ; 

CREATE TABLE envolvimento( 
      id  INTEGER    NOT NULL  , 
      tipo_processo_id int   NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(tipo_processo_id) REFERENCES tipo_processo(id)) ; 

CREATE TABLE escritorio( 
      id  INTEGER    NOT NULL  , 
      system_unit_id int   NOT NULL  , 
      cidade_id int   NOT NULL  , 
      nome text   NOT NULL  , 
      cnpj text   NOT NULL  , 
      telefone text   NOT NULL  , 
      email text   NOT NULL  , 
      endereco text   NOT NULL  , 
      bairro text   NOT NULL  , 
      cep text   NOT NULL  , 
      numero text   , 
      complemento text   , 
      logo_documento text   , 
      url_sistema text   , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(system_unit_id) REFERENCES system_unit(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(cidade_id) REFERENCES cidade(id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE escritorio_parceiro( 
      id  INTEGER    NOT NULL  , 
      parceiro_id int   NOT NULL  , 
      escritorio_id int   NOT NULL  , 
 PRIMARY KEY (id),
FOREIGN KEY(escritorio_id) REFERENCES escritorio(id),
FOREIGN KEY(parceiro_id) REFERENCES parceiro(id)) ; 

CREATE TABLE especialidade( 
      id  INTEGER    NOT NULL  , 
      descricao text   NOT NULL  , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE estado( 
      id  INTEGER    NOT NULL  , 
      nome text   NOT NULL  , 
      sigla char  (2)   NOT NULL  , 
      codigo_ibge text   , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE estado_agenda( 
      id  INTEGER    NOT NULL  , 
      nome text   NOT NULL  , 
      estado_inicial char   NOT NULL    DEFAULT 'N', 
      estado_final char   NOT NULL    DEFAULT 'N', 
      cor varchar  (10)   NOT NULL  , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE estado_agendamento( 
      id  INTEGER    NOT NULL  , 
      agendamento_id int   NOT NULL  , 
      estado_agenda_id int   NOT NULL  , 
      system_users_id int   , 
      atribuido_em datetime   NOT NULL  , 
 PRIMARY KEY (id),
FOREIGN KEY(agendamento_id) REFERENCES agendamento(id),
FOREIGN KEY(estado_agenda_id) REFERENCES estado_agenda(id),
FOREIGN KEY(system_users_id) REFERENCES system_users(id)) ; 

CREATE TABLE estado_civil( 
      id  INTEGER    NOT NULL  , 
      nome varchar  (30)   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE extrato( 
      id  INTEGER    NOT NULL  , 
      escritorio_id int   NOT NULL  , 
      conta_caixa_id int   NOT NULL  , 
      lancamento_id int   , 
      categoria_conta_id int   , 
      tipo_extrato_id int   NOT NULL  , 
      transferencia_conta_caixa_id int   , 
      extrato_vinculado int   , 
      entrada_valor double   , 
      saida_valor double   , 
      data_lancamento date   , 
      data_previsao_compensacao date   , 
      compensado char   NOT NULL    DEFAULT 'N', 
      data_compensacao date   , 
      historico varchar  (3000)   , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
      mes text   , 
      ano text   , 
      ano_mes text   , 
 PRIMARY KEY (id),
FOREIGN KEY(conta_caixa_id) REFERENCES conta_caixa(id),
FOREIGN KEY(escritorio_id) REFERENCES escritorio(id),
FOREIGN KEY(lancamento_id) REFERENCES lancamento(id),
FOREIGN KEY(categoria_conta_id) REFERENCES categoria_conta(id),
FOREIGN KEY(tipo_extrato_id) REFERENCES tipo_extrato(id),
FOREIGN KEY(transferencia_conta_caixa_id) REFERENCES conta_caixa(id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE fluxo_caixa_analitico( 
      id  INTEGER    NOT NULL  , 
      dia date   NOT NULL  , 
      tipo char  (1)   NOT NULL  , 
      numero varchar  (255)   NOT NULL  , 
      historico varchar  (255)   NOT NULL  , 
      entrada double   , 
      saida double   , 
      saldo double   NOT NULL    DEFAULT 0, 
 PRIMARY KEY (id)) ; 

CREATE TABLE fluxo_caixa_sintetico( 
      id  INTEGER    NOT NULL  , 
      dia date   NOT NULL  , 
      tipo char  (1)   , 
      numero varchar  (255)   , 
      historico varchar  (255)   , 
      entrada double   , 
      saida double   , 
      saldo double   NOT NULL    DEFAULT 0, 
 PRIMARY KEY (id)) ; 

CREATE TABLE formulario( 
      id  INTEGER    NOT NULL  , 
      nome text   NOT NULL  , 
      ativo char  (1)   NOT NULL    DEFAULT 'S', 
      ordem int   NOT NULL  , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE foro( 
      id  INTEGER    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE grupo( 
      id  INTEGER    NOT NULL  , 
      nome text   NOT NULL  , 
      cor varchar  (10)   NOT NULL  , 
      data_criacao datetime   , 
      criacao_user_id int  (100)   , 
      data_modificacao datetime   , 
      modificacao_user_id int  (100)   , 
 PRIMARY KEY (id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE jornal( 
      id  INTEGER    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE lancamento( 
      id  INTEGER    NOT NULL  , 
      conta_id int   NOT NULL  , 
      tipo_pagamento_id int   NOT NULL  , 
      parcela int     DEFAULT 1, 
      dt_vencimento date   NOT NULL  , 
      valor double   NOT NULL  , 
      dt_pagamento date   , 
      ano_pagamento text   , 
      mes_pagamento text   , 
      ano_mes_pagamento text   , 
      ano_vencimento text   , 
      mes_vencimento text   , 
      ano_mes_vencimento text   , 
      cheque_numero varchar  (100)   , 
      cheque_banco_id int   , 
      extrato_id int   , 
      cancelado char  (1)     DEFAULT 'N', 
      motivo_cancelamento varchar  (300)   , 
      contrato_parcela_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(contrato_parcela_id) REFERENCES contrato_pagamento_parcela(id),
FOREIGN KEY(cheque_banco_id) REFERENCES banco(id),
FOREIGN KEY(extrato_id) REFERENCES extrato(id),
FOREIGN KEY(conta_id) REFERENCES conta(id),
FOREIGN KEY(tipo_pagamento_id) REFERENCES tipo_pagamento(id)) ; 

CREATE TABLE log_crontab( 
      system_unit_id int   NOT NULL  , 
      id  INTEGER    NOT NULL  , 
      classe text   NOT NULL  , 
      metodo text   , 
      data_hora datetime   , 
      status int   , 
      mensagem text   , 
      observacao text   , 
 PRIMARY KEY (id),
FOREIGN KEY(system_unit_id) REFERENCES system_unit(id)) ; 

CREATE TABLE material( 
      id  INTEGER    NOT NULL  , 
      unidade_medida_id int   NOT NULL  , 
      nome text   NOT NULL  , 
      estoque_minimo double   , 
      dt_vencimento date   , 
      estoque_atualizado double   , 
      lote text   , 
      ativo char  (1)   NOT NULL    DEFAULT 'S', 
 PRIMARY KEY (id),
FOREIGN KEY(unidade_medida_id) REFERENCES unidade_medida(id)) ; 

CREATE TABLE mensagem( 
      id  INTEGER    NOT NULL  , 
      agendamento_id int   NOT NULL  , 
      template_escritorio_id int   , 
      system_user_id int   NOT NULL  , 
      titulo text   , 
      template text   , 
      enviado_em datetime   , 
      tipo_mensagem text   , 
 PRIMARY KEY (id),
FOREIGN KEY(agendamento_id) REFERENCES agendamento(id),
FOREIGN KEY(template_escritorio_id) REFERENCES template_escritorio(id),
FOREIGN KEY(system_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE mensagem_acao( 
      id  INTEGER    NOT NULL  , 
      mensagem_id int   NOT NULL  , 
      url text   , 
      label text   , 
 PRIMARY KEY (id),
FOREIGN KEY(mensagem_id) REFERENCES mensagem(id)) ; 

CREATE TABLE modelo_doc_aplicacao( 
      id  INTEGER    NOT NULL  , 
      modelo_documento_id int   NOT NULL  , 
      tipo_aplicacao_id int   NOT NULL  , 
 PRIMARY KEY (id),
FOREIGN KEY(modelo_documento_id) REFERENCES modelo_documento(id),
FOREIGN KEY(tipo_aplicacao_id) REFERENCES modelo_doc_tipo_aplicacao(id)) ; 

CREATE TABLE modelo_doc_tipo_aplicacao( 
      id  INTEGER    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE modelo_documento( 
      id  INTEGER    NOT NULL  , 
      tipo_modelo_documento_id int   NOT NULL  , 
      nome text   NOT NULL  , 
      ativo char  (1)   NOT NULL    DEFAULT 'S', 
      clausula_pagamento int   , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(tipo_modelo_documento_id) REFERENCES tipo_modelo_documento(id)) ; 

CREATE TABLE modelo_documento_pf( 
      id  INTEGER    NOT NULL  , 
      modelo_documento_id int   NOT NULL  , 
      filename text   NOT NULL  , 
      objeto char  (1)     DEFAULT 'N', 
      informacoes_pagamento char  (1)     DEFAULT 'N', 
      nacionalidade char  (1)     DEFAULT 'N', 
      estado_civil char  (1)     DEFAULT 'N', 
      profissao char  (1)     DEFAULT 'N', 
      rg char  (1)     DEFAULT 'N', 
      cpf char  (1)     DEFAULT 'N', 
      endereco char  (1)     DEFAULT 'N', 
      data_nascimento char  (1)   , 
 PRIMARY KEY (id),
FOREIGN KEY(modelo_documento_id) REFERENCES modelo_documento(id)) ; 

CREATE TABLE modelo_documento_pfrep( 
      id  INTEGER    NOT NULL  , 
      modelo_documento_id int   NOT NULL  , 
      filename text   NOT NULL  , 
      objeto char  (1)     DEFAULT 'N', 
      informacoes_pagamento char  (1)     DEFAULT 'N', 
      nacionalidade char  (1)     DEFAULT 'N', 
      estado_civil char  (1)     DEFAULT 'N', 
      profissao char  (1)     DEFAULT 'N', 
      rg char  (1)     DEFAULT 'N', 
      cpf char  (1)     DEFAULT 'N', 
      data_nascimento char  (1)   , 
      endereco char  (1)     DEFAULT 'N', 
      nacionalidade_rep char  (1)     DEFAULT 'N', 
      estado_civil_rep char  (1)     DEFAULT 'N', 
      profissao_rep char  (1)     DEFAULT 'N', 
      rg_rep char  (1)     DEFAULT 'N', 
      cpf_rep char  (1)     DEFAULT 'N', 
      endereco_rep  INTEGER    , 
      data_nascimento_rep char  (1)   , 
 PRIMARY KEY (id),
FOREIGN KEY(modelo_documento_id) REFERENCES modelo_documento(id)) ; 

CREATE TABLE modelo_documento_pj( 
      id  INTEGER    NOT NULL  , 
      modelo_documento_id int   NOT NULL  , 
      filename text   NOT NULL  , 
      objeto char  (1)     DEFAULT 'N', 
      informacoes_pagamento char  (1)     DEFAULT 'N', 
      cnpj char  (1)     DEFAULT 'N', 
      endereco char  (1)     DEFAULT 'N', 
      nacionalidade_rep char  (1)     DEFAULT 'N', 
      estado_civil_rep char  (1)     DEFAULT 'N', 
      profissao_rep char  (1)     DEFAULT 'N', 
      rg_rep char  (1)     DEFAULT 'N', 
      cpf_rep char  (1)     DEFAULT 'N', 
      endereco_rep char  (1)   , 
      data_abertura char  (1)   , 
      data_nascimento_rep char  (1)   , 
 PRIMARY KEY (id),
FOREIGN KEY(modelo_documento_id) REFERENCES modelo_documento(id)) ; 

CREATE TABLE movimentacao( 
      id  INTEGER    NOT NULL  , 
      material_id int   NOT NULL  , 
      system_user_id int   NOT NULL  , 
      dt_movimentacao text   , 
      quantidade double   , 
 PRIMARY KEY (id),
FOREIGN KEY(material_id) REFERENCES material(id),
FOREIGN KEY(system_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE nacionalidade( 
      id  INTEGER    NOT NULL  , 
      nome varchar  (30)   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE orgao( 
      id  INTEGER    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE padrao_atendimento_documento( 
      id  INTEGER    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE padrao_atend_modelo_doc( 
      id  INTEGER    NOT NULL  , 
      tipo_padrao_doc_atendimento_id int   NOT NULL  , 
      modelo_documento_id int   NOT NULL  , 
 PRIMARY KEY (id),
FOREIGN KEY(tipo_padrao_doc_atendimento_id) REFERENCES padrao_atendimento_documento(id),
FOREIGN KEY(modelo_documento_id) REFERENCES modelo_documento(id)) ; 

CREATE TABLE parceiro( 
      id  INTEGER    NOT NULL  , 
      nome text   NOT NULL  , 
      pessoa_id int   , 
      percentual double  (255)   , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(pessoa_id) REFERENCES pessoa(id)) ; 

CREATE TABLE pessoa( 
      tipo_pessoa_id int   NOT NULL  , 
      id  INTEGER    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      nome_busca varchar  (255)   , 
      email varchar  (255)   , 
      telefone varchar  (20)   , 
      aceita_receber_mensagen_whatsapp char  (1)   NOT NULL    DEFAULT 'F', 
      system_users_id int   , 
      dt_nascimento_abertura date   , 
      dt_falecimento date   , 
      cpf_cnpj varchar  (14)   , 
      rg_ie varchar  (15)   , 
      orgao_emissor varchar  (20)   , 
      sexo_id int   , 
      nacionalidade_id int   , 
      estado_civil_id int   , 
      profissao text   , 
      nit varchar  (255)   , 
      ctps varchar  (255)   , 
      situacao_profissional_id int   , 
      orgao varchar  (255)   , 
      unidade varchar  (255)   , 
      observacao text   , 
      assinatura text   , 
      tratamento text   , 
      tipo_profissional_id int   , 
      orgao_registro_profissional varchar  (30)   , 
      registro_profissional varchar  (255)   , 
      usuario varchar  (255)   , 
      senha varchar  (255)   , 
      foto text   , 
      data_criacao datetime   , 
      criacao_user_id int  (100)   , 
      data_modificacao datetime   , 
      modificacao_user_id int  (100)   , 
      chave_aasp varchar  (255)   , 
 PRIMARY KEY (id),
FOREIGN KEY(tipo_profissional_id) REFERENCES tipo_profissional(id),
FOREIGN KEY(system_users_id) REFERENCES system_users(id),
FOREIGN KEY(tipo_pessoa_id) REFERENCES tipo_pessoa(id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(sexo_id) REFERENCES sexo(id),
FOREIGN KEY(nacionalidade_id) REFERENCES nacionalidade(id),
FOREIGN KEY(estado_civil_id) REFERENCES estado_civil(id),
FOREIGN KEY(situacao_profissional_id) REFERENCES situacao_profissional(id)) ; 

CREATE TABLE pessoa_contato( 
      id  INTEGER    NOT NULL  , 
      pessoa_id int   NOT NULL  , 
      descricao varchar  (255)   NOT NULL  , 
      telefone varchar  (20)   , 
      email varchar  (255)   , 
 PRIMARY KEY (id),
FOREIGN KEY(pessoa_id) REFERENCES pessoa(id)) ; 

CREATE TABLE pessoa_endereco( 
      id  INTEGER    NOT NULL  , 
      pessoa_id int   NOT NULL  , 
      cidade_id int   NOT NULL  , 
      cep varchar  (10)   NOT NULL  , 
      rua varchar  (500)   NOT NULL  , 
      bairro varchar  (500)   NOT NULL  , 
      numero varchar  (100)   NOT NULL  , 
      complemento varchar  (500)   , 
      principal char     DEFAULT 'F', 
 PRIMARY KEY (id),
FOREIGN KEY(pessoa_id) REFERENCES pessoa(id),
FOREIGN KEY(cidade_id) REFERENCES cidade(id)) ; 

CREATE TABLE pessoa_especialidade( 
      id  INTEGER    NOT NULL  , 
      pessoa_id int   NOT NULL  , 
      especialidade_id int   NOT NULL  , 
 PRIMARY KEY (id),
FOREIGN KEY(pessoa_id) REFERENCES pessoa(id),
FOREIGN KEY(especialidade_id) REFERENCES especialidade(id)) ; 

CREATE TABLE pessoa_grupo( 
      id  INTEGER    NOT NULL  , 
      pessoa_id int   NOT NULL  , 
      grupo_id int   NOT NULL  , 
      cor varchar  (10)     DEFAULT '#ffffff', 
 PRIMARY KEY (id),
FOREIGN KEY(pessoa_id) REFERENCES pessoa(id),
FOREIGN KEY(grupo_id) REFERENCES grupo(id)) ; 

CREATE TABLE pessoa_representantes_legais( 
      id  INTEGER    NOT NULL  , 
      pessoa_juridica_id int   NOT NULL  , 
      representante_id int   NOT NULL  , 
      principal char  (1)   , 
      descricao varchar  (255)   NOT NULL  , 
      created_at datetime   , 
 PRIMARY KEY (id),
FOREIGN KEY(pessoa_juridica_id) REFERENCES pessoa(id),
FOREIGN KEY(representante_id) REFERENCES pessoa(id)) ; 

CREATE TABLE preferencia_sistema( 
      id  INTEGER    NOT NULL  , 
      system_users_id int   NOT NULL  , 
      zoom int   NOT NULL    DEFAULT 100, 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
      menu_fixado int   , 
 PRIMARY KEY (id),
FOREIGN KEY(system_users_id) REFERENCES system_users(id)) ; 

CREATE TABLE procedimento( 
      id int   NOT NULL  , 
      nome text   NOT NULL  , 
      cor varchar  (10)   NOT NULL  , 
      ativo char  (1)   NOT NULL    DEFAULT 'S', 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE procedimento_preco( 
      id  INTEGER    NOT NULL  , 
      procedimento_id int   NOT NULL  , 
      parceiro_id int   NOT NULL  , 
      valor double   NOT NULL  , 
 PRIMARY KEY (id),
FOREIGN KEY(procedimento_id) REFERENCES procedimento(id),
FOREIGN KEY(parceiro_id) REFERENCES parceiro(id)) ; 

CREATE TABLE processo( 
      id  INTEGER    NOT NULL  , 
      tipo_processo_id int   NOT NULL  , 
      numero_cnj_numero text   NOT NULL  , 
      numero_outro text   , 
      tribunal_id int   , 
      foro_id int   , 
      comarca_id int   , 
      vara_id int   , 
      orgao_id int   , 
      data_distribuicao_protocolo date   , 
      valor_causa double   , 
      area_id int   , 
      assunto_id int   , 
      gratuidade_processual char  (1)     DEFAULT 'F', 
      status_processual_id int   , 
      responsavel_id int   , 
      envolvimento_id int   , 
      observacao text   , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(envolvimento_id) REFERENCES envolvimento(id),
FOREIGN KEY(tipo_processo_id) REFERENCES tipo_processo(id),
FOREIGN KEY(tribunal_id) REFERENCES tribunal(id),
FOREIGN KEY(foro_id) REFERENCES foro(id),
FOREIGN KEY(comarca_id) REFERENCES comarca(id),
FOREIGN KEY(assunto_id) REFERENCES assunto(id),
FOREIGN KEY(area_id) REFERENCES area(id),
FOREIGN KEY(responsavel_id) REFERENCES pessoa(id),
FOREIGN KEY(status_processual_id) REFERENCES status_processual(id),
FOREIGN KEY(vara_id) REFERENCES vara(id),
FOREIGN KEY(orgao_id) REFERENCES orgao(id)) ; 

CREATE TABLE processo_vinculo( 
      id  INTEGER    NOT NULL  , 
      processo_principal_id int   , 
      processo_incidente_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(processo_principal_id) REFERENCES processo(id),
FOREIGN KEY(processo_incidente_id) REFERENCES processo(id)) ; 

CREATE TABLE publicacao( 
      id  INTEGER    NOT NULL  , 
      numero_arquivo text   , 
      numero_publicacao text   , 
      titulo text   , 
      texto text   , 
      cabecalho text   , 
      rodape text   , 
      processo_id int   , 
      numero_unico_processo text   , 
      numero_processo_principal text   , 
      jornal_id int   , 
      data_tratamento datetime   , 
      data_disponibilizacao date   , 
      termo_ref_data text   , 
      prazo date   , 
      confirma_prazo char  (1)     DEFAULT 'N', 
      data_entrega date   , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(processo_id) REFERENCES processo(id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(jornal_id) REFERENCES jornal(id)) ; 

CREATE TABLE publicacao_movimentacao( 
      id  INTEGER    NOT NULL  , 
      publicacao_id int   NOT NULL  , 
      descricao text   NOT NULL  , 
      processo_id int   , 
      tarefa_id int   , 
      data_criacao datetime   , 
      criacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(publicacao_id) REFERENCES publicacao(id),
FOREIGN KEY(processo_id) REFERENCES processo(id),
FOREIGN KEY(tarefa_id) REFERENCES tarefa(id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE publicacao_profissional( 
      id  INTEGER    NOT NULL  , 
      publicacao_id int   NOT NULL  , 
      profissional_id int   NOT NULL  , 
      codigo_relacionamento text   , 
 PRIMARY KEY (id),
FOREIGN KEY(publicacao_id) REFERENCES publicacao(id),
FOREIGN KEY(profissional_id) REFERENCES pessoa(id)) ; 

CREATE TABLE publicacao_sugestao_prazo( 
      id  INTEGER    NOT NULL  , 
      publicacao_id int   NOT NULL  , 
      config_busca_prazo_id int   NOT NULL  , 
      resultado_busca text   NOT NULL  , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(publicacao_id) REFERENCES publicacao(id),
FOREIGN KEY(config_busca_prazo_id) REFERENCES config_busca_prazo(id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE questao( 
      id  INTEGER    NOT NULL  , 
      formulario_id int   NOT NULL  , 
      nome text   NOT NULL  , 
      tipo_campo text   NOT NULL  , 
      fl_obrigatorio char   NOT NULL    DEFAULT 'N', 
      ativo char  (1)   NOT NULL    DEFAULT 'S', 
      opcoes text   , 
 PRIMARY KEY (id),
FOREIGN KEY(formulario_id) REFERENCES formulario(id)) ; 

CREATE TABLE resposta( 
      id  INTEGER    NOT NULL  , 
      resposta_formulario_id int   NOT NULL  , 
      questao_id int   NOT NULL  , 
      resposta text   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE resposta_formulario( 
      id  INTEGER    NOT NULL  , 
      formulario_id int   NOT NULL  , 
      atendimento_id int   NOT NULL  , 
      dt_resposta date   , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(formulario_id) REFERENCES formulario(id),
FOREIGN KEY(atendimento_id) REFERENCES atendimento(id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE sexo( 
      id  INTEGER    NOT NULL  , 
      nome varchar  (30)   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE situacao_profissional( 
      id  INTEGER    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE status_processual( 
      id  INTEGER    NOT NULL  , 
      tipo_processo_id int   NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(tipo_processo_id) REFERENCES tipo_processo(id)) ; 

CREATE TABLE system_group( 
      id int   NOT NULL  , 
      name text   NOT NULL  , 
      uuid varchar  (36)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE system_group_program( 
      id int   NOT NULL  , 
      system_group_id int   NOT NULL  , 
      system_program_id int   NOT NULL  , 
      actions text   , 
 PRIMARY KEY (id),
FOREIGN KEY(system_program_id) REFERENCES system_program(id),
FOREIGN KEY(system_group_id) REFERENCES system_group(id)) ; 

CREATE TABLE system_preference( 
      id varchar  (255)   NOT NULL  , 
      preference text   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE system_program( 
      id int   NOT NULL  , 
      name text   NOT NULL  , 
      controller text   NOT NULL  , 
      actions text   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE system_unit( 
      id int   NOT NULL  , 
      name text   NOT NULL  , 
      connection_name text   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE system_user_group( 
      id int   NOT NULL  , 
      system_user_id int   NOT NULL  , 
      system_group_id int   NOT NULL  , 
 PRIMARY KEY (id),
FOREIGN KEY(system_group_id) REFERENCES system_group(id),
FOREIGN KEY(system_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE system_user_program( 
      id int   NOT NULL  , 
      system_user_id int   NOT NULL  , 
      system_program_id int   NOT NULL  , 
 PRIMARY KEY (id),
FOREIGN KEY(system_program_id) REFERENCES system_program(id),
FOREIGN KEY(system_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE system_users( 
      id int   NOT NULL  , 
      name text   NOT NULL  , 
      login text   NOT NULL  , 
      password text   NOT NULL  , 
      email text   , 
      frontpage_id int   , 
      system_unit_id int   , 
      active char  (1)   , 
      accepted_term_policy_at text   , 
      accepted_term_policy char  (1)   , 
      two_factor_enabled char  (1)     DEFAULT 'N', 
      two_factor_type varchar  (100)   , 
      two_factor_secret varchar  (255)   , 
 PRIMARY KEY (id),
FOREIGN KEY(system_unit_id) REFERENCES system_unit(id),
FOREIGN KEY(frontpage_id) REFERENCES system_program(id)) ; 

CREATE TABLE system_user_unit( 
      id int   NOT NULL  , 
      system_user_id int   NOT NULL  , 
      system_unit_id int   NOT NULL  , 
 PRIMARY KEY (id),
FOREIGN KEY(system_user_id) REFERENCES system_users(id),
FOREIGN KEY(system_unit_id) REFERENCES system_unit(id)) ; 

CREATE TABLE tarefa( 
      id  INTEGER    NOT NULL  , 
      tarefa_status_id int   NOT NULL  , 
      publicacao_id int   , 
      processo_id int   , 
      usuario_destinatario_id int   NOT NULL  , 
      titulo varchar  (255)   NOT NULL  , 
      data_disponibilizacao datetime   , 
      prazo_validacao date   , 
      prazo_entrega date   NOT NULL  , 
      observacao text   , 
      data_entrega datetime   , 
      arquivado char  (1)     DEFAULT 'N', 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
      prazo_processual char  (1)     DEFAULT 'N', 
 PRIMARY KEY (id),
FOREIGN KEY(processo_id) REFERENCES processo(id),
FOREIGN KEY(tarefa_status_id) REFERENCES tarefa_status(id),
FOREIGN KEY(publicacao_id) REFERENCES publicacao(id),
FOREIGN KEY(usuario_destinatario_id) REFERENCES system_users(id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE tarefa_cliente( 
      id  INTEGER    NOT NULL  , 
      tarefa_id int   NOT NULL  , 
      cliente_id int   NOT NULL  , 
 PRIMARY KEY (id),
FOREIGN KEY(tarefa_id) REFERENCES tarefa(id),
FOREIGN KEY(cliente_id) REFERENCES pessoa(id)) ; 

CREATE TABLE tarefa_comentario( 
      id  INTEGER    NOT NULL  , 
      tarefa_id int   NOT NULL  , 
      texto text   NOT NULL  , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(tarefa_id) REFERENCES tarefa(id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE tarefa_configuracao( 
      id  INTEGER    NOT NULL  , 
      status_inicial_id int   NOT NULL  , 
      status_final_id int   NOT NULL  , 
      status_cancelado_id int   NOT NULL  , 
      tem_dtvalidacao char  (1)     DEFAULT 'N', 
      dtvalidacao_obrigatoria char  (1)     DEFAULT 'N', 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(status_inicial_id) REFERENCES tarefa_status(id),
FOREIGN KEY(status_final_id) REFERENCES tarefa_status(id),
FOREIGN KEY(status_cancelado_id) REFERENCES tarefa_status(id)) ; 

CREATE TABLE tarefa_horas_trabalhadas( 
      id  INTEGER    NOT NULL  , 
      tarefa_id int   NOT NULL  , 
      data_inicio datetime   NOT NULL  , 
      data_fim datetime   , 
      observacao text   , 
      data_criacao datetime   , 
      criacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(tarefa_id) REFERENCES tarefa(id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE tarefa_movimentacao( 
      id  INTEGER    NOT NULL  , 
      tarefa_id int   NOT NULL  , 
      descricao text   , 
      data_movimentacao datetime   NOT NULL  , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(tarefa_id) REFERENCES tarefa(id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE tarefa_status( 
      id  INTEGER    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      kanban int   NOT NULL  , 
      inicio char  (1)   , 
      fim char  (1)   , 
      cor varchar  (10)   NOT NULL  , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE tarefa_usuario_master( 
      id  INTEGER    NOT NULL  , 
      tarefa_configuracao_id int   NOT NULL  , 
      usuario_master_id int   NOT NULL  , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(usuario_master_id) REFERENCES system_users(id),
FOREIGN KEY(tarefa_configuracao_id) REFERENCES tarefa_configuracao(id)) ; 

CREATE TABLE tarefa_vinculo( 
      id  INTEGER    NOT NULL  , 
      tarefa_id int   NOT NULL  , 
      subtarefa_id int   NOT NULL  , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(tarefa_id) REFERENCES tarefa(id),
FOREIGN KEY(subtarefa_id) REFERENCES tarefa(id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE template_acao( 
      id  INTEGER    NOT NULL  , 
      template_escritorio_id int   NOT NULL  , 
      url text   , 
      label text   , 
 PRIMARY KEY (id),
FOREIGN KEY(template_escritorio_id) REFERENCES template_escritorio(id)) ; 

CREATE TABLE template_escritorio( 
      id  INTEGER    NOT NULL  , 
      escritorio_id int   NOT NULL  , 
      chave text   NOT NULL  , 
      descricao text   NOT NULL  , 
      habilitado char  (1)   NOT NULL    DEFAULT 'T', 
      template text   , 
      titulo text   , 
      tipo_template text   , 
      readonly char  (1)   NOT NULL    DEFAULT 'F', 
      data_criacao datetime   , 
      criacao_user_id int  (100)   , 
      data_modificacao datetime   , 
      modificacao_user_id int  (100)   , 
 PRIMARY KEY (id),
FOREIGN KEY(escritorio_id) REFERENCES escritorio(id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE tipo_andamento( 
      id  INTEGER    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE tipo_atendimento( 
      id  INTEGER    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tipo_compromisso( 
      id  INTEGER    NOT NULL  , 
      nome text   NOT NULL  , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE tipo_conta( 
      id  INTEGER    NOT NULL  , 
      nome text   NOT NULL  , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE tipo_conta_caixa( 
      id  INTEGER    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tipo_doc_financeiro_padrao( 
      id  INTEGER    NOT NULL  , 
      nome varchar  (30)   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tipo_documento_financeiro( 
      id  INTEGER    NOT NULL  , 
      codigo varchar  (4)   NOT NULL    DEFAULT 'Man', 
      nome varchar  (255)   NOT NULL  , 
      tipo_conta_id int   NOT NULL  , 
      gera_codigo char  (1)   NOT NULL    DEFAULT 'N', 
      padrao_id int   NOT NULL  , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(padrao_id) REFERENCES tipo_doc_financeiro_padrao(id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(tipo_conta_id) REFERENCES tipo_conta(id)) ; 

CREATE TABLE tipo_extrato( 
      id  INTEGER    NOT NULL  , 
      nome varchar  (50)   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tipo_modelo_documento( 
      id  INTEGER    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE tipo_pagamento( 
      id  INTEGER    NOT NULL  , 
      nome text   NOT NULL  , 
      ativo char  (1)   NOT NULL    DEFAULT 'S', 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE tipo_pessoa( 
      id  INTEGER    NOT NULL  , 
      nome varchar  (20)   NOT NULL  , 
      sigla char  (2)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tipo_prazo( 
      id  INTEGER    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE tipo_processo( 
      id  INTEGER    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tipo_profissional( 
      id  INTEGER    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE tmp_documento( 
      id  INTEGER    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      filename text   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tribunal( 
      id  INTEGER    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE unidade_indexador( 
      id  INTEGER    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      extenso text   , 
      simbolo varchar  (10)   , 
      criacao_user_id int   , 
      data_criacao datetime   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE unidade_medida( 
      id  INTEGER    NOT NULL  , 
      nome text   NOT NULL  , 
      sigla text   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE vara( 
      id  INTEGER    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      data_criacao datetime   , 
      criacao_user_id int   , 
      data_modificacao datetime   , 
      modificacao_user_id int   , 
 PRIMARY KEY (id),
FOREIGN KEY(criacao_user_id) REFERENCES system_users(id),
FOREIGN KEY(modificacao_user_id) REFERENCES system_users(id)) ; 

CREATE TABLE video( 
      id  INTEGER    NOT NULL  , 
      nome varchar  (255)   , 
      url text   , 
      tag_iframe text   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE whatsapp_config( 
      id  INTEGER    NOT NULL  , 
      escritorio_id int   NOT NULL  , 
      phone text   , 
      status text   , 
      api_token text   , 
      api_key text   , 
 PRIMARY KEY (id)) ; 

 
 CREATE UNIQUE INDEX unique_idx_cep_cache_cep ON cep_cache(cep);
 
 CREATE VIEW cliente_tarefas AS SELECT
	'Processo' as "origem",
	pessoa.id as "pessoa_id",
	tarefa.id as "tarefa_id",
    tarefa.tarefa_status_id as "tarefa_status_id",
    tarefa.usuario_destinatario_id as "usuario_destinatario_id",
    tarefa.titulo as "titulo",
    tarefa.prazo_entrega as "prazo_entrega",
    tarefa.data_entrega as "data_entrega",
	processo.id as "complemento_id"
FROM
	tarefa,
	processo,
	contrato_processo,
	contrato,
	contrato_pessoa,
	pessoa
WHERE
	tarefa.processo_id = processo.id
	AND processo.id = contrato_processo.processo_id
	AND contrato_processo.contrato_id = contrato.id
	AND contrato.id = contrato_pessoa.contrato_id
	AND contrato_pessoa.cliente_id = pessoa.id

UNION ALL

SELECT
	'Publicação' as "origem",
	pessoa.id as "pessoa_id",
	tarefa.id as "tarefa_id",
    tarefa.tarefa_status_id as "tarefa_status_id",
    tarefa.usuario_destinatario_id as "usuario_destinatario_id",
    tarefa.titulo as "titulo",
    tarefa.prazo_entrega as "prazo_entrega",
    tarefa.data_entrega as "data_entrega",
	publicacao.id as "complemento_id"
FROM
	tarefa,
	publicacao,
	processo,
	contrato_processo,
	contrato,
	contrato_pessoa,
	pessoa
WHERE
	tarefa.publicacao_id = publicacao.id
	AND publicacao.processo_id = processo.id
	AND processo.id = contrato_processo.processo_id
	AND contrato_processo.contrato_id = contrato.id
	AND contrato.id = contrato_pessoa.contrato_id
	AND contrato_pessoa.cliente_id = pessoa.id

UNION ALL

SELECT
	'Cliente' as "origem",
	pessoa.id as "pessoa_id",
	tarefa.id as "tarefa_id",
    tarefa.tarefa_status_id as "tarefa_status_id",
    tarefa.usuario_destinatario_id as "usuario_destinatario_id",
    tarefa.titulo as "titulo",
    tarefa.prazo_entrega as "prazo_entrega",
    tarefa.data_entrega as "data_entrega",
	null as "complemento_id"
FROM
	tarefa,
	tarefa_cliente,
	pessoa
WHERE
	tarefa.id = tarefa_cliente.tarefa_id
	AND tarefa_cliente.cliente_id = pessoa.id; 

CREATE VIEW view_andamentos AS SELECT 
    'Publicação' as "origem",
    publicacao.id as "id",
    publicacao.titulo as "titulo",
    publicacao.texto as "texto",
    publicacao.processo_id as "keyprocesso_id",
    publicacao.jornal_id as "jornal_tipo_id",
    publicacao.data_disponibilizacao as "dt",
    jornal.id as "key_jornal_tipo",
    jornal.nome as "jornal_tipo",
    processo.id as "processo_id",
    processo.numero_cnj_numero as "numero",
    tipo_processo.id as "tipo_processo_id",
    tipo_processo.nome as "tipo_processo_nome"
    
    FROM 
    publicacao, 
    processo, 
    tipo_processo,
    jornal
    
WHERE 
    publicacao.processo_id = processo.id AND 
    processo.tipo_processo_id = tipo_processo.id AND
    publicacao.jornal_id = jornal.id

UNION ALL 
SELECT 
    'Andamento' as "origem",
    andamento.id as "id",
    andamento.titulo as "titulo",
    andamento.texto as "texto",
    andamento.processo_id as "keyprocesso_id",
    andamento.tipo_andamento_id as "jornal_tipo_id",
    andamento.data_andamento as "dt",
    tipo_andamento.id as "key_jornal_tipo",
    tipo_andamento.nome as "jornal_tipo",
    processo.id as "processo_id",
    processo.numero_cnj_numero as "numero",
    tipo_processo.id as "tipo_processo_id",
    tipo_processo.nome as "tipo_processo_nome"

    FROM 
    andamento, 
    processo, 
    tipo_processo,
    tipo_andamento
    
WHERE 
    andamento.processo_id = processo.id AND 
    processo.tipo_processo_id = tipo_processo.id AND
    andamento.tipo_andamento_id = tipo_andamento.id
    

; 

CREATE VIEW view_publicacao AS SELECT 
    publicacao.id AS "id",
    publicacao.numero_arquivo AS "numero_arquivo",
    publicacao.numero_publicacao AS "numero_publicacao",
    publicacao.titulo AS "titulo",
    publicacao.texto AS "texto",
    publicacao.cabecalho AS "cabecalho",
    publicacao.rodape AS "rodape",
    publicacao.numero_unico_processo AS "numero_unico_processo",
    publicacao.numero_processo_principal AS "numero_processo_principal",
    publicacao.data_tratamento AS "data_tratamento",
    publicacao.data_disponibilizacao AS "data_disponibilizacao",
    publicacao.termo_ref_data AS "termo_ref_data",
    publicacao.prazo AS "prazo",
    publicacao.confirma_prazo AS "confirma_prazo",
    publicacao.data_entrega AS "data_entrega",
    processo.id AS "processo_id",
    processo.numero_cnj_numero AS "numero_cnj_numero",
    processo.numero_outro AS "numero_outro",
    processo.data_distribuicao_protocolo AS "data_distribuicao_protocolo",
    processo.valor_causa AS "valor_causa",
    processo.gratuidade_processual AS "gratuidade_processual",
    processo.observacao AS "observacao",
    pessoa.nome AS "responsavel",
    tipo_processo.nome AS "tipo_processo",
    jornal.nome AS "jornal",
    tribunal.nome AS "tribunal",
    vara.nome AS "vara",
    foro.nome AS "foro",
    comarca.nome AS "comarca",
    orgao.nome AS "orgao",
    envolvimento.nome AS "envolvimento",
    area.nome AS "area",
    assunto.nome AS "assunto",
    status_processual.nome AS "status"
FROM 
    publicacao
    LEFT JOIN processo ON publicacao.processo_id = processo.id
    LEFT JOIN jornal ON publicacao.jornal_id = jornal.id
    LEFT JOIN envolvimento ON processo.envolvimento_id = envolvimento.id
    LEFT JOIN tribunal ON processo.tribunal_id = tribunal.id
    LEFT JOIN foro ON processo.foro_id = foro.id
    LEFT JOIN comarca ON processo.comarca_id = comarca.id
    LEFT JOIN assunto ON processo.assunto_id = assunto.id
    LEFT JOIN area ON processo.area_id = area.id
    LEFT JOIN pessoa ON processo.responsavel_id = pessoa.id
    LEFT JOIN status_processual ON processo.status_processual_id = status_processual.id
    LEFT JOIN vara ON processo.vara_id = vara.id
    LEFT JOIN orgao ON processo.orgao_id = orgao.id
    LEFT JOIN tipo_processo ON processo.tipo_processo_id = tipo_processo.id;
; 
 
