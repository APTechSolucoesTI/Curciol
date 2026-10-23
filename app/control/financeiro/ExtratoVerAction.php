<?php
/**
 * Acao "Visualizar" do Extrato (23/10/2026): uma acao so para todas as linhas, para os icones ficarem alinhados na
 * mesma coluna. Linha comum abre o extrato (ExtratoFormView); linha de lote (ExtratoLoteService) abre a cortina do lote
 * (LoteExtratoView) com outro icone.
 */
class ExtratoVerAction extends TDataGridAction
{
    public function prepare($object)
    {
        if (ExtratoLoteService::ehLote($object)) {
            $lote = new TDataGridAction(['LoteExtratoView', 'onShow'], ['register_state' => 'false', 'lote_id' => (int) $object->lote_pagamento_id]);
            $lote->setUseButton(false);
            $lote->setLabel('Ver lote');
            $lote->setImage('fas:layer-group #0d4069');
            $lote->setField('id');
            return $lote->prepare($object);
        }
        return parent::prepare($object);
    }
}
