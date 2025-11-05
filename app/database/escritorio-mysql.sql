CREATE TABLE agenda( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `escritorio_id` int   NOT NULL  , 
      `profissional_id` int   NOT NULL  , 
      `nome` text   NOT NULL  , 
      `horario_inicial` time   NOT NULL    DEFAULT '08:00', 
      `horario_final` time   NOT NULL    DEFAULT '18:00', 
      `visualizacao_inicial` varchar  (30)   NOT NULL    DEFAULT 'agendaWeek', 
      `horario_inicio_intervalo` time   , 
      `horario_fim_intervalo` time   , 
      `duracao` int   NOT NULL    DEFAULT 30, 
      `dias` text   NOT NULL  , 
      `procedimento_id` int   , 
      `cor` varchar  (10)   , 
      `aceita_agendamento_online` char  (1)     DEFAULT 'F', 
      `publica` char  (1)     DEFAULT 'F', 
      `fl_permite_choque_horario` char  (1)     DEFAULT 'T', 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE agendamento( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `cliente_id` int   NOT NULL  , 
      `estado_agenda_id` int   NOT NULL  , 
      `agenda_id` int   NOT NULL  , 
      `especialidade_id` int   , 
      `dt_inicial` datetime   NOT NULL  , 
      `dt_final` datetime   NOT NULL  , 
      `agendamento_original_id` int   , 
      `observacao` text   , 
      `ativo` char  (1)     DEFAULT 'T', 
      `ano_inicial` text   , 
      `mes_inicial` text   , 
      `ano_mes_inicial` text   , 
      `ano_final` text   , 
      `mes_final` text   , 
      `ano_mes_final` text   , 
      `online` char  (1)     DEFAULT 'F', 
      `link_atendimento_online` text   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE agendamento_procedimento( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `agendamento_id` int   NOT NULL  , 
      `procedimento_id` int   NOT NULL  , 
      `parceiro_id` int   NOT NULL  , 
      `quantidade` double   NOT NULL  , 
      `valor` double   , 
      `valor_total` double   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE agenda_profissional( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `profissional_id` int   NOT NULL  , 
      `agenda_id` int   NOT NULL  , 
      `fl_manipula_atendimento` char   NOT NULL    DEFAULT 'N', 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE andamento( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `processo_id` int   NOT NULL  , 
      `tipo_andamento_id` int   NOT NULL  , 
      `data_andamento` datetime   , 
      `titulo` text   NOT NULL  , 
      `texto` text   , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE anexo( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `atendimento_id` int   NOT NULL  , 
      `arquivo` text   NOT NULL  , 
      `observacao` text   , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE api_error( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `classe` varchar  (255)   , 
      `metodo` varchar  (255)   , 
      `url` varchar  (500)   , 
      `dados` varchar  (3000)   , 
      `error_message` varchar  (3000)   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE area( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `nome` varchar  (255)   NOT NULL  , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE assunto( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `area_id` int   NOT NULL  , 
      `nome` varchar  (255)   NOT NULL  , 
      `descricao` text   , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE atendimento( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `agendamento_id` int   , 
      `cliente_id` int   NOT NULL  , 
      `profissional_id` int   NOT NULL  , 
      `tipo_atendimento_id` int   NOT NULL  , 
      `informacoes` varchar  (500)   , 
      `dt_inicio` datetime   , 
      `dt_final` datetime   , 
      `valor_total` double   , 
      `ano_inicial` text   , 
      `mes_inicial` text   , 
      `ano_mes_inicial` text   , 
      `mes_final` text   , 
      `ano_final` text   , 
      `ano_mes_final` text   , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE atendimento_contrato( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `atendimento_id` int   NOT NULL  , 
      `contrato_id` int   NOT NULL  , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE atendimento_historico( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `atendimento_id` int   NOT NULL  , 
      `historico` text   NOT NULL  , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE atendimento_material( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `material_id` int   NOT NULL  , 
      `atendimento_id` int   NOT NULL  , 
      `quantidade` double   NOT NULL  , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE atendimento_procedimento( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `parceiro_id` int   NOT NULL  , 
      `atendimento_id` int   NOT NULL  , 
      `procedimento_id` int   NOT NULL  , 
      `quantidade` double   NOT NULL  , 
      `valor` double   , 
      `valor_total` double   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE banco( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `codigo` varchar  (10)   NOT NULL  , 
      `nome` varchar  (255)   NOT NULL  , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE bloqueio( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `agenda_id` int   NOT NULL  , 
      `dt_inicio` datetime   NOT NULL  , 
      `dt_final` datetime   NOT NULL  , 
      `observacao` text   , 
      `horario_bloqueio_original` int   , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE categoria_conta( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `tipo_conta_id` int   NOT NULL  , 
      `nome` text   NOT NULL  , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE cep_cache( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `cep` varchar  (12)   NOT NULL  , 
      `codigo_ibge` text   , 
      `rua` text   , 
      `cidade` text   , 
      `bairro` text   , 
      `uf` text   , 
      `cidade_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE cidade( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `estado_id` int   NOT NULL  , 
      `nome` text   NOT NULL  , 
      `codigo_ibge` text   NOT NULL  , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE classificacoes( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `nome` varchar  (255)   NOT NULL  , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE classificacoes_cliente( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `pessoa_id` int   NOT NULL  , 
      `classificacoes_id` int   NOT NULL  , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE classificacoes_contraparte( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `contraparte_id` int   , 
      `pessoa_id` int   NOT NULL  , 
      `classificacoes_contraparte_dados_id` int   NOT NULL  , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE classificacoes_contraparte_dados( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
      `nome` varchar  (255)   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE clones( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `qtd` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE comarca( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `nome` varchar  (255)   NOT NULL  , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE compromisso( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `agenda_id` int   NOT NULL  , 
      `tipo_compromisso_id` int   NOT NULL  , 
      `dt_inicio` datetime   NOT NULL  , 
      `dt_final` datetime   NOT NULL  , 
      `observacao` text   , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE config_busca_a_partir( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `nome` varchar  (255)   NOT NULL  , 
      `add_dias` int   NOT NULL  , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE config_busca_prazo( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `titulo` varchar  (255)   NOT NULL  , 
      `prazo` int   NOT NULL  , 
      `tipo_prazo_id` int   NOT NULL  , 
      `config_busca_a_partir_id` int   NOT NULL  , 
      `pont` int     DEFAULT 0, 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE config_busca_prazo_texto( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `config_busca_prazo_id` int   NOT NULL  , 
      `texto` text   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE conta( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `pessoa_id` int   NOT NULL  , 
      `categoria_conta_id` int   NOT NULL  , 
      `tipo_conta_id` int   NOT NULL  , 
      `escritorio_id` int   NOT NULL  , 
      `tipo_documento_financeiro_id` int   NOT NULL  , 
      `atendimento_id` int   , 
      `contrato_id` int   , 
      `profissional_id` int   , 
      `processo_id` int   , 
      `numero_documento` varchar  (255)   , 
      `data_emissao` date   NOT NULL  , 
      `total_parcelas` int   NOT NULL    DEFAULT 1, 
      `quitada` char  (1)   NOT NULL    DEFAULT 'N', 
      `descricao` text   NOT NULL  , 
      `conta_origem_id` int   , 
      `total_conta` double   NOT NULL  , 
      `mes` text   , 
      `ano` text   , 
      `ano_mes` text   , 
      `proximo_vencimento_lancamento` date   , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE conta_caixa( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `nome` varchar  (255)   NOT NULL  , 
      `tipo_conta_caixa_id` int   NOT NULL  , 
      `dt_inicial` datetime   NOT NULL  , 
      `saldo_inicial` double   NOT NULL  , 
      `saldo_instantaneo` double   , 
      `saldo_nao_compensado` double   , 
      `ativo` char  (1)   NOT NULL    DEFAULT 'S', 
      `cor_nao_compensado` varchar  (7)     DEFAULT '#FF0000', 
      `cor_compensado` varchar  (7)     DEFAULT '#00FF00', 
      `banco_id` int   , 
      `codigo_agencia` varchar  (10)   , 
      `codigo_conta` varchar  (30)   , 
      `descricao_agencia` varchar  (255)   , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE contraparte( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `processo_id` int   NOT NULL  , 
      `pessoa_id` int   NOT NULL  , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE contrato( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `escritorio_id` int   NOT NULL  , 
      `tipo_processo_id` int   , 
      `area_id` int   , 
      `contrato_status_id` int   , 
      `assunto_id` int   , 
      `numero` varchar  (30)   NOT NULL  , 
      `objeto` text   NOT NULL  , 
      `valor` double   , 
      `quantidade_parcelas` int   , 
      `envolvimento_id` int   , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE contrato_config( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `clausula_pagamento` int   NOT NULL  , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE contrato_documento( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `contrato_id` int   NOT NULL  , 
      `modelo_documento_id` int   NOT NULL  , 
      `filename` text   , 
      `dt_preenchimento` datetime   NOT NULL  , 
      `autenticador` text   , 
      `dt_validade` datetime   , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE contrato_pagamento_evento( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `nome` varchar  (255)   NOT NULL  , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE contrato_pagamento_indexador( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `nome` varchar  (255)   NOT NULL  , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE contrato_pagamento_opcao( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `nome` varchar  (255)   NOT NULL  , 
      `descricao1` text   NOT NULL  , 
      `descricaon` text   NOT NULL  , 
      `recebe_valor` char  (1)   NOT NULL    DEFAULT 'N', 
      `recebe_data` char  (1)   NOT NULL    DEFAULT 'N', 
      `recebe_evento` char  (1)   NOT NULL    DEFAULT 'N', 
      `recebe_indexador` char  (1)   NOT NULL    DEFAULT 'N', 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE contrato_pagamento_parcela( 
      `contrato_id` int   NOT NULL  , 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `contrato_opcao_pagamento_id` int   NOT NULL  , 
      `valor` double   , 
      `data_pagamento` date   , 
      `contrato_evento_id` int   , 
      `unidade_indexador_id` int   , 
      `complemento_indexador` varchar  (255)   , 
      `contrato_indexador_id` int   , 
      `descritivo` text   , 
      `numero_parcelas` int   , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE contrato_pessoa( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `contrato_id` int   NOT NULL  , 
      `cliente_id` int   NOT NULL  , 
      `percentual` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE contrato_processo( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `contrato_id` int   NOT NULL  , 
      `processo_id` int   NOT NULL  , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE contrato_repasse( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `contrato_id` int   NOT NULL  , 
      `pessoa_id` int   NOT NULL  , 
      `percentual` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE contrato_representante( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `contrato_id` int   NOT NULL  , 
      `representante_id` int   NOT NULL  , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE contrato_status( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `nome` varchar  (255)   NOT NULL  , 
      `cor` varchar  (10)   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE convidado( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `agendamento_id` int   NOT NULL  , 
      `agenda_id` int   NOT NULL  , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE convidado_compromisso( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `compromisso_id` int   NOT NULL  , 
      `agenda_id` int   NOT NULL  , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE documento( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `atendimento_id` int   NOT NULL  , 
      `modelo_documento_id` int   , 
      `filename` text   , 
      `observacao` text   , 
      `dt_preenchimento` datetime   NOT NULL  , 
      `autenticador` text   , 
      `dt_validade` date   , 
      `procedimento_id` int   , 
      `medico_assistente` text   , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE documento_base_contrato( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `area_id` int   NOT NULL  , 
      `modelo_documento_id` int   NOT NULL  , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE email_config( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `escritorio_id` int   NOT NULL  , 
      `port` text   , 
      `username` text   , 
      `password` text   , 
      `host` text   , 
      `from_email` text   , 
      `from_name` text   , 
      `smtp_auth` char  (1)     DEFAULT 'T::bpchar', 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE envolvimento( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `tipo_processo_id` int   NOT NULL  , 
      `nome` varchar  (255)   NOT NULL  , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE escritorio( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `system_unit_id` int   NOT NULL  , 
      `cidade_id` int   NOT NULL  , 
      `nome` text   NOT NULL  , 
      `cnpj` text   NOT NULL  , 
      `telefone` text   NOT NULL  , 
      `email` text   NOT NULL  , 
      `endereco` text   NOT NULL  , 
      `bairro` text   NOT NULL  , 
      `cep` text   NOT NULL  , 
      `numero` text   , 
      `complemento` text   , 
      `logo_documento` text   , 
      `url_sistema` text   , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE escritorio_parceiro( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `parceiro_id` int   NOT NULL  , 
      `escritorio_id` int   NOT NULL  , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE especialidade( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `descricao` text   NOT NULL  , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE estado( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `nome` text   NOT NULL  , 
      `sigla` char  (2)   NOT NULL  , 
      `codigo_ibge` text   , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE estado_agenda( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `nome` text   NOT NULL  , 
      `estado_inicial` char   NOT NULL    DEFAULT 'N', 
      `estado_final` char   NOT NULL    DEFAULT 'N', 
      `cor` varchar  (10)   NOT NULL  , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE estado_agendamento( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `agendamento_id` int   NOT NULL  , 
      `estado_agenda_id` int   NOT NULL  , 
      `system_users_id` int   , 
      `atribuido_em` datetime   NOT NULL  , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE estado_civil( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `nome` varchar  (30)   NOT NULL  , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE extrato( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `escritorio_id` int   NOT NULL  , 
      `conta_caixa_id` int   NOT NULL  , 
      `lancamento_id` int   , 
      `categoria_conta_id` int   , 
      `tipo_extrato_id` int   NOT NULL  , 
      `transferencia_conta_caixa_id` int   , 
      `extrato_vinculado` int   , 
      `entrada_valor` double   , 
      `saida_valor` double   , 
      `data_lancamento` date   , 
      `data_previsao_compensacao` date   , 
      `compensado` char   NOT NULL    DEFAULT 'N', 
      `data_compensacao` date   , 
      `historico` varchar  (3000)   , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
      `mes` text   , 
      `ano` text   , 
      `ano_mes` text   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE fluxo_caixa_analitico( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `dia` date   NOT NULL  , 
      `tipo` char  (1)   NOT NULL  , 
      `numero` varchar  (255)   NOT NULL  , 
      `historico` varchar  (255)   NOT NULL  , 
      `entrada` double   , 
      `saida` double   , 
      `saldo` double   NOT NULL    DEFAULT 0, 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE fluxo_caixa_sintetico( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `dia` date   NOT NULL  , 
      `tipo` char  (1)   , 
      `numero` varchar  (255)   , 
      `historico` varchar  (255)   , 
      `entrada` double   , 
      `saida` double   , 
      `saldo` double   NOT NULL    DEFAULT 0, 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE formulario( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `nome` text   NOT NULL  , 
      `ativo` char  (1)   NOT NULL    DEFAULT 'S', 
      `ordem` int   NOT NULL  , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE foro( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `nome` varchar  (255)   NOT NULL  , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE grupo( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `nome` text   NOT NULL  , 
      `cor` varchar  (10)   NOT NULL  , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE jornal( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `nome` varchar  (255)   NOT NULL  , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE lancamento( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `conta_id` int   NOT NULL  , 
      `tipo_pagamento_id` int   NOT NULL  , 
      `parcela` int     DEFAULT 1, 
      `dt_vencimento` date   NOT NULL  , 
      `valor` double   NOT NULL  , 
      `dt_pagamento` date   , 
      `ano_pagamento` text   , 
      `mes_pagamento` text   , 
      `ano_mes_pagamento` text   , 
      `ano_vencimento` text   , 
      `mes_vencimento` text   , 
      `ano_mes_vencimento` text   , 
      `cheque_numero` varchar  (100)   , 
      `cheque_banco_id` int   , 
      `extrato_id` int   , 
      `cancelado` char  (1)     DEFAULT 'N', 
      `motivo_cancelamento` varchar  (300)   , 
      `contrato_parcela_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE log_crontab( 
      `system_unit_id` int   NOT NULL  , 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `classe` text   NOT NULL  , 
      `metodo` text   , 
      `data_hora` datetime   , 
      `status` int   , 
      `mensagem` text   , 
      `observacao` text   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE material( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `unidade_medida_id` int   NOT NULL  , 
      `nome` text   NOT NULL  , 
      `estoque_minimo` double   , 
      `dt_vencimento` date   , 
      `estoque_atualizado` double   , 
      `lote` text   , 
      `ativo` char  (1)   NOT NULL    DEFAULT 'S', 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE mensagem( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `agendamento_id` int   NOT NULL  , 
      `template_escritorio_id` int   , 
      `system_user_id` int   NOT NULL  , 
      `titulo` text   , 
      `template` text   , 
      `enviado_em` datetime   , 
      `tipo_mensagem` text   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE mensagem_acao( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `mensagem_id` int   NOT NULL  , 
      `url` text   , 
      `label` text   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE modelo_doc_aplicacao( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `modelo_documento_id` int   NOT NULL  , 
      `tipo_aplicacao_id` int   NOT NULL  , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE modelo_doc_tipo_aplicacao( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `nome` varchar  (255)   NOT NULL  , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE modelo_documento( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `tipo_modelo_documento_id` int   NOT NULL  , 
      `nome` text   NOT NULL  , 
      `ativo` char  (1)   NOT NULL    DEFAULT 'S', 
      `clausula_pagamento` int   , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE modelo_documento_pf( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `modelo_documento_id` int   NOT NULL  , 
      `filename` text   NOT NULL  , 
      `objeto` char  (1)     DEFAULT 'N', 
      `informacoes_pagamento` char  (1)     DEFAULT 'N', 
      `nacionalidade` char  (1)     DEFAULT 'N', 
      `estado_civil` char  (1)     DEFAULT 'N', 
      `profissao` char  (1)     DEFAULT 'N', 
      `rg` char  (1)     DEFAULT 'N', 
      `cpf` char  (1)     DEFAULT 'N', 
      `endereco` char  (1)     DEFAULT 'N', 
      `data_nascimento` char  (1)   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE modelo_documento_pfrep( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `modelo_documento_id` int   NOT NULL  , 
      `filename` text   NOT NULL  , 
      `objeto` char  (1)     DEFAULT 'N', 
      `informacoes_pagamento` char  (1)     DEFAULT 'N', 
      `nacionalidade` char  (1)     DEFAULT 'N', 
      `estado_civil` char  (1)     DEFAULT 'N', 
      `profissao` char  (1)     DEFAULT 'N', 
      `rg` char  (1)     DEFAULT 'N', 
      `cpf` char  (1)     DEFAULT 'N', 
      `data_nascimento` char  (1)   , 
      `endereco` char  (1)     DEFAULT 'N', 
      `nacionalidade_rep` char  (1)     DEFAULT 'N', 
      `estado_civil_rep` char  (1)     DEFAULT 'N', 
      `profissao_rep` char  (1)     DEFAULT 'N', 
      `rg_rep` char  (1)     DEFAULT 'N', 
      `cpf_rep` char  (1)     DEFAULT 'N', 
      `endereco_rep`  INT  AUTO_INCREMENT    , 
      `data_nascimento_rep` char  (1)   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE modelo_documento_pj( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `modelo_documento_id` int   NOT NULL  , 
      `filename` text   NOT NULL  , 
      `objeto` char  (1)     DEFAULT 'N', 
      `informacoes_pagamento` char  (1)     DEFAULT 'N', 
      `cnpj` char  (1)     DEFAULT 'N', 
      `endereco` char  (1)     DEFAULT 'N', 
      `nacionalidade_rep` char  (1)     DEFAULT 'N', 
      `estado_civil_rep` char  (1)     DEFAULT 'N', 
      `profissao_rep` char  (1)     DEFAULT 'N', 
      `rg_rep` char  (1)     DEFAULT 'N', 
      `cpf_rep` char  (1)     DEFAULT 'N', 
      `endereco_rep` char  (1)   , 
      `data_abertura` char  (1)   , 
      `data_nascimento_rep` char  (1)   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE movimentacao( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `material_id` int   NOT NULL  , 
      `system_user_id` int   NOT NULL  , 
      `dt_movimentacao` text   , 
      `quantidade` double   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE nacionalidade( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `nome` varchar  (30)   NOT NULL  , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE orgao( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `nome` varchar  (255)   NOT NULL  , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE padrao_atendimento_documento( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `nome` varchar  (255)   NOT NULL  , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE padrao_atend_modelo_doc( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `tipo_padrao_doc_atendimento_id` int   NOT NULL  , 
      `modelo_documento_id` int   NOT NULL  , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE parceiro( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `nome` text   NOT NULL  , 
      `pessoa_id` int   , 
      `percentual` double  (255)   , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE pessoa( 
      `tipo_pessoa_id` int   NOT NULL  , 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `nome` varchar  (255)   NOT NULL  , 
      `nome_busca` varchar  (255)   , 
      `email` varchar  (255)   , 
      `telefone` varchar  (20)   , 
      `aceita_receber_mensagen_whatsapp` char  (1)   NOT NULL    DEFAULT 'F', 
      `system_users_id` int   , 
      `dt_nascimento_abertura` date   , 
      `dt_falecimento` date   , 
      `cpf_cnpj` varchar  (14)   , 
      `rg_ie` varchar  (15)   , 
      `orgao_emissor` varchar  (20)   , 
      `sexo_id` int   , 
      `nacionalidade_id` int   , 
      `estado_civil_id` int   , 
      `profissao` text   , 
      `nit` varchar  (255)   , 
      `ctps` varchar  (255)   , 
      `situacao_profissional_id` int   , 
      `orgao` varchar  (255)   , 
      `unidade` varchar  (255)   , 
      `observacao` text   , 
      `assinatura` text   , 
      `tratamento` text   , 
      `tipo_profissional_id` int   , 
      `orgao_registro_profissional` varchar  (30)   , 
      `registro_profissional` varchar  (255)   , 
      `usuario` varchar  (255)   , 
      `senha` varchar  (255)   , 
      `foto` text   , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
      `chave_aasp` varchar  (255)   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE pessoa_contato( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `pessoa_id` int   NOT NULL  , 
      `descricao` varchar  (255)   NOT NULL  , 
      `telefone` varchar  (20)   , 
      `email` varchar  (255)   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE pessoa_endereco( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `pessoa_id` int   NOT NULL  , 
      `cidade_id` int   NOT NULL  , 
      `cep` varchar  (10)   NOT NULL  , 
      `rua` varchar  (500)   NOT NULL  , 
      `bairro` varchar  (500)   NOT NULL  , 
      `numero` varchar  (100)   NOT NULL  , 
      `complemento` varchar  (500)   , 
      `principal` char     DEFAULT 'F', 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE pessoa_especialidade( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `pessoa_id` int   NOT NULL  , 
      `especialidade_id` int   NOT NULL  , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE pessoa_grupo( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `pessoa_id` int   NOT NULL  , 
      `grupo_id` int   NOT NULL  , 
      `cor` varchar  (10)     DEFAULT '#ffffff', 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE pessoa_representantes_legais( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `pessoa_juridica_id` int   NOT NULL  , 
      `representante_id` int   NOT NULL  , 
      `principal` char  (1)   , 
      `descricao` varchar  (255)   NOT NULL  , 
      `created_at` datetime   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE preferencia_sistema( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `system_users_id` int   NOT NULL  , 
      `zoom` int   NOT NULL    DEFAULT 100, 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
      `menu_fixado` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE procedimento( 
      `id` int   NOT NULL  , 
      `nome` text   NOT NULL  , 
      `cor` varchar  (10)   NOT NULL  , 
      `ativo` char  (1)   NOT NULL    DEFAULT 'S', 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE procedimento_preco( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `procedimento_id` int   NOT NULL  , 
      `parceiro_id` int   NOT NULL  , 
      `valor` double   NOT NULL  , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE processo( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `tipo_processo_id` int   NOT NULL  , 
      `numero_cnj_numero` text   NOT NULL  , 
      `numero_outro` text   , 
      `tribunal_id` int   , 
      `foro_id` int   , 
      `comarca_id` int   , 
      `vara_id` int   , 
      `orgao_id` int   , 
      `data_distribuicao_protocolo` date   , 
      `valor_causa` double   , 
      `area_id` int   , 
      `assunto_id` int   , 
      `gratuidade_processual` char  (1)     DEFAULT 'F', 
      `status_processual_id` int   , 
      `responsavel_id` int   , 
      `envolvimento_id` int   , 
      `observacao` text   , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE processo_vinculo( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `processo_principal_id` int   , 
      `processo_incidente_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE publicacao( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `numero_arquivo` text   , 
      `numero_publicacao` text   , 
      `titulo` text   , 
      `texto` text   , 
      `cabecalho` text   , 
      `rodape` text   , 
      `processo_id` int   , 
      `numero_unico_processo` text   , 
      `numero_processo_principal` text   , 
      `jornal_id` int   , 
      `data_tratamento` datetime   , 
      `data_disponibilizacao` date   , 
      `termo_ref_data` text   , 
      `prazo` date   , 
      `confirma_prazo` char  (1)     DEFAULT 'N', 
      `data_entrega` date   , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE publicacao_movimentacao( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `publicacao_id` int   NOT NULL  , 
      `descricao` text   NOT NULL  , 
      `processo_id` int   , 
      `tarefa_id` int   , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE publicacao_profissional( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `publicacao_id` int   NOT NULL  , 
      `profissional_id` int   NOT NULL  , 
      `codigo_relacionamento` text   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE publicacao_sugestao_prazo( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `publicacao_id` int   NOT NULL  , 
      `config_busca_prazo_id` int   NOT NULL  , 
      `resultado_busca` text   NOT NULL  , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE questao( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `formulario_id` int   NOT NULL  , 
      `nome` text   NOT NULL  , 
      `tipo_campo` text   NOT NULL  , 
      `fl_obrigatorio` char   NOT NULL    DEFAULT 'N', 
      `ativo` char  (1)   NOT NULL    DEFAULT 'S', 
      `opcoes` text   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE resposta( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `resposta_formulario_id` int   NOT NULL  , 
      `questao_id` int   NOT NULL  , 
      `resposta` text   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE resposta_formulario( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `formulario_id` int   NOT NULL  , 
      `atendimento_id` int   NOT NULL  , 
      `dt_resposta` date   , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE sexo( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `nome` varchar  (30)   NOT NULL  , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE situacao_profissional( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `nome` varchar  (255)   NOT NULL  , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE status_processual( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `tipo_processo_id` int   NOT NULL  , 
      `nome` varchar  (255)   NOT NULL  , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE system_group( 
      `id` int   NOT NULL  , 
      `name` text   NOT NULL  , 
      `uuid` varchar  (36)   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE system_group_program( 
      `id` int   NOT NULL  , 
      `system_group_id` int   NOT NULL  , 
      `system_program_id` int   NOT NULL  , 
      `actions` text   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE system_preference( 
      `id` varchar  (255)   NOT NULL  , 
      `preference` text   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE system_program( 
      `id` int   NOT NULL  , 
      `name` text   NOT NULL  , 
      `controller` text   NOT NULL  , 
      `actions` text   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE system_unit( 
      `id` int   NOT NULL  , 
      `name` text   NOT NULL  , 
      `connection_name` text   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE system_user_group( 
      `id` int   NOT NULL  , 
      `system_user_id` int   NOT NULL  , 
      `system_group_id` int   NOT NULL  , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE system_user_program( 
      `id` int   NOT NULL  , 
      `system_user_id` int   NOT NULL  , 
      `system_program_id` int   NOT NULL  , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE system_users( 
      `id` int   NOT NULL  , 
      `name` text   NOT NULL  , 
      `login` text   NOT NULL  , 
      `password` text   NOT NULL  , 
      `email` text   , 
      `frontpage_id` int   , 
      `system_unit_id` int   , 
      `active` char  (1)   , 
      `accepted_term_policy_at` text   , 
      `accepted_term_policy` char  (1)   , 
      `two_factor_enabled` char  (1)     DEFAULT 'N', 
      `two_factor_type` varchar  (100)   , 
      `two_factor_secret` varchar  (255)   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE system_user_unit( 
      `id` int   NOT NULL  , 
      `system_user_id` int   NOT NULL  , 
      `system_unit_id` int   NOT NULL  , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE tarefa( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `tarefa_status_id` int   NOT NULL  , 
      `publicacao_id` int   , 
      `processo_id` int   , 
      `usuario_destinatario_id` int   NOT NULL  , 
      `titulo` varchar  (255)   NOT NULL  , 
      `data_disponibilizacao` datetime   , 
      `prazo_validacao` date   , 
      `prazo_entrega` date   NOT NULL  , 
      `observacao` text   , 
      `data_entrega` datetime   , 
      `arquivado` char  (1)     DEFAULT 'N', 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
      `prazo_processual` char  (1)     DEFAULT 'N', 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE tarefa_cliente( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `tarefa_id` int   NOT NULL  , 
      `cliente_id` int   NOT NULL  , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE tarefa_comentario( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `tarefa_id` int   NOT NULL  , 
      `texto` text   NOT NULL  , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE tarefa_configuracao( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `status_inicial_id` int   NOT NULL  , 
      `status_final_id` int   NOT NULL  , 
      `status_cancelado_id` int   NOT NULL  , 
      `tem_dtvalidacao` char  (1)     DEFAULT 'N', 
      `dtvalidacao_obrigatoria` char  (1)     DEFAULT 'N', 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE tarefa_horas_trabalhadas( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `tarefa_id` int   NOT NULL  , 
      `data_inicio` datetime   NOT NULL  , 
      `data_fim` datetime   , 
      `observacao` text   , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE tarefa_movimentacao( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `tarefa_id` int   NOT NULL  , 
      `descricao` text   , 
      `data_movimentacao` datetime   NOT NULL  , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE tarefa_status( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `nome` varchar  (255)   NOT NULL  , 
      `kanban` int   NOT NULL  , 
      `inicio` char  (1)   , 
      `fim` char  (1)   , 
      `cor` varchar  (10)   NOT NULL  , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE tarefa_usuario_master( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `tarefa_configuracao_id` int   NOT NULL  , 
      `usuario_master_id` int   NOT NULL  , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE tarefa_vinculo( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `tarefa_id` int   NOT NULL  , 
      `subtarefa_id` int   NOT NULL  , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE template_acao( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `template_escritorio_id` int   NOT NULL  , 
      `url` text   , 
      `label` text   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE template_escritorio( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `escritorio_id` int   NOT NULL  , 
      `chave` text   NOT NULL  , 
      `descricao` text   NOT NULL  , 
      `habilitado` char  (1)   NOT NULL    DEFAULT 'T', 
      `template` text   , 
      `titulo` text   , 
      `tipo_template` text   , 
      `readonly` char  (1)   NOT NULL    DEFAULT 'F', 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE tipo_andamento( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `nome` varchar  (255)   NOT NULL  , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE tipo_atendimento( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `nome` varchar  (255)   NOT NULL  , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE tipo_compromisso( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `nome` text   NOT NULL  , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE tipo_conta( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `nome` text   NOT NULL  , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE tipo_conta_caixa( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `nome` varchar  (255)   NOT NULL  , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE tipo_doc_financeiro_padrao( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `nome` varchar  (30)   NOT NULL  , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE tipo_documento_financeiro( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `codigo` varchar  (4)   NOT NULL    DEFAULT 'Man', 
      `nome` varchar  (255)   NOT NULL  , 
      `tipo_conta_id` int   NOT NULL  , 
      `gera_codigo` char  (1)   NOT NULL    DEFAULT 'N', 
      `padrao_id` int   NOT NULL  , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE tipo_extrato( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `nome` varchar  (50)   NOT NULL  , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE tipo_modelo_documento( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `nome` varchar  (255)   NOT NULL  , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE tipo_pagamento( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `nome` text   NOT NULL  , 
      `ativo` char  (1)   NOT NULL    DEFAULT 'S', 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE tipo_pessoa( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `nome` varchar  (20)   NOT NULL  , 
      `sigla` char  (2)   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE tipo_prazo( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `nome` varchar  (255)   NOT NULL  , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE tipo_processo( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `nome` varchar  (255)   NOT NULL  , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE tipo_profissional( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `nome` varchar  (255)   NOT NULL  , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE tmp_documento( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `nome` varchar  (255)   NOT NULL  , 
      `filename` text   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE tribunal( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `nome` varchar  (255)   NOT NULL  , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE unidade_indexador( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `nome` varchar  (255)   NOT NULL  , 
      `extenso` text   , 
      `simbolo` varchar  (10)   , 
      `criacao_user_id` int   , 
      `data_criacao` datetime   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE unidade_medida( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `nome` text   NOT NULL  , 
      `sigla` text   NOT NULL  , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE vara( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `nome` varchar  (255)   NOT NULL  , 
      `data_criacao` datetime   , 
      `criacao_user_id` int   , 
      `data_modificacao` datetime   , 
      `modificacao_user_id` int   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE video( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `nome` varchar  (255)   , 
      `url` text   , 
      `tag_iframe` text   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

CREATE TABLE whatsapp_config( 
      `id`  INT  AUTO_INCREMENT    NOT NULL  , 
      `escritorio_id` int   NOT NULL  , 
      `phone` text   , 
      `status` text   , 
      `api_token` text   , 
      `api_key` text   , 
 PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 

 
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
 
