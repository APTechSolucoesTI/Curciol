<?php
// app/model/ParceriaLevantamento.php
class ParceriaLevantamento extends TRecord
{
    const TABLENAME  = 'parceria_levantamento';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'serial';
    const CREATEDAT  = 'data_criacao';
    const UPDATEDAT  = 'data_modificacao';

    const TIPO_FIXA     = 'FIXA';
    const TIPO_TEMPORAL = 'TEMPORAL';

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        foreach (['nome', 'tipo', 'marco1', 'marco2', 'ativo', 'data_criacao', 'criacao_user_id', 'data_modificacao', 'modificacao_user_id'] as $a) {
            parent::addAttribute($a);
        }
    }

    /** @return ParceriaLevantamentoParticipante[] em ordem */
    public function participantes(): array
    {
        return ParceriaLevantamentoParticipante::where('parceria_levantamento_id', '=', $this->id)->orderBy('ordem')->load() ?: [];
    }
}
