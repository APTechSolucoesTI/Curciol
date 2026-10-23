<?php
// app/model/RepasseColaborador.php
/** Conta a pagar ao colaborador gerada por um recebimento de conta a receber com repasse. */
class RepasseColaborador extends TRecord
{
    const TABLENAME  = 'repasse_colaborador';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'serial';

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        foreach (['pessoa_id', 'conta_pagar_id', 'lancamento_pagar_id', 'conta_receber_id', 'lancamento_receber_id', 'extrato_recebimento_id',
                  'levantamento_financeiro_id', 'percentual', 'valor_recebido', 'valor', 'data_recebimento', 'data_criacao', 'criacao_user_id'] as $a) {
            parent::addAttribute($a);
        }
    }
}
