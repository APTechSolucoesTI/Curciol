<?php

/**
 * Vinculo direto entre um processo e um cliente.
 *
 * O caminho historico do sistema para saber de quem e um processo passa
 * por contrato_processo -> contrato_pessoa. Um pre-processo pode nascer
 * antes de existir contrato, entao precisa de um vinculo proprio. Esta
 * tabela nao substitui o caminho por contrato: a view processo_view une
 * os dois.
 */
class ProcessoCliente extends TRecord
{
    const TABLENAME  = 'processo_cliente';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const CREATEDAT  = 'data_criacao';
    const CREATED_BY_USER_ID = 'criacao_user_id';

    private Processo $processo;
    private Pessoa $cliente;

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('processo_id');
        parent::addAttribute('cliente_id');
        parent::addAttribute('data_criacao');
        parent::addAttribute('criacao_user_id');
    }

    /**
     * Method set_processo
     * @param $object Instance of Processo
     */
    public function set_processo(Processo $object)
    {
        $this->processo = $object;
        $this->processo_id = $object->id;
    }

    /**
     * Method get_processo
     * @returns Processo instance
     */
    public function get_processo()
    {
        if (empty($this->processo))
            $this->processo = new Processo($this->processo_id);

        return $this->processo;
    }

    /**
     * Method set_cliente
     * @param $object Instance of Pessoa
     */
    public function set_cliente(Pessoa $object)
    {
        $this->cliente = $object;
        $this->cliente_id = $object->id;
    }

    /**
     * Method get_cliente
     * @returns Pessoa instance
     */
    public function get_cliente()
    {
        if (empty($this->cliente))
            $this->cliente = new Pessoa($this->cliente_id);

        return $this->cliente;
    }
}
