<?php
// app/model/LevantamentoFinanceiroRepasse.php
class LevantamentoFinanceiroRepasse extends TRecord
{
    const TABLENAME  = 'levantamento_financeiro_repasse';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'serial';

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        foreach (['levantamento_financeiro_id', 'pessoa_id', 'percentual', 'valor', 'ajuste_centavos'] as $a) {
            parent::addAttribute($a);
        }
    }
}
