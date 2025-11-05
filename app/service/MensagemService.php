<?php

class MensagemService
{
    public static function enviarEmailCredenciais($pessoa, $escritorio)
    {
        //Recebe o template cadastrado no banco de dados
        //parametros (qual o template que precisa, qual escritorio vai mandar)
        $template = TemplateEscritorio::getTemplate('EMAIL_CREDENCIAIS', $escritorio->id);

        if ($template && $template->habilitado == 'T' && $pessoa->email)
        {
            //busca as configurações SMTP da escritorio, necessarias para enviar o email
            $config = EmailConfig::where('escritorio_id', '=', $escritorio->id)->first();
        
            if (empty($config))
            {
                throw new Exception('E-mail não configurado! Veja o cadastro da clínica');
            }
 
            //prepara o objeto ($cliente) para receber os parametros de envio de MensagemService
            //parametro config passado para receber os dados do smtp da escritorio
            $cliente = new EmailClient($config);
            
            //troca o title no template, parametros: (1, 2, 3)
            $title = TemplateEscritorio::replace($pessoa, $template->titulo, $escritorio);
            //troca as variaveis do template por dados para a mensagem, parametros: (1, 2, 3)
            $message = TemplateEscritorio::replace($pessoa, $template->template, $escritorio);
            //PARAMETROS REPLACE
            //1 - $pessoa: objeto que contem os dados da pessoa cadatrada
            //2 - $template->titulo : variavel que contem o texto do titulo (buscado no banco de dados)
            //3 - $escritorio: objeto que contem os dados da escritorio

            //Envia o email de acordo com os dados smtp armazenados em $cliente
            //PARAMETROS:
            //1: destinatário
            //2: titulo
            //3: mensagem
            $cliente->send($pessoa->email, $title, $message);
        }
    }
    
    public static function canEnviarWhatsappLembreteConsulta($agendamento)
    {
        if ($agendamento->estado_agenda_id != EstadoAgenda::CONFIRMADO)
        {
            return false;
        }
        
        $template = TemplateEscritorio::getTemplate('WHATSAPP_LEMBRETE_CONSULTA', TSession::getValue('userunitid'));
        
        if ($template && $template->habilitado == 'T' && $agendamento->cliente->telefone)
        {
            return true;
        }
        
        
        return false;
    }
    
    public static function canEnviarWhatsappConfirmacao($agendamento)
    {
        if ($agendamento->estado_agenda_id != EstadoAgenda::AGENDADO)
        {
            return false;
        }
        
        $template = TemplateEscritorio::getTemplate('WHATSAPP_CONFIRMACAO_AGENDAMENTO', TSession::getValue('userunitid'));
        
        if ($template && $template->habilitado == 'T' && $agendamento->cliente->telefone)
        {
            return true;
        }
        
        
        return false;
    }
    
    public static function canEnviarEmailConfirmacao($agendamento)
    {
        if ($agendamento->estado_agenda_id != EstadoAgenda::AGENDADO)
        {
            return false;
        }
        
        $template = TemplateEscritorio::getTemplate('EMAIL_CONFIRMACAO_AGENDAMENTO', TSession::getValue('userunitid'));
        
        if ($template && $template->habilitado == 'T' && $agendamento->cliente->email)
        {
            return true;
        }
        
        return false;
    }
    
    public static function canEnviarEmailLembreteConsulta($agendamento)
    {
        if ($agendamento->estado_agenda_id != EstadoAgenda::CONFIRMADO)
        {
            return false;
        }
        
        $template = TemplateEscritorio::getTemplate('EMAIL_LEMBRETE_CONSULTA', TSession::getValue('userunitid'));
        
        if ($template && $template->habilitado == 'T' && $agendamento->cliente->email)
        {
            return true;
        }
        
        return false;
    }
    
    public static function enviarWhatsappLembreteConsulta($agendamento)
    {
        $template = TemplateEscritorio::getTemplate('WHATSAPP_LEMBRETE_CONSULTA', TSession::getValue('userunitid'));

        if ($template && $template->habilitado == 'T' && $agendamento->cliente->telefone)
        {
            self::enviarWhatsapp($agendamento, $template->titulo, $template->template, $template->getTemplateAcaos(), $template->id);
        }
    }
    
    public static function enviarEmailConfirmacao($agendamento)
    {
        $template = TemplateEscritorio::getTemplate('EMAIL_CONFIRMACAO_AGENDAMENTO', TSession::getValue('userunitid'));

        if ($template && $template->habilitado == 'T' && $agendamento->cliente->email)
        {
            self::enviarEmail($agendamento, $template->titulo, $template->template, $template->id);
        }
    }
    
    public static function enviarWhatsappConfirmacao($agendamento)
    {
        $template = TemplateEscritorio::getTemplate('WHATSAPP_CONFIRMACAO_AGENDAMENTO', TSession::getValue('userunitid'));

        if ($template && $template->habilitado == 'T' && $agendamento->cliente->telefone)
        {
            self::enviarWhatsapp($agendamento, $template->titulo, $template->template, $template->getTemplateAcaos(), $template->id);
        }
    }
    
    public static function enviarEmailLembreteConsulta($agendamento)
    {
        $template = TemplateEscritorio::getTemplate('EMAIL_LEMBRETE_CONSULTA', TSession::getValue('userunitid'));

        if ($template && $template->habilitado == 'T' && $agendamento->cliente->email)
        {
            self::enviarEmail($agendamento, $template->titulo, $template->template, $template->id);
        }
    }
    
    
    public static function enviarWhatsapp($agendamento, $titulo, $template, $acoes, $template_escritorio_id)
    {
        $config = WhatsappConfig::where('(SELECT system_unit_id FROM escritorio WHERE escritorio.id = escritorio_id)', '=', TSession::getValue('userunitid'))->first();
        
        if (empty($config))
        {
            throw new Exception('WhatsApp não configurado! Veja o cadastro da clínica');
        }
        
        if ($agendamento->cliente->aceita_receber_mensagen_whatsapp != 'T')
        {
            throw new Exception('O cliente não aceita receber mensagens de whatsapp');
        }
        
        $cliente = new WhatsAppClient($config);
        
        $title = TemplateEscritorio::replace($agendamento, $titulo);
        $message = TemplateEscritorio::replace($agendamento, $template);
        
        $mensagem = new Mensagem();
        $mensagem->agendamento_id = $agendamento->id;
        $mensagem->template_escritorio_id = $template_escritorio_id;
        $mensagem->system_user_id = TSession::getValue('userid');
        $mensagem->titulo = $title;
        $mensagem->tipo_mensagem = TemplateEscritorio::WHATSAPP;
        $mensagem->template = $message;
        $mensagem->store();
        
        $actions = [];
        
        if ($acoes)
        {
            foreach ($acoes as $acao)
            {
                $acao->url = TemplateEscritorio::replace($agendamento, $acao->url);
                $acao->label = TemplateEscritorio::replace($agendamento, $acao->label);
                
                $actions[$acao->url] = $acao->label;
                
                $mensageAcao = new MensagemAcao;
                $mensageAcao->mensagem_id = $mensagem->id;
                $mensageAcao->label = $acao->label;
                $mensageAcao->url = $acao->url;
                $mensageAcao->store();
            }
        }
        
        $cliente->send($agendamento->cliente->telefone, $message, $title, $actions);
    }
    
    public static function enviarEmail($agendamento, $titulo, $template, $template_escritorio_id)
    {
        $config = EmailConfig::where('escritorio_id', '=', TSession::getValue('userunitid'))->first();
        
        if (empty($config))
        {
            throw new Exception('E-mail não configurado! Veja o cadastro da clínica');
        }
        
        $cliente = new EmailClient($config);
        
        $title = TemplateEscritorio::replace($agendamento, $titulo);
        $message = TemplateEscritorio::replace($agendamento, $template);
        
        $mensagem = new Mensagem();
        $mensagem->agendamento_id = $agendamento->id;
        $mensagem->template_escritorio_id = $template_escritorio_id;
        $mensagem->system_user_id = TSession::getValue('userid');
        $mensagem->titulo = $title;
        $mensagem->tipo_mensagem = TemplateEscritorio::EMAIL;
        $mensagem->template = $message;
        $mensagem->store();
        
        $cliente->send($agendamento->cliente->email, $title, $message);
    }
}
