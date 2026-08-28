<?php

class SystemUsers extends TRecord
{
    const TABLENAME  = 'system_users';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'max'; // {max, serial}

    private SystemUnit $system_unit;
    private SystemProgram $frontpage;

    private $unit;
    private $system_user_groups = array();
    private $system_user_programs = array();
    private $system_user_units = array();
                    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('name');
        parent::addAttribute('login');
        parent::addAttribute('password');
        parent::addAttribute('email');
        parent::addAttribute('frontpage_id');
        parent::addAttribute('system_unit_id');
        parent::addAttribute('active');
        parent::addAttribute('accepted_term_policy_at');
        parent::addAttribute('accepted_term_policy');
        parent::addAttribute('two_factor_enabled');
        parent::addAttribute('two_factor_type');
        parent::addAttribute('two_factor_secret');
    
    }

    /**
     * Method set_system_unit
     * Sample of usage: $var->system_unit = $object;
     * @param $object Instance of SystemUnit
     */
    public function set_system_unit(SystemUnit $object)
    {
        $this->system_unit = $object;
        $this->system_unit_id = $object->id;
    }

    /**
     * Method get_system_unit
     * Sample of usage: $var->system_unit->attribute;
     * @returns SystemUnit instance
     */
    public function get_system_unit()
    {
    
        // loads the associated object
        if (empty($this->system_unit))
            $this->system_unit = new SystemUnit($this->system_unit_id);
    
        // returns the associated object
        return $this->system_unit;
    }
    /**
     * Method set_system_program
     * Sample of usage: $var->system_program = $object;
     * @param $object Instance of SystemProgram
     */
    public function set_frontpage(SystemProgram $object)
    {
        $this->frontpage = $object;
        $this->frontpage_id = $object->id;
    }

    /**
     * Method get_frontpage
     * Sample of usage: $var->frontpage->attribute;
     * @returns SystemProgram instance
     */
    public function get_frontpage()
    {
    
        // loads the associated object
        if (empty($this->frontpage))
            $this->frontpage = new SystemProgram($this->frontpage_id);
    
        // returns the associated object
        return $this->frontpage;
    }

    /**
     * Method getAtendimentoHistoricos
     */
    public function getAtendimentoHistoricosByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return AtendimentoHistorico::getObjects( $criteria );
    }
    /**
     * Method getAtendimentoHistoricos
     */
    public function getAtendimentoHistoricosByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return AtendimentoHistorico::getObjects( $criteria );
    }
    /**
     * Method getBancos
     */
    public function getBancosByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return Banco::getObjects( $criteria );
    }
    /**
     * Method getBancos
     */
    public function getBancosByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return Banco::getObjects( $criteria );
    }
    /**
     * Method getAgendas
     */
    public function getAgendasByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return Agenda::getObjects( $criteria );
    }
    /**
     * Method getAgendas
     */
    public function getAgendasByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return Agenda::getObjects( $criteria );
    }
    /**
     * Method getAndamentos
     */
    public function getAndamentosByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return Andamento::getObjects( $criteria );
    }
    /**
     * Method getAndamentos
     */
    public function getAndamentosByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return Andamento::getObjects( $criteria );
    }
    /**
     * Method getAnexos
     */
    public function getAnexosByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return Anexo::getObjects( $criteria );
    }
    /**
     * Method getAnexos
     */
    public function getAnexosByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return Anexo::getObjects( $criteria );
    }
    /**
     * Method getAreas
     */
    public function getAreasByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return Area::getObjects( $criteria );
    }
    /**
     * Method getAreas
     */
    public function getAreasByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return Area::getObjects( $criteria );
    }
    /**
     * Method getAssuntos
     */
    public function getAssuntos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return Assunto::getObjects( $criteria );
    }
    /**
     * Method getAtendimentos
     */
    public function getAtendimentosByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return Atendimento::getObjects( $criteria );
    }
    /**
     * Method getAtendimentos
     */
    public function getAtendimentosByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return Atendimento::getObjects( $criteria );
    }
    /**
     * Method getBloqueios
     */
    public function getBloqueiosByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return Bloqueio::getObjects( $criteria );
    }
    /**
     * Method getBloqueios
     */
    public function getBloqueiosByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return Bloqueio::getObjects( $criteria );
    }
    /**
     * Method getCategoriaContas
     */
    public function getCategoriaContasByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return CategoriaConta::getObjects( $criteria );
    }
    /**
     * Method getCategoriaContas
     */
    public function getCategoriaContasByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return CategoriaConta::getObjects( $criteria );
    }
    /**
     * Method getCidades
     */
    public function getCidadesByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return Cidade::getObjects( $criteria );
    }
    /**
     * Method getCidades
     */
    public function getCidadesByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return Cidade::getObjects( $criteria );
    }
    /**
     * Method getClassificacoess
     */
    public function getClassificacoessByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return Classificacoes::getObjects( $criteria );
    }
    /**
     * Method getClassificacoess
     */
    public function getClassificacoessByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return Classificacoes::getObjects( $criteria );
    }
    /**
     * Method getClassificacoesContraparteDadoss
     */
    public function getClassificacoesContraparteDadossByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return ClassificacoesContraparteDados::getObjects( $criteria );
    }
    /**
     * Method getClassificacoesContraparteDadoss
     */
    public function getClassificacoesContraparteDadossByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return ClassificacoesContraparteDados::getObjects( $criteria );
    }
    /**
     * Method getComarcas
     */
    public function getComarcasByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return Comarca::getObjects( $criteria );
    }
    /**
     * Method getComarcas
     */
    public function getComarcasByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return Comarca::getObjects( $criteria );
    }
    /**
     * Method getCompromissos
     */
    public function getCompromissosByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return Compromisso::getObjects( $criteria );
    }
    /**
     * Method getCompromissos
     */
    public function getCompromissosByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return Compromisso::getObjects( $criteria );
    }
    /**
     * Method getConfigBuscaAPartirs
     */
    public function getConfigBuscaAPartirsByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return ConfigBuscaAPartir::getObjects( $criteria );
    }
    /**
     * Method getConfigBuscaAPartirs
     */
    public function getConfigBuscaAPartirsByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return ConfigBuscaAPartir::getObjects( $criteria );
    }
    /**
     * Method getConfigBuscaPrazos
     */
    public function getConfigBuscaPrazosByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return ConfigBuscaPrazo::getObjects( $criteria );
    }
    /**
     * Method getConfigBuscaPrazos
     */
    public function getConfigBuscaPrazosByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return ConfigBuscaPrazo::getObjects( $criteria );
    }
    /**
     * Method getContas
     */
    public function getContasByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return Conta::getObjects( $criteria );
    }
    /**
     * Method getContas
     */
    public function getContasByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return Conta::getObjects( $criteria );
    }
    /**
     * Method getContaCaixas
     */
    public function getContaCaixasByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return ContaCaixa::getObjects( $criteria );
    }
    /**
     * Method getContaCaixas
     */
    public function getContaCaixasByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return ContaCaixa::getObjects( $criteria );
    }
    /**
     * Method getContrapartes
     */
    public function getContrapartesByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return Contraparte::getObjects( $criteria );
    }
    /**
     * Method getContrapartes
     */
    public function getContrapartesByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return Contraparte::getObjects( $criteria );
    }
    /**
     * Method getContratos
     */
    public function getContratosByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return Contrato::getObjects( $criteria );
    }
    /**
     * Method getContratos
     */
    public function getContratosByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return Contrato::getObjects( $criteria );
    }
    /**
     * Method getContratoDocumentos
     */
    public function getContratoDocumentosByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return ContratoDocumento::getObjects( $criteria );
    }
    /**
     * Method getContratoDocumentos
     */
    public function getContratoDocumentosByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return ContratoDocumento::getObjects( $criteria );
    }
    /**
     * Method getContratoPagamentoEventos
     */
    public function getContratoPagamentoEventosByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return ContratoPagamentoEvento::getObjects( $criteria );
    }
    /**
     * Method getContratoPagamentoEventos
     */
    public function getContratoPagamentoEventosByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return ContratoPagamentoEvento::getObjects( $criteria );
    }
    /**
     * Method getContratoPagamentoIndexadors
     */
    public function getContratoPagamentoIndexadorsByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return ContratoPagamentoIndexador::getObjects( $criteria );
    }
    /**
     * Method getContratoPagamentoIndexadors
     */
    public function getContratoPagamentoIndexadorsByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return ContratoPagamentoIndexador::getObjects( $criteria );
    }
    /**
     * Method getContratoPagamentoOpcaos
     */
    public function getContratoPagamentoOpcaosByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return ContratoPagamentoOpcao::getObjects( $criteria );
    }
    /**
     * Method getContratoPagamentoOpcaos
     */
    public function getContratoPagamentoOpcaosByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return ContratoPagamentoOpcao::getObjects( $criteria );
    }
    /**
     * Method getContratoPagamentoParcelas
     */
    public function getContratoPagamentoParcelasByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return ContratoPagamentoParcela::getObjects( $criteria );
    }
    /**
     * Method getContratoPagamentoParcelas
     */
    public function getContratoPagamentoParcelasByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return ContratoPagamentoParcela::getObjects( $criteria );
    }
    /**
     * Method getContratoProcessos
     */
    public function getContratoProcessosByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return ContratoProcesso::getObjects( $criteria );
    }
    /**
     * Method getContratoProcessos
     */
    public function getContratoProcessosByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return ContratoProcesso::getObjects( $criteria );
    }
    /**
     * Method getConvidados
     */
    public function getConvidadosByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return Convidado::getObjects( $criteria );
    }
    /**
     * Method getConvidados
     */
    public function getConvidadosByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return Convidado::getObjects( $criteria );
    }
    /**
     * Method getConvidadoCompromissos
     */
    public function getConvidadoCompromissosByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return ConvidadoCompromisso::getObjects( $criteria );
    }
    /**
     * Method getConvidadoCompromissos
     */
    public function getConvidadoCompromissosByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return ConvidadoCompromisso::getObjects( $criteria );
    }
    /**
     * Method getDocumentos
     */
    public function getDocumentosByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return Documento::getObjects( $criteria );
    }
    /**
     * Method getDocumentos
     */
    public function getDocumentosByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return Documento::getObjects( $criteria );
    }
    /**
     * Method getEnvolvimentos
     */
    public function getEnvolvimentosByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return Envolvimento::getObjects( $criteria );
    }
    /**
     * Method getEnvolvimentos
     */
    public function getEnvolvimentosByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return Envolvimento::getObjects( $criteria );
    }
    /**
     * Method getEscritorios
     */
    public function getEscritoriosByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return Escritorio::getObjects( $criteria );
    }
    /**
     * Method getEscritorios
     */
    public function getEscritoriosByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return Escritorio::getObjects( $criteria );
    }
    /**
     * Method getEspecialidades
     */
    public function getEspecialidadesByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return Especialidade::getObjects( $criteria );
    }
    /**
     * Method getEspecialidades
     */
    public function getEspecialidadesByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return Especialidade::getObjects( $criteria );
    }
    /**
     * Method getEstados
     */
    public function getEstadosByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return Estado::getObjects( $criteria );
    }
    /**
     * Method getEstados
     */
    public function getEstadosByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return Estado::getObjects( $criteria );
    }
    /**
     * Method getEstadoAgendas
     */
    public function getEstadoAgendas()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return EstadoAgenda::getObjects( $criteria );
    }
    /**
     * Method getEstadoAgendamentos
     */
    public function getEstadoAgendamentos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('system_users_id', '=', $this->id));
        return EstadoAgendamento::getObjects( $criteria );
    }
    /**
     * Method getExtratos
     */
    public function getExtratosByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return Extrato::getObjects( $criteria );
    }
    /**
     * Method getExtratos
     */
    public function getExtratosByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return Extrato::getObjects( $criteria );
    }
    /**
     * Method getFormularios
     */
    public function getFormulariosByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return Formulario::getObjects( $criteria );
    }
    /**
     * Method getFormularios
     */
    public function getFormulariosByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return Formulario::getObjects( $criteria );
    }
    /**
     * Method getForos
     */
    public function getForosByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return Foro::getObjects( $criteria );
    }
    /**
     * Method getForos
     */
    public function getForosByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return Foro::getObjects( $criteria );
    }
    /**
     * Method getGrupos
     */
    public function getGruposByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return Grupo::getObjects( $criteria );
    }
    /**
     * Method getGrupos
     */
    public function getGruposByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return Grupo::getObjects( $criteria );
    }
    /**
     * Method getJornals
     */
    public function getJornalsByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return Jornal::getObjects( $criteria );
    }
    /**
     * Method getJornals
     */
    public function getJornalsByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return Jornal::getObjects( $criteria );
    }
    /**
     * Method getMensagems
     */
    public function getMensagems()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('system_user_id', '=', $this->id));
        return Mensagem::getObjects( $criteria );
    }
    /**
     * Method getModeloDocumentos
     */
    public function getModeloDocumentosByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return ModeloDocumento::getObjects( $criteria );
    }
    /**
     * Method getModeloDocumentos
     */
    public function getModeloDocumentosByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return ModeloDocumento::getObjects( $criteria );
    }
    /**
     * Method getMovimentacaos
     */
    public function getMovimentacaos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('system_user_id', '=', $this->id));
        return Movimentacao::getObjects( $criteria );
    }
    /**
     * Method getOrgaos
     */
    public function getOrgaosByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return Orgao::getObjects( $criteria );
    }
    /**
     * Method getOrgaos
     */
    public function getOrgaosByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return Orgao::getObjects( $criteria );
    }
    /**
     * Method getPadraoAtendimentoDocumentos
     */
    public function getPadraoAtendimentoDocumentosByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return PadraoAtendimentoDocumento::getObjects( $criteria );
    }
    /**
     * Method getPadraoAtendimentoDocumentos
     */
    public function getPadraoAtendimentoDocumentosByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return PadraoAtendimentoDocumento::getObjects( $criteria );
    }
    /**
     * Method getParceiros
     */
    public function getParceirosByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return Parceiro::getObjects( $criteria );
    }
    /**
     * Method getParceiros
     */
    public function getParceirosByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return Parceiro::getObjects( $criteria );
    }
    /**
     * Method getPessoas
     */
    public function getPessoasBySystemUserss()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('system_users_id', '=', $this->id));
        return Pessoa::getObjects( $criteria );
    }
    /**
     * Method getPessoas
     */
    public function getPessoasByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return Pessoa::getObjects( $criteria );
    }
    /**
     * Method getPessoas
     */
    public function getPessoasByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return Pessoa::getObjects( $criteria );
    }
    /**
     * Method getPreferenciaSistemas
     */
    public function getPreferenciaSistemas()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('system_users_id', '=', $this->id));
        return PreferenciaSistema::getObjects( $criteria );
    }
    /**
     * Method getProcedimentos
     */
    public function getProcedimentosByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return Procedimento::getObjects( $criteria );
    }
    /**
     * Method getProcedimentos
     */
    public function getProcedimentosByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return Procedimento::getObjects( $criteria );
    }
    /**
     * Method getProcessos
     */
    public function getProcessosByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return Processo::getObjects( $criteria );
    }
    /**
     * Method getProcessos
     */
    public function getProcessosByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return Processo::getObjects( $criteria );
    }
    /**
     * Method getPublicacaos
     */
    public function getPublicacaosByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return Publicacao::getObjects( $criteria );
    }
    /**
     * Method getPublicacaos
     */
    public function getPublicacaosByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return Publicacao::getObjects( $criteria );
    }
    /**
     * Method getPublicacaoMovimentacaos
     */
    public function getPublicacaoMovimentacaos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return PublicacaoMovimentacao::getObjects( $criteria );
    }
    /**
     * Method getPublicacaoSugestaoPrazos
     */
    public function getPublicacaoSugestaoPrazosByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return PublicacaoSugestaoPrazo::getObjects( $criteria );
    }
    /**
     * Method getPublicacaoSugestaoPrazos
     */
    public function getPublicacaoSugestaoPrazosByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return PublicacaoSugestaoPrazo::getObjects( $criteria );
    }
    /**
     * Method getRespostaFormularios
     */
    public function getRespostaFormulariosByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return RespostaFormulario::getObjects( $criteria );
    }
    /**
     * Method getRespostaFormularios
     */
    public function getRespostaFormulariosByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return RespostaFormulario::getObjects( $criteria );
    }
    /**
     * Method getStatusProcessuals
     */
    public function getStatusProcessualsByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return StatusProcessual::getObjects( $criteria );
    }
    /**
     * Method getStatusProcessuals
     */
    public function getStatusProcessualsByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return StatusProcessual::getObjects( $criteria );
    }
    /**
     * Method getTarefas
     */
    public function getTarefasByUsuarioDestinatarios()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('usuario_destinatario_id', '=', $this->id));
        return Tarefa::getObjects( $criteria );
    }
    /**
     * Method getTarefas
     */
    public function getTarefasByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return Tarefa::getObjects( $criteria );
    }
    /**
     * Method getTarefas
     */
    public function getTarefasByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return Tarefa::getObjects( $criteria );
    }
    /**
     * Method getTarefaComentarios
     */
    public function getTarefaComentariosByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return TarefaComentario::getObjects( $criteria );
    }
    /**
     * Method getTarefaComentarios
     */
    public function getTarefaComentariosByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return TarefaComentario::getObjects( $criteria );
    }
    /**
     * Method getTarefaConfiguracaos
     */
    public function getTarefaConfiguracaos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return TarefaConfiguracao::getObjects( $criteria );
    }
    /**
     * Method getTarefaHorasTrabalhadass
     */
    public function getTarefaHorasTrabalhadass()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return TarefaHorasTrabalhadas::getObjects( $criteria );
    }
    /**
     * Method getTarefaMovimentacaos
     */
    public function getTarefaMovimentacaosByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return TarefaMovimentacao::getObjects( $criteria );
    }
    /**
     * Method getTarefaMovimentacaos
     */
    public function getTarefaMovimentacaosByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return TarefaMovimentacao::getObjects( $criteria );
    }
    /**
     * Method getTarefaStatuss
     */
    public function getTarefaStatussByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return TarefaStatus::getObjects( $criteria );
    }
    /**
     * Method getTarefaStatuss
     */
    public function getTarefaStatussByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return TarefaStatus::getObjects( $criteria );
    }
    /**
     * Method getTarefaUsuarioMasters
     */
    public function getTarefaUsuarioMasters()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('usuario_master_id', '=', $this->id));
        return TarefaUsuarioMaster::getObjects( $criteria );
    }
    /**
     * Method getTarefaVinculos
     */
    public function getTarefaVinculosByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return TarefaVinculo::getObjects( $criteria );
    }
    /**
     * Method getTarefaVinculos
     */
    public function getTarefaVinculosByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return TarefaVinculo::getObjects( $criteria );
    }
    /**
     * Method getTemplateEscritorios
     */
    public function getTemplateEscritoriosByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return TemplateEscritorio::getObjects( $criteria );
    }
    /**
     * Method getTemplateEscritorios
     */
    public function getTemplateEscritoriosByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return TemplateEscritorio::getObjects( $criteria );
    }
    /**
     * Method getTipoAndamentos
     */
    public function getTipoAndamentosByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return TipoAndamento::getObjects( $criteria );
    }
    /**
     * Method getTipoAndamentos
     */
    public function getTipoAndamentosByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return TipoAndamento::getObjects( $criteria );
    }
    /**
     * Method getTipoCompromissos
     */
    public function getTipoCompromissosByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return TipoCompromisso::getObjects( $criteria );
    }
    /**
     * Method getTipoCompromissos
     */
    public function getTipoCompromissosByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return TipoCompromisso::getObjects( $criteria );
    }
    /**
     * Method getTipoContas
     */
    public function getTipoContasByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return TipoConta::getObjects( $criteria );
    }
    /**
     * Method getTipoContas
     */
    public function getTipoContasByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return TipoConta::getObjects( $criteria );
    }
    /**
     * Method getTipoDocumentoFinanceiros
     */
    public function getTipoDocumentoFinanceirosByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return TipoDocumentoFinanceiro::getObjects( $criteria );
    }
    /**
     * Method getTipoDocumentoFinanceiros
     */
    public function getTipoDocumentoFinanceirosByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return TipoDocumentoFinanceiro::getObjects( $criteria );
    }
    /**
     * Method getTipoModeloDocumentos
     */
    public function getTipoModeloDocumentosByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return TipoModeloDocumento::getObjects( $criteria );
    }
    /**
     * Method getTipoModeloDocumentos
     */
    public function getTipoModeloDocumentosByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return TipoModeloDocumento::getObjects( $criteria );
    }
    /**
     * Method getTipoPagamentos
     */
    public function getTipoPagamentosByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return TipoPagamento::getObjects( $criteria );
    }
    /**
     * Method getTipoPagamentos
     */
    public function getTipoPagamentosByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return TipoPagamento::getObjects( $criteria );
    }
    /**
     * Method getTipoPrazos
     */
    public function getTipoPrazosByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return TipoPrazo::getObjects( $criteria );
    }
    /**
     * Method getTipoPrazos
     */
    public function getTipoPrazosByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return TipoPrazo::getObjects( $criteria );
    }
    /**
     * Method getTipoProfissionals
     */
    public function getTipoProfissionalsByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return TipoProfissional::getObjects( $criteria );
    }
    /**
     * Method getTipoProfissionals
     */
    public function getTipoProfissionalsByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return TipoProfissional::getObjects( $criteria );
    }
    /**
     * Method getTribunals
     */
    public function getTribunalsByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return Tribunal::getObjects( $criteria );
    }
    /**
     * Method getTribunals
     */
    public function getTribunalsByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return Tribunal::getObjects( $criteria );
    }
    /**
     * Method getUnidadeIndexadors
     */
    public function getUnidadeIndexadorsByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return UnidadeIndexador::getObjects( $criteria );
    }
    /**
     * Method getUnidadeIndexadors
     */
    public function getUnidadeIndexadorsByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return UnidadeIndexador::getObjects( $criteria );
    }
    /**
     * Method getVaras
     */
    public function getVarasByCriacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('criacao_user_id', '=', $this->id));
        return Vara::getObjects( $criteria );
    }
    /**
     * Method getVaras
     */
    public function getVarasByModificacaoUsers()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modificacao_user_id', '=', $this->id));
        return Vara::getObjects( $criteria );
    }

    public function set_atendimento_historico_atendimento_to_string($atendimento_historico_atendimento_to_string)
    {
        if(is_array($atendimento_historico_atendimento_to_string))
        {
            $values = Atendimento::where('id', 'in', $atendimento_historico_atendimento_to_string)->getIndexedArray('id', 'id');
            $this->atendimento_historico_atendimento_to_string = implode(', ', $values);
        }
        else
        {
            $this->atendimento_historico_atendimento_to_string = $atendimento_historico_atendimento_to_string;
        }

        $this->vdata['atendimento_historico_atendimento_to_string'] = $this->atendimento_historico_atendimento_to_string;
    }

    public function get_atendimento_historico_atendimento_to_string()
    {
        if(!empty($this->atendimento_historico_atendimento_to_string))
        {
            return $this->atendimento_historico_atendimento_to_string;
        }
    
        $values = AtendimentoHistorico::where('modificacao_user_id', '=', $this->id)->getIndexedArray('atendimento_id','{atendimento->id}');
        return implode(', ', $values);
    }

    public function set_atendimento_historico_criacao_user_to_string($atendimento_historico_criacao_user_to_string)
    {
        if(is_array($atendimento_historico_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $atendimento_historico_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->atendimento_historico_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->atendimento_historico_criacao_user_to_string = $atendimento_historico_criacao_user_to_string;
        }

        $this->vdata['atendimento_historico_criacao_user_to_string'] = $this->atendimento_historico_criacao_user_to_string;
    }

    public function get_atendimento_historico_criacao_user_to_string()
    {
        if(!empty($this->atendimento_historico_criacao_user_to_string))
        {
            return $this->atendimento_historico_criacao_user_to_string;
        }
    
        $values = AtendimentoHistorico::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_atendimento_historico_modificacao_user_to_string($atendimento_historico_modificacao_user_to_string)
    {
        if(is_array($atendimento_historico_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $atendimento_historico_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->atendimento_historico_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->atendimento_historico_modificacao_user_to_string = $atendimento_historico_modificacao_user_to_string;
        }

        $this->vdata['atendimento_historico_modificacao_user_to_string'] = $this->atendimento_historico_modificacao_user_to_string;
    }

    public function get_atendimento_historico_modificacao_user_to_string()
    {
        if(!empty($this->atendimento_historico_modificacao_user_to_string))
        {
            return $this->atendimento_historico_modificacao_user_to_string;
        }
    
        $values = AtendimentoHistorico::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_banco_criacao_user_to_string($banco_criacao_user_to_string)
    {
        if(is_array($banco_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $banco_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->banco_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->banco_criacao_user_to_string = $banco_criacao_user_to_string;
        }

        $this->vdata['banco_criacao_user_to_string'] = $this->banco_criacao_user_to_string;
    }

    public function get_banco_criacao_user_to_string()
    {
        if(!empty($this->banco_criacao_user_to_string))
        {
            return $this->banco_criacao_user_to_string;
        }
    
        $values = Banco::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_banco_modificacao_user_to_string($banco_modificacao_user_to_string)
    {
        if(is_array($banco_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $banco_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->banco_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->banco_modificacao_user_to_string = $banco_modificacao_user_to_string;
        }

        $this->vdata['banco_modificacao_user_to_string'] = $this->banco_modificacao_user_to_string;
    }

    public function get_banco_modificacao_user_to_string()
    {
        if(!empty($this->banco_modificacao_user_to_string))
        {
            return $this->banco_modificacao_user_to_string;
        }
    
        $values = Banco::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_agenda_escritorio_to_string($agenda_escritorio_to_string)
    {
        if(is_array($agenda_escritorio_to_string))
        {
            $values = Escritorio::where('id', 'in', $agenda_escritorio_to_string)->getIndexedArray('nome', 'nome');
            $this->agenda_escritorio_to_string = implode(', ', $values);
        }
        else
        {
            $this->agenda_escritorio_to_string = $agenda_escritorio_to_string;
        }

        $this->vdata['agenda_escritorio_to_string'] = $this->agenda_escritorio_to_string;
    }

    public function get_agenda_escritorio_to_string()
    {
        if(!empty($this->agenda_escritorio_to_string))
        {
            return $this->agenda_escritorio_to_string;
        }
    
        $values = Agenda::where('modificacao_user_id', '=', $this->id)->getIndexedArray('escritorio_id','{escritorio->nome}');
        return implode(', ', $values);
    }

    public function set_agenda_profissional_to_string($agenda_profissional_to_string)
    {
        if(is_array($agenda_profissional_to_string))
        {
            $values = Pessoa::where('id', 'in', $agenda_profissional_to_string)->getIndexedArray('nome', 'nome');
            $this->agenda_profissional_to_string = implode(', ', $values);
        }
        else
        {
            $this->agenda_profissional_to_string = $agenda_profissional_to_string;
        }

        $this->vdata['agenda_profissional_to_string'] = $this->agenda_profissional_to_string;
    }

    public function get_agenda_profissional_to_string()
    {
        if(!empty($this->agenda_profissional_to_string))
        {
            return $this->agenda_profissional_to_string;
        }
    
        $values = Agenda::where('modificacao_user_id', '=', $this->id)->getIndexedArray('profissional_id','{profissional->nome}');
        return implode(', ', $values);
    }

    public function set_agenda_procedimento_to_string($agenda_procedimento_to_string)
    {
        if(is_array($agenda_procedimento_to_string))
        {
            $values = Procedimento::where('id', 'in', $agenda_procedimento_to_string)->getIndexedArray('nome', 'nome');
            $this->agenda_procedimento_to_string = implode(', ', $values);
        }
        else
        {
            $this->agenda_procedimento_to_string = $agenda_procedimento_to_string;
        }

        $this->vdata['agenda_procedimento_to_string'] = $this->agenda_procedimento_to_string;
    }

    public function get_agenda_procedimento_to_string()
    {
        if(!empty($this->agenda_procedimento_to_string))
        {
            return $this->agenda_procedimento_to_string;
        }
    
        $values = Agenda::where('modificacao_user_id', '=', $this->id)->getIndexedArray('procedimento_id','{procedimento->nome}');
        return implode(', ', $values);
    }

    public function set_agenda_criacao_user_to_string($agenda_criacao_user_to_string)
    {
        if(is_array($agenda_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $agenda_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->agenda_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->agenda_criacao_user_to_string = $agenda_criacao_user_to_string;
        }

        $this->vdata['agenda_criacao_user_to_string'] = $this->agenda_criacao_user_to_string;
    }

    public function get_agenda_criacao_user_to_string()
    {
        if(!empty($this->agenda_criacao_user_to_string))
        {
            return $this->agenda_criacao_user_to_string;
        }
    
        $values = Agenda::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_agenda_modificacao_user_to_string($agenda_modificacao_user_to_string)
    {
        if(is_array($agenda_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $agenda_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->agenda_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->agenda_modificacao_user_to_string = $agenda_modificacao_user_to_string;
        }

        $this->vdata['agenda_modificacao_user_to_string'] = $this->agenda_modificacao_user_to_string;
    }

    public function get_agenda_modificacao_user_to_string()
    {
        if(!empty($this->agenda_modificacao_user_to_string))
        {
            return $this->agenda_modificacao_user_to_string;
        }
    
        $values = Agenda::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_andamento_processo_to_string($andamento_processo_to_string)
    {
        if(is_array($andamento_processo_to_string))
        {
            $values = Processo::where('id', 'in', $andamento_processo_to_string)->getIndexedArray('numero_cnj_numero', 'numero_cnj_numero');
            $this->andamento_processo_to_string = implode(', ', $values);
        }
        else
        {
            $this->andamento_processo_to_string = $andamento_processo_to_string;
        }

        $this->vdata['andamento_processo_to_string'] = $this->andamento_processo_to_string;
    }

    public function get_andamento_processo_to_string()
    {
        if(!empty($this->andamento_processo_to_string))
        {
            return $this->andamento_processo_to_string;
        }
    
        $values = Andamento::where('modificacao_user_id', '=', $this->id)->getIndexedArray('processo_id','{processo->numero_cnj_numero}');
        return implode(', ', $values);
    }

    public function set_andamento_tipo_andamento_to_string($andamento_tipo_andamento_to_string)
    {
        if(is_array($andamento_tipo_andamento_to_string))
        {
            $values = TipoAndamento::where('id', 'in', $andamento_tipo_andamento_to_string)->getIndexedArray('nome', 'nome');
            $this->andamento_tipo_andamento_to_string = implode(', ', $values);
        }
        else
        {
            $this->andamento_tipo_andamento_to_string = $andamento_tipo_andamento_to_string;
        }

        $this->vdata['andamento_tipo_andamento_to_string'] = $this->andamento_tipo_andamento_to_string;
    }

    public function get_andamento_tipo_andamento_to_string()
    {
        if(!empty($this->andamento_tipo_andamento_to_string))
        {
            return $this->andamento_tipo_andamento_to_string;
        }
    
        $values = Andamento::where('modificacao_user_id', '=', $this->id)->getIndexedArray('tipo_andamento_id','{tipo_andamento->nome}');
        return implode(', ', $values);
    }

    public function set_andamento_criacao_user_to_string($andamento_criacao_user_to_string)
    {
        if(is_array($andamento_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $andamento_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->andamento_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->andamento_criacao_user_to_string = $andamento_criacao_user_to_string;
        }

        $this->vdata['andamento_criacao_user_to_string'] = $this->andamento_criacao_user_to_string;
    }

    public function get_andamento_criacao_user_to_string()
    {
        if(!empty($this->andamento_criacao_user_to_string))
        {
            return $this->andamento_criacao_user_to_string;
        }
    
        $values = Andamento::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_andamento_modificacao_user_to_string($andamento_modificacao_user_to_string)
    {
        if(is_array($andamento_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $andamento_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->andamento_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->andamento_modificacao_user_to_string = $andamento_modificacao_user_to_string;
        }

        $this->vdata['andamento_modificacao_user_to_string'] = $this->andamento_modificacao_user_to_string;
    }

    public function get_andamento_modificacao_user_to_string()
    {
        if(!empty($this->andamento_modificacao_user_to_string))
        {
            return $this->andamento_modificacao_user_to_string;
        }
    
        $values = Andamento::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_andamento_publicacao_etapa_to_string($andamento_publicacao_etapa_to_string)
    {
        if(is_array($andamento_publicacao_etapa_to_string))
        {
            $values = PublicacaoEtapa::where('id', 'in', $andamento_publicacao_etapa_to_string)->getIndexedArray('id', 'id');
            $this->andamento_publicacao_etapa_to_string = implode(', ', $values);
        }
        else
        {
            $this->andamento_publicacao_etapa_to_string = $andamento_publicacao_etapa_to_string;
        }

        $this->vdata['andamento_publicacao_etapa_to_string'] = $this->andamento_publicacao_etapa_to_string;
    }

    public function get_andamento_publicacao_etapa_to_string()
    {
        if(!empty($this->andamento_publicacao_etapa_to_string))
        {
            return $this->andamento_publicacao_etapa_to_string;
        }
    
        $values = Andamento::where('modificacao_user_id', '=', $this->id)->getIndexedArray('publicacao_etapa_id','{publicacao_etapa->id}');
        return implode(', ', $values);
    }

    public function set_anexo_atendimento_to_string($anexo_atendimento_to_string)
    {
        if(is_array($anexo_atendimento_to_string))
        {
            $values = Atendimento::where('id', 'in', $anexo_atendimento_to_string)->getIndexedArray('id', 'id');
            $this->anexo_atendimento_to_string = implode(', ', $values);
        }
        else
        {
            $this->anexo_atendimento_to_string = $anexo_atendimento_to_string;
        }

        $this->vdata['anexo_atendimento_to_string'] = $this->anexo_atendimento_to_string;
    }

    public function get_anexo_atendimento_to_string()
    {
        if(!empty($this->anexo_atendimento_to_string))
        {
            return $this->anexo_atendimento_to_string;
        }
    
        $values = Anexo::where('modificacao_user_id', '=', $this->id)->getIndexedArray('atendimento_id','{atendimento->id}');
        return implode(', ', $values);
    }

    public function set_anexo_criacao_user_to_string($anexo_criacao_user_to_string)
    {
        if(is_array($anexo_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $anexo_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->anexo_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->anexo_criacao_user_to_string = $anexo_criacao_user_to_string;
        }

        $this->vdata['anexo_criacao_user_to_string'] = $this->anexo_criacao_user_to_string;
    }

    public function get_anexo_criacao_user_to_string()
    {
        if(!empty($this->anexo_criacao_user_to_string))
        {
            return $this->anexo_criacao_user_to_string;
        }
    
        $values = Anexo::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_anexo_modificacao_user_to_string($anexo_modificacao_user_to_string)
    {
        if(is_array($anexo_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $anexo_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->anexo_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->anexo_modificacao_user_to_string = $anexo_modificacao_user_to_string;
        }

        $this->vdata['anexo_modificacao_user_to_string'] = $this->anexo_modificacao_user_to_string;
    }

    public function get_anexo_modificacao_user_to_string()
    {
        if(!empty($this->anexo_modificacao_user_to_string))
        {
            return $this->anexo_modificacao_user_to_string;
        }
    
        $values = Anexo::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_area_criacao_user_to_string($area_criacao_user_to_string)
    {
        if(is_array($area_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $area_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->area_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->area_criacao_user_to_string = $area_criacao_user_to_string;
        }

        $this->vdata['area_criacao_user_to_string'] = $this->area_criacao_user_to_string;
    }

    public function get_area_criacao_user_to_string()
    {
        if(!empty($this->area_criacao_user_to_string))
        {
            return $this->area_criacao_user_to_string;
        }
    
        $values = Area::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_area_modificacao_user_to_string($area_modificacao_user_to_string)
    {
        if(is_array($area_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $area_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->area_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->area_modificacao_user_to_string = $area_modificacao_user_to_string;
        }

        $this->vdata['area_modificacao_user_to_string'] = $this->area_modificacao_user_to_string;
    }

    public function get_area_modificacao_user_to_string()
    {
        if(!empty($this->area_modificacao_user_to_string))
        {
            return $this->area_modificacao_user_to_string;
        }
    
        $values = Area::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_assunto_area_to_string($assunto_area_to_string)
    {
        if(is_array($assunto_area_to_string))
        {
            $values = Area::where('id', 'in', $assunto_area_to_string)->getIndexedArray('nome', 'nome');
            $this->assunto_area_to_string = implode(', ', $values);
        }
        else
        {
            $this->assunto_area_to_string = $assunto_area_to_string;
        }

        $this->vdata['assunto_area_to_string'] = $this->assunto_area_to_string;
    }

    public function get_assunto_area_to_string()
    {
        if(!empty($this->assunto_area_to_string))
        {
            return $this->assunto_area_to_string;
        }
    
        $values = Assunto::where('criacao_user_id', '=', $this->id)->getIndexedArray('area_id','{area->nome}');
        return implode(', ', $values);
    }

    public function set_assunto_criacao_user_to_string($assunto_criacao_user_to_string)
    {
        if(is_array($assunto_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $assunto_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->assunto_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->assunto_criacao_user_to_string = $assunto_criacao_user_to_string;
        }

        $this->vdata['assunto_criacao_user_to_string'] = $this->assunto_criacao_user_to_string;
    }

    public function get_assunto_criacao_user_to_string()
    {
        if(!empty($this->assunto_criacao_user_to_string))
        {
            return $this->assunto_criacao_user_to_string;
        }
    
        $values = Assunto::where('criacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_atendimento_agendamento_to_string($atendimento_agendamento_to_string)
    {
        if(is_array($atendimento_agendamento_to_string))
        {
            $values = Agendamento::where('id', 'in', $atendimento_agendamento_to_string)->getIndexedArray('id', 'id');
            $this->atendimento_agendamento_to_string = implode(', ', $values);
        }
        else
        {
            $this->atendimento_agendamento_to_string = $atendimento_agendamento_to_string;
        }

        $this->vdata['atendimento_agendamento_to_string'] = $this->atendimento_agendamento_to_string;
    }

    public function get_atendimento_agendamento_to_string()
    {
        if(!empty($this->atendimento_agendamento_to_string))
        {
            return $this->atendimento_agendamento_to_string;
        }
    
        $values = Atendimento::where('modificacao_user_id', '=', $this->id)->getIndexedArray('agendamento_id','{agendamento->id}');
        return implode(', ', $values);
    }

    public function set_atendimento_cliente_to_string($atendimento_cliente_to_string)
    {
        if(is_array($atendimento_cliente_to_string))
        {
            $values = Pessoa::where('id', 'in', $atendimento_cliente_to_string)->getIndexedArray('nome', 'nome');
            $this->atendimento_cliente_to_string = implode(', ', $values);
        }
        else
        {
            $this->atendimento_cliente_to_string = $atendimento_cliente_to_string;
        }

        $this->vdata['atendimento_cliente_to_string'] = $this->atendimento_cliente_to_string;
    }

    public function get_atendimento_cliente_to_string()
    {
        if(!empty($this->atendimento_cliente_to_string))
        {
            return $this->atendimento_cliente_to_string;
        }
    
        $values = Atendimento::where('modificacao_user_id', '=', $this->id)->getIndexedArray('cliente_id','{cliente->nome}');
        return implode(', ', $values);
    }

    public function set_atendimento_profissional_to_string($atendimento_profissional_to_string)
    {
        if(is_array($atendimento_profissional_to_string))
        {
            $values = Pessoa::where('id', 'in', $atendimento_profissional_to_string)->getIndexedArray('nome', 'nome');
            $this->atendimento_profissional_to_string = implode(', ', $values);
        }
        else
        {
            $this->atendimento_profissional_to_string = $atendimento_profissional_to_string;
        }

        $this->vdata['atendimento_profissional_to_string'] = $this->atendimento_profissional_to_string;
    }

    public function get_atendimento_profissional_to_string()
    {
        if(!empty($this->atendimento_profissional_to_string))
        {
            return $this->atendimento_profissional_to_string;
        }
    
        $values = Atendimento::where('modificacao_user_id', '=', $this->id)->getIndexedArray('profissional_id','{profissional->nome}');
        return implode(', ', $values);
    }

    public function set_atendimento_tipo_atendimento_to_string($atendimento_tipo_atendimento_to_string)
    {
        if(is_array($atendimento_tipo_atendimento_to_string))
        {
            $values = TipoAtendimento::where('id', 'in', $atendimento_tipo_atendimento_to_string)->getIndexedArray('nome', 'nome');
            $this->atendimento_tipo_atendimento_to_string = implode(', ', $values);
        }
        else
        {
            $this->atendimento_tipo_atendimento_to_string = $atendimento_tipo_atendimento_to_string;
        }

        $this->vdata['atendimento_tipo_atendimento_to_string'] = $this->atendimento_tipo_atendimento_to_string;
    }

    public function get_atendimento_tipo_atendimento_to_string()
    {
        if(!empty($this->atendimento_tipo_atendimento_to_string))
        {
            return $this->atendimento_tipo_atendimento_to_string;
        }
    
        $values = Atendimento::where('modificacao_user_id', '=', $this->id)->getIndexedArray('tipo_atendimento_id','{tipo_atendimento->nome}');
        return implode(', ', $values);
    }

    public function set_atendimento_criacao_user_to_string($atendimento_criacao_user_to_string)
    {
        if(is_array($atendimento_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $atendimento_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->atendimento_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->atendimento_criacao_user_to_string = $atendimento_criacao_user_to_string;
        }

        $this->vdata['atendimento_criacao_user_to_string'] = $this->atendimento_criacao_user_to_string;
    }

    public function get_atendimento_criacao_user_to_string()
    {
        if(!empty($this->atendimento_criacao_user_to_string))
        {
            return $this->atendimento_criacao_user_to_string;
        }
    
        $values = Atendimento::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_atendimento_modificacao_user_to_string($atendimento_modificacao_user_to_string)
    {
        if(is_array($atendimento_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $atendimento_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->atendimento_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->atendimento_modificacao_user_to_string = $atendimento_modificacao_user_to_string;
        }

        $this->vdata['atendimento_modificacao_user_to_string'] = $this->atendimento_modificacao_user_to_string;
    }

    public function get_atendimento_modificacao_user_to_string()
    {
        if(!empty($this->atendimento_modificacao_user_to_string))
        {
            return $this->atendimento_modificacao_user_to_string;
        }
    
        $values = Atendimento::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_bloqueio_agenda_to_string($bloqueio_agenda_to_string)
    {
        if(is_array($bloqueio_agenda_to_string))
        {
            $values = Agenda::where('id', 'in', $bloqueio_agenda_to_string)->getIndexedArray('nome', 'nome');
            $this->bloqueio_agenda_to_string = implode(', ', $values);
        }
        else
        {
            $this->bloqueio_agenda_to_string = $bloqueio_agenda_to_string;
        }

        $this->vdata['bloqueio_agenda_to_string'] = $this->bloqueio_agenda_to_string;
    }

    public function get_bloqueio_agenda_to_string()
    {
        if(!empty($this->bloqueio_agenda_to_string))
        {
            return $this->bloqueio_agenda_to_string;
        }
    
        $values = Bloqueio::where('modificacao_user_id', '=', $this->id)->getIndexedArray('agenda_id','{agenda->nome}');
        return implode(', ', $values);
    }

    public function set_bloqueio_criacao_user_to_string($bloqueio_criacao_user_to_string)
    {
        if(is_array($bloqueio_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $bloqueio_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->bloqueio_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->bloqueio_criacao_user_to_string = $bloqueio_criacao_user_to_string;
        }

        $this->vdata['bloqueio_criacao_user_to_string'] = $this->bloqueio_criacao_user_to_string;
    }

    public function get_bloqueio_criacao_user_to_string()
    {
        if(!empty($this->bloqueio_criacao_user_to_string))
        {
            return $this->bloqueio_criacao_user_to_string;
        }
    
        $values = Bloqueio::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_bloqueio_modificacao_user_to_string($bloqueio_modificacao_user_to_string)
    {
        if(is_array($bloqueio_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $bloqueio_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->bloqueio_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->bloqueio_modificacao_user_to_string = $bloqueio_modificacao_user_to_string;
        }

        $this->vdata['bloqueio_modificacao_user_to_string'] = $this->bloqueio_modificacao_user_to_string;
    }

    public function get_bloqueio_modificacao_user_to_string()
    {
        if(!empty($this->bloqueio_modificacao_user_to_string))
        {
            return $this->bloqueio_modificacao_user_to_string;
        }
    
        $values = Bloqueio::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_categoria_conta_tipo_conta_to_string($categoria_conta_tipo_conta_to_string)
    {
        if(is_array($categoria_conta_tipo_conta_to_string))
        {
            $values = TipoConta::where('id', 'in', $categoria_conta_tipo_conta_to_string)->getIndexedArray('nome', 'nome');
            $this->categoria_conta_tipo_conta_to_string = implode(', ', $values);
        }
        else
        {
            $this->categoria_conta_tipo_conta_to_string = $categoria_conta_tipo_conta_to_string;
        }

        $this->vdata['categoria_conta_tipo_conta_to_string'] = $this->categoria_conta_tipo_conta_to_string;
    }

    public function get_categoria_conta_tipo_conta_to_string()
    {
        if(!empty($this->categoria_conta_tipo_conta_to_string))
        {
            return $this->categoria_conta_tipo_conta_to_string;
        }
    
        $values = CategoriaConta::where('modificacao_user_id', '=', $this->id)->getIndexedArray('tipo_conta_id','{tipo_conta->nome}');
        return implode(', ', $values);
    }

    public function set_categoria_conta_criacao_user_to_string($categoria_conta_criacao_user_to_string)
    {
        if(is_array($categoria_conta_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $categoria_conta_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->categoria_conta_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->categoria_conta_criacao_user_to_string = $categoria_conta_criacao_user_to_string;
        }

        $this->vdata['categoria_conta_criacao_user_to_string'] = $this->categoria_conta_criacao_user_to_string;
    }

    public function get_categoria_conta_criacao_user_to_string()
    {
        if(!empty($this->categoria_conta_criacao_user_to_string))
        {
            return $this->categoria_conta_criacao_user_to_string;
        }
    
        $values = CategoriaConta::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_categoria_conta_modificacao_user_to_string($categoria_conta_modificacao_user_to_string)
    {
        if(is_array($categoria_conta_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $categoria_conta_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->categoria_conta_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->categoria_conta_modificacao_user_to_string = $categoria_conta_modificacao_user_to_string;
        }

        $this->vdata['categoria_conta_modificacao_user_to_string'] = $this->categoria_conta_modificacao_user_to_string;
    }

    public function get_categoria_conta_modificacao_user_to_string()
    {
        if(!empty($this->categoria_conta_modificacao_user_to_string))
        {
            return $this->categoria_conta_modificacao_user_to_string;
        }
    
        $values = CategoriaConta::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_cidade_estado_to_string($cidade_estado_to_string)
    {
        if(is_array($cidade_estado_to_string))
        {
            $values = Estado::where('id', 'in', $cidade_estado_to_string)->getIndexedArray('nome', 'nome');
            $this->cidade_estado_to_string = implode(', ', $values);
        }
        else
        {
            $this->cidade_estado_to_string = $cidade_estado_to_string;
        }

        $this->vdata['cidade_estado_to_string'] = $this->cidade_estado_to_string;
    }

    public function get_cidade_estado_to_string()
    {
        if(!empty($this->cidade_estado_to_string))
        {
            return $this->cidade_estado_to_string;
        }
    
        $values = Cidade::where('modificacao_user_id', '=', $this->id)->getIndexedArray('estado_id','{estado->nome}');
        return implode(', ', $values);
    }

    public function set_cidade_criacao_user_to_string($cidade_criacao_user_to_string)
    {
        if(is_array($cidade_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $cidade_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->cidade_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->cidade_criacao_user_to_string = $cidade_criacao_user_to_string;
        }

        $this->vdata['cidade_criacao_user_to_string'] = $this->cidade_criacao_user_to_string;
    }

    public function get_cidade_criacao_user_to_string()
    {
        if(!empty($this->cidade_criacao_user_to_string))
        {
            return $this->cidade_criacao_user_to_string;
        }
    
        $values = Cidade::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_cidade_modificacao_user_to_string($cidade_modificacao_user_to_string)
    {
        if(is_array($cidade_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $cidade_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->cidade_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->cidade_modificacao_user_to_string = $cidade_modificacao_user_to_string;
        }

        $this->vdata['cidade_modificacao_user_to_string'] = $this->cidade_modificacao_user_to_string;
    }

    public function get_cidade_modificacao_user_to_string()
    {
        if(!empty($this->cidade_modificacao_user_to_string))
        {
            return $this->cidade_modificacao_user_to_string;
        }
    
        $values = Cidade::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_classificacoes_criacao_user_to_string($classificacoes_criacao_user_to_string)
    {
        if(is_array($classificacoes_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $classificacoes_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->classificacoes_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->classificacoes_criacao_user_to_string = $classificacoes_criacao_user_to_string;
        }

        $this->vdata['classificacoes_criacao_user_to_string'] = $this->classificacoes_criacao_user_to_string;
    }

    public function get_classificacoes_criacao_user_to_string()
    {
        if(!empty($this->classificacoes_criacao_user_to_string))
        {
            return $this->classificacoes_criacao_user_to_string;
        }
    
        $values = Classificacoes::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_classificacoes_modificacao_user_to_string($classificacoes_modificacao_user_to_string)
    {
        if(is_array($classificacoes_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $classificacoes_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->classificacoes_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->classificacoes_modificacao_user_to_string = $classificacoes_modificacao_user_to_string;
        }

        $this->vdata['classificacoes_modificacao_user_to_string'] = $this->classificacoes_modificacao_user_to_string;
    }

    public function get_classificacoes_modificacao_user_to_string()
    {
        if(!empty($this->classificacoes_modificacao_user_to_string))
        {
            return $this->classificacoes_modificacao_user_to_string;
        }
    
        $values = Classificacoes::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_classificacoes_contraparte_dados_criacao_user_to_string($classificacoes_contraparte_dados_criacao_user_to_string)
    {
        if(is_array($classificacoes_contraparte_dados_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $classificacoes_contraparte_dados_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->classificacoes_contraparte_dados_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->classificacoes_contraparte_dados_criacao_user_to_string = $classificacoes_contraparte_dados_criacao_user_to_string;
        }

        $this->vdata['classificacoes_contraparte_dados_criacao_user_to_string'] = $this->classificacoes_contraparte_dados_criacao_user_to_string;
    }

    public function get_classificacoes_contraparte_dados_criacao_user_to_string()
    {
        if(!empty($this->classificacoes_contraparte_dados_criacao_user_to_string))
        {
            return $this->classificacoes_contraparte_dados_criacao_user_to_string;
        }
    
        $values = ClassificacoesContraparteDados::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_classificacoes_contraparte_dados_modificacao_user_to_string($classificacoes_contraparte_dados_modificacao_user_to_string)
    {
        if(is_array($classificacoes_contraparte_dados_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $classificacoes_contraparte_dados_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->classificacoes_contraparte_dados_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->classificacoes_contraparte_dados_modificacao_user_to_string = $classificacoes_contraparte_dados_modificacao_user_to_string;
        }

        $this->vdata['classificacoes_contraparte_dados_modificacao_user_to_string'] = $this->classificacoes_contraparte_dados_modificacao_user_to_string;
    }

    public function get_classificacoes_contraparte_dados_modificacao_user_to_string()
    {
        if(!empty($this->classificacoes_contraparte_dados_modificacao_user_to_string))
        {
            return $this->classificacoes_contraparte_dados_modificacao_user_to_string;
        }
    
        $values = ClassificacoesContraparteDados::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_comarca_criacao_user_to_string($comarca_criacao_user_to_string)
    {
        if(is_array($comarca_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $comarca_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->comarca_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->comarca_criacao_user_to_string = $comarca_criacao_user_to_string;
        }

        $this->vdata['comarca_criacao_user_to_string'] = $this->comarca_criacao_user_to_string;
    }

    public function get_comarca_criacao_user_to_string()
    {
        if(!empty($this->comarca_criacao_user_to_string))
        {
            return $this->comarca_criacao_user_to_string;
        }
    
        $values = Comarca::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_comarca_modificacao_user_to_string($comarca_modificacao_user_to_string)
    {
        if(is_array($comarca_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $comarca_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->comarca_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->comarca_modificacao_user_to_string = $comarca_modificacao_user_to_string;
        }

        $this->vdata['comarca_modificacao_user_to_string'] = $this->comarca_modificacao_user_to_string;
    }

    public function get_comarca_modificacao_user_to_string()
    {
        if(!empty($this->comarca_modificacao_user_to_string))
        {
            return $this->comarca_modificacao_user_to_string;
        }
    
        $values = Comarca::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_compromisso_agenda_to_string($compromisso_agenda_to_string)
    {
        if(is_array($compromisso_agenda_to_string))
        {
            $values = Agenda::where('id', 'in', $compromisso_agenda_to_string)->getIndexedArray('nome', 'nome');
            $this->compromisso_agenda_to_string = implode(', ', $values);
        }
        else
        {
            $this->compromisso_agenda_to_string = $compromisso_agenda_to_string;
        }

        $this->vdata['compromisso_agenda_to_string'] = $this->compromisso_agenda_to_string;
    }

    public function get_compromisso_agenda_to_string()
    {
        if(!empty($this->compromisso_agenda_to_string))
        {
            return $this->compromisso_agenda_to_string;
        }
    
        $values = Compromisso::where('modificacao_user_id', '=', $this->id)->getIndexedArray('agenda_id','{agenda->nome}');
        return implode(', ', $values);
    }

    public function set_compromisso_tipo_compromisso_to_string($compromisso_tipo_compromisso_to_string)
    {
        if(is_array($compromisso_tipo_compromisso_to_string))
        {
            $values = TipoCompromisso::where('id', 'in', $compromisso_tipo_compromisso_to_string)->getIndexedArray('nome', 'nome');
            $this->compromisso_tipo_compromisso_to_string = implode(', ', $values);
        }
        else
        {
            $this->compromisso_tipo_compromisso_to_string = $compromisso_tipo_compromisso_to_string;
        }

        $this->vdata['compromisso_tipo_compromisso_to_string'] = $this->compromisso_tipo_compromisso_to_string;
    }

    public function get_compromisso_tipo_compromisso_to_string()
    {
        if(!empty($this->compromisso_tipo_compromisso_to_string))
        {
            return $this->compromisso_tipo_compromisso_to_string;
        }
    
        $values = Compromisso::where('modificacao_user_id', '=', $this->id)->getIndexedArray('tipo_compromisso_id','{tipo_compromisso->nome}');
        return implode(', ', $values);
    }

    public function set_compromisso_criacao_user_to_string($compromisso_criacao_user_to_string)
    {
        if(is_array($compromisso_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $compromisso_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->compromisso_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->compromisso_criacao_user_to_string = $compromisso_criacao_user_to_string;
        }

        $this->vdata['compromisso_criacao_user_to_string'] = $this->compromisso_criacao_user_to_string;
    }

    public function get_compromisso_criacao_user_to_string()
    {
        if(!empty($this->compromisso_criacao_user_to_string))
        {
            return $this->compromisso_criacao_user_to_string;
        }
    
        $values = Compromisso::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_compromisso_modificacao_user_to_string($compromisso_modificacao_user_to_string)
    {
        if(is_array($compromisso_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $compromisso_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->compromisso_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->compromisso_modificacao_user_to_string = $compromisso_modificacao_user_to_string;
        }

        $this->vdata['compromisso_modificacao_user_to_string'] = $this->compromisso_modificacao_user_to_string;
    }

    public function get_compromisso_modificacao_user_to_string()
    {
        if(!empty($this->compromisso_modificacao_user_to_string))
        {
            return $this->compromisso_modificacao_user_to_string;
        }
    
        $values = Compromisso::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_config_busca_a_partir_criacao_user_to_string($config_busca_a_partir_criacao_user_to_string)
    {
        if(is_array($config_busca_a_partir_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $config_busca_a_partir_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->config_busca_a_partir_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->config_busca_a_partir_criacao_user_to_string = $config_busca_a_partir_criacao_user_to_string;
        }

        $this->vdata['config_busca_a_partir_criacao_user_to_string'] = $this->config_busca_a_partir_criacao_user_to_string;
    }

    public function get_config_busca_a_partir_criacao_user_to_string()
    {
        if(!empty($this->config_busca_a_partir_criacao_user_to_string))
        {
            return $this->config_busca_a_partir_criacao_user_to_string;
        }
    
        $values = ConfigBuscaAPartir::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_config_busca_a_partir_modificacao_user_to_string($config_busca_a_partir_modificacao_user_to_string)
    {
        if(is_array($config_busca_a_partir_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $config_busca_a_partir_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->config_busca_a_partir_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->config_busca_a_partir_modificacao_user_to_string = $config_busca_a_partir_modificacao_user_to_string;
        }

        $this->vdata['config_busca_a_partir_modificacao_user_to_string'] = $this->config_busca_a_partir_modificacao_user_to_string;
    }

    public function get_config_busca_a_partir_modificacao_user_to_string()
    {
        if(!empty($this->config_busca_a_partir_modificacao_user_to_string))
        {
            return $this->config_busca_a_partir_modificacao_user_to_string;
        }
    
        $values = ConfigBuscaAPartir::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_config_busca_prazo_tipo_prazo_to_string($config_busca_prazo_tipo_prazo_to_string)
    {
        if(is_array($config_busca_prazo_tipo_prazo_to_string))
        {
            $values = TipoPrazo::where('id', 'in', $config_busca_prazo_tipo_prazo_to_string)->getIndexedArray('nome', 'nome');
            $this->config_busca_prazo_tipo_prazo_to_string = implode(', ', $values);
        }
        else
        {
            $this->config_busca_prazo_tipo_prazo_to_string = $config_busca_prazo_tipo_prazo_to_string;
        }

        $this->vdata['config_busca_prazo_tipo_prazo_to_string'] = $this->config_busca_prazo_tipo_prazo_to_string;
    }

    public function get_config_busca_prazo_tipo_prazo_to_string()
    {
        if(!empty($this->config_busca_prazo_tipo_prazo_to_string))
        {
            return $this->config_busca_prazo_tipo_prazo_to_string;
        }
    
        $values = ConfigBuscaPrazo::where('modificacao_user_id', '=', $this->id)->getIndexedArray('tipo_prazo_id','{tipo_prazo->nome}');
        return implode(', ', $values);
    }

    public function set_config_busca_prazo_config_busca_a_partir_to_string($config_busca_prazo_config_busca_a_partir_to_string)
    {
        if(is_array($config_busca_prazo_config_busca_a_partir_to_string))
        {
            $values = ConfigBuscaAPartir::where('id', 'in', $config_busca_prazo_config_busca_a_partir_to_string)->getIndexedArray('nome', 'nome');
            $this->config_busca_prazo_config_busca_a_partir_to_string = implode(', ', $values);
        }
        else
        {
            $this->config_busca_prazo_config_busca_a_partir_to_string = $config_busca_prazo_config_busca_a_partir_to_string;
        }

        $this->vdata['config_busca_prazo_config_busca_a_partir_to_string'] = $this->config_busca_prazo_config_busca_a_partir_to_string;
    }

    public function get_config_busca_prazo_config_busca_a_partir_to_string()
    {
        if(!empty($this->config_busca_prazo_config_busca_a_partir_to_string))
        {
            return $this->config_busca_prazo_config_busca_a_partir_to_string;
        }
    
        $values = ConfigBuscaPrazo::where('modificacao_user_id', '=', $this->id)->getIndexedArray('config_busca_a_partir_id','{config_busca_a_partir->nome}');
        return implode(', ', $values);
    }

    public function set_config_busca_prazo_criacao_user_to_string($config_busca_prazo_criacao_user_to_string)
    {
        if(is_array($config_busca_prazo_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $config_busca_prazo_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->config_busca_prazo_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->config_busca_prazo_criacao_user_to_string = $config_busca_prazo_criacao_user_to_string;
        }

        $this->vdata['config_busca_prazo_criacao_user_to_string'] = $this->config_busca_prazo_criacao_user_to_string;
    }

    public function get_config_busca_prazo_criacao_user_to_string()
    {
        if(!empty($this->config_busca_prazo_criacao_user_to_string))
        {
            return $this->config_busca_prazo_criacao_user_to_string;
        }
    
        $values = ConfigBuscaPrazo::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_config_busca_prazo_modificacao_user_to_string($config_busca_prazo_modificacao_user_to_string)
    {
        if(is_array($config_busca_prazo_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $config_busca_prazo_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->config_busca_prazo_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->config_busca_prazo_modificacao_user_to_string = $config_busca_prazo_modificacao_user_to_string;
        }

        $this->vdata['config_busca_prazo_modificacao_user_to_string'] = $this->config_busca_prazo_modificacao_user_to_string;
    }

    public function get_config_busca_prazo_modificacao_user_to_string()
    {
        if(!empty($this->config_busca_prazo_modificacao_user_to_string))
        {
            return $this->config_busca_prazo_modificacao_user_to_string;
        }
    
        $values = ConfigBuscaPrazo::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_conta_pessoa_to_string($conta_pessoa_to_string)
    {
        if(is_array($conta_pessoa_to_string))
        {
            $values = Pessoa::where('id', 'in', $conta_pessoa_to_string)->getIndexedArray('nome', 'nome');
            $this->conta_pessoa_to_string = implode(', ', $values);
        }
        else
        {
            $this->conta_pessoa_to_string = $conta_pessoa_to_string;
        }

        $this->vdata['conta_pessoa_to_string'] = $this->conta_pessoa_to_string;
    }

    public function get_conta_pessoa_to_string()
    {
        if(!empty($this->conta_pessoa_to_string))
        {
            return $this->conta_pessoa_to_string;
        }
    
        $values = Conta::where('modificacao_user_id', '=', $this->id)->getIndexedArray('pessoa_id','{pessoa->nome}');
        return implode(', ', $values);
    }

    public function set_conta_categoria_conta_to_string($conta_categoria_conta_to_string)
    {
        if(is_array($conta_categoria_conta_to_string))
        {
            $values = CategoriaConta::where('id', 'in', $conta_categoria_conta_to_string)->getIndexedArray('nome', 'nome');
            $this->conta_categoria_conta_to_string = implode(', ', $values);
        }
        else
        {
            $this->conta_categoria_conta_to_string = $conta_categoria_conta_to_string;
        }

        $this->vdata['conta_categoria_conta_to_string'] = $this->conta_categoria_conta_to_string;
    }

    public function get_conta_categoria_conta_to_string()
    {
        if(!empty($this->conta_categoria_conta_to_string))
        {
            return $this->conta_categoria_conta_to_string;
        }
    
        $values = Conta::where('modificacao_user_id', '=', $this->id)->getIndexedArray('categoria_conta_id','{categoria_conta->nome}');
        return implode(', ', $values);
    }

    public function set_conta_tipo_conta_to_string($conta_tipo_conta_to_string)
    {
        if(is_array($conta_tipo_conta_to_string))
        {
            $values = TipoConta::where('id', 'in', $conta_tipo_conta_to_string)->getIndexedArray('nome', 'nome');
            $this->conta_tipo_conta_to_string = implode(', ', $values);
        }
        else
        {
            $this->conta_tipo_conta_to_string = $conta_tipo_conta_to_string;
        }

        $this->vdata['conta_tipo_conta_to_string'] = $this->conta_tipo_conta_to_string;
    }

    public function get_conta_tipo_conta_to_string()
    {
        if(!empty($this->conta_tipo_conta_to_string))
        {
            return $this->conta_tipo_conta_to_string;
        }
    
        $values = Conta::where('modificacao_user_id', '=', $this->id)->getIndexedArray('tipo_conta_id','{tipo_conta->nome}');
        return implode(', ', $values);
    }

    public function set_conta_escritorio_to_string($conta_escritorio_to_string)
    {
        if(is_array($conta_escritorio_to_string))
        {
            $values = Escritorio::where('id', 'in', $conta_escritorio_to_string)->getIndexedArray('nome', 'nome');
            $this->conta_escritorio_to_string = implode(', ', $values);
        }
        else
        {
            $this->conta_escritorio_to_string = $conta_escritorio_to_string;
        }

        $this->vdata['conta_escritorio_to_string'] = $this->conta_escritorio_to_string;
    }

    public function get_conta_escritorio_to_string()
    {
        if(!empty($this->conta_escritorio_to_string))
        {
            return $this->conta_escritorio_to_string;
        }
    
        $values = Conta::where('modificacao_user_id', '=', $this->id)->getIndexedArray('escritorio_id','{escritorio->nome}');
        return implode(', ', $values);
    }

    public function set_conta_tipo_documento_financeiro_to_string($conta_tipo_documento_financeiro_to_string)
    {
        if(is_array($conta_tipo_documento_financeiro_to_string))
        {
            $values = TipoDocumentoFinanceiro::where('id', 'in', $conta_tipo_documento_financeiro_to_string)->getIndexedArray('nome', 'nome');
            $this->conta_tipo_documento_financeiro_to_string = implode(', ', $values);
        }
        else
        {
            $this->conta_tipo_documento_financeiro_to_string = $conta_tipo_documento_financeiro_to_string;
        }

        $this->vdata['conta_tipo_documento_financeiro_to_string'] = $this->conta_tipo_documento_financeiro_to_string;
    }

    public function get_conta_tipo_documento_financeiro_to_string()
    {
        if(!empty($this->conta_tipo_documento_financeiro_to_string))
        {
            return $this->conta_tipo_documento_financeiro_to_string;
        }
    
        $values = Conta::where('modificacao_user_id', '=', $this->id)->getIndexedArray('tipo_documento_financeiro_id','{tipo_documento_financeiro->nome}');
        return implode(', ', $values);
    }

    public function set_conta_atendimento_to_string($conta_atendimento_to_string)
    {
        if(is_array($conta_atendimento_to_string))
        {
            $values = Atendimento::where('id', 'in', $conta_atendimento_to_string)->getIndexedArray('id', 'id');
            $this->conta_atendimento_to_string = implode(', ', $values);
        }
        else
        {
            $this->conta_atendimento_to_string = $conta_atendimento_to_string;
        }

        $this->vdata['conta_atendimento_to_string'] = $this->conta_atendimento_to_string;
    }

    public function get_conta_atendimento_to_string()
    {
        if(!empty($this->conta_atendimento_to_string))
        {
            return $this->conta_atendimento_to_string;
        }
    
        $values = Conta::where('modificacao_user_id', '=', $this->id)->getIndexedArray('atendimento_id','{atendimento->id}');
        return implode(', ', $values);
    }

    public function set_conta_contrato_to_string($conta_contrato_to_string)
    {
        if(is_array($conta_contrato_to_string))
        {
            $values = Contrato::where('id', 'in', $conta_contrato_to_string)->getIndexedArray('objeto', 'objeto');
            $this->conta_contrato_to_string = implode(', ', $values);
        }
        else
        {
            $this->conta_contrato_to_string = $conta_contrato_to_string;
        }

        $this->vdata['conta_contrato_to_string'] = $this->conta_contrato_to_string;
    }

    public function get_conta_contrato_to_string()
    {
        if(!empty($this->conta_contrato_to_string))
        {
            return $this->conta_contrato_to_string;
        }
    
        $values = Conta::where('modificacao_user_id', '=', $this->id)->getIndexedArray('contrato_id','{contrato->objeto}');
        return implode(', ', $values);
    }

    public function set_conta_profissional_to_string($conta_profissional_to_string)
    {
        if(is_array($conta_profissional_to_string))
        {
            $values = Pessoa::where('id', 'in', $conta_profissional_to_string)->getIndexedArray('nome', 'nome');
            $this->conta_profissional_to_string = implode(', ', $values);
        }
        else
        {
            $this->conta_profissional_to_string = $conta_profissional_to_string;
        }

        $this->vdata['conta_profissional_to_string'] = $this->conta_profissional_to_string;
    }

    public function get_conta_profissional_to_string()
    {
        if(!empty($this->conta_profissional_to_string))
        {
            return $this->conta_profissional_to_string;
        }
    
        $values = Conta::where('modificacao_user_id', '=', $this->id)->getIndexedArray('profissional_id','{profissional->nome}');
        return implode(', ', $values);
    }

    public function set_conta_processo_to_string($conta_processo_to_string)
    {
        if(is_array($conta_processo_to_string))
        {
            $values = Processo::where('id', 'in', $conta_processo_to_string)->getIndexedArray('numero_cnj_numero', 'numero_cnj_numero');
            $this->conta_processo_to_string = implode(', ', $values);
        }
        else
        {
            $this->conta_processo_to_string = $conta_processo_to_string;
        }

        $this->vdata['conta_processo_to_string'] = $this->conta_processo_to_string;
    }

    public function get_conta_processo_to_string()
    {
        if(!empty($this->conta_processo_to_string))
        {
            return $this->conta_processo_to_string;
        }
    
        $values = Conta::where('modificacao_user_id', '=', $this->id)->getIndexedArray('processo_id','{processo->numero_cnj_numero}');
        return implode(', ', $values);
    }

    public function set_conta_criacao_user_to_string($conta_criacao_user_to_string)
    {
        if(is_array($conta_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $conta_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->conta_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->conta_criacao_user_to_string = $conta_criacao_user_to_string;
        }

        $this->vdata['conta_criacao_user_to_string'] = $this->conta_criacao_user_to_string;
    }

    public function get_conta_criacao_user_to_string()
    {
        if(!empty($this->conta_criacao_user_to_string))
        {
            return $this->conta_criacao_user_to_string;
        }
    
        $values = Conta::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_conta_modificacao_user_to_string($conta_modificacao_user_to_string)
    {
        if(is_array($conta_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $conta_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->conta_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->conta_modificacao_user_to_string = $conta_modificacao_user_to_string;
        }

        $this->vdata['conta_modificacao_user_to_string'] = $this->conta_modificacao_user_to_string;
    }

    public function get_conta_modificacao_user_to_string()
    {
        if(!empty($this->conta_modificacao_user_to_string))
        {
            return $this->conta_modificacao_user_to_string;
        }
    
        $values = Conta::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_conta_caixa_tipo_conta_caixa_to_string($conta_caixa_tipo_conta_caixa_to_string)
    {
        if(is_array($conta_caixa_tipo_conta_caixa_to_string))
        {
            $values = TipoContaCaixa::where('id', 'in', $conta_caixa_tipo_conta_caixa_to_string)->getIndexedArray('nome', 'nome');
            $this->conta_caixa_tipo_conta_caixa_to_string = implode(', ', $values);
        }
        else
        {
            $this->conta_caixa_tipo_conta_caixa_to_string = $conta_caixa_tipo_conta_caixa_to_string;
        }

        $this->vdata['conta_caixa_tipo_conta_caixa_to_string'] = $this->conta_caixa_tipo_conta_caixa_to_string;
    }

    public function get_conta_caixa_tipo_conta_caixa_to_string()
    {
        if(!empty($this->conta_caixa_tipo_conta_caixa_to_string))
        {
            return $this->conta_caixa_tipo_conta_caixa_to_string;
        }
    
        $values = ContaCaixa::where('modificacao_user_id', '=', $this->id)->getIndexedArray('tipo_conta_caixa_id','{tipo_conta_caixa->nome}');
        return implode(', ', $values);
    }

    public function set_conta_caixa_banco_to_string($conta_caixa_banco_to_string)
    {
        if(is_array($conta_caixa_banco_to_string))
        {
            $values = Banco::where('id', 'in', $conta_caixa_banco_to_string)->getIndexedArray('nome', 'nome');
            $this->conta_caixa_banco_to_string = implode(', ', $values);
        }
        else
        {
            $this->conta_caixa_banco_to_string = $conta_caixa_banco_to_string;
        }

        $this->vdata['conta_caixa_banco_to_string'] = $this->conta_caixa_banco_to_string;
    }

    public function get_conta_caixa_banco_to_string()
    {
        if(!empty($this->conta_caixa_banco_to_string))
        {
            return $this->conta_caixa_banco_to_string;
        }
    
        $values = ContaCaixa::where('modificacao_user_id', '=', $this->id)->getIndexedArray('banco_id','{banco->nome}');
        return implode(', ', $values);
    }

    public function set_conta_caixa_criacao_user_to_string($conta_caixa_criacao_user_to_string)
    {
        if(is_array($conta_caixa_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $conta_caixa_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->conta_caixa_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->conta_caixa_criacao_user_to_string = $conta_caixa_criacao_user_to_string;
        }

        $this->vdata['conta_caixa_criacao_user_to_string'] = $this->conta_caixa_criacao_user_to_string;
    }

    public function get_conta_caixa_criacao_user_to_string()
    {
        if(!empty($this->conta_caixa_criacao_user_to_string))
        {
            return $this->conta_caixa_criacao_user_to_string;
        }
    
        $values = ContaCaixa::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_conta_caixa_modificacao_user_to_string($conta_caixa_modificacao_user_to_string)
    {
        if(is_array($conta_caixa_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $conta_caixa_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->conta_caixa_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->conta_caixa_modificacao_user_to_string = $conta_caixa_modificacao_user_to_string;
        }

        $this->vdata['conta_caixa_modificacao_user_to_string'] = $this->conta_caixa_modificacao_user_to_string;
    }

    public function get_conta_caixa_modificacao_user_to_string()
    {
        if(!empty($this->conta_caixa_modificacao_user_to_string))
        {
            return $this->conta_caixa_modificacao_user_to_string;
        }
    
        $values = ContaCaixa::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_contraparte_processo_to_string($contraparte_processo_to_string)
    {
        if(is_array($contraparte_processo_to_string))
        {
            $values = Processo::where('id', 'in', $contraparte_processo_to_string)->getIndexedArray('numero_cnj_numero', 'numero_cnj_numero');
            $this->contraparte_processo_to_string = implode(', ', $values);
        }
        else
        {
            $this->contraparte_processo_to_string = $contraparte_processo_to_string;
        }

        $this->vdata['contraparte_processo_to_string'] = $this->contraparte_processo_to_string;
    }

    public function get_contraparte_processo_to_string()
    {
        if(!empty($this->contraparte_processo_to_string))
        {
            return $this->contraparte_processo_to_string;
        }
    
        $values = Contraparte::where('modificacao_user_id', '=', $this->id)->getIndexedArray('processo_id','{processo->numero_cnj_numero}');
        return implode(', ', $values);
    }

    public function set_contraparte_pessoa_to_string($contraparte_pessoa_to_string)
    {
        if(is_array($contraparte_pessoa_to_string))
        {
            $values = Pessoa::where('id', 'in', $contraparte_pessoa_to_string)->getIndexedArray('nome', 'nome');
            $this->contraparte_pessoa_to_string = implode(', ', $values);
        }
        else
        {
            $this->contraparte_pessoa_to_string = $contraparte_pessoa_to_string;
        }

        $this->vdata['contraparte_pessoa_to_string'] = $this->contraparte_pessoa_to_string;
    }

    public function get_contraparte_pessoa_to_string()
    {
        if(!empty($this->contraparte_pessoa_to_string))
        {
            return $this->contraparte_pessoa_to_string;
        }
    
        $values = Contraparte::where('modificacao_user_id', '=', $this->id)->getIndexedArray('pessoa_id','{pessoa->nome}');
        return implode(', ', $values);
    }

    public function set_contraparte_criacao_user_to_string($contraparte_criacao_user_to_string)
    {
        if(is_array($contraparte_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $contraparte_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->contraparte_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->contraparte_criacao_user_to_string = $contraparte_criacao_user_to_string;
        }

        $this->vdata['contraparte_criacao_user_to_string'] = $this->contraparte_criacao_user_to_string;
    }

    public function get_contraparte_criacao_user_to_string()
    {
        if(!empty($this->contraparte_criacao_user_to_string))
        {
            return $this->contraparte_criacao_user_to_string;
        }
    
        $values = Contraparte::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_contraparte_modificacao_user_to_string($contraparte_modificacao_user_to_string)
    {
        if(is_array($contraparte_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $contraparte_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->contraparte_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->contraparte_modificacao_user_to_string = $contraparte_modificacao_user_to_string;
        }

        $this->vdata['contraparte_modificacao_user_to_string'] = $this->contraparte_modificacao_user_to_string;
    }

    public function get_contraparte_modificacao_user_to_string()
    {
        if(!empty($this->contraparte_modificacao_user_to_string))
        {
            return $this->contraparte_modificacao_user_to_string;
        }
    
        $values = Contraparte::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_contrato_escritorio_to_string($contrato_escritorio_to_string)
    {
        if(is_array($contrato_escritorio_to_string))
        {
            $values = Escritorio::where('id', 'in', $contrato_escritorio_to_string)->getIndexedArray('nome', 'nome');
            $this->contrato_escritorio_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_escritorio_to_string = $contrato_escritorio_to_string;
        }

        $this->vdata['contrato_escritorio_to_string'] = $this->contrato_escritorio_to_string;
    }

    public function get_contrato_escritorio_to_string()
    {
        if(!empty($this->contrato_escritorio_to_string))
        {
            return $this->contrato_escritorio_to_string;
        }
    
        $values = Contrato::where('modificacao_user_id', '=', $this->id)->getIndexedArray('escritorio_id','{escritorio->nome}');
        return implode(', ', $values);
    }

    public function set_contrato_tipo_processo_to_string($contrato_tipo_processo_to_string)
    {
        if(is_array($contrato_tipo_processo_to_string))
        {
            $values = TipoProcesso::where('id', 'in', $contrato_tipo_processo_to_string)->getIndexedArray('nome', 'nome');
            $this->contrato_tipo_processo_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_tipo_processo_to_string = $contrato_tipo_processo_to_string;
        }

        $this->vdata['contrato_tipo_processo_to_string'] = $this->contrato_tipo_processo_to_string;
    }

    public function get_contrato_tipo_processo_to_string()
    {
        if(!empty($this->contrato_tipo_processo_to_string))
        {
            return $this->contrato_tipo_processo_to_string;
        }
    
        $values = Contrato::where('modificacao_user_id', '=', $this->id)->getIndexedArray('tipo_processo_id','{tipo_processo->nome}');
        return implode(', ', $values);
    }

    public function set_contrato_area_to_string($contrato_area_to_string)
    {
        if(is_array($contrato_area_to_string))
        {
            $values = Area::where('id', 'in', $contrato_area_to_string)->getIndexedArray('nome', 'nome');
            $this->contrato_area_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_area_to_string = $contrato_area_to_string;
        }

        $this->vdata['contrato_area_to_string'] = $this->contrato_area_to_string;
    }

    public function get_contrato_area_to_string()
    {
        if(!empty($this->contrato_area_to_string))
        {
            return $this->contrato_area_to_string;
        }
    
        $values = Contrato::where('modificacao_user_id', '=', $this->id)->getIndexedArray('area_id','{area->nome}');
        return implode(', ', $values);
    }

    public function set_contrato_contrato_status_to_string($contrato_contrato_status_to_string)
    {
        if(is_array($contrato_contrato_status_to_string))
        {
            $values = ContratoStatus::where('id', 'in', $contrato_contrato_status_to_string)->getIndexedArray('nome', 'nome');
            $this->contrato_contrato_status_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_contrato_status_to_string = $contrato_contrato_status_to_string;
        }

        $this->vdata['contrato_contrato_status_to_string'] = $this->contrato_contrato_status_to_string;
    }

    public function get_contrato_contrato_status_to_string()
    {
        if(!empty($this->contrato_contrato_status_to_string))
        {
            return $this->contrato_contrato_status_to_string;
        }
    
        $values = Contrato::where('modificacao_user_id', '=', $this->id)->getIndexedArray('contrato_status_id','{contrato_status->nome}');
        return implode(', ', $values);
    }

    public function set_contrato_assunto_to_string($contrato_assunto_to_string)
    {
        if(is_array($contrato_assunto_to_string))
        {
            $values = Assunto::where('id', 'in', $contrato_assunto_to_string)->getIndexedArray('nome', 'nome');
            $this->contrato_assunto_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_assunto_to_string = $contrato_assunto_to_string;
        }

        $this->vdata['contrato_assunto_to_string'] = $this->contrato_assunto_to_string;
    }

    public function get_contrato_assunto_to_string()
    {
        if(!empty($this->contrato_assunto_to_string))
        {
            return $this->contrato_assunto_to_string;
        }
    
        $values = Contrato::where('modificacao_user_id', '=', $this->id)->getIndexedArray('assunto_id','{assunto->nome}');
        return implode(', ', $values);
    }

    public function set_contrato_envolvimento_to_string($contrato_envolvimento_to_string)
    {
        if(is_array($contrato_envolvimento_to_string))
        {
            $values = Envolvimento::where('id', 'in', $contrato_envolvimento_to_string)->getIndexedArray('nome', 'nome');
            $this->contrato_envolvimento_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_envolvimento_to_string = $contrato_envolvimento_to_string;
        }

        $this->vdata['contrato_envolvimento_to_string'] = $this->contrato_envolvimento_to_string;
    }

    public function get_contrato_envolvimento_to_string()
    {
        if(!empty($this->contrato_envolvimento_to_string))
        {
            return $this->contrato_envolvimento_to_string;
        }
    
        $values = Contrato::where('modificacao_user_id', '=', $this->id)->getIndexedArray('envolvimento_id','{envolvimento->nome}');
        return implode(', ', $values);
    }

    public function set_contrato_criacao_user_to_string($contrato_criacao_user_to_string)
    {
        if(is_array($contrato_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $contrato_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->contrato_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_criacao_user_to_string = $contrato_criacao_user_to_string;
        }

        $this->vdata['contrato_criacao_user_to_string'] = $this->contrato_criacao_user_to_string;
    }

    public function get_contrato_criacao_user_to_string()
    {
        if(!empty($this->contrato_criacao_user_to_string))
        {
            return $this->contrato_criacao_user_to_string;
        }
    
        $values = Contrato::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_contrato_modificacao_user_to_string($contrato_modificacao_user_to_string)
    {
        if(is_array($contrato_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $contrato_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->contrato_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_modificacao_user_to_string = $contrato_modificacao_user_to_string;
        }

        $this->vdata['contrato_modificacao_user_to_string'] = $this->contrato_modificacao_user_to_string;
    }

    public function get_contrato_modificacao_user_to_string()
    {
        if(!empty($this->contrato_modificacao_user_to_string))
        {
            return $this->contrato_modificacao_user_to_string;
        }
    
        $values = Contrato::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_contrato_documento_contrato_to_string($contrato_documento_contrato_to_string)
    {
        if(is_array($contrato_documento_contrato_to_string))
        {
            $values = Contrato::where('id', 'in', $contrato_documento_contrato_to_string)->getIndexedArray('objeto', 'objeto');
            $this->contrato_documento_contrato_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_documento_contrato_to_string = $contrato_documento_contrato_to_string;
        }

        $this->vdata['contrato_documento_contrato_to_string'] = $this->contrato_documento_contrato_to_string;
    }

    public function get_contrato_documento_contrato_to_string()
    {
        if(!empty($this->contrato_documento_contrato_to_string))
        {
            return $this->contrato_documento_contrato_to_string;
        }
    
        $values = ContratoDocumento::where('modificacao_user_id', '=', $this->id)->getIndexedArray('contrato_id','{contrato->objeto}');
        return implode(', ', $values);
    }

    public function set_contrato_documento_modelo_documento_to_string($contrato_documento_modelo_documento_to_string)
    {
        if(is_array($contrato_documento_modelo_documento_to_string))
        {
            $values = ModeloDocumento::where('id', 'in', $contrato_documento_modelo_documento_to_string)->getIndexedArray('nome', 'nome');
            $this->contrato_documento_modelo_documento_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_documento_modelo_documento_to_string = $contrato_documento_modelo_documento_to_string;
        }

        $this->vdata['contrato_documento_modelo_documento_to_string'] = $this->contrato_documento_modelo_documento_to_string;
    }

    public function get_contrato_documento_modelo_documento_to_string()
    {
        if(!empty($this->contrato_documento_modelo_documento_to_string))
        {
            return $this->contrato_documento_modelo_documento_to_string;
        }
    
        $values = ContratoDocumento::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modelo_documento_id','{modelo_documento->nome}');
        return implode(', ', $values);
    }

    public function set_contrato_documento_criacao_user_to_string($contrato_documento_criacao_user_to_string)
    {
        if(is_array($contrato_documento_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $contrato_documento_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->contrato_documento_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_documento_criacao_user_to_string = $contrato_documento_criacao_user_to_string;
        }

        $this->vdata['contrato_documento_criacao_user_to_string'] = $this->contrato_documento_criacao_user_to_string;
    }

    public function get_contrato_documento_criacao_user_to_string()
    {
        if(!empty($this->contrato_documento_criacao_user_to_string))
        {
            return $this->contrato_documento_criacao_user_to_string;
        }
    
        $values = ContratoDocumento::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_contrato_documento_modificacao_user_to_string($contrato_documento_modificacao_user_to_string)
    {
        if(is_array($contrato_documento_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $contrato_documento_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->contrato_documento_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_documento_modificacao_user_to_string = $contrato_documento_modificacao_user_to_string;
        }

        $this->vdata['contrato_documento_modificacao_user_to_string'] = $this->contrato_documento_modificacao_user_to_string;
    }

    public function get_contrato_documento_modificacao_user_to_string()
    {
        if(!empty($this->contrato_documento_modificacao_user_to_string))
        {
            return $this->contrato_documento_modificacao_user_to_string;
        }
    
        $values = ContratoDocumento::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_contrato_pagamento_evento_criacao_user_to_string($contrato_pagamento_evento_criacao_user_to_string)
    {
        if(is_array($contrato_pagamento_evento_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $contrato_pagamento_evento_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->contrato_pagamento_evento_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_pagamento_evento_criacao_user_to_string = $contrato_pagamento_evento_criacao_user_to_string;
        }

        $this->vdata['contrato_pagamento_evento_criacao_user_to_string'] = $this->contrato_pagamento_evento_criacao_user_to_string;
    }

    public function get_contrato_pagamento_evento_criacao_user_to_string()
    {
        if(!empty($this->contrato_pagamento_evento_criacao_user_to_string))
        {
            return $this->contrato_pagamento_evento_criacao_user_to_string;
        }
    
        $values = ContratoPagamentoEvento::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_contrato_pagamento_evento_modificacao_user_to_string($contrato_pagamento_evento_modificacao_user_to_string)
    {
        if(is_array($contrato_pagamento_evento_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $contrato_pagamento_evento_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->contrato_pagamento_evento_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_pagamento_evento_modificacao_user_to_string = $contrato_pagamento_evento_modificacao_user_to_string;
        }

        $this->vdata['contrato_pagamento_evento_modificacao_user_to_string'] = $this->contrato_pagamento_evento_modificacao_user_to_string;
    }

    public function get_contrato_pagamento_evento_modificacao_user_to_string()
    {
        if(!empty($this->contrato_pagamento_evento_modificacao_user_to_string))
        {
            return $this->contrato_pagamento_evento_modificacao_user_to_string;
        }
    
        $values = ContratoPagamentoEvento::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_contrato_pagamento_indexador_criacao_user_to_string($contrato_pagamento_indexador_criacao_user_to_string)
    {
        if(is_array($contrato_pagamento_indexador_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $contrato_pagamento_indexador_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->contrato_pagamento_indexador_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_pagamento_indexador_criacao_user_to_string = $contrato_pagamento_indexador_criacao_user_to_string;
        }

        $this->vdata['contrato_pagamento_indexador_criacao_user_to_string'] = $this->contrato_pagamento_indexador_criacao_user_to_string;
    }

    public function get_contrato_pagamento_indexador_criacao_user_to_string()
    {
        if(!empty($this->contrato_pagamento_indexador_criacao_user_to_string))
        {
            return $this->contrato_pagamento_indexador_criacao_user_to_string;
        }
    
        $values = ContratoPagamentoIndexador::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_contrato_pagamento_indexador_modificacao_user_to_string($contrato_pagamento_indexador_modificacao_user_to_string)
    {
        if(is_array($contrato_pagamento_indexador_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $contrato_pagamento_indexador_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->contrato_pagamento_indexador_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_pagamento_indexador_modificacao_user_to_string = $contrato_pagamento_indexador_modificacao_user_to_string;
        }

        $this->vdata['contrato_pagamento_indexador_modificacao_user_to_string'] = $this->contrato_pagamento_indexador_modificacao_user_to_string;
    }

    public function get_contrato_pagamento_indexador_modificacao_user_to_string()
    {
        if(!empty($this->contrato_pagamento_indexador_modificacao_user_to_string))
        {
            return $this->contrato_pagamento_indexador_modificacao_user_to_string;
        }
    
        $values = ContratoPagamentoIndexador::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_contrato_pagamento_opcao_criacao_user_to_string($contrato_pagamento_opcao_criacao_user_to_string)
    {
        if(is_array($contrato_pagamento_opcao_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $contrato_pagamento_opcao_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->contrato_pagamento_opcao_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_pagamento_opcao_criacao_user_to_string = $contrato_pagamento_opcao_criacao_user_to_string;
        }

        $this->vdata['contrato_pagamento_opcao_criacao_user_to_string'] = $this->contrato_pagamento_opcao_criacao_user_to_string;
    }

    public function get_contrato_pagamento_opcao_criacao_user_to_string()
    {
        if(!empty($this->contrato_pagamento_opcao_criacao_user_to_string))
        {
            return $this->contrato_pagamento_opcao_criacao_user_to_string;
        }
    
        $values = ContratoPagamentoOpcao::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_contrato_pagamento_opcao_modificacao_user_to_string($contrato_pagamento_opcao_modificacao_user_to_string)
    {
        if(is_array($contrato_pagamento_opcao_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $contrato_pagamento_opcao_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->contrato_pagamento_opcao_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_pagamento_opcao_modificacao_user_to_string = $contrato_pagamento_opcao_modificacao_user_to_string;
        }

        $this->vdata['contrato_pagamento_opcao_modificacao_user_to_string'] = $this->contrato_pagamento_opcao_modificacao_user_to_string;
    }

    public function get_contrato_pagamento_opcao_modificacao_user_to_string()
    {
        if(!empty($this->contrato_pagamento_opcao_modificacao_user_to_string))
        {
            return $this->contrato_pagamento_opcao_modificacao_user_to_string;
        }
    
        $values = ContratoPagamentoOpcao::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_contrato_pagamento_parcela_contrato_to_string($contrato_pagamento_parcela_contrato_to_string)
    {
        if(is_array($contrato_pagamento_parcela_contrato_to_string))
        {
            $values = Contrato::where('id', 'in', $contrato_pagamento_parcela_contrato_to_string)->getIndexedArray('objeto', 'objeto');
            $this->contrato_pagamento_parcela_contrato_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_pagamento_parcela_contrato_to_string = $contrato_pagamento_parcela_contrato_to_string;
        }

        $this->vdata['contrato_pagamento_parcela_contrato_to_string'] = $this->contrato_pagamento_parcela_contrato_to_string;
    }

    public function get_contrato_pagamento_parcela_contrato_to_string()
    {
        if(!empty($this->contrato_pagamento_parcela_contrato_to_string))
        {
            return $this->contrato_pagamento_parcela_contrato_to_string;
        }
    
        $values = ContratoPagamentoParcela::where('modificacao_user_id', '=', $this->id)->getIndexedArray('contrato_id','{contrato->objeto}');
        return implode(', ', $values);
    }

    public function set_contrato_pagamento_parcela_status_contrato_pagamento_to_string($contrato_pagamento_parcela_status_contrato_pagamento_to_string)
    {
        if(is_array($contrato_pagamento_parcela_status_contrato_pagamento_to_string))
        {
            $values = StatusContratoPagamento::where('id', 'in', $contrato_pagamento_parcela_status_contrato_pagamento_to_string)->getIndexedArray('id', 'id');
            $this->contrato_pagamento_parcela_status_contrato_pagamento_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_pagamento_parcela_status_contrato_pagamento_to_string = $contrato_pagamento_parcela_status_contrato_pagamento_to_string;
        }

        $this->vdata['contrato_pagamento_parcela_status_contrato_pagamento_to_string'] = $this->contrato_pagamento_parcela_status_contrato_pagamento_to_string;
    }

    public function get_contrato_pagamento_parcela_status_contrato_pagamento_to_string()
    {
        if(!empty($this->contrato_pagamento_parcela_status_contrato_pagamento_to_string))
        {
            return $this->contrato_pagamento_parcela_status_contrato_pagamento_to_string;
        }
    
        $values = ContratoPagamentoParcela::where('modificacao_user_id', '=', $this->id)->getIndexedArray('status_contrato_pagamento_id','{status_contrato_pagamento->id}');
        return implode(', ', $values);
    }

    public function set_contrato_pagamento_parcela_contrato_opcao_pagamento_to_string($contrato_pagamento_parcela_contrato_opcao_pagamento_to_string)
    {
        if(is_array($contrato_pagamento_parcela_contrato_opcao_pagamento_to_string))
        {
            $values = ContratoPagamentoOpcao::where('id', 'in', $contrato_pagamento_parcela_contrato_opcao_pagamento_to_string)->getIndexedArray('nome', 'nome');
            $this->contrato_pagamento_parcela_contrato_opcao_pagamento_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_pagamento_parcela_contrato_opcao_pagamento_to_string = $contrato_pagamento_parcela_contrato_opcao_pagamento_to_string;
        }

        $this->vdata['contrato_pagamento_parcela_contrato_opcao_pagamento_to_string'] = $this->contrato_pagamento_parcela_contrato_opcao_pagamento_to_string;
    }

    public function get_contrato_pagamento_parcela_contrato_opcao_pagamento_to_string()
    {
        if(!empty($this->contrato_pagamento_parcela_contrato_opcao_pagamento_to_string))
        {
            return $this->contrato_pagamento_parcela_contrato_opcao_pagamento_to_string;
        }
    
        $values = ContratoPagamentoParcela::where('modificacao_user_id', '=', $this->id)->getIndexedArray('contrato_opcao_pagamento_id','{contrato_opcao_pagamento->nome}');
        return implode(', ', $values);
    }

    public function set_contrato_pagamento_parcela_contrato_evento_to_string($contrato_pagamento_parcela_contrato_evento_to_string)
    {
        if(is_array($contrato_pagamento_parcela_contrato_evento_to_string))
        {
            $values = ContratoPagamentoEvento::where('id', 'in', $contrato_pagamento_parcela_contrato_evento_to_string)->getIndexedArray('nome', 'nome');
            $this->contrato_pagamento_parcela_contrato_evento_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_pagamento_parcela_contrato_evento_to_string = $contrato_pagamento_parcela_contrato_evento_to_string;
        }

        $this->vdata['contrato_pagamento_parcela_contrato_evento_to_string'] = $this->contrato_pagamento_parcela_contrato_evento_to_string;
    }

    public function get_contrato_pagamento_parcela_contrato_evento_to_string()
    {
        if(!empty($this->contrato_pagamento_parcela_contrato_evento_to_string))
        {
            return $this->contrato_pagamento_parcela_contrato_evento_to_string;
        }
    
        $values = ContratoPagamentoParcela::where('modificacao_user_id', '=', $this->id)->getIndexedArray('contrato_evento_id','{contrato_evento->nome}');
        return implode(', ', $values);
    }

    public function set_contrato_pagamento_parcela_unidade_indexador_to_string($contrato_pagamento_parcela_unidade_indexador_to_string)
    {
        if(is_array($contrato_pagamento_parcela_unidade_indexador_to_string))
        {
            $values = UnidadeIndexador::where('id', 'in', $contrato_pagamento_parcela_unidade_indexador_to_string)->getIndexedArray('nome', 'nome');
            $this->contrato_pagamento_parcela_unidade_indexador_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_pagamento_parcela_unidade_indexador_to_string = $contrato_pagamento_parcela_unidade_indexador_to_string;
        }

        $this->vdata['contrato_pagamento_parcela_unidade_indexador_to_string'] = $this->contrato_pagamento_parcela_unidade_indexador_to_string;
    }

    public function get_contrato_pagamento_parcela_unidade_indexador_to_string()
    {
        if(!empty($this->contrato_pagamento_parcela_unidade_indexador_to_string))
        {
            return $this->contrato_pagamento_parcela_unidade_indexador_to_string;
        }
    
        $values = ContratoPagamentoParcela::where('modificacao_user_id', '=', $this->id)->getIndexedArray('unidade_indexador_id','{unidade_indexador->nome}');
        return implode(', ', $values);
    }

    public function set_contrato_pagamento_parcela_contrato_indexador_to_string($contrato_pagamento_parcela_contrato_indexador_to_string)
    {
        if(is_array($contrato_pagamento_parcela_contrato_indexador_to_string))
        {
            $values = ContratoPagamentoIndexador::where('id', 'in', $contrato_pagamento_parcela_contrato_indexador_to_string)->getIndexedArray('nome', 'nome');
            $this->contrato_pagamento_parcela_contrato_indexador_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_pagamento_parcela_contrato_indexador_to_string = $contrato_pagamento_parcela_contrato_indexador_to_string;
        }

        $this->vdata['contrato_pagamento_parcela_contrato_indexador_to_string'] = $this->contrato_pagamento_parcela_contrato_indexador_to_string;
    }

    public function get_contrato_pagamento_parcela_contrato_indexador_to_string()
    {
        if(!empty($this->contrato_pagamento_parcela_contrato_indexador_to_string))
        {
            return $this->contrato_pagamento_parcela_contrato_indexador_to_string;
        }
    
        $values = ContratoPagamentoParcela::where('modificacao_user_id', '=', $this->id)->getIndexedArray('contrato_indexador_id','{contrato_indexador->nome}');
        return implode(', ', $values);
    }

    public function set_contrato_pagamento_parcela_criacao_user_to_string($contrato_pagamento_parcela_criacao_user_to_string)
    {
        if(is_array($contrato_pagamento_parcela_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $contrato_pagamento_parcela_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->contrato_pagamento_parcela_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_pagamento_parcela_criacao_user_to_string = $contrato_pagamento_parcela_criacao_user_to_string;
        }

        $this->vdata['contrato_pagamento_parcela_criacao_user_to_string'] = $this->contrato_pagamento_parcela_criacao_user_to_string;
    }

    public function get_contrato_pagamento_parcela_criacao_user_to_string()
    {
        if(!empty($this->contrato_pagamento_parcela_criacao_user_to_string))
        {
            return $this->contrato_pagamento_parcela_criacao_user_to_string;
        }
    
        $values = ContratoPagamentoParcela::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_contrato_pagamento_parcela_modificacao_user_to_string($contrato_pagamento_parcela_modificacao_user_to_string)
    {
        if(is_array($contrato_pagamento_parcela_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $contrato_pagamento_parcela_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->contrato_pagamento_parcela_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_pagamento_parcela_modificacao_user_to_string = $contrato_pagamento_parcela_modificacao_user_to_string;
        }

        $this->vdata['contrato_pagamento_parcela_modificacao_user_to_string'] = $this->contrato_pagamento_parcela_modificacao_user_to_string;
    }

    public function get_contrato_pagamento_parcela_modificacao_user_to_string()
    {
        if(!empty($this->contrato_pagamento_parcela_modificacao_user_to_string))
        {
            return $this->contrato_pagamento_parcela_modificacao_user_to_string;
        }
    
        $values = ContratoPagamentoParcela::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_contrato_processo_contrato_to_string($contrato_processo_contrato_to_string)
    {
        if(is_array($contrato_processo_contrato_to_string))
        {
            $values = Contrato::where('id', 'in', $contrato_processo_contrato_to_string)->getIndexedArray('objeto', 'objeto');
            $this->contrato_processo_contrato_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_processo_contrato_to_string = $contrato_processo_contrato_to_string;
        }

        $this->vdata['contrato_processo_contrato_to_string'] = $this->contrato_processo_contrato_to_string;
    }

    public function get_contrato_processo_contrato_to_string()
    {
        if(!empty($this->contrato_processo_contrato_to_string))
        {
            return $this->contrato_processo_contrato_to_string;
        }
    
        $values = ContratoProcesso::where('modificacao_user_id', '=', $this->id)->getIndexedArray('contrato_id','{contrato->objeto}');
        return implode(', ', $values);
    }

    public function set_contrato_processo_processo_to_string($contrato_processo_processo_to_string)
    {
        if(is_array($contrato_processo_processo_to_string))
        {
            $values = Processo::where('id', 'in', $contrato_processo_processo_to_string)->getIndexedArray('numero_cnj_numero', 'numero_cnj_numero');
            $this->contrato_processo_processo_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_processo_processo_to_string = $contrato_processo_processo_to_string;
        }

        $this->vdata['contrato_processo_processo_to_string'] = $this->contrato_processo_processo_to_string;
    }

    public function get_contrato_processo_processo_to_string()
    {
        if(!empty($this->contrato_processo_processo_to_string))
        {
            return $this->contrato_processo_processo_to_string;
        }
    
        $values = ContratoProcesso::where('modificacao_user_id', '=', $this->id)->getIndexedArray('processo_id','{processo->numero_cnj_numero}');
        return implode(', ', $values);
    }

    public function set_contrato_processo_criacao_user_to_string($contrato_processo_criacao_user_to_string)
    {
        if(is_array($contrato_processo_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $contrato_processo_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->contrato_processo_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_processo_criacao_user_to_string = $contrato_processo_criacao_user_to_string;
        }

        $this->vdata['contrato_processo_criacao_user_to_string'] = $this->contrato_processo_criacao_user_to_string;
    }

    public function get_contrato_processo_criacao_user_to_string()
    {
        if(!empty($this->contrato_processo_criacao_user_to_string))
        {
            return $this->contrato_processo_criacao_user_to_string;
        }
    
        $values = ContratoProcesso::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_contrato_processo_modificacao_user_to_string($contrato_processo_modificacao_user_to_string)
    {
        if(is_array($contrato_processo_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $contrato_processo_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->contrato_processo_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_processo_modificacao_user_to_string = $contrato_processo_modificacao_user_to_string;
        }

        $this->vdata['contrato_processo_modificacao_user_to_string'] = $this->contrato_processo_modificacao_user_to_string;
    }

    public function get_contrato_processo_modificacao_user_to_string()
    {
        if(!empty($this->contrato_processo_modificacao_user_to_string))
        {
            return $this->contrato_processo_modificacao_user_to_string;
        }
    
        $values = ContratoProcesso::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_convidado_agendamento_to_string($convidado_agendamento_to_string)
    {
        if(is_array($convidado_agendamento_to_string))
        {
            $values = Agendamento::where('id', 'in', $convidado_agendamento_to_string)->getIndexedArray('id', 'id');
            $this->convidado_agendamento_to_string = implode(', ', $values);
        }
        else
        {
            $this->convidado_agendamento_to_string = $convidado_agendamento_to_string;
        }

        $this->vdata['convidado_agendamento_to_string'] = $this->convidado_agendamento_to_string;
    }

    public function get_convidado_agendamento_to_string()
    {
        if(!empty($this->convidado_agendamento_to_string))
        {
            return $this->convidado_agendamento_to_string;
        }
    
        $values = Convidado::where('modificacao_user_id', '=', $this->id)->getIndexedArray('agendamento_id','{agendamento->id}');
        return implode(', ', $values);
    }

    public function set_convidado_agenda_to_string($convidado_agenda_to_string)
    {
        if(is_array($convidado_agenda_to_string))
        {
            $values = Agenda::where('id', 'in', $convidado_agenda_to_string)->getIndexedArray('nome', 'nome');
            $this->convidado_agenda_to_string = implode(', ', $values);
        }
        else
        {
            $this->convidado_agenda_to_string = $convidado_agenda_to_string;
        }

        $this->vdata['convidado_agenda_to_string'] = $this->convidado_agenda_to_string;
    }

    public function get_convidado_agenda_to_string()
    {
        if(!empty($this->convidado_agenda_to_string))
        {
            return $this->convidado_agenda_to_string;
        }
    
        $values = Convidado::where('modificacao_user_id', '=', $this->id)->getIndexedArray('agenda_id','{agenda->nome}');
        return implode(', ', $values);
    }

    public function set_convidado_criacao_user_to_string($convidado_criacao_user_to_string)
    {
        if(is_array($convidado_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $convidado_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->convidado_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->convidado_criacao_user_to_string = $convidado_criacao_user_to_string;
        }

        $this->vdata['convidado_criacao_user_to_string'] = $this->convidado_criacao_user_to_string;
    }

    public function get_convidado_criacao_user_to_string()
    {
        if(!empty($this->convidado_criacao_user_to_string))
        {
            return $this->convidado_criacao_user_to_string;
        }
    
        $values = Convidado::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_convidado_modificacao_user_to_string($convidado_modificacao_user_to_string)
    {
        if(is_array($convidado_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $convidado_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->convidado_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->convidado_modificacao_user_to_string = $convidado_modificacao_user_to_string;
        }

        $this->vdata['convidado_modificacao_user_to_string'] = $this->convidado_modificacao_user_to_string;
    }

    public function get_convidado_modificacao_user_to_string()
    {
        if(!empty($this->convidado_modificacao_user_to_string))
        {
            return $this->convidado_modificacao_user_to_string;
        }
    
        $values = Convidado::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_convidado_compromisso_compromisso_to_string($convidado_compromisso_compromisso_to_string)
    {
        if(is_array($convidado_compromisso_compromisso_to_string))
        {
            $values = Compromisso::where('id', 'in', $convidado_compromisso_compromisso_to_string)->getIndexedArray('id', 'id');
            $this->convidado_compromisso_compromisso_to_string = implode(', ', $values);
        }
        else
        {
            $this->convidado_compromisso_compromisso_to_string = $convidado_compromisso_compromisso_to_string;
        }

        $this->vdata['convidado_compromisso_compromisso_to_string'] = $this->convidado_compromisso_compromisso_to_string;
    }

    public function get_convidado_compromisso_compromisso_to_string()
    {
        if(!empty($this->convidado_compromisso_compromisso_to_string))
        {
            return $this->convidado_compromisso_compromisso_to_string;
        }
    
        $values = ConvidadoCompromisso::where('modificacao_user_id', '=', $this->id)->getIndexedArray('compromisso_id','{compromisso->id}');
        return implode(', ', $values);
    }

    public function set_convidado_compromisso_agenda_to_string($convidado_compromisso_agenda_to_string)
    {
        if(is_array($convidado_compromisso_agenda_to_string))
        {
            $values = Agenda::where('id', 'in', $convidado_compromisso_agenda_to_string)->getIndexedArray('nome', 'nome');
            $this->convidado_compromisso_agenda_to_string = implode(', ', $values);
        }
        else
        {
            $this->convidado_compromisso_agenda_to_string = $convidado_compromisso_agenda_to_string;
        }

        $this->vdata['convidado_compromisso_agenda_to_string'] = $this->convidado_compromisso_agenda_to_string;
    }

    public function get_convidado_compromisso_agenda_to_string()
    {
        if(!empty($this->convidado_compromisso_agenda_to_string))
        {
            return $this->convidado_compromisso_agenda_to_string;
        }
    
        $values = ConvidadoCompromisso::where('modificacao_user_id', '=', $this->id)->getIndexedArray('agenda_id','{agenda->nome}');
        return implode(', ', $values);
    }

    public function set_convidado_compromisso_criacao_user_to_string($convidado_compromisso_criacao_user_to_string)
    {
        if(is_array($convidado_compromisso_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $convidado_compromisso_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->convidado_compromisso_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->convidado_compromisso_criacao_user_to_string = $convidado_compromisso_criacao_user_to_string;
        }

        $this->vdata['convidado_compromisso_criacao_user_to_string'] = $this->convidado_compromisso_criacao_user_to_string;
    }

    public function get_convidado_compromisso_criacao_user_to_string()
    {
        if(!empty($this->convidado_compromisso_criacao_user_to_string))
        {
            return $this->convidado_compromisso_criacao_user_to_string;
        }
    
        $values = ConvidadoCompromisso::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_convidado_compromisso_modificacao_user_to_string($convidado_compromisso_modificacao_user_to_string)
    {
        if(is_array($convidado_compromisso_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $convidado_compromisso_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->convidado_compromisso_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->convidado_compromisso_modificacao_user_to_string = $convidado_compromisso_modificacao_user_to_string;
        }

        $this->vdata['convidado_compromisso_modificacao_user_to_string'] = $this->convidado_compromisso_modificacao_user_to_string;
    }

    public function get_convidado_compromisso_modificacao_user_to_string()
    {
        if(!empty($this->convidado_compromisso_modificacao_user_to_string))
        {
            return $this->convidado_compromisso_modificacao_user_to_string;
        }
    
        $values = ConvidadoCompromisso::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_documento_atendimento_to_string($documento_atendimento_to_string)
    {
        if(is_array($documento_atendimento_to_string))
        {
            $values = Atendimento::where('id', 'in', $documento_atendimento_to_string)->getIndexedArray('id', 'id');
            $this->documento_atendimento_to_string = implode(', ', $values);
        }
        else
        {
            $this->documento_atendimento_to_string = $documento_atendimento_to_string;
        }

        $this->vdata['documento_atendimento_to_string'] = $this->documento_atendimento_to_string;
    }

    public function get_documento_atendimento_to_string()
    {
        if(!empty($this->documento_atendimento_to_string))
        {
            return $this->documento_atendimento_to_string;
        }
    
        $values = Documento::where('modificacao_user_id', '=', $this->id)->getIndexedArray('atendimento_id','{atendimento->id}');
        return implode(', ', $values);
    }

    public function set_documento_modelo_documento_to_string($documento_modelo_documento_to_string)
    {
        if(is_array($documento_modelo_documento_to_string))
        {
            $values = ModeloDocumento::where('id', 'in', $documento_modelo_documento_to_string)->getIndexedArray('nome', 'nome');
            $this->documento_modelo_documento_to_string = implode(', ', $values);
        }
        else
        {
            $this->documento_modelo_documento_to_string = $documento_modelo_documento_to_string;
        }

        $this->vdata['documento_modelo_documento_to_string'] = $this->documento_modelo_documento_to_string;
    }

    public function get_documento_modelo_documento_to_string()
    {
        if(!empty($this->documento_modelo_documento_to_string))
        {
            return $this->documento_modelo_documento_to_string;
        }
    
        $values = Documento::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modelo_documento_id','{modelo_documento->nome}');
        return implode(', ', $values);
    }

    public function set_documento_procedimento_to_string($documento_procedimento_to_string)
    {
        if(is_array($documento_procedimento_to_string))
        {
            $values = Procedimento::where('id', 'in', $documento_procedimento_to_string)->getIndexedArray('nome', 'nome');
            $this->documento_procedimento_to_string = implode(', ', $values);
        }
        else
        {
            $this->documento_procedimento_to_string = $documento_procedimento_to_string;
        }

        $this->vdata['documento_procedimento_to_string'] = $this->documento_procedimento_to_string;
    }

    public function get_documento_procedimento_to_string()
    {
        if(!empty($this->documento_procedimento_to_string))
        {
            return $this->documento_procedimento_to_string;
        }
    
        $values = Documento::where('modificacao_user_id', '=', $this->id)->getIndexedArray('procedimento_id','{procedimento->nome}');
        return implode(', ', $values);
    }

    public function set_documento_criacao_user_to_string($documento_criacao_user_to_string)
    {
        if(is_array($documento_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $documento_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->documento_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->documento_criacao_user_to_string = $documento_criacao_user_to_string;
        }

        $this->vdata['documento_criacao_user_to_string'] = $this->documento_criacao_user_to_string;
    }

    public function get_documento_criacao_user_to_string()
    {
        if(!empty($this->documento_criacao_user_to_string))
        {
            return $this->documento_criacao_user_to_string;
        }
    
        $values = Documento::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_documento_modificacao_user_to_string($documento_modificacao_user_to_string)
    {
        if(is_array($documento_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $documento_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->documento_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->documento_modificacao_user_to_string = $documento_modificacao_user_to_string;
        }

        $this->vdata['documento_modificacao_user_to_string'] = $this->documento_modificacao_user_to_string;
    }

    public function get_documento_modificacao_user_to_string()
    {
        if(!empty($this->documento_modificacao_user_to_string))
        {
            return $this->documento_modificacao_user_to_string;
        }
    
        $values = Documento::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_envolvimento_tipo_processo_to_string($envolvimento_tipo_processo_to_string)
    {
        if(is_array($envolvimento_tipo_processo_to_string))
        {
            $values = TipoProcesso::where('id', 'in', $envolvimento_tipo_processo_to_string)->getIndexedArray('nome', 'nome');
            $this->envolvimento_tipo_processo_to_string = implode(', ', $values);
        }
        else
        {
            $this->envolvimento_tipo_processo_to_string = $envolvimento_tipo_processo_to_string;
        }

        $this->vdata['envolvimento_tipo_processo_to_string'] = $this->envolvimento_tipo_processo_to_string;
    }

    public function get_envolvimento_tipo_processo_to_string()
    {
        if(!empty($this->envolvimento_tipo_processo_to_string))
        {
            return $this->envolvimento_tipo_processo_to_string;
        }
    
        $values = Envolvimento::where('modificacao_user_id', '=', $this->id)->getIndexedArray('tipo_processo_id','{tipo_processo->nome}');
        return implode(', ', $values);
    }

    public function set_envolvimento_criacao_user_to_string($envolvimento_criacao_user_to_string)
    {
        if(is_array($envolvimento_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $envolvimento_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->envolvimento_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->envolvimento_criacao_user_to_string = $envolvimento_criacao_user_to_string;
        }

        $this->vdata['envolvimento_criacao_user_to_string'] = $this->envolvimento_criacao_user_to_string;
    }

    public function get_envolvimento_criacao_user_to_string()
    {
        if(!empty($this->envolvimento_criacao_user_to_string))
        {
            return $this->envolvimento_criacao_user_to_string;
        }
    
        $values = Envolvimento::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_envolvimento_modificacao_user_to_string($envolvimento_modificacao_user_to_string)
    {
        if(is_array($envolvimento_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $envolvimento_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->envolvimento_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->envolvimento_modificacao_user_to_string = $envolvimento_modificacao_user_to_string;
        }

        $this->vdata['envolvimento_modificacao_user_to_string'] = $this->envolvimento_modificacao_user_to_string;
    }

    public function get_envolvimento_modificacao_user_to_string()
    {
        if(!empty($this->envolvimento_modificacao_user_to_string))
        {
            return $this->envolvimento_modificacao_user_to_string;
        }
    
        $values = Envolvimento::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_escritorio_system_unit_to_string($escritorio_system_unit_to_string)
    {
        if(is_array($escritorio_system_unit_to_string))
        {
            $values = SystemUnit::where('id', 'in', $escritorio_system_unit_to_string)->getIndexedArray('name', 'name');
            $this->escritorio_system_unit_to_string = implode(', ', $values);
        }
        else
        {
            $this->escritorio_system_unit_to_string = $escritorio_system_unit_to_string;
        }

        $this->vdata['escritorio_system_unit_to_string'] = $this->escritorio_system_unit_to_string;
    }

    public function get_escritorio_system_unit_to_string()
    {
        if(!empty($this->escritorio_system_unit_to_string))
        {
            return $this->escritorio_system_unit_to_string;
        }
    
        $values = Escritorio::where('criacao_user_id', '=', $this->id)->getIndexedArray('system_unit_id','{system_unit->name}');
        return implode(', ', $values);
    }

    public function set_escritorio_cidade_to_string($escritorio_cidade_to_string)
    {
        if(is_array($escritorio_cidade_to_string))
        {
            $values = Cidade::where('id', 'in', $escritorio_cidade_to_string)->getIndexedArray('nome', 'nome');
            $this->escritorio_cidade_to_string = implode(', ', $values);
        }
        else
        {
            $this->escritorio_cidade_to_string = $escritorio_cidade_to_string;
        }

        $this->vdata['escritorio_cidade_to_string'] = $this->escritorio_cidade_to_string;
    }

    public function get_escritorio_cidade_to_string()
    {
        if(!empty($this->escritorio_cidade_to_string))
        {
            return $this->escritorio_cidade_to_string;
        }
    
        $values = Escritorio::where('criacao_user_id', '=', $this->id)->getIndexedArray('cidade_id','{cidade->nome}');
        return implode(', ', $values);
    }

    public function set_escritorio_criacao_user_to_string($escritorio_criacao_user_to_string)
    {
        if(is_array($escritorio_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $escritorio_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->escritorio_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->escritorio_criacao_user_to_string = $escritorio_criacao_user_to_string;
        }

        $this->vdata['escritorio_criacao_user_to_string'] = $this->escritorio_criacao_user_to_string;
    }

    public function get_escritorio_criacao_user_to_string()
    {
        if(!empty($this->escritorio_criacao_user_to_string))
        {
            return $this->escritorio_criacao_user_to_string;
        }
    
        $values = Escritorio::where('criacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_escritorio_modificacao_user_to_string($escritorio_modificacao_user_to_string)
    {
        if(is_array($escritorio_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $escritorio_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->escritorio_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->escritorio_modificacao_user_to_string = $escritorio_modificacao_user_to_string;
        }

        $this->vdata['escritorio_modificacao_user_to_string'] = $this->escritorio_modificacao_user_to_string;
    }

    public function get_escritorio_modificacao_user_to_string()
    {
        if(!empty($this->escritorio_modificacao_user_to_string))
        {
            return $this->escritorio_modificacao_user_to_string;
        }
    
        $values = Escritorio::where('criacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_especialidade_criacao_user_to_string($especialidade_criacao_user_to_string)
    {
        if(is_array($especialidade_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $especialidade_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->especialidade_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->especialidade_criacao_user_to_string = $especialidade_criacao_user_to_string;
        }

        $this->vdata['especialidade_criacao_user_to_string'] = $this->especialidade_criacao_user_to_string;
    }

    public function get_especialidade_criacao_user_to_string()
    {
        if(!empty($this->especialidade_criacao_user_to_string))
        {
            return $this->especialidade_criacao_user_to_string;
        }
    
        $values = Especialidade::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_especialidade_modificacao_user_to_string($especialidade_modificacao_user_to_string)
    {
        if(is_array($especialidade_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $especialidade_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->especialidade_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->especialidade_modificacao_user_to_string = $especialidade_modificacao_user_to_string;
        }

        $this->vdata['especialidade_modificacao_user_to_string'] = $this->especialidade_modificacao_user_to_string;
    }

    public function get_especialidade_modificacao_user_to_string()
    {
        if(!empty($this->especialidade_modificacao_user_to_string))
        {
            return $this->especialidade_modificacao_user_to_string;
        }
    
        $values = Especialidade::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_estado_criacao_user_to_string($estado_criacao_user_to_string)
    {
        if(is_array($estado_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $estado_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->estado_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->estado_criacao_user_to_string = $estado_criacao_user_to_string;
        }

        $this->vdata['estado_criacao_user_to_string'] = $this->estado_criacao_user_to_string;
    }

    public function get_estado_criacao_user_to_string()
    {
        if(!empty($this->estado_criacao_user_to_string))
        {
            return $this->estado_criacao_user_to_string;
        }
    
        $values = Estado::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_estado_modificacao_user_to_string($estado_modificacao_user_to_string)
    {
        if(is_array($estado_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $estado_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->estado_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->estado_modificacao_user_to_string = $estado_modificacao_user_to_string;
        }

        $this->vdata['estado_modificacao_user_to_string'] = $this->estado_modificacao_user_to_string;
    }

    public function get_estado_modificacao_user_to_string()
    {
        if(!empty($this->estado_modificacao_user_to_string))
        {
            return $this->estado_modificacao_user_to_string;
        }
    
        $values = Estado::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_estado_agenda_modificacao_user_to_string($estado_agenda_modificacao_user_to_string)
    {
        if(is_array($estado_agenda_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $estado_agenda_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->estado_agenda_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->estado_agenda_modificacao_user_to_string = $estado_agenda_modificacao_user_to_string;
        }

        $this->vdata['estado_agenda_modificacao_user_to_string'] = $this->estado_agenda_modificacao_user_to_string;
    }

    public function get_estado_agenda_modificacao_user_to_string()
    {
        if(!empty($this->estado_agenda_modificacao_user_to_string))
        {
            return $this->estado_agenda_modificacao_user_to_string;
        }
    
        $values = EstadoAgenda::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_estado_agendamento_agendamento_to_string($estado_agendamento_agendamento_to_string)
    {
        if(is_array($estado_agendamento_agendamento_to_string))
        {
            $values = Agendamento::where('id', 'in', $estado_agendamento_agendamento_to_string)->getIndexedArray('id', 'id');
            $this->estado_agendamento_agendamento_to_string = implode(', ', $values);
        }
        else
        {
            $this->estado_agendamento_agendamento_to_string = $estado_agendamento_agendamento_to_string;
        }

        $this->vdata['estado_agendamento_agendamento_to_string'] = $this->estado_agendamento_agendamento_to_string;
    }

    public function get_estado_agendamento_agendamento_to_string()
    {
        if(!empty($this->estado_agendamento_agendamento_to_string))
        {
            return $this->estado_agendamento_agendamento_to_string;
        }
    
        $values = EstadoAgendamento::where('system_users_id', '=', $this->id)->getIndexedArray('agendamento_id','{agendamento->id}');
        return implode(', ', $values);
    }

    public function set_estado_agendamento_estado_agenda_to_string($estado_agendamento_estado_agenda_to_string)
    {
        if(is_array($estado_agendamento_estado_agenda_to_string))
        {
            $values = EstadoAgenda::where('id', 'in', $estado_agendamento_estado_agenda_to_string)->getIndexedArray('nome', 'nome');
            $this->estado_agendamento_estado_agenda_to_string = implode(', ', $values);
        }
        else
        {
            $this->estado_agendamento_estado_agenda_to_string = $estado_agendamento_estado_agenda_to_string;
        }

        $this->vdata['estado_agendamento_estado_agenda_to_string'] = $this->estado_agendamento_estado_agenda_to_string;
    }

    public function get_estado_agendamento_estado_agenda_to_string()
    {
        if(!empty($this->estado_agendamento_estado_agenda_to_string))
        {
            return $this->estado_agendamento_estado_agenda_to_string;
        }
    
        $values = EstadoAgendamento::where('system_users_id', '=', $this->id)->getIndexedArray('estado_agenda_id','{estado_agenda->nome}');
        return implode(', ', $values);
    }

    public function set_estado_agendamento_system_users_to_string($estado_agendamento_system_users_to_string)
    {
        if(is_array($estado_agendamento_system_users_to_string))
        {
            $values = SystemUsers::where('id', 'in', $estado_agendamento_system_users_to_string)->getIndexedArray('name', 'name');
            $this->estado_agendamento_system_users_to_string = implode(', ', $values);
        }
        else
        {
            $this->estado_agendamento_system_users_to_string = $estado_agendamento_system_users_to_string;
        }

        $this->vdata['estado_agendamento_system_users_to_string'] = $this->estado_agendamento_system_users_to_string;
    }

    public function get_estado_agendamento_system_users_to_string()
    {
        if(!empty($this->estado_agendamento_system_users_to_string))
        {
            return $this->estado_agendamento_system_users_to_string;
        }
    
        $values = EstadoAgendamento::where('system_users_id', '=', $this->id)->getIndexedArray('system_users_id','{system_users->name}');
        return implode(', ', $values);
    }

    public function set_extrato_escritorio_to_string($extrato_escritorio_to_string)
    {
        if(is_array($extrato_escritorio_to_string))
        {
            $values = Escritorio::where('id', 'in', $extrato_escritorio_to_string)->getIndexedArray('nome', 'nome');
            $this->extrato_escritorio_to_string = implode(', ', $values);
        }
        else
        {
            $this->extrato_escritorio_to_string = $extrato_escritorio_to_string;
        }

        $this->vdata['extrato_escritorio_to_string'] = $this->extrato_escritorio_to_string;
    }

    public function get_extrato_escritorio_to_string()
    {
        if(!empty($this->extrato_escritorio_to_string))
        {
            return $this->extrato_escritorio_to_string;
        }
    
        $values = Extrato::where('modificacao_user_id', '=', $this->id)->getIndexedArray('escritorio_id','{escritorio->nome}');
        return implode(', ', $values);
    }

    public function set_extrato_conta_caixa_to_string($extrato_conta_caixa_to_string)
    {
        if(is_array($extrato_conta_caixa_to_string))
        {
            $values = ContaCaixa::where('id', 'in', $extrato_conta_caixa_to_string)->getIndexedArray('nome', 'nome');
            $this->extrato_conta_caixa_to_string = implode(', ', $values);
        }
        else
        {
            $this->extrato_conta_caixa_to_string = $extrato_conta_caixa_to_string;
        }

        $this->vdata['extrato_conta_caixa_to_string'] = $this->extrato_conta_caixa_to_string;
    }

    public function get_extrato_conta_caixa_to_string()
    {
        if(!empty($this->extrato_conta_caixa_to_string))
        {
            return $this->extrato_conta_caixa_to_string;
        }
    
        $values = Extrato::where('modificacao_user_id', '=', $this->id)->getIndexedArray('conta_caixa_id','{conta_caixa->nome}');
        return implode(', ', $values);
    }

    public function set_extrato_lancamento_to_string($extrato_lancamento_to_string)
    {
        if(is_array($extrato_lancamento_to_string))
        {
            $values = Lancamento::where('id', 'in', $extrato_lancamento_to_string)->getIndexedArray('id', 'id');
            $this->extrato_lancamento_to_string = implode(', ', $values);
        }
        else
        {
            $this->extrato_lancamento_to_string = $extrato_lancamento_to_string;
        }

        $this->vdata['extrato_lancamento_to_string'] = $this->extrato_lancamento_to_string;
    }

    public function get_extrato_lancamento_to_string()
    {
        if(!empty($this->extrato_lancamento_to_string))
        {
            return $this->extrato_lancamento_to_string;
        }
    
        $values = Extrato::where('modificacao_user_id', '=', $this->id)->getIndexedArray('lancamento_id','{lancamento->id}');
        return implode(', ', $values);
    }

    public function set_extrato_categoria_conta_to_string($extrato_categoria_conta_to_string)
    {
        if(is_array($extrato_categoria_conta_to_string))
        {
            $values = CategoriaConta::where('id', 'in', $extrato_categoria_conta_to_string)->getIndexedArray('nome', 'nome');
            $this->extrato_categoria_conta_to_string = implode(', ', $values);
        }
        else
        {
            $this->extrato_categoria_conta_to_string = $extrato_categoria_conta_to_string;
        }

        $this->vdata['extrato_categoria_conta_to_string'] = $this->extrato_categoria_conta_to_string;
    }

    public function get_extrato_categoria_conta_to_string()
    {
        if(!empty($this->extrato_categoria_conta_to_string))
        {
            return $this->extrato_categoria_conta_to_string;
        }
    
        $values = Extrato::where('modificacao_user_id', '=', $this->id)->getIndexedArray('categoria_conta_id','{categoria_conta->nome}');
        return implode(', ', $values);
    }

    public function set_extrato_tipo_extrato_to_string($extrato_tipo_extrato_to_string)
    {
        if(is_array($extrato_tipo_extrato_to_string))
        {
            $values = TipoExtrato::where('id', 'in', $extrato_tipo_extrato_to_string)->getIndexedArray('nome', 'nome');
            $this->extrato_tipo_extrato_to_string = implode(', ', $values);
        }
        else
        {
            $this->extrato_tipo_extrato_to_string = $extrato_tipo_extrato_to_string;
        }

        $this->vdata['extrato_tipo_extrato_to_string'] = $this->extrato_tipo_extrato_to_string;
    }

    public function get_extrato_tipo_extrato_to_string()
    {
        if(!empty($this->extrato_tipo_extrato_to_string))
        {
            return $this->extrato_tipo_extrato_to_string;
        }
    
        $values = Extrato::where('modificacao_user_id', '=', $this->id)->getIndexedArray('tipo_extrato_id','{tipo_extrato->nome}');
        return implode(', ', $values);
    }

    public function set_extrato_transferencia_conta_caixa_to_string($extrato_transferencia_conta_caixa_to_string)
    {
        if(is_array($extrato_transferencia_conta_caixa_to_string))
        {
            $values = ContaCaixa::where('id', 'in', $extrato_transferencia_conta_caixa_to_string)->getIndexedArray('nome', 'nome');
            $this->extrato_transferencia_conta_caixa_to_string = implode(', ', $values);
        }
        else
        {
            $this->extrato_transferencia_conta_caixa_to_string = $extrato_transferencia_conta_caixa_to_string;
        }

        $this->vdata['extrato_transferencia_conta_caixa_to_string'] = $this->extrato_transferencia_conta_caixa_to_string;
    }

    public function get_extrato_transferencia_conta_caixa_to_string()
    {
        if(!empty($this->extrato_transferencia_conta_caixa_to_string))
        {
            return $this->extrato_transferencia_conta_caixa_to_string;
        }
    
        $values = Extrato::where('modificacao_user_id', '=', $this->id)->getIndexedArray('transferencia_conta_caixa_id','{transferencia_conta_caixa->nome}');
        return implode(', ', $values);
    }

    public function set_extrato_criacao_user_to_string($extrato_criacao_user_to_string)
    {
        if(is_array($extrato_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $extrato_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->extrato_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->extrato_criacao_user_to_string = $extrato_criacao_user_to_string;
        }

        $this->vdata['extrato_criacao_user_to_string'] = $this->extrato_criacao_user_to_string;
    }

    public function get_extrato_criacao_user_to_string()
    {
        if(!empty($this->extrato_criacao_user_to_string))
        {
            return $this->extrato_criacao_user_to_string;
        }
    
        $values = Extrato::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_extrato_modificacao_user_to_string($extrato_modificacao_user_to_string)
    {
        if(is_array($extrato_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $extrato_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->extrato_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->extrato_modificacao_user_to_string = $extrato_modificacao_user_to_string;
        }

        $this->vdata['extrato_modificacao_user_to_string'] = $this->extrato_modificacao_user_to_string;
    }

    public function get_extrato_modificacao_user_to_string()
    {
        if(!empty($this->extrato_modificacao_user_to_string))
        {
            return $this->extrato_modificacao_user_to_string;
        }
    
        $values = Extrato::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_formulario_criacao_user_to_string($formulario_criacao_user_to_string)
    {
        if(is_array($formulario_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $formulario_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->formulario_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->formulario_criacao_user_to_string = $formulario_criacao_user_to_string;
        }

        $this->vdata['formulario_criacao_user_to_string'] = $this->formulario_criacao_user_to_string;
    }

    public function get_formulario_criacao_user_to_string()
    {
        if(!empty($this->formulario_criacao_user_to_string))
        {
            return $this->formulario_criacao_user_to_string;
        }
    
        $values = Formulario::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_formulario_modificacao_user_to_string($formulario_modificacao_user_to_string)
    {
        if(is_array($formulario_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $formulario_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->formulario_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->formulario_modificacao_user_to_string = $formulario_modificacao_user_to_string;
        }

        $this->vdata['formulario_modificacao_user_to_string'] = $this->formulario_modificacao_user_to_string;
    }

    public function get_formulario_modificacao_user_to_string()
    {
        if(!empty($this->formulario_modificacao_user_to_string))
        {
            return $this->formulario_modificacao_user_to_string;
        }
    
        $values = Formulario::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_foro_criacao_user_to_string($foro_criacao_user_to_string)
    {
        if(is_array($foro_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $foro_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->foro_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->foro_criacao_user_to_string = $foro_criacao_user_to_string;
        }

        $this->vdata['foro_criacao_user_to_string'] = $this->foro_criacao_user_to_string;
    }

    public function get_foro_criacao_user_to_string()
    {
        if(!empty($this->foro_criacao_user_to_string))
        {
            return $this->foro_criacao_user_to_string;
        }
    
        $values = Foro::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_foro_modificacao_user_to_string($foro_modificacao_user_to_string)
    {
        if(is_array($foro_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $foro_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->foro_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->foro_modificacao_user_to_string = $foro_modificacao_user_to_string;
        }

        $this->vdata['foro_modificacao_user_to_string'] = $this->foro_modificacao_user_to_string;
    }

    public function get_foro_modificacao_user_to_string()
    {
        if(!empty($this->foro_modificacao_user_to_string))
        {
            return $this->foro_modificacao_user_to_string;
        }
    
        $values = Foro::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_grupo_criacao_user_to_string($grupo_criacao_user_to_string)
    {
        if(is_array($grupo_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $grupo_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->grupo_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->grupo_criacao_user_to_string = $grupo_criacao_user_to_string;
        }

        $this->vdata['grupo_criacao_user_to_string'] = $this->grupo_criacao_user_to_string;
    }

    public function get_grupo_criacao_user_to_string()
    {
        if(!empty($this->grupo_criacao_user_to_string))
        {
            return $this->grupo_criacao_user_to_string;
        }
    
        $values = Grupo::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_grupo_modificacao_user_to_string($grupo_modificacao_user_to_string)
    {
        if(is_array($grupo_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $grupo_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->grupo_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->grupo_modificacao_user_to_string = $grupo_modificacao_user_to_string;
        }

        $this->vdata['grupo_modificacao_user_to_string'] = $this->grupo_modificacao_user_to_string;
    }

    public function get_grupo_modificacao_user_to_string()
    {
        if(!empty($this->grupo_modificacao_user_to_string))
        {
            return $this->grupo_modificacao_user_to_string;
        }
    
        $values = Grupo::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_jornal_criacao_user_to_string($jornal_criacao_user_to_string)
    {
        if(is_array($jornal_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $jornal_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->jornal_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->jornal_criacao_user_to_string = $jornal_criacao_user_to_string;
        }

        $this->vdata['jornal_criacao_user_to_string'] = $this->jornal_criacao_user_to_string;
    }

    public function get_jornal_criacao_user_to_string()
    {
        if(!empty($this->jornal_criacao_user_to_string))
        {
            return $this->jornal_criacao_user_to_string;
        }
    
        $values = Jornal::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_jornal_modificacao_user_to_string($jornal_modificacao_user_to_string)
    {
        if(is_array($jornal_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $jornal_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->jornal_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->jornal_modificacao_user_to_string = $jornal_modificacao_user_to_string;
        }

        $this->vdata['jornal_modificacao_user_to_string'] = $this->jornal_modificacao_user_to_string;
    }

    public function get_jornal_modificacao_user_to_string()
    {
        if(!empty($this->jornal_modificacao_user_to_string))
        {
            return $this->jornal_modificacao_user_to_string;
        }
    
        $values = Jornal::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_mensagem_agendamento_to_string($mensagem_agendamento_to_string)
    {
        if(is_array($mensagem_agendamento_to_string))
        {
            $values = Agendamento::where('id', 'in', $mensagem_agendamento_to_string)->getIndexedArray('id', 'id');
            $this->mensagem_agendamento_to_string = implode(', ', $values);
        }
        else
        {
            $this->mensagem_agendamento_to_string = $mensagem_agendamento_to_string;
        }

        $this->vdata['mensagem_agendamento_to_string'] = $this->mensagem_agendamento_to_string;
    }

    public function get_mensagem_agendamento_to_string()
    {
        if(!empty($this->mensagem_agendamento_to_string))
        {
            return $this->mensagem_agendamento_to_string;
        }
    
        $values = Mensagem::where('system_user_id', '=', $this->id)->getIndexedArray('agendamento_id','{agendamento->id}');
        return implode(', ', $values);
    }

    public function set_mensagem_template_escritorio_to_string($mensagem_template_escritorio_to_string)
    {
        if(is_array($mensagem_template_escritorio_to_string))
        {
            $values = TemplateEscritorio::where('id', 'in', $mensagem_template_escritorio_to_string)->getIndexedArray('chave', 'chave');
            $this->mensagem_template_escritorio_to_string = implode(', ', $values);
        }
        else
        {
            $this->mensagem_template_escritorio_to_string = $mensagem_template_escritorio_to_string;
        }

        $this->vdata['mensagem_template_escritorio_to_string'] = $this->mensagem_template_escritorio_to_string;
    }

    public function get_mensagem_template_escritorio_to_string()
    {
        if(!empty($this->mensagem_template_escritorio_to_string))
        {
            return $this->mensagem_template_escritorio_to_string;
        }
    
        $values = Mensagem::where('system_user_id', '=', $this->id)->getIndexedArray('template_escritorio_id','{template_escritorio->chave}');
        return implode(', ', $values);
    }

    public function set_mensagem_system_user_to_string($mensagem_system_user_to_string)
    {
        if(is_array($mensagem_system_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $mensagem_system_user_to_string)->getIndexedArray('name', 'name');
            $this->mensagem_system_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->mensagem_system_user_to_string = $mensagem_system_user_to_string;
        }

        $this->vdata['mensagem_system_user_to_string'] = $this->mensagem_system_user_to_string;
    }

    public function get_mensagem_system_user_to_string()
    {
        if(!empty($this->mensagem_system_user_to_string))
        {
            return $this->mensagem_system_user_to_string;
        }
    
        $values = Mensagem::where('system_user_id', '=', $this->id)->getIndexedArray('system_user_id','{system_user->name}');
        return implode(', ', $values);
    }

    public function set_modelo_documento_tipo_modelo_documento_to_string($modelo_documento_tipo_modelo_documento_to_string)
    {
        if(is_array($modelo_documento_tipo_modelo_documento_to_string))
        {
            $values = TipoModeloDocumento::where('id', 'in', $modelo_documento_tipo_modelo_documento_to_string)->getIndexedArray('nome', 'nome');
            $this->modelo_documento_tipo_modelo_documento_to_string = implode(', ', $values);
        }
        else
        {
            $this->modelo_documento_tipo_modelo_documento_to_string = $modelo_documento_tipo_modelo_documento_to_string;
        }

        $this->vdata['modelo_documento_tipo_modelo_documento_to_string'] = $this->modelo_documento_tipo_modelo_documento_to_string;
    }

    public function get_modelo_documento_tipo_modelo_documento_to_string()
    {
        if(!empty($this->modelo_documento_tipo_modelo_documento_to_string))
        {
            return $this->modelo_documento_tipo_modelo_documento_to_string;
        }
    
        $values = ModeloDocumento::where('modificacao_user_id', '=', $this->id)->getIndexedArray('tipo_modelo_documento_id','{tipo_modelo_documento->nome}');
        return implode(', ', $values);
    }

    public function set_modelo_documento_criacao_user_to_string($modelo_documento_criacao_user_to_string)
    {
        if(is_array($modelo_documento_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $modelo_documento_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->modelo_documento_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->modelo_documento_criacao_user_to_string = $modelo_documento_criacao_user_to_string;
        }

        $this->vdata['modelo_documento_criacao_user_to_string'] = $this->modelo_documento_criacao_user_to_string;
    }

    public function get_modelo_documento_criacao_user_to_string()
    {
        if(!empty($this->modelo_documento_criacao_user_to_string))
        {
            return $this->modelo_documento_criacao_user_to_string;
        }
    
        $values = ModeloDocumento::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_modelo_documento_modificacao_user_to_string($modelo_documento_modificacao_user_to_string)
    {
        if(is_array($modelo_documento_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $modelo_documento_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->modelo_documento_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->modelo_documento_modificacao_user_to_string = $modelo_documento_modificacao_user_to_string;
        }

        $this->vdata['modelo_documento_modificacao_user_to_string'] = $this->modelo_documento_modificacao_user_to_string;
    }

    public function get_modelo_documento_modificacao_user_to_string()
    {
        if(!empty($this->modelo_documento_modificacao_user_to_string))
        {
            return $this->modelo_documento_modificacao_user_to_string;
        }
    
        $values = ModeloDocumento::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_movimentacao_material_to_string($movimentacao_material_to_string)
    {
        if(is_array($movimentacao_material_to_string))
        {
            $values = Material::where('id', 'in', $movimentacao_material_to_string)->getIndexedArray('nome', 'nome');
            $this->movimentacao_material_to_string = implode(', ', $values);
        }
        else
        {
            $this->movimentacao_material_to_string = $movimentacao_material_to_string;
        }

        $this->vdata['movimentacao_material_to_string'] = $this->movimentacao_material_to_string;
    }

    public function get_movimentacao_material_to_string()
    {
        if(!empty($this->movimentacao_material_to_string))
        {
            return $this->movimentacao_material_to_string;
        }
    
        $values = Movimentacao::where('system_user_id', '=', $this->id)->getIndexedArray('material_id','{material->nome}');
        return implode(', ', $values);
    }

    public function set_movimentacao_system_user_to_string($movimentacao_system_user_to_string)
    {
        if(is_array($movimentacao_system_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $movimentacao_system_user_to_string)->getIndexedArray('name', 'name');
            $this->movimentacao_system_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->movimentacao_system_user_to_string = $movimentacao_system_user_to_string;
        }

        $this->vdata['movimentacao_system_user_to_string'] = $this->movimentacao_system_user_to_string;
    }

    public function get_movimentacao_system_user_to_string()
    {
        if(!empty($this->movimentacao_system_user_to_string))
        {
            return $this->movimentacao_system_user_to_string;
        }
    
        $values = Movimentacao::where('system_user_id', '=', $this->id)->getIndexedArray('system_user_id','{system_user->name}');
        return implode(', ', $values);
    }

    public function set_orgao_criacao_user_to_string($orgao_criacao_user_to_string)
    {
        if(is_array($orgao_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $orgao_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->orgao_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->orgao_criacao_user_to_string = $orgao_criacao_user_to_string;
        }

        $this->vdata['orgao_criacao_user_to_string'] = $this->orgao_criacao_user_to_string;
    }

    public function get_orgao_criacao_user_to_string()
    {
        if(!empty($this->orgao_criacao_user_to_string))
        {
            return $this->orgao_criacao_user_to_string;
        }
    
        $values = Orgao::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_orgao_modificacao_user_to_string($orgao_modificacao_user_to_string)
    {
        if(is_array($orgao_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $orgao_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->orgao_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->orgao_modificacao_user_to_string = $orgao_modificacao_user_to_string;
        }

        $this->vdata['orgao_modificacao_user_to_string'] = $this->orgao_modificacao_user_to_string;
    }

    public function get_orgao_modificacao_user_to_string()
    {
        if(!empty($this->orgao_modificacao_user_to_string))
        {
            return $this->orgao_modificacao_user_to_string;
        }
    
        $values = Orgao::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_padrao_atendimento_documento_criacao_user_to_string($padrao_atendimento_documento_criacao_user_to_string)
    {
        if(is_array($padrao_atendimento_documento_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $padrao_atendimento_documento_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->padrao_atendimento_documento_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->padrao_atendimento_documento_criacao_user_to_string = $padrao_atendimento_documento_criacao_user_to_string;
        }

        $this->vdata['padrao_atendimento_documento_criacao_user_to_string'] = $this->padrao_atendimento_documento_criacao_user_to_string;
    }

    public function get_padrao_atendimento_documento_criacao_user_to_string()
    {
        if(!empty($this->padrao_atendimento_documento_criacao_user_to_string))
        {
            return $this->padrao_atendimento_documento_criacao_user_to_string;
        }
    
        $values = PadraoAtendimentoDocumento::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_padrao_atendimento_documento_modificacao_user_to_string($padrao_atendimento_documento_modificacao_user_to_string)
    {
        if(is_array($padrao_atendimento_documento_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $padrao_atendimento_documento_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->padrao_atendimento_documento_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->padrao_atendimento_documento_modificacao_user_to_string = $padrao_atendimento_documento_modificacao_user_to_string;
        }

        $this->vdata['padrao_atendimento_documento_modificacao_user_to_string'] = $this->padrao_atendimento_documento_modificacao_user_to_string;
    }

    public function get_padrao_atendimento_documento_modificacao_user_to_string()
    {
        if(!empty($this->padrao_atendimento_documento_modificacao_user_to_string))
        {
            return $this->padrao_atendimento_documento_modificacao_user_to_string;
        }
    
        $values = PadraoAtendimentoDocumento::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_parceiro_pessoa_to_string($parceiro_pessoa_to_string)
    {
        if(is_array($parceiro_pessoa_to_string))
        {
            $values = Pessoa::where('id', 'in', $parceiro_pessoa_to_string)->getIndexedArray('nome', 'nome');
            $this->parceiro_pessoa_to_string = implode(', ', $values);
        }
        else
        {
            $this->parceiro_pessoa_to_string = $parceiro_pessoa_to_string;
        }

        $this->vdata['parceiro_pessoa_to_string'] = $this->parceiro_pessoa_to_string;
    }

    public function get_parceiro_pessoa_to_string()
    {
        if(!empty($this->parceiro_pessoa_to_string))
        {
            return $this->parceiro_pessoa_to_string;
        }
    
        $values = Parceiro::where('modificacao_user_id', '=', $this->id)->getIndexedArray('pessoa_id','{pessoa->nome}');
        return implode(', ', $values);
    }

    public function set_parceiro_criacao_user_to_string($parceiro_criacao_user_to_string)
    {
        if(is_array($parceiro_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $parceiro_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->parceiro_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->parceiro_criacao_user_to_string = $parceiro_criacao_user_to_string;
        }

        $this->vdata['parceiro_criacao_user_to_string'] = $this->parceiro_criacao_user_to_string;
    }

    public function get_parceiro_criacao_user_to_string()
    {
        if(!empty($this->parceiro_criacao_user_to_string))
        {
            return $this->parceiro_criacao_user_to_string;
        }
    
        $values = Parceiro::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_parceiro_modificacao_user_to_string($parceiro_modificacao_user_to_string)
    {
        if(is_array($parceiro_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $parceiro_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->parceiro_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->parceiro_modificacao_user_to_string = $parceiro_modificacao_user_to_string;
        }

        $this->vdata['parceiro_modificacao_user_to_string'] = $this->parceiro_modificacao_user_to_string;
    }

    public function get_parceiro_modificacao_user_to_string()
    {
        if(!empty($this->parceiro_modificacao_user_to_string))
        {
            return $this->parceiro_modificacao_user_to_string;
        }
    
        $values = Parceiro::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_pessoa_tipo_pessoa_to_string($pessoa_tipo_pessoa_to_string)
    {
        if(is_array($pessoa_tipo_pessoa_to_string))
        {
            $values = TipoPessoa::where('id', 'in', $pessoa_tipo_pessoa_to_string)->getIndexedArray('nome', 'nome');
            $this->pessoa_tipo_pessoa_to_string = implode(', ', $values);
        }
        else
        {
            $this->pessoa_tipo_pessoa_to_string = $pessoa_tipo_pessoa_to_string;
        }

        $this->vdata['pessoa_tipo_pessoa_to_string'] = $this->pessoa_tipo_pessoa_to_string;
    }

    public function get_pessoa_tipo_pessoa_to_string()
    {
        if(!empty($this->pessoa_tipo_pessoa_to_string))
        {
            return $this->pessoa_tipo_pessoa_to_string;
        }
    
        $values = Pessoa::where('modificacao_user_id', '=', $this->id)->getIndexedArray('tipo_pessoa_id','{tipo_pessoa->nome}');
        return implode(', ', $values);
    }

    public function set_pessoa_system_users_to_string($pessoa_system_users_to_string)
    {
        if(is_array($pessoa_system_users_to_string))
        {
            $values = SystemUsers::where('id', 'in', $pessoa_system_users_to_string)->getIndexedArray('name', 'name');
            $this->pessoa_system_users_to_string = implode(', ', $values);
        }
        else
        {
            $this->pessoa_system_users_to_string = $pessoa_system_users_to_string;
        }

        $this->vdata['pessoa_system_users_to_string'] = $this->pessoa_system_users_to_string;
    }

    public function get_pessoa_system_users_to_string()
    {
        if(!empty($this->pessoa_system_users_to_string))
        {
            return $this->pessoa_system_users_to_string;
        }
    
        $values = Pessoa::where('modificacao_user_id', '=', $this->id)->getIndexedArray('system_users_id','{system_users->name}');
        return implode(', ', $values);
    }

    public function set_pessoa_sexo_to_string($pessoa_sexo_to_string)
    {
        if(is_array($pessoa_sexo_to_string))
        {
            $values = Sexo::where('id', 'in', $pessoa_sexo_to_string)->getIndexedArray('nome', 'nome');
            $this->pessoa_sexo_to_string = implode(', ', $values);
        }
        else
        {
            $this->pessoa_sexo_to_string = $pessoa_sexo_to_string;
        }

        $this->vdata['pessoa_sexo_to_string'] = $this->pessoa_sexo_to_string;
    }

    public function get_pessoa_sexo_to_string()
    {
        if(!empty($this->pessoa_sexo_to_string))
        {
            return $this->pessoa_sexo_to_string;
        }
    
        $values = Pessoa::where('modificacao_user_id', '=', $this->id)->getIndexedArray('sexo_id','{sexo->nome}');
        return implode(', ', $values);
    }

    public function set_pessoa_nacionalidade_to_string($pessoa_nacionalidade_to_string)
    {
        if(is_array($pessoa_nacionalidade_to_string))
        {
            $values = Nacionalidade::where('id', 'in', $pessoa_nacionalidade_to_string)->getIndexedArray('nome', 'nome');
            $this->pessoa_nacionalidade_to_string = implode(', ', $values);
        }
        else
        {
            $this->pessoa_nacionalidade_to_string = $pessoa_nacionalidade_to_string;
        }

        $this->vdata['pessoa_nacionalidade_to_string'] = $this->pessoa_nacionalidade_to_string;
    }

    public function get_pessoa_nacionalidade_to_string()
    {
        if(!empty($this->pessoa_nacionalidade_to_string))
        {
            return $this->pessoa_nacionalidade_to_string;
        }
    
        $values = Pessoa::where('modificacao_user_id', '=', $this->id)->getIndexedArray('nacionalidade_id','{nacionalidade->nome}');
        return implode(', ', $values);
    }

    public function set_pessoa_estado_civil_to_string($pessoa_estado_civil_to_string)
    {
        if(is_array($pessoa_estado_civil_to_string))
        {
            $values = EstadoCivil::where('id', 'in', $pessoa_estado_civil_to_string)->getIndexedArray('nome', 'nome');
            $this->pessoa_estado_civil_to_string = implode(', ', $values);
        }
        else
        {
            $this->pessoa_estado_civil_to_string = $pessoa_estado_civil_to_string;
        }

        $this->vdata['pessoa_estado_civil_to_string'] = $this->pessoa_estado_civil_to_string;
    }

    public function get_pessoa_estado_civil_to_string()
    {
        if(!empty($this->pessoa_estado_civil_to_string))
        {
            return $this->pessoa_estado_civil_to_string;
        }
    
        $values = Pessoa::where('modificacao_user_id', '=', $this->id)->getIndexedArray('estado_civil_id','{estado_civil->nome}');
        return implode(', ', $values);
    }

    public function set_pessoa_situacao_profissional_to_string($pessoa_situacao_profissional_to_string)
    {
        if(is_array($pessoa_situacao_profissional_to_string))
        {
            $values = SituacaoProfissional::where('id', 'in', $pessoa_situacao_profissional_to_string)->getIndexedArray('nome', 'nome');
            $this->pessoa_situacao_profissional_to_string = implode(', ', $values);
        }
        else
        {
            $this->pessoa_situacao_profissional_to_string = $pessoa_situacao_profissional_to_string;
        }

        $this->vdata['pessoa_situacao_profissional_to_string'] = $this->pessoa_situacao_profissional_to_string;
    }

    public function get_pessoa_situacao_profissional_to_string()
    {
        if(!empty($this->pessoa_situacao_profissional_to_string))
        {
            return $this->pessoa_situacao_profissional_to_string;
        }
    
        $values = Pessoa::where('modificacao_user_id', '=', $this->id)->getIndexedArray('situacao_profissional_id','{situacao_profissional->nome}');
        return implode(', ', $values);
    }

    public function set_pessoa_tipo_profissional_to_string($pessoa_tipo_profissional_to_string)
    {
        if(is_array($pessoa_tipo_profissional_to_string))
        {
            $values = TipoProfissional::where('id', 'in', $pessoa_tipo_profissional_to_string)->getIndexedArray('nome', 'nome');
            $this->pessoa_tipo_profissional_to_string = implode(', ', $values);
        }
        else
        {
            $this->pessoa_tipo_profissional_to_string = $pessoa_tipo_profissional_to_string;
        }

        $this->vdata['pessoa_tipo_profissional_to_string'] = $this->pessoa_tipo_profissional_to_string;
    }

    public function get_pessoa_tipo_profissional_to_string()
    {
        if(!empty($this->pessoa_tipo_profissional_to_string))
        {
            return $this->pessoa_tipo_profissional_to_string;
        }
    
        $values = Pessoa::where('modificacao_user_id', '=', $this->id)->getIndexedArray('tipo_profissional_id','{tipo_profissional->nome}');
        return implode(', ', $values);
    }

    public function set_pessoa_criacao_user_to_string($pessoa_criacao_user_to_string)
    {
        if(is_array($pessoa_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $pessoa_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->pessoa_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->pessoa_criacao_user_to_string = $pessoa_criacao_user_to_string;
        }

        $this->vdata['pessoa_criacao_user_to_string'] = $this->pessoa_criacao_user_to_string;
    }

    public function get_pessoa_criacao_user_to_string()
    {
        if(!empty($this->pessoa_criacao_user_to_string))
        {
            return $this->pessoa_criacao_user_to_string;
        }
    
        $values = Pessoa::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_pessoa_modificacao_user_to_string($pessoa_modificacao_user_to_string)
    {
        if(is_array($pessoa_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $pessoa_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->pessoa_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->pessoa_modificacao_user_to_string = $pessoa_modificacao_user_to_string;
        }

        $this->vdata['pessoa_modificacao_user_to_string'] = $this->pessoa_modificacao_user_to_string;
    }

    public function get_pessoa_modificacao_user_to_string()
    {
        if(!empty($this->pessoa_modificacao_user_to_string))
        {
            return $this->pessoa_modificacao_user_to_string;
        }
    
        $values = Pessoa::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_preferencia_sistema_system_users_to_string($preferencia_sistema_system_users_to_string)
    {
        if(is_array($preferencia_sistema_system_users_to_string))
        {
            $values = SystemUsers::where('id', 'in', $preferencia_sistema_system_users_to_string)->getIndexedArray('name', 'name');
            $this->preferencia_sistema_system_users_to_string = implode(', ', $values);
        }
        else
        {
            $this->preferencia_sistema_system_users_to_string = $preferencia_sistema_system_users_to_string;
        }

        $this->vdata['preferencia_sistema_system_users_to_string'] = $this->preferencia_sistema_system_users_to_string;
    }

    public function get_preferencia_sistema_system_users_to_string()
    {
        if(!empty($this->preferencia_sistema_system_users_to_string))
        {
            return $this->preferencia_sistema_system_users_to_string;
        }
    
        $values = PreferenciaSistema::where('system_users_id', '=', $this->id)->getIndexedArray('system_users_id','{system_users->name}');
        return implode(', ', $values);
    }

    public function set_procedimento_criacao_user_to_string($procedimento_criacao_user_to_string)
    {
        if(is_array($procedimento_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $procedimento_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->procedimento_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->procedimento_criacao_user_to_string = $procedimento_criacao_user_to_string;
        }

        $this->vdata['procedimento_criacao_user_to_string'] = $this->procedimento_criacao_user_to_string;
    }

    public function get_procedimento_criacao_user_to_string()
    {
        if(!empty($this->procedimento_criacao_user_to_string))
        {
            return $this->procedimento_criacao_user_to_string;
        }
    
        $values = Procedimento::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_procedimento_modificacao_user_to_string($procedimento_modificacao_user_to_string)
    {
        if(is_array($procedimento_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $procedimento_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->procedimento_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->procedimento_modificacao_user_to_string = $procedimento_modificacao_user_to_string;
        }

        $this->vdata['procedimento_modificacao_user_to_string'] = $this->procedimento_modificacao_user_to_string;
    }

    public function get_procedimento_modificacao_user_to_string()
    {
        if(!empty($this->procedimento_modificacao_user_to_string))
        {
            return $this->procedimento_modificacao_user_to_string;
        }
    
        $values = Procedimento::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_processo_tipo_processo_to_string($processo_tipo_processo_to_string)
    {
        if(is_array($processo_tipo_processo_to_string))
        {
            $values = TipoProcesso::where('id', 'in', $processo_tipo_processo_to_string)->getIndexedArray('nome', 'nome');
            $this->processo_tipo_processo_to_string = implode(', ', $values);
        }
        else
        {
            $this->processo_tipo_processo_to_string = $processo_tipo_processo_to_string;
        }

        $this->vdata['processo_tipo_processo_to_string'] = $this->processo_tipo_processo_to_string;
    }

    public function get_processo_tipo_processo_to_string()
    {
        if(!empty($this->processo_tipo_processo_to_string))
        {
            return $this->processo_tipo_processo_to_string;
        }
    
        $values = Processo::where('modificacao_user_id', '=', $this->id)->getIndexedArray('tipo_processo_id','{tipo_processo->nome}');
        return implode(', ', $values);
    }

    public function set_processo_tribunal_to_string($processo_tribunal_to_string)
    {
        if(is_array($processo_tribunal_to_string))
        {
            $values = Tribunal::where('id', 'in', $processo_tribunal_to_string)->getIndexedArray('nome', 'nome');
            $this->processo_tribunal_to_string = implode(', ', $values);
        }
        else
        {
            $this->processo_tribunal_to_string = $processo_tribunal_to_string;
        }

        $this->vdata['processo_tribunal_to_string'] = $this->processo_tribunal_to_string;
    }

    public function get_processo_tribunal_to_string()
    {
        if(!empty($this->processo_tribunal_to_string))
        {
            return $this->processo_tribunal_to_string;
        }
    
        $values = Processo::where('modificacao_user_id', '=', $this->id)->getIndexedArray('tribunal_id','{tribunal->nome}');
        return implode(', ', $values);
    }

    public function set_processo_foro_to_string($processo_foro_to_string)
    {
        if(is_array($processo_foro_to_string))
        {
            $values = Foro::where('id', 'in', $processo_foro_to_string)->getIndexedArray('nome', 'nome');
            $this->processo_foro_to_string = implode(', ', $values);
        }
        else
        {
            $this->processo_foro_to_string = $processo_foro_to_string;
        }

        $this->vdata['processo_foro_to_string'] = $this->processo_foro_to_string;
    }

    public function get_processo_foro_to_string()
    {
        if(!empty($this->processo_foro_to_string))
        {
            return $this->processo_foro_to_string;
        }
    
        $values = Processo::where('modificacao_user_id', '=', $this->id)->getIndexedArray('foro_id','{foro->nome}');
        return implode(', ', $values);
    }

    public function set_processo_comarca_to_string($processo_comarca_to_string)
    {
        if(is_array($processo_comarca_to_string))
        {
            $values = Comarca::where('id', 'in', $processo_comarca_to_string)->getIndexedArray('nome', 'nome');
            $this->processo_comarca_to_string = implode(', ', $values);
        }
        else
        {
            $this->processo_comarca_to_string = $processo_comarca_to_string;
        }

        $this->vdata['processo_comarca_to_string'] = $this->processo_comarca_to_string;
    }

    public function get_processo_comarca_to_string()
    {
        if(!empty($this->processo_comarca_to_string))
        {
            return $this->processo_comarca_to_string;
        }
    
        $values = Processo::where('modificacao_user_id', '=', $this->id)->getIndexedArray('comarca_id','{comarca->nome}');
        return implode(', ', $values);
    }

    public function set_processo_vara_to_string($processo_vara_to_string)
    {
        if(is_array($processo_vara_to_string))
        {
            $values = Vara::where('id', 'in', $processo_vara_to_string)->getIndexedArray('nome', 'nome');
            $this->processo_vara_to_string = implode(', ', $values);
        }
        else
        {
            $this->processo_vara_to_string = $processo_vara_to_string;
        }

        $this->vdata['processo_vara_to_string'] = $this->processo_vara_to_string;
    }

    public function get_processo_vara_to_string()
    {
        if(!empty($this->processo_vara_to_string))
        {
            return $this->processo_vara_to_string;
        }
    
        $values = Processo::where('modificacao_user_id', '=', $this->id)->getIndexedArray('vara_id','{vara->nome}');
        return implode(', ', $values);
    }

    public function set_processo_orgao_to_string($processo_orgao_to_string)
    {
        if(is_array($processo_orgao_to_string))
        {
            $values = Orgao::where('id', 'in', $processo_orgao_to_string)->getIndexedArray('nome', 'nome');
            $this->processo_orgao_to_string = implode(', ', $values);
        }
        else
        {
            $this->processo_orgao_to_string = $processo_orgao_to_string;
        }

        $this->vdata['processo_orgao_to_string'] = $this->processo_orgao_to_string;
    }

    public function get_processo_orgao_to_string()
    {
        if(!empty($this->processo_orgao_to_string))
        {
            return $this->processo_orgao_to_string;
        }
    
        $values = Processo::where('modificacao_user_id', '=', $this->id)->getIndexedArray('orgao_id','{orgao->nome}');
        return implode(', ', $values);
    }

    public function set_processo_area_to_string($processo_area_to_string)
    {
        if(is_array($processo_area_to_string))
        {
            $values = Area::where('id', 'in', $processo_area_to_string)->getIndexedArray('nome', 'nome');
            $this->processo_area_to_string = implode(', ', $values);
        }
        else
        {
            $this->processo_area_to_string = $processo_area_to_string;
        }

        $this->vdata['processo_area_to_string'] = $this->processo_area_to_string;
    }

    public function get_processo_area_to_string()
    {
        if(!empty($this->processo_area_to_string))
        {
            return $this->processo_area_to_string;
        }
    
        $values = Processo::where('modificacao_user_id', '=', $this->id)->getIndexedArray('area_id','{area->nome}');
        return implode(', ', $values);
    }

    public function set_processo_assunto_to_string($processo_assunto_to_string)
    {
        if(is_array($processo_assunto_to_string))
        {
            $values = Assunto::where('id', 'in', $processo_assunto_to_string)->getIndexedArray('nome', 'nome');
            $this->processo_assunto_to_string = implode(', ', $values);
        }
        else
        {
            $this->processo_assunto_to_string = $processo_assunto_to_string;
        }

        $this->vdata['processo_assunto_to_string'] = $this->processo_assunto_to_string;
    }

    public function get_processo_assunto_to_string()
    {
        if(!empty($this->processo_assunto_to_string))
        {
            return $this->processo_assunto_to_string;
        }
    
        $values = Processo::where('modificacao_user_id', '=', $this->id)->getIndexedArray('assunto_id','{assunto->nome}');
        return implode(', ', $values);
    }

    public function set_processo_status_processual_to_string($processo_status_processual_to_string)
    {
        if(is_array($processo_status_processual_to_string))
        {
            $values = StatusProcessual::where('id', 'in', $processo_status_processual_to_string)->getIndexedArray('nome', 'nome');
            $this->processo_status_processual_to_string = implode(', ', $values);
        }
        else
        {
            $this->processo_status_processual_to_string = $processo_status_processual_to_string;
        }

        $this->vdata['processo_status_processual_to_string'] = $this->processo_status_processual_to_string;
    }

    public function get_processo_status_processual_to_string()
    {
        if(!empty($this->processo_status_processual_to_string))
        {
            return $this->processo_status_processual_to_string;
        }
    
        $values = Processo::where('modificacao_user_id', '=', $this->id)->getIndexedArray('status_processual_id','{status_processual->nome}');
        return implode(', ', $values);
    }

    public function set_processo_responsavel_to_string($processo_responsavel_to_string)
    {
        if(is_array($processo_responsavel_to_string))
        {
            $values = Pessoa::where('id', 'in', $processo_responsavel_to_string)->getIndexedArray('nome', 'nome');
            $this->processo_responsavel_to_string = implode(', ', $values);
        }
        else
        {
            $this->processo_responsavel_to_string = $processo_responsavel_to_string;
        }

        $this->vdata['processo_responsavel_to_string'] = $this->processo_responsavel_to_string;
    }

    public function get_processo_responsavel_to_string()
    {
        if(!empty($this->processo_responsavel_to_string))
        {
            return $this->processo_responsavel_to_string;
        }
    
        $values = Processo::where('modificacao_user_id', '=', $this->id)->getIndexedArray('responsavel_id','{responsavel->nome}');
        return implode(', ', $values);
    }

    public function set_processo_envolvimento_to_string($processo_envolvimento_to_string)
    {
        if(is_array($processo_envolvimento_to_string))
        {
            $values = Envolvimento::where('id', 'in', $processo_envolvimento_to_string)->getIndexedArray('nome', 'nome');
            $this->processo_envolvimento_to_string = implode(', ', $values);
        }
        else
        {
            $this->processo_envolvimento_to_string = $processo_envolvimento_to_string;
        }

        $this->vdata['processo_envolvimento_to_string'] = $this->processo_envolvimento_to_string;
    }

    public function get_processo_envolvimento_to_string()
    {
        if(!empty($this->processo_envolvimento_to_string))
        {
            return $this->processo_envolvimento_to_string;
        }
    
        $values = Processo::where('modificacao_user_id', '=', $this->id)->getIndexedArray('envolvimento_id','{envolvimento->nome}');
        return implode(', ', $values);
    }

    public function set_processo_criacao_user_to_string($processo_criacao_user_to_string)
    {
        if(is_array($processo_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $processo_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->processo_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->processo_criacao_user_to_string = $processo_criacao_user_to_string;
        }

        $this->vdata['processo_criacao_user_to_string'] = $this->processo_criacao_user_to_string;
    }

    public function get_processo_criacao_user_to_string()
    {
        if(!empty($this->processo_criacao_user_to_string))
        {
            return $this->processo_criacao_user_to_string;
        }
    
        $values = Processo::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_processo_modificacao_user_to_string($processo_modificacao_user_to_string)
    {
        if(is_array($processo_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $processo_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->processo_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->processo_modificacao_user_to_string = $processo_modificacao_user_to_string;
        }

        $this->vdata['processo_modificacao_user_to_string'] = $this->processo_modificacao_user_to_string;
    }

    public function get_processo_modificacao_user_to_string()
    {
        if(!empty($this->processo_modificacao_user_to_string))
        {
            return $this->processo_modificacao_user_to_string;
        }
    
        $values = Processo::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_publicacao_processo_to_string($publicacao_processo_to_string)
    {
        if(is_array($publicacao_processo_to_string))
        {
            $values = Processo::where('id', 'in', $publicacao_processo_to_string)->getIndexedArray('numero_cnj_numero', 'numero_cnj_numero');
            $this->publicacao_processo_to_string = implode(', ', $values);
        }
        else
        {
            $this->publicacao_processo_to_string = $publicacao_processo_to_string;
        }

        $this->vdata['publicacao_processo_to_string'] = $this->publicacao_processo_to_string;
    }

    public function get_publicacao_processo_to_string()
    {
        if(!empty($this->publicacao_processo_to_string))
        {
            return $this->publicacao_processo_to_string;
        }
    
        $values = Publicacao::where('modificacao_user_id', '=', $this->id)->getIndexedArray('processo_id','{processo->numero_cnj_numero}');
        return implode(', ', $values);
    }

    public function set_publicacao_jornal_to_string($publicacao_jornal_to_string)
    {
        if(is_array($publicacao_jornal_to_string))
        {
            $values = Jornal::where('id', 'in', $publicacao_jornal_to_string)->getIndexedArray('nome', 'nome');
            $this->publicacao_jornal_to_string = implode(', ', $values);
        }
        else
        {
            $this->publicacao_jornal_to_string = $publicacao_jornal_to_string;
        }

        $this->vdata['publicacao_jornal_to_string'] = $this->publicacao_jornal_to_string;
    }

    public function get_publicacao_jornal_to_string()
    {
        if(!empty($this->publicacao_jornal_to_string))
        {
            return $this->publicacao_jornal_to_string;
        }
    
        $values = Publicacao::where('modificacao_user_id', '=', $this->id)->getIndexedArray('jornal_id','{jornal->nome}');
        return implode(', ', $values);
    }

    public function set_publicacao_criacao_user_to_string($publicacao_criacao_user_to_string)
    {
        if(is_array($publicacao_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $publicacao_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->publicacao_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->publicacao_criacao_user_to_string = $publicacao_criacao_user_to_string;
        }

        $this->vdata['publicacao_criacao_user_to_string'] = $this->publicacao_criacao_user_to_string;
    }

    public function get_publicacao_criacao_user_to_string()
    {
        if(!empty($this->publicacao_criacao_user_to_string))
        {
            return $this->publicacao_criacao_user_to_string;
        }
    
        $values = Publicacao::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_publicacao_modificacao_user_to_string($publicacao_modificacao_user_to_string)
    {
        if(is_array($publicacao_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $publicacao_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->publicacao_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->publicacao_modificacao_user_to_string = $publicacao_modificacao_user_to_string;
        }

        $this->vdata['publicacao_modificacao_user_to_string'] = $this->publicacao_modificacao_user_to_string;
    }

    public function get_publicacao_modificacao_user_to_string()
    {
        if(!empty($this->publicacao_modificacao_user_to_string))
        {
            return $this->publicacao_modificacao_user_to_string;
        }
    
        $values = Publicacao::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_publicacao_publicacao_etapa_to_string($publicacao_publicacao_etapa_to_string)
    {
        if(is_array($publicacao_publicacao_etapa_to_string))
        {
            $values = PublicacaoEtapa::where('id', 'in', $publicacao_publicacao_etapa_to_string)->getIndexedArray('id', 'id');
            $this->publicacao_publicacao_etapa_to_string = implode(', ', $values);
        }
        else
        {
            $this->publicacao_publicacao_etapa_to_string = $publicacao_publicacao_etapa_to_string;
        }

        $this->vdata['publicacao_publicacao_etapa_to_string'] = $this->publicacao_publicacao_etapa_to_string;
    }

    public function get_publicacao_publicacao_etapa_to_string()
    {
        if(!empty($this->publicacao_publicacao_etapa_to_string))
        {
            return $this->publicacao_publicacao_etapa_to_string;
        }
    
        $values = Publicacao::where('modificacao_user_id', '=', $this->id)->getIndexedArray('publicacao_etapa_id','{publicacao_etapa->id}');
        return implode(', ', $values);
    }

    public function set_publicacao_movimentacao_publicacao_to_string($publicacao_movimentacao_publicacao_to_string)
    {
        if(is_array($publicacao_movimentacao_publicacao_to_string))
        {
            $values = Publicacao::where('id', 'in', $publicacao_movimentacao_publicacao_to_string)->getIndexedArray('id', 'id');
            $this->publicacao_movimentacao_publicacao_to_string = implode(', ', $values);
        }
        else
        {
            $this->publicacao_movimentacao_publicacao_to_string = $publicacao_movimentacao_publicacao_to_string;
        }

        $this->vdata['publicacao_movimentacao_publicacao_to_string'] = $this->publicacao_movimentacao_publicacao_to_string;
    }

    public function get_publicacao_movimentacao_publicacao_to_string()
    {
        if(!empty($this->publicacao_movimentacao_publicacao_to_string))
        {
            return $this->publicacao_movimentacao_publicacao_to_string;
        }
    
        $values = PublicacaoMovimentacao::where('criacao_user_id', '=', $this->id)->getIndexedArray('publicacao_id','{publicacao->id}');
        return implode(', ', $values);
    }

    public function set_publicacao_movimentacao_processo_to_string($publicacao_movimentacao_processo_to_string)
    {
        if(is_array($publicacao_movimentacao_processo_to_string))
        {
            $values = Processo::where('id', 'in', $publicacao_movimentacao_processo_to_string)->getIndexedArray('numero_cnj_numero', 'numero_cnj_numero');
            $this->publicacao_movimentacao_processo_to_string = implode(', ', $values);
        }
        else
        {
            $this->publicacao_movimentacao_processo_to_string = $publicacao_movimentacao_processo_to_string;
        }

        $this->vdata['publicacao_movimentacao_processo_to_string'] = $this->publicacao_movimentacao_processo_to_string;
    }

    public function get_publicacao_movimentacao_processo_to_string()
    {
        if(!empty($this->publicacao_movimentacao_processo_to_string))
        {
            return $this->publicacao_movimentacao_processo_to_string;
        }
    
        $values = PublicacaoMovimentacao::where('criacao_user_id', '=', $this->id)->getIndexedArray('processo_id','{processo->numero_cnj_numero}');
        return implode(', ', $values);
    }

    public function set_publicacao_movimentacao_tarefa_to_string($publicacao_movimentacao_tarefa_to_string)
    {
        if(is_array($publicacao_movimentacao_tarefa_to_string))
        {
            $values = Tarefa::where('id', 'in', $publicacao_movimentacao_tarefa_to_string)->getIndexedArray('titulo', 'titulo');
            $this->publicacao_movimentacao_tarefa_to_string = implode(', ', $values);
        }
        else
        {
            $this->publicacao_movimentacao_tarefa_to_string = $publicacao_movimentacao_tarefa_to_string;
        }

        $this->vdata['publicacao_movimentacao_tarefa_to_string'] = $this->publicacao_movimentacao_tarefa_to_string;
    }

    public function get_publicacao_movimentacao_tarefa_to_string()
    {
        if(!empty($this->publicacao_movimentacao_tarefa_to_string))
        {
            return $this->publicacao_movimentacao_tarefa_to_string;
        }
    
        $values = PublicacaoMovimentacao::where('criacao_user_id', '=', $this->id)->getIndexedArray('tarefa_id','{tarefa->titulo}');
        return implode(', ', $values);
    }

    public function set_publicacao_movimentacao_criacao_user_to_string($publicacao_movimentacao_criacao_user_to_string)
    {
        if(is_array($publicacao_movimentacao_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $publicacao_movimentacao_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->publicacao_movimentacao_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->publicacao_movimentacao_criacao_user_to_string = $publicacao_movimentacao_criacao_user_to_string;
        }

        $this->vdata['publicacao_movimentacao_criacao_user_to_string'] = $this->publicacao_movimentacao_criacao_user_to_string;
    }

    public function get_publicacao_movimentacao_criacao_user_to_string()
    {
        if(!empty($this->publicacao_movimentacao_criacao_user_to_string))
        {
            return $this->publicacao_movimentacao_criacao_user_to_string;
        }
    
        $values = PublicacaoMovimentacao::where('criacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_publicacao_sugestao_prazo_publicacao_to_string($publicacao_sugestao_prazo_publicacao_to_string)
    {
        if(is_array($publicacao_sugestao_prazo_publicacao_to_string))
        {
            $values = Publicacao::where('id', 'in', $publicacao_sugestao_prazo_publicacao_to_string)->getIndexedArray('id', 'id');
            $this->publicacao_sugestao_prazo_publicacao_to_string = implode(', ', $values);
        }
        else
        {
            $this->publicacao_sugestao_prazo_publicacao_to_string = $publicacao_sugestao_prazo_publicacao_to_string;
        }

        $this->vdata['publicacao_sugestao_prazo_publicacao_to_string'] = $this->publicacao_sugestao_prazo_publicacao_to_string;
    }

    public function get_publicacao_sugestao_prazo_publicacao_to_string()
    {
        if(!empty($this->publicacao_sugestao_prazo_publicacao_to_string))
        {
            return $this->publicacao_sugestao_prazo_publicacao_to_string;
        }
    
        $values = PublicacaoSugestaoPrazo::where('modificacao_user_id', '=', $this->id)->getIndexedArray('publicacao_id','{publicacao->id}');
        return implode(', ', $values);
    }

    public function set_publicacao_sugestao_prazo_config_busca_prazo_to_string($publicacao_sugestao_prazo_config_busca_prazo_to_string)
    {
        if(is_array($publicacao_sugestao_prazo_config_busca_prazo_to_string))
        {
            $values = ConfigBuscaPrazo::where('id', 'in', $publicacao_sugestao_prazo_config_busca_prazo_to_string)->getIndexedArray('titulo', 'titulo');
            $this->publicacao_sugestao_prazo_config_busca_prazo_to_string = implode(', ', $values);
        }
        else
        {
            $this->publicacao_sugestao_prazo_config_busca_prazo_to_string = $publicacao_sugestao_prazo_config_busca_prazo_to_string;
        }

        $this->vdata['publicacao_sugestao_prazo_config_busca_prazo_to_string'] = $this->publicacao_sugestao_prazo_config_busca_prazo_to_string;
    }

    public function get_publicacao_sugestao_prazo_config_busca_prazo_to_string()
    {
        if(!empty($this->publicacao_sugestao_prazo_config_busca_prazo_to_string))
        {
            return $this->publicacao_sugestao_prazo_config_busca_prazo_to_string;
        }
    
        $values = PublicacaoSugestaoPrazo::where('modificacao_user_id', '=', $this->id)->getIndexedArray('config_busca_prazo_id','{config_busca_prazo->titulo}');
        return implode(', ', $values);
    }

    public function set_publicacao_sugestao_prazo_criacao_user_to_string($publicacao_sugestao_prazo_criacao_user_to_string)
    {
        if(is_array($publicacao_sugestao_prazo_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $publicacao_sugestao_prazo_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->publicacao_sugestao_prazo_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->publicacao_sugestao_prazo_criacao_user_to_string = $publicacao_sugestao_prazo_criacao_user_to_string;
        }

        $this->vdata['publicacao_sugestao_prazo_criacao_user_to_string'] = $this->publicacao_sugestao_prazo_criacao_user_to_string;
    }

    public function get_publicacao_sugestao_prazo_criacao_user_to_string()
    {
        if(!empty($this->publicacao_sugestao_prazo_criacao_user_to_string))
        {
            return $this->publicacao_sugestao_prazo_criacao_user_to_string;
        }
    
        $values = PublicacaoSugestaoPrazo::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_publicacao_sugestao_prazo_modificacao_user_to_string($publicacao_sugestao_prazo_modificacao_user_to_string)
    {
        if(is_array($publicacao_sugestao_prazo_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $publicacao_sugestao_prazo_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->publicacao_sugestao_prazo_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->publicacao_sugestao_prazo_modificacao_user_to_string = $publicacao_sugestao_prazo_modificacao_user_to_string;
        }

        $this->vdata['publicacao_sugestao_prazo_modificacao_user_to_string'] = $this->publicacao_sugestao_prazo_modificacao_user_to_string;
    }

    public function get_publicacao_sugestao_prazo_modificacao_user_to_string()
    {
        if(!empty($this->publicacao_sugestao_prazo_modificacao_user_to_string))
        {
            return $this->publicacao_sugestao_prazo_modificacao_user_to_string;
        }
    
        $values = PublicacaoSugestaoPrazo::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_resposta_formulario_formulario_to_string($resposta_formulario_formulario_to_string)
    {
        if(is_array($resposta_formulario_formulario_to_string))
        {
            $values = Formulario::where('id', 'in', $resposta_formulario_formulario_to_string)->getIndexedArray('nome', 'nome');
            $this->resposta_formulario_formulario_to_string = implode(', ', $values);
        }
        else
        {
            $this->resposta_formulario_formulario_to_string = $resposta_formulario_formulario_to_string;
        }

        $this->vdata['resposta_formulario_formulario_to_string'] = $this->resposta_formulario_formulario_to_string;
    }

    public function get_resposta_formulario_formulario_to_string()
    {
        if(!empty($this->resposta_formulario_formulario_to_string))
        {
            return $this->resposta_formulario_formulario_to_string;
        }
    
        $values = RespostaFormulario::where('modificacao_user_id', '=', $this->id)->getIndexedArray('formulario_id','{formulario->nome}');
        return implode(', ', $values);
    }

    public function set_resposta_formulario_atendimento_to_string($resposta_formulario_atendimento_to_string)
    {
        if(is_array($resposta_formulario_atendimento_to_string))
        {
            $values = Atendimento::where('id', 'in', $resposta_formulario_atendimento_to_string)->getIndexedArray('id', 'id');
            $this->resposta_formulario_atendimento_to_string = implode(', ', $values);
        }
        else
        {
            $this->resposta_formulario_atendimento_to_string = $resposta_formulario_atendimento_to_string;
        }

        $this->vdata['resposta_formulario_atendimento_to_string'] = $this->resposta_formulario_atendimento_to_string;
    }

    public function get_resposta_formulario_atendimento_to_string()
    {
        if(!empty($this->resposta_formulario_atendimento_to_string))
        {
            return $this->resposta_formulario_atendimento_to_string;
        }
    
        $values = RespostaFormulario::where('modificacao_user_id', '=', $this->id)->getIndexedArray('atendimento_id','{atendimento->id}');
        return implode(', ', $values);
    }

    public function set_resposta_formulario_criacao_user_to_string($resposta_formulario_criacao_user_to_string)
    {
        if(is_array($resposta_formulario_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $resposta_formulario_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->resposta_formulario_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->resposta_formulario_criacao_user_to_string = $resposta_formulario_criacao_user_to_string;
        }

        $this->vdata['resposta_formulario_criacao_user_to_string'] = $this->resposta_formulario_criacao_user_to_string;
    }

    public function get_resposta_formulario_criacao_user_to_string()
    {
        if(!empty($this->resposta_formulario_criacao_user_to_string))
        {
            return $this->resposta_formulario_criacao_user_to_string;
        }
    
        $values = RespostaFormulario::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_resposta_formulario_modificacao_user_to_string($resposta_formulario_modificacao_user_to_string)
    {
        if(is_array($resposta_formulario_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $resposta_formulario_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->resposta_formulario_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->resposta_formulario_modificacao_user_to_string = $resposta_formulario_modificacao_user_to_string;
        }

        $this->vdata['resposta_formulario_modificacao_user_to_string'] = $this->resposta_formulario_modificacao_user_to_string;
    }

    public function get_resposta_formulario_modificacao_user_to_string()
    {
        if(!empty($this->resposta_formulario_modificacao_user_to_string))
        {
            return $this->resposta_formulario_modificacao_user_to_string;
        }
    
        $values = RespostaFormulario::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_status_processual_tipo_processo_to_string($status_processual_tipo_processo_to_string)
    {
        if(is_array($status_processual_tipo_processo_to_string))
        {
            $values = TipoProcesso::where('id', 'in', $status_processual_tipo_processo_to_string)->getIndexedArray('nome', 'nome');
            $this->status_processual_tipo_processo_to_string = implode(', ', $values);
        }
        else
        {
            $this->status_processual_tipo_processo_to_string = $status_processual_tipo_processo_to_string;
        }

        $this->vdata['status_processual_tipo_processo_to_string'] = $this->status_processual_tipo_processo_to_string;
    }

    public function get_status_processual_tipo_processo_to_string()
    {
        if(!empty($this->status_processual_tipo_processo_to_string))
        {
            return $this->status_processual_tipo_processo_to_string;
        }
    
        $values = StatusProcessual::where('modificacao_user_id', '=', $this->id)->getIndexedArray('tipo_processo_id','{tipo_processo->nome}');
        return implode(', ', $values);
    }

    public function set_status_processual_criacao_user_to_string($status_processual_criacao_user_to_string)
    {
        if(is_array($status_processual_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $status_processual_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->status_processual_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->status_processual_criacao_user_to_string = $status_processual_criacao_user_to_string;
        }

        $this->vdata['status_processual_criacao_user_to_string'] = $this->status_processual_criacao_user_to_string;
    }

    public function get_status_processual_criacao_user_to_string()
    {
        if(!empty($this->status_processual_criacao_user_to_string))
        {
            return $this->status_processual_criacao_user_to_string;
        }
    
        $values = StatusProcessual::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_status_processual_modificacao_user_to_string($status_processual_modificacao_user_to_string)
    {
        if(is_array($status_processual_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $status_processual_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->status_processual_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->status_processual_modificacao_user_to_string = $status_processual_modificacao_user_to_string;
        }

        $this->vdata['status_processual_modificacao_user_to_string'] = $this->status_processual_modificacao_user_to_string;
    }

    public function get_status_processual_modificacao_user_to_string()
    {
        if(!empty($this->status_processual_modificacao_user_to_string))
        {
            return $this->status_processual_modificacao_user_to_string;
        }
    
        $values = StatusProcessual::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_tarefa_tarefa_status_to_string($tarefa_tarefa_status_to_string)
    {
        if(is_array($tarefa_tarefa_status_to_string))
        {
            $values = TarefaStatus::where('id', 'in', $tarefa_tarefa_status_to_string)->getIndexedArray('nome', 'nome');
            $this->tarefa_tarefa_status_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_tarefa_status_to_string = $tarefa_tarefa_status_to_string;
        }

        $this->vdata['tarefa_tarefa_status_to_string'] = $this->tarefa_tarefa_status_to_string;
    }

    public function get_tarefa_tarefa_status_to_string()
    {
        if(!empty($this->tarefa_tarefa_status_to_string))
        {
            return $this->tarefa_tarefa_status_to_string;
        }
    
        $values = Tarefa::where('modificacao_user_id', '=', $this->id)->getIndexedArray('tarefa_status_id','{tarefa_status->nome}');
        return implode(', ', $values);
    }

    public function set_tarefa_publicacao_to_string($tarefa_publicacao_to_string)
    {
        if(is_array($tarefa_publicacao_to_string))
        {
            $values = Publicacao::where('id', 'in', $tarefa_publicacao_to_string)->getIndexedArray('id', 'id');
            $this->tarefa_publicacao_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_publicacao_to_string = $tarefa_publicacao_to_string;
        }

        $this->vdata['tarefa_publicacao_to_string'] = $this->tarefa_publicacao_to_string;
    }

    public function get_tarefa_publicacao_to_string()
    {
        if(!empty($this->tarefa_publicacao_to_string))
        {
            return $this->tarefa_publicacao_to_string;
        }
    
        $values = Tarefa::where('modificacao_user_id', '=', $this->id)->getIndexedArray('publicacao_id','{publicacao->id}');
        return implode(', ', $values);
    }

    public function set_tarefa_processo_to_string($tarefa_processo_to_string)
    {
        if(is_array($tarefa_processo_to_string))
        {
            $values = Processo::where('id', 'in', $tarefa_processo_to_string)->getIndexedArray('numero_cnj_numero', 'numero_cnj_numero');
            $this->tarefa_processo_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_processo_to_string = $tarefa_processo_to_string;
        }

        $this->vdata['tarefa_processo_to_string'] = $this->tarefa_processo_to_string;
    }

    public function get_tarefa_processo_to_string()
    {
        if(!empty($this->tarefa_processo_to_string))
        {
            return $this->tarefa_processo_to_string;
        }
    
        $values = Tarefa::where('modificacao_user_id', '=', $this->id)->getIndexedArray('processo_id','{processo->numero_cnj_numero}');
        return implode(', ', $values);
    }

    public function set_tarefa_usuario_destinatario_to_string($tarefa_usuario_destinatario_to_string)
    {
        if(is_array($tarefa_usuario_destinatario_to_string))
        {
            $values = SystemUsers::where('id', 'in', $tarefa_usuario_destinatario_to_string)->getIndexedArray('name', 'name');
            $this->tarefa_usuario_destinatario_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_usuario_destinatario_to_string = $tarefa_usuario_destinatario_to_string;
        }

        $this->vdata['tarefa_usuario_destinatario_to_string'] = $this->tarefa_usuario_destinatario_to_string;
    }

    public function get_tarefa_usuario_destinatario_to_string()
    {
        if(!empty($this->tarefa_usuario_destinatario_to_string))
        {
            return $this->tarefa_usuario_destinatario_to_string;
        }
    
        $values = Tarefa::where('modificacao_user_id', '=', $this->id)->getIndexedArray('usuario_destinatario_id','{usuario_destinatario->name}');
        return implode(', ', $values);
    }

    public function set_tarefa_criacao_user_to_string($tarefa_criacao_user_to_string)
    {
        if(is_array($tarefa_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $tarefa_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->tarefa_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_criacao_user_to_string = $tarefa_criacao_user_to_string;
        }

        $this->vdata['tarefa_criacao_user_to_string'] = $this->tarefa_criacao_user_to_string;
    }

    public function get_tarefa_criacao_user_to_string()
    {
        if(!empty($this->tarefa_criacao_user_to_string))
        {
            return $this->tarefa_criacao_user_to_string;
        }
    
        $values = Tarefa::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_tarefa_modificacao_user_to_string($tarefa_modificacao_user_to_string)
    {
        if(is_array($tarefa_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $tarefa_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->tarefa_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_modificacao_user_to_string = $tarefa_modificacao_user_to_string;
        }

        $this->vdata['tarefa_modificacao_user_to_string'] = $this->tarefa_modificacao_user_to_string;
    }

    public function get_tarefa_modificacao_user_to_string()
    {
        if(!empty($this->tarefa_modificacao_user_to_string))
        {
            return $this->tarefa_modificacao_user_to_string;
        }
    
        $values = Tarefa::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_tarefa_comentario_tarefa_to_string($tarefa_comentario_tarefa_to_string)
    {
        if(is_array($tarefa_comentario_tarefa_to_string))
        {
            $values = Tarefa::where('id', 'in', $tarefa_comentario_tarefa_to_string)->getIndexedArray('titulo', 'titulo');
            $this->tarefa_comentario_tarefa_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_comentario_tarefa_to_string = $tarefa_comentario_tarefa_to_string;
        }

        $this->vdata['tarefa_comentario_tarefa_to_string'] = $this->tarefa_comentario_tarefa_to_string;
    }

    public function get_tarefa_comentario_tarefa_to_string()
    {
        if(!empty($this->tarefa_comentario_tarefa_to_string))
        {
            return $this->tarefa_comentario_tarefa_to_string;
        }
    
        $values = TarefaComentario::where('modificacao_user_id', '=', $this->id)->getIndexedArray('tarefa_id','{tarefa->titulo}');
        return implode(', ', $values);
    }

    public function set_tarefa_comentario_criacao_user_to_string($tarefa_comentario_criacao_user_to_string)
    {
        if(is_array($tarefa_comentario_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $tarefa_comentario_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->tarefa_comentario_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_comentario_criacao_user_to_string = $tarefa_comentario_criacao_user_to_string;
        }

        $this->vdata['tarefa_comentario_criacao_user_to_string'] = $this->tarefa_comentario_criacao_user_to_string;
    }

    public function get_tarefa_comentario_criacao_user_to_string()
    {
        if(!empty($this->tarefa_comentario_criacao_user_to_string))
        {
            return $this->tarefa_comentario_criacao_user_to_string;
        }
    
        $values = TarefaComentario::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_tarefa_comentario_modificacao_user_to_string($tarefa_comentario_modificacao_user_to_string)
    {
        if(is_array($tarefa_comentario_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $tarefa_comentario_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->tarefa_comentario_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_comentario_modificacao_user_to_string = $tarefa_comentario_modificacao_user_to_string;
        }

        $this->vdata['tarefa_comentario_modificacao_user_to_string'] = $this->tarefa_comentario_modificacao_user_to_string;
    }

    public function get_tarefa_comentario_modificacao_user_to_string()
    {
        if(!empty($this->tarefa_comentario_modificacao_user_to_string))
        {
            return $this->tarefa_comentario_modificacao_user_to_string;
        }
    
        $values = TarefaComentario::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_tarefa_configuracao_status_inicial_to_string($tarefa_configuracao_status_inicial_to_string)
    {
        if(is_array($tarefa_configuracao_status_inicial_to_string))
        {
            $values = TarefaStatus::where('id', 'in', $tarefa_configuracao_status_inicial_to_string)->getIndexedArray('nome', 'nome');
            $this->tarefa_configuracao_status_inicial_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_configuracao_status_inicial_to_string = $tarefa_configuracao_status_inicial_to_string;
        }

        $this->vdata['tarefa_configuracao_status_inicial_to_string'] = $this->tarefa_configuracao_status_inicial_to_string;
    }

    public function get_tarefa_configuracao_status_inicial_to_string()
    {
        if(!empty($this->tarefa_configuracao_status_inicial_to_string))
        {
            return $this->tarefa_configuracao_status_inicial_to_string;
        }
    
        $values = TarefaConfiguracao::where('modificacao_user_id', '=', $this->id)->getIndexedArray('status_inicial_id','{status_inicial->nome}');
        return implode(', ', $values);
    }

    public function set_tarefa_configuracao_status_final_to_string($tarefa_configuracao_status_final_to_string)
    {
        if(is_array($tarefa_configuracao_status_final_to_string))
        {
            $values = TarefaStatus::where('id', 'in', $tarefa_configuracao_status_final_to_string)->getIndexedArray('nome', 'nome');
            $this->tarefa_configuracao_status_final_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_configuracao_status_final_to_string = $tarefa_configuracao_status_final_to_string;
        }

        $this->vdata['tarefa_configuracao_status_final_to_string'] = $this->tarefa_configuracao_status_final_to_string;
    }

    public function get_tarefa_configuracao_status_final_to_string()
    {
        if(!empty($this->tarefa_configuracao_status_final_to_string))
        {
            return $this->tarefa_configuracao_status_final_to_string;
        }
    
        $values = TarefaConfiguracao::where('modificacao_user_id', '=', $this->id)->getIndexedArray('status_final_id','{status_final->nome}');
        return implode(', ', $values);
    }

    public function set_tarefa_configuracao_status_cancelado_to_string($tarefa_configuracao_status_cancelado_to_string)
    {
        if(is_array($tarefa_configuracao_status_cancelado_to_string))
        {
            $values = TarefaStatus::where('id', 'in', $tarefa_configuracao_status_cancelado_to_string)->getIndexedArray('nome', 'nome');
            $this->tarefa_configuracao_status_cancelado_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_configuracao_status_cancelado_to_string = $tarefa_configuracao_status_cancelado_to_string;
        }

        $this->vdata['tarefa_configuracao_status_cancelado_to_string'] = $this->tarefa_configuracao_status_cancelado_to_string;
    }

    public function get_tarefa_configuracao_status_cancelado_to_string()
    {
        if(!empty($this->tarefa_configuracao_status_cancelado_to_string))
        {
            return $this->tarefa_configuracao_status_cancelado_to_string;
        }
    
        $values = TarefaConfiguracao::where('modificacao_user_id', '=', $this->id)->getIndexedArray('status_cancelado_id','{status_cancelado->nome}');
        return implode(', ', $values);
    }

    public function set_tarefa_configuracao_modificacao_user_to_string($tarefa_configuracao_modificacao_user_to_string)
    {
        if(is_array($tarefa_configuracao_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $tarefa_configuracao_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->tarefa_configuracao_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_configuracao_modificacao_user_to_string = $tarefa_configuracao_modificacao_user_to_string;
        }

        $this->vdata['tarefa_configuracao_modificacao_user_to_string'] = $this->tarefa_configuracao_modificacao_user_to_string;
    }

    public function get_tarefa_configuracao_modificacao_user_to_string()
    {
        if(!empty($this->tarefa_configuracao_modificacao_user_to_string))
        {
            return $this->tarefa_configuracao_modificacao_user_to_string;
        }
    
        $values = TarefaConfiguracao::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_tarefa_horas_trabalhadas_tarefa_to_string($tarefa_horas_trabalhadas_tarefa_to_string)
    {
        if(is_array($tarefa_horas_trabalhadas_tarefa_to_string))
        {
            $values = Tarefa::where('id', 'in', $tarefa_horas_trabalhadas_tarefa_to_string)->getIndexedArray('titulo', 'titulo');
            $this->tarefa_horas_trabalhadas_tarefa_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_horas_trabalhadas_tarefa_to_string = $tarefa_horas_trabalhadas_tarefa_to_string;
        }

        $this->vdata['tarefa_horas_trabalhadas_tarefa_to_string'] = $this->tarefa_horas_trabalhadas_tarefa_to_string;
    }

    public function get_tarefa_horas_trabalhadas_tarefa_to_string()
    {
        if(!empty($this->tarefa_horas_trabalhadas_tarefa_to_string))
        {
            return $this->tarefa_horas_trabalhadas_tarefa_to_string;
        }
    
        $values = TarefaHorasTrabalhadas::where('criacao_user_id', '=', $this->id)->getIndexedArray('tarefa_id','{tarefa->titulo}');
        return implode(', ', $values);
    }

    public function set_tarefa_horas_trabalhadas_criacao_user_to_string($tarefa_horas_trabalhadas_criacao_user_to_string)
    {
        if(is_array($tarefa_horas_trabalhadas_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $tarefa_horas_trabalhadas_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->tarefa_horas_trabalhadas_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_horas_trabalhadas_criacao_user_to_string = $tarefa_horas_trabalhadas_criacao_user_to_string;
        }

        $this->vdata['tarefa_horas_trabalhadas_criacao_user_to_string'] = $this->tarefa_horas_trabalhadas_criacao_user_to_string;
    }

    public function get_tarefa_horas_trabalhadas_criacao_user_to_string()
    {
        if(!empty($this->tarefa_horas_trabalhadas_criacao_user_to_string))
        {
            return $this->tarefa_horas_trabalhadas_criacao_user_to_string;
        }
    
        $values = TarefaHorasTrabalhadas::where('criacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_tarefa_movimentacao_tarefa_to_string($tarefa_movimentacao_tarefa_to_string)
    {
        if(is_array($tarefa_movimentacao_tarefa_to_string))
        {
            $values = Tarefa::where('id', 'in', $tarefa_movimentacao_tarefa_to_string)->getIndexedArray('titulo', 'titulo');
            $this->tarefa_movimentacao_tarefa_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_movimentacao_tarefa_to_string = $tarefa_movimentacao_tarefa_to_string;
        }

        $this->vdata['tarefa_movimentacao_tarefa_to_string'] = $this->tarefa_movimentacao_tarefa_to_string;
    }

    public function get_tarefa_movimentacao_tarefa_to_string()
    {
        if(!empty($this->tarefa_movimentacao_tarefa_to_string))
        {
            return $this->tarefa_movimentacao_tarefa_to_string;
        }
    
        $values = TarefaMovimentacao::where('modificacao_user_id', '=', $this->id)->getIndexedArray('tarefa_id','{tarefa->titulo}');
        return implode(', ', $values);
    }

    public function set_tarefa_movimentacao_criacao_user_to_string($tarefa_movimentacao_criacao_user_to_string)
    {
        if(is_array($tarefa_movimentacao_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $tarefa_movimentacao_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->tarefa_movimentacao_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_movimentacao_criacao_user_to_string = $tarefa_movimentacao_criacao_user_to_string;
        }

        $this->vdata['tarefa_movimentacao_criacao_user_to_string'] = $this->tarefa_movimentacao_criacao_user_to_string;
    }

    public function get_tarefa_movimentacao_criacao_user_to_string()
    {
        if(!empty($this->tarefa_movimentacao_criacao_user_to_string))
        {
            return $this->tarefa_movimentacao_criacao_user_to_string;
        }
    
        $values = TarefaMovimentacao::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_tarefa_movimentacao_modificacao_user_to_string($tarefa_movimentacao_modificacao_user_to_string)
    {
        if(is_array($tarefa_movimentacao_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $tarefa_movimentacao_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->tarefa_movimentacao_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_movimentacao_modificacao_user_to_string = $tarefa_movimentacao_modificacao_user_to_string;
        }

        $this->vdata['tarefa_movimentacao_modificacao_user_to_string'] = $this->tarefa_movimentacao_modificacao_user_to_string;
    }

    public function get_tarefa_movimentacao_modificacao_user_to_string()
    {
        if(!empty($this->tarefa_movimentacao_modificacao_user_to_string))
        {
            return $this->tarefa_movimentacao_modificacao_user_to_string;
        }
    
        $values = TarefaMovimentacao::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_tarefa_status_criacao_user_to_string($tarefa_status_criacao_user_to_string)
    {
        if(is_array($tarefa_status_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $tarefa_status_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->tarefa_status_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_status_criacao_user_to_string = $tarefa_status_criacao_user_to_string;
        }

        $this->vdata['tarefa_status_criacao_user_to_string'] = $this->tarefa_status_criacao_user_to_string;
    }

    public function get_tarefa_status_criacao_user_to_string()
    {
        if(!empty($this->tarefa_status_criacao_user_to_string))
        {
            return $this->tarefa_status_criacao_user_to_string;
        }
    
        $values = TarefaStatus::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_tarefa_status_modificacao_user_to_string($tarefa_status_modificacao_user_to_string)
    {
        if(is_array($tarefa_status_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $tarefa_status_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->tarefa_status_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_status_modificacao_user_to_string = $tarefa_status_modificacao_user_to_string;
        }

        $this->vdata['tarefa_status_modificacao_user_to_string'] = $this->tarefa_status_modificacao_user_to_string;
    }

    public function get_tarefa_status_modificacao_user_to_string()
    {
        if(!empty($this->tarefa_status_modificacao_user_to_string))
        {
            return $this->tarefa_status_modificacao_user_to_string;
        }
    
        $values = TarefaStatus::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_tarefa_usuario_master_tarefa_configuracao_to_string($tarefa_usuario_master_tarefa_configuracao_to_string)
    {
        if(is_array($tarefa_usuario_master_tarefa_configuracao_to_string))
        {
            $values = TarefaConfiguracao::where('id', 'in', $tarefa_usuario_master_tarefa_configuracao_to_string)->getIndexedArray('id', 'id');
            $this->tarefa_usuario_master_tarefa_configuracao_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_usuario_master_tarefa_configuracao_to_string = $tarefa_usuario_master_tarefa_configuracao_to_string;
        }

        $this->vdata['tarefa_usuario_master_tarefa_configuracao_to_string'] = $this->tarefa_usuario_master_tarefa_configuracao_to_string;
    }

    public function get_tarefa_usuario_master_tarefa_configuracao_to_string()
    {
        if(!empty($this->tarefa_usuario_master_tarefa_configuracao_to_string))
        {
            return $this->tarefa_usuario_master_tarefa_configuracao_to_string;
        }
    
        $values = TarefaUsuarioMaster::where('usuario_master_id', '=', $this->id)->getIndexedArray('tarefa_configuracao_id','{tarefa_configuracao->id}');
        return implode(', ', $values);
    }

    public function set_tarefa_usuario_master_usuario_master_to_string($tarefa_usuario_master_usuario_master_to_string)
    {
        if(is_array($tarefa_usuario_master_usuario_master_to_string))
        {
            $values = SystemUsers::where('id', 'in', $tarefa_usuario_master_usuario_master_to_string)->getIndexedArray('name', 'name');
            $this->tarefa_usuario_master_usuario_master_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_usuario_master_usuario_master_to_string = $tarefa_usuario_master_usuario_master_to_string;
        }

        $this->vdata['tarefa_usuario_master_usuario_master_to_string'] = $this->tarefa_usuario_master_usuario_master_to_string;
    }

    public function get_tarefa_usuario_master_usuario_master_to_string()
    {
        if(!empty($this->tarefa_usuario_master_usuario_master_to_string))
        {
            return $this->tarefa_usuario_master_usuario_master_to_string;
        }
    
        $values = TarefaUsuarioMaster::where('usuario_master_id', '=', $this->id)->getIndexedArray('usuario_master_id','{usuario_master->name}');
        return implode(', ', $values);
    }

    public function set_tarefa_vinculo_tarefa_to_string($tarefa_vinculo_tarefa_to_string)
    {
        if(is_array($tarefa_vinculo_tarefa_to_string))
        {
            $values = Tarefa::where('id', 'in', $tarefa_vinculo_tarefa_to_string)->getIndexedArray('titulo', 'titulo');
            $this->tarefa_vinculo_tarefa_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_vinculo_tarefa_to_string = $tarefa_vinculo_tarefa_to_string;
        }

        $this->vdata['tarefa_vinculo_tarefa_to_string'] = $this->tarefa_vinculo_tarefa_to_string;
    }

    public function get_tarefa_vinculo_tarefa_to_string()
    {
        if(!empty($this->tarefa_vinculo_tarefa_to_string))
        {
            return $this->tarefa_vinculo_tarefa_to_string;
        }
    
        $values = TarefaVinculo::where('modificacao_user_id', '=', $this->id)->getIndexedArray('tarefa_id','{tarefa->titulo}');
        return implode(', ', $values);
    }

    public function set_tarefa_vinculo_subtarefa_to_string($tarefa_vinculo_subtarefa_to_string)
    {
        if(is_array($tarefa_vinculo_subtarefa_to_string))
        {
            $values = Tarefa::where('id', 'in', $tarefa_vinculo_subtarefa_to_string)->getIndexedArray('titulo', 'titulo');
            $this->tarefa_vinculo_subtarefa_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_vinculo_subtarefa_to_string = $tarefa_vinculo_subtarefa_to_string;
        }

        $this->vdata['tarefa_vinculo_subtarefa_to_string'] = $this->tarefa_vinculo_subtarefa_to_string;
    }

    public function get_tarefa_vinculo_subtarefa_to_string()
    {
        if(!empty($this->tarefa_vinculo_subtarefa_to_string))
        {
            return $this->tarefa_vinculo_subtarefa_to_string;
        }
    
        $values = TarefaVinculo::where('modificacao_user_id', '=', $this->id)->getIndexedArray('subtarefa_id','{subtarefa->titulo}');
        return implode(', ', $values);
    }

    public function set_tarefa_vinculo_criacao_user_to_string($tarefa_vinculo_criacao_user_to_string)
    {
        if(is_array($tarefa_vinculo_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $tarefa_vinculo_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->tarefa_vinculo_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_vinculo_criacao_user_to_string = $tarefa_vinculo_criacao_user_to_string;
        }

        $this->vdata['tarefa_vinculo_criacao_user_to_string'] = $this->tarefa_vinculo_criacao_user_to_string;
    }

    public function get_tarefa_vinculo_criacao_user_to_string()
    {
        if(!empty($this->tarefa_vinculo_criacao_user_to_string))
        {
            return $this->tarefa_vinculo_criacao_user_to_string;
        }
    
        $values = TarefaVinculo::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_tarefa_vinculo_modificacao_user_to_string($tarefa_vinculo_modificacao_user_to_string)
    {
        if(is_array($tarefa_vinculo_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $tarefa_vinculo_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->tarefa_vinculo_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_vinculo_modificacao_user_to_string = $tarefa_vinculo_modificacao_user_to_string;
        }

        $this->vdata['tarefa_vinculo_modificacao_user_to_string'] = $this->tarefa_vinculo_modificacao_user_to_string;
    }

    public function get_tarefa_vinculo_modificacao_user_to_string()
    {
        if(!empty($this->tarefa_vinculo_modificacao_user_to_string))
        {
            return $this->tarefa_vinculo_modificacao_user_to_string;
        }
    
        $values = TarefaVinculo::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_template_escritorio_escritorio_to_string($template_escritorio_escritorio_to_string)
    {
        if(is_array($template_escritorio_escritorio_to_string))
        {
            $values = Escritorio::where('id', 'in', $template_escritorio_escritorio_to_string)->getIndexedArray('nome', 'nome');
            $this->template_escritorio_escritorio_to_string = implode(', ', $values);
        }
        else
        {
            $this->template_escritorio_escritorio_to_string = $template_escritorio_escritorio_to_string;
        }

        $this->vdata['template_escritorio_escritorio_to_string'] = $this->template_escritorio_escritorio_to_string;
    }

    public function get_template_escritorio_escritorio_to_string()
    {
        if(!empty($this->template_escritorio_escritorio_to_string))
        {
            return $this->template_escritorio_escritorio_to_string;
        }
    
        $values = TemplateEscritorio::where('modificacao_user_id', '=', $this->id)->getIndexedArray('escritorio_id','{escritorio->nome}');
        return implode(', ', $values);
    }

    public function set_template_escritorio_criacao_user_to_string($template_escritorio_criacao_user_to_string)
    {
        if(is_array($template_escritorio_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $template_escritorio_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->template_escritorio_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->template_escritorio_criacao_user_to_string = $template_escritorio_criacao_user_to_string;
        }

        $this->vdata['template_escritorio_criacao_user_to_string'] = $this->template_escritorio_criacao_user_to_string;
    }

    public function get_template_escritorio_criacao_user_to_string()
    {
        if(!empty($this->template_escritorio_criacao_user_to_string))
        {
            return $this->template_escritorio_criacao_user_to_string;
        }
    
        $values = TemplateEscritorio::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_template_escritorio_modificacao_user_to_string($template_escritorio_modificacao_user_to_string)
    {
        if(is_array($template_escritorio_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $template_escritorio_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->template_escritorio_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->template_escritorio_modificacao_user_to_string = $template_escritorio_modificacao_user_to_string;
        }

        $this->vdata['template_escritorio_modificacao_user_to_string'] = $this->template_escritorio_modificacao_user_to_string;
    }

    public function get_template_escritorio_modificacao_user_to_string()
    {
        if(!empty($this->template_escritorio_modificacao_user_to_string))
        {
            return $this->template_escritorio_modificacao_user_to_string;
        }
    
        $values = TemplateEscritorio::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_tipo_andamento_criacao_user_to_string($tipo_andamento_criacao_user_to_string)
    {
        if(is_array($tipo_andamento_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $tipo_andamento_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->tipo_andamento_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->tipo_andamento_criacao_user_to_string = $tipo_andamento_criacao_user_to_string;
        }

        $this->vdata['tipo_andamento_criacao_user_to_string'] = $this->tipo_andamento_criacao_user_to_string;
    }

    public function get_tipo_andamento_criacao_user_to_string()
    {
        if(!empty($this->tipo_andamento_criacao_user_to_string))
        {
            return $this->tipo_andamento_criacao_user_to_string;
        }
    
        $values = TipoAndamento::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_tipo_andamento_modificacao_user_to_string($tipo_andamento_modificacao_user_to_string)
    {
        if(is_array($tipo_andamento_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $tipo_andamento_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->tipo_andamento_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->tipo_andamento_modificacao_user_to_string = $tipo_andamento_modificacao_user_to_string;
        }

        $this->vdata['tipo_andamento_modificacao_user_to_string'] = $this->tipo_andamento_modificacao_user_to_string;
    }

    public function get_tipo_andamento_modificacao_user_to_string()
    {
        if(!empty($this->tipo_andamento_modificacao_user_to_string))
        {
            return $this->tipo_andamento_modificacao_user_to_string;
        }
    
        $values = TipoAndamento::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_tipo_compromisso_criacao_user_to_string($tipo_compromisso_criacao_user_to_string)
    {
        if(is_array($tipo_compromisso_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $tipo_compromisso_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->tipo_compromisso_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->tipo_compromisso_criacao_user_to_string = $tipo_compromisso_criacao_user_to_string;
        }

        $this->vdata['tipo_compromisso_criacao_user_to_string'] = $this->tipo_compromisso_criacao_user_to_string;
    }

    public function get_tipo_compromisso_criacao_user_to_string()
    {
        if(!empty($this->tipo_compromisso_criacao_user_to_string))
        {
            return $this->tipo_compromisso_criacao_user_to_string;
        }
    
        $values = TipoCompromisso::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_tipo_compromisso_modificacao_user_to_string($tipo_compromisso_modificacao_user_to_string)
    {
        if(is_array($tipo_compromisso_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $tipo_compromisso_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->tipo_compromisso_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->tipo_compromisso_modificacao_user_to_string = $tipo_compromisso_modificacao_user_to_string;
        }

        $this->vdata['tipo_compromisso_modificacao_user_to_string'] = $this->tipo_compromisso_modificacao_user_to_string;
    }

    public function get_tipo_compromisso_modificacao_user_to_string()
    {
        if(!empty($this->tipo_compromisso_modificacao_user_to_string))
        {
            return $this->tipo_compromisso_modificacao_user_to_string;
        }
    
        $values = TipoCompromisso::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_tipo_conta_criacao_user_to_string($tipo_conta_criacao_user_to_string)
    {
        if(is_array($tipo_conta_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $tipo_conta_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->tipo_conta_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->tipo_conta_criacao_user_to_string = $tipo_conta_criacao_user_to_string;
        }

        $this->vdata['tipo_conta_criacao_user_to_string'] = $this->tipo_conta_criacao_user_to_string;
    }

    public function get_tipo_conta_criacao_user_to_string()
    {
        if(!empty($this->tipo_conta_criacao_user_to_string))
        {
            return $this->tipo_conta_criacao_user_to_string;
        }
    
        $values = TipoConta::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_tipo_conta_modificacao_user_to_string($tipo_conta_modificacao_user_to_string)
    {
        if(is_array($tipo_conta_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $tipo_conta_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->tipo_conta_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->tipo_conta_modificacao_user_to_string = $tipo_conta_modificacao_user_to_string;
        }

        $this->vdata['tipo_conta_modificacao_user_to_string'] = $this->tipo_conta_modificacao_user_to_string;
    }

    public function get_tipo_conta_modificacao_user_to_string()
    {
        if(!empty($this->tipo_conta_modificacao_user_to_string))
        {
            return $this->tipo_conta_modificacao_user_to_string;
        }
    
        $values = TipoConta::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_tipo_documento_financeiro_tipo_conta_to_string($tipo_documento_financeiro_tipo_conta_to_string)
    {
        if(is_array($tipo_documento_financeiro_tipo_conta_to_string))
        {
            $values = TipoConta::where('id', 'in', $tipo_documento_financeiro_tipo_conta_to_string)->getIndexedArray('nome', 'nome');
            $this->tipo_documento_financeiro_tipo_conta_to_string = implode(', ', $values);
        }
        else
        {
            $this->tipo_documento_financeiro_tipo_conta_to_string = $tipo_documento_financeiro_tipo_conta_to_string;
        }

        $this->vdata['tipo_documento_financeiro_tipo_conta_to_string'] = $this->tipo_documento_financeiro_tipo_conta_to_string;
    }

    public function get_tipo_documento_financeiro_tipo_conta_to_string()
    {
        if(!empty($this->tipo_documento_financeiro_tipo_conta_to_string))
        {
            return $this->tipo_documento_financeiro_tipo_conta_to_string;
        }
    
        $values = TipoDocumentoFinanceiro::where('modificacao_user_id', '=', $this->id)->getIndexedArray('tipo_conta_id','{tipo_conta->nome}');
        return implode(', ', $values);
    }

    public function set_tipo_documento_financeiro_padrao_to_string($tipo_documento_financeiro_padrao_to_string)
    {
        if(is_array($tipo_documento_financeiro_padrao_to_string))
        {
            $values = TipoDocFinanceiroPadrao::where('id', 'in', $tipo_documento_financeiro_padrao_to_string)->getIndexedArray('nome', 'nome');
            $this->tipo_documento_financeiro_padrao_to_string = implode(', ', $values);
        }
        else
        {
            $this->tipo_documento_financeiro_padrao_to_string = $tipo_documento_financeiro_padrao_to_string;
        }

        $this->vdata['tipo_documento_financeiro_padrao_to_string'] = $this->tipo_documento_financeiro_padrao_to_string;
    }

    public function get_tipo_documento_financeiro_padrao_to_string()
    {
        if(!empty($this->tipo_documento_financeiro_padrao_to_string))
        {
            return $this->tipo_documento_financeiro_padrao_to_string;
        }
    
        $values = TipoDocumentoFinanceiro::where('modificacao_user_id', '=', $this->id)->getIndexedArray('padrao_id','{padrao->nome}');
        return implode(', ', $values);
    }

    public function set_tipo_documento_financeiro_criacao_user_to_string($tipo_documento_financeiro_criacao_user_to_string)
    {
        if(is_array($tipo_documento_financeiro_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $tipo_documento_financeiro_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->tipo_documento_financeiro_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->tipo_documento_financeiro_criacao_user_to_string = $tipo_documento_financeiro_criacao_user_to_string;
        }

        $this->vdata['tipo_documento_financeiro_criacao_user_to_string'] = $this->tipo_documento_financeiro_criacao_user_to_string;
    }

    public function get_tipo_documento_financeiro_criacao_user_to_string()
    {
        if(!empty($this->tipo_documento_financeiro_criacao_user_to_string))
        {
            return $this->tipo_documento_financeiro_criacao_user_to_string;
        }
    
        $values = TipoDocumentoFinanceiro::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_tipo_documento_financeiro_modificacao_user_to_string($tipo_documento_financeiro_modificacao_user_to_string)
    {
        if(is_array($tipo_documento_financeiro_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $tipo_documento_financeiro_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->tipo_documento_financeiro_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->tipo_documento_financeiro_modificacao_user_to_string = $tipo_documento_financeiro_modificacao_user_to_string;
        }

        $this->vdata['tipo_documento_financeiro_modificacao_user_to_string'] = $this->tipo_documento_financeiro_modificacao_user_to_string;
    }

    public function get_tipo_documento_financeiro_modificacao_user_to_string()
    {
        if(!empty($this->tipo_documento_financeiro_modificacao_user_to_string))
        {
            return $this->tipo_documento_financeiro_modificacao_user_to_string;
        }
    
        $values = TipoDocumentoFinanceiro::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_tipo_modelo_documento_criacao_user_to_string($tipo_modelo_documento_criacao_user_to_string)
    {
        if(is_array($tipo_modelo_documento_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $tipo_modelo_documento_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->tipo_modelo_documento_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->tipo_modelo_documento_criacao_user_to_string = $tipo_modelo_documento_criacao_user_to_string;
        }

        $this->vdata['tipo_modelo_documento_criacao_user_to_string'] = $this->tipo_modelo_documento_criacao_user_to_string;
    }

    public function get_tipo_modelo_documento_criacao_user_to_string()
    {
        if(!empty($this->tipo_modelo_documento_criacao_user_to_string))
        {
            return $this->tipo_modelo_documento_criacao_user_to_string;
        }
    
        $values = TipoModeloDocumento::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_tipo_modelo_documento_modificacao_user_to_string($tipo_modelo_documento_modificacao_user_to_string)
    {
        if(is_array($tipo_modelo_documento_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $tipo_modelo_documento_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->tipo_modelo_documento_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->tipo_modelo_documento_modificacao_user_to_string = $tipo_modelo_documento_modificacao_user_to_string;
        }

        $this->vdata['tipo_modelo_documento_modificacao_user_to_string'] = $this->tipo_modelo_documento_modificacao_user_to_string;
    }

    public function get_tipo_modelo_documento_modificacao_user_to_string()
    {
        if(!empty($this->tipo_modelo_documento_modificacao_user_to_string))
        {
            return $this->tipo_modelo_documento_modificacao_user_to_string;
        }
    
        $values = TipoModeloDocumento::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_tipo_pagamento_criacao_user_to_string($tipo_pagamento_criacao_user_to_string)
    {
        if(is_array($tipo_pagamento_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $tipo_pagamento_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->tipo_pagamento_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->tipo_pagamento_criacao_user_to_string = $tipo_pagamento_criacao_user_to_string;
        }

        $this->vdata['tipo_pagamento_criacao_user_to_string'] = $this->tipo_pagamento_criacao_user_to_string;
    }

    public function get_tipo_pagamento_criacao_user_to_string()
    {
        if(!empty($this->tipo_pagamento_criacao_user_to_string))
        {
            return $this->tipo_pagamento_criacao_user_to_string;
        }
    
        $values = TipoPagamento::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_tipo_pagamento_modificacao_user_to_string($tipo_pagamento_modificacao_user_to_string)
    {
        if(is_array($tipo_pagamento_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $tipo_pagamento_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->tipo_pagamento_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->tipo_pagamento_modificacao_user_to_string = $tipo_pagamento_modificacao_user_to_string;
        }

        $this->vdata['tipo_pagamento_modificacao_user_to_string'] = $this->tipo_pagamento_modificacao_user_to_string;
    }

    public function get_tipo_pagamento_modificacao_user_to_string()
    {
        if(!empty($this->tipo_pagamento_modificacao_user_to_string))
        {
            return $this->tipo_pagamento_modificacao_user_to_string;
        }
    
        $values = TipoPagamento::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_tipo_prazo_criacao_user_to_string($tipo_prazo_criacao_user_to_string)
    {
        if(is_array($tipo_prazo_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $tipo_prazo_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->tipo_prazo_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->tipo_prazo_criacao_user_to_string = $tipo_prazo_criacao_user_to_string;
        }

        $this->vdata['tipo_prazo_criacao_user_to_string'] = $this->tipo_prazo_criacao_user_to_string;
    }

    public function get_tipo_prazo_criacao_user_to_string()
    {
        if(!empty($this->tipo_prazo_criacao_user_to_string))
        {
            return $this->tipo_prazo_criacao_user_to_string;
        }
    
        $values = TipoPrazo::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_tipo_prazo_modificacao_user_to_string($tipo_prazo_modificacao_user_to_string)
    {
        if(is_array($tipo_prazo_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $tipo_prazo_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->tipo_prazo_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->tipo_prazo_modificacao_user_to_string = $tipo_prazo_modificacao_user_to_string;
        }

        $this->vdata['tipo_prazo_modificacao_user_to_string'] = $this->tipo_prazo_modificacao_user_to_string;
    }

    public function get_tipo_prazo_modificacao_user_to_string()
    {
        if(!empty($this->tipo_prazo_modificacao_user_to_string))
        {
            return $this->tipo_prazo_modificacao_user_to_string;
        }
    
        $values = TipoPrazo::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_tipo_profissional_criacao_user_to_string($tipo_profissional_criacao_user_to_string)
    {
        if(is_array($tipo_profissional_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $tipo_profissional_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->tipo_profissional_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->tipo_profissional_criacao_user_to_string = $tipo_profissional_criacao_user_to_string;
        }

        $this->vdata['tipo_profissional_criacao_user_to_string'] = $this->tipo_profissional_criacao_user_to_string;
    }

    public function get_tipo_profissional_criacao_user_to_string()
    {
        if(!empty($this->tipo_profissional_criacao_user_to_string))
        {
            return $this->tipo_profissional_criacao_user_to_string;
        }
    
        $values = TipoProfissional::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_tipo_profissional_modificacao_user_to_string($tipo_profissional_modificacao_user_to_string)
    {
        if(is_array($tipo_profissional_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $tipo_profissional_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->tipo_profissional_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->tipo_profissional_modificacao_user_to_string = $tipo_profissional_modificacao_user_to_string;
        }

        $this->vdata['tipo_profissional_modificacao_user_to_string'] = $this->tipo_profissional_modificacao_user_to_string;
    }

    public function get_tipo_profissional_modificacao_user_to_string()
    {
        if(!empty($this->tipo_profissional_modificacao_user_to_string))
        {
            return $this->tipo_profissional_modificacao_user_to_string;
        }
    
        $values = TipoProfissional::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_tribunal_criacao_user_to_string($tribunal_criacao_user_to_string)
    {
        if(is_array($tribunal_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $tribunal_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->tribunal_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->tribunal_criacao_user_to_string = $tribunal_criacao_user_to_string;
        }

        $this->vdata['tribunal_criacao_user_to_string'] = $this->tribunal_criacao_user_to_string;
    }

    public function get_tribunal_criacao_user_to_string()
    {
        if(!empty($this->tribunal_criacao_user_to_string))
        {
            return $this->tribunal_criacao_user_to_string;
        }
    
        $values = Tribunal::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_tribunal_modificacao_user_to_string($tribunal_modificacao_user_to_string)
    {
        if(is_array($tribunal_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $tribunal_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->tribunal_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->tribunal_modificacao_user_to_string = $tribunal_modificacao_user_to_string;
        }

        $this->vdata['tribunal_modificacao_user_to_string'] = $this->tribunal_modificacao_user_to_string;
    }

    public function get_tribunal_modificacao_user_to_string()
    {
        if(!empty($this->tribunal_modificacao_user_to_string))
        {
            return $this->tribunal_modificacao_user_to_string;
        }
    
        $values = Tribunal::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_unidade_indexador_criacao_user_to_string($unidade_indexador_criacao_user_to_string)
    {
        if(is_array($unidade_indexador_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $unidade_indexador_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->unidade_indexador_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->unidade_indexador_criacao_user_to_string = $unidade_indexador_criacao_user_to_string;
        }

        $this->vdata['unidade_indexador_criacao_user_to_string'] = $this->unidade_indexador_criacao_user_to_string;
    }

    public function get_unidade_indexador_criacao_user_to_string()
    {
        if(!empty($this->unidade_indexador_criacao_user_to_string))
        {
            return $this->unidade_indexador_criacao_user_to_string;
        }
    
        $values = UnidadeIndexador::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_unidade_indexador_modificacao_user_to_string($unidade_indexador_modificacao_user_to_string)
    {
        if(is_array($unidade_indexador_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $unidade_indexador_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->unidade_indexador_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->unidade_indexador_modificacao_user_to_string = $unidade_indexador_modificacao_user_to_string;
        }

        $this->vdata['unidade_indexador_modificacao_user_to_string'] = $this->unidade_indexador_modificacao_user_to_string;
    }

    public function get_unidade_indexador_modificacao_user_to_string()
    {
        if(!empty($this->unidade_indexador_modificacao_user_to_string))
        {
            return $this->unidade_indexador_modificacao_user_to_string;
        }
    
        $values = UnidadeIndexador::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_vara_criacao_user_to_string($vara_criacao_user_to_string)
    {
        if(is_array($vara_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $vara_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->vara_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->vara_criacao_user_to_string = $vara_criacao_user_to_string;
        }

        $this->vdata['vara_criacao_user_to_string'] = $this->vara_criacao_user_to_string;
    }

    public function get_vara_criacao_user_to_string()
    {
        if(!empty($this->vara_criacao_user_to_string))
        {
            return $this->vara_criacao_user_to_string;
        }
    
        $values = Vara::where('modificacao_user_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_vara_modificacao_user_to_string($vara_modificacao_user_to_string)
    {
        if(is_array($vara_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $vara_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->vara_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->vara_modificacao_user_to_string = $vara_modificacao_user_to_string;
        }

        $this->vdata['vara_modificacao_user_to_string'] = $this->vara_modificacao_user_to_string;
    }

    public function get_vara_modificacao_user_to_string()
    {
        if(!empty($this->vara_modificacao_user_to_string))
        {
            return $this->vara_modificacao_user_to_string;
        }
    
        $values = Vara::where('modificacao_user_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    /**
     * Return the user' group's
     * @return Collection of SystemGroup
     */
    public function getSystemUserGroups()
    {
        return parent::loadAggregate('SystemGroup', 'SystemUserGroup', 'system_user_id', 'system_group_id', $this->id);
    }

    /**
     * Return the user' unit's
     * @return Collection of SystemUnit
     */
    public function getSystemUserUnits()
    {
        return parent::loadAggregate('SystemUnit', 'SystemUserUnit', 'system_user_id', 'system_unit_id', $this->id);
    }

    /**
     * Return the user' program's
     * @return Collection of SystemProgram
     */
    public function getSystemUserPrograms()
    {
        return parent::loadAggregate('SystemProgram', 'SystemUserProgram', 'system_user_id', 'system_program_id', $this->id);
    }

    /**
     * Returns the frontpage name
     */
    public function get_frontpage_name()
    {
        // loads the associated object
        if (empty($this->frontpage))
            $this->frontpage = new SystemProgram($this->frontpage_id);

        // returns the associated object
        return $this->frontpage->name;
    }

    /**
     * Returns the unit
     */
    public function get_unit()
    {
        // loads the associated object
        if (empty($this->unit))
            $this->unit = new SystemUnit($this->system_unit_id);

        // returns the associated object
        return $this->unit;
    }

    /**
     * Add a Group to the user
     * @param $object Instance of SystemGroup
     */
    public function addSystemUserGroup(SystemGroup $systemgroup)
    {
        $object = new SystemUserGroup;
        $object->system_group_id = $systemgroup->id;
        $object->system_user_id = $this->id;
        $object->store();
    }

    /**
     * Add a Unit to the user
     * @param $object Instance of SystemUnit
     */
    public function addSystemUserUnit(SystemUnit $systemunit)
    {
        $object = new SystemUserUnit;
        $object->system_unit_id = $systemunit->id;
        $object->system_user_id = $this->id;
        $object->store();
    }

    /**
     * Add a program to the user
     * @param $object Instance of SystemProgram
     */
    public function addSystemUserProgram(SystemProgram $systemprogram)
    {
        $object = new SystemUserProgram;
        $object->system_program_id = $systemprogram->id;
        $object->system_user_id = $this->id;
        $object->store();
    }

    /**
     * Get user group ids
     */
    public function getSystemUserGroupIds( $as_string = false )
    {
        $groupids = array();
        $groups = $this->getSystemUserGroups();
        if ($groups)
        {
            foreach ($groups as $group)
            {
                $groupids[] = $group->id;
            }
        }
    
        if ($as_string)
        {
            return implode(',', $groupids);
        }
    
        return $groupids;
    }

    /**
     * Get user unit ids
     */
    public function getSystemUserUnitIds( $as_string = false )
    {
        $unitids = array();
        $units = $this->getSystemUserUnits();
        if ($units)
        {
            foreach ($units as $unit)
            {
                $unitids[] = $unit->id;
            }
        }
    
        if ($as_string)
        {
            return implode(',', $unitids);
        }
    
        return $unitids;
    }

    /**
     * Get user group names
     */
    public function getSystemUserGroupNames()
    {
        $groupnames = array();
        $groups = $this->getSystemUserGroups();
        if ($groups)
        {
            foreach ($groups as $group)
            {
                $groupnames[] = $group->name;
            }
        }
    
        return implode(',', $groupnames);
    }

    /**
     * Reset aggregates
     */
    public function clearParts()
    {
        SystemUserGroup::where('system_user_id', '=', $this->id)->delete();
        SystemUserUnit::where('system_user_id', '=', $this->id)->delete();
        SystemUserProgram::where('system_user_id', '=', $this->id)->delete();
    }

    /**
     * Delete the object and its aggregates
     * @param $id object ID
     */
    public function delete($id = NULL)
    {
        // delete the related System_userSystem_user_group objects
        $id = isset($id) ? $id : $this->id;
    
        SystemUserGroup::where('system_user_id', '=', $id)->delete();
        SystemUserUnit::where('system_user_id', '=', $id)->delete();
        SystemUserProgram::where('system_user_id', '=', $id)->delete();
    
        // delete the object itself
        parent::delete($id);
    }

    /**
     * Validate user login
     * @param $login String with user login
     */
    public static function validate($login)
    {
        $user = self::newFromLogin($login);
    
        if ($user instanceof SystemUsers)
        {
            if ($user->active == 'N')
            {
                throw new Exception(_t('Inactive user'));
            }
        }
        else
        {
            throw new Exception(_t('User not found'));
        }
    
        return $user;
    }

    /**
     * Authenticate the user
     * @param $login String with user login
     * @param $password String with user password
     * @returns TRUE if the password matches, otherwise throw Exception
     */
    public static function authenticate($login, $password)
    {
        $user = self::newFromLogin($login);
        if (hash_equals($user->password, md5($password)))
        {
            self::updatePasswordHash($user, $password);
        }
        if (password_verify($password, $user->password)) 
        {
            if (password_needs_rehash($user->password, PASSWORD_DEFAULT))
            {
                self::updatePasswordHash($user, $password);
            }
        }
        else
        {
            throw new Exception(_t('Invalid username or password'));
        }
        return $user;
    }

    /**
     * Update the user password to a new algo
     * @param $user SystemUsers
     * @param $password String with user password
     * @returns void
     */
    private static function updatePasswordHash($user, $userPassword)
    {
        $user->password = password_hash($userPassword, PASSWORD_DEFAULT);
        $user->store();
    }
    /**
     * Return the action of programs the user has permission to run
     */
    public function getProgramsActions()
    {
        $programs_actions = [];
        foreach( $this->getSystemUserGroups() as $group )
        {
            foreach( $group->getSystemPrograms() as $prog )
            {
                if($prog->actions)
                {
                    if(empty($programs_actions[$prog->controller]))
                    {
                        $programs_actions[$prog->controller] = [];
                    }
                    $actions = array_map(function($actions){
                        return $actions->action;
                    },json_decode($prog->actions));
                    $allowed_actions = json_decode($prog->allowed_actions);
                    $allowed_actions = array_flip($allowed_actions);
                    if($actions)
                    {
                        foreach($actions as $action)
                        {
                            if(!isset($programs_actions[$prog->controller][$action]))
                            {
                                $programs_actions[$prog->controller][$action] = false;
                            }
                            if(isset($allowed_actions[$action]))
                            {
                                $programs_actions[$prog->controller][$action] = true;
                            }
                        }   
                    }
                }
            }
        }
        return $programs_actions;
    }

    /**
     * Returns a SystemUser object based on its login
     * @param $login String with user login
     */
    static public function newFromLogin($login)
    {
        return SystemUsers::where('login', '=', $login)->first();
    }

    /**
     * Returns a SystemUser object based on its e-mail
     * @param $email String with user email
     */
    static public function newFromEmail($email)
    {
        return SystemUsers::where('email', '=', $email)->first();
    }

    /**
     * Return the programs the user has permission to run
     */
    public function getPrograms()
    {
        $programs = array();
    
        foreach( $this->getSystemUserGroups() as $group )
        {
            foreach( $group->getSystemPrograms() as $prog )
            {
                $programs[$prog->controller] = true;
            }
        }
            
        foreach( $this->getSystemUserPrograms() as $prog )
        {
            $programs[$prog->controller] = true;
        }
    
        return $programs;
    }

    /**
     * Return the programs the user has permission to run
     */
    public function getProgramsList()
    {
        $programs = array();
    
        foreach( $this->getSystemUserGroups() as $group )
        {
            foreach( $group->getSystemPrograms() as $prog )
            {
                $programs[$prog->controller] = $prog->name;
            }
        }
            
        foreach( $this->getSystemUserPrograms() as $prog )
        {
            $programs[$prog->controller] = $prog->name;
        }
    
        asort($programs);
        return $programs;
    }

    /**
     * Check if the user is within a group
     */
    public function checkInGroup( SystemGroup $group )
    {
        $user_groups = array();
        foreach( $this->getSystemUserGroups() as $user_group )
        {
            $user_groups[] = $user_group->id;
        }

        return in_array($group->id, $user_groups);
    }

    /**
     *
     */
    public static function getInGroups( $groups )
    {
        $collection = [];
        $users = self::all();
        if ($users)
        {
            foreach ($users as $user)
            {
                foreach ($groups as $group)
                {
                    if ($user->checkInGroup($group))
                    {
                        $collection[] = $user;
                    }
                }
            }
        }
        return $collection;
    }

    /**
     * Clone the entire object and related ones
     */
    public function cloneUser()
    {
        $groups   = $this->getSystemUserGroups();
        $units    = $this->getSystemUserUnits();
        $programs = $this->getSystemUserPrograms();
        unset($this->id);
        $this->name .= ' (clone)';
        $this->store();
        if ($groups)
        {
            foreach ($groups as $group)
            {
                $this->addSystemUserGroup( $group );
            }
        }
        if ($units)
        {
            foreach ($units as $unit)
            {
                $this->addSystemUserUnit( $unit );
            }
        }
        if ($programs)
        {
            foreach ($programs as $program)
            {
                $this->addSystemUserProgram( $program );
            }
        }
    }

                    
}

