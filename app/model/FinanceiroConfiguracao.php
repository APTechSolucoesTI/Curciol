<?php

class FinanceiroConfiguracao extends TRecord
{
    const TABLENAME  = 'financeiro_configuracao';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'serial'; // {max, serial}

    const UPDATED_BY_USER_ID = 'modificacao_user_id';
    const UPDATEDAT          = 'data_modificacao';

    const ID_UNICO     = 1;
    const PCT_NF_PADRAO = 16.0;

    use SystemChangeLogTrait;

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('pct_nf_padrao');
        parent::addAttribute('repasse_dias_vencimento');
        parent::addAttribute('repasse_categoria_conta_id');
        parent::addAttribute('repasse_tipo_documento_id');
        parent::addAttribute('data_modificacao');
        parent::addAttribute('modificacao_user_id');
    }

    /** % de custo de NF padrao do levantamento (16 se a configuracao nao existir). Usa a transacao aberta ou abre a propria. */
    public static function pctNfPadrao(): float
    {
        $propria = (TTransaction::get() === null);
        if ($propria) { TTransaction::open('escritorio'); }
        try {
            $c = self::find(self::ID_UNICO);
            if ($propria) { TTransaction::close(); }
        } catch (Throwable $e) {
            if (!$propria) { throw $e; } // transacao do chamador ficou abortada: nao mascarar
            TTransaction::rollback();
            return self::PCT_NF_PADRAO;
        }
        return ($c && $c->pct_nf_padrao !== null && $c->pct_nf_padrao !== '') ? (float) $c->pct_nf_padrao : self::PCT_NF_PADRAO;
    }

    /** Configuracao do repasse a colaboradores: ['dias' => int, 'categoria_id' => ?int, 'documento_id' => ?int]. Usa a transacao aberta. */
    public static function repasse(): array
    {
        $c = self::find(self::ID_UNICO);
        return [
            'dias' => $c ? max(0, (int) $c->repasse_dias_vencimento) : 0,
            'categoria_id' => ($c && $c->repasse_categoria_conta_id) ? (int) $c->repasse_categoria_conta_id : null,
            'documento_id' => ($c && $c->repasse_tipo_documento_id) ? (int) $c->repasse_tipo_documento_id : null,
        ];
    }
}
