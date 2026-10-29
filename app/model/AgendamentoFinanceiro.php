<?php
// app/model/AgendamentoFinanceiro.php
/** Financeiro gerado pelo agendamento (Gerar financeiro, 29/10/2026): um por agendamento. */
class AgendamentoFinanceiro extends TRecord
{
    const TABLENAME  = 'agendamento_financeiro';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'serial';

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        foreach (['agendamento_id', 'conta_receber_id', 'lancamento_id', 'parceria_levantamento_id', 'valor', 'conta_caixa_id',
                  'quitado_ao_gerar', 'data_criacao', 'criacao_user_id'] as $a) {
            parent::addAttribute($a);
        }
    }
}
