<?php

class AtendimentoService
{
    public static function podeManipular(Atendimento $object, $userId)
    {
        $isProfissionalResponsavel = $object->agendamento->agenda->profissional->system_users_id == $userId;
        
        $pessoa = Pessoa::where('system_users_id','=', $userId)->first();
        
        $isProfissionalVinculado = false;
        
        if($pessoa)
        {
            $isProfissionalVinculado = AgendaProfissional::where('agenda_id', '=', $object->agendamento->agenda_id)->where('profissional_id', '=', $pessoa->id)->where('fl_manipula_atendimento', '=', 'S')->first();        
        }
        
        return $isProfissionalResponsavel || $isProfissionalVinculado;
    }
    
    
    public static function adicionarEstoque(AtendimentoMaterial $atendimentoMaterial, $userId)
    {
        $movimentacao = new Movimentacao();
        $movimentacao->dt_movimentacao = date('Y-m-d H:i:s');
        $movimentacao->material_id = $atendimentoMaterial->material_id;
        $movimentacao->quantidade = $atendimentoMaterial->quantidade;
        $movimentacao->system_user_id = $userId;
        $movimentacao->store();
        
        $atendimentoMaterial->material->estoque_atualizado += $atendimentoMaterial->quantidade;
        $atendimentoMaterial->material->store();
    }
    
    public static function subtrairEstoque(AtendimentoMaterial $atendimentoMaterial, $userId)
    {
        $movimentacao = new Movimentacao();
        $movimentacao->dt_movimentacao = date('Y-m-d H:i:s');
        $movimentacao->material_id = $atendimentoMaterial->material_id;
        $movimentacao->quantidade = -$atendimentoMaterial->quantidade;
        $movimentacao->system_user_id = $userId;
        $movimentacao->store();
        
        $atendimentoMaterial->material->estoque_atualizado -= $atendimentoMaterial->quantidade;
        $atendimentoMaterial->material->store();
    }
    
    public static function iniciarAtendimento(Agendamento $agendamento)
    {
        $enderecoPrincipal = PessoaEndereco::where('pessoa_id','=',$agendamento->cliente_id)
                                            ->where('principal','=','S')
                                            ->first();
                                            
        if(!$enderecoPrincipal){
            throw new Exception("Cadastre um endereço principal para iniciar um atendimento.");
        }
        
        $atendimento = Atendimento::where('agendamento_id','=',$agendamento->id)->first();
        $atendimento->dt_inicio = date('Y-m-d H:i:s');
        $atendimento->store();
    
        return $atendimento;
    }
    
    public static function gerarContaReceber($atendimento)
    {
        
        $escritorio = Escritorio::where('system_unit_id', '=', TSession::getValue('userunitid'))->first();
        
        $conta = new Conta();
        $conta->atendimento_id = $atendimento->id;
        $conta->pessoa_id = $atendimento->cliente_id;
        $conta->profissional_id = $atendimento->profissional_id;
        $conta->categoria_conta_id = CategoriaConta::RECEITA_DE_SERVICOS;
        $conta->tipo_conta_id = TipoConta::RECEBER;
        
        $searchTipoDoc = TipoDocumentoFinanceiro::where('padrao_id','=',TipoDocFinanceiroPadrao::ATENDIMENTO)->load();
        foreach($searchTipoDoc as $resultTipoDoc){
            if($resultTipoDoc->tipo_conta_id == TipoConta::RECEBER || $resultTipoDoc->tipo_conta_id == TipoConta::AMBOS){
                $conta->tipo_documento_financeiro_id = $resultTipoDoc->id;
            }
        }
        
        $conta->escritorio_id = $escritorio->id;
        $conta->data_emissao = date('Y-m-d');
        $conta->total_parcelas = 1;
        $conta->quitada = 'N';
        $conta->descricao = "Atendimento: #{$atendimento->id}";
        $conta->conta_origem_id = $atendimento->id;
        $conta->total_conta = $atendimento->valor_total;
        $conta->criacao_user_id = TSession::getValue('userid');
        $conta->store();
        
        $lancamento = new Lancamento();
        $lancamento->conta_id = $conta->id;
        $lancamento->tipo_pagamento_id = TipoPagamento::DINHEIRO;
        $lancamento->dt_vencimento = date('Y-m-d');
        $lancamento->parcela = 1;
        $lancamento->valor = $conta->total_conta;
        $lancamento->store();
    }
}
