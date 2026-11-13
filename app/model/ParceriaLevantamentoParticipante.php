<?php
// app/model/ParceriaLevantamentoParticipante.php
class ParceriaLevantamentoParticipante extends TRecord
{
    const TABLENAME  = 'parceria_levantamento_participante';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'serial';

    const NIVEL_FIXO    = 'FIXO';     // parceria fixa; na por tempo: percentual nao muda (entre e depois = antes)
    const NIVEL_MARCO1  = 'MARCO1';   // percentual muda so no 1o marco (entre = depois)
    const NIVEL_MARCO2  = 'MARCO2';   // percentual muda so no 2o marco (entre = antes)
    const NIVEL_MARCO12 = 'MARCO12';  // percentual muda nos dois marcos (entre informado)

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        foreach (['parceria_levantamento_id', 'pessoa_id', 'nivel', 'percentual', 'percentual_antes', 'percentual_entre', 'percentual_depois', 'recebe_diferenca', 'ordem'] as $a) {
            parent::addAttribute($a);
        }
    }

    /** Linha para o ParceriaPercentualService. */
    public function paraCalculo(): array
    {
        return ['pessoa_id' => $this->pessoa_id, 'nivel' => $this->nivel, 'percentual' => $this->percentual,
                'percentual_antes' => $this->percentual_antes, 'percentual_entre' => $this->percentual_entre,
                'percentual_depois' => $this->percentual_depois, 'recebe_diferenca' => $this->recebe_diferenca];
    }
}
