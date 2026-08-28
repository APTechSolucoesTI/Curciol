CREATE TABLE agenda( 
      id number(10)    NOT NULL , 
      escritorio_id number(10)    NOT NULL , 
      profissional_id number(10)    NOT NULL , 
      nome varchar(3000)    NOT NULL , 
      horario_inicial time    DEFAULT '08:00'  NOT NULL , 
      horario_final time    DEFAULT '18:00'  NOT NULL , 
      visualizacao_inicial varchar  (30)    DEFAULT 'agendaWeek'  NOT NULL , 
      horario_inicio_intervalo time   , 
      horario_fim_intervalo time   , 
      duracao number(10)    DEFAULT 30  NOT NULL , 
      dias varchar(3000)    NOT NULL , 
      procedimento_id number(10)   , 
      cor varchar  (10)   , 
      aceita_agendamento_online char  (1)    DEFAULT 'F' , 
      publica char  (1)    DEFAULT 'F' , 
      fl_permite_choque_horario char  (1)    DEFAULT 'T' , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)  (100)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE agendamento( 
      id number(10)    NOT NULL , 
      cliente_id number(10)    NOT NULL , 
      estado_agenda_id number(10)    NOT NULL , 
      agenda_id number(10)    NOT NULL , 
      especialidade_id number(10)   , 
      dt_inicial timestamp(0)    NOT NULL , 
      dt_final timestamp(0)    NOT NULL , 
      agendamento_original_id number(10)   , 
      observacao varchar(3000)   , 
      ativo char  (1)    DEFAULT 'T' , 
      ano_inicial varchar(3000)   , 
      mes_inicial varchar(3000)   , 
      ano_mes_inicial varchar(3000)   , 
      ano_final varchar(3000)   , 
      mes_final varchar(3000)   , 
      ano_mes_final varchar(3000)   , 
      online char  (1)    DEFAULT 'F' , 
      link_atendimento_online varchar(3000)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE agendamento_procedimento( 
      id number(10)    NOT NULL , 
      agendamento_id number(10)    NOT NULL , 
      procedimento_id number(10)    NOT NULL , 
      parceiro_id number(10)    NOT NULL , 
      quantidade binary_double    NOT NULL , 
      valor binary_double   , 
      valor_total binary_double   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE agenda_profissional( 
      id number(10)    NOT NULL , 
      profissional_id number(10)    NOT NULL , 
      agenda_id number(10)    NOT NULL , 
      fl_manipula_atendimento char    DEFAULT 'N'  NOT NULL , 
 PRIMARY KEY (id)) ; 

CREATE TABLE andamento( 
      id number(10)    NOT NULL , 
      processo_id number(10)    NOT NULL , 
      tipo_andamento_id number(10)    NOT NULL , 
      data_andamento timestamp(0)   , 
      titulo varchar(3000)    NOT NULL , 
      texto varchar(3000)   , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
      publicacao_etapa_id number(10)    NOT NULL , 
      etapa_verificada char  (1)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE anexo( 
      id number(10)    NOT NULL , 
      atendimento_id number(10)    NOT NULL , 
      arquivo varchar(3000)    NOT NULL , 
      observacao varchar(3000)   , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE api_error( 
      id number(10)    NOT NULL , 
      classe varchar  (255)   , 
      metodo varchar  (255)   , 
      url varchar  (500)   , 
      dados varchar  (3000)   , 
      error_message varchar  (3000)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE area( 
      id number(10)    NOT NULL , 
      nome varchar  (255)    NOT NULL , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE assunto( 
      id number(10)    NOT NULL , 
      area_id number(10)    NOT NULL , 
      nome varchar  (255)    NOT NULL , 
      descricao varchar(3000)   , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE atendimento( 
      id number(10)    NOT NULL , 
      agendamento_id number(10)   , 
      cliente_id number(10)    NOT NULL , 
      profissional_id number(10)    NOT NULL , 
      tipo_atendimento_id number(10)    NOT NULL , 
      informacoes varchar  (500)   , 
      dt_inicio timestamp(0)   , 
      dt_final timestamp(0)   , 
      valor_total binary_double   , 
      ano_inicial varchar(3000)   , 
      mes_inicial varchar(3000)   , 
      ano_mes_inicial varchar(3000)   , 
      mes_final varchar(3000)   , 
      ano_final varchar(3000)   , 
      ano_mes_final varchar(3000)   , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE atendimento_contrato( 
      id number(10)    NOT NULL , 
      atendimento_id number(10)    NOT NULL , 
      contrato_id number(10)    NOT NULL , 
 PRIMARY KEY (id)) ; 

CREATE TABLE atendimento_historico( 
      id number(10)    NOT NULL , 
      atendimento_id number(10)    NOT NULL , 
      historico varchar(3000)    NOT NULL , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE atendimento_material( 
      id number(10)    NOT NULL , 
      material_id number(10)    NOT NULL , 
      atendimento_id number(10)    NOT NULL , 
      quantidade binary_double    NOT NULL , 
 PRIMARY KEY (id)) ; 

CREATE TABLE atendimento_procedimento( 
      id number(10)    NOT NULL , 
      parceiro_id number(10)    NOT NULL , 
      atendimento_id number(10)    NOT NULL , 
      procedimento_id number(10)    NOT NULL , 
      quantidade binary_double    NOT NULL , 
      valor binary_double   , 
      valor_total binary_double   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE banco( 
      id number(10)    NOT NULL , 
      codigo varchar  (10)    NOT NULL , 
      nome varchar  (255)    NOT NULL , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE bloqueio( 
      id number(10)    NOT NULL , 
      agenda_id number(10)    NOT NULL , 
      dt_inicio timestamp(0)    NOT NULL , 
      dt_final timestamp(0)    NOT NULL , 
      observacao varchar(3000)   , 
      horario_bloqueio_original number(10)   , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE categoria_conta( 
      id number(10)    NOT NULL , 
      tipo_conta_id number(10)    NOT NULL , 
      nome varchar(3000)    NOT NULL , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE cep_cache( 
      id number(10)    NOT NULL , 
      cep varchar  (12)    NOT NULL , 
      codigo_ibge varchar(3000)   , 
      rua varchar(3000)   , 
      cidade varchar(3000)   , 
      bairro varchar(3000)   , 
      uf varchar(3000)   , 
      cidade_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE cidade( 
      id number(10)    NOT NULL , 
      estado_id number(10)    NOT NULL , 
      nome varchar(3000)    NOT NULL , 
      codigo_ibge varchar(3000)    NOT NULL , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE classificacoes( 
      id number(10)    NOT NULL , 
      nome varchar  (255)    NOT NULL , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)  (100)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)  (100)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE classificacoes_cliente( 
      id number(10)    NOT NULL , 
      pessoa_id number(10)    NOT NULL , 
      classificacoes_id number(10)    NOT NULL , 
 PRIMARY KEY (id)) ; 

CREATE TABLE classificacoes_contraparte( 
      id number(10)    NOT NULL , 
      contraparte_id number(10)   , 
      pessoa_id number(10)    NOT NULL , 
      classificacoes_contraparte_dados_id number(10)    NOT NULL , 
 PRIMARY KEY (id)) ; 

CREATE TABLE classificacoes_contraparte_dados( 
      id number(10)    NOT NULL , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
      nome varchar  (255)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE clones( 
      id number(10)    NOT NULL , 
      qtd number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE comarca( 
      id number(10)    NOT NULL , 
      nome varchar  (255)    NOT NULL , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE compromisso( 
      id number(10)    NOT NULL , 
      agenda_id number(10)    NOT NULL , 
      tipo_compromisso_id number(10)    NOT NULL , 
      dt_inicio timestamp(0)    NOT NULL , 
      dt_final timestamp(0)    NOT NULL , 
      observacao varchar(3000)   , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE config_busca_a_partir( 
      id number(10)    NOT NULL , 
      nome varchar  (255)    NOT NULL , 
      add_dias number(10)    NOT NULL , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE config_busca_prazo( 
      id number(10)    NOT NULL , 
      titulo varchar  (255)    NOT NULL , 
      prazo number(10)    NOT NULL , 
      tipo_prazo_id number(10)    NOT NULL , 
      config_busca_a_partir_id number(10)    NOT NULL , 
      pont number(10)    DEFAULT 0 , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE config_busca_prazo_texto( 
      id number(10)    NOT NULL , 
      config_busca_prazo_id number(10)    NOT NULL , 
      texto varchar(3000)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE conta( 
      id number(10)    NOT NULL , 
      pessoa_id number(10)    NOT NULL , 
      categoria_conta_id number(10)    NOT NULL , 
      tipo_conta_id number(10)    NOT NULL , 
      escritorio_id number(10)    NOT NULL , 
      tipo_documento_financeiro_id number(10)    NOT NULL , 
      atendimento_id number(10)   , 
      contrato_id number(10)   , 
      profissional_id number(10)   , 
      processo_id number(10)   , 
      numero_documento varchar  (255)   , 
      data_emissao date    NOT NULL , 
      total_parcelas number(10)    DEFAULT 1  NOT NULL , 
      quitada char  (1)    DEFAULT 'N'  NOT NULL , 
      descricao varchar(3000)    NOT NULL , 
      conta_origem_id number(10)   , 
      total_conta binary_double    NOT NULL , 
      mes varchar(3000)   , 
      ano varchar(3000)   , 
      ano_mes varchar(3000)   , 
      proximo_vencimento_lancamento date   , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
      tipo_lancamento varchar  (1)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE conta_caixa( 
      id number(10)    NOT NULL , 
      nome varchar  (255)    NOT NULL , 
      tipo_conta_caixa_id number(10)    NOT NULL , 
      dt_inicial timestamp(0)    NOT NULL , 
      saldo_inicial binary_double    NOT NULL , 
      saldo_instantaneo binary_double   , 
      saldo_nao_compensado binary_double   , 
      ativo char  (1)    DEFAULT 'S'  NOT NULL , 
      cor_nao_compensado varchar  (7)    DEFAULT '#FF0000' , 
      cor_compensado varchar  (7)    DEFAULT '#00FF00' , 
      banco_id number(10)   , 
      codigo_agencia varchar  (10)   , 
      codigo_conta varchar  (30)   , 
      descricao_agencia varchar  (255)   , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE conta_profissional( 
      id number(10)    NOT NULL , 
      conta_id number(10)    NOT NULL , 
      pessoa_id number(10)    NOT NULL , 
      percentual binary_double   , 
      valor binary_double   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE contraparte( 
      id number(10)    NOT NULL , 
      processo_id number(10)    NOT NULL , 
      pessoa_id number(10)    NOT NULL , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE contrato( 
      id number(10)    NOT NULL , 
      escritorio_id number(10)    NOT NULL , 
      tipo_processo_id number(10)   , 
      area_id number(10)   , 
      contrato_status_id number(10)   , 
      assunto_id number(10)   , 
      numero varchar  (30)    NOT NULL , 
      objeto varchar(3000)    NOT NULL , 
      valor binary_double   , 
      quantidade_parcelas number(10)   , 
      envolvimento_id number(10)   , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE contrato_config( 
      id number(10)    NOT NULL , 
      clausula_pagamento number(10)    NOT NULL , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE contrato_documento( 
      id number(10)    NOT NULL , 
      contrato_id number(10)    NOT NULL , 
      modelo_documento_id number(10)    NOT NULL , 
      filename varchar(3000)   , 
      dt_preenchimento timestamp(0)    NOT NULL , 
      autenticador varchar(3000)   , 
      dt_validade timestamp(0)   , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE contrato_pagamento_evento( 
      id number(10)    NOT NULL , 
      nome varchar  (255)    NOT NULL , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE contrato_pagamento_indexador( 
      id number(10)    NOT NULL , 
      nome varchar  (255)    NOT NULL , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE contrato_pagamento_opcao( 
      id number(10)    NOT NULL , 
      nome varchar  (255)    NOT NULL , 
      descricao1 varchar(3000)    NOT NULL , 
      descricaon varchar(3000)    NOT NULL , 
      recebe_valor char  (1)    DEFAULT 'N'  NOT NULL , 
      recebe_data char  (1)    DEFAULT 'N'  NOT NULL , 
      recebe_evento char  (1)    DEFAULT 'N'  NOT NULL , 
      recebe_indexador char  (1)    DEFAULT 'N'  NOT NULL , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE contrato_pagamento_parcela( 
      contrato_id number(10)    NOT NULL , 
      id number(10)    NOT NULL , 
      status_contrato_pagamento_id number(10)    NOT NULL , 
      contrato_opcao_pagamento_id number(10)    NOT NULL , 
      valor binary_double   , 
      saldo binary_double   , 
      data_pagamento date   , 
      contrato_evento_id number(10)   , 
      unidade_indexador_id number(10)   , 
      complemento_indexador varchar  (255)   , 
      contrato_indexador_id number(10)   , 
      descritivo varchar(3000)   , 
      numero_parcelas number(10)   , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE contrato_pessoa( 
      id number(10)    NOT NULL , 
      contrato_id number(10)    NOT NULL , 
      cliente_id number(10)    NOT NULL , 
      percentual binary_double   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE contrato_processo( 
      id number(10)    NOT NULL , 
      contrato_id number(10)    NOT NULL , 
      processo_id number(10)    NOT NULL , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE contrato_repasse( 
      id number(10)    NOT NULL , 
      contrato_id number(10)    NOT NULL , 
      pessoa_id number(10)    NOT NULL , 
      percentual binary_double  (2)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE contrato_representante( 
      id number(10)    NOT NULL , 
      contrato_id number(10)    NOT NULL , 
      representante_id number(10)    NOT NULL , 
 PRIMARY KEY (id)) ; 

CREATE TABLE contrato_status( 
      id number(10)    NOT NULL , 
      nome varchar  (255)    NOT NULL , 
      cor varchar  (10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE convidado( 
      id number(10)    NOT NULL , 
      agendamento_id number(10)    NOT NULL , 
      agenda_id number(10)    NOT NULL , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE convidado_compromisso( 
      id number(10)    NOT NULL , 
      compromisso_id number(10)    NOT NULL , 
      agenda_id number(10)    NOT NULL , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE documento( 
      id number(10)    NOT NULL , 
      atendimento_id number(10)    NOT NULL , 
      modelo_documento_id number(10)   , 
      filename varchar(3000)   , 
      observacao varchar(3000)   , 
      dt_preenchimento timestamp(0)    NOT NULL , 
      autenticador varchar(3000)   , 
      dt_validade date   , 
      procedimento_id number(10)   , 
      medico_assistente varchar(3000)   , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE documento_base_contrato( 
      id number(10)    NOT NULL , 
      area_id number(10)    NOT NULL , 
      modelo_documento_id number(10)    NOT NULL , 
 PRIMARY KEY (id)) ; 

CREATE TABLE email_config( 
      id number(10)    NOT NULL , 
      escritorio_id number(10)    NOT NULL , 
      port varchar(3000)   , 
      username varchar(3000)   , 
      password varchar(3000)   , 
      host varchar(3000)   , 
      from_email varchar(3000)   , 
      from_name varchar(3000)   , 
      smtp_auth char  (1)    DEFAULT 'T::bpchar' , 
 PRIMARY KEY (id)) ; 

CREATE TABLE envolvimento( 
      id number(10)    NOT NULL , 
      tipo_processo_id number(10)    NOT NULL , 
      nome varchar  (255)    NOT NULL , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE escritorio( 
      id number(10)    NOT NULL , 
      system_unit_id number(10)    NOT NULL , 
      cidade_id number(10)    NOT NULL , 
      nome varchar(3000)    NOT NULL , 
      cnpj varchar(3000)    NOT NULL , 
      telefone varchar(3000)    NOT NULL , 
      email varchar(3000)    NOT NULL , 
      endereco varchar(3000)    NOT NULL , 
      bairro varchar(3000)    NOT NULL , 
      cep varchar(3000)    NOT NULL , 
      numero varchar(3000)   , 
      complemento varchar(3000)   , 
      logo_documento varchar(3000)   , 
      url_sistema varchar(3000)   , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE escritorio_parceiro( 
      id number(10)    NOT NULL , 
      parceiro_id number(10)    NOT NULL , 
      escritorio_id number(10)    NOT NULL , 
 PRIMARY KEY (id)) ; 

CREATE TABLE especialidade( 
      id number(10)    NOT NULL , 
      descricao varchar(3000)    NOT NULL , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE estado( 
      id number(10)    NOT NULL , 
      nome varchar(3000)    NOT NULL , 
      sigla char  (2)    NOT NULL , 
      codigo_ibge varchar(3000)   , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE estado_agenda( 
      id number(10)    NOT NULL , 
      nome varchar(3000)    NOT NULL , 
      estado_inicial char    DEFAULT 'N'  NOT NULL , 
      estado_final char    DEFAULT 'N'  NOT NULL , 
      cor varchar  (10)    NOT NULL , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE estado_agendamento( 
      id number(10)    NOT NULL , 
      agendamento_id number(10)    NOT NULL , 
      estado_agenda_id number(10)    NOT NULL , 
      system_users_id number(10)   , 
      atribuido_em timestamp(0)    NOT NULL , 
 PRIMARY KEY (id)) ; 

CREATE TABLE estado_civil( 
      id number(10)    NOT NULL , 
      nome varchar  (30)    NOT NULL , 
 PRIMARY KEY (id)) ; 

CREATE TABLE etapa_palavras_chaves( 
      id number(10)    NOT NULL , 
      publicacao_etapa_id number(10)    NOT NULL , 
      palavra_chave varchar(3000)   , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE extrato( 
      id number(10)    NOT NULL , 
      escritorio_id number(10)    NOT NULL , 
      conta_caixa_id number(10)    NOT NULL , 
      lancamento_id number(10)   , 
      categoria_conta_id number(10)   , 
      tipo_extrato_id number(10)    NOT NULL , 
      transferencia_conta_caixa_id number(10)   , 
      extrato_vinculado number(10)   , 
      entrada_valor binary_double   , 
      saida_valor binary_double   , 
      data_lancamento date   , 
      data_previsao_compensacao date   , 
      compensado char    DEFAULT 'N'  NOT NULL , 
      data_compensacao date   , 
      historico varchar  (3000)   , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
      mes varchar(3000)   , 
      ano varchar(3000)   , 
      ano_mes varchar(3000)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE fluxo_caixa_analitico( 
      id number(10)    NOT NULL , 
      dia date    NOT NULL , 
      tipo char  (1)    NOT NULL , 
      numero varchar  (255)    NOT NULL , 
      historico varchar  (255)    NOT NULL , 
      entrada binary_double   , 
      saida binary_double   , 
      saldo binary_double    DEFAULT 0  NOT NULL , 
 PRIMARY KEY (id)) ; 

CREATE TABLE fluxo_caixa_sintetico( 
      id number(10)    NOT NULL , 
      dia date    NOT NULL , 
      tipo char  (1)   , 
      numero varchar  (255)   , 
      historico varchar  (255)   , 
      entrada binary_double   , 
      saida binary_double   , 
      saldo binary_double    DEFAULT 0  NOT NULL , 
 PRIMARY KEY (id)) ; 

CREATE TABLE formulario( 
      id number(10)    NOT NULL , 
      nome varchar(3000)    NOT NULL , 
      ativo char  (1)    DEFAULT 'S'  NOT NULL , 
      ordem number(10)    NOT NULL , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE foro( 
      id number(10)    NOT NULL , 
      nome varchar  (255)    NOT NULL , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE grupo( 
      id number(10)    NOT NULL , 
      nome varchar(3000)    NOT NULL , 
      cor varchar  (10)    NOT NULL , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)  (100)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)  (100)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE jornal( 
      id number(10)    NOT NULL , 
      nome varchar  (255)    NOT NULL , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE lancamento( 
      id number(10)    NOT NULL , 
      conta_id number(10)    NOT NULL , 
      tipo_pagamento_id number(10)    NOT NULL , 
      parcela number(10)    DEFAULT 1 , 
      valor binary_double    NOT NULL , 
      saldo binary_double  (15,2)   , 
      acrescimo binary_double  (15,2)   , 
      desconto binary_double  (15,2)   , 
      valor_total binary_double  (15,2)   , 
      dt_vencimento date    NOT NULL , 
      dt_pagamento date   , 
      ano_pagamento varchar(3000)   , 
      mes_pagamento varchar(3000)   , 
      ano_mes_pagamento varchar(3000)   , 
      ano_vencimento varchar(3000)   , 
      mes_vencimento varchar(3000)   , 
      ano_mes_vencimento varchar(3000)   , 
      cheque_numero varchar  (100)   , 
      cheque_banco_id number(10)   , 
      extrato_id number(10)   , 
      cancelado char  (1)    DEFAULT 'N' , 
      motivo_cancelamento varchar  (300)   , 
      contrato_parcela_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE lancamento_profissional( 
      id number(10)    NOT NULL , 
      lancamento_id number(10)    NOT NULL , 
      pessoa_id number(10)    NOT NULL , 
      percentual binary_double   , 
      valor binary_double   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE lancamento_profissional_ajuste( 
      id number(10)    NOT NULL , 
      lancamento_profissional_id number(10)    NOT NULL , 
      tipo char  (1)   , 
      valor binary_double  (15,2)   , 
      descricao varchar  (255)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE log_crontab( 
      system_unit_id number(10)    NOT NULL , 
      id number(10)    NOT NULL , 
      classe varchar(3000)    NOT NULL , 
      metodo varchar(3000)   , 
      data_hora timestamp(0)   , 
      status number(10)   , 
      mensagem varchar(3000)   , 
      observacao varchar(3000)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE material( 
      id number(10)    NOT NULL , 
      unidade_medida_id number(10)    NOT NULL , 
      nome varchar(3000)    NOT NULL , 
      estoque_minimo binary_double   , 
      dt_vencimento date   , 
      estoque_atualizado binary_double   , 
      lote varchar(3000)   , 
      ativo char  (1)    DEFAULT 'S'  NOT NULL , 
 PRIMARY KEY (id)) ; 

CREATE TABLE mensagem( 
      id number(10)    NOT NULL , 
      agendamento_id number(10)    NOT NULL , 
      template_escritorio_id number(10)   , 
      system_user_id number(10)    NOT NULL , 
      titulo varchar(3000)   , 
      template varchar(3000)   , 
      enviado_em timestamp(0)   , 
      tipo_mensagem varchar(3000)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE mensagem_acao( 
      id number(10)    NOT NULL , 
      mensagem_id number(10)    NOT NULL , 
      url varchar(3000)   , 
      label varchar(3000)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE modelo_doc_aplicacao( 
      id number(10)    NOT NULL , 
      modelo_documento_id number(10)    NOT NULL , 
      tipo_aplicacao_id number(10)    NOT NULL , 
 PRIMARY KEY (id)) ; 

CREATE TABLE modelo_doc_tipo_aplicacao( 
      id number(10)    NOT NULL , 
      nome varchar  (255)    NOT NULL , 
 PRIMARY KEY (id)) ; 

CREATE TABLE modelo_documento( 
      id number(10)    NOT NULL , 
      tipo_modelo_documento_id number(10)    NOT NULL , 
      nome varchar(3000)    NOT NULL , 
      ativo char  (1)    DEFAULT 'S'  NOT NULL , 
      clausula_pagamento number(10)   , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE modelo_documento_pf( 
      id number(10)    NOT NULL , 
      modelo_documento_id number(10)    NOT NULL , 
      filename varchar(3000)    NOT NULL , 
      objeto char  (1)    DEFAULT 'N' , 
      informacoes_pagamento char  (1)    DEFAULT 'N' , 
      nacionalidade char  (1)    DEFAULT 'N' , 
      estado_civil char  (1)    DEFAULT 'N' , 
      profissao char  (1)    DEFAULT 'N' , 
      rg char  (1)    DEFAULT 'N' , 
      cpf char  (1)    DEFAULT 'N' , 
      endereco char  (1)    DEFAULT 'N' , 
      data_nascimento char  (1)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE modelo_documento_pfrep( 
      id number(10)    NOT NULL , 
      modelo_documento_id number(10)    NOT NULL , 
      filename varchar(3000)    NOT NULL , 
      objeto char  (1)    DEFAULT 'N' , 
      informacoes_pagamento char  (1)    DEFAULT 'N' , 
      nacionalidade char  (1)    DEFAULT 'N' , 
      estado_civil char  (1)    DEFAULT 'N' , 
      profissao char  (1)    DEFAULT 'N' , 
      rg char  (1)    DEFAULT 'N' , 
      cpf char  (1)    DEFAULT 'N' , 
      data_nascimento char  (1)   , 
      endereco char  (1)    DEFAULT 'N' , 
      nacionalidade_rep char  (1)    DEFAULT 'N' , 
      estado_civil_rep char  (1)    DEFAULT 'N' , 
      profissao_rep char  (1)    DEFAULT 'N' , 
      rg_rep char  (1)    DEFAULT 'N' , 
      cpf_rep char  (1)    DEFAULT 'N' , 
      endereco_rep number(10)   , 
      data_nascimento_rep char  (1)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE modelo_documento_pj( 
      id number(10)    NOT NULL , 
      modelo_documento_id number(10)    NOT NULL , 
      filename varchar(3000)    NOT NULL , 
      objeto char  (1)    DEFAULT 'N' , 
      informacoes_pagamento char  (1)    DEFAULT 'N' , 
      cnpj char  (1)    DEFAULT 'N' , 
      endereco char  (1)    DEFAULT 'N' , 
      nacionalidade_rep char  (1)    DEFAULT 'N' , 
      estado_civil_rep char  (1)    DEFAULT 'N' , 
      profissao_rep char  (1)    DEFAULT 'N' , 
      rg_rep char  (1)    DEFAULT 'N' , 
      cpf_rep char  (1)    DEFAULT 'N' , 
      endereco_rep char  (1)   , 
      data_abertura char  (1)   , 
      data_nascimento_rep char  (1)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE movimentacao( 
      id number(10)    NOT NULL , 
      material_id number(10)    NOT NULL , 
      system_user_id number(10)    NOT NULL , 
      dt_movimentacao varchar(3000)   , 
      quantidade binary_double   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE nacionalidade( 
      id number(10)    NOT NULL , 
      nome varchar  (30)    NOT NULL , 
 PRIMARY KEY (id)) ; 

CREATE TABLE orgao( 
      id number(10)    NOT NULL , 
      nome varchar  (255)    NOT NULL , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE padrao_atendimento_documento( 
      id number(10)    NOT NULL , 
      nome varchar  (255)    NOT NULL , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE padrao_atend_modelo_doc( 
      id number(10)    NOT NULL , 
      tipo_padrao_doc_atendimento_id number(10)    NOT NULL , 
      modelo_documento_id number(10)    NOT NULL , 
 PRIMARY KEY (id)) ; 

CREATE TABLE parceiro( 
      id number(10)    NOT NULL , 
      nome varchar(3000)    NOT NULL , 
      pessoa_id number(10)   , 
      percentual binary_double  (255)   , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE pessoa( 
      tipo_pessoa_id number(10)    NOT NULL , 
      id number(10)    NOT NULL , 
      nome varchar  (255)    NOT NULL , 
      nome_busca varchar  (255)   , 
      email varchar  (255)   , 
      telefone varchar  (20)   , 
      aceita_receber_mensagen_whatsapp char  (1)    DEFAULT 'F'  NOT NULL , 
      system_users_id number(10)   , 
      dt_nascimento_abertura date   , 
      dt_falecimento date   , 
      cpf_cnpj varchar  (14)   , 
      rg_ie varchar  (15)   , 
      orgao_emissor varchar  (20)   , 
      sexo_id number(10)   , 
      nacionalidade_id number(10)   , 
      estado_civil_id number(10)   , 
      profissao varchar(3000)   , 
      nit varchar  (255)   , 
      ctps varchar  (255)   , 
      situacao_profissional_id number(10)   , 
      orgao varchar  (255)   , 
      unidade varchar  (255)   , 
      observacao varchar(3000)   , 
      assinatura varchar(3000)   , 
      tratamento varchar(3000)   , 
      tipo_profissional_id number(10)   , 
      orgao_registro_profissional varchar  (30)   , 
      registro_profissional varchar  (255)   , 
      usuario varchar  (255)   , 
      senha varchar  (255)   , 
      foto varchar(3000)   , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)  (100)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)  (100)   , 
      chave_aasp varchar  (255)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE pessoa_contato( 
      id number(10)    NOT NULL , 
      pessoa_id number(10)    NOT NULL , 
      descricao varchar  (255)    NOT NULL , 
      telefone varchar  (20)   , 
      email varchar  (255)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE pessoa_endereco( 
      id number(10)    NOT NULL , 
      pessoa_id number(10)    NOT NULL , 
      cidade_id number(10)    NOT NULL , 
      cep varchar  (10)    NOT NULL , 
      rua varchar  (500)    NOT NULL , 
      bairro varchar  (500)    NOT NULL , 
      numero varchar  (100)    NOT NULL , 
      complemento varchar  (500)   , 
      principal char    DEFAULT 'F' , 
 PRIMARY KEY (id)) ; 

CREATE TABLE pessoa_especialidade( 
      id number(10)    NOT NULL , 
      pessoa_id number(10)    NOT NULL , 
      especialidade_id number(10)    NOT NULL , 
 PRIMARY KEY (id)) ; 

CREATE TABLE pessoa_grupo( 
      id number(10)    NOT NULL , 
      pessoa_id number(10)    NOT NULL , 
      grupo_id number(10)    NOT NULL , 
      cor varchar  (10)    DEFAULT '#ffffff' , 
 PRIMARY KEY (id)) ; 

CREATE TABLE pessoa_representantes_legais( 
      id number(10)    NOT NULL , 
      pessoa_juridica_id number(10)    NOT NULL , 
      representante_id number(10)    NOT NULL , 
      principal char  (1)   , 
      descricao varchar  (255)    NOT NULL , 
      created_at timestamp(0)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE preferencia_sistema( 
      id number(10)    NOT NULL , 
      system_users_id number(10)    NOT NULL , 
      zoom number(10)    DEFAULT 100  NOT NULL , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
      menu_fixado number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE procedimento( 
      id number(10)    NOT NULL , 
      nome varchar(3000)    NOT NULL , 
      cor varchar  (10)    NOT NULL , 
      ativo char  (1)    DEFAULT 'S'  NOT NULL , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE procedimento_preco( 
      id number(10)    NOT NULL , 
      procedimento_id number(10)    NOT NULL , 
      parceiro_id number(10)    NOT NULL , 
      valor binary_double    NOT NULL , 
 PRIMARY KEY (id)) ; 

CREATE TABLE processo( 
      id number(10)    NOT NULL , 
      tipo_processo_id number(10)    NOT NULL , 
      numero_cnj_numero varchar(3000)    NOT NULL , 
      numero_outro varchar(3000)   , 
      tribunal_id number(10)   , 
      foro_id number(10)   , 
      comarca_id number(10)   , 
      vara_id number(10)   , 
      orgao_id number(10)   , 
      data_distribuicao_protocolo date   , 
      valor_causa binary_double   , 
      area_id number(10)   , 
      assunto_id number(10)   , 
      gratuidade_processual char  (1)    DEFAULT 'F' , 
      status_processual_id number(10)   , 
      responsavel_id number(10)   , 
      envolvimento_id number(10)   , 
      observacao varchar(3000)   , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
      exibir_cliente char  (1)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE processo_publicacoes( 
      id number(10)    NOT NULL , 
      processo_id number(10)    NOT NULL , 
      publicacao_id number(10)   , 
      andamento_id number(10)   , 
      publicacao_etapa_id number(10)    NOT NULL , 
      date_log timestamp(0)   , 
      complemento varchar(3000)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE processo_vinculo( 
      id number(10)    NOT NULL , 
      processo_principal_id number(10)   , 
      processo_incidente_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE publicacao( 
      id number(10)    NOT NULL , 
      numero_arquivo varchar(3000)   , 
      numero_publicacao varchar(3000)   , 
      titulo varchar(3000)   , 
      texto varchar(3000)   , 
      cabecalho varchar(3000)   , 
      rodape varchar(3000)   , 
      processo_id number(10)   , 
      numero_unico_processo varchar(3000)   , 
      numero_processo_principal varchar(3000)   , 
      jornal_id number(10)   , 
      data_tratamento timestamp(0)   , 
      data_disponibilizacao date   , 
      termo_ref_data varchar(3000)   , 
      prazo date   , 
      confirma_prazo char  (1)    DEFAULT 'N' , 
      data_entrega date   , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
      publicacao_etapa_id number(10)    NOT NULL , 
      etapa_verificada char  (1)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE publicacao_etapa( 
      id number(10)    NOT NULL , 
      etapa_nome varchar(3000)   , 
      ordem_prioridade number(10)   , 
      descricao varchar(3000)   , 
      cor varchar(3000)   , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
      extrajudicial char  (1)   , 
      judicial char  (1)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE publicacao_movimentacao( 
      id number(10)    NOT NULL , 
      publicacao_id number(10)    NOT NULL , 
      descricao varchar(3000)    NOT NULL , 
      processo_id number(10)   , 
      tarefa_id number(10)   , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE publicacao_profissional( 
      id number(10)    NOT NULL , 
      publicacao_id number(10)    NOT NULL , 
      profissional_id number(10)    NOT NULL , 
      codigo_relacionamento varchar(3000)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE publicacao_sugestao_prazo( 
      id number(10)    NOT NULL , 
      publicacao_id number(10)    NOT NULL , 
      config_busca_prazo_id number(10)    NOT NULL , 
      resultado_busca varchar(3000)    NOT NULL , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE questao( 
      id number(10)    NOT NULL , 
      formulario_id number(10)    NOT NULL , 
      nome varchar(3000)    NOT NULL , 
      tipo_campo varchar(3000)    NOT NULL , 
      fl_obrigatorio char    DEFAULT 'N'  NOT NULL , 
      ativo char  (1)    DEFAULT 'S'  NOT NULL , 
      opcoes varchar(3000)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE requisicao_pagamento( 
      id number(10)    NOT NULL , 
      processo_id number(10)    NOT NULL , 
      tipos_requisicao_pagamento_id number(10)    NOT NULL , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE requisicao_pagamento_cliente( 
      id number(10)    NOT NULL , 
      pessoa_id number(10)    NOT NULL , 
      entidade_devedora_id number(10)    NOT NULL , 
      requisicao_pagamento_id number(10)    NOT NULL , 
      status_requisicao_pagamento_id number(10)    NOT NULL , 
      valor binary_double  (15,2)   , 
      obs varchar(3000)   , 
      conta_indicada_mle varchar  (255)   , 
      data_base date   , 
      data_criacao date   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
      data_requerimento timestamp(0)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE requisicao_pagamento_etapa2( 
      id number(10)    NOT NULL , 
      requisicao_pagamento_cliente_id number(10)    NOT NULL , 
      processo_filho_id number(10)    NOT NULL , 
      data_deferimento_expedicao_requisitorio date   , 
      protocolo_depre_entidade_devedora date   , 
      numero_depre_entidade_devedora varchar  (100)   , 
      numero_ordem varchar  (100)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE requisicao_pagamento_etapa3( 
      id number(10)    NOT NULL , 
      requisicao_pagamento_cliente_id number(10)    NOT NULL , 
      processo_filho_id number(10)    NOT NULL , 
      data_deposito date   , 
      valor_bruto_depositado binary_double  (15,2)   , 
      valor_mle binary_double  (15,2)   , 
      conta_indicada_mle varchar  (255)   , 
      data_pedido_mle date   , 
      data_deferimento_mle date   , 
      numero_ciclo number(10)   , 
      saldo_bruto binary_double  (15,2)   , 
      data_base_saldo date   , 
      possui_saldo char  (1)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE resposta( 
      id number(10)    NOT NULL , 
      resposta_formulario_id number(10)    NOT NULL , 
      questao_id number(10)    NOT NULL , 
      resposta varchar(3000)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE resposta_formulario( 
      id number(10)    NOT NULL , 
      formulario_id number(10)    NOT NULL , 
      atendimento_id number(10)    NOT NULL , 
      dt_resposta date   , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE sexo( 
      id number(10)    NOT NULL , 
      nome varchar  (30)    NOT NULL , 
 PRIMARY KEY (id)) ; 

CREATE TABLE situacao_profissional( 
      id number(10)    NOT NULL , 
      nome varchar  (255)    NOT NULL , 
 PRIMARY KEY (id)) ; 

CREATE TABLE status_contrato_pagamento( 
      id number(10)    NOT NULL , 
      nome varchar  (40)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE status_processual( 
      id number(10)    NOT NULL , 
      tipo_processo_id number(10)    NOT NULL , 
      nome varchar  (255)    NOT NULL , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE status_requisicao_pagamento( 
      id number(10)    NOT NULL , 
      nome varchar  (50)   , 
      cor varchar  (50)   , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE system_group( 
      id number(10)    NOT NULL , 
      name varchar(3000)    NOT NULL , 
      uuid varchar  (36)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE system_group_program( 
      id number(10)    NOT NULL , 
      system_group_id number(10)    NOT NULL , 
      system_program_id number(10)    NOT NULL , 
      actions varchar(3000)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE system_preference( 
      id varchar  (255)    NOT NULL , 
      preference varchar(3000)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE system_program( 
      id number(10)    NOT NULL , 
      name varchar(3000)    NOT NULL , 
      controller varchar(3000)    NOT NULL , 
      actions varchar(3000)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE system_unit( 
      id number(10)    NOT NULL , 
      name varchar(3000)    NOT NULL , 
      connection_name varchar(3000)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE system_user_group( 
      id number(10)    NOT NULL , 
      system_user_id number(10)    NOT NULL , 
      system_group_id number(10)    NOT NULL , 
 PRIMARY KEY (id)) ; 

CREATE TABLE system_user_program( 
      id number(10)    NOT NULL , 
      system_user_id number(10)    NOT NULL , 
      system_program_id number(10)    NOT NULL , 
 PRIMARY KEY (id)) ; 

CREATE TABLE system_users( 
      id number(10)    NOT NULL , 
      name varchar(3000)    NOT NULL , 
      login varchar(3000)    NOT NULL , 
      password varchar(3000)    NOT NULL , 
      email varchar(3000)   , 
      frontpage_id number(10)   , 
      system_unit_id number(10)   , 
      active char  (1)   , 
      accepted_term_policy_at varchar(3000)   , 
      accepted_term_policy char  (1)   , 
      two_factor_enabled char  (1)    DEFAULT 'N' , 
      two_factor_type varchar  (100)   , 
      two_factor_secret varchar  (255)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE system_user_unit( 
      id number(10)    NOT NULL , 
      system_user_id number(10)    NOT NULL , 
      system_unit_id number(10)    NOT NULL , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tarefa( 
      id number(10)    NOT NULL , 
      tarefa_status_id number(10)    NOT NULL , 
      publicacao_id number(10)   , 
      processo_id number(10)   , 
      usuario_destinatario_id number(10)    NOT NULL , 
      titulo varchar  (1000)    NOT NULL , 
      data_disponibilizacao timestamp(0)   , 
      prazo_validacao date   , 
      prazo_entrega date    NOT NULL , 
      observacao varchar(3000)   , 
      data_entrega timestamp(0)   , 
      arquivado char  (1)    DEFAULT 'N' , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
      prazo_processual char  (1)    DEFAULT 'N' , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tarefa_cliente( 
      id number(10)    NOT NULL , 
      tarefa_id number(10)    NOT NULL , 
      cliente_id number(10)    NOT NULL , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tarefa_comentario( 
      id number(10)    NOT NULL , 
      tarefa_id number(10)    NOT NULL , 
      texto varchar(3000)    NOT NULL , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tarefa_configuracao( 
      id number(10)    NOT NULL , 
      status_inicial_id number(10)    NOT NULL , 
      status_final_id number(10)    NOT NULL , 
      status_cancelado_id number(10)    NOT NULL , 
      tem_dtvalidacao char  (1)    DEFAULT 'N' , 
      dtvalidacao_obrigatoria char  (1)    DEFAULT 'N' , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tarefa_horas_trabalhadas( 
      id number(10)    NOT NULL , 
      tarefa_id number(10)    NOT NULL , 
      data_inicio timestamp(0)    NOT NULL , 
      data_fim timestamp(0)   , 
      observacao varchar(3000)   , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tarefa_movimentacao( 
      id number(10)    NOT NULL , 
      tarefa_id number(10)    NOT NULL , 
      descricao varchar(3000)   , 
      data_movimentacao timestamp(0)    NOT NULL , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tarefa_status( 
      id number(10)    NOT NULL , 
      nome varchar  (255)    NOT NULL , 
      kanban number(10)    NOT NULL , 
      inicio char  (1)   , 
      fim char  (1)   , 
      cor varchar  (10)    NOT NULL , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tarefa_usuario_master( 
      id number(10)    NOT NULL , 
      tarefa_configuracao_id number(10)    NOT NULL , 
      usuario_master_id number(10)    NOT NULL , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tarefa_vinculo( 
      id number(10)    NOT NULL , 
      tarefa_id number(10)    NOT NULL , 
      subtarefa_id number(10)    NOT NULL , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE template_acao( 
      id number(10)    NOT NULL , 
      template_escritorio_id number(10)    NOT NULL , 
      url varchar(3000)   , 
      label varchar(3000)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE template_escritorio( 
      id number(10)    NOT NULL , 
      escritorio_id number(10)    NOT NULL , 
      chave varchar(3000)    NOT NULL , 
      descricao varchar(3000)    NOT NULL , 
      habilitado char  (1)    DEFAULT 'T'  NOT NULL , 
      template varchar(3000)   , 
      titulo varchar(3000)   , 
      tipo_template varchar(3000)   , 
      readonly char  (1)    DEFAULT 'F'  NOT NULL , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)  (100)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)  (100)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tipo_andamento( 
      id number(10)    NOT NULL , 
      nome varchar  (255)    NOT NULL , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tipo_atendimento( 
      id number(10)    NOT NULL , 
      nome varchar  (255)    NOT NULL , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tipo_compromisso( 
      id number(10)    NOT NULL , 
      nome varchar(3000)    NOT NULL , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tipo_conta( 
      id number(10)    NOT NULL , 
      nome varchar(3000)    NOT NULL , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tipo_conta_caixa( 
      id number(10)    NOT NULL , 
      nome varchar  (255)    NOT NULL , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tipo_doc_financeiro_padrao( 
      id number(10)    NOT NULL , 
      nome varchar  (30)    NOT NULL , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tipo_documento_financeiro( 
      id number(10)    NOT NULL , 
      codigo varchar  (4)    DEFAULT 'Man'  NOT NULL , 
      nome varchar  (255)    NOT NULL , 
      tipo_conta_id number(10)    NOT NULL , 
      gera_codigo char  (1)    DEFAULT 'N'  NOT NULL , 
      padrao_id number(10)    NOT NULL , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tipo_extrato( 
      id number(10)    NOT NULL , 
      nome varchar  (50)    NOT NULL , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tipo_modelo_documento( 
      id number(10)    NOT NULL , 
      nome varchar  (255)    NOT NULL , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tipo_pagamento( 
      id number(10)    NOT NULL , 
      nome varchar(3000)    NOT NULL , 
      ativo char  (1)    DEFAULT 'S'  NOT NULL , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tipo_pessoa( 
      id number(10)    NOT NULL , 
      nome varchar  (20)    NOT NULL , 
      sigla char  (2)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tipo_prazo( 
      id number(10)    NOT NULL , 
      nome varchar  (255)    NOT NULL , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tipo_processo( 
      id number(10)    NOT NULL , 
      nome varchar  (255)    NOT NULL , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tipo_profissional( 
      id number(10)    NOT NULL , 
      nome varchar  (255)    NOT NULL , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tipos_requisicao_pagamento( 
      id number(10)    NOT NULL , 
      nome varchar  (50)   , 
      descricao varchar  (100)   , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tmp_documento( 
      id number(10)    NOT NULL , 
      nome varchar  (255)    NOT NULL , 
      filename varchar(3000)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE tribunal( 
      id number(10)    NOT NULL , 
      nome varchar  (255)    NOT NULL , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE unidade_indexador( 
      id number(10)    NOT NULL , 
      nome varchar  (255)    NOT NULL , 
      extenso varchar(3000)   , 
      simbolo varchar  (10)   , 
      criacao_user_id number(10)   , 
      data_criacao timestamp(0)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE unidade_medida( 
      id number(10)    NOT NULL , 
      nome varchar(3000)    NOT NULL , 
      sigla varchar(3000)    NOT NULL , 
 PRIMARY KEY (id)) ; 

CREATE TABLE vara( 
      id number(10)    NOT NULL , 
      nome varchar  (255)    NOT NULL , 
      data_criacao timestamp(0)   , 
      criacao_user_id number(10)   , 
      data_modificacao timestamp(0)   , 
      modificacao_user_id number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE video( 
      id number(10)    NOT NULL , 
      nome varchar  (255)   , 
      url varchar(3000)   , 
      tag_iframe varchar(3000)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE whatsapp_config( 
      id number(10)    NOT NULL , 
      escritorio_id number(10)    NOT NULL , 
      phone varchar(3000)   , 
      status varchar(3000)   , 
      api_token varchar(3000)   , 
      api_key varchar(3000)   , 
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
ALTER TABLE andamento ADD CONSTRAINT fk_andamento_5 FOREIGN KEY (publicacao_etapa_id) references publicacao_etapa(id); 
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
ALTER TABLE conta_profissional ADD CONSTRAINT fk_conta_profissional_1 FOREIGN KEY (conta_id) references conta(id); 
ALTER TABLE conta_profissional ADD CONSTRAINT fk_conta_profissional_2 FOREIGN KEY (pessoa_id) references pessoa(id); 
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
ALTER TABLE contrato_pagamento_parcela ADD CONSTRAINT fk_contrato_pagamento_parcela_8 FOREIGN KEY (status_contrato_pagamento_id) references status_contrato_pagamento(id); 
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
ALTER TABLE etapa_palavras_chaves ADD CONSTRAINT fk_etapa_palavras_chaves_1 FOREIGN KEY (publicacao_etapa_id) references publicacao_etapa(id); 
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
ALTER TABLE lancamento_profissional ADD CONSTRAINT fk_lancamento_profissional_1 FOREIGN KEY (lancamento_id) references lancamento(id); 
ALTER TABLE lancamento_profissional ADD CONSTRAINT fk_lancamento_profissional_2 FOREIGN KEY (pessoa_id) references pessoa(id); 
ALTER TABLE lancamento_profissional_ajuste ADD CONSTRAINT fk_lancamento_profissional_ajuste_1 FOREIGN KEY (lancamento_profissional_id) references lancamento_profissional(id); 
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
ALTER TABLE processo_publicacoes ADD CONSTRAINT fk_processo_publicacoes_3 FOREIGN KEY (publicacao_etapa_id) references publicacao_etapa(id); 
ALTER TABLE processo_publicacoes ADD CONSTRAINT fk_processo_publicacoes_1 FOREIGN KEY (processo_id) references processo(id); 
ALTER TABLE processo_publicacoes ADD CONSTRAINT fk_processo_publicacoes_2 FOREIGN KEY (publicacao_id) references publicacao(id); 
ALTER TABLE processo_vinculo ADD CONSTRAINT fk_processo_vinculo_1 FOREIGN KEY (processo_principal_id) references processo(id); 
ALTER TABLE processo_vinculo ADD CONSTRAINT fk_processo_vinculo_2 FOREIGN KEY (processo_incidente_id) references processo(id); 
ALTER TABLE publicacao ADD CONSTRAINT fk_andamento_4 FOREIGN KEY (processo_id) references processo(id); 
ALTER TABLE publicacao ADD CONSTRAINT fk_andamentos_1 FOREIGN KEY (criacao_user_id) references system_users(id); 
ALTER TABLE publicacao ADD CONSTRAINT fk_andamentos_2 FOREIGN KEY (modificacao_user_id) references system_users(id); 
ALTER TABLE publicacao ADD CONSTRAINT fk_andamento_4 FOREIGN KEY (jornal_id) references jornal(id); 
ALTER TABLE publicacao ADD CONSTRAINT fk_publicacao_5 FOREIGN KEY (publicacao_etapa_id) references publicacao_etapa(id); 
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
ALTER TABLE requisicao_pagamento ADD CONSTRAINT fk_requisicao_pagamento_1 FOREIGN KEY (processo_id) references processo(id); 
ALTER TABLE requisicao_pagamento ADD CONSTRAINT fk_requisicao_pagamento_2 FOREIGN KEY (tipos_requisicao_pagamento_id) references tipos_requisicao_pagamento(id); 
ALTER TABLE requisicao_pagamento_cliente ADD CONSTRAINT fk_requisicao_pagamento_cliente_1 FOREIGN KEY (pessoa_id) references pessoa(id); 
ALTER TABLE requisicao_pagamento_cliente ADD CONSTRAINT fk_requisicao_pagamento_cliente_2 FOREIGN KEY (entidade_devedora_id) references pessoa(id); 
ALTER TABLE requisicao_pagamento_cliente ADD CONSTRAINT fk_requisicao_pagamento_cliente_3 FOREIGN KEY (requisicao_pagamento_id) references requisicao_pagamento(id); 
ALTER TABLE requisicao_pagamento_cliente ADD CONSTRAINT fk_requisicao_pagamento_cliente_4 FOREIGN KEY (status_requisicao_pagamento_id) references status_requisicao_pagamento(id); 
ALTER TABLE requisicao_pagamento_etapa2 ADD CONSTRAINT fk_requisicao_pagamento_etapa2_1 FOREIGN KEY (processo_filho_id) references processo(id); 
ALTER TABLE requisicao_pagamento_etapa2 ADD CONSTRAINT fk_requisicao_pagamento_etapa2_2 FOREIGN KEY (requisicao_pagamento_cliente_id) references requisicao_pagamento_cliente(id); 
ALTER TABLE requisicao_pagamento_etapa3 ADD CONSTRAINT fk_requisicao_pagamento_etapa3_1 FOREIGN KEY (requisicao_pagamento_cliente_id) references requisicao_pagamento_cliente(id); 
ALTER TABLE requisicao_pagamento_etapa3 ADD CONSTRAINT fk_requisicao_pagamento_etapa3_2 FOREIGN KEY (processo_filho_id) references processo(id); 
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
 CREATE SEQUENCE agenda_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER agenda_id_seq_tr 

BEFORE INSERT ON agenda FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT agenda_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE agendamento_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER agendamento_id_seq_tr 

BEFORE INSERT ON agendamento FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT agendamento_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE agendamento_procedimento_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER agendamento_procedimento_id_seq_tr 

BEFORE INSERT ON agendamento_procedimento FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT agendamento_procedimento_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE agenda_profissional_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER agenda_profissional_id_seq_tr 

BEFORE INSERT ON agenda_profissional FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT agenda_profissional_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE andamento_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER andamento_id_seq_tr 

BEFORE INSERT ON andamento FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT andamento_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE anexo_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER anexo_id_seq_tr 

BEFORE INSERT ON anexo FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT anexo_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE api_error_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER api_error_id_seq_tr 

BEFORE INSERT ON api_error FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT api_error_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE area_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER area_id_seq_tr 

BEFORE INSERT ON area FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT area_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE assunto_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER assunto_id_seq_tr 

BEFORE INSERT ON assunto FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT assunto_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE atendimento_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER atendimento_id_seq_tr 

BEFORE INSERT ON atendimento FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT atendimento_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE atendimento_contrato_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER atendimento_contrato_id_seq_tr 

BEFORE INSERT ON atendimento_contrato FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT atendimento_contrato_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE atendimento_historico_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER atendimento_historico_id_seq_tr 

BEFORE INSERT ON atendimento_historico FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT atendimento_historico_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE atendimento_material_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER atendimento_material_id_seq_tr 

BEFORE INSERT ON atendimento_material FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT atendimento_material_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE atendimento_procedimento_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER atendimento_procedimento_id_seq_tr 

BEFORE INSERT ON atendimento_procedimento FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT atendimento_procedimento_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE banco_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER banco_id_seq_tr 

BEFORE INSERT ON banco FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT banco_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE bloqueio_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER bloqueio_id_seq_tr 

BEFORE INSERT ON bloqueio FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT bloqueio_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE categoria_conta_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER categoria_conta_id_seq_tr 

BEFORE INSERT ON categoria_conta FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT categoria_conta_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE cep_cache_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER cep_cache_id_seq_tr 

BEFORE INSERT ON cep_cache FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT cep_cache_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE cidade_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER cidade_id_seq_tr 

BEFORE INSERT ON cidade FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT cidade_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE classificacoes_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER classificacoes_id_seq_tr 

BEFORE INSERT ON classificacoes FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT classificacoes_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE classificacoes_cliente_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER classificacoes_cliente_id_seq_tr 

BEFORE INSERT ON classificacoes_cliente FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT classificacoes_cliente_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE classificacoes_contraparte_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER classificacoes_contraparte_id_seq_tr 

BEFORE INSERT ON classificacoes_contraparte FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT classificacoes_contraparte_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE classificacoes_contraparte_dados_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER classificacoes_contraparte_dados_id_seq_tr 

BEFORE INSERT ON classificacoes_contraparte_dados FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT classificacoes_contraparte_dados_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE clones_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER clones_id_seq_tr 

BEFORE INSERT ON clones FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT clones_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE comarca_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER comarca_id_seq_tr 

BEFORE INSERT ON comarca FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT comarca_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE compromisso_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER compromisso_id_seq_tr 

BEFORE INSERT ON compromisso FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT compromisso_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE config_busca_a_partir_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER config_busca_a_partir_id_seq_tr 

BEFORE INSERT ON config_busca_a_partir FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT config_busca_a_partir_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE config_busca_prazo_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER config_busca_prazo_id_seq_tr 

BEFORE INSERT ON config_busca_prazo FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT config_busca_prazo_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE config_busca_prazo_texto_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER config_busca_prazo_texto_id_seq_tr 

BEFORE INSERT ON config_busca_prazo_texto FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT config_busca_prazo_texto_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE conta_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER conta_id_seq_tr 

BEFORE INSERT ON conta FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT conta_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE conta_caixa_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER conta_caixa_id_seq_tr 

BEFORE INSERT ON conta_caixa FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT conta_caixa_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE conta_profissional_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER conta_profissional_id_seq_tr 

BEFORE INSERT ON conta_profissional FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT conta_profissional_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE contraparte_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER contraparte_id_seq_tr 

BEFORE INSERT ON contraparte FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT contraparte_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE contrato_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER contrato_id_seq_tr 

BEFORE INSERT ON contrato FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT contrato_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE contrato_config_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER contrato_config_id_seq_tr 

BEFORE INSERT ON contrato_config FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT contrato_config_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE contrato_documento_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER contrato_documento_id_seq_tr 

BEFORE INSERT ON contrato_documento FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT contrato_documento_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE contrato_pagamento_evento_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER contrato_pagamento_evento_id_seq_tr 

BEFORE INSERT ON contrato_pagamento_evento FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT contrato_pagamento_evento_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE contrato_pagamento_indexador_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER contrato_pagamento_indexador_id_seq_tr 

BEFORE INSERT ON contrato_pagamento_indexador FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT contrato_pagamento_indexador_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE contrato_pagamento_opcao_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER contrato_pagamento_opcao_id_seq_tr 

BEFORE INSERT ON contrato_pagamento_opcao FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT contrato_pagamento_opcao_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE contrato_pagamento_parcela_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER contrato_pagamento_parcela_id_seq_tr 

BEFORE INSERT ON contrato_pagamento_parcela FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT contrato_pagamento_parcela_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE contrato_pessoa_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER contrato_pessoa_id_seq_tr 

BEFORE INSERT ON contrato_pessoa FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT contrato_pessoa_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE contrato_processo_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER contrato_processo_id_seq_tr 

BEFORE INSERT ON contrato_processo FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT contrato_processo_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE contrato_repasse_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER contrato_repasse_id_seq_tr 

BEFORE INSERT ON contrato_repasse FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT contrato_repasse_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE contrato_representante_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER contrato_representante_id_seq_tr 

BEFORE INSERT ON contrato_representante FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT contrato_representante_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE contrato_status_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER contrato_status_id_seq_tr 

BEFORE INSERT ON contrato_status FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT contrato_status_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE convidado_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER convidado_id_seq_tr 

BEFORE INSERT ON convidado FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT convidado_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE convidado_compromisso_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER convidado_compromisso_id_seq_tr 

BEFORE INSERT ON convidado_compromisso FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT convidado_compromisso_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE documento_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER documento_id_seq_tr 

BEFORE INSERT ON documento FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT documento_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE documento_base_contrato_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER documento_base_contrato_id_seq_tr 

BEFORE INSERT ON documento_base_contrato FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT documento_base_contrato_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE email_config_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER email_config_id_seq_tr 

BEFORE INSERT ON email_config FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT email_config_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE envolvimento_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER envolvimento_id_seq_tr 

BEFORE INSERT ON envolvimento FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT envolvimento_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE escritorio_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER escritorio_id_seq_tr 

BEFORE INSERT ON escritorio FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT escritorio_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE escritorio_parceiro_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER escritorio_parceiro_id_seq_tr 

BEFORE INSERT ON escritorio_parceiro FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT escritorio_parceiro_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE especialidade_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER especialidade_id_seq_tr 

BEFORE INSERT ON especialidade FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT especialidade_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE estado_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER estado_id_seq_tr 

BEFORE INSERT ON estado FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT estado_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE estado_agenda_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER estado_agenda_id_seq_tr 

BEFORE INSERT ON estado_agenda FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT estado_agenda_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE estado_agendamento_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER estado_agendamento_id_seq_tr 

BEFORE INSERT ON estado_agendamento FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT estado_agendamento_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE estado_civil_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER estado_civil_id_seq_tr 

BEFORE INSERT ON estado_civil FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT estado_civil_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE etapa_palavras_chaves_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER etapa_palavras_chaves_id_seq_tr 

BEFORE INSERT ON etapa_palavras_chaves FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT etapa_palavras_chaves_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE extrato_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER extrato_id_seq_tr 

BEFORE INSERT ON extrato FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT extrato_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE fluxo_caixa_analitico_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER fluxo_caixa_analitico_id_seq_tr 

BEFORE INSERT ON fluxo_caixa_analitico FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT fluxo_caixa_analitico_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE fluxo_caixa_sintetico_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER fluxo_caixa_sintetico_id_seq_tr 

BEFORE INSERT ON fluxo_caixa_sintetico FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT fluxo_caixa_sintetico_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE formulario_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER formulario_id_seq_tr 

BEFORE INSERT ON formulario FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT formulario_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE foro_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER foro_id_seq_tr 

BEFORE INSERT ON foro FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT foro_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE grupo_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER grupo_id_seq_tr 

BEFORE INSERT ON grupo FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT grupo_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE jornal_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER jornal_id_seq_tr 

BEFORE INSERT ON jornal FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT jornal_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE lancamento_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER lancamento_id_seq_tr 

BEFORE INSERT ON lancamento FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT lancamento_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE lancamento_profissional_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER lancamento_profissional_id_seq_tr 

BEFORE INSERT ON lancamento_profissional FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT lancamento_profissional_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE lancamento_profissional_ajuste_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER lancamento_profissional_ajuste_id_seq_tr 

BEFORE INSERT ON lancamento_profissional_ajuste FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT lancamento_profissional_ajuste_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE log_crontab_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER log_crontab_id_seq_tr 

BEFORE INSERT ON log_crontab FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT log_crontab_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE material_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER material_id_seq_tr 

BEFORE INSERT ON material FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT material_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE mensagem_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER mensagem_id_seq_tr 

BEFORE INSERT ON mensagem FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT mensagem_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE mensagem_acao_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER mensagem_acao_id_seq_tr 

BEFORE INSERT ON mensagem_acao FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT mensagem_acao_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE modelo_doc_aplicacao_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER modelo_doc_aplicacao_id_seq_tr 

BEFORE INSERT ON modelo_doc_aplicacao FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT modelo_doc_aplicacao_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE modelo_doc_tipo_aplicacao_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER modelo_doc_tipo_aplicacao_id_seq_tr 

BEFORE INSERT ON modelo_doc_tipo_aplicacao FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT modelo_doc_tipo_aplicacao_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE modelo_documento_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER modelo_documento_id_seq_tr 

BEFORE INSERT ON modelo_documento FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT modelo_documento_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE modelo_documento_pf_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER modelo_documento_pf_id_seq_tr 

BEFORE INSERT ON modelo_documento_pf FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT modelo_documento_pf_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE modelo_documento_pfrep_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER modelo_documento_pfrep_id_seq_tr 

BEFORE INSERT ON modelo_documento_pfrep FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT modelo_documento_pfrep_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE modelo_documento_pfrep_endereco_rep_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER modelo_documento_pfrep_endereco_rep_seq_tr 

BEFORE INSERT ON modelo_documento_pfrep FOR EACH ROW 

    WHEN 

        (NEW.endereco_rep IS NULL) 

    BEGIN 

        SELECT modelo_documento_pfrep_endereco_rep_seq.NEXTVAL INTO :NEW.endereco_rep FROM DUAL; 

END;
CREATE SEQUENCE modelo_documento_pj_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER modelo_documento_pj_id_seq_tr 

BEFORE INSERT ON modelo_documento_pj FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT modelo_documento_pj_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE movimentacao_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER movimentacao_id_seq_tr 

BEFORE INSERT ON movimentacao FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT movimentacao_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE nacionalidade_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER nacionalidade_id_seq_tr 

BEFORE INSERT ON nacionalidade FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT nacionalidade_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE orgao_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER orgao_id_seq_tr 

BEFORE INSERT ON orgao FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT orgao_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE padrao_atendimento_documento_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER padrao_atendimento_documento_id_seq_tr 

BEFORE INSERT ON padrao_atendimento_documento FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT padrao_atendimento_documento_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE padrao_atend_modelo_doc_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER padrao_atend_modelo_doc_id_seq_tr 

BEFORE INSERT ON padrao_atend_modelo_doc FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT padrao_atend_modelo_doc_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE parceiro_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER parceiro_id_seq_tr 

BEFORE INSERT ON parceiro FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT parceiro_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE pessoa_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER pessoa_id_seq_tr 

BEFORE INSERT ON pessoa FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT pessoa_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE pessoa_contato_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER pessoa_contato_id_seq_tr 

BEFORE INSERT ON pessoa_contato FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT pessoa_contato_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE pessoa_endereco_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER pessoa_endereco_id_seq_tr 

BEFORE INSERT ON pessoa_endereco FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT pessoa_endereco_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE pessoa_especialidade_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER pessoa_especialidade_id_seq_tr 

BEFORE INSERT ON pessoa_especialidade FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT pessoa_especialidade_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE pessoa_grupo_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER pessoa_grupo_id_seq_tr 

BEFORE INSERT ON pessoa_grupo FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT pessoa_grupo_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE pessoa_representantes_legais_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER pessoa_representantes_legais_id_seq_tr 

BEFORE INSERT ON pessoa_representantes_legais FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT pessoa_representantes_legais_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE preferencia_sistema_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER preferencia_sistema_id_seq_tr 

BEFORE INSERT ON preferencia_sistema FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT preferencia_sistema_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE procedimento_preco_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER procedimento_preco_id_seq_tr 

BEFORE INSERT ON procedimento_preco FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT procedimento_preco_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE processo_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER processo_id_seq_tr 

BEFORE INSERT ON processo FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT processo_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE processo_publicacoes_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER processo_publicacoes_id_seq_tr 

BEFORE INSERT ON processo_publicacoes FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT processo_publicacoes_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE processo_vinculo_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER processo_vinculo_id_seq_tr 

BEFORE INSERT ON processo_vinculo FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT processo_vinculo_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE publicacao_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER publicacao_id_seq_tr 

BEFORE INSERT ON publicacao FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT publicacao_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE publicacao_etapa_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER publicacao_etapa_id_seq_tr 

BEFORE INSERT ON publicacao_etapa FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT publicacao_etapa_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE publicacao_movimentacao_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER publicacao_movimentacao_id_seq_tr 

BEFORE INSERT ON publicacao_movimentacao FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT publicacao_movimentacao_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE publicacao_profissional_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER publicacao_profissional_id_seq_tr 

BEFORE INSERT ON publicacao_profissional FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT publicacao_profissional_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE publicacao_sugestao_prazo_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER publicacao_sugestao_prazo_id_seq_tr 

BEFORE INSERT ON publicacao_sugestao_prazo FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT publicacao_sugestao_prazo_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE questao_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER questao_id_seq_tr 

BEFORE INSERT ON questao FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT questao_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE requisicao_pagamento_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER requisicao_pagamento_id_seq_tr 

BEFORE INSERT ON requisicao_pagamento FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT requisicao_pagamento_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE requisicao_pagamento_cliente_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER requisicao_pagamento_cliente_id_seq_tr 

BEFORE INSERT ON requisicao_pagamento_cliente FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT requisicao_pagamento_cliente_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE requisicao_pagamento_etapa2_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER requisicao_pagamento_etapa2_id_seq_tr 

BEFORE INSERT ON requisicao_pagamento_etapa2 FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT requisicao_pagamento_etapa2_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE requisicao_pagamento_etapa3_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER requisicao_pagamento_etapa3_id_seq_tr 

BEFORE INSERT ON requisicao_pagamento_etapa3 FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT requisicao_pagamento_etapa3_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE resposta_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER resposta_id_seq_tr 

BEFORE INSERT ON resposta FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT resposta_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE resposta_formulario_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER resposta_formulario_id_seq_tr 

BEFORE INSERT ON resposta_formulario FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT resposta_formulario_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE sexo_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER sexo_id_seq_tr 

BEFORE INSERT ON sexo FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT sexo_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE situacao_profissional_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER situacao_profissional_id_seq_tr 

BEFORE INSERT ON situacao_profissional FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT situacao_profissional_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE status_contrato_pagamento_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER status_contrato_pagamento_id_seq_tr 

BEFORE INSERT ON status_contrato_pagamento FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT status_contrato_pagamento_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE status_processual_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER status_processual_id_seq_tr 

BEFORE INSERT ON status_processual FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT status_processual_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE status_requisicao_pagamento_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER status_requisicao_pagamento_id_seq_tr 

BEFORE INSERT ON status_requisicao_pagamento FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT status_requisicao_pagamento_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE tarefa_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER tarefa_id_seq_tr 

BEFORE INSERT ON tarefa FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT tarefa_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE tarefa_cliente_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER tarefa_cliente_id_seq_tr 

BEFORE INSERT ON tarefa_cliente FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT tarefa_cliente_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE tarefa_comentario_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER tarefa_comentario_id_seq_tr 

BEFORE INSERT ON tarefa_comentario FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT tarefa_comentario_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE tarefa_configuracao_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER tarefa_configuracao_id_seq_tr 

BEFORE INSERT ON tarefa_configuracao FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT tarefa_configuracao_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE tarefa_horas_trabalhadas_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER tarefa_horas_trabalhadas_id_seq_tr 

BEFORE INSERT ON tarefa_horas_trabalhadas FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT tarefa_horas_trabalhadas_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE tarefa_movimentacao_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER tarefa_movimentacao_id_seq_tr 

BEFORE INSERT ON tarefa_movimentacao FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT tarefa_movimentacao_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE tarefa_status_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER tarefa_status_id_seq_tr 

BEFORE INSERT ON tarefa_status FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT tarefa_status_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE tarefa_usuario_master_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER tarefa_usuario_master_id_seq_tr 

BEFORE INSERT ON tarefa_usuario_master FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT tarefa_usuario_master_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE tarefa_vinculo_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER tarefa_vinculo_id_seq_tr 

BEFORE INSERT ON tarefa_vinculo FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT tarefa_vinculo_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE template_acao_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER template_acao_id_seq_tr 

BEFORE INSERT ON template_acao FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT template_acao_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE template_escritorio_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER template_escritorio_id_seq_tr 

BEFORE INSERT ON template_escritorio FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT template_escritorio_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE tipo_andamento_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER tipo_andamento_id_seq_tr 

BEFORE INSERT ON tipo_andamento FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT tipo_andamento_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE tipo_atendimento_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER tipo_atendimento_id_seq_tr 

BEFORE INSERT ON tipo_atendimento FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT tipo_atendimento_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE tipo_compromisso_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER tipo_compromisso_id_seq_tr 

BEFORE INSERT ON tipo_compromisso FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT tipo_compromisso_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE tipo_conta_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER tipo_conta_id_seq_tr 

BEFORE INSERT ON tipo_conta FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT tipo_conta_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE tipo_conta_caixa_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER tipo_conta_caixa_id_seq_tr 

BEFORE INSERT ON tipo_conta_caixa FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT tipo_conta_caixa_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE tipo_doc_financeiro_padrao_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER tipo_doc_financeiro_padrao_id_seq_tr 

BEFORE INSERT ON tipo_doc_financeiro_padrao FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT tipo_doc_financeiro_padrao_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE tipo_documento_financeiro_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER tipo_documento_financeiro_id_seq_tr 

BEFORE INSERT ON tipo_documento_financeiro FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT tipo_documento_financeiro_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE tipo_extrato_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER tipo_extrato_id_seq_tr 

BEFORE INSERT ON tipo_extrato FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT tipo_extrato_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE tipo_modelo_documento_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER tipo_modelo_documento_id_seq_tr 

BEFORE INSERT ON tipo_modelo_documento FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT tipo_modelo_documento_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE tipo_pagamento_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER tipo_pagamento_id_seq_tr 

BEFORE INSERT ON tipo_pagamento FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT tipo_pagamento_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE tipo_pessoa_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER tipo_pessoa_id_seq_tr 

BEFORE INSERT ON tipo_pessoa FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT tipo_pessoa_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE tipo_prazo_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER tipo_prazo_id_seq_tr 

BEFORE INSERT ON tipo_prazo FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT tipo_prazo_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE tipo_processo_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER tipo_processo_id_seq_tr 

BEFORE INSERT ON tipo_processo FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT tipo_processo_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE tipo_profissional_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER tipo_profissional_id_seq_tr 

BEFORE INSERT ON tipo_profissional FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT tipo_profissional_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE tipos_requisicao_pagamento_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER tipos_requisicao_pagamento_id_seq_tr 

BEFORE INSERT ON tipos_requisicao_pagamento FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT tipos_requisicao_pagamento_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE tmp_documento_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER tmp_documento_id_seq_tr 

BEFORE INSERT ON tmp_documento FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT tmp_documento_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE tribunal_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER tribunal_id_seq_tr 

BEFORE INSERT ON tribunal FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT tribunal_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE unidade_indexador_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER unidade_indexador_id_seq_tr 

BEFORE INSERT ON unidade_indexador FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT unidade_indexador_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE unidade_medida_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER unidade_medida_id_seq_tr 

BEFORE INSERT ON unidade_medida FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT unidade_medida_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE vara_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER vara_id_seq_tr 

BEFORE INSERT ON vara FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT vara_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE video_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER video_id_seq_tr 

BEFORE INSERT ON video FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT video_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE whatsapp_config_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER whatsapp_config_id_seq_tr 

BEFORE INSERT ON whatsapp_config FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT whatsapp_config_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
 
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

CREATE VIEW processo_view AS SELECT 
    p.id AS id, 
    tp.nome AS tipo_processo, 
    p.numero_cnj_numero AS numero, 
    pe.nome AS cliente, 
    a.nome AS area, 
    ass.nome AS assunto, 
    rep.nome AS representante, 
    pe.id AS pessoa_id, 
    p.exibir_cliente AS exibir_cliente, 
    pp_ult.publicacao_etapa_id AS ultima_etapa_id, 
    etapa_pp.etapa_nome AS ultima_etapa
FROM processo p 
JOIN contrato_processo cp 
    ON cp.processo_id = p.id 
JOIN contrato_pessoa cpe 
    ON cpe.contrato_id = cp.contrato_id 
JOIN pessoa pe 
    ON pe.id = cpe.cliente_id 
JOIN tipo_processo tp 
    ON tp.id = p.tipo_processo_id 
JOIN area a 
    ON a.id = p.area_id 
JOIN assunto ass 
    ON ass.id = p.assunto_id 
JOIN pessoa rep 
    ON rep.id = p.responsavel_id

LEFT JOIN (
    SELECT DISTINCT ON (mov.processo_id)
        mov.processo_id,
        mov.publicacao_etapa_id,
        mov.publicacao_id,
        mov.andamento_id,
        mov.id,
        mov.data_ultima_movimentacao
    FROM (
        SELECT 
            pp.processo_id,
            pp.publicacao_etapa_id,
            pp.publicacao_id,
            pp.andamento_id,
            pp.id,
            pub.data_disponibilizacao::timestamp AS data_ultima_movimentacao
        FROM processo_publicacoes pp
        JOIN publicacao pub 
            ON pub.id = pp.publicacao_id
        WHERE pp.publicacao_etapa_id NOT IN (1, 10)
          AND pub.etapa_verificada = 'S'

        UNION ALL

        SELECT 
            pp.processo_id,
            pp.publicacao_etapa_id,
            pp.publicacao_id,
            pp.andamento_id,
            pp.id,
            andm.data_andamento::timestamp AS data_ultima_movimentacao
        FROM processo_publicacoes pp
        JOIN andamento andm
            ON andm.id = pp.andamento_id
        WHERE pp.publicacao_etapa_id NOT IN (1, 10)
          AND andm.etapa_verificada = 'S'
    ) mov
    ORDER BY 
        mov.processo_id,
        mov.data_ultima_movimentacao DESC NULLS LAST,
        mov.id DESC
) pp_ult 
    ON pp_ult.processo_id = p.id

LEFT JOIN publicacao_etapa etapa_pp 
    ON etapa_pp.id = pp_ult.publicacao_etapa_id

ORDER BY 
    pp_ult.data_ultima_movimentacao DESC NULLS LAST,
    p.id DESC;; 

CREATE VIEW requisicao_pagamento_listagem AS SELECT
    rp.id AS requisicao_pagamento_id,
    rpc.id AS requisicao_pagamento_cliente_id,
    rpc.pessoa_id AS pessoa_id,

    COALESCE(p.numero_cnj_numero, p.numero_outro) AS numero_processo,

    trp.id AS tipo_requisicao,

    pe.nome AS cliente,

    rpc.status_requisicao_pagamento_id AS status,
    rpc.data_requerimento AS data_requerimento,

    e2.data_deferimento_expedicao_requisitorio AS data_deferimento_expedicao_requisitorio,

    e3.data_pedido_mle AS data_pedido_mle,
    e3.data_deferimento_mle AS data_deferimento_mle

FROM requisicao_pagamento rp

LEFT JOIN processo p
    ON p.id = rp.processo_id

LEFT JOIN tipos_requisicao_pagamento trp
    ON trp.id = rp.tipos_requisicao_pagamento_id

LEFT JOIN requisicao_pagamento_cliente rpc
    ON rpc.requisicao_pagamento_id = rp.id

LEFT JOIN pessoa pe
    ON pe.id = rpc.pessoa_id

LEFT JOIN (
    SELECT
        requisicao_pagamento_cliente_id,
        MAX(data_deferimento_expedicao_requisitorio) AS data_deferimento_expedicao_requisitorio
    FROM requisicao_pagamento_etapa2
    GROUP BY requisicao_pagamento_cliente_id
) e2
    ON e2.requisicao_pagamento_cliente_id = rpc.id

LEFT JOIN (
    SELECT
        requisicao_pagamento_cliente_id,
        MAX(data_pedido_mle) AS data_pedido_mle,
        MAX(data_deferimento_mle) AS data_deferimento_mle
    FROM requisicao_pagamento_etapa3
    GROUP BY requisicao_pagamento_cliente_id
) e3
    ON e3.requisicao_pagamento_cliente_id = rpc.id

ORDER BY
    rp.id DESC,
    pe.nome ASC;; 

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
    tipo_processo.nome as "tipo_processo_nome",
    publicacao.publicacao_etapa_id as "publicacao_etapa_id",
    publicacao.etapa_verificada as "etapa_verificada"
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
    tipo_processo.nome as "tipo_processo_nome",
    andamento.publicacao_etapa_id as "publicacao_etapa_id",
    andamento.etapa_verificada as "etapa_verificada"
FROM  
    andamento,  
    processo,  
    tipo_processo, 
    tipo_andamento 
WHERE  
    andamento.processo_id = processo.id AND  
    processo.tipo_processo_id = tipo_processo.id AND 
    andamento.tipo_andamento_id = tipo_andamento.id; 

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
    status_processual.nome AS "status",
    publicacao_etapa.etapa_nome AS "etapa",
    publicacao.etapa_verificada as "etapa_verificada"
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
    LEFT JOIN tipo_processo ON processo.tipo_processo_id = tipo_processo.id
    LEFT JOIN publicacao_etapa ON publicacao.publicacao_etapa_id = publicacao_etapa.id;
; 
 
