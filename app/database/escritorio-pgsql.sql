CREATE TABLE agenda( 
      id  SERIAL    NOT NULL  , 
      escritorio_id integer   NOT NULL  , 
      profissional_id integer   NOT NULL  , 
      nome text   NOT NULL  , 
      horario_inicial time   NOT NULL    DEFAULT '08:00', 
      horario_final time   NOT NULL    DEFAULT '18:00', 
      visualizacao_inicial varchar  (30)   NOT NULL    DEFAULT 'agendaWeek', 
      horario_inicio_intervalo time   , 
      horario_fim_intervalo time   , 
      duracao integer   NOT NULL    DEFAULT 30, 
      dias text   NOT NULL  , 
      procedimento_id integer   , 
      cor varchar  (10)   , 
      aceita_agendamento_online char  (1)     DEFAULT 'F', 
      publica char  (1)     DEFAULT 'F', 
      fl_permite_choque_horario char  (1)     DEFAULT 'T', 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE agendamento( 
      id  SERIAL    NOT NULL  , 
      cliente_id integer   NOT NULL  , 
      estado_agenda_id integer   NOT NULL  , 
      agenda_id integer   NOT NULL  , 
      especialidade_id integer   , 
      dt_inicial timestamp   NOT NULL  , 
      dt_final timestamp   NOT NULL  , 
      agendamento_original_id integer   , 
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
 PRIMARY KEY (id)) ; 

CREATE TABLE agendamento_procedimento( 
      id  SERIAL    NOT NULL  , 
      agendamento_id integer   NOT NULL  , 
      procedimento_id integer   NOT NULL  , 
      parceiro_id integer   NOT NULL  , 
      quantidade float   NOT NULL  , 
      valor float   , 
      valor_total float   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE agenda_profissional( 
      id  SERIAL    NOT NULL  , 
      profissional_id integer   NOT NULL  , 
      agenda_id integer   NOT NULL  , 
      fl_manipula_atendimento char   NOT NULL    DEFAULT 'N', 
 PRIMARY KEY (id)) ; 

CREATE TABLE andamento( 
      id  SERIAL    NOT NULL  , 
      processo_id integer   NOT NULL  , 
      tipo_andamento_id integer   NOT NULL  , 
      data_andamento timestamp   , 
      titulo text   NOT NULL  , 
      texto text   , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE anexo( 
      id  SERIAL    NOT NULL  , 
      atendimento_id integer   NOT NULL  , 
      arquivo text   NOT NULL  , 
      observacao text   , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE api_error( 
      id  SERIAL    NOT NULL  , 
      classe varchar  (255)   , 
      metodo varchar  (255)   , 
      url varchar  (500)   , 
      dados varchar  (3000)   , 
      error_message varchar  (3000)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE area( 
      id  SERIAL    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE assunto( 
      id  SERIAL    NOT NULL  , 
      area_id integer   NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      descricao text   , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE atendimento( 
      id  SERIAL    NOT NULL  , 
      agendamento_id integer   , 
      cliente_id integer   NOT NULL  , 
      profissional_id integer   NOT NULL  , 
      tipo_atendimento_id integer   NOT NULL  , 
      informacoes varchar  (500)   , 
      dt_inicio timestamp   , 
      dt_final timestamp   , 
      valor_total float   , 
      ano_inicial text   , 
      mes_inicial text   , 
      ano_mes_inicial text   , 
      mes_final text   , 
      ano_final text   , 
      ano_mes_final text   , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE atendimento_contrato( 
      id  SERIAL    NOT NULL  , 
      atendimento_id integer   NOT NULL  , 
      contrato_id integer   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE atendimento_historico( 
      id  SERIAL    NOT NULL  , 
      atendimento_id integer   NOT NULL  , 
      historico text   NOT NULL  , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE atendimento_material( 
      id  SERIAL    NOT NULL  , 
      material_id integer   NOT NULL  , 
      atendimento_id integer   NOT NULL  , 
      quantidade float   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE atendimento_procedimento( 
      id  SERIAL    NOT NULL  , 
      parceiro_id integer   NOT NULL  , 
      atendimento_id integer   NOT NULL  , 
      procedimento_id integer   NOT NULL  , 
      quantidade float   NOT NULL  , 
      valor float   , 
      valor_total float   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE banco( 
      id  SERIAL    NOT NULL  , 
      codigo varchar  (10)   NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE bloqueio( 
      id  SERIAL    NOT NULL  , 
      agenda_id integer   NOT NULL  , 
      dt_inicio timestamp   NOT NULL  , 
      dt_final timestamp   NOT NULL  , 
      observacao text   , 
      horario_bloqueio_original integer   , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE categoria_conta( 
      id  SERIAL    NOT NULL  , 
      tipo_conta_id integer   NOT NULL  , 
      nome text   NOT NULL  , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE cep_cache( 
      id  SERIAL    NOT NULL  , 
      cep varchar  (12)   NOT NULL  , 
      codigo_ibge text   , 
      rua text   , 
      cidade text   , 
      bairro text   , 
      uf text   , 
      cidade_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE cidade( 
      id  SERIAL    NOT NULL  , 
      estado_id integer   NOT NULL  , 
      nome text   NOT NULL  , 
      codigo_ibge text   NOT NULL  , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE classificacoes( 
      id  SERIAL    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE classificacoes_cliente( 
      id  SERIAL    NOT NULL  , 
      pessoa_id integer   NOT NULL  , 
      classificacoes_id integer   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE classificacoes_contraparte( 
      id  SERIAL    NOT NULL  , 
      contraparte_id integer   , 
      pessoa_id integer   NOT NULL  , 
      classificacoes_contraparte_dados_id integer   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE classificacoes_contraparte_dados( 
      id  SERIAL    NOT NULL  , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
      nome varchar  (255)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE clones( 
      id  SERIAL    NOT NULL  , 
      qtd integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE comarca( 
      id  SERIAL    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE compromisso( 
      id  SERIAL    NOT NULL  , 
      agenda_id integer   NOT NULL  , 
      tipo_compromisso_id integer   NOT NULL  , 
      dt_inicio timestamp   NOT NULL  , 
      dt_final timestamp   NOT NULL  , 
      observacao text   , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE config_busca_a_partir( 
      id  SERIAL    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      add_dias integer   NOT NULL  , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE config_busca_prazo( 
      id  SERIAL    NOT NULL  , 
      titulo varchar  (255)   NOT NULL  , 
      prazo integer   NOT NULL  , 
      tipo_prazo_id integer   NOT NULL  , 
      config_busca_a_partir_id integer   NOT NULL  , 
      pont integer     DEFAULT 0, 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE config_busca_prazo_texto( 
      id  SERIAL    NOT NULL  , 
      config_busca_prazo_id integer   NOT NULL  , 
      texto text   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE conta( 
      id  SERIAL    NOT NULL  , 
      pessoa_id integer   NOT NULL  , 
      categoria_conta_id integer   NOT NULL  , 
      tipo_conta_id integer   NOT NULL  , 
      escritorio_id integer   NOT NULL  , 
      tipo_documento_financeiro_id integer   NOT NULL  , 
      atendimento_id integer   , 
      contrato_id integer   , 
      profissional_id integer   , 
      processo_id integer   , 
      numero_documento varchar  (255)   , 
      data_emissao date   NOT NULL  , 
      total_parcelas integer   NOT NULL    DEFAULT 1, 
      quitada char  (1)   NOT NULL    DEFAULT 'N', 
      descricao text   NOT NULL  , 
      conta_origem_id integer   , 
      total_conta float   NOT NULL  , 
      mes text   , 
      ano text   , 
      ano_mes text   , 
      proximo_vencimento_lancamento date   , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE conta_caixa( 
      id  SERIAL    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      tipo_conta_caixa_id integer   NOT NULL  , 
      dt_inicial timestamp   NOT NULL  , 
      saldo_inicial float   NOT NULL  , 
      saldo_instantaneo float   , 
      saldo_nao_compensado float   , 
      ativo char  (1)   NOT NULL    DEFAULT 'S', 
      cor_nao_compensado varchar  (7)     DEFAULT '#FF0000', 
      cor_compensado varchar  (7)     DEFAULT '#00FF00', 
      banco_id integer   , 
      codigo_agencia varchar  (10)   , 
      codigo_conta varchar  (30)   , 
      descricao_agencia varchar  (255)   , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE contraparte( 
      id  SERIAL    NOT NULL  , 
      processo_id integer   NOT NULL  , 
      pessoa_id integer   NOT NULL  , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE contrato( 
      id  SERIAL    NOT NULL  , 
      escritorio_id integer   NOT NULL  , 
      tipo_processo_id integer   , 
      area_id integer   , 
      contrato_status_id integer   , 
      assunto_id integer   , 
      numero varchar  (30)   NOT NULL  , 
      objeto text   NOT NULL  , 
      valor float   , 
      quantidade_parcelas integer   , 
      envolvimento_id integer   , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE contrato_config( 
      id  SERIAL    NOT NULL  , 
      clausula_pagamento integer   NOT NULL  , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE contrato_documento( 
      id  SERIAL    NOT NULL  , 
      contrato_id integer   NOT NULL  , 
      modelo_documento_id integer   NOT NULL  , 
      filename text   , 
      dt_preenchimento timestamp   NOT NULL  , 
      autenticador text   , 
      dt_validade timestamp   , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE contrato_pagamento_evento( 
      id  SERIAL    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE contrato_pagamento_indexador( 
      id  SERIAL    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE contrato_pagamento_opcao( 
      id  SERIAL    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      descricao1 text   NOT NULL  , 
      descricaon text   NOT NULL  , 
      recebe_valor char  (1)   NOT NULL    DEFAULT 'N', 
      recebe_data char  (1)   NOT NULL    DEFAULT 'N', 
      recebe_evento char  (1)   NOT NULL    DEFAULT 'N', 
      recebe_indexador char  (1)   NOT NULL    DEFAULT 'N', 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE contrato_pagamento_parcela( 
      contrato_id integer   NOT NULL  , 
      id  SERIAL    NOT NULL  , 
      contrato_opcao_pagamento_id integer   NOT NULL  , 
      valor float   , 
      data_pagamento date   , 
      contrato_evento_id integer   , 
      unidade_indexador_id integer   , 
      complemento_indexador varchar  (255)   , 
      contrato_indexador_id integer   , 
      descritivo text   , 
      numero_parcelas integer   , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE contrato_pessoa( 
      id  SERIAL    NOT NULL  , 
      contrato_id integer   NOT NULL  , 
      cliente_id integer   NOT NULL  , 
      percentual integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE contrato_processo( 
      id  SERIAL    NOT NULL  , 
      contrato_id integer   NOT NULL  , 
      processo_id integer   NOT NULL  , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE contrato_repasse( 
      id  SERIAL    NOT NULL  , 
      contrato_id integer   NOT NULL  , 
      pessoa_id integer   NOT NULL  , 
      percentual integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE contrato_representante( 
      id  SERIAL    NOT NULL  , 
      contrato_id integer   NOT NULL  , 
      representante_id integer   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE contrato_status( 
      id  SERIAL    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      cor varchar  (10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE convidado( 
      id  SERIAL    NOT NULL  , 
      agendamento_id integer   NOT NULL  , 
      agenda_id integer   NOT NULL  , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE convidado_compromisso( 
      id  SERIAL    NOT NULL  , 
      compromisso_id integer   NOT NULL  , 
      agenda_id integer   NOT NULL  , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE documento( 
      id  SERIAL    NOT NULL  , 
      atendimento_id integer   NOT NULL  , 
      modelo_documento_id integer   , 
      filename text   , 
      observacao text   , 
      dt_preenchimento timestamp   NOT NULL  , 
      autenticador text   , 
      dt_validade date   , 
      procedimento_id integer   , 
      medico_assistente text   , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE documento_base_contrato( 
      id  SERIAL    NOT NULL  , 
      area_id integer   NOT NULL  , 
      modelo_documento_id integer   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE email_config( 
      id  SERIAL    NOT NULL  , 
      escritorio_id integer   NOT NULL  , 
      port text   , 
      username text   , 
      password text   , 
      host text   , 
      from_email text   , 
      from_name text   , 
      smtp_auth char  (1)     DEFAULT 'T::bpchar', 
 PRIMARY KEY (id)) ; 

CREATE TABLE envolvimento( 
      id  SERIAL    NOT NULL  , 
      tipo_processo_id integer   NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE escritorio( 
      id  SERIAL    NOT NULL  , 
      system_unit_id integer   NOT NULL  , 
      cidade_id integer   NOT NULL  , 
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
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE escritorio_parceiro( 
      id  SERIAL    NOT NULL  , 
      parceiro_id integer   NOT NULL  , 
      escritorio_id integer   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE especialidade( 
      id  SERIAL    NOT NULL  , 
      descricao text   NOT NULL  , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE estado( 
      id  SERIAL    NOT NULL  , 
      nome text   NOT NULL  , 
      sigla char  (2)   NOT NULL  , 
      codigo_ibge text   , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE estado_agenda( 
      id  SERIAL    NOT NULL  , 
      nome text   NOT NULL  , 
      estado_inicial char   NOT NULL    DEFAULT 'N', 
      estado_final char   NOT NULL    DEFAULT 'N', 
      cor varchar  (10)   NOT NULL  , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE estado_agendamento( 
      id  SERIAL    NOT NULL  , 
      agendamento_id integer   NOT NULL  , 
      estado_agenda_id integer   NOT NULL  , 
      system_users_id integer   , 
      atribuido_em timestamp   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE estado_civil( 
      id  SERIAL    NOT NULL  , 
      nome varchar  (30)   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE extrato( 
      id  SERIAL    NOT NULL  , 
      escritorio_id integer   NOT NULL  , 
      conta_caixa_id integer   NOT NULL  , 
      lancamento_id integer   , 
      categoria_conta_id integer   , 
      tipo_extrato_id integer   NOT NULL  , 
      transferencia_conta_caixa_id integer   , 
      extrato_vinculado integer   , 
      entrada_valor float   , 
      saida_valor float   , 
      data_lancamento date   , 
      data_previsao_compensacao date   , 
      compensado char   NOT NULL    DEFAULT 'N', 
      data_compensacao date   , 
      historico varchar  (3000)   , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
      mes text   , 
      ano text   , 
      ano_mes text   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE fluxo_caixa_analitico( 
      id  SERIAL    NOT NULL  , 
      dia date   NOT NULL  , 
      tipo char  (1)   NOT NULL  , 
      numero varchar  (255)   NOT NULL  , 
      historico varchar  (255)   NOT NULL  , 
      entrada float   , 
      saida float   , 
      saldo float   NOT NULL    DEFAULT 0, 
 PRIMARY KEY (id)) ; 

CREATE TABLE fluxo_caixa_sintetico( 
      id  SERIAL    NOT NULL  , 
      dia date   NOT NULL  , 
      tipo char  (1)   , 
      numero varchar  (255)   , 
      historico varchar  (255)   , 
      entrada float   , 
      saida float   , 
      saldo float   NOT NULL    DEFAULT 0, 
 PRIMARY KEY (id)) ; 

CREATE TABLE formulario( 
      id  SERIAL    NOT NULL  , 
      nome text   NOT NULL  , 
      ativo char  (1)   NOT NULL    DEFAULT 'S', 
      ordem integer   NOT NULL  , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE foro( 
      id  SERIAL    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE grupo( 
      id  SERIAL    NOT NULL  , 
      nome text   NOT NULL  , 
      cor varchar  (10)   NOT NULL  , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE jornal( 
      id  SERIAL    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE lancamento( 
      id  SERIAL    NOT NULL  , 
      conta_id integer   NOT NULL  , 
      tipo_pagamento_id integer   NOT NULL  , 
      parcela integer     DEFAULT 1, 
      dt_vencimento date   NOT NULL  , 
      valor float   NOT NULL  , 
      dt_pagamento date   , 
      ano_pagamento text   , 
      mes_pagamento text   , 
      ano_mes_pagamento text   , 
      ano_vencimento text   , 
      mes_vencimento text   , 
      ano_mes_vencimento text   , 
      cheque_numero varchar  (100)   , 
      cheque_banco_id integer   , 
      extrato_id integer   , 
      cancelado char  (1)     DEFAULT 'N', 
      motivo_cancelamento varchar  (300)   , 
      contrato_parcela_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE log_crontab( 
      system_unit_id integer   NOT NULL  , 
      id  SERIAL    NOT NULL  , 
      classe text   NOT NULL  , 
      metodo text   , 
      data_hora timestamp   , 
      status integer   , 
      mensagem text   , 
      observacao text   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE material( 
      id  SERIAL    NOT NULL  , 
      unidade_medida_id integer   NOT NULL  , 
      nome text   NOT NULL  , 
      estoque_minimo float   , 
      dt_vencimento date   , 
      estoque_atualizado float   , 
      lote text   , 
      ativo char  (1)   NOT NULL    DEFAULT 'S', 
 PRIMARY KEY (id)) ; 

CREATE TABLE mensagem( 
      id  SERIAL    NOT NULL  , 
      agendamento_id integer   NOT NULL  , 
      template_escritorio_id integer   , 
      system_user_id integer   NOT NULL  , 
      titulo text   , 
      template text   , 
      enviado_em timestamp   , 
      tipo_mensagem text   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE mensagem_acao( 
      id  SERIAL    NOT NULL  , 
      mensagem_id integer   NOT NULL  , 
      url text   , 
      label text   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE modelo_doc_aplicacao( 
      id  SERIAL    NOT NULL  , 
      modelo_documento_id integer   NOT NULL  , 
      tipo_aplicacao_id integer   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE modelo_doc_tipo_aplicacao( 
      id  SERIAL    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE modelo_documento( 
      id  SERIAL    NOT NULL  , 
      tipo_modelo_documento_id integer   NOT NULL  , 
      nome text   NOT NULL  , 
      ativo char  (1)   NOT NULL    DEFAULT 'S', 
      clausula_pagamento integer   , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE modelo_documento_pf( 
      id  SERIAL    NOT NULL  , 
      modelo_documento_id integer   NOT NULL  , 
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
 PRIMARY KEY (id)) ; 

CREATE TABLE modelo_documento_pfrep( 
      id  SERIAL    NOT NULL  , 
      modelo_documento_id integer   NOT NULL  , 
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
      endereco_rep  SERIAL    , 
      data_nascimento_rep char  (1)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE modelo_documento_pj( 
      id  SERIAL    NOT NULL  , 
      modelo_documento_id integer   NOT NULL  , 
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
 PRIMARY KEY (id)) ; 

CREATE TABLE movimentacao( 
      id  SERIAL    NOT NULL  , 
      material_id integer   NOT NULL  , 
      system_user_id integer   NOT NULL  , 
      dt_movimentacao text   , 
      quantidade float   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE nacionalidade( 
      id  SERIAL    NOT NULL  , 
      nome varchar  (30)   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE orgao( 
      id  SERIAL    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE padrao_atendimento_documento( 
      id  SERIAL    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE padrao_atend_modelo_doc( 
      id  SERIAL    NOT NULL  , 
      tipo_padrao_doc_atendimento_id integer   NOT NULL  , 
      modelo_documento_id integer   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE parceiro( 
      id  SERIAL    NOT NULL  , 
      nome text   NOT NULL  , 
      pessoa_id integer   , 
      percentual float   , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE pessoa( 
      tipo_pessoa_id integer   NOT NULL  , 
      id  SERIAL    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      nome_busca varchar  (255)   , 
      email varchar  (255)   , 
      telefone varchar  (20)   , 
      aceita_receber_mensagen_whatsapp char  (1)   NOT NULL    DEFAULT 'F', 
      system_users_id integer   , 
      dt_nascimento_abertura date   , 
      dt_falecimento date   , 
      cpf_cnpj varchar  (14)   , 
      rg_ie varchar  (15)   , 
      orgao_emissor varchar  (20)   , 
      sexo_id integer   , 
      nacionalidade_id integer   , 
      estado_civil_id integer   , 
      profissao text   , 
      nit varchar  (255)   , 
      ctps varchar  (255)   , 
      situacao_profissional_id integer   , 
      orgao varchar  (255)   , 
      unidade varchar  (255)   , 
      observacao text   , 
      assinatura text   , 
      tratamento text   , 
      tipo_profissional_id integer   , 
      orgao_registro_profissional varchar  (30)   , 
      registro_profissional varchar  (255)   , 
      usuario varchar  (255)   , 
      senha varchar  (255)   , 
      foto text   , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
      chave_aasp varchar  (255)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE pessoa_contato( 
      id  SERIAL    NOT NULL  , 
      pessoa_id integer   NOT NULL  , 
      descricao varchar  (255)   NOT NULL  , 
      telefone varchar  (20)   , 
      email varchar  (255)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE pessoa_endereco( 
      id  SERIAL    NOT NULL  , 
      pessoa_id integer   NOT NULL  , 
      cidade_id integer   NOT NULL  , 
      cep varchar  (10)   NOT NULL  , 
      rua varchar  (500)   NOT NULL  , 
      bairro varchar  (500)   NOT NULL  , 
      numero varchar  (100)   NOT NULL  , 
      complemento varchar  (500)   , 
      principal char     DEFAULT 'F', 
 PRIMARY KEY (id)) ; 

CREATE TABLE pessoa_especialidade( 
      id  SERIAL    NOT NULL  , 
      pessoa_id integer   NOT NULL  , 
      especialidade_id integer   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE pessoa_grupo( 
      id  SERIAL    NOT NULL  , 
      pessoa_id integer   NOT NULL  , 
      grupo_id integer   NOT NULL  , 
      cor varchar  (10)     DEFAULT '#ffffff', 
 PRIMARY KEY (id)) ; 

CREATE TABLE pessoa_representantes_legais( 
      id  SERIAL    NOT NULL  , 
      pessoa_juridica_id integer   NOT NULL  , 
      representante_id integer   NOT NULL  , 
      principal char  (1)   , 
      descricao varchar  (255)   NOT NULL  , 
      created_at timestamp   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE preferencia_sistema( 
      id  SERIAL    NOT NULL  , 
      system_users_id integer   NOT NULL  , 
      zoom integer   NOT NULL    DEFAULT 100, 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
      menu_fixado integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE procedimento( 
      id integer   NOT NULL  , 
      nome text   NOT NULL  , 
      cor varchar  (10)   NOT NULL  , 
      ativo char  (1)   NOT NULL    DEFAULT 'S', 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE procedimento_preco( 
      id  SERIAL    NOT NULL  , 
      procedimento_id integer   NOT NULL  , 
      parceiro_id integer   NOT NULL  , 
      valor float   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE processo( 
      id  SERIAL    NOT NULL  , 
      tipo_processo_id integer   NOT NULL  , 
      numero_cnj_numero text   NOT NULL  , 
      numero_outro text   , 
      tribunal_id integer   , 
      foro_id integer   , 
      comarca_id integer   , 
      vara_id integer   , 
      orgao_id integer   , 
      data_distribuicao_protocolo date   , 
      valor_causa float   , 
      area_id integer   , 
      assunto_id integer   , 
      gratuidade_processual char  (1)     DEFAULT 'F', 
      status_processual_id integer   , 
      responsavel_id integer   , 
      envolvimento_id integer   , 
      observacao text   , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE processo_vinculo( 
      id  SERIAL    NOT NULL  , 
      processo_principal_id integer   , 
      processo_incidente_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE publicacao( 
      id  SERIAL    NOT NULL  , 
      numero_arquivo text   , 
      numero_publicacao text   , 
      titulo text   , 
      texto text   , 
      cabecalho text   , 
      rodape text   , 
      processo_id integer   , 
      numero_unico_processo text   , 
      numero_processo_principal text   , 
      jornal_id integer   , 
      data_tratamento timestamp   , 
      data_disponibilizacao date   , 
      termo_ref_data text   , 
      prazo date   , 
      confirma_prazo char  (1)     DEFAULT 'N', 
      data_entrega date   , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE publicacao_movimentacao( 
      id  SERIAL    NOT NULL  , 
      publicacao_id integer   NOT NULL  , 
      descricao text   NOT NULL  , 
      processo_id integer   , 
      tarefa_id integer   , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE publicacao_profissional( 
      id  SERIAL    NOT NULL  , 
      publicacao_id integer   NOT NULL  , 
      profissional_id integer   NOT NULL  , 
      codigo_relacionamento text   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE publicacao_sugestao_prazo( 
      id  SERIAL    NOT NULL  , 
      publicacao_id integer   NOT NULL  , 
      config_busca_prazo_id integer   NOT NULL  , 
      resultado_busca text   NOT NULL  , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE questao( 
      id  SERIAL    NOT NULL  , 
      formulario_id integer   NOT NULL  , 
      nome text   NOT NULL  , 
      tipo_campo text   NOT NULL  , 
      fl_obrigatorio char   NOT NULL    DEFAULT 'N', 
      ativo char  (1)   NOT NULL    DEFAULT 'S', 
      opcoes text   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE resposta( 
      id  SERIAL    NOT NULL  , 
      resposta_formulario_id integer   NOT NULL  , 
      questao_id integer   NOT NULL  , 
      resposta text   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE resposta_formulario( 
      id  SERIAL    NOT NULL  , 
      formulario_id integer   NOT NULL  , 
      atendimento_id integer   NOT NULL  , 
      dt_resposta date   , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE sexo( 
      id  SERIAL    NOT NULL  , 
      nome varchar  (30)   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE situacao_profissional( 
      id  SERIAL    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE status_processual( 
      id  SERIAL    NOT NULL  , 
      tipo_processo_id integer   NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE system_group( 
      id integer   NOT NULL  , 
      name text   NOT NULL  , 
      uuid varchar  (36)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE system_group_program( 
      id integer   NOT NULL  , 
      system_group_id integer   NOT NULL  , 
      system_program_id integer   NOT NULL  , 
      actions text   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE system_preference( 
      id varchar  (255)   NOT NULL  , 
      preference text   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE system_program( 
      id integer   NOT NULL  , 
      name text   NOT NULL  , 
      controller text   NOT NULL  , 
      actions text   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE system_unit( 
      id integer   NOT NULL  , 
      name text   NOT NULL  , 
      connection_name text   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE system_user_group( 
      id integer   NOT NULL  , 
      system_user_id integer   NOT NULL  , 
      system_group_id integer   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE system_user_program( 
      id integer   NOT NULL  , 
      system_user_id integer   NOT NULL  , 
      system_program_id integer   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE system_users( 
      id integer   NOT NULL  , 
      name text   NOT NULL  , 
      login text   NOT NULL  , 
      password text   NOT NULL  , 
      email text   , 
      frontpage_id integer   , 
      system_unit_id integer   , 
      active char  (1)   , 
      accepted_term_policy_at text   , 
      accepted_term_policy char  (1)   , 
      two_factor_enabled char  (1)     DEFAULT 'N', 
      two_factor_type varchar  (100)   , 
      two_factor_secret varchar  (255)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE system_user_unit( 
      id integer   NOT NULL  , 
      system_user_id integer   NOT NULL  , 
      system_unit_id integer   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tarefa( 
      id  SERIAL    NOT NULL  , 
      tarefa_status_id integer   NOT NULL  , 
      publicacao_id integer   , 
      processo_id integer   , 
      usuario_destinatario_id integer   NOT NULL  , 
      titulo varchar  (255)   NOT NULL  , 
      data_disponibilizacao timestamp   , 
      prazo_validacao date   , 
      prazo_entrega date   NOT NULL  , 
      observacao text   , 
      data_entrega timestamp   , 
      arquivado char  (1)     DEFAULT 'N', 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
      prazo_processual char  (1)     DEFAULT 'N', 
 PRIMARY KEY (id)) ; 

CREATE TABLE tarefa_cliente( 
      id  SERIAL    NOT NULL  , 
      tarefa_id integer   NOT NULL  , 
      cliente_id integer   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tarefa_comentario( 
      id  SERIAL    NOT NULL  , 
      tarefa_id integer   NOT NULL  , 
      texto text   NOT NULL  , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tarefa_configuracao( 
      id  SERIAL    NOT NULL  , 
      status_inicial_id integer   NOT NULL  , 
      status_final_id integer   NOT NULL  , 
      status_cancelado_id integer   NOT NULL  , 
      tem_dtvalidacao char  (1)     DEFAULT 'N', 
      dtvalidacao_obrigatoria char  (1)     DEFAULT 'N', 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tarefa_horas_trabalhadas( 
      id  SERIAL    NOT NULL  , 
      tarefa_id integer   NOT NULL  , 
      data_inicio timestamp   NOT NULL  , 
      data_fim timestamp   , 
      observacao text   , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tarefa_movimentacao( 
      id  SERIAL    NOT NULL  , 
      tarefa_id integer   NOT NULL  , 
      descricao text   , 
      data_movimentacao timestamp   NOT NULL  , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tarefa_status( 
      id  SERIAL    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      kanban integer   NOT NULL  , 
      inicio char  (1)   , 
      fim char  (1)   , 
      cor varchar  (10)   NOT NULL  , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tarefa_usuario_master( 
      id  SERIAL    NOT NULL  , 
      tarefa_configuracao_id integer   NOT NULL  , 
      usuario_master_id integer   NOT NULL  , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tarefa_vinculo( 
      id  SERIAL    NOT NULL  , 
      tarefa_id integer   NOT NULL  , 
      subtarefa_id integer   NOT NULL  , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE template_acao( 
      id  SERIAL    NOT NULL  , 
      template_escritorio_id integer   NOT NULL  , 
      url text   , 
      label text   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE template_escritorio( 
      id  SERIAL    NOT NULL  , 
      escritorio_id integer   NOT NULL  , 
      chave text   NOT NULL  , 
      descricao text   NOT NULL  , 
      habilitado char  (1)   NOT NULL    DEFAULT 'T', 
      template text   , 
      titulo text   , 
      tipo_template text   , 
      readonly char  (1)   NOT NULL    DEFAULT 'F', 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tipo_andamento( 
      id  SERIAL    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tipo_atendimento( 
      id  SERIAL    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tipo_compromisso( 
      id  SERIAL    NOT NULL  , 
      nome text   NOT NULL  , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tipo_conta( 
      id  SERIAL    NOT NULL  , 
      nome text   NOT NULL  , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tipo_conta_caixa( 
      id  SERIAL    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tipo_doc_financeiro_padrao( 
      id  SERIAL    NOT NULL  , 
      nome varchar  (30)   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tipo_documento_financeiro( 
      id  SERIAL    NOT NULL  , 
      codigo varchar  (4)   NOT NULL    DEFAULT 'Man', 
      nome varchar  (255)   NOT NULL  , 
      tipo_conta_id integer   NOT NULL  , 
      gera_codigo char  (1)   NOT NULL    DEFAULT 'N', 
      padrao_id integer   NOT NULL  , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tipo_extrato( 
      id  SERIAL    NOT NULL  , 
      nome varchar  (50)   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tipo_modelo_documento( 
      id  SERIAL    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tipo_pagamento( 
      id  SERIAL    NOT NULL  , 
      nome text   NOT NULL  , 
      ativo char  (1)   NOT NULL    DEFAULT 'S', 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tipo_pessoa( 
      id  SERIAL    NOT NULL  , 
      nome varchar  (20)   NOT NULL  , 
      sigla char  (2)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tipo_prazo( 
      id  SERIAL    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tipo_processo( 
      id  SERIAL    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tipo_profissional( 
      id  SERIAL    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tmp_documento( 
      id  SERIAL    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      filename text   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tribunal( 
      id  SERIAL    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE unidade_indexador( 
      id  SERIAL    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      extenso text   , 
      simbolo varchar  (10)   , 
      criacao_user_id integer   , 
      data_criacao timestamp   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE unidade_medida( 
      id  SERIAL    NOT NULL  , 
      nome text   NOT NULL  , 
      sigla text   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE vara( 
      id  SERIAL    NOT NULL  , 
      nome varchar  (255)   NOT NULL  , 
      data_criacao timestamp   , 
      criacao_user_id integer   , 
      data_modificacao timestamp   , 
      modificacao_user_id integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE video( 
      id  SERIAL    NOT NULL  , 
      nome varchar  (255)   , 
      url text   , 
      tag_iframe text   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE whatsapp_config( 
      id  SERIAL    NOT NULL  , 
      escritorio_id integer   NOT NULL  , 
      phone text   , 
      status text   , 
      api_token text   , 
      api_key text   , 
 PRIMARY KEY (id)) ; 

 
 ALTER TABLE cep_cache ADD UNIQUE (cep);
  
 ALTER TABLE agenda ADD CONSTRAINT fk_agenda_3 FOREIGN KEY (procedimento_id) references procedimento(id); 
ALTER TABLE agenda ADD CONSTRAINT fk_agenda_1 FOREIGN KEY (escritorio_id) references escritorio(id); 
ALTER TABLE agenda ADD CONSTRAINT fk_agenda_2 FOREIGN KEY (profissional_id) references pessoa(id); 
ALTER TABLE agenda ADD CONSTRAINT fk_agenda_4 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE agenda ADD CONSTRAINT fk_agenda_5 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE agendamento ADD CONSTRAINT fk_agendamento_1 FOREIGN KEY (cliente_id) references pessoa(id); 
ALTER TABLE agendamento ADD CONSTRAINT fk_agendamento_2 FOREIGN KEY (estado_agenda_id) references estado_agenda(id); 
ALTER TABLE agendamento ADD CONSTRAINT fk_agendamento_3 FOREIGN KEY (agenda_id) references agenda(id); 
ALTER TABLE agendamento ADD CONSTRAINT fk_agendamento_4 FOREIGN KEY (especialidade_id) references especialidade(id); 
ALTER TABLE agendamento_procedimento ADD CONSTRAINT fk_agendamento_procedimento_1 FOREIGN KEY (agendamento_id) references agendamento(id); 
ALTER TABLE agendamento_procedimento ADD CONSTRAINT fk_agendamento_procedimento_2 FOREIGN KEY (procedimento_id) references procedimento(id); 
ALTER TABLE agendamento_procedimento ADD CONSTRAINT fk_agendamento_procedimento_3 FOREIGN KEY (parceiro_id) references parceiro(id); 
ALTER TABLE agenda_profissional ADD CONSTRAINT fk_agenda_profissional_1 FOREIGN KEY (profissional_id) references pessoa(id); 
ALTER TABLE agenda_profissional ADD CONSTRAINT fk_agenda_profissional_2 FOREIGN KEY (agenda_id) references agenda(id); 
ALTER TABLE andamento ADD CONSTRAINT fk_andamento_4 FOREIGN KEY (tipo_andamento_id) references tipo_andamento(id); 
ALTER TABLE andamento ADD CONSTRAINT fk_andamento_1 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE andamento ADD CONSTRAINT fk_andamento_2 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE andamento ADD CONSTRAINT fk_andamento_3 FOREIGN KEY (processo_id) references processo(id); 
ALTER TABLE anexo ADD CONSTRAINT fk_anexo_1 FOREIGN KEY (atendimento_id) references atendimento(id); 
ALTER TABLE anexo ADD CONSTRAINT fk_anexo_2 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE anexo ADD CONSTRAINT fk_anexo_3 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE area ADD CONSTRAINT fk_tipo_contrato_1_53814656486e5de481 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE area ADD CONSTRAINT fk_tipo_contrato_2_53814656486e5de481 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE assunto ADD CONSTRAINT fk_assunto_processo_1 FOREIGN KEY (area_id) references area(id); 
ALTER TABLE assunto ADD CONSTRAINT fk_assunto_processo_2 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE atendimento ADD CONSTRAINT fk_atendimento_3 FOREIGN KEY (profissional_id) references pessoa(id); 
ALTER TABLE atendimento ADD CONSTRAINT fk_atendimento_1 FOREIGN KEY (agendamento_id) references agendamento(id); 
ALTER TABLE atendimento ADD CONSTRAINT fk_atendimento_7 FOREIGN KEY (tipo_atendimento_id) references tipo_atendimento(id); 
ALTER TABLE atendimento ADD CONSTRAINT fk_atendimento_4 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE atendimento ADD CONSTRAINT fk_atendimento_5 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE atendimento ADD CONSTRAINT fk_atendimento_2 FOREIGN KEY (cliente_id) references pessoa(id); 
ALTER TABLE atendimento_contrato ADD CONSTRAINT fk_atendimento_contrato_1 FOREIGN KEY (atendimento_id) references atendimento(id); 
ALTER TABLE atendimento_contrato ADD CONSTRAINT fk_atendimento_contrato_2 FOREIGN KEY (contrato_id) references contrato(id); 
ALTER TABLE atendimento_historico ADD CONSTRAINT fk_atendimento_historico_1 FOREIGN KEY (atendimento_id) references atendimento(id); 
ALTER TABLE atendimento_historico ADD CONSTRAINT fk_atendimento_historico_2 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE atendimento_historico ADD CONSTRAINT fk_atendimento_historico_3 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE atendimento_material ADD CONSTRAINT fk_atendimento_material_1 FOREIGN KEY (material_id) references material(id); 
ALTER TABLE atendimento_material ADD CONSTRAINT fk_atendimento_material_2 FOREIGN KEY (atendimento_id) references atendimento(id); 
ALTER TABLE atendimento_procedimento ADD CONSTRAINT fk_atendimento_procedimento_1 FOREIGN KEY (atendimento_id) references atendimento(id); 
ALTER TABLE atendimento_procedimento ADD CONSTRAINT fk_atendimento_procedimento_2 FOREIGN KEY (procedimento_id) references procedimento(id); 
ALTER TABLE atendimento_procedimento ADD CONSTRAINT fk_atendimento_procedimento_3 FOREIGN KEY (parceiro_id) references parceiro(id); 
ALTER TABLE banco ADD CONSTRAINT fk_banco_1 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE banco ADD CONSTRAINT fk_banco_2 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE bloqueio ADD CONSTRAINT fk_bloqueio_1 FOREIGN KEY (agenda_id) references agenda(id); 
ALTER TABLE bloqueio ADD CONSTRAINT fk_bloqueio_2 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE bloqueio ADD CONSTRAINT fk_bloqueio_3 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE categoria_conta ADD CONSTRAINT fk_categoria_conta_1 FOREIGN KEY (tipo_conta_id) references tipo_conta(id); 
ALTER TABLE categoria_conta ADD CONSTRAINT fk_categoria_conta_2 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE categoria_conta ADD CONSTRAINT fk_categoria_conta_3 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE cidade ADD CONSTRAINT fk_cidade_1 FOREIGN KEY (estado_id) references estado(id); 
ALTER TABLE cidade ADD CONSTRAINT fk_cidade_2 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE cidade ADD CONSTRAINT fk_cidade_3 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE classificacoes ADD CONSTRAINT fk_classificacoes_1 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE classificacoes ADD CONSTRAINT fk_classificacoes_3 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE classificacoes_cliente ADD CONSTRAINT fk_classificacoes_cliente_1 FOREIGN KEY (pessoa_id) references pessoa(id); 
ALTER TABLE classificacoes_cliente ADD CONSTRAINT fk_classificacoes_cliente_2 FOREIGN KEY (classificacoes_id) references classificacoes(id); 
ALTER TABLE classificacoes_contraparte ADD CONSTRAINT fk_classificacoes_contraparte_1 FOREIGN KEY (contraparte_id) references contraparte(id); 
ALTER TABLE classificacoes_contraparte ADD CONSTRAINT fk_classificacoes_contraparte_3 FOREIGN KEY (pessoa_id) references pessoa(id); 
ALTER TABLE classificacoes_contraparte ADD CONSTRAINT fk_classificacoes_contraparte_3 FOREIGN KEY (classificacoes_contraparte_dados_id) references classificacoes_contraparte_dados(id); 
ALTER TABLE classificacoes_contraparte_dados ADD CONSTRAINT fk_classificacao_contra1 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE classificacoes_contraparte_dados ADD CONSTRAINT fk_classificacoes_contraparte_dados_3 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE comarca ADD CONSTRAINT fk_comarca_1 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE comarca ADD CONSTRAINT fk_comarca_2 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE compromisso ADD CONSTRAINT fk_bloqueio_1_b64b15067e0267 FOREIGN KEY (agenda_id) references agenda(id); 
ALTER TABLE compromisso ADD CONSTRAINT fk_bloqueio_2_b64b15067e0267 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE compromisso ADD CONSTRAINT fk_bloqueio_3_b64b15067e0267 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE compromisso ADD CONSTRAINT fk_compromisso_4 FOREIGN KEY (tipo_compromisso_id) references tipo_compromisso(id); 
ALTER TABLE config_busca_a_partir ADD CONSTRAINT fk_config_busca_a_partir_1 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE config_busca_a_partir ADD CONSTRAINT fk_config_busca_a_partir_2 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE config_busca_prazo ADD CONSTRAINT fk_config_ia_busca_prazo_1 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE config_busca_prazo ADD CONSTRAINT fk_config_ia_busca_prazo_2 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE config_busca_prazo ADD CONSTRAINT fk_config_ia_busca_prazo_3 FOREIGN KEY (tipo_prazo_id) references tipo_prazo(id); 
ALTER TABLE config_busca_prazo ADD CONSTRAINT fk_config_busca_prazo_4 FOREIGN KEY (config_busca_a_partir_id) references config_busca_a_partir(id); 
ALTER TABLE config_busca_prazo_texto ADD CONSTRAINT fk_config_busca_prazo_texto_1 FOREIGN KEY (config_busca_prazo_id) references config_busca_prazo(id); 
ALTER TABLE conta ADD CONSTRAINT fk_conta_6 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE conta ADD CONSTRAINT fk_conta_7 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE conta ADD CONSTRAINT fk_conta_2 FOREIGN KEY (tipo_conta_id) references tipo_conta(id); 
ALTER TABLE conta ADD CONSTRAINT fk_conta_3 FOREIGN KEY (pessoa_id) references pessoa(id); 
ALTER TABLE conta ADD CONSTRAINT fk_conta_4 FOREIGN KEY (atendimento_id) references atendimento(id); 
ALTER TABLE conta ADD CONSTRAINT fk_conta_5 FOREIGN KEY (escritorio_id) references escritorio(id); 
ALTER TABLE conta ADD CONSTRAINT fk_conta_1 FOREIGN KEY (categoria_conta_id) references categoria_conta(id); 
ALTER TABLE conta ADD CONSTRAINT fk_conta_9 FOREIGN KEY (tipo_documento_financeiro_id) references tipo_documento_financeiro(id); 
ALTER TABLE conta ADD CONSTRAINT fk_conta_8 FOREIGN KEY (profissional_id) references pessoa(id); 
ALTER TABLE conta ADD CONSTRAINT fk_conta_10 FOREIGN KEY (contrato_id) references contrato(id); 
ALTER TABLE conta ADD CONSTRAINT fk_conta_11 FOREIGN KEY (processo_id) references processo(id); 
ALTER TABLE conta_caixa ADD CONSTRAINT fk_conta_caixa_1 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE conta_caixa ADD CONSTRAINT fk_conta_caixa_2 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE conta_caixa ADD CONSTRAINT fk_conta_caixa_3 FOREIGN KEY (tipo_conta_caixa_id) references tipo_conta_caixa(id); 
ALTER TABLE conta_caixa ADD CONSTRAINT fk_conta_caixa_4 FOREIGN KEY (banco_id) references banco(id); 
ALTER TABLE contraparte ADD CONSTRAINT fk_contraparte_1 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE contraparte ADD CONSTRAINT fk_contraparte_2 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE contraparte ADD CONSTRAINT fk_contraparte_3 FOREIGN KEY (processo_id) references processo(id); 
ALTER TABLE contraparte ADD CONSTRAINT fk_contraparte_4 FOREIGN KEY (pessoa_id) references pessoa(id); 
ALTER TABLE contrato ADD CONSTRAINT fk_contratos_1 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE contrato ADD CONSTRAINT fk_contratos_2 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE contrato ADD CONSTRAINT fk_contrato_3 FOREIGN KEY (escritorio_id) references escritorio(id); 
ALTER TABLE contrato ADD CONSTRAINT fk_contrato_6 FOREIGN KEY (envolvimento_id) references envolvimento(id); 
ALTER TABLE contrato ADD CONSTRAINT fk_contrato_5 FOREIGN KEY (area_id) references area(id); 
ALTER TABLE contrato ADD CONSTRAINT fk_contrato_6 FOREIGN KEY (assunto_id) references assunto(id); 
ALTER TABLE contrato ADD CONSTRAINT fk_contrato_7 FOREIGN KEY (tipo_processo_id) references tipo_processo(id); 
ALTER TABLE contrato ADD CONSTRAINT fk_contrato_8 FOREIGN KEY (contrato_status_id) references contrato_status(id); 
ALTER TABLE contrato_documento ADD CONSTRAINT fk_contrato_documento_3 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE contrato_documento ADD CONSTRAINT fk_contrato_documento_4 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE contrato_documento ADD CONSTRAINT fk_documento_1_b64b15067e5d03 FOREIGN KEY (modelo_documento_id) references modelo_documento(id); 
ALTER TABLE contrato_documento ADD CONSTRAINT fk_documento_clone_5381464de33238bbe6_4 FOREIGN KEY (contrato_id) references contrato(id); 
ALTER TABLE contrato_pagamento_evento ADD CONSTRAINT fk_contrato_evento_1 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE contrato_pagamento_evento ADD CONSTRAINT fk_contrato_evento_2 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE contrato_pagamento_indexador ADD CONSTRAINT fk_contrato_indexador_1 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE contrato_pagamento_indexador ADD CONSTRAINT fk_contrato_indexador_2 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE contrato_pagamento_opcao ADD CONSTRAINT fk_contrato_opcao_pagamento_1 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE contrato_pagamento_opcao ADD CONSTRAINT fk_contrato_opcao_pagamento_2 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE contrato_pagamento_parcela ADD CONSTRAINT fk_contrato_pagamento_parcela_2 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE contrato_pagamento_parcela ADD CONSTRAINT fk_contrato_pagamento_parcela_3 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE contrato_pagamento_parcela ADD CONSTRAINT fk_contrato_pagamento_parcela_4 FOREIGN KEY (contrato_opcao_pagamento_id) references contrato_pagamento_opcao(id); 
ALTER TABLE contrato_pagamento_parcela ADD CONSTRAINT fk_contrato_pagamento_parcela_5 FOREIGN KEY (contrato_evento_id) references contrato_pagamento_evento(id); 
ALTER TABLE contrato_pagamento_parcela ADD CONSTRAINT fk_contrato_pagamento_parcela_6 FOREIGN KEY (contrato_indexador_id) references contrato_pagamento_indexador(id); 
ALTER TABLE contrato_pagamento_parcela ADD CONSTRAINT fk_contrato_pagamento_parcela_6 FOREIGN KEY (contrato_id) references contrato(id); 
ALTER TABLE contrato_pagamento_parcela ADD CONSTRAINT fk_contrato_pagamento_parcela_7 FOREIGN KEY (unidade_indexador_id) references unidade_indexador(id); 
ALTER TABLE contrato_pessoa ADD CONSTRAINT fk_contrato_pessoa_1 FOREIGN KEY (cliente_id) references pessoa(id); 
ALTER TABLE contrato_pessoa ADD CONSTRAINT fk_contrato_pessoa_2 FOREIGN KEY (contrato_id) references contrato(id); 
ALTER TABLE contrato_processo ADD CONSTRAINT fk_contrato_processo_1 FOREIGN KEY (contrato_id) references contrato(id); 
ALTER TABLE contrato_processo ADD CONSTRAINT fk_contrato_processo_2 FOREIGN KEY (processo_id) references processo(id); 
ALTER TABLE contrato_processo ADD CONSTRAINT fk_contrato_processo_3 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE contrato_processo ADD CONSTRAINT fk_contrato_processo_4 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE contrato_repasse ADD CONSTRAINT fk_contrato_profissional_1 FOREIGN KEY (pessoa_id) references pessoa(id); 
ALTER TABLE contrato_repasse ADD CONSTRAINT fk_contrato_profissional_2 FOREIGN KEY (contrato_id) references contrato(id); 
ALTER TABLE contrato_representante ADD CONSTRAINT fk_contrato_pessoa_2_5381464db893f6598a FOREIGN KEY (contrato_id) references contrato(id); 
ALTER TABLE contrato_representante ADD CONSTRAINT fk_contrato_pessoa_1_5381464db893f6598a FOREIGN KEY (representante_id) references pessoa(id); 
ALTER TABLE convidado ADD CONSTRAINT fk_convidado_1 FOREIGN KEY (agenda_id) references agenda(id); 
ALTER TABLE convidado ADD CONSTRAINT fk_convidado_2 FOREIGN KEY (agendamento_id) references agendamento(id); 
ALTER TABLE convidado ADD CONSTRAINT fk_convidado_3 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE convidado ADD CONSTRAINT fk_convidado_4 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE convidado_compromisso ADD CONSTRAINT fk_convidado_1_5381464d4faed0967d FOREIGN KEY (agenda_id) references agenda(id); 
ALTER TABLE convidado_compromisso ADD CONSTRAINT fk_convidado_3_5381464d4faed0967d FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE convidado_compromisso ADD CONSTRAINT fk_convidado_4_5381464d4faed0967d FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE convidado_compromisso ADD CONSTRAINT fk_convidado_compromisso_4 FOREIGN KEY (compromisso_id) references compromisso(id); 
ALTER TABLE documento ADD CONSTRAINT fk_documento_1 FOREIGN KEY (modelo_documento_id) references modelo_documento(id); 
ALTER TABLE documento ADD CONSTRAINT fk_documento_2 FOREIGN KEY (atendimento_id) references atendimento(id); 
ALTER TABLE documento ADD CONSTRAINT fk_documento_3 FOREIGN KEY (procedimento_id) references procedimento(id); 
ALTER TABLE documento ADD CONSTRAINT fk_documento_4 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE documento ADD CONSTRAINT fk_documento_5 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE documento_base_contrato ADD CONSTRAINT fk_documento_base_contrato_2 FOREIGN KEY (area_id) references area(id); 
ALTER TABLE documento_base_contrato ADD CONSTRAINT fk_documento_base_contrato_2 FOREIGN KEY (modelo_documento_id) references modelo_documento(id); 
ALTER TABLE envolvimento ADD CONSTRAINT fk_envolvimento_1 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE envolvimento ADD CONSTRAINT fk_envolvimento_2 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE envolvimento ADD CONSTRAINT fk_envolvimento_3 FOREIGN KEY (tipo_processo_id) references tipo_processo(id); 
ALTER TABLE escritorio ADD CONSTRAINT fk_clinica_1 FOREIGN KEY (system_unit_id) references system_unit(id); 
ALTER TABLE escritorio ADD CONSTRAINT fk_escritorio_6 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE escritorio ADD CONSTRAINT fk_clinica_2 FOREIGN KEY (cidade_id) references cidade(id); 
ALTER TABLE escritorio ADD CONSTRAINT fk_escritorio_5 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE escritorio_parceiro ADD CONSTRAINT fk_clinica_convenio_2 FOREIGN KEY (escritorio_id) references escritorio(id); 
ALTER TABLE escritorio_parceiro ADD CONSTRAINT fk_clinica_convenio_1 FOREIGN KEY (parceiro_id) references parceiro(id); 
ALTER TABLE especialidade ADD CONSTRAINT fk_especialidade_1 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE especialidade ADD CONSTRAINT fk_especialidade_2 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE estado ADD CONSTRAINT fk_estado_1 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE estado ADD CONSTRAINT fk_estado_2 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE estado_agenda ADD CONSTRAINT fk_estado_agenda_1 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE estado_agendamento ADD CONSTRAINT fk_estado_agendamento_1 FOREIGN KEY (agendamento_id) references agendamento(id); 
ALTER TABLE estado_agendamento ADD CONSTRAINT fk_estado_agendamento_2 FOREIGN KEY (estado_agenda_id) references estado_agenda(id); 
ALTER TABLE estado_agendamento ADD CONSTRAINT fk_estado_agendamento_3 FOREIGN KEY (system_users_id) references system_users(id); 
ALTER TABLE extrato ADD CONSTRAINT fk_extrato_1 FOREIGN KEY (conta_caixa_id) references conta_caixa(id); 
ALTER TABLE extrato ADD CONSTRAINT fk_extrato_2 FOREIGN KEY (escritorio_id) references escritorio(id); 
ALTER TABLE extrato ADD CONSTRAINT fk_extrato_3 FOREIGN KEY (lancamento_id) references lancamento(id); 
ALTER TABLE extrato ADD CONSTRAINT fk_extrato_4 FOREIGN KEY (categoria_conta_id) references categoria_conta(id); 
ALTER TABLE extrato ADD CONSTRAINT fk_extrato_5 FOREIGN KEY (tipo_extrato_id) references tipo_extrato(id); 
ALTER TABLE extrato ADD CONSTRAINT fk_extrato_6 FOREIGN KEY (transferencia_conta_caixa_id) references conta_caixa(id); 
ALTER TABLE extrato ADD CONSTRAINT fk_extrato_7 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE extrato ADD CONSTRAINT fk_extrato_8 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE formulario ADD CONSTRAINT fk_formulario_1 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE formulario ADD CONSTRAINT fk_formulario_2 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE foro ADD CONSTRAINT fk_foro_1 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE foro ADD CONSTRAINT fk_foro_2 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE grupo ADD CONSTRAINT fk_grupo_1 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE grupo ADD CONSTRAINT fk_grupo_2 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE jornal ADD CONSTRAINT fk_jornal_1 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE jornal ADD CONSTRAINT fk_jornal_2 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE lancamento ADD CONSTRAINT fk_lancamento_5 FOREIGN KEY (contrato_parcela_id) references contrato_pagamento_parcela(id); 
ALTER TABLE lancamento ADD CONSTRAINT fk_lancamento_3 FOREIGN KEY (cheque_banco_id) references banco(id); 
ALTER TABLE lancamento ADD CONSTRAINT fk_lancamento_4 FOREIGN KEY (extrato_id) references extrato(id); 
ALTER TABLE lancamento ADD CONSTRAINT fk_lancamento_1 FOREIGN KEY (conta_id) references conta(id); 
ALTER TABLE lancamento ADD CONSTRAINT fk_lancamento_2 FOREIGN KEY (tipo_pagamento_id) references tipo_pagamento(id); 
ALTER TABLE log_crontab ADD CONSTRAINT fk_log_crontab_1 FOREIGN KEY (system_unit_id) references system_unit(id); 
ALTER TABLE material ADD CONSTRAINT fk_material_1 FOREIGN KEY (unidade_medida_id) references unidade_medida(id); 
ALTER TABLE mensagem ADD CONSTRAINT fk_message_1 FOREIGN KEY (agendamento_id) references agendamento(id); 
ALTER TABLE mensagem ADD CONSTRAINT fk_message_2 FOREIGN KEY (template_escritorio_id) references template_escritorio(id); 
ALTER TABLE mensagem ADD CONSTRAINT fk_message_3 FOREIGN KEY (system_user_id) references system_users(id); 
ALTER TABLE mensagem_acao ADD CONSTRAINT fk_mensagem_acao_1 FOREIGN KEY (mensagem_id) references mensagem(id); 
ALTER TABLE modelo_doc_aplicacao ADD CONSTRAINT fk_tipo_doc_aplicacao_1 FOREIGN KEY (modelo_documento_id) references modelo_documento(id); 
ALTER TABLE modelo_doc_aplicacao ADD CONSTRAINT fk_tipo_doc_aplicacao_2 FOREIGN KEY (tipo_aplicacao_id) references modelo_doc_tipo_aplicacao(id); 
ALTER TABLE modelo_documento ADD CONSTRAINT fk_tipo_documento_1 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE modelo_documento ADD CONSTRAINT fk_tipo_documento_2 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE modelo_documento ADD CONSTRAINT fk_modelo_documento_3 FOREIGN KEY (tipo_modelo_documento_id) references tipo_modelo_documento(id); 
ALTER TABLE modelo_documento_pf ADD CONSTRAINT fk_modelo_documento_pf_1 FOREIGN KEY (modelo_documento_id) references modelo_documento(id); 
ALTER TABLE modelo_documento_pfrep ADD CONSTRAINT fk_modelo_documento_pfrep_1 FOREIGN KEY (modelo_documento_id) references modelo_documento(id); 
ALTER TABLE modelo_documento_pj ADD CONSTRAINT fk_modelo_documento_pj_1 FOREIGN KEY (modelo_documento_id) references modelo_documento(id); 
ALTER TABLE movimentacao ADD CONSTRAINT fk_movimentacao_1 FOREIGN KEY (material_id) references material(id); 
ALTER TABLE movimentacao ADD CONSTRAINT fk_movimentacao_2 FOREIGN KEY (system_user_id) references system_users(id); 
ALTER TABLE orgao ADD CONSTRAINT fk_foro_1_5381465b926567fd57_5381465ba3bcbf2346 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE orgao ADD CONSTRAINT fk_foro_2_5381465b926567fd57_5381465ba3bcbf2346 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE padrao_atendimento_documento ADD CONSTRAINT fk_padrao_atendimento_documento_1 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE padrao_atendimento_documento ADD CONSTRAINT fk_padrao_atendimento_documento_2 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE padrao_atend_modelo_doc ADD CONSTRAINT fk_new_table_95_1 FOREIGN KEY (tipo_padrao_doc_atendimento_id) references padrao_atendimento_documento(id); 
ALTER TABLE padrao_atend_modelo_doc ADD CONSTRAINT fk_new_table_95_2 FOREIGN KEY (modelo_documento_id) references modelo_documento(id); 
ALTER TABLE parceiro ADD CONSTRAINT fk_paceiro_1 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE parceiro ADD CONSTRAINT fk_paceiro_2 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE parceiro ADD CONSTRAINT fk_parceiro_3 FOREIGN KEY (pessoa_id) references pessoa(id); 
ALTER TABLE pessoa ADD CONSTRAINT fk_pessoa_9 FOREIGN KEY (tipo_profissional_id) references tipo_profissional(id); 
ALTER TABLE pessoa ADD CONSTRAINT fk_pessoa_1 FOREIGN KEY (system_users_id) references system_users(id); 
ALTER TABLE pessoa ADD CONSTRAINT fk_pessoa_2 FOREIGN KEY (tipo_pessoa_id) references tipo_pessoa(id); 
ALTER TABLE pessoa ADD CONSTRAINT fk_pessoa_3 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE pessoa ADD CONSTRAINT fk_pessoa_4 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE pessoa ADD CONSTRAINT fk_pessoa_5 FOREIGN KEY (sexo_id) references sexo(id); 
ALTER TABLE pessoa ADD CONSTRAINT fk_pessoa_6 FOREIGN KEY (nacionalidade_id) references nacionalidade(id); 
ALTER TABLE pessoa ADD CONSTRAINT fk_pessoa_7 FOREIGN KEY (estado_civil_id) references estado_civil(id); 
ALTER TABLE pessoa ADD CONSTRAINT fk_pessoa_8 FOREIGN KEY (situacao_profissional_id) references situacao_profissional(id); 
ALTER TABLE pessoa_contato ADD CONSTRAINT fk_pessoa_contato_1 FOREIGN KEY (pessoa_id) references pessoa(id); 
ALTER TABLE pessoa_endereco ADD CONSTRAINT fk_pessoa_endereco_1 FOREIGN KEY (pessoa_id) references pessoa(id); 
ALTER TABLE pessoa_endereco ADD CONSTRAINT fk_pessoa_endereco_2 FOREIGN KEY (cidade_id) references cidade(id); 
ALTER TABLE pessoa_especialidade ADD CONSTRAINT fk_pessoa_especialidade_1 FOREIGN KEY (pessoa_id) references pessoa(id); 
ALTER TABLE pessoa_especialidade ADD CONSTRAINT fk_pessoa_especialidade_2 FOREIGN KEY (especialidade_id) references especialidade(id); 
ALTER TABLE pessoa_grupo ADD CONSTRAINT fk_pessoa_grupo_1 FOREIGN KEY (pessoa_id) references pessoa(id); 
ALTER TABLE pessoa_grupo ADD CONSTRAINT fk_pessoa_grupo_2 FOREIGN KEY (grupo_id) references grupo(id); 
ALTER TABLE pessoa_representantes_legais ADD CONSTRAINT fk_pessoa_representantes_legais_1 FOREIGN KEY (pessoa_juridica_id) references pessoa(id); 
ALTER TABLE pessoa_representantes_legais ADD CONSTRAINT fk_pessoa_representantes_legais_2 FOREIGN KEY (representante_id) references pessoa(id); 
ALTER TABLE preferencia_sistema ADD CONSTRAINT fk_preferencia_sistema_1 FOREIGN KEY (system_users_id) references system_users(id); 
ALTER TABLE procedimento ADD CONSTRAINT fk_procedimento_1 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE procedimento ADD CONSTRAINT fk_procedimento_2 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE procedimento_preco ADD CONSTRAINT fk_procedimento_preco_1 FOREIGN KEY (procedimento_id) references procedimento(id); 
ALTER TABLE procedimento_preco ADD CONSTRAINT fk_procedimento_preco_2 FOREIGN KEY (parceiro_id) references parceiro(id); 
ALTER TABLE processo ADD CONSTRAINT fk_processo_2 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE processo ADD CONSTRAINT fk_processo_3 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE processo ADD CONSTRAINT fk_processo_13 FOREIGN KEY (envolvimento_id) references envolvimento(id); 
ALTER TABLE processo ADD CONSTRAINT fk_processo_4 FOREIGN KEY (tipo_processo_id) references tipo_processo(id); 
ALTER TABLE processo ADD CONSTRAINT fk_processo_5 FOREIGN KEY (tribunal_id) references tribunal(id); 
ALTER TABLE processo ADD CONSTRAINT fk_processo_6 FOREIGN KEY (foro_id) references foro(id); 
ALTER TABLE processo ADD CONSTRAINT fk_processo_7 FOREIGN KEY (comarca_id) references comarca(id); 
ALTER TABLE processo ADD CONSTRAINT fk_processo_7 FOREIGN KEY (assunto_id) references assunto(id); 
ALTER TABLE processo ADD CONSTRAINT fk_processo_8 FOREIGN KEY (area_id) references area(id); 
ALTER TABLE processo ADD CONSTRAINT fk_processo_9 FOREIGN KEY (responsavel_id) references pessoa(id); 
ALTER TABLE processo ADD CONSTRAINT fk_processo_10 FOREIGN KEY (status_processual_id) references status_processual(id); 
ALTER TABLE processo ADD CONSTRAINT fk_processo_11 FOREIGN KEY (vara_id) references vara(id); 
ALTER TABLE processo ADD CONSTRAINT fk_processo_12 FOREIGN KEY (orgao_id) references orgao(id); 
ALTER TABLE processo_vinculo ADD CONSTRAINT fk_processo_vinculo_1 FOREIGN KEY (processo_principal_id) references processo(id); 
ALTER TABLE processo_vinculo ADD CONSTRAINT fk_processo_vinculo_2 FOREIGN KEY (processo_incidente_id) references processo(id); 
ALTER TABLE publicacao ADD CONSTRAINT fk_andamento_4 FOREIGN KEY (processo_id) references processo(id); 
ALTER TABLE publicacao ADD CONSTRAINT fk_andamentos_1 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE publicacao ADD CONSTRAINT fk_andamentos_2 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE publicacao ADD CONSTRAINT fk_andamento_4 FOREIGN KEY (jornal_id) references jornal(id); 
ALTER TABLE publicacao_movimentacao ADD CONSTRAINT fk_publicacao_movimentacao_1 FOREIGN KEY (publicacao_id) references publicacao(id); 
ALTER TABLE publicacao_movimentacao ADD CONSTRAINT fk_publicacao_movimentacao_2 FOREIGN KEY (processo_id) references processo(id); 
ALTER TABLE publicacao_movimentacao ADD CONSTRAINT fk_publicacao_movimentacao_3 FOREIGN KEY (tarefa_id) references tarefa(id); 
ALTER TABLE publicacao_movimentacao ADD CONSTRAINT fk_publicacao_movimentacao_4 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE publicacao_profissional ADD CONSTRAINT fk_publicacao_profissional_1 FOREIGN KEY (publicacao_id) references publicacao(id); 
ALTER TABLE publicacao_profissional ADD CONSTRAINT fk_publicacao_profissional_2 FOREIGN KEY (profissional_id) references pessoa(id); 
ALTER TABLE publicacao_sugestao_prazo ADD CONSTRAINT fk_publicacao_sugestao_prazo_1 FOREIGN KEY (publicacao_id) references publicacao(id); 
ALTER TABLE publicacao_sugestao_prazo ADD CONSTRAINT fk_publicacao_sugestao_prazo_2 FOREIGN KEY (config_busca_prazo_id) references config_busca_prazo(id); 
ALTER TABLE publicacao_sugestao_prazo ADD CONSTRAINT fk_publicacao_sugestao_prazo_3 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE publicacao_sugestao_prazo ADD CONSTRAINT fk_publicacao_sugestao_prazo_4 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE questao ADD CONSTRAINT fk_questao_1 FOREIGN KEY (formulario_id) references formulario(id); 
ALTER TABLE resposta_formulario ADD CONSTRAINT fk_resposta_formulario_1 FOREIGN KEY (formulario_id) references formulario(id); 
ALTER TABLE resposta_formulario ADD CONSTRAINT fk_resposta_formulario_2 FOREIGN KEY (atendimento_id) references atendimento(id); 
ALTER TABLE resposta_formulario ADD CONSTRAINT fk_resposta_formulario_3 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE resposta_formulario ADD CONSTRAINT fk_resposta_formulario_4 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE status_processual ADD CONSTRAINT fk_tribunal_1_5381465b926127fd4c FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE status_processual ADD CONSTRAINT fk_tribunal_2_5381465b926127fd4c FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE status_processual ADD CONSTRAINT fk_status_processual_3 FOREIGN KEY (tipo_processo_id) references tipo_processo(id); 
ALTER TABLE system_group_program ADD CONSTRAINT fk_system_group_program_1 FOREIGN KEY (system_program_id) references system_program(id); 
ALTER TABLE system_group_program ADD CONSTRAINT fk_system_group_program_2 FOREIGN KEY (system_group_id) references system_group(id); 
ALTER TABLE system_user_group ADD CONSTRAINT fk_system_user_group_1 FOREIGN KEY (system_group_id) references system_group(id); 
ALTER TABLE system_user_group ADD CONSTRAINT fk_system_user_group_2 FOREIGN KEY (system_user_id) references system_users(id); 
ALTER TABLE system_user_program ADD CONSTRAINT fk_system_user_program_1 FOREIGN KEY (system_program_id) references system_program(id); 
ALTER TABLE system_user_program ADD CONSTRAINT fk_system_user_program_2 FOREIGN KEY (system_user_id) references system_users(id); 
ALTER TABLE system_users ADD CONSTRAINT fk_system_user_1 FOREIGN KEY (system_unit_id) references system_unit(id); 
ALTER TABLE system_users ADD CONSTRAINT fk_system_user_2 FOREIGN KEY (frontpage_id) references system_program(id); 
ALTER TABLE system_user_unit ADD CONSTRAINT fk_system_user_unit_1 FOREIGN KEY (system_user_id) references system_users(id); 
ALTER TABLE system_user_unit ADD CONSTRAINT fk_system_user_unit_2 FOREIGN KEY (system_unit_id) references system_unit(id); 
ALTER TABLE tarefa ADD CONSTRAINT fk_tarefa_6 FOREIGN KEY (processo_id) references processo(id); 
ALTER TABLE tarefa ADD CONSTRAINT fk_tarefa_4 FOREIGN KEY (tarefa_status_id) references tarefa_status(id); 
ALTER TABLE tarefa ADD CONSTRAINT fk_tarefa_2 FOREIGN KEY (publicacao_id) references publicacao(id); 
ALTER TABLE tarefa ADD CONSTRAINT fk_tarefa_3 FOREIGN KEY (usuario_destinatario_id) references system_users(id); 
ALTER TABLE tarefa ADD CONSTRAINT fk_tarefa_4 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE tarefa ADD CONSTRAINT fk_tarefa_5 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE tarefa_cliente ADD CONSTRAINT fk_tarefa_cliente_1 FOREIGN KEY (tarefa_id) references tarefa(id); 
ALTER TABLE tarefa_cliente ADD CONSTRAINT fk_tarefa_cliente_2 FOREIGN KEY (cliente_id) references pessoa(id); 
ALTER TABLE tarefa_comentario ADD CONSTRAINT fk_tarefa_comentario_1 FOREIGN KEY (tarefa_id) references tarefa(id); 
ALTER TABLE tarefa_comentario ADD CONSTRAINT fk_tarefa_comentario_2 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE tarefa_comentario ADD CONSTRAINT fk_tarefa_comentario_3 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE tarefa_configuracao ADD CONSTRAINT fk_tarefa_configuracao_1 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE tarefa_configuracao ADD CONSTRAINT fk_tarefa_configuracao_2 FOREIGN KEY (status_inicial_id) references tarefa_status(id); 
ALTER TABLE tarefa_configuracao ADD CONSTRAINT fk_tarefa_configuracao_3 FOREIGN KEY (status_final_id) references tarefa_status(id); 
ALTER TABLE tarefa_configuracao ADD CONSTRAINT fk_tarefa_configuracao_4 FOREIGN KEY (status_cancelado_id) references tarefa_status(id); 
ALTER TABLE tarefa_horas_trabalhadas ADD CONSTRAINT fk_tarefa_horas_trabalhadas_1 FOREIGN KEY (tarefa_id) references tarefa(id); 
ALTER TABLE tarefa_horas_trabalhadas ADD CONSTRAINT fk_tarefa_horas_trabalhadas_2 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE tarefa_movimentacao ADD CONSTRAINT fk_tarefa_movimentacao_3 FOREIGN KEY (tarefa_id) references tarefa(id); 
ALTER TABLE tarefa_movimentacao ADD CONSTRAINT fk_tarefa_movimentacao_2 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE tarefa_movimentacao ADD CONSTRAINT fk_tarefa_movimentacao_3 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE tarefa_status ADD CONSTRAINT fk_tarefa_status_1 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE tarefa_status ADD CONSTRAINT fk_tarefa_status_2 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE tarefa_usuario_master ADD CONSTRAINT fk_tarefa_usuario_master_1 FOREIGN KEY (usuario_master_id) references system_users(id); 
ALTER TABLE tarefa_usuario_master ADD CONSTRAINT fk_tarefa_usuario_master_2 FOREIGN KEY (tarefa_configuracao_id) references tarefa_configuracao(id); 
ALTER TABLE tarefa_vinculo ADD CONSTRAINT fk_subtarefa_3 FOREIGN KEY (tarefa_id) references tarefa(id); 
ALTER TABLE tarefa_vinculo ADD CONSTRAINT fk_subtarefa_4 FOREIGN KEY (subtarefa_id) references tarefa(id); 
ALTER TABLE tarefa_vinculo ADD CONSTRAINT fk_subtarefa_3 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE tarefa_vinculo ADD CONSTRAINT fk_subtarefa_5 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE template_acao ADD CONSTRAINT fk_template_acao_1 FOREIGN KEY (template_escritorio_id) references template_escritorio(id); 
ALTER TABLE template_escritorio ADD CONSTRAINT fk_template_clinica_1 FOREIGN KEY (escritorio_id) references escritorio(id); 
ALTER TABLE template_escritorio ADD CONSTRAINT fk_template_escritorio_2 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE template_escritorio ADD CONSTRAINT fk_template_escritorio_3 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE tipo_andamento ADD CONSTRAINT fk_tipo_andamento_1 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE tipo_andamento ADD CONSTRAINT fk_tipo_andamento_2 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE tipo_compromisso ADD CONSTRAINT fk_tipo_conta_1_b64b150680ba5d FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE tipo_compromisso ADD CONSTRAINT fk_tipo_conta_2_b64b150680ba5d FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE tipo_conta ADD CONSTRAINT fk_tipo_conta_1 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE tipo_conta ADD CONSTRAINT fk_tipo_conta_2 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE tipo_documento_financeiro ADD CONSTRAINT fk_tipo_documento_financeiro_4 FOREIGN KEY (padrao_id) references tipo_doc_financeiro_padrao(id); 
ALTER TABLE tipo_documento_financeiro ADD CONSTRAINT fk_conta_tipo_documento_1 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE tipo_documento_financeiro ADD CONSTRAINT fk_conta_tipo_documento_2 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE tipo_documento_financeiro ADD CONSTRAINT fk_tipo_documento_financeiro_3 FOREIGN KEY (tipo_conta_id) references tipo_conta(id); 
ALTER TABLE tipo_modelo_documento ADD CONSTRAINT fk_tipo_modelo_documento_1 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE tipo_modelo_documento ADD CONSTRAINT fk_tipo_modelo_documento_2 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE tipo_pagamento ADD CONSTRAINT fk_tipo_pagamento_1 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE tipo_pagamento ADD CONSTRAINT fk_tipo_pagamento_2 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE tipo_prazo ADD CONSTRAINT fk_tipo_prazo_1 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE tipo_prazo ADD CONSTRAINT fk_tipo_prazo_2 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE tipo_profissional ADD CONSTRAINT fk_tipo_profissional_1 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE tipo_profissional ADD CONSTRAINT fk_tipo_profissional_2 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE tribunal ADD CONSTRAINT fk_tribunal_1 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE tribunal ADD CONSTRAINT fk_tribunal_2 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE unidade_indexador ADD CONSTRAINT fk_unidade_indexador_1 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE unidade_indexador ADD CONSTRAINT fk_unidade_indexador_2 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE vara ADD CONSTRAINT fk_foro_1_5381465b926567fd57 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE vara ADD CONSTRAINT fk_foro_2_5381465b926567fd57 FOREIGN KEY (modificacao_user_id) references system_users(id); 

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
 
 
 CREATE index idx_agenda_procedimento_id on agenda(procedimento_id); 
CREATE index idx_agenda_escritorio_id on agenda(escritorio_id); 
CREATE index idx_agenda_profissional_id on agenda(profissional_id); 
CREATE index idx_agenda_criacao_user_id on agenda(criacao_user_id); 
CREATE index idx_agenda_modificacao_user_id on agenda(modificacao_user_id); 
CREATE index idx_agendamento_cliente_id on agendamento(cliente_id); 
CREATE index idx_agendamento_estado_agenda_id on agendamento(estado_agenda_id); 
CREATE index idx_agendamento_agenda_id on agendamento(agenda_id); 
CREATE index idx_agendamento_especialidade_id on agendamento(especialidade_id); 
CREATE index idx_agendamento_procedimento_agendamento_id on agendamento_procedimento(agendamento_id); 
CREATE index idx_agendamento_procedimento_procedimento_id on agendamento_procedimento(procedimento_id); 
CREATE index idx_agendamento_procedimento_parceiro_id on agendamento_procedimento(parceiro_id); 
CREATE index idx_agenda_profissional_profissional_id on agenda_profissional(profissional_id); 
CREATE index idx_agenda_profissional_agenda_id on agenda_profissional(agenda_id); 
CREATE index idx_andamento_tipo_andamento_id on andamento(tipo_andamento_id); 
CREATE index idx_andamento_criacao_user_id on andamento(criacao_user_id); 
CREATE index idx_andamento_modificacao_user_id on andamento(modificacao_user_id); 
CREATE index idx_andamento_processo_id on andamento(processo_id); 
CREATE index idx_anexo_atendimento_id on anexo(atendimento_id); 
CREATE index idx_anexo_criacao_user_id on anexo(criacao_user_id); 
CREATE index idx_anexo_modificacao_user_id on anexo(modificacao_user_id); 
CREATE index idx_area_criacao_user_id on area(criacao_user_id); 
CREATE index idx_area_modificacao_user_id on area(modificacao_user_id); 
CREATE index idx_assunto_area_id on assunto(area_id); 
CREATE index idx_assunto_criacao_user_id on assunto(criacao_user_id); 
CREATE index idx_atendimento_profissional_id on atendimento(profissional_id); 
CREATE index idx_atendimento_agendamento_id on atendimento(agendamento_id); 
CREATE index idx_atendimento_tipo_atendimento_id on atendimento(tipo_atendimento_id); 
CREATE index idx_atendimento_criacao_user_id on atendimento(criacao_user_id); 
CREATE index idx_atendimento_modificacao_user_id on atendimento(modificacao_user_id); 
CREATE index idx_atendimento_cliente_id on atendimento(cliente_id); 
CREATE index idx_atendimento_contrato_atendimento_id on atendimento_contrato(atendimento_id); 
CREATE index idx_atendimento_contrato_contrato_id on atendimento_contrato(contrato_id); 
CREATE index idx_atendimento_historico_atendimento_id on atendimento_historico(atendimento_id); 
CREATE index idx_atendimento_historico_criacao_user_id on atendimento_historico(criacao_user_id); 
CREATE index idx_atendimento_historico_modificacao_user_id on atendimento_historico(modificacao_user_id); 
CREATE index idx_atendimento_material_material_id on atendimento_material(material_id); 
CREATE index idx_atendimento_material_atendimento_id on atendimento_material(atendimento_id); 
CREATE index idx_atendimento_procedimento_atendimento_id on atendimento_procedimento(atendimento_id); 
CREATE index idx_atendimento_procedimento_procedimento_id on atendimento_procedimento(procedimento_id); 
CREATE index idx_atendimento_procedimento_parceiro_id on atendimento_procedimento(parceiro_id); 
CREATE index idx_banco_criacao_user_id on banco(criacao_user_id); 
CREATE index idx_banco_modificacao_user_id on banco(modificacao_user_id); 
CREATE index idx_bloqueio_agenda_id on bloqueio(agenda_id); 
CREATE index idx_bloqueio_criacao_user_id on bloqueio(criacao_user_id); 
CREATE index idx_bloqueio_modificacao_user_id on bloqueio(modificacao_user_id); 
CREATE index idx_categoria_conta_tipo_conta_id on categoria_conta(tipo_conta_id); 
CREATE index idx_categoria_conta_criacao_user_id on categoria_conta(criacao_user_id); 
CREATE index idx_categoria_conta_modificacao_user_id on categoria_conta(modificacao_user_id); 
CREATE index idx_cidade_estado_id on cidade(estado_id); 
CREATE index idx_cidade_criacao_user_id on cidade(criacao_user_id); 
CREATE index idx_cidade_modificacao_user_id on cidade(modificacao_user_id); 
CREATE index idx_classificacoes_criacao_user_id on classificacoes(criacao_user_id); 
CREATE index idx_classificacoes_modificacao_user_id on classificacoes(modificacao_user_id); 
CREATE index idx_classificacoes_cliente_pessoa_id on classificacoes_cliente(pessoa_id); 
CREATE index idx_classificacoes_cliente_classificacoes_id on classificacoes_cliente(classificacoes_id); 
CREATE index idx_classificacoes_contraparte_contraparte_id on classificacoes_contraparte(contraparte_id); 
CREATE index idx_classificacoes_contraparte_pessoa_id on classificacoes_contraparte(pessoa_id); 
CREATE index idx_classificacoes_contraparte_classificacoes_690ba3e003022 on classificacoes_contraparte(classificacoes_contraparte_dados_id); 
CREATE index idx_classificacoes_contraparte_dados_criacao_user_id on classificacoes_contraparte_dados(criacao_user_id); 
CREATE index idx_classificacoes_contraparte_dados_modificacao_user_id on classificacoes_contraparte_dados(modificacao_user_id); 
CREATE index idx_comarca_criacao_user_id on comarca(criacao_user_id); 
CREATE index idx_comarca_modificacao_user_id on comarca(modificacao_user_id); 
CREATE index idx_compromisso_agenda_id on compromisso(agenda_id); 
CREATE index idx_compromisso_criacao_user_id on compromisso(criacao_user_id); 
CREATE index idx_compromisso_modificacao_user_id on compromisso(modificacao_user_id); 
CREATE index idx_compromisso_tipo_compromisso_id on compromisso(tipo_compromisso_id); 
CREATE index idx_config_busca_a_partir_criacao_user_id on config_busca_a_partir(criacao_user_id); 
CREATE index idx_config_busca_a_partir_modificacao_user_id on config_busca_a_partir(modificacao_user_id); 
CREATE index idx_config_busca_prazo_criacao_user_id on config_busca_prazo(criacao_user_id); 
CREATE index idx_config_busca_prazo_modificacao_user_id on config_busca_prazo(modificacao_user_id); 
CREATE index idx_config_busca_prazo_tipo_prazo_id on config_busca_prazo(tipo_prazo_id); 
CREATE index idx_config_busca_prazo_config_busca_a_partir_id on config_busca_prazo(config_busca_a_partir_id); 
CREATE index idx_config_busca_prazo_texto_config_busca_prazo_id on config_busca_prazo_texto(config_busca_prazo_id); 
CREATE index idx_conta_criacao_user_id on conta(criacao_user_id); 
CREATE index idx_conta_modificacao_user_id on conta(modificacao_user_id); 
CREATE index idx_conta_tipo_conta_id on conta(tipo_conta_id); 
CREATE index idx_conta_pessoa_id on conta(pessoa_id); 
CREATE index idx_conta_atendimento_id on conta(atendimento_id); 
CREATE index idx_conta_escritorio_id on conta(escritorio_id); 
CREATE index idx_conta_categoria_conta_id on conta(categoria_conta_id); 
CREATE index idx_conta_tipo_documento_financeiro_id on conta(tipo_documento_financeiro_id); 
CREATE index idx_conta_profissional_id on conta(profissional_id); 
CREATE index idx_conta_contrato_id on conta(contrato_id); 
CREATE index idx_conta_processo_id on conta(processo_id); 
CREATE index idx_conta_caixa_criacao_user_id on conta_caixa(criacao_user_id); 
CREATE index idx_conta_caixa_modificacao_user_id on conta_caixa(modificacao_user_id); 
CREATE index idx_conta_caixa_tipo_conta_caixa_id on conta_caixa(tipo_conta_caixa_id); 
CREATE index idx_conta_caixa_banco_id on conta_caixa(banco_id); 
CREATE index idx_contraparte_criacao_user_id on contraparte(criacao_user_id); 
CREATE index idx_contraparte_modificacao_user_id on contraparte(modificacao_user_id); 
CREATE index idx_contraparte_processo_id on contraparte(processo_id); 
CREATE index idx_contraparte_pessoa_id on contraparte(pessoa_id); 
CREATE index idx_contrato_criacao_user_id on contrato(criacao_user_id); 
CREATE index idx_contrato_modificacao_user_id on contrato(modificacao_user_id); 
CREATE index idx_contrato_escritorio_id on contrato(escritorio_id); 
CREATE index idx_contrato_envolvimento_id on contrato(envolvimento_id); 
CREATE index idx_contrato_area_id on contrato(area_id); 
CREATE index idx_contrato_assunto_id on contrato(assunto_id); 
CREATE index idx_contrato_tipo_processo_id on contrato(tipo_processo_id); 
CREATE index idx_contrato_contrato_status_id on contrato(contrato_status_id); 
CREATE index idx_contrato_documento_criacao_user_id on contrato_documento(criacao_user_id); 
CREATE index idx_contrato_documento_modificacao_user_id on contrato_documento(modificacao_user_id); 
CREATE index idx_contrato_documento_modelo_documento_id on contrato_documento(modelo_documento_id); 
CREATE index idx_contrato_documento_contrato_id on contrato_documento(contrato_id); 
CREATE index idx_contrato_pagamento_evento_criacao_user_id on contrato_pagamento_evento(criacao_user_id); 
CREATE index idx_contrato_pagamento_evento_modificacao_user_id on contrato_pagamento_evento(modificacao_user_id); 
CREATE index idx_contrato_pagamento_indexador_criacao_user_id on contrato_pagamento_indexador(criacao_user_id); 
CREATE index idx_contrato_pagamento_indexador_modificacao_user_id on contrato_pagamento_indexador(modificacao_user_id); 
CREATE index idx_contrato_pagamento_opcao_criacao_user_id on contrato_pagamento_opcao(criacao_user_id); 
CREATE index idx_contrato_pagamento_opcao_modificacao_user_id on contrato_pagamento_opcao(modificacao_user_id); 
CREATE index idx_contrato_pagamento_parcela_criacao_user_id on contrato_pagamento_parcela(criacao_user_id); 
CREATE index idx_contrato_pagamento_parcela_modificacao_user_id on contrato_pagamento_parcela(modificacao_user_id); 
CREATE index idx_contrato_pagamento_parcela_contrato_opcao_pagamento_id on contrato_pagamento_parcela(contrato_opcao_pagamento_id); 
CREATE index idx_contrato_pagamento_parcela_contrato_evento_id on contrato_pagamento_parcela(contrato_evento_id); 
CREATE index idx_contrato_pagamento_parcela_contrato_indexador_id on contrato_pagamento_parcela(contrato_indexador_id); 
CREATE index idx_contrato_pagamento_parcela_contrato_id on contrato_pagamento_parcela(contrato_id); 
CREATE index idx_contrato_pagamento_parcela_unidade_indexador_id on contrato_pagamento_parcela(unidade_indexador_id); 
CREATE index idx_contrato_pessoa_cliente_id on contrato_pessoa(cliente_id); 
CREATE index idx_contrato_pessoa_contrato_id on contrato_pessoa(contrato_id); 
CREATE index idx_contrato_processo_contrato_id on contrato_processo(contrato_id); 
CREATE index idx_contrato_processo_processo_id on contrato_processo(processo_id); 
CREATE index idx_contrato_processo_criacao_user_id on contrato_processo(criacao_user_id); 
CREATE index idx_contrato_processo_modificacao_user_id on contrato_processo(modificacao_user_id); 
CREATE index idx_contrato_repasse_pessoa_id on contrato_repasse(pessoa_id); 
CREATE index idx_contrato_repasse_contrato_id on contrato_repasse(contrato_id); 
CREATE index idx_contrato_representante_contrato_id on contrato_representante(contrato_id); 
CREATE index idx_contrato_representante_representante_id on contrato_representante(representante_id); 
CREATE index idx_convidado_agenda_id on convidado(agenda_id); 
CREATE index idx_convidado_agendamento_id on convidado(agendamento_id); 
CREATE index idx_convidado_criacao_user_id on convidado(criacao_user_id); 
CREATE index idx_convidado_modificacao_user_id on convidado(modificacao_user_id); 
CREATE index idx_convidado_compromisso_agenda_id on convidado_compromisso(agenda_id); 
CREATE index idx_convidado_compromisso_criacao_user_id on convidado_compromisso(criacao_user_id); 
CREATE index idx_convidado_compromisso_modificacao_user_id on convidado_compromisso(modificacao_user_id); 
CREATE index idx_convidado_compromisso_compromisso_id on convidado_compromisso(compromisso_id); 
CREATE index idx_documento_modelo_documento_id on documento(modelo_documento_id); 
CREATE index idx_documento_atendimento_id on documento(atendimento_id); 
CREATE index idx_documento_procedimento_id on documento(procedimento_id); 
CREATE index idx_documento_criacao_user_id on documento(criacao_user_id); 
CREATE index idx_documento_modificacao_user_id on documento(modificacao_user_id); 
CREATE index idx_documento_base_contrato_area_id on documento_base_contrato(area_id); 
CREATE index idx_documento_base_contrato_modelo_documento_id on documento_base_contrato(modelo_documento_id); 
CREATE index idx_envolvimento_criacao_user_id on envolvimento(criacao_user_id); 
CREATE index idx_envolvimento_modificacao_user_id on envolvimento(modificacao_user_id); 
CREATE index idx_envolvimento_tipo_processo_id on envolvimento(tipo_processo_id); 
CREATE index idx_escritorio_system_unit_id on escritorio(system_unit_id); 
CREATE index idx_escritorio_modificacao_user_id on escritorio(modificacao_user_id); 
CREATE index idx_escritorio_cidade_id on escritorio(cidade_id); 
CREATE index idx_escritorio_criacao_user_id on escritorio(criacao_user_id); 
CREATE index idx_escritorio_parceiro_escritorio_id on escritorio_parceiro(escritorio_id); 
CREATE index idx_escritorio_parceiro_parceiro_id on escritorio_parceiro(parceiro_id); 
CREATE index idx_especialidade_criacao_user_id on especialidade(criacao_user_id); 
CREATE index idx_especialidade_modificacao_user_id on especialidade(modificacao_user_id); 
CREATE index idx_estado_criacao_user_id on estado(criacao_user_id); 
CREATE index idx_estado_modificacao_user_id on estado(modificacao_user_id); 
CREATE index idx_estado_agenda_modificacao_user_id on estado_agenda(modificacao_user_id); 
CREATE index idx_estado_agendamento_agendamento_id on estado_agendamento(agendamento_id); 
CREATE index idx_estado_agendamento_estado_agenda_id on estado_agendamento(estado_agenda_id); 
CREATE index idx_estado_agendamento_system_users_id on estado_agendamento(system_users_id); 
CREATE index idx_extrato_conta_caixa_id on extrato(conta_caixa_id); 
CREATE index idx_extrato_escritorio_id on extrato(escritorio_id); 
CREATE index idx_extrato_lancamento_id on extrato(lancamento_id); 
CREATE index idx_extrato_categoria_conta_id on extrato(categoria_conta_id); 
CREATE index idx_extrato_tipo_extrato_id on extrato(tipo_extrato_id); 
CREATE index idx_extrato_transferencia_conta_caixa_id on extrato(transferencia_conta_caixa_id); 
CREATE index idx_extrato_criacao_user_id on extrato(criacao_user_id); 
CREATE index idx_extrato_modificacao_user_id on extrato(modificacao_user_id); 
CREATE index idx_formulario_criacao_user_id on formulario(criacao_user_id); 
CREATE index idx_formulario_modificacao_user_id on formulario(modificacao_user_id); 
CREATE index idx_foro_criacao_user_id on foro(criacao_user_id); 
CREATE index idx_foro_modificacao_user_id on foro(modificacao_user_id); 
CREATE index idx_grupo_criacao_user_id on grupo(criacao_user_id); 
CREATE index idx_grupo_modificacao_user_id on grupo(modificacao_user_id); 
CREATE index idx_jornal_criacao_user_id on jornal(criacao_user_id); 
CREATE index idx_jornal_modificacao_user_id on jornal(modificacao_user_id); 
CREATE index idx_lancamento_contrato_parcela_id on lancamento(contrato_parcela_id); 
CREATE index idx_lancamento_cheque_banco_id on lancamento(cheque_banco_id); 
CREATE index idx_lancamento_extrato_id on lancamento(extrato_id); 
CREATE index idx_lancamento_conta_id on lancamento(conta_id); 
CREATE index idx_lancamento_tipo_pagamento_id on lancamento(tipo_pagamento_id); 
CREATE index idx_log_crontab_system_unit_id on log_crontab(system_unit_id); 
CREATE index idx_material_unidade_medida_id on material(unidade_medida_id); 
CREATE index idx_mensagem_agendamento_id on mensagem(agendamento_id); 
CREATE index idx_mensagem_template_escritorio_id on mensagem(template_escritorio_id); 
CREATE index idx_mensagem_system_user_id on mensagem(system_user_id); 
CREATE index idx_mensagem_acao_mensagem_id on mensagem_acao(mensagem_id); 
CREATE index idx_modelo_doc_aplicacao_modelo_documento_id on modelo_doc_aplicacao(modelo_documento_id); 
CREATE index idx_modelo_doc_aplicacao_tipo_aplicacao_id on modelo_doc_aplicacao(tipo_aplicacao_id); 
CREATE index idx_modelo_documento_criacao_user_id on modelo_documento(criacao_user_id); 
CREATE index idx_modelo_documento_modificacao_user_id on modelo_documento(modificacao_user_id); 
CREATE index idx_modelo_documento_tipo_modelo_documento_id on modelo_documento(tipo_modelo_documento_id); 
CREATE index idx_modelo_documento_pf_modelo_documento_id on modelo_documento_pf(modelo_documento_id); 
CREATE index idx_modelo_documento_pfrep_modelo_documento_id on modelo_documento_pfrep(modelo_documento_id); 
CREATE index idx_modelo_documento_pj_modelo_documento_id on modelo_documento_pj(modelo_documento_id); 
CREATE index idx_movimentacao_material_id on movimentacao(material_id); 
CREATE index idx_movimentacao_system_user_id on movimentacao(system_user_id); 
CREATE index idx_orgao_criacao_user_id on orgao(criacao_user_id); 
CREATE index idx_orgao_modificacao_user_id on orgao(modificacao_user_id); 
CREATE index idx_padrao_atendimento_documento_criacao_user_id on padrao_atendimento_documento(criacao_user_id); 
CREATE index idx_padrao_atendimento_documento_modificacao_user_id on padrao_atendimento_documento(modificacao_user_id); 
CREATE index idx_padrao_atend_modelo_doc_tipo_padrao_doc_atendimento_id on padrao_atend_modelo_doc(tipo_padrao_doc_atendimento_id); 
CREATE index idx_padrao_atend_modelo_doc_modelo_documento_id on padrao_atend_modelo_doc(modelo_documento_id); 
CREATE index idx_parceiro_criacao_user_id on parceiro(criacao_user_id); 
CREATE index idx_parceiro_modificacao_user_id on parceiro(modificacao_user_id); 
CREATE index idx_parceiro_pessoa_id on parceiro(pessoa_id); 
CREATE index idx_pessoa_tipo_profissional_id on pessoa(tipo_profissional_id); 
CREATE index idx_pessoa_system_users_id on pessoa(system_users_id); 
CREATE index idx_pessoa_tipo_pessoa_id on pessoa(tipo_pessoa_id); 
CREATE index idx_pessoa_criacao_user_id on pessoa(criacao_user_id); 
CREATE index idx_pessoa_modificacao_user_id on pessoa(modificacao_user_id); 
CREATE index idx_pessoa_sexo_id on pessoa(sexo_id); 
CREATE index idx_pessoa_nacionalidade_id on pessoa(nacionalidade_id); 
CREATE index idx_pessoa_estado_civil_id on pessoa(estado_civil_id); 
CREATE index idx_pessoa_situacao_profissional_id on pessoa(situacao_profissional_id); 
CREATE index idx_pessoa_contato_pessoa_id on pessoa_contato(pessoa_id); 
CREATE index idx_pessoa_endereco_pessoa_id on pessoa_endereco(pessoa_id); 
CREATE index idx_pessoa_endereco_cidade_id on pessoa_endereco(cidade_id); 
CREATE index idx_pessoa_especialidade_pessoa_id on pessoa_especialidade(pessoa_id); 
CREATE index idx_pessoa_especialidade_especialidade_id on pessoa_especialidade(especialidade_id); 
CREATE index idx_pessoa_grupo_pessoa_id on pessoa_grupo(pessoa_id); 
CREATE index idx_pessoa_grupo_grupo_id on pessoa_grupo(grupo_id); 
CREATE index idx_pessoa_representantes_legais_pessoa_juridica_id on pessoa_representantes_legais(pessoa_juridica_id); 
CREATE index idx_pessoa_representantes_legais_representante_id on pessoa_representantes_legais(representante_id); 
CREATE index idx_preferencia_sistema_system_users_id on preferencia_sistema(system_users_id); 
CREATE index idx_procedimento_criacao_user_id on procedimento(criacao_user_id); 
CREATE index idx_procedimento_modificacao_user_id on procedimento(modificacao_user_id); 
CREATE index idx_procedimento_preco_procedimento_id on procedimento_preco(procedimento_id); 
CREATE index idx_procedimento_preco_parceiro_id on procedimento_preco(parceiro_id); 
CREATE index idx_processo_criacao_user_id on processo(criacao_user_id); 
CREATE index idx_processo_modificacao_user_id on processo(modificacao_user_id); 
CREATE index idx_processo_envolvimento_id on processo(envolvimento_id); 
CREATE index idx_processo_tipo_processo_id on processo(tipo_processo_id); 
CREATE index idx_processo_tribunal_id on processo(tribunal_id); 
CREATE index idx_processo_foro_id on processo(foro_id); 
CREATE index idx_processo_comarca_id on processo(comarca_id); 
CREATE index idx_processo_assunto_id on processo(assunto_id); 
CREATE index idx_processo_area_id on processo(area_id); 
CREATE index idx_processo_responsavel_id on processo(responsavel_id); 
CREATE index idx_processo_status_processual_id on processo(status_processual_id); 
CREATE index idx_processo_vara_id on processo(vara_id); 
CREATE index idx_processo_orgao_id on processo(orgao_id); 
CREATE index idx_processo_vinculo_processo_principal_id on processo_vinculo(processo_principal_id); 
CREATE index idx_processo_vinculo_processo_incidente_id on processo_vinculo(processo_incidente_id); 
CREATE index idx_publicacao_processo_id on publicacao(processo_id); 
CREATE index idx_publicacao_criacao_user_id on publicacao(criacao_user_id); 
CREATE index idx_publicacao_modificacao_user_id on publicacao(modificacao_user_id); 
CREATE index idx_publicacao_jornal_id on publicacao(jornal_id); 
CREATE index idx_publicacao_movimentacao_publicacao_id on publicacao_movimentacao(publicacao_id); 
CREATE index idx_publicacao_movimentacao_processo_id on publicacao_movimentacao(processo_id); 
CREATE index idx_publicacao_movimentacao_tarefa_id on publicacao_movimentacao(tarefa_id); 
CREATE index idx_publicacao_movimentacao_criacao_user_id on publicacao_movimentacao(criacao_user_id); 
CREATE index idx_publicacao_profissional_publicacao_id on publicacao_profissional(publicacao_id); 
CREATE index idx_publicacao_profissional_profissional_id on publicacao_profissional(profissional_id); 
CREATE index idx_publicacao_sugestao_prazo_publicacao_id on publicacao_sugestao_prazo(publicacao_id); 
CREATE index idx_publicacao_sugestao_prazo_config_busca_prazo_id on publicacao_sugestao_prazo(config_busca_prazo_id); 
CREATE index idx_publicacao_sugestao_prazo_criacao_user_id on publicacao_sugestao_prazo(criacao_user_id); 
CREATE index idx_publicacao_sugestao_prazo_modificacao_user_id on publicacao_sugestao_prazo(modificacao_user_id); 
CREATE index idx_questao_formulario_id on questao(formulario_id); 
CREATE index idx_resposta_formulario_formulario_id on resposta_formulario(formulario_id); 
CREATE index idx_resposta_formulario_atendimento_id on resposta_formulario(atendimento_id); 
CREATE index idx_resposta_formulario_criacao_user_id on resposta_formulario(criacao_user_id); 
CREATE index idx_resposta_formulario_modificacao_user_id on resposta_formulario(modificacao_user_id); 
CREATE index idx_status_processual_criacao_user_id on status_processual(criacao_user_id); 
CREATE index idx_status_processual_modificacao_user_id on status_processual(modificacao_user_id); 
CREATE index idx_status_processual_tipo_processo_id on status_processual(tipo_processo_id); 
CREATE index idx_system_group_program_system_program_id on system_group_program(system_program_id); 
CREATE index idx_system_group_program_system_group_id on system_group_program(system_group_id); 
CREATE index idx_system_user_group_system_group_id on system_user_group(system_group_id); 
CREATE index idx_system_user_group_system_user_id on system_user_group(system_user_id); 
CREATE index idx_system_user_program_system_program_id on system_user_program(system_program_id); 
CREATE index idx_system_user_program_system_user_id on system_user_program(system_user_id); 
CREATE index idx_system_users_system_unit_id on system_users(system_unit_id); 
CREATE index idx_system_users_frontpage_id on system_users(frontpage_id); 
CREATE index idx_system_user_unit_system_user_id on system_user_unit(system_user_id); 
CREATE index idx_system_user_unit_system_unit_id on system_user_unit(system_unit_id); 
CREATE index idx_tarefa_processo_id on tarefa(processo_id); 
CREATE index idx_tarefa_tarefa_status_id on tarefa(tarefa_status_id); 
CREATE index idx_tarefa_publicacao_id on tarefa(publicacao_id); 
CREATE index idx_tarefa_usuario_destinatario_id on tarefa(usuario_destinatario_id); 
CREATE index idx_tarefa_criacao_user_id on tarefa(criacao_user_id); 
CREATE index idx_tarefa_modificacao_user_id on tarefa(modificacao_user_id); 
CREATE index idx_tarefa_cliente_tarefa_id on tarefa_cliente(tarefa_id); 
CREATE index idx_tarefa_cliente_cliente_id on tarefa_cliente(cliente_id); 
CREATE index idx_tarefa_comentario_tarefa_id on tarefa_comentario(tarefa_id); 
CREATE index idx_tarefa_comentario_criacao_user_id on tarefa_comentario(criacao_user_id); 
CREATE index idx_tarefa_comentario_modificacao_user_id on tarefa_comentario(modificacao_user_id); 
CREATE index idx_tarefa_configuracao_modificacao_user_id on tarefa_configuracao(modificacao_user_id); 
CREATE index idx_tarefa_configuracao_status_inicial_id on tarefa_configuracao(status_inicial_id); 
CREATE index idx_tarefa_configuracao_status_final_id on tarefa_configuracao(status_final_id); 
CREATE index idx_tarefa_configuracao_status_cancelado_id on tarefa_configuracao(status_cancelado_id); 
CREATE index idx_tarefa_horas_trabalhadas_tarefa_id on tarefa_horas_trabalhadas(tarefa_id); 
CREATE index idx_tarefa_horas_trabalhadas_criacao_user_id on tarefa_horas_trabalhadas(criacao_user_id); 
CREATE index idx_tarefa_movimentacao_tarefa_id on tarefa_movimentacao(tarefa_id); 
CREATE index idx_tarefa_movimentacao_criacao_user_id on tarefa_movimentacao(criacao_user_id); 
CREATE index idx_tarefa_movimentacao_modificacao_user_id on tarefa_movimentacao(modificacao_user_id); 
CREATE index idx_tarefa_status_criacao_user_id on tarefa_status(criacao_user_id); 
CREATE index idx_tarefa_status_modificacao_user_id on tarefa_status(modificacao_user_id); 
CREATE index idx_tarefa_usuario_master_usuario_master_id on tarefa_usuario_master(usuario_master_id); 
CREATE index idx_tarefa_usuario_master_tarefa_configuracao_id on tarefa_usuario_master(tarefa_configuracao_id); 
CREATE index idx_tarefa_vinculo_tarefa_id on tarefa_vinculo(tarefa_id); 
CREATE index idx_tarefa_vinculo_subtarefa_id on tarefa_vinculo(subtarefa_id); 
CREATE index idx_tarefa_vinculo_criacao_user_id on tarefa_vinculo(criacao_user_id); 
CREATE index idx_tarefa_vinculo_modificacao_user_id on tarefa_vinculo(modificacao_user_id); 
CREATE index idx_template_acao_template_escritorio_id on template_acao(template_escritorio_id); 
CREATE index idx_template_escritorio_escritorio_id on template_escritorio(escritorio_id); 
CREATE index idx_template_escritorio_criacao_user_id on template_escritorio(criacao_user_id); 
CREATE index idx_template_escritorio_modificacao_user_id on template_escritorio(modificacao_user_id); 
CREATE index idx_tipo_andamento_criacao_user_id on tipo_andamento(criacao_user_id); 
CREATE index idx_tipo_andamento_modificacao_user_id on tipo_andamento(modificacao_user_id); 
CREATE index idx_tipo_compromisso_criacao_user_id on tipo_compromisso(criacao_user_id); 
CREATE index idx_tipo_compromisso_modificacao_user_id on tipo_compromisso(modificacao_user_id); 
CREATE index idx_tipo_conta_criacao_user_id on tipo_conta(criacao_user_id); 
CREATE index idx_tipo_conta_modificacao_user_id on tipo_conta(modificacao_user_id); 
CREATE index idx_tipo_documento_financeiro_padrao_id on tipo_documento_financeiro(padrao_id); 
CREATE index idx_tipo_documento_financeiro_criacao_user_id on tipo_documento_financeiro(criacao_user_id); 
CREATE index idx_tipo_documento_financeiro_modificacao_user_id on tipo_documento_financeiro(modificacao_user_id); 
CREATE index idx_tipo_documento_financeiro_tipo_conta_id on tipo_documento_financeiro(tipo_conta_id); 
CREATE index idx_tipo_modelo_documento_criacao_user_id on tipo_modelo_documento(criacao_user_id); 
CREATE index idx_tipo_modelo_documento_modificacao_user_id on tipo_modelo_documento(modificacao_user_id); 
CREATE index idx_tipo_pagamento_criacao_user_id on tipo_pagamento(criacao_user_id); 
CREATE index idx_tipo_pagamento_modificacao_user_id on tipo_pagamento(modificacao_user_id); 
CREATE index idx_tipo_prazo_criacao_user_id on tipo_prazo(criacao_user_id); 
CREATE index idx_tipo_prazo_modificacao_user_id on tipo_prazo(modificacao_user_id); 
CREATE index idx_tipo_profissional_criacao_user_id on tipo_profissional(criacao_user_id); 
CREATE index idx_tipo_profissional_modificacao_user_id on tipo_profissional(modificacao_user_id); 
CREATE index idx_tribunal_criacao_user_id on tribunal(criacao_user_id); 
CREATE index idx_tribunal_modificacao_user_id on tribunal(modificacao_user_id); 
CREATE index idx_unidade_indexador_criacao_user_id on unidade_indexador(criacao_user_id); 
CREATE index idx_unidade_indexador_modificacao_user_id on unidade_indexador(modificacao_user_id); 
CREATE index idx_vara_criacao_user_id on vara(criacao_user_id); 
CREATE index idx_vara_modificacao_user_id on vara(modificacao_user_id); 
