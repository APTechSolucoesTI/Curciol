<?php
// app/model/LevantamentoFinanceiro.php
class LevantamentoFinanceiro extends TRecord
{
    const TABLENAME  = 'levantamento_financeiro';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'serial';

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        foreach (['requisicao_pagamento_etapa3_id', 'requisicao_pagamento_id', 'requisicao_pagamento_cliente_id', 'contrato_id', 'processo_id',
                  'beneficiario_credito_id', 'parceria_levantamento_id', 'regra', 'bruto_depositado', 'valor_mle', 'descontos',
                  'pct_honorarios', 'pct_nf', 'data_levantamento', 'valor_levantado', 'data_distribuicao', 'fracao_a', 'fracao_p', 'fator_d',
                  'u', 'v', 'w', 'z', 'aa', 'ac', 'ad', 'conta_receber_id', 'conta_pagar_id', 'extrato_id', 'conta_caixa_id',
                  'data_criacao', 'criacao_user_id'] as $a) {
            parent::addAttribute($a);
        }
    }

    /** @return LevantamentoFinanceiroRepasse[] */
    public function repasses(): array
    {
        return LevantamentoFinanceiroRepasse::where('levantamento_financeiro_id', '=', $this->id)->orderBy('id')->load() ?: [];
    }
}
