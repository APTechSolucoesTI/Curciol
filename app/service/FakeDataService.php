<?php

class FakeDataService
{
    public static function generate()
    {
        $estados  = EstadoAgenda::getObjects();
        $procedimentos = Procedimento::getObjects();
        //$convenios = Convenio::getObjects();
        $agendas = Agenda::getObjects();
        //$exames = Exame::getObjects();
        $materiais = Material::getObjects();
        $tipo_pagamentos = TipoPagamento::getObjects();
        
        for ($i = 0; $i < 2000; $i++)
        {
            $data_ini = '2022-' .  str_pad(rand(1,12), 2, "0", STR_PAD_LEFT) . '-' . str_pad(rand(1,28), 2, "0", STR_PAD_LEFT) . ' ' . str_pad(rand(0,23), 2, "0", STR_PAD_LEFT) . ':' . str_pad(rand(0,59), 2, "0", STR_PAD_LEFT);
            
            $agendamento = new Agendamento;
            $agendamento->cliente_id = ($i%2==0?1:3);
            $agendamento->estado_agenda_id = rand(1, count($estados));
            $agendamento->agenda_id = rand(1, count($agendas));
            $agendamento->escritorio_id = $agendamento->agenda->escritorio_id;
            $agendamento->observacao = ($i%2==0?'':'Obs: ' . $i);
            $agendamento->dt_inicial = $data_ini;
            $agendamento->dt_final = date('Y-m-d H:i', strtotime($data_ini . ' + ' . $agendamento->agenda->duracao . 'minutes'));
            $agendamento->ativo = 'T';
            $agendamento->store();
            
            $total = 0;
            $procedimentosAgendamento = [];
            $atendimento = null;
            
            for ($x = 1; $x <= rand(1, count($procedimentos)); $x++)
            {
                $agendamento_procedimento = new AgendamentoProcedimento;
                $agendamento_procedimento->agendamento_id = $agendamento->id;
                //$agendamento_procedimento->convenio_id = rand(1, count($convenios));
                $agendamento_procedimento->procedimento_id = $x;
                $agendamento_procedimento->quantidade = rand(1, 3);
                $agendamento_procedimento->valor = rand(100,200) . '.' . rand(1,99);
                $agendamento_procedimento->valor_total = $agendamento_procedimento->valor * $agendamento_procedimento->quantidade;
                $agendamento_procedimento->store();
                
                $total += $agendamento_procedimento->valor_total;
                
                $procedimentosAgendamento[] = $agendamento_procedimento;
            }
            
            if (in_array($agendamento->estado_agenda_id, [EstadoAgenda::ATENDIDO, EstadoAgenda::EM_ATENDIMENTO]))
            {
                $atendimento = new Atendimento;
                $atendimento->agendamento_id = $agendamento->id;
                $atendimento->cliente_id = $agendamento->cliente_id;
                $atendimento->convenio_id = $agendamento->convenio_id;
                $atendimento->profissional_id = 2;
                $atendimento->dt_inicio = $agendamento->dt_inicial;
                $atendimento->dt_final = $agendamento->dt_final;
                $atendimento->valor_total = $total;
                $atendimento->store();
             
                if ($procedimentosAgendamento)
                {
                    foreach ($procedimentosAgendamento as $procedimentoAgendamento)
                    {
                        $atendimentoProcedimento = new AtendimentoProcedimento;
                        $atendimentoProcedimento->fromArray($procedimentoAgendamento->toArray());
                        $atendimentoProcedimento->atendimento_id = $atendimento->id;
                        unset($atendimentoProcedimento->id);
                        $atendimentoProcedimento->store();
                    }
                }

                $exame_atendimento = new ExameAtendimento;
                $exame_atendimento->atendimento_id = $atendimento->id;
                //$exame_atendimento->exame_id = rand(1, count($exames));
                $exame_atendimento->indicacao_escritorio = 'Indicação';
                $exame_atendimento->dt_exames = $atendimento->dt_final;
                $exame_atendimento->quantidade = 1;
                $exame_atendimento->store();
                
                $prescricao = new Prescricao;
                $prescricao->controle_especial = 'N';
                $prescricao->atendimento_id = $atendimento->id;
                $prescricao->dt_prescricao = $atendimento->dt_final;
                $prescricao->store();
                
                for($y = 0; $y < rand(1, 4); $y++)
                {
                    $medicamento = new Medicamento;
                    $medicamento->medicamento = 'Medicamento #00' . $y;
                    $medicamento->quantidade = '1 comprimido';
                    $medicamento->posologia = '1x por dia - 15 dias';
                    $medicamento->prescricao_id = $prescricao->id;
                    $medicamento->store();
                }
                
                for($y = 0; $y < rand(1, 4); $y++)
                {
                    $atendimento_material = new AtendimentoMaterial;
                    $atendimento_material->atendimento_id = $atendimento->id;
                    $atendimento_material->material_id = rand(1, count($materiais));
                    $atendimento_material->atendimento_id = $atendimento->id;
                    $atendimento_material->quantidade = 1;
                    
                    $atendimento_material->store();
                }
            }
            
            if ($atendimento)
            {
                $conta = new Conta;
                $conta->pessoa_id = $agendamento->cliente_id;
                $conta->categoria_conta_id = CategoriaConta::RECEITA_DE_SERVICOS;
                $conta->tipo_conta_id = TipoConta::RECEBER;
                $conta->escritorio_id = $agendamento->escritorio_id;
                $conta->atendimento_id = $atendimento->id;
                $conta->data_emissao = $atendimento->dt_inicio;
                $conta->quitada = 'N';
                $conta->descricao = 'Atendimento: ' . $atendimento->id;
                $conta->total_parcelas = 1;
                $conta->total_conta = $total;
                $conta->store();
                
                $lancamento = new Lancamento;
                $lancamento->conta_id = $conta->id;
                $lancamento->parecela = 1;
                $lancamento->dt_vencimento = date('Y-m-d', strtotime($atendimento->dt_inicio . ' + 30 days'));
                $lancamento->conta_id = $conta->id;
                $lancamento->valor = $total;
                $lancamento->tipo_pagamento_id = rand(1, count($tipo_pagamentos));
                $lancamento->dt_pagamento = $i%2==0? null : $atendimento->dt_inicio;
                if($lancamento->dt_pagamento)
                {
                    $conta->quitada = 'S';
                }
                $lancamento->store();
            }
        }
        
        
    }
    public static function generatePessoa(){
        for ($i = 0; $i < 2; $i++)
        {
            $data_ini = '2022-' .  str_pad(rand(1,12), 2, "0", STR_PAD_LEFT) . '-' . str_pad(rand(1,28), 2, "0", STR_PAD_LEFT) . ' ' . str_pad(rand(0,23), 2, "0", STR_PAD_LEFT) . ':' . str_pad(rand(0,59), 2, "0", STR_PAD_LEFT);
        $agendamento = new Agendamento;
            $agendamento->cliente_id = ($i%2==0?1:3);
            $agendamento->estado_agenda_id = 1;
            $agendamento->agenda_id = 1;
            $agendamento->escritorio_id = 1;
            $agendamento->observacao = ($i%2==0?'':'Obs: ' . $i);
            $agendamento->dt_inicial = $data_ini;
            $agendamento->dt_final = date('Y-m-d H:i', strtotime($data_ini . ' + 60 minutes'));
            $agendamento->ativo = 'T';
            $agendamento->store();
        }
        
       //$pessoas = Pessoa::getObjects(); 
       //for ($i = 0; $i < 2000; $i++){
       //    $pessoa = new Pessoa;
       //    $pessoa->tipo_pessoa_id = rand(1,2);
       //    $pessoa->nome = 'Generated #'.$i;
       //    $pessoa->store();
       //    $pessoa_grupo = new PessoaGrupo;
       //    $pessoa_grupo->pessoa_id=$pessoa->id;
       //    $pessoa_grupo->grupo_id=2;
       //    $pessoa_grupo->store();
       //}
    }
}
