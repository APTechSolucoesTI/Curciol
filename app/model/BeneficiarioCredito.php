<?php
// app/model/BeneficiarioCredito.php
class BeneficiarioCredito extends TRecord
{
    const TABLENAME  = 'beneficiario_credito';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'serial';
    const CREATEDAT  = 'data_criacao';
    const UPDATEDAT  = 'data_modificacao';

    const REGRA_CLIENTE_E_HONORARIOS = 'CLIENTE_E_HONORARIOS';
    const REGRA_SOMENTE_HONORARIOS   = 'SOMENTE_HONORARIOS';
    const REGRA_SOMENTE_CLIENTE      = 'SOMENTE_CLIENTE';

    public static function regras(): array
    {
        return [
            self::REGRA_CLIENTE_E_HONORARIOS => 'Cliente e honorários (honorários = bruto × %)',
            self::REGRA_SOMENTE_HONORARIOS   => 'Somente honorários (sem conta ao cliente)',
            self::REGRA_SOMENTE_CLIENTE      => 'Somente cliente (sem honorários)',
        ];
    }

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        foreach (['nome', 'regra', 'ativo', 'data_criacao', 'criacao_user_id', 'data_modificacao', 'modificacao_user_id'] as $a) {
            parent::addAttribute($a);
        }
    }
}
