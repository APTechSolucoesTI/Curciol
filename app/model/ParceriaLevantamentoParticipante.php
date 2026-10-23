<?php
// app/model/ParceriaLevantamentoParticipante.php
class ParceriaLevantamentoParticipante extends TRecord
{
    const TABLENAME  = 'parceria_levantamento_participante';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'serial';

    const NIVEL_FIXO   = 'FIXO';
    const NIVEL_MARCO1 = 'MARCO1';
    const NIVEL_MARCO2 = 'MARCO2';

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        foreach (['parceria_levantamento_id', 'pessoa_id', 'nivel', 'percentual', 'percentual_antes', 'percentual_depois', 'recebe_diferenca', 'ordem'] as $a) {
            parent::addAttribute($a);
        }
    }
}
