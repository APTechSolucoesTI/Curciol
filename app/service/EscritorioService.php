<?php

class EscritorioService
{
   public static function criaTemplatesPadroes($escritorio_id)
   {
        $templateEscritorio = new TemplateEscritorio();
        $templateEscritorio->chave = 'EMAIL_CONFIRMACAO_AGENDAMENTO';
        $templateEscritorio->descricao = 'Confirmação de agendamento';
        $templateEscritorio->escritorio_id = $escritorio_id;
        $templateEscritorio->habilitado = 'T';
        $templateEscritorio->template = '<p>Olá <b>{$cliente}</b>,</p><p>Temos um agendamento marcado com você em: {$data_inicial}, com<span style="color: var(--text-color); font-family: var(--font-family); font-size: var(--font-size);"> {$profissional}.</span></p><p style="line-height: 1;"><br></p><p style="line-height: 1;">Você deseja:</p><p style="line-height: 1;"><a style="border: 1px solid grey; border-radius: 4px; padding: 10px 15px; background-color: #4caf50; color: white;" href="{$link_confirmacao}" target="_blank">Confirmar agendamento</a> <a style="border: 1px solid grey; border-radius: 4px; padding: 10px 15px; background-color: #f44336; color: white;" href="{$link_cancelamento}" target="_blank">Cancelar agendamento</a><br></p><p style="line-height: 1;"><br></p><p style="line-height: 1;"><span style="font-size: 11px; font-family: Arial;">{$escritorio} - </span><span style="color: var(--text-color); font-family: Arial; font-size: 11px;">{$email_escritorio} - </span><span style="font-family: Arial; font-size: 11px; color: var(--text-color);">{$telefone_escritorio}</span></p>';
        $templateEscritorio->titulo = 'Confirmação de agendamento';
        $templateEscritorio->tipo_template = 'EMAIL';
        $templateEscritorio->readonly = 'T';
        $templateEscritorio->store();
        
        $templateAcao = new TemplateAcao();
        $templateAcao->url = '{$link_confirmacao}';
        $templateAcao->label = 'Confirmar';
        $templateAcao->template_escritorio_id = $templateEscritorio->id;
        $templateAcao->store();
        
        $templateAcao = new TemplateAcao();
        $templateAcao->url = '{$link_cancelamento}';
        $templateAcao->label = 'Cancelar';
        $templateAcao->template_escritorio_id = $templateEscritorio->id;
        $templateAcao->store();
        
        $templateEscritorio = new TemplateEscritorio();
        $templateEscritorio->chave = 'EMAIL_LEMBRETE_CONSULTA';
        $templateEscritorio->descricao = 'Aviso de proximidade de consulta';
        $templateEscritorio->escritorio_id = $escritorio_id;
        $templateEscritorio->habilitado = 'T';
        $templateEscritorio->template = '<p>Olá <b>{$cliente}</b>,</p><p>Lembramos que você tem um agendamento marcado em: {$data_inicial}, com<span style="color: var(--text-color); font-family: var(--font-family); font-size: var(--font-size);"> {$profissional}.</span></p><p style="line-height: 1;"><br></p><p style="line-height: 1;"><a style="border: 1px solid grey; border-radius: 4px; padding: 10px 15px;" href="{$link_detalhe}" target="_blank">Abrir consulta</a><br></p><p style="line-height: 1;"><br></p><p style="line-height: 1;"><span style="font-size: 11px; font-family: Arial;">{$escritorio} - </span><span style="color: var(--text-color); font-family: Arial; font-size: 11px;">{$email_escritorio} - </span><span style="font-family: Arial; font-size: 11px; color: var(--text-color);">{$telefone_escritorio}</span></p>';
        $templateEscritorio->titulo = 'Lembrete de consulta';
        $templateEscritorio->tipo_template = 'EMAIL';
        $templateEscritorio->readonly = 'T';
        $templateEscritorio->store();
        
        $templateAcao = new TemplateAcao();
        $templateAcao->url = '{$link_detalhe}';
        $templateAcao->label = 'Ver agendamento';
        $templateAcao->template_escritorio_id = $templateEscritorio->id;
        $templateAcao->store();
        
        $templateEscritorio = new TemplateEscritorio();
        $templateEscritorio->chave = 'WHATSAPP_LEMBRETE_CONSULTA';
        $templateEscritorio->descricao = 'Aviso de proximidade de consulta';
        $templateEscritorio->escritorio_id = $escritorio_id;
        $templateEscritorio->habilitado = 'T';
        $templateEscritorio->template = 'Olá {$cliente}. Lembramos que seu agendamento está confirmado para {$data_inicial}';
        $templateEscritorio->titulo = 'Lembrete de consulta';
        $templateEscritorio->tipo_template = 'WHATSAPP';
        $templateEscritorio->readonly = 'T';
        $templateEscritorio->store();
        
        $templateAcao = new TemplateAcao();
        $templateAcao->url = '{$link_detalhe}';
        $templateAcao->label = 'Ver agendamento';
        $templateAcao->template_escritorio_id = $templateEscritorio->id;
        $templateAcao->store();
        
        $templateEscritorio = new TemplateEscritorio();
        $templateEscritorio->chave = 'WHATSAPP_CONFIRMACAO_AGENDAMENTO';
        $templateEscritorio->descricao = 'Confirmação de agendamento';
        $templateEscritorio->escritorio_id = $escritorio_id;
        $templateEscritorio->habilitado = 'T';
        // sem links: o cliente responde SIM ou NAO na conversa do WhatsApp (03/11/2026)
        $templateEscritorio->template = "Olá {\$cliente}. Temos agendado para {\$data_inicial}, com {\$profissional}.\n\nResponda *SIM* para confirmar ou *NÃO* para cancelar.";
        $templateEscritorio->titulo = 'Confirmação de agendamento';
        $templateEscritorio->tipo_template = 'WHATSAPP';
        $templateEscritorio->readonly = 'T';
        $templateEscritorio->store();

        // mensagens automaticas depois da resposta SIM/NAO (ApchatRespostaService)
        $respostas = [
            ['WHATSAPP_RESPOSTA_SIM', 'Resposta SIM: confirmação recebida', 'Confirmação recebida',
             'Obrigado, {$cliente}! Seu agendamento para {$data_inicial}, com {$profissional}, está confirmado.'],
            ['WHATSAPP_RESPOSTA_NAO', 'Resposta NÃO: agradecimento', 'Agendamento cancelado',
             'Obrigado por avisar, {$cliente}. Seu agendamento de {$data_inicial} foi cancelado.'],
        ];
        foreach ($respostas as [$chave, $descricao, $titulo, $texto])
        {
            $templateEscritorio = new TemplateEscritorio();
            $templateEscritorio->chave = $chave;
            $templateEscritorio->descricao = $descricao;
            $templateEscritorio->escritorio_id = $escritorio_id;
            $templateEscritorio->habilitado = 'T';
            $templateEscritorio->template = $texto;
            $templateEscritorio->titulo = $titulo;
            $templateEscritorio->tipo_template = 'WHATSAPP';
            $templateEscritorio->readonly = 'T';
            $templateEscritorio->store();
        }

        // categorias e fluxo da confirmacao, os mesmos da migracao 2026-11-04-templates-fluxo-historico.sql
        $c = TTransaction::get();
        $st = $c->prepare("UPDATE template_escritorio SET categoria = CASE
                               WHEN chave LIKE '%\\_CONFIRMACAO\\_AGENDAMENTO' THEN 'CONFIRMACAO'
                               WHEN chave LIKE '%\\_LEMBRETE\\_CONSULTA' THEN 'LEMBRETE'
                               WHEN chave = 'WHATSAPP_RESPOSTA_SIM' THEN 'CONFIRMADO'
                               WHEN chave = 'WHATSAPP_RESPOSTA_NAO' THEN 'CANCELAMENTO'
                               ELSE 'OUTROS' END WHERE escritorio_id = ?");
        $st->execute([$escritorio_id]);
        $id = function ($chave) use ($escritorio_id) { $t = TemplateEscritorio::where('escritorio_id', '=', $escritorio_id)->where('chave', '=', $chave)->first(); return $t ? $t->id : null; };
        $confirmacao = TemplateEscritorio::where('escritorio_id', '=', $escritorio_id)->where('chave', '=', 'WHATSAPP_CONFIRMACAO_AGENDAMENTO')->first();
        $confirmacao->respostas_confirmar = 'sim';
        $confirmacao->respostas_cancelar = 'não';
        $confirmacao->ignorar_maiusculas = 'T';
        $confirmacao->template_confirmado_id = $id('WHATSAPP_RESPOSTA_SIM');
        $confirmacao->template_cancelado_id = $id('WHATSAPP_RESPOSTA_NAO');
        $confirmacao->store();
   }
}
